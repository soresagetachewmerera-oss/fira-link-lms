#!/usr/bin/env python3
"""
Deploy the Fira Link Laravel app (fira-link.com / lms.fira-link.com) to the cPanel server.

Safe by default:
  * Code only. Database migrations run only with --migrate, after a typed confirmation that a
    database backup exists. Without --migrate the deploy stops if the server is missing a migration.
  * The server's SSH host key must already be in ~/.ssh/known_hosts (connect once with `ssh` and
    check the fingerprint with the host). Unknown keys are refused.
  * Local-only files never leave this computer: .env, storage/ (uploads, private documents, logs),
    the local SQLite database, tests, bootstrap/cache and the one-off root scripts.
  * The current server code (without storage/ and vendor/) is archived on the server first, so the
    previous version can be restored.
  * vendor/ is not uploaded. If composer.lock differs from the server's copy the deploy stops:
    run `composer install --no-dev --optimize-autoloader` on the server first.
  * Every server command must succeed; the first failure stops the deploy and is reported.
    Success is printed only when every step and every page check passed.

Usage:
  python scripts/deploy_lms_production.py --dry-run     build the archive and list what would be sent
  python scripts/deploy_lms_production.py               deploy code (no migrations)
  python scripts/deploy_lms_production.py --migrate     deploy code and run reviewed migrations
"""
import argparse
import hashlib
import re
import sys
import tarfile
import tempfile
import time
from datetime import datetime
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8", errors="replace", line_buffering=True)

LOCAL_LMS = Path(__file__).resolve().parent.parent / "firalink web" / "firalink"
KEY_FILE = Path.home() / ".ssh" / "id_rsa_firalink"

HOST = "37.26.105.27"
USER = "firalijh"
REMOTE_HOME = "/home/firalijh"
REMOTE_PATH = f"{REMOTE_HOME}/lms.fira-link.com"
BACKUP_DIR = f"{REMOTE_HOME}/deploy-backups"

CHECK_URLS = [
    "https://fira-link.com",
    "https://fira-link.com/marketplace",
    "https://fira-link.com/events",
    "https://fira-link.com/tours",
]

# Top-level directories that are never uploaded.
SKIP_TOP_DIRS = {
    "vendor", "node_modules", "storage", "tests", ".git", ".idea", ".vscode", "eduex_full",
}
# Paths (relative, posix) that are never uploaded.
SKIP_PATHS = {
    "bootstrap/cache", "public/storage", "public/hot", "database/database.sqlite",
}
# One-off scripts in the project root that write to the database when run.
SKIP_ROOT_FILES = {
    "audit_assets.php", "audit_repo.php", "fix_local_urls.php", "test_booking_payment.php",
    "update_company_branding.php", "update_ethiopian_content.php", "scratch_capture_all_tabs.py",
    "readme!.html", "php.ini",
}
SKIP_NAMES = {"__pycache__", ".DS_Store", "Thumbs.db", "laravel.log", ".phpunit.result.cache"}
SKIP_SUFFIXES = {".mov", ".mp4", ".zip", ".tar", ".tgz", ".gz", ".sqlite", ".wal", ".shm", ".log"}


def is_skipped(rel: Path, with_assets: bool = False) -> bool:
    posix = rel.as_posix()
    parts = rel.parts
    if parts[0] in SKIP_TOP_DIRS:
        return True
    if len(parts) == 1 and parts[0] in SKIP_ROOT_FILES:
        return True
    if any(p.startswith(".env") for p in parts):
        return True
    if not with_assets:
        if (posix == "public/assets" or posix.startswith("public/assets/")) and not posix.startswith("public/assets/front/css"):
            return True
        if posix == "public/videos" or posix.startswith("public/videos/"):
            return True
        if posix == "public/themes" or posix.startswith("public/themes/"):
            return True
    if any(posix == s or posix.startswith(s + "/") for s in SKIP_PATHS):
        return True
    if any(p in SKIP_NAMES for p in parts):
        return True
    return rel.suffix.lower() in SKIP_SUFFIXES


def build_archive(tar_path: Path, with_assets: bool = False) -> int:
    print(f"[1] Packaging {LOCAL_LMS} ...")
    count = 0
    with tarfile.open(tar_path, "w:gz") as tar:
        for path in sorted(LOCAL_LMS.rglob("*")):
            if not path.is_file():
                continue
            rel = path.relative_to(LOCAL_LMS)
            if is_skipped(rel, with_assets=with_assets):
                continue
            tar.add(path, arcname=rel.as_posix())
            count += 1
    print(f"    {count} files, {tar_path.stat().st_size / 1024 / 1024:.2f} MB")
    return count


def local_migrations() -> list[str]:
    return sorted(p.stem for p in (LOCAL_LMS / "database" / "migrations").glob("*.php"))


def sha256(path: Path) -> str:
    return hashlib.sha256(path.read_bytes().replace(b"\r\n", b"\n")).hexdigest()


class Remote:
    def __init__(self):
        import shutil
        self.use_cli = bool(shutil.which("ssh") and shutil.which("scp"))
        self.ssh = None

        if not self.use_cli:
            import paramiko
            self.ssh = paramiko.SSHClient()
            self.ssh.load_system_host_keys()
            known = Path.home() / ".ssh" / "known_hosts"
            if known.exists():
                self.ssh.load_host_keys(str(known))
            self.ssh.set_missing_host_key_policy(paramiko.RejectPolicy())
            key = paramiko.RSAKey.from_private_key_file(str(KEY_FILE))
            try:
                self.ssh.connect(HOST, port=22, username=USER, pkey=key, timeout=30)
                if self.ssh.get_transport():
                    self.ssh.get_transport().set_keepalive(15)
            except paramiko.SSHException as e:
                raise SystemExit(
                    f"SSH refused: {e}\nIf the host key is unknown, connect once with `ssh {USER}@{HOST}`, "
                    "compare the fingerprint with the hosting provider, then run this again."
                )

    def ensure_connected(self):
        if self.use_cli:
            return
        transport = self.ssh.get_transport()
        if transport is None or not transport.is_active():
            print("    reconnecting SSH session ...")
            import paramiko
            key = paramiko.RSAKey.from_private_key_file(str(KEY_FILE))
            self.ssh.connect(HOST, port=22, username=USER, pkey=key, timeout=30)
            if self.ssh.get_transport():
                self.ssh.get_transport().set_keepalive(15)

    def run(self, cmd: str, quiet: bool = False) -> tuple[int, str]:
        import shutil
        import subprocess
        if self.use_cli or shutil.which("ssh"):
            if not quiet:
                print(f"\n>> {cmd}")
            ssh_cmd = [
                "ssh",
                "-i", str(KEY_FILE),
                "-o", "StrictHostKeyChecking=accept-new",
                "-o", "BatchMode=yes",
                "-o", "ConnectTimeout=30",
                f"{USER}@{HOST}",
                cmd,
            ]
            res = subprocess.run(
                ssh_cmd,
                stdin=subprocess.DEVNULL,
                capture_output=True,
                text=True,
                encoding="utf-8",
                errors="replace"
            )
            output = res.stdout + res.stderr
            if not quiet and output:
                sys.stdout.write(output)
                sys.stdout.flush()
            return res.returncode, output

        self.ensure_connected()
        if not quiet:
            print(f"\n>> {cmd}")
        stdin, stdout, stderr = self.ssh.exec_command(cmd)
        chunks = []
        for line in iter(stdout.readline, ""):
            chunks.append(line)
            if not quiet:
                sys.stdout.write(line)
                sys.stdout.flush()
        err = stderr.read().decode("utf-8", "replace")
        if err:
            chunks.append(err)
            if not quiet:
                sys.stdout.write(err)
                sys.stdout.flush()
        code = stdout.channel.recv_exit_status()
        return code, "".join(chunks)

    def must(self, cmd: str, quiet: bool = False) -> str:
        code, out = self.run(cmd, quiet)
        if code != 0:
            if quiet:
                print(out)
            raise DeployError(f"Command failed with exit code {code}: {cmd}")
        return out

    def upload(self, local: Path, remote: str):
        mb = local.stat().st_size / 1024 / 1024
        print(f"    uploading {local.name} ({mb:.2f} MB) ...")
        import shutil
        import subprocess
        if shutil.which("scp"):
            cmd = [
                "scp", "-O",
                "-i", str(KEY_FILE),
                "-o", "StrictHostKeyChecking=accept-new",
                "-o", "BatchMode=yes",
                str(local),
                f"{USER}@{HOST}:{remote}",
            ]
            res = subprocess.run(cmd, stdin=subprocess.DEVNULL, capture_output=True, text=True)
            if res.returncode == 0:
                print("    upload complete (via scp).")
                return
            print(f"    scp failed ({res.stderr.strip()}), falling back to SFTP ...")
        self.ensure_connected()
        sftp = self.ssh.open_sftp()
        try:
            sftp.put(str(local), remote)
            print("    upload complete (via SFTP).")
        finally:
            sftp.close()

    def close(self):
        if self.ssh:
            self.ssh.close()


class DeployError(Exception):
    pass


def ran_migrations(remote: Remote) -> set[str]:
    out = remote.must(f"cd {REMOTE_PATH} && php artisan migrate:status --no-ansi", quiet=True)
    ran = set()
    for line in out.splitlines():
        m = re.match(r"\s*(\d{4}_\d{2}_\d{2}_\d{6}_\S+)\s.*\bRan\b", line)
        if m:
            ran.add(m.group(1))
    if not ran:
        raise DeployError("Could not read the server's migration status; nothing was changed.")
    return ran


def check_pages(remote: Remote) -> bool:
    ok = True
    for url in CHECK_URLS:
        _, out = remote.run(f"curl -s -o /dev/null -L -w '%{{http_code}}' --max-time 30 {url}", quiet=True)
        status = out.strip()[-3:]
        good = status == "200"
        ok = ok and good
        print(f"    {'OK  ' if good else 'FAIL'} {status} {url}")
    return ok


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--dry-run", action="store_true", help="build the archive and stop; no connection")
    parser.add_argument("--migrate", action="store_true", help="also run pending migrations (asks for confirmation)")
    parser.add_argument("--yes", "-y", action="store_true", help="skip interactive prompt when --migrate is passed")
    args = parser.parse_args()

    stamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    tar_path = Path(tempfile.gettempdir()) / f"firalink-lms-{stamp}.tgz"
    build_archive(tar_path)

    if args.dry_run:
        with tarfile.open(tar_path) as tar:
            names = tar.getnames()
        tops = sorted({n.split("/")[0] for n in names})
        print("    top-level entries:", ", ".join(tops))
        leaked = [n for n in names if n.startswith(("storage/", "tests/", "vendor/")) or ".env" in n.split("/")[-1]]
        print(f"    storage/, tests/, vendor/ or .env files in archive: {len(leaked)}")
        print(f"    local migrations: {len(local_migrations())}")
        print(f"\nDry run only. Archive left at {tar_path} for inspection. Nothing was sent.")
        return

    remote = Remote()
    in_maintenance = False
    try:
        print("\n[2] Checking the server before changing anything ...")
        server_ran = ran_migrations(remote)
        pending = [m for m in local_migrations() if m not in server_ran]
        if pending:
            print(f"    {len(pending)} migration(s) not yet run on the server:")
            for m in pending:
                print(f"      - {m}")
            if not args.migrate:
                raise DeployError(
                    "The new code needs these migrations. Review them (see CLAUDE.md, one-time production "
                    "tasks), take a database backup, then run again with --migrate. Nothing was changed."
                )
            if not args.yes:
                print("\n    Before continuing you need a database backup taken today and a tested restore.")
                answer = input("    Type MIGRATE to confirm the backup exists and run these migrations: ")
                if answer.strip() != "MIGRATE":
                    raise DeployError("Not confirmed. Nothing was changed.")
        else:
            print("    No pending migrations.")

        _, out = remote.run(f"tr -d '\\r' < {REMOTE_PATH}/composer.lock | sha256sum", quiet=True)
        if out.split()[:1] != [sha256(LOCAL_LMS / "composer.lock")]:
            raise DeployError(
                "composer.lock differs from the server's copy. Install the same dependencies on the server "
                "(composer install --no-dev --optimize-autoloader) before deploying. Nothing was changed."
            )

        print("\n[3] Archiving the current server code for rollback ...")
        backup = f"{BACKUP_DIR}/lms-{stamp}.tgz"
        remote.must(f"mkdir -p {BACKUP_DIR} && tar -czf {backup} --exclude=./storage --exclude=./vendor --exclude=./public/assets --exclude=./public/videos --exclude=./public/themes --exclude=./node_modules --exclude=./.git -C {REMOTE_PATH} .")
        print(f"    Code: {backup}")

        print("\n[4] Uploading ...")
        remote_tar = f"{REMOTE_HOME}/firalink-lms-deploy-{stamp}.tgz"
        remote.upload(tar_path, remote_tar)

        print("\n[5] Installing ...")
        remote.must(f"cd {REMOTE_PATH} && php artisan down --retry=60")
        in_maintenance = True
        remote.must(f"tar -xzf {remote_tar} -C {REMOTE_PATH} && rm -f {remote_tar}")
        if pending:
            remote.must(f"cd {REMOTE_PATH} && php artisan migrate --force")
        remote.must(f"cd {REMOTE_PATH} && php artisan optimize:clear")
        remote.must(f"cd {REMOTE_PATH} && php artisan config:cache && php artisan route:cache && php artisan view:cache")
        remote.must(f"cd {REMOTE_PATH} && php artisan queue:restart")
        remote.must(f"cd {REMOTE_PATH} && php artisan up")
        in_maintenance = False

        print("\n[6] Checking pages ...")
        if not check_pages(remote):
            raise DeployError("The code is installed but at least one page did not answer 200. Check the site now.")
        print("\nDeploy finished: every step succeeded and every page answered 200.")
        print(f"Rollback archive: {backup}")
    except DeployError as e:
        print(f"\nDEPLOY STOPPED: {e}")
        if in_maintenance:
            print("The site is still in maintenance mode. Fix the problem, or restore the code with\n"
                  f"  tar -xzf <rollback archive> -C {REMOTE_PATH}\n"
                  f"then run `php artisan optimize:clear && php artisan up` in {REMOTE_PATH}.")
        sys.exit(1)
    finally:
        remote.close()


if __name__ == "__main__":
    main()

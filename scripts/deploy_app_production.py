#!/usr/bin/env python3
"""
Deploy Fira Link Flutter Web App to cPanel at app.fira-link.com
"""
import os
import subprocess
import sys
import tarfile
import tempfile
import time
from pathlib import Path
import paramiko

sys.stdout.reconfigure(encoding="utf-8", errors="replace", line_buffering=True)

BUILD_WEB_DIR = Path(r"D:\.gradle\fira-link-lms\firalink flutter\build\web")
KEY_FILE = Path(r"C:\Users\sora\.ssh\id_rsa_firalink")

HOST = "37.26.105.27"
USER = "firalijh"
REMOTE_PATH = "/home/firalijh/app.fira-link.com"

HTACCESS_CONTENT = """<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
"""

def prepare_app_archive(tar_path: Path):
    if not BUILD_WEB_DIR.exists():
        raise FileNotFoundError(f"Directory {BUILD_WEB_DIR} does not exist. Run flutter build web first.")

    # Ensure .htaccess exists
    htaccess_file = BUILD_WEB_DIR / ".htaccess"
    if not htaccess_file.exists() or htaccess_file.stat().st_size == 0:
        with open(htaccess_file, "w", encoding="utf-8") as f:
            f.write(HTACCESS_CONTENT)
        print("  Added .htaccess for SPA routing.")

    print(f"[1/4] Packaging Flutter web build into {tar_path}...")
    count = 0
    with tarfile.open(tar_path, "w:gz") as tar:
        for path in BUILD_WEB_DIR.rglob("*"):
            if not path.is_file():
                continue
            if path.name.endswith(".symbols"):
                continue
            rel = path.relative_to(BUILD_WEB_DIR).as_posix()
            tar.add(path, arcname=rel)
            count += 1

    size_mb = tar_path.stat().st_size / 1024 / 1024
    print(f"  Tarball completed: {count} files, {size_mb:.2f} MB")

def main():
    temp_dir = Path(tempfile.gettempdir())
    tar_path = temp_dir / "firalink-app-deploy.tgz"
    if tar_path.exists():
        tar_path.unlink()

    prepare_app_archive(tar_path)

    print(f"\n[2/4] Connecting SSH to {USER}@{HOST}...")
    key = paramiko.RSAKey.from_private_key_file(str(KEY_FILE))
    ssh = paramiko.SSHClient()
    # The host key must already be in ~/.ssh/known_hosts; unknown keys are refused.
    ssh.load_system_host_keys()
    known = Path.home() / ".ssh" / "known_hosts"
    if known.exists():
        ssh.load_host_keys(str(known))
    ssh.set_missing_host_key_policy(paramiko.RejectPolicy())
    ssh.connect(HOST, port=22, username=USER, pkey=key, timeout=30)

    remote_tar = "/home/firalijh/firalink-app-deploy.tgz"
    print(f"\n[3/4] Uploading {tar_path.name} ({tar_path.stat().st_size / 1024 / 1024:.2f} MB) via scp -O...")
    scp_cmd = ["scp", "-O", "-i", str(KEY_FILE), "-o", "StrictHostKeyChecking=accept-new", str(tar_path), f"{USER}@{HOST}:{remote_tar}"]
    res = subprocess.run(scp_cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print(f"DEPLOY STOPPED: Upload failed: {res.stderr or res.stdout}")
        ssh.close()
        sys.exit(1)
    print("  Upload completed successfully.")

    def run_cmd(cmd):
        print("\n" + "=" * 60)
        print(">>", cmd)
        stdin, stdout, stderr = ssh.exec_command(cmd)
        chunks = []
        for line in iter(stdout.readline, ""):
            chunks.append(line)
            sys.stdout.write(line)
            sys.stdout.flush()
        err = stderr.read().decode("utf-8", "replace")
        if err:
            chunks.append(err)
            sys.stdout.write(err)
            sys.stdout.flush()
        code = stdout.channel.recv_exit_status()
        print(f"[Exit code: {code}]")
        return code

    print(f"\n[4/4] Extracting Flutter Web files onto {REMOTE_PATH}...")
    commands = [
        f"mkdir -p {REMOTE_PATH}",
        f"tar -xzf {remote_tar} -C {REMOTE_PATH}",
        f"rm -f {remote_tar}",
        "test \"$(curl -s -o /dev/null -L -w '%{http_code}' --max-time 30 https://app.fira-link.com)\" = 200",
    ]

    for c in commands:
        code = run_cmd(c)
        if code != 0:
            ssh.close()
            print(f"\nDEPLOY STOPPED: command failed with exit code {code}: {c}")
            sys.exit(1)

    ssh.close()
    print("\nApp deploy finished: every command succeeded and https://app.fira-link.com answered 200.")

if __name__ == "__main__":
    main()

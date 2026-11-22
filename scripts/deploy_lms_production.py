#!/usr/bin/env python3
"""
Deploy Fira Link LMS & Web to cPanel at lms.fira-link.com / fira-link.com
"""
import os
import sys
import tarfile
import tempfile
import time
from pathlib import Path
import paramiko

sys.stdout.reconfigure(encoding="utf-8", errors="replace", line_buffering=True)

LOCAL_LMS = Path(r"D:\.gradle\fira-link-lms\firalink web\firalink")
KEY_FILE = Path(r"C:\Users\sora\.ssh\id_rsa_firalink")

HOST = "37.26.105.27"
USER = "firalijh"
REMOTE_PATH = "/home/firalijh/lms.fira-link.com"

SKIP_DIRS = {
    "vendor",
    ".git",
    "node_modules",
    "__pycache__",
    ".idea",
    ".vscode",
}

SKIP_FILES = {
    ".env",
    ".env.local",
    ".env.example",
    "database.sqlite",
    "database.sqlite-wal",
    "database.sqlite-shm",
    "laravel.log",
}

SKIP_PREFIXES = (
    "storage/logs/",
    "storage/framework/cache/",
    "storage/framework/sessions/",
    "storage/framework/views/",
)

def build_lms_tar(tar_path: Path):
    print(f"[1/4] Packaging LMS codebase into {tar_path}...")
    count = 0
    with tarfile.open(tar_path, "w:gz") as tar:
        for path in LOCAL_LMS.rglob("*"):
            if not path.is_file():
                continue
            rel = path.relative_to(LOCAL_LMS).as_posix()
            parts = set(path.relative_to(LOCAL_LMS).parts)

            if parts & SKIP_DIRS:
                continue
            if path.name in SKIP_FILES:
                continue
            if path.suffix.lower() in ('.mov', '.mp4', '.zip', '.tar', '.tgz', '.wal', '.shm'):
                continue
            if any(rel.startswith(p) for p in SKIP_PREFIXES):
                continue

            tar.add(path, arcname=rel)
            count += 1
            if count % 200 == 0:
                print(f"  archived {count} files...")

    size_mb = tar_path.stat().st_size / 1024 / 1024
    print(f"  Tarball completed: {count} files, {size_mb:.2f} MB")

def main():
    temp_dir = Path(tempfile.gettempdir())
    tar_path = temp_dir / "firalink-lms-deploy.tgz"
    if tar_path.exists():
        tar_path.unlink()

    build_lms_tar(tar_path)

    print(f"\n[2/4] Connecting SSH to {USER}@{HOST}...")
    key = paramiko.RSAKey.from_private_key_file(str(KEY_FILE))
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, port=22, username=USER, pkey=key, timeout=30)

    remote_tar = "/home/firalijh/firalink-lms-deploy.tgz"
    print(f"\n[3/4] Uploading {tar_path.name} -> {remote_tar} via SFTP...")
    sftp = ssh.open_sftp()
    file_size = tar_path.stat().st_size
    chunk_size = 512 * 1024
    sent = 0
    with open(tar_path, "rb") as fl:
        with sftp.open(remote_tar, "wb") as fr:
            fr.set_pipelined(True)
            while True:
                chunk = fl.read(chunk_size)
                if not chunk:
                    break
                fr.write(chunk)
                sent += len(chunk)
                if sent % (3 * 1024 * 1024) < chunk_size or sent == file_size:
                    print(f"  uploaded {sent/1024/1024:.1f}/{file_size/1024/1024:.1f} MB ({sent/file_size*100:.0f}%)")
    sftp.close()
    print("  Upload completed successfully.")

    def run_cmd(cmd):
        print("\n" + "=" * 60)
        print(">>", cmd)
        chan = ssh.get_transport().open_session()
        chan.get_pty()
        chan.exec_command(cmd)
        while True:
            if chan.recv_ready():
                sys.stdout.write(chan.recv(65536).decode("utf-8", "replace"))
                sys.stdout.flush()
            if chan.recv_stderr_ready():
                sys.stdout.write(chan.recv_stderr(65536).decode("utf-8", "replace"))
                sys.stdout.flush()
            if chan.exit_status_ready():
                while chan.recv_ready():
                    sys.stdout.write(chan.recv(65536).decode("utf-8", "replace"))
                code = chan.recv_exit_status()
                print(f"\n[Exit code: {code}]")
                return code
            time.sleep(0.1)

    print(f"\n[4/4] Extracting and running Laravel deployment tasks on {REMOTE_PATH}...")
    commands = [
        f"tar -xzf {remote_tar} -C {REMOTE_PATH}",
        f"cd {REMOTE_PATH} && php artisan storage:link",
        f"cd {REMOTE_PATH} && php artisan migrate --force",
        f"cd {REMOTE_PATH} && php artisan optimize:clear",
        f"cd {REMOTE_PATH} && php artisan config:cache",
        f"cd {REMOTE_PATH} && php artisan route:cache",
        f"cd {REMOTE_PATH} && php artisan view:cache",
        f"rm -f {remote_tar}",
        "curl -s -IL https://fira-link.com | head -n 15",
        "curl -s -IL https://fira-link.com/tours | head -n 15",
        "curl -s -IL https://fira-link.com/events | head -n 15",
        "curl -s -IL https://fira-link.com/marketplace | head -n 15",
    ]

    for c in commands:
        code = run_cmd(c)
        if code != 0 and "storage:link" not in c:
            print(f"Warning: command returned code {code}")

    ssh.close()
    print("\nFIRA-LINK.COM MAIN DOMAIN DEPLOYMENT FINISHED SUCCESSFULLY!")

if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""
Deploy Fira Link Flutter Web App to cPanel at app.fira-link.com
"""
import os
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
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, port=22, username=USER, pkey=key, timeout=30)

    remote_tar = "/home/firalijh/firalink-app-deploy.tgz"
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

    print(f"\n[4/4] Extracting Flutter Web files onto {REMOTE_PATH}...")
    commands = [
        f"mkdir -p {REMOTE_PATH}",
        f"tar -xzf {remote_tar} -C {REMOTE_PATH}",
        f"rm -f {remote_tar}",
        "curl -s -IL https://app.fira-link.com | head -n 15",
    ]

    for c in commands:
        code = run_cmd(c)
        if code != 0:
            print(f"Warning: command returned code {code}")

    ssh.close()
    print("\nAPP DEPLOYMENT FINISHED SUCCESSFULLY!")

if __name__ == "__main__":
    main()

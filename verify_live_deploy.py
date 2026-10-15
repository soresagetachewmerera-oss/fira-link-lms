import paramiko

KEY_FILE = r'D:\flms\.deploy_keys\id_rsa_firalink'
HOST = '37.26.105.27'
USER = 'firalijh'

key = paramiko.RSAKey.from_private_key_file(KEY_FILE)
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, port=22, username=USER, pkey=key, timeout=15)

commands = [
    ("LMS Homepage HTTP Code", "curl -s -o /dev/null -w '%{http_code}' https://lms.fira-link.com"),
    ("LMS Events HTTP Code", "curl -s -o /dev/null -w '%{http_code}' https://lms.fira-link.com/events"),
    ("LMS Tours HTTP Code", "curl -s -o /dev/null -w '%{http_code}' https://lms.fira-link.com/tours"),
    ("App Web Root HTTP Code", "curl -s -o /dev/null -w '%{http_code}' https://app.fira-link.com"),
    ("App main.dart.js HTTP Code", "curl -s -o /dev/null -w '%{http_code}' https://app.fira-link.com/main.dart.js"),
    ("Live DB Events Count", "cd /home/firalijh/lms.fira-link.com && php artisan tinker --execute='echo \"Events: \" . \\App\\Models\\Event::count();'"),
    ("Live DB Tours Count", "cd /home/firalijh/lms.fira-link.com && php artisan tinker --execute='echo \"Tours: \" . \\App\\Models\\Tour::count();'"),
    ("Live Storage Images Check", "ls -l /home/firalijh/lms.fira-link.com/public/storage/events/ | head -n 6"),
]

print("=== LIVE SERVER PRODUCTION VERIFICATION ===\n")
for title, cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    print(f"{title}: {out if out else err}")

ssh.close()

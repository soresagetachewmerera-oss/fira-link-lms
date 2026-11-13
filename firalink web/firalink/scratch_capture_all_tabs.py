import os
import subprocess
import time

tabs = [
    ("market", "http://127.0.0.1:8000/free-tools#market"),
    ("calculator", "http://127.0.0.1:8000/free-tools#calculator"),
    ("quotation", "http://127.0.0.1:8000/free-tools#quotation"),
    ("compliance", "http://127.0.0.1:8000/free-tools#compliance"),
    ("quality", "http://127.0.0.1:8000/free-tools#quality"),
    ("advisor", "http://127.0.0.1:8000/free-tools#advisor"),
]

edge_path = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
artifact_dir = r"C:\Users\sora\.gemini\antigravity-ide\brain\d99e062f-d41f-448f-b998-8ac31b743cab"

# Capture 1440px desktop
for tab_name, url in tabs:
    out_file = os.path.join(artifact_dir, f"redesign_{tab_name}_1440.png")
    cmd = [
        edge_path,
        "--headless=new",
        "--disable-gpu",
        "--window-size=1440,1100",
        f"--screenshot={out_file}",
        url
    ]
    subprocess.run(cmd, check=True)
    print(f"Captured {out_file}: {os.path.exists(out_file)}")

# Capture 390px mobile
for tab_name, url in tabs:
    out_file = os.path.join(artifact_dir, f"redesign_{tab_name}_390.png")
    cmd = [
        edge_path,
        "--headless=new",
        "--disable-gpu",
        "--window-size=390,920",
        f"--screenshot={out_file}",
        url
    ]
    subprocess.run(cmd, check=True)
    print(f"Captured {out_file}: {os.path.exists(out_file)}")

print("All screenshots captured successfully.")

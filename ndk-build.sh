#!/bin/bash

# Script to build the exploit using Android NDK

set -e

# Check if NDK is set
if [ -z "$ANDROID_NDK_HOME" ]; then
    echo "Error: ANDROID_NDK_HOME is not set"
    echo "Please set it to your NDK path:"
    echo "export ANDROID_NDK_HOME=/path/to/android-ndk-r21e"
    exit 1
fi

echo "[+] Building exploit with NDK..."
echo "[+] NDK Path: $ANDROID_NDK_HOME"

cd jni
$ANDROID_NDK_HOME/ndk-build clean
$ANDROID_NDK_HOME/ndk-build

if [ $? -eq 0 ]; then
    echo "[+] Build successful!"
    echo "[+] Library location: libs/"
else
    echo "[-] Build failed!"
    exit 1
fi

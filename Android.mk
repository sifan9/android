LOCAL_PATH := $(call my-dir)

include $(CLEAR_VARS)

LOCAL_MODULE    := android_root_exploit
LOCAL_SRC_FILES := android_root_exploit.cpp
LOCAL_LDLIBS    := -llog -landroid
LOCAL_CFLAGS    := -Wall -O3 -fPIC -DPIC -march=native
LOCAL_CPPFLAGS  := -std=c++11 -frtti -fexceptions

include $(BUILD_SHARED_LIBRARY)
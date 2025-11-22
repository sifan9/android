LOCAL_PATH := $(call my-dir)

include $(CLEAR_VARS)

LOCAL_MODULE := exploit
LOCAL_SRC_FILES := exploit.cpp

# Android Binder libraries
LOCAL_SHARED_LIBRARIES := \
    libbinder \
    libutils \
    liblog \
    libcutils

# C++ standard library
LOCAL_LDLIBS := -llog

# C++ flags
LOCAL_CPPFLAGS := -std=c++11 -fexceptions -frtti

# Include paths
LOCAL_C_INCLUDES := \
    $(LOCAL_PATH)/../../external/binder/include \
    $(LOCAL_PATH)/../../frameworks/native/include

include $(BUILD_SHARED_LIBRARY)

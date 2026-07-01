# 📤 قائمة رفع الباك اند للسيرفر

## 📅 التاريخ: 2026-06-30
## 🎯 الهدف: رفع تحديثات v2.1 على ab7tb.alwaysdata.net

---

## ⚠️ قبل البدء (إجباري!)

### 1. اعمل Backup كامل للداتا بيز:
```bash
mysqldump -u USERNAME -p DATABASE_NAME > backup_before_v2.1_$(date +%Y%m%d_%H%M%S).sql
```

### 2. اعمل Backup للـ .env القديم:
```bash
cp .env .env.backup_before_v2.1
```

### 3. تأكد من APP_KEY:
- ⚠️ مهم جداً: APP_KEY في .env لازم يفضل ثابت
- لو اتغير، البيانات المشفرة هتروح

---

## 📁 قائمة الملفات للرفع

### 🆕 ملفات جديدة (يجب إنشاؤها على السيرفر):

#### Middleware:
- [ ] `app/Application/Http/Middleware/EnsureDeviceOwnership.php`
  📝 الوصف: Middleware للتحقق من ملكية الجهاز

#### Jobs:
- [ ] `app/Jobs/SendCommandViaFcm.php`
  📝 الوصف: Job لإرسال أوامر FCM بشكل asynchronous

#### Services:
- [ ] `app/Domain/Notification/Services/FirebaseMessagingService.php`
  📝 الوصف: Firebase HTTP v1 Service (بدون package خارجي)
- [ ] `app/Domain/Notification/Exceptions/InvalidFcmTokenException.php`
  📝 الوصف: التعامل مع مشكلات Firebase Token

#### Console Commands:
- [ ] `app/Console/Commands/EncryptExistingData.php`
  📝 الوصف: Command لتشفير البيانات الموجودة

#### Migrations:
- [ ] `database/migrations/2026_06_30_030000_add_v2_1_security_fcm_columns.php`
  📝 الوصف: إضافة fcm_token + push_enabled + fcm_updated_at + phone_hash
- [ ] `database/migrations/2026_06_30_030100_replace_sp_get_pending_commands_atomic.php`
  📝 الوصف: استبدال الـ SP ليعمل بشكل Atomic و Lock-based
- [ ] `database/migrations/2026_06_30_030200_create_queue_tables.php`
  📝 الوصف: جداول الـ Queue لـ Jobs

#### Docs:
- [ ] `CHANGES.md`
- [ ] `INSTALLATION.md`
- [ ] `DAILY_REPORT.md`
- [ ] `TODAY_CHANGES_REPORT.md`
- [ ] `UPLOAD_CHECKLIST.md`

### ✏️ ملفات معدّلة (تحديث):

#### Models:
- [ ] `app/Domain/Communication/Models/SmsLog.php`
  📝 التعديل: إضافة encrypted casts + phone_hash mutator
- [ ] `app/Domain/Communication/Models/CallLog.php`
  📝 التعديل: إضافة encrypted casts + phone_hash mutator
- [ ] `app/Domain/Communication/Models/Contact.php`
  📝 التعديل: إضافة encrypted casts + phone_hash mutator
- [ ] `app/Domain/Communication/Models/BlockedNumber.php`
  📝 التعديل: إضافة encrypted casts + phone_hash mutator
- [ ] `app/Domain/Web/Models/BrowsingHistory.php`
  📝 التعديل: إضافة encrypted casts للـ URL
- [ ] `app/Domain/Device/Models/DeviceLocation.php`
  📝 التعديل: إضافة encrypted casts للموقع
- [ ] `app/Domain/Device/Models/Device.php`
  📝 التعديل: SoftDeletes + fcm fields في fillable + phone_hash generate
- [ ] `app/Domain/User/Models/User.php`
  📝 التعديل: SoftDeletes
- [ ] `app/Domain/Device/Models/RemoteCommand.php`
  📝 التعديل: إضافة حالات الـ push notification

#### Controllers:
- [ ] `app/Application/Http/Controllers/Api/V1/Device/DeviceController.php`
  📝 التعديل: إضافة FCM Token Update Endpoint
- [ ] `app/Application/Http/Controllers/Api/V1/Device/ParentVerificationController.php`
  📝 التعديل: إضافة الـ Security Verify endpoints
- [ ] `app/Application/Http/Controllers/Api/V1/Communication/SmsController.php`
- [ ] `app/Application/Http/Controllers/Api/V1/Communication/ContactController.php`

#### Routes:
- [ ] `routes/api.php`
  📝 التعديل: إضافة middleware الجديد والراوتس الخاصة بـ fcm/verify
- [ ] `routes/console.php`
  📝 التعديل: إضافة الجدولة الزمنية للـ jobs

#### Config/Other:
- [ ] `config/services.php`
  📝 التعديل: Firebase credentials config
- [ ] `composer.json` / `composer.lock`
  📝 التعديل: تحديث الـ dependencies

---

## 🗄️ Database Migrations (الترتيب)

شغّل بالترتيب ده:

```bash
# 1. تنظيف Cache
php artisan optimize:clear

# 2. تشغيل Migrations
php artisan migrate --force

# 3. التحقق
php artisan migrate:status
```

---

## 🔐 ملفات تحتاج رفع منفصل (Sensitive)

### Firebase Credentials:
- [ ] `storage/app/firebase-credentials.json`
  ⚠️ ⚠️ ⚠️ مهم جداً:
  - لا ترفعه من الـ ZIP
  - ارفعه يدوياً
  - الصلاحيات: chmod 600
  - مش يدخل Git أبداً

---

## ⚙️ تحديثات .env

أضف الـ Variables دي على السيرفر:

```env
# Firebase
FIREBASE_PROJECT_ID=com.abo7tb.childapp
FIREBASE_CREDENTIALS_PATH=storage/app/firebase-credentials.json

# Queue
QUEUE_CONNECTION=database

# تأكد من ثبات APP_KEY
APP_KEY=base64:xxxxxxxxxxxxxx  # ⚠️ لا تغيره!
```

---

## 🚀 ترتيب الرفع والتشغيل

### الخطوة 1: Backup (إجباري)
```bash
mysqldump -u USER -p DB_NAME > backup_$(date +%Y%m%d).sql
cp .env .env.backup
```

### الخطوة 2: ارفع الكود
- ارفع كل الملفات من ملف `backend-upload-2026-06-30.zip`

### الخطوة 3: ارفع firebase-credentials.json
- ارفعه يدوياً على `storage/app/`
- `chmod 600 storage/app/firebase-credentials.json`

### الخطوة 4: حدّث .env
- أضف المتغيرات الجديدة من الأعلى.

### الخطوة 5: شغّل الأوامر بالترتيب
```bash
# 1. تنظيف
php artisan optimize:clear

# 2. Migrations
php artisan migrate --force

# 3. تشفير البيانات (مهم!)
php artisan data:encrypt-existing --dry-run
php artisan data:encrypt-existing --force

# 4. Cache
php artisan config:cache
php artisan route:cache

# 5. شغّل Queue Worker
php artisan queue:work --tries=3 --timeout=60 --sleep=3 &
```

### الخطوة 6: إعداد Supervisor (للـ Queue)
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start family-guard-worker:*
```

### الخطوة 7: إعداد Cron
أضف للـ crontab:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 ملخص للـ PM

### عدد الملفات الإجمالي:
- 📋 إجمالي الملفات المعدلة/الجديدة: 50 ملف تقريباً.

### الوقت المتوقع للرفع:
- الرفع نفسه: 5 دقايق
- Migration + Encryption: 5-10 دقايق
- التحقق والاختبار: 10 دقايق
- **المجموع: 20-30 دقيقة**

### Tests Status:
- ✅ Total Tests: 2
- ✅ Passed: 2
- ❌ Failed: 0

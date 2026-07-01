# 🔄 Backend Update v2.1 - Changes Log

## 📁 Section 1: New Files Created
- `app/Jobs/SendCommandViaFcm.php` (Handles async FCM pushing)
- `app/Services/FirebaseMessagingService.php` (Direct Firebase API integration without external packages)
- `app/Console/Commands/EncryptExistingData.php` (Command `data:encrypt-existing` with dry-run support)
- `app/Domain/Notification/Exceptions/InvalidFcmTokenException.php`
- `database/migrations/2026_06_30_030100_replace_sp_get_pending_commands_atomic.php` (Atomic pickup for commands)

## 📝 Section 2: Modified Files
- `routes/api.php`: Applied `device.ownership` middleware and Rate Limiting to sensitive endpoints.
- `app/Http/Middleware/EnsureDeviceOwnership.php`: Added Audit Logging for ownership violations.
- `app/Domain/Device/Models/*.php`: Added `encrypted` casts to PII fields (SMS, Calls, Contacts, Browsing, Location).
- `app/Domain/Device/Models/Device.php`: Added `phone_hash` handling for secure searching post-encryption.

## 🗄️ Section 3: Migrations
- `2026_06_30_030100_replace_sp_get_pending_commands_atomic.php`

## ⚙️ Section 4: Required Server Actions
1. `php artisan optimize:clear`
2. `php artisan migrate --force`
3. `php artisan data:encrypt-existing --dry-run`
4. `php artisan data:encrypt-existing --force`
5. Restart Queue Workers (`php artisan queue:restart`)

## 🔑 Section 5: Required .env Variables
```env
# Firebase Cloud Messaging
FIREBASE_CREDENTIALS_PATH=/absolute/path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=com.abo7tb.childapp
```

## 📁 Section 6: Files Needed on Server
- `firebase-credentials.json` (Must be placed securely on the server and referenced in `.env`)

## 🚨 Section 7: Critical Warnings
> **[WARNING]** 🚨 `APP_KEY` MUST REMAIN UNCHANGED. If `APP_KEY` is lost or modified, all encrypted data (including child data) will be permanently irrecoverable.
> **[WARNING]** 🚨 DO NOT commit `firebase-credentials.json` to any public or private Git repository. Keep it secure on the server.
> **[WARNING]** 🚨 The `data:encrypt-existing` process cannot be easily reversed. ALWAYS run with `--dry-run` first!

## 🧪 Section 8: Tests Status
- **Total tests**: 2
- **Passed**: 2
- **Failed**: 0
(Basic health and setup tests are passing perfectly).

## 🐛 Section 9: Known Issues
- Large datasets during `data:encrypt-existing` might require increased `memory_limit` or `max_execution_time` in `php.ini`.

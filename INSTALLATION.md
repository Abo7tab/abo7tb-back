# 🚀 Backend Installation & Update Guide (v2.1)

🚨 **CRITICAL WARNING**: BEFORE PROCEEDING, ENSURE YOU HAVE A FULL DATABASE BACKUP. 🚨

## 🔄 Step 1: Backup
Run these commands on the server to back up the database and `.env` file before making any changes.
```bash
# Backup Database
mysqldump -u [username] -p [database_name] > backup_v2.0_$(date +%F).sql

# Backup .env
cp .env .env.backup
```

## 📤 Step 2: Upload Files
1. Extract the contents of `backend-v2.1-update.zip`.
2. Upload the extracted files to your server, replacing the existing files.
3. Ensure file permissions remain intact (e.g., `storage/` and `bootstrap/cache/` must be writable).

## 🔑 Step 3: Firebase Credentials
1. Upload your `firebase-credentials.json` file to the server.
2. **SECURITY WARNING**: Place this file OUTSIDE the `public/` directory (e.g., inside `storage/app/private/` or entirely outside the project root).
3. Ensure it is readable only by the web server user.

## ⚙️ Step 4: Update .env
Open your `.env` file and add the following new variables at the bottom:
```env
# FCM Configuration
FIREBASE_CREDENTIALS_PATH=/absolute/path/to/firebase-credentials.json
FIREBASE_PROJECT_ID=com.abo7tb.childapp
```
🚨 **WARNING**: NEVER change the `APP_KEY`. Changing it will permanently lock out all encrypted data.

## 🚀 Step 5: Run Commands
Run these commands in the exact order shown from the project root:

```bash
# 1. Clear Caches
php artisan optimize:clear

# 2. Run Database Migrations (Creates Atomic SP)
php artisan migrate --force

# 3. DRY RUN - Encrypt Existing Data (Will show what will be encrypted)
php artisan data:encrypt-existing --dry-run

# 4. ACTUAL ENCRYPTION - RUN ONLY IF DRY-RUN LOOKS GOOD
php artisan data:encrypt-existing --force

# 5. Restart Queues
php artisan queue:restart
```

## 🔄 Step 6: Supervisor Setup (For Queues)
Ensure Supervisor is configured and running to handle the FCM jobs:
```ini
[program:familyguard-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/worker.log
```
Run `supervisorctl reread`, `supervisorctl update`, and `supervisorctl start familyguard-worker:*`.

## ⏱️ Step 7: Cron Setup (For Scheduled Tasks)
Ensure the Laravel Scheduler is running via Cron:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

## ✅ Step 8: Verification
1. Access the `/api/v1/health` endpoint to ensure the API is running.
2. Test a push notification (send a `Lock Screen` command via the dashboard) and check `storage/logs/laravel.log` for any FCM or queue errors.
3. Check the DB to ensure PII (calls, sms) is successfully encrypted.

## 🔙 Step 9: Rollback Plan
If something goes critically wrong:
1. Re-upload `.env.backup` to `.env`.
2. Restore the database from `backup_v2.0_YYYY-MM-DD.sql`:
   ```bash
   mysql -u [username] -p [database_name] < backup_v2.0_YYYY-MM-DD.sql
   ```
3. Remove the new files and revert to the `v2.0` codebase via your Git / backup archive.
4. Run `php artisan optimize:clear`.

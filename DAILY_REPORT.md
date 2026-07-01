# 📊 Daily Report - Backend Phase 2 (v2.1)

## 🎯 What was completed in v2.1:
1. **Security Enhancements**: Implemented `device.ownership` middleware to strictly prevent cross-device manipulation. Added audit logging for unauthorized access attempts.
2. **Data Encryption at Rest**: Implemented `encrypted` Laravel model casts for all PII fields (SMS, Call Logs, Contacts, Location, Browsing History). Added an intelligent hashing mechanism (`phone_hash`) to allow for exact match lookups on encrypted phone numbers.
3. **Encryption Migration Tool**: Created the `data:encrypt-existing` Artisan command equipped with a `--dry-run` flag to safely encrypt unencrypted legacy data in production.
4. **Firebase Cloud Messaging (FCM)**: Implemented native, lightweight `FirebaseMessagingService` without heavy third-party packages. The service correctly utilizes the `firebase-credentials.json` via Google's official client to send structured background push notifications.
5. **Real-time Command Processing**: Integrated the `SendCommandViaFcm` Job and replaced the legacy polling stored procedure with an atomic, lock-based SQL Server stored procedure (`sp_get_pending_commands`) to prevent race conditions during command pickup.

## 🧠 Key Technical Decisions:
- **Avoiding FCM Packages**: We opted out of third-party Laravel FCM packages to reduce dependency bloat. Using Google's native API client directly is much more reliable and easier to maintain long-term.
- **Hashing vs. Searchable Encryption**: SQL `LIKE` queries inherently break when data is strongly encrypted (AES-256-CBC). We implemented a one-way `phone_hash` column to securely allow lookup of numbers while maintaining absolute data confidentiality.
- **Atomic Database Locks**: Moving command pickups to an atomic stored procedure ensures that horizontal scaling (adding more backend workers) won't result in duplicate commands being sent to the child device.

## 🚧 Challenges Faced & Resolved:
- **FCM Token Management**: Token invalidation and refreshing required catching specific Firebase exceptions (like `UNREGISTERED`). We implemented `InvalidFcmTokenException` handler to safely purge dead tokens from the DB instead of infinitely retrying.

## 💡 Recommendations for the PM:
- 🚨 **BACKUPS**: The encryption process modifies all existing sensitive data in the database. I cannot stress enough how critical a full database backup is before executing `data:encrypt-existing`.
- **APP_KEY Integrity**: If the server's `.env` `APP_KEY` is accidentally changed or lost after encryption, the child data is gone forever. Consider securely backing up the `.env` file to a secure, off-server vault.
- **Testing**: Before mass-deploying the APK, run a full integration test with the backend to ensure the atomic command pickup queue doesn't get flooded.

## ⚠️ Potential Risks:
- High CPU/RAM usage during the initial `data:encrypt-existing` run if the database is massive. I highly recommend running this command during low-traffic off-peak hours (e.g., 3:00 AM).

## 🔙 Rollback Plan:
See `INSTALLATION.md` for a comprehensive step-by-step rollback plan utilizing the SQL dumps and `.env` backups.

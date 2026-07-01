<?php

use App\Domain\Device\Models\Device;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// ── قناة المستخدم (للأب) ────────────────────────────────────────────────
// الأب يستمع لموقع أبنائه وتحديثاتهم
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
});

// ── قناة الجهاز (للجهاز نفسه) ──────────────────────────────────────────
// الجهاز يستمع للأوامر الواردة من الأب
Broadcast::channel('device.{deviceId}', function (User $user, int $deviceId) {
    // الأب مخوّل إذا كان الجهاز ملكه
    return Device::where('id', $deviceId)
                 ->where('user_id', $user->id)
                 ->exists();
});

// ── قناة المستخدم العامة (للتوافق مع Laravel Echo) ──────────────────────
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

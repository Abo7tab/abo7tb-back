<?php

namespace App\Providers;

use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use App\Domain\Device\Services\CommandService;
use App\Domain\Device\Services\DeviceService;
use App\Domain\Device\Services\LocationTrackingService;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\App\Services\AppMonitoringService;
use App\Domain\App\Services\AppBlockingService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentDeviceRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Domain\Web\Services\WebFilteringService;
use App\Domain\Communication\Services\CallMonitoringService;
use App\Domain\Communication\Services\SmsMonitoringService;
use App\Domain\Communication\Services\ContactService;
use App\Domain\Media\Services\CameraService;
use App\Domain\Media\Services\AudioService;
use App\Domain\Media\Services\GalleryService;
use App\Domain\Media\Services\ScreenshotService;
use App\Domain\ScreenControl\Services\ScreenLockService;
use App\Domain\ScreenControl\Services\BedtimeService;
use App\Domain\TimeManagement\Services\TimeLimitService;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Dashboard\Services\DashboardService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings (auto-registered by Laravel)
     *
     * @var array<string, string>
     */
    public array $bindings = [
        // User Domain
        UserRepositoryInterface::class   => EloquentUserRepository::class,

        // Device Domain
        DeviceRepositoryInterface::class => EloquentDeviceRepository::class,
    ];

    /**
     * Register services
     */
    public function register(): void
    {
        // Device Services as singletons
        $this->app->singleton(DeviceService::class, function ($app) {
            return new DeviceService(
                $app->make(DeviceRepositoryInterface::class)
            );
        });

        $this->app->singleton(CommandService::class, function ($app) {
            return new CommandService(
                $app->make(DeviceRepositoryInterface::class)
            );
        });

        $this->app->singleton(LocationTrackingService::class, function ($app) {
            return new LocationTrackingService();
        });

        // App Domain Services
        $this->app->singleton(AppMonitoringService::class, function ($app) {
            return new AppMonitoringService();
        });

        $this->app->singleton(AppBlockingService::class, function ($app) {
            return new AppBlockingService();
        });

        // Web Domain Services
        $this->app->singleton(WebFilteringService::class, function ($app) {
            return new WebFilteringService();
        });

        // Communication Domain Services
        $this->app->singleton(CallMonitoringService::class, function ($app) {
            return new CallMonitoringService();
        });

        $this->app->singleton(SmsMonitoringService::class, function ($app) {
            return new SmsMonitoringService();
        });

        $this->app->singleton(ContactService::class, function ($app) {
            return new ContactService();
        });

        // Media Domain Services
        $this->app->singleton(CameraService::class, function ($app) {
            return new CameraService();
        });

        $this->app->singleton(AudioService::class, function ($app) {
            return new AudioService();
        });

        $this->app->singleton(GalleryService::class, function ($app) {
            return new GalleryService();
        });

        $this->app->singleton(ScreenshotService::class, function ($app) {
            return new ScreenshotService();
        });

        // Phase 8: ScreenControl Domain
        $this->app->singleton(ScreenLockService::class, function ($app) {
            return new ScreenLockService();
        });

        $this->app->singleton(BedtimeService::class, function ($app) {
            return new BedtimeService($app->make(ScreenLockService::class));
        });

        // Phase 9: TimeManagement Domain
        $this->app->singleton(TimeLimitService::class, function ($app) {
            return new TimeLimitService();
        });

        // Phase 10: Notification Domain
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        // Phase 11: Dashboard & Analytics
        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService();
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}

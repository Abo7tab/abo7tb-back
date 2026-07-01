#!/bin/bash

echo "🚀 Checking Laravel 12 + DDD Setup..."
echo "========================================"

# Check Laravel version
echo ""
echo "✓ Laravel Version:"
php artisan --version

# Check PHP version
echo ""
echo "✓ PHP Version:"
php -v | head -n 1

# Check Composer packages
echo ""
echo "✓ Key Installed Packages:"
composer show | grep -E "laravel/framework|laravel/sanctum|laravel/reverb|spatie|predis"

# Check directories
echo ""
echo "✓ DDD Directory Structure:"
if [ -d "app/Domain" ]; then
    echo "  ✓ app/Domain exists"
    ls -d app/Domain/*/ 2>/dev/null | sed 's/^/    /'
fi

if [ -d "app/Application" ]; then
    echo "  ✓ app/Application exists"
fi

if [ -d "app/Infrastructure" ]; then
    echo "  ✓ app/Infrastructure exists"
fi

# Check Value Objects
echo ""
echo "✓ Shared Value Objects:"
for file in Email PhoneNumber Coordinates DateRange; do
    if [ -f "app/Domain/Shared/ValueObjects/${file}.php" ]; then
        echo "  ✓ ${file}.php"
    else
        echo "  ✗ ${file}.php MISSING"
    fi
done

# Check Enums
echo ""
echo "✓ Shared Enums:"
for file in DeviceStatus CommandType NotificationPriority; do
    if [ -f "app/Domain/Shared/Enums/${file}.php" ]; then
        echo "  ✓ ${file}.php"
    else
        echo "  ✗ ${file}.php MISSING"
    fi
done

# Check .env
echo ""
echo "✓ Environment Configuration:"
if [ -f .env ]; then
    echo "  ✓ .env exists"
else
    echo "  ✗ .env missing - Run: cp .env.example .env"
fi

# Check app key
echo ""
echo "✓ Application Key:"
if grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "  ✓ APP_KEY is set"
else
    echo "  ✗ APP_KEY not set - Run: php artisan key:generate"
fi

# Check config files
echo ""
echo "✓ Custom Config Files:"
if [ -f "config/parental-control.php" ]; then
    echo "  ✓ config/parental-control.php exists"
fi

# Check database connection
echo ""
echo "✓ Database Connection:"
php artisan db:show 2>/dev/null && echo "  ✓ Database connected" || echo "  ✗ Database connection failed (configure .env first)"

echo ""
echo "========================================"
echo "✅ Setup check complete!"
echo ""
echo "Next steps:"
echo "1. Update .env with your database credentials"
echo "2. Run: php artisan migrate"
echo "3. Start Reverb: php artisan reverb:start"
echo "4. Proceed to Phase 2 implementation"

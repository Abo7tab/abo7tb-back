<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingData extends Command
{
    protected $signature = 'data:encrypt-existing {--dry-run} {--force}';

    protected $description = 'Encrypt existing sensitive database values and populate lookup hashes.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Dry run: no rows will be updated.' : 'Encryption mode: rows will be updated.');

        if (!$dryRun && !$this->option('force') && !$this->confirm('Did you create a full database backup?')) {
            $this->error('Create a backup first, then run the command again.');
            return self::FAILURE;
        }

        $tables = [
            'sms_logs' => [
                'columns' => ['phone_number', 'contact_name', 'message_body'],
                'phone_column' => 'phone_number',
            ],
            'call_logs' => [
                'columns' => ['phone_number', 'contact_name'],
                'phone_column' => 'phone_number',
            ],
            'contacts' => [
                'columns' => ['phone_number', 'contact_name', 'email'],
                'phone_column' => 'phone_number',
            ],
            'blocked_numbers' => [
                'columns' => ['phone_number', 'contact_name'],
                'phone_column' => 'phone_number',
            ],
            'browsing_history' => [
                'columns' => ['url', 'title'],
            ],
            'device_locations' => [
                'columns' => ['address', 'city', 'country'],
            ],
        ];

        foreach ($tables as $table => $config) {
            $this->encryptTable($table, $config['columns'], $config['phone_column'] ?? null, $dryRun);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function encryptTable(string $table, array $columns, ?string $phoneColumn, bool $dryRun): void
    {
        $this->line("Processing {$table}");

        $count = DB::table($table)->count();
        $bar = $this->output->createProgressBar($count);

        DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns, $phoneColumn, $dryRun, $bar) {
            foreach ($rows as $row) {
                $updates = [];

                if ($phoneColumn && !empty($row->{$phoneColumn}) && property_exists($row, 'phone_hash')) {
                    $plainPhone = $this->decryptIfNeeded((string) $row->{$phoneColumn});
                    $updates['phone_hash'] = $this->hashPhone($plainPhone);
                }

                foreach ($columns as $column) {
                    if (!property_exists($row, $column) || $row->{$column} === null || $row->{$column} === '') {
                        continue;
                    }

                    $value = (string) $row->{$column};

                    if ($this->isEncrypted($value)) {
                        continue;
                    }

                    $updates[$column] = Crypt::encryptString($value);
                }

                if ($updates && !$dryRun) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function decryptIfNeeded(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    private function hashPhone(string $number): string
    {
        return hash('sha256', preg_replace('/\s+/', '', $number));
    }
}

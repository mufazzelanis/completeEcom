<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\VAPID;

/**
 * One-command setup for admin phone alerts: verifies the server can do Web Push and
 * generates the VAPID key pair straight into .env on the machine it runs on — so the private
 * key is created where it's used and never has to be pasted into a chat or a repo.
 * Safe to re-run; it won't replace existing keys unless told to.
 */
class SetupAdminAlerts extends Command
{
    protected $signature = 'admin-alerts:setup {--regenerate : Replace the existing VAPID keys (every phone must turn alerts on again)}';

    protected $description = 'Check requirements and generate the VAPID keys used for admin phone (Web Push) alerts.';

    public function handle(): int
    {
        $this->info('Admin alerts — setup check');
        $ok = true;

        foreach (['openssl' => 'encrypting push messages', 'mbstring' => 'handling text', 'curl' => 'sending to browser push services'] as $ext => $why) {
            $has = extension_loaded($ext);
            $ok = $ok && $has;
            $this->line(($has ? '  <info>OK</info>   ' : '  <error>MISSING</error> ') . "PHP extension {$ext} ({$why})");
        }

        $tables = Schema::hasTable('admin_alerts') && Schema::hasColumn('push_subscriptions', 'endpoint_hash');
        $ok = $ok && $tables;
        $this->line(($tables ? '  <info>OK</info>   ' : '  <error>MISSING</error> ') . 'Database tables' . ($tables ? '' : ' — run: php artisan migrate --force'));

        if (! $ok) {
            $this->newLine();
            $this->error('Fix the items above, then run this command again.');
            return self::FAILURE;
        }

        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : '';
        $hasKeys = preg_match('/^VAPID_PUBLIC_KEY=\S+/m', $env) && preg_match('/^VAPID_PRIVATE_KEY=\S+/m', $env);

        if ($hasKeys && ! $this->option('regenerate')) {
            $this->line('  <info>OK</info>   VAPID keys already present in .env (use --regenerate to replace them)');
        } else {
            try {
                $keys = VAPID::createVapidKeys();
            } catch (\Throwable $e) {
                $this->error('Could not generate VAPID keys: ' . $e->getMessage());
                return self::FAILURE;
            }

            $env = $this->setEnvValue($env, 'VAPID_PUBLIC_KEY', $keys['publicKey']);
            $env = $this->setEnvValue($env, 'VAPID_PRIVATE_KEY', $keys['privateKey']);

            if ($hasKeys) {
                // Existing subscriptions were created against the OLD public key and can
                // never receive a push signed with the new one.
                $removed = PushSubscription::where('scope', 'admin')->delete();
                $this->warn("  Replaced the VAPID keys and cleared {$removed} old phone subscription(s) — turn phone alerts on again on each device.");
            } else {
                $this->line('  <info>OK</info>   Generated a new VAPID key pair and saved it to .env');
            }
        }

        if (! preg_match('/^VAPID_SUBJECT=\S+/m', $env)) {
            $subject = $this->defaultSubject();
            $env = $this->setEnvValue($env, 'VAPID_SUBJECT', $subject);
            $this->line("  <info>OK</info>   VAPID_SUBJECT set to {$subject}");
        }

        file_put_contents($envPath, $env);
        $this->callSilently('config:clear');

        $this->newLine();
        $this->info('Done. Next steps on your phone:');
        $this->line('  1. Open your site\'s /admin in Chrome (Android) and choose "Install app" / "Add to Home screen".');
        $this->line('     iPhone: Safari → Share → "Add to Home Screen" (iOS 16.4 or newer).');
        $this->line('  2. Open the installed app, tap the bell, switch on "Phone alerts", and allow notifications.');
        $this->line('  3. Tap "Send me a test alert" — you should get a notification within a few seconds.');

        return self::SUCCESS;
    }

    /** Push services want a mailto: or https: contact for whoever operates the sending server. */
    private function defaultSubject(): string
    {
        $appUrl = (string) config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            return rtrim($appUrl, '/');
        }

        $email = User::where('role', 'admin')->value('email');

        return 'mailto:' . (filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'admin@example.com');
    }

    private function setEnvValue(string $env, string $key, string $value): string
    {
        $line = "{$key}={$value}";

        if (preg_match("/^{$key}=.*$/m", $env)) {
            return preg_replace("/^{$key}=.*$/m", $line, $env);
        }

        return rtrim($env, "\r\n") . "\n{$line}\n";
    }
}

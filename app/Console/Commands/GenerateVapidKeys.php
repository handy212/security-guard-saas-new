<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'guardops:vapid-keys';

    protected $description = 'Generate VAPID keys for Web Push notifications';

    public function handle(): int
    {
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:admin@'.parse_url(config('app.url'), PHP_URL_HOST));
        $this->line('PUSH_NOTIFICATIONS_ENABLED=true');

        return self::SUCCESS;
    }
}

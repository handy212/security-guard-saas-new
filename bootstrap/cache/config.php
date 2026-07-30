<?php return array (
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'mail' => 
  array (
    'default' => 'log',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => 2525,
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'local_domain' => 'localhost',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@example.com',
      'name' => 'Example',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/var/www/html/resources/views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => 12,
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/var/www/html/resources/views',
    ),
    'compiled' => '/var/www/html/storage/framework/views',
  ),
  'app' => 
  array (
    'name' => 'GuardCore Pro',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost:8080',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:g48iI2wBjRKr570eAGkmRFHPrBDHUfHHVceU1+Z1Tes=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      17 => 'Illuminate\\Queue\\QueueServiceProvider',
      18 => 'Illuminate\\Redis\\RedisServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\AuthServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
  ),
  'assets' => 
  array (
    'statuses' => 
    array (
      'available' => 'Available',
      'issued' => 'Issued',
      'maintenance' => 'Maintenance',
      'retired' => 'Retired',
      'lost' => 'Lost',
    ),
    'conditions' => 
    array (
      'good' => 'Good',
      'fair' => 'Fair',
      'poor' => 'Poor',
    ),
    'category_types' => 
    array (
      'serialized' => 'Serialized (unique items)',
      'consumable' => 'Consumable (quantity tracked)',
    ),
    'deploy_kit_categories' => 
    array (
      0 => 'Vehicles',
      1 => 'Motors',
      2 => 'Radios',
      3 => 'Bodycams',
    ),
    'fleet_type_categories' => 
    array (
      'car' => 'Vehicles',
      'van' => 'Vehicles',
      'motor' => 'Motors',
      'other' => 'Vehicles',
    ),
    'po_statuses' => 
    array (
      'draft' => 'Draft',
      'submitted' => 'Submitted',
      'ordered' => 'Ordered',
      'partial' => 'Partially received',
      'received' => 'Received',
      'cancelled' => 'Cancelled',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => 'users',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'broadcasting' => 
  array (
    'default' => 'reverb',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => 'local-key',
        'secret' => 'local-secret',
        'app_id' => 'guardops',
        'options' => 
        array (
          'host' => 'localhost',
          'port' => '8081',
          'scheme' => 'http',
          'useTLS' => false,
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusher.com',
          'port' => 443,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'redis',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'lock_connection' => NULL,
        'lock_table' => 'cache_locks',
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/var/www/html/storage/framework/cache/data',
        'lock_path' => '/var/www/html/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => 'guardcore-pro-cache-',
  ),
  'client_profile' => 
  array (
    'document_types' => 
    array (
      'contract' => 'Contract / SOW',
      'insurance' => 'Insurance certificate',
      'license' => 'Business license',
      'policy' => 'Security policy',
      'invoice' => 'Billing document',
      'general' => 'General',
    ),
    'report_types' => 
    array (
      'daily_activity' => 'Daily activity report',
      'patrol_summary' => 'Patrol summary',
      'incidents' => 'Incident digest',
      'custom' => 'Custom report',
    ),
    'report_frequencies' => 
    array (
      'daily' => 'Daily',
      'weekly' => 'Weekly',
      'monthly' => 'Monthly',
    ),
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'guard_saas',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => 'mysql',
        'port' => '3306',
        'database' => 'guard_saas',
        'username' => 'guardops',
        'password' => 'secret',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => 'mysql',
        'port' => '3306',
        'database' => 'guard_saas',
        'username' => 'guardops',
        'password' => 'secret',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => 'mysql',
        'port' => '3306',
        'database' => 'guard_saas',
        'username' => 'guardops',
        'password' => 'secret',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => 'mysql',
        'port' => '3306',
        'database' => 'guard_saas',
        'username' => 'guardops',
        'password' => 'secret',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'guardcore_pro_database_',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => 'redis',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => 'redis',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
  ),
  'dispatch' => 
  array (
    'incident_types' => 
    array (
      'alarm' => 'Alarm activation',
      'trespass' => 'Trespassing',
      'medical' => 'Medical emergency',
      'fire' => 'Fire / smoke',
      'theft' => 'Theft / burglary',
      'disturbance' => 'Disturbance',
      'vandalism' => 'Vandalism',
      'welfare_check' => 'Welfare check',
      'other' => 'Other',
    ),
    'caller_types' => 
    array (
      'client' => 'Client',
      'guard' => 'Guard',
      'public' => 'Public',
      'internal' => 'Internal',
    ),
  ),
  'file_scanner' => 
  array (
    'driver' => 'null',
    'clamav' => 
    array (
      'binary' => 'clamscan',
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/html/storage/app/private',
        'serve' => true,
        'throw' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/html/storage/app/public',
        'url' => 'http://localhost:8080/storage',
        'visibility' => 'public',
        'throw' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => NULL,
        'bucket' => NULL,
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
      ),
      'tenant_private' => 
      array (
        'driver' => 'local',
        'root' => '/var/www/html/storage/app/tenant-private',
        'visibility' => 'private',
        'throw' => false,
      ),
    ),
    'links' => 
    array (
      '/var/www/html/public/storage' => '/var/www/html/storage/app/public',
    ),
    'tenant_disk' => 'tenant_private',
  ),
  'guard_hr' => 
  array (
    'skills' => 
    array (
      0 => 'Armed security',
      1 => 'Unarmed security',
      2 => 'CCTV monitoring',
      3 => 'Access control',
      4 => 'First aid',
      5 => 'Fire safety',
      6 => 'Conflict de-escalation',
      7 => 'Patrol procedures',
      8 => 'Report writing',
      9 => 'Customer service',
      10 => 'Defensive driving',
      11 => 'Radio communications',
    ),
    'training_courses' => 
    array (
      0 => 'Basic security training',
      1 => 'WEAC certification',
      2 => 'First aid / CPR',
      3 => 'Fire warden training',
      4 => 'CCTV operator training',
      5 => 'Conflict management',
      6 => 'Health & safety induction',
      7 => 'Emergency response',
      8 => 'Professional ethics',
      9 => 'Use of force (basic)',
    ),
    'skill_levels' => 
    array (
      'basic' => 'Basic',
      'intermediate' => 'Intermediate',
      'advanced' => 'Advanced',
      'expert' => 'Expert',
    ),
  ),
  'guard_profile' => 
  array (
    'weekdays' => 
    array (
      0 => 'Sunday',
      1 => 'Monday',
      2 => 'Tuesday',
      3 => 'Wednesday',
      4 => 'Thursday',
      5 => 'Friday',
      6 => 'Saturday',
    ),
    'default_settings' => 
    array (
      'show_current_assignment' => true,
      'notify_on_shift_change' => true,
      'allow_open_shift_bids' => true,
      'preferred_contact_method' => 'phone',
    ),
  ),
  'guard_verification' => 
  array (
    'token_ttl_days' => 365,
    'page' => 
    array (
      'subtitle' => 'Real-time staff verification.',
      'verified_banner_title' => 'Verified & authorised today',
      'verified_banner_hint' => 'Safe to grant access after matching photo, ID card and uniform.',
      'unassigned_banner_title' => 'No site assignment',
      'unassigned_banner_hint' => 'Identity is verified, but this officer is not currently assigned to any site. Do not grant access until assignment is confirmed.',
      'access_guidance' => 'Grant access only if the person matches the photo, presents a company ID card, and is wearing the approved uniform. Do not accept screenshots.',
      'security_notice' => 'Always scan the guard\'s ID card directly. Do not rely on forwarded links or screenshots. If anything looks suspicious, contact the control room immediately.',
      'verified_by_label' => 'Verified by Control Room',
      'database_source_label' => 'Source: secure staff database',
      'live_page_notice' => 'Live page — values refresh each time this QR is scanned.',
      'expected_appearance' => 
      array (
        0 => 'Branded uniform',
        1 => 'Visible staff ID',
      ),
      'competencies_heading' => 'Verified competencies',
      'appearance_heading' => 'Expected appearance',
      'issued_kit_heading' => 'Issued for this shift',
      'support_heading' => 'Need to confirm?',
      'support_intro' => 'If you are unsure about this guard, contact the control room or the site supervisor before granting access.',
      'call_button_label' => 'Call control room',
      'report_button_label' => 'Report concern',
      'supervisor_heading' => 'Site supervisor',
      'supervisor_intro' => 'Contact the supervisor for this site if you need on-site confirmation.',
      'call_supervisor_label' => 'Call supervisor',
    ),
  ),
  'id_card' => 
  array (
    'pdf_driver' => 'browsershot',
    'chrome_path' => '/usr/bin/chromium',
    'node_path' => '/usr/bin/node',
    'npm_path' => '/usr/bin/npm',
    'width_in' => 3.375,
    'height_in' => 2.125,
    'design_width_px' => 280,
    'design_height_px' => 445,
    'png_dpi' => 300,
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => 'null',
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/var/www/html/storage/logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/var/www/html/storage/logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/var/www/html/storage/logs/laravel.log',
      ),
    ),
  ),
  'navigation' => 
  array (
    'assets' => 
    array (
      0 => 
      array (
        'href' => '/assets',
        'label' => 'Overview',
        'permission' => 'equipment.manage',
      ),
      1 => 
      array (
        'href' => '/assets/list',
        'label' => 'Assets',
        'permission' => 'equipment.manage',
      ),
      2 => 
      array (
        'href' => '/assets/categories',
        'label' => 'Categories',
        'permission' => 'equipment.manage',
      ),
      3 => 
      array (
        'href' => '/assets/inventory',
        'label' => 'Asset Inventory',
        'permission' => 'equipment.manage',
      ),
      4 => 
      array (
        'href' => '/assets/vendors',
        'label' => 'Vendors',
        'permission' => 'equipment.manage',
      ),
      5 => 
      array (
        'href' => '/assets/purchase-orders',
        'label' => 'Purchase Orders',
        'permission' => 'equipment.manage',
      ),
    ),
    'patrols' => 
    array (
      0 => 
      array (
        'href' => '/patrols',
        'label' => 'Patrol board',
        'permission' => 'patrols.manage',
        'feature' => 'patrols',
      ),
      1 => 
      array (
        'href' => '/patrols/fleet',
        'label' => 'Fleet',
        'permission' => 'patrols.manage',
        'feature' => 'patrols',
      ),
      2 => 
      array (
        'href' => '/patrols/vehicles',
        'label' => 'Vehicle patrols',
        'permission' => 'patrols.manage',
        'feature' => 'patrols',
      ),
      3 => 
      array (
        'href' => '/patrols/playback',
        'label' => 'Patrol playback',
        'permission' => 'patrols.manage',
        'feature' => 'gps',
      ),
      4 => 
      array (
        'href' => '/passdown',
        'label' => 'Passdown',
        'permission' => 'patrols.manage',
        'feature' => 'passdown',
      ),
    ),
    'reports' => 
    array (
      0 => 
      array (
        'href' => '/reports',
        'label' => 'Overview',
        'permission' => 'reports.approve',
        'feature' => 'reports',
      ),
      1 => 
      array (
        'href' => '/reports/daily',
        'label' => 'Daily reports',
        'permission' => 'reports.approve',
        'feature' => 'reports',
      ),
      2 => 
      array (
        'href' => '/reports/templates',
        'label' => 'Custom templates',
        'permission' => 'reports.approve',
        'feature' => 'custom_reports',
      ),
    ),
    'schedules' => 
    array (
      0 => 
      array (
        'href' => '/schedules/calendar',
        'label' => 'Calendar',
        'group' => 'Planning',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      1 => 
      array (
        'href' => '/schedules',
        'label' => 'Day roster',
        'group' => 'Planning',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      2 => 
      array (
        'href' => '/schedules/templates',
        'label' => 'Templates',
        'group' => 'Planning',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      3 => 
      array (
        'href' => '/schedules/deploy',
        'label' => 'Deploy',
        'group' => 'Planning',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      4 => 
      array (
        'href' => '/schedules/attendance',
        'label' => 'Attendance',
        'group' => 'Field day',
        'permission' => 'attendance.manage',
        'feature' => 'attendance',
      ),
      5 => 
      array (
        'href' => '/schedules/reconciliation',
        'label' => 'Reconciliation',
        'group' => 'Field day',
        'permission' => 'attendance.manage',
        'feature' => 'attendance',
      ),
    ),
    'schedules_more' => 
    array (
      0 => 
      array (
        'href' => '/schedules/deployment-sheet',
        'label' => 'Deployment sheet',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      1 => 
      array (
        'href' => '/schedules/shift-status',
        'label' => 'Confirmations',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      2 => 
      array (
        'href' => '/schedules/open-shifts',
        'label' => 'Open shifts',
        'permission' => 'schedules.manage',
        'feature' => 'marketplace',
      ),
      3 => 
      array (
        'href' => '/schedules/shift-exchange',
        'label' => 'Shift exchange',
        'permission' => 'schedules.manage',
        'feature' => 'marketplace',
      ),
      4 => 
      array (
        'href' => '/schedules/time-off',
        'label' => 'Time off',
        'permission' => 'schedules.manage',
        'feature' => 'workforce',
      ),
    ),
    'billing' => 
    array (
      0 => 
      array (
        'href' => '/billing',
        'label' => 'Overview',
      ),
      1 => 
      array (
        'href' => '/billing/invoices',
        'label' => 'Invoices',
        'permission' => 'billing.manage',
        'feature' => 'billing',
      ),
      2 => 
      array (
        'href' => '/billing/estimates',
        'label' => 'Estimates',
        'permission' => 'billing.manage',
        'feature' => 'estimates',
      ),
      3 => 
      array (
        'href' => '/billing/payments',
        'label' => 'Payments',
        'permission' => 'billing.manage',
        'feature' => 'billing',
      ),
      4 => 
      array (
        'href' => '/billing/expenses',
        'label' => 'Expenses',
        'permission' => 'billing.manage',
        'feature' => 'expenses',
      ),
      5 => 
      array (
        'href' => '/billing/payroll',
        'label' => 'Payroll',
        'permission' => 'payroll.manage',
        'feature' => 'payroll',
      ),
      6 => 
      array (
        'href' => '/compliance',
        'label' => 'Compliance',
        'permission' => 'compliance.manage',
        'feature' => 'compliance',
      ),
      7 => 
      array (
        'href' => '/compliance/policies',
        'label' => 'Policies',
        'permission' => 'compliance.manage',
        'feature' => 'compliance',
      ),
      8 => 
      array (
        'href' => '/analytics',
        'label' => 'Analytics',
        'permission' => 'analytics.view',
        'feature' => 'analytics',
      ),
    ),
    'pinned' => 
    array (
      0 => 
      array (
        'href' => '/dashboard',
        'label' => 'Dashboard',
        'icon' => 'dashboard',
        'permission' => 'dashboard.view',
      ),
      1 => 
      array (
        'href' => '/tracking',
        'label' => 'Live Tracker',
        'icon' => 'gps',
        'permission' => 'dispatch.manage',
        'feature' => 'gps',
      ),
      2 => 
      array (
        'href' => '/clients',
        'label' => 'Clients',
        'icon' => 'clients',
        'permission' => 'clients.manage',
        'feature' => 'clients',
      ),
      3 => 
      array (
        'href' => '/sites',
        'label' => 'Sites',
        'icon' => 'sites',
        'permission' => 'sites.manage',
        'feature' => 'clients',
      ),
      4 => 
      array (
        'href' => '/guards',
        'label' => 'Guards',
        'icon' => 'guards',
        'permission' => 'guards.manage',
        'feature' => 'guards',
      ),
      5 => 
      array (
        'href' => '/schedules',
        'label' => 'Scheduler',
        'icon' => 'schedules',
        'permission' => 'schedules.manage',
        'feature' => 'schedules',
      ),
      6 => 
      array (
        'href' => '/dispatch',
        'label' => 'Dispatch',
        'icon' => 'dispatch',
        'permission' => 'dispatch.manage',
        'feature' => 'dispatch',
      ),
      7 => 
      array (
        'href' => '/incidents',
        'label' => 'Incidents',
        'icon' => 'incidents',
        'permission' => 'incidents.manage',
        'feature' => 'incidents',
      ),
      8 => 
      array (
        'href' => '/patrols',
        'label' => 'Patrols',
        'icon' => 'patrols',
        'permission' => 'patrols.manage',
        'feature' => 'patrols',
      ),
      9 => 
      array (
        'href' => '/billing',
        'label' => 'Back Office',
        'icon' => 'billing',
        'hub' => 'billing',
      ),
      10 => 
      array (
        'href' => '/guard',
        'label' => 'Field app',
        'icon' => 'mobile',
        'permission' => 'mobile.use',
        'feature' => 'guards',
        'highlight' => true,
      ),
    ),
    'groups' => 
    array (
      'Workforce' => 
      array (
        0 => 
        array (
          'href' => '/guards/know-your-guard',
          'label' => 'Know Your Guard',
          'icon' => 'guards',
          'permission' => 'guards.manage',
          'feature' => 'guards',
        ),
        1 => 
        array (
          'href' => '/guards/applications',
          'label' => 'Applications',
          'icon' => 'workforce',
          'permission' => 'guards.manage',
          'feature' => 'guards',
        ),
        2 => 
        array (
          'href' => '/visitors',
          'label' => 'Visitors',
          'icon' => 'visitors',
          'permission' => 'visitors.manage',
          'feature' => 'visitors',
        ),
        3 => 
        array (
          'href' => '/clients/complaints',
          'label' => 'Complaints',
          'icon' => 'clients',
          'permission' => 'clients.manage',
          'feature' => 'clients',
        ),
      ),
      'Live ops' => 
      array (
        0 => 
        array (
          'href' => '/messenger',
          'label' => 'Messenger',
          'icon' => 'messenger',
          'permission' => 'dispatch.manage',
          'feature' => 'messenger',
        ),
      ),
      'Resources' => 
      array (
        0 => 
        array (
          'href' => '/reports',
          'label' => 'Reports',
          'icon' => 'reports',
          'permission' => 'reports.approve',
          'feature' => 'reports',
        ),
        1 => 
        array (
          'href' => '/assets',
          'label' => 'Assets',
          'icon' => 'equipment',
          'permission' => 'equipment.manage',
          'feature' => 'equipment',
        ),
      ),
    ),
    'footer' => 
    array (
      0 => 
      array (
        'href' => '/settings',
        'label' => 'Settings',
        'icon' => 'settings',
        'permission' => 'settings.manage',
      ),
    ),
    'platform' => 
    array (
      0 => 
      array (
        'href' => '/saas/tenants',
        'label' => 'Tenants',
        'icon' => 'tenants',
        'permission' => 'tenants.manage',
      ),
      1 => 
      array (
        'href' => '/saas/plans',
        'label' => 'Plans',
        'icon' => 'plans',
        'permission' => 'tenants.manage',
      ),
      2 => 
      array (
        'href' => '/saas/subscriptions',
        'label' => 'Subscriptions',
        'icon' => 'subscriptions',
        'permission' => 'tenants.manage',
      ),
    ),
    'settings' => 
    array (
      0 => 
      array (
        'href' => '/billing/subscription',
        'label' => 'Your plan',
        'permission' => 'billing.manage',
      ),
      1 => 
      array (
        'href' => '/settings/branches',
        'label' => 'Branches',
        'permission' => 'settings.manage',
      ),
      2 => 
      array (
        'href' => '/settings/id-card',
        'label' => 'ID Card',
        'permission' => 'settings.manage',
      ),
      3 => 
      array (
        'href' => '/settings/know-your-guard',
        'label' => 'KYG public page',
        'permission' => 'settings.manage',
      ),
      4 => 
      array (
        'href' => '/settings/roles',
        'label' => 'Roles & Permissions',
        'permission' => 'settings.manage',
      ),
      5 => 
      array (
        'href' => '/settings/staff',
        'label' => 'Team members',
        'permission' => 'settings.manage',
      ),
      6 => 
      array (
        'href' => '/settings/audit-log',
        'label' => 'Audit trail',
        'permission' => 'audit.view',
      ),
      7 => 
      array (
        'href' => '/settings/team',
        'label' => 'Team passwords',
        'permission' => 'settings.manage',
      ),
      8 => 
      array (
        'href' => '/settings/two-factor',
        'label' => 'Two-Factor Auth',
        'permission' => NULL,
      ),
      9 => 
      array (
        'href' => '/settings/webhooks',
        'label' => 'Webhooks',
        'permission' => 'settings.manage',
        'feature' => 'webhooks',
      ),
      10 => 
      array (
        'href' => '/settings/notifications',
        'label' => 'Notification templates',
        'permission' => 'settings.manage',
      ),
      11 => 
      array (
        'href' => '/mobile/offline-sync',
        'label' => 'Offline Sync',
        'permission' => 'mobile.use',
      ),
    ),
  ),
  'notifications' => 
  array (
    'push' => 
    array (
      'enabled' => true,
      'vapid' => 
      array (
        'subject' => 'mailto:admin@localhost',
        'public_key' => 'BKQ3VKglVa5lTz6apZjA1Ct723PQmCDoKUnJ6wczeGAjrjj25zgyATZueJBhuKgCPNYhELeeluLXSpIq_noCd3U',
        'private_key' => '4VxKTUMRB7P0GUgMfYIs4jfK2g1ujlphw5crA6W_67g',
      ),
    ),
    'sms' => 
    array (
      'enabled' => false,
      'driver' => 'twilio',
      'twilio' => 
      array (
        'sid' => '',
        'token' => '',
        'from' => '',
      ),
      'templates_requiring_sms' => 
      array (
        0 => 'sos.raised',
        1 => 'patrol.missed',
        2 => 'geofence.violation',
        3 => 'guard.idle',
      ),
    ),
    'idle_alert_minutes' => 15,
    'geofence_check_on_location' => true,
  ),
  'paystack' => 
  array (
    'public_key' => 'pk_test_0936c37a704f45e0ebadbd87014f9951bbdc5095',
    'secret_key' => 'sk_test_3adeeed8f602a413e8236ebb5963c5f906798bfd',
    'webhook_secret' => '',
    'currency' => 'GHS',
    'base_url' => 'https://api.paystack.co',
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => 'role_id',
      'permission_pivot_key' => 'permission_id',
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'tenant_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => true,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'plan_entitlements' => 
  array (
    'default_features' => 
    array (
      0 => 'guards',
      1 => 'schedules',
      2 => 'incidents',
      3 => 'clients',
    ),
    'tiers' => 
    array (
      'starter' => 
      array (
        'label' => 'Starter',
        'features' => 
        array (
          0 => 'guards',
          1 => 'schedules',
          2 => 'attendance',
          3 => 'incidents',
          4 => 'patrols',
          5 => 'clients',
        ),
      ),
      'professional' => 
      array (
        'label' => 'Professional',
        'features' => 
        array (
          0 => 'guards',
          1 => 'schedules',
          2 => 'attendance',
          3 => 'incidents',
          4 => 'reports',
          5 => 'patrols',
          6 => 'gps',
          7 => 'dispatch',
          8 => 'clients',
          9 => 'client_portal',
        ),
      ),
      'business' => 
      array (
        'label' => 'Business',
        'features' => 
        array (
          0 => 'guards',
          1 => 'schedules',
          2 => 'attendance',
          3 => 'incidents',
          4 => 'reports',
          5 => 'patrols',
          6 => 'gps',
          7 => 'dispatch',
          8 => 'equipment',
          9 => 'visitors',
          10 => 'clients',
          11 => 'client_portal',
          12 => 'billing',
          13 => 'payroll',
          14 => 'compliance',
          15 => 'analytics',
          16 => 'marketplace',
          17 => 'messenger',
          18 => 'custom_reports',
          19 => 'estimates',
          20 => 'expenses',
          21 => 'sms_alerts',
        ),
      ),
      'enterprise' => 
      array (
        'label' => 'Enterprise',
        'features' => 
        array (
          0 => 'guards',
          1 => 'schedules',
          2 => 'attendance',
          3 => 'incidents',
          4 => 'reports',
          5 => 'patrols',
          6 => 'gps',
          7 => 'dispatch',
          8 => 'equipment',
          9 => 'visitors',
          10 => 'clients',
          11 => 'client_portal',
          12 => 'billing',
          13 => 'payroll',
          14 => 'compliance',
          15 => 'analytics',
          16 => 'marketplace',
          17 => 'messenger',
          18 => 'custom_reports',
          19 => 'estimates',
          20 => 'expenses',
          21 => 'sms_alerts',
          22 => 'workforce',
          23 => 'passdown',
          24 => 'webhooks',
          25 => 'api',
        ),
      ),
    ),
    'features' => 
    array (
      'guards' => 
      array (
        'label' => 'Guard management',
        'group' => 'Core',
      ),
      'schedules' => 
      array (
        'label' => 'Scheduling & rostering',
        'group' => 'Core',
      ),
      'attendance' => 
      array (
        'label' => 'Attendance & timekeeping',
        'group' => 'Core',
      ),
      'incidents' => 
      array (
        'label' => 'Incident reporting',
        'group' => 'Core',
      ),
      'reports' => 
      array (
        'label' => 'Daily reports',
        'group' => 'Core',
      ),
      'patrols' => 
      array (
        'label' => 'Patrol routes & checkpoints',
        'group' => 'Field',
      ),
      'gps' => 
      array (
        'label' => 'GPS tracking & playback',
        'group' => 'Field',
      ),
      'dispatch' => 
      array (
        'label' => 'Dispatch control room',
        'group' => 'Field',
      ),
      'equipment' => 
      array (
        'label' => 'Assets management',
        'group' => 'Assets',
      ),
      'visitors' => 
      array (
        'label' => 'Visitor logs',
        'group' => 'People',
      ),
      'clients' => 
      array (
        'label' => 'Clients & sites',
        'group' => 'Clients',
      ),
      'client_portal' => 
      array (
        'label' => 'Client portal',
        'group' => 'Clients',
      ),
      'billing' => 
      array (
        'label' => 'Billing & invoices',
        'group' => 'Back Office',
      ),
      'payroll' => 
      array (
        'label' => 'Payroll',
        'group' => 'Back Office',
      ),
      'estimates' => 
      array (
        'label' => 'Estimates & invoicer',
        'group' => 'Back Office',
      ),
      'expenses' => 
      array (
        'label' => 'Expenses',
        'group' => 'Back Office',
      ),
      'compliance' => 
      array (
        'label' => 'Compliance module',
        'group' => 'Back Office',
      ),
      'analytics' => 
      array (
        'label' => 'Analytics dashboard',
        'group' => 'Back Office',
      ),
      'marketplace' => 
      array (
        'label' => 'Shift marketplace',
        'group' => 'Insights',
      ),
      'custom_reports' => 
      array (
        'label' => 'Custom report builder',
        'group' => 'Insights',
      ),
      'messenger' => 
      array (
        'label' => 'Team messenger',
        'group' => 'Communication',
      ),
      'passdown' => 
      array (
        'label' => 'Passdown logs',
        'group' => 'Communication',
      ),
      'workforce' => 
      array (
        'label' => 'Shift templates & availability',
        'group' => 'Workforce',
      ),
      'sms_alerts' => 
      array (
        'label' => 'SMS alerts',
        'group' => 'Notifications',
      ),
      'webhooks' => 
      array (
        'label' => 'Outbound webhooks',
        'group' => 'Enterprise',
      ),
      'api' => 
      array (
        'label' => 'Enterprise API',
        'group' => 'Enterprise',
      ),
    ),
    'routes' => 
    array (
      'dispatch.control-room' => 'dispatch',
      'tracking.live' => 'gps',
      'schedules.index' => 'schedules',
      'schedules.calendar' => 'schedules',
      'schedules.deployment-sheet' => 'schedules',
      'schedules.templates' => 'schedules',
      'schedules.attendance' => 'attendance',
      'schedules.shift-status' => 'schedules',
      'schedules.open-shifts' => 'marketplace',
      'schedules.shift-exchange' => 'marketplace',
      'schedules.time-off' => 'workforce',
      'attendance.reconciliation' => 'attendance',
      'schedules.reconciliation' => 'attendance',
      'patrols.index' => 'patrols',
      'patrols.playback' => 'gps',
      'patrols.vehicles' => 'patrols',
      'passdown.index' => 'passdown',
      'incidents.index' => 'incidents',
      'reports.daily' => 'reports',
      'reports.templates' => 'custom_reports',
      'guards.index' => 'guards',
      'guards.kyg' => 'guards',
      'guards.show' => 'guards',
      'guard.mobile' => 'guards',
      'equipment.index' => 'equipment',
      'assets.overview' => 'equipment',
      'assets.index' => 'equipment',
      'assets.categories' => 'equipment',
      'assets.inventory' => 'equipment',
      'assets.vendors' => 'equipment',
      'assets.purchase-orders' => 'equipment',
      'visitors.index' => 'visitors',
      'clients.index' => 'clients',
      'clients.show' => 'clients',
      'sites.index' => 'clients',
      'sites.show' => 'clients',
      'clients.complaints' => 'clients',
      'billing.invoices' => 'billing',
      'billing.estimates' => 'estimates',
      'billing.expenses' => 'expenses',
      'billing.payments' => 'billing',
      'billing.payroll' => 'payroll',
      'billing.hub' => 'billing',
      'reports.hub' => 'reports',
      'messenger.index' => 'messenger',
      'client-portal.dashboard' => 'client_portal',
      'client-portal.approvals' => 'client_portal',
      'client-portal.invoices' => 'client_portal',
      'client-portal.invoices.show' => 'client_portal',
      'compliance.dashboard' => 'compliance',
      'compliance.policies' => 'compliance',
      'analytics.dashboard' => 'analytics',
      'settings.webhooks' => 'webhooks',
    ),
  ),
  'queue' => 
  array (
    'default' => 'redis',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => NULL,
        'secret' => NULL,
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
  ),
  'reverb' => 
  array (
    'default' => 'reverb',
    'servers' => 
    array (
      'reverb' => 
      array (
        'host' => '0.0.0.0',
        'port' => 8080,
        'hostname' => 'localhost',
        'options' => 
        array (
          'tls' => 
          array (
          ),
        ),
        'max_request_size' => 10000,
        'scaling' => 
        array (
          'enabled' => false,
          'channel' => 'reverb',
        ),
        'pulse_ingest_interval' => 15,
        'telescope_ingest_interval' => 15,
      ),
    ),
    'apps' => 
    array (
      'provider' => 'config',
      'apps' => 
      array (
        0 => 
        array (
          'key' => 'local-key',
          'secret' => 'local-secret',
          'app_id' => 'guardops',
          'options' => 
          array (
            'host' => 'localhost',
            'port' => '8081',
            'scheme' => 'http',
            'useTLS' => false,
          ),
          'allowed_origins' => 
          array (
            0 => '*',
          ),
          'ping_interval' => 60,
          'activity_timeout' => 30,
          'max_message_size' => 10000,
        ),
      ),
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => 'localhost:8080',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => NULL,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'scheduling' => 
  array (
    'shift_statuses' => 
    array (
      'draft' => 'Draft',
      'open' => 'Open',
      'scheduled' => 'Scheduled',
      'assigned' => 'Assigned',
      'confirmed' => 'Confirmed',
      'in_progress' => 'In progress',
      'completed' => 'Completed',
      'cancelled' => 'Cancelled',
    ),
    'assignment_statuses' => 
    array (
      'assigned' => 'Assigned',
      'confirmed' => 'Confirmed',
      'in_progress' => 'In progress',
      'completed' => 'Completed',
      'no_show' => 'No show',
      'cancelled' => 'Cancelled',
    ),
    'leave_statuses' => 
    array (
      'pending' => 'Pending',
      'approved' => 'Approved',
      'rejected' => 'Rejected',
      'cancelled' => 'Cancelled',
    ),
    'leave_types' => 
    array (
      'annual' => 'Annual leave',
      'sick' => 'Sick leave',
      'unpaid' => 'Unpaid leave',
      'emergency' => 'Emergency leave',
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => NULL,
      'secret' => NULL,
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
  ),
  'session' => 
  array (
    'driver' => 'redis',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/var/www/html/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'guardcore-pro-session',
    'path' => '/',
    'domain' => NULL,
    'secure' => false,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'site_profile' => 
  array (
    'document_types' => 
    array (
      'sop' => 'Standard operating procedure',
      'permit' => 'Permit / license',
      'insurance' => 'Insurance',
      'floor_plan' => 'Floor plan / map',
      'general' => 'General',
    ),
    'report_types' => 
    array (
      'daily_activity' => 'Daily activity report',
      'patrol_summary' => 'Patrol summary',
      'incidents' => 'Incident digest',
      'custom' => 'Custom report',
    ),
    'report_frequencies' => 
    array (
      'daily' => 'Daily',
      'weekly' => 'Weekly',
      'monthly' => 'Monthly',
    ),
    'default_settings' => 
    array (
      'require_geofence_clock_in' => true,
      'notify_on_incident' => true,
      'patrol_reminder_minutes' => 30,
      'show_in_client_portal' => true,
    ),
  ),
  'sso' => 
  array (
    'enabled' => false,
    'provider' => 'oidc',
    'client_id' => NULL,
    'client_secret' => NULL,
    'redirect_uri' => 'http://localhost:8080/auth/sso/callback',
    'issuer' => NULL,
  ),
  'tenancy' => 
  array (
    'tenant_model' => 'Stancl\\Tenancy\\Database\\Models\\Tenant',
    'id_generator' => 'Stancl\\Tenancy\\UUIDGenerator',
    'domain_model' => 'Stancl\\Tenancy\\Database\\Models\\Domain',
    'central_domains' => 
    array (
      0 => 'localhost',
      1 => '127.0.0.1',
    ),
    'bootstrappers' => 
    array (
      0 => 'Stancl\\Tenancy\\Bootstrappers\\DatabaseTenancyBootstrapper',
      1 => 'Stancl\\Tenancy\\Bootstrappers\\CacheTenancyBootstrapper',
      2 => 'Stancl\\Tenancy\\Bootstrappers\\FilesystemTenancyBootstrapper',
      3 => 'Stancl\\Tenancy\\Bootstrappers\\QueueTenancyBootstrapper',
    ),
    'database' => 
    array (
      'central_connection' => 'mysql',
      'template_tenant_connection' => NULL,
      'prefix' => 'tenant',
      'suffix' => '',
      'managers' => 
      array (
        'sqlite' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\SQLiteDatabaseManager',
        'mysql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\MySQLDatabaseManager',
        'mariadb' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\MySQLDatabaseManager',
        'pgsql' => 'Stancl\\Tenancy\\TenantDatabaseManagers\\PostgreSQLDatabaseManager',
      ),
    ),
    'cache' => 
    array (
      'tag_base' => 'tenant',
    ),
    'filesystem' => 
    array (
      'suffix_base' => 'tenant',
      'disks' => 
      array (
        0 => 'local',
        1 => 'public',
      ),
      'root_override' => 
      array (
        'local' => '%storage_path%/app/',
        'public' => '%storage_path%/app/public/',
      ),
      'suffix_storage_path' => true,
      'asset_helper_tenancy' => true,
    ),
    'redis' => 
    array (
      'prefix_base' => 'tenant',
      'prefixed_connections' => 
      array (
      ),
    ),
    'features' => 
    array (
    ),
    'routes' => true,
    'migration_parameters' => 
    array (
      '--force' => true,
      '--path' => 
      array (
        0 => '/var/www/html/database/migrations/tenant',
      ),
      '--realpath' => true,
    ),
    'seeder_parameters' => 
    array (
      '--class' => 'DatabaseSeeder',
    ),
    'base_domain' => 'guardops.test',
  ),
  'tenant_roles' => 
  array (
    'roles' => 
    array (
      'company-admin' => 
      array (
        0 => 'dashboard.view',
        1 => 'clients.manage',
        2 => 'sites.manage',
        3 => 'guards.manage',
        4 => 'schedules.manage',
        5 => 'attendance.manage',
        6 => 'patrols.manage',
        7 => 'incidents.manage',
        8 => 'reports.approve',
        9 => 'dispatch.manage',
        10 => 'billing.manage',
        11 => 'payroll.manage',
        12 => 'settings.manage',
        13 => 'audit.view',
        14 => 'client_portal.view',
        15 => 'mobile.use',
        16 => 'analytics.view',
        17 => 'compliance.manage',
        18 => 'equipment.manage',
        19 => 'visitors.manage',
        20 => 'exports.manage',
      ),
      'operations-manager' => 
      array (
        0 => 'dashboard.view',
        1 => 'clients.manage',
        2 => 'sites.manage',
        3 => 'guards.manage',
        4 => 'schedules.manage',
        5 => 'attendance.manage',
        6 => 'patrols.manage',
        7 => 'incidents.manage',
        8 => 'reports.approve',
        9 => 'dispatch.manage',
        10 => 'analytics.view',
        11 => 'compliance.manage',
        12 => 'equipment.manage',
        13 => 'visitors.manage',
        14 => 'audit.view',
      ),
      'supervisor' => 
      array (
        0 => 'dashboard.view',
        1 => 'attendance.manage',
        2 => 'patrols.manage',
        3 => 'incidents.manage',
        4 => 'reports.approve',
        5 => 'dispatch.manage',
        6 => 'audit.view',
      ),
      'guard' => 
      array (
        0 => 'mobile.use',
      ),
      'client' => 
      array (
        0 => 'client_portal.view',
      ),
      'finance' => 
      array (
        0 => 'dashboard.view',
        1 => 'billing.manage',
        2 => 'payroll.manage',
        3 => 'exports.manage',
        4 => 'analytics.view',
      ),
    ),
    'platform_roles' => 
    array (
      0 => 'super-admin',
    ),
  ),
  'dompdf' => 
  array (
    'show_warnings' => false,
    'public_path' => NULL,
    'convert_entities' => true,
    'options' => 
    array (
      'font_dir' => '/var/www/html/storage/fonts',
      'font_cache' => '/var/www/html/storage/fonts',
      'temp_dir' => '/tmp',
      'chroot' => '/var/www/html',
      'allowed_protocols' => 
      array (
        'data://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'file://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'http://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'https://' => 
        array (
          'rules' => 
          array (
          ),
        ),
      ),
      'artifactPathValidation' => NULL,
      'log_output_file' => NULL,
      'enable_font_subsetting' => false,
      'pdf_backend' => 'CPDF',
      'default_media_type' => 'screen',
      'default_paper_size' => 'a4',
      'default_paper_orientation' => 'portrait',
      'default_font' => 'serif',
      'dpi' => 96,
      'enable_php' => false,
      'enable_javascript' => true,
      'enable_remote' => false,
      'allowed_remote_hosts' => NULL,
      'font_height_ratio' => 1.1,
      'enable_html5_parser' => true,
    ),
  ),
  'livewire' => 
  array (
    'class_namespace' => 'App\\Livewire',
    'view_path' => '/var/www/html/resources/views/livewire',
    'layout' => 'components.layouts.app',
    'lazy_placeholder' => NULL,
    'temporary_file_upload' => 
    array (
      'disk' => NULL,
      'rules' => NULL,
      'directory' => NULL,
      'middleware' => NULL,
      'preview_mimes' => 
      array (
        0 => 'png',
        1 => 'gif',
        2 => 'bmp',
        3 => 'svg',
        4 => 'wav',
        5 => 'mp4',
        6 => 'mov',
        7 => 'avi',
        8 => 'wmv',
        9 => 'mp3',
        10 => 'm4a',
        11 => 'jpg',
        12 => 'jpeg',
        13 => 'mpga',
        14 => 'webp',
        15 => 'wma',
      ),
      'max_upload_time' => 5,
      'cleanup' => true,
    ),
    'render_on_redirect' => false,
    'legacy_model_binding' => false,
    'inject_assets' => true,
    'navigate' => 
    array (
      'show_progress_bar' => true,
      'progress_bar_color' => '#2299dd',
    ),
    'inject_morph_markers' => true,
    'smart_wire_keys' => false,
    'pagination_theme' => 'tailwind',
    'release_token' => 'a',
  ),
  'excel' => 
  array (
    'exports' => 
    array (
      'chunk_size' => 1000,
      'pre_calculate_formulas' => false,
      'strict_null_comparison' => false,
      'csv' => 
      array (
        'delimiter' => ',',
        'enclosure' => '"',
        'line_ending' => '
',
        'use_bom' => false,
        'include_separator_line' => false,
        'excel_compatibility' => false,
        'output_encoding' => '',
        'test_auto_detect' => true,
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
    ),
    'imports' => 
    array (
      'read_only' => true,
      'ignore_empty' => false,
      'heading_row' => 
      array (
        'formatter' => 'slug',
      ),
      'csv' => 
      array (
        'delimiter' => NULL,
        'enclosure' => '"',
        'escape_character' => '\\',
        'contiguous' => false,
        'input_encoding' => 'guess',
      ),
      'properties' => 
      array (
        'creator' => '',
        'lastModifiedBy' => '',
        'title' => '',
        'description' => '',
        'subject' => '',
        'keywords' => '',
        'category' => '',
        'manager' => '',
        'company' => '',
      ),
      'cells' => 
      array (
        'middleware' => 
        array (
        ),
      ),
    ),
    'extension_detector' => 
    array (
      'xlsx' => 'Xlsx',
      'xlsm' => 'Xlsx',
      'xltx' => 'Xlsx',
      'xltm' => 'Xlsx',
      'xls' => 'Xls',
      'xlt' => 'Xls',
      'ods' => 'Ods',
      'ots' => 'Ods',
      'slk' => 'Slk',
      'xml' => 'Xml',
      'gnumeric' => 'Gnumeric',
      'htm' => 'Html',
      'html' => 'Html',
      'csv' => 'Csv',
      'tsv' => 'Csv',
      'pdf' => 'Dompdf',
    ),
    'value_binder' => 
    array (
      'default' => 'Maatwebsite\\Excel\\DefaultValueBinder',
    ),
    'cache' => 
    array (
      'driver' => 'memory',
      'batch' => 
      array (
        'memory_limit' => 60000,
      ),
      'illuminate' => 
      array (
        'store' => NULL,
      ),
      'default_ttl' => 10800,
    ),
    'transactions' => 
    array (
      'handler' => 'db',
      'db' => 
      array (
        'connection' => NULL,
      ),
    ),
    'temporary_files' => 
    array (
      'local_path' => '/var/www/html/storage/framework/cache/laravel-excel',
      'local_permissions' => 
      array (
      ),
      'remote_disk' => NULL,
      'remote_prefix' => NULL,
      'force_resync_remote' => NULL,
    ),
  ),
  'activitylog' => 
  array (
    'enabled' => true,
    'delete_records_older_than_days' => 365,
    'default_log_name' => 'default',
    'default_auth_driver' => NULL,
    'subject_returns_soft_deleted_models' => false,
    'activity_model' => 'Spatie\\Activitylog\\Models\\Activity',
    'table_name' => 'activity_log',
    'database_connection' => NULL,
  ),
);

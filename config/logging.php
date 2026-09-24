<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [

        'stack' => [
            'driver'            => 'stack',
            'channels'          => explode(',', (string) env('LOG_STACK', 'daily')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver'              => 'single',
            'path'                => storage_path('logs/laravel.log'),
            'level'               => env('LOG_LEVEL', 'debug'),
            'replace_placeholders'=> true,
        ],

        'daily' => [
            'driver'              => 'daily',
            'path'                => storage_path('logs/laravel.log'),
            'level'               => env('LOG_LEVEL', 'debug'),
            'days'                => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders'=> true,
        ],

        // ─── ShaloTrack application error log ───────────────────────────────
        // Used exclusively by the withExceptions() pipeline in bootstrap/app.php.
        // Every entry includes a reference_id, category, summary, exception class,
        // file, line, URL, HTTP method, admin_id, IP, and user_agent — so a support
        // person can go straight from the ref the user reads out to a full trace
        // without grepping the general laravel.log for a needle in a haystack.
        //
        // Kept separate from the general laravel.log so it can be:
        //   • shipped to CloudWatch Logs under its own log group in production
        //   • grepped in isolation without noise from framework debug messages
        //   • rotated on a longer schedule (30 days vs 14) since these are support records
        //
        // Set LOG_APP_ERRORS_CHANNEL=stderr in your ECS task definition to send
        // these to stdout/stderr instead, where the CloudWatch log driver picks
        // them up automatically (zero extra config needed on ECS Fargate).
        'app_errors' => [
            'driver'              => env('LOG_APP_ERRORS_DRIVER', 'daily'),
            'path'                => storage_path('logs/app_errors.log'),
            'level'               => 'debug',
            'days'                => 30,
            'replace_placeholders'=> true,
        ],

        'slack' => [
            'driver'              => 'slack',
            'url'                 => env('LOG_SLACK_WEBHOOK_URL'),
            'username'            => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji'               => env('LOG_SLACK_EMOJI', ':boom:'),
            'level'               => env('LOG_LEVEL', 'critical'),
            'replace_placeholders'=> true,
        ],

        'papertrail' => [
            'driver'       => 'monolog',
            'level'        => env('LOG_LEVEL', 'debug'),
            'handler'      => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host'             => env('PAPERTRAIL_URL'),
                'port'             => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver'      => 'monolog',
            'level'       => env('LOG_LEVEL', 'debug'),
            'handler'     => StreamHandler::class,
            'handler_with'=> [
                'stream' => 'php://stderr',
            ],
            'formatter'  => env('LOG_STDERR_FORMATTER'),
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver'              => 'syslog',
            'level'               => env('LOG_LEVEL', 'debug'),
            'facility'            => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders'=> true,
        ],

        'errorlog' => [
            'driver'              => 'errorlog',
            'level'               => env('LOG_LEVEL', 'debug'),
            'replace_placeholders'=> true,
        ],

        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
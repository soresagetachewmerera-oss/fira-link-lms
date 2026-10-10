<?php

return [
    'installed_marker' => storage_path('installed'),

    'requirements' => [
        'php' => [
            'min_version' => '8.2.0',
        ],
        'extensions' => [
            'openssl',
            'pdo',
            'mbstring',
            'tokenizer',
            'json',
            'curl',
            'fileinfo',
            'bcmath',
        ],
    ],

    'permissions' => [
        'storage' => '775',
        'storage/framework' => '775',
        'storage/logs' => '775',
        'bootstrap/cache' => '775',
    ],

    'environment' => [
        'sections' => [
            [
                'title' => 'Application',
                'description' => 'Update the core app configuration.',
                'fields' => [
                    'APP_NAME' => [
                        'label' => 'Application Name',
                        'rules' => 'required|string|max:50',
                        'type' => 'text',
                        'default' => 'EduEx',
                    ],
                    'APP_ENV' => [
                        'label' => 'Environment',
                        'rules' => 'required|string|in:local,staging,production',
                        'type' => 'select',
                        'options' => [
                            'local' => 'Local',
                            'staging' => 'Staging',
                            'production' => 'Production',
                        ],
                        'default' => 'local',
                    ],
                    'APP_DEBUG' => [
                        'label' => 'Debug Mode',
                        'rules' => 'required',
                        'type' => 'toggle',
                        'default' => true,
                    ],
                    'APP_URL' => [
                        'label' => 'Application URL',
                        'rules' => 'required|url',
                        'type' => 'text',
                        'default' => 'http://localhost',
                    ],
                    'APP_LOCALE' => [
                        'label' => 'Default Locale',
                        'rules' => 'required|string|max:5',
                        'type' => 'text',
                        'default' => 'en',
                    ],
                ],
            ],
            [
                'title' => 'Database',
                'description' => 'Configure your database connection.',
                'fields' => [
                    'DB_CONNECTION' => [
                        'label' => 'Driver',
                        'rules' => 'required|string|in:sqlite,mysql,pgsql,sqlsrv',
                        'type' => 'select',
                        'options' => [
                            'sqlite' => 'SQLite',
                            'mysql' => 'MySQL / MariaDB',
                            'pgsql' => 'PostgreSQL',
                            'sqlsrv' => 'SQL Server',
                        ],
                        'default' => 'mysql',
                    ],
                    'DB_HOST' => [
                        'label' => 'Host',
                        'rules' => 'nullable|string|max:100',
                        'type' => 'text',
                        'default' => '127.0.0.1',
                    ],
                    'DB_PORT' => [
                        'label' => 'Port',
                        'rules' => 'nullable|integer',
                        'type' => 'text',
                        'default' => '3306',
                    ],
                    'DB_DATABASE' => [
                        'label' => 'Database',
                        'rules' => 'required_unless:DB_CONNECTION,sqlite|string|max:100',
                        'type' => 'text',
                        'default' => 'eduex',
                    ],
                    'DB_USERNAME' => [
                        'label' => 'Username',
                        'rules' => 'nullable|string|max:100',
                        'type' => 'text',
                        'default' => 'root',
                    ],
                    'DB_PASSWORD' => [
                        'label' => 'Password',
                        'rules' => 'nullable|string|max:100',
                        'type' => 'password',
                        'default' => '',
                    ],
                ],
            ],
            [
                'title' => 'Cache & Queue',
                'description' => 'Select the drivers for caching, queues and sessions.',
                'fields' => [
                    'CACHE_STORE' => [
                        'label' => 'Cache Driver',
                        'rules' => 'required|string|in:file,database,redis,array',
                        'type' => 'select',
                        'options' => [
                            'file' => 'File',
                            'database' => 'Database',
                            'redis' => 'Redis',
                            'array' => 'Array (ephemeral)',
                        ],
                        'default' => 'database',
                    ],
                    'QUEUE_CONNECTION' => [
                        'label' => 'Queue Driver',
                        'rules' => 'required|string|in:sync,database,redis,beanstalkd,sqs,null',
                        'type' => 'select',
                        'options' => [
                            'sync' => 'Sync (development)',
                            'database' => 'Database',
                            'redis' => 'Redis',
                            'beanstalkd' => 'Beanstalkd',
                            'sqs' => 'Amazon SQS',
                            'null' => 'Disabled',
                        ],
                        'default' => 'database',
                    ],
                    'SESSION_DRIVER' => [
                        'label' => 'Session Driver',
                        'rules' => 'required|string|in:file,database,redis,array',
                        'type' => 'select',
                        'options' => [
                            'file' => 'File',
                            'database' => 'Database',
                            'redis' => 'Redis',
                            'array' => 'Array (testing)',
                        ],
                        'default' => 'database',
                    ],
                    'SESSION_LIFETIME' => [
                        'label' => 'Session Lifetime (minutes)',
                        'rules' => 'required|integer|min:5|max:10080',
                        'type' => 'number',
                        'default' => 120,
                    ],
                ],
            ],
            [
                'title' => 'Mail',
                'description' => 'Configure outbound email settings.',
                'fields' => [
                    'MAIL_MAILER' => [
                        'label' => 'Mailer',
                        'rules' => 'required|string|in:smtp,sendmail,log,mailgun,ses,postmark,resend,array',
                        'type' => 'select',
                        'options' => [
                            'smtp' => 'SMTP',
                            'sendmail' => 'Sendmail',
                            'log' => 'Log (development)',
                            'mailgun' => 'Mailgun',
                            'ses' => 'Amazon SES',
                            'postmark' => 'Postmark',
                            'resend' => 'Resend',
                            'array' => 'Array (testing)',
                        ],
                        'default' => 'log',
                    ],
                    'MAIL_HOST' => [
                        'label' => 'Host',
                        'rules' => 'nullable|string|max:100',
                        'type' => 'text',
                        'default' => '127.0.0.1',
                    ],
                    'MAIL_PORT' => [
                        'label' => 'Port',
                        'rules' => 'nullable|integer',
                        'type' => 'text',
                        'default' => '2525',
                    ],
                    'MAIL_USERNAME' => [
                        'label' => 'Username',
                        'rules' => 'nullable|string|max:100',
                        'type' => 'text',
                        'default' => null,
                    ],
                    'MAIL_PASSWORD' => [
                        'label' => 'Password',
                        'rules' => 'nullable|string|max:100',
                        'type' => 'password',
                        'default' => null,
                    ],
                    'MAIL_ENCRYPTION' => [
                        'label' => 'Encryption',
                        'rules' => 'nullable|string|max:10',
                        'type' => 'text',
                        'default' => null,
                    ],
                    'MAIL_FROM_ADDRESS' => [
                        'label' => 'From Address',
                        'rules' => 'required|email',
                        'type' => 'text',
                        'default' => 'hello@example.com',
                    ],
                    'MAIL_FROM_NAME' => [
                        'label' => 'From Name',
                        'rules' => 'required|string|max:100',
                        'type' => 'text',
                        'default' => 'EduEx',
                    ],
                ],
            ],
        ],
    ],

    'final_commands' => [
        [
            'command' => 'key:generate',
            'options' => ['--force' => true],
        ],
        [
            'command' => 'storage:link',
            'options' => ['--force' => true],
        ],
        [
            'command' => 'optimize:clear',
            'options' => [],
        ],
    ],
];


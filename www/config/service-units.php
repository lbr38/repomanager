<?php
/**
 *  Configure background service units
 *  The unit must have its own Controller class in the \Controllers\Service\Unit namespace
 *  The unit must have a method that hold the logic to execute or call other controllers/services
 */
$units = [
    // This cleans temporary files every hour
    'cleanup-temp-files' => [
        'title' => 'Temporary files cleanup',
        'description' => 'Ensures temporary files under Repomanager data directory are cleaned',
        'controller' => 'Service\Unit\Cleanup\File',
        'method' => 'run',
        'frequency' => 'every-day',
        'time' => '00:00',
        'log-dir' => 'cleanup/temporary-files'
    ],
    // This cleans old history logs every day at midnight
    'cleanup-history' => [
        'title' => 'History logs cleanup',
        'description' => 'Ensures old history logs are cleaned (older than 1 year)',
        'controller' => 'Service\Unit\Cleanup\History',
        'method' => 'run',
        'frequency' => 'every-day',
        'time' => '00:00',
        'log-dir' => 'cleanup/history'
    ],
    // This cleans old tasks every day at midnight
    'cleanup-tasks' => [
        'title' => 'Tasks logs cleanup',
        'description' => 'Ensures old tasks logs are cleaned (older than the value defined in settings)',
        'controller' => 'Service\Unit\Cleanup\Task',
        'method' => 'run',
        'frequency' => 'every-day',
        'time' => '00:00',
        'log-dir' => 'cleanup/tasks'
    ],
    // This retrieves notifications from github every hour
    'notifications' => [
        'title' => 'Notifications',
        'description' => 'Retrieve new notifications from GitHub',
        'controller' => 'Service\Unit\Notification',
        'method' => 'get',
        'frequency' => 'every-hour'
    ],
    // This monitors CPU, memory and disk usage every minute
    'system-monitoring' => [
        'title' => 'System monitoring',
        'description' => 'Monitors CPU, memory and disk usage every minute',
        'controller' => 'Service\Unit\Monitoring',
        'method' => 'monitor',
        'frequency' => 'every-minute',
        'log-dir' => 'system/monitoring'
    ],
    // This generates statistics of repositories (size, packages count) every day at midnight
    'stats-generate' => [
        'title' => 'Repositories statistics generation',
        'description' => 'Generates repositories statistics (size, packages count) every day at midnight',
        'controller' => 'Service\Unit\Statistic',
        'method' => 'generate',
        'frequency' => 'every-day',
        'time' => '00:00',
        'log-dir' => 'stats/generate'
    ],
    // This parses nginx access logs to generate repositories access statistics
    'stats-parse' => [
        'title' => 'Access logs parsing',
        'description' => 'Parses webserver access logs to catch repository accesses and generate statistics. The catched accesses are stored in a queue to be processed by another service unit.',
        'controller' => 'Service\Unit\Statistic',
        'method' => 'parseLogs',
        // Make sure the stats parsing service is always running
        'frequency' => 'forever',
        'log-dir' => 'stats/parse'
    ],
    // This processes the repositories access statistics queue
    'stats-process' => [
        'title' => 'Access logs statistics processing',
        'description' => 'Processing repositories access statistics queue',
        'controller' => 'Service\Unit\Statistic',
        'method' => 'processQueue',
        // Make sure the stats processing service is always running
        'frequency' => 'every-minute',
        'log-dir' => 'stats/process'
    ],
    // This cleans old repositories statistics every day at midnight
    'cleanup-stats' => [
        'title' => 'Statistics cleanup',
        'description' => 'Cleans old repositories statistics (older than 1 year)',
        'controller' => 'Service\Unit\Statistic',
        'method' => 'clean',
        'frequency' => 'every-day',
        'time' => '00:00',
        'log-dir' => 'cleanup/stats'
    ],
    // This performs a VACUUM/ANALYZE and integrity check on the databases every sunday at 1am
    'db-maintenance' => [
        'title' => 'Databases maintenance',
        'description' => 'Performs a VACUUM/ANALYZE and integrity check on the databases',
        'controller' => 'Service\Unit\Database',
        'method' => 'maintenance',
        'frequency' => 'every-week',
        'day' => 'sunday',
        'time' => '01:00',
        'log-dir' => 'db/maintenance'
    ],
    // This executes scheduled tasks every minute
    'scheduled-tasks-exec' => [
        'title' => 'Scheduled tasks',
        'description' => 'Scheduled tasks execution',
        'controller' => 'Service\Unit\ScheduledTask',
        'method' => 'execute',
        'frequency' => 'every-minute',
        // Force the execution of scheduled tasks every minute, even if another instance is already running
        // Because scheduled tasks can take more than a minute to execute, and we want to make sure that all tasks are executed at their scheduled time
        'force' => true,
        'log-dir' => 'scheduled-tasks/execute'
    ],
    // This sends reminders for upcoming scheduled tasks every day at midnight
    'scheduled-tasks-reminders' => [
        'title' => 'Scheduled tasks reminders',
        'description' => 'Sends reminders for upcoming scheduled tasks',
        'controller' => 'Service\Unit\ScheduledTask',
        'method' => 'sendReminders',
        // Task reminders are sent at midnight
        'frequency' => 'every-day',
        'time' => '00:00',
        'log-dir' => 'scheduled-tasks/reminders'
    ],
    // This checks for new version of the application every hour
    'version-check' => [
        'title' => 'Version check',
        'description' => 'Checks for new version of the application',
        'controller' => 'Service\Unit\Version',
        'method' => 'get',
        'frequency' => 'every-hour',
    ],
    // This runs the websocket server
    'wss' => [
        'title' => 'Websocket server',
        'description' => 'Runs the websocket server to handle real-time communications with browser clients',
        'controller' => 'Service\Unit\WebsocketServer',
        'method' => 'run',
        // Make sure the websocket server is always running
        'frequency' => 'forever'
    ],
    // CVE disabled for now, I do not have time to maintain it
    // 'cve-import' => [
    //     'description' => 'CVE database import',
    //     'controller' => 'Service\Unit\Cve',
    //     'method' => 'import'
    // ],
];

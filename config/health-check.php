<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Health Check Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait for a response from the health check
    | endpoint before considering it a failure.
    |
    */

    'timeout' => env('HEALTH_CHECK_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Enable Email Notifications
    |--------------------------------------------------------------------------
    |
    | When enabled, email notifications will be sent to the configured
    | recipients when a health check fails.
    |
    */

    'notifications_enabled' => env('HEALTH_CHECK_NOTIFICATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Admin Email Addresses
    |--------------------------------------------------------------------------
    |
    | A comma-separated list of email addresses that will receive notifications
    | when health check batch jobs fail.
    |
    */

    'admin_emails' => env('HEALTH_CHECK_ADMIN_EMAILS', ''),

    /*
    |--------------------------------------------------------------------------
    | Verify SSL Certificates
    |--------------------------------------------------------------------------
    |
    | When disabled, SSL certificate verification will be skipped for all
    | health check requests. This is useful for development or when checking
    | endpoints with self-signed certificates.
    |
    */

    'verify_ssl' => env('HEALTH_CHECK_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Notification Escalation Intervals
    |--------------------------------------------------------------------------
    |
    | Maps consecutive failure count ranges to notification intervals (minutes).
    | Format: [min_failures, max_failures] => interval_in_minutes
    |
    | Examples:
    | - Failures 1: Immediate (0 min interval)
    | - Failures 2-5: Every check (1 min)
    | - Failures 6-15: Every 5 minutes
    | - Failures 61+: Hourly
    |
    */

    'escalation' => [
        ['min' => 1, 'max' => 1, 'interval' => 0],              // First failure: immediate
        ['min' => 2, 'max' => 5, 'interval' => 0],              // Failures 2-5: every minute (rapid detection)
        ['min' => 6, 'max' => 15, 'interval' => 5],             // Failures 6-15: every 5 minutes
        ['min' => 16, 'max' => 30, 'interval' => 15],           // Failures 16-30: every 15 minutes
        ['min' => 31, 'max' => 60, 'interval' => 30],           // Failures 31-60: every 30 minutes
        ['min' => 61, 'max' => PHP_INT_MAX, 'interval' => 60],  // Failures 61+: hourly
    ],

    /*
    |--------------------------------------------------------------------------
    | Recovery Notifications
    |--------------------------------------------------------------------------
    |
    | Send notification when a previously failing service becomes healthy.
    | Includes downtime duration and failure count.
    |
    */

    'recovery_notifications_enabled' => env('HEALTH_CHECK_RECOVERY_NOTIFICATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Confirmation Retry
    |--------------------------------------------------------------------------
    |
    | When enabled, a failed health check will be retried after a delay before
    | recording the failure and sending notifications. This helps reduce false
    | positives from transient network issues.
    |
    */

    'confirmation_retry_enabled' => env('HEALTH_CHECK_CONFIRMATION_RETRY', true),

    /*
    |--------------------------------------------------------------------------
    | Confirmation Retry Delay
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait before retrying a failed health check.
    | Only applies when confirmation_retry_enabled is true.
    |
    */

    'confirmation_retry_delay' => env('HEALTH_CHECK_CONFIRMATION_DELAY', 30),

];

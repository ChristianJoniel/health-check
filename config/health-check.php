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

];

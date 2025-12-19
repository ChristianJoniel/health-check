<x-mail::message>
# Health Check Recovered ✅

Good news! The health check for **{{ $projectName }}** has recovered and is now healthy.

<x-mail::panel>
**URL:** {{ $url }}

**Downtime Duration:** {{ $downtimeDuration }}

**Total Failed Checks:** {{ $consecutiveFailures }}

**First Failed:** {{ $firstFailedAt?->format('M j, Y g:i A') ?? 'N/A' }}

**Recovered:** {{ $recoveredAt?->format('M j, Y g:i A') }}
</x-mail::panel>

The service is back online and operating normally.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

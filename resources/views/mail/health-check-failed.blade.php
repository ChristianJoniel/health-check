<x-mail::message>
# Health Check Failed

A health check has failed for **{{ $projectName }}**.

<x-mail::panel>
**URL:** {{ $url }}

**Error:** {{ $errorMessage ?? 'No error message' }}

**Response Code:** {{ $responseCode ?? 'N/A' }}

**Response Time:** {{ $responseTime ? $responseTime . 'ms' : 'N/A' }}

**Checked At:** {{ $checkedAt->format('M j, Y g:i A') }}
</x-mail::panel>

Please investigate this issue as soon as possible.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

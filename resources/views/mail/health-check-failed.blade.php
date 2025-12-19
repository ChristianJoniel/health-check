<x-mail::message>
# Health Check Failed

@if($isFirstFailure)
A health check has **just started failing** for **{{ $projectName }}**.
@else
The health check for **{{ $projectName }}** is **still failing**.

This is failure **#{{ $consecutiveFailures }}** since {{ $failingSince?->format('M j, Y g:i A') }}
({{ $failingSince?->diffForHumans() }}).
@endif

<x-mail::panel>
**URL:** {{ $url }}

**Error:** {{ $errorMessage ?? 'No error message' }}

**Response Code:** {{ $responseCode ?? 'N/A' }}

**Response Time:** {{ $responseTime ? $responseTime . 'ms' : 'N/A' }}

**Checked At:** {{ $checkedAt->format('M j, Y g:i A') }}

@if(!$isFirstFailure)
**Failing Since:** {{ $failingSince?->format('M j, Y g:i A') }} ({{ $failingSince?->diffForHumans() }})

**Consecutive Failures:** {{ $consecutiveFailures }}
@endif
</x-mail::panel>

@if($isFirstFailure)
Please investigate this issue as soon as possible.
@else
This issue requires immediate attention as it has been ongoing for {{ $failingSince?->diffForHumans(null, true) }}.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

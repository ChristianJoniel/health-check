<x-mail::message>
# Health Check Batch Failed

A health check batch job has encountered an error.

<x-mail::panel>
**Project:** {{ $projectName }}

**Health Check URL:** {{ $projectUrl }}

**Error:** {{ $errorMessage }}

**Batch ID:** {{ $batchId }}

**Batch Name:** {{ $batchName }}

**Failed Jobs:** {{ $failedJobs }} of {{ $totalJobs }}
</x-mail::panel>

Please investigate this issue as soon as possible.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>


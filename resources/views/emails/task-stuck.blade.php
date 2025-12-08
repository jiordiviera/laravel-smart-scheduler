<x-mail::message>
# Scheduled Task Stuck

A scheduled task appears to be stuck on server **{{ $run->server_name }}**.

The task has been running for more than **{{ $timeout }} minutes** without completing. This may indicate:
- The process was killed unexpectedly
- The server crashed or restarted
- A deadlock or infinite loop in the task

## Task Details

- **Task:** {{ $run->task_identifier }}
- **Started At:** {{ $run->started_at->format('Y-m-d H:i:s') }}
- **Detected At:** {{ $run->finished_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s') }}
- **Running Time:** {{ $run->started_at->diffForHumans(now(), true) }}
- **Server:** {{ $run->server_name }}

@if($run->exception)
## Status

```
{{ $run->exception }}
```
@endif

The task has been marked as stuck and future executions will be allowed to proceed.

Please investigate the root cause to prevent this from happening again.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

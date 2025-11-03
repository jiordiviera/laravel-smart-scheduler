<x-mail::message>
# Scheduled Task Failed

A scheduled task has failed on server **{{ $run->server_name }}**.

## Task Details

- **Task:** {{ $run->task_identifier }}
- **Started At:** {{ $run->started_at->format('Y-m-d H:i:s') }}
- **Duration:** {{ $run->duration ? number_format($run->duration, 3) . 's' : 'N/A' }}
- **Server:** {{ $run->server_name }}

@if($run->exception)
## Error

```
{{ $run->exception }}
```
@endif

@if($run->output)
## Output

```
{{ $run->output }}
```
@endif

Please investigate and resolve the issue.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

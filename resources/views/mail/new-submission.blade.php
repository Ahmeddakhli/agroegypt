<x-mail::message>
# {{ $isApplication ? 'New job application' : 'New contact message' }}

**Name:** {{ $submission->name }}<br>
**Email:** {{ $submission->email }}<br>
@if ($submission->phone)
**Phone:** {{ $submission->phone }}<br>
@endif
@if ($isApplication)
**Job title:** {{ $submission->job_title }}<br>
@endif
**Language:** {{ $submission->locale }}

@if ($submission->message)
<x-mail::panel>
{{ $submission->message }}
</x-mail::panel>
@endif

<x-mail::button :url="$adminUrl">
Open the admin dashboard
</x-mail::button>
</x-mail::message>

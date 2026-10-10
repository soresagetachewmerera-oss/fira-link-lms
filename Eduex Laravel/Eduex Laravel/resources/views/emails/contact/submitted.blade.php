@component('mail::message')
# New Contact Message

You have received a new message from the {{ site_name() }} contact form.

- **Name:** {{ $data['name'] ?? 'N/A' }}
- **Email:** {{ $data['email'] ?? 'N/A' }}
@isset($data['phone'])
- **Phone:** {{ $data['phone'] }}
@endisset
- **Subject:** {{ $data['subject'] ?? 'N/A' }}

@component('mail::panel')
{{ $data['message'] ?? 'No message provided.' }}
@endcomponent

@isset($data['user'])
- **Submitted by user:** {{ $data['user']['name'] ?? 'Unknown' }} (ID: {{ $data['user']['id'] ?? 'N/A' }})
@endisset
- **IP Address:** {{ $data['ip'] ?? 'N/A' }}
- **User Agent:** {{ $data['user_agent'] ?? 'N/A' }}

@component('mail::button', ['url' => config('app.url')])
Open Dashboard
@endcomponent

Thanks,<br>
{{ site_name() }}
@endcomponent

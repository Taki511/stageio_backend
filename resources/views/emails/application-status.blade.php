<x-mail::message>
# Application Status Update

@switch($data['notification_type'])

@case('application_accepted')
## Congratulations {{ $data['student_name'] }}! 🎉

Your application for **{{ $data['offer_title'] }}** at **{{ $data['company_name'] }}** has been **ACCEPTED**!

The recruiter is interested in your profile and would like to proceed with your application.

### ⏰ Important: Confirmation Required

You have **{{ $data['days_to_confirm'] }} days** to confirm this offer.

**Confirmation Deadline:** {{ $data['confirm_deadline'] }}

If you don't confirm within this period, your application will be automatically cancelled.

<x-mail::button :url="$data['action_url']" color="primary">
{{ $data['action_text'] }}
</x-mail::button>

**Note:** Once you confirm, all your other accepted applications will be automatically cancelled.
@break

@case('application_refused')
## Hello {{ $data['student_name'] }},

We regret to inform you that your application for **{{ $data['offer_title'] }}** at **{{ $data['company_name'] }}** has not been selected.

Don't be discouraged! There are many other opportunities waiting for you.

<x-mail::button :url="$data['action_url']" color="primary">
{{ $data['action_text'] }}
</x-mail::button>
@break

@case('internship_validated')
## Congratulations {{ $data['student_name'] }}! 🎉

Your internship has been **VALIDATED** by the university administration!

### Internship Details:

- **Position:** {{ $data['offer_title'] }}
- **Company:** {{ $data['company_name'] }}
- **Start Date:** {{ $data['start_date'] }}
- **End Date:** {{ $data['end_date'] }}

Your internship agreement is now being generated. You will receive another email once it's ready for download.

<x-mail::button :url="$data['action_url']" color="primary">
{{ $data['action_text'] }}
</x-mail::button>
@break

@case('application_rejected')
## Hello {{ $data['student_name'] }},

We regret to inform you that your application for **{{ $data['offer_title'] }}** at **{{ $data['company_name'] }}** has been **rejected** by the university administration.

For more information, please contact your university's internship office.

<x-mail::button :url="$data['action_url']" color="primary">
{{ $data['action_text'] }}
</x-mail::button>
@break

@default
Status update for your application.
@endswitch

---

Best regards,<br>
{{ config('app.name') }} Team

<x-mail::subcopy>
If you're having trouble clicking the "{{ $data['action_text'] }}" button, copy and paste the URL below into your web browser: {{ $data['action_url'] }}
</x-mail::subcopy>
</x-mail::message>

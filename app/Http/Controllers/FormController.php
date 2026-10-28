<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareerApplicationRequest;
use App\Http\Requests\ContactRequest;
use App\Mail\NewSubmissionMail;
use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function contact(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->safe()->only(['name', 'email', 'message']),
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $this->notify('mail.notify_contact', $message);

        return back()->with('form_success', 'contact')->withFragment('contact-form');
    }

    public function careers(CareerApplicationRequest $request): RedirectResponse
    {
        $cv = $request->file('cv');

        $application = JobApplication::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'job_title', 'message']),
            'cv_path' => $cv->store('applications', 'local'),
            'cv_original_name' => substr($cv->getClientOriginalName(), 0, 255),
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
        ]);

        $this->notify('mail.notify_careers', $application);

        return back()->with('form_success', 'careers')->withFragment('careers-form');
    }

    /**
     * Email the recipients configured in settings (comma-separated), when notifications are enabled.
     */
    private function notify(string $settingKey, ContactMessage|JobApplication $submission): void
    {
        $recipients = array_filter(array_map('trim', explode(',', (string) Setting::get($settingKey, ''))), fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        if ($recipients !== [] && Setting::get('mail.notifications_enabled', '1') === '1') {
            Mail::to($recipients)->queue(new NewSubmissionMail($submission));
        }
    }
}

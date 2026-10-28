<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Admin notification for a new contact message or job application.
 */
class NewSubmissionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage|JobApplication $submission) {}

    public function envelope(): Envelope
    {
        $kind = $this->submission instanceof JobApplication ? 'New job application' : 'New contact message';

        return new Envelope(
            replyTo: [new Address($this->submission->email, $this->submission->name)],
            subject: "{$kind} from {$this->submission->name}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-submission', with: [
            'isApplication' => $this->submission instanceof JobApplication,
            'adminUrl' => url('/admin'),
        ]);
    }
}

<?php

namespace App\Mail;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a portal candidate immediately after successful submission.
 *
 * Contains their anonymized profile preview and a link to their token-based
 * status page so they can check their application progress without an account.
 * Queued via the Redis queue worker (ShouldQueue).
 */
class CandidateConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Candidate  $candidate  The freshly processed candidate record.
     */
    public function __construct(public Candidate $candidate) {}

    /**
     * Build the envelope with the job title in the subject line.
     */
    public function envelope(): Envelope
    {
        $jobTitle = $this->candidate->job->title ?? 'Position';

        return new Envelope(subject: "Application Received — {$jobTitle}");
    }

    /**
     * Specify the view template for the email body.
     */
    public function content(): Content
    {
        return new Content(view: 'mail.candidate-confirmation');
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

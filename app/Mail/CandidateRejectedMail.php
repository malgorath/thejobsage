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
 * Sent to a candidate after an HR user completes the rejection form.
 *
 * Contains the PII-stripped rejection note and the LLM-generated skill gap
 * summary. Both fields have been sanitized before storage and are safe to
 * include verbatim. Queued via the Redis queue worker (ShouldQueue).
 */
class CandidateRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Candidate  $candidate  The freshly rejected candidate (use ->fresh() to ensure updated fields).
     */
    public function __construct(public Candidate $candidate) {}

    /**
     * Build the envelope with the job title in the subject line.
     */
    public function envelope(): Envelope
    {
        $jobTitle = $this->candidate->job->title ?? 'Position';

        return new Envelope(subject: "Update on Your Application — {$jobTitle}");
    }

    /**
     * Specify the view template for the email body.
     */
    public function content(): Content
    {
        return new Content(view: 'mail.candidate-rejected');
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

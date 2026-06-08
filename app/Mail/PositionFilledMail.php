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
 * Sent to all active (non-rejected) candidates when a recruiter closes a position.
 *
 * Notifies candidates that the role has been filled so they can stop waiting
 * for updates. Only sent to candidates with a `candidate_email` on record.
 * Queued via the Redis queue worker (ShouldQueue).
 */
class PositionFilledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Candidate  $candidate  One of the active candidates for the closed job.
     */
    public function __construct(public Candidate $candidate) {}

    /**
     * Build the envelope with the job title in the subject line.
     */
    public function envelope(): Envelope
    {
        $jobTitle = $this->candidate->job->title ?? 'Position';

        return new Envelope(subject: "{$jobTitle} — Position Has Been Filled");
    }

    /**
     * Specify the view template for the email body.
     */
    public function content(): Content
    {
        return new Content(view: 'mail.position-filled');
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

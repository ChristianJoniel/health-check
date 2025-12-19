<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HealthCheckRecovered extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Project $project) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[RECOVERED] Health Check Restored: '.$this->project->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $downtimeDuration = $this->project->first_failed_at
            ? $this->project->first_failed_at->diffForHumans($this->project->last_recovered_at, true)
            : 'Unknown';

        return new Content(
            markdown: 'mail.health-check-recovered',
            with: [
                'projectName' => $this->project->name,
                'url' => $this->project->health_check_url,
                'consecutiveFailures' => $this->project->consecutive_failures,
                'downtimeDuration' => $downtimeDuration,
                'firstFailedAt' => $this->project->first_failed_at,
                'recoveredAt' => $this->project->last_recovered_at,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

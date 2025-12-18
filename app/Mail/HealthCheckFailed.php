<?php

namespace App\Mail;

use App\Models\HealthCheckFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HealthCheckFailed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public HealthCheckFailure $failure) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ALERT] Health Check Failed: '.$this->failure->project->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.health-check-failed',
            with: [
                'projectName' => $this->failure->project->name,
                'url' => $this->failure->project->health_check_url,
                'errorMessage' => $this->failure->error_message,
                'responseCode' => $this->failure->response_code,
                'responseTime' => $this->failure->response_time_ms,
                'checkedAt' => $this->failure->checked_at,
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

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

use App\ContactSubmission;

class ReplyToContactSubmission extends Notification implements ShouldQueue
{
    use Queueable;

    /**
    * @var ContactSubmission
    */
    public $submission;

    /**
    * @var string
    */
    public $reply;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ContactSubmission $submission, string $reply)
    {
        $this->submission = $submission;
        $this->reply = $reply;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [ 'mail' ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('FunInATL - Contact Form Submission Response')
            ->view(
                'emails.contact_submission_reply',
                [
                    'submission' => $this->submission,
                    'reply' => $this->reply,
                ]
            );
    }
}

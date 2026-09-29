<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** A customer sent the contact/quote form: email the business and ping the team's bell. */
class NewEnquiry extends Notification
{
    public function __construct(public Message $message) {}

    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->message;
        $digits = preg_replace('/\D/', '', (string) $m->phone);
        if (strlen($digits) === 8) {
            $digits = '65' . $digits;
        }

        $mail = (new MailMessage)
            ->subject('New enquiry: ' . ($m->subject ?: 'Website') . ' · ' . $m->name)
            ->greeting('New enquiry from the website')
            ->line("**Name:** {$m->name}")
            ->line('**Phone:** ' . ($m->phone ?: '—'))
            ->line("**Email:** {$m->email}")
            ->line('**About:** ' . ($m->subject ?: 'Website enquiry'))
            ->line('**Message:**');
        foreach (preg_split('/\R/', trim((string) $m->message)) as $line) {
            $mail->line($line === '' ? ' ' : $line);
        }
        $mail->action('Open in admin', url('/admin/messages'));
        if ($digits) {
            $mail->line('Reply on WhatsApp: https://wa.me/' . $digits);
        }

        return $mail->replyTo($m->email, $m->name)->salutation('Reply fast: most customers ask two or three companies.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'enquiry',
            'title' => 'New enquiry from ' . $this->message->name,
            'body' => $this->message->subject ?: \Illuminate\Support\Str::limit($this->message->message, 80),
            'url' => url('/admin/messages'),
            'level' => 'warning',
        ];
    }
}

<?php

namespace jmaloneytrevetts\bagistohubexport;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class SlackNotification extends \Illuminate\Notifications\Notification
{
    use Queueable;
    public $success = true; //if success or failure type of message
    public $message = "";

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($success = true, $message = '')
    {
        $this->success = $success;
        $this->message = $message;
    }
    

    /**
     * Route notifications for the Slack channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return env('SLACK_HOOK');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

     /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        echo 'Sending Slack Notification';

        if ($this->success) {
            return (new SlackMessage)
                ->success()
                ->content($this->message);
        } else {
            return (new SlackMessage)
            ->error()
            ->content('Whoops! Something went wrong.')
            ->attachment(function ($attachment) {
                $attachment->title( env('APP_NAME') . ' Exception: Export Order to hub failed')
                           ->content($this->message);
            });
        }

        
    }
     /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            
        ];
    }
}

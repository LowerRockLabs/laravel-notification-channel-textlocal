<?php

namespace NotificationChannels\Textlocal;

use Illuminate\Notifications\Notification;
use NotificationChannels\Textlocal\Contracts\INotificationUsesTextlocalClientConfig;
use NotificationChannels\Textlocal\Contracts\IUsesTextlocalClientConfig;
use NotificationChannels\Textlocal\Exceptions\CouldNotSendNotification;

/**
 * Textlocal channel class which is used to interact with core
 * textlocal sdk and faciliate to send sms via
 * laravel notification system
 */
class TextlocalChannel
{
    private $sender;
    private $receiptURL;
    private $customData;

    /**
     * creates a textlocal channel object by using the configs
     *
     * @param Textlocal $client
     */
    public function __construct(private Textlocal $client)
    {
        $this->sender = config('textlocal.sender');
    }

    /**
     * Send the given notification.
     *
     * @param mixed                                  $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @throws \NotificationChannels\Textlocal\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        // Get the mobile number/s from the model
        if (! $numbers = $notifiable->routeNotificationFor('Textlocal')) {
            return;
        }

        if (empty($numbers)) {
            return;
        }

        if (!is_array($numbers)) {
            $numbers = [$numbers];
        }

        // Get the message from the notification class
        $message = (string) $notification->toTextlocal($notifiable);

        if (empty($message)) {
            return;
        }

        // Get unicode parameter from notification class
        $unicode = false;
        if (method_exists($notification, 'getUnicodeMode')) {
            $unicode = $notification->getUnicodeMode();
        }

        // Get receipt URL
        if (method_exists($notification, 'getTextlocalReceiptURL'))
        {
            $this->receiptURL = $notification->getTextlocalReceiptURL();
        }

        // Get custom data
        if (method_exists($notification, 'getTextlocalCustomData'))
        {
            $this->customData = $notification->getTextlocalCustomData();
        }


        /*if (method_exists($notification, 'getSenderId')) {
            $this->sender = $notification->getSenderId($notifiable);
        }*/

        $client = $this->getClient($notifiable, $notification);

        try {
            $response = $client
                ->setUnicodeMode($unicode)
                ->sendSms($numbers, $message, $this->sender, false, $this->receiptURL, $this->customData);

            return $response;
        } catch (\Exception $exception) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($exception, $message);
        }
    }

    public function getClient($notifiable, Notification $notification)
    {
        $client = $this->client;

        if ($notifiable instanceof IUsesTextlocalClientConfig) {

            if (! $notifiable->shouldUseCustomTextlocalConfig($notification)) {
                return $client;
            }

            [$username, $hash, $apiKey, $country] = $notifiable->getTextlocalClientConfig($notification);

            $client = new Textlocal($username, $hash, $apiKey, $country);
        }

        if ($notification instanceof INotificationUsesTextlocalClientConfig) {

            if (! $notification->shouldUseCustomTextlocalConfig($notification)) {
                return $client;
            }

            [$username, $hash, $apiKey, $country] = $notification->getTextlocalClientConfig($notifiable);

            $client = new Textlocal($username, $hash, $apiKey, $country);
        }

        return $client;
    }
}

<?php

namespace NotificationChannels\Textlocal;

use Illuminate\Notifications\Notification;
use NotificationChannels\Textlocal\Contracts\INotificationUsesTextlocalClientConfig;
use NotificationChannels\Textlocal\Contracts\IUsesTextlocalClientConfig;
use NotificationChannels\Textlocal\Exceptions\CouldNotSendNotification;
use Illuminate\Support\Facades\Log;

/**
 * Textlocal channel class which is used to interact with core
 * textlocal sdk and faciliate to send sms via
 * laravel notification system
 */
class TextlocalChannel
{
    public $sender;
    public $receiptURL;
    public $customData;
    protected Textlocal $client;

    /**
     * creates a textlocal channel object by using the configs
     *
     * @param Textlocal $client
     */
    public function __construct(Textlocal $client)
    {
        $this->client = $client;

        $this->sender = config('textlocal.sender', 'DefaultSender');
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
        
        if ($notifiable->routeNotificationFor(\NotificationChannels\TextLocal\TextlocalChannel::class))
        {
            $numbers[] = $notifiable->routeNotificationFor(\NotificationChannels\TextLocal\TextlocalChannel::class);
        }
        else if ($notifiable->routeNotificationFor(self::class, $notification)) {
            Log::info('Route for Self');

            $numbers = $notifiable->routeNotificationFor(self::class, $notification);
        }
        else if ($notifiable->routeNotificationFor('textlocal', $notification)) {
            Log::info('Route for x');

            $numbers =  $notifiable->routeNotificationFor('textlocal', $notification);
        }
        else if (isset($notifiable->phone_number)) {
            Log::info('Has phone_number?');

            $numbers =  $notifiable->phone_number;
        }
        else
        {
            Log::info("No Numbers!");
            return;
        }

        

        if (empty($numbers)) {
            Log::info('No Numbers!');

            return;
        }

        if (!is_array($numbers)) {
            $numbers = [$numbers];
        }


        // Get the message from the notification class
        $message = (string) $notification->toTextlocal($notifiable);

        if (empty($message)) {
            Log::info('No Message!');

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

        try {
            $client = $this->getClient($notifiable, $notification);
        }
        catch (Exception $e)
        {
            throw CouldNotSendNotification::serviceRespondedWithAnError($e, "Faults");
        }
        try {
            $response = $client
            ->sendSms($numbers, $message, $this->sender, null, false, $this->receiptURL, $this->customData);    
        }
        catch (Exception $e)
        {
            throw CouldNotSendNotification::serviceRespondedWithAnError($e, "Faults");
        }
        return $response;
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

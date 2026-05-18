<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendMessage(string $to, string $otp): array
    {
        // 1️⃣ إرسال WhatsApp
        $whatsapp = $this->client->messages->create(
            'whatsapp:' . $to,
            [
                'from' => env('TWILIO_WHATSAPP_FROM'),
                'contentSid' => 'HXfe23b7b44fbc08f71ad05561794dab3c',
                'contentVariables' => json_encode([
                    '1' => $otp
                ]),
            ]
        );

        // ⏳ انتظري لحظة صغيرة
        sleep(2);

        // 2️⃣ جلب الحالة الحقيقية
        $status = $this->client->messages($whatsapp->sid)->fetch()->status;

        if (in_array($status, ['sent', 'delivered', 'queued'])) {
            return [
                'success' => true,
                'sid' => $whatsapp->sid,
                'channel' => 'whatsapp',
                'status' => $status,
            ];
        }

        // 3️⃣ WhatsApp فشل → SMS
        $sms = $this->client->messages->create(
            $to,
            [
                'messagingServiceSid' => env('TWILIO_MESSAGING_SERVICE_SID'),
                'body' => "Your verification code is: {$otp}",
            ]
        );

        return [
            'success' => true,
            'sid' => $sms->sid,
            'channel' => 'sms',
        ];
    }

    /**
     * Check the delivery status of a message
     */
    public function getStatus(string $sid): ?string
    {
        try {
            $message = $this->client->messages($sid)->fetch();
            return $message->status;  // queued, sent, delivered, failed, undelivered
        } catch (RestException $e) {
            return null;
        }
    }
}

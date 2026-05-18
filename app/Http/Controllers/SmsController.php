<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;

class SmsController extends Controller
{
    // public function sendSms(Request $request)
    // {
    //     $sid = env('TWILIO_SID');
    //     $token = env('TWILIO_AUTH_TOKEN');
    //     $messagingServiceSid = env('TWILIO_MESSAGING_SERVICE_SID');
    //     $client = new Client($sid, $token);

    //     try {
    //         $message = $client->messages->create(
    //             $request->to,  // recipient
    //             [
    //                 'messagingServiceSid' => $messagingServiceSid,
    //                 'body' => $request->body,
    //             ]
    //         );

    //         return response()->json(['status' => 'success', 'sid' => $message->sid]);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    //     }
    //     // $to = '+966580811117'; // الرقم المستلم
    //     // $body = 'مرحبًا! هذه رسالة اختبار من Laravel باستخدام Alpha Sender ID.';
    //     // $request->validate([
    //     //     'to' => 'required|string',  // رقم المستلم
    //     //     'body' => 'required|string',  // نص الرسالة
    //     // ]);
    //     // try {
    //     //     $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

    //     //     $message = $twilio->messages->create(
    //     //         $request->to,
    //     //         [
    //     //             // 'messagingServiceSid' => env('TWILIO_MESSAGING_SERVICE_SID'),
    //     //             'from' => env('TWILIO_ALPHA_SENDER'),  // الاسم اللي هيظهر للمستلم
    //     //             'body' => $request->body
    //     //         ]
    //     //     );

    //     //     return response()->json([
    //     //         'status' => 'success',
    //     //         'messageSid' => $message->sid,
    //     //         'to' => $to,
    //     //         'body' => $body
    //     //     ]);
    //     // } catch (\Exception $e) {
    //     //     return response()->json([
    //     //         'status' => 'error',
    //     //         'message' => $e->getMessage()
    //     //     ]);
    //     // }
    // }

    public function sendSms(Request $request)
    {
        // $to = '+966580811117'; // الرقم المستلم
        // $body = 'مرحبًا! هذه رسالة اختبار من Laravel باستخدام Alpha Sender ID.';
        $request->validate([
            'to' => 'required|string',  // رقم المستلم
            'body' => 'required|string',  // نص الرسالة
        ]);
        try {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

            $message = $twilio->messages->create(
                $request->to,
                [
                    'messagingServiceSid' => env('TWILIO_MESSAGING_SERVICE_SID'),
                    // 'from' => env('TWILIO_ALPHA_SENDER'),  // الاسم اللي هيظهر للمستلم
                    'body' => $request->body
                ]
            );

            return response()->json([
                'status' => 'success',
                'messageSid' => $message->sid,
                'to' => $request->to,
                'body' => $request->body
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

        try {
            $verification = $twilio
                ->verify
                ->v2
                ->services(env('TWILIO_VERIFY_SID'))
                ->verifications
                ->create($request->phone, 'sms');

            return response()->json([
                'status' => 'success',
                'sid' => $verification->sid,
                'to' => $request->phone,
                'channel' => 'sms'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string'
        ]);

        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

        try {
            $verificationCheck = $twilio
                ->verify
                ->v2
                ->services(env('TWILIO_VERIFY_SID'))
                ->verificationChecks
                ->create([
                    'to' => $request->phone,
                    'code' => $request->code
                ]);

            if ($verificationCheck->status === 'approved') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Verification successful'
                ]);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid code or not approved yet'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}

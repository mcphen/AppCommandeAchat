<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppTestController extends Controller
{
    public function __construct(private TwilioClient $twilio) {}

    public function send(Request $request): JsonResponse
    {
        $to          = str_replace(' ', '+', $request->query('to', ''));
        $message     = $request->query('message', 'Test WhatsApp depuis AchatPro');
        $templateSid = $request->query('template_sid');

        if (! $to) {
            return response()->json(['success' => false, 'error' => 'Paramètre "to" manquant.'], 422);
        }

        $params = ['from' => 'whatsapp:' . config('services.twilio.whatsapp_from')];

        if ($templateSid) {
            $params['contentSid']       = $templateSid;
            $params['contentVariables'] = json_encode(['1' => $message]);
        } else {
            $params['body'] = $message;
        }

        try {
            $msg = $this->twilio->messages->create('whatsapp:' . $to, $params);

            return response()->json([
                'success' => true,
                'sid'     => $msg->sid,
                'status'  => $msg->status,
                'to'      => $to,
                'mode'    => $templateSid ? 'template' : 'freetext',
            ]);
        } catch (RestException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ], 500);
        }
    }
}

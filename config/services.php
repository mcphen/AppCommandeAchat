<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid'           => env('TWILIO_SID'),
        'token'         => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', '+221773325158'),
        'templates'     => [
            'order_submitted'         => env('TWILIO_TPL_ORDER_SUBMITTED'),
            'order_approved_at_level' => env('TWILIO_TPL_ORDER_APPROVED_LEVEL'),
            'order_finally_approved'  => env('TWILIO_TPL_ORDER_FINALLY_APPROVED'),
            'order_rejected'          => env('TWILIO_TPL_ORDER_REJECTED'),
            'revision_requested'      => env('TWILIO_TPL_REVISION_REQUESTED'),
            'comment_added'           => env('TWILIO_TPL_COMMENT_ADDED'),
            'delegation_received'      => env('TWILIO_TPL_DELEGATION_RECEIVED'),
            'decaissement_pret'        => env('TWILIO_TPL_DECAISSEMENT_PRET'),
            'decaissement_enregistre'  => env('TWILIO_TPL_DECAISSEMENT_ENREGISTRE'),
            'dr_submitted'             => env('TWILIO_TPL_DR_SUBMITTED'),
            'dr_approved_at_level'     => env('TWILIO_TPL_DR_APPROVED_LEVEL'),
            'dr_finally_approved'      => env('TWILIO_TPL_DR_FINALLY_APPROVED'),
            'dr_rejected'              => env('TWILIO_TPL_DR_REJECTED'),
        ],
    ],

];

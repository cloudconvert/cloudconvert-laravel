<?php

namespace CloudConvert\Laravel;

use CloudConvert\Exceptions\SignatureVerificationException;
use CloudConvert\Exceptions\UnexpectedDataException;
use CloudConvert\Laravel\Facades\CloudConvert;
use Psr\Http\Message\ServerRequestInterface;


class CloudConvertWebhooksController
{

    public function __invoke(ServerRequestInterface $request)
    {

        try {
            $webhookEvent = CloudConvert::webhookHandler()->constructEventFromRequest($request,
                config('cloudconvert.webhook_signing_secret'));
        } catch (UnexpectedDataException $e) {
            abort(400, 'Unexpected data');
        } catch (SignatureVerificationException $e) {
            abort(400, 'Signature verification failed');
        }


        event('cloudconvert-webhooks::' . $webhookEvent->getEvent(), $webhookEvent);

    }

}

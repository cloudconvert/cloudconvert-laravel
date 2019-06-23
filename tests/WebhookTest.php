<?php

namespace CloudConvert\Laravel\Tests;


use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use CloudConvert\Models\WebhookEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class WebhookTest extends TestCase
{


    public function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Route::post('webhook/cloudconvert', '\CloudConvert\Laravel\CloudConvertWebhooksController');
        config(['cloudconvert.webhook_signing_secret' => 'secret']);
    }

    public function testHandleWebhook()
    {

        $this->withoutExceptionHandling();

        $payload = json_decode(file_get_contents(__DIR__ . '/stubs/webhook_job_finished_payload.json'), true);
        $headers = ['CloudConvert-Signature' => '38c9a2e4db791ef8c9ca5f7300417376028fa55d5c875894137b1c312e5e3560'];

        $this
            ->postJson('webhook/cloudconvert', $payload, $headers)
            ->assertSuccessful();


        Event::assertDispatched('cloudconvert-webhooks::job.finished', function ($event, $webhookEvent) {
            if (!$webhookEvent instanceof WebhookEvent) {
                return false;
            }
            if ($webhookEvent->getEvent() != 'job.finished') {
                return false;
            }

            $job = $webhookEvent->getJob();

            if (!$job instanceof Job) {
                return false;
            }

            if ($job->getId() !== 'c677ccf7-8876-4f48-bb96-0ab8e0d88cd7') {
                return false;
            }

            $tasks = $job->getTasks();

            if (!$tasks[0] instanceof Task) {
                return false;
            }

            return true;
        });

    }

}

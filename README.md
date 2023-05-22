cloudconvert-laravel
=======================

This is the official Laravel package for the [CloudConvert](https://cloudconvert.com/api/v2) _API v2_.
This package depends on the [PHP SDK v3](https://github.com/cloudconvert/cloudconvert-php/tree/master).

[![Tests](https://github.com/cloudconvert/cloudconvert-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cloudconvert/cloudconvert-laravel/actions/workflows/run-tests.yml)
[![Latest Stable Version](https://poser.pugx.org/cloudconvert/cloudconvert-laravel/v/stable)](https://packagist.org/packages/cloudconvert/cloudconvert-laravel)
[![Total Downloads](https://poser.pugx.org/cloudconvert/cloudconvert-laravel/downloads)](https://packagist.org/packages/cloudconvert/cloudconvert-laravel)


## Installation


You can install the package alongside Guzzle via composer:

    composer require cloudconvert/cloudconvert-laravel guzzlehttp/guzzle

This package is not tied to any specific HTTP client by using [PSR-7](https://www.php-fig.org/psr/psr-7/), [PSR-17](https://www.php-fig.org/psr/psr-17/), [PSR-18](https://www.php-fig.org/psr/psr-18/), and [HTTPlug](https://httplug.io/). Therefore, you will also need to install packages that provide [`psr/http-client-implementation`](https://packagist.org/providers/psr/http-client-implementation) and [`psr/http-factory-implementation`](https://packagist.org/providers/psr/http-factory-implementation) (for example Guzzle).

Next you must publish the config file. 

    php artisan vendor:publish --provider="CloudConvert\Laravel\Providers\CloudConvertServiceProvider"

This is the content that will be published to `config/cloudconvert.php`:

```php
<?php
return [

    /**
     * You can generate API keys here: https://cloudconvert.com/dashboard/api/v2/keys.
     */

    'api_key' => env('CLOUDCONVERT_API_KEY', ''),

    /**
     * Use the CloudConvert Sanbox API (Defaults to false, which enables the Production API).
     */
    'sandbox' => env('CLOUDCONVERT_SANDBOX', false),

    /**
     * You can find the secret used at the webhook settings: https://cloudconvert.com/dashboard/api/v2/webhooks
     */
    'webhook_signing_secret' => env('CLOUDCONVERT_WEBHOOK_SIGNING_SECRET', '')

];
```


## Usage

Once configured you can call all the PHP SDK methods on the `CloudConvert` facade.

```php
use \CloudConvert\Laravel\Facades\CloudConvert;
use \CloudConvert\Models\Job;
use \CloudConvert\Models\Task;

CloudConvert::jobs()->create(
    (new Job())
    ->setTag('myjob-123')
    ->addTask(
        (new Task('import/url', 'import-my-file'))
            ->set('url','https://my-url')
    )
    ->addTask(
        (new Task('convert', 'convert-my-file'))
            ->set('input', 'import-my-file')
            ->set('output_format', 'pdf')
            ->set('some_other_option', 'value')
    )
    ->addTask(
        (new Task('export/url', 'export-my-file'))
            ->set('input', 'convert-my-file')
    )
);
```

Please check the [PHP SDK repository](https://github.com/cloudconvert/cloudconvert-php/tree/master) for the full documentation.


## Uploading Files

Uploads to CloudConvert are done via `import/upload` tasks (see the [docs](https://cloudconvert.com/api/v2/import#import-upload-tasks)). This SDK offers a convenient upload method:

```php
use \CloudConvert\Models\Job;

$job = (new Job())
    ->addTask(new Task('import/upload','upload-my-file'))
    ->addTask(
        (new Task('convert', 'convert-my-file'))
            ->set('input', 'upload-my-file')
            ->set('output_format', 'pdf')
    )
    ->addTask(
        (new Task('export/url', 'export-my-file'))
            ->set('input', 'convert-my-file')
    );

$cloudconvert->jobs()->create($job);

$uploadTask = $job->getTasks()->whereName('upload-my-file')[0];

$inputStream = fopen(Storage::path('my/input.docx'), 'r');

CloudConvert::tasks()->upload($uploadTask, $inputStream);
```

## Downloading Files

CloudConvert can generate public URLs for using `export/url` tasks. You can use the PHP SDK to download the output files when the Job is finished.

```php
$cloudconvert->jobs()->wait($job); // Wait for job completion

foreach ($job->getExportUrls() as $file) {

    $source = $cloudconvert->getHttpTransport()->download($file->url)->detach();
    $dest = fopen(Storage::path('out/' . $file->filename), 'w');
    
    stream_copy_to_stream($source, $dest);

}
```




## Webhooks

This package can help you handle the CloudConvert webhooks. Out of the box it will verify the CloudConvert signature of all incoming requests. You can easily define event subscribers when specific events hit your app.


#### Route

You can create your webhook in the [webhook settings](https://cloudconvert.com/dashboard/api/v2/webhooks) and point it to something like `https://your.app/webhook/cloudconvert`. Make sure to to configure the shown signing secret in the config file of this package. 

In the routes file of your app you must pass that route to a controller provided by this package.

```php
Route::post('webhook/cloudconvert', '\CloudConvert\Laravel\CloudConvertWebhooksController');
```

Because CSRF token validation is not availble for this route, you must also add that route to the except array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook/cloudconvert',
];
```

#### Events

Whenever a webhook event hits your app, the package fires a `cloudconvert-webhooks::<event-name>` event (for example `cloudconvert-webhooks::job.finished`).


The payload of the event will be a `WebhookEvent` from the PHP SDK. An event subscriber in your app could look like this:

```php
<?php

namespace App\Listeners;

use CloudConvert\Models\WebhookEvent;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Illuminate\Support\Facades\Log;

class CloudConvertEventListener
{

    public function onJobFinished(WebhookEvent $event) {
        
        $job = $event->getJob();
        
        $job->getTag(); // can be used to store an ID
        
        $exportTask = $job->getTasks()
            ->whereStatus(Task::STATUS_FINISHED) // get the task with 'finished' status ...
            ->whereName('my-export-task')[0];    // ... and with the name 'my-export-task'
        
        // $exportTask->getResult() ...
        
    }

    public function onJobFailed(WebhookEvent $event) {
        
        $job = $event->getJob();
        
        $job->getTag(); // can be used to store an ID
        
        $failingTask =  $job->getTasks()->whereStatus(Task::STATUS_ERROR)[0];
        
        Log::error('CloudConvert task failed: ' . $failingTask->getId());
        
    }

    public function subscribe($events)
    {
        $events->listen(
            'cloudconvert-webhooks::job.finished',
            'App\Listeners\CloudConvertEventListener@onJobFinished'
        );

        $events->listen(
            'cloudconvert-webhooks::job.failed',
            'App\Listeners\CloudConvertEventListener@onJobFailed'
        );
    }

}
 ```

Register the subscriber in the EventServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        'App\Listeners\CloudConvertEventListener',
    ];
}
```

Tests
-----------------

    vendor/bin/phpunit 


Resources
---------

* [PHP SDK](https://github.com/cloudconvert/cloudconvert-php)
* [API Documentation](https://cloudconvert.com/api/v2)
* [CloudConvert Blog](https://cloudconvert.com/blog)

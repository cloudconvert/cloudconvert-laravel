__WIP! Do not use yet!__

cloudconvert-laravel
=======================

> This is the official Laravel package for the [CloudConvert](https://cloudconvert.com/api/v2) _API v2_. It is not compatible with API v1!
> This package depends on the [PHP SDK v3](https://github.com/cloudconvert/cloudconvert-php/tree/v3).

[![Build Status](https://travis-ci.org/cloudconvert/cloudconvert-laravel.svg)](https://travis-ci.org/cloudconvert/cloudconvert-laravel)
[![Latest Stable Version](https://poser.pugx.org/cloudconvert/cloudconvert-laravel/v/stable)](https://packagist.org/packages/cloudconvert/cloudconvert-laravel)
[![Total Downloads](https://poser.pugx.org/cloudconvert/cloudconvert-laravel/downloads)](https://packagist.org/packages/cloudconvert/cloudconvert-laravel)
[![License](https://poser.pugx.org/cloudconvert/cloudconvert-laravel/license)](https://packagist.org/packages/cloudconvert/cloudconvert-laravel)


## Installation


You can install the package via composer:

    composer require cloudconvert/cloudconvert-laravel


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
)
```

Please check the [PHP SDK repository](https://github.com/cloudconvert/cloudconvert-php/tree/v3) for the full documentation.


## Webhooks

This package can help you handle the CloudConvert webhooks. Out of the box it will verify the CloudConvert signature of all incoming requests. You can easily define event subscribers when specific events hit your app.

Tests
-----------------

    vendor/bin/phpunit 


Resources
---------

* [PHP SDK](https://github.com/cloudconvert/cloudconvert-php)
* [API Documentation](https://cloudconvert.com/api/v2)
* [CloudConvert Blog](https://cloudconvert.com/blog)

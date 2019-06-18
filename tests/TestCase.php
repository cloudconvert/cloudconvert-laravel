<?php

namespace CloudConvert\Laravel\Tests;


use CloudConvert\Laravel\Facades\CloudConvert;
use CloudConvert\Laravel\Providers\CloudConvertServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;


abstract class TestCase extends OrchestraTestCase
{

    protected $http;


    protected function getPackageProviders($app)
    {
        return [
            CloudConvertServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'CloudConvert' => CloudConvert::class,
        ];
    }

}

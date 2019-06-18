<?php

namespace CloudConvert\Laravel\Tests;


use CloudConvert\CloudConvert;

class CloudConvertTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        config()->set('cloudconvert', [
            'api_key'                => 'test',
            'webhook_signing_secret' => '123',
        ]);
    }

    public function testCloudConvertClassIsBound()
    {
        $cloudConvert = app(CloudConvert::class);

        $this->assertInstanceOf(CloudConvert::class, $cloudConvert);

        $reflection = new \ReflectionClass($cloudConvert);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $options = $property->getValue($cloudConvert);

        $this->assertEquals('test', $options['api_key']);
    }

    public function testFacadeIsRegistered()
    {
        app()->bind(CloudConvert::class, function () {
            return new class()
            {
                public function jobs()
                {
                    return 'jobs';
                }
            };
        });
        $this->assertEquals('jobs', \CloudConvert\Laravel\Facades\CloudConvert::jobs());
    }

}

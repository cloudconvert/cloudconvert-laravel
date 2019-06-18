<?php


namespace CloudConvert\Laravel\Facades;


use Illuminate\Support\Facades\Facade;


class CloudConvert extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \CloudConvert\CloudConvert::class;
    }
}

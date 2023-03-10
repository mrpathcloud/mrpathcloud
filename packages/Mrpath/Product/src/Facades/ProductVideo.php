<?php

namespace Mrpath\Product\Facades;

use Illuminate\Support\Facades\Facade;

class ProductVideo extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'productvideo';
    }
}
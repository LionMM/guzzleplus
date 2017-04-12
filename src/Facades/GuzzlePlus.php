<?php namespace LionMM\GuzzlePlus\Facades;

use Illuminate\Support\Facades\Facade;

class GuzzlePlus extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'guzzleplus';
    }

} 
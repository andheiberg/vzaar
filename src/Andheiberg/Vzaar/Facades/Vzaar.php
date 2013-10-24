<?php namespace Andheiberg\Vzaar\Facades;

use Illuminate\Support\Facades\Facade;

class Vzaar extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'vzaar'; }

}
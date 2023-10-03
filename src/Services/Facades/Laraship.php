<?php

namespace Susheelbhai\Laraship\Services\Facades;

use Illuminate\Support\Facades\Facade;

class Laraship extends Facade{

    protected static function getFacadeAccessor()
    {
        
        return 'laraship';
    }

}
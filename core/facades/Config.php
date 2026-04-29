<?php

namespace Core\Facades;

use Core\Facade;

class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'config';
    }
}

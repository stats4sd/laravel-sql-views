<?php

namespace Stats4SD\SqlViews\Facades;

use Illuminate\Support\Facades\Facade;

class SqlViews extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sqlviews';
    }
}

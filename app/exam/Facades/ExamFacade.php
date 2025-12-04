<?php

namespace App\Exam\Facades;

use Illuminate\Support\Facades\Facade;

class ExamFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return self::class;
    }
}

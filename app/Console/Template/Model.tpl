<?php

namespace App\{module}\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class {action}Model extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = '{table}';
}

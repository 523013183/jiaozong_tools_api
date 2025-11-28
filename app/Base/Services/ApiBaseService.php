<?php


namespace App\Base\Services;


use App\Base\Models\BaseModel;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiBaseService extends AbstractBaseService
{
    use ApiAuthUser;
}

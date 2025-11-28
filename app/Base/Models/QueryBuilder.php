<?php


namespace App\Base\Models;


use Illuminate\Database\Query\Builder;

class QueryBuilder extends Builder
{
    use Criteria;
}
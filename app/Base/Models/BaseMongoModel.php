<?php


namespace App\Base\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class BaseMongoModel extends Model
{
    protected $connection = 'mongodb';
}

<?php

namespace App\Base\Models;

use App\Base\Models\BaseModel;
use App\Base\Models\ApiSoftDeletes;

class DictLocationModel extends BaseModel
{
    use ApiSoftDeletes;
    protected $table = 'dict_location';

    public function child($class = DictLocationModel::class){
        return $this->hasMany($class,'pid','id')
            ->whereIn('status',[0,1])
            ->selectRaw('id,pid,name,status,name_pinyin,level,code')
            ->with(['child','languages']);
    }

    public function parent($class = DictLocationModel::class){
        return $this->belongsTo($class,'id','pid')
            ->selectRaw('id,pid,name')
            ->whereIn('status',[0,1])
            ->with(['parent','language']);
    }

    public function languages($class = DictPlurilingualModel::class){
        return $this->hasMany($class,'relation_id','id')
            ->where('type','=',DictPlurilingualModel::TYPE_LOCATION)
            ->where('status','=',0)
            ->selectRaw('relation_id,language_id,name')->with('language');
    }

    public function getHasChildAttribute(){
        if(count($this->child)){
            return 1;
        }else{
            return 0;
        }
    }
}

<?php

namespace App\Base\Models;


use Illuminate\Database\Eloquent\SoftDeletes;

trait ApiSoftDeletes
{
    use SoftDeletes ;

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new ApiSoftDeletingScope);
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return $this->{$this->getDeletedAtColumn()} == static::STATUS_DELETED;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getDeletedAtColumn()} = $this->getDeletedColumnValue();

        $query->update([$this->getDeletedAtColumn() => $this->getDeletedColumnValue()]);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->{$this->getDeletedAtColumn()} = static::STATUS_ENABLED;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * 获取删除字段值
     * @return string|int
     */
    public function getDeletedColumnValue(){
        return self::STATUS_DELETED;
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        $alias = empty($this->getAliasName())?$this->getTable():$this->getAliasName();
        return $alias.'.'.$this->getDeletedAtColumn();
    }

    /**
     * 该方法纯粹为了覆盖SoftDeletes里面指定delete_at类型不正确的问题
     */
    public function initializeSoftDeletes()
    {
    }

}
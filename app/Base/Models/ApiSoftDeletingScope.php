<?php
/**
 * 软删除 socpe
 */

namespace App\Base\Models;


use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApiSoftDeletingScope extends SoftDeletingScope
{
    /**
     * All of the extensions to be added to the builder.
     * restore:恢复
     * WithTrashed:带有删除状态和正常状态的数据
     * OnlyTrashed:只有删除状态的数据
     * WithDisabled:带有禁用状态的数据 即正常状态+禁用状态
     * OnlyDisabled:只查询出禁用状态
     * WithAll:所有
     * @var array
     */
    protected $extensions = ['Restore', 'WithTrashed', 'OnlyTrashed','WithDisabled','OnlyDisabled','WithAll'];

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if(is_null($model::STATUS_ENABLED)){
            $builder->whereNull($model->getQualifiedDeletedAtColumn());
        }else{
//            $builder->where($model->getQualifiedDeletedAtColumn(),$model::STATUS_ENABLED);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }

        $builder->onDelete(function (Builder $builder) {
            $column = $this->getDeletedAtColumn($builder);
            $model = $builder->getModel();
            return $builder->update([
                $column => $model->getDeletedColumnValue(),
            ]);
        });
    }

    /**
     * Add the restore extension to the builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addRestore(Builder $builder)
    {
        $builder->macro('restore', function (Builder $builder) {
            $builder->withAll();
            $model = $builder->getModel();
            return $builder->update([$model->getDeletedAtColumn() => $model::STATUS_ENABLED]);
        });
    }

    /**
     * 筛选出正常状态和删除状态的数据
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addWithTrashed(Builder $builder)
    {
        $builder->macro('withTrashed', function (Builder $builder) {
            $builder->withoutGlobalScope($this);
            $model = $builder->getModel();
            return $builder->whereIn($model::DELETED_AT,[$model::STATUS_DELETED,$model::STATUS_ENABLED]);
        });
    }

    /**
     * 只筛选出删除的数据
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function addOnlyTrashed(Builder $builder)
    {
        $builder->macro('onlyTrashed', function (Builder $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedAtColumn(),$model::STATUS_DELETED
            );

            return $builder;
        });
    }

    /**
     * 筛选出正常状态和禁用状态的数据
     * @param Builder $builder
     */
    protected function addWithDisabled(Builder $builder){
        $builder->macro('withDisabled', function (Builder $builder) {
            $model = $builder->getModel();
            $builder->withoutGlobalScope($this)->whereIn(
                $model->getQualifiedDeletedAtColumn(),[$model::STATUS_DISABLED,$model::STATUS_ENABLED]
            );
            return $builder;
        });
    }

    /**
     * 只筛选出禁用状态的数据
     * @param Builder $builder
     */
    protected function addOnlyDisabled(Builder $builder){
        $builder->macro('onlyDisabled', function (Builder $builder) {
            $model = $builder->getModel();
            $builder->withoutGlobalScope($this)->where(
                $model->getQualifiedDeletedAtColumn(),$model::STATUS_DISABLED
            );
            return $builder;
        });
    }

    /**
     * 筛选出所有数据
     * @param Builder $builder
     */
    protected function addWithAll(Builder $builder){
        $builder->macro('withAll', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
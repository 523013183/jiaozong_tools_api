<?php
namespace App\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    use Criteria;


    /**
     * 插入时间字段
     */
    const CREATED_AT = 'create_time';

    /**
     * 更新时间字段
     */
    const UPDATED_AT = 'update_time';

    /**
     * 状态字段
     */
    const DELETED_AT = 'status';

    /**
     * 正常状态
     */
    const STATUS_ENABLED = 0;

    /**
     * 禁用状态
     */
    const STATUS_DISABLED = 1;

    /**
     * 删除状态
     */
    const STATUS_DELETED = 2;

    /**
     * 审核状态：未提交审核
     */
    const AUDIT_STATUS_NOT_SUBMITTED = 0;

    /**
     * 审核状态：已提交/审核中
     */
    const AUDIT_STATUS_SUBMITTED = 1;

    /**
     * 审核状态：审核通过
     */
    const AUDIT_STATUS_SUCCESSFUL = 2;

    /**
     * 审核状态：审核失败
     */
    const AUDIT_STATUS_FAIL = 3;

    /**
     * 该模型是否被自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 别名名称
     * @var null
     */
    protected $aliasName = null;

    /**
     * 模型的日期字段保存格式。
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';


    /**
     * 新的连接方式，用于分库后可指定连接哪个库的，因原有的connection在分库中已失效，所以需要重新加此变量
     * @var null
     */
    protected $connectionNew = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * 自动获取数据库表字段
     */
    public function setFillable()
    {
        $key = get_class($this);
        $cache = Cache::store('file')->get($key);
        if (empty($cache)) {
            //            $columns = Schema::getColumnListing($this->table);
            //如果是公共库的，会读取默认库，而默认库没有表的情况下会为空，所以改成以下语句
            $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->table);
            $cache = $columns;
            Cache::store('file')->put($key, $columns, config('cache.columns'));
        }
        $this->fillable = $cache;
    }

    /**
     * 获取所有列
     * @return array
     */
    public function getColumns()
    {
        $this->setFillable();
        return $this->getFillable();
    }

    /**
     * 过滤掉非数据库字段的数据
     * @param $data
     * @return array
     */
    public function filter($data)
    {
        if (empty($this->fillable)) {
            $this->setFillable();
        }
        $result = [];
        if (empty($data) || !is_array($data)) {
            return $result;
        }
        foreach ($this->fillable as $item) {
            if (isset($data[$item])) {
                $result[$item] = $data[$item];
            }
        }
        return $result;
    }

    /**
     * 覆盖setCreatedAt 不添加createAt字段
     *
     * @param  mixed $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        if (!empty(static::CREATED_AT)) {
            $this->{static::CREATED_AT} = $value;
        }
        if (!empty(static::UPDATED_AT)) {
            $this->{static::UPDATED_AT} = $value;
        }
        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     *
     * @param  mixed $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        if (!empty(static::UPDATED_AT)) {
            $this->{static::UPDATED_AT} = $value;
        }
        return $this;
    }

    /**
     * 覆盖父类方法 新建连接
     * @return \Illuminate\Database\Query\Builder|QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    /**
     * 覆盖父类方法
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Base\Models\EloquentBuilder|Model|Builder
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }

    /**
     * 覆盖父类方法 获取连接名称
     * @return null|string
     */
    public function getConnectionName()
    {
        if (!config('app.database_split', false)) { // 未开启分库
            return null;
        }

        //判断是否在model中有指定连接哪个库，如果有，直接返回此指定库，没有则继续往下按公司连接判断
        if ($this->connectionNew) {
            return $this->connectionNew;
        }
        return $this->connection;
    }

    /**
     * 设置全局DB Config
     * @param $dbConnectionName
     * @param $db
     */
    public static function setConfigDBConnection($dbConnectionName, $db)
    {
        config([
            $dbConnectionName => [
                'driver' => 'mysql',
                'host' => $db['host'],
                'port' => $db['port'],
                'database' => $db['database'],
                'username' => $db['username'],
                'password' => $db['password'],
                'charset' => config('database.connections.mysql.charset') ?? 'utf8mb4',
                'collation' => config('database.connections.mysql.collation') ?? 'utf8mb4_general_ci',
                'prefix' => '',
                'timezone' => '+08:00',
                'strict' => false
            ]]);
    }

    /**
     * 生成DBConnectionName
     * @param $connectionName
     * @return string
     */
    public static function getDBConnectionName($connectionName)
    {
        $dbConnectionName = 'database.connections.' . $connectionName;
        return $dbConnectionName;
    }

    /**
     * 别名
     * @param $name
     * @return
     */
    public function alias($name)
    {
        $this->aliasName = $name;
        return $this->from($this->getTable() . ' as ' . $name);
    }

    /**
     * 获取别名名称
     * @return null
     */
    public function getAliasName()
    {
        return $this->aliasName;
    }

    /**
     * Set the table associated with the model.
     *
     * @param  string $table
     * @return $this
     */
    public function setTable($table)
    {
        $table = trim($table);
        $this->table = $table;
        if (strpos($table, ' as ')) {
            list($table, $alias) = explode(' as ', $table);
            if (!empty($alias)) {
                $this->aliasName = trim($alias);
            }
        }
        return $this;
    }

    /**
     * 返回正常状态的值
     * @return int
     */
    public function getStatusEnabled()
    {
        return self::STATUS_ENABLED;
    }

    /**
     * 返回禁用状态的值
     * @return int
     */
    public function getStatusDisabled()
    {
        return self::STATUS_DISABLED;
    }

    /**
     * 返回删除状态的值
     * @return int
     */
    public function getStatusDeleted()
    {
        return self::STATUS_DELETED;
    }

    public function isJsonCastingField($field)
    {
        if (isset($this->casts[$field]) && $this->casts[$field] == 'array') {
            return true;
        }
        return false;
    }

    //重载model里的事件通知
    public function fireModelEvent($event, $halt = true)
    {
        parent::fireModelEvent($event, $halt);
    }

    /**
     * 格式化时间
     * */
    protected function serializeDate($date)
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        return $date;
    }

    /**
     * 解析时间值
     * @param mixed $value
     * @return \Illuminate\Support\Carbon|mixed
     */
    protected function asDateTime($value)
    {
        if (null == $value || '0000-00-00 00:00:00' == $value) {
            return $value;
        }

        $format = $this->getDateFormat();
        try {
            $date = Date::createFromFormat($format, $value);
        } catch (\InvalidArgumentException $e) {
            $date = false;
        }

        return $date ?: Date::parse($value);
    }

    /**
     * @param bool $alias
     * @return \Illuminate\Database\Query\Builder
     */
    public function db($alias = false)
    {
        return DB::table($this->getTable() . ($alias ? " as $alias" : ''));
    }

    /**
     * 批量写入数据
     * @param $datas
     * @return bool
     */
    public function insertAll($datas)
    {
        if (empty($datas)) {
            return false;
        }
        foreach ($datas as &$data) {
            if ($this->timestamps) {
                $createAt = $this::CREATED_AT;
                $updateAt = $this::UPDATED_AT;
                $data[$updateAt] = $data[$createAt] = nowTime();
            }
        }
        return DB::table($this->table)->insert($datas);
    }

    /**
     * 保存一条记录或者多条记录
     * @param array $data
     * @param mixed $where
     * @return int
     */
    public function updateData($data, $where = false)
    {
//        $data = $this->filter($data);
        if ($this->timestamps) {
            $updateAt = $this::UPDATED_AT;
            $data[$updateAt] = nowTime();
        }
        if (!$where) {
            $id = $data[$this->getKeyName()];
            unset($data[$this->getKeyName()]);
            $where = [
                $this->getKeyName() => $id
            ];
        }
        return $this->bindCriteria($this->db(),$where)->update($data);
    }

    /**
     * 新增记录
     * @param $data
     * @return int
     */
    public function insertData($data)
    {
//        $data = $this->filter($data);
        if ($this->timestamps) {
            $createAt = $this::CREATED_AT;
            $updateAt = $this::UPDATED_AT;
            $data[$updateAt] = $data[$createAt] = nowTime();
        }
        return $this->db()->insertGetId($data);
    }

    /**
     * 获取一条记录
     * @param $id
     * @param $field
     * @return Model|\Illuminate\Database\Query\Builder|null|object
     */
    public function findOneById($id, $field = "*")
    {
        $db = $this->db()->selectRaw($field);
        if (method_exists($this, 'runSoftDelete') && !isset($criteria['status'])) {
            $db = $db->whereIn('status', [0, 1]);
        }
        return $db->where([$this->getKeyName() => $id])->first();
    }

    /**
     * 根据条件获取一条记录
     * @param $criteria
     * @param $field
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function findOneBy($criteria, $field = "*")
    {
        $db = $this->db()->selectRaw($field);
        if (method_exists($this, 'runSoftDelete') && !isset($criteria['status'])) {
            $db = $db->whereIn('status', [0, 1]);
        }
        return $this->bindCriteria($db, $criteria)->first();
    }

    /**
     * 根据条件获取一条记录
     * @param $criteria
     * @param $field
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function countBy($criteria)
    {
        $db = $this->db();
        if (method_exists($this, 'runSoftDelete') && !isset($criteria['status'])) {
            $db = $db->whereIn('status', [0, 1]);
        }
        return $this->bindCriteria($db, $criteria)->count();
    }

    /**
     * 绑定查询条件
     * @param \Illuminate\Database\Query\Builder $db
     * @param $criteria
     * @return \Illuminate\Database\Query\Builder
     */
    public function bindCriteria($db, &$criteria)
    {
        $where = [];
        foreach ($criteria as $k=>$v) {
            if (!is_numeric($k)) {
                if (is_array($v)) {
                    if ($v[0] == 'in') {
                        $db = $db->whereIn($k, $v[1]);
                    } else {
                        $where[] = [$k, $v[0], $v[1]];
                    }
                } else {
                    $where[$k] = $v;
                }
            } else if (is_array($v)) {
                if ($v[1] == 'in') {
                    $db = $db->whereIn($v[0], $v[2]);
                } else {
                    $where[] = $v;
                }
            }
        }
        return $db->where($where);
    }

    /**
     * 根据条件获取多条记录
     * @param $criteria
     * @param $field
     * @return array
     */
    public function findBy($criteria, $field = "*")
    {
        $db = $this->db()->selectRaw($field);
        if (method_exists($this, 'runSoftDelete') && !isset($criteria['status'])) {
            $db = $db->whereIn('status', [0, 1]);
        }
        return $this->bindCriteria($db,$criteria)
                    ->get()->toArray();
    }

    /**
     * 根据条件删除
     * @param $criteria
     * @return int
     */
    public function deleteBy($criteria)
    {
        if (method_exists($this, 'runSoftDelete') && !isset($criteria['status'])) {
            return $this->db()->where($criteria)->update(['status'=>2]);
        } else {
            return $this->db()->where($criteria)->delete();
        }
    }
}

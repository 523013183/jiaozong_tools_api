<?php
/**
 * Created by PhpStorm.
 * User: luxf
 * Date: 2022/3/29
 * Time: 9:53 AM
 */

namespace App\Base\Models;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * 通用导入EXCEL文件对象
 * Class ImportExcel
 * @package App\Base\Models
 */
class ImportExcel implements ToCollection, WithChunkReading
{
    private $callback;
    private $readRows = 0;

    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * 格式化日期
     * @param int $value
     * @param string $format
     * @return Carbon|false|string
     */
    public static function transformDateTime(int $value, string $format = 'Y-m-d H:i:s')
    {
        if (!$value) {
            return '';
        }
        $value--;
        return date($format, strtotime("1900-01-00 00:00:00 +$value day"));
    }

    /**
     * @param  Collection $collection
     */
    public function collection(Collection $collection)
    {
        if ($this->readRows == 0) {
            unset($collection[0]);
            $this->readRows += count($collection);
        }
        $this->callback->call($this, $collection);
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }

}

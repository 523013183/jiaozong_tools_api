<?php
namespace App\Base\Models;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * 通用导入EXCEL文件对象（多个工作簿）
 * Class ImportMultipleExcel
 * @package App\Base\Models
 */
class ImportMultipleExcel implements WithMultipleSheets
{
    private $callback;
    private $readRows = 0;

    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    public function sheets(): array
    {
        // $rows = [];
        // dd(call_user_func($this->callback, $rows, 0));
        return [
            // 第一个工作表
            0 => function ($rows) {
                // 这里只获取数据，不进行处理
                call_user_func($this->callback, $rows, 0); // 调用外部处理逻辑
            },
            // 第二个工作表
            1 => function ($rows) {
                call_user_func($this->callback, $rows, 1); // 调用外部处理逻辑
            }
        ];
    }

}

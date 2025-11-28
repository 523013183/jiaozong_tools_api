<?php

namespace App\Base\Models;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * 通用导出EXCEL文件对象
 * Class ExportObject
 * @package App\Base\Models
 */
class ExportExcel implements FromArray, WithMapping, WithHeadings, WithEvents
{
    private $dataList = [];
    private $headerMap = [];
    private $afterSheetStyle = [];

    /**
     * 构造函数
     * ExportObject constructor.
     * @param array $headerMap 头部描述数据
     * @param array $dataList 数据列表
     */
    public function __construct(array &$headerMap, array &$dataList, array &$afterSheetStyle = [])
    {
        @ini_set("memory_limit",'512M');
        set_time_limit(60);
        $this->dataList = $dataList;
        $this->headerMap = $headerMap;
        $this->afterSheetStyle = $afterSheetStyle;
    }

    /**
     * 注册事件
     * @return array
     */
    public function registerEvents(): array
    {
        return $this->afterSheetStyle;
    }

    /**
     * 映射数据行
     * @param  mixed $row
     * @return array
     */
    public function map($row): array
    {
        $o = [];
        foreach ($this->headerMap as $k=>$v) {
            $o[] = $row[$k]??null;
        }
        return $o;
    }

    /**
     * 返回头部
     * @return array
     */
    public function headings(): array
    {
        return array_values($this->headerMap);
    }

    /**
     * 数据源
     * @return array
     */
    public function array(): array
    {
        return $this->dataList;
    }
}

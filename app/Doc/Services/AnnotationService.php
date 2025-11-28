<?php


namespace App\Doc\Services;


use Illuminate\Support\Facades\Log;

class AnnotationService
{
    private $objRe;
    private $class;
    private $data;

    public function __construct($class)
    {
        $className = '';
        if (is_object($class)) {
            $className = get_class($class);
        } else if (is_string($class)) {
            $className = $class;
        } else {
            exit("class param error!");
        }
        $this->objRe = new \ReflectionClass($className);//此类的方法被原封不动的继承 可以直接调用
        $this->class = $className;
    }

    /**
     * 获取所有的注释文档
     * @return array
     */
    public function getAllDocComment()
    {
        $methods = $this->objRe->getMethods();
        $this->data = [];
        foreach ($methods as $item) {
            if ($item->isConstructor() || !$item->isPublic() || $item->class != $this->class) {
                continue;
            }
            //获取方法注释
            $doc = $item->getDocComment();
            $params = $this->getDocuments($doc);
            if (isset($params['api'])) {
                if (!empty($params) && !isset($params['group'])) {
                    continue;
                }
                $this->formatDocument($params);
                $group = $params['group']['group'] . ($params['group']['child'] ? '-' . $params['group']['child'] : '');
                $data[$group][] = $params;
            }
        }
        return $this->data;
    }

    /**
     * 解析注释
     * @param $doc
     * @return array
     */
    private function getDocuments($doc)
    {
        $result = [];
        $list = explode('* @', $doc);
        foreach ($list as $item) {
            $item = str_replace('*/', '', $item);
            $item = str_replace('* ', '', $item);
            $item = trim($item);
            $line = explode(' ', $item);
            $defined = $line[0];
            $defined = str_replace(array("\r\n", "\r", "\n"), "", $defined);

            switch ($defined) {
                case 'api':
                    $result[$defined] = [
                        'key' => $line[0] ?? '',
                        'method' => isset($line[1]) && in_array($line[1], ['get', 'post', 'put', 'delete', 'options', 'patch']) ? $line[1] : 'get',
                        'route' => $line[2] ?? '',
                        'comment' => $line[3] ?? ''
                    ];
                    break;
                case 'group':
                    $result[$defined] = [
                        'key' => $line[0] ?? '',
                        'group' => isset($line[1]) ? str_replace(array("\r\n", "\r", "\n"), "", $line[1]) : '',
                        'child' => isset($line[2]) ? str_replace(array("\r\n", "\r", "\n"), "", $line[2]) : '',
                    ];
                    break;
                case 'param':
                    $result[$defined][] = [
                        'desc' => $line[3] ?? '',
                        'example' => $line[5] ?? '',
                        'type' => (isset($line[1]) && $line[1] == 'file') ? 'file' : 'text',
                        'name' => $line[2] ?? '',
                        'required' => (isset($line[4]) && $line[4] == 'required') ? "1" : "0",
                    ];
                    break;
                case 'header':
                    $result[$defined][] = [
                        'desc' => $line[3] ?? '',
                        'example' => $line[5] ?? '',
                        'name' => $line[3] ?? '',
                        'required' => (isset($line[4]) && $line[4] == 'required') ? "1" : "0",
                    ];
                    break;
                case 'success':
                    $result[$defined][] = [
                        'key' => $line[0] ?? '',
                        'type' => $line[1] ?? '',
                        'field' => $line[2] ?? '',
                        'comment' => $line[3] ?? '',
                        'required' => (isset($line[4]) && $line[4] == 'required') ? true : false,
                    ];
                    break;
                case 'error':
                    $result[$defined][] = [
                        'key' => $line[0] ?? '',
                        'code' => $line[1] ?? '',
                        'msg' => $line[2] ?? ''
                    ];
                    break;
                case 'paramExample':
                case 'successExample':
                    unset($line[0]);
                    $result[$defined] = implode(' ', $line);
                    break;
                case 'desc':
                    unset($line[0]);
                    $result[$defined] = implode(' ', $line);
                    break;
            }
        }
        return $result;
    }

    /**
     * 更改文档格式
     * @param $doc
     */
    private function formatDocument($doc)
    {
        if (!isset($this->data[$doc['group']['group'] . '-' . $doc['group']['child']])) {
            $this->data[$doc['group']['group'] . '-' . $doc['group']['child']] = [
                'name' => $doc['group']['group'] . '-' . $doc['group']['child'],
                'desc' => $doc['group']['group'] . '-' . $doc['group']['child'],
                'add_time' => time(),
                'up_time' => time(),
                'list' => []
            ];
        }
        $this->data[$doc['group']['group'] . '-' . $doc['group']['child']]['list'][$doc['api']['comment']] = [
            'method' => $doc['api']['method'],
            'title' => $doc['api']['comment'] ? $doc['api']['comment'] : '-',
            'path' => $doc['api']['route'] ? ($doc['api']['route'][0] == '/' ? $doc['api']['route'] : '/' . $doc['api']['route']) : '/undefine',
            'res_body_type' => 'json',
            'res_body' => $doc['successExample'] ?? '',
            'req_body_is_json_schema' => false,
            'req_params' => [],
            'req_headers' => isset($doc['header']) && $doc['header'] ? $doc['header'] : [],
            'type' => 'static',
            'status' => 'done',
            'desc' => $doc['desc'] ?? '',
        ];
        if ($doc['api']['method'] == 'post') {
            $this->data[$doc['group']['group'] . '-' . $doc['group']['child']]['list'][$doc['api']['comment']]['req_body_type'] = 'form';
            $this->data[$doc['group']['group'] . '-' . $doc['group']['child']]['list'][$doc['api']['comment']]['req_body_form'] = $doc['param'] ?? [];
        } else {
            $this->data[$doc['group']['group'] . '-' . $doc['group']['child']]['list'][$doc['api']['comment']]['req_query'] = $doc['param'] ?? [];
        }
        $this->data[$doc['group']['group'] . '-' . $doc['group']['child']]['list'][$doc['api']['comment']]['query_path'] = [
            'path' => $doc['api']['route'],
            'params' => []
        ];
//        $this->data[$doc['group']['group']][$doc['group']['child']][$doc['api']['comment']] = [
//            'method'    =>  $doc['api']['method'],
//            'url'       =>  $doc['api']['route'],
//            'comment'   =>  $doc['api']['comment'],
//            'param'     =>  $doc['param']??[],
//            'success'   =>  $doc['success']??[],
//            'header'    =>  $doc['header']??[],
//            'successExample' => $doc['successExample']??''
//        ];

    }
}

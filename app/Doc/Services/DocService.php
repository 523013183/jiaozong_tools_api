<?php


namespace App\Doc\Services;

use Illuminate\Support\Facades\Log;

class DocService
{
    private $data = [];
    /**
     * 获取文档分组菜单
     * @return array
     */
    public function group()
    {
        return include base_path('config/doc.php');
    }

    /**
     * 获取模块接口详情
     * @param $data
     * @return array
     */
    public function detail($data)
    {
        if (!isset($data['module'])) {
            return [];
        }
        $module = $data['module'];
        $content = file_get_contents(storage_path('doc/' . $module . '.json'));
        $content = json_decode($content, true);
        return $content;
    }

    /**
     * 根据路径生成文档
     * @param $path
     */
    public function make($path)
    {
        $this->delete();
        $files = $this->getAllFiles($path);
        foreach ($files as $file) {
            if (strpos($file, '.php') == false) {
                continue;
            }
            if (strpos($file, 'routes.php') != false) {
                continue;
            }
            if (strpos($file, 'helpers.php') != false) {
                continue;
            }
            if (strpos($file, 'Controller.php') == false) {
                continue;
            }
            $class = $this->getClassByPath($file);
            $annotation = new AnnotationService($class);
            $document = $annotation->getAllDocComment();
            if (empty($document)) {
                continue;
            }
            if ($document) {
                $this->addData($document);
            }
        }
        $this->writeToFile(base_path('public').'/doc.json', $this->data);
        echo 'success'.PHP_EOL;
    }

    /**
     * 根据路径获取类名
     * @param $path
     * @return string
     */
    private function getClassByPath($path)
    {
        $basePath = base_path('app');
        $path = str_replace($basePath, 'App', $path);
        $path = str_replace("/", "\\", $path);
        $path = str_replace('.php', '', $path);
        return $path;
    }

    /**
     * 删除存储目录下所有生成的文件
     */
    private function delete()
    {
        $files = $this->getAllFiles(storage_path('doc'));
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * 遍历获取目录下的所有文件
     * @param $path
     * @param $files
     */
    private function getFiles($path, &$files)
    {
        if (is_dir($path)) {
            $dp = dir($path);
            while ($file = $dp->read()) {
                if ($file != "." && $file != "..") {
                    // AudiencePool/Scene/Websocket/Workflow
                    $this->getFiles($path . "/" . $file, $files);
                }
            }
            $dp->close();
        }

        if (is_file($path)) {
            $files[] = $path;
        }
    }

    /**
     * 递归获取目录下所有的文件 包含子目录
     * @param $dir
     * @return array
     */
    public function getAllFiles($dir)
    {
        $files = array();
        $this->getFiles($dir, $files);
        return $files;
    }

    /**
     * 写入文档
     * @param $document
     */
    private function write($document)
    {
        foreach ($document as $key=>$item) {
            $file = storage_path('doc/'.$key.'.json');
            if (!file_exists($file)) {
                $this->writeToFile($file);
            }
            $content = json_decode(file_get_contents($file), true);
            foreach ($item as $value) {
                $content[] = $value;
            }
            $this->writeToFile($file, $content);
        }
    }

    /**
     * 写入内容到文件
     * @param $file
     * @param array $content
     */
    private function writeToFile($file, $content=[])
    {
        $content = array_values($content);
        foreach ($content as &$item) {
            if (isset($item['list'])) {
                $item['list'] = array_values($item['list']);
            }
        }
        if (strpos($file, '.json')) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        } else {
            $content = "export default ".json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        $file = fopen($file, 'w');
        fwrite($file, $content);
        fclose($file);
    }

    /**
     * 加入数据
     * @param $document
     */
    private function addData($document)
    {
        foreach ($document as $index=>$item) {
            if (isset($this->data[$index])) {
                foreach ($item['list'] as $list) {
                    $this->data[$index]['list'][] = $list;
                }
            } else {
                $this->data[$index] = $document[$index];
            }
        }
    }
}

<?php
/**
 * User: bluey
 * Date: 2019/1/10
 * Time: 17:47
 */

namespace App\Doc\Controllers;

use App\Base\Controllers\Controller;
use function GuzzleHttp\Psr7\str;

class CountController extends Controller
{
    /**
     * 获取文件列表
     * @param $dir
     * @return array
     */
    protected function file_list($dir)
    {
        $arr = [];
        $dir_handle = opendir($dir);
        if ($dir_handle) {
            while (($file = readdir($dir_handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $tmp = realpath($dir . '/' . $file);
                if (is_dir($tmp)) {
                    if (in_array($file, ['Doc', 'Facades', 'Traits', 'Api', 'Providers', 'Base', 'Console', 'Jobs'])) {
                        continue;
                    }

                    $retArr = $this->file_list($tmp);
                    if (!empty($retArr)) {
                        $arr = array_merge($arr, $retArr);
                    }
                } else {
                    if (in_array($file, ['routes.php', 'README.MD', 'WebSocketHandler.php'])) {
                        continue;
                    }

                    $arr[] = $tmp;
                }
            }
            closedir($dir_handle);
        }
        return $arr;
    }

    /**
     * 解析PHP文件
     * @param $file - 文件绝对路径
     * @return string
     */
    protected function parseDoc($file)
    {
        $doc = '';
        $content = file_get_contents($file);
        $tokens = token_get_all($content);
        $total = count($tokens);
        for ($i = 0; $i < $total; $i++) {
            if (is_string($tokens[$i])) {
                $doc .= $tokens[$i];
            } else {
                if ($tokens[$i][0] != T_COMMENT && $tokens[$i][0] != T_DOC_COMMENT) {
                    $doc .= $tokens[$i][1];
                }
            }
        }

        return str_replace("\r\n\r\n", "\r\n", $doc);
    }

    /**
     * 统计文件列表
     */
    public function doc()
    {
        set_time_limit(0);
        $zhList = [];
        $dir = str_replace(lcfirst(__CLASS__) . ".php", "app\\", __FILE__);
        $fileList = $this->file_list($dir);
        $curFile = '';
        foreach ($fileList as $file) {
            $path = str_replace($dir, "", $file);
            if ($curFile != $file) {
                $zhList[] = '## ' . $path;
            }

            $content = $this->parseDoc($file);
            // 逐行解析数据
            $lines = explode("\r\n", $content);
            if (1 == count($lines)) {
                $lines = explode("\n", $content);
            }

            $zhList[] = '~~~';
            $bFind = false;
            foreach ($lines as $line) {
                $newline = trim($line);
                if ($newline == "") {
                    continue;
                }

                if (strlen($newline) != mb_strlen($newline)) {
                    if (false !== strpos($newline, 'ApiException') ||
                        false !== strpos($newline, 'transL')) {
                        continue;
                    }

                    $bFind = true;
                    $zhList[] = $newline . "\n";
                }
            }

            if ($bFind) {
                $zhList[] = '~~~';
            } else {
                array_pop($zhList);
                array_pop($zhList);
            }
        }

        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-Disposition:attachment;filename=多语言关联文件.md");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Pragma:public");
        echo implode("\n", $zhList);
    }
}

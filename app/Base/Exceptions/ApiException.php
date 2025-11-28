<?php
namespace App\Base\Exceptions;


use App\Base\Services\AuthUser;

class ApiException extends \Exception
{
    use AuthUser;

    /**
     * 错误信息参数
     * @var array
     */
    protected $params = [];
    /**
     * 携带的参数
     * @var array
     */
    protected $data = [];

    protected $langs = [];

    /** 处理异常错误
     * @param string $id 语言包中的key or 错误代码
     * @param null $message 错误信息 or 替换参数
     * @param array $params 替换参数 or 附加数据
     * @param int $code 错误代码 or 无用
     * @param null $locale 语言 or 无用
     * @param array $data or 无用
     */
    public function __construct($id, $message = null, $params = [], $code = 0, $locale = null, $data = [])
    {
        if (empty($id)) {
            return parent::__construct($message, $code, null);
        }
        $locale = $locale ?: app('translator')->getLocale();
        if (empty($locale)) {
            $locale = "zh-cn";
        }
        $this->params = $params;
        $this->data = $data;
        if (is_numeric($id)) {
            //兼容原本的调用方式和顺序
            //$id, $params=[], $data=[], $message = null
            $this->params = $message ? (array)$message : [];
            $this->data = $params;
            if (!$code) {
                //根据中间件设置的语言处理多语言消息
                $message = $this->parseMessage($id);
            } else {
                $message = $code;
            }
            $code = $id;
        } else {
            $message = $this->parseNewMessage($id, $message, $code, $locale);
        }
        parent::__construct($message, $code, null);
    }

    /**
     * 解析错误信息
     * @param $code
     * @return string
     */
    protected function parseMessage($code)
    {
        $errors = config('error');
        if (!isset($errors[$code])) {
            return '服务器繁忙，请稍候重试.';
        }
        $message = trans($errors[$code], $this->params);
        if ($message != $code && !empty($this->params)) {
            foreach ($this->params as $key => $item) {
                $message = str_replace('{' . $key . '}', $item, $message);
            }
        }
        return $message;
    }

    /**
     * 获取参数数据
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 解析语言包文件
     * @param $id
     * @param $message
     * @param $code
     * @param null $locale
     * @return array
     */
    public function parseNewMessage($id, $message, &$code, $locale = null)
    {
        $code = -1;
        if (is_null($id)) {
            return $message ?: $id;
        }
        $keys = explode('.', $id);
        $this->getLang($keys[0], $locale);

        if (!$this->langs[$locale][$keys[0]]) {
            return $message ?: $id;
        }

        $data = isset($this->langs[$locale][$keys[0]][$keys[1]]) ? $this->langs[$locale][$keys[0]][$keys[1]] : [];
        if ($data) {
            if (is_array($data)) {
                $code = $data[0];
                $message = $data[1];
            } else {
                $message = $data;
            }
        } else {
            $message = $message ?: $id;
        }

        if (!empty($this->params)) {
            foreach ($this->params as $key => $item) {
                $message = str_replace('{' . $key . '}', $item, $message);
            }
        }
        return $message;
    }

    /*
     * 取语言包文件
     */
    public function getLang($key, $locale = 'zh-cn')
    {
        $path = 'lang/' . $locale . '/' . $key;
        if (!isset($this->langs[$locale]) || !isset($this->langs[$locale][$key])) {
            $this->langs[$locale][$key] = include resource_path($path . '.php');
        }
    }
}

<?php

namespace App\Base\Library;

class DES
{
    private $key = '';

    function __construct($key)
    {
        $this->key = $this->complement($key);
    }

    /*
     * 不足8位的key自动补足8位
     * */
    public function complement($key)
    {
        $len = strlen($key) % 8;
        if ($len == 0) {
            return $key;
        } else {
            $j = 8 - $len;
            for ($i = 0; $i < $j; $i++) {
                $key .= "\0";
            }
            return $key;
        }
    }

    /**
     * PHP DES 加密程式
     * @param $encrypt 要加密的明文
     * @return string 密文
     */
    function encrypt($encrypt)
    {
        $out = openssl_encrypt($encrypt, 'DES-ECB', $this->key, OPENSSL_RAW_DATA);
        return base64_encode($out);
    }

    /**
     * PHP DES 解密程式
     * @param $decrypt 要解密的密文
     * @return string 明文
     */
    function decrypt($decrypt)
    {
        return openssl_decrypt(base64_decode($decrypt), 'DES-ECB', $this->key, OPENSSL_RAW_DATA);
    }
}

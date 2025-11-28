<?php

abstract class TestCase extends Laravel\Lumen\Testing\TestCase
{
    protected $token = '5da1beb37d511577b6fc3a9b67052344';
    protected $username = 'youwl';
    protected $password = 'Mm123654!';//'111111';
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * 测试模拟请求
     * @param string $method
     * @param string $url
     * @param array ;
     */
    public function request(string $method,string $url,array $params=[], $content=null,$need_auth = 1){
        $token = '';
        if($need_auth) {
            $token = !empty($this->token) ? $this->token : $this->getToken();
            var_dump($token);
        }
        $headers = [
            'token' => $token,
            'X-Requested-With'=>'XMLHttpRequest',
            'Content-Type' => 'application/x-www-form-urlencoded',
//            'token' => '6ac7ad9ca379fc2d94e2df02eb457594'
        ];
        $this->refreshApplication();
        $this->setUp();
        $server = $this->transformHeadersToServerVars($headers);
        $response = $this->call($method,$url,$params,[], [], $server, $content);
        print_r('HTTP状态码：'.$response->getStatusCode().PHP_EOL);
        print_r('返回值：'.PHP_EOL);
        $data = json_decode($response->getContent(),true);
        print_r($data);
        $this->assertEquals(0,$data['ret']??-1);
        echo PHP_EOL.PHP_EOL.json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    protected function getToken(){
//        return 'de5676e2dd0c2666d8b3f135b819d305';
        $headers = [
            'X-Requested-With'=>'XMLHttpRequest',
        ];
        $server = $this->transformHeadersToServerVars($headers);
        $response = $this->call('post','/admin/admin-login',['user_name'=>$this->username,'password'=>$this->password],[],[],$server);
        if($response->getStatusCode()!=200){
            echo '登录失败';exit;
        }

        $data = json_decode($response->getContent(),true);
        if(isset($data['ret']) && $data['ret']!=0){
            print_r($data);
            exit;
        }
        return $data['data']['token'];
    }

    /**
     * 覆盖setUp方法 添加对sql查询监听
     */
    protected function setUp(): void
    {
        parent::setUp();
        sqlDump();
    }
}

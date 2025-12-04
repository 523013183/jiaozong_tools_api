<?php

class ApiExamTest extends TestCase
{
    // ./vendor/bin/phpunit tests/ApiExamTest.php --filter testGetList  --dont-report-useless-tests
    public function testGetList()
    {
        $this->request('get','/api/exam/list',['page'=>1,'page_size'=>10],null,0);
    }
}

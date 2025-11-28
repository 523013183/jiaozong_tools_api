<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->get('/');
        \Illuminate\Support\Facades\Log::info('1212434');
//        $this->assertEquals(
//            $this->app->version(), $this->response->getContent()
//        );
    }

    /**
     * 获取上个月的开始和结束
     * @param int $ts 时间戳
     * @return array 第一个元素为开始日期，第二个元素为结束日期
     */
    protected function lastMonth($ts) {
        $ts = intval($ts);

        $oneMonthAgo = mktime(0, 0, 0, date('n', $ts) - 1, 1, date('Y', $ts));
        $year = date('Y', $oneMonthAgo);
        $month = date('n', $oneMonthAgo);
        return array(
            date('Y-m-1', strtotime($year . "-{$month}-1")),
            date('Y-m-t', strtotime($year . "-{$month}-1"))
        );
    }
    /**
     * 获取上n周的开始和结束，每周从周一开始，周日结束日期
     * @param int $ts 时间戳
     * @param int $n 你懂的(前多少周)
     * @param string $format 默认为'%Y-%m-%d',比如"2012-12-18"
     * @return array 第一个元素为开始日期，第二个元素为结束日期
     */
    protected function lastNWeek($ts, $n, $format = '%Y-%m-%d') {
        $ts = intval($ts);
        $n  = abs(intval($n));

        // 周一到周日分别为1-7
        $dayOfWeek = date('w', $ts);
        if (0 == $dayOfWeek)
        {
            $dayOfWeek = 7;
        }

        $lastNMonday = 7 * $n + $dayOfWeek - 1;
        $lastNSunday = 7 * ($n - 1) + $dayOfWeek;
        return array(
            strftime($format, strtotime("-{$lastNMonday} day", $ts)),
            strftime($format, strtotime("-{$lastNSunday} day", $ts))
        );
    }

    public function testLast(){
        print_r($this->lastMonth(strtotime('2017-07-06')));
        print_r($this->lastNWeek(strtotime('2017-07-06'),1));
        echo date('Y-m-d', strtotime('this week'));
        echo date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
    }
}

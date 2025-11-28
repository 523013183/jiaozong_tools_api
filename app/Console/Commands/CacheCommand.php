<?php


namespace App\Console\Commands;

use App\Base\Services\CacheTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheCommand extends Command
{
    use CacheTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除缓存';



    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        if ($name=='flush') {
            Cache::flush();
            echo 'cache flush success.';
        } elseif (!empty($name)) {
            if ($name == 'ws:fd') {
                $withPrefix = false;
            } else {
                $withPrefix = true;
            }
            $this->removeByKey($name, $withPrefix);
            echo 'cache keys:'.$name.' remove successful';
        }
    }
}

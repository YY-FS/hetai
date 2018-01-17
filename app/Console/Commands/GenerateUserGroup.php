<?php

namespace App\Console\Commands;

use App\Models\Platv4UserGroup;
use App\Services\UserGroupService;
use Illuminate\Console\Command;

class GenerateUserGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Generate:UserGroup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成用户分组的用户id';

    /**
     * Create a new command instance.
     *
     * @return void
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
        ini_set('memory_limit', '1024M');

//        $this->_testArray();
//        return;

        $userGroups = Platv4UserGroup::where('mode', 'auto')->where('status', '!=', -1)->get()->toArray();
        $serviceUserGroup = new UserGroupService();
//        $serviceUserGroup->genGroupUser(1); // debug
        foreach ($userGroups as $userGroup) {
            $serviceUserGroup->genGroupUser($userGroup->id);
        }

    }

    private function _testArray()
    {
        //
        echo 'start: ' . $this->convert(memory_get_usage()) , PHP_EOL;

        $a = [];
        for ($i=0; $i<8000000; $i++) {
            $a[] = (int)rand(1, 8000000);
        }
        echo '$a: ' . $this->convert(memory_get_usage()) , PHP_EOL;

        $st = microtime(true);
        var_dump(array_key_exists(23423, $a));
        $et = microtime(true);
        echo 'duration: ' . ($et - $st) . ' s' . PHP_EOL;
        echo 'in_array: ' . $this->convert(memory_get_usage()) , PHP_EOL;
        echo 'in_array: TOP: ' . $this->convert(memory_get_peak_usage()) , PHP_EOL;

    }

    private function _test()
    {
        //
        echo $this->convert(memory_get_usage()) , PHP_EOL;
        $start = memory_get_usage();

        $a = [];
        for ($i=0; $i<8000000; $i++) {
            $a[] = rand(1, 8000000);
        }
        echo '$a: ' . $this->convert(memory_get_usage()) , PHP_EOL;

        $str = implode(',', $a);
        echo '$str: ' . $this->convert(memory_get_usage()) , PHP_EOL;
        echo '$str: TOP: ' . $this->convert(memory_get_peak_usage()) , PHP_EOL;

        unset($a);

        $arr = explode(',', $str);
        echo '$arr: ' . $this->convert(memory_get_usage()) , PHP_EOL;
        echo '$arr: TOP: ' . $this->convert(memory_get_peak_usage()) , PHP_EOL;

        var_dump(count($arr));
//        $b = [];
//        for ($i=0; $i<4000000; $i++) {
//            $b[] = rand(1, 4000000);
//        }
//        echo '$b: ' . $this->convert(memory_get_usage()) , PHP_EOL;
//        echo '$b top: ' . $this->convert(memory_get_peak_usage()) , PHP_EOL;

//        $result = array_intersect($a, $b);
//        echo 'array_intersect: ' . $this->convert(memory_get_usage()) , PHP_EOL;
//        echo 'array_intersect: top: ' . $this->convert(memory_get_peak_usage()) , PHP_EOL;
//        var_dump(count($result));
//        echo 'count: ' . $this->convert(memory_get_usage()) , PHP_EOL;

        $end =  memory_get_usage();

        echo 'argv:',$this->convert($end - $start), PHP_EOL;
    }

    public function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}

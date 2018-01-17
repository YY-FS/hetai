<?php

namespace App\Console\Commands;

use App\Models\Platv4UserFilter;
use Illuminate\Console\Command;
use App\Services\UserFilterService;

class GenerateUserFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Generate:UserFilter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成用户筛选';

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
        //
        ini_set('memory_limit', '1024M');

        $filters = Platv4UserFilter::getUserFilters();

        $serviceUserFilter = new UserFilterService();
        foreach ($filters as $filter) {
            $funcName = $filter->filter_type;
            call_user_func_array([$serviceUserFilter, $funcName], [$filter]);
        }
    }
}

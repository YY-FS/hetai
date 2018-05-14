<?php

namespace App\Console\Commands;

use App\Models\Platv4WechatSubscriber;
use Illuminate\Console\Command;
use Overtrue\LaravelWeChat\Facade as EasyWechat;

class TagUsers extends Command
{
    const tagUsersError = [
        40032 => '每次传入的openid列表个数不能超过50个',
        45159 => '非法的标签',
        45059 => '有粉丝身上的标签数已经超过限制，即超过20个',
        40003 => '传入非法的openid',
        49003 => '传入的openid不属于此AppID',
        45009 => '接口调用超过限制',
        -1 => '系统繁忙',
    ];

    const tagCreateError = [
        45157 => '标签名非法，请注意不能和其他标签重名',
        45158 => '标签名长度超过30个字节',
        45056 => '	创建的标签数过多，请注意不能超过100个',
        -1 => '系统繁忙',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Generate:tagUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '非maka用户分批量添加标签';

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
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function handle()
    {
        // 微信服务
        $wechat = EasyWechat::officialAccount('default');

        // TODO: 需要填写每个标签多少人
        // 每组 50 万
        $perGroup = 50 * 10000;
        // 每次查询标记 10000 人
        $chunk = 1 * 10000;
        // 当前组别
        $group = 1;
        // 获取总人数
        $total = Platv4WechatSubscriber::where('status', 1)->doesntHave('platv4User')->count();
        // 分组数量，向上取整
        $groupNum = ceil($total / $perGroup);

        // 进度条
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->setFormat('%message% %tagsName% --> %status%' . "\n" . ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');


        // 1. 预先创建分组
        $bar = $this->output->createProgressBar($groupNum);
        $bar->setFormat('%message% %tagsName% --> %status%' . "\n" . ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        for ($i = 1; $i <= $groupNum; $i++) {
            $name = '非MAKA用户【' . $i . '】';

            $bar->setMessage('正在创建标签');
            $bar->setMessage($name, 'tagsName');

            $resp = $wechat->user_tag->create($name);

            $bar->advance();
            if (isset($resp['errcode']) && $resp['errcode'] != 0) {
                $this->question("\n" . '【创建标签失败】 ' . "\n");
                $bar->setMessage('失败' . $resp['errcode'] . ':' . $resp['errmsg'], 'status');
            } else {
                $this->info("\n" . '【创建标签成功】' . $name . "\n");
                $bar->setMessage('成功', 'status');
            }

        }

        // 2. 获取标签信息
        $list = $wechat->user_tag->list();
        // 3. 转换数据格式
        $tagsList = collect($list['tags'])->keyBy('name')->toArray();


        // 4. 分块查询非maka用户信息
        Platv4WechatSubscriber::where('status', 1)
            ->doesntHave('platv4User')
            ->select('id', 'open_id')
            ->chunkById($chunk, function ($users) use ($wechat, &$tagsList, &$group, $perGroup, $groupNum, $progressBar) {
                $users->pluck('open_id')
                    ->chunk(50)
                    ->map(function ($openIds, $key) use ($wechat, &$tagsList, &$group, $perGroup, $groupNum, $progressBar) {
                        // 4.1 大于预设分组数，退出
                        if ($group > $groupNum) {
                            $progressBar->finish();
                            return false;
                        }

                        // 标签组
                        $tagsName = '非MAKA用户【' . $group . '】';

                        // 进度条
                        $progressBar->setMessage('正在归类');
                        $progressBar->setMessage($tagsName, 'tagsName');

                        // 4.2 获取 50 个 open_id
                        $openIds = $openIds->toArray();
                        $openIdsCount = count($openIds);
                        // 4.3 设置标签
                        $resp = $wechat->user_tag->tagUsers($openIds, $tagsList[$tagsName]['id']);
                        if (isset($resp['errcode']) && $resp['errcode'] == 0) {
                            // 4.4.1 设置成功 标签计数
                            $tagsList[$tagsName]['count'] = $tagsList[$tagsName]['count'] + $openIdsCount;
                            $progressBar->setMessage('成功', 'status');
                        } else {
                            // 4.4.2 设置失败
                            $this->error("\n【标签归类失败】\n");
                            $progressBar->setMessage('失败' . $resp['errcode'] . ':' . $resp['errmsg'], 'status');
                        }

                        // 进度条增加
                        $progressBar->advance($openIdsCount);

                        // 4.5 标签分组大于预设每组数量，进行下一组
                        if ($tagsList[$tagsName]['count'] >= $perGroup) {
                            $group++;
                        }
                    });
            });
        $this->info('处理完成');
    }
}

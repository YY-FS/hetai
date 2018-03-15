<?php
/**
 * Created by PhpStorm.
 * User: dodd
 * Date: 2017/10/24
 * Time: 14:28
 */

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use OSS\Core\OssException;
use OSS\OssClient;

class OssController extends Controller
{
    public $ossBucket;
    public $ossAppId;
    public $ossAppSecret;
    public $ossEndpoint;
    public $ossViewDomain;

    public function __construct()
    {
        $this->ossBucket = env('ALI_OSS_PLAT_BUCKET');
        $this->ossAppId = env('ALI_OSS_PLAT_ACCESS_KEY');
        $this->ossAppSecret = env('ALI_OSS_PLAT_ACCESS_SECRET');
        $this->ossEndpoint = env('ALI_OSS_PLAT_ENDPOINT');
        $this->ossViewDomain = env('ALI_OSS_PLAT_VIEW_DOMAIN');
    }

    public function showObject()
    {
        $nextMarker = '';
        $oss = new OssClient($this->ossAppId, $this->ossAppSecret, $this->ossEndpoint);
        $list = [];
        $sort = [];
//        $url = url()->current();
        $prefix = Input::get('dir','');
        $single = Input::get('single',false);  //配置是否限制为单文件上传
//        if(strpos($url,'modal') !== false){
//            $prefix = 'MODAL/IMAGES/' . $imageDir . '/';
//            $single = true;
//        }else{
//            $prefix = 'HEADLINE/IMAGES/' . $imageDir . '/';
//            $single = false;
//        }

        while (true) {
            $options = [
                'prefix' => $prefix,
                'delimiter' => '/',
                'max-keys' => 200,
                'marker' => $nextMarker,
            ];
            try {
                $objectInfo = $oss->listObjects($this->ossBucket, $options);
            } catch (OssException $e) {
                printf(__FUNCTION__ . ": FAILED\n");
                printf($e->getMessage() . "\n");
                return;
            }

//            得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
            $nextMarker = $objectInfo->getNextMarker();

            $objectList = $objectInfo->getObjectList();

            foreach ($objectList as $key => $item) {
                if (strpos($item->getKey(), 'html') !== false) continue;
                $list[] = [
                    'auto_id' => $key,
                    'name' => str_replace($options['prefix'], '', $item->getKey()),
                    'key' => $item->getKey(),
                    'last_modify' => date('Y-m-d H:i:s', strtotime($item->getLastModified())),
                    'eTag' => $item->getETag(),
                    'type' => $item->getType(),
                    'size' => $this->fileSizeFormat($item->getSize()),
                    'storageClass' => $item->getStorageClass(),
                    'url' => 'http://' . $this->ossViewDomain . '/' . $item->getKey(),
                ];
                $sort[] = strtotime($item->getLastModified());
            }

            if ($nextMarker === '') {
                break;
            }
        }
        array_multisort($sort, SORT_DESC, $list);

        $dir = $prefix;
        $ossViewDomain = $this->ossViewDomain;
        return view('oss.list', compact('list', 'dir','single','ossViewDomain'));
    }

    /**
     * 文件大小格式化
     * @param integer $size 初始文件大小，单位为byte
     * @return string 格式化后的文件大小和单位数组，单位为byte、KB、MB、GB、TB
     */
    public function fileSizeFormat($size = 0, $dec = 2)
    {
        $unit = ["B", "KB", "MB", "GB", "TB", "PB"];
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        $result['size'] = round($size, $dec);
        $result['unit'] = $unit[$pos];

        return $result['size'] . $result['unit'];
    }

    public function auth()
    {
        $this->requestValidate([
            'dir' => 'required',
        ]);
        $dir = Input::get('dir', null);

        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmtIso8601($end);

        //最大文件大小.用户可以自己设置
        $condition = [0 => 'content-length-range', 1 => 0, 2 => 1048576000];
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = [0 => 'starts-with', 1 => '$key', 2 => $dir];
        $conditions[] = $start;


        $arr = ['expiration' => $expiration, 'conditions' => $conditions];

        $policy = json_encode($arr);
        $base64Policy = base64_encode($policy);
        $stringToSign = $base64Policy;
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->ossAppSecret, true));

        $response = [];
        $response['accessid'] = $this->ossAppId;
        $response['host'] = 'http://' . $this->ossBucket . '.' . $this->ossEndpoint;
        $response['policy'] = $base64Policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;

        return $this->respData($response);
    }

    private function gmtIso8601($time)
    {
        $dtStr = date("c", $time);
        $myDatetime = new \DateTime($dtStr);
        $expiration = $myDatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }
}
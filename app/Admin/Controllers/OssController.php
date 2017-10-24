<?php
/**
 * Created by PhpStorm.
 * User: dodd
 * Date: 2017/10/24
 * Time: 14:28
 */

namespace App\Admin\Controllers;

use OSS\OssClient;

class OssController
{
    public $ossBucket;
    public $ossAppId;
    public $ossAppSecret;
    public $ossEndpoint;

    public function __construct()
    {
        $this->ossBucket = env('ALI_OSS_PLAT_BUCKET');
        $this->ossAppId = env('ALI_OSS_PLAT_ACCESS_KEY');
        $this->ossAppSecret = env('ALI_OSS_PLAT_ACCESS_SECRET');
        $this->ossEndpoint = env('ALI_OSS_PLAT_ENDPOINT');
    }

    public function headlineObject($id)
    {
        $oss = new OssClient($this->ossAppId, $this->ossAppSecret, $this->ossEndpoint);
        $options = [
            'prefix' => 'HEADLINE/' . $id . '/',
            'delimiter' => '/',
        ];
        $objectInfo = $oss->listObjects($this->ossBucket, $options);
        $objectList = $objectInfo->getObjectList();

        $list = [];
        foreach ($objectList as $key => $item) {
            if (strpos($item->getKey(), 'html') !== false) continue;
            $list[] = [
                'auto_id' => $key,
                'name' => str_replace($options['prefix'], '', $item->getKey()),
                'key' => $item->getKey(),
                'last_modify' => date('Y-m-d H:i:s',strtotime($item->getLastModified())),
                'eTag' => $item->getETag(),
                'type' => $item->getType(),
                'size' => $this->fileSizeFormat($item->getSize()),
                'storageClass' => $item->getStorageClass(),
                'url' => 'http://' . $this->ossBucket . '.' . $this->ossEndpoint . '/' . $item->getKey(),
            ];
        }
        return view('oss.list', compact('list'));
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
}
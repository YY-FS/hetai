<?php
namespace App\Services\Umeng;

require_once(dirname(__FILE__) . '/notification/android/AndroidBroadcast.php');
require_once(dirname(__FILE__) . '/notification/android/AndroidFilecast.php');
require_once(dirname(__FILE__) . '/notification/android/AndroidGroupcast.php');
require_once(dirname(__FILE__) . '/notification/android/AndroidUnicast.php');
require_once(dirname(__FILE__) . '/notification/android/AndroidCustomizedcast.php');
require_once(dirname(__FILE__) . '/notification/ios/IOSBroadcast.php');
require_once(dirname(__FILE__) . '/notification/ios/IOSFilecast.php');
require_once(dirname(__FILE__) . '/notification/ios/IOSGroupcast.php');
require_once(dirname(__FILE__) . '/notification/ios/IOSUnicast.php');
require_once(dirname(__FILE__) . '/notification/ios/IOSCustomizedcast.php');

class Umeng
{
    protected $appkey = NULL;
    protected $productionMode = true;
    protected $appMasterSecret = NULL;
    protected $timestamp = NULL;
    protected $validation_token = NULL;

    public function __construct($key, $secret, $mode = "false")
    {
        $this->appkey = $key;
        $this->appMasterSecret = $secret;
        $this->productionMode = $mode;
        $this->timestamp = strval(time());
    }

    function debug($str)
    {
//        print($str);
    }


}
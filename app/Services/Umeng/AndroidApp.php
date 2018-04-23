<?php
/**
 * User: linjianmin
 * Date: 2017/9/5
 * Time: 16:35
 */
namespace App\Services\Umeng;

class AndroidApp extends Umeng
{

    function sendAndroidBroadcast($ticker, $title,$text,$afterOpen='go_custom',$custom,$extraField=null)  {
        try {
            $brocast = new \AndroidBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey",           $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $brocast->setPredefinedKeyValue("ticker",           $ticker);
            $brocast->setPredefinedKeyValue("title",            $title);
            $brocast->setPredefinedKeyValue("description",      $title);
            $brocast->setPredefinedKeyValue("text",             $text);
            $brocast->setPredefinedKeyValue("after_open",       $afterOpen);
            $brocast->setPredefinedKeyValue("custom",       $custom);
            $brocast->setPredefinedKeyValue("production_mode", $this->productionMode);
            // [optional]Set extra fields
            if( $extraField ){
                foreach( $extraField as $key => $field ){
                    $brocast->setExtraField("{$key}", "{$field}");
                }
            }

            $this->debug("Sending broadcast notification, please wait...\r\n");
            $brocast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    /**
     * @param $deviceToken
     * @param $ticker
     * @param $title
     * @param $text
     * @param string $afterOpen
     * @param null $extraField
     * @param bool|false $debug
     */
    function sendAndroidUnicast($deviceToken, $ticker, $title,$text,$afterOpen='go_custom',$custom=null) {
        try {
            $unicast = new \AndroidUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey",           $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $unicast->setPredefinedKeyValue("device_tokens",    $deviceToken);
            $unicast->setPredefinedKeyValue("ticker",           $ticker);
            $unicast->setPredefinedKeyValue("title",            $title);
            $unicast->setPredefinedKeyValue("text",             $text);
            $unicast->setPredefinedKeyValue("after_open",       $afterOpen);
            if( $custom ){
                $unicast->setPredefinedKeyValue("custom",       $custom);
            }

            $unicast->setPredefinedKeyValue("production_mode", $this->productionMode);

            $this->debug("Sending unicast notification, please wait...\r\n");
            $unicast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidFilecast() {
        try {
            $filecast = new \AndroidFilecast();
            $filecast->setAppMasterSecret($this->appMasterSecret);
            $filecast->setPredefinedKeyValue("appkey",           $this->appkey);
            $filecast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $filecast->setPredefinedKeyValue("ticker",           "Android filecast ticker");
            $filecast->setPredefinedKeyValue("title",            "Android filecast title");
            $filecast->setPredefinedKeyValue("text",             "Android filecast text");
            $filecast->setPredefinedKeyValue("after_open",       "go_custom");  //go to app
            $this->debug("Uploading file contents, please wait...\r\n");
            // Upload your device tokens, and use '\n' to split them if there are multiple tokens
            $filecast->uploadContents("aa"."\n"."bb");
            $this->debug("Sending filecast notification, please wait...\r\n");
            $filecast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidGroupcast($filter,$ticker,$title,$text,$afterOpen='go_custom') {
        try {
            $groupcast = new \AndroidGroupcast();
            $groupcast->setAppMasterSecret($this->appMasterSecret);
            $groupcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $groupcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set the filter condition
            $groupcast->setPredefinedKeyValue("filter",           $filter);
            $groupcast->setPredefinedKeyValue("ticker",           $ticker);
            $groupcast->setPredefinedKeyValue("title",            $title);
            $groupcast->setPredefinedKeyValue("text",             $text);
            $groupcast->setPredefinedKeyValue("after_open",       $afterOpen);
            $groupcast->setPredefinedKeyValue("production_mode", $this->productionMode);
            $this->debug("Sending groupcast notification, please wait...\r\n");
            $groupcast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    function sendAndroidCustomizedcast($ticker,$title,$text,$alias,$alias_type,$custom=null,$after_open="go_custom") {
        try {
            $customizedcast = new \AndroidCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias",            $alias);
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type",       $alias_type);
            $customizedcast->setPredefinedKeyValue("ticker",           $ticker);
            $customizedcast->setPredefinedKeyValue("title",            $title);
            $customizedcast->setPredefinedKeyValue("description",      $title);
            $customizedcast->setPredefinedKeyValue("text",             $text);
            $customizedcast->setPredefinedKeyValue("after_open",       $after_open);
            $customizedcast->setPredefinedKeyValue("custom",       $custom);

            $this->debug("Sending customizedcast notification, please wait...\r\n");
            return $customizedcast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }
    // 重新封装了一下,适配广播与组播的方式
    function sendAndroid($ticker,$title,$text,$afterOpen,$custom=null,$filter=null){
        $brocast = new \AndroidBroadcast($filter);
        $brocast->setAppMasterSecret($this->appMasterSecret);
        $brocast->setPredefinedKeyValue("appkey",           $this->appkey);
        $brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
        $brocast->setPredefinedKeyValue("ticker",           $ticker);
        $brocast->setPredefinedKeyValue("title",            $title);
        $brocast->setPredefinedKeyValue("text",             $text);
        $brocast->setPredefinedKeyValue("after_open",       $afterOpen);
        if( !empty($custom) ){
            $brocast->setPredefinedKeyValue("custom",           $custom);
        }
        if( !empty($filter) ){
            $brocast->setPredefinedKeyValue("filter",           $filter);
        }

        $brocast->setPredefinedKeyValue("production_mode", $this->productionMode);
        return  $brocast->send();
    }
}
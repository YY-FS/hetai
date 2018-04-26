<?php
/**
 * User: linjianmin
 * Date: 2017/9/5
 * Time: 16:35
 */
namespace App\Services\Umeng;

class IOSApp extends Umeng
{

    /**
     * @param $text
     * @param null $custom
     * @param null $filter
     * @param int $badge
     * @param string $sound
     * @return mixed
     * 组播或广播
     */
    function sendIOS($text, $custom = null, $filter = null, $badge = 0, $sound = "chime")
    {
        $brocast = new \IOSBroadcast($filter);
        $brocast->setAppMasterSecret($this->appMasterSecret);
        $brocast->setPredefinedKeyValue("appkey", $this->appkey);
        $brocast->setPredefinedKeyValue("timestamp", $this->timestamp);

        $brocast->setPredefinedKeyValue("alert", $text);
        $brocast->setPredefinedKeyValue("badge", $badge);
        $brocast->setPredefinedKeyValue("sound", $sound);
        $brocast->setPredefinedKeyValue("production_mode", $this->productionMode);

        if (!empty($custom)) {
            $brocast->setCustomizedField("data", $custom);
        }
        if (!empty($filter)) {
            $brocast->setPredefinedKeyValue("filter", $filter);
        }
        return $brocast->send();
    }

    function sendIOSBroadcast($text, $extraField = null, $badge = 0, $sound = "chime")
    {
        try {
            $brocast = new \IOSBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey", $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp", $this->timestamp);

            $brocast->setPredefinedKeyValue("alert", $text);
            $brocast->setPredefinedKeyValue("description", $text);
            $brocast->setPredefinedKeyValue("badge", $badge);
            $brocast->setPredefinedKeyValue("sound", $sound);
            $brocast->setPredefinedKeyValue("production_mode", $this->productionMode);
            if ($extraField) {
                foreach ($extraField as $key => $field) {
                    $brocast->setCustomizedField("{$key}", "{$field}");
                }
            }
            $this->debug("Sending broadcast notification, please wait...\r\n");
            $brocast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSUnicast($device_token, $title, $extraField = null, $badge = 0, $sound = "chime")
    {
        try {
            $unicast = new \IOSUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey", $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp", $this->timestamp);
            $unicast->setPredefinedKeyValue("device_tokens", $device_token);
            $unicast->setPredefinedKeyValue("alert", $title);
            $unicast->setPredefinedKeyValue("badge", $badge);
            $unicast->setPredefinedKeyValue("sound", $sound);
            $unicast->setPredefinedKeyValue("production_mode", "false");
            if ($extraField) {
                foreach ($extraField as $key => $field) {
                    $unicast->setCustomizedField("{$key}", "{$field}");
                }
            }

            $this->debug("Sending unicast notification, please wait...\r\n");
            $unicast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSFilecast()
    {
        try {
            $filecast = new \IOSFilecast();
            $filecast->setAppMasterSecret($this->appMasterSecret);
            $filecast->setPredefinedKeyValue("appkey", $this->appkey);
            $filecast->setPredefinedKeyValue("timestamp", $this->timestamp);

            $filecast->setPredefinedKeyValue("alert", "IOS 文件播测试");
            $filecast->setPredefinedKeyValue("badge", 0);
            $filecast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $filecast->setPredefinedKeyValue("production_mode", "false");
            $this->debug("Uploading file contents, please wait...\r\n");
            // Upload your device tokens, and use '\n' to split them if there are multiple tokens
            $filecast->uploadContents("aa" . "\n" . "bb");
            $this->debug("Sending filecast notification, please wait...\r\n");
            $filecast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    function sendIOSGroupcast($filter, $ticker, $title, $text, $afterOpen = 'go_custom')
    {
        try {
            /*
              *  Construct the filter condition:
              *  "where":
              *	{
              *		"and":
              *		[
                *			{"tag":"iostest"}
              *		]
              *	}
              */
            $filter = array(
                "where" => array(
                    "and" => array(
                        array(
                            "tag" => "iostest"
                        )
                    )
                )
            );

            $groupcast = new \IOSGroupcast();
            $groupcast->setAppMasterSecret($this->appMasterSecret);
            $groupcast->setPredefinedKeyValue("appkey", $this->appkey);
            $groupcast->setPredefinedKeyValue("timestamp", $this->timestamp);
            // Set the filter condition
            $groupcast->setPredefinedKeyValue("filter", $filter);
            $groupcast->setPredefinedKeyValue("alert", $title);
            $groupcast->setPredefinedKeyValue("badge", 0);
            $groupcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $groupcast->setPredefinedKeyValue("production_mode", "false");
            $this->debug("Sending groupcast notification, please wait...\r\n");
            $groupcast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

    /**
     * 发送给指定用户
     */
    function sendIOSCustomizedcast($pushData, $alias, $alias_type, $custom = null, $filter = null)
    {
        try {
            $customizedcast = new \IOSCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey", $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp", $this->timestamp);

            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias", $alias);
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type", $alias_type);
            $customizedcast->setPredefinedKeyValue("alert", $pushData);
            $customizedcast->setPredefinedKeyValue("badge", 1);
            $customizedcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $customizedcast->setPredefinedKeyValue("production_mode", $this->productionMode);

            if (!empty($custom)) {
                $customizedcast->setCustomizedField("data", $custom);
            }
            if (!empty($filter)) {
                $customizedcast->setPredefinedKeyValue("filter", $filter);
            }


            $this->debug("Sending customizedcast notification, please wait...\r\n");
            return $customizedcast->send();
            $this->debug("Sent SUCCESS\r\n");
        } catch (\Exception $e) {
            $this->debug("Caught exception: " . $e->getMessage());
        }
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-4-19
 * Time: 下午4:34
 */
namespace App\Services;

use App\Models\Platv4Message;
use App\Services\Umeng\AndroidApp;
use App\Services\Umeng\IOSApp;
use Illuminate\Http\Response;

class UmengMessageService
{

    const IOS_ALIAS_TYPE = 'MAKA';
    const Android_ALIAS_TYPE = 'com.maka.app.uid';
    const AFTER_OPEN = 'go_custom';

    private $android_production_mode = true;
    private $ios_production_mode = true;
    protected $iosApp;
    protected $androidApp;

    private function initAndroid()
    {
        $this->androidApp = new AndroidApp(config('umeng.android_key'), config('umeng.android_secret'), $this->android_production_mode);
    }

    private function initIOS()
    {
        $this->iosApp = new IOSApp(config('umeng.ios_key'), config('umeng.ios_secret'), $this->ios_production_mode);
    }

//    public function umengPush($messageId, $uid = null)
//    {
//        $message = Platv4Message::find($messageId);
//        if (empty($message)) throw new \Exception('通知 not found');
//        $msg = [
//            'type' => $message->type,
//            'type_param' => $message->type_param,
//            'title' => $message->title,
//            'content' => $message->content,
//            'create_time' => date('Y-m-d H:i:s'),
//            'label' => $message->label,
//        ];
//
//        $customData = $this->buildCustomData($msg);
//
//        if ($uid == null) {
//            $ret = $this->umengBroadcast($message->title, $message->content, $customData, $message->device);
//        } else {
//            $ret = $this->umengCustomizedcast($message->title, $message->content, $customData, $uid, $message->device);
//        }
//        return $ret;
//    }
//
//    private function buildCustomData($msg)
//    {
//
//        $custom['title'] = $msg['title'];
//        $custom['description'] = $msg['content'];
//        $custom['create_time'] = $msg['create_time'];
//        $custom['type'] = $msg['type'];
//        $custom['label'] = $msg['label'];
//        $custom['data'] = [
//            'id' => $msg['type_param'],
//            'title' => $msg['title'],
//        ];
//        switch ($this->TYPE_ARRAY[$msg['type']]) {
//            // 如果是普通消息,则直接发广播
//            case  -1://"普通消息"
//                unset($custom['data']);
//                break;
//            case 0://"热门项目"
//                break;
//            case 1://"热门项目列表"
//                $custom['data'] = [
//                    'url' => 'http://www.maka.im/app/publicEvents?order=lastest&cateId=' . $msg['type_param'],
//                    'title' => $msg['title']
//                ];
//                break;
//            case  2://"H5页面"
//                $custom['data'] = [
//                    'url' => $msg['type_param'],
//                    'title' => $msg['title'],
//                ];
//                break;
//            case 3://"专题模版"
//                break;
//            case 4://"专题模版列表"
//                break;
//        }
//        return $custom;
//    }
//    public function umengBroadcast($title, $content, $customData = [], $device = 'all', $ticker = '您有新的消息', $filter = null)
//    {
//        if (!in_array($device, ['ios', 'app', 'android']) || !($title == null) || !($content == null))
//            return ['code' => Response::HTTP_INTERNAL_SERVER_ERROR];
//
//        $after = empty($customData) ? 'go_app' : 'go_custom';
//        $retIos['ret'] = $retAndroid['ret'] = 'FREE';
//        if ($device == 'app' || $device == 'ios') {
//            $this->initIOS();
//            $retIos = $this->iosApp->sendIOS($title, $customData, $filter);
//        }
//        if ($device == 'app' || $device == 'android') {
//            $this->initAndroid();
//            $retAndroid = $this->androidApp->sendAndroid($ticker, $title, $content, $after, $customData, $filter);
//        }
//
//        if ($retIos['ret'] == 'FAIL' && $retAndroid['ret'] == 'FAIL') {
//            throw new \Exception('发送友盟广播失败, ios:' . json_encode($retIos) . ', android:' . json_encode($retAndroid));
//        } else {
//            return ['code' => Response::HTTP_OK];//成功
//        }
//    }
//
//    public function umengCustomizedcast($title, $content, $customData = [], $uid, $device = 'all', $ticker = '您有新的信息', $filter = null)
//    {
//        if (empty($uid)) throw new \Exception('单播时uid不能为空');
//        if (!in_array($device, ['ios', 'app', 'android']) || $title == null || $content == null)
//            return ['code' => Response::HTTP_INTERNAL_SERVER_ERROR];
//
//        $after = empty($customData) ? 'go_app' : 'go_custom';
//        $retIos['ret'] = $retAndroid['ret'] = 'FREE';
//        if ($device == 'app' || $device == 'ios') {
//            $this->initIOS();
//            $retIos = $this->iosApp->sendIOSCustomizedcast($title, $uid, self::ALIAS_TYPE, $customData, $filter);
//        }
//        if ($device == 'app' || $device == 'android') {
//            $this->initAndroid();
//            $retAndroid = $this->androidApp->sendAndroidCustomizedcast($ticker, $title, $content, $uid, self::ALIAS_TYPE, $customData, $after);
//        }
//
//        if ($retIos['ret'] == 'FAIL' && $retAndroid['ret'] == 'FAIL') {
//            throw new \Exception('发送友盟广播失败, ios:' . json_encode($retIos) . ', android:' . json_encode($retAndroid));
//        } else {
//            return ['code' => Response::HTTP_OK];//成功
//        }
//    }
    public function umengPush($uid = '', $banner_id, $device, $type, $title, $description = '', $url = null, $template_set_id = null)
    {
        $msg = [
            'banner_id' => $banner_id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'template_set_id' => $template_set_id,
        ];

        $customData = $this->buildCustomData($msg);

        if ($uid == '') {
            $ret = $this->umengBroadcast($title, $description, $customData, $device);
        } else {
            $ret = $this->umengCustomizedcast($title, $description, $customData, $uid, $device);
        }
        return $ret;
    }

    private function buildCustomData($msg)
    {

        $custom['banner_id'] = $msg['banner_id'];
        $custom['type'] = $msg['type'];
        $custom['title'] = $msg['title'];
        $custom['description'] = $msg['description'];
        $custom['url'] = $msg['url'];
        $custom['template_set_id'] = $msg['template_set_id'];

        switch ($msg['type']) {
            case 'maka':
            case 'poster':
            case 'danye':
                $custom['url'] = '';
                break;
            case  'link':
                $custom['template_set_id'] = '';
                break;
            case 'category':
                $custom['url'] = '';
                $custom['template_set_id'] = '';
                break;
        }
        return $custom;
    }

    public function umengBroadcast($title, $description, $customData = [], $device = 'app', $ticker = '您有新的消息', $filter = null)
    {
        if (!in_array($device, ['ios', 'app', 'android']) || !($title == null) || !($description == null))
            return ['code' => Response::HTTP_INTERNAL_SERVER_ERROR];

        $after = empty($customData) ? 'go_app' : 'go_custom';
        $retIos['ret'] = $retAndroid['ret'] = 'FREE';
        if ($device == 'app' || $device == 'ios') {
            $this->initIOS();
            $retIos = $this->iosApp->sendIOS($title, $customData, $filter);
        }
        if ($device == 'app' || $device == 'android') {
            $this->initAndroid();
            $retAndroid = $this->androidApp->sendAndroid($ticker, $title, $description, $after, $customData, $filter);
        }

        if ($retIos['ret'] == 'FAIL' && $retAndroid['ret'] == 'FAIL') {
            throw new \Exception('发送友盟广播失败, ios:' . json_encode($retIos) . ', android:' . json_encode($retAndroid));
        } else {
            return ['code' => Response::HTTP_OK];//成功
        }
    }

    public function umengCustomizedcast($title, $description, $customData = [], $uid, $device = 'app', $ticker = '您有新的信息', $filter = null)
    {
        if (empty($uid)) throw new \Exception('单播时uid不能为空');
        if (!in_array($device, ['ios', 'app', 'android']) || $title == null || $description == null)
            return ['code' => Response::HTTP_INTERNAL_SERVER_ERROR];

        $after = empty($customData) ? 'go_app' : 'go_custom';
        $retIos['ret'] = $retAndroid['ret'] = 'FREE';
        if ($device == 'app' || $device == 'ios') {
            $this->initIOS();
            $retIos = $this->iosApp->sendIOSCustomizedcast($title, $uid, self::ALIAS_TYPE, $customData, $filter);
        }
        if ($device == 'app' || $device == 'android') {
            $this->initAndroid();
            $retAndroid = $this->androidApp->sendAndroidCustomizedcast($ticker, $title, $description, $uid, self::ALIAS_TYPE, $customData, $after);
        }

        if ($retIos['ret'] == 'FAIL' && $retAndroid['ret'] == 'FAIL') {
            throw new \Exception('发送友盟广播失败, ios:' . json_encode($retIos) . ', android:' . json_encode($retAndroid));
        } else {
            return ['code' => Response::HTTP_OK];//成功
        }
    }
}
<?php

/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use net\Http;
use Workerman\Timer;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    private static $businessConfig;

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据 
        Gateway::sendToClient($client_id, "connected");
        //连接成功，30秒内要进行绑定，否则自动关闭连接
        $_SESSION['bind_timer'] = Timer::add(30, function ($client_id) {
            Gateway::closeClient($client_id);
        }, array($client_id), false);

        self::$businessConfig = (new Business())->getConfig();
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $domain = self::$businessConfig['site_url'] ?? '';

        // 向所有人发送 
        $data = json_decode($message);
        $msgType = $data->msg_type ?? '';
        if (empty($msgType)) {
            Gateway::closeClient($client_id);
        } else {
            switch ($msgType) {
                case 'ping':
                    break;
                case 'bind':  //绑定，绑定成功才允许发消息
                    if (empty($data->from_id)) {
                        Gateway::closeClient($client_id);
                    } else {
                        Gateway::bindUid($client_id, $data->from_id);
                        Gateway::sendToClient($client_id, json_encode(['msg_type' => 'bind']));  //绑定成功这一句不能删除，前端是根据这个判断的
                        Timer::del($_SESSION['bind_timer']);
                    }
                    break;
                case 'text':  //文本
                case 'image':  //图片
                case 'video':  //视频
                case 'recommend_goods':  //客服推荐商品
                case 'kf_coupon':  //客服发送优惠券


                case 'coupon':  //自助菜单 -优惠券
                case 'search_orders': //自助菜单 -订单查询
                case 'guess_you_like': //自助菜单 - 猜你喜欢
                    if (empty($_SESSION[$client_id]) || $_SESSION[$client_id] < (time() - 120)) {
                        Gateway::closeClient($client_id);
                    } else {
                        try {
                            $sendResult = self::curlPost($domain . '/v20/public/index.php/im/User/send?cur_env=internal', $message);
                            if ($sendResult['status'] == 1000) {
                                //发送成功
                                $toUser = $sendResult['data']['to_user'];
                                $autoReply = $sendResult['data']['auto_reply']??'';
                                $data->target_id =  $toUser;

                                $extra = $sendResult['data']['extra']??[];
                                if($extra){
                                    if(!empty($extra['welcome'])){
                                        //系统消息发送欢迎语
                                        $welcome = [
                                            'chat_id' => $data->chat_id,
                                            'msg_type' => 'system_notify',
                                            'msg_body' => ['text' => $extra['welcome']],
                                            'from_id' => $data->target_id,
                                            'target_id' => $data->from_id,
                                        ];
                                        Gateway::sendToUid($data->from_id, json_encode($welcome));
                                    }
                                }

                                if (!in_array($data->msg_type, ['coupon', 'search_orders', 'guess_you_like'])) {
                                    Gateway::sendToUid($sendResult['data']['to_user'], json_encode($data));
                                }
                                $sendResult['msg_id'] = $data->msg_id;
                                Gateway::sendToUid($data->from_id, json_encode($sendResult));
                                if ($autoReply) {
                                    $autoReplyData = [
                                        'msg_id' => time(),
                                        'chat_id' => $data->chat_id,
                                        'msg_type' => 'text',
                                        'msg_body' => ['text' => $autoReply],
                                        'from_id' => $data->target_id,
                                        'target_id' => $data->from_id,
                                    ];
                                    Gateway::sendToUid($data->from_id, json_encode($autoReplyData));
                                }
                            } else {
                                //发送失败
                                throw new Exception($sendResult['msg'], $sendResult['status']);
                            }

                            //定时器检查回复是否过期并转发提示语到窗口
                            if ($data->chat_id && empty($_SESSION[$data->chat_id])) {
                                $_SESSION[$data->chat_id] = Timer::add(30, function ($client_id, $data, $domain) {
                                    $timeoutResult = self::curlPost($domain . '/v20/public/index.php/im/User/checkTimeout?cur_env=internal', json_encode(['username'=>$data->from_id,'chat_id'=>$data->chat_id]));
                                    $timeoutResult = $timeoutResult['data'] ?? [];
                                    if ($timeoutResult) {
                                        $timeoutResult['msg_id'] = date('YmdHis') . rand(1000, 9999);
                                        $timeoutResult['chat_id'] = $data->chat_id;
                                        Gateway::sendToUid($timeoutResult['target_id'], json_encode($timeoutResult));
                                        Timer::del($_SESSION[$data->chat_id]);
                                    }
                                }, array($client_id, $data, $domain), true);
                            }
                            
                        } catch (\Exception $e) {
                            //推送系统异常消息
                            $sendResult = [
                                'chat_id' => $data->chat_id,
                                'msg_type' => 'system_notify',
                                'msg_body' => ['text' => $e->getMessage()],
                                'from_id' => $data->target_id,
                                'target_id' => $data->from_id,
                            ];
                            Gateway::sendToUid($data->from_id, json_encode($sendResult));
                            Gateway::sendToUid($data->from_id, json_encode(['msg_id' => $data->msg_id, 'status' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => new \stdClass()]));
                        }
                    }
                    break;
                case 'read_report':
                    if (empty($_SESSION[$client_id]) || $_SESSION[$client_id] < (time() - 120)) {
                        Gateway::closeClient($client_id);
                    } else {
                        try {
                            $sendResult = self::curlPost($domain . '/v20/public/index.php/im/User/readReport?cur_env=internal', $message);
                            if ($sendResult['status'] == 1000) {
                                //发送成功
                                $toUser = $sendResult['data']['to_user'];
                                $toUser && $data->target_id =  $toUser;
                                $sendResult['msg_id'] = $data->msg_id;
                                Gateway::sendToUid($data->from_id, json_encode($sendResult));
                                Gateway::sendToUid($data->target_id, json_encode($data));
                            } else {
                                //发送失败
                                throw new Exception($sendResult['msg'], $sendResult['status']);
                            }
                        } catch (\Exception $e) {
                            Gateway::sendToUid($data->from_id, json_encode(['msg_id' => $data->msg_id, 'status' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => new \stdClass()]));
                        }
                    }
                    break;
                default:
                    Gateway::sendToUid($data->from_id, json_encode(['msg_id' => $data->msg_id, 'status' => 1001, 'msg' => '发送类型不支持', 'data' => new \stdClass()]));
                    break;
            }

            //最后更新会话时间
            $_SESSION[$client_id] = time();
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送 
        GateWay::sendToAll("$client_id logout");
    }


    public static function curlPost($url, $data)
    {
        $result = Http::curlPostToken($url, $data);
        return $result;
    }
}

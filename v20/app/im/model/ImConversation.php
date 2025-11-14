<?php

declare(strict_types=1);

namespace app\im\model;

use Exception;
use think\facade\Db;
use think\Model;

/**
 * @mixin \think\Model
 */
class ImConversation extends Model
{

    const TYPE_SINGLE = 3;

    const TYPE_GROUP = 4;

    //
    public function getUnreadMsgCount($username, $relation = '')
    {
        $where = [
            'c.from_user' => $username,
            'c.is_del' => 0
        ];
        if ($relation) {
            $where['c.relation'] = $relation;
        }
        $count = $this->alias('c')
            ->join('im_user u', 'c.to_user = u.username')
            ->where($where)
            ->sum('unread_msg_count');
        return intval($count);
    }


    public function getChatLists($username, $page, $limit, $keyword = '')
    {
        $where = [
            ['c.from_user', '=', $username],
            ['c.is_del', '=', 0],
        ];
        if ($keyword) {
            $where[] = ['u.nickname', 'like', '%' . $keyword . '%'];
        }
        $chatRecord = new ImChatRecord();
        $list = $this->alias('c')
            ->join('im_user u', 'c.to_user = u.username')
            ->field('u.username,u.avatar,u.nickname,u.avatar,c.id,c.last_msg_time,c.last_msg,c.unread_msg_count,c.chat_id')
            ->where($where)
            ->order('c.status desc,c.last_msg_time desc')
            ->page($page, $limit)
            ->select()
            ->each(function ($item) use ($chatRecord) {
                $item['avatar'] = $item['avatar'] ? replace_file_domain($item['avatar']) : cfg('site_url') . '/static/im/icon/user_icon.png';
                $item['last_time'] = $item['last_msg_time'];
                $item['last_msg_time'] = date('m-d H:i', $item['last_msg_time']);
            });

        return $list;
    }

    public function delChat($username, $id)
    {
        $this->where('id', $id)->where('from_user', $username)->update(['is_del' => 1, 'status' => 0]);
        (new MerchantStoreKefu())->where('username', $username)->where('cur_receive', '>', 0)->dec('cur_receive')->update();
        return true;
    }


    /**
     * 获取两个账号之间的关系
     */
    public function getRelationStr($username1, $username2)
    {
        $split1 = explode('_', $username1);
        $split2 = explode('_', $username2);

        return $split1[0] . '2' . $split2[0];
    }

    public function build($from, $to, $type)
    {
        $tm = time();
        $imUserMod = new ImUser();
        $chatRecordMod = new ImChatRecord();
        list($fprefix, $fuid) = $imUserMod->getUidAndRole($from);
        list($tprefix, $tuid) = $imUserMod->getUidAndRole($to);
        $fuuid = $imUserMod->getUUIDByUsername($from);
        $tuuid = $imUserMod->getUUIDByUsername($to);

        if ($fuuid == $tuuid) {
            throw new Exception('非法参数');
        }

        $hasConversation = $this->where(['from_uuid' => $fuuid, 'to_uuid' => $tuuid])->find();
        if (!$hasConversation) {
            //没有则创建会话
            $data = [
                [
                    'chat_id' => $chatRecordMod->buildChatIndexKey($from, $to),
                    'from_user' => $from,
                    'to_user' => $to,
                    'from_uuid' => $fuuid,
                    'to_uuid' => $tuuid,
                    'type' => $type,
                    'relation' => $this->getRelationStr($from, $to),
                    'create_time' => $tm,
                ],
                [
                    'chat_id' => $chatRecordMod->buildChatIndexKey($to, $from),
                    'from_user' => $to,
                    'to_user' => $from,
                    'from_uuid' => $tuuid,
                    'to_uuid' => $fuuid,
                    'type' => $type,
                    'relation' => $this->getRelationStr($to, $from),
                    'create_time' => $tm,
                ]
            ];
            return $this->insertAll($data);
        } else {
            if ($hasConversation->from_user == $from && $hasConversation->to_user == $to) {

                //当前对接客服是同一个，若会话标记为删除，则恢复，否则退出
                $update = ['status' => 1, 'is_del' => 0];
                $this->where('id', $hasConversation->id)->update($update);
                $this->where(['from_uuid' => $tuuid, 'to_uuid' => $fuuid])->save($update);

                //如果客服也删除了会话，则恢复会话同时连接数+1
                $kf = '';
                if (in_array($fprefix, [$imUserMod::PREFIX_PLAT, $imUserMod::PREFIX_STORE])) {
                    $kf = $hasConversation['from_user'];
                } else if (in_array($tprefix, [$imUserMod::PREFIX_PLAT, $imUserMod::PREFIX_STORE])) {
                    $kf = $hasConversation['to_user'];
                }

                //修正客服连接数
                if ($kf) {
                    $count = $this->where(['from_user' => $kf, 'status' => 1])->count();
                    (new MerchantStoreKefu())->where(['username' => $kf])->update(['cur_receive' => intval($count)]);
                }
                return;
            } else {
                //不是同一个客服，则更新当前对接客服信息
                $this->where(['id' => $hasConversation['id']])
                    ->update(['from_uuid' => $fuuid, 'from_user' => $from, 'to_uuid' => $tuuid, 'to_user' => $to, 'status' => 1]);
                $this->where(['from_uuid' => $tuuid, 'to_uuid' => $fuuid])
                    ->update(['from_uuid' => $tuuid, 'from_user' => $to, 'to_uuid' => $fuuid, 'to_user' => $from, 'status' => 1]);
                return true;
            }
        }
    }


    //检查客服/用户回复超时
    public function checkReplyTimeout($username, $chatId = '')
    {
        $tm = time();
        $earlyTwoMins = 120;

        $where = [
            ['c.status', '=', 1],
            ['c.from_user', '=', $username],
            ['c.next_check_timeout', '<', $tm]
        ];
        !empty($chatId) && $where[] = ['c.chat_id', '=', $chatId];

        $chatLists = $this->alias('c')->join('im_chat_record r', 'c.last_msg_id = r.msg_id')
            ->where($where)
            ->field('c.id,c.from_user,c.from_uuid,c.to_user,c.to_uuid,c.last_msg_time,c.last_msg_id,c.chat_id,r.from_id,r.target_id,r.index_key')
            ->order('c.last_msg_time asc')
            ->select()
            ->toArray();

        if (empty($chatLists)) {
            return [];
        }

      
        $imUserMod = new ImUser();
        $messages = [];

        foreach ($chatLists as $chat) {
            list($lastMsgRole,) = $imUserMod->getUidAndRole($chat['from_id']);  //最后一条消息发送人
            list($fRole, $fuid) = $imUserMod->getUidAndRole($chat['from_user']); //当前会话的发送人
            list($tRole, $tuid) = $imUserMod->getUidAndRole($chat['to_user']);  //当前会话的接收人

            $config = [
                'customer_unrespon_switch' => 1,
                'customer_unrespon_min' => 3,
                'customer_unrespon_tips' => '您好，由于我们长时间没有收到您的回复，稍后将自动结束本次对话。',
                'customer_unreceived_switch' => 1,
                'customer_unreceived_min' => 30,
                'customer_unreceived_tips' => '由于长时间没有回复客户的咨询，稍后系统将自动结束会话。'
            ];
            $kfUsername = '';  //客服用户名
            $chatUsername = '';  //会话用户名
            if (in_array($fRole, ['store', 'plat'])) {
                $config = Db::name('store_kefu_params')->where('store_id', $fuid)->where('belong', $fRole)->find();
                $kfUsername = $chat['from_user'];
                $chatUsername = $chat['to_user'];
            } else if (in_array($tRole, ['store', 'plat'])) {
                $config = Db::name('store_kefu_params')->where('store_id', $tuid)->where('belong', $tRole)->find();
                $kfUsername = $chat['to_user'];
                $chatUsername = $chat['from_user'];
            }

            //当前用户是否是客服
            $loginUserIsKefu = in_array($fRole, ['store', 'plat']);
            if (in_array($lastMsgRole, ['store', 'plat'])) {
                //最后一条消息是客服发送，判断客户响应超时
                if ($config['customer_unrespon_switch'] == 1) {
                    if (!$loginUserIsKefu) {
                        if ($tm >= $chat['last_msg_time'] + $config['customer_unrespon_min'] * 60) {
                            //断开会话,减去客服连接数
                            $this->whereOr([[['from_user', '=', $username], ['to_user', '=', $chat['to_user']]], [['from_user', '=', $chat['to_user']], ['to_user', '=', $username]]])->update(['status' => 0]);
                            $this->updateKfCurReceive($kfUsername);
                            $messages[] = [
                                'msg_type' => 'system_notify',
                                'chat_id' => $chat['chat_id'],
                                'from_id' => $kfUsername,
                                'target_id' => $chatUsername,
                                'msg_body' => [
                                    'text' => '系统结束会话'
                                ]
                            ];
                            $this->where('id', $chat['id'])->update(['next_check_timeout' => $tm]);
                        } else if ($tm >= $chat['last_msg_time'] + $config['customer_unrespon_min'] * 60 - $earlyTwoMins) {
                            $messages[] = [
                                'msg_type' => 'system_notify',
                                'chat_id' => $chat['chat_id'],
                                'from_id' => $kfUsername,
                                'target_id' => $chatUsername,
                                'msg_body' => [
                                    'text' => $config['customer_unrespon_tips']
                                ]
                            ];
                            $this->where('id', $chat['id'])->update(['next_check_timeout' => $tm + $earlyTwoMins + 10]);
                        }
                    }
                }
            } else if (in_array($lastMsgRole, ['user'])) {
                //最后一条消息是客户发送，判断客服回复超时
                if ($config['customer_unreceived_switch'] == 1 && $loginUserIsKefu) {
                    if ($loginUserIsKefu) {
                        //如果当前用户是客服，那应该客服自己收到
                        if ($tm >= $chat['last_msg_time'] + $config['customer_unreceived_min'] * 60) {
                            //断开会话,减去客服连接数
                            $this->whereOr([[['from_user', '=', $username], ['to_user', '=', $chat['to_user']]], [['from_user', '=', $chat['to_user']], ['to_user', '=', $username]]])->update(['status' => 0]);
                            $this->updateKfCurReceive($kfUsername);
                            $messages[] = [
                                'msg_type' => 'system_notify',
                                'chat_id' => $chat['chat_id'],
                                'from_id' => $chatUsername,
                                'target_id' => $kfUsername,
                                'msg_body' => [
                                    'text' => '系统结束会话'
                                ]
                            ];
                            $this->where('id', $chat['id'])->update(['next_check_timeout' => $tm]);
                        } else if ($tm >= $chat['last_msg_time'] + $config['customer_unreceived_min'] * 60 - $earlyTwoMins) {
                            $messages[] = [
                                'msg_type' => 'system_notify',
                                'chat_id' => $chat['chat_id'],
                                'from_id' => $chatUsername,
                                'target_id' => $kfUsername,
                                'msg_body' => [
                                    'text' => $config['customer_unreceived_tips']
                                ]
                            ];
                            $this->where('id', $chat['id'])->update(['next_check_timeout' => $tm + $earlyTwoMins + 10]);
                        }
                    }
                }
            }
        }
        return $messages;
    }

    //更新客服连接数
    public function updateKfCurReceive($kf)
    {
        $count = $this->where(['from_user' => $kf, 'status' => 1])->count();
        (new MerchantStoreKefu())->where(['username' => $kf])->update(['cur_receive' => intval($count)]);
    }
}

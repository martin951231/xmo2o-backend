<?php

declare(strict_types=1);

namespace app\im\model;

use Exception;
use think\facade\Db;
use think\Model;

/**
 * @mixin \think\Model
 */
class MerchantStoreKefu extends Model
{
    //

    public function assignKefu($fromUser, $toUser, $type, $body)
    {
        $rs = ['code' => 0, 'errorMsg' => ''];
        $imUserMod = new ImUser();
        $conversationMod = new ImConversation();
        list($fPrefix, $uid) =   $imUserMod->getUidAndRole($fromUser);
        list($tPrefix, $storeId) = $imUserMod->getUidAndRole($toUser);
        $fromUUID = $fPrefix . '_' . $uid;
        $toUUID = $tPrefix . '_' . $storeId;

        $findOnlineKf = $this->where(['store_id' => $storeId, 'status' => 1])->find();
        if (empty($findOnlineKf)) {
            //没有客服在线
            $rs['code'] = 1001;
            $offlineTip = Db::name('store_kefu_params')->where(['store_id' => $storeId])->value('off_duty_notice');
            $rs['errorMsg'] = $offlineTip ?: '你好，当前客服不在线，请稍后重试。';
        } else {
            //按照优先分配给上次聊天客服，其次按照饱和度分配
            $conversation = $conversationMod->where(['from_uuid' => $fromUUID, 'to_uuid' => $toUUID])->find();
            $assignKefu = '';
            $increReceiveNum = true; //是否增加接待人数
            if ($conversation) {
                if($conversation->status == 1 && $conversation->is_del == 0){
                    $assignKefu = $conversation->to_user;
                    if (empty($this->where(['username' => $assignKefu])->find())) {  //客服被删除了
                        $assignKefu = '';
                    }
                }else{
                    $assignKefu = $this->lastFirstAssign($conversation->to_user);
                }
            }
            if (empty($assignKefu)) {
                $assignKefu = $this->saturationAssign($uid, $storeId);
            }
            if ($conversation && $conversation->status == 1 && $conversation->is_del == 0 && $conversation->to_user == $assignKefu) {
                $increReceiveNum = false;
            }

            if (empty($assignKefu)) {
                //TODO 未分配到客服则丢到待分配队列
                $rs['code'] = 1002;
                $rs['errorMsg'] = '您好，当前客服正在繁忙中，请稍后重试。';
            } else {
                $rs['retval'] = array('username' => $assignKefu);
                if ($increReceiveNum) {
                    //增加客服接待数
                    $rs['retval']['welcome'] = $this->getWelcome($assignKefu);
                    $this->where(['username' => $assignKefu])->inc('cur_receive');
                }
            }
        }
        return $rs;
    }

    /**
     * 上次客服优先分配
     */
    public function lastFirstAssign($priorityUser)
    {
        $kefu = $this->where(['username' => $priorityUser, 'status' => 1])->field('max_receive,cur_receive')->find();
        if ($kefu && $kefu->max_receive > $kefu->cur_receive) {
            return $priorityUser;
        } else {
            return '';
        }
    }

    /**
     * 饱和度分配
     */
    public function saturationAssign($uid, $storeId)
    {
        $kefu = $this->where("`store_id` = $storeId AND status=1 AND `max_receive` > `cur_receive`")->field('username,(cur_receive / max_receive) AS baohedu')->order('baohedu ASC')->find();
        return $kefu ? $kefu->username : '';
    }

    /**
     * 获取欢迎语，优先级：客服 > 店铺
     * @param $username
     * @return string
     */
    public function getWelcome($username)
    {
        list($prefix, $storeId) = (new ImUser())->getUidAndRole($username);
        $kf = $this->where(['username' => $username])->field('welcome,name,username')->find();
        if (empty($kf)) {
            return '';
        }
        if ($kf->welcome) {
            return $kf->welcome;
        }
        $welecome = Db::name('store_kefu_params')->where(['store_id' => $storeId])->value('welcome');
        if ($welecome) {
            return str_replace('{nickname}', $kf['name'], $welecome);
        }
        return '你好，欢迎光临。';
    }


    public function getOnlineKfListsByStoreId($uid, $role, $except = '')
    {
        $where = [
            ['kf.store_id', '=', $uid],
            ['kf.belong', '=', $role],
            ['kf.status', '=', 1]
        ];


        if ($except) {
            $where[] = ['kf.username', '<>', $except];
        }
        if ($uid > 0) {
            //店铺客服
            $kflists = $this->alias('kf')
                ->join('merchant_store s', 's.store_id = kf.store_id')
                ->where($where)
                ->field('kf.`name`,kf.username,kf.first_letter,s.logo AS avatar')
                ->order('first_letter ASC')
                ->select();
        } else if ($uid == 0) {
            //平台客服
            $avatar = cfg('wechat_share_img');
            $kflists = $this->alias('kf')
                ->where($where)
                ->field('kf.`name`,kf.username,kf.first_letter,"' . $avatar . '" AS avatar')
                ->order('first_letter ASC')
                ->select();
        }

        $rs = [];
        foreach ($kflists as $v) {
            if (!isset($rs[$v['first_letter']])) {
                $rs[$v['first_letter']] = array('first_letter' => $v['first_letter'], 'kf_lists' => []);
            }
            $v['avatar'] = replace_file_domain($v['avatar']);
            $rs[$v['first_letter']]['kf_lists'][] = $v;
        }
        unset($kflists);
        return array_values($rs);
    }

    /**
     * 转接客服
     */
    public function transferKf($fKfUser, $tKfUser, $user)
    {
        $imUserMod = new ImUser();
        $conversationMod = new ImConversation();

        $fromKf = $this->where(array('username' => $fKfUser))->field('id,name,store_id,username')->find();
        $toKf = $this->where(array('username' => $tKfUser))->field('id,name,store_id,username')->find();
        $customer = $imUserMod->where(array('username' => $user))->field('id,username,nickname')->find();

        $rs = array('result' => true, 'msg' => '');
        try {
            if (empty($fromKf)) {
                throw new Exception('被转接客服不存在');
            }
            if (empty($toKf)) {
                throw new Exception('转接客服不存在');
            }
            if (empty($customer)) {
                throw new Exception('转接客户不存在');
            }
            if ($fromKf->store_id != $toKf->store_id) {
                throw new Exception('不能跨店铺转接客服');
            }
            $fuuid = $imUserMod->getUUIDByUsername($fKfUser);
            $tuuid = $imUserMod->getUUIDByUsername($user);
            $conversation = $conversationMod->where(array('from_uuid' => $fuuid, 'to_uuid' => $tuuid, 'status' => 1))->find();
            if (empty($conversation)) {
                throw new Exception('会话不存在或者已断开，禁止转接');
            }

            //更新当前接入的店铺客服
            $conversationMod->build($tKfUser, $user, $conversationMod::TYPE_SINGLE);
            $this->where(array('username' => $tKfUser))->inc('cur_receive')->update();
            $this->where('username', $fKfUser)->where('cur_receive', '>', 0)->dec('cur_receive')->update();
            $rs['msg'] = sprintf("%s客服转接%s客户给您", $fromKf['name'], $customer['nickname']);
        } catch (Exception $e) {
            $rs['result'] = false;
            $rs['msg'] = $e->getMessage();
        }
        return $rs;
    }
}

<?php

namespace app\group\model\service\appoint;

use app\group\model\db\Group;
use app\group\model\db\GroupAppoint;
use app\group\model\db\GroupAppointGift;
use app\merchant\model\db\MerchantStore;
use app\merchant\model\service\MerchantStoreService;
use think\Exception;

class GroupAppointService
{
    /**
     * @param $mer_id
     * @param $page
     * @param $pageSize
     * @return mixed
     * 团购预约礼店铺列表
     */
    public function getStoreAppointGiftList($mer_id, $page, $pageSize)
    {
        $where = [['b.mer_id', '=', $mer_id]];
        $list = (new MerchantStore())->getAppointGiftList($where, $page, $pageSize);
        return $list;
    }

    /**
     * @param $store_id
     * @return mixed
     * 店铺预约礼信息
     */
    public function getAppointGiftMsg($store_id)
    {
        $where = [['b.store_id', '=', $store_id]];
        $list = (new MerchantStore())->getAppointGiftMsg($where);
        if (empty($list['list']['gift'])) {
            $list['list']['gift1'] = "";
            $list['list']['gift2'] = "";
            $list['list']['gift3'] = "";
        } else {
            $arr = explode(",", $list['list']['gift']);
            for ($i = 0; $i < 3; $i++) {
                if (isset($arr[$i])) {
                    $list['list']['gift' . ($i + 1)] = $arr[$i];
                } else {
                    $list['list']['gift' . ($i + 1)] = "";
                }
            }
        }
        return $list;
    }

    /**
     * @param $stroeId
     * @param $merId
     * @param $gift1
     * @param $gift2
     * @param $gift3
     * @return mixed
     * 编辑店铺预约礼
     */
    public function updateAppointGift($stroeId, $merId, $gift1, $gift2, $gift3)
    {
        $where = [['store_id', '=', $stroeId], ['mer_id', '=', $merId]];
        $gift = [];
        if (!empty($gift1)) {
            $gift[] = $gift1;
        }
        if (!empty($gift2)) {
            $gift[] = $gift2;
        }
        if (!empty($gift3)) {
            $gift[] = $gift3;
        }
        $data['gift'] = implode(",", $gift);
        $data['store_id'] = $stroeId;
        $data['mer_id'] = $merId;
        $msg = (new GroupAppointGift())->getOne($where);
        if (empty($msg)) {
            $ret = (new GroupAppointGift())->add($data);
        } else {
            $ret = (new GroupAppointGift())->updateThis($where, $data);
        }
        return $ret;
    }

    /**
     * @param $merId
     * @param $page
     * @param $pageSize
     * @return mixed
     * 预约到店列表
     */
    public function getAppointArriveList($merId, $page, $pageSize, $store_id, $status, $phone)
    {
        $where1 = [['mer_id', '=', $merId]];
        $store_list = (new MerchantStore())->getSome($where1)->toArray();
        if(empty($store_list)){
            return [];
        }else{
            $where=array();
            $storeList = array();
            $store_ids=array();
            foreach ($store_list as $key => $val) {
                $assign['store_id'] = $val['store_id'];
                $store_ids[]=$val['store_id'];
                $assign['name'] = $val['name'];
                $storeList[] = $assign;
            }

        if(!empty($store_ids)){
            $where = [['g.store_id', 'in', $store_ids]];
        }
        $field = "g.*,s.name as store_name,u.nickname as user_name";
        $order = "g.id desc";
        if ($status!="") {
            array_push($where, ['g.status', '=', $status*1]);
        }
        if (!empty($store_id)) {
            array_push($where, ['g.store_id', '=', $store_id]);
        }
        if (!empty($phone)) {
            array_push($where, ['g.phone|u.nickname', '=', $phone]);
        }
        $list = (new GroupAppoint())->getAppointArriveList($where, $field, $order, $page, $pageSize);
        if (!empty($list['list'])) {
            foreach ($list['list'] as $k => $v) {
                if (!empty($v['appoint_time'])) {
                    $list['list'][$k]['appoint_time'] = date('Y-m-d H:i:s', $v['appoint_time']);
                }
                if (!empty($v['arrive_time'])) {
                    $list['list'][$k]['arrive_time'] = date('Y-m-d H:i:s', $v['arrive_time']);
                }
            }
        }
        $list['store_list'] = $storeList;
        return $list;
        }
    }

    /**
     * @param $where
     * @param $data
     * @return mixed
     * 更新到店预约状态
     */
    public function updateAppointArriveStatus($where, $data)
    {
        $ret = (new GroupAppoint())->updateThis($where, $data);
        return $ret;
    }

    /**
     * @param $data
     * @return mixed
     * 预约礼添加
     */
    public function addAppointArrive($data)
    {
       return (new GroupAppoint())->add($data);
    }

    /**
     * @param $where
     * @param $data
     * @return mixed
     * 课程预约提交
     */
    public function appointCourse($param)
    {
        $storeId = $param['store_id'] ?? 0;
        $groupId = $param['group_id'] ?? 0;
        $phone = $param['phone'] ?? '';

        $user = request()->user;
        if(empty($user)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        if(empty($groupId)){
            throw new \think\Exception(L_('缺少参数'),1003);
        }

        // 查询预约过吗
        $where = [
            ['store_id', '=', $storeId],
            ['group_id', '=', $groupId],
            ['uid', '=', $user['uid']],
            ['status', 'in', '0,1'],
        ];
        $res = (new GroupAppoint())->where($where)->find();
        if($res){
            throw new \think\Exception(L_('您已预约过'),1003);
        }

        $data = [
            'mer_id'=>(new MerchantStore())->getIdsByWhere(['store_id'=>$storeId],'mer_id')[0],
            'group_id' => $groupId,
            'appoint_content'=>"商品预约：".(new Group())->getGroupName(['group_id'=>$groupId],'s_name'),
            'store_id' => $storeId,
            'uid' => $user['uid'],
            'appoint_time' => time(),
            'appoint_type' => 1
        ];
        if(strpos($phone,'*') || empty($phone)){
            $data['phone'] = $user['phone'];
        }else{
            $data['phone'] = $phone;
        }
        $res = $this->addAppointArrive($data);
        if(empty($res)){
            throw new \think\Exception(L_('预约失败'),1003);
        }

        return true;
    }

    /**
     * 获取店铺预约礼配置
     * @author: 张涛
     * @date: 2021/05/22
     */
    public function getStoreAppointGift($storeId)
    {
        return (new GroupAppointGift())->where('store_id', $storeId)->findOrEmpty()->toArray();
    }

    /**
     * 预约礼提交
     * @param $storeId
     * @param $phone
     * @author: 张涛
     * @date: 2021/05/28
     */
    public function appointSubmit($uid, $storeId, $phone)
    {
        //查询店铺
        $storeInfo = (new MerchantStoreService())->getStoreByStoreId($storeId);
        if (empty($storeInfo)) {
            throw new Exception(L_('店铺不存在'), 1003);
        }
        // 查询预约过吗
        $where = [
            ['store_id', '=', $storeId],
            ['group_id', '=', 0],
            ['uid', '=', $uid],
            ['status', 'in', '0,1'],
        ];
        $res = (new GroupAppoint())->where($where)->find();
        if ($res) {
            throw new Exception(L_('您已预约过'), 1003);
        }

        $data = [
            'group_id' => 0,
            'store_id' => $storeId,
            'mer_id' => $storeInfo['mer_id'],
            'uid' => $uid,
            'appoint_content' => '到店预约',
            'appoint_time' => time(),
            'appoint_type' => 0,
            'phone' => $phone
        ];
        $res = $this->addAppointArrive($data);
        if (empty($res)) {
            throw new Exception(L_('预约失败'), 1002);
        }
        return true;
    }
}
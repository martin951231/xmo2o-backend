<?php

/**
 * 问答service
 */

namespace app\warn\model\service;

use app\common\model\db\StockWarn;
use app\common\model\db\WarnList;
use app\warn\model\db\WarnNotice;
use app\warn\model\db\WarnUser;
use think\facade\Db;

class WarnService
{
    public function getList($params)
    {
        $limit = $params['pageSize'];
        $data = (new WarnUser())->where(['is_del'=>0,'mer_id'=>$params['mer_id']])->order('pigcms_id desc')->paginate($limit)->toArray();
        foreach($data['data'] as &$v){
            $business = '';
            if($v['is_warn_mall']){
                $business = '新版商城';
            }
            if($v['is_warn_shop']){
                $business = $business ? $business.'、' : '';
                $business = $business . '外卖';
            }
            if($v['is_warn_group']){
                $business = $business ? $business.'、' : '';
                $business = $business . '团购';
            }
            if($v['is_warn_scenic']){
                $business = $business ? $business.'、' : '';
                $business = $business . '新版景区';
            }
            $workTime = $v['work_time'] ? json_decode($v['work_time'],true) : '';
            $workTimeMsg = '';
            if($workTime){
                foreach ($workTime as $work){
                    if($work['is_work']){
                        $workTimeMsg = $workTimeMsg ? $workTimeMsg.'、'.$this->getWeekMsg($work['week']) : $this->getWeekMsg($work['week']);
                    }
                }
            }
            $v['business'] = $business;//业务
            $v['work_time'] = $workTimeMsg;//工作时间
        }
        return $data;
    }
    public function getWeekMsg($num)
    {
        switch ($num){
            case 1:
                return '周一';
            case 2:
                return '周二';
            case 3:
                return '周三';
            case 4:
                return '周四';
            case 5:
                return '周五';
            case 6:
                return '周六';
            case 0:
                return '周日';
        }
    }
    
    public function addUser($params)
    {
        if(!$params['name']){
            throw new \think\Exception(L_("姓名不能为空"), 1003);
        }
        if(!$params['phone']){
            throw new \think\Exception(L_("手机号不能为空"), 1003);
        }
        if(!$params['business']){
            throw new \think\Exception(L_("业务不能为空"), 1003);
        }
        if(!preg_match('/^[0-9]{11}$/', $params['phone'])){
            throw new \think\Exception(L_("请填写正确的手机号"), 1003);
        }
        //避免重复的手机号
        $user = (new WarnUser())->where(['is_del'=>0,'phone'=>$params['phone'],'mer_id'=>$params['mer_id']])->find();
        if($user){
            throw new \think\Exception(L_("手机号已被使用"), 1003);
        }
        if($params['work_time_arr']){
            foreach ($params['work_time_arr'] as $v){
                $params['work_time'][] = [
                    'week'=>$v,
                    'is_work'=>1
                ];
            }
            $params['work_time'] = json_encode($params['work_time']);
        }else{
            $params['work_time'] = '';
        }
        $params['is_warn_mall'] = 0;
        $params['is_warn_shop'] = 0;
        $params['is_warn_group'] = 0;
        $params['is_warn_scenic'] = 0;
        if($params['business']){
            foreach ($params['business'] as $vv){
                if($vv == 'mall'){
                    $params['is_warn_mall'] = 1;
                }
                if($vv == 'shop'){
                    $params['is_warn_shop'] = 1;
                }
                if($vv == 'group'){
                    $params['is_warn_group'] = 1;
                }
                if($vv == 'scenic'){
                    $params['is_warn_scenic'] = 1;
                }
            }
        }
        $add = (new WarnUser())->insert([
            'mer_id'=>$params['mer_id'],
            'name'=>$params['name'],
            'phone'=>$params['phone'],
            'is_warn_mall'=>$params['is_warn_mall'],
            'is_warn_shop'=>$params['is_warn_shop'],
            'is_warn_group'=>$params['is_warn_group'],
            'is_warn_scenic'=>$params['is_warn_scenic'],
            'work_time'=>$params['work_time'],
            'status'=>$params['status'],
            'create_time'=>time()
        ]);
        if(!$add){
            throw new \think\Exception(L_("新建运营失败"), 1003);
        }
        return true;
    }
    
    public function editUser($params)
    {
        if(!$params['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1003);
        }
        if(!$params['name']){
            throw new \think\Exception(L_("姓名不能为空"), 1003);
        }
        if(!$params['phone']){
            throw new \think\Exception(L_("手机号不能为空"), 1003);
        }
        if(!$params['business']){
            throw new \think\Exception(L_("业务不能为空"), 1003);
        }
        if(!preg_match('/^[0-9]{11}$/', $params['phone'])){
            throw new \think\Exception(L_("请填写正确的手机号"), 1003);
        }
        //查询运营信息
        $warnUserInfo = (new WarnUser())->where(['pigcms_id'=>$params['id'],'is_del'=>0])->find();
        if(!$warnUserInfo){
            throw new \think\Exception(L_("操作对象不存在,请刷新后重试"), 1003);
        }
        //避免重复的手机号
        $user = (new WarnUser())->where([['is_del','=',0],['phone','=',$params['phone']],['mer_id','=',$warnUserInfo['mer_id']],['pigcms_id','<>',$warnUserInfo['pigcms_id']]])->find();
        if($user){
            throw new \think\Exception(L_("手机号已被使用"), 1003);
        }
        if($params['work_time_arr']){
            foreach ($params['work_time_arr'] as $v){
                $params['work_time'][] = [
                    'week'=>$v,
                    'is_work'=>1
                ];
            }
            $params['work_time'] = json_encode($params['work_time']);
        }else{
            $params['work_time'] = '';
        }
        $params['is_warn_mall'] = 0;
        $params['is_warn_shop'] = 0;
        $params['is_warn_group'] = 0;
        $params['is_warn_scenic'] = 0;
        if($params['business']){
            foreach ($params['business'] as $vv){
                if($vv == 'mall'){
                    $params['is_warn_mall'] = 1;
                }
                if($vv == 'shop'){
                    $params['is_warn_shop'] = 1;
                }
                if($vv == 'group'){
                    $params['is_warn_group'] = 1;
                }
                if($vv == 'scenic'){
                    $params['is_warn_scenic'] = 1;
                }
            }
        }
        if($params['name'] == $warnUserInfo['name'] && $params['phone'] == $warnUserInfo['phone'] && $params['is_warn_mall'] == $warnUserInfo['is_warn_mall'] && $params['is_warn_shop'] == $warnUserInfo['is_warn_shop'] && $params['is_warn_group'] == $warnUserInfo['is_warn_group'] && $params['is_warn_scenic'] == $warnUserInfo['is_warn_scenic'] && $params['work_time'] == $warnUserInfo['work_time'] && $params['status'] == $warnUserInfo['status']){
            return true;
        }
        //编辑信息
        $update = (new WarnUser())->where(['pigcms_id'=>$params['id']])->update([
            'name'=>$params['name'],
            'phone'=>$params['phone'],
            'is_warn_mall'=>$params['is_warn_mall'],
            'is_warn_shop'=>$params['is_warn_shop'],
            'is_warn_group'=>$params['is_warn_group'],
            'is_warn_scenic'=>$params['is_warn_scenic'],
            'work_time'=>$params['work_time'],
            'status'=>$params['status'],
            'update_time'=>time()
        ]);
        if(!$update){
            throw new \think\Exception(L_("操作失败"), 1003);
        }
        return true;
    }
    
    public function delUser($params)
    {
        if(!$params['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1003);
        }
        $del = (new WarnUser())->where(['pigcms_id'=>$params['id']])->update([
            'is_del'=>1,
            'update_time'=>time()
        ]);
        if(!$del){
            throw new \think\Exception(L_("操作失败"), 1003);
        }
        return true;
    }

    /**
     * 获取配置信息
     */
    public function getConfig($param){
        $mer_id = $param['mer_id']??0;
        //获取提醒信息
        $where[] = ['mer_id','=',$mer_id];
        $warn_list = (new StockWarn())->where($where)->select();
        $warn_info = [
            'mall' => [
                'pigcms_id' => 0,
                'min_num' => 0,
                'apply_after_sales_time' => 0,
                'is_warn_only_once' => 0,
                'status' => 0
            ],
            'shop' => [
                'pigcms_id' => 0,
                'min_num' => 0,
                'apply_after_sales_time' => 0,
                'is_warn_only_once' => 0,
                'status' => 0
            ],
            'group' => [
                'pigcms_id' => 0,
                'min_num' => 0,
                'apply_after_sales_time' => 0,
                'is_warn_only_once' => 0,
                'status' => 0
            ],
            'appoint' => [
                'pigcms_id' => 0,
                'min_num' => 0,
                'apply_after_sales_time' => 0,
                'is_warn_only_once' => 0,
                'status' => 0
            ],
            'scenic' => [
                'pigcms_id' => 0,
                'min_num' => 0,
                'apply_after_sales_time' => 0,
                'is_warn_only_once' => 0,
                'status' => 0
            ],
        ];
        foreach ($warn_list as $item){
            if(isset($warn_info[$item['business']])){
                $warn_info[$item['business']] = [
                    'pigcms_id' => $item['pigcms_id'],
                    'min_num' => $item['min_num'],
                    'apply_after_sales_time' => $item['apply_after_sales_time'],
                    'is_warn_only_once' => $item['is_warn_only_once'],
                    'status' => $item['status']
                ];
            }
        }
        return $warn_info;
    }

    /**
     * 保存配置信息
     */
    public function saveConfig($param){
        $mer_id = $param['mer_id'];
        $mall_info = $param['mall']??[];
        $shop_info = $param['shop']??[];
        $group_info = $param['group']??[];
        $appoint_info = $param['appoint']??[];
        $scenic_info = $param['scenic']??[];
        $saveData = $editData = [];
        //获取已保存信息
        $tock_warn = (new StockWarn())->where(['mer_id'=>$mer_id])->select()->toArray();
        $tock_warn_arr = [];
        foreach ($tock_warn as $item){
            $tock_warn_arr[$item['business']] = $item;
        }
        if(isset($tock_warn_arr['mall'])){
            $editData[$tock_warn_arr['mall']['pigcms_id']] = $this->queryParam($mall_info,$mer_id,'mall',1);
        }else{
            $saveData[] = $this->queryParam($mall_info,$mer_id,'mall',0);
        }

        if(isset($tock_warn_arr['shop'])){
            $editData[$tock_warn_arr['shop']['pigcms_id']] = $this->queryParam($shop_info,$mer_id,'shop',1);
        }else{
            $saveData[] = $this->queryParam($shop_info,$mer_id,'shop',0);
        }

        if(isset($tock_warn_arr['group'])){
            $editData[$tock_warn_arr['group']['pigcms_id']] = $this->queryParam($group_info,$mer_id,'group',1);
        }else{
            $saveData[] = $this->queryParam($group_info,$mer_id,'group',0);
        }

        if(isset($tock_warn_arr['appoint'])){
            $editData[$tock_warn_arr['appoint']['pigcms_id']] = $this->queryParam($appoint_info,$mer_id,'appoint',1);
        }else{
            $saveData[] = $this->queryParam($appoint_info,$mer_id,'appoint',0);
        }

        if(isset($tock_warn_arr['scenic'])){
            $editData[$tock_warn_arr['scenic']['pigcms_id']] = $this->queryParam($scenic_info,$mer_id,'scenic',1);
        }else{
            $saveData[] = $this->queryParam($scenic_info,$mer_id,'scenic',0);
        }
        
        if ($saveData||$editData) {
            //启动事务
            Db::startTrans();
            try {
                //写入数据表
                if($saveData){
                    (new StockWarn())->saveAll($saveData);
                }
               
                foreach ($editData as $key=>$item){
                    (new StockWarn())->where(['pigcms_id'=>$key])->save($item);
                }
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                throw new \think\Exception(L_($e->getMessage()));
            }
        }
        return true;
    }
    
    public function queryParam($info,$mer_id,$business,$type=0){
        if($type==1){
            $data = [
                'min_num' => $info['min_num']??0,
                'apply_after_sales_time' => $info['apply_after_sales_time']??0,
                'is_warn_only_once' => $info['is_warn_only_once']??0,
                'update_time' => time(),
                'status' => $info['status']??0,
            ];
        }else{
            $data = [
                'mer_id' => $mer_id,
                'business' => $business,
                'min_num' => $info['min_num']??0,
                'apply_after_sales_time' => $info['apply_after_sales_time']??0,
                'is_warn_only_once' => $info['is_warn_only_once']??0,
                'update_time' => time(),
                'status' => $info['status']??0,
            ];
        }
        return $data;
    }

    public function getUserDetail($param)
    {
        if(!$param['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1003);
        }
        $info = (new WarnUser())->where('pigcms_id',$param['id'])->find()->toArray();
        $info['business'] = [];
        if($info['is_warn_mall']){
            $info['business'][] = 'mall';
        }
        if($info['is_warn_shop']){
            $info['business'][] = 'shop';
        }
        if($info['is_warn_group']){
            $info['business'][] = 'group';
        }
        if($info['is_warn_scenic']){
            $info['business'][] = 'scenic';
        }
        //获取工作时间
        $workTime = $info['work_time'] ? json_decode($info['work_time'],true) : '';
        $info['work_times'] = [];
        if($workTime){
            foreach ($workTime as $work){
                if($work['is_work']){
                    $info['work_times'][] = $work['week'];
                }
            }
        }
        return $info;
    }
    
    public function read($param)
    {
        if(!$param['id'] && !$param['is_all']){//没有传id返回成功
            return true;
        } 
        if($param['is_all']){//全部已读
            $where = [
                'mer_id'=>$param['mer_id'],
                'is_read'=>0,
            ];
        }else{
            $where = [
                'mer_id'=>$param['mer_id'],
                'is_read'=>0,
                'pigcms_id'=>$param['id'],
            ];
        }
        (new WarnNotice())->where($where)->update([
            'is_read'=>1,
            'read_time'=>time()
        ]);
        return true;
    }

    /**
     * 消息提醒列表
     * @param $params
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getNoticeList($params)
    {
        $page = $params['page'] ?? 1;
        $page_size = $params['pageSize'] ?? 10;
        $mer_id = $params['mer_id'] ?? 0;
        $where = [];
        $where[] = ['mer_id', '=', $mer_id];
        //$where[] = ['business', 'in', ['mall', 'shop', 'group']];
        $notice_list = (new WarnNotice())->field('pigcms_id,title,content,create_time,business,is_read,type,goods_id,store_id,order_id')->where($where)->order('create_time desc,pigcms_id desc')->paginate($page_size)->toArray();
        $site_url = cfg('site_url');
        foreach ($notice_list['data'] as $k => $v) {
            $notice_list['data'][$k]['create_time'] = date('m-d', $v['create_time']);
            $h5Url = '';
            $pcUrl = '';
            if ($v['business'] == 'mall') {
                if ($v['type'] == 1) {
                    $h5Url = '/packapp/merchant/fastList.html?store_id=' . $v['store_id'] . '&shop_type=2&order_id=' . $v['order_id'];
                    $pcUrl = '/v20/public/platform/#/merchant/merchant.mall/orderList?store_id=' . $v['store_id'] . '&order_id=' . $v['order_id'];
                } else {
                    $h5Url = '/packapp/merchant/add_goods.html?goods_id=' . $v['goods_id'] . '&store_id=' . $v['store_id'];
                    $pcUrl = '/v20/public/platform/#/merchant/merchant.mall/editGoods?store_id=' . $v['store_id'] . '&goods_id=' . $v['goods_id'];
                }
            } elseif ($v['business'] == 'shop') {
                if ($v['type'] == 1) {
                    $h5Url = '/packapp/merchant/fastList.html?store_id=' . $v['store_id'] . '&shop_type=1&order_id=' . $v['order_id'];
                    $pcUrl = '/merchant.php?g=Merchant&c=Shop&a=order&store_id=' . $v['store_id'] . '&order_id=' . $v['order_id'];
                } else {
                    $h5Url = '/packapp/merchant/add_goods.html?goods_id=' . $v['goods_id'] . '&store_id=' . $v['store_id'];
                    $pcUrl = '/merchant.php?g=Merchant&c=Shop&a=goods_edit&goods_id=' . $v['goods_id'] . '&store_id=' . $v['store_id'];
                }
            } elseif ($v['business'] == 'group') {
                if ($v['type'] == 0) {
                    $pcUrl = '/v20/public/platform/#/merchant/merchant.group/goodsEdit?group_id=' . $v['goods_id'];
                }
            }elseif ($v['business'] == 'wechat_live_audit') {
                $pcUrl = '/v20/public/platform/#/merchant/liveBroadcast.merchant/baseSetting';
            }
            $notice_list['data'][$k]['h5Url'] = $h5Url ? ($site_url . $h5Url) : '';
            $notice_list['data'][$k]['pcUrl'] = $pcUrl ? ($site_url . $pcUrl) : '';
        }
        return $notice_list;
    }
    
    public function unreadNum($param)
    {
        $num = (new WarnNotice())->where(['mer_id'=>$param['mer_id'],'is_read'=>0])->count();
        //获取最新的三条信息
        $data = (new WarnNotice())->where(['mer_id'=>$param['mer_id'],'is_read'=>0])->field('pigcms_id,title,content,create_time,business,is_read,type,goods_id,store_id,order_id')->order('pigcms_id desc')->limit(3)->select();
        $site_url = cfg('site_url');
        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = date('m-d', $v['create_time']);
            $h5Url = '';
            $pcUrl = '';
            if ($v['business'] == 'mall') {
                if ($v['type'] == 1) {
                    $h5Url = '/packapp/merchant/fastList.html?store_id=' . $v['store_id'] . '&shop_type=2&order_id=' . $v['order_id'];
                    $pcUrl = '/v20/public/platform/#/merchant/merchant.mall/orderList?store_id=' . $v['store_id'] . '&order_id=' . $v['order_id'];
                } else {
                    $h5Url = '/packapp/merchant/add_goods.html?goods_id=' . $v['goods_id'] . '&store_id=' . $v['store_id'];
                    $pcUrl = '/v20/public/platform/#/merchant/merchant.mall/editGoods?store_id=' . $v['store_id'] . '&goods_id=' . $v['goods_id'];
                }
            } elseif ($v['business'] == 'shop') {
                if ($v['type'] == 1) {
                    $h5Url = '/packapp/merchant/fastList.html?store_id=' . $v['store_id'] . '&shop_type=1&order_id=' . $v['order_id'];
                    $pcUrl = '/merchant.php?g=Merchant&c=Shop&a=order&store_id=' . $v['store_id'] . '&order_id=' . $v['order_id'];
                } else {
                    $h5Url = '/packapp/merchant/add_goods.html?goods_id=' . $v['goods_id'] . '&store_id=' . $v['store_id'];
                    $pcUrl = '/merchant.php?g=Merchant&c=Shop&a=goods_edit&goods_id=' . $v['goods_id'] . '&store_id=' . $v['store_id'];
                }
            } elseif ($v['business'] == 'group') {
                if ($v['type'] == 0) {
                    $pcUrl = '/v20/public/platform/#/merchant/merchant.group/goodsEdit?group_id=' . $v['goods_id'];
                }
            }
            $data[$k]['h5Url'] = $h5Url ? ($site_url . $h5Url) : '';
            $data[$k]['pcUrl'] = $pcUrl ? ($site_url . $pcUrl) : '';
            unset($data[$k]['business'],$data[$k]['type'],$data[$k]['goods_id'],$data[$k]['store_id'],$data[$k]['order_id']);
        }
        return ['num'=>$num,'data'=>$data];
    }
}

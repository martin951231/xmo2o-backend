<?php
/**
 * 景区团体票订单绑定的用户信息
 */

namespace app\life_tools\model\service\group;

use app\life_tools\model\db\LifeToolsGroupOrder;
use app\life_tools\model\db\LifeToolsGroupOrderTourists;
use app\life_tools\model\db\LifeToolsGroupSetting;
use app\life_tools\model\db\LifeToolsOrder;

class LifeToolsGroupOrderTouristsService
{
    public $lifeToolsGroupOrderTouristsModel = null;
    public $lifeToolsGroupOrderModel = null;
    public $lifeToolsGroupSettingModel = null;

    public function __construct()
    {
        $this->lifeToolsGroupOrderTouristsModel = new LifeToolsGroupOrderTourists();
        $this->lifeToolsOrderModel = new LifeToolsOrder();
        $this->lifeToolsGroupOrderModel = new LifeToolsGroupOrder();
        $this->lifeToolsGroupSettingModel = new LifeToolsGroupSetting();
    }

    /**
     * 获取游客列表
     */
    public function getTouristsList($params)
    {
        $uid = $params['uid'] ?? 0;
        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $order = $this->lifeToolsOrderModel->where($condition)->find();
        if(!$order){
            throw new \think\Exception("订单不存在！");
        }
        if($order->uid != $uid){
            return [];
//            throw new \think\Exception("无权访问该订单！");
        }
        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $condition[] = ['is_del', '=', 0];
        if(!empty($params['group_tourist_search'])){
            $params['group_tourist_search']=str_replace("\\","_",json_encode($params['group_tourist_search']));
            $params['group_tourist_search'] = trim($params['group_tourist_search'],'"');
            $condition[] = ['tourists_custom_form', 'like', "%{$params['group_tourist_search']}%"];
        }
        $data = $this->lifeToolsGroupOrderTouristsModel
                    ->field('*,id tourists_id')
                    ->where($condition)
                    ->paginate($params['page_size']);
        return $data;
    }

    /**
     * 获取游客信息
     */
    public function getTouristsDetail($params)
    {
        if(empty($params['order_id'])){
            throw new \think\Exception('order_id不能为空！');
        }

        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $order = $this->lifeToolsOrderModel->where($condition)->find();
        if(!$order){
            throw new \think\Exception("订单不存在！");
        }

        $setting = $this->lifeToolsGroupSettingModel->where('mer_id', $order->mer_id)->find();
        if(!$setting || !$setting->tourists_custom_form){
            throw new \think\Exception("未找到配置信息！");
        }

        $custom_form = json_decode($setting->tourists_custom_form, true);
        
        if(!empty($params['tourists_id'])){
             
            $condition = [];
            $condition[] = ['id', '=', $params['tourists_id']];
            $tourists = $this->lifeToolsGroupOrderTouristsModel->field('*,id tourists_id')->where($condition)->find()->toArray();
            if(!$tourists){
                throw new \think\Exception("游客不存在！");
            }
            if($tourists['is_del'] == 1){
                throw new \think\Exception("游客信息已被删除！");
            }
            foreach($custom_form as $key => $val){
                foreach ($tourists['tourists_custom_form'] as $k => $v) {
                    if($val['title'] == $v['title']){
                        $custom_form[$key]['value'] = $v['value'];
                        $custom_form[$key]['show_value'] = $v['show_value'];
                    }
                }
            }

        }

        //过滤禁用表单
        foreach ($custom_form as $key => $val) {
            if($val['status'] == 0){
                unset($custom_form[$key]);
                continue;
            }
            if($val['type'] == 'select'){
                $content = explode(',', $val['content']);
                $conArr = [];
                foreach($content as $k => $v){
                    $conArr[$k]['label'] = $v;
                    $conArr[$k]['value'] = $k;
                }
                $custom_form[$key]['content'] = $conArr;
            }
        }
        $custom_form = array_values($custom_form);

        //排序
        $sortArr = array_column($custom_form, 'sort');
        asort($sortArr);
        $return = [];
        foreach ($sortArr as $key => $value) {
            $return[] = $custom_form[$key];
        }
        $return = array_reverse($return);
        return [$return];
    }


    /**
     * 添加/修改游客信息
     */
    public function addOrEditTourists($params)
    {
        $order = $this->lifeToolsOrderModel->where('order_id', $params['order_id'])->find();
        $group_order = $this->lifeToolsGroupOrderModel->where('order_id', $params['order_id'])->find();
        if(!$order || !$group_order){
            throw new \think\Exception("订单不存在！");
        }
        if($group_order->group_status != 0){
            throw new \think\Exception("当前订单状态不支持此操作！");
        }

        $tourists_custom_form = $params['tourists_custom_form'];
        $saveData = [];

        $id_card = [];
        foreach($tourists_custom_form as $key => $form){

            foreach($form as $k => &$item){
                if(empty($item['value']) && $item['is_must'] == 1){
                    throw new \think\Exception($item['title'] . '参数不能为空');
                }

                if($item['type'] == 'image'){
                    if(!is_array($item['value'])){
                        throw new \think\Exception('value格式有误！');
                    }
                    if(count($item['value']) > $item['image_max_num']){
                        throw new \think\Exception($item['title'] . '最多上传' . $item['image_max_num'] . '张图片');
                    }

                    $item['value'] = array_filter($item['value']);
                }

                if($item['type'] == 'idcard'){
                    if(!is_idcard($item['value'])){
                        throw new \think\Exception('身份证号码'. $item['value'] .'不正确!');
                    }
                    $id_card[] = $item['value'];
                }

                if($item['type'] == 'select'){
                    if(!empty($item['value']) && is_array($item['value']) && isset($item['value'][0]['value'])){
                        $item['value'] = $item['value'][0]['value'];
                    }
                }
    
            }
            $saveData[] = $form;
        } 

        if(!count($saveData)){
            throw new \think\Exception("请上传信息");
        }

        //编辑
        if(!empty($params['tourists_id'])){
            if($order->uid != $params['uid']){
                throw new \think\Exception("无权操作！");
            }
            $condition = [];
            $condition[] = ['id', '=', $params['tourists_id']];
            $condition[] = ['order_id', '=', $order['order_id']];
            $condition[] = ['is_del', '=', 0];
            $tourists = $this->lifeToolsGroupOrderTouristsModel->where($condition)->find();
            if(!$tourists){
                throw new \think\Exception("游客不存在！");
            }
            $tourists->order_id = $order['order_id'];
            $tourists->tourists_custom_form = $saveData[0];
            $tourists->is_del = 0;
            return $tourists->save();
        }else{//添加
            $condition = [];
            $condition[] = ['order_id', '=', $order['order_id']];
            $condition[] = ['is_del', '=', 0];
            $num = $this->lifeToolsGroupOrderTouristsModel->where($condition)->count();
            if($num >= $order->num){
                throw new \think\Exception("此订单人数已满！");
            }
            $remain_num = $order->num - $num; 
            if(($remain_num) < count($saveData)){
                throw new \think\Exception('游客数量超出订单门票总数，无法提交，请重新检查游客信息');
            }
            //验证身份证是否重复
            $exist_form = $this->lifeToolsGroupOrderTouristsModel->where($condition)->column('tourists_custom_form');
            foreach ($exist_form as $form) {
                if(!$form){
                    continue;
                }
                $form = json_decode($form, true);
                foreach ($form as $items) {
                    if($items['type'] == 'idcard' && in_array($items['value'], $id_card)){
                        throw new \think\Exception('身份证号码'. $items['value'] .'已存在，请勿重复添加!');
                    }
                }
            }
            
            $time = time();
            $saveAllData = [];
            foreach($saveData as $key => $val)
            {
                $tmp = [];
                $tmp['order_id'] = $order['order_id'];
                $tmp['uid'] = $params['uid'];
                $tmp['tourists_custom_form'] = $val;
                $tmp['is_del'] = 0;
                $tmp['create_time'] = $time;
                $saveAllData[] = $tmp;
            }
        
            $tourists = $this->lifeToolsGroupOrderTouristsModel;
             
            $tourists->saveAll($saveAllData);
            return true;
        }
        
    }


    /**
     * 删除游客信息
     */
    public function delTourists($params)
    {
        $condition = [];
        $condition[] = ['id', '=', $params['tourists_id']];
        $tourists = $this->lifeToolsGroupOrderTouristsModel->where($condition)->find();
        if(!$tourists){
            throw new \think\Exception("游客不存在！");
        }
        if($tourists->is_del == 1){
            throw new \think\Exception("游客信息已被删除！");
        } 

        $order = $this->lifeToolsOrderModel->where('order_id', $tourists->order_id)->find();
        $group_order = $this->lifeToolsGroupOrderModel->where('order_id', $tourists->order_id)->find();
        if(!$order || !$group_order){
            throw new \think\Exception("订单不存在！");
        }
        if($group_order->group_status != 0){
            throw new \think\Exception("当前订单状态不支持此操作！");
        }
        if($order->uid != $params['uid']){
            throw new \think\Exception("无权操作！");
        }
        $tourists->is_del = 1;
        $tourists->save();
        return true;
    }

}
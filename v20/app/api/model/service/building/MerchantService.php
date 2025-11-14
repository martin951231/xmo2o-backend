<?php

namespace app\api\model\service\building;

use think\facade\Db;
use token\Ticket;

class MerchantService
{
    //自动创建商家（名称、手机号）
    public function create($params)
    {
        $name = isset($params['name']) ? trim($params['name']) : '';
        $phone = isset($params['phone']) ? trim($params['phone']) : '';
        if (empty($name) || empty($phone)) {
            throw new \Exception('参数错误');
        }

        $merchant = Db::name('merchant')->where('phone', '=', $phone)->where('status', '<>', 4)->find();
        if ($merchant) {
            //商家存在
            return [
                'mer_id' => $merchant['mer_id'],
                'name' => $merchant['name'],
            ];
        } else {
            //商家不存在,默认给所有权限节点
            $menus = Db::name('new_merchant_menu')->where('status', 1)->where('show', 1)
                ->where('id', '<>', 70)
                ->where('fid', '<>', 70)
                ->order('id asc')
                ->column('id');

            $data = [
                'name' => $name,
                'phone' => $phone,
                'status' => 1,
                'menus' => $menus ? implode(',', $menus) : '',
                'reg_time' => time()
            ];
            $merId = Db::name('merchant')->insertGetId($data);
            return [
                'mer_id' => $merId,
                'name' => $name,
            ];
        }
    }

    //获取伪登录地址
    public function getLoginUrl($params)
    {
        $merId = isset($params['mer_id']) ? intval($params['mer_id']) : 0;
        $phone  = isset($params['phone']) ? trim($params['phone']) : '';

        if (empty($merId) && empty($phone)) {
            throw new \Exception('参数错误');
        }

        //优先读取商家ID、其次是手机号
        if ($merId) {
            $merchant = Db::name('merchant')->where('mer_id', '=', $merId)->where('status', '<>', 4)->find();
        } else {
            $merchant = Db::name('merchant')->where('phone', '=', $phone)->where('status', '<>', 4)->find();
        }
        if (!$merchant) {
            throw new \Exception('商家不存在或已删除');
        }

        $tm = time();
        $type = 'merchant';
        $ttl = $tm + 300;
        $ticket = Ticket::create($merchant['mer_id'], $type . $ttl, true)['ticket'];
        return ['login_url' => cfg('site_url') . '/source/login.php?type=' . $type . '&tm=' . $tm . '&tmp_ticket=' . $ticket];
    }

    //商场积分设置
    public function getMallLists(){
        $result = Db::name('shopping_mall')->alias('mall')
          ->leftJoin('shopping_mall_score_setting score','mall.id=score.mall_id')
          ->field('score.*,mall.name,mall.id as mall_id')
          ->select()->each(function($item){
            unset($item['id']);
            $item['get_score_rule'] = $item['get_score_rule']?json_decode($item['get_score_rule'],true):[];
            $item['use_score_rule'] = $item['use_score_rule']?json_decode($item['use_score_rule'],true):[];
            $item['is_online']= intval($item['is_online']);
            $item['use_max_percent'] = $item['use_max_percent']?:0;
            $item['update_time'] = $item['update_time']?:'--';
            return $item;
          });
          return $result;
    }

    //切换商场积分上下线状态
    public function changeOnlineStatus($mallId){
        $mall = Db::name('shopping_mall')->where('id',$mallId)->find();
        if(!$mall){
            throw new \Exception('商场不存在');
        }

        $setting = Db::name('shopping_mall_score_setting')->where('mall_id',$mallId)->find();
        if(!$setting){
            $update['is_online'] = 1;
            $update['mall_id'] = $mallId;
            Db::name('shopping_mall_score_setting')->insert($update);
        }else{
            $update['is_online'] = $setting['is_online']?0:1;
            Db::name('shopping_mall_score_setting')->where('mall_id',$mallId)->update($update);
        }
        return ['is_online'=>$update['is_online']];
    }

    //保存积分配置
    public function saveScoreSetting($params){
        $mallId = isset($params['mall_id']) ? intval($params['mall_id']) : 0;
        if(empty($mallId)){
            throw new \Exception('参数错误');
        }
        $mall = Db::name('shopping_mall')->where('id',$mallId)->find();
        if(!$mall){
            throw new \Exception('商场不存在');
        }
        $update = [
            'mall_id' => $mallId,
            'get_score_rule' => (isset($params['get_score_rule']) ? json_encode($params['get_score_rule'], JSON_UNESCAPED_UNICODE) : []),
            'use_score_rule' => (isset($params['use_score_rule']) ? json_encode($params['use_score_rule'], JSON_UNESCAPED_UNICODE) : []),
            'use_max_percent' => $params['use_max_percent'] ?? 0
        ];

        if ($update['use_max_percent'] < 0 || $update['use_max_percent'] > 100) {
            throw new \Exception('使用最大百分比错误,请输入0-100之间的数字,最多支持两位小数');
        }

        $old = Db::name('shopping_mall_score_setting')->where('mall_id',$mallId)->find();
        if($old){
            Db::name('shopping_mall_score_setting')->where('mall_id',$mallId)->update($update);
        }else{
            Db::name('shopping_mall_score_setting')->insert($update);
        }
        return true;
    }
}

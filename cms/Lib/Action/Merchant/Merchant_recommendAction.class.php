<?php
// 商家推广商家
class Merchant_recommendAction extends BaseAction{
    public function index(){
        import('@.ORG.merchant_page');
        if($_GET['keyword']){
            $where['name'] = array('like','%'.$_GET['keyword'].'%');
        }
        // 获取当前商家信息
        $m_merchant = M('Merchant');
        $m_merchant_recommend_merchant_record = M('Merchant_recommend_merchant_record');
        $merchant_info = $m_merchant->where(array('mer_id'=> $this->merchant_session['mer_id']))->find();
        if(empty($merchant_info['mer_code'])){
            $mer_code = $this->make_code();
            M('Merchant')->where(array('mer_id'=>$merchant_info['mer_id']))->setField('mer_code',$mer_code);
            $merchant_info['mer_code'] = $mer_code;
        }
        $this->assign('merchant_info', $merchant_info);

        // 当前默认查询 批发市场进货分佣

        $where['mer_spread_code'] = $merchant_info['mer_code'];

        $count = $m_merchant->where($where)->count();

        $p = new Page($count, 20);
        $spread_user = $m_merchant->field('name as spread_mer_name, mer_id, reg_time as spread_time, type_id, phone, province_id, city_id, area_id')
            ->where($where)->limit($p->firstRow . ',' . $p->listRows)->order('reg_time DESC')->select();
        $pagebar = $p->show();


        if ($spread_user) {
            foreach ($spread_user as &$val) {
                $where_record['order_type'] = 'market_spread';
                $where_record['spread_mer_id'] = $val['mer_id'];
                $record_spread_user = $m_merchant_recommend_merchant_record
                    ->field('SUM(money) AS spread_money, order_type, spread_time, spread_mer_name, mer_id, spread_mer_id')
                    ->where($where_record)->find();
                if ($record_spread_user) {
                    $val['spread_money'] = $record_spread_user['spread_money'];
                    $val['order_type'] = $record_spread_user['order_type'];
                    $val['spread_mer_id'] = $record_spread_user['spread_mer_id'];
                    $val['mer_id'] = $record_spread_user['mer_id'];
                }
                if ($this->config['merchant_type_switch'] == 1) {
                    $val['type_name'] = M('Merchant_type')->where(array('type_id' => $val['type_id']))->getField('type_name');
                    $val['province_name'] = M('Area')->where(array('area_id' => $val['province_id']))->getField('area_name');
                    $val['city_name'] = M('Area')->where(array('area_id' => $val['city_id']))->getField('area_name');
                    $val['area_name'] = M('Area')->where(array('area_id' => $val['area_id']))->getField('area_name');
                }
            }
        }

        $where['order_type'] = 'market_spread';
        $where['mer_id'] = $this->merchant_session['mer_id'];

        $this->assign('pagebar', $pagebar);
        $all_spread_money = M('Merchant_recommend_merchant_record')->where($where)->sum('money');
        $this->assign('all_spread_money',$all_spread_money?$all_spread_money:0);
        $this->assign('spread_user',$spread_user);
        $this->display();
    }
    // 生成推广码 改为纯数字暂定为 100000-999999  记得对应注册商家的位置添加对应判断-pc和App端
    private function make_code()
    {
        $str = mt_rand(100000,999999);
        if(!M('Merchant')->where(array('mer_spread_code'=>$str))->find()){
            return $str;
        }else{
            return  $this->make_code();
        }
    }
}
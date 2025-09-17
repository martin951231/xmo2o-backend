<?php
class House_village_parkModel extends Model{
    public function house_village_park_add($data){
        if(!$data){
            return false;
        }

        if(empty($data['park_month_price'])){
            return array('status'=>0,'msg'=>'收费价格不能为空！');
        }
        if(!$data['park_month_num']){
            $data['park_month_num'] = $data['diy_park_month_num'];
            unset($data['diy_park_month_num']);
        }


        if($data['park_month_num'] <= 0){
            return array('status'=>0,'msg'=>'停车缴费周期不合法！');
        }

        if(!$data['presented_park_month_num']){
            $data['presented_park_month_num'] = $data['diy_presented_park_month_num'];
            unset($data['diy_presented_park_month_num']);
        }

        if($data['diy_type'] == 0){
            if(($data['park_month_num'] > 36) || ($data['park_month_num'] < 1)){
                return array('status'=>0,'msg'=>'请填写1-36之间的值！');
            }
            unset($data['diy_content']);
        }else{
            unset($data['presented_park_month_num'] , $data['diy_presented_park_month_num']);
        }
        $info = $this->where(array('park_month_num'=>$data['park_month_num'],'village_id'=>$_SESSION['house']['village_id'],'park_month_id'=>$data['park_month_id']))->find();
        if(!empty($info)){
            return array('status'=>0,'msg'=>'该月份已存在！');
        }
        $data['add_time'] = time();
        $data['park_sys_type']=D('House_village_park_config')->where(['village_id'=>$_SESSION['house']['village_id']])->getField('park_sys_type');

        $inser_id = $this->data($data)->add();
        if($inser_id){
            $park_system = M('Park_system')->where(array('park_id'=>$_SESSION['house']['village_id']))->find();
            $house_village_park_month = M('house_village_park_month')->where(array('id'=>$data['park_month_id'],'village_id'=>$_SESSION['house']['village_id']))->find();
            if($park_system && $house_village_park_month){
                $record['service_name'] = 'month_card_sync';
                $record['village_id'] = $_SESSION['house']['village_id'];
                $record['token'] = $park_system['token'];
                $record['park_id'] = $park_system['park_id'];
                $record['status'] = 2;
                $record['third_id'] = $inser_id;
                    //序列化存储
                    $desc_arr['operate_type'] = 1;
                    $desc_arr['price'] = $data['park_month_price'];
                    $desc_arr['park_id'] = $park_system['park_id'];
                    $desc_arr['create_time'] = time();
                    $desc_arr['update_time'] = time();
                    $desc_arr['name'] = $house_village_park_month['month_name'];
                    $desc_arr['describe'] = '';//描述
                    $desc_arr['package_id'] = $inser_id;
                    $desc_arr['period'] = '月';
                    $desc_arr['car_type'] = '';//车辆类型car_type_tb 中的car_type_id  否
                    $arr['data'] = $desc_arr;
                    $arr['service_name'] = 'month_card_sync';
                $record['unserialize_desc'] = serialize($arr);

                M('Park_total_record')->data($record)->add();
            }


            return array('status'=>1,'msg'=>'添加成功！');
        }else{
            return array('status'=>0,'msg'=>'添加失败！');
        }
    }

    public function house_village_park_page_list($where , $fields = true , $order = 'id desc',$pageSize = 20){
        if(!$where){
            return false;
        }

        import('@.ORG.merchant_page');
        $count = $this->where($where)->count();
        $p = new Page($count , $pageSize , 'page');

        $house_village_proerty_list = $this->where($where)->field($fields)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $list['list'] = $house_village_proerty_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }

    public function house_village_park_detail($where,$fields =true){
        if(!$where){
            return false;
        }

        $detail = $this->where($where)->field($fields)->find();
        if(!$detail){
            return array('status'=>0,'detail'=>$detail);
        }else{
            return array('status'=>1,'detail'=>$detail);
        }
    }

    public function house_village_park_del($where){
        if(!$where){
            return false;
        }

        $insert_id = $this->where($where)->delete();
        if($insert_id){

            return array('status'=>1,'msg'=>'删除成功！');
        }else{
            return array('status'=>0,'msg'=>'删除失败！');
        }
    }

    public function house_village_park_edit($where , $data){
        if(!$where || !$data){
            return false;
        }
        if(empty($data['park_month_price'])){
            return array('status'=>0,'msg'=>'收费价格不能为空！');
        }
        if(!$data['park_month_num']){
            $data['park_month_num'] = $data['diy_park_month_num'];
            unset($data['diy_park_month_num']);
        }

        if($data['park_month_num'] <= 0){
            return array('status' => 0,'msg' => '停车缴费周期不合法！');
        }

        if($data['diy_type'] == 0){
            if(!$data['presented_park_month_num']){
                $data['presented_park_month_num'] = $data['diy_presented_park_month_num'];
                unset($data['diy_presented_park_month_num']);
            }

            if(($data['park_month_num'] > 36) || ($data['park_month_num'] < 1)){
                return array('status' => 0,'msg'=>'请填写1-36之间的值！');
            }
            unset($data['diy_content']);
        }else{
            $data['presented_park_month_num'] = 0;
        }

        $insert_id = $this->where($where)->data($data)->save();

        if($insert_id){
            $park_system = M('Park_system')->where(array('park_id'=>$_SESSION['house']['village_id']))->find();
            $house_village_park_month = M('house_village_park_month')->where(array('id'=>$data['park_month_id'],'village_id'=>$_SESSION['house']['village_id']))->find();
            if($park_system && $house_village_park_month){
                $record['service_name'] = 'month_card_sync';
                $record['village_id'] = $_SESSION['house']['village_id'];
                $record['token'] = $park_system['token'];
                $record['park_id'] = $park_system['park_id'];
                $record['status'] = 2;
                $record['third_id'] = $where['id'];
                //序列化存储
                $desc_arr['operate_type'] = 2;
                $desc_arr['price'] = $data['park_month_price'];
                $desc_arr['park_id'] = $park_system['park_id'];
                $desc_arr['create_time'] = time();
                $desc_arr['update_time'] = time();
                $desc_arr['name'] = $house_village_park_month['month_name'];
                $desc_arr['describe'] = '';//描述
                $desc_arr['package_id'] = $where['id'];
                $desc_arr['period'] = '月';
                $desc_arr['car_type'] = '';//车辆类型car_type_tb 中的car_type_id  否
                $arr['data'] = $desc_arr;
                $arr['service_name'] = 'month_card_sync';
                $record['unserialize_desc'] = serialize($arr);

                M('Park_total_record')->data($record)->add();
            }
            return array('status'=>1,'msg'=>'修改成功！');
        }else{
            return array('status'=>0,'msg'=>'未做更改！');
        }
    }

    // ↓ A1（kumike）智慧停车【杂淘】相关方法
    public function kumikeParkPrepayCardTradeSync($orderParam) {
        $service_name = 'prepay_card_trade_sync';
        // ↑ 3.21 储值卡余额变更记录下发（停车云）文档地址：https://www.kancloud.cn/bolink/sdk/1938328
        $park_id = trim($orderParam['park_id']);
        //  ↑ 泊链车场编号(对应小区id)	String	例如 123（必传）
        $car_number = isset($orderParam['car_number'])&&trim($orderParam['car_number'])?trim($orderParam['car_number']):'';
        // ↑ 车牌 String	京GH0093 (非必传)
        $card_id = trim($orderParam['card_id']);
        // ↑ 储值卡会员编号（收费系统唯一编号） String 例如 2017081000 （必传）
        $pay_time = trim($orderParam['pay_time']);
        // ↑ 续费时间(unix时间戳格式) Number	例如 1490875218 （必传）
        $pay_type = trim($orderParam['pay_type']);
        // ↑ 缴费类型 String	现金 （必传）
        $total_price = trim($orderParam['total_price']);
        // ↑ 变更金额 String 例如 123（负数表示退款；正数表示续费充值） （必传）
        $mobile = trim($orderParam['mobile']);
        // ↑ 联系电话	String	例如 15000000000 （必传）
        $name = trim($orderParam['name']);
        // ↑ 车主名称	String	例如 zhaa （必传）
        $collector = isset($orderParam['collector'])&&trim($orderParam['collector'])?trim($orderParam['collector']):'';
        // ↑ 收费员	String	例如 管理员 （非必传）
        $user_id = isset($orderParam['user_id'])&&trim($orderParam['user_id'])?trim($orderParam['user_id']):'';
        // ↑ 用户编号	String	例如 001 （非必传）
        $account_type = isset($orderParam['account_type'])?intval($orderParam['account_type']):1;
        // ↑ 流水类型	Number	1收入；0支出（必传）
        $actual_money = trim($orderParam['actual_money']);
        // ↑ 实收/实付金额 Number	例如 123 （负数表示退款；正数表示续费充值）	（必传）
        if (isset($orderParam['trade_no']) && trim($orderParam['trade_no'])) {
            $trade_no = trim($orderParam['trade_no']);
            // ↑ 交易流水号	String	例如 20170511810（必传）
        } else {
            $nowtime = date("mdHis");
            $order_id = isset($orderParam['order_id'])?$orderParam['order_id']:$park_id;
            $trade_no = $nowtime . sprintf("%04d", $order_id);
            // ↑ 交易流水号	String	例如 20170511810（必传）
        }
        if (!$park_id || !$card_id || !$pay_type || !$total_price || !$mobile) {
            fdump_api(['储值车退款同步'.__LINE__,$orderParam],"park/kumikeParkPrepayCardTradeSyncLog",1);
            return false;
        }
        $park_data = [
            'park_id' => $park_id,
            'card_id' => $card_id,
            'pay_time' => $pay_time?intval($pay_time):time(),
            'pay_type' => $pay_type,
            'total_price' => $total_price,
            'mobile' => $mobile,
            'name' => $name,
            'account_type' => $account_type,
            'actual_money' => $actual_money,
            'trade_no' => $trade_no,
        ];
        if ($car_number) {
            $park_data['car_number'] = $car_number;
            if (!$park_data['name']) {
                $park_data['name'] = '车主【'.$car_number.'】';
            }
        }
        if ($collector) {
            $park_data['collector'] = $collector;
        }
        if ($user_id) {
            $park_data['user_id'] = $user_id;
        }
        $json_data = [];
        $json_data['service_name'] = $service_name;
        $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
        $json_data['data'] = $park_data;
        $post_data['unserialize_desc'] = serialize($json_data);
        $post_data['park_id'] = $park_id;
        $post_data['token'] = isset($orderParam['token'])&&trim($orderParam['token'])?trim($orderParam['token']):'';
        $post_data['service_name'] = $service_name;
        $post_data['village_id'] = isset($orderParam['village_id'])&&trim($orderParam['village_id'])?trim($orderParam['village_id']):$park_id;
        $post_data['msg_id'] = $json_data['msg_id'];
        $post_data['stored_card'] = $park_data['card_id'];
        $post_data['order_id'] = isset($orderParam['order_id'])&&trim($orderParam['order_id'])?trim($orderParam['order_id']):0;
        $post_data['create_time'] = time();
        $post_data['car_id'] = isset($orderParam['car_id'])?intval($orderParam['car_id']):0;
        if ($total_price<0) {
            $post_data['third_id'] = '-99';
            // ↑ 标识退款同步
        }
        $id = M('Park_total_record')->data($post_data)->add();//储值卡续充同步
        if (!$id) {
            fdump_api(['添加储值车退款同步错误'.__LINE__,$orderParam,$post_data,M('park_total_record')->getDbError()],"park/kumikeParkPrepayCardTradeSyncLog",1);
            return false;
        }
        return true;
    }
}
?>
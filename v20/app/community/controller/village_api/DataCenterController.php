<?php


namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\VillageQywxDataCenterService;

class DataCenterController extends CommunityBaseController
{

    /**
     * 数据统计
     * @author lijie
     * @date_time 2021/03/29
     * @return \json
     */
    public function index()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $where[] = ['add_type','in',[1,2]];
            $where[] = ['property_id','=',$property_id];
        } else {
            $type = 1;//小区
            $where[] = ['add_type','=',$type];
            $where[] = ['village_id','=',$village_id];
        }
        $selected_type = $this->request->post('selected_type','week');
        $selected_name = $this->request->post('selected_name','apply_friends_num');
        $enterprise_staff = $this->request->post('enterprise_staff',[]);
        if($enterprise_staff) {
            $where[] = ['user_id','in',$enterprise_staff];
        }
        $service_village_qywx_data_center = new VillageQywxDataCenterService();
        if($selected_type == 'week'){
            $time = time();
            //组合数据
            $date_arr = [];
            $sum_arr = [];
            for ($i=1; $i<=7; $i++){
                $date_arr[] = date('Y-m-d' ,strtotime( '+' . $i-7 .' days', $time));
            }
            foreach ($date_arr as $v){
                if($where[count($where)-1][0] == 'D') {
                    $where[count($where)-1][2] = $v;
                } else {
                    $where[] = ['D','=',$v];
                }
                $sum = $service_village_qywx_data_center->getSum($where,$selected_name);
                $sum_arr[] = $sum;
            }
            $res['date_arr'] = $date_arr;
            $res['sum_arr'] = $sum_arr;
        }else{
            if($selected_type == 'year'){
                $year = $this->request->post('year',date('Y'));
                $year = explode('-',$year);
                $year = $year[0];
                $start_month = $year.'-01';
                $end_month = $year.'-12';
            }elseif($selected_type == 'month'){
                $month_between = $this->request->post('month_between','');
                if(empty($month_between)){
                    return api_output_error(1001,'请选择月份');
                }
                $m1=explode('-',$month_between[0]);
                $m2=explode('-',$month_between[1]);
                $start_month = $m1[0].'-'.$m1[1];
                $end_month = $m2[0].'-'.$m2[1];
            }
            $date_arr = dateMonths($start_month,$end_month);
            $sum_arr = [];
            foreach ($date_arr as $v){
                if($where[count($where)-1][0] == 'M') {
                    $where[count($where)-1][2] = $v;
                } else {
                    $where[] = ['M','=',$v];
                }
                $sum = $service_village_qywx_data_center->getSum($where,$selected_name);
                $sum_arr[] = $sum;
            }
            $res['date_arr'] = $date_arr;
            $res['sum_arr'] = $sum_arr;
        }
        return api_output(0,$res);
    }

    /**
     * 数据统计
     * @author lijie
     * @date_time 2021/03/31
     * @return \json
     */
    public function tongji()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id = $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $where[] = ['property_id','=',$property_id];
        } else {
            $where[] = ['village_id','=',$village_id];
        }
        $yesterday_date = date("Y-m-d",time()-24*60*60);
        $previous_date = date("Y-m-d",time()-2*24*60*60);
        $where[] = ['D','=',$yesterday_date];
        $service_village_qywx_data_center = new VillageQywxDataCenterService();
        $yesterday_apply_friends_num = $service_village_qywx_data_center->getSum($where,'apply_friends_num');  //好友申请数
        $yesterday_new_house_holds_num = $service_village_qywx_data_center->getSum($where,'new_house_holds_num'); //新增住户数
        $yesterday_new_non_residents_num = $service_village_qywx_data_center->getSum($where,'new_non_residents_num');  //新增非住户数
        $yesterday_block_num = $service_village_qywx_data_center->getSum($where,'block_num');  //拉黑删除数
        $where[] = ['D','=',$previous_date];
        $previous_apply_friends_num = $service_village_qywx_data_center->getSum($where,'apply_friends_num');  //好友申请数
        $previous_new_house_holds_num = $service_village_qywx_data_center->getSum($where,'new_house_holds_num'); //新增住户数
        $previous_new_non_residents_num = $service_village_qywx_data_center->getSum($where,'new_non_residents_num');  //新增非住户数
        $previous_block_num = $service_village_qywx_data_center->getSum($where,'block_num');  //拉黑删除数
        $data['apply_friends_num'][] = $yesterday_apply_friends_num;
        if ($yesterday_apply_friends_num > 0 && $previous_apply_friends_num == 0) {
            $data['apply_friends_num'][] = round($yesterday_apply_friends_num/1*100,2).'%';
        } else {
            $data['apply_friends_num'][] = $previous_apply_friends_num==0?'0%':round($yesterday_apply_friends_num/$previous_apply_friends_num*100,2).'%';
        }
        $data['apply_friends_num'][] = $previous_apply_friends_num==0?0:round($yesterday_apply_friends_num/$previous_apply_friends_num*100,2);

        $data['new_house_holds_num'][] = $yesterday_new_house_holds_num;
        if ($yesterday_new_house_holds_num > 0 && $previous_new_house_holds_num == 0) {
            $data['new_house_holds_num'][] = round($yesterday_new_house_holds_num/1*100,2).'%';
        } else {
            $data['new_house_holds_num'][] = $previous_new_house_holds_num==0?'0%':round($yesterday_new_house_holds_num/$previous_new_house_holds_num*100,2).'%';
        }
        $data['new_house_holds_num'][] = $previous_new_house_holds_num==0?0:round($yesterday_new_house_holds_num/$previous_new_house_holds_num*100,2);

        $data['new_non_residents_num'][] = $yesterday_new_non_residents_num;
        if ($yesterday_new_non_residents_num > 0 && $previous_new_non_residents_num == 0) {
            $data['new_non_residents_num'][] = round($yesterday_new_non_residents_num/1*100,2).'%';
        } else {
            $data['new_non_residents_num'][] = $previous_new_non_residents_num==0?'0%':round($yesterday_new_non_residents_num/$previous_new_non_residents_num*100,2).'%';
        }
        $data['new_non_residents_num'][] = $previous_new_non_residents_num==0?0:round($yesterday_new_non_residents_num/$previous_new_non_residents_num*100,2);

        $data['block_num'][] = $yesterday_block_num;
        if ($yesterday_new_non_residents_num > 0 && $previous_new_non_residents_num == 0) {
            $data['block_num'][] = round($yesterday_block_num/1*100,2).'%';
        } else {
            $data['block_num'][] = $previous_block_num==0?'0%':round($yesterday_block_num/$previous_block_num*100,2).'%';
        }
        $data['block_num'][] = $previous_block_num==0?0:round($yesterday_block_num/$previous_block_num*100,2);
        return api_output(0,$data);
    }

    
}
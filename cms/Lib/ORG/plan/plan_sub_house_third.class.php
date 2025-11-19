<?php
/*
 *社区第三方对接子计划任务
 * */

use app\common\model\db\ProcessSubPlan;

class plan_sub_house_third extends plan_base{
    public function runTask($param){
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }

        if (isset($param['type']) && 'carDataToAccessRecord'==$param['type']) {
            $this->carDataToAccessRecord();
        } elseif (isset($param['type']) && 'orderBillBankFile'==$param['type']) {
            $model_call = '\app\thirdAccess\model\service\BillAccessService/orderBillBankFile';
            $this->keepThread();
            $v20res = invoke_v20_service($model_call,['param'=>$param]);
        } else {
            D('House_village_third_user_info')->third_runtask($param,$this->plan_processTimeFile);
        }
        return true;
    }

    public $db_in_park;
    public $db_out_park;
    public $db_house_village;
    public $m_house_village_parking_car;
    public function carDataToAccessRecord($park_id=0,$limit=100) {
        $this->db_in_park = D('In_park');
        $this->db_out_park = D('Out_park');
        $this->db_house_village = D('House_village');
        $this->m_house_village_parking_car = D('House_village_parking_car');
        $where = [];
        $where['moved'] = 0;
        $park_name = '';
        if ($park_id) {
            $where['park_id'] = $park_id;
            $park_name = $this->db_house_village->where(['village_id'=> $park_id])->getField('village_name');
        }
        $this->keepThread();
        $inParkList = $this->db_in_park->where($where)->limit($limit)->select();
        $outParkList = [];
        $nolicenceInPark = [];
        if (empty($inParkList)) {
            // 无牌车入场


            $this->keepThread();
            D('Config_data')->save_config_data('inParkMove',time());
            $outParkList = $this->db_out_park->where($where)->limit($limit)->select();
            if (empty($outParkList)) {
                D('Config_data')->save_config_data('outParkMove',time());
                return false;
            }
        }
        $noInParkList = [];
        $signMovedPark = [
            'moved' => time(),
        ];
        fdump(["line"=> __LINE__,'inParkList' => $inParkList,'outParkList' => $outParkList],'carDataToAccessRecord',1);
        if ($inParkList) {
            $signParkType = 'in_park';
            foreach ($inParkList as $item) {
                $this->keepThread();
                $item['optcode'] = isset($item['uid'])&&$item['uid']?$item['uid']:0;
                unset($item['uid']);
                $item['from_id'] = isset($item['id'])&&$item['id']?$item['id']:0;
                $item['accessType'] = 1;
                $item['accessTime'] = $item['in_time'];
                $item['service_name'] = 'inpark';
                $access_record = $this->m_house_village_parking_car->carSingleToAccessRecord($item,$park_id,$park_name);
                $this->keepThread();
                try {
                    $addInParkLists = $this->m_house_village_parking_car->addSingleToAccessRecord($park_id, $park_name, $access_record,$item);
                }catch (Exception $e) {
                    fdump(['title'=>'入场迁移错误记录','line'=>__LINE__,'park_id'=>$park_id,'park_name'=>$park_name,'errMessage'=>$e->getMessage(),'park_id' => $park_id,'item' => $item, 'access_record' => $access_record],'api/log/errCarDataToAccessRecordLog',true);
                }
                if (!empty($addInParkLists['noInParkLists'])) {
                    $noInParkList[] = $addInParkLists['noInParkLists'];
                }
                $this->keepThread();
                if (isset($addInParkLists['record_id'])&&$addInParkLists['record_id']) {
                    $this->m_house_village_parking_car->signMovedPark($item['id'],$signParkType,$signMovedPark);
                }
            }
        }
        if ($outParkList) {
            $signParkType = 'out_park';
            foreach ($outParkList as $out) {
                $this->keepThread();
                $out['optcode'] = isset($out['uid'])&&$out['uid']?$out['uid']:0;
                unset($out['uid']);
                $out['from_id'] = isset($out['id'])&&$out['id']?$out['id']:0;
                $out['accessType'] = 2;
                $item['accessTime'] = $out['out_time'];
                $item['service_name'] = 'outpark';
                $access_record = $this->m_house_village_parking_car->carSingleToAccessRecord($out,$park_id,$park_name);
                fdump($access_record,'$access_record',1);
                $this->keepThread();
                try {
                    $addInParkLists = $this->m_house_village_parking_car->addSingleToAccessRecord($park_id, $park_name, $access_record,$out);
                }catch (Exception $e) {
                    fdump(['title'=>'出场迁移错误记录','line'=>__LINE__,'addInParkLists'=>$addInParkLists,'errMessage'=>$e->getMessage(),'park_id' => $park_id,'item' => $item, 'access_record' => $access_record],'api/log/errCarDataToAccessRecordLog',true);
                }
                if (!empty($addInParkLists['noInParkLists'])) {
                    $noInParkList[] = $addInParkLists['noInParkLists'];
                }
                $this->keepThread();
                if (isset($addInParkLists['record_id'])&&$addInParkLists['record_id']) {
                    $this->m_house_village_parking_car->signMovedPark($out['id'],$signParkType,$signMovedPark);
                }
            }
        }
        $task_id = '';
        if (!empty($inParkList) || (!empty($outParkList)&&count($outParkList)>=($limit-5))) {
            // 如果入场不为空或者出场不为空且查询数据大于查询数量-5[避免同步的时候有数据过来] 说明还有未完成同步的 执行下一次同步
            $param = [
                'type' => 'carDataToAccessRecord',
            ];
            $task_id = $this->replaceAddPlan($param);
        }
        if (empty($inParkList) && (empty($outParkList)||count($outParkList)<($limit-5))) {
            D('Config_data')->save_config_data('outParkMove',time());
        }
        fdump(['title'=>'重复记录','line'=>__LINE__,'$inParkList' => $inParkList,'$outParkList' => $outParkList,'$noInParkList' => $noInParkList,'task_id' => $task_id],'api/log/carDataToAccessRecordLog',true);
        return true;
    }

    private function replaceAddPlan($param) {
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = time();
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'sub_house_third';
        $arr['time_type'] = 1;
        if (isset($param['park_id'])&&$param['park_id']) {
            $arr['unique_id'] = 'carDataToAccessRecord_'.$param['park_id'];
        } else {
            $arr['unique_id'] = 'carDataToAccessRecord';
        }
        $data['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
        $id = (new ProcessSubPlan())->add($data);
        fdump_api(['id'=>$id,'data'=>$data],'000addTask',1);
    }
}
?>
<?php
/**
 * 智慧二维码 （老版本迁移到新版本）
 * @author weili
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\WisdomQrcodePerson;
use app\community\model\db\WisdomQrcodeCate;
use app\community\model\db\WisdomQrcode;
use app\community\model\db\WisdomQrcodePositionIndex;
use app\community\model\db\WisdomQrcodePositionRecord;
use app\community\model\db\WisdomQrcodeRecordLog;
use app\community\model\db\WisdomQrcodeFieldCate;
use app\community\model\db\WisdomQrcodeCateFieldCateConfig;
use app\community\model\db\WisdomQrcodeRecordCate;
use app\community\model\db\WisdomQrcodeRecordCateFieldConfig;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\WisdomQrcodeStatistics;
use app\community\model\db\HouseNewRepairWorksOrder;
use map\longLat;
use think\facade\Db;

class SmartQrCodeService
{

    public $positionConfig = [
        'interval' => 5,  //间隔 秒
        'number' => 5,    //每天次数
        'duration' => 180 //时长 秒
    ];

    /**
     * Notes: 任务列表
     * @param $village_id
     * @param $wid
     * @param $phone
     * @param $page
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/3 17:40
     */
    public function myTask($village_id, $wid = 0, $phone, $page)
    {
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseWorker = new HouseWorker();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $dbWisdomQrcode = new WisdomQrcode();
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $village_user_bind[] = ['village_id', '=', $village_id];
        $village_user_bind[] = ['type', '=', 4];
        $village_user_bind[] = ['phone', '=', $phone];
        $bind_info = $dbHouseVillageUserBind->getOne($village_user_bind);
        fdump($bind_info, 'aa');
        fdump($village_user_bind, 'aa', 1);
        if (empty($bind_info)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $worker_where = array();
        $worker_where[] = ['village_id', '=', $bind_info['village_id']];
        if ($wid > 0) {
            $worker_where[] = ['wid', '=', $wid];
        } else {
            $worker_where[] = ['phone', '=', $bind_info['phone']];
        }
        $worker_where[] = ['status', '=', 1];
        $worker_where[] = ['is_del', '=', 0];
        $house_worker = $dbHouseWorker->getOne($worker_where);
        if (empty($house_worker)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }

        $data = array();
        $person_where[] = ['uid', '=', $house_worker['wid']];
        $person_where[] = ['status', '=', 1];
        $qrcode_person = $dbWisdomQrcodePerson->getList($person_where, '*', 'cate_id desc', $page, 15);
        fdump_api(['任务列表' . __LINE__, $person_where, $qrcode_person], 'myTask', 1);
        if (empty($qrcode_person)) {
            $data['task_list'] = array();
            return $data;
            //throw new \think\Exception("你不是智慧二维码负责人!");
        }
        $person = [];
        $dataTmpList = array();
//        echo "<pre>";
        foreach ($qrcode_person as $k1 => $pv) {

            //$person[] = $pv['cate_id'];
            $finalData = [];
            $qrcodeCate = $dbWisdomQrcodeCate->getFind(array('id' => $pv['cate_id'], 'village_id' => $village_id), '*');

            if( empty($qrcodeCate) ){
                continue;
            }

            if ($qrcodeCate['record_type'] == 1) {
                // 1. 按逗号切割record_time为时间区间数组
                $timeRanges = explode(',', $qrcodeCate['record_time']);

                // 2. 遍历每个时间区间，复制原始数据并更新start/end_time
                foreach ($timeRanges as $timeRange) {
                    // 复制原始数据（深拷贝，避免引用传递）
                    $newItem = $qrcodeCate;

                    // 3. 按横杠切割当前区间为开始/结束时间
                    $timeArr = explode('-', trim($timeRange)); // trim防止空格干扰
                    if (count($timeArr) == 2) { // 确保切割出两个值
                        $newItem['record_time'] = $timeArr[0] . ' - ' . $timeArr[1]; // 开始时间（键0）
                        $newItem['start_time'] = $timeArr[0]; // 开始时间（键0）
                        $newItem['end_time'] = $timeArr[1];   // 结束时间（键1）
                    }

                    // 4. 将新数据加入结果数组
                    $finalData[] = $newItem->toArray();
                }
            } else {
//                $qrcodeCate['record_time'] = $qrcodeCate['start_time'] . ' - ' . $qrcodeCate['end_time'];
                    // record_type≠1时，直接保留原始数据
                $finalData[] = $qrcodeCate;
            }

            foreach ($finalData as $data_k => $data_v) {
                $today = date('Y-m-d');
                $startTimestamp = strtotime("{$today} {$data_v['start_time']}");
                $endTimestamp = strtotime("{$today} {$data_v['end_time']}");
                $currentTimestamp = time(); // 当前时间戳
                // 判断区间
                $isInRange = ($currentTimestamp >= $startTimestamp) && ($currentTimestamp <= $endTimestamp);
                if( !$isInRange ){
                    continue;
                }

                $data_v['add_time'] = date('Y-m-d');
                $data_v['cate_id'] = $data_v['id'];
                $q_where = [];
                $q_where[] = ['cate_id', '=', $data_v['id']];
                $qrcode_count = $dbWisdomQrcode->getCount($q_where);
//                $jsonStr = $data_v['bind_qrcode'];
//                $jsonStr = htmlspecialchars_decode($jsonStr, ENT_QUOTES);
//                $qrcodeList = json_decode($jsonStr, true);
//                $qrcode_count = count( $qrcodeList );
                $data_v['total_num'] = $qrcode_count;

                if (empty($data_v['start_time'])) {
                    $data_v['start_time'] = '00:00';
                }
                if (empty($data_v['end_time'])) {
                    $data_v['end_time'] = '23:59';
                }
                $qrcode = $dbWisdomQrcode->getList($q_where, '*');
                $qrcode_num = [];
                foreach ($qrcode as $ks => $vs) {
                    $qrcode_num[] = $vs['id'];
                }
//                $qrcode_num = array_column( $qrcodeList, 'id' );
                $dates = date('Y-m-d', time());
                $start_time = strtotime($dates . ' ' . $data_v['start_time']);
                $end_time = strtotime($dates . ' ' . $data_v['end_time']);
                $record_log = [];
                $record_log[] = ['qrcode_id', 'in', $qrcode_num];
                $record_log[] = ['cate_id', '=', $data_v['id']];
                $str = [];
                //如果是每天巡检, 则看时间段内是否有记录
                if ($data_v['record_type'] == 1) {
                    $record_time_arr = explode(',', $data_v['record_time']);
                    foreach ($record_time_arr as $r) {
                        [$start_time_p, $end_time_p] = explode('-', $r);
                        $dates = date('Y-m-d', time());
                        $start_time_p = strtotime($dates . ' ' . $start_time_p);
                        $end_time_p = strtotime($dates . ' ' . $end_time_p);
                        $start_time_p = "'" . addslashes($start_time_p) . "'"; // 结果：'14:00'
                        $end_time_p = "'" . addslashes($end_time_p) . "'";     // 结果：'17:00'
                        $str[] = "add_time BETWEEN {$start_time_p} AND {$end_time_p}";
                    }
                    $expSql = '(' . implode(' OR ', $str) . ')';
                    $record_log[] = ['', 'exp', Db::raw($expSql)];
                } else {
                    $record_log[] = ['add_time', 'between', [$start_time, $end_time]];
                }

                $record_log[] = ['village_id', '=', $village_id];
                if (!isset($data_v['type']) || $data_v['type'] != 2) {
                    //type 2时 只要有人巡检过来就是已完成
                    $record_log[] = ['uid', '=', $house_worker['wid']];
                }

                $record_log_count = $dbWisdomQrcodeRecordLog->getCountGroup($record_log, 'qrcode_id');

                if ($data_v['record_type'] == 1) {
//                    $data_v['record_type'] = 1;
                    $data_v['record_type_desc'] = '每天';
                    if ($record_log_count) {
                        if ($record_log_count < $qrcode_count) {
                            $data_v['desc'] = '待完成';
                            $data_v['desc_status'] = 1;
                            $data_v['num'] = $record_log_count . '/' . $qrcode_count;
                        } else {
                            $data_v['desc'] = '已完成';
                            $data_v['desc_status'] = 2;
                            $data_v['num'] = $qrcode_count . '/' . $qrcode_count;
                        }
                    } else {
                        $data_v['desc'] = '待完成';
                        $data_v['desc_status'] = 1;
                        $data_v['num'] = '0' . '/' . $qrcode_count;
                    }
                    $data_v['record_time_one'] = $data_v['start_time'] . '-' . $data_v['end_time'];
                    $data_v['record_time_two'] = '';
                    $data_v['record_time_three'] = '';
                }
                elseif ($data_v['record_type'] == 2) {
//                    $data_v['record_type'] = 2;
                    $data_v['record_type_desc'] = '每周 ';
                    if ($data_v['record_time']) {
                        $data_v['record_type_desc'] .= '(';
                    }
                    $record_time = explode(',', $data_v['record_time']);
                    if (in_array('1', $record_time)) {
                        $data_v['record_type_desc'] .= '周一,';
                    }
                    if (in_array('2', $record_time)) {
                        $data_v['record_type_desc'] .= '周二,';
                    }
                    if (in_array('3', $record_time)) {
                        $data_v['record_type_desc'] .= '周三,';
                    }
                    if (in_array('4', $record_time)) {
                        $data_v['record_type_desc'] .= '周四,';
                    }
                    if (in_array('5', $record_time)) {
                        $data_v['record_type_desc'] .= '周五,';
                    }
                    if (in_array('6', $record_time)) {
                        $data_v['record_type_desc'] .= '周六,';
                    }
                    if (in_array('7', $record_time)) {
                        $data_v['record_type_desc'] .= '周日,';
                    }
                    $data_v['record_type_desc'] = rtrim($data_v['record_type_desc'], ',');
                    if ($data_v['record_time']) {
                        $data_v['record_type_desc'] .= ')';
                    }
                    $record_time = explode(',', $data_v['record_time']);
                    $dates = date('w', time());
                    if (in_array($dates, $record_time)) {
                        if ($record_log_count) {
                            if ($record_log_count < $qrcode_count) {
                                $data_v['desc'] = '待完成';
                                $data_v['desc_status'] = 1;
                                $data_v['num'] = ($qrcode_count - $record_log_count) . '/' . $qrcode_count;
                            } else {
                                $data_v['desc'] = '已完成';
                                $data_v['num'] = $qrcode_count . '/' . $qrcode_count;
                                $data_v['desc_status'] = 2;
                            }
                        } else {
                            $data_v['desc'] = '待完成';
                            $data_v['desc_status'] = 1;
                            $data_v['num'] = '0' . '/' . $qrcode_count;
                        }
                    } else {
                        $data_v['desc'] = '未来计划';
                        $data_v['desc_status'] = 3;
                        $data_v['num'] = '0' . '/' . $qrcode_count;
                    }
                    $data_v['record_time_one'] = '';
                    $data_v['record_time_two'] = $data_v['start_time'] . '-' . $data_v['end_time'];
                    $data_v['record_time_three'] = '';
                }
                elseif ($data_v['record_type'] == 3) {
//                    $data_v['record_type'] = 3;
                    $data_v['record_type_desc'] = '每月 (' . $data_v['record_time'] . ')';
                    $record_time = explode(',', $data_v['record_time']);
                    $dates = date('d', time());
                    if (in_array($dates, $record_time)) {
                        if ($record_log_count) {
                            if ($record_log_count < $qrcode_count) {
                                $data_v['desc'] = '待完成';
                                $data_v['desc_status'] = 1;
                                $data_v['num'] = ($qrcode_count - $record_log_count) . '/' . $qrcode_count;
                            } else {
                                $data_v['desc'] = '已完成';
                                $data_v['desc_status'] = 2;
                                $data_v['num'] = $qrcode_count . '/' . $qrcode_count;
                            }
                        } else {
                            $data_v['desc'] = '待完成';
                            $data_v['desc_status'] = 1;
                            $data_v['num'] = '0' . '/' . $qrcode_count;
                        }
                        $data_v['record_time_one'] = '';
                        $data_v['record_time_two'] = '';
                        $data_v['record_time_three'] = $data_v['start_time'] . '-' . $data_v['end_time'];
                    } else {
                        $data_v['desc'] = '未来计划';
                        $data_v['desc_status'] = 3;
                        $data_v['num'] = '0' . '/' . $qrcode_count;
                        $dates = date('d', time());
                        $arr1 = $record_time;
                        $arr1[] = $dates;
                        sort($arr1);
                        $key = array_search($dates, $arr1);
                        $data_v['record_time_one'] = '';
                        $data_v['record_time_two'] = '';
                        $data_v['record_time_three'] = date('Y-m', time()) . '-' . $arr1[$key];
                    }
                }

                $dataTmpList[] = $data_v;
            }

//            $dataTmpList[]=$qrcodeCate;
        }
        $dataList = $dataTmpList;
        /*
        $where=array(['village_id','=',$village_id]);
        $where[] = ['id','in',$person];
        $dataList = $dbWisdomQrcodeCate->getLists($where,true,'id desc',$page,15);
        foreach($dataList as $k=>&$v){
            $v['add_time'] = date('Y-m-d');
            $v['cate_id'] = $v['id'];
            $q_where = [];
            $q_where[] = ['cate_id','=',$v['id']];
            $qrcode_count = $dbWisdomQrcode->getCount($q_where);
            $v['total_num'] =$qrcode_count;
            $p_where = [];
            $p_where[] = ['uid','=',$house_worker['wid']];
            $p_where[] = ['cate_id','=',$v['id']];
            $qrcode_person = $dbWisdomQrcodePerson->getFind($p_where);
            if($qrcode_person && empty($qrcode_person['start_time'])){
                $qrcode_person['start_time']='00:00';
            }
            if($qrcode_person && empty($qrcode_person['end_time'])){
                $qrcode_person['end_time']='23:59';
            }
            $qrcode = $dbWisdomQrcode->getList($q_where);
            $qrcode_num = [];
            foreach($qrcode as $ks=>$vs){
                $qrcode_num[] =  $vs['id'];
            }
            $dates = date('Y-m-d',time());
            $start_time = strtotime($dates.' '.$qrcode_person['start_time']);
            $end_time =strtotime($dates.' '.$qrcode_person['end_time']);
            $record_log = [];
            $record_log[] = ['qrcode_id','in',$qrcode_num];
            $record_log[] = ['cate_id','=',$v['id']];
            $record_log[] = ['add_time','between',[$start_time,$end_time]];
            $record_log[] = ['village_id','=',$village_id];
            if(!isset($v['type']) || $v['type']!=2){
                //type 2时 只要有人巡检过来就是已完成
                $record_log[] = ['uid','=',$house_worker['wid']];
                
            }
            $record_log_count = $dbWisdomQrcodeRecordLog->getCountGroup($record_log,'qrcode_id');
            if($qrcode_person){
                if($qrcode_person['record_type'] == 1){
                    $v['record_type'] = 1;
                    $v['record_type_desc'] = '每天';
                    if($record_log_count){
                        if($record_log_count < $qrcode_count){
                            $v['desc'] = '待完成';
                            $v['desc_status'] = 1;
                            $v['num'] = $record_log_count.'/'.$qrcode_count;
                        }else{
                            $v['desc'] = '已完成';
                            $v['desc_status'] = 2;
                            $v['num'] = $qrcode_count.'/'.$qrcode_count;
                        }
                    }else{
                        $v['desc'] = '待完成';
                        $v['desc_status'] = 1;
                        $v['num'] = '0'.'/'.$qrcode_count;
                    }
                    $v['record_time_one'] = $qrcode_person['start_time'].'-'.$qrcode_person['end_time'];
                    $v['record_time_two'] = '';
                    $v['record_time_three'] = '';
                }elseif($qrcode_person['record_type'] == 2){
                    $v['record_type'] = 2;
                    $v['record_type_desc'] = '每周 ';
                    if($qrcode_person['record_time']){
                        $v['record_type_desc'] .=  '(';
                    }
                    $record_time = explode(',',$qrcode_person['record_time']);
                    if(in_array('1',$record_time)){
                        $v['record_type_desc'] .= '周一,';
                    }
                    if(in_array('2',$record_time)){
                        $v['record_type_desc'] .= '周二,';
                    }
                    if(in_array('3',$record_time)){
                        $v['record_type_desc'] .= '周三,';
                    }
                    if(in_array('4',$record_time)){
                        $v['record_type_desc'] .= '周四,';
                    }
                    if(in_array('5',$record_time)){
                        $v['record_type_desc'] .= '周五,';
                    }
                    if(in_array('6',$record_time)){
                        $v['record_type_desc'] .= '周六,';
                    }
                    if(in_array('7',$record_time)){
                        $v['record_type_desc'] .= '周日,';
                    }
                    $v['record_type_desc'] = rtrim($v['record_type_desc'],',');
                    if($qrcode_person['record_time']){
                        $v['record_type_desc'] .=  ')';
                    }
                    $record_time = explode(',',$qrcode_person['record_time']);
                    $dates = date('w',time());
                    if(in_array($dates,$record_time)){
                        if($record_log_count){
                            if($record_log_count < $qrcode_count){
                                $v['desc'] = '待完成';
                                $v['desc_status'] = 1;
                                $v['num'] = ($qrcode_count- $record_log_count).'/'.$qrcode_count;
                            }else{
                                $v['desc'] = '已完成';
                                $v['num'] = $qrcode_count.'/'.$qrcode_count;
                                $v['desc_status'] = 2;
                            }
                        }else{
                            $v['desc'] = '待完成';
                            $v['desc_status'] = 1;
                            $v['num'] = '0'.'/'.$qrcode_count;
                        }
                    }else{
                        $v['desc'] = '未来计划';
                        $v['desc_status'] = 3;
                        $v['num'] = '0'.'/'.$qrcode_count;
                    }
                    $v['record_time_one'] = '';
                    $v['record_time_two'] = $qrcode_person['start_time'].'-'.$qrcode_person['end_time'];
                    $v['record_time_three'] = '';
                }elseif($qrcode_person['record_type'] == 3){
                    $v['record_type'] = 3;
                    $v['record_type_desc'] = '每月 ('.$qrcode_person['record_time'].')';
                    $record_time = explode(',',$qrcode_person['record_time']);
                    $dates = date('d',time());
                    if(in_array($dates,$record_time)){
                        if($record_log_count){
                            if($record_log_count < $qrcode_count){
                                $v['desc'] = '待完成';
                                $v['desc_status'] = 1;
                                $v['num'] = ($qrcode_count- $record_log_count).'/'.$qrcode_count;
                            }else{
                                $v['desc'] = '已完成';
                                $v['desc_status'] = 2;
                                $v['num'] = $qrcode_count.'/'.$qrcode_count;
                            }
                        }else{
                            $v['desc'] = '待完成';
                            $v['desc_status'] = 1;
                            $v['num'] = '0'.'/'.$qrcode_count;
                        }
                    }else{
                        $v['desc'] = '未来计划';
                        $v['desc_status'] = 3;
                        $v['num'] = '0'.'/'.$qrcode_count;
                        $dates = date('d',time());
                        $arr1=$record_time;
                        $arr1[]=$dates;
                        sort($arr1);
                        $key = array_search($dates,$arr1);
                        $v['record_time_one'] = '';
                        $v['record_time_two'] = '';
                        $v['record_time_three'] = date('Y-m',time()).'-'.$arr1[$key];
                    }

                }
            }else{
                $v['record_type'] = 0;
                $v['record_type_desc'] = '';
            }

        }
        unset($v);
        */
        $dataList = $this->getRsort($dataList, 'desc_status');
        fdump_api(['任务列表' . __LINE__, $dataList], 'myTask', 1);
        $data['task_list'] = $dataList;
        return $data;
    }

    public function getRsort($list, $field)
    {
        $finishTime = [];
        foreach ($list as $val) {
            $finishTime[] = $val[$field];
        }
        array_multisort($finishTime, SORT_ASC, $list);
        return $list;
    }

    /**
     * Notes: 任务详情列表==>地图显示点
     * @param $village_id
     * @param $wid
     * @param $phone
     * @param $cate_id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/3 17:40
     */
    public function taskQrCode($village_id, $wid = 0, $phone, $cate_id, $page = 0, $param = [])
    {
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseWorker = new HouseWorker();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $dbWisdomQrcode = new WisdomQrcode();
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $village_user_bind[] = ['village_id', '=', $village_id];
        $village_user_bind[] = ['type', '=', 4];
        $village_user_bind[] = ['phone', '=', $phone];
        $bind_info = $dbHouseVillageUserBind->getOne($village_user_bind);
        if (empty($bind_info)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $worker_where[] = ['village_id', '=', $bind_info['village_id']];
        if ($wid > 0) {
            $worker_where[] = ['wid', '=', $wid];
        } else {
            $worker_where[] = ['phone', '=', $bind_info['phone']];
        }
        $worker_where[] = ['status', '=', 1];
        $worker_where[] = ['is_del', '=', 0];
        $house_worker = $dbHouseWorker->getOne($worker_where);
        if (empty($house_worker)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $cateWhere = array();
        $cateWhere[] = array('id', '=', $cate_id);
        $cateWhere[] = array('village_id', '=', $village_id);
        $qrcodeCate = $dbWisdomQrcodeCate->getFind($cateWhere);
        if (empty($qrcodeCate) || $qrcodeCate->isEmpty()) {
            throw new \think\Exception("二维码类别不存在！");
        }

        $c_where[] = ['id', '=', $cate_id];
        $cate_list = $dbWisdomQrcodeCate->getFind($c_where)->toArray();
        if( empty($cate_list) || empty($cate_list['bind_qrcode']) ){
            throw new \think\Exception("暂无需要巡检的点位,请联系管理员添加！");
        }

//        $bind_qrcode_list = json_decode(htmlspecialchars_decode( $cate_list['bind_qrcode'] ));
//        $qrcodeIds = array_column($bind_qrcode_list, 'id');
//        $q_where[] = ['id', 'in', $qrcodeIds];

        $q_where[] = ['cate_id', '=', $cate_id];
        $limit = 20;
        $qrcode_list = $dbWisdomQrcode->getList($q_where, true, 'num_id', $page, $limit)->toArray();
        $qrcode_count = $dbWisdomQrcode->getCount($q_where);

        $qrcode_uncount = 0;
        $record_log_counts = 0;
//        echo "<pre>";
        $app_type = isset($param['app_type']) ? trim($param['app_type']) : '';
        foreach ($qrcode_list as $k => &$v) {
            if (!$app_type || !in_array($app_type, ['android', 'ios'])) {
                //todo 百度地图坐标转腾讯地图
                $gcjLongLat = (new longLat())->baiduToGcj02($v['lat'], $v['long']);
                $v['lat'] = (string)$gcjLongLat['lat'];
                $v['long'] = (string)$gcjLongLat['lng'];
            }

            $v['qrcode_id'] = $v['id'];
            //$v['record_name'] = $v['num_id'].$v['name'];
            $v['record_name'] = $v['name'];
            unset($v['add_time']);
            unset($v['image_path']);
            unset($v['entication_field']);
            $c_where = [];
            $c_where[] = ['id', '=', $cate_id];
            $qrcode_cate = $dbWisdomQrcodeCate->getFind($c_where);

//            $q_where = [];
//            $q_where[] = ['uid','=',$house_worker['wid']];
//            $q_where[] = ['cate_id','=',$v['cate_id']];
//            $qrcode_person = $dbWisdomQrcodePerson->getFind($q_where);
            if ($qrcode_cate && empty($qrcode_cate['start_time'])) {
                $qrcode_cate['start_time'] = '00:00';
            }
            if ($qrcode_cate && empty($qrcode_cate['end_time'])) {
                $qrcode_cate['end_time'] = '23:59';
            }
            $where = [];
            $where[] = ['qrcode_id', '=', $v['id']];
            $where[] = ['cate_id', '=', $cate_id];

            $dates = date('Y-m-d', time());
            $start_time = strtotime($dates . ' ' . $qrcode_cate['start_time']);
            $end_time = strtotime($dates . ' ' . $qrcode_cate['end_time']);
            $where[] = ['add_time', 'between', [$start_time, $end_time]];
            $where[] = array('village_id', '=', $village_id);;
            if (!isset($qrcodeCate['type']) || $qrcodeCate['type'] != 2) {
                $where[] = ['uid', '=', $house_worker['wid']];
            }
            if ($qrcode_cate['record_type'] == 1) {
                //每日
                $record_log_count = $dbWisdomQrcodeRecordLog->getCount($where);
                $record_log_counts += $record_log_count;
                if ($record_log_count) {
                    $v['desc'] = '已记录';
                    $v['status'] = 1;
                    $count = $qrcode_count - $record_log_count;
//                    if($count > 0){
//                        $qrcode_uncount = 0;
//                    }else{
//                        $qrcode_uncount = $qrcode_count;
//                    }
                    $qrcode_uncount++;
                } else {
                    $v['desc'] = '待完成';
                    $v['status'] = 2;
//                    $qrcode_uncount = ($qrcode_count-$record_log_counts)>0?($qrcode_count-$record_log_counts):0;
                }
            }
            elseif ($qrcode_cate['record_type'] == 2) {
                //每周
                $record_time = explode(',', $qrcode_cate['record_time']);
                $dates = date('w', time());
                if (in_array($dates, $record_time)) {
                    $record_log_count = $dbWisdomQrcodeRecordLog->getCount($where);
                    if ($record_log_count) {
                        if ($record_log_count <= 0) {
                            $v['desc'] = '待完成';
                            $v['status'] = 2;
//                            $qrcode_uncount += 0;
                        } else {
                            $v['desc'] = '已记录';
                            $v['status'] = 1;
                            $qrcode_uncount++;
                            $count = $qrcode_count - $record_log_count;
//                            if($count > 0){
//                                $qrcode_uncount += $count;
//                            }else{
//                                $qrcode_uncount += $qrcode_count;
//                            }
                        }

                    } else {
                        $v['desc'] = '待完成';
                        $v['status'] = 2;
//                        $qrcode_uncount += 0;
                    }
                } else {
                    $v['desc'] = '待完成';
                    $v['status'] = 2;
                    //$qrcode_uncount += 0;
                }
            }
            elseif ($qrcode_cate['record_type'] == 3) {
                //每月
                $record_time = explode(',', $qrcode_cate['record_time']);
                $dates = date('d', time());
                if (in_array($dates, $record_time)) {
                    $record_log_count = $dbWisdomQrcodeRecordLog->getCount($where);
                    if ($record_log_count) {
                        if ($record_log_count <= 0) {
                            $v['desc'] = '待完成';
                            $v['status'] = 2;
//                            $qrcode_uncount += 0;
                        } else {
                            $v['desc'] = '已记录';
                            $v['status'] = 1;
                            $count = $qrcode_count - $record_log_count;
                            $qrcode_uncount++;
//                            if($count > 0){
//                                $qrcode_uncount += $count;
//                            }else{
//                                $qrcode_uncount += $qrcode_count;
//                            }
                        }

                    } else {
                        $v['desc'] = '待完成';
                        $v['status'] = 2;
//                        $qrcode_uncount += 0;
                    }

                } else {
                    $v['desc'] = '待完成';
                    $v['status'] = 2;
                    //$qrcode_uncount += 0;

                }
            }
            unset($v['id']);
        }
        unset($v);
        $data['qrcode_list'] = $qrcode_list;
        $data['qrcode_count'] = $qrcode_count;
        $data['qrcode_uncount'] = $qrcode_uncount;
        $is_xunjian_position = $this->checkWisdomQrcodePerson($wid, $cate_id);
        $data['is_xunjian_position'] = $is_xunjian_position ? 1 : 0;
        $result = $this->positionConfig;
        $number = $this->positionCountIndexMethod($village_id, $cate_id, $wid, 0);
        $result['number'] = $result['number'] - $number;
        $result['index_id'] = 0;
        $data['qrcode_config'] = $result;
        return $data;
    }

    //经纬度算距离
    public function getDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2)
    {
        $EARTH_RADIUS = 6378.137;//地球半径
        $PI = 3.1415926;

        $radLat1 = $lat1 * $PI / 180.0;
        $radLat2 = $lat2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * $PI / 180.0) - ($lng2 * $PI / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s * 1000);

        if ($len_type > 1) {
            $s /= 1000;
        }
        return round($s, $decimal);
    }

    /**
     * Notes: 设备详情
     * @param $qrcode_id
     * @param $village_id
     * @param $wid
     * @param $lat
     * @param $lng
     * @param $map_type
     * @return array
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/3 17:40
     */
    public function equipment($qrcode_id, $village_id, $wid, $phone, $lat, $lng, $map_type)
    {
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseWorker = new HouseWorker();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $dbWisdomQrcode = new WisdomQrcode();
        $dbWisdomQrcodeFieldCate = new WisdomQrcodeFieldCate();
        $dbWisdomQrcodeCateFieldCateConfig = new WisdomQrcodeCateFieldCateConfig();

        //$fields = "(2 * 6378.137 * ASIN(	SQRT(POW( SIN( PI( ) * ( " . $lng . "- q.long ) / 360 ), 2 ) + COS( PI( ) * " .$lat . " / 180 ) * COS(  q.lat * PI( ) / 180 ) * POW( SIN( PI( ) * ( " .$lat. "- q.lat ) / 360 ), 2 )))) AS distance";
        $field = 'round( ACOS(SIN((' . $lat . ' * 3.1415) / 180 ) *SIN((q.lat * 3.1415) / 180 ) +COS((' . $lat . ' * 3.1415) / 180 ) * COS((q.lat * 3.1415) / 180 ) *COS((' . $lng . ' * 3.1415) / 180 - (q.long * 3.1415) / 180 ) ) * 6380,2) as distance';
        $q_where[] = ['id', '=', $qrcode_id];
        $wisdom_qrcode = $dbWisdomQrcode->getFind($q_where, $field . ',q.*', 'q');
        $distances = $this->getDistance($lng, $lat, $wisdom_qrcode['long'], $wisdom_qrcode['lat']);
//        $wisdom_qrcode['distance'] = sprintf('%.2f',$wisdom_qrcode['distance']);
        if (empty($wisdom_qrcode)) {
            throw new \think\Exception("二维码不存在");
        }
//        $cate_village_id = $dbWisdomQrcodeCate->getValues(['id' => $wisdom_qrcode['cate_id']], 'village_id');
//        if ($cate_village_id != $village_id) {
//            throw new \think\Exception("此二维码不属于当前登录小区");
//        }

        $c_where[] = ['id', '=', $wisdom_qrcode['cate_id']];
        $distance = $dbWisdomQrcodeCate->getValues($c_where, 'distance');
//        $distance = 100;
        $wisdom_qrcode['qr_distance'] = $distance;
//        if(($wisdom_qrcode['distance'] > $distance && $distance != 0)){

//        if (($distances > $distance && $distance != 0)) {
//            $res['code'] = '-1';
//            throw new \think\Exception("您超出了工作范围");
//        }

        $village_user_bind[] = ['village_id', '=', $village_id];
        $village_user_bind[] = ['type', '=', 4];
        $village_user_bind[] = ['phone', '=', $phone];
        $bind_info = $dbHouseVillageUserBind->getOne($village_user_bind);
        if (empty($bind_info)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $worker_where[] = ['village_id', '=', $bind_info['village_id']];
        $worker_where[] = ['phone', '=', $bind_info['phone']];
        $worker_where[] = ['status', '=', 1];
        $worker_where[] = ['is_del', '=', 0];
        $house_worker = $dbHouseWorker->getOne($worker_where);
        if (empty($house_worker)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $p_where[] = ['uid', '=', $house_worker['wid']];
        $p_where[] = ['cate_id', '=', $wisdom_qrcode['cate_id']];
        $qrcode_person = $dbWisdomQrcodePerson->getFind($p_where);
        $arr = [];
        if ($map_type == 1) {
            if ($qrcode_person) {
                if ($qrcode_person['status'] == 0) {
                    $arr['map_type'] = 0;
                } else {
                    $arr['map_type'] = 1;
                }
            }
        } else {
            if ($qrcode_person) {
                if ($qrcode_person['status'] == 0) {
                    $arr['map_type'] = 0;
                } else {
                    $arr['map_type'] = 1;
                }
            } else {
                //是用户
                $arr['map_type'] = 0;
            }
        }

        $wisdom_qrcode['image_path'] = replace_file_domain($wisdom_qrcode['image_path']);
        $wisdom_qrcode['add_time'] = date('Y-m-d', $wisdom_qrcode['add_time']);
        $wisdom_qrcode['entication_field'] = $wisdom_qrcode['entication_field'] ? unserialize($wisdom_qrcode['entication_field']) : [];
        $cate_id = $wisdom_qrcode['cate_id'];
        $f_where[] = ['cate_id', '=', $cate_id];
        $dataList = $dbWisdomQrcodeFieldCate->getList($f_where);
        if ($dataList) {
            foreach ($dataList as $key => &$val) {
                unset($val['add_time']);
                unset($val['sort']);
                $config_where = [];
                $config_where[] = ['field_cate_id', '=', $val['id']];
                $val['field_cate_test'] = $dbWisdomQrcodeCateFieldCateConfig->getList($config_where);
                foreach ($val['field_cate_test'] as &$v) {
                    unset($v['acid']);
                    unset($v['add_time']);
                    unset($v['field_cate_id']);
                    unset($v['field_sort']);
                    if ($v['type'] == 2) {
                        $v['value_test'] = isset($wisdom_qrcode['entication_field'][$v['key']]['value'][0]) ? replace_file_domain($wisdom_qrcode['entication_field'][$v['key']]['value'][0]) : '';
                    } else {
                        $v['value_test'] = isset($wisdom_qrcode['entication_field'][$v['key']]['value'][0]) ? $wisdom_qrcode['entication_field'][$v['key']]['value'][0] : '';
                    }
                }
            }
        }

        $arr['wisdom_qrcode'] = $wisdom_qrcode;
        $arr['qrcode_list'] = $dataList;
        $arr['code'] = '0';
        return $arr;
    }

    /**
     * Notes: 记录详情==>页面==>数据添加
     * @param $qrcode_id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/3 18:09
     */
    public function addRecord($qrcode_id, $village_id = 0, $wid = 0,$cate_id = 0)
    {
        $dbWisdomQrcode = new WisdomQrcode();
        $dbWisdomQrcodeRecordCate = new WisdomQrcodeRecordCate();
        $dbWisdomQrcodeRecordCateFieldConfig = new WisdomQrcodeRecordCateFieldConfig();
        $configCustomizationService = new ConfigCustomizationService();
        $sqAeondeLightJiangsuCustomized_judge = $configCustomizationService->getSqAeondeLightJiangsuCustomized();
        $q_where[] = ['id', '=', $qrcode_id];
        $qrcode = $dbWisdomQrcode->getFind($q_where);
        if (empty($qrcode)) {
            throw new \think\Exception("二维码不存在");
        }
        if (empty($qrcode['category_id'])) {
            throw new \think\Exception("二维码分类不存在");
        }

//        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
//        $whereArr = array('id' => $qrcode['cate_id']);
//        if ($village_id > 0) {
//            $whereArr['village_id'] = $village_id;
//        }
//        $qrcodeCate = $dbWisdomQrcodeCate->getFind($whereArr);
//        if ($qrcodeCate && !$qrcodeCate->isEmpty()) {
//            $village_id = $qrcodeCate['village_id'];
//        } else {
//            throw new \think\Exception("二维码类别不存在");
//        }

        $to_repair_btn = 0;
        if ($sqAeondeLightJiangsuCustomized_judge) {
            $houseNewRepairWorksOrder = new HouseNewRepairWorksOrder();
            $to_repair_btn = 1;
            $max_time = time() + 100;
            $min_time_date = date('Y-m-d') . ' 00:00:00';
            $min_time = strtotime($min_time_date) - 10;
            $whereRepairArr = array(['village_id', '=', $village_id]);
            $whereRepairArr[] = array('wisdomqrcode_id', '=', $qrcode_id);
            $whereRepairArr[] = array('uid', '=', $wid);
            $whereRepairArr[] = array('order_type', 'in', array(3, 4));
            $whereRepairArr[] = array('add_time', '>=', $min_time);
            $whereRepairArr[] = array('add_time', '<=', $max_time);
            $repairWorksOrder = $houseNewRepairWorksOrder->getOne($whereRepairArr);
            if ($repairWorksOrder && !$repairWorksOrder->isEmpty()) {
                $to_repair_btn = 0;
            }
        }
        $c_where[] = ['category_id', '=', $qrcode['category_id']];
        $c_where[] = ['status', '=', 1];
        $dataList = $dbWisdomQrcodeRecordCate->getList($c_where, true, 'sort desc,id desc');
        if ($dataList) {
            foreach ($dataList as $k => &$v) {
                $fc_where = [];
                $fc_where[] = ['record_cate_id', '=', $v['id']];
                $record_cate_field_config = $dbWisdomQrcodeRecordCateFieldConfig->getList($fc_where, true, 'sort desc,acid desc');
                if ($record_cate_field_config) {
                    foreach ($record_cate_field_config as $ks => &$vs) {
                        $vs['value'] = '';
                        if ($vs['type'] == 3) {
                            $vs['use_field'] = explode(',', $vs['use_field']);
                        } else {
                            $vs['use_field'] = [];
                        }
                        unset($vs['acid']);
                        unset($vs['add_time']);
                        unset($vs['record_cate_id']);
                        unset($vs['sort']);
                    }
                    $v['record_cate_field_config'] = $record_cate_field_config;
                }
                unset($v['id']);
                unset($v['add_time']);
                unset($v['sort']);
                unset($v['status']);
                unset($v['cate_id']);
            }

            //手动添加一个点位的二维码
            $field_config_fixed = [
                'record_name'=>'异常情况',
                'category_id'=>'9999',
                'record_cate_field_config'=>[[
                    'is_must' => 1,
                    'key' => 'FIXED_POINT_IS_ABNORMALITY',
                    'title' => '点位是否存在异常情况',
                    'type' => 3,
                    'use_field' => ["是", "否"],
                    'value' => 0,
                ]]
            ];
            $dataList[] = $field_config_fixed;
        }
        $arr = array();
        $arr['to_repair_btn'] = $to_repair_btn;
        $arr['wisdom_qrcode_set'] = 1;
        $houseVillageInfoDb = new HouseVillageInfo();
        if ($village_id > 0) {
            $villageInfoWhere = array('village_id' => $village_id);
            $villageInfoData = $houseVillageInfoDb->getOne($villageInfoWhere, 'village_id,property_id,wisdom_qrcode_set');
            if ($villageInfoData && !$villageInfoData->isEmpty()) {
                $arr['wisdom_qrcode_set'] = $villageInfoData['wisdom_qrcode_set'];
            }
        }
        $arr['cate_id'] = $qrcode['cate_id'];
        $arr['task_list'] = $dataList;
        return $arr;
    }

    /**
     * Notes:添加qrcode类别
     * @param $village_id
     * @param $wid
     * @param $phone
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/4 17:48
     */
    public function qrCodeCate($village_id, $wid, $phone)
    {
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseWorker = new HouseWorker();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $village_user_bind[] = ['village_id', '=', $village_id];
        $village_user_bind[] = ['type', '=', 4];
        $village_user_bind[] = ['phone', '=', $phone];
        $bind_info = $dbHouseVillageUserBind->getOne($village_user_bind);
        if (empty($bind_info)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $worker_where[] = ['village_id', '=', $bind_info['village_id']];
        $worker_where[] = ['phone', '=', $bind_info['phone']];
        $worker_where[] = ['status', '=', 1];
        $worker_where[] = ['is_del', '=', 0];
        $house_worker = $dbHouseWorker->getOne($worker_where);
        if (empty($house_worker)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $p_where[] = ['uid', '=', $house_worker['wid']];
        $qrcode_person = $dbWisdomQrcodePerson->getList($p_where);

        $cate = [];
        foreach ($qrcode_person as $k => $v) {
            $cate[] = $v['cate_id'];
        }
//        $cate_where[] = ['id','in',$cate];
        $cate_where[] = ['village_id', '=', $village_id];
        $dataList = $dbWisdomQrcodeCate->getLists($cate_where);
//        dd($dataList);
        $cate_list = [];
        foreach ($dataList as $k => $value) {
            $list['cate_name'] = $value['cate_name'];
            $list['cate_id'] = $value['id'];
            $list['village_id'] = $value['village_id'];
            $cate_list[] = $list;
        }
        $test_cate_list = array(
            'cate_name' => '全部',
            'cate_id' => '',
            'village_id' => ''
        );
        array_unshift($cate_list, $test_cate_list);
        $arr['cate_list'] = $cate_list;
        return $arr;
    }

    /**
     * Notes:用户查看记录
     * @param $qrcode_id
     * @param $page
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/4 17:50
     */
    public function getRecord($qrcode_id, $page, $app_type, $add_time = '', $cate_id = 0)
    {
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $dbWisdomQrcode = new WisdomQrcode();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbHouseWorker = new HouseWorker();
        $log_where[] = ['qrcode_id', '=', $qrcode_id];
        if ($add_time) {
            $start_time = strtotime($add_time . ' 00:00:00');
            $end_time = strtotime($add_time . ' 23:59:59');
            $log_where[] = ['add_time', 'between', [$start_time, $end_time]];
        }
        if (!empty($cate_id)) {
            $log_where[] = ['cate_id', '=', $cate_id];
        }
        $record_log_list = $dbWisdomQrcodeRecordLog->getList($log_where, true, 'id desc', $page, 10);
        $record_log_count = $dbWisdomQrcodeRecordLog->getCount($log_where);
        foreach ($record_log_list as $k => &$v) {
            $map = [];
            $map[] = ['id', '=', $v['cate_id']];
            $qrcode_cate = $dbWisdomQrcodeCate->getFind($map);
            $v['cate_name'] = $qrcode_cate['cate_name'];
            $map = [];
            $map[] = ['id', '=', $v['qrcode_id']];
            $qrcode = $dbWisdomQrcode->getFind($map);
            $v['qrcode_name'] = $qrcode['name'];
            $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            $map = [];
            $map[] = ['uid', '=', $v['uid']];
            $map[] = ['cate_id', '=', $v['cate_id']];
            $qrcode_person = $dbWisdomQrcodePerson->getFind($map);
            $map = [];
            $map[] = ['phone', '=', $qrcode_person['user_phone']];
            $map[] = ['village_id', '=', $qrcode_cate['village_id']];
            $map[] = ['is_del', '=', 0];
            $hosue_worker = $dbHouseWorker->getOne($map);
            $v['worker_name'] = $hosue_worker['name'];
            if ($v['entication_field']) {
                $entication_field = unserialize($v['entication_field']);
                foreach ($entication_field['test'] as &$vs) {
                    if (array_key_exists('record_cate_field_config', $vs) && $vs['record_cate_field_config']) {
                        foreach ($vs['record_cate_field_config'] as &$k_value) {
                            if ($k_value['type'] == 2) {
                                if (is_array($k_value['value'])) {
                                    $img_value = [];
                                    foreach ($k_value['value'] as $value) {
                                        $img_value[] = replace_file_domain($value);
                                    }
                                    $k_value['value'] = $img_value;
                                    if ($app_type <> 'packapp') {
                                        $k_value['value_img'] = $img_value;
                                    }
                                } else {
                                    $k_value['value'] = replace_file_domain($k_value['value']);
                                    if ($app_type <> 'packapp') {
                                        $k_value['value_img'] = replace_file_domain($k_value['value']);
                                    }
                                }
                            }
                            if ($k_value['type'] == 1 && $app_type <> 'packapp') {
                                $k_value['value_text'] = $k_value['value'];
                            }
                            if ($k_value['type'] == 3 && $app_type <> 'packapp') {
                                $k_value['value_choose'] = $k_value['value'];
                            }
                        }
                    }
                }
                $v['entication_field'] = $entication_field['test'];
            } else {
                $v['entication_field'] = [];
            }
        }
        $arr['record_log_list'] = $record_log_list;
        $arr['record_log_count'] = $record_log_count;
        return $arr;
    }

    /***
     **获取一条二维码提交详细数据
     ***/
    public function getOneQrcodeRecord($whereArr, $field = '*')
    {
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $oneQrcodeRecord = $dbWisdomQrcodeRecordLog->getOneData($whereArr, $field);
        if ($oneQrcodeRecord && !$oneQrcodeRecord->isEmpty()) {
            $oneQrcodeRecord = $oneQrcodeRecord->toArray();
            $entication_field = !empty($oneQrcodeRecord['entication_field']) ? unserialize($oneQrcodeRecord['entication_field']) : '';
            unset($oneQrcodeRecord['entication_field']);
            $fieldContent = array('text' => array(), 'imgs' => array(), 'text_str' => '');
            if (!empty($entication_field)) {
                if (isset($entication_field['test'])) {
                    $entication_field = $entication_field['test'];
                }
                foreach ($entication_field as $fkk => $fvv) {
                    if (isset($fvv['record_cate_field_config'])) {
                        foreach ($fvv['record_cate_field_config'] as $rkk => $rvv) {
                            if ($rvv['type'] == 2) {
                                if (is_array($rvv['value'])) {
                                    foreach ($rvv['value'] as $k3 => $v3) {
                                        if (is_string($v3)) {
                                            $imgs = array('url' => replace_file_domain($v3), 'path' => $v3);
                                            $fieldContent['imgs'][] = $imgs;
                                        }
                                    }
                                } else {
                                    $imgs = array('url' => replace_file_domain($rvv['value']), 'path' => $rvv['value']);
                                    $fieldContent['imgs'][] = $imgs;
                                }
                            } elseif ($rvv['type'] == 1) {
                                $fieldContent['text'][] = $rvv['value'];
                                $fieldContent['text_str'] .= $rvv['value'] . '，';
                            }
                        }
                    }
                }
                $fieldContent['text_str'] = rtrim($fieldContent['text_str'], '，');
                $oneQrcodeRecord['entication_field'] = $entication_field;
                $oneQrcodeRecord['field_content'] = $fieldContent;
                return $oneQrcodeRecord;
            }
        }
        return array();
    }

    /**
     * Notes:工作人员查看记录
     * @param $village_id
     * @param $wid
     * @param $phone
     * @param $page
     * @param $add_time
     * @param $cate_id
     * @param $qrcode_id
     * @return mixed
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/5 16:07
     */
    public function getRecordList($village_id, $wid = 0, $phone, $page, $add_time, $cate_id, $qrcode_id = 0)
    {
        //增加工作人员判断
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbHouseWorker = new HouseWorker();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $dbWisdomQrcode = new WisdomQrcode();
        $village_user_bind[] = ['village_id', '=', $village_id];
        $village_user_bind[] = ['type', '=', 4];
        $village_user_bind[] = ['phone', '=', $phone];
        $bind_info = $dbHouseVillageUserBind->getOne($village_user_bind);
        if (empty($bind_info)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $worker_where[] = ['village_id', '=', $bind_info['village_id']];
        if ($wid > 0) {
            $worker_where[] = ['wid', '=', $wid];
        } else {
            $worker_where[] = ['phone', '=', $bind_info['phone']];
        }
        $worker_where[] = ['status', '=', 1];
        $worker_where[] = ['is_del', '=', 0];
        $house_worker = $dbHouseWorker->getOne($worker_where);
        if (empty($house_worker)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $where = [];
        $where[] = ['uid', '=', $house_worker['wid']];
        if ($add_time) {
            $start_time = strtotime($add_time . ' 00:00:00');
            $end_time = strtotime($add_time . ' 23:59:59');
            $where[] = ['add_time', 'between', [$start_time, $end_time]];
        }
        if (!empty($cate_id)) {
            $where[] = ['cate_id', '=', $cate_id];
        }
        if ($qrcode_id) {
            $where[] = ['qrcode_id', '=', $qrcode_id];
        }
        $record_log_list = $dbWisdomQrcodeRecordLog->getList($where, true, 'id desc', $page, 10)->toArray();
        $record_log_count = $dbWisdomQrcodeRecordLog->getCount($where);

        foreach ($record_log_list as $k => &$v) {
            $qrcode_cate = $dbWisdomQrcodeCate->getFind(['id' => $v['cate_id']]);
            $v['cate_name'] = $qrcode_cate['cate_name'];
            $qrcode = $dbWisdomQrcode->getFind(['id' => $v['qrcode_id']]);
            $v['qrcode_name'] = $qrcode['name'];
            $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            $qrcode_person = $dbWisdomQrcodePerson->getFind(['uid' => $v['uid'], 'cate_id' => $v['cate_id']]);
            $hosue_worker = $dbHouseWorker->getOne(['phone' => $qrcode_person['user_phone'], 'village_id' => $qrcode_cate['village_id'], 'is_del' => 0]);
            $v['worker_name'] = $hosue_worker['name'];
            if ($v['entication_field']) {
                $entication_field = unserialize($v['entication_field']);
                if (isset($entication_field['test'])) {
                    $entication_field = $entication_field['test'];
                    foreach ($entication_field as $ks => &$vs) {
                        if (array_key_exists('record_cate_field_config', $vs) && $vs['record_cate_field_config']) {
                            foreach ($vs['record_cate_field_config'] as $kk => &$vv) {
                                unset($vv['key']);
                                unset($vv['use_field']);
                                unset($vv['aricle_title']);
                                unset($vv['num']);
                                if ($vv['type'] == 2) {
                                    if (is_array($vv['value'])) {
                                        foreach ($vv['value'] as $k3 => $v3) {
                                            if (is_string($v3))
                                                $vv['image_value'][] = replace_file_domain($v3);
                                            else
                                                $vv['image_value'][] = [];
                                        }
                                        $vv['value'] = $vv['image_value'];
                                    } else {
                                        $vv['value'] = replace_file_domain($vv['value']);
                                    }
                                } else {
                                    $vv['value_text'] = $vv['value'];
                                    $vv['image_value'] = [];
                                }
                            }
                        }
                    }
                } else {
                    foreach ($entication_field as $ks => &$vs) {
                        if (isset($vs['record_cate_field_config'])) {
                            foreach ($vs['record_cate_field_config'] as $kk => &$vv) {
                                unset($vv['key']);
                                unset($vv['use_field']);
                                unset($vv['aricle_title']);
                                unset($vv['num']);
                                if ($vv['type'] == 2) {
                                    if (is_array($vv['value'])) {
                                        foreach ($vv['value'] as $k3 => $v3) {
                                            $vv['image_value'][] = replace_file_domain($v3);
                                        }
                                        $vv['value'] = $vv['image_value'];
                                    } else {
                                        $vv['value'] = replace_file_domain($vv['value']);
                                    }
                                } else {
                                    $vv['image_value'] = [];
                                }
                            }
                        }
                    }
                }
                if (isset($entication_field['test'])) {
                    $v['entication_field'] = $entication_field['test'];
                } else {
                    $v['entication_field'] = $entication_field;
                }
            } else {
                $v['entication_field'] = [];
            }
        }
        $arr['record_log_list'] = $record_log_list;
        $arr['record_log_count'] = $record_log_count;
        return $arr;
    }

    /**
     * Notes:添加记录
     * @param $village_id
     * @param $wid
     * @param $phone
     * @param $cate_id
     * @param $qrcode_id
     * @param $post
     * @return int|string
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/5 16:07
     */
    public function postRecord($village_id, $wid = 0, $phone, $cate_id, $qrcode_id, $post)
    {
        $dbHouseWorker = new HouseWorker();
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        //新增经纬度参数传参
        if (!isset($post['lat']) || empty($post['lat']) || !isset($post['lng']) || empty($post['lng'])) {
            throw new \think\Exception('定位失败');
        }
        $this->checkLegalDistance($post['lng'], $post['lat'], $qrcode_id);
        //增加工作人员判断
        $village_user_bind[] = ['village_id', '=', $village_id];
        $village_user_bind[] = ['type', '=', 4];
        $village_user_bind[] = ['phone', '=', $phone];
        $bind_info = $dbHouseVillageUserBind->getOne($village_user_bind);
        if (empty($bind_info)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $worker_where[] = ['village_id', '=', $bind_info['village_id']];
        if ($wid > 0) {
            $worker_where[] = ['wid', '=', $wid];
        } else {
            $worker_where[] = ['phone', '=', $bind_info['phone']];
        }
        $worker_where[] = ['status', '=', 1];
        $worker_where[] = ['is_del', '=', 0];
        $house_worker = $dbHouseWorker->getOne($worker_where);
        if (empty($house_worker)) {
            throw new \think\Exception("你不是本小区的工作的人员,请联系管理员添加!");
        }
        $qrcode_person = $dbWisdomQrcodePerson->getFind(['uid' => $house_worker['wid'], 'cate_id' => $cate_id]);
        if (empty($qrcode_person)) {
            throw new \think\Exception("你不是智慧二维码负责人!");
        }
        if ($qrcode_person['status'] != 1) {
            throw new \think\Exception("你的信息正在审核中!");
        }
        $data['village_id'] = $village_id;
        $data['cate_id'] = $cate_id;
        $data['qrcode_id'] = $qrcode_id;
        $data['uid'] = $house_worker['wid'];
        $data['add_time'] = time();
        unset($post['cate_id']);
        unset($post['village_id']);
        unset($post['qrcode_id']);
        unset($post['ticket']);
        unset($post['now_lang']);
        unset($post['wxapp_type']);
        unset($post['app_type']);
        unset($post['Device-Id']);
        unset($post['app_version']);
        unset($post['now_city']);
        unset($post['now_area']);
        unset($post['param_data']);
        if (!is_array($post['test'])) {
            $post['test'] = json_decode($post['test'], true);
        }
        foreach ($post['test'] as $k => $v) {
            if ($v['record_cate_field_config']) {
                foreach ($v['record_cate_field_config'] as $k1 => $v1) {
                    if ($v1['value'] == '' && $v1['is_must'] == 1) {
                        throw new \think\Exception($v1['title'] . '为必填项!');
                    }
                    if ($v1['type'] == 2 && $v1['value'] && is_array($v1['value'])) {
                        $newvalue = array();
                        foreach ($v1['value'] as $ivv) {
                            if ($ivv) {
                                $newvalue[] = $ivv;
                            }
                        }
                        $post['test'][$k]['record_cate_field_config'][$k1]['value'] = $newvalue;
                    }
                }
            }
        }
        $jz_wsxdsj_net_customized_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();
        if ($jz_wsxdsj_net_customized_open) {
            //记录已巡检次数
            $this->addPatrolInspectionCompletionNum($qrcode_id, $cate_id, $house_worker['wid'], $village_id);
        }

        $data['entication_field'] = serialize($post);
        $insert_id = $dbWisdomQrcodeRecordLog->addData($data);
        if ($qrcode_person && !$qrcode_person->isEmpty()) {
            $qrcode_person = $qrcode_person->toArray();
        }
        //todo 同步写入工单及时率
        $param = [
            'qrcode_person' => $qrcode_person,
            'log' => $data,
            'village_id' => $village_id,
            'uid' => $house_worker['wid'],
            'group_id' => $house_worker['department_id'],
            'worker_id' => $house_worker['wid'],
            'order_id' => $cate_id
        ];
        $this->addOrderTimely($param);
        return array('record_id' => $insert_id, 'wid' => $house_worker['wid']);
        //return $insert_id;
    }

    //添加巡检记录，校验经纬度
    public function checkLegalDistance($lng, $lat, $qrcode_id)
    {
        $q_where[] = ['id', '=', $qrcode_id];
        $field = 'round( ACOS(SIN((' . $lat . ' * 3.1415) / 180 ) *SIN((q.lat * 3.1415) / 180 ) +COS((' . $lat . ' * 3.1415) / 180 ) * COS((q.lat * 3.1415) / 180 ) *COS((' . $lng . ' * 3.1415) / 180 - (q.long * 3.1415) / 180 ) ) * 6380,2) as distance';
        $wisdom_qrcode = (new WisdomQrcode())->getFind($q_where, $field . ',q.*', 'q');
        if (!$wisdom_qrcode) {
            throw new \think\Exception('该数据不存在，请刷新页面');
        }
        $distances = $this->getDistance($lng, $lat, $wisdom_qrcode['long'], $wisdom_qrcode['lat']);
        $distance = (new WisdomQrcodeCate())->getValues([
            ['id', '=', $wisdom_qrcode['cate_id']]
        ], 'distance');
//        if ($distance && intval($distance) > 0 && $distances > $distance) {
//            throw new \think\Exception('您超出了工作范围');
//        }
        return true;
    }

    //todo 添加巡检 同步记录
    public function addOrderTimely($param)
    {
        $dbWisdomQrcode = new WisdomQrcode();
        $dbHouseNewRepairService = new HouseNewRepairService();
        fdump_api(['参数进来了===' . __LINE__, $param], 'timely/addOrderTimely');
        $time = time();
        //查询对应工作人员数据
        $person = $param['qrcode_person'];
        if (empty($person)) {
            fdump_api(['缺少工作人员数据===' . __LINE__, $param], 'timely/addOrderTimely');
            return true;
        }
        //查询该分类下所有二维码
        $Wisdom_qrcode = $dbWisdomQrcode->getColumn(['cate_id' => $param['log']['cate_id']], 'id');
        if (!$Wisdom_qrcode) {
            fdump_api(['暂无分类数据===' . __LINE__, $param], 'timely/addOrderTimely');
            return true;
        }
        sort($Wisdom_qrcode);
        //查询已经添加过的巡检记录
        $prefix = config('database.connections.mysql.prefix');
        $sql = 'SELECT
                * 
            FROM (
                SELECT
                    * 
                FROM
                    `' . $prefix . 'wisdom_qrcode_record_log` 
                WHERE
                    ( `village_id` = ' . $param['village_id'] . ' ) 
                    AND ( `uid` = ' . $param['uid'] . ' ) 
                    AND ( `qrcode_id` IN ( ' . (implode(",", $Wisdom_qrcode)) . ' ) ) 
                ORDER BY
                    id DESC 
                ) a 
            GROUP BY
                a.qrcode_id 
            ORDER BY
	            a.id DESC ';
        $record_log = Db::query($sql);
        fdump_api(['sql===' . __LINE__, $sql, $param, $record_log], 'timely/addOrderTimely');
        if (empty($record_log)) {
            fdump_api(['暂无添加过的巡检记录===' . __LINE__, $sql, $param], 'timely/addOrderTimely');
            return true;
        }
        $record_id=[];
        foreach ($record_log as $v){
            $record_id[]=$v['qrcode_id'];
        }
        sort($record_id);
        if ($Wisdom_qrcode !== $record_id) {
            fdump_api(['巡检记录未添加完===' . __LINE__, $Wisdom_qrcode, $record_id, $param], 'timely/addOrderTimely');
            return true;
        }
        $start_time = $end_time = '';

        $record_time = empty($person['record_time']) ? [] : explode(',', $person['record_time']);
        $where2 = [];
        $where2[] = ['type', '=', 2];
        $where2[] = ['record_type', '=', $person['record_type']];
        $where2[] = ['village_id', '=', $param['village_id']];
        $where2[] = ['order_id', '=', $param['order_id']];
        $where2[] = ['worker_id', '=', $param['worker_id']];
        $startDate = $endDate = '';
        if ($person && $person['start_time']) {
            $person['start_time'] = '00:00';
        }
        if ($person && $person['end_time']) {
            $person['end_time'] = '23:59';
        }
        switch (intval($person['record_type'])) {
            case 1: //每日
                $start_time = date('Y-m-d ' . $person['start_time']);
                $end_time = date('Y-m-d ' . $person['end_time']);

                //当天
                $startDate = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                $endDate = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
                break;
            case 2: //每周
                if (empty($record_time)) {
                    fdump_api(['$record_time为空===' . __LINE__, $Wisdom_qrcode, $record_id, $param, $record_time], 'timely/addOrderTimely');
                    return true;
                }
                if (!in_array(date('N', $time), $record_time)) {
                    return true;
                }
                $start_time = date('Y-m-d ' . $person['start_time']);
                $end_time = date('Y-m-d ' . $person['end_time']);

                //本周
                $w = date("w");
                $startDate = mktime(0, 0, 0, date("m"), date("d") - $w + 1, date("Y"));
                $endDate = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('Y'));

                break;
            case 3: //每月
                if (empty($record_time)) {
                    return true;
                }
                if (!in_array(date('d', $time), $record_time)) {
                    return true;
                }
                $start_time = date('Y-m-d ' . $person['start_time']);
                $end_time = date('Y-m-d ' . $person['end_time']);

                //本月
                $startDate = mktime(0, 0, 0, date("m"), 1, date("Y"));
                $endDate = mktime(23, 59, 59, date('m'), date('t'), date('Y')) - 1;
                break;
        }
        if (empty($start_time) || empty($end_time)) {
            fdump_api(['时间不对===' . __LINE__, $start_time, $end_time, $param], 'timely/addOrderTimely');
            return true;
        }
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        $where2[] = ['add_time', 'between', [$startDate, $endDate]];
        $res = $dbHouseNewRepairService->getOrderTimelyOne($where2, 'id');
        if ($res && !$res->isEmpty()) {
            fdump_api(['数据已添加过===' . __LINE__, $start_time, $end_time, $param, $where2, $res], 'timely/addOrderTimely');
            return true;
        }
        if ($record_log[0]['add_time'] >= $start_time && $record_log[0]['add_time'] <= $end_time) {
            $is_status = 2;
        } else {
            $is_status = 1;
        }
        $dd = [
            'village_id' => $param['village_id'],
            'type' => 2,
            'record_type' => $person['record_type'],
            'order_id' => $param['order_id'],
            'group_id' => $param['group_id'],
            'worker_id' => $param['worker_id'],
            'is_status' => $is_status,
            'add_time' => $time
        ];
        return $dbHouseNewRepairService->addOrderTimely($dd);
    }


    //todo 生成随机颜色
    function get_color_by_scale()
    {
        $str = '0123456789ABCDEF';
        $estr = '';
        $len = strlen($str);
        for ($i = 1; $i <= 6; $i++) {
            $num = rand(0, $len - 1);
            $estr = $estr . $str[$num];
        }

        $estr = $this->changeColor($estr, "no");
        return $estr;
    }

    //hex颜色加深减淡
    function changeColor($hex, $type = 'no')
    {
        $level = "0.9"; //level为加深的程度，限0-1之间
        $diycolor = '#b4e0e1';
        if ($hex < 0 || hexdec($hex) > hexdec('ffffff')) {
            $hex = $diycolor;
        }
        $rgb = $this->hexToRgb($hex);
        if ($type == '-') {     //减淡
            for ($i = 0; $i < 3; $i++) {
                $re[$i] = floor((255 - $rgb[$i]) * $level + $rgb[$i]);
            }
            $re = rgbToHex($re);
        } elseif ($type == '+') {    //加深
            for ($i = 0; $i < 3; $i++) {
                $re[$i] = floor($rgb[$i] * (1 - $level));
            }
            $re = rgbToHex($re);
        } else {
            $re = $hex;
        }
        return $re;
    }

    //hex颜色转RGB
    function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        $rgb[0] = hexdec($hex[0] . $hex[1]);
        $rgb[1] = hexdec($hex[2] . $hex[3]);
        $rgb[2] = hexdec($hex[4] . $hex[5]);
        return $rgb;
    }


    function randColor()
    {
        $colors = array();
        for ($i = 0; $i < 6; $i++) {
            $colors[] = dechex(rand(0, 15));
        }
        return implode('', $colors);
    }

    //todo 查询定位记录
    public function getPositionRecord($type, $where = [], $order = 'id asc', $page = 0, $limit = 10)
    {
        $data = [];
        $prefix = config('database.connections.mysql.prefix');
        $dbWisdomQrcodePositionIndex = new WisdomQrcodePositionIndex();
        $dbWisdomQrcodePositionRecord = new WisdomQrcodePositionRecord();
        $longLat = new longLat();
        $where[] = ['i.del_time', '=', 0];
        if ($type == 2) {
            $start_time = strtotime(date('Y-m-d 00:00:00'));
            $end_time = strtotime(date('Y-m-d 23:59:59'));
            $where[] = ['add_time', 'between', [$start_time, $end_time]];
        }
        $field = 'i.id as index_id,i.cate_id,(SELECT count(r.id) FROM ' . $prefix . 'wisdom_qrcode_position_record as r WHERE r.index_id=i.id) as num,w.name,i.add_time';
        $index_list = $dbWisdomQrcodePositionIndex->getIndexList($where, $field, 'num > 0', $order, $page, $limit);
        if (!$index_list || $index_list->isEmpty()) {
            return $data;
        }
        $index_list = $index_list->toArray();
        $field = 'id,index_id,long,lat';
        $record_list = $dbWisdomQrcodePositionRecord->getList([
            ['index_id', 'in', (array_column($index_list, 'index_id'))],
            ['del_time', '=', 0]
        ], $field, 'id asc');
        if ($record_list && !$record_list->isEmpty()) {
            $record_list = $record_list->toArray();
        }
        $color = '#2A87EB';
        foreach ($index_list as $k1 => $v1) {
            $rr = [];
            if ($record_list) {
                foreach ($record_list as $k2 => $v2) {
                    if ($v1['index_id'] == $v2['index_id']) {
                        $gcjLongLat = $longLat->baiduToGcj02($v2['lat'], $v2['long']);
                        $rr[] = [
                            'bd_long' => $v2['long'],
                            'bd_lat' => $v2['lat'],
                            'tx_long' => $gcjLongLat['lng'],
                            'tx_lat' => $gcjLongLat['lat'],
                        ];
                    }
                }
            }
            if (count($rr) <= 1) {
                continue;
            }
            $data1 = [
                'trails' => $rr
            ];
            if ($type == 2) {
                $color = '#' . $this->randColor();
                $data1['name'] = $v1['name'];
                $data1['time'] = date('Y-m-d H:i:s', $v1['add_time']);
            }
            $data1['color'] = $color;
            $data[] = $data1;
        }
        return $data;
    }


    //todo 开启定位 写入记录
    public function positionAddIndexMethod($village_id, $cate_id, $wid)
    {
        $time = time();
        $dbWisdomQrcodePositionIndex = new WisdomQrcodePositionIndex();
        $param = [
            'village_id' => $village_id,
            'cate_id' => $cate_id,
            'wid' => $wid,
            'add_time' => $time
        ];
        $result = $dbWisdomQrcodePositionIndex->addFind($param);
        if (!$result) {
            throw new \think\Exception("写入记录，操作失败");
        }
        return $result;
    }

    //todo 开启定位 写入经纬度
    public function positionAddRecordMethod($type, $wid, $index_id, $long, $lat, $village_id = 0)
    {
        $time = time();
        $dbWisdomQrcodePositionIndex = new WisdomQrcodePositionIndex();
        $dbWisdomQrcodePositionRecord = new WisdomQrcodePositionRecord();
        $whereArr = [
            ['a.id', '=', $index_id],
            ['a.wid', '=', $wid],
            ['a.del_time', '=', 0],
        ];
        if ($village_id > 0) {
            $whereArr[] = ['a.village_id', '=', $village_id];
        }
        $info = $dbWisdomQrcodePositionIndex->getFinds($whereArr, 'a.id,a.village_id,a.cate_id,a.wid,a.status,b.cate_name,b.id as qrcode_cate_id,c.uid');
        if (!$info || $info->isEmpty()) {
            return ['record_id' => -1, 'info' => [], 'msg' => '巡检任务已删除，请重新设置巡检任务'];
        }
        if (!$info['qrcode_cate_id'] || !$info['uid']) {
            return ['record_id' => -1, 'info' => [], 'msg' => '巡检任务已删除，请重新设置巡检任务'];
        }
        $result = 0;
        $info = $info->toArray();
        if (intval($info['status']) == 0 && !empty($long) && !empty($lat)) {
            $param = [
                'index_id' => $info['id'],
                'village_id' => $info['village_id'],
                'cate_id' => $info['cate_id'],
                'wid' => $info['wid'],
                'long' => $long,
                'lat' => $lat,
                'add_time' => $time
            ];
            $result = $dbWisdomQrcodePositionRecord->addFind($param);
            if (!$result) {
                throw new \think\Exception("写入经纬度，操作失败");
            }
        }
        if ($type == 2) { //关闭经纬度
            $dbWisdomQrcodePositionIndex->editFind([
                ['id', '=', $info['id']]
            ], ['update_time' => $time, 'status' => 1]);
        }
        return ['record_id' => $result, 'info' => $info, 'msg' => ''];
    }

    //todo 校验当前上传定位巡检是否存在，不存在开启
    public function checkTaskPosition($village_id, $cate_id, $wid, $info, $param = [])
    {
        $index_id = 0;
        $checkWorker = $this->checkVillageHouseWorker($village_id, $wid);
        if (!empty($checkWorker) && $info['status'] == 1) { //当前上传点位巡检已结束，重新开启
            $dbWisdomQrcodePositionIndex = new WisdomQrcodePositionIndex();
            $whereArr = array();
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('cate_id', '=', $cate_id);
            $whereArr[] = array('wid', '=', $wid);
            $whereArr[] = array('id', '>', $info['id']);
            $whereArr[] = array('status', '=', 0);
            $tmpPositionIndex = $dbWisdomQrcodePositionIndex->getFind($whereArr);
            if ($tmpPositionIndex && !$tmpPositionIndex->isEmpty() && isset($tmpPositionIndex['id'])) {
                $result = [
                    'status' => $tmpPositionIndex['status'],
                    'index_id' => $tmpPositionIndex['id']
                ];
                return $result;
            }
            $index_id = $this->positionAddIndexMethod($village_id, $cate_id, $wid);
            $this->positionAddRecordMethod(1, $wid, $index_id, $param['long'], $param['lat'], $village_id);
        }
        $result = [
            'status' => $info['status'],
            'index_id' => $index_id
        ];
        return $result;
    }

    public function checkVillageHouseWorker($village_id = 0, $wid = 0)
    {
        $houseWorkerDb = new HouseWorker();
        if ($village_id > 0 && $wid > 0) {
            $whereArr = array();
            $whereArr[] = array('wid', '=', $wid);
            $whereArr[] = array('village_id', '=', $village_id);
            $whereArr[] = array('is_del', '<', 1);
            $whereArr[] = array('status', '<', 2);
            $houseWorkerObj = $houseWorkerDb->get_one($whereArr, 'wid,village_id,phone,name,openid,nickname,relation_id,xtype,account');
            if ($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
                $houseWorker = $houseWorkerObj->toArray();
                return $houseWorker;
            }
        }
        return '';
    }

    //todo 开启定位 查询记录次数
    public function positionCountIndexMethod($village_id, $cate_id, $wid, $status = 1)
    {
        return 0;//应产品要求不限制次数
        $dbWisdomQrcodePositionIndex = new WisdomQrcodePositionIndex();
        $date = date('Y-m-d');
        $start_time = strtotime($date . ' 00:00:00');
        $end_time = strtotime($date . ' 23:59:59');
        $result = $dbWisdomQrcodePositionIndex->getCounts([
            ['village_id', '=', $village_id],
            ['cate_id', '=', $cate_id],
            ['wid', '=', $wid],
            ['del_time', '=', 0],
            ['add_time', 'between', [$start_time, $end_time]]
        ]);
        return intval($result);
    }

    //todo 开启定位
    public function positionIndexStart($param)
    {
        $cate_info = (new WisdomQrcodeCate())->getFind([
            ['village_id', '=', $param['village_id']],
            ['id', '=', $param['cate_id']]
        ], 'cate_name');
        if (!$cate_info || $cate_info->isEmpty()) {
            throw new \think\Exception("该巡检不存在");
        }
        $result = $this->positionConfig;
        $number = $this->positionCountIndexMethod($param['village_id'], $param['cate_id'], $param['wid']);
        if ($number >= $result['number']) {
            throw new \think\Exception('巡检任务每天仅可开启' . $result['number'] . '次定位，今天已开启' . $number . '次');
        }
        $checkWorker = $this->checkVillageHouseWorker($param['village_id'], $param['wid']);
        $index_id = 0;
        if (!empty($checkWorker)) {
            $index_id = $this->positionAddIndexMethod($param['village_id'], $param['cate_id'], $param['wid']);
            $this->positionAddRecordMethod(1, $param['wid'], $index_id, $param['long'], $param['lat'], $param['village_id']);
        }
        $result['number'] = $result['number'] - ($number + 1);
        $result['index_id'] = (int)$index_id;
        $result['title'] = '【' . $cate_info['cate_name'] . '】巡检任务获取定位数据';
        $result['cate_id'] = $param['cate_id'];
        return $result;
    }

    //todo 写入/关闭经纬度 $type  1：写入  2：关闭
    public function positionIndexRecord($type, $param)
    {
        $result = $this->positionConfig;
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $cateWhere = array();
        $cateWhere[] = array('id', '=', $param['cate_id']);
        $cateWhere[] = array('village_id', '=', $param['village_id']);
        $qrcodeCate = $dbWisdomQrcodeCate->getFind($cateWhere);
        if (empty($qrcodeCate) || $qrcodeCate->isEmpty()) {
            $result['number'] = $result['number'];
            $result['is_current_task'] = 0;
            $result['cate_id'] = 0;
            $result['cate_name'] = '';
            $result['title'] = '';
            $result['index_id'] = 0;
            $result['task_msg'] = "二维码类别不存在了！";
            //throw new \think\Exception("二维码类别不存在！");
            return $result;
        }
        $number = $this->positionCountIndexMethod($param['village_id'], $param['cate_id'], $param['wid']);
        $record_info = $this->positionAddRecordMethod($type, $param['wid'], $param['index_id'], $param['long'], $param['lat'], $param['village_id']);
        $info = $record_info['info'];
        $result['number'] = $result['number'] - $number;
        if ((int)$record_info['record_id'] == -1) {
            $result['is_current_task'] = (int)$record_info['record_id'];
            $result['cate_id'] = 0;
            $result['cate_name'] = '';
            $result['title'] = '';
            $result['index_id'] = 0;
            $result['task_msg'] = $record_info['msg'];
        } else {
            $task_info = $this->checkTaskPosition($param['village_id'], $param['cate_id'], $param['wid'], $info, $param);
            $result['task_msg'] = 'ok';
            $result['is_current_task'] = (int)$task_info['status'];
            $result['cate_id'] = $info['cate_id'];
            $result['cate_name'] = $info['cate_name'];
            $result['title'] = '【' . $info['cate_name'] . '】巡检任务获取定位数据';
            if ((int)$task_info['status'] == 1) { //开启新的巡检记录
                $result['index_id'] = $task_info['index_id'];
            } else {
                $result['index_id'] = $info['id'];
            }
        }
        return $result;
    }

    //todo 校验当前巡检人员
    public function checkWisdomQrcodePerson($wid, $cate_id)
    {
        $result = (new WisdomQrcodePerson())->getFind([
            ['uid', '=', $wid],
            ['cate_id', '=', $cate_id],
        ], 'id');
        if (!$result || $result->isEmpty()) {
            return false;
        } else {
            return true;
        }
    }

    //todo 获取当前最新的巡检任务
    public function getWisdomQrcodePersonTask($village_id, $wid, $status = true)
    {
        $where[] = ['a.uid', '=', $wid];
        $where[] = ['b.village_id', '=', $village_id];
        $where[] = ['a.status', '=', 1];
        $record = (new WisdomQrcodePositionRecord())->getFind([
            ['wid', '=', $wid],
            ['village_id', '=', $village_id],
        ], 'index_id,cate_id');
        if ($record && !$record->isEmpty()) {
            $where[] = ['b.id', '=', $record['cate_id']];
        }
        $result = (new WisdomQrcodePerson())->getWisdomQrcodeCate($where, 'a.cate_id,b.village_id,a.uid,b.cate_name', 'b.id desc', 1, 2);
        $data = [
            'status' => 0,
            'cate_id' => 0,
            'cate_name' => '',
            'village_id' => 0,
            'title' => ''
        ];
        if ($status && $result && !$result->isEmpty()) {
            $result = $result->toArray();
            foreach ($result as $k => $v) {
                if ($k == 0) {
                    $data = [
                        'status' => 1,
                        'cate_id' => $v['cate_id'],
                        'cate_name' => $v['cate_name'],
                        'village_id' => $v['village_id'],
                        'title' => '【' . $v['cate_name'] . '】巡检任务获取定位数据'
                    ];
                    break;
                }
            }
        }
        return $data;
    }


    /**
     * 记录已巡检次数
     */
    public function addPatrolInspectionCompletionNum($qrcode_id, $cate_id, $uid, $village_id)
    {
        $dbWisdomQrcodePerson = new WisdomQrcodePerson();
        $dbWisdomQrcodeRecordLog = new WisdomQrcodeRecordLog();
        $dbWisdomQrcodeStatistics = new WisdomQrcodeStatistics();
        $dbWisdomQrcode = new WisdomQrcode();
        $dbWisdomQrcodeCate = new WisdomQrcodeCate();
        $dbHouseVillage = new HouseVillage();
        //查询上报的负责人当前时间今天是否是上报时间
//        $qrcode_person = $dbWisdomQrcodePerson->getFind(['uid'=>$uid,'cate_id'=>$cate_id,'status'=>1]);
//        $qrcode_person = $qrcode_person ? $qrcode_person->toArray() : [];
//        if(!$qrcode_person){
//            return true;
//        }

        $qrcode_cate = $dbWisdomQrcodeCate->getFind(['id' => $cate_id]);
        $qrcode_cate = $qrcode_cate ? $qrcode_cate->toArray() : [];
        if (!$qrcode_cate) {
            return true;
        }
        $time = time();
        if ($qrcode_cate['record_type'] == 2) {//每周
            $record_time = $qrcode_cate['record_time'] ? explode(',', $qrcode_cate['record_time']) : $qrcode_cate['record_time'];
            $dates = date('N', $time);
            if (!in_array($dates, $record_time)) {
                return true;
            }
        } elseif ($qrcode_cate['record_type'] == 3) {//每月
            $record_time = $qrcode_cate['record_time'] ? explode(',', $qrcode_cate['record_time']) : $qrcode_cate['record_time'];
            $dates = date('d', $time);
            if (!in_array($dates, $record_time)) {
                return true;
            }
        }

        $num = $dbWisdomQrcode->getCount(['cate_id' => $cate_id]);//负责任今日的巡检总次数
        if ($num < 1) {
            return true;
        }
        //查询小区的该类型巡检统计数据是否生成
        $statisticsWhere = ['village_id' => $village_id, 'cate_id' => $cate_id];
        $statistics = $dbWisdomQrcodeStatistics->getOne($statisticsWhere, 'uid,update_time');
        $statistics = $statistics ? $statistics->toArray() : [];
        if (!$statistics) {
            $villageInfo = $dbHouseVillage->getOne($village_id, 'property_id');
            $property_id = $villageInfo['property_id'] ?? 0;
            if (!$property_id) {
                return false;
            }
            //否：新增数据，且已巡检数量加的为1
            $dbWisdomQrcodeStatistics->addOne([
                'village_id' => $village_id,
                'cate_id' => $cate_id,
                'property_id' => $property_id,
                'patrol_inspection_num' => $num,
                'patrol_inspection_completion_num' => 1,
                'add_time' => time(),
                'update_time' => time(),
                'uid' => $uid
            ]);
        } else {
            $uidStr = $statistics['uid'];
            //是：查询该二维码今天是否已经上报过，已上报不记录次数
            $recordCount = $dbWisdomQrcodeRecordLog->getCount([
                ['uid', '=', $uid],
                ['cate_id', '=', $cate_id],
                ['qrcode_id', '=', $qrcode_id],
                ['add_time', '>=', strtotime(date('Y-m-d'))],
                ['add_time', '<', time()]
            ]);
            if ($recordCount > 0) {
                return true;
            }
            //查询最后更新时间是不是今天
            if ($statistics['update_time'] < strtotime(date('Y-m-d'))) {
                //否：清空统计数据的uid，只保留本次的uid。增加总巡检次数，增加已巡检次数1，更新最后修改时间
                $uidStr = $uid;
                $dbWisdomQrcodeStatistics->addNum($statisticsWhere, 'patrol_inspection_num', $num);
                $dbWisdomQrcodeStatistics->addNum($statisticsWhere, 'patrol_inspection_completion_num', 1);
            } else {
                //是：查询已统计今天总巡检次数的uid中是否包含本次的uid
                $statisticsUidAry = $statistics['uid'] ? explode($statistics['uid'], true) : [];
                if (in_array($uid, $statisticsUidAry)) {
                    //是：增加已巡检次数1
                    $dbWisdomQrcodeStatistics->addNum($statisticsWhere, 'patrol_inspection_completion_num', 1);
                } else {
                    //否：增加本次的uid。增加总巡检次数，增加已巡检次数1，更新最后修改时间
                    $uidStr = $uidStr ? $uidStr . ',' . $uid : $uid;
                    $dbWisdomQrcodeStatistics->addNum($statisticsWhere, 'patrol_inspection_num', $num);
                    $dbWisdomQrcodeStatistics->addNum($statisticsWhere, 'patrol_inspection_completion_num', 1);
                }
            }
            if ($uidStr != $statistics['uid']) {//uid有更新，修改最后更新时间
                $dbWisdomQrcodeStatistics->updateRecord($statisticsWhere, [
                    'uid' => $uidStr,
                    'update_time' => time()
                ]);
            }
        }
        return true;
    }


}
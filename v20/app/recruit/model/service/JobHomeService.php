<?php


namespace app\recruit\model\service;


use app\common\model\db\Area;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitJobDelivery;
use app\recruit\model\db\NewRecruitResumeSend;

class JobHomeService
{
    /**
     * 在招职位
     */
    public function jopHomeList($type, $hr_uid, $page, $pageSize)
    {
        if ($type == 1) {//在招
            $where = ['s.status' => 1, 's.is_del' => 0, 's.add_type' => 0, 's.author' => $hr_uid];
        } else {
            $where = ['s.status' => 0, 's.is_del' => 0, 's.add_type' => 0, 's.author' => $hr_uid];
        }

        $field = 's.job_name,s.job_age,s.job_id,s.education,s.wages,s.recruit_nums,s.city_id,s.area_id,s.uptime,h.first_name,h.last_name,s.create_time,s.end_time';
        $order = 's.uptime asc';
        $list = (new NewRecruitJob())->jopHomeList1($where, $field, $order, $page, $pageSize);
        $list['upCount']=(new NewRecruitJob())->jopHomeListCount(['s.status' => 1, 's.is_del' => 0, 's.add_type' => 0, 's.author' => $hr_uid]);
        $list['downCount']=(new NewRecruitJob())->jopHomeListCount(['s.status' => 0, 's.is_del' => 0, 's.add_type' => 0, 's.author' => $hr_uid]);
        if (!empty($list['list'])) {
            foreach ($list['list'] as $key => $val) {
                $list['list'][$key]['job_age'] = (new JobService())->job_age[$val['job_age']];

                $list['list'][$key]['education'] = (new JobService())->education[$val['education']];
               // $d = $val['create_time'];
                $s = $val['uptime'];
                if ($val['end_time'] == 0) {
                    $d = strtotime("+1 month", $s);
                } elseif ($val['end_time'] == 1) {
                    $d = strtotime("+3 month", $s);
                } else{
                    $d = strtotime("+6 month", $s);
                }
                $list['list'][$key]['end_date'] = date('m-d', $d);
                $list['list'][$key]['update'] = date('m-d', $val['uptime']);
                if ($val['city_id'] && $val['area_id']) {
                    $where = [['area_id', '=', $val['city_id']]];
                    $area_name = (new Area())->getNowCityTimezone('area_name', $where);
                    $where = [['area_id', '=', $val['area_id']]];
                    $area_name1 = (new Area())->getNowCityTimezone('area_name', $where);
                    $list['list'][$key]['city_area'] = $area_name . $area_name1;
                }else{
                    $list['list'][$key]['city_area'] = '全城市';
                }
                $list['list'][$key]['hr_name'] = $val['first_name'] . $val['last_name'];
                $where1 = ['position_id' => $val['job_id']];
                $list['list'][$key]['deliver_num'] = (new NewRecruitResumeSend())->getCount($where1);
                if(!empty($val['wages'])){
                   $arr=explode(',',$val['wages']);
                    $list['list'][$key]['wages'] =intval($arr[0]/1000).'-'.intval($arr[1]/1000).'K';
                }else{
                    $list['list'][$key]['wages'] ="面议";
                }
            }
        }

        return $list;
    }
}
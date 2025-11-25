<?php


namespace app\recruit\model\service;


use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\recruit\model\db\NewRecruitJobDelivery;
use app\recruit\model\db\NewRecruitResumeSend;

class RecruitJobDeliveryService
{
    public $sendMod = null;

    public function __construct()
    {
        $this->sendMod = new NewRecruitResumeSend();
    }

    /**
     * 投递记录
     */
    public function deliveryList($uid, $page, $pageSize)
    {

        $where = [['s.uid', '=', $uid]];
        $field = 's.add_time,s.position_id,j.job_name,j.job_age,j.age,j.wages,j.education,j.area_id,j.circle_id,m.name,j.status,m.mer_id';
        $order = 's.add_time desc';
        $list = (new NewRecruitResumeSend())->deliveryList($where, $field, $order, $page, $pageSize);
        if (!empty($list['list'])) {
            foreach ($list['list'] as $k => $v) {
                if (empty($v['wages'])) {
                    $list['list'][$k]['wages'] = L_('面议');
                } else {
                    $arr = explode(',', $v['wages']);
                    $wage_s = intval($arr[0] / 1000);
                    $wage_e = intval($arr[1] / 1000) . 'K';
                    if(empty($arr[1])){
                        $list['list'][$k]['wages'] = L_('面议');
                    }else{
                        $list['list'][$k]['wages'] = $wage_s . '-' . $wage_e;
                    }
                }

                $list['list'][$k]['job_age'] = (new JobService())->job_age[$v['job_age']];
                $list['list'][$k]['education'] = (new JobService())->education[$v['education']];
//                if ($v['area_id'] && $v['circle_id']) {
//                    $where = [['area_id', '=', $v['area_id']]];
//                    $area_name = (new Area())->getNowCityTimezone('area_name', $where);
//                    $where = [['area_id', '=', $v['circle_id']]];
//                    $area_name1 = (new Area())->getNowCityTimezone('area_name', $where);
//                    $list['list'][$k]['area_txt'] = $area_name .' '. $area_name1;
//                } else {
//                    $list['list'][$k]['area_txt'] = "";
//                }

                if(!empty($v['update_time'])){
                    $now_date=date('m-d',time());
                    $now_date1=date('m-d',$v['update_time']);
                    if($now_date==$now_date1){
                        $list['list'][$k]['time_txt'] = date('H:i',$v['update_time']);
                    }else{
                        $list['list'][$k]['time_txt'] = date('m-d H:i',$v['update_time']);
                    }
                }

                if(empty($v['mer_id'])){
                    $list['list'][$k]['name'] = "";
                    $list['list'][$k]['logo'] = "";
                }else{
                    $where=[['mer_id','=',$v['mer_id']]];
                    $com=(new Merchant())->getOne($where);
                    if(!empty($com)){
                        $com=$com->toArray();
                        $list['list'][$k]['logo'] = empty($com['logo'])?"":replace_file_domain($com['logo']);
                    }
                }
                $list['list'][$k]['add_time'] = date('m-d H:i',$v['add_time']);
            }
        }
        return $list;
    }

    /**
     * 获取我的投递职位记录
     * @date: 2021/07/14
     */
    public function getMyJobDeliveryRecords($uid, $field = '*', $where = [])
    {
        $where[] = ['uid', '=', $uid];
        return $this->sendMod->where($where)->field($field)->select()->toArray();
    }
}
<?php


namespace app\recruit\model\service;


use app\common\model\db\Area;
use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitIndustry;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitJobCategory;
use app\recruit\model\db\NewRecruitWelfare;

class EmploymentService
{
    public $job_time = [
        '0' => '一个月',
        '1' => '三个月',
        '2' => '六个月',
    ];

    public $job_properties = [
        '1' => "全职",
        '2' => '兼职',
        '3' => '实习',
    ];

    public function getOne($where)
    {
        $ret = (new NewRecruitJob())->getOne($where);
        if (!empty($ret)) {
            $ret = $ret->toArray();
        }
        return $ret;
    }

    /**
     * 工作地点列表
     *
     */
    public function getAreaList()
    {
        $output[] = [
            'area_name' => L_("全部"),
            'area_id' => 0,
            'child' => [],
        ];
        $where = [['area_type', '=', 1], ['is_open', '=', 1]];
        $order = 'area_sort desc';
        $first = (new Area())->getSome($where, true, $order)->toArray();
        if (!empty($first)) {
            foreach ($first as $key => $val) {
                $arr['area_name'] = $val['area_name'];
                $arr['area_id'] = $val['area_id'];
                $where = [['is_open', '=', 1], ['area_pid', '=', $val['area_id']]];
                $second = (new Area())->getSome($where, true, $order)->toArray();
                $item_list = array();
                if (!empty($second)) {
                    foreach ($second as $key1 => $val1) {
                        $item_list1 = array();
                        $item['area_name'] = $val1['area_name'];
                        $item['area_id'] = $val1['area_id'];
                        $where1 = [['is_open', '=', 1], ['area_pid', '=', $val1['area_id']]];
                        $three = (new Area())->getSome($where1, true, $order)->toArray();
                        if (!empty($three)) {
                            foreach ($three as $key2 => $val2) {
                                $item1['area_name'] = $val2['area_name'];
                                $item1['area_id'] = $val2['area_id'];
                                $item_list1[] = $item1;
                            }
                            $item['child'] = $item_list1;
                        } else {
                            $item['child'] = [];
                        }
                        $item_list[] = $item;
                    }
                }
                $arr['child'] = $item_list;
                $output[] = $arr;
            }

        }
        return $output;
    }

    /**
     * 福利列表
     */
    public function welfareList($uid)
    {
        $out=array();
        $where_hr = [['uid','=',$uid]];
        $hr = (new NewRecruitHr())->getOne($where_hr);
        if(!empty($hr)){
            $hr=$hr->toArray();
            $where = [['mer_id','=',$hr['mer_id']]];
            $list = (new NewRecruitCompany())->getOne($where);
            if(!empty($list)){
                $list=$list->toArray();
                if(!empty($list['welfare'])){
                    $arr=explode(',',$list['welfare']);
                    foreach ($arr as $k=>$v){
                        $w_arr=(new NewRecruitWelfare())->getOne(['id'=>$v,'status'=>0]);
                        if(!empty($w_arr)){
                            $out[]=$w_arr->toArray();
                        }
                    }
                }
            }
        }
        return $out;
    }

    /**
     * @param $param
     * 发布职位
     */
    public function publishJob($param)
    {
        $where = [['area_id', '=', $param['area_id']]];
        $msg = (new Area())->getOne($where);
        if(!empty($msg)){
            $msg=$msg->toArray();
            $param['city_id'] = $msg['area_pid'];
            $where = [['area_id', '=', $msg['area_pid']]];
            $msg1 = (new Area())->getOne($where)->toArray();
            $param['province_id'] = $msg1['area_pid'];
        }else{
            $param['city_id'] =0;
            $param['province_id'] =0;
        }
        $ret = (new NewRecruitJob())->add($param);
        return $ret;
    }

    /**
     * 职位详情
     */
    public function getJobDetail($job_id)
    {
        $where = [['job_id', '=', $job_id]];
        $ret = (new NewRecruitJob())->getOne($where);
        try {
            if (!empty($ret)) {
                $ret = $ret->toArray();
                if (!empty($ret['first_cate']) && !empty($ret['second_cate']) && !empty($ret['third_cate'])) {
                    $where = [['cat_id', '=', $ret['first_cate']]];
                    $first_cate_name = (new NewRecruitJobCategory())->where($where)->column('cat_title');
                    $ret['first_cate_name'] = empty($first_cate_name) ? '' : $first_cate_name;
                    $where = [['cat_id', '=', $ret['second_cate']]];
                    $second_cate_name = (new NewRecruitJobCategory())->where($where)->column('cat_title');
                    $ret['second_cate_name'] = empty($second_cate_name) ? '' : $second_cate_name;
                    $where = [['cat_id', '=', $ret['third_cate']]];
                    $third_cate_name = (new NewRecruitJobCategory())->where($where)->column('cat_title');
                    $ret['third_cate_name'] = empty($third_cate_name) ? '' : $third_cate_name;
                } else {
                    $ret['first_cate_name'] = "";
                    $ret['second_cate_name'] = "";
                    $ret['third_cate_name'] = "";
                }

                if (!empty($ret['province_id']) && !empty($ret['city_id']) && !empty($ret['area_id'])) {
                    $where = [['area_id', '=', $ret['province_id']]];
                    $ret['province_name'] = (new Area())->getNowCityTimezone('area_name', $where);
                    $where = [['area_id', '=', $ret['city_id']]];
                    $ret['city_name'] = (new Area())->getNowCityTimezone('area_name', $where);
                    $where = [['area_id', '=', $ret['area_id']]];
                    $ret['area_name'] = (new Area())->getNowCityTimezone('area_name', $where);
                } else {
                    $ret['province_name'] = "";
                    $ret['city_name'] = "";
                    $ret['area_name'] = "全部";
                }

                $fuli = $ret['fuli'];
                $welfare = array();
                if (!empty($fuli)) {
                    $arr = explode(',', $fuli);
                    foreach ($arr as $k => $v) {
                        $get = (new NewRecruitWelfare())->getVal('name', ['id' => $v, 'status' => 0]);
                        if (!empty($get)) {
                            $assign['id'] = $v;
                            $assign['name'] = $get;
                            $welfare[] = $assign;
                        }
                    }
                }
                $ret['fuli_arr'] = $welfare;
            }
            return $ret;
        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * @param $param
     * 删除更新职位
     */
    public function updateJob($param)
    {
        $where = [['job_id', '=', $param['job_id']]];
        $ret = (new NewRecruitJob())->updateThis($where, $param);
        return $ret;
    }
}
<?php


namespace app\recruit\model\service;


use app\common\model\db\Area;
use app\common\model\db\User;
use app\common\model\service\AreaService;
use app\recruit\model\db\NewRecruitIndustry;
use app\recruit\model\db\NewRecruitJobCategory;
use app\recruit\model\db\NewRecruitJobIntention;
use app\recruit\model\db\NewRecruitResume;
use app\recruit\model\service\RecruitJobCategoryService;

class JobIntentionService
{
    /**
     * 个人求职意向列表
     */
    public function getJobIntentionList($where, $page, $pageSize,$type)
    {
        $job_list = array();
        $rets_arr=array();
        $list = (new NewRecruitJobIntention())->getIntentionList($where, 's.*,m.cat_title as job_name', true, $page, $pageSize);
        if (!empty($list['list'])) {
            foreach ($list['list'] as $k => $v) {
                $arr['id'] = $v['id'];
                $arr['job_name'] = $v['job_name'];
                $arr['salary'] = $v['salary'];
                $arr['city_id'] = $v['city_id'];
                $arr['province_id'] = $v['province_id'];
                $arr['area_id'] = $v['area_id'];
                $arr['circle_id'] = $v['circle_id'];
                // 省份
                $where = [['area_id', '=', $v['province_id']]];
                $provinceName = (new Area())->getNowCityTimezone('area_name', $where);
                $arr['area_txt'] = $provinceName;

                // 城市
                if($v['city_id']){
                    $where = [['area_id', '=', $v['city_id']]];
                    $cityName = (new Area())->getNowCityTimezone('area_name', $where);
                    $arr['area_txt'] = $cityName;

                    if(!$v['province_id']){// 老数据没有省份id 
                        $city = (new AreaService())->getAreaByAreaId($v['city_id']);
                        $arr['province_id'] = $city['area_pid'];
                    }
                }

                // 区域
                if($v['area_id']){
                    $where = [['area_id', '=', $v['area_id']]];
                    $areaName = (new Area())->getNowCityTimezone('area_name', $where);
                    $arr['area_txt'] .= $areaName;
                }

                if($type){
                    // if ( $arr['city_id']) {
                    //     // $where = [['area_id', '=', $v['city_id']]];
                    //     // $area_name = (new Area())->getNowCityTimezone('area_name', $where);
                    //     $arr['area_txt'] = $area_name . $area_name1;
                    // }
                }else{
                    $where1=[['cat_id','=',$v['job_id']]];
                    $a_jobcategory=(new NewRecruitJobCategory())->getOne($where1)->toArray();
                    $where1=[['cat_id','=',$a_jobcategory['cat_fid']]];
                    $a_jobcategory=(new NewRecruitJobCategory())->getOne($where1)->toArray();
                    $where1=[['cat_id','=',$a_jobcategory['cat_fid']]];
                    $a_jobcategory=(new NewRecruitJobCategory())->getOne($where1)->toArray();
                    $rets_arr['job_fid']=$a_jobcategory['cat_id'];
                    $rets_arr['job_id']=$v['job_id'];
                    $area_arr['area_id']=$v['area_id'];
                    $area_arr['circle']=[];
                    if ($arr['circle_id']>0) {
                        $cir=explode(",",$arr['circle_id']);
                        $area=array();
                        $circle=array();
                        foreach ($cir as $c=>$c_v){
                            $circle_id['circle_id']=$c_v;
                            $where = [['area_id', '=', $c_v]];
                            $name=(new Area())->getNowCityTimezone('area_name', $where);
                            $circle_id['area_name']=$name;
                            $area[] =$name;
                            $circle[]=$circle_id;
                        }
                        $area_arr['circle']=$circle;
                        $arr['area_txt'] =implode(' ',$area);
                    }
                    $rets_arr['area']=$area_arr;
                }
                $arr['job_properties'] = $v['job_properties'];
                if ($v['job_properties'] == 1) {
                    $arr['job_properties_name'] = L_("全职");
                } elseif ($v['job_properties'] == 2) {
                    $arr['job_properties_name'] = L_("兼职");
                } else {
                    $arr['job_properties_name'] = L_("实习");
                }
                $arr['industry_ids'] = $v['industry_ids'];
                if (empty($v['industry_ids'])) {
                    $arr['industry_name'] = L_("全行业");
                } else {
                    $industry = explode(',', $v['industry_ids']);
                    $get_industry = "";
                    //$a['industry']=[];
                    $a=array();
                    foreach ($industry as $key => $value) {
                        $where = [['id', '=', $value]];
                        $industry_name = (new NewRecruitIndustry())->getOne($where);
                        if (!empty($industry_name)) {
                            $rets['cat_id']=$value;
                            $industry_name = $industry_name->toArray();
                            $get_industry .= $industry_name['name'] . " ";
                            $rets['cat_name']=$industry_name['name'];
                            $a[]=$rets;
                        }
                    }
                    $rets_arr['industry']=$a;

                    $arr['industry_name'] = L_($get_industry);
                }
                $job_list[] = $arr;
            }
        }
        $output['list'] = $job_list;
        $output['count'] = $list['count'];
        $output['pageSize'] = $pageSize;
        if($type==0){
            $output['other_info']=$rets_arr;
        }
        return $output;
    }


    /**
     * 岗位列表
     *
     */
    public function getJobList()
    {
        $where = [['is_del', '=', 0], ['cat_fid', '=', 0]];
        $first = (new NewRecruitJobCategory())->getSome($where,true,'sort desc,cat_id desc')->toArray();
        $one_list = array();
        if (!empty($first)) {
            foreach ($first as $key => $val) {
                $arr['cat_id'] = $val['cat_id'];
                $arr['cat_name'] = $val['cat_title'];
                $arr['cat_fid'] = 0;
                $where = [['is_del', '=', 0], ['cat_fid', '=', $val['cat_id']]];
                $second = (new NewRecruitJobCategory())->getSome($where,true,'sort desc,cat_id desc')->toArray();
                $item_list = array();
                if (!empty($second)) {
                    foreach ($second as $key1 => $val1) {
                        $second_list = array();
                        $item['cat_id'] = $val1['cat_id'];
                        $item['cat_name'] = $val1['cat_title'];
                        $item['cat_fid'] = $val1['cat_fid'];
                        $where = [['is_del', '=', 0], ['cat_fid', '=', $val1['cat_id']]];
                        $three = (new NewRecruitJobCategory())->getSome($where,true,'sort desc,cat_id desc')->toArray();
                        foreach ($three as $key2 => $val2) {
                            $three1['cat_id'] = $val2['cat_id'];
                            $three1['cat_name'] = $val2['cat_title'];
                            $three1['cat_fid'] = $val2['cat_fid'];
                            $second_list[] = $three1;
                        }
                        $item['child'] = $second_list;
                        $item_list[] = $item;
                    }
                }
                $arr['second'] = $item_list;
                $one_list[] = $arr;
            }

        }
        return $one_list;
    }

    /**
     * 搜索职位分类名称
     */
    public function searchJobCategory($where)
    {
        $three = (new NewRecruitJobCategory())->getSome($where)->toArray();
        $three_list = array();
        if (!empty($three)) {
            foreach ($three as $key2 => $val2) {
                $three1['cat_id'] = $val2['cat_id'];
                $three1['cat_name'] = $val2['cat_title'];
                $three1['cat_fid'] = $val2['cat_fid'];
                $three_list[] = $three1;
            }
        }
        return $three_list;
    }

    /**
     * 行业列表
     *
     */
    public function getIndustryList()
    {
        $where = [['status', '=', 0], ['fid', '=', 0]];
        $order = 'sort DESC, id DESC';
        $first = (new NewRecruitIndustry())->getSome($where, true, $order)->toArray();
        $one_list = array();
        if (!empty($first)) {
            foreach ($first as $key => $val) {
                $arr['cat_id'] = $val['id'];
                $arr['cat_name'] = $val['name'];
                $arr['cat_fid'] = 0;
                $where = [['status', '=', 0], ['fid', '=', $val['id']]];
                $second = (new NewRecruitIndustry())->getSome($where, true, $order)->toArray();
                $item_list = array();
                if (!empty($second)) {
                    foreach ($second as $key1 => $val1) {
                        $item['cat_id'] = $val1['id'];
                        $item['cat_name'] = $val1['name'];
                        $item['cat_fid'] = $val1['fid'];
                        $item_list[] = $item;
                    }
                }
                $arr['second'] = $item_list;
                $one_list[] = $arr;
            }

        }
        return $one_list;
    }


    /**
     * 工作地点列表
     *
     */
    public function getAreaList($city_id)
    {
        $output[] = [
            'area_name' => L_("全部"),
            'area_id' => 0,
            'child' => [[
                'area_name' => L_("全部"),
                'area_id' => 0,
              ]
            ],
        ];
        $where = [['area_type', '=', 3], ['is_open', '=', 1],['area_pid','=',$city_id]];
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
                    $item_list[] = [
                        'area_name' => L_("全部"),
                        'area_id' => 0,
                    ];
                    foreach ($second as $key1 => $val1) {
                        $item['area_name'] = $val1['area_name'];
                        $item['area_id'] = $val1['area_id'];
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
     * 工作性质
     *
     */
    public function getJobProperties() 
    {
        $output = [[
            'name' => L_("全职"),
            'job_properties' => 1,
        ],
            ['name' => L_("兼职"),
                'job_properties' => 2,],
            ['name' => L_("实习"),
                'job_properties' => 3,]];
        return $output;
    }

    /**
     * 添加
     */
    public function addJobIntention($param){
        $ret=(new NewRecruitJobIntention())->add($param);
        $user=(new User())->getOne(['uid'=>$param['uid']]);
        if($ret){
            $arr=(new NewRecruitResume())->getOne(['uid'=>$param['uid']]);
            if(empty($arr)){//没有简历
                $data['uid']=$param['uid'];
                $data['int_id']=$ret;
                if(!empty($user)){
                    $user=$user->toArray();
                    $data['name']=$user['nickname'];
                    $data['sex']=$user['sex'];
                    $data['phone']=$user['phone'];
                    $data['portrait']=$user['avatar'];
                    $data['age']=empty($user['age']) ? 0 : $user['age'];
                    $data['email']=$user['email'];
                    $data['update_time']=time();
                    (new NewRecruitResume())->add($data);
                }
            }else{
                $arr=$arr->toArray();
                if(empty($arr['int_id'])){
                    $data['int_id']=$ret;
                }else{
                    $data['int_id']=$arr['int_id'].','.$ret;
                }
                (new NewRecruitResume())->updateThis(['uid'=>$param['uid']],$data);
            }
        }
        (new RecruitResumeService())->updateRecruitResumeTime($param['uid'] ?? 0);
        return $ret;
    }

    /**
     * 修改--删除
     */
    public function updateJobIntention($param){
        $where=[['id','=',$param['id']]];
        $data = $param;
        unset($data['id']);
        $ret=(new NewRecruitJobIntention())->updateThis($where,$data);
        (new RecruitResumeService())->updateRecruitResumeTime($param['uid'] ?? 0);
        if($ret!==false){
            $param['uid'] && (new NewRecruitResume())->updateThis(['uid'=>$param['uid']], ['int_id' => $param['id']]);
            return true;
        }else{
            return false;
        }
    }

    //获取用户的求职意向
    public function getUserIntentions($uid, $intention_id = 0){
        if(empty($uid)) return [];
        $where = [
            ['uid', '=', $uid],
            ['is_del', '=', 0]
        ];
        $order = 'update_time desc';
        $data = (new NewRecruitJobIntention())->getSome($where, 'id, job_id, province_id,city_id', $order);
        if(empty($data)) return [];
        $data = $data->toArray();
        $temp = [];//选中的那个放前面
        $result = [];
        foreach ($data as $key => $value) {
            $value['cate_name'] = (new RecruitJobCategoryService)->getCateName($value['job_id']);
            if($value['id'] == $intention_id){
                $temp = $value;
            }
            else{
                $result[] = $value;
            }
        }
        if($temp){
            return array_merge([$temp], $result);
        }
        else{
            return $result;   
        }
    }

    public function getInfo($id){
        if(empty($id)) return [];
        $where = [
            ['id', '=', $id],
        ];
        $data = (new NewRecruitJobIntention())->getOne($where);
        if($data){
            return $data->toArray();
        }
        else{
            return [];
        }
    }

    /**
     * @param $uid
     * 更新活跃时间
     */
    public function updateLivelyTime($uid){
        $where=[['uid','=',$uid]];
        $data['lively_time']=time();
        (new User())->updateThis($where,$data);
    }

    /**
     * 获取求职意向
     */
    public function recruitResumeIntention($uid, $id, $fields='*'){

        if($id > 0){
            $where=[['id','=',$id], ['uid','=',$uid]];
            return (new NewRecruitJobIntention())->where($where)->find();
        }else{
            $where=[['uid','=',$uid]];
            return (new NewRecruitJobIntention())->where($where)->select();
        }
    }

    /**
     * 获取求职意向列表
     */
    public function recruitResumeIntentionList($uid){
        $where=[['g.uid','=',$uid]];
        $order = 'g.create_time ASC';
        $fields = 'g.*, a.cat_title as job_name';
        return (new NewRecruitJobIntention())->recruitResumeIntentionList($where, $fields, $order);
    }

    /**
     * 简历求职意向保存
     */
    public function recruitResumeIntentionCreate($params, $id)
    {
        $params['update_time'] = time();
        if($id > 0){
            // 修改
            $where = ['id'=>$id];
            $return=(new NewRecruitJobIntention())->where($where)->update($params);
        }else{
            // 新增
            $data['create_time'] = $params['update_time'];
            $return=(new NewRecruitJobIntention())->add($params);
        }
        return $return;
    }

    /**
     * 简历求职意向删除
     */
    public function recruitResumeIntentionDel($id)
    {
        $where = ['id'=>$id];
        $return=(new NewRecruitJobIntention())->where($where)->delete();
        return $return;
    }
}
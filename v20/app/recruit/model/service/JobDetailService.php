<?php


namespace app\recruit\model\service;


use app\common\model\db\Area;
use app\common\model\db\Merchant;
use app\common\model\db\User;
use app\recruit\model\db\NewRecruitCompany;
use app\recruit\model\db\NewRecruitHr;
use app\recruit\model\db\NewRecruitJob;
use app\recruit\model\db\NewRecruitJobCollect;
use app\recruit\model\db\NewRecruitJobDelivery;
use app\recruit\model\db\NewRecruitJobIntention;
use app\recruit\model\db\NewRecruitResume;
use app\recruit\model\db\NewRecruitResumeLog;
use app\recruit\model\db\NewRecruitResumeSend;
use app\recruit\model\db\NewRecruitWelfare;
use think\Exception;

class JobDetailService
{
    public $financing_status = [
        '1' => '未融资',
        '2' => '天使轮',
        '3' => 'A轮',
        '4' => 'B轮',
        '5' => 'C轮',
        '6' => 'D轮及以上',
        '7' => '已上市',
        '8' => '不需要融资',
    ];

    public $nature = [
        '1' => '民营',
        '2' => '国企',
        '3' => '外企',
        '4' => '合资',
        '5' => '股份制企业',
        '6' => '事业单位',
        '7' => '个体',
        '8' => '其他',
    ];

    public $people_scale = [
        '1' => '小于50人',
        '2' => '50-100人',
        '3' => '101-200人',
        '4' => '201-500人',
        '5' => '500-1000人以上',
    ];
    /**
     * 职位详情
     */
    public function getJobDetail($where, $job_id, $uid)
    {
        $output = [
            'job_id'=>$job_id,
            'job_title' => [
                'job_name' => "",
                'area_txt' => "",
                'job_age' => '',
                'age' => '',//年龄范围
                'education' => "",
                'type' => '',
                'wages' => '',
                'collect_status' => 0,
                'update_time' => '',

                'city_area' => '',//城市区域
                'com_name' => '',//公司名称
                'recruit_num' => 0,//招聘人数
                'province_name'=>'',
                'city_name'=>'',
                'area_name'=>'',
                'circle_name'=>'',
            ],
            'hr' => [
                'uid'=>'',
                'name' => "",
                'image' => '',
                'mer_msg' => '',
                'line_status' => 0,
                'qq' => '',
                'wechat' => '',
                'tel' => '',
                'phone' => '',
                'email' => '',
                'job_name' => '招聘主管',

                'end_date' => '00-00',//截止日期
            ],
            'deliver_man' => [//投递人群
                'count' => 0,
                'list' => [],
            ],
            'job_content' => "",
            'job_status' => 1,
            'up_status' => 1,//是否可上线，超时没
            'my_status' => 0,//是否投递，0，没有1投了
            'welfare' => [
            ],
            'com_msg' => [
                'mer_id'=>'',
                'logo' => '',
                'name' => '',
                'financing_status' => '',
                'people_scale' => '',
                'nature' => '',
                'long' => '',
                'lat' => '',
                'address' => '',//详细地址
            ],
            'rec_list' => [
                [
                    'job_id'=>0,
                    'job_name' => '',
                    'wages' => '',
                    'education' => '',
                    'job_age' => '',
                    'logo' => '',
                    'company_name' => '',
                    'area_txt' => '',
                ]
            ],
        ];
        $intention=(new NewRecruitJobIntention())->getOne(['uid'=>$uid]);
        if(empty($intention)){
             $output['job_intention_status']=0;
        }else{
            $output['job_intention_status']=1;
        }
        $where2 = ['position_id' => $job_id, 'uid' => $uid];
        $my = (new NewRecruitResumeSend())->getOne($where2);
        if (!empty($my)) {
            $output['my_status'] = 1;
        }
        $msg = (new NewRecruitJob())->getOne($where);
        if (!empty($msg)) {
            $msg = $msg->toArray();
            $where_deliver = [['s.position_id', '=', $msg['job_id']]];
            $delivers = (new NewRecruitResumeSend())->getDeliverers($where_deliver, 'u.uid,u.avatar', 's.add_time desc');
            $output['deliver_man']['list'] = $delivers['list'];
            $output['deliver_man']['count'] = $delivers['count'];
            if (!empty($msg['wages'])) {
                $arr_wage = explode(',', $msg['wages']);
                $w['wages_start'] = intval($arr_wage[0]/1000);
                $w['wages_end'] = isset($arr_wage[1])?intval($arr_wage[1]/1000):0;
                if($w['wages_end']){
                    $msg['wages']=$w['wages_start'].'-'.$w['wages_end']."K";
                }else{
                    $msg['wages']="面议";
                }
            }else{
                $msg['wages']="面议";
            }

            if (!empty($msg['age'])) {
                $arr_age = explode(',', $msg['age']);
                $w['age_start'] = $arr_age[0];
                $w['age_end'] = $arr_age[1];
                if($w['age_end']){
                    $msg['age']=$w['age_start'].'-'.$w['age_end']."岁";
                }else{
                    $msg['age']="不限年龄";
                }
            }else{
                $msg['age']="不限年龄";
            }

            $output['job_title'] = [
                'job_name' => $msg['job_name'],
                'age' =>$msg['age'],//年龄范围
                'area_txt' => '',
                'job_age' => (new JobService())->job_age[$msg['job_age']]??"",
                'education' => (new JobService())->education[$msg['education']]??"",
                'type' => (new EmploymentService())->job_properties[$msg['type']]??"不限",
                'wages' => $msg['wages'],
                'collect_status' => 0,

                'update_time' => '',
                'city_area' => '',//城市区域
                'com_name' => '',//公司名称
                'recruit_num' => 0,//招聘人数
            ];
            $output['job_title']['age'] = $msg['age'];
            $output['job_title']['recruit_num'] = $msg['recruit_nums'];
            $output['job_status'] = $msg['status'];
            if (empty($msg['update_time'])) {
                $output['job_title']['update_time'] = date('m-d', $msg['create_time']);
            } else {
                $output['job_title']['update_time'] = date('m-d', $msg['update_time']);
            }
            $output['job_content'] = $msg['desc'];
            if (!empty($msg['fuli'])) {
                $welfare = explode(',', $msg['fuli']);
                $item_welfare = array();
                foreach ($welfare as $k_w => $v_w) {
                    $where = [['id', '=', $v_w]];
                    $item_welfare[] = (new NewRecruitWelfare())->getVal('name', $where);
                }
                $output['welfare'] = $item_welfare;
            }
            if ($msg['city_id'] && $msg['area_id'] && $msg['circle_id']) {
                $where = [['area_id', '=', $msg['city_id']]];
                $area_name = (new Area())->getNowCityTimezone('area_name', $where);
                $where = [['area_id', '=', $msg['area_id']]];
                $area_name1 = (new Area())->getNowCityTimezone('area_name', $where);
                $where = [['area_id', '=', $msg['circle_id']]];
                $area_name2 = (new Area())->getNowCityTimezone('area_name', $where);
                $output['job_title']['area_txt'] = $area_name . ' ' . $area_name1 . ' ' . $area_name2;
                $output['job_title']['city_area'] = $area_name . '-' . $area_name1;
            }else{
                $output['job_title']['city_area'] = '全城市';
                $output['job_title']['area_txt'] = '全城市';
            }

            if ($uid) {
                $where = [['job_id', '=', $job_id], ['uid', '=', $uid]];
                $msg1 = (new NewRecruitJobCollect())->getOne($where);
                if (!empty($msg1)) {
                    $msg1 = $msg1->toArray();
                    $output['job_title']['collect_status'] = $msg1['is_del'] == 0 ? 1 : 0;
                }
            }

            if ($msg['author']) {
                $where = [['uid', '=', $msg['author']]];
                $hr = (new NewRecruitHr())->getOne($where);
                if (!empty($hr)) {
                    $hr = $hr->toArray();
                    $output['hr'] = [
                        'uid'=>$hr['uid'],
                        'name' => $hr['show_set'] & 1 ?$hr['first_name'] .  $hr['last_name']:'',
                        'image' => '',
                        'mer_msg' => '',
                        'line_status' => 0,
                        'end_date' => '00-00',//截止日期

                        'qq' => $hr['show_set'] & 16 ? $hr['qq'] : '',
                        'wechat' => $hr['show_set'] & 8 ? $hr['wechat'] : '',
                        'tel' => $hr['show_set'] & 4 ? $hr['tel'] : '',
                        'phone' => $hr['phone'],
                        'email' => $hr['show_set'] & 2 ? $hr['email'] : '',
                        'job_name' => $hr['position'],
                    ];

                    $s = $msg['uptime'];
                    //$s = date('Y-m-d', time());
                    if ($msg['end_time'] == 0) {
                        $d = strtotime("+1 month", $s);
                    } elseif ($msg['end_time'] == 1) {
                        $d = strtotime("+3 month", $s);
                    } else{
                        $d = strtotime("+6 month", $s);
                    }
                    if ($d < time()) {
                        $output['up_status'] = 0;
                    }
                    $output['hr']['end_date'] = date('m-d', $d);

                    $where = [['phone', '=', $hr['phone']]];
                    $author = (new User())->getOne($where, 'avatar,lively_time');
                    if (!empty($author)) {
                        $author = $author->toArray();
                        $output['hr']['line_status'] = (time() - $author['lively_time']) <= 3600 ? 1 : 0;
                        $output['hr']['image'] = $author['avatar'];
                    }
                    if ($hr['mer_id']) {
                        $where = [['mer_id', '=', $hr['mer_id']]];
                        $company = (new NewRecruitCompany())->getOne($where);
                        if (!empty($company)) {
                            $company = $company->toArray();
                            $output['job_title']['com_name'] = $company['name'];
                            $output['hr']['mer_msg'] = $company['name'];
                            $where = [['mer_id', '=', $company['mer_id']]];
                            $mer = (new Merchant())->getOne($where)->toArray();
                            $output['com_msg'] = [
                                'mer_id'=>$company['mer_id'],
                                'logo' => empty($mer['logo']) ? '' : replace_file_domain($mer['logo']),
                                'name' => $company['name'],
                                'financing_status' => $this->financing_status[$company['financing_status']]??"",
                                'people_scale' => $this->people_scale[$company['people_scale']]??"",
                                'nature' => $this->nature[$company['nature']]??"",
                                'long' => $company['long'],
                                'lat' => $company['lat'],
                                'address' => $msg['address'],//详细地址
                                'is_authentication' => 1,//1认证 0未认证
                            ];
                        }
                    }

                }
            }
            if($msg['city_id'] && $msg['area_id']){
                $output['job_title']['province_name']=$prov=(new Area())->getNowCityTimezone('area_name',['area_id'=>$msg['province_id']]);
                $output['job_title']['city_name']=$city=(new Area())->getNowCityTimezone('area_name',['area_id'=>$msg['city_id']]);
                $output['job_title']['area_name']=$area=(new Area())->getNowCityTimezone('area_name',['area_id'=>$msg['area_id']]);
                $output['job_title']['circle_name']=$circle=(new Area())->getNowCityTimezone('area_name',['area_id'=>$msg['circle_id']]);
                $output['job_title']['area_txt'] = $city." ".$area." ".$mer['address'];
            }else{
                $output['job_title']['area_txt'] = $mer['address'];
            }
            if(empty($output['job_title']['area_txt'])){
                $output['job_title']['area_txt'] = '全城市';
            }
            $black_com=(new NewRecruitCompany())->getCompanyByMer(['s.recruit_status'=>0],'s.mer_id')->toArray();//拉黑商家不推荐
            $black=[];
            if(!empty($black_com)){
                foreach ($black_com as $k=>$v){
                    $black[]=$v['mer_id'];
                }
            }
            $where = [['s.third_cate', '=', $msg['third_cate']], ['s.city_id', '=', $msg['city_id']], ['s.area_id', '=', $msg['area_id']], ['at.position_id', '<>', $msg['job_id']], ['at.uid', '<>', $uid]];
            if(!empty($black)){
                array_push($where,['s.mer_id','not in',$black]);
            }
            $rec_list = (new NewRecruitJob())->getRecJobList($where, "s.*", 's.deliveries_nums desc,s.collect_nums desc', 1, 5);
            $rec = array();
            if (!empty($rec_list)) {
                foreach ($rec_list as $r_k => $r_v) {
                    $where1=[['uid','=',$r_v['author']]];
                    $user=(new User())->getUser('lively_time',$where1);
                    if(!empty($user)){
                        $user=$user->toArray();
                        $item['line_status'] = (time() - $user['lively_time']) <= 3600 ? 1 : 0;
                    }else{
                        $item['active'] =0;
                    }

                    if (!empty($r_v['wages'])) {
                        $arr_wage = explode(',', $r_v['wages']);
                        $w['wages_start'] = intval($arr_wage[0]/1000);
                        $w['wages_end'] = isset($arr_wage[1])?intval($arr_wage[1]/1000):0;
                        if($w['wages_end']){
                            $msg['wages']=$w['wages_start'].'-'.$w['wages_end']."K";
                        }else{
                            $msg['wages']="面议";
                        }
                    }else{
                        $msg['wages']="面议";
                    }
                    $item['job_id'] = $r_v['job_id'];
                    $item['job_name'] = $r_v['job_name'];
                    $item['wages'] = $msg['wages'];
                    $item['education'] = (new JobService())->education[$r_v['education']]??"";
                    $item['job_age'] = (new JobService())->job_age[$r_v['job_age']]??"";
                    $item['logo'] = "";
                    $item['company_name'] = "";
                    $where = [['mer_id', '=', $r_v['mer_id']]];
                    $mer = (new Merchant())->getOne($where);

                    $where = [['mer_id', '=', $r_v['mer_id']]];
                    $company = (new NewRecruitCompany())->getOne($where);
                    if (!empty($company)) {
                        $company = $company->toArray();
                        $item['company_name'] = $company['name'];
                    }

                    if ($r_v['city_id'] && $r_v['area_id'] && $r_v['circle_id']) {
                        $where = [['area_id', '=', $r_v['city_id']]];
                        $area_name = (new Area())->getNowCityTimezone('area_name', $where);
                        $where = [['area_id', '=', $r_v['area_id']]];
                        $area_name1 = (new Area())->getNowCityTimezone('area_name', $where);
                        $where = [['area_id', '=', $r_v['circle_id']]];
                        $area_name2 = (new Area())->getNowCityTimezone('area_name', $where);
                        $item['area_txt'] = $area_name . ' ' . $area_name1 . ' ' . $area_name2;
                    }
                    if(empty($item['area_txt'])){
                        $item['area_txt'] = '全城市';
                    }
                    $recruit_status=1;
                    if (!empty($mer)) {
                        $mer = $mer->toArray();
                        $item['logo'] = empty($mer['logo']) ? '' : replace_file_domain($mer['logo']);
                        $recruit_status=$mer['recruit_status'];
                    }

                    if($recruit_status){
                        $rec[] = $item;
                    }
                }
            }

            $output['rec_list'] = $rec;
        }
        return $output;
    }


    /**
     * @param $where
     * @param $param
     * @return bool
     * 职位投递
     */
    public function jobDelivery($where, $param)
    {
        $yanzheng = (new NewRecruitResumeSend())->getOne($where);
        if (!empty($yanzheng)) {
            return false;
        } else {
            $re=(new NewRecruitResume())->getOne(['uid'=>$param['uid']], true, ['id DESC']);
            if (empty($re)) {
                throw new Exception(L_('请先完善个人简历再投递'));
            }

            $job=(new NewRecruitJob())->getOne(['job_id'=>$param['position_id']]);
            if(!empty($job)){
                $job=$job->toArray();
                $param['company_id']=$job['mer_id'];
                $param['position_name']=$job['job_name'];
            }
            if(!empty($re)){
                $re=$re->toArray();
                $param['resume_id']=$re['id'];
            }
            $param['add_time']=time();
            $ret = (new NewRecruitResumeSend())->add($param);
            if ($ret) {
                $where = [['job_id', '=', $param['position_id']]];
                $this->updateJobDeliveryNum($where);
                // 写入操作记录
                $logData = [
                    'resume_id'=>$param['resume_id'],
                    'uid'=>$param['uid'],
                    'send_id'=>$ret,
                    'name'=>'投递简历',
                ];
                $logData['log_time'] = $logData['add_time'] = time();
                (new NewRecruitResumeLog())->recruitResumeLogAdd($logData);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param $where
     * @return mixed
     * 投递数加1
     */
    public function updateJobDeliveryNum($where)
    {
        return (new NewRecruitJob())->setInc($where, 'deliveries_nums');
    }

    /**
     * 收藏
     */
    public function updateJobCollect($where, $param)
    {
        $sc = (new NewRecruitJobCollect())->getOne($where);
        if (!empty($sc)) {
            $sc = $sc->toArray();
            if ($sc['is_del']) {
                (new NewRecruitJobCollect())->updateThis($where, ['is_del' => 0]);
                (new NewRecruitJob())->setDec($where, 'collect_nums');
            } else {
                (new NewRecruitJobCollect())->updateThis($where, ['is_del' => 1]);
                (new NewRecruitJob())->setInc($where, 'collect_nums');
            }
            return true;
        } else {
            $ret = (new NewRecruitJobCollect())->add($param);
            if ($ret) {
                (new NewRecruitJob())->setInc($where, 'collect_nums');
                return true;
            } else {
                return false;
            }
        }
    }
}
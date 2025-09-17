<?php


namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\AreaStreetPartyBranch;
use app\community\model\db\AreaStreetWorkers;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserLabel;
use app\community\model\db\StreetPartyBindUser;
use app\community\model\db\User;
use think\Exception;

class HouseVillageUserLabelService
{
    // 政治面貌
    public $political_affiliation_title = '政治面貌';
    public $political_affiliation_arr = array(
        1 => '党员',
        2 => '共青团员',
        3 => '群众',
    );
    // 特殊人群
    public $special_groups_title = '特殊人群';
    public $special_groups_arr = array(
        1 => '刑满释放',
        2 => '社区矫正',
        3 => '重点青少年',
        4 => '吸毒',
        5 => '艾滋病',
        6 => '精神病',
    );
    // 重点人群
    public $focus_groups_title = '重点人群';
    public $focus_groups_arr = array(
        1 => '服刑人员',
        2 => '非法组织',
        3 => '上访人员'
    );
    // 弱势困难群体
    public $vulnerable_groups_title = '关怀群体';
    public $vulnerable_groups_arr = array(
        1 => '低保',
        2 => '残疾人',
        3 => '空巢',
        4 => '失独老人',
        5 => '孤寡老人',
        6 => '退役军人',
        7 => '留守儿童',
    );
    /**
     * 获取党员数量
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @return mixed
     */
    public function getCount($where)
    {
        $db_house_village_user_label = new HouseVillageUserLabel();
        $count = $db_house_village_user_label->getCount($where);
        return $count;
    }

    /**
     * 获取党员列表
     * @author lijie
     * @date_time 2020/09/10
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getPartyMemberLists($where,$field=true,$page=0,$limit=0,$order='l.id DESC',$param=array())
    {
        $db_house_village_user_label = new HouseVillageUserLabel();
        $dataObj = $db_house_village_user_label->getPartyMemberLists($where,$field,$page,$limit,$order,'b.uid');
        $tmpCount=0;
        $data=array();
        $site_url = cfg('site_url');
        $static_resources = static_resources(true);
        if($dataObj && !$dataObj->isEmpty()){
            $data=$dataObj->toArray();
            foreach ($data as $k=>$v){
                $tmpCount++;
                $data[$k]['part_year']=0;
                if (isset($v['part_time']) && $v['part_time']>100) {
                    $res = $this->diffBetweenTwoDate(time(),$v['part_time']);
                    $data[$k]['part_year'] = $res['year'];
                }else if(isset($v['part_time']) && $v['part_time']<-100){
                    $res = $this->diffBetweenTwoDate(time(),100);
                    $year=$res['year'];
                    $tmp_part_time=abs($v['part_time']);
                    $tmp_year=$tmp_part_time/(86400*365);
                    $tmp_year=round($tmp_year);
                    $year =$tmp_year+$year;
                    $data[$k]['part_year'] =round($year);
                }
                if (isset($v['logo']) && $v['logo']) {
                    if (strpos($v['logo'],'/v20/') !== false) {
                        $data[$k]['logo'] = cfg('site_url') . $v['logo'];
                    } else {
                        $data[$k]['logo'] = replace_file_domain($v['logo']);
                    }
                } else {
                    $data[$k]['logo'] =  $site_url . $static_resources . 'images/member.png';
                }
            }
        }
        $configCustomizationService=new ConfigCustomizationService();
        $func_lantiancom_special=$configCustomizationService->getFuncZhihuilantiancomSpecial();
        if($func_lantiancom_special>0 && $param && isset($param['countMember']) && isset($param['street_id'])){
            $xpage=1;
            $xlimit=$limit;
            if($page==0){
                $xpage=0;
                $xlimit=0;
            }elseif($tmpCount<=0 && $page>0){
                $tmpP=$param['countMember']/$limit;
                $tmpP=round($tmpP,2);
                $tmpPg=ceil($tmpP);
                $tmpPg=intval($tmpPg);
                $xpage=($page-$tmpPg)+1;
                if($tmpP==$tmpPg){
                    $xpage=$page-$tmpPg;
                }
            }else if($tmpCount>0 && $tmpCount<$limit){
                $xpage=1;
                $xlimit=$limit;
                if($param['countMember']==-1){
                    $xlimit=$limit-$tmpCount;
                }
            }
            $areaStreetWorkers =new AreaStreetWorkers();
            $where=array(['is_party_member','=',1]);
            $where[] = ['area_id','=',$param['street_id']];
            $where[]= ['work_status','<',4];
            $field = 'worker_id,work_name,work_phone,work_status,work_id_card,join_party_time,party_id,work_head';
            $listObj=$areaStreetWorkers->getSelect($where,$field,'worker_id DESC',$xpage,$xlimit);
            if($listObj && !$listObj->isEmpty()){
                $db_area_street_party_branch = new AreaStreetPartyBranch();
                $list=$listObj->toArray();
                foreach ($list as $kk=>$vv){
                    $work_party=array('part_year'=>0,'name'=>$vv['work_name'],'logo'=>$site_url . $static_resources . 'images/member.png','party_branch_name'=>'');
                    if ( $vv['join_party_time']>100) {
                        $res = $this->diffBetweenTwoDate(time(),$vv['join_party_time']);
                        $work_party['part_year'] = $res['year'];
                    }else if( $vv['join_party_time']<-100){
                        $res = $this->diffBetweenTwoDate(time(),100);
                        $year=$res['year'];
                        $tmp_part_time=abs($vv['join_party_time']);
                        $tmp_year=$tmp_part_time/(86400*365);
                        $tmp_year=round($tmp_year);
                        $year =$tmp_year+$year;
                        $work_party['part_year'] =round($year);
                    }
                    if($vv['work_head']){
                        $work_party['logo']=replace_file_domain($vv['work_head']);
                    }
                    if($vv['party_id']>0){
                        $party_branch_obj= $db_area_street_party_branch->getOne(array('id'=>$vv['party_id'],'status'=>'0'),'id,name,street_id,type');
                        if($party_branch_obj && isset($party_branch_obj['name'])){
                            $work_party['party_branch_name']=$party_branch_obj['name'];
                        }
                    }
                    $data[]=$work_party;
                }
            }
        }

        return $data;
    }

    /**
     * 获取两个日期相差多少年，多少月，多少天，多少小时
     * @author lijie
     * @date_time 2020/09/10
     * @param  [type]  $startTime [开始日期，如:2018-02-10 10:00]
     * @param  [type]  $endTime   [结束日期，如:2018-03-01 15:00]
     * @return array
     */
    public function diffBetweenTwoDate($dateOne, $dateTwo)
    {
        $strtoDateOne = $dateOne;
        $strtoDateTwo = $dateTwo;

        if ($strtoDateOne < $strtoDateTwo) {
            $tmp = $strtoDateTwo;
            $strtoDateTwo = $strtoDateOne;
            $strtoDateOne = $tmp;
        }

        $dateMonthOne = explode('-', date('Y-m', $strtoDateOne));
        $dateMonthTwo = explode('-', date('Y-m', $strtoDateTwo));

        $diff = [];
        $diff['hours'] = ($strtoDateOne - $strtoDateTwo) / (60 * 60);
        $diff['day'] = ($strtoDateOne - $strtoDateTwo) / (60 * 60 * 24);
        $diff['month'] = abs($dateMonthOne[0] - $dateMonthTwo[0]) * 12 + abs($dateMonthOne[1] - $dateMonthTwo[1]);
        $diff['year'] = date('Y', $strtoDateOne) - date('Y', $strtoDateTwo);

        return $diff;
    }

    /**
     * 获取街道困难群体
     * @author lijie
     * @date_time 2020/10/16
     * @param $where
     * @param bool $field
     * @param $page
     * @param $limit
     * @param string $order
     * @param int $type
     * @return mixed
     */
    public function getStreetUser($where,$field=true,$page,$limit,$order='l.id DESC',$type=1)
    {
        $db_house_village_user_label = new HouseVillageUserLabel();
        $service_house_village = new HouseVillageService();
        $data = $db_house_village_user_label->getStreetUser($where,$field,$page,$limit,$order);
        $count = $db_house_village_user_label->getCount($where);
        if($data){
            foreach ($data as $key=>$val){
                $data[$key]['address']=$service_house_village->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
                if ($data[$key]['address']!='暂无房间(数据异常)'){
                    $data[$key]['address']=$data[$key]['village_name'].$data[$key]['address'];
                }
                switch ($type){
                    case 1:
                        $user_vulnerable_groups_txt = '';
                        $user_vulnerable_groups_txt_arr = explode(',',$val['user_vulnerable_groups']);
                        foreach ($user_vulnerable_groups_txt_arr as $k=>$v){
                            $user_vulnerable_groups_txt .= $this->vulnerable_groups_arr[$v].',';
                        }
                        $user_vulnerable_groups_txt = rtrim($user_vulnerable_groups_txt,',');
                        $data[$key]['user_vulnerable_groups_txt'] = $user_vulnerable_groups_txt;
                        break;
                    case 3:
                        $user_focus_groups_txt = '';
                        $user_focus_groups_txt_arr = explode(',',$val['user_focus_groups']);
                        foreach ($user_focus_groups_txt_arr as $k1=>$v1){
                            $user_focus_groups_txt .= $this->focus_groups_arr[$v1].',';
                        }
                        $user_focus_groups_txt = rtrim($user_focus_groups_txt,',');
                        $data[$key]['user_focus_groups_txt'] = $user_focus_groups_txt;
                        break;
                    case 4:
                        $user_special_groups_txt = '';
                        $user_special_groups_txt_arr = explode(',',$val['user_special_groups']);
                        foreach ($user_special_groups_txt_arr as $k2=>$v2){
                            $user_special_groups_txt .= $this->special_groups_arr[$v2].',';
                        }
                        $user_special_groups_txt = rtrim($user_special_groups_txt,',');
                        $data[$key]['user_special_groups_txt'] = $user_special_groups_txt;
                        break;
                    default:
                        break;
                }
                if($page>0 && isset($val['phone']) && !empty($val['phone'])){
                    $data[$key]['phone']=phone_desensitization($val['phone']);
                }
            }
        }
        $res['list'] = $data;
        $res['count'] = $count;
        return $res;
    }


    /**
     * 获取党员详情
     * @author: liukezhu
     * @date : 2022/10/31
     * @param $village_id
     * @param $pigcms_id
     * @return array
     * @throws Exception
     */
    public function getPartyMemberInfo($village_id,$pigcms_id){
        $HouseVillageUserBind=new HouseVillageUserBind();
        $HouseVillageService= new HouseVillageService();
        $HouseVillageUserLabel=new HouseVillageUserLabel();
        $StreetPartyBindUser=new StreetPartyBindUser();
        $User=new User();
        $time=time();
        $user_bind_where=[
            ['village_id','=',$village_id],
            ['pigcms_id','=',$pigcms_id],
            ['status','=',1]
        ];
        $field='pigcms_id,village_id,uid,name,phone,single_id,floor_id,layer_id,vacancy_id';
        $user_bind=$HouseVillageUserBind->getOne($user_bind_where,$field);
        if (!$user_bind || $user_bind->isEmpty()) {
            throw new Exception('该用户不存在或被禁用');
        }
        //房间号
        $room_number = $HouseVillageService->getSingleFloorRoom($user_bind['single_id'], $user_bind['floor_id'], $user_bind['layer_id'], $user_bind['vacancy_id'],$user_bind['village_id']);
        //信息标注
        $field='user_political_affiliation,user_special_groups,user_focus_groups,user_vulnerable_groups,part_time,logo';
        $user_label=$HouseVillageUserLabel->getOne([['bind_id','=',$user_bind['pigcms_id']]],$field);
        //党支部
        $field='a.party_id,a.join_party_time,a.party_img,b.name as party_branch_name';
        $party_bind_user=$StreetPartyBindUser->getBindPartyBranch([['a.uid','=',$user_bind['uid']]],$field);
        //头像
        $user_avatar  =   cfg('site_url') . '/static/images/user_avatar.jpg';
        if(isset($user_label['logo']) && $user_label['logo']){
            $user_avatar=$user_label['logo'];
        } else{
            $user=$User->getOne([['uid','=',$user_bind['uid']]],'avatar');
            if ($user && !$user->isEmpty()){
                if(!empty($user['avatar'])){
                    $user_avatar=$user['avatar'];
                }
            }
        }
        $data=[
            'user_name'=>$user_bind['name'],
            'user_avatar'=>replace_file_domain($user_avatar),
            'room_number'=>$room_number,
        ];
        //政治面貌
        if(isset($user_label['user_political_affiliation']) && $user_label['user_political_affiliation']){
            $user_political=$this->political_affiliation_arr[$user_label['user_political_affiliation']];
        }else{
            $user_political='';
        }
        //所属党支部
        if(isset($party_bind_user['party_branch_name']) && $party_bind_user['party_branch_name']){
            $party_branch_name=$party_bind_user['party_branch_name'];
        }
        else{
            $party_branch_name='';
        }
        //入党时间
        if(isset($user_label['part_time']) && $user_label['part_time']>1000){
            $user_part_time= date('Y-m-d',$user_label['part_time']);
        }
        else{
            $user_part_time='';
        }
        //党龄
        if(isset($user_label['part_time']) && $user_label['part_time']>1000){
            $user_part_year= $this->diffBetweenTwoDate($time,$user_label['part_time'])['year'];
        }
        else{
            $user_part_year= '';
        }
        $data['list']=[
            [
                'key'=>'手机号码','value'=>$user_bind['phone']
            ],
            [
                'key'=>'政治面貌','value'=>$user_political
            ],
            [
                'key'=>'所属党支部','value'=>$party_branch_name
            ],
            [
                'key'=>'入党时间','value'=>$user_part_time
            ],
            [
                'key'=>'党龄','value'=>$user_part_year
            ],
        ];
        return $data;
    }
}
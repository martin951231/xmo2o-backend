<?php
/**
 * 党员相关
 * @author weili
 * @date 2020/9/16
 */

namespace app\community\model\service;


use app\community\model\db\HouseVillageUserLabel;
use app\community\model\service\HouseVillageService;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageFloorType;
use app\community\model\db\HouseVillageDataConfig;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\StreetPartyBindUser;
use app\community\model\db\User;
use app\community\model\db\AreaStreetPartyBranch;
use app\community\model\db\AreaStreetWorkers;
use app\community\model\db\HouseVillage;

class PartyMemberService
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
    public $vulnerable_groups_title = '弱势困难群体';
    public $vulnerable_groups_arr = array(
        1 => '低保',
        2 => '残疾人',
        3 => '空巢',
        4 => '失独老人',
        5 => '孤寡老人',
        6 => '退役军人',
        7 => '留守儿童',
    );
    public function getList($street_id,$page,$limit,$keyword, $community_id=0)
    {
        $dbHouseVillageUserLabel =new HouseVillageUserLabel();
        $serviceHouseVillage =new HouseVillageService();
        $where[] = ['l.user_political_affiliation','=',1];
        if ($street_id) {
            $where[] = ['v.street_id','=',$street_id];
        }
        if ($community_id) {
            $where[] = ['v.community_id','=',$community_id];
        }
        if(isset($keyword['village_ids']) && is_array($keyword['village_ids'])){
            $where[] = ['b.village_id','in',$keyword['village_ids']];
        }
        if(isset($keyword['party_branch_type']) && $keyword['party_branch_type']){
            $where[] = ['p.id','=',$keyword['party_branch_type']];
        }
        if($keyword['name'])
        {
            $where[] = ['b.name','like','%'.$keyword['name'].'%'];
        }
        if($keyword['phone'])
        {
            $where[] = ['b.phone','like','%'.$keyword['phone'].'%'];
        }
        if($keyword['party_status'])
        {
            $where[] = ['pbu.party_status','=',$keyword['party_status']];
        }else{
            $where[] =['pbu.party_status','in',[1,4,5]];
        }
        $dbStreetPartyBindUser =new StreetPartyBindUser();
        $where[] = ['b.status','<>',4];
        $field='l.id,b.pigcms_id,b.authentication_field,b.village_id,b.uid,b.name,b.phone,b.id_card,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,p.name as party_name,pbu.party_status,v.street_id,v.community_id,pbu.id as party_bind_user_id';
        $count = $dbHouseVillageUserLabel->getCount($where,'b.uid');
        $list = $dbHouseVillageUserLabel->getPartyMemberLists($where,$field,$page,$limit,'l.id DESC','b.uid');
        foreach ($list as &$val){
            $authentication_field = unserialize($val['authentication_field']);
            unset($val['authentication_field']);
//            $val['authentication_field'] =$authentication_field;
            if($authentication_field['sex']['value']){
                $val['sex'] = $authentication_field['sex']['value'];
            }else{
                $val['sex'] = '暂未公布';
            }
            
            $whereArr=array();
            $whereArr[]=array('a.uid','=',$val['uid']);
            $whereArr[]=array('b.street_id','=',$val['street_id']);
            $partyBranch=$dbStreetPartyBindUser->getBindPartyBranch($whereArr,'b.name,a.party_status');
            if($partyBranch && !$partyBranch->isEmpty()){
                $val['party_name']=$partyBranch['name'];
                $val['party_status']=$partyBranch['party_status'];
            }
            $room_where=[
                ['b.uid','=',$val['uid']],
                ['l.user_political_affiliation','=',1],
                ['b.status','<>',4],
                ['pbu.party_status','in',[1,4,5]]
            ];
            if ($street_id) {
                $room_where[] = ['v.street_id','=',$street_id];
            }
            if ($community_id) {
                $room_where[] = ['v.community_id','=',$community_id];
            }
            $val['room_num']=$dbHouseVillageUserLabel->getPartyMemberRoomCounts($room_where);
            $val['address'] = $serviceHouseVillage->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
            switch ($val['party_status']){
                case '2':
                    $val['party_status'] = '--';
                    break;
                case '3':
                    $val['party_status'] = '--';
                    break;
                case '4':
                    $val['party_status'] = '死亡';
                    break;
                case '5':
                    $val['party_status'] = '退党';
                    break;
                default:
                    $val['party_status'] = '正常';
                    break;
            }
            if($page>0){
                if(isset($val['phone']) && !empty($val['phone'])){
                    $val['phone']=phone_desensitization($val['phone']);
                }
                if(isset($val['id_card']) && !empty($val['id_card'])){
                    $val['id_card']=idnum_desensitization($val['id_card']);
                }    
            }
        }
        $data['list'] = $list;
        $data['count'] = $count;
        return $data;
    }
    public function getWorkerMemberListCount($street_id=0,$searchArr=array(), $community_id=0){
        $areaStreetWorkers =new AreaStreetWorkers();
        $where=array(['is_party_member','=',1]);
        $where[] = ['area_id','=',$street_id];
        $where[]= ['work_status','<',4];
        if(isset($searchArr['name']) && $searchArr['name'])
        {
            $where[] = ['work_name','like','%'.$searchArr['name'].'%'];
        }
        if(isset($searchArr['phone']) && $searchArr['phone'])
        {
            $where[] = ['work_phone','like','%'.$searchArr['phone'].'%'];
        }
        if(isset($searchArr['party_branch_type']) && $searchArr['party_branch_type']){
            $where[] = ['party_id','=',$searchArr['party_branch_type']];
        }
        $count=$areaStreetWorkers->getCount($where);
        $count=$count>0 ? $count:0;
        return $count;
    }
    public function getWorkerMemberList($street_id=0,$page=0,$limit=10,$searchArr=array(), $community_id=0){
        $areaStreetWorkers =new AreaStreetWorkers();
        $where=array(['is_party_member','=',1]);
        $where[] = ['area_id','=',$street_id];
        $where[]= ['work_status','<',4];
        if(isset($searchArr['name']) && $searchArr['name'])
        {
            $where[] = ['work_name','like','%'.$searchArr['name'].'%'];
        }
        if(isset($searchArr['phone']) && $searchArr['phone'])
        {
            $where[] = ['work_phone','like','%'.$searchArr['phone'].'%'];
        }
        if(isset($searchArr['party_branch_type']) && $searchArr['party_branch_type']){
            $where[] = ['party_id','=',$searchArr['party_branch_type']];
        }
        $field = 'worker_id,work_name ,work_phone,work_status,work_id_card,join_party_time,party_id';
        $count=$areaStreetWorkers->getCount($where);
        $list=array();
        $db_area_street_party_branch = new AreaStreetPartyBranch();
        if($count>0){
            $listObj=$areaStreetWorkers->getSelect($where,$field,'worker_id DESC',$page,$limit);
            if($listObj && !$listObj->isEmpty()){
                $list=$listObj->toArray();
                foreach ($list as $kk=>$vv){
                    $list[$kk]['status_txt']='正常';
                    if($vv['work_status']==2){
                        $list[$kk]['status_txt']='禁止';
                    }
                    $list[$kk]['join_party_time_txt']='';
                    $list[$kk]['party_name']='';
                    if($vv['party_id']>0){
                       $party_branch_obj= $db_area_street_party_branch->getOne(array('id'=>$vv['party_id'],'status'=>'0'),'id,name,street_id,type');
                       if($party_branch_obj && isset($party_branch_obj['name'])){
                           $list[$kk]['party_name']=$party_branch_obj['name'];
                       }
                    }
                    if($vv['join_party_time']>100){
                        $list[$kk]['join_party_time_txt']=date('Y-m-d',$vv['join_party_time']);
                    }
                }
            }
        }
        $data=array();
        $data['list'] = $list;
        $data['count'] = $count;
        return $data;
    }
    public function editPartyMember($post)
    {
        unset($post['address']);

        $dbHouseVillageUserLabel =new HouseVillageUserLabel();
        $dbHouseVillageUserBind =new HouseVillageUserBind();
        $dbStreetPartyBindUser =new StreetPartyBindUser();
        $dbUser =new User();
        $where[] = ['l.id','=',$post['id']];
        $field = 'l.id,b.pigcms_id,b.housesize,b.authentication_field,b.village_id,b.uid,b.name,b.phone,b.id_card,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,p.name as party_name,pbu.party_status,pbu.join_party_time,pbu.party_img,pbu.is_good_party,pbu.party_id';
        $info = $dbHouseVillageUserLabel->getFind($where,$field);
        if(!$info){
            throw new \think\Exception("党员不存在");
        }
        if(!$post['id_card']){
            throw new \think\Exception("请填写身份证号码");
        }
        if(is_idcard($post['id_card']) == false){
            throw new \think\Exception("请填写正确的身份证号码");
        }
        if($post['political_affiliation_val'] == 1 && $post['party_type'] < 1){
            throw new \think\Exception("请选择党员属性");
        }
        if($post['political_affiliation_val'] == 1 && $post['party_type'] == 1 && $post['party_id'] < 1){
            throw new \think\Exception("请选择党支部");
        }
        if($post['political_affiliation_val'] != 1 || $post['party_type'] != 1){
            $post['party_id'] = 0;
        }
        //处理业主资料 start
        if ($info['authentication_field']) {
            $authentication_field = unserialize($info['authentication_field']);
            if (!$authentication_field) {
                $authentication_field = [];
            }
        } else {
            $authentication_field = [];
        }
        $dbHouseVillageDataConfig =new HouseVillageDataConfig();
        $serviceHouseVillage =new HouseVillageService();
        $dbHouseVillage = new HouseVillage();
        $village_field = 'property_id';
        $village_info = $dbHouseVillage->getOne($info['village_id'],$village_field);
        $dataList = $dbHouseVillageDataConfig->getList(['property_id'=>$village_info['property_id']]);
        if (!empty($dataList)) {
            $dataList = $dataList->toArray();
        }
        foreach($dataList as $k=>&$v){
            if($v['type'] == 2){
                if($v['is_system'] == 1){
                    $v['use_field'] = $serviceHouseVillage->get_system_value($v['key']);
                }else{
                    $v['use_field'] = explode(',',$v['use_field']);
                }
            }elseif($v['type'] == 3){

                $v['use_field'] = [];
            }else{
                $v['use_field'] = [];
            }
            if (!$authentication_field || !isset($authentication_field[$v['key']]) || !$authentication_field[$v['key']]) {
                $authentication_field[$v['key']] = $v;
                $authentication_field[$v['key']]['value'] = '';
            }
        }
        foreach ($authentication_field as $key=>&$val){
            if(array_key_exists($key,$post) && $key != 'party_type'){
                if($key=='province_idss'){
                    $val = $post["$key"];
                }elseif ($key=='city_idss'){
                    $val = $post["$key"];
                }elseif ($key == 'native_place'){
                    $native_place = explode('|',$post["$key"]);
                    $val['province'] = $native_place[0];
                    $val['city'] = $native_place[1];
                }else{
                    $val['value'] = $post["$key"];
                }
                unset($post["$key"]);
            }
        }
//        dd($authentication_field);
        //处理业主资料 end
        //处理小区居住人员关联标签 start
        $label_data = [];
        if($post['special_groups_val'] && count($post['special_groups_val'])>0)
        {
            $label_data['user_special_groups'] = implode(',',$post['special_groups_val']);
        }
        if($post['focus_groups_value'] && count($post['focus_groups_value'])>0)
        {
            $label_data['user_focus_groups'] = implode(',',$post['focus_groups_value']);
        }
        if($post['vulnerable_groups_val'] && count($post['vulnerable_groups_val'])>0)
        {
            $label_data['user_vulnerable_groups'] = implode(',',$post['vulnerable_groups_val']);
        }
        if($post['political_affiliation_val']){
            $label_data['user_political_affiliation'] = $post['political_affiliation_val'];
        }
        if($post['join_party_time']){
            $label_data['part_time'] = strtotime($post['join_party_time']);
        }
        if($post['join_party_time']){
            $label_data['part_time'] = strtotime($post['join_party_time']);
        }
        if($post['party_img']){
            $label_data['logo'] = $post['party_img'];
        }
        if($post['is_good_party']){
            $label_data['is_excellent'] = $post['is_good_party'];
        }
        if($post['party_type']){
            $label_data['party_type'] = $post['party_type'];
        }
        if($label_data) {
            $label_where[] = ['id', '=', $post['id']];
            $dbHouseVillageUserLabel->edit($label_where, $label_data);
        }
        //处理小区居住人员关联标签 end
        //处理用户信息 start
        $user_data = [
//            'name'=>$post['name'],
            'id_card'=>$post['id_card'],
//            'phone'=>$post['phone'],
            'authentication_field'=>serialize($authentication_field),
        ];
//        dump($info['bind_id']);
//        dd($user_data);
        //todo 用户同步写入属性表记录
        (new TaskReleaseService())->userAttributeRecord($info['pigcms_id'],$authentication_field);
        $user_where[] = ['pigcms_id','=',$info['pigcms_id']];
        $dbHouseVillageUserBind->saveOne($user_where,$user_data);
        //处理用户信息 end
        //处理党相关信息 start
//        if ($info['uid']==0 && $info['phone']=='' && $info['name'] == '') { // 虚拟业主
//            $user_id = 0;
//        }elseif($info['uid']==0 && $info['phone']==''){//没有uid 电话 拿名字创建一个用户
//            $user_data = [
//                'nickname'=>$info['name'],
//                'add_time'=>time()
//            ];
//            $user_id = $dbUser->addOne($user_data);
//            $dbHouseVillageUserBind->saveOne(['pigcms_id'=>$info['pigcms_id']],['uid'=>$user_id]);
//        }elseif ($info['uid']==0){//没有uid 情况
//            $user = D('User')->where(['phone'=>$info['phone']])->find();
//            $user_id = $user['uid'];
//            if(!$user){
//                $user_data = [
//                    'nickname'=>$info['name'],
//                    'phone'=>$info['phone'],
//                    'add_time'=>time()
//                ];
//                $user_id = $dbUser->addOne($user_data);
//            }
//            $dbHouseVillageUserBind->saveOne(['pigcms_id'=>$info['pigcms_id']],['uid'=>$user_id]);
//        }elseif($info['uid'] && $info['phone'] == ''){//没有电话的情况
//            $user = $dbUser->getOne(['uid'=>$info['uid']]);
//            $dbHouseVillageUserBind->saveOne(['pigcms_id'=>$info['pigcms_id']],['phone'=>$user['phone']]);
//            $user_id = $info['uid'];
//        }else{
//            $user_id = $info['uid'];
//        }
        if(array_key_exists('party_id',$post) && $post['party_type'] > 0)
        {

            $party_data = [
                'party_status'=>$post['party_status'],
                'join_party_time'=>strtotime($post['join_party_time']),
                'party_img'=>$post['party_img'],
                'is_good_party'=>$post['is_good_party'],
                'party_id'=>$post['party_id'],
                'uid'=>$info['uid'],
            ];
            if(isset($post['party_bind_user_id'])){
                $whereArr=array('id'=>$post['party_bind_user_id']);
            }else{
                $whereArr=array('uid'=>$info['uid'],'party_id'=>$post['party_id']);
            }
            $party_bind_user = $dbStreetPartyBindUser->getFind($whereArr);

            if(!$party_bind_user)
            {
                $party_data['create_time'] =time();
                $dbStreetPartyBindUser->addFind($party_data);
            }else {
                $party_where[] = ['id','=',$party_bind_user['id']];
                $dbStreetPartyBindUser->edit($party_where,$party_data);
            }
        }
        //处理党相关信息 end

        return $info;
    }
    public function getInfo($id,$street_id,$area_type)
    {
        $dbHouseVillageUserLabel =new HouseVillageUserLabel();
        $serviceHouseVillage =new HouseVillageService();
        $dbHouseVillageFloor =new HouseVillageFloor();
        $dbHouseVillageFloorType =new HouseVillageFloorType();
        $dbHouseVillageDataConfig =new HouseVillageDataConfig();
        $db_area_street_party_branch = new AreaStreetPartyBranch();
        $dbStreetPartyBindUser =new StreetPartyBindUser();
        $where[] = ['l.id','=',$id];
        $field = 'l.*,b.pigcms_id,b.housesize,b.authentication_field,b.village_id,b.uid,b.name,b.phone,b.id_card,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,p.name as party_name,pbu.party_status,pbu.join_party_time,pbu.party_img,pbu.is_good_party,pbu.party_id,v.street_id,v.community_id';
        $info = $dbHouseVillageUserLabel->getFind($where,$field);
        if ($info['authentication_field']) {
            $authentication_field = unserialize($info['authentication_field']);
            if (!$authentication_field) {
                $authentication_field = [];
            }
        } else {
            $authentication_field = [];
        }
        $whereArr=array();
        $whereArr[]=array('a.uid','=',$info['uid']);
        $whereArr[]=array('b.street_id','=',$info['street_id']);
        $partyBranch=$dbStreetPartyBindUser->getBindPartyBranch($whereArr,'b.name,a.party_id,a.party_status,a.join_party_time,a.party_img,a.is_good_party');
        if($partyBranch && !$partyBranch->isEmpty()){
            $info['party_id']=$partyBranch['party_id'];
            $info['party_name']=$partyBranch['name'];
            $info['party_status']=$partyBranch['party_status'];
            $info['join_party_time']=$partyBranch['join_party_time'];
            $info['is_good_party']=$partyBranch['is_good_party'];
            $info['party_img']=$partyBranch['party_img'];
        }
        if($info['join_party_time']){
            $info['join_party_time'] = date('Y-m-d',$info['join_party_time']);
        }else{
            $info['join_party_time'] = date('Y-m-d',time());
        }
        $info['user_special_groups'] = explode(',',$info['user_special_groups']);
        $info['user_focus_groups'] = explode(',',$info['user_focus_groups']);
        $info['user_vulnerable_groups'] = explode(',',$info['user_vulnerable_groups']);
        $info['party_img'] = dispose_url($info['logo']);
        $info['address'] = $serviceHouseVillage->getSingleFloorRoom($info['single_id'],$info['floor_id'],$info['layer_id'],$info['vacancy_id'],$info['village_id']);

        $dbHouseVillage = new HouseVillage();
        $village_field = 'property_id';
        $village_info = $dbHouseVillage->getOne($info['village_id'],$village_field);
        $dataList = $dbHouseVillageDataConfig->getList(['property_id'=>$village_info['property_id']]);
        if (!empty($dataList)) {
            $dataList = $dataList->toArray();
        }
        foreach($dataList as $k=>&$v){
            if($v['type'] == 2){
                if($v['is_system'] == 1){
                    $v['use_field'] = $serviceHouseVillage->get_system_value($v['key']);
                }else{
                    $v['use_field'] = explode(',',$v['use_field']);
                }
            }elseif($v['type'] == 3){

                $v['use_field'] = [];
            }else{
                $v['use_field'] = [];
            }
            if (!$authentication_field || !isset($authentication_field[$v['key']]) || !$authentication_field[$v['key']]) {
                $authentication_field[$v['key']] = $v;
                $authentication_field[$v['key']]['value'] = '';
            }
        }
        if(isset($authentication_field['native_place']) || array_key_exists('native_place',$authentication_field)){
                if (isset($authentication_field['native_place']['value']) && $authentication_field['native_place']['value']) {
                    $tmpArr = explode('#',$authentication_field['native_place']['value']);
                    $authentication_field['native_place']['province'] = intval($tmpArr[0]);
                    $authentication_field['native_place']['city'] = intval($tmpArr[1]);
                }
            if(isset($authentication_field['native_place']['province'])) {
                if ($authentication_field['native_place']['province'] == 'undefined' || $authentication_field['native_place']['province'] == '') {
                    $authentication_field['native_place']['province'] = 0;
                }
            }else{
                $authentication_field['native_place']['province'] = 0;
            }
            if(isset($authentication_field['native_place']['city'])) {
                if ($authentication_field['native_place']['city'] == 'undefined' || $authentication_field['native_place']['city'] == '') {
                    $authentication_field['native_place']['city'] = 0;
                }
            }else{
                $authentication_field['native_place']['city'] = 0;
            }
        }
        $info['authentication_field'] = $authentication_field;
        $data['dataList'] = $dataList;
        //房间类型
        $floor_info = $dbHouseVillageFloor->getOne(['floor_id'=>$info['floor_id']],'floor_type, floor_name, floor_layer');
        $floor_type = $floor_info['floor_type'];
        $info['floor_type_name'] =$dbHouseVillageFloorType->getValues(['id'=>$floor_type],'name');

        //政治面貌
        $data['political_affiliation_title'] = $this->political_affiliation_title;
        $data['political_affiliation_arr'] = $this->political_affiliation_arr;
        //特殊人群
        $data['special_groups_title'] = $this->special_groups_title;
        $data['special_groups_arr'] = $this->special_groups_arr;
        // 重点人群
        $data['focus_groups_title'] = $this->focus_groups_title;
        $data['focus_groups_arr'] = $this->focus_groups_arr;
        // 弱势困难群体
        $data['vulnerable_groups_title'] = $this->vulnerable_groups_title;
        $data['vulnerable_groups_arr'] = $this->vulnerable_groups_arr;
        //党支部

        $party_where[] = ['status','<>','-1'];
        if(!$area_type) {
            $party_where[] = ['street_id', '=', $street_id];
        }else{
            $party_where[] = ['id','=',$info['party_id']];
        }
        $party_branch_list = $db_area_street_party_branch->getSome($party_where);

        $data['party_branch_list'] = $party_branch_list;
        $data['info'] = $info;
        return $data;
    }

    public function getInfoOfGrid($id,$street_id,$area_type)
    {
        $serviceHouseVillage =new HouseVillageService();
        $dbHouseVillageFloor =new HouseVillageFloor();
        $dbHouseVillageFloorType =new HouseVillageFloorType();
        $dbHouseVillageDataConfig =new HouseVillageDataConfig();
        $db_area_street_party_branch = new AreaStreetPartyBranch();
        $db_house_village_user_bind = new HouseVillageUserBind();
        $where[] = ['b.pigcms_id','=',$id];
        $field = 'l.*,b.pigcms_id,b.housesize,b.authentication_field,b.village_id,b.uid,b.name,b.phone,b.id_card,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,p.name as party_name,pbu.party_status,pbu.join_party_time,pbu.party_img,pbu.is_good_party,pbu.party_id';
        $info = $db_house_village_user_bind->getFind($where,$field);
        if ($info['authentication_field']) {
            $authentication_field = unserialize($info['authentication_field']);
            if (!$authentication_field) {
                $authentication_field = [];
            }
        } else {
            $authentication_field = [];
        }
//        $info['authentication_field']['person_phone']['value'] = '6464646464646';
//        dd($info['authentication_field']['person_phone']);
        if($info['join_party_time']){
            $info['join_party_time'] = date('Y-m-d',$info['join_party_time']);
        }else{
            $info['join_party_time'] = date('Y-m-d',time());
        }
        $info['user_special_groups'] = explode(',',$info['user_special_groups']);
        $info['user_focus_groups'] = explode(',',$info['user_focus_groups']);
        $info['user_vulnerable_groups'] = explode(',',$info['user_vulnerable_groups']);
        $info['party_img'] = dispose_url($info['party_img']);
        $info['address'] = $serviceHouseVillage->getSingleFloorRoom($info['single_id'],$info['floor_id'],$info['layer_id'],$info['vacancy_id'],$info['village_id']);

        $dbHouseVillage = new HouseVillage();
        $village_field = 'property_id';
        $village_info = $dbHouseVillage->getOne($info['village_id'],$village_field);
        $dataList = $dbHouseVillageDataConfig->getList(['property_id'=>$village_info['property_id']]);
        if (!empty($dataList)) {
            $dataList = $dataList->toArray();
        }
        foreach($dataList as $k=>&$v){
            if($v['type'] == 2){
                if($v['is_system'] == 1){
                    $v['use_field'] = $serviceHouseVillage->get_system_value($v['key']);
                }else{
                    $v['use_field'] = explode(',',$v['use_field']);
                }
            }elseif($v['type'] == 3){

                $v['use_field'] = [];
            }else{
                $v['use_field'] = [];
            }
            if (!$authentication_field || !isset($authentication_field[$v['key']]) || !$authentication_field[$v['key']]) {
                $authentication_field[$v['key']] = $v;
                $authentication_field[$v['key']]['value'] = '';
            }
        }
        if(isset($authentication_field['native_place']) || array_key_exists('native_place',$authentication_field)){
            if(isset($authentication_field['native_place']['province'])) {
                if ($authentication_field['native_place']['province'] == 'undefined' || $authentication_field['native_place']['province'] == '') {
                    $authentication_field['native_place']['province'] = 0;
                }
            }
            if(isset($authentication_field['native_place']['city'])) {
                if ($authentication_field['native_place']['city'] == 'undefined' || $authentication_field['native_place']['city'] == '') {
                    $authentication_field['native_place']['city'] = 0;
                }
            }
        }
        $info['authentication_field'] = $authentication_field;
        $data['dataList'] = $dataList;
        //房间类型
        $floor_info = $dbHouseVillageFloor->getOne(['floor_id'=>$info['floor_id']],'floor_type, floor_name, floor_layer');
        $floor_type = $floor_info['floor_type'];
        $info['floor_type_name'] =$dbHouseVillageFloorType->getValues(['id'=>$floor_type],'name');

        //政治面貌
        $data['political_affiliation_title'] = $this->political_affiliation_title;
        $data['political_affiliation_arr'] = $this->political_affiliation_arr;
        //特殊人群
        $data['special_groups_title'] = $this->special_groups_title;
        $data['special_groups_arr'] = $this->special_groups_arr;
        // 重点人群
        $data['focus_groups_title'] = $this->focus_groups_title;
        $data['focus_groups_arr'] = $this->focus_groups_arr;
        // 弱势困难群体
        $data['vulnerable_groups_title'] = $this->vulnerable_groups_title;
        $data['vulnerable_groups_arr'] = $this->vulnerable_groups_arr;
        //党支部

        $party_where[] = ['status','<>','-1'];
        if(!$area_type) {
            $party_where[] = ['street_id', '=', $street_id];
        }else{
            $party_where[] = ['id','=',$info['party_id']];
        }
        $party_branch_list = $db_area_street_party_branch->getSome($party_where);

        $data['party_branch_list'] = $party_branch_list;
        $data['info'] = $info;
        return $data;
    }


    /**
     * 查询党员所在房间列表
     * @author: liukezhu
     * @date : 2022/11/1
     * @param $param
     * @return mixed
     */
    public function getPartyMemberRoomInfo($param){
        $dbHouseVillageUserLabel =new HouseVillageUserLabel();
        $serviceHouseVillage =new HouseVillageService();
        $where[] = ['b.uid','=',$param['uid']];
        if ($param['street_id']) {
            $where[] = ['v.street_id','=',$param['street_id']];
        }
        if ($param['community_id']) {
            $where[] = ['v.community_id','=',$param['community_id']];
        }
        $where[] = ['l.user_political_affiliation','=',1];
        $where[] = ['b.status','<>',4];
        $where[] = ['pbu.party_status','in',[1,4,5]];
        $field='l.id,b.pigcms_id,b.village_id,b.uid,b.name,b.phone,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,v.street_id,v.community_id,s.area_name as street_name,c.area_name as community_name';
        $count = $dbHouseVillageUserLabel->getPartyMemberRoomCount($where);
        $list = $dbHouseVillageUserLabel->getPartyMemberRoomList($where,$field,$param['page'],$param['limit']);
        foreach ($list as &$val){
            $val['address'] = $serviceHouseVillage->getSingleFloorRoom($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
            unset($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id']);
        }
        $data['list'] = $list;
        $data['count'] = $count;
        return $data;
    }

}
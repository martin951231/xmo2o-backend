<?php
/**
 * 工作人员service
 * @datetime 2020/07/14
 * @author weili
**/
namespace app\community\model\service;

use app\community\model\db\HouseAdmin;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseVillage;
use app\community\model\db\PropertyGroup;
use app\community\model\db\HousePropertyVillageWorker;
class HouseWorkerService
{

    // 对应操作动作
    public $log_name = [
        'user_submit' => '用户提交成功',
        'property_admin_submit' => '物业管理员提交成功',// 物业管理员提交成功
        'property_work_submit' => '物业工作人员提交成功',// 物业工作人员提交成功
        'house_admin_submit' => '小区管理员提交成功',// 小区管理员提交成功
        'house_work_submit' => '小区工作人员提交成功',// 小区工作人员提交成功
        'property_admin_assign' => '物业管理员指派',// 物业管理员指派
        'property_work_assign' => '物业工作人员指派',// 物业工作人员指派
        'house_admin_assign' => '小区管理员指派',// 小区管理员指派
        'property_admin_reply' => '物业管理员回复',// 物业管理员回复
        'property_work_reply' => '物业工作人员回复',// 物业工作人员回复
        'house_admin_reply' => '小区管理员回复',// 小区管理员回复
        'reopen' => '重新打开','recall' => '撤回','closed' => '关闭','evaluate' => '评价',
        'work_reject_center' => '驳回给处理中心','work_follow_up' => '处理人员跟进','work_completed' => '处理人员结单',
        'work_change' => '处理人员转单','work_reject_change' => '被转单人员拒绝',
        'center_assign_work' => '处理中心指派','center_reply_submit' => '处理中心回复',
    ];
    public $worker_name=array(
        '0'=>'客服专员',
        '1'=>'维修技工',
    //	'2'=>'物业人员',
        '3'=>'保洁人员',
        '4'=>'保安人员',
        '5'=>'招商开发',
        '6'=>'财务',
        '7'=>'人力资源',
        '8'=>'管理决策',
        '9'=>'后勤',
        '99'=>'其他',
    );
    /**
     * 获取所有工作人员相关数据
     * @param array $where
     * @param string $field
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author weili
     * @datetime 2020/07/14 18:02
     */
    public function getWorker($where,$field='wid,name,type,phone')
    {
        $dbHouseWorker = new HouseWorker();
        $data = $dbHouseWorker->getAll($where,$field);
        return $data;
    }

    /**
     * 获取绑定了企业微信的工作人员
     * @param $where
     * @param string $field
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQyWorker($where,$field='qy_id')
    {
        $dbHouseWorker = new HouseWorker();
        $data = $dbHouseWorker->getAll($where,$field);
        $str = '';
        if($data){
            foreach ($data as $k=>$v){
                $str .= $v['qy_id'].',';
            }
            $str = rtrim($str,',');
        }
        return $str;
    }
    /**
     * 查询工作人员信息
     * @author lijie
     * @date_time 2020/11/02
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getOneWorker($where,$field=true)
    {
        $dbHouseWorker = new HouseWorker();
        $data = $dbHouseWorker->get_one($where,$field);
        if (!$data || $data->isEmpty()) {
            $data = [];
        }else{
            $data=$data->toArray();
        }
        return $data;
    }

    public function getNav($where,$type=false)
    {
        $db_property_group = new PropertyGroup();
        $property_group_info = $db_property_group->getList($where,'id,fid,name,sort',0,0,'id ASC')->toArray();
        $items = [];
        foreach ($property_group_info as $key=>$value){
            if($key == 0) {
                $fid = $value['fid'];
            }
            $list['permission_id']        = $value['id'];
            $list['parent_permission_id'] = $value['fid'];
            $list['title']                = $value['name'];
            $list['disabled']             = true;
            $list['slots']                = ['icon'=>'cluster'];
            $items[] = $list;
        }
        if($items)
            $res = $this->getTree($items,$fid,$where,$type);
        else
            $res = [];
        return $res;
    }

    public function getTree($arr=[],$fid,$where,$type=false){
        $temp_new_arr = [];
        foreach($arr as $k=>$v){
            if($v['parent_permission_id'] == $fid){
                $temp_arr = $v;
                $temp_arr['worker'] =$this->worker_list($v['permission_id'],$where,$type);
                if($temp_arr['worker'] == []){
                    unset($temp_arr['worker']);
                }
                $temp_arr['children'] =$this->getTree($arr,$v['permission_id'],$where,$type);
                foreach($temp_arr['worker'] as $k1=>$v1){
                    $list['title'] = $v1['title'];
                    $list['disabled'] = $v1['disabled'];
                    $list['slots'] = $v1['slots'];
                    $list['key'] = $v1['key'];
                    array_unshift($temp_arr['children'],$list);
                }
                unset($temp_arr['worker']);
                if(count($temp_arr['children']) == 0){
                    unset($temp_arr['children']);
                }
                $temp_new_arr[] = $temp_arr;
            }
        }
        return $temp_new_arr;
    }

    public function worker_list($department_id,$where,$type=false)
    {
        $where[] = ['department_id','=',$department_id];
        $where[] = ['qy_id','<>',''];
        $dbHouseWorker = new HouseWorker();
        $data = $dbHouseWorker->getAll($where,'wid,name,qy_id');
        if($data){
            foreach ($data as $k=>$v) {
                $data[$k]['title'] = $v['name'];
                $data[$k]['disabled'] = false;
                $data[$k]['slots'] = ['icon'=>'user'];
                //todo 加类型以区分
                if($type){
                    $data[$k]['key'] = $v['wid'].'-'.$v['name'];
                }else{
                    $data[$k]['key'] = $v['qy_id'].'-'.$v['name'];
                }
            }
        }
        return $data;
    }

    public function  get_worker_list($whereArr,$field='*',$page=1,$limit=20)
    {
        $dataArr = ['list' => array(), 'total_limit' => $limit, 'count' => 0];
        $dbHouseWorker = new HouseWorker();
        $count = $dbHouseWorker->getMemberCount($whereArr);
        if ($count > 0) {

            $dataArr['count'] = $count;
            $res = $dbHouseWorker->getWorkLists($whereArr, $field,'wid desc', $page, $limit);
            if (!empty($res)) {
                $houseVillageDb=new HouseVillage();
                $village_info=$houseVillageDb->getInfo(array('village_id'=>$res['0']['village_id']),'*');
                foreach ($res as $kk => $vv) {
                    $res[$kk]['set_pwd']=0;
                    if(!empty($vv['password'])){
                        $res[$kk]['set_pwd']=1;
                    }
                    $res[$kk]['list_phone']=$vv['phone'];
                    if(isset($vv['phone']) && !empty($vv['phone'])){
                        $res[$kk]['list_phone']=$houseVillageDb->phone_desensitization_v($vv['phone'],$vv['village_id'],$village_info);
                    }
                    unset($res[$kk]['password']);
                    $res[$kk]['create_time_str'] = '';
                    if ($vv['create_time'] > 0) {
                        $res[$kk]['create_time_str'] = date('Y-m-d H:i:s', $vv['create_time']);
                    }
                    $res[$kk]['type_name'] = '';
                    if(isset($this->worker_name[$vv['type']])){
                        $res[$kk]['type_name'] = $this->worker_name[$vv['type']];
                    }
                }
                $dataArr['list'] = $res;
            } else {
                $dataArr['list'] = array();
            }
        }
        return $dataArr;
    }

    public function  get_all_worker_list($whereArr,$field='*',$default=true){
        $dataArr = ['list' => array()];
        if($default){
            $defaultArr=array('wid'=>'','name'=>'请选择员工','phone'=>'','openid'=>'','relation_id'=>0);
            $dataArr['list'][]=$defaultArr;
        }
        $dbHouseWorker = new HouseWorker();
            $res = $dbHouseWorker->getWorkLists($whereArr, $field,'wid desc', 0);
            if (!empty($res) && !$res->isEmpty()) {
                $res=$res->toArray();
                foreach ($res as $kk => $vv) {
                    $vv['type_name'] = '';
                    if(isset($this->worker_name[$vv['type']])){
                        $vv['type_name'] = $this->worker_name[$vv['type']];
                    }
                    $vv['openid_desc'] = '未绑';
                    if(!empty($vv['openid'])){
                        $vv['openid_desc'] = '已绑';
                    }
                    if(empty($vv['name']) && !empty($vv['nickname'])){
                        $vv['name']=$vv['nickname'];
                    }
                    $dataArr['list'][] = $vv;
                }
               
            } 
        return $dataArr;
    }
    
    //更新数据
    public function updateHouseWorker($where = array(), $updateArr = array(), $now_worker = array())
    {
        if (empty($where) || empty($updateArr)) {
            return false;
        }
        $db_HouseWorker = new HouseWorker();
        $ret = $db_HouseWorker->editData($where, $updateArr);

        if (!empty($now_worker)) {
            if(isset($now_worker['cancel_wx_openid']) && $now_worker['cancel_wx_openid'] &&  isset($now_worker['hw_id']) && $now_worker['hw_id']>0){
                $whereWxArr=array(['village_id','>',0],['hw_id','=',$now_worker['hw_id']]);
                $db_HouseWorker->editData($whereWxArr, $updateArr);
                $tmpUpData=array('openid'=>'','nickname'=>'','avatar'=>'');
                $whereTmpWorker=array('hw_id'=>$now_worker['hw_id']);
                $housePropertyVillageWorkerDb=new HousePropertyVillageWorker();
                $housePropertyVillageWorkerDb->saveOneData($whereTmpWorker,$tmpUpData);
                return $ret;
            }
            $houseAdminDb = new HouseAdmin();
            $whereArr = array();
            if ($now_worker['xtype'] == 2 && $now_worker['relation_id'] > 0) {
                $whereArr['id'] = $now_worker['relation_id'];
            } else {
                $houseAdmin = $houseAdminDb->getOne(array('wid' => $now_worker['wid']));
                if ($houseAdmin && !$houseAdmin->isEmpty()) {
                    $houseAdmin = $houseAdmin->toArray();
                    $whereArr['id'] = $houseAdmin['id'];
                    $db_HouseWorker->editData(array('wid' => $now_worker['wid']), array('xtype' => 2, 'relation_id' => $houseAdmin['id']));
                }
            }
            if (!empty($whereArr)) {
                if (isset($updateArr['menus'])) {
                    $upres = $houseAdminDb->save_one($whereArr, $updateArr);
                } elseif (isset($updateArr['account'])) {
                    $updateArr['realname'] = $updateArr['name'];
                    unset($updateArr['name']);
                    if (isset($updateArr['password'])) {
                        $updateArr['pwd'] = $updateArr['password'];
                        unset($updateArr['password']);
                    }
                    $upres = $houseAdminDb->save_one($whereArr, $updateArr);
                }
            }
        }
        return $ret;
    }


    //todo 查询工作人员数据
    public function getWorkerList($where,$field,$order,$page,$limit){
        $dbHouseWorker = new HouseWorker();
        $count=$dbHouseWorker->getGroupWorkCount($where);
        if($count > 0){
            $list=$dbHouseWorker->getGroupWork($where,$field,$order,$page,$limit);
            if ($list && !$list->isEmpty()){
                $list=$list->toArray();
                if(!empty($list)){
                    foreach ($list as &$v){
                        $v['open_door'] = $v['open_door'] ? '是':'否';
                        $v['gender']    = $v['gender'] == 1 ? '男':'女';
                        $v['nickname']  = empty($v['openid'])  ? '--' : $v['nickname'];
                    }
                    unset($v);
                }
            }
        }
        else{
            $list=[];
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    //todo 校验工作人员是否绑定微信
    public function checkWorker($village_id,$wid){
        $where = [
            ['wid','=',$wid],
            ['village_id','=',$village_id],
            ['is_del','=',0],
        ];
        $field='wid,openid,nickname,avatar';
        $worker = $this->getOneWorker($where,$field);
        if(!$worker){
            throw new \think\Exception("该数据不存在");
        }
        if(!empty($worker['openid'])){
            $res=['status'=>1,'nickname'=>$worker['nickname'],'avatar'=>$worker['avatar']];
        }else{
            $res=['status'=>0,'nickname'=>'','avatar'=>''];
        }
        return $res;
    }
}

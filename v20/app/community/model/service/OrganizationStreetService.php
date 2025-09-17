<?php
namespace app\community\model\service;

use app\community\model\db\AreaStreetWorkers;
use app\community\model\db\AreaStreetOrganization;
use app\community\model\db\AreaStreet;
use app\community\model\db\AreaStreetWorkersLoginLog;
use app\community\model\db\HouseVillageGridMember;
use app\common\model\service\config\ConfigCustomizationService;
class OrganizationStreetService
{
    private $data = [];
    public function getNav($street_id)
    {
        $areaStreetdb = new AreaStreet();
        $where[] = ['area_id','=',$street_id];
        $areaStreetInfo = $areaStreetdb->getLists($where,'area_id,area_name');
        $arr_data = [];
        foreach ($areaStreetInfo as $key=>$val){
            $arr_data[$key]=[
                'title'=>$val['area_name'],
                'key'=>'0-'.$val['area_id'],
            ];
        }
        $dbAreaStreetOrganization = new AreaStreetOrganization();
        $map = [];
        $map[] = ['area_id','=',$street_id];
        $map[] = ['is_del','=',0];
        $map[] = ['area_type','=',0];
        $organization = $dbAreaStreetOrganization->getSelectList($map,'id,fid,name,sort','sort desc')->toArray();
//        dump($organization);die;
        $new_organization = [];
        foreach ($organization as $key=>$val){
            $new_organization[$key] = [
                'id'=>$val['id'],
                'fid'=>$val['fid'],
                'title'=> $val['name'],
                'key'=> '0-'.$street_id.'-'.$val['id'],
                'scopedSlots'=> ['title'=> "edit_out"],
            ];
        }
        $new_arr = $this->getTree($new_organization);
        $arr_data[0]['children'] = $new_arr;
        $key = [['key'=> "1-1", 'assets_id'=> 1],
            ['key'=> "4-4", 'assets_id'=> 4]];
        $data['menu_list'] = $arr_data;
        $data['key'] = $key;
        $data['street_id'] = $street_id;
        $configCustomizationService=new ConfigCustomizationService();
        $func_lantiancom_special=$configCustomizationService->getFuncZhihuilantiancomSpecial();
        $data['party_member_show'] = $func_lantiancom_special;
        return $data;
    }

    /**
     * Notes:获取部门详情
     * @param $id
     * @return array|\think\Model|null
     * @datetime: 2021/2/22 17:31
     */
    public function getNavInfo($id)
    {
        $dbAreaStreetOrganization = new AreaStreetOrganization();
        $where[] = ['id','=',$id];
        $info = $dbAreaStreetOrganization->getOne($where);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 添加编辑部门
     * @param $data
     * @param $id
     * @return bool|int|string
     * @throws \think\Exception
     * @datetime: 2021/2/22 18:06
     */
    public function subBranch($data,$id)
    {
        $dbAreaStreetOrganization = new AreaStreetOrganization();
        $where[] = ['name','=',$data['name']];
        $where[] = ['is_del','=',0];
        $where[] = ['area_id','=',$data['area_id']];
        $where[] = ['area_type','=',$data['area_type']];
        $info = $dbAreaStreetOrganization->getOne($where);
        if($id){
            if($info && $info['id'] != $id){
                throw new \think\Exception('该部门已存在');
            }
            $map[] = ['id','=',$id];
            unset($data['fid']);
            $res = $dbAreaStreetOrganization->saveOne($map,$data);
        }else{
            if($info){
                throw new \think\Exception('该部门已存在');
            }
            $data['add_time'] = time();
            $res = $dbAreaStreetOrganization->addOne($data);
        }
        return $res;
    }

    /**
     * Notes: 软删除
     * @param $id
     * @return bool
     * @datetime: 2021/2/23 9:41
     */
    public function delBranch($id)
    {
        $dbAreaStreetOrganization = new AreaStreetOrganization();
        $data['is_del'] = 1;
        $data['del_time'] = time();
        $arr = $this->recursion($id,[]);
        $arr[] = $id;
        foreach ($arr as $v){
            $dbAreaStreetOrganization->saveOne(['id'=>$v],$data);
            $this->delStreetWorker('find_in_set('.$v.',organization_ids)');
        }
        return true;
    }
    public function getTree($list,$fid ="fid")
    {
        $map  = [];
        $tree = [];
        foreach ($list as &$val) {
            $map[$val['id']] = &$val;
        }
        foreach ($list as &$val) {
            $parent = &$map[$val[$fid]];
            if($parent) {
                $parent['children'][] = &$val;
            }else{
                $tree[] = &$val;
            }
        }
        return $tree;
    }
    //获取部门
    public function getTissueNavList($street_id)
    {
        $dbAreaStreetOrganization = new AreaStreetOrganization();
        $map = [];
        $map[] = ['area_id','=',$street_id];
        $map[] = ['is_del','=',0];
        $map[] = ['area_type','=',0];
        $organization = $dbAreaStreetOrganization->getSelectList($map,'id,fid,name,sort','sort desc')->toArray();
        return $organization;
    }
    //------------------------------------------------------部门成员
    public function getMemberList($where,$field=true,$order='worker_id DESC',$page=1,$limit=15,$branch_id=0)
    {
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $db_area_street_organization = new AreaStreetOrganization();
        if($branch_id){
            $arr = $this->recursion($branch_id,[]);
            $arr[] = $branch_id;
            $data = $dbAreaStreetWorkers->getSelect($where,$field,$order,$page,$limit,$arr);
        }else{
            $data = $dbAreaStreetWorkers->getSelect($where,$field,$order,$page,$limit,$branch_id);
        }
        if($data){
            foreach ($data as $k=>$v){
                $organization_txt = [];
                if($v['organization_ids']){
                    $organization_ids_arr = explode(',',$v['organization_ids']);
                    $organization_list = $db_area_street_organization->getSelectList([['id','in',$organization_ids_arr]],'name','id DESC',0,0);
                    if($organization_list && !$organization_list->isEmpty()){
                        $organization_txt=array_column($organization_list->toArray(), 'name');
//                        foreach ($organization_list as $val){
//                            $organization_txt .= $val['name'].'-';
//                        }
//                        $organization_txt = rtrim($organization_txt, "-");
                    }
                }
                $data[$k]['organization_txt'] = $organization_txt;
                $data[$k]['grid_member_label']=$this->getGridMemberLabel($v['worker_id']);
                if($page>0 && $v['work_phone']){
                    $data[$k]['work_phone']=phone_desensitization($v['work_phone']);
                }
            }
        }
        return $data;
    }

    //todo 查询网格员对应关联的街道部门/社区
    public function getGridMemberLabel($workers_id){
        $db_house_village_grid_member = new HouseVillageGridMember();
        $db_area_street_organization = new AreaStreetOrganization();
        $db_area_street=new AreaStreet();
        $data=['status'=>0,'name'=>'--','color'=>'','tips'=>''];
        $where[]=['workers_id', '=', $workers_id];
        $grid_member=$db_house_village_grid_member->getOne($where,'business_id,business_type,area_id');
        if (!$grid_member || $grid_member->isEmpty()) {
            return $data;
        }
        $where=[];
        if($grid_member['business_type'] == 0){
            //街道
            $where[] = ['area_type', '=', 0];
            $where[] = ['area_id', '=', $grid_member['area_id']];
        }elseif ($grid_member['business_type'] == 1){
            //街道部门
            $where[] = ['id', '=', $grid_member['business_id']];
        }elseif ($grid_member['business_type'] == 2){
            //社区
            $where[] = ['area_id', '=', $grid_member['business_id']];
            $where[] = ['area_type', '=', 1];
        }
        if(!$where){
            return $data;
        }
        if($grid_member['business_type'] == 1){
            $result=$db_area_street_organization->getOne($where,'name');
            $data['color']='#FCBE79';
            $data['tips']='部门';
        }else{
            $result=$db_area_street->getOne($where,'area_name as name');
            $data['color']=$grid_member['business_type'] == 2 ? 'red' : '#1890FF';
            $data['tips']=$grid_member['business_type'] == 2 ? '社区' : '街道';
        }
        if($result && !$result->isEmpty()){
            $data['status']=1;
            $data['name']=$result['name'];
        }
        return $data;

    }

    /**
     * 获取工作人员数量
     * @author lijie
     * @date_time 2021/02/26
     * @param $where
     * @param int $branch_id
     * @return int
     */
    public function getWorkersCount($where,$branch_id=0)
    {
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        if(!$branch_id){
            $arr = 0;
        }else{
            $arr = $this->recursion($branch_id,[]);
            $arr[] = $branch_id;
        }
        $data = $dbAreaStreetWorkers->getCount($where,$arr);
        return $data;
    }

    public function getMemberInfo($worker_id)
    {
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $where[] = ['worker_id','=',$worker_id];
        $where[] = ['work_status','=',1];
        $info = $dbAreaStreetWorkers->getOne($where);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
        } else {
            $info = [];
        }
        $data['info'] = $info;
        return $data;
    }

    /**
     * 添加工作人员
     * @author lijie
     * @date_time 2021/02/26
     * @param $data
     * @return int|string
     */
    public function addStreetWorker($data)
    {
        $db_area_street_workers = new AreaStreetWorkers();
        unset($data['system_type']);
        $res = $db_area_street_workers->addOne($data);
        return $res;
    }

    /**
     * 删除工作人员
     * @author lijie
     * @date_time 2021/02/26
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delStreetWorker($where)
    {
        $db_area_street_workers = new AreaStreetWorkers();
        $res = $db_area_street_workers->delFind($where);
        return $res;
    }

    /**
     * 编辑工作人员
     * @author lijie
     * @date_time 2021/02/26
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveStreetWorker($where,$data)
    {
        $db_area_street_workers = new AreaStreetWorkers();
        unset($data['system_type']);
        $res = $db_area_street_workers->saveOne($where,$data);
        return $res;
    }
    /*
     * Notes:根据条件获取街道社区工作人员
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @author: wanzy
     * @date_time: 2021/2/27 9:40
     */
    public function getMemberDetail($where,$field =true,$order='work_status asc,worker_id desc')
    {
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $info = $dbAreaStreetWorkers->getOne($where,$field,$order);
        if ($info && !$info->isEmpty()) {
            $info = $info->toArray();
            if (isset($info['work_head']) && $info['work_head']) {
                $info['work_head_txt'] = replace_file_domain($info['work_head']);
            }
        } else {
            $info = [];
        }
        return $info;
    }

    /**
     * Notes: 根据条件更改工作人员信息
     * @param $where
     * @param $save
     * @return bool
     * @author: wanzy
     * @date_time: 2021/2/27 10:23
     */
    public function saveStreetWork($where, $save) {
        $dbAreaStreetWorkers = new AreaStreetWorkers();
        $info = $dbAreaStreetWorkers->saveOne($where,$save);
        return $info;
    }


    /**
     * 添加登录记录
     * Notes:
     * @param $data
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/2/27 10:57
     */
    public function addLoginLog($data) {
        // 初始化 街道社区管理员 数据层
        $db_area_street_worker_login_log = new AreaStreetWorkersLoginLog();
        $area_id = $db_area_street_worker_login_log->add($data);
        return $area_id;
    }

    /**
     * 部门详情
     * @author lijie
     * @date_time 2021/03/01
     * @param $where
     * @param $field
     * @return array|\think\Model|null
     */
    public function getOrganizationInfo($where,$field=true)
    {
        $db_area_street_organization = new AreaStreetOrganization();
        $data = $db_area_street_organization->getOne($where,$field);
        return $data;
    }

    /**
     * 部门下所有子部门
     * @author lijie
     * @date_time 2021/03/05
     * @param int $branch_id
     * @param array $arr
     * @param int $is_last
     * @return array
     */
    public function  recursion($branch_id=0,$arr = [],$is_last=1)
    {
        $this->data=$arr;
        $info = $this->getOrganizationList(['fid'=>$branch_id,'is_del'=>0],'id');
        if($info){
            foreach ($info as $k=>$v){
                $this->data[] = $v['id'];
                if(count($info) == $k+1){
                    $res =  $this->recursion($v['id'],$this->data,1);
                    $this->data = $res;
                    return $this->data;
                }else{
                    $this->recursion($v['id'],$this->data,0);
                }
            }
        }elseif ($is_last==1){
            return $this->data;
        }
        return $this->data;
    }

    public function getOrganizationList($where,$field=true)
    {
        $db_area_street_organization = new AreaStreetOrganization();
        $data = $db_area_street_organization->getSelectList($where,$field)->toArray();
        return $data;
    }



    //todo 校验工作人员是否绑定微信
    public function checkWorker($street_id,$wid){
        $where = [
            ['worker_id','=',$wid],
            ['area_id','=',$street_id],
            ['work_status','<>',4],
        ];
        $field='worker_id,openid,nickname,avatar';
        $worker = (new AreaStreetWorkers())->getOne($where,$field);
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

    //todo 取消工作人员绑定微信
    public function cancelWorkerBind($street_id,$wid){
        $info= $this->checkWorker($street_id,$wid);
        if(!$info['status']){
            throw new \think\Exception("已取消绑定，请刷新页面");
        }
        $worker = (new AreaStreetWorkers())->saveOne([
            ['worker_id','=',$wid],
            ['area_id','=',$street_id],
            ['work_status','<>',4],
        ],[
            'nickname'=>'',
            'openid'=>'',
            'avatar'=>''
        ]);
        return ['status'=>1];
    }
}

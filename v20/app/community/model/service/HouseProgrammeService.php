<?php
/**
 * @author : liukezhu
 * @date : 2022/3/22
 */

namespace app\community\model\service;


use app\community\model\db\HouseAdminGroup;
use app\community\model\db\HouseAdminGroupLabelRelation;
use app\community\model\db\HouseProgramme;
use app\community\model\db\HouseProgrammeRelation;
use app\community\model\db\HouseWorker;
use app\community\model\db\PropertyGroup;

class HouseProgrammeService
{
    protected $time;
    protected $HouseProgramme;
    protected $HouseAdminGroup;
    protected $HouseAdminGroupService;
    protected $PropertyGroup;
    protected $HouseWorker;
    protected $HouseProgrammeRelation;
    protected $HouseAdminGroupLabel;
    public $org_u='org_u'; //用户标识
    public $org_b='org_b'; //部门标识
    public $org_s='org_s'; //社区标识

    public function __construct()
    {
        $this->time=time();
        $this->HouseProgramme =  new HouseProgramme();
        $this->HouseAdminGroup =  new HouseAdminGroup();
        $this->HouseAdminGroupService =  new HouseAdminGroupService();
        $this->PropertyGroup = new PropertyGroup();
        $this->HouseWorker = new HouseWorker();
        $this->HouseProgrammeRelation = new HouseProgrammeRelation();
        $this->HouseAdminGroupLabel = new HouseAdminGroupLabelRelation();
    }

    /**
     * 查询列表数据
     * @author: liukezhu
     * @date : 2022/3/22
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getProgrammeList($where,$field=true,$order='id DESC',$page=0,$limit=10){
        $list = $this->HouseProgramme->getList($where,$field,$order,$page,$limit);
        if($list){
            foreach ($list as &$v){
                $v['worker']=$this->HouseProgrammeRelation->getRelationWorkColumn([
                    ['r.pid', '=',$v['id']],
                    ['r.village_id', '=',$v['village_id']]
                ],'w.name');
            }
            unset($v);
        }
        $count = $this->HouseProgramme->getCount($where);
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * 获取分组列表
     * @author: liukezhu
     * @date : 2022/3/23
     * @param $where
     * @param $field
     * @return array|bool
     */
    public function getGroup($where,$field){
        $list=$this->HouseAdminGroupService->getGroupList($where,$field);
        if(!$list){
            return [];
        }
        return $list;
    }

    //todo 获取单条方案数据
    public function getFind($where,$field=true){
        $data = $this->HouseProgramme->getOne($where,$field);
        if($data && !$data->isEmpty()){
            return $data->toArray();
        }else{
            throw new \think\Exception('数据不存在');
        }
    }

    //todo 编辑单条数据
    public function programmeEdit($where,$data){
        $result=$this->getFind($where,'id');
        return $this->HouseProgramme->saveOne(['id'=>$result['id']],$data);
    }

    //todo 获取部门数据 $source=1给工单分类单独使用
    public function businessNav($village_id,$type=1,$property_id=0,$source=0){
        $where[] = ['is_del','=',0];
        if($property_id == 0){
            $where[] = ['village_id','=',$village_id];
        }else{
            $where[] = ['property_id','=',$property_id];
        }
        $dataList=$this->PropertyGroup->getList($where,'id,fid,name as title,type,village_id',0,10,'sort desc,id desc');
        if(!$dataList || $dataList->isEmpty()) {
            return ['key'=>[],'data'=>[]];
        }
        $dataList = $dataList->toArray();
        $department_id = array_column($dataList, 'id');
        $work=$key_all=[];
        if($type == 1){
            $work=$this->HouseWorker->getAll([
                ['is_del','=',0],
                ['status','<>',4],
                ['department_id','in',$department_id]
            ],'wid,name as title,phone,department_id');
            if($work && !$work->isEmpty()){
                $work = $work->toArray();
            }
        }
        foreach ($dataList as &$v){
            $v['key']=$this->org_b.$v['id'];
            $v['title']=$v['title'].'-'.($v['type'] == 0 ? '小区' : '部门').'';
            if($work){
                foreach ($work as &$v2){
                    if($v['id'] == $v2['department_id']){
                        if($source > 0){
                            $v2['key']=$v2['wid'].'-'. $v2['title'];
                        }else{
                            $v2['key']=$this->org_u.$v2['wid'];
                        }
                        $v2['title']=$v2['title'].'-人员';
                        $v['children'][]=$v2;
                    }
                }
            }
            $key_all[]=$v['key'];
            if($source > 0 && (!isset($v['children']) ||  in_array($v['type'],[0,1,2,99]))){
                $v['disabled']=true;
            }
        }
        $new_arr = (new OrganizationStreetService())->getTree($dataList);
        return ['key'=>$key_all,'data'=>$new_arr];
    }

    //todo 校验选择人员数据
    public function checkWidAll($param){
        $widAll=[];
        foreach ($param['wid_all'] as $v){
            if(strpos($v, $this->org_u) !== false){
                $widAll[]=explode($this->org_u,$v)[1];
            }
        }
        if(empty($widAll)){
            throw new \think\Exception('选择的部门暂无人员，请重新选择部门！');
        }
        sort($widAll);
        $widAll=array_unique($widAll);
        if(count($widAll) > 30){
            throw new \think\Exception('当前选择了'.count($widAll).'个，最多可选择30个人员！');
        }
        $param['wid_all']=$widAll;
        return $param;
    }

    //todo 删除关系表
    public function programmeRelationDel($where){
        return $this->HouseProgrammeRelation->delOne($where);
    }

    /**
     * 操作写入关系表
     * @author: liukezhu
     * @date : 2022/3/24
     * @param $village_id  小区id
     * @param $wid_all     工作人员wid
     * @param $pid         方案id
     * @return int
     */
    public function programmeRelation($village_id,$wid_all,$pid){
        if(empty($wid_all)){
            return true;
        }
        $this->programmeRelationDel([['village_id','=',$village_id],['pid','=',$pid]]);
        $data=[];
        foreach ($wid_all as $v){
            $data[]=[
                'village_id'=>$village_id,
                'wid'=>$v,
                'pid'=>$pid,
                'add_time'=>date('Y-m-d H:i:s',$this->time)
            ];
        }
        return $this->HouseProgrammeRelation->addAll($data);
    }

    /**
     * 查询数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @param $village_id
     * @param $id
     * @return mixed
     * @throws \think\Exception
     */
    public function find($village_id,$id){
        $where[]=['id', '=', $id];
        $where[]=['del_time', '=', 0];
        $where[]=['village_id','=',$village_id];
        $list = $this->getFind($where,'id,title,group_id,remarks');
        if($list){
            $relation=$this->HouseProgrammeRelation->getColumn([
                ['village_id','=',$village_id],
                ['pid','=',$id]
            ],'wid');
            if($relation){
                foreach ($relation as &$v){
                    $v=$this->org_u.$v;
                }
            }
            $list['wid_all']=$relation;
        }
        return $list;
    }

    /**
     *添加数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @param $param
     * @return int|string
     * @throws \think\Exception
     */
    public function add($param){
        $rr=$this->checkWidAll($param);
        $wid_all=$rr['wid_all'];unset($rr['wid_all']);
        $result=$this->HouseProgramme->addOne($rr);
        if(!$result){
            throw new \think\Exception('添加失败，请重试！');
        }
        $this->programmeRelation($rr['village_id'],$wid_all,$result);
        return $result;
    }

    /**
     * 编辑数据
     * @author: liukezhu
     * @date : 2022/3/23
     * @param $village_id
     * @param $id
     * @param $param
     * @return mixed
     * @throws \think\Exception
     */
    public function edit($village_id,$id,$param){
        $rr=$this->checkWidAll($param);
        $wid_all=$rr['wid_all'];unset($rr['wid_all']);
        $data=$this->getFind([
            ['id', '=', $id],
            ['village_id','=',$village_id],
            ['del_time', '=', 0]
        ]);
        $result = $this->programmeEdit([['id', '=', $data['id']]],$rr);
        if(!$result){
            throw new \think\Exception('编辑失败，请重试！');
        }
        $this->programmeRelation($village_id,$wid_all,$id);
        return $result;
    }

    //todo 查询该工作人员下绑定标签
    public function getProgrammeWid($village_id,$wid,$group_id=0,$page = 0, $limit=10){
        $groupID=$this->getProgrammeGroupId($village_id,$wid,$group_id);
        if(empty($groupID)){
            return '';
        }
        $data=$this->HouseAdminGroupLabel->getGroupLabel([
            ['g.group_id','in',$groupID],
            ['g.village_id','=',$village_id],
//            ['c.status','=',1],
//            ['b.status','=',0],
//            ['b.is_delete','=',0]
        ],'g.label_id desc','c.cat_name,b.label_name',$page,$limit);
        if($data && !$data->isEmpty()){
            $data= $data->toArray();
        }
        $label=[];
        if($data){
            foreach ($data as $k=>$v){
                $label[]=$v['cat_name'].'/'.$v['label_name'];
            }
        }
        if($label){
            return $label;
        }
        else{
            return '';
        }
    }

    //todo 获取工作人员下所有分组id
    public function getProgrammeGroupId($village_id,$wid,$group_id=0){
        $groupID=[];
        $list= $this->HouseProgrammeRelation->getProgrammeRelationWid([
            ['r.wid', '=', $wid],
            ['r.village_id','=',$village_id],
        ],'p.group_id');
        if($group_id > 0){
            $groupID[]=$group_id;
        }
        if(!empty($groupID)){
            $groupID=array_merge($list,$groupID);
        }
        else{
            $groupID=$list;
        }
        return !empty($groupID) ? array_values(array_unique($groupID)) : [];
    }


    //二维数组转一维
    public function once_array($arr){
        $arr2=[];
        foreach ($arr as $k1=>$v1){
            if (is_array($v1)) {
                foreach ($v1 as $k2=>$v2){
                    $arr2[]=$v2;
                }
            }
        }
        return $arr2;
    }

    //todo 查询工作人员下所有menus
    public function getProgrammeGroupMenus($village_id,$wid,$group_id=0,$menus){
        $list=$this->getProgrammeGroupId($village_id,$wid,$group_id);
        if(empty($list)){
            return $menus;
        }
        $data=$this->HouseAdminGroup->getColumn([
            ['status', '=', 1],
            ['group_id','in',$list]
        ],'group_menus');
        if(empty($data)){
            return $menus;
        }
        foreach ($data as &$v){
            if($v){
                $v=explode(',',$v);
            }
        }
        unset($v);
        $data=$this->once_array($data);
        if(empty($data)){
            return $menus;
        }
        $data=array_unique($data);
        if($menus){
            $data=array_unique(array_merge($data,explode(',',$menus)));
        }
        return implode(',',$data);
    }

    //todo 获取返回部门id集合
    public function getOrgId($param){
        $data=[];
        foreach ($param as $v){
            if(!empty($v) && strpos($v,$this->org_b) !==false){
                $data[]= explode($this->org_b,$v)[1];;
            }
        }
        $data=$data ? array_values(array_unique($data)) : [];
        return $data;
    }

    //todo 组装返回部门标识
    public function asseOrgId($param){
        foreach ($param as &$v){
            $v=$this->org_b.$v;
        }
        return $param;
    }


}
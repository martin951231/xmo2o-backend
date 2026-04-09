<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/8/12 15:29
 */

namespace app\community\model\service;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\HouseNewRepairCate;
use app\community\model\db\HouseNewRepairCateCustom;
use app\community\model\db\HouseNewRepairCateGroupRelation;
use app\community\model\db\HouseNewRepairDirectorScheduling;
use app\community\model\db\HouseNewRepairSubject;
use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseWorker;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\PropertyGroup;
use app\community\model\service\workorder\HouseCommunityAboutService;
use app\community\model\service\workorder\HouseCommunityHandleNoticeService;
use app\community\model\service\workorder\HouseCommunityWorkOrderService;
use app\consts\WorkOrderConst;
use app\community\model\db\WisdomQrcodeRecordLog;
use think\facade\Cache;
use think\facade\Db;

class RepairCateService
{
    public $status_subject = [1 => '正常', 2 => '关闭'];


   //todo 查询分类数据
   public function getRepairCate($where, $field=true, $page=0, $limit=10,$order=''){
       $db_repair_cate = new HouseNewRepairCate();
       $cate_list = $db_repair_cate->getList($where, $field, $page, $limit,$order);
       $count = $db_repair_cate->getCount($where);
       return ['count'=>$count,'list'=>$cate_list];
   }

   //todo 查询分类列表
    public function getCateList($where,$data)
    {
        $db_repair_cate = new HouseNewRepairCate();
        $cate_list = $db_repair_cate->getList($where, '*', $data['page'], $data['limit'],'status ASC,sort DESC,id desc');
        if (!empty($cate_list)) {
            $cate_list = $cate_list->toArray();
            if (!empty($cate_list)) {
                foreach ($cate_list as &$v) {
                    $v['status1'] =$v['status'];
                    if ($v['status'] == 1) {
                        $v['status'] = '开启';
                    }else{
                        $v['status'] = '关闭';
                    }
                    if ($v['type'] == 1){
                        $v['type_txt'] = '单人';
                    }else if($v['type'] == 2){
                        $v['type_txt'] = '多人';
                    }else{
                        $v['type_txt'] = '--';
                    }
					if ($v['charge_type'] == 1){
						$v['charge_type_text'] = "收费";
					}else{
						$v['charge_type_text'] = "";
					}
                }
            }
        }
        $count = $db_repair_cate->getCount($where);
        $data = [];
        $data['count'] = $count;
        $data['list'] = $cate_list;
        return $data;

    }

    //todo 添加分类数据
   public function addRepairCate($data){
       return (new HouseNewRepairCate())->addOne($data);
   }

    //todo 编辑分类数据
   public function editRepairCate($where,$data){
       return (new HouseNewRepairCate())->saveOne($where, $data);
   }

    //todo 查询单条分类数据
   public function queryRepairCate($where, $field=true){
       return (new HouseNewRepairCate())->getOne($where,$field);
   }

   //todo 校验同小区下分类名称不可重复
   public function checkRepairCateOnly($village_id,$cate_name,$id=0){
       $where[] = ['village_id','=',$village_id];
       $where[] = ['cate_name', '=', $cate_name];
       $where[] =  ['status', '<>', 4];
       if(!empty($id)){
           $where[] =  ['id', '<>', $id];
       }
       $cate_info = (new HouseNewRepairCate())->getOne($where);
       if (!empty($cate_info)) {
           throw new \think\Exception("分类名称不能重复");
       }
       return true;
   }

   //todo 生成投诉建议和在线报修
   public function checkRepairCate($property_id,$village_id){
       $title1='投诉建议';
       $title2='在线报修';
       $time= time();
       $key1=md5('new_repair_cate_tousu'.$property_id.$village_id);
       $key2=md5('new_repair_cate_jianyi'.$property_id.$village_id);
       $is_tousu= Cache::get($key1);
       if(!$is_tousu){
           $where=[];
           $where[] = ['property_id', '=', $property_id];
           $where[] = ['village_id','=',$village_id];
           $where[] = ['cate_name', '=', $title1];
           $tousu=$this->queryRepairCate($where);
           if(!$tousu){
               $add_data= [
                   'cate_name' => $title1,
                   'color' => '#4D80FD',
                   'parent_id'=>0,
                   'village_id' => $village_id,
                   'property_id' => $property_id,
                   'add_time' =>$time,
                   'update_time' => $time,
               ];
               $this->addRepairCate($add_data);
           }
           Cache::set($key1,'1',86400 * 2);
       }
       $is_jianyi= Cache::get($key2);
       if(!$is_jianyi){
           $where=[];
           $where[] = ['property_id', '=', $property_id];
           $where[] = ['village_id','=',$village_id];
           $where[] = ['cate_name', '=', $title2];
           $tousu=$this->queryRepairCate($where);
           if(!$tousu){
               $add_data= [
                   'cate_name' => $title2,
                   'color' => '#FFB425',
                   'parent_id'=>0,
                   'village_id' => $village_id,
                   'property_id' => $property_id,
                   'add_time' =>$time,
                   'update_time' => $time,
               ];
               $this->addRepairCate($add_data);
           }
           Cache::set($key2,'1',86400 * 2);
       }
       return true;

   }

   //todo 查询分类数据
   public function getNewRepairCate($where, $field=true, $page=0, $limit=10,$order=''){
      return (new HouseNewRepairCate())->getList($where, $field, $page, $limit,$order);
   }

    /**
     * 查询类目列表
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:53
     */
    public function getSubjectList($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $subject_list = $db_repair_subject->getList(['village_id' => $data['village_id'], 'status' => [1, 2], 'type' => 1]);
        if (empty($subject_list)) {
            $this->addSubjectDefult($data['village_id'], $data['property_id']);
        } else {
            $subject_list = $subject_list->toArray();
            if (empty($subject_list)) {
                $this->addSubjectDefult($data['village_id'], $data['property_id']);
            } else {
                $defult_name = 0;
                $defult_name1 = 0;
                foreach ($subject_list as $vv) {
                    if ($vv['subject_name'] == '在线报修') {
                        $defult_name = 1;
                    }
                    if ($vv['subject_name'] == '投诉建议') {
                        $defult_name1 = 1;
                    }
                }
                if (empty($defult_name) && empty($defult_name1)) {
                    $this->addSubjectDefult($data['village_id'], $data['property_id']);
                } elseif (empty($defult_name) && !empty($defult_name1)) {
                    $this->addSubjectDefult($data['village_id'], $data['property_id'], 1);
                } elseif (!empty($defult_name) && empty($defult_name1)) {
                    $this->addSubjectDefult($data['village_id'], $data['property_id'], 2);
                }
            }
        }
        $subjectList = $db_repair_subject->getList(['village_id' => $data['village_id'], 'status' => [1, 2], 'type' => 1], '*', $data['page'], $data['limit'])->toArray();
        foreach ($subjectList as &$vv) {
            $vv['status1'] = $vv['status'];
            if ($vv['status'] == 1) {
                $vv['status'] = '开启';
            }
            if ($vv['status'] == 2) {
                $vv['status'] = '关闭';
            }
        }
        $count = $db_repair_subject->getCount(['village_id' => $data['village_id'], 'status' => [1, 2], 'type' => 1]);
        $data = [];
        $data['count'] = $count;
        $data['list'] = $subjectList;
        return $data;
    }

    /**
     * 查询类目详情
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:53
     */
    public function getSubjectInfo($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $subject_info = $db_repair_subject->getOne(['village_id' => $data['village_id'], 'id' => $data['id']]);
        if (empty($subject_info)) {
            throw new \think\Exception("暂无数据");
        }
        if ($subject_info['subject_name'] == '在线报修' || $subject_info['subject_name'] == '投诉建议') {
            $subject_info['flag'] = 1;
        } else {
            $subject_info['flag'] = 0;
        }
        if ($subject_info['subject_name'] == '个人报修' || $subject_info['subject_name'] == '公共报修'||$subject_info['subject_name'] == '投诉'||$subject_info['subject_name'] == '建议') {
            $subject_info['flag1'] = 1;
        } else {
            $subject_info['flag1'] = 0;
        }
        return $subject_info;
    }

    /**
     * 添加类目
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function addSubject($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $where = [
            ['subject_name', '=', $data['subject_name']],
            ['status', '<>', 4],
            ['type', '=', 1],
        ];
        $subject_info = $db_repair_subject->getOne($where);
        if (!empty($subject_info)) {
            throw new \think\Exception("类目名称不能重复");
        }
        $add_data = [
            'subject_name' => $data['subject_name'],
            'color' => $data['color'],
            'village_id' => $data['village_id'],
            'property_id' => $data['property_id'],
            'status' => $data['status'],
            'type' => 1,
            'add_time' => time(),
            'update_time' => time(),
        ];
        $id = $db_repair_subject->addOne($add_data);
        return $id;
    }

    /**
     * 编辑类目
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function editSubject($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $where = [
            'id' => $data['id'],
            'village_id' => $data['village_id'],
        ];
        $subject_info = $db_repair_subject->getOne($where);
        if (empty($subject_info)) {
            throw new \think\Exception("当前类目不存在");
        }
        //   print_r($data);exit;
        if ($subject_info['subject_name'] == '在线报修' || $subject_info['subject_name'] == '投诉建议') {
            $data['subject_name'] = $subject_info['subject_name'];
        } else {
            $where1 = [
                ['id', '<>', $data['id']],
                ['subject_name', '=', $data['subject_name']],
                ['status', '<>', 4],
                ['type', '=', 1],
            ];
            $subject_info1 = $db_repair_subject->getOne($where1);
            if (!empty($subject_info1)) {
                throw new \think\Exception("类目名称不能重复");
            }
        }
        $save_data = [
            'subject_name' => $data['subject_name'],
            'color' => $data['color'],
            'status' => $data['status'],
            'update_time' => time(),
        ];
        $res = $db_repair_subject->saveOne($where, $save_data);
        return $res;
    }

    /**
     * 查询类别列表
     * @return array|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:53
     */
    public function getCategoryList($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();

        $subject_list = $db_repair_subject->getList(['village_id' => $data['village_id'], 'parent_id' => $data['parent_id'], 'status' => [1, 2], 'type' => 2], '*', $data['page'], $data['limit']);
        if (!empty($subject_list)) {
            $subject_list = $subject_list->toArray();
            if (!empty($subject_list)) {
                foreach ($subject_list as &$vv) {
                    if ($vv['status'] == 1) {
                        $vv['status'] = '开启';
                    }
                    if ($vv['status'] == 2) {
                        $vv['status'] = '关闭';
                    }
                    if ($vv['subject_name']=='个人报修'||$vv['subject_name']=='公共报修'||$vv['subject_name']=='投诉'||$vv['subject_name']=='建议'){
                        $vv['flag1'] = 1;
                    }else{
                        $vv['flag1'] = 0;
                    }
                }
            }
        }
        $count = $db_repair_subject->getCount(['village_id' => $data['village_id'], 'parent_id' => $data['parent_id'], 'status' => [1, 2], 'type' => 2]);
        $data = [];
        $data['count'] = $count;
        $data['list'] = $subject_list;
        return $data;

    }


    /**
     * 添加类别
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function addCategory($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $where = [
            ['subject_name', '=', $data['subject_name']],
            ['status', '<>', 4],
            ['type', '=', 2],
        ];
        $subject_info = $db_repair_subject->getOne($where);
        if (!empty($subject_info)) {
            throw new \think\Exception("类别名称不能重复");
        }
        $add_data = [
            'subject_name' => $data['subject_name'],
            'village_id' => $data['village_id'],
            'property_id' => $data['property_id'],
            'parent_id' => $data['parent_id'],
            'status' => $data['status'],
            'type' => 2,
            'add_time' => time(),
            'update_time' => time(),
        ];
        $id = $db_repair_subject->addOne($add_data);
        return $id;
    }

    /**
     * 编辑类别
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function editCategory($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $where = [
            'id' => $data['id'],
            'village_id' => $data['village_id'],
        ];
        $subject_info = $db_repair_subject->getOne($where);
        if (empty($subject_info)) {
            throw new \think\Exception("当前类别不存在");
        }
        $where1 = [
            ['parent_id', '=', $data['parent_id']],
            ['id', '<>', $data['id']],
            ['subject_name', '=', $data['subject_name']],
            ['status', '<>', 4],
            ['type', '=', 2],
        ];
        $subject_info1 = $db_repair_subject->getOne($where1);
        if (!empty($subject_info1)) {
            throw new \think\Exception("类别名称不能重复");
        }
        $save_data = [
            'subject_name' => $data['subject_name'],
            'status' => $data['status'],
            'update_time' => time(),
        ];
        $res = $db_repair_subject->saveOne($where, $save_data);
        return $res;
    }

    /**
     * 删除类别
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function delCategory($data)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $where = [
            'id' => $data['id'],
            'village_id' => $data['village_id'],
        ];
        $res = $db_repair_subject->saveOne($where, ['status' => 4]);
        return $res;
    }

    /**
     * 添加默认类目
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function addSubjectDefult($village_id, $property_id, $type = 0)
    {
        $db_repair_subject = new HouseNewRepairSubject();
        $add_data = [];
        $id = [];
        $add_data[0] = [
            'subject_name' => '在线报修',
            'color' => '#4D80FD',
            'village_id' => $village_id,
            'property_id' => $property_id,
            'type' => 1,
            'add_time' => time(),
            'update_time' => time(),
        ];
        $add_data[1] = [
            'subject_name' => '投诉建议',
            'color' => '#FFB425',
            'village_id' => $village_id,
            'property_id' => $property_id,
            'type' => 1,
            'add_time' => time(),
            'update_time' => time(),
        ];

        if ($type == 1) {
            unset($add_data[1]);
        }
        if ($type == 2) {
            unset($add_data[0]);
        }

        foreach ($add_data as $v) {
            $ids = $db_repair_subject->addOne($v);
            $id[] = $ids;
            if ($ids > 0) {
                if ($v['subject_name'] == '投诉建议') {
                    $add_cate_data = [];
                    $add_cate_data[0] = [
                        'subject_name' => '投诉',
                        'parent_id' => $ids,
                        'village_id' => $village_id,
                        'property_id' => $property_id,
                        'type' => 2,
                        'add_time' => time(),
                        'update_time' => time(),
                    ];
                    $add_cate_data[1] = [
                        'subject_name' => '建议',
                        'parent_id' => $ids,
                        'village_id' => $village_id,
                        'property_id' => $property_id,
                        'type' => 2,
                        'add_time' => time(),
                        'update_time' => time(),
                    ];
                } else {
                    $add_cate_data = [];
                    $add_cate_data[0] = [
                        'subject_name' => '个人报修',
                        'parent_id' => $ids,
                        'village_id' => $village_id,
                        'property_id' => $property_id,
                        'type' => 2,
                        'add_time' => time(),
                        'update_time' => time(),
                    ];
                    $add_cate_data[1] = [
                        'subject_name' => '公共报修',
                        'parent_id' => $ids,
                        'village_id' => $village_id,
                        'property_id' => $property_id,
                        'type' => 2,
                        'add_time' => time(),
                        'update_time' => time(),
                    ];
                }
                foreach ($add_cate_data as $vv) {
                    $idc[] = $db_repair_subject->addOne($vv);
                    // print_r($idc);exit;
                }
            }
        }
        return $id;
    }

    /**
     * 查询分类详情
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 15:23
     */
    public function getCateInfo($data)
    {
        $db_repair_cate = new HouseNewRepairCate();
        $db_repair_director_scheduling = new HouseNewRepairDirectorScheduling();
        $db_house_worker = new HouseWorker();
        $cate_info = $db_repair_cate->getOne(['village_id' => $data['village_id'], 'id' => $data['id']]);
        if (empty($cate_info) || $cate_info->isEmpty()) {
            throw new \think\Exception("暂无数据");
        }
        $cate_info=$cate_info->toArray();
        if ($cate_info['status'] == 1) {
            $cate_info['status1'] = '开启';
        } else {
            $cate_info['status1'] = '关闭';
        }
        $cate_info['director_id'] = [];
        $cate_info['scheduling'] = array('id1'=>array(), 'id2'=> array(), 'id3'=>array(), 'id4'=> array(), 'id5'=>array(), 'id6'=>array(), 'id7'=> array());
        if ($cate_info['type'] == 1) {
            $cate_info['type1'] = '单人';
            if (empty($cate_info['uid'])) {
                $cate_info['card_show'] = 1;
            } else {
                $cate_info['card_show'] = 2;
                $worker_info = $db_house_worker->get_one(['wid' => $cate_info['uid']], 'name');
                if (!empty($worker_info)) {
                    $cate_info['usernmae'] = $worker_info['name'];
                }
            }
        }
        else {
            $cate_info['type1'] = '多人';
            $scheduling_info = $db_repair_director_scheduling->getList(['cate_id' => $cate_info['id']]);
            if (empty($scheduling_info)) {
                $cate_info['card_show'] = 1;
            } else {
                $scheduling_info = $scheduling_info->toArray();
                if (empty($scheduling_info)) {
                    $cate_info['card_show'] = 1;
                } else {
                    $ids = [];
                    foreach ($scheduling_info as $v) {
                        $ids[] = $v['id'];

                        if($v['date_type']==0){
                            $cate_info['scheduling']['id7'][]=$v['id'];
                        }else{
                            $scheduling_index='id'.$v['date_type'];
                            $cate_info['scheduling'][$scheduling_index][]=$v['id'];
                        }

                    }
                    $cate_info['director_id'] = $ids;
                    $cate_info['card_show'] = 2;
                }
            }

        }
        $cate_info['name'] = $cate_info['cate_name'];
        $cate_info['timely_time'] =!empty($cate_info['timely_time']) ? gmdate("H:i",($cate_info['timely_time']*60)) : '00:00';
        return $cate_info;
    }


    /**
     * 添加分类
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:31
     */
    public function addCate($data)
    {
        $db_repair_cate = new HouseNewRepairCate();
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $where = [
            ['village_id', '=', $data['village_id']],
            ['cate_name', '=', $data['cate_name']],
            ['status', '<>', 4],
        ];
        $cate_info = $db_repair_cate->getOne($where);
        if (!empty($cate_info)) {
            throw new \think\Exception("分类名称不能重复");
        }
        $uid = '';
        if ($data['type'] == 2) {
            /*
            if (!empty($data['scheduling']) && count(array_filter($data['scheduling'])) > 0 && count(array_filter($data['scheduling'])) < 7) {
                throw new \think\Exception("周负责人缺失");
            }
            */
        } else {
            if (!empty($data['uid']) && trim($data['uid'])) {
                $uid = $data['uid'];
            }
        }
        $add_data = [
            'cate_name' => $data['cate_name'],
            'village_id' => $data['village_id'],
            'property_id' => $data['property_id'],
            'parent_id' => $data['parent_id'],
            'status' => $data['status'],
            'type' => $data['type'],
            'uid' => $uid,
            'sort' => $data['sort'],
            'add_time' => time(),
            'update_time' => time(),
            'timely_time'=>$this->handleTimely($data['timely_time'])
        ];
        if (isset($data['to_supply_station'])) {
            $add_data['to_supply_station'] = intval($data['to_supply_station']);
        }
        if (isset($data['other_village_id'])) {
            $add_data['other_village_id'] = intval($data['other_village_id']);
        }
        if (isset($data['other_village_cate_id'])) {
            $add_data['other_village_cate_id'] = intval($data['other_village_cate_id']);
        }
        if (isset($data['other_village_cate_fid'])) {
            $add_data['other_village_cate_fid'] = intval($data['other_village_cate_fid']);
        }
        if (isset($data['other_village_text'])) {
            $add_data['other_village_text'] = $data['other_village_text'];
        }
        if (isset($data['other_village_time'])) {
            $add_data['other_village_time'] = intval($data['other_village_time']);
        }
        
        $id = $db_repair_cate->addOne($add_data);
        if (!empty($data['scheduling']) && !empty($id) && $data['type'] == 2) {
            //$data['director_id'] = trim($data['director_id']);
            // $director_arr[] = explode(',', $data['director_id']);
            foreach ($data['scheduling'] as $v) {
                if (!empty($v)) {
                    $director_info = $db_repair_director->getOne([['id','in',$v]]);
                    if (!empty($director_info)) {
                        $db_repair_director->saveOne([['id','in',$v]], ['cate_id' => $id]);
                    }
                }
            }

        }

        return $id;
    }


    /**
     * 编辑分类
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:31
     */
    public function editCate($data)
    {
        $db_repair_cate = new HouseNewRepairCate();
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $where = [
            ['village_id', '=', $data['village_id']],
            ['cate_name', '=', $data['cate_name']],
            ['status', '<>', 4],
            ['id', '<>', $data['id']],
        ];
        $cate_info = $db_repair_cate->getOne($where);
        if (!empty($cate_info)) {
            throw new \think\Exception("分类名称不能重复");
        }

        $uid = '';
        $date_type=[];
        if ($data['type'] == 2 && !empty($data['director_id'])) {
            $db_repair_director = new HouseNewRepairDirectorScheduling();
            $director_list = $db_repair_director->getList([['id', 'in', $data['director_id']]], 'id,date_type,director_uid');
            if (!empty($director_list) && !$director_list->isEmpty()) {
                $director_list = $director_list->toArray();
                if (!empty($director_list)) {
                   // $director_arr = [];
                    foreach ($director_list as $k => $vv) {
                        if (empty($vv['date_type'])) {
                            $type = 7;
                        } else {
                            $type = $vv['date_type'];
                        }
                        if (empty($data['scheduling']['id' . $type]) || !in_array($vv['id'],$data['scheduling']['id' . $type]) || empty($vv['director_uid'])) {
                            $date_type[] = array('id'=>$vv['id'],'date_type'=>$vv['date_type']);
                            //unset($director_list[$k]);
                            //continue;
                        }
                        //$director_arr[] = $vv['date_type'];
                    }
                    //$director_arr = array_unique($director_arr);
                }
            }
        } else {
            if (!empty($data['uid']) && trim($data['uid'])) {
                $uid = $data['uid'];
            }
        }
        $edit_data = [
            'cate_name' => $data['cate_name'],
            'village_id' => $data['village_id'],
            'property_id' => $data['property_id'],
            'parent_id' => $data['parent_id'],
            'status' => $data['status'],
            'type' => $data['type'],
            'uid' => $uid,
            'sort' => $data['sort'],
            'update_time' => time(),
            'timely_time'=>$this->handleTimely($data['timely_time'])
        ];
        if (isset($data['to_supply_station'])) {
            $edit_data['to_supply_station'] = intval($data['to_supply_station']);
        }
        if (isset($data['other_village_id'])) {
            $edit_data['other_village_id'] = intval($data['other_village_id']);
        }
        if (isset($data['other_village_cate_id'])) {
            $edit_data['other_village_cate_id'] = intval($data['other_village_cate_id']);
        }
        if (isset($data['other_village_cate_fid'])) {
            $edit_data['other_village_cate_fid'] = intval($data['other_village_cate_fid']);
        }
        if (isset($data['other_village_text'])) {
            $edit_data['other_village_text'] = $data['other_village_text'];
        }
        if (isset($data['other_village_time'])) {
            $edit_data['other_village_time'] = intval($data['other_village_time']);
        }
        $id = $db_repair_cate->saveOne(['id' => $data['id']], $edit_data);
        if (!empty($data['scheduling']) && !empty($id) && $data['type'] == 2) {
            if (!empty($date_type)){
                foreach ($date_type as $v) {
                    $db_repair_director->del(['id'=>$v['id'],'date_type'=>$v['date_type']]);
                }
            }
            foreach ($data['scheduling'] as $v) {
                if (!empty($v)) {
                    $director_info = $db_repair_director->getOne([['id','in',$v]]);
                    if (!empty($director_info)) {
                        $db_repair_director->saveOne([['id','in',$v]], ['cate_id' => $data['id']]);
                    }
                }
            }

        }
        return $id;
    }


    /**
     * 删除分类
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function delCate($data)
    {
        $db_repair_cate = new HouseNewRepairCate();
        $where = [
            'id' => $data['id'],
            'village_id' => $data['village_id'],
        ];
        $cate_info = $db_repair_cate->getOne($where);
        $res = 0;
        $now_time=time();
        if (!empty($cate_info)) {
            $where_list = [
                'parent_id' => $data['id'],
                'village_id' => $data['village_id'],
            ];
            $cate_list = $db_repair_cate->getList($where_list);
            if (!empty($cate_list)) {
                $cate_list = $cate_list->toArray();
                if (!empty($cate_list)) {
                    $id = [];
                    foreach ($cate_list as $v) {
                        $id[] = $v['id'];
                    }
                    if (is_array($id)) {
                        $where_del = [
                            ['id', 'in', $id],
                        ];
                        $res_del = $db_repair_cate->saveOne($where_del, ['status' => 4,'update_time'=>$now_time]);
                    }
                }
            }
            $res = $db_repair_cate->saveOne($where, ['status' => 4,'update_time'=>$now_time]);
        }
        return $res;
    }


    /**
     * 查询负责人排班列表
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 15:23
     */
    public function getDirectorList($data)
    {
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $director_list = $db_repair_director->getList(['cate_id' => $data['cate_id']]);
        if (empty($director_list)) {
            throw new \think\Exception("暂无数据");
        } else {
            $director_list = $director_list->toArray();
            if (empty($director_list)) {
                throw new \think\Exception("暂无数据");
            }
        }
        return $director_list;

    }


    /**
     * 根据id查询负责人排班列表
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 15:23
     */
    public function getDirectorLists($data)
    {
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $db_house_worker = new HouseWorker();
        $where = [
            ['id', 'in', $data['id']]
        ];
        $director_list = $db_repair_director->getList($where);
        if (empty($director_list)) {
            throw new \think\Exception("暂无数据");
        } else {
            $director_list = $director_list->toArray();
            if (empty($director_list)) {
                throw new \think\Exception("暂无数据");
            }
        }

        $data = [];
        foreach ($director_list as $k => $value) {
            $wid = explode(',', $value['director_uid']);
            $wid = array_filter($wid);
            $worker_list = $db_house_worker->getAll(['wid' => $wid]);
            $name = '';
            if (!empty($worker_list)) {
                $worker_list = $worker_list->toArray();
                if (!empty($worker_list)) {
                    foreach ($worker_list as $w) {
                        $name = $w['name'] . ',' . $name;
                    }
                }
            }
            if ($value['end_time'] == 24) {
                $value['end_time'] = 0;
            }
            $list = [];
            $list['type'] = $value['date_type'];
            $list['child']['time'] = $value['start_time'] . ':00~' . $value['end_time'] . ':00';
            $list['child']['name1'] = $name;
           // $name = substr($name, 0, -1);
            if (mb_strlen($name) > 8) {
                $list['child']['name'] = mb_substr($name, 0, 5) . '...';
            } else {
                $list['child']['name'] = $name;
            }

            $data[] = $list;
        }
        return $data;

    }

    /**
     *查询指定日期类型的负责人列表
     * @author:zhubaodi
     * @date_time: 2021/9/25 13:51
     */
    public function getScheduling($data)
    {
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $db_house_worker = new HouseWorker();
        if ($data['date_type'] == 7) {
            $data['date_type'] = 0;
        }
        $director_list=[];
        if (!empty($data['cate_id'])){
            $where = [
                ['cate_id', '=', $data['cate_id']],
                ['date_type', '=', $data['date_type']]
            ];
            $director_list = $db_repair_director->getList($where);
        }
        if(!empty($data['id']) && !is_array($data['id'])){
            $data['id']=array($data['id']);
        }
        $where = [
            ['id', 'in', $data['id']]
        ];
        $director_list1 = $db_repair_director->getList($where);
        if (!empty($director_list)&&!empty($director_list1)) {
            $list = $director_list->toArray();
            $list1 = $director_list1->toArray();
            $directorList=array_merge($list,$list1);
        }elseif(!empty($director_list)){
            $directorList=$director_list;
        }else{
            $directorList=$director_list1;
        }
        $data = [];
        foreach ($directorList as $k => $value) {
            $index_key_arr=[];
            if(!empty($data) && array_key_exists($value['id'],$data)){
                continue;
            }
            $widArr=array();
            if(!empty($value['director_uid'])){
                $widArr = explode(',', $value['director_uid']);
                $widArr = array_filter($widArr);
            }
            $name = '';
            if (!empty($widArr)) {
                $whereArr = array(['wid', 'in', $widArr]);
                $worker_list = $db_house_worker->getAll($whereArr);
                if (!empty($worker_list)) {
                    $worker_list = $worker_list->toArray();
                    if (!empty($worker_list)) {
                        foreach ($worker_list as $w) {
                            $name = $w['name'] . ',' . $name;
                            $index_key_arr[]=$w['wid'].'-'.$w['name'];
                        }
                    }
                }
            }
            if ($value['end_time'] == 24) {
                $value['end_time'] = 0;
            }

            $list = [];
            $list['type'] = $value['date_type'];
            $list['start_time'] = $value['start_time'] . ':00';
            $list['end_time'] = $value['end_time'] . ':00';
            $list['name'] = !empty($name) ? trim($name,','):'';
            $list['uid'] = $value['director_uid'];
            $list['id'] = $value['id'];
            $list['index_key'] = $index_key_arr;
            $data[$value['id']] = $list;
        }
        $data=!empty($data) ? array_values($data):array();
        return $data;
    }

    /**
     * 查询分类详情
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 15:23
     */
    public function getDirectorInfo($data)
    {
        $db_repair_director = new HouseNewRepairDirectorScheduling();
        $director_list = $db_repair_director->getList(['cate_id' => $data['cate_id'], 'date_type' => $data['date_type']]);
        if (empty($director_list)) {
            throw new \think\Exception("暂无数据");
        } else {
            $director_list = $director_list->toArray();
            if (empty($director_list)) {
                throw new \think\Exception("暂无数据");
            }
        }
        return $director_list;
    }


    /**
     * 添加负责人
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:31
     */
    public function addDirector($data)
    {
        if (is_array($data['item']) && !empty($data['item'])) {
            $item = $data['item'];
            if (!empty($data['date_type'])) {
                $type_arr = $data['date_type'];
            } else {
                throw new \think\Exception("请选择适用周期");
            }
            $db_repair_director = new HouseNewRepairDirectorScheduling();
            // 启动事务
            Db::startTrans();
            $add_id = [];
            $ids = [];
            foreach ($data['item'] as $k1=>$v1) {
                $is_del=false;
                if(isset($v1['isdel']) && $v1['isdel']==1){
                    $is_del=true;
                }
                if (empty($v1['starttime']) && !$is_del) {
                    // 回滚事务
                    Db::rollback();
                    throw new \think\Exception("开始时间不能为空");
                }
                if (empty($v1['endtime']) && !$is_del) {
                    // 回滚事务
                    Db::rollback();
                    throw new \think\Exception("结束时间不能为空");
                }
                if (empty($v1['uid']) && !trim($v1['uid']) && !$is_del) {
                    // 回滚事务
                   // Db::rollback();
                    // throw new \think\Exception("负责人不能为空");
                }
                $time1[0] = explode(':', $v1['starttime'])[0];
                $time1[1] = explode(':', $v1['endtime'])[0];
                $time_0 = 0;
                if (isset($v1['is_defult']) && ($v1['is_defult']==1) && ($time1[0] == 0 && $time1[1] == 0) && !$is_del) {
                    $time_0 = $time_0 + 1;
                } else {
                    foreach ($item as $k2=>$v2) {
                        $is_del_tmp=false;
                        if(isset($v2['isdel']) && $v2['isdel']==1){
                            $is_del_tmp=true;
                        }
                        $time2[0] = explode(':', $v2['starttime'])[0];
                        $time2[1] = explode(':', $v2['endtime'])[0];
                        if (isset($v2['is_defult']) && ($v2['is_defult']==1)  && ($time2[0] == 0 && $time2[1] == 0) && !$is_del_tmp) {
                            continue;
                        }elseif((!isset($v2['is_defult']) || ($v2['is_defult']!=1)) && ($time2[0] == 0 && $time2[1] == 0) && !$is_del_tmp){
                            Db::rollback();
                            throw new \think\Exception("时间段不能重复");
                        }
						if ($k2==$k1){
                            continue;
                        }
						if (empty($v2['starttime']) && !$is_del_tmp) {
                            // 回滚事务
                            Db::rollback();
                            throw new \think\Exception("开始时间不能为空");
                        }
                        if (empty($v2['endtime']) && !$is_del_tmp) {
                            // 回滚事务
                            Db::rollback();
                            throw new \think\Exception("结束时间不能为空");
                        }
                        if ($time1[0] < $time2[0] && $time1[1] > $time2[0] && !$is_del && !$is_del_tmp) {
                            // 回滚事务
                            Db::rollback();
                            throw new \think\Exception("时间段不能重复。");
                        } elseif ($time1[0] >= $time2[0] && $time1[1] <= $time2[1] && !$is_del && !$is_del_tmp) {
                            // 回滚事务
                            Db::rollback();
                            throw new \think\Exception("时间段不能重复！");
                        }

                    }
                }
                if ($time_0 > 1) {
                    // 回滚事务
                    Db::rollback();
                    throw new \think\Exception("24小时时间段已存在");
                }
                if ($time1[1] == 0) {
                    $time1[1] = 24;
                }
                $flag=0;
                foreach ($type_arr as $v3) {
                    if (!empty($v3['focus'])) {
                        $flag=1;
                        $key = trim($v3['key']);
                        if ($key) {
                            if ($key == 7) {
                                $key = 0;
                            }
                            $add_data = [
                                'date_type' => $key,
                                'start_time' => $time1[0],
                                'end_time' => $time1[1],
                                'director_uid' => $v1['uid'],
                                'add_time' => time(),
                                'update_time' => time()
                            ];
                            if(isset($v1['id']) && $v1['id']>0 && ($v1['date_type']==$key)){
                                $id=$v1['id'];
                                if(isset($v1['isdel']) && $v1['isdel']==1){
                                    $db_repair_director->del(['id'=>$id]);
                                    continue;
                                }else {
                                    if(isset($v1['is_defult']) && ($v1['is_defult']==1) && (empty($v1['uid']) || $v1['uid']=='0')){
                                        $add_data['cate_id']=0;
                                    }
                                    $db_repair_director->saveOne(['id'=>$id],$add_data);
                                }
                            }else{
                                if((isset($v1['isdel']) && $v1['isdel']==1) || (empty($v1['uid']) || $v1['uid']=='0')) {
                                    continue;
                                }else{
                                    $id = $db_repair_director->addOne($add_data);
                                }
                            }

                            if ($id < 1) {
                                // 回滚事务
                                Db::rollback();
                                throw new \think\Exception("添加失败");
                            } else {
                                $add_id['type'] = trim($v3['key']);
                                $add_id['id'] = $id;
                                $ids[] = $add_id;
                            }
                        }
                    }

                }
                if ($flag!=1){
                    // 回滚事务
                    Db::rollback();
                    throw new \think\Exception("请选择适用周期");
                }
            }
            if ($time_0 < 1) {
                // 回滚事务
                //Db::rollback();
                //throw new \think\Exception("缺少24小时时间段");
            }
            Db::commit();
        }

        $id1 = [];
        $id = [];
        if (!empty($ids)) {
            $idss = $ids;
            foreach ($ids as $va) {
                $aa = [];
                if (!empty($idss)) {
                    foreach ($idss as $k => $va1) {
                        if ($va['type'] == $va1['type']) {
                            $aa[] = $va1['id'];
                            unset($idss[$k]);
                        }
                    }
                }
                if (!empty($aa)) {
                    $id['type'] = $va['type'];
                    $id['id'] = $aa;
                    $id1[] = $id;
                }
            }
        }
        return $id1;
    }

    /**
     * 查询自定义字段列表
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 15:23
     */
    public function getCateCustomList($data,$order='sort desc,id desc')
    {
        $db_repair_custom = new HouseNewRepairCateCustom();
        $custom_list = $db_repair_custom->getList(['cate_id' => $data['cate_id'], 'status' => [1, 2]], '*', $data['page'], $data['limit'],$order);
       $data1=[];
        if (!empty($custom_list)) {
            $custom_list = $custom_list->toArray();
            foreach ($custom_list as &$v) {
                if ($v['status'] == 1) {
                    $v['status'] = '开启';
                }
                if ($v['status'] == 2) {
                    $v['status'] = '关闭';
                }
            }
            $count = $db_repair_custom->getCount(['cate_id' => $data['cate_id'], 'status' => [1, 2]]);
            $data1['count'] = $count;
            $data1['list'] = $custom_list;
        }
        return $data1;
    }

    /**
     * 查询自定义字段详情
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 15:23
     */
    public function getCateCustomInfo($data)
    {
        $db_repair_custom = new HouseNewRepairCateCustom();
        $custom_info = $db_repair_custom->getOne(['cate_id' => $data['cate_id'], 'id' => $data['id']]);
        if (empty($custom_info)) {
            throw new \think\Exception("暂无数据");
        }
        return $custom_info;
    }

    /**
     * 标签信息
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getCustomInfo($where=[],$field=true)
    {
        $db_repair_custom = new HouseNewRepairCateCustom();
        $data = $db_repair_custom->getOne($where,$field);
        return $data;
    }

    /**
     * 添加自定义字段
     * @author:zhubaodi
     * @date_time: 2021/8/16 10:31
     */
    public function addCateCustom($data)
    {
        $db_repair_cate = new HouseNewRepairCate();
        $where = [
            ['id', '=', $data['cate_id']],
            ['status', '<>', 4],
        ];
        $cate_info = $db_repair_cate->getOne($where);
        if (empty($cate_info)) {
            throw new \think\Exception("分类不存在");
        }
        $idd=0;
        if(isset($data['id'])){
            $idd=$data['id'];
        }
        $db_repair_custom = new HouseNewRepairCateCustom();
        $whereArr=array(array('name','=',$data['name']));
        $whereArr[]=['cate_id', '=', $data['cate_id']];
        $whereArr[]=['status','<>',4];
        if($idd>0){
            $whereArr[]=['id','<>',$idd];

        }
        $custom_info = $db_repair_custom->getOne($whereArr);
        if (!empty($custom_info)) {
            throw new \think\Exception("字段名称不能重复");
        }
        $add_data = [
            'name' => $data['name'],
            'cate_id' => $data['cate_id'],
            'status' => $data['status'],
            'sort' => $data['sort'],
            'add_time' => time(),
            'update_time' => time(),
        ];
        if($idd>0){
            $id=$idd;
            $add_data = [
                'name' => $data['name'],
                'status' => $data['status'],
                'sort' => $data['sort'],
                'update_time' => time(),
            ];
            $db_repair_custom->saveOne(['id'=>$idd,'cate_id'=>$data['cate_id']],$add_data);
        }else{
            $id = $db_repair_custom->addOne($add_data);
        }

        return $id;
    }


    /**
     * 删除自定义字段
     * @return mixed|null|\think\Model
     * @throws \think\Exception
     * @author:zhubaodi
     * @date_time: 2021/8/13 13:54
     */
    public function delCateCustom($data)
    {
        $db_repair_custom = new HouseNewRepairCateCustom();
        $where = [
            'id' => $data['id'],
            'cate_id' => $data['cate_id'],
        ];
        $res = $db_repair_custom->saveOne($where, ['status' => 4]);
        return $res;
    }


    public function getDirectortree($data)
    {
        $list = (new HouseProgrammeService())->businessNav($data['village_id'],1,0,1);
        return $list['data'];

        $db_house_property_group = new PropertyGroup();
        $db_house_worker = new HouseWorker();
        $dataid = $db_house_property_group->getOne(['property_id' => $data['property_id'], 'is_del' => 0, 'fid' => 0]);
        $dataList = $db_house_property_group->getList(['property_id' => $data['property_id'], 'is_del' => 0], '*', 0, 10, 'sort desc,id desc');

        foreach ($dataList as $k => $value) {

            if ($value['type'] == 99 && $value['property_id']) {
                continue;
                $whereArr=array(['property_id' ,'=', $value['property_id']], ['is_del' ,'=',  0]);
                $whereArr[]=array('status','<>',4);
                $count = $db_house_worker->getMemberCount();
                if ($count < 1) {
                    continue;
                }
                $value['name'] = $data['property_name'];
                $list['title'] = $value['name'];
            } elseif ($value['type'] == 0 && $value['property_id'] && $value['village_id']) {
                $whereArr=array(['property_id' ,'=', $value['property_id']], ['is_del' ,'=',  0], ['status' ,'<>',  4]);
                $whereArr[]=array('village_id' ,'=', $value['village_id']);
                $whereArr[]=array('people_type' ,'in', [0, 1]);
                $count = $db_house_worker->getMemberCount($whereArr);
                if ($count < 1) {
                    continue;
                }
                //$value['name'] = $data['village_name'];
                $list['title'] = $value['name'];
            } elseif ($value['type'] == 1 && $value['property_id'] && $value['village_id']) {
                $whereArr=array(['property_id' ,'=', $value['property_id']], ['is_del' ,'=',  0], ['status' ,'<>',  4]);
                $whereArr[]=array('village_id' ,'=', $value['village_id']);
                $whereArr[]=array('department_id' ,'=',$value['id']);
                $count = $db_house_worker->getMemberCount($whereArr);
                if ($count < 1) {
                    continue;
                }
                $list['title'] = $value['name'];
            } elseif ($value['type'] == 2 && $value['property_id']) {
                $whereArr=array(['property_id' ,'=', $value['property_id']], ['is_del' ,'=',  0], ['status' ,'<>',  4]);
                $whereArr[]=array('department_id' ,'=',$value['id']);
                $count = $db_house_worker->getMemberCount($whereArr);
                if ($count < 1) {
                    continue;
                }
                $list['title'] = $value['name'];
            } else {
                $whereArr=array(['property_id' ,'=', $value['property_id']], ['is_del' ,'=',  0], ['status' ,'<>',  4]);
                $whereArr[]=array('department_id' ,'=',$value['id']);
                $count = $db_house_worker->getMemberCount($whereArr);
                if ($count < 1) {
                    continue;
                }
                $list['title'] = $value['name'];
            }
            $list['permission_id'] = $value['id'];
            $list['parent_permission_id'] = $value['fid'];
            $list['key'] = $value['id'];
            $items[] = $list;
        }
        $res = $this->getTree($data['property_id'], $items, $dataid['id']);

        //    print_r($res);exit;
        return $res;
    }

    public function getTree($property_id, $arr = [], $fid)
    {
        $temp_new_arr = [];
        foreach ($arr as $k => $v) {
            $v['disabled'] = true;
            if ($v['parent_permission_id'] == $fid) {
                $temp_arr = $v;
                $temp_arr['worker'] = $this->worker_list($v['permission_id'], $property_id);
                if (empty($temp_arr['worker'])) {
                    unset($temp_arr['worker']);
                }
                $temp_arr['children'] = $this->getTree($property_id, $arr, $v['permission_id']);

                if (isset($temp_arr['worker'])) {
                    foreach ($temp_arr['worker'] as $k1 => $v1) {
                        $list['permission_id'] = $v1['wid'];
                        $list['parent_permission_id'] = $v1['department_id'];
                        $list['title'] = $v1['name'];
                        $list['key'] = $v1['wid'] . '-' . $v1['name'];
                        array_unshift($temp_arr['children'], $list);
                    }
                    unset($temp_arr['worker']);
                }
                if (count($temp_arr['children']) == 0) {
                    unset($temp_arr['children']);
                }
                $temp_new_arr[] = $temp_arr;
            }

        }
        return $temp_new_arr;
    }

    public function worker_list($department_id, $property_id)
    {
        $db_house_worker = new HouseWorker();
        
        $whereArr=array(['property_id' ,'=', $property_id], ['is_del' ,'=',  0], ['status' ,'<>',  4]);
        $whereArr[]=array('department_id' ,'=',$department_id);
        $dataList = $db_house_worker->getAll($whereArr);
        $worker_user = [];
        if (!empty($dataList)) {
            $dataList = $dataList->toArray();
            if (!empty($dataList)) {
                foreach ($dataList as $k => $value) {
                    $worker_user[] = $value;
                }
            }
        }
        return $worker_user;
    }

    public function setRepairCharge($data){
        $db_house_new_repair_subject=new HouseNewRepairCate();
        $db_house_new_repair_works_order=new HouseNewRepairWorksOrder();
        $error_data=[];
        $error_data1=[];
        if (!empty($data['subject_id'])){
            foreach ($data['subject_id'] as $v){
                $subject_info=$db_house_new_repair_subject->getOne(['id'=>$v,'status'=>[1,2]]);

                //   print_r($subject_info);exit;
                if (empty($subject_info)){
                    $error_data[]=$v;
                    continue;
                }
                if ($subject_info['charge_type']==$data['charge_type']){
                    continue;
                }
                $where=[
                    ['village_id','=',$data['village_id']],
                    ['cat_id','=',$v],
                    ['event_status','<',60],
                ];
                $works=$db_house_new_repair_works_order->getOne($where);
                if (!empty($works)){
                    $error_data[]=$subject_info['cate_name'];
                    continue;
                }
                $res=$db_house_new_repair_subject->saveOne(['id'=>$v],['charge_type'=>$data['charge_type']]);
                if ($res<1){
                    $error_data[]=$subject_info['cate_name'];
                    continue;
                }

            }
        }
        $where=[];
        $where[]=['id','not in',$data['subject_id']];
        $where[]=['charge_type','=',1];
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['status','<>',4];
        $list=$db_house_new_repair_subject->getList($where,'*',0);
        if (!empty($list)){
            $list=$list->toArray();
        }
        if (!empty($list)){
            foreach ($list as $vv){
                $where=[
                    ['village_id','=',$data['village_id']],
                    ['cat_id','=',$vv['id']],
                    ['event_status','<',60],
                ];
                $works=$db_house_new_repair_works_order->getOne($where);
                if (!empty($works)){
                    $error_data1[]=$vv['cate_name'];
                    continue;
                }
                $db_house_new_repair_subject->saveOne(['id'=>$vv['id']],['charge_type'=>0]);
            }
        }
        $msg='';
        if (!empty($error_data)){
            $msg.=implode(',',$error_data).'未绑定成功，请重新绑定';
          //  return ['code'=>0,'msg'=>$msg.'未绑定成功，请重新绑定'];
        }
        if (!empty($error_data1)){
            $msg.=implode(',',$error_data1).'取消绑定失败，请重新操作';
            //  return ['code'=>0,'msg'=>$msg.'未绑定成功，请重新绑定'];
        }
        if (!empty($msg)){
            return ['code'=>0,'msg'=>$msg];
        }else{
            return ['code'=>1,'msg'=>'收费设置绑定成功'];
        }
    }


    public function getRepairCharge($data){
        $db_house_new_repair_subject=new HouseNewRepairCate();
        $where[]=['village_id','=',$data['village_id']];
        $where[]=['status','in',[1,2]];
        $subject_list=$db_house_new_repair_subject->getList($where,'id,cate_name as title,parent_id as fid,charge_type',0);
        if (!empty($subject_list)){
            $subject_list=$subject_list->toArray();
        }

        $new_arr=[];
        if (!empty($subject_list)) {
            foreach ($subject_list as &$vv){
                $vv['key']=$vv['id'];
            }
           //  print_r($subject_list);die;
            $new_arr = (new OrganizationStreetService())->getTree($subject_list);
        }
        $where[]=['charge_type','=',1];
        $where[]=['parent_id','<>',0];
        $subject_ids=$db_house_new_repair_subject->getColumn($where,'id');
        $res['res']=$new_arr;
        $res['subject_ids']=$subject_ids;
        return $res;
    }
    /***
    ** 机器人二维码生成工单
     **/
    public function addWisdomQrcodeToWorksOrder($params=array()){
        $returnInfo=array('err_code'=>0,'msg'=>'','order_id'=>0);
        fdump_api(['params'=>$params],'00addWisdomQrcodeToWorksOrder',1);
        if(empty($params) || !isset($params['f_cate_id']) || !isset($params['cate_id'])|| !isset($params['log_id'])|| !isset($params['village_id'])){
            $returnInfo['err_code']=1;
            $returnInfo['msg']='参数错误!';
            return $returnInfo;
        }
        if(empty($params['f_cate_id']) || empty($params['cate_id'])|| empty($params['log_id'])|| empty($params['village_id'])){
            $returnInfo['err_code']=1;
            $returnInfo['msg']='参数值错误!';
            return $returnInfo;
        }
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($params['village_id'], 'village_id,village_name,property_id');
        if (empty($village_info) || $village_info->isEmpty()) {
            $returnInfo['err_code']=1;
            $returnInfo['msg']='小区不存在。';
            return $returnInfo;
        }
        $db_house_village_info = new HouseVillageInfo();
        $village_info_data = $db_house_village_info->getOne(['village_id'=>$params['village_id']], 'village_id,is_timely');
        if (empty($village_info_data) || $village_info_data->isEmpty()) {
            $returnInfo['err_code']=1;
            $returnInfo['msg']='小区不存在!';
            return $returnInfo;
        }
        $wisdomQrcodeRecordLogDb=new WisdomQrcodeRecordLog();
        $wqWhereArr=array();
        $wqWhereArr['id']=$params['log_id'];
        $wqWhereArr['village_id']=$params['village_id'];

        $record_log=$wisdomQrcodeRecordLogDb->getOneData($wqWhereArr);
        $order_content=array();
        $order_imgs=array();
        $address_txt='';
        $name='';
        $address_type='robot_report';
        $extra_data=array();
        if($record_log && !$record_log->isEmpty()){
            $name=$record_log['robot_code'];
            $robot_record=$record_log['robot_record'] ? json_decode($record_log['robot_record'],true):array();
            $extra_data=$robot_record;
            $extra_data['log_id']=$params['log_id'];
            $extra_data['robot_code']=$record_log['robot_code'];
            $extra_data['qrcode_id']=$record_log['qrcode_id'];
            $extra_data['cate_id']=$record_log['cate_id'];
            if($robot_record){
                if($robot_record['title']){
                    $order_content[]=$robot_record['title'];
                }
                if($robot_record['point_name']){
                    $order_content[]=$robot_record['point_name'];
                    $address_txt=$robot_record['point_name'];
                }
                if($robot_record['remark']){
                    $order_content[]=$robot_record['remark'];
                }
                if($robot_record['image_urls']){
                    $order_imgs=$robot_record['image_urls'];
                }
            }
        }

        $extra_data_str='';
        if($extra_data){
            $extra_data_str=json_encode($extra_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        $order_content_str='';
        if($order_content){
            $order_content_str=implode('|',$order_content);
        }
        $order_imgs_str='';
        if($order_imgs){
            $order_imgs_str=implode(';',$order_imgs);
        }

        $house_new_repair_works_order_service = new HouseNewRepairService();
        $worker_ids = $house_new_repair_works_order_service->getWorkerByTime($params['cate_id']);

        if(!empty($worker_ids)){
            $worker_id = $worker_ids[array_rand($worker_ids)];
        }else{
            $worker_id = 0;
        }
        $now_role=2;
        if ($worker_id) {
            // 如果存在接手工作人员
            $event_status = 20;
            $now_role = 3;
        } else {
            $now_role = 2;
            $event_status = 10;
        }
        $property_id = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;
        $public_id = 0;
        $room_id = 0;
        $single_id = 0;
        $floor_id = 0;
        $layer_id = 0;
        $order_type = 5;
        $now_time = time();
        $order = [
            'village_id' => $params['village_id'],
            'property_id' => $property_id,
            'category_id' => 0,
            'type_id' => 0,
            'cat_fid' => $params['f_cate_id'],
            'cat_id' => $params['cate_id'],
            'label_txt' => '',
            'order_content' => $order_content_str,
            'order_imgs' => $order_imgs_str,
            'order_type' => $order_type,
            'uid' => 0,
            'phone' => '',
            'name' => $name,
            'now_role' => $now_role,
            'worker_type' => 0,
            'worker_id' => $worker_id,
            'event_status' => $event_status,
            'address_type' => $address_type,
            'address_id' => 0,
            'public_id' => $public_id,
            'single_id' => $single_id,
            'floor_id' => $floor_id,
            'layer_id' => $layer_id,
            'room_id' => $room_id,
            'address_txt' => $address_txt,
            'add_time' => $now_time,
            'go_time' => 0,
            'extra_data'=>$extra_data_str,
        ];
        fdump_api(['order'=>$order],'00addWisdomQrcodeToWorksOrder',1);
        $log_name = 'backstage_work_submit';
        $order_id = $house_new_repair_works_order_service->addRepairOrder($order);
        if ($order_id) {
            $wisdomQrcodeRecordLogDb->saveOneData(['id'=>$params['log_id']],['work_order_id'=>$order_id]);
            $logData['order_id'] = $order_id;
            $logData['log_name'] = $log_name;
            $logData['village_id'] = $params['village_id'];
            $logData['property_id'] = $property_id;
            $logData['log_operator'] = $order['name'] ? $order['name'] : '';
            $logData['log_phone'] = $order['phone'] ? $order['phone'] : '';
            $logData['operator_type'] = 10;
            $logData['operator_id'] = $order['uid'] ? $order['uid'] : 0;
            $logData['log_uid'] = 0;
            $logData['log_content'] = $order_content;
            $logData['log_imgs'] = $order['order_imgs'];
            $logData['add_time'] = $now_time;
            $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
            $where_count = [];
            $where_count[] = ['order_id', '=', $order_id];
            $where_count[] = ['log_name', '=', strval($log_name)];
            $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
            if (!$log_num) {
                $log_num = 1;
            } else {
                $log_num = intval($log_num) + 1;
            }
            $logData['log_num'] = $log_num;
            $otherData='';
            if($worker_id<1){
                $otherData=array('opt_type'=>'order_worker_submit','wid'=>0,'village_id'=>$params['village_id']);
            }
            $house_new_repair_works_order_service->addRepairLog($logData,$otherData);
            if($worker_id){
                // 查询处理人信息
                $db_house_worker = new HouseWorker();
                $where_house_work = [];
                $where_house_work[] = ['wid', '=', $worker_id];
                $house_work = $db_house_worker->get_one($where_house_work, 'wid,name,phone');
                $logData = [];
                $logData['order_id'] = $order_id;
                $logData['log_name'] = 'house_auto_assign';
                $logData['log_operator'] = $house_work['name'] ? $house_work['name'] : '';
                $logData['log_phone'] = $house_work['phone'] ? $house_work['phone'] : '';
                $logData['operator_type'] = 10;
                $logData['operator_id'] = 0;
                $logData['log_uid'] = $house_work['wid'] ? $house_work['wid'] : 0;
                $logData['log_content'] = '';
                $logData['log_imgs'] = '';
                $logData['add_time'] = $now_time;
                $logData['village_id'] = $params['village_id'];
                $logData['property_id'] = $property_id;
                $otherData=array('opt_type'=>'assign_to_house_worker','wid'=>$worker_id,'village_id'=>$params['village_id']);
                $house_new_repair_works_order_service->addRepairLog($logData,$otherData);
            }
            //todo 开启抢单模式
            $house_new_repair_works_order_service->grabOrder($params['village_id'],$order_id,$params['cate_id']);
            //当提交的工单有指派时
            if($order['worker_id'] > 0){
                //针对工作人员提交的工单有指派时 给提交人发送通知
                /*
                if($order['order_type'] == 4){
                    $house_new_repair_works_order_service->newRepairSendMsg(11,$order_id,0,$property_id);
                }
                */
                //给工作人员发送通知
                $house_new_repair_works_order_service->newRepairSendMsg(12,$order_id,0,$property_id);
            }
        }
        fdump_api(['returnInfo'=>$returnInfo],'00addWisdomQrcodeToWorksOrder',1);
        $returnInfo['order_id'] = $order_id;
        return $returnInfo;
    }
    /**
     * Notes: 移动管理端添加工单
     * @param $data
     * @param $auto_assign 是否为巡检异常自动创建工单, true为是, false为否
     * @return array
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/8/18 11:56
     */
    public function addWorksOrder($data,$auto_assign=false)
    {
        $house_new_repair_works_order_service = new HouseNewRepairService();
        if (!isset($data['village_id']) || !$data['village_id']) {
            throw new \think\Exception('缺少必传参数');
        }
        $village_id = intval($data['village_id']);
        $db_house_village = new HouseVillage();
        $village_info = $db_house_village->getOne($village_id, 'village_id,village_name,property_id');
        if (empty($village_info)) {
            throw new \think\Exception('对应小区不存在');
        }
        $db_house_village_info = new HouseVillageInfo();
        $village_info_data = $db_house_village_info->getOne(['village_id'=>$village_id], 'village_id,is_timely');
        if (empty($village_info_data)) {
            throw new \think\Exception('对应小区不存在');
        }
        $property_id = isset($village_info['property_id']) ? intval($village_info['property_id']) : 0;

        if( $auto_assign === false ){
            if (isset($data['is_complete']) && $data['is_complete'] == 1) {
                $data['go_time'] = !empty($data['go_time']) ? strtotime($data['go_time']) : 0;

            } else if ($village_info_data['is_timely'] == 1) {
                if (empty($data['go_time'])) {
                    throw new \think\Exception('上门时间不能为空');
                }
                $data['go_time'] = strtotime($data['go_time']);
                if (empty($data['go_time'])) {
                    throw new \think\Exception('上门时间不能为空');
                }
                if ($data['go_time'] < time()) {
                    throw new \think\Exception('上门时间不能小于当前时间');
                }
            } else {
                if (!empty($data['go_time'])) {
                    $data['go_time'] = strtotime($data['go_time']);
                    if (!empty($data['go_time']) && $data['go_time'] < time()) {
                        throw new \think\Exception('上门时间不能小于当前时间');
                    } elseif (empty($data['go_time'])) {
                        $data['go_time'] = 0;
                    }
                }
            }
            if (!isset($data['cat_id']) || !$data['cat_id']) {
                throw new \think\Exception('缺少必传参数');
            }
            $db_house_new_repair_cate = new HouseNewRepairCate();
            $cat_id = $data['cat_id'];
            $cate_info = $db_house_new_repair_cate->getOne(['id' => $cat_id,'village_id'=>$village_id], 'id,subject_id,cate_name,parent_id,type,uid');
            if (empty($cate_info) || $cate_info->isEmpty()) {
                throw new \think\Exception('对应分类信息不存在');
            }
            $address_type = isset($data['address_type'])?trim($data['address_type']):'';
            if($address_type){
                if (!in_array($address_type,['public', 'room'])) {
                    throw new \think\Exception('缺少必传参数');
                }
                if (!isset($data['address_id']) || !$data['address_id']) {
                    throw new \think\Exception('缺少必传参数');
                }
            }
        }else{
            $address_type = $data['address_type'];
        }
        if (isset($data['cat_fid']) && $data['cat_fid']) {
            $cat_fid = intval($data['cat_fid']);
        } elseif (isset($cate_info['parent_id']) && $cate_info['parent_id']) {
            $cat_fid = intval($cate_info['parent_id']);
        } else {
            throw new \think\Exception('缺少主分类');
        }
        $houseWorkerDb= new HouseWorker();
        $houseVillageUserBind=new HouseVillageUserBind();
        $address_id = intval($data['address_id']);
        $public_id = 0;
        $room_id = 0;
        $single_id = 0;
        $floor_id = 0;
        $layer_id = 0;
        $address_txt = isset($data['address_txt']) ? trim($data['address_txt']) : '';
        $toSingleId = 0;
        if($auto_assign === false){
            if ('public'==$address_type) {
                $public_id = intval($data['address_id']);
                // 获取下公共区域
                $db_house_village_public_area = new HouseVillagePublicArea();
                $wherePublicArea = [];
                $wherePublicArea[] = ['public_area_id', '=', $public_id];
                $publicAreaInfo = $db_house_village_public_area->getOne($wherePublicArea, 'public_area_id, public_area_name');
                if (empty($publicAreaInfo)) {
                    throw new \think\Exception('所选公共区域不存在');
                }
                $address_txt = $publicAreaInfo['public_area_name'];
            } elseif ('room'==$address_type) {

                $room_id = intval($data['address_id']);
                $db_house_village_user_vacancy = new HouseVillageUserVacancy();
                $where_room = [];
                $where_room[] = ['pigcms_id', '=', $room_id];
                $field_room = 'pigcms_id, room, village_id, single_id, floor_id, layer_id';
                $room_info = $db_house_village_user_vacancy->getOne($where_room, $field_room);
                if (empty($room_info)) {
                    throw new \think\Exception('所选房屋不存在');
                }
                if (isset($data['single_id']) && $data['single_id']) {
                    $single_id = intval($data['single_id']);
                    $toSingleId = $single_id;
                } elseif (isset($room_info['single_id']) && $room_info['single_id']) {
                    $single_id = intval($room_info['single_id']);
                    $toSingleId = $single_id;
                } else {
                    throw new \think\Exception('缺少楼栋');
                }
                if (isset($data['floor_id']) && $data['floor_id']) {
                    $floor_id = intval($data['floor_id']);
                } elseif (isset($room_info['floor_id']) && $room_info['floor_id']) {
                    $floor_id = intval($room_info['floor_id']);
                } else {
                    throw new \think\Exception('缺少单元');
                }
                if (isset($data['layer_id']) && $data['layer_id']) {
                    $layer_id = intval($data['layer_id']);
                } elseif (isset($room_info['layer_id']) && $room_info['layer_id']) {
                    $layer_id = intval($room_info['layer_id']);
                } else {
                    throw new \think\Exception('缺少楼层');
                }
                $room_village_id = isset($room_info['village_id']) && $room_info['village_id'] ? intval($room_info['village_id']) : $village_id;
                $service_house_village = new HouseVillageService();
                $address_txt = $service_house_village->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $room_village_id);
            }
        }else{
            $address_txt = $data['address_text_name'];
        }
        if ($address_txt && isset($village_info['village_name']) && $village_info['village_name']) {
            $address_txt = $village_info['village_name'] . ' ' . $address_txt;
        } elseif (!$address_txt) {
            $address_txt = '';
        }
        $jz_open = (new ConfigCustomizationService())->getWSXCustomizedOpen();

        $toWorkId = 0;
        $workOrderConfig = [];
        $assign_record = [];
        $is_supply_station = 0;
        $communityConfig = [];
        if ($jz_open) {
            $configInfo = (new HouseCommunityAboutService())->getHouseCommunityConfig($village_id);
            $supplyInfo = (new HouseCommunityAboutService())->getVillageFacilitiesInfo($village_id);
            if (isset($supplyInfo['is_virtual']) && intval($supplyInfo['is_virtual']) == 1) {
                // 是供暖站
                $is_supply_station = 1;
            }
            // 按照配置获取下分配人员
            $workOrderParam = [
                'village_id' => $village_id,
                'is_supply_station' => $is_supply_station,
                'configInfo' => $configInfo,
                'cat_id' => $cat_id,
                'toSingleId' => $toSingleId,
            ];
            $assignRecord = (new HouseCommunityWorkOrderService())->getWorkOrderToWorker($workOrderParam);
            if (isset($assignRecord['errCode']) && intval($assignRecord['errCode']) == WorkOrderConst::WORK_ORDER_ERR_CODE_NOT_BUILDER) {
                $telephone = isset($assignRecord['telephone']) ? trim($assignRecord['telephone']) : '';
                $msg = '未设置楼栋管家，不能提交工单请先添加对应楼栋管家！';
                $telephone && $msg .= "\n【联系电话：{$telephone}】";
                throw new \think\Exception($msg);
            }
            $toWorkId = isset($assignRecord['toWorkId']) ? intval($assignRecord['toWorkId']) : 0;
            $communityAutoToWork = isset($assignRecord['communityAutoToWork']) ? $assignRecord['communityAutoToWork'] : [];
            $communityConfig = isset($assignRecord['communityConfig']) ? $assignRecord['communityConfig'] : [];
            $repairCateConfig = isset($assignRecord['repairCateConfig']) ? $assignRecord['repairCateConfig'] : [];
            $communityAutoToWork && $assign_record['communityAutoToWork'] = $communityAutoToWork;
            $workOrderConfig['village_id'] = $village_id;
            $communityConfig && $workOrderConfig['community_config'] = json_encode($communityConfig,JSON_UNESCAPED_UNICODE);
            $repairCateConfig && $workOrderConfig['repair_cate_config'] = json_encode($repairCateConfig,JSON_UNESCAPED_UNICODE);
            isset($assignRecord['not_report_switch']) && $workOrderConfig['not_report_switch'] = intval($assignRecord['not_report_switch']);
            $workOrderConfig['quality_start_time'] = isset($repairCateConfig['quality_start_time']) ? intval($repairCateConfig['quality_start_time']) : 0;
            $workOrderConfig['quality_end_time'] = isset($repairCateConfig['quality_end_time']) ? intval($repairCateConfig['quality_end_time']) : 0;
        }
        
        $repairCateScheduling = [];
        if ($toWorkId > 0) {
            $worker_id = $toWorkId;
        } else {
            $worker_ids = $house_new_repair_works_order_service->getWorkerByTime($cat_id, $repairCateScheduling);
            if (!empty($worker_ids)) {
                $worker_id = $worker_ids[array_rand($worker_ids)];
            } else {
                $worker_id = 0;
            }
        }
        $repairCateScheduling && $assign_record['repairCateScheduling'] = $repairCateScheduling;
        
        if ($worker_id) {
            // 如果存在接手工作人员
            $event_status = 20;
            $now_role = 3;
        } else {
            $now_role = 2;
            $event_status = 10;
        }

        $log_name = 'backstage_work_submit';
        $order_type = 5;
        $uid = 0;

        $order_imgs = isset($data['order_imgs']) && $data['order_imgs'] ? $data['order_imgs'] : '';
        if ($order_imgs && is_array($order_imgs)) {
            $order_imgs = implode(';', $order_imgs);
        } elseif ($order_imgs && is_string($order_imgs)) {
            $order_imgs = trim($order_imgs);
        }
        $order_content = isset($data['order_content']) && $data['order_content'] ? $data['order_content'] : '';
        $label_txt = isset($data['label_txt']) ? $data['label_txt'] : '';
        if (is_array($label_txt)) {
            $label_txt = implode(',', $label_txt);
        } elseif (is_string($label_txt)) {
            $label_txt = trim($label_txt);
        }
        if ( $auto_assign === false && empty($order_imgs) && !$order_content && empty($label_txt)) {
            throw new \think\Exception('缺少必传参数');
        }
        $videos = isset($data['videos']) ? $data['videos'] : '';
        if (is_array($videos)) {
            $videos = implode(',', $videos);
        } elseif (is_string($videos)) {
            $videos = trim($videos);
        }
        if ($jz_open) {
            $uploadVideoMax = WorkOrderConst::WORK_ORDER_UPLOAD_VIDEO_MAX;
            $is_pic_video = isset($configInfo['is_pic_video']) ? intval($configInfo['is_pic_video']) : 0;
            if (is_array($videos) && count($videos) > $uploadVideoMax) {
                throw new \think\Exception('上传视频数量不能超过'.$uploadVideoMax.'个');
            }
            if ($is_pic_video == 1 && empty($videos) && empty($order_imgs_value)) {
                throw new \think\Exception('图片或者视频必须上传最少1个');
            }
        }
        $now_time = time();
        if(isset($data['assign_wid']) && $data['assign_wid']){
            $worker_id=$data['assign_wid'];
        }
        $order = [
            'village_id' => $village_id,
            'property_id' => $property_id,
            'category_id' => 0,
            'type_id' => 0,
            'cat_fid' => $cat_fid,
            'cat_id' => $cat_id,
            'label_txt' => $label_txt,
            'order_content' => $order_content,
            'order_imgs' => $order_imgs,
            'order_type' => $auto_assign ? $data['order_type'] :$order_type,
            'uid' => $auto_assign ? $data['worker_id'] :$uid,
            'phone' => $auto_assign ?$data['phone'] :'',
            'name' => $auto_assign ?$data['name'] :'',
            'now_role' => $now_role,
            'worker_type' => 0,
            'worker_id' => $auto_assign ? $data['worker_id'] :$worker_id,
            'event_status' => $event_status,
            'address_type' => $address_type,
            'address_id' => $address_id,
            'public_id' => $public_id,
            'single_id' => $single_id,
            'floor_id' => $floor_id,
            'layer_id' => $layer_id,
            'room_id' => $room_id,
            'address_txt' => $address_txt,
            'add_time' => $now_time,
            'go_time' => $data['go_time'],
            'inspection_id' => $data['inspection_id'],
            'inspection_auto_add' => $data['inspection_auto_add'],
        ];
        if(isset($data['is_complete']) && $data['is_complete']==1){
            $order['event_status']=40; //办结 
            $order['update_time']= isset($data['complete_time']) && $data['complete_time']>100 ? $data['complete_time']:$now_time;
            $worker_id=0; //完成了不要走原来的指派逻辑
        }
        if(isset($data['report_wid']) && $data['report_wid']){
            $order['uid']=$data['report_wid'];
            $reportWorker=$houseWorkerDb->get_one(array('wid'=>$data['report_wid']),'wid,village_id,phone,name');
            if($reportWorker && !$reportWorker->isEmpty()){
                $order['name']=$reportWorker['name'];
                $order['phone']=$reportWorker['phone'];
            }
        }else if(isset($data['report_pigcms_id']) && $data['report_pigcms_id']){
            $order['bind_id']=$data['report_pigcms_id'];
            $userBindObj=$houseVillageUserBind->getOne(array('pigcms_id'=>$data['report_pigcms_id']),'pigcms_id,village_id,uid,name,phone','pigcms_id desc');
            if($userBindObj && !$userBindObj->isEmpty()){
                $order['uid']=$userBindObj['uid'];
                $order['name']=$userBindObj['name'];
                $order['phone']=$userBindObj['phone'];
            }
        }

        $videos && $order['videos'] = $videos;
        $order_id = $house_new_repair_works_order_service->addRepairOrder($order);
        if ($order_id) {
            $logData['order_id'] = $order_id;
            $logData['log_name'] = $log_name;
            $logData['village_id'] = $village_id;
            $logData['property_id'] = $property_id;
            $logData['log_operator'] = $order['name'] ? $order['name'] : '';
            $logData['log_phone'] = $order['phone'] ? $order['phone'] : '';
            $logData['operator_type'] = 20;
            $logData['operator_id'] = $order['uid'] ? $order['uid'] : 0;
            $logData['log_uid'] = 0;
            $logData['log_content'] = $order_content;
            $logData['log_imgs'] = $order['order_imgs'];
            $logData['add_time'] = $now_time;
            $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
            $where_count = [];
            $where_count[] = ['order_id', '=', $order_id];
            $where_count[] = ['log_name', '=', strval($log_name)];
            $log_num = $db_house_new_repair_works_order_log->getCount($where_count);
            if (!$log_num) {
                $log_num = 1;
            } else {
                $log_num = intval($log_num) + 1;
            }
            $logData['log_num'] = $log_num;
            $otherData='';
            if($worker_id<1 && (!isset($data['is_complete']) || $data['is_complete']!=1)){
                $otherData=array('opt_type'=>'order_worker_submit','wid'=>0,'village_id'=>$village_id);
            }
            $house_new_repair_works_order_service->addRepairLog($logData,$otherData);
            if(isset($data['is_complete']) && $data['is_complete']==1){
                $worker_id=0;
                //指派
                if(isset($data['assign_wid']) && $data['assign_wid']){
                    $assign_work_obj=$houseWorkerDb->get_one(array('wid'=>$data['assign_wid']),'wid,village_id,phone,name');
                    $assign_work='';
                    if($assign_work_obj && !$assign_work_obj->isEmpty()){
                        $assign_work=$assign_work_obj->toArray();
                    }
                    $logData = [];
                    $logData['order_id'] = $order_id;
                    $logData['log_name'] = 'center_assign_work';  //后台处理中心手动指派
                    $logData['log_operator'] = $assign_work ? $assign_work['name'] : '';
                    $logData['log_phone'] = $assign_work ? $assign_work['phone'] : '';
                    $logData['operator_type'] = 20;
                    $logData['operator_id'] = 0;
                    $logData['log_uid'] = $data['assign_wid'];
                    $logData['log_content'] = '';
                    $logData['log_imgs'] = '';
                    $logData['add_time'] = isset($data['complete_time']) && $data['complete_time']>100 ? $data['complete_time']:$now_time;
                    $logData['village_id'] = $village_id;
                    $logData['property_id'] = $property_id;
                    $house_new_repair_works_order_service->addRepairLog($logData);
                }
                //办结
                $logData = [];
                $logData['order_id'] = $order_id;
                $logData['log_name'] = 'center_work_completed';  //后台处理中心手动结单
                $logData['log_operator'] =  '';
                $logData['log_phone'] =  '';
                $logData['operator_type'] = 20;
                $logData['operator_id'] = 0;
                $logData['log_uid'] = 0;
                $logData['log_content'] = '';
                $logData['log_imgs'] = '';
                $logData['add_time'] = isset($data['complete_time']) && $data['complete_time']>100 ? $data['complete_time']:$now_time;
                $logData['village_id'] = $village_id;
                $logData['property_id'] = $property_id;
                $house_new_repair_works_order_service->addRepairLog($logData);

            }
            if($worker_id){
                // 查询处理人信息
                $where_house_work = [];
                $where_house_work[] = ['wid', '=', $worker_id];
                $house_work = $houseWorkerDb->get_one($where_house_work, 'wid,name,phone');
                $logData = [];
                $logData['order_id'] = $order_id;
                $logData['log_name'] = 'house_auto_assign';
                $logData['log_operator'] = $house_work['name'] ? $house_work['name'] : '';
                $logData['log_phone'] = $house_work['phone'] ? $house_work['phone'] : '';
                $logData['operator_type'] = 10;
                $logData['operator_id'] = 0;
                $logData['log_uid'] = $house_work['wid'] ? $house_work['wid'] : 0;
                $logData['log_content'] = '';
                $logData['log_imgs'] = '';
                $logData['add_time'] = $now_time;
                $logData['village_id'] = $village_id;
                $logData['property_id'] = $property_id;
                $otherData=array('opt_type'=>'assign_to_house_worker','wid'=>$worker_id,'village_id'=>$village_id);
                $house_new_repair_works_order_service->addRepairLog($logData,$otherData);
            }
            if ($jz_open) {
                if (isset($communityConfig['not_timely_report_switch'])) {
                    $not_timely_report_switch = intval($communityConfig['not_timely_report_switch']);
                    $isPublic = false;
                    if (isset($order['address_type']) && $order['address_type'] == 'public') {
                        $isPublic = true;
                    } elseif (isset($order['public_id']) && intval($order['public_id']) > 0) {
                        $isPublic = true;
                    }
                    if ($not_timely_report_switch == 0 && $isPublic) {
                        $noticeArr = [
                            'village_id' => $village_id, 'works_order_id' => $order_id,
                        ];
                        (new HouseCommunityHandleNoticeService())->tryHandlePublicToReportBox($noticeArr);
                    }
                }
                $assign_record && $workOrderConfig['assign_record'] = json_encode($assign_record,JSON_UNESCAPED_UNICODE);
                $workOrderConfig['works_order_id'] = $order_id;
                $workOrderConfig['add_time'] = $now_time;
                $serviceHouseCommunityWorkOrder = new HouseCommunityWorkOrderService();
                $configId = $serviceHouseCommunityWorkOrder->addHouseCommunityWorkOrderConfig($workOrderConfig);
                if($worker_id){
                    $workRecord = [
                        'worker_id' => $worker_id, 'village_id' => $village_id, 'property_id' => $property_id,
                        'works_order_id' => $order_id, 'latest_receive_time' => $now_time
                    ];
                    $serviceHouseCommunityWorkOrder->handleWorkOrderWorkRecord($workRecord);
                }
                if ($configId && !empty($communityConfig)) {
                    $workName = isset($house_work['name']) && $house_work['name'] ? trim($house_work['name']) : '';
                    $workPhone = isset($house_work['phone']) && $house_work['phone'] ? trim($house_work['phone']) : '';
                    $handleParams = [
                        'configId' => $configId, 'village_id' => $village_id, 'communityConfig' => $communityConfig,
                        'nowTime' => $now_time, 'worker_id' => $worker_id, 'workName' => $workName, 'workPhone' => $workPhone,
                        'works_order_id' => $order_id,
                    ];
                    $serviceHouseCommunityWorkOrder->handleIncreaseWorkOrderConfig($handleParams);
                }
            }
            if($order['event_status']<40){
                //当提交的工单有指派时
                if($order['worker_id'] > 0){
                    //针对工作人员提交的工单有指派时 给提交人发送通知
                    if($order['order_type'] == 4){
                        $house_new_repair_works_order_service->newRepairSendMsg(11,$order_id,$uid,$property_id);
                    }
                    //给工作人员发送通知
                    $house_new_repair_works_order_service->newRepairSendMsg(12,$order_id,0,$property_id);
                } else {
                    //todo 开启抢单模式
                    $house_new_repair_works_order_service->grabOrder($village_id,$order_id,$cat_id);
                }
            }
        }
        $add_msg = [];
        $add_msg['order_id'] = $order_id;
        $add_msg['status'] = 'todo';
        return $add_msg;
    }


    //todo 计算返回及时接单时间
    public function handleTimely($timely_time){
        $str=0;
        if(!empty($timely_time) && strpos($timely_time,':') !==false){
            $rr=explode(':',$timely_time);
            $str=(intval($rr[0]) * 60)+($rr[1]);
        }
        return $str;
    }

    //todo 查询分类对应绑定的部门
    public function getCateGroup($village_id,$cate_id){
        $data= (new HouseNewRepairCateGroupRelation())->getColumn([['village_id', '=', $village_id], ['cate_id', '=', $cate_id]],'group_id');
        if($data){
            $data=(new HouseProgrammeService())->asseOrgId($data);
        }
        return $data;
    }

    //todo 添加分类对应绑定的部门
    public function addCateGroup($type,$village_id,$cate_id,$param){
        $dbRelation=new HouseNewRepairCateGroupRelation();
        $status=(new HouseVillageService())->checkVillageField($village_id,'is_grab_order');
        if(!$status){
            return true;
        }
        if(empty($param)){
            throw new \think\Exception('请至少勾选上一个关联部门');
        }
        if($type == 2){
            $dbRelation->delOne([['village_id', '=', $village_id], ['cate_id', '=', $cate_id]]);
        }
        $getOrgId=(new HouseProgrammeService())->getOrgId($param);
        if(empty($getOrgId)){
            return true;
        }
        $data=[];
        foreach ($getOrgId as $v){
            $data[]=[
                'village_id'=>$village_id,
                'cate_id'=>$cate_id,
                'group_id'=>$v,
                'add_time'=>time()
            ];
        }
        if($data){
            $dbRelation->addAll($data);
        }
        return true;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/3/12 15:39
 */
namespace app\community\model\service;

use app\community\model\db\HouseVillage;
use app\community\model\db\VillageQywxChannelGroup;
use app\community\model\db\VillageQywxChannelCode;
use app\community\model\db\HouseEnterpriseWxBind;
use app\community\model\db\HouseWorker;
use app\community\model\db\VillageQywxCodeLabel;
use app\community\model\db\VillageQywxCodeBindWork;
use app\community\model\db\VillageQywxCodeBindLabel;
use app\community\model\db\VillageQywxEngineContent;

class ChannelCodeService
{
    /**
     * Notes: 获取渠道活码分类数据
     * @param $type
     * @param $property_id
     * @param $village_id
     * @param bool $is_return 是否不进行处理直接返回结果
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/12 17:08
     */
    public function getGroupMenu($type,$property_id,$village_id=0,$is_return=false,$gid=0)
    {
        //小区
        $whereRaw = '';
        if($type == 1){
            //小区
            $where = [];
            if ($property_id) {
                $whereRaw = "status=1 AND ((type=1 AND village_id={$village_id}) OR (type=2 AND property_id={$property_id}))";
            } else {
                $where[] = ['type','=',$type];
                $where[] = ['village_id','=',$village_id];
            }
        }else{
            $dbHouseVillage = new HouseVillage();
            $where_village = [];
            $where_village[] = ['property_id','=',$property_id];
            $where_village[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($where_village,'village_id');
            $where = [];
            if($village_id_arr && count($village_id_arr)>0)
            {
                $village_id_str = implode(',',$village_id_arr);
                if (!$whereRaw) {
                    $whereRaw .= "status=1 AND ((type=2 AND property_id={$property_id}) OR (type=1 AND village_id in ({$village_id_str})))";
                } else {
                    $whereRaw .= " AND status=1 AND ((type=2 AND property_id={$property_id}) OR (type=1 AND village_id in ({$village_id_str})))";
                }
            }else{
                $where[] = ['type','=',$type];
                $where[] = ['property_id','=',$property_id];
            }
        }
        if (!empty($where)) {
            $where[] = ['status','=',1];
        }
        $dbvillage_qywx_channel_group = new VillageQywxChannelGroup();
        $field = true;
        $list = $dbvillage_qywx_channel_group->getList($where,$whereRaw,$field,'pid ASC,id DESC');
        if ($is_return) {
            return $list;
        }
        $total_sum_number = $dbvillage_qywx_channel_group->getSum($where,$whereRaw,'number');
        if (!$total_sum_number) {
            $total_sum_number = 0;
        }
        // 记录对应key
        $arr_data = [];
        $key_arr = [];
        $expandedKeys = [];
        $key_arr[0] = '0-0';

        $new_list = [];
        $new_key_list = [];
        $choose_data = ['0-0'];
        $choose_id = 0;
        if (!empty($list)) {
            $list = $list->toArray();

            foreach ($list as $key=>$val){
                if ($val['type']!=$type) {
                    $add = 0;
                    $edit = 0;
                    $del = 0;
                } else {
                    $add = 1;
                    $edit = 1;
                    $del = 1;
                }
                if($val['pid'] == 0) {
                    $map = [];
                    $map[] = ['pid', '=', $val['id']];
                    $sum_number = $dbvillage_qywx_channel_group->getSum($map, '','number');
                    $sum_number = $sum_number+$val['number'];
                }else{
                    $sum_number = $val['number'];
                }
                if (!isset($val['pid']) || !$val['pid']) {
                    $val['pid'] = 0;
                }
                $msg = [
                    'id'=>$val['id'],
                    'pid'=>$val['pid'],
                    'title'=> $val['name'].' ('.$sum_number.')',
                    'key'=> $val['id'].'-'.$key_arr[$val['pid']],
                    'scopedSlots'=> ['title'=> "edit_out"],
                    'is_add' => $add,
                    'is_edit' => $edit,
                    'is_del' => $del,
                ];
                $key_arr[$val['id']] = $msg['key'];
                $new_key_list[$val['id']] = $msg;
                if (empty($expandedKeys) || !$val['pid']) {
                    $expandedKeys[] = $msg['key'];
                }
                if ($gid && $gid==$val['id']) {
                    $choose_data = [$msg['key']];
                }
//                if (empty($choose_data) && !$val['pid']) {
//                    $choose_data = [$msg['key']];
//                    $choose_id = $val['id'];
//                }
                $new_list[] = $msg;
            }
            $new_arr = $this->getTree($new_list);
            if (empty($new_arr)) {
                $new_arr = [];
            }
            $parent = [
                'id'=>0,
                'title'=>'所有 ('.$total_sum_number.')',
                'key'=>'0-0',
                'scopedSlots'=> ['title'=> "add_out"],
                'children' => $new_arr
            ];
            $arr_data[] = $parent;
        } else {
            $list = [];
        }
        $key = [
            ['key'=> "1-1", 'assets_id'=> 1],
            ['key'=> "4-4", 'assets_id'=> 4]
        ];
        $data['menu_list'] = $arr_data;
        $data['key'] = $key;
        $data['street_id'] = $property_id;
        // 默认展开的一级分类
        $data['expandedKeys'] = $expandedKeys;
        // 默认选中的一级分类
        $data['choose_data'] = $choose_data;
        $data['choose_id'] = $choose_id;
        return $data;
    }

    /**
     * Notes: 处理成树状图数据
     * @param $list
     * @param string $fid
     * @return array
     * @author: wanzy
     * @date_time: 2021/3/12 16:56
     */
    public function getTree($list,$fid ="pid")
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

    /**
     * Notes: 添加编辑分组
     * @param $id
     * @param $data
     * @param $param_id
     * @return VillageQywxChannelGroup|int|string
     * @author: wanzy
     * @date_time: 2021/3/12 19:19
     */
    public function subGroup($id,$data,$param_id)
    {
        $dbvillage_qywx_channel_group = new VillageQywxChannelGroup();
        // 查重
        if (isset($data['name']) && $data['name']) {
            $where_repeat = [];
            $where_repeat[] = ['name','=',$data['name']];
            $where_repeat[] = ['status','=', 1];
            if ($id) {
                $where_repeat[] = ['id','<>',$id];
            }
            $repeat_info = $dbvillage_qywx_channel_group->getFind($where_repeat,'id,pid');
            if (!empty($repeat_info)) {
                throw new \Exception("分组【{$data['name']}】已经存在，请添加其他名称");
            }
        }
        if($id){
            if($data['type'] == 1)
            {
                $data['village_id'] = $param_id;
            }else{
                $data['property_id'] = $param_id;
            }
            $where[] = ['id','=',$id];
            $res = $dbvillage_qywx_channel_group->editFind($where,$data);
        }else{
            if($data['type'] == 1)
            {
                $data['village_id'] = $param_id;
            }else{
                $data['property_id'] = $param_id;
            }
            $data['add_time'] = time();
            $res = $dbvillage_qywx_channel_group->addFind($data);
        }
        return $res;
    }

    /**
     * Notes: 获取单个详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/12 19:37
     */
    public function getGroupInfo($id)
    {
        $dbvillage_qywx_channel_group = new VillageQywxChannelGroup();
        $where[] = ['id','=',$id];
        $info = $dbvillage_qywx_channel_group->getFind($where);
        if (!empty($info)) {
            $info = $info->toArray();
        } else {
            $info = [];
        }
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 删除
     * @param $id
     * @return VillageQywxChannelGroup
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/12 19:37
     */
    public function delGroup($id)
    {
        $dbvillage_qywx_channel_group = new VillageQywxChannelGroup();
        $map[] = ['pid','=',$id];
        $map[] = ['status','=',1];
        $info = $dbvillage_qywx_channel_group->getFind($map);
        if($info){
            throw new \Exception('该分组下有子分组，请先删除子分组');
        }
        $where[] = ['id','=',$id];
        $data['status'] = 4;
        $data['del_time'] = time();
        $res = $dbvillage_qywx_channel_group->editFind($where,$data);
        //需要删除分组下的成员

        return $res;
    }

    /**
     * Notes: 获取活码信息
     * @param $data
     * @return array
     * @author: wanzy
     * @date_time: 2021/3/15 14:28
     */
    public function getChannelCodeList($data) {
        $db_village_qywx_channel_code = new VillageQywxChannelCode();
        $where = [];
        $type = intval($data['type'])>0 ? intval($data['type']) : 1;
        if (isset($data['page'])) {
            $page = intval($data['page'])>0 ? intval($data['page']) : 1;
        } else {
            $page = 0;
        }
        $village_id = intval($data['village_id'])>0 ? intval($data['village_id']) : 0;
        $property_id = intval($data['property_id'])>0 ? intval($data['property_id']) : 0;
        $whereRaw = '';
        if ($type==1) {
            // 小区  可以看小区自身和对应物业的
            $whereRaw = "((a.add_type=1 AND a.village_id={$village_id}) OR a.add_type=2 AND a.property_id={$property_id})";
        } elseif ($type==2) {
            // 物业  可以看下属小区和物业自己的
            $dbHouseVillage = new HouseVillage();
            $where_village = [];
            $where_village[] = ['property_id','=',$property_id];
            $where_village[] = ['status','in',[0,1]];
            $village_id_arr = $dbHouseVillage->getColumn($where_village,'village_id');
            $where = [];
            if($village_id_arr && count($village_id_arr)>0)
            {
                $village_id_str = implode(',',$village_id_arr);
                if (!$whereRaw) {
                    $whereRaw = "((a.add_type=2 AND a.property_id={$property_id}) OR (a.add_type=1 AND a.village_id in ({$village_id_str})))";
                } else {
                    $whereRaw .= " AND ((a.add_type=2 AND a.property_id={$property_id}) OR (a.add_type=1 AND a.village_id in ({$village_id_str})))";
                }
            }else{
                $where[] = ['a.add_type','=',$type];
                $where[] = ['a.property_id','=',$property_id];
            }
        }
        if (isset($data['code_group_id']) && $data['code_group_id']) {
            // 先查询下 分组信息
            $dbvillage_qywx_channel_group = new VillageQywxChannelGroup();
            $where_code_group = [];
            $where_code_group[] = ['id','=',$data['code_group_id']];
            $code_group_info = $dbvillage_qywx_channel_group->getFind($where_code_group,'id,pid');
            $group_arr = [];
            $group_str = '';
            if (!$code_group_info['pid']) {
                // 查询子分类
                $where_code_group_child = [];
                $where_code_group_child[] = ['pid','=',$data['code_group_id']];
                $where_code_group_child[] = ['status','=',1];
                $code_group_children = $dbvillage_qywx_channel_group->getList($where_code_group_child,'','id,pid');
                if (!empty($code_group_children)) {
                    $code_group_children = $code_group_children->toArray();
                    if (!empty($code_group_children)) {
                        $group_arr[] = $data['code_group_id'];
                        foreach ($code_group_children as $v) {
                            if ($v['id']) {
                                $group_arr[] = $v['id'];
                            }
                        }
                    }
                }
            }
            if (!empty($group_arr)) {
                $group_str = implode(',',$group_arr);
            }
            if ($whereRaw) {
                if ($group_str) {
                    $whereRaw .= " AND a.code_group_id in ({$group_str})";
                } else {
                    $whereRaw .= " AND a.code_group_id={$data['code_group_id']}";
                }
            } else {
                if (!empty($group_arr)) {
                    $where[] = ['a.code_group_id', 'in', $group_arr];
                } else {
                    $where[] = ['a.code_group_id', '=', $data['code_group_id']];
                }
            }
        }
        if (isset($data['work_id']) && $data['work_id']) {
            if ($whereRaw) {
                $whereRaw .= " AND b.work_id={$data['work_id']}";
            } else {
                $where[] = ['b.work_id', '=', $data['work_id']];
            }
        }
        if (isset($data['code_name']) && $data['code_name']) {
            if ($whereRaw) {
                $whereRaw .= " AND a.code_name LIKE '%{$data['code_name']}%'";
            } else {
                $where[] = ['a.code_name', 'like', '%'.$data['code_name'].'%'];
            }
        }
        if (isset($data['status'])) {
            if ($whereRaw) {
                $whereRaw .= " AND a.status ={$data['status']}";
            } else {
                $where[] = ['a.status', '=', $data['status']];
            }
        } else {
            if ($whereRaw) {
                $whereRaw .= " AND a.status<>4";
            } else {
                $where[] = ['a.status', '<>', 4];
            }
        }
        $limit = 10;
        $list = $db_village_qywx_channel_code->getList($where,$whereRaw,$page,'a.*,c.name as group_name','a.code_id ASC',$limit);
        $count = $db_village_qywx_channel_code->getContentCount($where, $whereRaw);
        if (!$count) {
            $count = 0;
        }
        if (!empty($list)) {
            $list = $list->toArray();
            $dbVillageQywxCodeBindLabel = new VillageQywxCodeBindLabel();
            foreach ($list as &$val) {
                if ($type!=$val['add_type']) {
                    $val['is_operation'] = false;
                } else {
                    $val['is_operation'] = true;
                }

                if (isset($val['code_url']) && $val['code_url']) {
                    $val['code_url_txt'] = replace_file_domain($val['code_url']);
                }
                if (isset($val['add_time']) && $val['add_time']) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if (isset($val['label_txt']) && $val['label_txt'] && isset($val['code_id']) && $val['code_id']) {
                    $whereLabel = [];
                    $whereLabel[] = ['c.code_id','=', $val['code_id']];
                    $label = $dbVillageQywxCodeBindLabel->getCodeLabel($whereLabel,0,'a.code_id,a.label_id,b.label_name');
                    if (!empty($label)) {
                        $val['label_arr'] = $label->toArray();
                    } else {
                        $val['label_arr'] = [];
                    }
                }
            }
        } else {
            $list = [];
        }
        $arr = [];
        $arr['list'] = $list;
        $arr['total_limit'] = $limit;
        $arr['count'] = $count;
        return $arr;
    }

    /**
     * Notes: 获取工作人员列表
     * @param int $village_id
     * @param int $house_property_id
     * @return array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/16 10:06
     */
    public function getWorkList($village_id=0,$house_property_id=0, $work_field = true) {
        if (!$village_id && !$house_property_id) {
            return [];
        }
        $dbHouseVillage = new HouseVillage();
        if(!$house_property_id && $village_id){
            $village_info = $dbHouseVillage->getOne($village_id,'property_id');
            $house_property_id = $village_info['property_id'];
        }
        $db_house_enterprise_wx_bind = new HouseEnterpriseWxBind();
        $where_enterprise_wx = [];
        $where_enterprise_wx[] = ['bind_id','=',$house_property_id];
        $where_enterprise_wx[] = ['bind_type','=',0];
        $enterprise_wx_corpid = $db_house_enterprise_wx_bind->getSome($where_enterprise_wx,'pigcms_id,corpid')->toArray();
        if (empty($enterprise_wx_corpid)) {
            // 未绑定微信 不返回工作人员信息
            return [];
        }
        $dbHouseWorker = new HouseWorker();
        $where = [];
        if ($house_property_id) {
            $where_village = [];
            $where_village[] = ['property_id','=',$house_property_id];
            $where_village[] = ['status','in','0,1'];
            $village_id_arr = $dbHouseVillage->getColumn($where_village,'village_id');
        } else {
            $village_id_arr = [];
        }
        $whereRaw = '';
        if(!empty($village_id_arr) && count($village_id_arr)>0)
        {
            $whereRaw = "status=1 AND qy_status=1 AND qy_id<>''";
            $village_id_str = implode(',',$village_id_arr);
            if (!$whereRaw) {
                $whereRaw .= "(property_id={$house_property_id} OR village_id in ({$village_id_str}))";
            } else {
                $whereRaw .= " AND (property_id={$house_property_id} OR village_id in ({$village_id_str}))";
            }
        } elseif (!$village_id && $house_property_id) {
            $where[] = ['property_id','=',$house_property_id];
        } elseif ($village_id) {
            $where[] = ['village_id','=',$village_id];
            $where[] = ['status','=',1];
            $where[] = ['qy_status','=',1];
            $where[] = ['qy_id','<>',''];
        } else {
            return [];
        }
        $work_list = $dbHouseWorker->getWorkList($where,$whereRaw, $work_field);
        if (!empty($work_list)) {
            $work_list = $work_list->toArray();
        } else {
            $work_list = [];
        }
        return $work_list;
    }

    /**
     * Notes: 添加编辑时候获取详情
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/18 15:24
     */
    public function getChannelCodeDetail($data) {
        $db_village_qywx_channel_code = new VillageQywxChannelCode();
        $dbvillage_qywx_channel_group = new VillageQywxChannelGroup();
        $where = [];
        $arr = [];
        $channel_group_value = [];
        if (isset($data['code_id']) && $data['code_id']) {
            $where[] = ['code_id','=',$data['code_id']];
            $detail = $db_village_qywx_channel_code->getOne($where);
            if (isset($detail['welcome_img']) && $detail['welcome_img']) {
                $detail['welcome_img_txt'] = replace_file_domain($detail['welcome_img']);
                $file_info =  pathinfo($detail['welcome_img_txt']);
                if (isset($file_info['basename']) && $file_info['basename']) {
                    $detail['welcome_img_name'] = $file_info['basename'];
                } elseif (isset($file_info['filename']) && $file_info['filename'] && isset($file_info['extension']) && $file_info['extension']) {
                    $detail['welcome_img_name'] = $file_info['filename'].'.'.$file_info['extension'];
                }
            }
            if (isset($detail['code_group_id']) && $detail['code_group_id']) {
                $where_code_group = [];
                $where_code_group[] = ['id','=',$detail['code_group_id']];
                $code_group_info = $dbvillage_qywx_channel_group->getFind($where_code_group,'id,pid');
                if ($code_group_info && isset($code_group_info['id']) && $code_group_info['id'] && $code_group_info['pid']) {
                    $channel_group_value[] = $code_group_info['pid'];
                    $channel_group_value[] = $code_group_info['id'];
                }
            }
            // 处理关联工作人员
            $work_id_arr = [];
            if (isset($detail['work_arr']) && $detail['work_arr']) {
                $dbHouseWorker = new HouseWorker();
                $where_work = [];
                $where_work[] = ['b.code_id','=',$data['code_id']];
                $where_work[] = ['a.status','=',1];
                $work_list = $dbHouseWorker->getVillageQywxCodeBindWork($where_work);
                if (!empty($work_list)) {
                    $work_list = $work_list->toArray();
                    foreach ($work_list as $val) {
                        $work_id_arr[] = $val['wid'];
                    }
                } else {
                    $work_list = [];
                    $work_arr = unserialize($detail['work_arr']);
                    if ($work_arr) {
                        $work_id_arr = $work_arr;
                    }
                }
                $detail['work_list'] = $work_list;
            }
            $detail['work_id_arr'] = $work_id_arr;
            // 处理标签
            $label_id_arr = [];
            $choose_tag_index = [];
            if (isset($detail['label_txt']) && $detail['label_txt']) {
                $dbVillageQywxCodeLabel = new VillageQywxCodeLabel();
                $where_label = [];
                $where_label[] = ['b.code_id','=',$data['code_id']];
                $where_label[] = ['a.status','=',1];
                $label_list = $dbVillageQywxCodeLabel->getVillageQywxCodeBindLabel($where_label,'a.label_id');
                if (!empty($label_list)) {
                    $label_list = $label_list->toArray();
                    foreach ($label_list as $vals) {
                        $label_id_arr[] = $vals['label_id'];
                        $choose_tag_index[$vals['label_id']] = $vals['label_id'];
                    }
                } else {
                    $label_txt = unserialize($detail['label_txt']);
                    if ($label_txt) {
                        $label_id_arr = $label_txt;
                        foreach ($label_txt as $items) {
                            $choose_tag_index[$items] = $items;
                        }
                    }
                }
            }
            $detail['label_id_arr'] = $label_id_arr;
            $detail['choose_tag_index'] = $choose_tag_index;
        } else {
            $detail = [];
        }
        $type = isset($data['type']) && $data['type'] ? $data['type'] : 1;
        $property_id = isset($data['property_id']) && $data['property_id'] ? $data['property_id'] : 0;
        $village_id = isset($data['village_id']) && $data['village_id'] ? $data['village_id'] : 0;
        $channel_group_list = $this->getGroupMenu($type,$property_id,$village_id,true);
        $label_group = [];
        if (!empty($channel_group_list)) {
            $channel_group_list = $channel_group_list->toArray();
            foreach ($channel_group_list as $key1=>$val1){
                if ((!isset($val1['pid']) || $val1['pid']==0) && !isset($label_group[$val1['id']])) {
                    $msg = [
                        'id' => $val1['id'],
                        'value' => $val1['id'],
                        'label' => $val1['name'],
                        'children' => []
                    ];
                    $label_group[$val1['id']] = $msg;
                } elseif ((!isset($val1['pid']) || $val1['pid']==0) && isset($label_group[$val1['id']])) {
                    $msg = $label_group[$val1['id']];
                    $msg['id'] = $val1['id'];
                    $msg['value'] = $val1['id'];
                    $msg['label'] = $val1['name'];
                } elseif (isset($val1['pid']) && $val1['pid'] && isset($label_group[$val1['pid']])) {
                    $children = $label_group[$val1['pid']]['children'];
                    $msg = [
                        'id' => $val1['id'],
                        'value' => $val1['id'],
                        'label' => $val1['name'],
                    ];
                    $children[] = $msg;
                    $label_group[$val1['pid']]['children'] = $children;
                } elseif  (isset($val1['pid']) && $val1['pid'] && !isset($label_group[$val1['pid']])) {
                    $children = [];
                    $msg = [
                        'id' => $val1['id'],
                        'value' => $val1['id'],
                        'label' => $val1['name'],
                    ];
                    $children[] = $msg;
                    $label_group[$val1['pid']]['children'] = $children;
                }
            }
            if (!empty($label_group)) {
                $label_group = array_values($label_group);
            }
        }

        $arr['detail'] = $detail;
        $work_arr = [];
        $post_data = [
            'code_group_id'=>'',
            'code_name'=>'',
            'work_arr'=>$work_arr,
            'is_send'=>true,
            'skip_verify'=>true,
            'welcome_tip'=>'',
            'welcome_img'=>'',
            'welcome_url'=>'',
            'engine_content_id'=>0
        ];
        if (!empty($detail)) {
            if (isset($detail['code_id']) && $detail['code_id']) {
                $post_data['code_id'] = $detail['code_id'];
            }
            if (isset($detail['code_group_id']) && $detail['code_group_id']) {
                $post_data['code_group_id'] = $detail['code_group_id'];
            }
            if (isset($detail['code_name']) && $detail['code_name']) {
                $post_data['code_name'] = $detail['code_name'];
            }
            if (isset($detail['work_id_arr']) && $detail['work_id_arr']) {
                $post_data['work_arr'] = $detail['work_id_arr'];
            }
            if (isset($detail['is_send']) && $detail['is_send']) {
                $post_data['is_send'] = $detail['is_send']==1 ? true : false;
            }
            if (isset($detail['skip_verify']) && $detail['skip_verify']) {
                $post_data['skip_verify'] = $detail['skip_verify']==1 ? true : false;
            }
            if (isset($detail['welcome_tip']) && $detail['welcome_tip']) {
                $post_data['welcome_tip'] = $detail['welcome_tip'];
            }
            if (isset($detail['welcome_img']) && $detail['welcome_img']) {
                $post_data['welcome_img'] = $detail['welcome_img'];
            }
            if (isset($detail['welcome_url']) && $detail['welcome_url']) {
                $post_data['welcome_url'] = $detail['welcome_url'];
            }
            if (isset($detail['engine_content_id']) && $detail['engine_content_id']) {
                $post_data['engine_content_id'] = $detail['engine_content_id'];
                $dbVillageQywxEngineContent = new VillageQywxEngineContent();
                $where_engine = [];
                $where_engine[] = ['id','=', $detail['engine_content_id']];
                $record = $dbVillageQywxEngineContent->getFind($where_engine);
                if (!empty($record)) {
                    $record = $record->toArray();
                }
                if(isset($record['share_img']) && $record['share_img']){
                    $record['share_img_txt'] = replace_file_domain($record['share_img']);
                }
                $arr['record'] = $record;
            }
        }
        $arr['post_data'] = $post_data;
        $arr['label_group'] = $label_group;
        $arr['channel_group_value'] = $channel_group_value;
        $arr['tipLabelName'] = $this->tipLabelName;
        return $arr;
    }

    public $tipLabelName = '<span><span class="ant-tag ant-tag-orange" contenteditable="false">客户名称</span></span>';
    public $replaceTipLabelName = '{客户名称}';

    /**
     * Notes: 添加活码
     * @param $data
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/23 15:47
     */
    public function addCode($data) {
        if(!isset($data['code_group_id']) || !$data['code_group_id']){
            throw new \think\Exception('请选择分组');
        }
        if(!isset($data['code_name']) || !$data['code_name']){
            throw new \think\Exception('请填写活码名称');
        }
        if(!isset($data['work_arr']) || empty($data['work_arr'])){
            throw new \think\Exception('请选择绑定员工');
        }
        $work_arr = $data['work_arr'];
        $dbHouseWorker = new HouseWorker();
        $where_work = [];
        $where_work[] = ['wid','in',$work_arr];
        $where_work[] = ['status','=',1];
        $where_work[] = ['qy_id','<>',''];
        $where_work[] = ['qy_status','=',1];
        $work_field = 'wid,name';
        $work_list = $dbHouseWorker->getWorkList($where_work,'', $work_field);
        if (empty($work_list)) {
            throw new \think\Exception('请选择绑定正确的员工');
        } else {
            $work_list = $work_list->toArray();
        }
        $dbVillageQywxChannelGroup = new VillageQywxChannelGroup();
        $where_group = [];
        $where_group[] = ['id', '=', $data['code_group_id']];
        $code_group = $dbVillageQywxChannelGroup->getFind($where_group);
        if (empty($code_group)) {
            throw new \think\Exception('当前选择分组不存在或者已经被删除');
        }
        $dbVillageQywxChannelCode = new VillageQywxChannelCode();
        $now_time = time();
        // 传递了id编辑相关信息
        if (isset($data['code_id']) && $data['code_id']) {
            $where = [];
            $set_data = $data;
            $code_id = $set_data['code_id'];
            $where[] = ['code_id', '=', $code_id];
            $code_single = $dbVillageQywxChannelCode->getOne($where,'code_id, config_id');
            if (empty($code_single)) {
                throw new \think\Exception('当前编辑对象不存在或者已经被删除');
            }
            $config_id = $code_single['config_id'];
            unset($set_data['code_id']);
            // 数组处理成英文逗号分隔
            if (isset($set_data['work_arr']) && !empty($set_data['work_arr'])) {
                $set_data['work_arr'] = implode(',',$set_data['work_arr']);
            } else {
                $set_data['work_arr'] = '';
                unset($set_data['work_arr']);
            }
            if (isset($set_data['tags']) && !empty($set_data['tags'])) {
                $set_data['label_txt'] = implode(',',$set_data['tags']);
                unset($set_data['tags']);
            } else {
                $set_data['label_txt'] = '';
                unset($set_data['tags']);
            }
            $set_data['last_time'] = $now_time;
            $set = $dbVillageQywxChannelCode->updateThis($where,$set_data);
            if (!$set) {
                throw new \think\Exception('渠道活码编辑失败');
            }
        } else {
            $config_id = '';
            $add_data = $data;
            // 数组处理成英文逗号分隔
            if (isset($add_data['work_arr']) && !empty($add_data['work_arr'])) {
                $add_data['work_arr'] = implode(',',$add_data['work_arr']);
            } else {
                $add_data['work_arr'] = '';
                unset($add_data['work_arr']);
            }
            if (isset($add_data['tags']) && !empty($add_data['tags'])) {
                $add_data['label_txt'] = implode(',',$add_data['tags']);
                unset($add_data['tags']);
            } else {
                $add_data['label_txt'] = '';
                unset($add_data['tags']);
            }
            $add_data['add_time'] = $now_time;
            $code_id = $dbVillageQywxChannelCode->add($add_data);
            if (!$code_id) {
                throw new \think\Exception('渠道活码添加失败');
            }
        }

        $dbVillageQywxCodeBindWork = new VillageQywxCodeBindWork();
        // 添加前删除之前绑定的工作人员
        if (isset($data['code_id']) && $data['code_id']) {
            $delWhere = [];
            $delWhere[] = ['code_id','=',$data['code_id']];
            $dbVillageQywxCodeBindWork->delWhere($delWhere);
        }
        foreach ($work_list as $val1) {
            $work_id = $val1['wid'];
            $code_bind_work = [
                'code_id' => $code_id,
                'work_id' => $work_id,
                'add_time' => $now_time,
            ];
            $dbVillageQywxCodeBindWork->add($code_bind_work);
        }
        $dbVillageQywxCodeBindLabel= new VillageQywxCodeBindLabel();
        // 添加前删除之前绑定的标签
        if (isset($data['code_id']) && $data['code_id']) {
            $delWhere = [];
            $delWhere[] = ['code_id','=',$data['code_id']];
            $dbVillageQywxCodeBindLabel->delWhere($delWhere);
        }
        // 存在标签 进行绑定
        if (isset($data['tags']) && !empty($data['tags'])) {
            $dbVillageQywxCodeLabel= new VillageQywxCodeLabel();
            $where_label= [];
            $where_label[] = ['label_id','in',$data['tags']];
            $label_field = 'label_id,label_name';
            $label_list = $dbVillageQywxCodeLabel->getSome($where_label,'', $label_field);
            foreach ($label_list as $val2) {
                $label_id = $val2['label_id'];
                $code_bind_label = [
                    'code_id' => $code_id,
                    'label_id' => $label_id,
                    'add_time' => $now_time,
                ];
                $dbVillageQywxCodeBindLabel->add($code_bind_label);
            }
        }
        // 处理下链接问题-上面数据全部添加处理完成后处理这个 避免获取企业微信那边数据报错导致数据未添加上
        $property_id = $data['property_id'];
        $param = [];
        $param['type'] = "village_qywx_channel_code";
        $param['remark'] = "【{$data['code_name']}】码";
        $param['state'] = "channelCode#{$code_id}|property_id#{$property_id}";
        if (isset($data['skip_verify'])) {
            $param['skip_verify'] = $data['skip_verify']==1 ? true : false;
        }
        if ($config_id) {
            $param['config_id'] = $config_id;
        }
        $serviceQywx = new QywxService();
        $contact_way = $serviceQywx->contactWay($property_id, $work_arr, $param);
        if (isset($contact_way['errmsg']) && $contact_way['errmsg']) {
            if (!isset($data['code_id']) || !$data['code_id']) {
                $where_del = [];
                $where_del[] = ['code_id', '=', $code_id];
                // 出错渠道活码同步删除
                $this->delCode($where_del);
            }
            throw new \think\Exception($contact_way['errmsg']);
        }
        $data_set = [];
        if (isset($contact_way['config_id']) && $contact_way['config_id'] && !$config_id) {
            $data_set['config_id'] = $contact_way['config_id'];
        }
        if (isset($contact_way['qr_code']) && $contact_way['qr_code']) {
            $data_set['code_url'] = $contact_way['qr_code'];
        }
        if (isset($contact_way['contact_way_json']) && $contact_way['contact_way_json']) {
            $data_set['contact_way_json'] = $contact_way['contact_way_json'];
        }
        if (!empty($data_set)) {
            $data_set['last_time'] = $now_time;
            $where = [];
            $where[] = ['code_id', '=', $code_id];
            $set = $dbVillageQywxChannelCode->updateThis($where,$data_set);
            if ($set!==false) {
                // 编辑渠道活码
            } else {
                if (!isset($data['code_id']) || !$data['code_id']) {
                    $where_del = [];
                    $where_del[] = ['code_id', '=', $code_id];
                    // 出错渠道活码同步删除
                    $this->delCode($where_del);
                }
                throw new \think\Exception('渠道活码记录信息失败');
            }
        }
        if (!isset($data['code_id']) || !$data['code_id']) {
            // 删除对应渠道活码更新数量
            $dbVillageQywxChannelCode->updateNum($data['code_group_id']);
        }
        return $code_id;
    }

    /**
     * Notes: 删除
     * @param $where
     * @return mixed
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/3/23 16:12
     */
    public function delCode($where, $type=1, $param_id=0) {
        if(empty($where)){
            throw new \think\Exception('请选择删除对象');
        }
        $dbVillageQywxChannelCode = new VillageQywxChannelCode();

        $list = $dbVillageQywxChannelCode->getSome($where,'code_id, code_group_id,village_id,property_id');
        $code_group_id_arr = [];
        if (empty($list)) {
            throw new \think\Exception('删除对象不存在或者已经被删除');
        } else {
            $list = $list->toArray();
            foreach ($list as $val) {
                if (1==$type && $param_id && $val['village_id']!=$param_id) {
                    throw new \think\Exception('您没有权限删除当前对象');
                } elseif (2==$type && $param_id && $val['property_id']!=$param_id) {
                    throw new \think\Exception('您没有权限删除当前对象');
                }
                $code_group_id_arr[] = $val['code_group_id'];
            }
        }

        $data = [
            'status' => 4,
            'last_time' => time(),
        ];
        $del = $dbVillageQywxChannelCode->updateThis($where,$data);
        if ($del!==false) {
            // 此处同步更新下删除数据
            foreach ($code_group_id_arr as $vals) {
                // 删除对应渠道活码前先对应更新下数量
                $dbVillageQywxChannelCode->updateNum($vals['code_group_id']);
            }
            return $del;
        } else {
            throw new \think\Exception('删除错误');
        }
    }

    /**
     * Notes:更新分组数据
     * @param $code_group_id
     * @return VillageQywxChannelGroup
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/3/30 15:50
     */
    public function updateChannelCodeNum($code_group_id) {
        if(empty($code_group_id)){
            throw new \think\Exception('缺少更新对象');
        }
        $dbVillageQywxChannelCode = new VillageQywxChannelCode();
        // 删除对应渠道活码前先对应更新下数量
        $update = $dbVillageQywxChannelCode->updateNum($code_group_id);
        return $update;
    }

    public function uploadCode($where, $code_group_id=0) {
        if(empty($where)){
            throw new \think\Exception('请选择下载对象');
        }
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $dbVillageQywxChannelCode = new VillageQywxChannelCode();
        $where[] = ['code_url', '<>', ''];
        $list = $dbVillageQywxChannelCode->getSome($where, 'code_id, code_name, code_url');
        if ($code_group_id>0) {
            $dbVillageQywxChannelGroup = new VillageQywxChannelGroup();
            $where_group = [];
            $where_group[] = ['id', '=', $code_group_id];
            $code_group = $dbVillageQywxChannelGroup->getFind($where_group, 'id,name');
            $name = $code_group['name'].'_'.uniqid();
        } else {
            $name = time().'_'.uniqid();
        }
        $date_time = date('Ymd');// 换成日期存储
        $root_url = request()->server('DOCUMENT_ROOT');
        $base_file =  $root_url.'/upload/lineLocal/'.$date_time.'/'.$name;
        if (!empty($list)) {
            $list = $list->toArray();
            $files = [];
            foreach ($list as &$val) {
                $file_name = $val['code_name'] . "_{$val['code_id']}.png";
                $url = $this->imgOnLineToLocal($val['code_url'], $base_file,$file_name);
                $files[] = [
                    'file_name' => $file_name,
                    'file' => $url,
                ];
            }
            $baseUrl =  $root_url.'/upload/lineLocal/'.$date_time.'/zip/';
            $fileName = $name . '.zip';
            $zip_name_path = $this->zipFile($base_file,$baseUrl,$fileName,$files);
            if ($zip_name_path) {
                $zip_name_path = str_replace($root_url, '', $zip_name_path);
//                $zip_name_path = replace_file_domain($zip_name_path);
                $zip_name_path = cfg('site_url') . $zip_name_path;
            }
            return $zip_name_path;
        } else {
            throw new \think\Exception('当前下载对象不存在或者没有图片文件');
        }
    }

    /**
     * Notes: 线上图片转移到本地
     * @param $base_img
     * @param string $base_file
     * @return bool|string
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/3/24 9:10
     */
    public function imgOnLineToLocal($base_img, $base_file='',$file_name='') {
        $date_time = date('Ymd');// 换成日期存储
        if (!$base_file) {
            $base_file = request()->server('DOCUMENT_ROOT').'/upload/lineLocal/'.$date_time.'/';
        }
        $up_dir = "{$base_file}/";
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        $file_info =  pathinfo($base_img);
        if ($file_name) {
            $new_file = $up_dir.$file_name;
        } elseif ($file_info && $file_info['basename']) {
            $new_file = $up_dir.$file_info['basename'];
        } elseif ($file_info && $file_info['extension']) {
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$file_info['extension'];
        } else {
            $type = 'jpg';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
        }
        # 远程文件处理
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$base_img);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  # 过期时间
        //当请求https的数据时，会要求证书，加上下面这两个参数，规避ssl的证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $img = curl_exec($ch);
        curl_close($ch);
        $fp2 = @fopen($new_file ,'a');
        fwrite($fp2,$img);
        fclose($fp2);
        unset($img,$url);
        $img_path = trim($new_file,'.');
        return $img_path;
    }

    /**
     * Notes:打包zip
     * @param $filePath
     * @param string $baseUrl
     * @param string $fileName
     * @param bool $isDown
     * @return string
     * @throws \think\Exception
     * @author: wanzy
     * @date_time: 2021/3/24 10:01
     */
    public function zipFile($filePath,$baseUrl='',$fileName='',$files=[],$isDown=false){
        // 文件名为空则生成文件名
        if (!$fileName) {
            $fileName = 'file_' . date('YmdHis') . '.zip';
        }
        // 基础路径不存在 默认一个
        if (!$baseUrl) {
            $date_time = date('Ymd');// 换成日期存储
            $baseUrl =  request()->server('DOCUMENT_ROOT').'/upload/lineLocal/'.$date_time.'/zip/';
        }
        if (!is_dir($baseUrl)){
            mkdir($baseUrl,0777,true);
        }
        $zip_name_path = $baseUrl . $fileName;
        $dir = dirname($zip_name_path);
        if (!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        // 实例化类,使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释
        /*
        * 通过ZipArchive的对象处理zip文件
        * $zip->open这个方法如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        * $zip->open这个方法第一个参数表示处理的zip文件名。
        * 这里重点说下第二个参数，它表示处理模式
        * ZipArchive::OVERWRITE 总是以一个新的压缩包开始，此模式下如果已经存在则会被覆盖。
        * ZipArchive::OVERWRITE 不会新建，只有当前存在这个压缩包的时候，它才有效
        * */
        $zip = new \ZipArchive;

        if($zip->open($zip_name_path, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE) === TRUE){
            // 文件夹打包处理
            if (!empty($files)) {
                foreach ($files as $val) {
                    $zip->addFile($val['file'], $val['file_name']);
                }
                $zip->close();
            } else {
                $this->addFileToZip($filePath, $zip);
            }
        } else {
            throw new \think\Exception('无法打开文件，或者文件创建失败');
        }
        if ($isDown) {
            // ob_clean();
            // 下载压缩包
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename=' . basename($zip_name_path)); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: ' . filesize($zip_name_path)); //告诉浏览器，文件大小
            @readfile($zip_name_path);//ob_end_clean();
            @unlink(app()->getRootPath().'public/'.$zip_name_path);//删除压缩包
        } else {
            // 直接返回压缩包地址
            @unlink($filePath);
            return $zip_name_path;
        }
    }

    function addFileToZip($path, $zip) {
        $handler = opendir($path); //打开当前文件夹由$path指定。

        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
                if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归
                    $this->addFileToZip($path . "/" . $filename, $zip);
                } else { //将文件加入zip对象
                    $zip->addFile($path . "/" . $filename);
                }
            }
        }
        @closedir($path);
    }
}
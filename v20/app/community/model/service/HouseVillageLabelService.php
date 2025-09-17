<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/9
 * Time: 13:27
 *======================================================
 */

namespace app\community\model\service;



use app\community\model\db\HouseAdminGroupLabelRelation;
use app\community\model\db\HouseVillageLabel;
use app\community\model\db\HouseVillageLabelCat;
use app\community\model\db\HouseVillageUserLabel;

class HouseVillageLabelService
{
    public $label_type = ['政治面貌','特殊人群','重点人群','关怀对象'];

    /**
     * 获取小区用户标签列表
     * @param $village_id
     * @param string $field
     * @param bool $group
     * @param int $page
     * @param int $limit
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseVillageLabel($village_id,$field = '*',$page = 0,$limit = 10){
        $where = [];
        $where[] = ['village_id','=',$village_id];
        $where[] = ['is_delete','=',0];
        $where[] = ['cat_id','=',0];
        // 获取标签总数
        $house_village_label = new HouseVillageLabel();
        $house_village_label_num = $house_village_label->getHouseVillageLabelCount($where);
        // 获取标签列表
        $house_village_label_data = $house_village_label->getHouseVillageLabel($where,$field,'id DESC',$page,$limit);
        $data = [];
        if(!empty($house_village_label_data)){
            foreach ($house_village_label_data as &$value){
                $value['label_type'] = $this->label_type[$value['label_type']];
                $value['create_at'] = date('Y-m-d H:i:s',$value['create_at']);
            }
        }
        $data['list'] = $house_village_label_data;
        $data['count'] = $house_village_label_num;
        $data['total_limit'] = $limit;
        return $data;
    }
    
    public function getHouseVillageUserLabelInfo($where,$field=true) {
        $dbHouseVillageUserLabel = new HouseVillageUserLabel();
        $user_label_arr = $dbHouseVillageUserLabel->getOne($where, $field);
        if ($user_label_arr && !is_array($user_label_arr)) {
            $user_label_arr = $user_label_arr->toArray();
        }
        return $user_label_arr;
    }

    public function handleUserLabel($bind_id) {
        $user_label_arr = $this->getHouseVillageUserLabelInfo(['bind_id' => $bind_id]);
        if (empty($user_label_arr)) {
            return [];
        }
        if ($user_label_arr && $user_label_arr['user_special_groups']) {
            $user_label_arr['user_special_groups'] = explode(',',$user_label_arr['user_special_groups']);
        }
        if ($user_label_arr && $user_label_arr['user_focus_groups']) {
            $user_label_arr['user_focus_groups'] = explode(',',$user_label_arr['user_focus_groups']);
        }
        if ($user_label_arr && $user_label_arr['user_vulnerable_groups']) {
            $user_label_arr['user_vulnerable_groups'] = explode(',',$user_label_arr['user_vulnerable_groups']);
        }
        $labelArr = [];
        $houseVillageUserLabelService =  new HouseVillageUserLabelService();
        // 政治面貌
        $political_affiliation_arr = $houseVillageUserLabelService->political_affiliation_arr;
        $user_political_affiliation = isset($user_label_arr['user_political_affiliation']) && $user_label_arr['user_political_affiliation'] ? $user_label_arr['user_political_affiliation'] : 0;
        if (isset($political_affiliation_arr[$user_political_affiliation]) && $political_affiliation_arr[$user_political_affiliation]) {
            $labelArr[] = [
                'id' => $user_political_affiliation,
                'label_name' => $political_affiliation_arr[$user_political_affiliation],
            ];
        }
        // 特殊人群
        $special_groups_arr = $houseVillageUserLabelService->special_groups_arr;
        $user_special_groups_arr = isset($user_label_arr['user_special_groups']) && $user_label_arr['user_special_groups'] ? $user_label_arr['user_special_groups'] : [];
        foreach ($user_special_groups_arr as $item1) {
            if (isset($special_groups_arr[$item1]) && $special_groups_arr[$item1]) {
                $labelArr[] = [
                    'id' => $item1,
                    'label_name' => $special_groups_arr[$item1],
                ];
            }
        }
        // 重点人群
        $focus_groups_arr = $houseVillageUserLabelService->focus_groups_arr;
        $user_focus_groups_arr = isset($user_label_arr['user_focus_groups']) && $user_label_arr['user_focus_groups'] ? $user_label_arr['user_focus_groups'] : [];
        foreach ($user_focus_groups_arr as $item2) {
            if (isset($focus_groups_arr[$item2]) && $focus_groups_arr[$item2]) {
                $labelArr[] = [
                    'id' => $item2,
                    'label_name' => $focus_groups_arr[$item2],
                ];
            }
        }
        // 弱势困难群体
        $vulnerable_groups_arr = $houseVillageUserLabelService->vulnerable_groups_arr;
        $user_vulnerable_groups_arr = isset($user_label_arr['user_vulnerable_groups']) && $user_label_arr['user_vulnerable_groups'] ? $user_label_arr['user_vulnerable_groups'] : [];
        foreach ($user_vulnerable_groups_arr as $item3) {
            if (isset($vulnerable_groups_arr[$item3]) && $vulnerable_groups_arr[$item3]) {
                $labelArr[] = [
                    'id' => $item3,
                    'label_name' => $vulnerable_groups_arr[$item3],
                ];
            }
        }
        return $labelArr;
    }
    
    /**
     * 获取用户标签列表 分组
     * @param $village_id
     * @param $bind_id
     * @param $field
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseVillageUserLabel($village_id,$bind_id,$field,$whereArr=array()){

       $houseVillageUserLabelService =  new HouseVillageUserLabelService();
        // 政治面貌
        $political_affiliation_title = $houseVillageUserLabelService->political_affiliation_title;
        $political_affiliation_arr = $houseVillageUserLabelService->political_affiliation_arr;
        // 特殊人群
        $special_groups_title = $houseVillageUserLabelService->special_groups_title;
        $special_groups_arr = $houseVillageUserLabelService->special_groups_arr;
        // 重点人群
        $focus_groups_title = $houseVillageUserLabelService->focus_groups_title;
        $focus_groups_arr = $houseVillageUserLabelService->focus_groups_arr;
        // 弱势困难群体
        $vulnerable_groups_title = $houseVillageUserLabelService->vulnerable_groups_title;
        $vulnerable_groups_arr = $houseVillageUserLabelService->vulnerable_groups_arr;

        //用户数据
        if($bind_id){
            $user_label_arr = $this->getHouseVillageUserLabelInfo(['bind_id' => $bind_id]);
            if (isset($user_label_arr['user_special_groups']) && $user_label_arr['user_special_groups']) {
                $user_label_arr['user_special_groups'] = explode(',',$user_label_arr['user_special_groups']);
            }
            if (isset($user_label_arr['user_focus_groups']) && $user_label_arr['user_focus_groups']) {
                $user_label_arr['user_focus_groups'] = explode(',',$user_label_arr['user_focus_groups']);
            }
            if (isset($user_label_arr['user_vulnerable_groups']) && $user_label_arr['user_vulnerable_groups']) {
                $user_label_arr['user_vulnerable_groups'] = explode(',',$user_label_arr['user_vulnerable_groups']);
            }
        }

        return [
            'political_affiliation_title' => $political_affiliation_title,
            'political_affiliation_arr' => $political_affiliation_arr,
            'special_groups_title' => $special_groups_title,
            'special_groups_arr' => $special_groups_arr,
            'focus_groups_title' => $focus_groups_title,
            'focus_groups_arr' => $focus_groups_arr,
            'vulnerable_groups_title' => $vulnerable_groups_title,
            'vulnerable_groups_arr' => $vulnerable_groups_arr,
            'user_label_arr' => $user_label_arr,
        ];
    }
    
    public function getVillageCustomLabel($village_id,$cat_function) {
        $db_house_village_label_cat = new HouseVillageLabelCat();
        $db_house_village_label = new HouseVillageLabel();
        $where = [
            'cat_function'=>$cat_function,'village_id'=>$village_id,'status'=>1
        ];
        $list = [];
        $cat_list = $db_house_village_label_cat->getList($where,'cat_id as id,cat_name as name');
        if ($cat_list && !is_array($cat_list)) {
            $cat_list = $cat_list->toArray();
        }
        if (empty($cat_list)){
            return $list;
        }
        $catIds = array_column($cat_list, 'id');
        $where_label = [];
        $where_label[] =  ['status', '=', 0];
        $where_label[] =  ['cat_id', 'in', $catIds];
        $label_list = $db_house_village_label->getLists($where_label, 'id,cat_id as pid,label_name as name');
        if ($label_list && !is_array($label_list)) {
            $label_list = $label_list->toArray();
        }
        $label_arr = [];
        foreach ($label_list as $item) {
            $pid = isset($item['pid']) && $item['pid'] ? $item['pid'] : 0;
            !isset($label_arr[$pid]) && $label_arr[$pid] = [];
            $label_arr[$pid][] = $item;
        }
        foreach ($cat_list as $v1) {
            $data = [
                'id' => $v1['id'],
                'name' => $v1['name']
            ];
            if (isset($label_arr[$v1['id']]) && $label_arr[$v1['id']]) {
                $data['children'] = $label_arr[$v1['id']];
            }
            $list[] = $data;
        }
        return $list;
    }

    /**
     * 新增、编辑标签
     * @param $type
     * @param $where
     * @param $data
     * @return bool
     */
    public function changeHouseVillageLabel($type,$where,$data){
        $house_village_label = new HouseVillageLabel();
        return $house_village_label->changeHouseVillageLabel($type,$where,$data);
    }

    /**
     * 获取标签详情
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHouseVillageLabelInfo($where,$field){
        $house_village_label = new HouseVillageLabel();
        $data = $house_village_label->getHouseVillageLabelInfo($where,$field);
        $data['label_type'] = $this->label_type[$data['label_type']];
        return $data;
    }


    /**
     * 查询权限标签分组
     * @author: liukezhu
     * @date : 2022/3/24
     * @param $village_id
     * @return \think\Collection
     */
    public function getPowerLabelAll($village_id){
        $where[] = ['c.cat_function','=','power'];
        $where[] = ['c.status','=',1];
        $where[] = ['c.village_id','=',$village_id];
        $where[] = ['b.status','=',0];
        $where[] = ['b.is_delete','=',0];
        $where[] = ['b.cat_id','>',0];
        $list= (new HouseVillageLabelCat())->getLabelData($where,'c.cat_id as id,c.cat_name as title,b.cat_id as pid,b.id as children_id,b.label_name as children_title','c.cat_id desc,b.id desc');
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
            foreach ($list as &$v){
                $v['id']=$v['id'].'_'.$v['children_id'];
                $v['title']=$v['title'].'/'.$v['children_title'];
            }
        }
        return $list;
    }

    //写入绑定标签记录
    public function groupLabelRelation($village_id,$group_id,$label_all){
        $HouseAdminGroupLabel=new HouseAdminGroupLabelRelation();
        $this->AdminGroupLabelDel([
            ['village_id','=',$village_id],
            ['group_id','=',$group_id]
        ]);
        if(empty($label_all)){
            return true;
        }
        $data=[];
        foreach ($label_all as $v){
            $str=explode('_',$v);
            $data[]=[
                'village_id'=>$village_id,
                'group_id'=>$group_id,
                'cat_id'=>$str[0],
                'label_id'=>$str[1],
                'add_time'=>date('Y-m-d H:i:s',time())
            ];
        }
        return $HouseAdminGroupLabel->addAll($data);
    }

    //获取权限组标签
    public function getPowerLabel($village_id,$group_id){
        $list=(new HouseAdminGroupLabelRelation())->getGroupLabel([
            ['g.village_id','=',$village_id],
            ['g.group_id','=',$group_id],
            ['b.status','=',0],
            ['b.is_delete','=',0],
            ['c.status','=',1],
        ],'g.label_id desc','g.cat_id,g.label_id');
        $data=[];
        if ($list && !$list->isEmpty()) {
            $list = $list->toArray();
            foreach ($list as $v){
                $data[]=$v['cat_id'].'_'.$v['label_id'];
            }
        }
        return $data;
    }

    //删除权限分组标签
    public function AdminGroupLabelDel($where){
        return (new HouseAdminGroupLabelRelation())->delOne($where);
    }

}
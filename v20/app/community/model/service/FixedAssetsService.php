<?php
/**
 * 固定资产管理相关
 * @author weili
 * @date 2020/11/20
 */

namespace app\community\model\service;

use app\community\model\db\AreaStreetAssetsCategory;
use app\community\model\db\AreaStreetAssets;
use app\community\model\db\AreaStreetAssetsList;
use app\community\model\db\AreaStreetAssetsRecord;
use app\community\model\db\AreaStreetAssetsMaintain;
use file_handle\FileHandle;

class FixedAssetsService
{
    /**
     * Notes: 获取分类
     * @param $street_id
     * @return array
     * @author: weili
     * @datetime: 2020/11/20 11:00
     */
    public function getClassifyNav($street_id)
    {
        $dbAreaStreetAssetsCategory = new AreaStreetAssetsCategory();
        $dbAreaStreetAssets = new AreaStreetAssets();
        $where[] = ['street_id|community_id','=',$street_id];
        $where[] = ['cat_status','=',1];
        $field = 'cat_id,street_id,cat_name';
        $list = $dbAreaStreetAssetsCategory->getSelect($where,$field)->toArray();
        $cat_id = array_column($list,'cat_id');
        $map[] = ['status','=',1];
        $map[] = ['cat_id','in',$cat_id];
        $field_assets = 'assets_id,assets_name as title,cat_id';
        $assets_list = $dbAreaStreetAssets->getSelect($map,$field_assets)->toArray();
        $assets_arr= [];
        if($assets_list) {
            foreach ($assets_list as $val) {
                $val['isLeaf'] = true;
                $val['key'] = $val['cat_id'] . '-' . $val['assets_id'];
                $val['scopedSlots'] = ['title'=>'edit_outs'];
                $assets_arr[$val['cat_id']][] = $val;
            }
        }
        $new_arr = [];
        $arr_new = [];
        if($list) {
            foreach ($list as $key => $val) {
                $new_arr[$key] = [
                    'id' => $val['cat_id'],
                    'title' => $val['cat_name'],
                    'key' => '0' . '-' . $val['cat_id'],
                    'scopedSlots'=> [
                            'title'=> 'edit_out'
                        ]
                ];
                if (isset($assets_arr[$val['cat_id']])) {
                    $arr_new[] = [
                        'key'=>$assets_arr[$val['cat_id']][0]['key'],
                        'assets_id'=>$assets_arr[$val['cat_id']][0]['assets_id']
                    ];
                    $new_arr[$key]['children'] = $assets_arr[$val['cat_id']];
                } else {
                    $new_arr[$key]['children'] = [];
                }
            }
        }
        $data['key'] = $arr_new;
        $data['menu_list'] = $new_arr;
        return $data;
    }

    /**
     * Notes: 获取分类列表
     * @param $street_id
     * @return array
     * @author: weili
     * @datetime: 2020/11/20 14:01
     */
    public function getClassify($street_id=0,$whereArr=array())
    {
        $dbAreaStreetAssetsCategory = new AreaStreetAssetsCategory();
        if(!empty($whereArr)){
            
        }else{
            $whereArr=array();
            $whereArr[] = ['street_id','=',$street_id];
            $whereArr[] = ['cat_status','=',1];
        }
        $field = 'cat_id,street_id,cat_name';
        $list = $dbAreaStreetAssetsCategory->getSelect($whereArr,$field)->toArray();
        $data['list'] = $list;
        return $data;
    }
    /**
     * Notes:获取一级分类详情
     * @param $cat_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/23 15:37
     */
    public function getClassifyInfo($cat_id)
    {
        $dbAreaStreetAssetsCategory = new AreaStreetAssetsCategory();
        $where[] = ['cat_id','=',$cat_id];
        $info = $dbAreaStreetAssetsCategory->getFind($where);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 删除一级分类
     * @param $cat_id
     * @return AreaStreetAssetsCategory
     * @author: weili
     * @datetime: 2020/11/23 15:37
     */
    public function delClassify($cat_id)
    {
        $dbAreaStreetAssetsCategory = new AreaStreetAssetsCategory();
        $dbAreaStreetAssets = new AreaStreetAssets();

        $where[] = ['cat_id','=',$cat_id];
        $info = $dbAreaStreetAssets->getFind($where);
        if($info){
            throw new \think\Exception('分类下有资产，请先删除资产');
        }
        $res = $dbAreaStreetAssetsCategory->editFind($where,['cat_status'=>4]);
        return $res;
    }
    /**
     * Notes: 编辑、添加数据
     * @param $data
     * @param $cat_id
     * @return AreaStreetAssetsCategory|int|string
     * @author: weili
     * @datetime: 2020/11/20 11:21
     */
    public function operateClassify($data,$cat_id)
    {
        $dbAreaStreetAssetsCategory = new AreaStreetAssetsCategory();
        $map[] = ['cat_name','=',$data['cat_name']];
        $map[] = ['cat_status','=',1];
        if($data['street_id'])
            $map[] = ['street_id','=',$data['street_id']];
        else
            $map[] = ['community_id','=',$data['community_id']];
        $info = $dbAreaStreetAssetsCategory->getFind($map);
        if(!$cat_id) {
            if($info['cat_name']==$data['cat_name']){
                throw new \think\Exception('分类名称重复');
            }
            $data['add_time'] = time();
            $data['last_time'] = time();
            $res = $dbAreaStreetAssetsCategory->addFind($data);
        }else{
            $where[] = ['cat_id','=',$cat_id];
            $data['last_time'] = time();
            $res = $dbAreaStreetAssetsCategory->editFind($where,$data);
        }
        return $res;
    }

    /**
     * Notes: 获取资产详情
     * @param $assets_name
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/20 16:20
     */
    public function getAssetsInfo($assets_name,$assets_id,$street_id)
    {
        $dbAreaStreetAssets = new AreaStreetAssets();
        $where[] = ['status','=',1];
        $where[] = ['street_id','=',$street_id];
        if($assets_name)
        {
            $where[] = ['assets_name','=',$assets_name];
        }else{
            $where[] = ['assets_id','=',$assets_id];
        }
        $info = $dbAreaStreetAssets->getFind($where);
        $data['info'] = $info;
        return $data;
    }
    /**
     * Notes: 添加/编辑数据
     * @param $data
     * @param $assets_id
     * @return AreaStreetAssets|int|string
     * @author: weili
     * @datetime: 2020/11/20 14:41
     */
    public function subAssets($data,$assets_id)
    {
        $dbAreaStreetAssets = new AreaStreetAssets();
        $dbAreaStreetAssetsList = new AreaStreetAssetsList();
        if($assets_id){
            $where[] = ['assets_id','=',$assets_id];
            $data['last_time'] = time();
            $res = $dbAreaStreetAssets->editFind($where,$data);
        }else{
            $map[] = ['assets_name','=',$data['assets_name']];
            $map[] = ['cat_id','=',$data['cat_id']];
            $map[] = ['status','=',1];
            $map[] = ['street_id','=',$data['street_id']];
            $info = $dbAreaStreetAssets->getFind($map,'assets_id,assets_name');

            $num = 0;
            if($info)
            {
                $assets_where[] = ['assets_id','=',$info['assets_id']];
                $assets_where[] = ['status','=',1];
                $assetsListInfo = $dbAreaStreetAssetsList->getFind($assets_where,'id,num','id desc');
                $num = $assetsListInfo['num'];
                $total = $num+$data['num'];
                $assets_id = $info['assets_id'];

                $assets_where[] = ['assets_id','=',$info['assets_id']];
            }else{
                $total =$num+$data['num'];
                $data['add_time'] = time();
                $assets_id = $dbAreaStreetAssets->addFind($data);

                $assets_where[] = ['assets_id','=',$assets_id];
            }
            $new_arr = [];
            for ($i=($num+1);$i<=$total;$i++){
                $new_arr[]=[
                    'assets_id'=>$assets_id,
                    'num'=>$i,
                    'add_time'=>time(),
                ];
            }
            $res = $dbAreaStreetAssetsList->addAll($new_arr);//一次生成设备数据
            if($res && $info) {
                $dbAreaStreetAssets->editFind($assets_where,['num'=>$total]);
            }
        }
        return $res;
    }

    /**
     * Notes: 删除资产  （二级分类）
     * @param $assets_id
     * @return AreaStreetAssets
     * @author: weili
     * @datetime: 2020/11/23 15:43
     */
    public function delAssets($assets_id)
    {
        $dbAreaStreetAssets = new AreaStreetAssets();
        $dbAreaStreetAssetsList = new AreaStreetAssetsList();

        $where[] = ['assets_id','=',$assets_id];
        $dbAreaStreetAssetsList->editFind($where,['status'=>'-1']);
        $res = $dbAreaStreetAssets->editFind($where,['status'=>'-1']);
        return $res;
    }
    /**
     * Notes:获取资产信息列表
     * @param $assets_id
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/20 18:15
     */
    public function getAssetsList($assets_id,$page,$limit,$status=0,$where=[])
    {
        $dbAreaStreetAssetsList = new AreaStreetAssetsList();
        $dbAreaStreetAssets = new AreaStreetAssets();
        $map[]=['assets_id','=',$assets_id];
        $map[]=['status','=',1];
        $assetsInfo = $dbAreaStreetAssets->getFind($map);
        $list = [];
        $count = [];
        $time=time();
        if($assetsInfo) {
            $where[] = ['assets_id', '=', $assets_id];
            if ($status && is_array($status)) {
                $where[] = ['status', 'in', $status];
            } else {
                if ($status) {
                    $where[] = ['status', '=', $status];
                }
            }
            $count = $dbAreaStreetAssetsList->getCount($where);
            $list = $dbAreaStreetAssetsList->getSelect($where, true, 'id asc', $page, $limit);
            foreach ($list as &$val) {
                $val['add_time'] = date('Y-m-d', $val['add_time']);
                if ($val['status'] == 3) {
                    if($time > $val['rent_end_time']){ //已到期
                        $end_day = intval(($time - $val['rent_end_time']) / 86400);
                        $val['msg_day'] = '已超过归还天数' . $end_day . '天';
                    }else{
                        $end_day = intval(($val['rent_end_time'] - $time) / 86400);
                        $val['msg_day'] = '距离归还还有' . $end_day . '天';
                    }
//                    $end_day = ($val['rent_end_time'] - $val['led_time']) / 86400;
//                    $val['msg_day'] = '距离归还还有' . $end_day . '天';
                }
            }
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * Notes: 提交领取租借
     * @param $data
     * @return int
     * @throws \Exception
     * @author: weili
     * @datetime: 2020/11/21 14:15
     */
    public function subLedRent($data,$street_id)
    {
        $dbAreaStreetAssetsRecord = new AreaStreetAssetsRecord();
        $dbAreaStreetAssetsList = new AreaStreetAssetsList();
        if($data['type']==1)
        {
            $status = 2;//领取
        }else{
            $status = 3;//租借
        }
        $assets_list = [];
        $arr = [];
        foreach($data['assets'] as $key=>$val)
        {
            $arr[$key] = [
                'type'=>$data['type'],
                'name'=>$data['name'],
                'tel'=>$data['tel'],
                'assets_id'=>$val,
                'time'=>strtotime($data['time']),
                'add_time'=>time(),
                'street_id'=>$street_id,
            ];
            $assets_list[$key] = [
                'id'=>$val,
                'status'=>$status,
                'led_time'=>strtotime($data['time']),
            ];
            if(array_key_exists('rent_end_time',$data) && isset($data['rent_end_time'])){
                $arr[$key]['rent_end_time'] = strtotime($data['rent_end_time']);
                $assets_list[$key]['rent_end_time'] = strtotime($data['rent_end_time']);
            }
        }
        $res = $dbAreaStreetAssetsRecord->addAll($arr);
        $res = $dbAreaStreetAssetsList->editAll($assets_list);
        return $res;
    }
    //收回、报废
    public function subTakeBack($data,$street_id)
    {
        $dbAreaStreetAssetsRecord = new AreaStreetAssetsRecord();
        $dbAreaStreetAssetsList = new AreaStreetAssetsList();

        $assets_list = [];
        foreach($data['assets'] as $key=>$val)
        {
            $assets_list[$key] = [
                'id'=>$val,
                'status'=>$data['status']==4?1:$data['status'],
            ];
            $arr = [
                'status'=>$data['status']==4?2:3,
            ];
            if($data['status'] == 4){
                $arr['take_back_time'] = strtotime($data['take_back_time']);
            }
            if($data['status'] == 5){
                $assets_list[$key]['record'] = $data['record'];
                $arr['record'] = $data['record'];
            }
            $where = [];
            $where[] = ['street_id','=',$street_id];
            $where[] = ['assets_id','=',$val];
            $dbAreaStreetAssetsRecord->editFind($where,$arr);
        }
        $res = $dbAreaStreetAssetsList->editAll($assets_list);
        return $res;
    }
    //获取领取租借记录
    public function getRecordList($id,$street_id,$page,$limit)
    {
        $dbAreaStreetAssetsRecord = new AreaStreetAssetsRecord();
        $where[]=['assets_id','=',$id];
        $where[]=['street_id','=',$street_id];
        $count = $dbAreaStreetAssetsRecord->getCount($where);
        $list = $dbAreaStreetAssetsRecord->getList($where,true,'id desc',$page,$limit);

        foreach ($list as $key=>&$val){
            if($val['rent_end_time']) {
                $val['rent_end_time'] = date('Y-m-d', $val['rent_end_time']);
            }else{
                $val['rent_end_time'] = '--';
            }
            if($val['take_back_time']) {
                $val['take_back_time'] = date('Y-m-d', $val['take_back_time']);
            }else{
                $val['take_back_time'] = '--';
            }
            if($val['time']) {
                $val['time'] = date('Y-m-d', $val['time']);
            }else{
                $val['time'] = '--';
            }
            $type = $val['type'];
            if($val['type'] == 1){
                $val['type'] = '领取';
            }
            if($val['type'] == 2){
                $val['type'] = '租借';
            }
            if($val['status'] == 1){
                if($type == 1){
                    $val['status'] = '领取中';
                }else{
                    $val['status'] = '租借中';
                }
            }elseif ($val['status'] == 2){
                $val['status'] = '收回';
            }else{
                $val['status'] = '报废';
            }
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * Notes: 维修记录
     * @param $assets_num_id
     * @param $street_id
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/21 19:00
     */
    public function getMaintainList($assets_num_id,$street_id,$page,$limit)
    {
        $dbAreaStreetAssetsMaintain = new AreaStreetAssetsMaintain();
        $where[]=['assets_num_id','=',$assets_num_id];
        $where[]=['street_id','=',$street_id];
        $count = $dbAreaStreetAssetsMaintain->getCount($where);
        $list = $dbAreaStreetAssetsMaintain->getList($where,true,'id desc',$page,$limit);
        foreach ($list as $key=>&$val){
            $val['time'] = date('Y-m-d',$val['time']);
        }
        $data['list'] = $list;
        $data['total_limit'] = $limit;
        $data['count'] = $count;
        return $data;
    }

    /**
     * Notes: 获取维修记录详情
     * @param $id
     * @param $street_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/23 9:39
     */
    public function getMaintainInfo($id,$street_id)
    {
        $dbAreaStreetAssetsMaintain = new AreaStreetAssetsMaintain();
        $where[] = ['id','=',$id];
        $info  = $dbAreaStreetAssetsMaintain->getFind($where);
        $imgList = [];
        if (isset($info['img_path']) && $info['img_path']) {
            $img_path = unserialize($info['img_path']);
            foreach ($img_path as $key=>$val)
            {
                $imgList[] = [
                    'uid'=>$key+1,
                    'name'=>'image.jpg',
                    'status'=>'done',
                    'url'=>dispose_url($val),
                    'url_path'=>$val,
                    'img_url'=>dispose_url($val)
                ];
            }
        }
        $info['time'] = date('Y-m-d',$info['time']);
        $info['imgList'] = $imgList;
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 添加编辑资产
     * @param $street_id
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @author: weili
     * @datetime: 2020/11/23 9:56
     */
    public function subMaintain($id,$street_id,$data)
    {
        $dbAreaStreetAssetsMaintain = new AreaStreetAssetsMaintain();
        $dbAreaStreetAssetsList = new AreaStreetAssetsList();
        $map[] = ['id','=',$data['assets_num_id']];
        $assets_num_info = $dbAreaStreetAssetsList->getFind($map);
        if(!$assets_num_info){
            throw new \think\Exception('资产编号不存在');
        }
        $data['img_path'] = serialize($data['img_path']);
        $data['time'] = strtotime($data['time']);
        if($id){
            $where[] = ['id','=',$id];
            $info = $dbAreaStreetAssetsMaintain->getFind($where);
            if(!$info){
                throw new \think\Exception('维修记录不存在');
            }
            $res = $dbAreaStreetAssetsMaintain->editFind($where,$data);
        }else {
            $data['street_id'] = $street_id;
            $data['add_time'] = time();
            $res = $dbAreaStreetAssetsMaintain->addFind($data);
        }
        return $res;
    }
    /**
     * Notes: 整合数据定义树状图
     * @param $list
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int $parent_id
     * @return array
     * @author: weili
     * @datetime: 2020/11/20 11:00
     */
    public function toTree($list,$pk='cat_id',$pid='parent_id',$child='children',$parent_id=0) {
        $tree = array();
        if (is_array($list)) {
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                $parentId = $data[ $pid ];
                if ($parent_id == $parentId) {
                    $tree[$data[$pk]] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[ $parentId ];
                        $parent[$child][$data[$pk]] = &$list[$key];
                        $parent[$child] = array_values($parent[ $child ]);
                    }
                }
            }
        }
        $tree = array_values($tree);
        return $tree;
    }
    public function uploads($file)
    {
        // 验证
//        validate(['img' => [
//            'fileSize' => 1024 * 1024 * 10,   //10M
//            'fileExt' => 'jpg,png,jpeg,gif,ico',
//            'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
//        ]])->check(['img' => $file]);
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public_upload')->putFile('street', $file);
        if (strpos($savename, "\\") !== false) {
            $savename = str_replace('\\', '/', $savename);
        }
        $img_url = '/upload/' . $savename;
        $params = ['savepath'=>'/upload/' . $img_url];
        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image', $params);
        }
        return $img_url;
    }
}
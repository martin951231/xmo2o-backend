<?php
/**
 * 事项政务相关
 * @author weili
 * @date 2020/11/2
 */

namespace app\community\model\service;

use app\community\model\db\AreaStreetMatterCategory;
use app\community\model\db\AreaStreetMatter;
class MatterService
{
    /**
     * Notes: 事项政务分类列表
     * @param $area_id
     * @param $name
     * @param $page
     * @param $limit
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/11/2 14:32
     */
    public function categoryList($area_id,$name,$page,$limit)
    {
        $dbAreaStreetMatterCategory = new AreaStreetMatterCategory();
        if($name){
            $where[] = ['cat_name','like','%'.$name.'%'];
        }
        $where[] = ['cat_status','<>','-1'];
        $where[] = ['area_id','=',$area_id];
        $order = 'cat_sort asc,cat_id desc';
        $list = $dbAreaStreetMatterCategory->getLists($where,'*',$order,$page,$limit);
        foreach ($list as &$val){
            if($val['create_time']) {
                $val['create_time'] = date('Y-m-d', $val['create_time']);
            }
        }
        $data['list'] = $list;
        return $data;
    }

    /**
     * Notes: 详情
     * @param $id
     * @return array|\think\Model|null
     * @author: weili
     * @datetime: 2020/11/2 14:49
     */
    public function details($id)
    {
        $dbAreaStreetMatterCategory = new AreaStreetMatterCategory();
        $where[] = ['cat_id','=',$id];
        $info = $dbAreaStreetMatterCategory->getFind($where);
        $data['info'] = $info;
        return $data;
    }

    /**
     * Notes: 添加/编辑分类
     * @param $data
     * @param $cat_id
     * @return AreaStreetMatterCategory|int|string
     * @author: weili
     * @datetime: 2020/11/2 14:49
     */
    public function postCategory($data,$cat_id)
    {
        $dbAreaStreetMatterCategory = new AreaStreetMatterCategory();
        if($cat_id){
            $where[] = ['cat_id','=',$cat_id];
            unset($data['area_id']);
            $res = $dbAreaStreetMatterCategory->editData($where,$data);
        }else{
            $data['create_time'] = time();
            $res = $dbAreaStreetMatterCategory->addData($data);
        }
        return $res;
    }

    /**
     * Notes: 软删除分类
     * @param $id
     * @return AreaStreetMatterCategory
     * @author: weili
     * @datetime: 2020/11/2 15:07
     */
    public function del($id)
    {
        $dbAreaStreetMatterCategory = new AreaStreetMatterCategory();
        $dbAreaStreetMatter = new AreaStreetMatter();
        $where[] = ['cat_id','=',$id];
        $data['cat_status'] = '-1';
        $dbAreaStreetMatter->editData($where,['status'=>-1]);
        $res = $dbAreaStreetMatterCategory->editData($where,$data);
        return $res;
    }
    /**
     * Notes:获取事项列表
     * @param $cat_id
     * @param $area_id
     * @param $name
     * @param $page
     * @param $limit
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/2 16:28
     */
    public function getMatterList($cat_id,$area_id,$name,$page,$limit)
    {
        $dbAreaStreetMatter = new AreaStreetMatter();
        if($name){
            $where[] = ['title','like','%'.$name.'%'];
        }
        $where[] = ['cat_id','=',$cat_id];
        $where[] = ['area_id','=',$area_id];
        $where[] = ['status','<>','-1'];
        $list = $dbAreaStreetMatter->getLists($where,$field=true,$page,$limit);
        foreach ($list as &$val){
            if($val['add_time']){
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        $data['list'] = $list;
        return $data;
    }
    /**
     * Notes:获取事项详情
     * @param $matter_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/11/2 16:28
     */
    public function getMatterInfo($matter_id)
    {
        $dbAreaStreetMatter = new AreaStreetMatter();
        $where[] = ['matter_id','=',$matter_id];
        $info = $dbAreaStreetMatter->getOne($where,true);
        $data['info'] = $info;
        return $data;
    }
    /**
     * Notes:添加、编辑
     * @param $data
     * @param $matter_id
     * @return AreaStreetMatter|int|string
     * @author: weili
     * @datetime: 2020/11/2 16:28
     */
    public function subMatter($data,$matter_id)
    {
        $dbAreaStreetMatter = new AreaStreetMatter();
        if($matter_id){
            $where[] = ['matter_id','=',$matter_id];
            $res = $dbAreaStreetMatter->editData($where,$data);
        }else{
            $data['add_time'] = time();
            $res = $dbAreaStreetMatter->addData($data);
        }
        return $res;
    }
    /**
     * Notes:删除
     * @param $id
     * @return AreaStreetMatter
     * @author: weili
     * @datetime: 2020/11/2 16:28
     */
    public function delMatter($id)
    {
        $dbAreaStreetMatter = new AreaStreetMatter();
        $where[]= ['matter_id','=',$id];
        $res = $dbAreaStreetMatter->editData($where,['status'=>'-1']);
        return $res;
    }
}
<?php
/**
 * @author : liukezhu
 * @date : 2022/8/3
 */

namespace app\community\model\db;

use think\Model;
class HousePublicRentalApplyRecord extends Model
{

    public function getList($where,$field='*',$order='r.id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('r')
            ->leftJoin('user u','u.uid = r.uid')
            ->leftJoin('plugin_material_diy_template t','t.template_id = r.template_id')
            ->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCount($where) {
        $sql= $this->alias('r')
            ->leftJoin('user u','u.uid = r.uid')
            ->leftJoin('plugin_material_diy_template t','t.template_id = r.template_id')
            ->where($where)->count();
        return $sql;
    }

    public function getOne($where,$field=true,$order='id desc'){
        return $this->field($field)->where($where)->order($order)->find();
    }

    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    public function getRecordUser($where,$field=true) {
        $sql= $this->alias('r')
            ->leftJoin('user u','u.uid = r.uid')
            ->leftJoin('house_village v','v.village_id = r.village_id')
            ->where($where)->field($field)->find();
        return $sql;
    }

    public function getRecordBindUser($where,$field=true) {
        $sql= $this->alias('r')
            ->leftJoin('user u','u.uid = r.uid')
            ->where($where)->field($field)->find();
        return $sql;
    }


    public function getRecordList($where,$field='*',$order='id desc',$page=0,$limit=20,$whereor=[])
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($whereor && is_array($whereor)){
            $sql->where(function($query) use ($whereor){$query->whereOr($whereor);});
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getRecordCount($where,$whereor=[])
    {
        $sql = $this->where($where);
        if($whereor && is_array($whereor)){
            $sql->where(function($query) use ($whereor){$query->whereOr($whereor);});
        }
        $list = $sql->count();
        return $list;
    }


    public function getRecordArrangingList($where,$field='*',$order='r.id desc',$page=0,$limit=20,$whereor=[])
    {
        $sql = $this->alias('r')->leftJoin('house_public_rental_arranging_record a','a.id = r.arranging_record_id')->where($where)->field($field)->order($order);
        if($whereor && is_array($whereor)){
            $sql->where(function($query) use ($whereor){$query->whereOr($whereor);});
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getRecordArrangingCount($where,$whereor=[])
    {
        $sql = $this->alias('r')->leftJoin('house_public_rental_arranging_record a','a.id = r.arranging_record_id')->leftJoin('user u','u.uid = r.uid')->where($where);
        if($whereor && is_array($whereor)){
            $sql->where(function($query) use ($whereor){$query->whereOr($whereor);});
        }
        $list = $sql->count();
        return $list;
    }


    public function getRecordArrangingFind($where,$field=true) {
        $sql=  $this->alias('r')->leftJoin('house_public_rental_arranging_record a','a.id = r.arranging_record_id')
            ->where($where)->field($field)->find();
        return $sql;
    }



    public function getRecordBindUserList($where,$field='*',$order='r.id desc',$page=0,$limit=20,$whereor=[])
    {
        $sql = $this->alias('r')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = r.pigcms_id')
            ->leftJoin('house_public_rental_arranging_record a','a.id = r.arranging_record_id')
            ->where($where)->field($field)->order($order);
        if($whereor && is_array($whereor)){
            $sql->where(function($query) use ($whereor){$query->whereOr($whereor);});
        }
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getRecordBindUserCount($where,$whereor=[])
    {
        $sql = $this->alias('r')
            ->leftJoin('house_village_user_bind b','b.pigcms_id = r.pigcms_id')
            ->leftJoin('house_public_rental_arranging_record a','a.id = r.arranging_record_id')
            ->where($where);
        if($whereor && is_array($whereor)){
            $sql->where(function($query) use ($whereor){$query->whereOr($whereor);});
        }
        $list = $sql->count();
        return $list;
    }


}
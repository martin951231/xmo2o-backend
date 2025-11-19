<?php
/**
 * Created by PhpStorm.
 * Author: weili
 * Date Time: 2020/7/8 10:51
 */

namespace app\community\model\service;
use app\community\model\db\PropertyAdmin;
use app\community\model\db\PropertyAdminAuth;

class PropertyAdminService
{
    /**
     * 获取信息
     * @author: weili
     * @datetime:2020/7/8 10:53
     * @param array $where 查询条件
     * @param bool|string $field 查询字段
     * @return array|null|\think\Model
     */
    public function getFind($where,$field=true,$orderby='status asc,id desc')
    {
        // 初始化 数据层
        $propertyAdminDb = new PropertyAdmin();
        $info = $propertyAdminDb->get_one($where,$field,$orderby);
        if (!$info || $info->isEmpty()) {
            $info = [];
        }
        return $info;
    }
    /**
     * 编辑数据
     * @author weili
     * @datetime 2020/7/8 11:16
     * @param array $where 查询条件
     * @param array $data 要修改的数据
     * @return integer
     **/
    public function editData($where,$data)
    {
        $propertyAdminDb = new PropertyAdmin();
        $res = $propertyAdminDb->save_one($where,$data);
        return $res;
    }

    public function getOnePropertyAdminAuth($where,$field=true)
    {
        $propertyAdminAuthDb = new PropertyAdminAuth();
        $resObj = $propertyAdminAuthDb->getOne($where,$field);
        $propertyAdminAuth=array();
        if($resObj && !$resObj->isEmpty() ){
            $propertyAdminAuth=$resObj->toArray();
        }
        return $propertyAdminAuth;
    }
    public function checkPermissionMenu($pid='',$adminUser=array()){
        //物业权限
        $property_id=isset($adminUser['property_id']) ? $adminUser['property_id']:0;
        $admin_id=isset($adminUser['id']) ? $adminUser['id']:0;
        $is_power=1;
        $adminAuthWhere=array();
        $adminAuthWhere[]=array('admin_id','=',$admin_id);
        $adminAuthWhere[]=array('property_id','=',$property_id);
        $adminAuthWhere[]=array('village_id','=',0);
        $propertyAdminAuth= $this->getOnePropertyAdminAuth($adminAuthWhere);
        $property_menus=array();
        if($propertyAdminAuth && !empty($propertyAdminAuth['menus'])){
            $property_menus=explode(',',$propertyAdminAuth['menus']);
        }
        if($pid && !empty($property_menus) && !in_array($pid,$property_menus)){
            $is_power=0;
        }
        return  $is_power;
    }
}

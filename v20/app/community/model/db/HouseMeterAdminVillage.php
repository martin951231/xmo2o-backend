<?php
/**
 * 管理员绑定小区
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/10 11:23
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMeterAdminVillage extends Model{

    /**
     * 获取单个数据信息
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param int $village_id 社区id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($village_id,$field =true){
        $info = $this->field($field)->where(array('village_id'=>$village_id))->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取已绑定小区列表
     * @author: zhubaodi
     * @date_time: 2021/4/9 13:23
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=1) {
        $list = $this->alias('b')
            ->Join('house_village a', 'b.village_id=a.village_id')
            ->where($where)
            ->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
    /**
     * 添加数据
     * @author zhubaodi
     * @datetime 2021/4/9 15:43
     * @param array $data
     * @return integer
    **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }
    /**
     * 获取单个数据
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param array $where
     * @param bool $field
     * @return array
    **/
    public function getInfo($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }


    /**
     * 移除已绑定小区
     * @author zhubaodi
     * @datetime 2021/4/9 16:13
     * @param array $where
     * @return integer
     **/
    public function deleteInfo($where)
    {
        $info = $this->where($where)->delete();
        return $info;
    }



    /**
     * 获取已绑定小区列表
     * @author: zhubaodi
     * @date_time: 2021/4/9 13:23
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return array|null|Model
     */
    public function getLists($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=1) {
        $list = $this->where($where)
            ->field($field);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
}

<?php
/**
 * 收费标准
 * Created by PhpStorm.
 * User: zhubaodi
 * Date Time: 2021/4/9 11:23
 */
namespace app\community\model\db;

use think\Model;
use think\facade\Db;

class HouseMeterElectricPrice extends Model{

    /**
     * 获取单个数据信息
     * @author: zhubaodi
     * @date_time: 2021/4/9 11:23
     * @param int $village_id 社区id
     * @param bool $field 需要查询的字段 默认查询所有
     * @return array|null|Model
     */
    public function getOne($id,$field =true){
        $info = $this->field($field)->where(array('id'=>$id))->find();
        return $info;
    }

    /**
     * 修改信息
     * @author: zhubaodi
     * @date_time: 2021/4/9 14:39
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 获取列表
     * @author: zhubaodi
     * @date_time: 2021/4/9 15:23
     * @param array $where 查询条件
     * @param bool $field 需要查询的字段 默认查询所有
     * @param int $page
     * @param int $limit
     * @param string $order
     * @param int $type
     * @return mixed|null|Model
     */
    public function getList($where,$field=true,$page=0,$limit=20,$order='id DESC',$type=0) {
        $list = $this->field($field)->where($where);
        if($page)
            $list = $list->order($order)->page($page,$limit)->select();
        else
            $list = $list->select();
        return $list;
    }
    /**
     * 添加数据
     * @author zhubaodi
     * @datetime 2021/4/9 10:43
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
     * @author zhubaodi
     * @datetime 2021/4/9 13:13
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
     * 获取单个数据
     * @author zhubaodi
     * @datetime 2021/4/10 16:13
     * @param array $where
     * @param bool $field
     * @return array
     **/
    public function getPriceInfo($where,$field=true)
    {
        $info = $this->alias('a')
            ->leftjoin('house_village_user_vacancy b',['b.pigcms_id=a.vacancy_id ',' b.house_type=a.house_type'])
            ->where($where)->field($field)->find();
        return $info;
    }

}

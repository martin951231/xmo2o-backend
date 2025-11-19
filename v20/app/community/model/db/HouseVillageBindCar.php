<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/11/4 14:34
 */
namespace app\community\model\db;
use think\Model;
use think\Db;
class HouseVillageBindCar extends Model {
    use \app\common\model\db\db_trait\CommonFunc;

    public function user_bind_car_list($bind_id, $where=[], $field=true,$page=0,$limit=0) {
        $where['a.user_id'] = $bind_id;
        $list = $this->alias('a')
            ->leftJoin('house_village_parking_car b', 'b.car_id=a.car_id')
            ->leftJoin('house_village_parking_position c', 'c.position_id=b.car_position_id')
            ->where($where)
            ->field($field);
        if($page){
            $list = $list->page($page,$limit)->select();
        }else{
            $list = $list->select();
        }
        return $list;
    }

    //todo 查询用户下车辆数据
    public function getUserCar($where,$column){
        $list = $this->alias('b')
            ->leftJoin('house_village_parking_car c',' c.car_id = b.car_id')
            ->where($where)->column($column);
        return $list;
    }
    
    /**
     * 获取车辆数量
     * @param $where
     * @return int
     */
    public function getVillageCarCount($where)
    {
        $num = $this->where($where)->count();
        return $num;
    }

    /**
     * 根据条件获取列表
     * @author zhubaodi
     * @date_time 2021/11/10
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field,$page=0,$limit=15,$order='b.car_id DESC',$whereRaw='')
    {
        if ($page){
            $sql = $this->alias('a')
                 ->rightJoin('house_village_parking_car b', 'b.car_id=a.car_id')
                 ->where($where);
            if($whereRaw){
                $sql=$sql->whereRaw($whereRaw);
            }
            $data = $sql->field($field)->page($page,$limit)->order($order)->select();
        }else{
            $sql = $this->alias('a')
                ->rightJoin('house_village_parking_car b', 'b.car_id=a.car_id')
                ->where($where);
            if($whereRaw){
                $sql=$sql->whereRaw($whereRaw);
            }
            $data = $sql->field($field)->order($order)->select();
        }
        return $data;
    }


    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: zhubaodi
     * @datetime: 2021/11/3 13:37
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->order('id desc')->find();
        return $info;
    }


    /**
     * 修改信息
     * @author: zhubaodi
     * @datetime: 2021/11/3 13:37
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * 删除中心点
     * @author: zhubaodi
     * @datetime: 2021/11/3 13:37
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * 添加车辆
     * @author lijie
     * @date_time 2020/07/17 14:15
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data,true);
        return $res;
    }

    /**
     * 查询车辆关联的用户
     * @author: liukezhu
     * @date : 2022/1/15
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getUserBind($where,$field=true) {
        $list = $this->alias('bc')
            ->leftJoin('house_village_parking_car c', 'c.car_id = bc.car_id')
            ->leftJoin('house_village_user_bind b', 'b.pigcms_id = bc.user_id')
            ->leftJoin('house_village_parking_position p', 'p.position_id = c.car_position_id')
            ->where($where)
            ->field($field)->find();
        return $list;
    }

    public function getOneCarBindUserBind($where,$field='*') {
        $oneObj = $this->alias('bc')
            ->leftJoin('house_village_user_bind b', 'b.pigcms_id = bc.user_id')
            ->where($where)
            ->field($field)->find();
        return $oneObj;
    }
    
    public function getUserBindCar($where,$field=true) {
        $list = $this->alias('bc')
            ->leftJoin('house_village_parking_car c', 'c.car_id = bc.car_id')
            ->leftJoin('house_village_user_bind b', 'b.pigcms_id = bc.user_id')
            ->where($where)
            ->field($field)->select();
        return $list;
    }

    public function getColumn($where,$field, $key = ''){
        $info = $this->where($where)->column($field, $key);
        return $info;
    }

    public function getUserBindList($where=[], $field=true,$order='a.id DESC',$page=0,$limit=0) {
        $list = $this->alias('a')
            ->leftJoin('house_village_user_bind ub', 'ub.pigcms_id = a.user_id')
            ->leftJoin('house_village_parking_car c', 'c.car_id=a.car_id')
            ->leftJoin('house_village_parking_position p', 'p.position_id=c.car_position_id')
            ->leftJoin('house_village_parking_garage g', 'g.garage_id=p.garage_id')
            ->where($where)
            ->order($order)
            ->field($field);
        if($page){
            $list = $list->page($page,$limit)->select();
        }else{
            $list = $list->select();
        }
        return $list;
    }

    public function getUserBindCount($where){
        $list = $this->alias('a')
            ->leftJoin('house_village_user_bind ub', 'ub.pigcms_id = a.user_id')
            ->leftJoin('house_village_parking_car c', 'c.car_id=a.car_id')
            ->leftJoin('house_village_parking_position p', 'p.position_id=c.car_position_id')
            ->leftJoin('house_village_parking_garage g', 'g.garage_id=p.garage_id')
            ->where($where)->count();
        return $list;
    }
}
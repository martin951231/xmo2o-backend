<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageGridRange extends Model
{
    /**
     * 添加网格多边形
     * @author lijie
     * @date_time 2020/12/22
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 删除网格多边形
     * @author lijie
     * @date_time 2020/12/22
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
     * 查找网格
     * @author lijie
     * @date_time 2020/12/22
     * @param $where
     * @param $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true)
    {
        $data = $this->alias('r')
            ->leftJoin('house_village_grid_center c','r.type = c.type and r.bind_id = c.bind_id')
            ->leftJoin('house_village_grid_member m','m.id = r.grid_member_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    /**
     * 更新
     * @author lijie
     * @date_time 2020/01/26
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $data = $this->where($where)->save($data);
        return $data;
    }

    public function getLists($where,$field=true,$order='create_time ASC')
    {
        $data = $this->where($where)->field($field)->order($order)->select();
        return $data;
    }

    /**
     * 获取最近的层级
     * @author lijie
     * @date_time 2020/12/25
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getLastOne($where,$field=true,$order='b.zoom ASC')
    {
        $data = $this->alias('a')
            ->leftJoin('house_village_grid_center b','a.bind_id = b.bind_id and a.type = b.type')
            ->field($field)
            ->where($where)
            ->order($order)
            ->select();
        return $data;
    }

    /**
     * 获取网格
     * @author lijie
     * @date_time 2020/12/31
     * @param $where
     * @param bool $field
     * @param string $order
     * @return mixed
     */
    public function getList($where,$field=true,$order='b.zoom DESC')
    {
        $data = $this->alias('a')
            ->leftJoin('house_village_grid_center b','a.bind_id = b.bind_id and a.type = b.type')
            ->where($where)
            ->order($order)
            ->field($field)
            ->select();
        return $data;
    }

    /**
     * 网格员信息
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getGridMember($where,$field=true)
    {
        $data = $this->alias('a')
            ->leftJoin('house_village_grid_member b','b.id = a.grid_member_id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }


    public function getWhereOrList($whereor,$field=true)
    {
        $sql = $this->where(function($query) use ($whereor){$query->whereOr($whereor);});
        $list = $sql->field($field)->select();
        return $list;
    }

}
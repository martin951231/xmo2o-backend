<?php


namespace app\community\model\db;
use think\Model;

class HouseVillageDeposit extends Model
{
    /**
     * 根据条件获取押金列表
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$page=1,$limit=15)
    {
        $lists = $this->alias('hvd')
            ->Leftjoin('house_village_user_bind hvub','hvub.pigcms_id = hvd.pigcms_id')
            ->where($where)
            ->field($field)
            ->page($page,$limit)
            ->select();
        return $lists;
    }

    /**
     * 获取押金详细信息
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $data = $this->alias('hvd')
            ->leftJoin('house_village_user_bind hvub','hvub.pigcms_id = hvd.pigcms_id')
            ->leftJoin('house_village_pay_type pt','hvd.pay_type = pt.id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }

    /**
     * 修改押金管理
     * @author lijie
     * @date_time 2020/07/14
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 添加押金管理
     * @author lijie
     * @date_time 2020/07/14
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }


    /**
     *押金列表
     * @author: liukezhu
     * @date : 2021/11/10
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $page_size
     * @return mixed
     */
    public function getList($where,$field ='*',$order='deposit_id DESC',$page=0,$page_size=10) {
        $db_list = $this->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * 押金统计
     * @author: liukezhu
     * @date : 2021/11/10
     * @param $where
     * @return mixed
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     *押金详情
     * @author: liukezhu
     * @date : 2021/11/10
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOnes($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
}
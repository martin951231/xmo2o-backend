<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageBindPosition extends Model
{
    /**
     * 添加用户绑定车位
     * @author lijie
     * @date_time 2020/02/02
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function getList($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }

    /**
     * 查询用户绑定车位
     * @author lijie
     * @date_time 2020/02/02
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$order='bind_id desc')
    {
        $data = $this->where($where)->order($order)->find();
        return $data;
    }
    
    public function getRoomUserBindByPosition($where=array(),$field='*',$order='bp.bind_id desc'){

        $data = $this->alias('bp')->leftJoin('house_village_user_bind ub','bp.user_id = ub.pigcms_id')->field($field)->where($where)->order($order)->find();
        return $data;
    }
    
    public function getUserBindPositionList($where=array(),$field='*',$order='bp.bind_id desc'){

        $data = $this->alias('bp')->leftJoin('house_village_parking_position pp','bp.position_id = pp.position_id')->field($field)->where($where)->order($order)->select();
        return $data;
    }
    /**
     * 业主解绑车位
     * @author lijie
     * @date_time 2020/02/02
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delOne($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }



    //todo 查询用户下车位数据
    public function getUserPosition($where,$column){
        $where2='';
        if(isset($where['_string']) && !empty($where['_string'])){
            $where2=$where['_string'];
            unset($where['_string']);
        }
        $list = $this->alias('b')
            ->leftJoin('house_village_parking_position p',' p.position_id = b.position_id')
            ->where($where)->where($where2)->column($column);
        return $list;
    }

    /**
     * 查询用户下车位数据
     * @author:zhubaodi
     * @date_time: 2022/6/8 18:35
     */
    public function getUserPositionList($where,$field,$page='',$page_size=10,$order='p.position_id desc'){

        $sql = $this->alias('b')
            ->leftJoin('house_village_parking_position p',' p.position_id = b.position_id')
            ->leftJoin('house_village_parking_garage g',' g.garage_id = p.garage_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($page) {
            $sql->page($page, $page_size);
        }
        $list = $sql->select();
        return $list;
    }

    /**
     * 查询用户下车位数据
     * @author:zhubaodi
     * @date_time: 2022/6/8 18:35
     */
    public function getUserPositionCount($where){

        $list = $this->alias('b')
            ->leftJoin('house_village_parking_position p',' p.position_id = b.position_id')
            ->leftJoin('house_village_parking_garage g',' g.garage_id = p.garage_id')
            ->where($where)->count();
        return $list;
    }

    /**
     * 编辑车位
     * @author lijie
     * @date_time 2020/07/16 16:11
     * @param $where
     * @param $data
     * @return mixed
     */
    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    /**
     * 获取相关数量
     * @author:zhubaodi
     * @date_time: 2022/6/7 15:28
     * @param array $where 查询条件
     * @return array|null|Model
     */
    public function getCount($where){
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * 得到某个列的数组
     * @access public
     * @param string $field 字段名 多个字段用逗号分隔
     * @param string $key   索引
     * @return array
     */
    public function getColumn($where,$field, $key = '')
    {
        $data = $this->where($where)->column($field,$key);
        return $data;
    }
    
    public function getUserPositionPaymentList($where,$field='pa.*,ps.*,psb.*,p.position_num')
    {
        $list = $this->alias('b')
            ->join('house_village_parking_position p','p.position_id=b.position_id')
//            ->join('house_village_parking_garage g','g.garage_id=p.garage_id')
            ->join('house_village_payment_standard_bind psb','psb.position_id=b.position_id')
            ->join('house_village_payment_standard ps','ps.standard_id=psb.standard_id')
            ->join('house_village_payment pa','pa.payment_id=psb.payment_id')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $list;
    }
}
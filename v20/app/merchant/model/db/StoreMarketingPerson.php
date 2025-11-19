<?php


namespace app\merchant\model\db;


use think\Model;

class StoreMarketingPerson extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param array $where
     * @param bool $field
     * @param bool $order
     * 连表查询列表
     */
    public function getSome($where = [], $field = true,$order=true){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('sp')
            ->join($prefix.'store_marketing_person_store s','s.person_id = sp.id')
            ->where($where);
        $list['count']=$result->count();
        $list['list']=$result
            ->field($field)
            ->order($order)
            ->select()
            ->toArray();
        return $list;
    }

    /**
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *
     */
    public function getOne($where = [], $field = true) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('sp')
            ->join($prefix.'store_marketing_person_store s','s.person_id = sp.id')
            ->where($where)
            ->field($field)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 查询单表数据
     */
    public function getOneMsg($where = [], $field = true) {
        $result = $this
            ->where($where)
            ->field($field)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @return bool
     * @throws \Exception
     * 硬删除
     */
    public function delData($where){
        $ret=$this->where($where)->delete();
        return $ret;
    }
}
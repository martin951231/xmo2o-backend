<?php


namespace app\merchant\model\db;


use think\Model;

class JobPerson extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getJobPersonList($where,$field=true,$order=true){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'merchant_position'.' m','s.job_id = m.id')
            ->leftJoin($prefix.'user'.' u','s.uid = u.uid')
            ->where($where)
            ->order($order)
            ->select()
           ->toArray();
        return $result;
    }

    /**
     * @param $where
     * @param bool $field
     * @param bool $order
     * @return mixed
     * 查找是不是技师
     */
    public function getJobPerson($where,$field=true){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'user'.' m','s.uid = m.uid')
            ->join($prefix.'merchant_store'.' st','st.store_id = s.store_id')
            ->join($prefix.'merchant_position'.' mp','mp.id = s.job_id')
            ->where($where)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }
}
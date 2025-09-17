<?php
/**
 * 公共的DB取数据的方法
 * 直接use到你们的db类中
 */
namespace app\common\model\db\db_trait;

trait CommonFunc{

    public function dbPrefix() {
        return config('database.connections.mysql.prefix');
    }

    public function getOne($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
//    	$result = $this->field($field)->where($where)->order($order)->limit($page,$limit)->select();
//    	return $result;

        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }

    public function getSomeAndPage($where = [], $field = true,$order=true,$page=0,$pageSize=0){

        if($pageSize){
            $limit = [
                'page' => $page ?? 1,
                'list_rows' => $pageSize
            ];
            $result =  $this->where($where)->field($field)->order($order)->paginate($limit);
        }else{
             $result =  $this->where($where)->field($field)->order($order)->select();
        }
        
        return $result;
    }

    public function getProSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $sql = $this->field($field)->where($where)->order($order);

        if($limit)
        {
            $sql->limit(($page-1)*$limit,$limit);
        }
        $result = $sql->select();
        return $result;
    }

    public function getCount($where = []){
        $result = $this->where($where)->count();
        return $result;
    }

    public function updateThis($where, $data){
    	$result = $this->where($where)->update($data);
    	return $result;
    }

    public function addAll($data){
        $result = $this->insertAll($data);
        return $result;
    }

    public function add($data){
        $result = $this->insertGetId($data);
        return $result;
    }

    /**
     * 自增
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setInc($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->inc($field, $num)->update();
        return $result;
    }

    /**
     * 自减
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setDec($where = [], $field = '', $num = 1) {
        $result = $this->where($where)->dec($field, $num)->update();
        return $result;
    }
}
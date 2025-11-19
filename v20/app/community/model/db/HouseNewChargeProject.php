<?php
/**
 * @author : liukezhu
 * @date : 2021/6/10
 */
namespace app\community\model\db;

use think\facade\Cache;
use think\Model;
use think\facade\Db;

class HouseNewChargeProject extends Model{

	public $baseCacheKey = 'village:HouseNewChargeProject:';
	public $cacheTag = 'HouseNewChargeProject';

	/**
	 * TODO  放这里其实不太合适，应该是要根据当前的ID 
	 */
	public function deleteCacheTag()
	{
		Cache::tag($this->cacheTag)->clear();
	}
	
    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($where,$field='*',$order='p.id desc',$page=0,$limit=20)
    {
		/*
		$cachKey = $this->baseCacheKey . md5(\json_encode($where).\json_encode($field).\json_encode($order).$page.$limit);
	    $list = Cache::get($cachKey);
		if (!empty($list)){
			return  $list;
		}
		*/
        $sql = $this->alias('p')
            ->leftJoin('house_new_charge_number c','c.id = p.subject_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
		//Cache::tag($this->cacheTag)->set($cachKey,$list,7200-mt_rand(10,30));
        return $list;
    }
    
    public function getLists($where,$field='*',$order='id desc',$page=0,$limit=20)
    {
	    /*
		$cachKey = $this->baseCacheKey . md5(\json_encode($where).\json_encode($field).\json_encode($order).$page.$limit);
	    $list = Cache::get($cachKey);
	    if (!empty($list)){
		    return  $list;
	    }
		*/
        $sql = $this
            ->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
	    //Cache::tag($this->cacheTag)->set($cachKey,$list,7200-mt_rand(10,30));
        return $list;
    }

    public function getProjectCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }
    /**
     * 查询总数
     * @author: liukezhu
     * @date : 2021/5/11
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $count =$this->alias('p')->leftJoin('house_new_charge_number c','c.id = p.subject_id')->where($where)->count();
        return $count;
    }


    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     */
    public function addFind($data)
    {
		$this->deleteCacheTag();
        $id =$this->insertGetId($data);
        return $id;
    }

    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return WorkMsgAuditInfo
     */
    public function editFind($where,$data){
		$this->deleteCacheTag();
        $res = $this->where($where)->update($data);
        return $res;
    }


    public function getFind($where,$field=true)
    {
	    /*
		$cachKey = $this->baseCacheKey . md5(\json_encode($where).\json_encode($field));
	    $data = Cache::get($cachKey);
	    if (!empty($data)){
		    return  $data;
	    }
		*/
        $data = $this->alias('p')
            ->leftJoin('house_new_charge_number c','c.id = p.subject_id')
            ->leftJoin('house_new_charge_rule r','r.charge_project_id = p.id')
            ->where($where)
            ->field($field)->find();
	    //Cache::tag($this->cacheTag)->set($cachKey,$data,7200-mt_rand(10,30));
        return $data;
    }
    
    public function getOne($where,$field='*',$order='id DESC'){
        $list = $this->where($where)->field($field)->order($order)->find();
        return $list;
    }
    
    public function getColumn($where,$column, $key = '')
    {
	    /*
		$cachKey = $this->baseCacheKey . md5(\json_encode($where).\json_encode($column).\json_encode($key));
	    $data = Cache::get($cachKey);
	    if (!empty($data)){
		    return  $data;
	    }
		*/
        $data = $this->where($where)->column($column,$key);
	    //Cache::tag($this->cacheTag)->set($cachKey,$data,7200-mt_rand(10,30));
        return $data;
    }


    public function getProjectColumn($where,$column)
    {
	    /*
		$cachKey = $this->baseCacheKey . md5(\json_encode($where).\json_encode($column));
	    $data = Cache::get($cachKey);
	    if (!empty($data)){
		    return  $data;
	    }
		*/
        $data = $this->alias('p')
            ->leftJoin('house_new_charge_number c','c.id = p.subject_id')
            ->where($where)
            ->column($column);
	   // Cache::tag($this->cacheTag)->set($cachKey,$data,7200-mt_rand(10,30));
        return $data;
    }
    public function delOne($where){
		$this->deleteCacheTag();
        return $this->where($where)->delete();
    }


    /**
     * 查询收费项目以及对应科目
     */
    public function getProjectListByChargeType($where,$field,$order='p.id desc')
    {

        $result = $this->alias('p')
            ->join('house_new_charge_number n','n.id=p.subject_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();

        return $result;
    }

    public function getRuleList($where,$field=true)
    {

        $data = $this->alias('p')
            ->leftJoin('house_new_charge_number c','c.id = p.subject_id')
            ->leftJoin('house_new_charge_rule r','r.charge_project_id = p.id')
            ->where($where)
            ->field($field)->select();
        return $data;
    }
}
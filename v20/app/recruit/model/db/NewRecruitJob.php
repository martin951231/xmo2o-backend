<?php


namespace app\recruit\model\db;


use think\Model;

class NewRecruitJob extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getJobList($where, $keyword, $page, $pageSize){
    	if(!empty($keyword)){
    		$map1 = [
    			['job_name', 'like', "%".$keyword."%"]
    		];
    		$map2 = [
    			['desc', 'like', "%".$keyword."%"]
    		];
    		$sql = $this->field(true)->whereOr([$map1, $map2])->where($where);
    	}
    	else{
	    	$sql = $this->field(true)->where($where);
	    }
        if($pageSize)
        {
            $sql->limit($page,$pageSize);
        }
        $result = $sql->select();
        return $result;
    }

    public function getJobByName($where, $page, $pageSize){
        $sql = $this->field(true)->where($where);
        if($pageSize)
        {
            $sql->limit($page,$pageSize);
        }
        $result = $sql->select();
        return $result;
    }

    public function getJobCount($where, $keyword){
    	if(!empty($keyword)){
    		$map1 = [
    			['job_name', 'like', "%".$keyword."%"]
    		];
    		$map2 = [
    			['desc', 'like', "%".$keyword."%"]
    		];
    		$sql = $this->field(true)->where($where)->whereOr([$map1, $map2]);
    	}
    	else{
	    	$sql = $this->field(true)->where($where);
	    }
        $result = $sql->count();
        return $result;
    }

    public function selectNotRepeat($where, $field, $limit){
        $result = $this->field($field)->where($where)->distinct()->limit($limit)->select();
        return $result;
    }

    /**
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return mixed推荐相似职位
     */
    public function getRecJobList($where,$field,$order,$page,$pageSize){
        $result = $this->alias('s')
            ->leftJoin('new_recruit_resume_send at', 'at.position_id = s.job_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $result;
    }

    public function searchJobList($where, $field, $order, $limit){
        $sql = "SELECT ".$field." from ".config('database.connections.mysql.prefix')."new_recruit_job as j left join ".config('database.connections.mysql.prefix')."new_recruit_company as c on c.mer_id=j.mer_id WHERE ".$where." ".$order." ".$limit;
        $data = $this->query($sql);
        return $data;
    }

    public function searchJobList1($where, $field, $order, $limit){
        $sql = "SELECT ".$field." from ".config('database.connections.mysql.prefix')."new_recruit_job as j left join ".config('database.connections.mysql.prefix')."new_recruit_company as c on c.mer_id=j.mer_id left join ".config('database.connections.mysql.prefix')."merchant as m on j.mer_id=m.mer_id WHERE ".$where." ".$order." ".$limit;
        $data = $this->query($sql);
        return $data;
    }

    public function searchJobCount($where){
        $sql = "SELECT COUNT(*) as count from ".config('database.connections.mysql.prefix')."new_recruit_job as j left join ".config('database.connections.mysql.prefix')."new_recruit_company as c on c.mer_id=j.mer_id WHERE ".$where;
        $count = $this->query($sql);
        return $count;
    }

    public function searchJobCount1($where){
        $sql = "SELECT COUNT(*) as count from ".config('database.connections.mysql.prefix')."new_recruit_job as j left join ".config('database.connections.mysql.prefix')."new_recruit_company as c on c.mer_id=j.mer_id  left join ".config('database.connections.mysql.prefix')."merchant as m on j.mer_id=m.mer_id WHERE ".$where;
        $count = $this->query($sql);
        return $count;
    }

    /**
     * 职位首页
     */
    public function jopHomeList($where,$field,$order,$page,$pageSize)
    {
        $result = $this->alias('s')
            ->leftJoin('new_recruit_hr h', 's.author = h.id')
            ->where($where)
            ->field($field);
        $list['pageSize']=$pageSize;
        $list['count']= $result->count();
        $list['list']= $result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $list;
    }

    /**
     * 职位首页
     */
    public function jopHomeList1($where,$field,$order,$page,$pageSize)
    {
        $result = $this->alias('s')
            ->leftJoin('new_recruit_hr h', 's.author = h.uid')
            ->where($where)
            ->field($field);
        $list['pageSize']=$pageSize;
        $list['count']= $result->count();
        $list['list']= $result->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $list;
    }

    /**
     * 职位首页
     */
    public function jopHomeListCount($where)
    {
        $result = $this->alias('s')
            ->leftJoin('new_recruit_hr h', 's.author = h.id')
            ->where($where);
        $list['count']= $result->count();
        return $list['count'];
    }

    /**
     * 获取字段值
     * @param string $field
     * @param array $where
     * @return mixed
     */
    public function getColVal($field, $where = []) {
        $result = $this->where($where)->value($field);
        return $result;
    }

}
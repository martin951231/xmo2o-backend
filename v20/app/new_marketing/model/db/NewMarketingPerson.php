<?php
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPerson extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getRegionalAgency($where,$field){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'new_marketing_person_agency'.' r','r.person_id = s.id')
            ->join($prefix.'user'.' u','s.uid = u.uid')
            ->where($where)
            ->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    public function getRegionalAgencyList($where,$field,$order=[]){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'new_marketing_person_agency'.' r','r.person_id = s.id')
            ->join($prefix.'user'.' u','s.uid = u.uid')
            ->where($where);
        $assign['list']=$result->order($order)->select()
            ->toArray();
        $assign['count']=$result->count();

        return $assign;
    }

	/**
	 * @param array $where
	 * @param string $field
	 * 获取经理列表
	 * 连表团队表，查看是否有团队，有团队的看是否有区域代理
	 */
	public function getManagerList($where = [], $field = 's.*',$pageSize=10)
	{
		$prefix = config('database.connections.mysql.prefix');
		$result = $this ->alias('p')
			->where($where)
			->field($field)
			->join($prefix.'new_marketing_person_manager'.' s','s.person_id = p.id','LEFT')
			->join($prefix.'new_marketing_team'.' nmt','s.team_id = nmt.id','LEFT');

		$assign['list']=$result->paginate($pageSize);

		$assign['count']=$result->count();

		return $assign;
	}

	public function findNameById($id)
	{
		return $this->where(['id'=>$id])->value('name');
	}

	public function findByWhere($where,$field = '*')
	{
		return $this->where($where)->field($field)->find();
	}

	//根据条件查出指定字段的列表
    public function getListByWhere($where, $field = '*')
    {
        $list = $this->where($where)->field($field)->select();
        return $list;
    }

	//根据条件查出指定字段的区域代理列表
	public function getAgencyListByWhere($where, $field = '*')
	{
		$prefix = config('database.connections.mysql.prefix');
		$list = $this->alias('p')
			->field($field)
			->leftJoin($prefix.'new_marketing_person_agency pa','pa.person_id = p.id')
			->where($where)
			->select();
		return $list;
	}

    //根据条件查出数据的所有ID
    public function getIdsByWhere($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $ids = $this->alias('a')
            ->leftJoin($prefix.'new_marketing_team b','b.id = a.team_id')
            ->where($where)
            ->column('a.id');
        return $ids;
    }

    public function getCanManager($where){
		$prefix = config('database.connections.mysql.prefix');

		$result = $this ->alias('p')
			->field('p.id,p.name')
			->leftJoin($prefix.'new_marketing_person_manager'.' bp','p.id = bp.person_id')
			->where($where)
			->select();

		return $result;
	}

	public function getCanSalesMan($where){
		$prefix = config('database.connections.mysql.prefix');

		$result = $this ->alias('p')
			->field('bp.id,p.name')
			->leftJoin($prefix.'new_marketing_person_salesman'.' bp','p.id = bp.person_id')
			->where($where)
			->select();

		return $result;
	}

    /**
     * @param $where
     * @return mixed
     * 物业列表
     */
    public function getHouseList($where,$field,$order,$page,$pageSize){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('p')
            ->field($field)
            ->join($prefix.'new_marketing_person_mer'.' mer','mer.person_id = p.id')
            ->join($prefix.'house_property'.' hp','hp.id = mer.mer_id')
            ->where($where);
        $ret['count']=$result->count();
        $ret['list']=$result->order($order)->page($page, $pageSize)->select()->toArray();
        return $ret;
    }

    /**
     * @param $where
     * @param $field
     * @return mixed
     * 关联业务经理信息
     */
	public function getPerson($where,$field){
		$prefix = config('database.connections.mysql.prefix');

		$result = $this ->alias('p')
			->field($field)
			->leftJoin($prefix.'new_marketing_person_manager'.' bp','p.id = bp.person_id')
			->where($where)
			->find();
		return $result;
	}

	/**
	 * 职位审核用
	 * status 1 业务员 2业务经理 3区域代理
	 */
	public function getPerson2($where,$field,$status){
		$prefix = config('database.connections.mysql.prefix');

		if($status == 1){
			$join = 	$prefix.'new_marketing_person_salesman'.' bp';
		}elseif ($status == 2){
			$join = 	$prefix.'new_marketing_person_manager'.' bp';
		}else{
			$join = 	$prefix.'new_marketing_person_agency'.' bp';
		}
		$result =$result = $this ->alias('p')
			->field($field)
			->leftJoin($join,'p.id = bp.person_id')
			->where($where)
			->find();
        if(!empty($result)){
            $result=$result->toarray();
        }else{
            $result=[];
        }
		return $result;
	}

    /**
     * 职位审核用
     * status 1 业务员 2业务经理 3区域代理
     */
    public function getSomePerson($where,$field=true,$order=true,$status){
        $prefix = config('database.connections.mysql.prefix');

        if($status == 1){
            $join = 	$prefix.'new_marketing_person_salesman'.' bp';
        }elseif ($status == 2){
            $join = 	$prefix.'new_marketing_person_manager'.' bp';
        }else{
            $join = 	$prefix.'new_marketing_person_agency'.' bp';
        }
        $result =$result = $this ->alias('p')
            ->field($field)
            ->leftJoin($join,'p.id = bp.person_id')
            ->leftJoin($prefix.'user u','u.uid = p.uid')
            ->where($where)
            ->order($order)
            ->select()
            ->toarray();
        return $result;
    }
    // 营销人员列表（业务经理列表、区域代理列表、业务人员列表）
    public function serviceManagerList($where,$field){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->field($field)
            ->leftJoin($prefix.'new_marketing_person_salesman a','g.id = a.person_id')
            ->leftJoin($prefix.'new_marketing_person_manager b','g.id = b.person_id')
            ->leftJoin($prefix.'new_marketing_person_agency c','g.id = c.person_id')
            ->where($where)
            ->group('g.id')
            ->select();
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
     * @param array $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 查询人员信息
     */
    public function getPersonMsg($where = [], $field = true, $order = []){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('p')
            ->join($prefix.'user u','u.uid = p.uid')
        ->field($field)->where($where)->order($order)->find();
        if(empty($result)){
            return [];
        }else{
            return $result->toArray();
        }

    }

    /**
     * @param $where
     * @param $field
     * @return mixed
     *找出物业
     */
    public function getHourse($where,$field){
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('p')
            ->field($field)
            ->leftJoin($prefix.'new_marketing_person_mer'.' mer','mer.person_id = p.id')
            ->leftJoin($prefix.'house_property'.' h','h.id = mer.mer_id')
            ->where($where)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }else{
            $result=[];
        }
        return $result;
    }
    /**
     * @param array $where
     * @param bool $field
     * @param array $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 查询人员信息
     */
    public function getBusinessMsg($where = [], $field = true, $order = []){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('p')
            ->join($prefix.'user u','u.uid = p.uid')
            ->join($prefix.'new_marketing_person_salesman ns','ns.person_id = p.id')
            ->join($prefix.'new_marketing_team nt','nt.id = ns.team_id')
            ->field($field)->where($where)->order($order)->find();
        if(empty($result)){
            return [];
        }else{
            return $result->toArray();
        }

    }

    /**
     * @param $where
     * 删除数据
     */
    public function delData($where){
        $ret=$this->where($where)->delete();
        return $ret;
    }
}
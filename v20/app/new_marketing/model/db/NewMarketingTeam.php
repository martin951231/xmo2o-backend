<?php
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingTeam extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 团队列表
    public function getMarketingManagementList($where, $field, $order, $page, $pageSize){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->field($field)
            ->leftJoin($prefix.'new_marketing_person'.' a','g.manager_uid = a.id')
            ->leftJoin($prefix.'new_marketing_person'.' b','g.area_uid = b.id')
            ->leftJoin($prefix.'new_marketing_person_manager'.' c','c.person_id = a.id')
            ->leftJoin($prefix.'new_marketing_person_agency'.' d','d.person_id = b.id')
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->group('g.id')
            ->select();
        return $result;
    }

    // 团队列表数量
    public function getMarketingManagementCount($where, $field, $order){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->field($field)
            ->leftJoin($prefix.'new_marketing_person'.' a','g.manager_uid = a.id')
            ->leftJoin($prefix.'new_marketing_person'.' b','g.area_uid = b.id')
            ->leftJoin($prefix.'new_marketing_person_manager'.' c','c.person_id = a.id')
            ->leftJoin($prefix.'new_marketing_person_agency'.' d','d.person_id = b.id')
            ->where($where)
            ->order($order)
            ->group('g.id')
            ->count();
        return $result;
    }

    //获取业务经理团队总业绩
    public function getTeamAchievement($where) {
        $sum = $this
            ->where($where)
            ->sum('achievement');
        return $sum;
    }

    //根据条件查出数据的所有ID
    public function getIdsByWhere($where)
    {
        $ids = $this->where($where)->column('id');
        return $ids;
    }

   //根据条件查出数据
    public function getOne($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
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
	 * 通过团队id获取业务经理
	 */
	public function getManagerMsg($where = [], $field = true, $order = []){
		$prefix = config('database.connections.mysql.prefix');
		$result = $this->alias('t')
			->join($prefix.'new_marketing_person p','p.id = t.manager_uid','LEFT')
			->join($prefix.'user u','u.uid = p.uid','LEFT')
			->field($field)->where($where)->order($order)->select();
		if(empty($result)){
			return [];
		}else{
			return $result->toArray();
		}

	}
}
<?php
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPersonMer extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 注册商家列表
    public function teamManagementMerchantList($where, $field, $order, $page, $pageSize) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->order($order)
            ->leftJoin($prefix.'new_marketing_person b','b.id = g.person_id')
            ->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id');
        $assign['list']=$result->page($page, $pageSize)
            ->select()
            ->toArray();
        $assign['count']=$result->count();
        return $assign;
    }

    /**
     * @param array $where
     * @return mixed
     * 计算数量
     */
    public function getCountMer($where = [])
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('m')
            ->where($where)
            ->join($prefix.'new_marketing_team t','t.id = m.team_id')
            ->join($prefix.'new_marketing_person p','m.person_id = p.id')// 推广业务员
            ->count();
        return $result;
    }

    /**
	 * 获得商家列表
	 */
    public function getMerchantList($where, $field, $order, $page, $pageSize){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('pm')
            ->field($field)
            ->join($prefix.'merchant m','pm.mer_id = m.mer_id')// 商家
            ->join($prefix.'new_marketing_person p','pm.person_id = p.id')// 推广业务员
            ->join($prefix.'new_marketing_person_salesman ps','ps.person_id = p.id')// 推广业务员详情
            ->join($prefix.'new_marketing_team team','team.id = ps.team_id')// 团队
            ->where('pm.type',0)//0商家 1社区物业
            ->where($where)
            ->order($order)
            ->group('m.mer_id')
            ->page($page, $pageSize)
            ->select();
        return $result;
    }

    public function getList($where = [],$field){
        $result = $this->where($where)->group($field)->select()->toArray();
        return $result;
    }
	/**
	 * 获得商家详情
	 */
	public function getMerchantInfo($where, $field, $order){
		$prefix = config('database.connections.mysql.prefix');
		$result = $this ->alias('pm')
			->field($field)
			->join($prefix.'merchant m','pm.mer_id = m.mer_id')// 商家
			->join($prefix.'new_marketing_person p','pm.person_id = p.id')// 推广业务员
			->join($prefix.'new_marketing_person_salesman ps','ps.person_id = p.id')// 推广业务员详情
			->join($prefix.'new_marketing_team team','team.id = ps.team_id')// 团队
			->where($where)
			->order($order)
			->find();
		return $result;
	}

    /**
     * 获得社区列表
     */
    public function getPropertyList($where, $field, $order, $page, $pageSize){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('pm')
            ->field($field)
            ->join($prefix.'house_property m','m.id = pm.mer_id')// 社区
            ->join($prefix.'new_marketing_person p','pm.person_id = p.id')// 推广业务员
            ->join($prefix.'new_marketing_person_salesman ps','ps.person_id = p.id')// 推广业务员详情
            ->join($prefix.'new_marketing_team team','team.id = ps.team_id')// 团队
            ->where('pm.type',1)//0商家 1社区物业
            ->where($where);
        $list['list']=$result->order($order)
            ->page($page, $pageSize)
            ->select();
        $list['count']= $result->count();
        return $list;
    }


    /**
	 * 获得商家总数
	 */
    public function getMerchantCount($where){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('pm')
            ->join($prefix.'merchant m','pm.mer_id = m.mer_id')// 商家
            ->join($prefix.'new_marketing_person p','pm.person_id = p.id')// 推广业务员
            ->join($prefix.'new_marketing_person_salesman ps','ps.person_id = p.id')// 推广业务员详情
            ->join($prefix.'new_marketing_team team','team.id = ps.team_id')// 团队
            ->where('pm.type',0)//0商家 1社区物业
            ->where($where)
            ->group('m.mer_id')
            ->count();
        return $result;
    }

    /**
     * 添加一条数据
     * @author:zhubaodi
     * @date_time: 2021/9/10 13:25
     */
    public function addOne($data)
    {
        $insert = $this->insertGetId($data);
        return $insert;
    }

    /**
     * 查询单条数据
     * @author:zhubaodi
     * @date_time: 2021/9/10 13:45
     */
    public function getOne($where,$field =true){
        $info = $this->field($field)->where($where)->find();
        return $info;
    }
}
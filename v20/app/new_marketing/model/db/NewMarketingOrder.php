<?php
/**
 * 汪晨
 * 2021/08/24
 * 订单
 */
namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingOrder extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    // 团队业绩详情列表
    public function teamManagementSavage($where, $field, $order, $page, $pageSize) {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->where($where)
            ->field($field)
            ->order($order)
            ->leftJoin($prefix.'new_marketing_order_type t','t.order_id = g.order_id')
            ->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = t.id')
            ->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
            ->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id')
			->leftJoin($prefix.'package_order f','f.order_id = t.order_id')
			->leftJoin($prefix.'new_marketing_team mt','mt.id = t.team_id');
//			->leftJoin($prefix.'new_marketing_order_type_team to','to.type_id = t.id');
		$assign['count']=$result->count();

		//店铺数量
		$result2 =$this ->alias('g')
			->where($where)
			->field($field)
			->order($order)
			->leftJoin($prefix.'new_marketing_order_type t','t.order_id = g.order_id')
			->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = t.id')
			->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
			->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id')
			->leftJoin($prefix.'package_order f','f.order_id = t.order_id')
			->leftJoin($prefix.'new_marketing_team mt','mt.id = t.team_id')
			->group('g.mer_id')
			->count();
		$assign['mer_num']=$result2;

		//成交金额
		$result3 = $this ->alias('g')
			->where($where)
			->field($field)
			->order($order)
			->leftJoin($prefix.'new_marketing_order_type t','t.order_id = g.order_id')
			->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = t.id')
			->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
			->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id')
			->leftJoin($prefix.'package_order f','f.order_id = t.order_id')
			->leftJoin($prefix.'new_marketing_team mt','mt.id = t.team_id')
			->sum('g.total_price');
		$assign['total_price']=$result3;

        $assign['list']=$result->page($page, $pageSize)
           ->select()
           ->toArray();

        return $assign;
    }

    //获取套餐下单数量
    public function getPackageCount($where) {
        $count = $this->where($where)->count();
        return $count;
    }

    //获取人员提成结算列表
    public function getPersonSettleList($where, $field, $limit) {
        $prefix = config('database.connections.mysql.prefix');
        $list = $this->alias('o')
            ->leftJoin($prefix.'new_marketing_order_type g','g.order_id = o.order_id')
            ->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = g.id')
            ->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
            ->leftJoin($prefix.'merchant c','c.mer_id = o.mer_id')
            ->leftJoin($prefix.'new_marketing_order_type_artisan d','d.type_id = g.id')
            ->leftJoin($prefix.'new_marketing_artisan e','e.id = d.artisan_id')
            ->leftJoin($prefix.'package_order f','f.order_id = g.order_id')
            ->where($where)
            ->group('o.order_id')
            ->field($field)
            ->order('o.pay_time desc')
            ->paginate($limit);
        return $list;
    }

    /**
     * Notes: 对应字段求和
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getSum($where,$field){
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

	// 订单详情
	public function getOrderInfo($where, $field, $order, $page, $pageSize) {
		$newMarketingOrderType = (new NewMarketingOrderType());
		$prefix = config('database.connections.mysql.prefix');
		$result = $newMarketingOrderType ->alias('t')
			->where($where)
			->field($field)
			->order($order)
			->leftJoin($prefix.'new_marketing_order g','t.order_id = g.order_id')
			->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = t.id')
			->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
			->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id')
			->leftJoin($prefix.'package_order f','f.order_id = t.order_id')
			->leftJoin($prefix.'new_marketing_team mt','mt.id = t.team_id');
//			->leftJoin($prefix.'new_marketing_order_type_team to','to.type_id = t.id');
		$assign['count']=$result->count();

		//店铺数量
		$result2 =$newMarketingOrderType ->alias('t')
			->where($where)
			->field($field)
			->order($order)
			->leftJoin($prefix.'new_marketing_order g','t.order_id = g.order_id')
			->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = t.id')
			->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
			->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id')
			->leftJoin($prefix.'package_order f','f.order_id = t.order_id')
			->leftJoin($prefix.'new_marketing_team mt','mt.id = t.team_id')
			->group('g.mer_id')
			->count();
		$assign['mer_num']=$result2;

		//成交金额
		$result3 = $newMarketingOrderType ->alias('t')
			->where($where)
			->field($field)
			->order($order)
			->leftJoin($prefix.'new_marketing_order g','t.order_id = g.order_id')
			->leftJoin($prefix.'new_marketing_order_type_person a','a.type_id = t.id')
			->leftJoin($prefix.'new_marketing_person b','b.id = a.person_id')
			->leftJoin($prefix.'merchant c','c.mer_id = g.mer_id')
			->leftJoin($prefix.'package_order f','f.order_id = t.order_id')
			->leftJoin($prefix.'new_marketing_team mt','mt.id = t.team_id')
			->sum('g.total_price');
		$assign['total_price']=$result3;

		$assign['list']=$result->page($page, $pageSize)
			->select()
			->toArray();

		return $assign;
	}

}
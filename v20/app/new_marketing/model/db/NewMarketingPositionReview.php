<?php
/**
 * 职位审核
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/24
 * Time: 10:47
 */

namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPositionReview extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

	public function getPositionReviewList($where = [], $field = '', $pageSize, $order = '')
	{
		$prefix = config('database.connections.mysql.prefix');
		$result = $this->alias('s')
			->where($where)
			->field($field)
			->order($order)
			->join($prefix.'new_marketing_person'.' nmp','s.pid = nmp.id','LEFT')
            ->join($prefix.'new_marketing_person_salesman'.' ps','ps.person_id = nmp.id','LEFT')
			->join($prefix.'new_marketing_person_manager'.' bp','bp.person_id = nmp.id','LEFT')
			->join($prefix.'new_marketing_team'.' nmt','bp.team_id = nmt.id','LEFT')
            ->join($prefix.'new_marketing_team'.' nmtt','ps.team_id = nmtt.id','LEFT')
            ->group('s.id')
			->paginate($pageSize);

		return $result;
	}
}
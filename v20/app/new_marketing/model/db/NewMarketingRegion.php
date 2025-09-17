<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/25
 * Time: 13:17
 */

namespace app\new_marketing\model\db;


use think\Model;

class NewMarketingRegion extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

	public function getRegionList($where,$order,$pageSize,$field){
		$prefix = config('database.connections.mysql.prefix');
		return $this->alias('r')
			->where($where)
			->field($field)
//			->join($prefix.'area'.' a','a.area_id = r.region_id','LEFT')
			->order($order)
			->paginate($pageSize);
	}

    //获取全部省ID列表
    public function getAllProvinceIds($where) {
        $list = $this->where($where)->field('id,region_id')->select()->toArray();
        return $list;
    }

}
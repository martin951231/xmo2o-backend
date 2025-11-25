<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      普通日志
	 */

	namespace app\common\model\db;

	use think\Exception;
	use think\Model;

	class CommonSystemLog extends Model
	{

		protected $schema = [
			'id'        => 'int',
			'tbname'    => 'string',
			'table'     => 'string',
			'client'    => 'string',
			'trigger_path' => 'string',
			'trigger_type' => 'string',
			'addtime'      => 'int',
			'content'      => 'text',
			'op_id'        => 'int', //当前登录账号的uid
			'op_type'      => 'int', //当前登录操作员的类型，比如是物业的、小区的、还是街道等
			'op_name'      => 'string', //当前登录账号的名称
			'property_id'  => 'int',//物业的id （用来业务展示筛选)
			'village_id'   => 'int', //小区id  （用来业务展示筛选)
			'area_id'      => 'int', //街道社区id（用来业务展示筛选)
		];

		public function addNewLog (array $data = [])
		{
			return	$this->insert($data,true);
		}
	}
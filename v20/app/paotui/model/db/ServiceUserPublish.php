<?php

namespace app\paotui\model\db;

use think\Model;

/**
 * 服务快派订单模型
 * @author: 张涛
 * @date: 2020/10/16
 * @package app\paotui\model\db
 */
class ServiceUserPublish extends Model
{
	protected $pk = 'publish_id';
	
	const STATUS_PAY_SUCCESS = 2; //已支付待服务
	const STATUS_CANCEL = 10; //已取消
}

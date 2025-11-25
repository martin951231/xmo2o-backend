<?php

namespace app\appoint\model\db;

use think\Model;

class AppointOrderRecords extends Model
{
	public function setContentAttr($val)
	{
		return is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
	}
	
	public function getContentAttr($val)
	{
		return $val ? json_decode($val, true) : [];
	}
}
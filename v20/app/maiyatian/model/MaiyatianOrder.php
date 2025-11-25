<?php

namespace app\maiyatian\model;

use think\Model;
use think\model\relation\HasMany;

class MaiyatianOrder extends Model
{
	public function goodsList(): HasMany
	{
		return $this->hasMany(MaiyatianOrderGoods::class, 'order_id', 'id');
	}
}
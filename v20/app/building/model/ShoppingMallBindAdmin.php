<?php

namespace app\building\model;

use think\Model;

class ShoppingMallBindAdmin extends Model
{
	public static function updateOrCreate(array $condition, array $data)
	{
		$record = self::where($condition)->find();

		if ($record) {
			return $record->save($data);
		} else {
			$newData = array_merge($condition, $data);
			return self::create($newData);
		}
	}
}
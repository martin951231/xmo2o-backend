<?php

namespace app\maiyatian\model;

use think\Model;
use think\model\relation\HasOne;

class MaiyatianMerMoneyList extends Model
{
	const TYPE_ADD = 1; // 加钱
	const TYPE_MINUS = 2; // 减钱
}
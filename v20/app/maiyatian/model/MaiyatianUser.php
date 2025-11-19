<?php

namespace app\maiyatian\model;

use think\Model;

class MaiyatianUser extends Model
{
	const SENDER_TYPE_USER = 1; //发货方
	const RECEIVER_TYPE_USER = 2; //收货方
}
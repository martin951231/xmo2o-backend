<?php
/**
 * 支付通道表
 * Created by lumin.
 */

namespace app\pay\model\db;

use think\Model;
use think\facade\Db;
class PayChannel extends Model{
	use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where = []){
    	return $this->where($where)->select();
    }

    public function getOne($where = []){
    	return $this->where($where)->find();
    }
}
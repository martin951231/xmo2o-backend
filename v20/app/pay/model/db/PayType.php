<?php
/**
 * 支付方式表
 * Created by lumin.
 */

namespace app\pay\model\db;

use think\Model;
use think\facade\Db;
class PayType extends Model{
	use \app\common\model\db\db_trait\CommonFunc;
    public function getList($where = []){
    	return $this->where($where)->select();
    }
}
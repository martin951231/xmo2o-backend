<?php
/**
 * 支付通道配置参数表
 * Created by lumin.
 */

namespace app\pay\model\db;

use think\Model;
use think\facade\Db;
class PayChannelParam extends Model{
	use \app\common\model\db\db_trait\CommonFunc;
	
    public function getList($where = []){
    	return $this->where($where)->order('orderby desc')->select();
    }
}
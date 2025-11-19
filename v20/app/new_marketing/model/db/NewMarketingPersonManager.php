<?php
/**
 * Created by : PhpStorm
 * User: wangyi
 * Date: 2021/8/31
 * Time: 11:33
 */

namespace app\new_marketing\model\db;

use think\Model;

class NewMarketingPersonManager extends Model
{
	use \app\common\model\db\db_trait\CommonFunc;

    //获取数据
    public function getOneData($param) {
        $res = $this->where($param)->find();
        return $res;
    }

}
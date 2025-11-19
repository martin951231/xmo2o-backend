<?php

namespace app\foodshop\model\db;

use think\Model;
use think\facade\Db;

class ShopDecorationColor extends Model{
    // 获取装修颜色
    public function getOneColor($where){
        $result = $this->where($where)->find();
        $where['type_id'] == 1 ? $type_color = 'rgb(0,204,153)' : $type_color = 'rgb(255,255,255)';
        empty($result) ? $return_color = $type_color : $return_color = $result['value'];
        return $return_color;
    }

    // 获取爆款列表
    public function hotGoodsList($shop_id){
        $where = "sh.recommend_status = 1 AND s.cat_fid=".$shop_id;
        $list = Db::query("SELECT `m`.`name` AS merchant_name, `s`.`name` AS store_name, `m`.`phone` AS merchant_phone, `s`.`phone` AS store_phone, `s`.`store_id`,`s`.`status`,`s`.`open_1`,`s`.`close_1`,`s`.`open_2`,`s`.`close_2`,`s`.`open_3`,`s`.`close_3`, `sh`.* FROM pigcms_merchant AS m INNER JOIN pigcms_merchant_store AS s ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN pigcms_merchant_store_shop AS sh ON `sh`.`store_id`=`s`.`store_id` WHERE " .$where. " ORDER BY `sh`.`sort` DESC, `sh`.`store_id` DESC");
        return $list;
    }
}
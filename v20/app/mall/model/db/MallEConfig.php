<?php
/**
 * MallEConfig.php
 * 商家发票配置model
 * Create on 2020/10/13 15:38
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class MallEConfig extends Model
{
    /**
     * 查询商家发票配置信息
     * @param $mer_id
     * @return array
     */
    public function getEConfig($mer_id)
    {
        $where = ['mer_id' => $mer_id];
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
}
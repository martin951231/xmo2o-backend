<?php
/**
 * 小区和用户绑定关系
 * Created by.PhpStorm
 * User: chenxiang
 * Date: 2020/5/26 19:13
 */

namespace app\common\model\service;

use app\common\model\db\HouseVillageUserBind;

class HouseVillageUserBindService
{
    public $houseVillageUserBindObj = null;
    public function __construct()
    {
        $this->houseVillageUserBindObj = new HouseVillageUserBind();
    }

    /**
     * 更新小区和用户绑定关系
     * User: chenxiang
     * Date: 2020/5/26 19:23
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveData($where = [], $data= []) {
        $result = $this->where($where)->update($data);
        if($result !== true) {
            return false;
        }
        return $result;
    }
}

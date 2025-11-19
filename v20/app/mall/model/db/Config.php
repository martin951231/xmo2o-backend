<?php

namespace app\mall\model\db;

use think\Collection;
use think\Model;

class Config extends Model
{
    /**
     * 获取库里的BaiduMap AK
     * @param
     * @return Collection
     */
    public function getAk()
    {
        $where = [
            'name' => 'baidu_map_ak'
        ];
        return $this->field('value')->where($where)->select();
    }
}
<?php
/**
 * 滚动消息
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/5/28 18:51
 */

namespace app\common\model\db;

use think\Model;

class ScrollMsg extends Model
{
    /**
     * 添加滚动消息
     * User: chenxiang
     * Date: 2020/5/28 18:52
     * @param array $data
     * @return int|string
     */
    public function addMsg($data = []) {
        $result = $this->insert($data);
        return $result;
    }

}
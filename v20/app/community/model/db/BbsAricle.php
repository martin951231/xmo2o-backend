<?php
/**
 * 论坛文章表
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/25 15:14
 */

namespace app\community\model\db;

use think\Model;
use think\facade\Db;
class BbsAricle extends Model{

    /**
     * 查询对应条件下文章数量
     * @author: wanziyang
     * @date_time: 2020/4/25 15:01
     * @param array $where
     * @return int
     */
    public function get_bbs_aricle_num($where) {
        $count = $this->alias('a')
            ->leftJoin('bbs b','a.bbs_id = b.bbs_id')
            ->where($where)->count();
        return $count;
    }
}
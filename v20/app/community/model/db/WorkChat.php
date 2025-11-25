<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/10/26
 * Time: 14:06
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class WorkChat extends Model
{
    /**
     * 获取客户群列表
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChatList($where = [],$field = true){
        $data = $this->where($where)->field($field)->select();
        if(!$data->isEmpty()){
            $data = $data->toArray();
            return $data;
        }else{
            return [];
        }
    }
}
<?php
/**
 * 系统后台配置model
 * Author: chenxiang
 * Date Time: 2020/5/23 17:09
 */

namespace app\common\model\db;

use think\Model;

class ConfigData extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 查询一条数据
     * @param array $where
     * @return array|Model|null
     */
    public function getDataOne($where = []) {
        $result = $this->where($where)->find();
        return $result;
    }

    /**
     * 更新数据
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function saveData($where = [], $data = []) {
        $result = $this->where($where)->save($data);
        return $result;
    }

    /**
     * 添加一条数据
     * @param array $data
     * @return int|string
     */
    public function addData($data = []) {
        $result = $this->insert($data);
        return $result;
    }
}

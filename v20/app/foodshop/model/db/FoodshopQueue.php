<?php
/**
 * 餐饮排号model
 * Created by vscode.
 * Author: 钱大双
 * Date Time: 2020年12月4日15:15:08
 */

namespace app\foodshop\model\db;

use think\Model;

class FoodshopQueue extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    /**插入数据
     * @param $data
     * @return int|string
     */
    public function insert_queue($data)
    {
        return $this->insertGetId($data);
    }


    /**根据条件删除数据
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function del($where)
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->where($where)->delete();
        return $result;
    }
}
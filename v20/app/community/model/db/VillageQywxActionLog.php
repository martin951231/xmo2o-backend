<?php


namespace app\community\model\db;
use think\Model;

class VillageQywxActionLog extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 添加日志
     * @author lijie
     * @date_time 2021/03/25
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }
}
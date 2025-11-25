<?php
namespace app\community\model\db;

use think\Model;
class HouseDeviceChannel extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    public function getLists($where,$field =true,$page=1,$limit=10,$order='a.channel_id ASC') {
        $db_list = $this->alias('a')
            ->leftJoin('house_camera_device b','a.deviceSerial=b.camera_sn')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$limit);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * Notes:获取某个字段
     * @param $where
     * @param string $column 字段名 多个字段用逗号分隔
     * @param string $key   索引
     * @return array
     */
    public function getColumn($where, $column ='', $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
    
}
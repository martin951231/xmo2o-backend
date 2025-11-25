<?php


namespace app\community\model\db;

use think\Model;

class HouseServicesHousekeeper extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function getList($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->select();
        return $data;
    }

    public function getListAndRaw($where=[],$field='*',$whereRaw='')
    {
        $sql = $this->where($where);
        if ($whereRaw) {
            $sql = $sql->whereRaw($whereRaw);
        }
        $data = $sql->field($field)->select();
        return $data;
    }
}
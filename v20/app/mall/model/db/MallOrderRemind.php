<?php

/**
 * 店员端提醒表
 */
namespace app\mall\model\db;

use think\Model;

class MallOrderRemind extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     *根据条件获取数量
     * @author mrdeng
     */
    public function getSomeCount($where)
    {
        $count = $this->where($where)->count();
        $list = $this->where($where)->select();
        if(!empty($list)){
            $list=$list->toArray();
            foreach ($list as $key=>$val){
                $up = [['id', '=', $val['id']]];
                $data['status_up']=1;
                $this->updateThis($up,$data);
            }
        }
        return $count;
    }

    /**
     * 更新信息
     * @param $where
     * @param $data
     * @return MallOrder
     * @author mrdeng
     */
    public function updateThis($where, $data)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * 添加一项
     * @param $where
     * @param $data
     * @author mrdeng
     */
    public function addOne($data)
    {
        return $result = $this->insertGetId($data);

    }

}
<?php
/**
 * 党支部
 */

namespace app\community\model\db;

use think\Model;
class AreaStreetPartyBranch extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes: 添加数据获取id
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/9/14 14:10
     */
    public function addFind($data){
        $result = $this->insertGetId($data);
        return $result;
    }

    public function del($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }

    /**
     * 统计类型
     * @author: liukezhu
     * @date : 2022/5/6
     * @param $where
     * @param string $group
     * @param bool $field
     * @return mixed
     */
    public function getListByGroup($where,$group='type',$field=true)
    {
        return $this->where($where)->field($field)->group($group)->select();
    }

    public function getList($where,$field='*',$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }
}
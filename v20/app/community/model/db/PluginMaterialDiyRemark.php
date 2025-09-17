<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/22
 * Time: 15:43
 *======================================================
 */

namespace app\community\model\db;


use think\Model;

class PluginMaterialDiyRemark extends Model
{
    /**
     * 获取装饰申请单备注列表
     * @param $where
     * @param int $page
     * @param int $limit
     * @param string $field
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRemarkList($where, $page = 0, $limit = 10, $field='*',$order='remark_id desc'){
        $sql = $this->where($where)
            ->field($field)
            ->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        if(!$list->isEmpty()){
            $list = $list->toArray();
            return $list;
        }else{
            return [];
        }
    }

    /**
     * 获取备注总数
     * @param $where
     * @return int
     */
    public function getRemarkCount($where){
        return $this->where($where)->count();
    }

    /**
     * 添加备注
     * @param $data
     * @return int|string
     */
    public function addRemark($data){
        return $this->insert($data);
    }

    /**
     * 修改备注信息
     * @param $where
     * @param $data
     * @return bool
     */
    public function saveRemark($where,$data){
        return $this->where($where)->save($data);
    }

    /**
     * 删除备注
     * @param $remark_id
     * @return bool
     * @throws \Exception
     */
    public function delRemark($remark_id){
        if(empty($remark_id)){
            return false;
        }
        return $this->where([['remark_id','=',$remark_id]])->delete();
    }
}
<?php
/**
 * 会员余额操作 model
 * Created by.PhpStorm
 * User: chenxiang
 * Date: 2020/5/26 10:59
 */

namespace app\common\model\db;

use think\Model;

class UserMoneyList extends Model
{
    /**
     * 添加会员余额记录
     * User: chenxiang
     * Date: 2020/5/26 11:05
     * @param array $data
     * @return int|string
     */
    public function addData($data = []) {
        $result = $this->insert($data);
        return $result;
    }


    /**
     * 查询列表
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getList($where,$field='*',$order='pigcms_id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getListRelateUser($where,$field='*',$order='pigcms_id desc',$page=0,$limit=20)
    {
        $sql = $this->alias('ml')->leftJoin('user u','ml.uid = u.uid')->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getCountRelateUser($where) {
        $list = $this->alias('ml')->leftJoin('user u','ml.uid = u.uid')->where($where)->count();
        return $list;
    }
    
    /**查询总数
     * @author: liukezhu
     * @date : 2021/11/22
     * @param $where
     * @return mixed
     */
    public function getCount($where) {
        $count =$this->where($where)->count();
        return $count;
    }
}

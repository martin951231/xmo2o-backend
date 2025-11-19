<?php
/**
 * 小区工作人员
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/4/28 13:57
 */

namespace app\community\model\db;


use think\Model;
use think\facade\Db;

class HouseWorker extends Model{
    protected $pk = 'wid';
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * Notes:获取单个信息
     * @param array $where 查询条件
     * @param bool|string $field 需要查询的字段 默认查询所有
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/5/13 21:11
     */
    public function get_one($where,$field ='*',$orderby='wid desc'){
        $info = $this->field($field)->where($where)->order($orderby)->find();
        return $info;
    }

    public function getAll($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('wid desc')->select();
        return $data;
    }

    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return HouseWorker
     * @author: weili
     * @datetime: 2020/10/20 16:38
     */
    public function editData($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    public function saveOneData($where, $data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
    //添加数据
    public function addData($addData=array()){
        if(empty($addData)){
            return false;
        }
        $idd=$this->insertGetId($addData);
        return $idd;
    }
    /**
     * Notes: 获取工作人员列表
     * @param array $where
     * @param string $whereRaw
     * @param bool $field
     * @param bool $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: wanzy
     * @date_time: 2021/3/16 10:05
     */
    public function getWorkList($where = [],$whereRaw='', $field = true,$order=true,$page=0,$limit=0)
    {
        if ($whereRaw) {
            $sql = $this->field($field)->whereRaw($whereRaw)->order($order);
        } else {
            $sql = $this->field($field)->where($where)->order($order);
        }
        if($limit)
        {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
    public function getWorkLists($where = [] ,$field = true,$order=true,$page=0,$limit=20)
    {
        $sql = $this->field($field)->where($where)->order($order);
        if($page)
        {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }
    /**
     * Notes: 获取渠道活码绑定的工作人员
     * @param $where
     * @param int $page
     * @param string $field
     * @param string $order
     * @param int $page_size
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/18 13:55
     */
    public function getVillageQywxCodeBindWork($where,$field ='a.*',$page=0,$order='a.wid ASC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('village_qywx_code_bind_work b', 'a.wid=b.work_id')
            ->group('a.wid')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }
    public function getColumn($where,$column,$key='')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
    public function getMemberCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }



    public function checkStaff($where){
        $list = $this
            ->alias('w')
            ->leftJoin('property_group g','g.id=w.department_id')
            ->leftJoin('house_worker w2','g.fid=w2.department_id')
            ->where($where)->field('w.wid as staff_id,w2.wid')->order('w2.wid asc')->group('w.wid')->select();
        return $list;
    }

    //todo 获取分组下对应工作人员
    public function getGroupWork($where,$field = true,$order=true,$page=0,$limit=20){
        $sql = $this->alias('w')
            ->leftJoin('property_group g','g.id=w.department_id')
            ->field($field)->where($where)->order($order);
        if($page) {
            $sql->page($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }

    //todo 统计分组下对应工作人员
    public function getGroupWorkCount($where)
    {
        $count = $this->alias('w')
            ->leftJoin('property_group g','g.id=w.department_id')->where($where)->count();
        return $count;
    }


    public function getWhereOrPage($where, $field = true, $page = 1, $limit = 200, $order = 'wid ASC')
    {
        if ($page > 0) {
            $list = $this->whereOr($where)->field($field)->page($page, $limit)->order($order)->select();
        } else {
            $list = $this->whereOr($where)->field($field)->order($order)->select();
        }
        return $list;
    }

}

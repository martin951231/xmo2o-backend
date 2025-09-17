<?php
namespace app\community\model\db;

use think\Model;

class AreaStreetWorkers extends Model
{
    public $con = [];
    /**
     * Notes: 获取列表
     * @param $where
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @param int $branch_id
     * @datetime: 2021/2/23 10:42
     * @return \think\Collection
     */
    public function getSelect($where,$field=true,$order='worker_id DESC',$page=1,$limit=20,$branch_id=0)
    {
        if($page) {
            if($branch_id){
                foreach ($branch_id as $k=>$v){
                    $this->con[] = 'find_in_set('.$v.',organization_ids)';
                }
                $res = $this->where(function ($query){
                    foreach ($this->con as $key=>$val){
                        if($key == 0)
                            $query->where($val);
                        else
                            $query->whereOr($val);
                    }
                })->where($where)->field($field)->order($order)->page($page, $limit)->select();
            } else{
                $res = $this->where($where)->field($field)->order($order)->page($page, $limit)->select();
            }
            return $res;
        }else{
            $res = $this->where($where)->field($field)->order($order)->select();
            return $res;
        }
    }

    /**
     * Notes: 获取一条
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @datetime: 2021/2/23 10:48
     */
    public function getOne($where,$field =true,$order='work_status asc'){
        $info = $this->field($field)->where($where)->order($order)->find();
        return $info;
    }


    /**
     * Notes: 添加单个
     * @param $data
     * @return int|string
     * @datetime: 2021/2/23 10:48
     */
    public function addOne($data) {
        $area_id = $this->insertGetId($data);
        return $area_id;
    }

    /**
     * Notes: 修改数据
     * @param $where
     * @param $save
     * @return bool
     * @datetime: 2021/2/23 10:48
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }

    /**
     * Notes: 硬删除
     * @param $where
     * @return bool
     * @throws \Exception
     * @datetime: 2021/2/23 10:54
     */
    public function delFind($where)
    {
        $res = $this->where($where)->save(['work_status'=>4]);
        return $res;
    }

    /**
     * 工作人员数量
     * @author lijie
     * @date_time 2021/02/26
     * @param $where
     * @param int $branch_id
     * @return int
     */
    public function getCount($where,$branch_id=0)
    {
        if($branch_id){
            if(is_array($branch_id)) {
                foreach ($branch_id as $k=>$v){
                    $this->con[] = 'find_in_set('.$v.',organization_ids)';
                }
                $count = $this->where(function ($query){
                    foreach ($this->con as $key=>$val){
                        if($key == 0)
                            $query->where($val);
                        else
                            $query->whereOr($val);
                    }
                })->where($where)->count();
            }else{
                $count = $this->where($where)->where('find_in_set('.$branch_id.',organization_ids)')->count();
            }
        } else{
            $count = $this->where($where)->count();
        }
        return $count;
    }


    public function getAll($where,$field=true)
    {
        $data = $this->where($where)->field($field)->order('worker_id asc')->select();
        return $data;
    }
}

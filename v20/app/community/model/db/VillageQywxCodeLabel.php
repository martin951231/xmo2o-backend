<?php


namespace app\community\model\db;

use think\Model;

class VillageQywxCodeLabel extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 标签列表
     * @author lijie
     * @date_time 2021/03/15
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where, $field = true, $page = 1, $limit = 10, $order = 'add_time ASC')
    {
        if ($page) {
            $data = $this->where($where)->field($field)->page($page, $limit)->select();
        } else {
            $data = $this->where($where)->field($field)->select();
        }
        return $data;
    }

    /**
     * 添加标签
     * @author lijie
     * @date_time 2021/03/18
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }


    /**
     * Notes: 获取渠道活码绑定的标签信息
     * @param $where
     * @param int $page
     * @param string $field
     * @param string $order
     * @param int $page_size
     * @return mixed
     * @author: wanzy
     * @date_time: 2021/3/18 13:55
     */
    public function getVillageQywxCodeBindLabel($where,$field ='a.*',$page=0,$order='a.label_id ASC',$page_size=10) {
        $db_list = $this->alias('a')
            ->leftJoin('village_qywx_code_bind_label b', 'a.label_id=b.label_id')
            ->group('a.label_id')
            ->field($field)
            ->order($order);
        if ($page) {
            $db_list->page($page,$page_size);
        }
        $list = $db_list->where($where)->select();
        return $list;
    }

    /**
     * 标签信息
     * @author lijie
     * @date_time 2021/03/23
     * @param array $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }


    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}
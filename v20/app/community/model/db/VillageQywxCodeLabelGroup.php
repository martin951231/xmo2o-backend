<?php


namespace app\community\model\db;

use think\Model;

class VillageQywxCodeLabelGroup extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 标签组列表
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
    public function getList($where,$field=true,$page=1,$limit=10,$order='add_time ASC')
    {
        if($page)
            $data = $this->where($where)->field($field)->page($page,$limit)->select();
        else
            $data = $this->where($where)->field($field)->select();
        return $data;
    }

    /**
     * 获取标签组详情
     * @author lijie
     * @date_time 2021/03/18
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    /**
     * 添加标签组
     * @author lijie
     * @date_time 2021/03/18
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }
}
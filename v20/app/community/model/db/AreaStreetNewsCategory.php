<?php


namespace app\community\model\db;

use think\Model;

class AreaStreetNewsCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取街道新闻分类列表
     * @author lijie
     * @date_time 2020/09/09 11:21
     * @param $where
     * @param $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLists($where,$field=true,$order='cat_sort DESC',$page=0,$limit=20)
    {
        $data = $this->where($where)->field($field);
        if($page)
            $data = $data->page($page,$limit)->order($order)->select();
        else
            $data = $data->order($order)->select();
        return $data;
    }
}
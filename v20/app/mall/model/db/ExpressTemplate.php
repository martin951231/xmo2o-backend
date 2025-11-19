<?php
/**
 * ExpressTemplate.php
 * 运费模板model
 * Create on 2020/10/24 13:13
 * Created by zhumengqun
 */

namespace app\mall\model\db;

use \think\Model;

class ExpressTemplate extends Model
{
    /**
     * @param $where
     * @return array
     * 获取某个商家的运费模板
     */
    public function getETByMerId($where,$page=0,$pageSize=10)
    {
        $arr = $this->field(true)->where($where);
        $count=$arr->count();
        if($page==0){
            $list = $arr->order('dateline desc')
                ->select()
                ->toArray();
        }else{
            $list = $arr->page($page, $pageSize)
                ->order('dateline desc')
                ->select()
                ->toArray();
        }

        $return = [
            'total_count' => $count,
            'page_count' => intval(ceil($count / $pageSize)),
            'now_page' => $page,
            'list' => $list
        ];
       return $return;
    }

    /**
     * @param $where
     * @return int
     * 获取某个商家的运费模板数量
     */
    public function getETCountByMerId($where)
    {
        $arr = $this->field('id,name')->where($where)->count();
        return $arr;
    }
    /**
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 根据条件获取一条
     */
    public function getOne($where)
    {
        $arr = $this->field(true)->where($where)->find();
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function updateOne($data,$where)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addOne($data) {
        return $this->insertGetId($data);

    }
}
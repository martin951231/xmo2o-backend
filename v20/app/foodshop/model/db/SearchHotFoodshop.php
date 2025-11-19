<?php
/**
 * 餐饮热门搜索关键词
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/29 15:00
 */

namespace app\foodshop\model\db;
use think\Model;
class SearchHotFoodshop extends Model {
    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获取热门搜索词列表
     * @param $order array 排序
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllSearchList($order=[]) {
        $result = $this ->order($order)
                        ->select();
        return $result;
    }

    
	/**
     * 获取热门搜索词列表带分页的
     * @param $where array 条件
     * @param $order array 排序
     * @param $page int 当前页数
     * @param $pageSize int 每页显示数量
     * @return array|bool|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSearchHotListBycondition($where, $order=[], $page = '1', $pageSize= '10') {
        $result = $this ->where($where)
                        ->order($order)
                        ->page($page,$pageSize)
                        ->select();
        return $result;
    }

}
<?php
/**
 * 外卖商品分类
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/5/19 10:19
 */

namespace app\shop\model\service\goods;
use app\shop\model\db\ShopGoodsSort as ShopGoodsSortModel;
class ShopGoodsSortService{
    public $shopGoodsSortModel = null;
    public function __construct()
    {
		$this->shopGoodsSortModel = new ShopGoodsSortModel();
	}


    /**
     * 获得店铺树形结构分类列表
     * @return array
     */
    public function getSortListTree($param){
        if(empty($param['store_id'])){
            throw new \think\Exception(L_("店铺id不存在"), 1001);
        }

        $where['store_id'] = $param['store_id'];
        
        // 是否附属分类
        if(isset($param['status'])){
            $where['status'] = $param['status'];
        }
        
        // 分类状态
        if(isset($param['sort_status'])){
            $where['sort_status'] = $param['sort_status'];
        }
        $sortList = $this->getSortListByCondition($where);
        if(!$sortList) {
            return [];
        }

        $tmpMap = array();
        $todayWeek = date('w');
        foreach ($sortList as $key => $_sort) {
            // 分类id
            $_sort['cat_id'] = $_sort['sort_id'];
            // 分类名陈
            $_sort['cat_name'] = htmlspecialchars_decode($_sort['sort_name'],ENT_QUOTES);
            // 分类名陈
            $_sort['sort_name'] = htmlspecialchars_decode($_sort['sort_name'],ENT_QUOTES);

            // 分类显示时间
            if (strlen($_sort['week'])) {
                $week_arr = explode(',', $_sort['week']);
                $week_str = '';
                if (isset($param['week_show']) && $param['week_show'] && !in_array($todayWeek, $week_arr)) {
                    //不在显示时间范围内的不显示
                    unset($sortList[$key]);
                    continue;
                }
                foreach ($week_arr as $k => $v) {
                    $week_str .= $this->getWeek($v) . ' ';
                }
                $_sort['week_str'] = $week_str;
            }

            $tmpMap[$_sort['sort_id']] = $_sort;
        }
        $list = array();
        foreach ($sortList as $_sort) {
            if ($_sort['fid'] && isset($tmpMap[$_sort['fid']])) {
                $tmpMap[$_sort['fid']]['son_list'][$_sort['sort_id']] = &$tmpMap[$_sort['sort_id']];
            } elseif(!$_sort['fid']) {
                $list[$_sort['sort_id']] = &$tmpMap[$_sort['sort_id']];
            }
        }
        unset($tmpMap);
        $list = array_values($list);
        foreach ($list as &$child){
            if(isset($child['son_list'])){
                $child['son_list'] = array_values($child['son_list']);
                foreach ($child['son_list'] as &$child2){
                    if(isset($child2['son_list'])){
                        $child2['son_list'] = array_values($child2['son_list']);
                    }
                }
            }
        }
        return $list;
    }

    protected function getWeek($num)
    {
        switch($num){
            case 1:
                return L_('星期一');
            case 2:
                return L_('星期二');
            case 3:
                return L_('星期三');
            case 4:
                return L_('星期四');
            case 5:
                return L_('星期五');
            case 6:
                return L_('星期六');
            case 0:
                return L_('星期日');
            default:
                return '';
        }
    }

    /**
     * 根据条件获取分类列表
     * @return array
     */
	public function getSortListByCondition($where){
		$sortList = $this->shopGoodsSortModel->getSortListByCondition($where);
		if(!$sortList) {
            return [];
        }
		return $sortList->toArray();
	}

	
    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return bool
     */
	public function updateThis($where,$data){
        if (!$where || !$data) {
            return false;
		}
		
		try {
			$result = $this->shopGoodsSortModel->updateThis($where,$data);
        }catch (\Exception $e) {
			return false;
        }
		
		return $result;
	}


	
    /**
     * 批量添加数据
     * @param $data
     * @return bool
     */
	public function addAll($data){
        if (!$data) {
            return false;
		}
		
		try {
			$result = $this->shopGoodsSortModel->addAll($data);
        }catch (\Exception $e) {
			return false;
        }
		
		return $result;
	}
}
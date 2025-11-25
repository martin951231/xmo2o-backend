<?php
class Shop_goods_sortModel extends Model
{
	public function get_sorts($store_id)
	{
		$store_shop = D('Merchant_store_shop')->where(array('store_id' => $store_id))->find();
		$store_discount = isset($store_shop['store_discount']) ? intval($store_shop['store_discount']) : 0;
		$sorts = D(_view('Shop_goods_sort'))->where(array('store_id' => $store_id))->select();
		$sort_discount = null;
		foreach ($sorts as $sort) {
			$sort_discount[$sort['sort_id']] = $sort['sort_discount'] ? $sort['sort_discount'] : $store_discount;
		}
		if ($sort_discount) {
			$sort_discount['discount_type'] = isset($store_shop['discount_type']) ? intval($store_shop['discount_type']) : 0;//0:折中折，1：以最优的优惠形式
		}
		return $sort_discount;
	}
	
    //@param int $shop_type 店铺类型 1-快店；2商城
	public function get_sorts_discount($store_id,$shop_type=1)
	{
		$store_shop = D('Merchant_store_shop')->where(array('store_id' => $store_id))->find();
        if($shop_type==2){
            $store_discount = isset($store_shop['mall_discount']) ? intval($store_shop['mall_discount']) : 0;    
        }else{
            $store_discount = isset($store_shop['store_discount']) ? intval($store_shop['store_discount']) : 0;    
        }
		
		$sorts = $this->where(array('store_id' => $store_id))->select();
		$sort_discount = null;
        if($shop_type==2){
            foreach ($sorts as $sort) {
                $sort_discount[$sort['sort_id']] = array('discount' =>$store_discount, 'discount_type' =>0);
            }
        }else{
            foreach ($sorts as $sort) {
                $sort_discount[$sort['sort_id']] = array('discount' => $sort['sort_discount'] ? $sort['sort_discount'] : $store_discount, 'discount_type' => $sort['sort_discount'] ? 1 : 0);
            }    
        }
		
		if ($sort_discount) {
            if($shop_type==2){
                $sort_discount['discount_type'] = isset($store_shop['mall_discount_type']) ? intval($store_shop['mall_discount_type']) : 0;//0:折中折，1：以最优的优惠形式
            }else{
                $sort_discount['discount_type'] = isset($store_shop['discount_type']) ? intval($store_shop['discount_type']) : 0;//0:折中折，1：以最优的优惠形式
            }			
		}
		return $sort_discount;
	}
	
	/**
	 * 获取一个店铺下的所有商品分类的树
	 * @param int $store_id
	 * @param string $is_hide
     * @param string $isOpen 分类状态，默认不检测状态
     * @param string $mallGoodsConfig2Array   true:mall_goods_config字段转换成数组为了放置前端parseJSON失败
	 * @return multitype:Ambigous <Ambigous <unknown, string>>
	 */
    public function lists($store_id, $is_hide = true, $mallGoodsConfig2Array = false,$inWeek = false,$isOpen=false)
    {
		$where = array('store_id' => $store_id);

        // 是否附属分类
		if($is_hide){
			$where['status'] = 1;
		}

        // 状态
        if($isOpen){
            $where['sort_status'] = 1;
        }

        $items = M(_view('Shop_goods_sort'))->where($where)->order('`sort` DESC,`sort_id` ASC')->select();

        $tmpMap = array();
        $today = date('w');
        foreach ($items as $key=>$item) {
            $item['cat_id'] = $item['sort_id'];
            $item['cat_name'] = htmlspecialchars_decode($item['sort_name'],ENT_QUOTES);
            $item['sort_name'] = htmlspecialchars_decode($item['sort_name'],ENT_QUOTES);
            if (strlen($item['week'])) {
                $week_arr = explode(',', $item['week']);
                $week_str = '';
                if ($inWeek && !in_array($today, $week_arr)) {
                    unset($items[$key]);
                    continue;
                }
                foreach ($week_arr as $k => $v) {
                    $week_str .= $this->getWeek($v) . ' ';
                }
                $item['week_str'] = $week_str;
            }
            if ($mallGoodsConfig2Array) {
                $item['mall_goods_config'] = $item['mall_goods_config'] ? json_decode($item['mall_goods_config'], true) : [];
            }
            else{
                $item['mall_goods_config'] = addslashes($item['mall_goods_config']);
            }
            $tmpMap[$item['sort_id']] = $item;
        }
        $list = array();
        foreach ($items as $item) {
            if ($item['fid'] && isset($tmpMap[$item['fid']])) {
                $tmpMap[$item['fid']]['son_list'][$item['sort_id']] = &$tmpMap[$item['sort_id']];
            } elseif(!$item['fid']) {
                $list[$item['sort_id']] = &$tmpMap[$item['sort_id']];
            }
        }
        unset($tmpMap);
        $today = date('w');
        if ($is_hide) {
            foreach ($list as $key => $row) {
                if(strlen($row['week'])){
                    $week_arr = explode(',', $row['week']);
                    if (!in_array($today, $week_arr)) {
                        unset($list[$key]);
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 获取一个店铺下的所有商品分类（只获取前两级分类）
     * @param int $store_id
     * @param string $field需要获取的字段
     * @return array
     */
    public function get_level_sort($store_id,$field=true,$otherWhere = []){
        $where = $otherWhere ?: array();
        $where['status'] = 1;
        $where['level']  = array('lt',3);
        $where['store_id']  = $store_id;
        $string = '(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')';
        $where['_string'] = $string;
        $list = M(_view('Shop_goods_sort'))->field($field)->where($where)->order('`sort` DESC,`sort_id` ASC')->select();
        $first = array();//一级分类
        if($list){
            $second = array();//二级分类
            foreach ($list as $key => $val) {
                $val['sort_name'] = htmlspecialchars_decode($val['sort_name']);
                if($val['level']==1){
                    $first[] = $val;
                }
                if($val['level']==2){
                    $second[] = $val;
                }
            }
            foreach ($first as $k => $v) {
                $first[$k]['son_list'] = array();
                foreach ($second as $sk => $sv) {
                    if($v['sort_id']==$sv['fid']){
                        $first[$k]['son_list'][] = $sv;
                    }
                }
            }
        }
        return $first;
    }
    
    public function getAllChilds($store_id)
    {
        $items = D('Shop_goods_sort')->field(true)->where(array('store_id' => $store_id,'status' => '1'))->order('`sort` DESC,`sort_id` ASC')->select();
        $s_list = array();
        $today = date('w');
        foreach ($items as $value) {
            if ($value['week']!="") {
                $week_arr = explode(',', $value['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
            }
            $value['sort_discount'] = $value['sort_discount'] ? ($value['sort_discount'] / 10) : 0;
            $s_list[$value['sort_id']] = $value;
        }
        
        foreach ($s_list as $row) {
            unset($s_list[$row['fid']]);
        }
        return $s_list;
    }

    public function getFirst($sortId, $store_id)
    {
        if ($item = D('Shop_goods_sort')->field(true)->where(array('store_id' => $store_id, 'fid' => $sortId))->order('sort DESC')->find()) {
            return $this->getFirst($item['sort_id'], $store_id);
        } else {
            return array($sortId);
        }
    }

    /**
     * 根据指定的ID获取相应的父分类id
     */
    public function getIds($sortId, $store_id)
    {
        static $items;
        if (empty($items)) {
            $items = D('Shop_goods_sort')->field(true)->where(array('store_id' => $store_id))->order('sort DESC')->select();
        }
        $idsArray = array();
        foreach ($items as $item) {
            $idsArray[$item['sort_id']] = $item['fid'];
        }
        return array_reverse($this->ids($idsArray, $sortId));//[父ID，子ID, 孙ID, ..., $sortId（指定的ID）]
    }

    private function ids($idsArray, $sortId, $ids = array())
    {
        foreach ($idsArray as $k => $v) {
            if ($k == $v) {
                unset($idsArray[$k]);
            }
        }
        $ids[] = $sortId;
        if (isset($idsArray[$sortId]) && $idsArray[$sortId]) {
            return $this->ids($idsArray, $idsArray[$sortId], $ids);
        } else {
            return $ids;
        }
    }
    
    /**
     * 根据指定的分类ID获取这个分类下的所有子分类ID（这个子分类下面直接包含商品）
     * @param int $sortId
     * @param int $store_id
     * @param int $isOpen 分类状态，默认不检测状态
     * @return array
     */
    public function getAllSonIds($sortId, $store_id, $isShow = false, $isOpen = false,$all_sort=[])
    {
        $condition = array('store_id' => $store_id,'status' => '1');

        // 是否开启
        if ($isOpen) {
            $condition['sort_status'] = 1;
        }

        // 周几显示
        if ($isShow) {
            $where_string = '(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')';
            $condition['_string'] = $where_string;
        }

        if ($all_sort) {
            $items = $all_sort;
        }else{
            $items = D('Shop_goods_sort')->field(true)->where($condition)->order('sort DESC')->select();
        }

        // 以分类id为键值的分类数组
        $tmpMap = array();

        // 所有父分类id
        $idsArray = array();

        $today = date('w');
        foreach ($items as $item) {
            if (strlen($item['week']) && !$isShow) {
                $week_arr = explode(',', $item['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
            }
            $idsArray[$item['sort_id']] = $item['fid'];
            $item['cat_id'] = $item['sort_id'];
            $item['cat_name'] = $item['sort_name'];
            $item['sort_status'] = $item['sort_status'];
            $tmpMap[$item['sort_id']] = $item;
        }

        $list = array();
        foreach ($items as $item) {
            if (!empty(strlen($item['week']) && !$isShow)) {
                $week_arr = explode(',', $item['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
            }
            if ($item['fid'] && isset($tmpMap[$item['fid']])) {
                $tmpMap[$item['fid']]['son_list'][$item['sort_id']] = &$tmpMap[$item['sort_id']];
            } elseif(!$item['fid']) {
                $list[$item['sort_id']] = &$tmpMap[$item['sort_id']];
            }
        }
        
        $allIds = array_reverse($this->ids($idsArray, $sortId));
        
        array_pop($allIds);
        foreach ($allIds as $id) {
            if (isset($list[$id])) {
                if (isset($list[$id]['son_list'])) {
                    $list = $list[$id]['son_list'];
                }
            }
        }
        
        if (isset($list[$sortId]['son_list'])) {
            $this->sonIds($list[$sortId]['son_list'], $ids);
        } else {
            $ids[] = $sortId;
        }
        return $ids;
    }
    
    private function sonIds($list, &$ids)
    {
        foreach ($list as $row) {
            if (isset($row['son_list']) && $row['son_list']) {
                $this->sonIds($row['son_list'], $ids);
            } else {
                $ids[] = $row['sort_id'];
            }
        }
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
    
    public function checkShopDiscount($store_id)
    {
        $sorts = D('Shop_goods_sort')->where(array('store_id' => $store_id, 'sort_discount' => array('gt', 0)))->limit(1)->select();
        if ($sorts) {
            return true;
        } else {
            return false;
        }
    }

    public function getAllFidsBySortId($sortId)
    {
        $sort = $this->find($sortId);
        if ($sort['level'] == 1) {
            return [$sort['fid']];
        } else if ($sort['level'] == 2) {
            $level = $this->alias('l2')
                ->join(C('DB_PREFIX') . 'shop_goods_sort AS l1 ON l1.sort_id = l2.fid AND l2.level = 2')
                ->field('l1.sort_id as level1')
                ->where(['l2.sort_id' => $sortId])
                ->find();
        } else if ($sort['level'] == 3) {
            $level = $this->alias('l3')
                ->join(C('DB_PREFIX') . 'shop_goods_sort AS l2 ON l2.sort_id = l3.fid AND l3.level = 3')
                ->join(C('DB_PREFIX') . 'shop_goods_sort AS l1 ON l1.sort_id = l2.fid AND l2.level = 2')
                ->field('l3.sort_id AS level3,l1.sort_id as level1,l2.sort_id AS level2')
                ->where(['l3.sort_id' => $sortId])
                ->find();
        }
        return array_values($level);
    }

    public function get_show_sort($store_id=0){
        $model = M('Shop_goods_sort');
        if($store_id){
            $where_string = '`store_id`='.$store_id.' and ((`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\'))';
        }else{
            $where_string = '(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')';
        }
        $where['_string'] = $where_string;
        $data = $model->FIELD(`sort_id`)->where($where)->select();

         // 以分类id为键值的分类数组
        $tmpMap = array();

        // 所有父分类id
        $idsArray = array();

        $today = date('w');
        foreach ($data as $item) {
            if (strlen($item['week']) && !$isShow) {
                $week_arr = explode(',', $item['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
            }
            $idsArray[$item['sort_id']] = $item['fid'];
            $tmpMap[$item['sort_id']] = $item;
        }

        $list = array();
        foreach ($items as $item) {
            
            if ($item['fid'] && isset($tmpMap[$item['fid']])) {
                $tmpMap[$item['fid']]['son_list'][$item['sort_id']] = &$tmpMap[$item['sort_id']];
            } elseif(!$item['fid']) {
                $list[$item['sort_id']] = &$tmpMap[$item['sort_id']];
            }
        }
        
        $sort = array();
        foreach($data as $v){
            $sort[] = $v['sort_id'];
        }
        return $sort;
    }

    /**
     * 获得所有正常分类 去掉已关闭的，附属菜的，不在显示时间内的
     * @param int $store_id
     * @param array $sort_ids 分类id数组
     * @param bool $isOpen 状态开启关闭 默认开启
     * @param bool $isSubsidiary 是否附属菜分类 默认是
     * @param bool $inWeek 是在显示时间段内 默认需要
     * @param bool $isAll 返回子分分类还是所有分类 默认子分类
     * @return array
     */
    public function getAllShowChildSortIds($store_id=0,$sort_ids=[], $isOpen=true, $isSubsidiary=true, $inWeek = true, $isAll = false)
    {   
        $condition = [];
        if ($store_id) {
            $condition['store_id'] = $store_id;
        }

        if ($sort_ids) {
            $condition['sort_id'] = ['in',$sort_ids];
        }

        // 分类状态开启
        if ($isOpen) {
            $condition['sort_status'] = 1;
        }

        //排除附属分类
        if ($isSubsidiary) {
            $condition['status'] = 1;
        }

        //星期几显示
        if ($inWeek) {
            $condition['_string'] = '(`is_weekshow`=0) OR (`is_weekshow`=1 AND `week` LIKE \'%'.date('w').'%\')';
        }

        $all_sort = D('Shop_goods_sort')->field('sort_id,level,fid,week,sort_name')->where($condition)->select();
       
        // 获取状态正常的分类三级模式
        $resultList = $this->getTreeSortList($all_sort);

        $list = $resultList['list'];

        // 获取子分类
        $childIds = array();
        foreach ($list as $key => $value) {
            $ids = [];
            if (isset($value['son_list'])) {
                if ($isAll) {// 获得所有分类id
                    $ids[] = $value['sort_id'];
                    $this->getSonIds($value['son_list'], $ids);
                }else{
                    // 只获得子分类id
                    $this->sonIds($value['son_list'], $ids);
                }
            } else {
                $ids[] = $value['sort_id'];
            }

            if ($ids) {
                $childIds = array_merge($childIds,$ids);
            }
        }
        return array_unique($childIds);
    }

    private function getSonIds($list, &$ids)
    {
        foreach ($list as $row) {
            if (isset($row['son_list']) && $row['son_list']) {
                $ids[] = $row['sort_id'];
                $this->sonIds($row['son_list'], $ids);
            } else {
                $ids[] = $row['sort_id'];
            }
        }
    }

    /**
     * 获得树状分类
     * @param int $sortId
     * @param int $store_id
     * @param int $isOpen 分类状态，默认不检测状态
     * @return array
     */
    public function getTreeSortList($items)
    {
        // 以分类id为键值的分类数组
        $tmpMap = array();

        // 所有父分类id
        $idsArray = array();

        $today = date('w');
        foreach ($items as $item) {
            $idsArray[$item['sort_id']] = $item['fid'];
            $item['cat_id'] = $item['sort_id'];
            $item['cat_name'] = $item['sort_name'];
            $item['sort_status'] = $item['sort_status'];
            $tmpMap[$item['sort_id']] = $item;
        }

        $list = array();
        foreach ($items as $item) {
            if (!empty(strlen($item['week']))) {
                $week_arr = explode(',', $item['week']);
                if (!in_array($today, $week_arr)) {
                    continue;
                }
            }
            if ($item['fid'] && isset($tmpMap[$item['fid']])) {
                $tmpMap[$item['fid']]['son_list'][$item['sort_id']] = &$tmpMap[$item['sort_id']];
            } elseif(!$item['fid']) {
                $list[$item['sort_id']] = &$tmpMap[$item['sort_id']];
            }
        }
        return ['list'=>$list,'idsArray'=>$idsArray];
    }

    /**
     * 根据分类id判断父级所有分类id是否展示
     * @param $sortId
     * @author 张涛
     * @date 2020/05/06
     */
    public function testFidIsShowBySortId($sortId)
    {
        $week = date('w');
        $allWeek = range(0, 6);
        $sort = $this->find($sortId);
        if (!$sort) {
            return false;
        }

        if ($sort['level'] == 1) {
            $sortArr = [
                'week1' => $sort['week'],
                'status1' => $sort['status'],
                'sort_status1' => $sort['sort_status'],
                'is_weekshow1' => $sort['is_weekshow']
            ];
        } else if ($sort['level'] == 2) {
            $fields = 'l1.week AS week1,l1.status AS status1,l1.sort_status AS sort_status1,l1.is_weekshow AS is_weekshow1';
            $fields .= ',l2.week AS week2,l2.status AS status2,l2.sort_status AS sort_status2,l2.is_weekshow AS is_weekshow2';
            $sortArr = $this->alias('l2')
                ->join(C('DB_PREFIX') . 'shop_goods_sort AS l1 ON l1.sort_id = l2.fid AND l2.level = 2')
                ->field($fields)
                ->where(['l2.sort_id' => $sortId])
                ->find();

        } else if ($sort['level'] == 3) {
            $fields = 'l1.week AS week1,l1.status AS status1,l1.sort_status AS sort_status1,l1.is_weekshow AS is_weekshow1';
            $fields .= ',l2.week AS week2,l2.status AS status2,l2.sort_status AS sort_status2,l2.is_weekshow AS is_weekshow2';
            $fields .= ',l3.week AS week3,l3.status AS status3,l3.sort_status AS sort_status3,l3.is_weekshow AS is_weekshow3';
            $sortArr = $this->alias('l3')
                ->join(C('DB_PREFIX') . 'shop_goods_sort AS l2 ON l2.sort_id = l3.fid AND l3.level = 3')
                ->join(C('DB_PREFIX') . 'shop_goods_sort AS l1 ON l1.sort_id = l2.fid AND l2.level = 2')
                ->field($fields)
                ->where(['l3.sort_id' => $sortId])
                ->find();
        }
        if (isset($sortArr['week1'])) {
            $sortArr['week1'] = $sortArr['week1'] ? explode(',', $sortArr['week1']) : $allWeek;
        }
        if (isset($sortArr['week2'])) {
            $sortArr['week2'] = $sortArr['week2'] ? explode(',', $sortArr['week2']) : $allWeek;
        }
        if (isset($sortArr['week3'])) {
            $sortArr['week3'] = $sortArr['week3'] ? explode(',', $sortArr['week3']) : $allWeek;
        }

        //循环判断三级分类是否展示
        $tag = true;
        for ($i = 1; $i <= 3; $i++) {
            if (isset($sortArr['week' . $i])) {
                $isStatus = $sortArr['status' . $i] == 1;
                $isSortStatus = $sortArr['sort_status' . $i] == 1;
                $isWeek = ($sortArr['is_weekshow' . $i] == 0 || ($sortArr['is_weekshow' . $i] == 1 && in_array($week, $sortArr['week' . $i])));
                if (!$isStatus || !$isSortStatus || !$isWeek) {
                    $tag = false;
                    break;
                }
            }
        }
        return $tag;
    }
    
    public function syncMiaomaiCategory($storeId, $category, $parentId = 0): bool
    {
        $goodsCategory = D('Shop_goods_sort')
            ->where(['store_id' => $storeId, 'miaomai_category_id' => $category['category_id']])
            ->find();
        
        $saveData = [
            'store_id' => $storeId,
            'sort_name' => $category['category_name'],
            'miaomai_category_id' => $category['category_id'],
            'fid' => $parentId,
            'level' => $category['depth'],
            'sort_status' => 1,
            'status' => 1,
            'is_weekshow' => 1,
            'week' => '1,2,3,4,5,6,0',
        ];
        if(empty($goodsCategory)) {
            D('Shop_goods_sort')->add($saveData);
        }else{
            D('Shop_goods_sort')
                ->where(['store_id' => $storeId, 'miaomai_category_id' => $category['category_id']])
                ->save($saveData);
        }
        if(!empty($category['children'])){
            foreach ($category['children'] as $childCategory){
                $this->syncMiaomaiCategory($storeId, $childCategory, $category['category_id']);
            }
        }
        
        return true;
    }
}
?>
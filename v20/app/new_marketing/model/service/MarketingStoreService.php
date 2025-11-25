<?php
/**
 * 购买店铺记录表
 * User: 衡婷妹
 * Date: 2021/8/24
 */
namespace app\new_marketing\model\service;

use app\merchant\model\service\MerchantStoreService;
use app\new_marketing\model\db\NewMarketingStore;

class MarketingStoreService
{
	public $newMarketingStoreModel = null;

    public function __construct()
    {
        $this->newMarketingStoreModel = new NewMarketingStore();
    }
	/**
	 * 店铺使用情况
	 * @param array $merchantUser 商家信息
	 * @return array
	 */
	public function getStoreUserdDetail($merchantUser){
		$returnArr = [
			'store_count_buy' => 0,// 总数量
			'store_count_used' => 0,// 已使用
			'store_count_unused' => 0,// 未使用
			'category_list' => [],// 已购买的分类列表
		];
		
		$where = [
			'mer_id' => $merchantUser['mer_id'],
		];
		$returnArr['store_count_buy'] = $this->newMarketingStoreModel->where($where)->sum('store_count');
		$returnArr['store_count_used'] = $this->newMarketingStoreModel->where($where)->sum('used_count');
		$returnArr['store_count_unused'] = $returnArr['store_count_buy'] - $returnArr['store_count_used'];

		// 获得每个分类下的店铺数
		$where = [
			'mer_id' => $merchantUser['mer_id'],
		];
		$categoryCountList = $this->newMarketingStoreModel->field('sum(store_count) as store_count,sum(used_count) as used_count,cat_id,cat_fid')->where($where)->group('cat_id')->select();

		if(empty($categoryCountList)){
			return $returnArr;
		}
		$categoryCountList = $categoryCountList->toArray();

		// 正在使用的
		$where = [
			['mer_id', '=', $merchantUser['mer_id']],
			['status', '<>', 4],
			['end_time', '>=', time()]
		];
		$categoryUsingList = (new MerchantStoreService)->getCountByGroup($where, 'cat_id');
		$categoryUsingList = array_column($categoryUsingList, 'count', 'cat_id');
		
		// 已过期的
		$where = [
			['mer_id', '=', $merchantUser['mer_id']],
			['status', '<>', 4],
			['end_time', '<=', time()]
		];
		$categoryOverdueList = (new MerchantStoreService)->getCountByGroup($where, 'cat_id');
		$categoryOverdueList = array_column($categoryOverdueList, 'count', 'cat_id');

		// 分类详情
		$catIdArr = array_column($categoryCountList, 'cat_id');
		$catFidArr = array_column($categoryCountList, 'cat_fid');
		$catIdArr = array_merge($catIdArr, $catFidArr);
		$categoryWhere = [
			['cat_id', 'in', implode(',', $catIdArr)]
		];
		$categoryList = (new MerchantCategoryService)->getSome($categoryWhere,'cat_id,cat_name,cat_pic');
		$categoryNameList = array_column($categoryList, 'cat_name', 'cat_id');
		$categoryPicList = array_column($categoryList, 'cat_pic', 'cat_id');

		foreach($categoryCountList as &$_category){
			$_category['using_count'] = $categoryUsingList[$_category['cat_id']] ?? 0;// 正在使用总数
			$_category['overdue_count'] = $categoryOverdueList[$_category['cat_id']] ?? 0;// 已过期总数
			$_category['unused_count'] = $_category['store_count'] - $_category['used_count'];// 未使用总数
			$_category['cat_name'] = $categoryNameList[$_category['cat_fid']] ?? '';// 分类名
			$_category['child_cat_name'] = $categoryNameList[$_category['cat_id']] ?? '';// 子分类名

			$catPic = $categoryPicList[$_category['cat_id']] ?? '';// 子分类图片
			if($catPic && strpos($catPic,'upload/') !== false) {
				$catPic = replace_file_domain($catPic);
			} elseif($catPic) {
				$catPic = file_domain().'/upload/system'.$catPic;
			}
			$_category['cat_pic'] = $catPic ? : '';// 子分类图片
		}
		$returnArr['category_list'] = $categoryCountList;
		return $returnArr;
	}
	
	/**
	 * 获得某个分类下购买的店铺详情
	 * @param int $catId 分类id
	 * @param int $merId 商家id
	 * @return array
	 */
	public function getCategoryStoreDetail($catId, $merId= 0){
		$returnArr = [
			'type_name' => '',// 分类名
			'unused_list' => [],// 未使用店铺列表
			'unused_count' => 0,// 未使用店铺总数
			'used_list' => [],// 已使用店铺列表
		];

		if(empty($catId)){			
            throw new \think\Exception(L_("缺少参数"), 1001);
		}
		
		// 获得分类详情
		$childCategory = (new MerchantCategoryService())->getOneData(['cat_id' => $catId]);// 子分类
		$categoryName = $childCategory['cat_name'];
		if(!$childCategory){
            throw new \think\Exception(L_("分类不存在"), 1003);
		}

		$category = (new MerchantCategoryService())->getOneData(['cat_id' => $childCategory['cat_fid']]);// 父分类
		if($category && $category['cat_name']){
			$categoryName =  $category['cat_name'] . '-' . $categoryName;
		}
		$returnArr['type_name'] = $categoryName;
		
		// 获得未使用店铺列表
		$res = $this->getUnusedStoreList($catId, $merId);
		$returnArr['unused_count'] = $res['unused_count'];
		$returnArr['unused_list'] = $res['list'];

		// 已使用的店铺列表
		$where = [
			['mer_id', '=', $merId],
			['status', '<>', 4],
		];
		$storeList = (new MerchantStoreService)->getSome($where);
		$storeImageService = new \app\merchant\model\service\storeImageService();
		foreach($storeList as $_store){
			// 店铺图片
			$images = $storeImageService->getAllImageByPath($_store['pic_info']);
			$image = $images[0] ?? '';

			$storeStatus = (new MerchantStoreService)->getStoreStatus($_store['end_time']);
			$returnArr['used_list'][] = [
				'name' => $_store['name'],
				'image' => $image ?: replace_file_domain($_store['logo']),
				'type_name' => $categoryName,
				'store_id' => $_store['store_id'],
				'end_time' => $_store['end_time'] ? date('Y-m-d H:i:s', $_store['end_time']) : '',
				'add_time' => $_store['add_time'] ? date('Y-m-d H:i:s', $_store['add_time']) : '',
				'phone' => $_store['phone'],
				'address' => $_store['adress'],
				'store_status' => $storeStatus,
				'store_status_str' => (new MerchantStoreService)->getStoreStatusStr($storeStatus),
			];
		}

		return $returnArr;
	}

	
	/**
	 * 获得某个分类下未使用的店铺列表
	 * @param int $catId 分类id
	 * @param int $merId 商家id
	 * @return array
	 */
	public function getUnusedStoreList($catId, $merId = 0){
		$where = [];
		$where[] = ['cat_id', '=', $catId];

		if($merId){// 商家id
			$where[] = ['mer_id', '=', $merId];
		}
		$returnArr['unused_count'] = 0;
		$returnArr['list'] = [];

		// 获得未使用店铺列表
		$storeList = $this->newMarketingStoreModel->where($where)->select();
		foreach($storeList as $_store){
			$unusedNum = $_store['store_count'] - $_store['used_count'];// 未使用店铺数量
			$returnArr['unused_count'] += $unusedNum;
			if($unusedNum > 0){
				$returnArr['list'][] = [
					'buy_id' => $_store['id'],
					'years_num' => $_store['years_num'],
					'store_count' => $unusedNum,
					'cat_id' => $_store['cat_id'],
					'cat_fid' => $_store['cat_fid'],

				];
			}
		}

		return $returnArr;
	}

	/**
	 * 获得分类下购买的店铺数量
	 * @param int $merId 商家id
	 * @return array
	 */
	public function getCategoryStoreList($merId = 0){
		$returnArr = [
			'list' => [],// 未使用店铺列表
		];

		// 获得每个分类下的店铺数
		$where = [
			'mer_id' => $merId,
		];
		$categoryCountList = $this->newMarketingStoreModel->field('sum(store_count) as store_count,sum(used_count) as used_count,cat_id,cat_fid')->where($where)->group('cat_id')->select();

		if(empty($categoryCountList)){
			return $returnArr;
		}
		$categoryCountList = $categoryCountList->toArray();

		// 获得分类列表
		$catIdArr = array_column($categoryCountList, 'cat_id');
		$catFidArr = array_column($categoryCountList, 'cat_fid');
		$catIdArr = array_merge($catIdArr, $catFidArr);
		$categoryWhere = [
			['cat_id', 'in', implode(',', $catIdArr)]
		];
		$categoryList = (new MerchantCategoryService)->getSome($categoryWhere,'cat_id,cat_name,cat_pic');

		// 分类名数组
		$categoryNameList = array_column($categoryList, 'cat_name', 'cat_id');
		$categoryPicList = array_column($categoryList, 'cat_pic', 'cat_id');

		foreach($categoryCountList as &$_category){
			$unusedNum = $_category['store_count'] - $_category['used_count'];
			if($unusedNum > 0){
				$catPic = $categoryPicList[$_category['cat_id']] ?? '';// 子分类图片
				if($catPic && strpos($catPic,'upload/') !== false) {
					$catPic = replace_file_domain($catPic);
				} elseif($catPic) {
					$catPic = file_domain().'/upload/system'.$catPic;
				}
				$res = $this->getUnusedStoreList($_category['cat_id'], $merId);
				$returnArr['list'][] = [
					'unused_count' => $unusedNum,
					'unused_list' => $res['list'],
					'cat_name' => $categoryNameList[$_category['cat_fid']] ?? '',// 分类名
					'child_cat_name' => $categoryNameList[$_category['cat_id']] ?? '',// 子分类名
					'cat_pic' => $catPic ?: '',// 子分类图片
				];
			}
		}


		return $returnArr;
	}

    /**
     * 获得多条数据
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
		$start = ($page - 1) * $limit;
		$start = max($start, 0);
        $list =  $this->newMarketingStoreModel->getSome($where,$field,$order,$start,$limit);
        if(!$list) {
            return [];
        }
        return $list->toArray();
    }

	
    /**
     * 获得已使用店铺数量
     * @return array
     */
    public function getUsedStoreNumber($where = []){
        $count =  $this->newMarketingStoreModel->where($where)->sum('used_count');
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 获得店铺总数量
     * @return array
     */
    public function getStoreNumber($where = []){
        $count =  $this->newMarketingStoreModel->where($where)->sum('store_count');
        if(!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * 新增或更新数据
     */
    public function save($param){
        $data = $this->newMarketingStoreModel->where([['mer_id', '=', $param['mer_id']], ['cat_id', '=', $param['cat_id']], ['years_num', '=', $param['years_num']]])->find();
        if ($data) {//更新
//            $param['years_num'] += $data['years_num'];
            $param['store_count'] += $data['store_count'];
            $param['used_count'] += $data['used_count'];
            $res = $this->newMarketingStoreModel->where(['id' => $data['id']])->update($param);
        } else {//新增
            $res = $this->newMarketingStoreModel->insertGetId($param);
        }
        return $res;
    }

}
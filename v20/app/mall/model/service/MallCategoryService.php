<?php
/**
 * 商城商品分类
 * Created by vscode.
 * Author: jjc
 * Date Time: 2020/6/8 10:50
 */

namespace app\mall\model\service;

use app\mall\model\db\MallCategory as MallCategoryModel;

use app\mall\model\db\MallGoods;
use app\mall\model\service\MallCategorySpecService as MallCategorySpecService;
use think\facade\Config;

class MallCategoryService
{

    public $MallCategoryModel = null;

    public function __construct()
    {
        $this->MallCategoryModel = new MallCategoryModel();
        $this->bannerService = new MallGoodsBannerService();
    }

    /**
     * [getNormalList 获取后台设置的推荐栏目]
     * @Author   JJC
     * @DateTime 2020-06-08T14:08:56+0800
     * @param string $deal [针对不同的端口格式化成不同的数据格式]
     * @return   [type]                  [description]
     */
    public function  getNormalList($deal, $type)
    {
        $order = [
            'sort' => 'DESC',
            'cat_id' => 'ASC'
        ];
        $list = $this->MallCategoryModel->getCategoryByCondition(['is_del'=>0,'status'=>1], $order);
        $farr = array();
        $carr = array();
        $garr = array();
        if (!empty($list)) {
            //分离出子数组和父数组
            foreach ($list as $val) {
                $val['image'] = $val ? replace_file_domain($val['image']) : '';
                if ($val['level'] == 1) {
                    $farr[] = $val;
                } else if ($val['level'] == 2) {
                    $carr[] = $val;
                } else {
                    $garr[] = $val;
                }
            }
            foreach ($carr as $key => $val1) {
                foreach ($garr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $carr[$key]['son_list'][] = $val2;
                    }
                }
            }
            foreach ($farr as $key => $val1) {
				//子分类
				$farr[$key]['son_list'] = [];
                foreach ($carr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $farr[$key]['son_list'][] = $val2;
                    }
                }
				
				//banner图
                $banner = (new MallGoodsBannerService())->getBannerList($val1['cat_id'], $type);
                if (!empty($banner)) {
                    foreach ($banner as $k => $v) {
                        $banner[$k]['pic'] = $v['image'];
                        unset($banner[$k]['image']);
                    }
                }
                $farr[$key]['banner'] = $banner ?: '';
            }
            return $farr;
        } else {
            return [];
        }

    }

    /**
     * [dealTree 将列表处理成树状结构]
     * @Author   JJC
     * @DateTime 2020-06-08T16:11:12+0800
     * @param array $list [description]
     * @return   [type]                         [description]
     */
    private function dealTree($list, $type)
    {
        //遍历数组，按照id作为键名重新组建新的数组
        $new_array = [];
        foreach ($list as $v) {
            $v['image'] = $v['image'] ? cfg('site_url').($v['image']) :cfg('site_url').'/20/public/static/mall/default_image.png';
            $new_array[$v['cat_id']] = $v;
        }
        $return_tree = [];
        foreach ($new_array as $kk => $vv) {
            if (isset($new_array[$vv['cat_fid']])) {
                $new_array[$vv['cat_fid']]['son_list'][] = &$new_array[$kk];
            } else {
                //banner图
                $banner = (new MallGoodsBannerService())->getBannerList($vv['cat_id'], $type);
                if(!empty($banner)){
                    foreach ($banner as $k => $v) {
                        $banner[$k]['pic'] = $v['image'];
                        unset($banner[$k]['image']);
                    }
                }
                $new_array[$kk]['banner'] = $banner ?: '';
                $return_tree[] = &$new_array[$kk];
            }
        }
        return $return_tree;
    }

    /**
     * [getLevelDetail 获取所有某一等级分类详情]
     * @Author   JJC
     * @DateTime 2020-06-16T14:38:01+0800
     * @param string $level [description]
     * @return   [type]                          [description]
     */
    public function getLevelDetail($level = 2)
    {
        $where = [
            ['level', '=', $level],
            ['is_del', '=', 0],
            ['status', '=', 1]
        ];
        $categoryList = $list = $this->MallCategoryModel->getList($where);

        if ($categoryList) {
            $MallCategorySpecService = new MallCategorySpecService();
            $specList = $MallCategorySpecService->getAll();
			
			foreach ($categoryList as $key => $val) {
				if (isset($specList[$val['cat_id']])) {
					$categoryList[$key]['spec_list'] = $specList[$val['cat_id']];
				} else {
					$categoryList[$key]['spec_list'] = [];
				}
			}
        }
        return $categoryList;
    }

    /**
     * [getLevel2 获取二级分类填充足5个]
     * @Author   Mrdeng
     * @DateTime 2020-6-29
     * @param string $level [description]
     * @return   [type]                          [description]
     */

    public function getLevel2($list, $count, $level = 2)
    {
        $where = [
            /* ['level','=',$level],
             ['cat_id','not in',$list],
             ['is_del','=',0]*/
        ];
        $mycount = 5 - $count;
        $categoryList = $this->MallCategoryModel->getList2($list, $where, $mycount);
        return $categoryList;
    }

    /**
     * 获取分类列表
     * @param $pageSize
     * @param $page
     * @return array
     * @author zhumengqun
     */
    public function goodsCategoryList($pageSize, $page, $status=0)
    {
        $order = [
            'sort' => 'DESC',
            'cat_id' => 'ASC'
        ];
        $where = ['is_del'=>0];
        if($status){
            $where = ['is_del'=>0,'status'=>1];
        }
        $list = $this->MallCategoryModel->getCategoryByCondition($where, $order);
        $count = $this->MallCategoryModel->getCategoryCount();
        $farr = array();
        $carr = array();
        $garr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                $val['image'] = $val ? replace_file_domain($val['image']) : '';
                if ($val['level'] == 1) {
                    $farr[] = $val;
                } else if ($val['level'] == 2) {
                    $carr[] = $val;
                } else {
                    $garr[] = $val;
                }
            }
            foreach ($carr as $key => $val1) {
                foreach ($garr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $carr[$key]['children'][] = $val2;
                    }
                }
            }
            foreach ($farr as $key => $val1) {
                foreach ($carr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $farr[$key]['children'][] = $val2;
                    }
                }
            }
            $list1['list'] = $farr;
            $list1['count'] =$count;
            return $list1;
        } else {
            return [];
        }
    }

    /**
     * @param $id
     * 设置排序
     */
    public function saveSort($id, $sort)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $res = $this->MallCategoryModel->editCategory(['cat_id' => $id], ['sort' => $sort]);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * @param $id
     * 设置排序
     */
    public function saveStatus($id, $status)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $res = $this->MallCategoryModel->editCategory(['cat_id' => $id], ['status' => $status]);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            $list=$this->MallCategoryModel->getSome(['cat_fid'=>$id])->toArray();
            if(!empty($list)){
                $this->MallCategoryModel->updateThis(['cat_fid'=>$id],['status' => $status]);
                foreach ($list as $k=>$v){
                    $list1=$this->MallCategoryModel->getSome(['cat_fid'=>$v['cat_id']])->toArray();
                    if(!empty($list1)){
                        $this->MallCategoryModel->updateThis(['cat_fid'=>$v['cat_id']],['status' => $status]);
                    }
                }
            }
            return true;
        }
    }

    /**
     * @param $id
     * 设置排序
     */
    public function saveImage($id, $image)
    {
        if (empty($id)) {
            throw new \think\Exception('参数缺失');
        }
        $res = $this->MallCategoryModel->editCategory(['cat_id' => $id], ['image' => $image]);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 平台后台-添加或编辑分类
     * @param $arr
     * @return bool
     * @throws \think\Exception
     * * @author zhumengqun
     */
    public function addOrEditCategory($arr)
    {
        $data = $arr;
        if (!empty($data['cat_id'])) {
            $where = ['cat_id' => $data['cat_id']];
            unset($arr['cat_id']);
            //编辑
            $result = $this->MallCategoryModel->editCategory($where, $data);
//            //新建下级分类，如果父分类存在商品，则移入子分类
//            $goods = (new MallGoodsService())->getSome(['sort_id' => $arr['cat_fid']]);
//            if (!empty($goods)) {
//                (new MallGoods())->updateOne(['cat_id' => $arr['cat_fid']], ['cat_id' => $arr['cat_id']]);
//            }
        } else {
            //新增
            $result = $this->MallCategoryModel->addCategory($data);
//            //新建下级分类，如果父分类存在商品，则移入子分类
//            $goods = (new MallGoodsService())->getSome(['sort_id' => $arr['cat_fid']]);
//            if (!empty($goods) && $result) {
//                (new MallGoods())->updateOne(['cat_id' => $arr['cat_fid']], ['cat_id' => $result]);
//            }
        }
        if ($result !== false) {
            return true;
        } else {
            throw new \think\Exception('操作失败，请重试');
        }
    }

    /**
     * 平台后台-获取编辑的分类信息
     * @param $cat_id
     * @return array
     * @throws \think\Exception
     * * @author zhumengqun
     */
    public function getEditCategory($cat_id)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('cat_id参数缺失');
        }
        $arr = $this->MallCategoryModel->getEditCategory(['cat_id' => $cat_id]);
        if (!empty($arr)) {
            return $arr;
        } else {
           return[];
        }
    }

    /**
     * 平台后台-删除分类
     * @param $cat_id
     * @return bool
     * @throws \think\Exception
     * * @author zhumengqun
     */
    public function delCategory($cat_id, $level)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('cat_id参数缺失');
        }
        //如果存在子分类和该分类下存在商品则不允许删除
        $arr = $this->MallCategoryModel->getCategoryByCondition(['cat_fid' => $cat_id, 'is_del' => 0], '');
        if (!empty($arr)) {
            return 200;//用200标识该分类下存在子分类
        }
        switch ($level) {
            case 1:
                $goods_where = ['g.cate_first' => $cat_id, 'g.is_del'=>0];
                break;
            case 2:
                $goods_where = ['g.cate_second' => $cat_id, 'g.is_del'=>0];
                break;
            case 3:
                $goods_where = ['g.cate_three' => $cat_id, 'g.is_del'=>0];
                break;
            default:
                $goods_where = ['g.cat_id' => $cat_id, 'g.is_del'=>0];
        }
        $noGoods = (new MallGoodsService())->MallGoodsModel->alias('g')
        ->join('merchant_store s', 's.store_id = g.store_id')
        ->where('s.status', '<>', 4)
        ->where($goods_where)
        ->findOrEmpty()
        ->isEmpty();
        //$goods = (new MallGoodsService())->getSome($goods_where);
        if (!$noGoods) {
            return 100; //用100标识该分类下存在商品，无法被删除
        }
        $result = $this->MallCategoryModel->delCategory(['cat_id' => $cat_id]);
        if ($result !== false) {
            return true;
        } else {
            throw new \think\Exception('操作失败，请重试');
        }
    }

    /**
     * 平台后台-获取轮播列表
     * @param $cat_id
     * @param $type
     * @return array
     * @throws \think\Exception
     * * @author zhumengqun
     */
    public function bannerList($cat_id, $type)
    {
        if (empty($cat_id)) {
            throw new \think\Exception('cat_id参数缺失');
        }
        if (empty($type)) {
            throw new \think\Exception('type参数缺失');
        }
        $arr = $this->bannerService->getBannerList($cat_id, $type);
        return $arr;

    }

    /**
     * 平台后台-添加或编辑轮播图
     * @param $arr
     * @return bool
     * @throws \think\Exception
     * * @author zhumengqun
     */
    public function addOrEditBanner($arr)
    {
        if (empty($arr['cat_id'])) {
            throw new \think\Exception('cat_id参数缺失');
        }
        if (empty($arr['image'])) {
            throw new \think\Exception('iamge参数缺失');
        }else{
            $arr['image'] = '/upload/'.explode('/upload/',$arr['image'])[1];
        }
        if (empty($arr['type'])) {
            throw new \think\Exception('type参数缺失');
        }
        $result = $this->bannerService->addOrEditBanner($arr);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 平台后台-删除轮播图
     * @param $id
     * @return bool
     * @throws \think\Exception
     * * @author zhumengqun
     */
    public function delBanner($id)
    {
        if (empty($id)) {
            throw new \think\Exception('id参数缺失');
        }
        $result = $this->bannerService->delBanner($id);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }
}
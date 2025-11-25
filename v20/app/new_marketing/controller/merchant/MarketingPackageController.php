<?php
/**
 * liuruofei
 * 2021/08/30
 * 套餐管理
 */
namespace app\new_marketing\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\new_marketing\model\db\NewMarketingClassRegion;
use app\new_marketing\model\service\ClassPriceService;
use app\new_marketing\model\service\MarketingPackageRegionService;
use app\new_marketing\model\service\RegionService;
use app\new_marketing\model\service\MerchantCategoryService;
use app\new_marketing\model\db\MerchantCategory;

class MarketingPackageController extends AuthBaseController
{

    /**
     * 套餐列表
     */
    public function getSearchList(){
        $param = [
            ['a.status', '=', 1],
            ['b.is_del', '=', 0]
        ];
        $where = [
            ['is_del', '=', 0],
            ['status', '=', 1]
        ];
        if (!$this->merchantUser['province_id']) {
            $where[] = ['region_id', '=', '[]'];
        }
        $regionList = (new RegionService())->getAllProvinceIds($where);
        if (!$regionList) {
            $data = [
                'money' => $this->merchantUser['money'],
                'currency' => cfg('Currency_symbol'),//货币符号
                'data' => []
            ];
            return api_output(0, $data, 'success');//该商家区域没有套餐信息
        }
        $a = 0;
        $regionid = 0;
        foreach ($regionList as $k => $v) {
            if (!$this->merchantUser['province_id']) {
                $a = 1;
                $param[] = ['a.region_id', '=', $v['id']];
                break;
            }
            $region_id = json_decode($v['region_id'], true);
            if (empty($region_ids)) {
                $regionid = $v['id'];
            }
            if (in_array($this->merchantUser['province_id'], $region_id)) {
                $a = 1;
                $param[] = ['a.region_id', '=', $v['id']];
                break;
            }
        }
        if ($a == 0) {
            if ($regionid > 0) {
                $param[] = ['a.region_id', '=', $regionid];
            } else {
                $data = [
                    'money' => $this->merchantUser['money'],
                    'currency' => cfg('Currency_symbol'),//货币符号
                    'data' => []
                ];
                return api_output(0, $data, 'success');//该商家区域没有套餐信息
            }
        }
        try {
            $field = 'a.*,b.name,b.store_detail,b.all_num,b.remark';
            $list = (new MarketingPackageRegionService())->getMerSearchList($param, $field);
            $data = [
                'money' => $this->merchantUser['money'],
                'currency' => cfg('Currency_symbol'),//货币符号
                'data' => $list
            ];
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 套餐详情
     */
    public function getpackageDetail(){
        $id = $this->request->param('id', 0, 'intval');//区域套餐ID
        if (!$id) {
            return api_output_error(1003, '区域套餐ID不存在');
        }
        try {
            $field = 'a.*,b.name,b.store_detail,b.all_num,b.remark';
            $list = (new MarketingPackageRegionService())->getDetailData(['a.id' => $id], $field);
            $data = [
                'money' => $this->merchantUser['money'],
                'currency' => cfg('Currency_symbol'),//货币符号
                'data' => $list
            ];
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 分类列表(一级)
     */
    public function getCatList(){
        $where = [
            ['is_del', '=', 0],
            ['status', '=', 1]
        ];
        if (!$this->merchantUser['province_id']) {
            return api_output(0, [], 'success');//该商家没有区域信息
        }
        $regionList = (new RegionService())->getAllProvinceIds($where);
        if (!$regionList) {
            return api_output(0, [], 'success');//该商家区域没有套餐信息
        }
        $region_id = 0;
        $regionid = 0;
        foreach ($regionList as $v) {
            $region_ids = json_decode($v['region_id'], true);
            if (empty($region_ids)) {
                $regionid = $v['id'];
            }
            if (in_array($this->merchantUser['province_id'], $region_ids)) {
                $region_id = $v['id'];
                break;
            }
        }
        if ($region_id == 0) {
            $region_id = $regionid;
        }
        $where = [
            ['cat_status', '=', 1],
            ['cat_fid', '=', 0]
        ];
        try {
            $fcatIds = (new MerchantCategoryService)->getStoreFcatIds($where);//获取全部一级分类ID
            $param = [
                ['region_id', '=', $region_id],
                ['is_del', '=', 0],
                ['status', '=', 1],
                ['class_id', 'in', $fcatIds]
            ];
            $list = (new ClassPriceService())->getClassList($param);
            $arr = [];
            if ($list) {
                foreach ($list as $k => $v) {
                    $arr[$k]['value'] = $v['class_id'];
                    $arr[$k]['label'] = (new MerchantCategory)->where(['cat_id' => $v['class_id']])->value('cat_name');
                }
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 分类店铺列表
     */
    public function getCatSearchList(){
        $id = $this->request->param('id', 0, 'intval');//一级分类ID
        if (!$id) {
            return api_output_error(1003, '分类ID不存在');
        }
        $where = [
            ['cat_status', '=', 1],
            ['cat_fid', '=', $id]
        ];
        try {
//            $catList = (new MerchantCategoryService())->getStoreCatList($where);
            $param = [
                ['is_del', '=', 0],
                ['status', '=', 1]
            ];
            $where1 = [
                ['is_del', '=', 0],
                ['status', '=', 1]
            ];
            if (!$this->merchantUser['province_id']) {
                $where1[] = ['region_id', '=', '[]'];
            }
            $regionList = (new RegionService())->getAllProvinceIds($where1);
            if (!$regionList) {
                return api_output(0, [], 'success');//该商家区域没有分类店铺信息
            }
            $a = 0;
            $regionid = 0;
            foreach ($regionList as $v) {
                if (!$this->merchantUser['province_id']) {
                    $a = 1;
                    $param[] = ['region_id', '=', $v['id']];
                    break;
                }
                $region_id = json_decode($v['region_id'], true);
                if (empty($region_ids)) {
                    $regionid = $v['id'];
                }
                if (in_array($this->merchantUser['province_id'], $region_id)) {
                    $a = 1;
                    $param[] = ['region_id', '=', $v['id']];
                    break;
                }
            }
            if ($a == 0) {
                if ($regionid > 0) {
                    $param[] = ['region_id', '=', $regionid];
                } else {
                    return api_output(0, [], 'success');//该商家区域没有分类店铺信息
                }
            }
            $catIds = (new MerchantCategoryService)->getStoreFcatIds([['cat_fid', '=', $id], ['cat_status', '=', 1]]);//获取全部一级下二级分类ID
//            dump($catIds);die;
//            $catList = (new ClassPriceService())->getClassList(array_merge($param, [['class_id', 'in', $catIds]]));
            $fdata = (new ClassPriceService())->getClassData(array_merge($param, [['class_id', '=', $id]]));
//            dump($fdata);die;
            $list = [];
            if ($catIds) {
                foreach ($catIds as $v) {
                    $data = (new ClassPriceService())->getClassData(array_merge($param, [['class_id', '=', $v]]));
                    $catData = (new MerchantCategory)->where(['cat_id' => $v])->find();
                    if ($data && $data['discount_type'] != 0) {
                        $data = $data->toArray();
                        $data['name'] = $catData['cat_name'] . ' 店铺' ?? '';
                        $data['cat_img'] = replace_file_domain($catData['cat_pic']) ?? '';
                        $list[] = $data;
                    } else if ($fdata && $fdata['discount_type'] != 0) {
                        $fdata1 = $fdata->toArray();
                        if (!$data) {
                            $fdata1['id'] = (new NewMarketingClassRegion())->insertGetId([
                                'class_id' => $v,
                                'region_id' => $fdata['region_id'],
                                'add_time' => time()
                            ]);
                        } else {
                            $fdata1['id'] = $data['id'];
                        }
                        $fdata1['class_id'] = $v;
                        $fdata1['name'] = $catData['cat_name'] . ' 店铺' ?? '';
                        $fdata1['cat_img'] = replace_file_domain($catData['cat_pic']) ?? '';
                        $list[] = $fdata1;
                    }
                }
//            } else if ($fdata && $fdata['discount_type'] != 0) {
//                $catData = (new MerchantCategory)->where(['cat_id' => $id])->find();
//                $fdata['name'] = $catData['cat_name'] . ' 店铺' ?? '';
//                $fdata['cat_img'] = replace_file_domain($catData['cat_pic']) ?? '';
//                $list[] = $fdata;
            }
            $list = array_values($list);
            return api_output(0, $list, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 分类店铺详情
     */
    public function getCatDetail(){
        $id = $this->request->param('id', 0, 'intval');//区域分类店铺ID
        if (!$id) {
            return api_output_error(1003, '区域分类店铺ID不存在');
        }
        try {
            $list = (new ClassPriceService())->getClassDetailData([['id', '=', $id]]);
            $data = [
                'money' => $this->merchantUser['money'],
                'currency' => cfg('Currency_symbol'),//货币符号
                'data' => $list
            ];
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 计算优惠和付款金额(套餐和分类店铺共用)
     */
    public function getDiscountPayPrice() {
        $id = $this->request->param('id', 0, 'intval');
        $year = $this->request->param('year', 1, 'intval');
        $num = $this->request->param('num', 1, 'intval');
        $type = $this->request->param('type', 1, 'intval');//1=购买套餐,2=购买分类店铺
        if (!$id) {
            return api_output_error(1003, '区域套餐ID不存在');
        }
        if ($year > 5) {
            return api_output_error(1003, '年限不得大于5');
        }
        if ($num <= 0) {
            return api_output_error(1003, '购买数量必须大于0');
        }
        try {
            if ($type == 2) {
                $data = (new ClassPriceService())->getDiscountPayPrice(['id' => $id], $year, $num);
            } else {
                $data = (new MarketingPackageRegionService())->getDiscountPayPrice(['id' => $id], $year, $num);
            }
            return api_output(0, $data, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}
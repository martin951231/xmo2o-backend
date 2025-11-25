<?php
/**
 * 系统后台广告分类服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 09:22
 */

namespace app\common\model\service;
use app\common\model\db\Adver;
use app\common\model\db\AdverCategory as AdverCategoryModel;
use app\mall\model\service\AppOtherService;
use app\mall\model\service\WxappOtherService;

class AdverCategoryService {
    public $adverCategoryModel = null;
    public $Adver = null;
    public $areaService = null;
    public $appOtherService = null;
    public $wxappOtherService = null;
    public function __construct()
    {
        $this->adverCategoryModel = new AdverCategoryModel();
        $this->Adver = new Adver();
        $this->areaService = new AreaService();
        $this->wxappOtherService = new WxappOtherService();
        $this->appOtherService = new AppOtherService();
    }
   
    /**
     * 根据分类key获广告分类
     * @param $catKey
     * @return array
     */
    public function getAdverCategoryByCatKey($catKey) {
        $cat = $this->adverCategoryModel->getAdverCategoryByCatKey($catKey);
        if(!$cat) {
            return [];
        }
        return $cat->toArray();
    }

    /**
     * 获取列表
     * @param $cat_key
     * @param $systemUser
     * @return array
     * @throws \think\Exception
     */
    public function getList($cat_key, $systemUser)
    {
        if (empty($cat_key)) {
            throw new \think\Exception('缺少cat_key参数');
        }
        $where_cat = ['cat_key' => $cat_key];
        $now_category = $this->adverCategoryModel->getById(true, $where_cat);
        $arr['now_category'] = $now_category;
        $many_city = cfg('many_city');
        $where = [['cat_id', '=', $now_category['cat_id']]];
        if ($systemUser['area_id']) {
            $area_id = $systemUser['area_id'];
            if ($systemUser['level'] == 1) {
                $temp = $this->areaService->getOne(['area_id' => $systemUser['area_id']]);
                if ($temp['area_type'] == 1) {
                    $city_list = $this->areaService->getAreaListByCondition(['area_pid' => $temp['area_id']]);
                    $area_id = array();
                    foreach ($city_list as $value) {
                        $area_id[] = $value['area_id'];
                    }
                } else if ($temp['area_type'] == 2) {
                    $area_id = $temp['area_id'];
                } else {
                    $area_id = $temp['area_pid'];
                }
            }
            if (is_array($area_id)) {
                array_push($where, ['city_id', 'in', $area_id]);
            } else {
                array_push($where, ['city_id', '=', $area_id]);
            }
        }
        $order = ['sort' => 'DESC', 'id' => 'DESC'];
        $adver_list = $this->Adver->getByCondition(true, $where, $order);
        if (!empty($adver_list)) {
            if ($many_city == 1 && !empty($adver_list)) {
                foreach ($adver_list as $key => $v) {
                    $city = $this->areaService->getOne(['area_id' => $v['city_id']]);
                    if (empty($city)) {
                        $adver_list[$key]['area_name'] = '通用';
                    } else {
                        $adver_list[$key]['area_name'] = $city['area_name'];
                    }
                }
            }
            //处理图片
            foreach ($adver_list as $key => $v) {
                $adver_list[$key]['pic'] = $v['pic'] ? replace_file_domain($v['pic']) : '';
                $adver_list[$key]['last_time'] = date('Y-m-d H:i:s', $adver_list[$key]['last_time']);
            }
            $arr['adver_list'] = $adver_list;
            $arr['many_city'] = $many_city;
        }
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     *删除
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function getDel($id)
    {
        if (empty($id)) {
            throw new \think\Exception('缺少id参数');
        }
        $where = ['id' => $id];
        $res = $this->Adver->getDel($where);
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**
     * 编辑或参看时的一些参数
     * @param $id
     * @return array
     * @throws \think\Exception
     */
    public function getEdit($id)
    {
        if (!empty($id)) {
            $where = ['id' => $id];
            $now_adver = $this->Adver->getById(true, $where);
            if (!empty($now_adver)) {
                $now_adver['pic'] = $now_adver['pic'] ? replace_file_domain($now_adver['pic']) : '';
            }
            $arr['now_adver'] = $now_adver;
            if (empty($now_adver)) {
                throw new \think\Exception('该广告不存在');
            }
            $where_cat = ['cat_id' => $now_adver['cat_id']];
            $now_category = $this->adverCategoryModel->getById(true, $where_cat);
            $arr['now_category'] = $now_category;
        }
        $many_city = cfg('many_city');
        $arr['many_city'] = $many_city;
        //小程序列表
        $wxapp_list = $this->wxappOtherService->getAll();
        $arr['wxapp_list'] = $wxapp_list;
        //ios列表
        $app_list = $this->appOtherService->getAll();
        $arr['app_list'] = $app_list;
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 编辑或新增
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addOrEdit($param)
    {
        if (empty($param['name'])) {
            throw new \think\Exception('缺少name参数');
        }
//        if (empty($param['url'])) {
//            throw new \think\Exception('缺少url参数');
//        }
        if (empty($param['cat_key'])) {
            throw new \think\Exception('缺少cat_key参数');
        }
//        if ($param['currency'] == 1) {
//            $param['province_id'] = 0;
//            $param['city_id'] = 0;
//        } else {
//            if (!empty($param['areaList'])) {
//                $param['province_id'] = $param['areaList'][0];
//                $param['city_id'] = $param['areaList'][1];
//            } else {
//                $param['currency'] == 1;
//                $param['province_id'] = 0;
//                $param['city_id'] = 0;
//            }
//        }
        //没图片使用默认图片地址
        if (empty($param['pic'])) {
            $param['pic'] = '/v20/public/static/mall/mall_platform_default_decorate.png';
        }
        unset($param['areaList']);
        $where_cat = ['cat_key' => $param['cat_key']];
        $now_category = $this->adverCategoryModel->getById(true, $where_cat);
        $param['cat_id'] = $now_category['cat_id'];
        if (empty($param['cat_id'])) {
            throw new \think\Exception('缺少cat_id参数');
        }
        unset($param['cat_key']);
        // app打开其他小程序需要获取原始id
        if ($param['app_wxapp_id']) {
            $wxapp = $this->wxappOtherService->getById(true, ['appid' => $param['app_wxapp_id']]);
            $param['app_wxapp_username'] = $wxapp['username'];
        }

        $param['last_time'] = time();
        $param['url'] = htmlspecialchars_decode($param['url']);

        if (empty($param['id'])) {
            //添加
            $res = $this->Adver->addOne($param);
        } else {
            //编辑
            if (stripos($param['pic'], 'http') !== false) {
                $param['pic'] = '/upload/' . explode('/upload/', $param['pic'])[1];
            }
            $res = $this->Adver->editOne(['id' => $param['id']], $param);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

}
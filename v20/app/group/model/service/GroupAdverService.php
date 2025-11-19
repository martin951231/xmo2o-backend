<?php
/**
 * 新版团购广告
 * Author: 钱大双
 * Date Time: 2021-1-20 14:49:09
 */

namespace app\group\model\service;

use app\group\model\db\GroupAdver as GroupAdverModel;
use app\group\model\db\GroupAdverCategory as GroupAdverCategoryModel;
use app\common\model\service\AreaService;

class GroupAdverService
{
    public $groupAdverModel = null;

    public function __construct()
    {
        $this->groupAdverModel = new GroupAdverModel();
    }

    /**广告列表
     * @param $param
     * @param $systemUser
     * @return array
     * @throws \think\Exception
     */
    public function getAdverList($param, $systemUser)
    {
        $now_cat_id = $param['now_cat_id'] ?? '0';//团购广告模块自增ID
        $cat_id = $param['cat_id'] ?? '0';
        $cat_name = $param['cat_name'] ?? '';
        $location = $param['location'] ?? '0';
        $cat_key = $param['cat_key'] ?? '';
        $size = $param['size'] ?? '';
        if ($now_cat_id == 0) {
            $where_cat = [
                ['cat_id', '=', $cat_id],
                ['location', '=', $location],
                ['cat_key', '=', $cat_key],
            ];
        } else {
            $where_cat = [
                ['id', '=', $now_cat_id]
            ];
        }
        if (empty($now_cat_id) && empty($cat_key)) {
            throw new \think\Exception('缺少cat_key参数');
        }

        $now_category = (new GroupAdverCategoryModel())->getOne($where_cat);
        $adver_list = [];
        $many_city = 1;
        if (empty($now_category)) {
            $add_data = [];
            $add_data['cat_id'] = $cat_id;
            $add_data['cat_name'] = $cat_name;
            $add_data['location'] = $location;
            $add_data['cat_key'] = $cat_key;
            $add_data['size_info'] = $size;
            $add_data['cat_type'] = 0;
            $now_cat_id = (new GroupAdverCategoryModel())->addData($add_data);
        } else {
            $now_cat_id = $now_category['id'];
            $arr['now_category'] = $now_category;
            $many_city = cfg('many_city');
            $where = [['cat_id', '=', $now_category['id']]];
            if ($systemUser['area_id']) {
                $area_id = $systemUser['area_id'];
                if ($systemUser['level'] == 1) {
                    $temp = (new areaService())->getOne(['area_id' => $systemUser['area_id']]);
                    if ($temp['area_type'] == 1) {
                        $city_list = (new areaService())->getAreaListByCondition(['area_pid' => $temp['area_id']]);
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
            $adver_list = $this->groupAdverModel->getSome($where, true, $order);
            if (!empty($adver_list)) {
                if ($many_city == 1 && !empty($adver_list)) {
                    foreach ($adver_list as $key => $v) {
                        $city = (new areaService())->getOne(['area_id' => $v['city_id']]);
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
            }
        }
        $returnArr = [];
        $returnArr['adver_list'] = $adver_list;
        $returnArr['many_city'] = $many_city;
        $returnArr['now_cat_id'] = $now_cat_id;
        return $returnArr;
    }

    /**添加广告
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function addGroupAdver($param)
    {
        $id = $param['id'] ?? 0;
        $name = $param['name'] ?? '';
        $currency = $param['currency'] ?? 1;
        $url = $param['url'] ?? '';
        $sort = $param['sort'] ?? 0;
        $status = $param['status'] ?? 0;
        $cat_id = $param['cat_id'] ?? 0;
        $pic = $param['pic'] ?? '';
        $areaList = $param['areaList'] ?? [];
        if (empty($name)) {
            throw new \think\Exception('缺少name参数');
        }
        if (empty($url)) {
            throw new \think\Exception('缺少url参数');
        }
        if ($id == 0 && empty($cat_id)) {
            throw new \think\Exception('缺少cat_id参数');
        }

        $data = [];
        $data['name'] = $name;
        $data['sort'] = $sort;
        $data['status'] = $status;
        $data['currency'] = $currency;
        if ($currency == 1) {
            $data['province_id'] = 0;
            $data['city_id'] = 0;
        } else {
            if (!empty($areaList)) {
                $data['province_id'] = $areaList[0];
                $data['city_id'] = $areaList[1];
            } else {
                $data['province_id'] = 0;
                $data['city_id'] = 0;
            }
        }
        //没图片使用默认图片地址
        if (empty($pic)) {
            $data['pic'] = '/v20/public/static/mall/mall_platform_default_decorate.png';
        } else {
            if (stripos($pic, 'http') !== false) {
                $data['pic'] = '/upload/' . explode('/upload/', $pic)[1];
            }else{
				$data['pic'] = $pic;
			}
        }
        $data['last_time'] = time();
        $data['url'] = htmlspecialchars_decode($url);
        if ($id == 0) {//添加
            $info = $this->getOne($where = ['name' => $name, 'cat_id' => $cat_id, 'status' => 1]);
            if (!empty($info)) {
                throw new \think\Exception('名称已存在');
            }
            $data['cat_id'] = $cat_id;
            $res = $this->groupAdverModel->add($data);
        } else {
            $info = $this->getOne($where = ['name' => $name, 'cat_id' => $cat_id, 'status' => 1]);
            if (!empty($info) && $info['id'] != $id) {
                throw new \think\Exception('名称已存在');
            }
            $res = $this->groupAdverModel->updateThis($where = ['id' => $id], $data);
        }
        if ($res === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return true;
        }
    }

    /**删除广告
     * @param $param
     * @return bool
     * @throws \think\Exception
     */
    public function delGroupAdver($param)
    {
        $id = $param['id'] ?? '0';
        $res = $this->groupAdverModel->del($where = ['id' => $id]);
        return $res;
    }

    public function getEditAdver($param)
    {
        $id = $param['id'] ?? '0';
        $now_adver = $this->groupAdverModel->getOne($where = ['id' => $id]);
        if (!empty($now_adver)) {
            $now_adver['pic'] = $now_adver['pic'] ? replace_file_domain($now_adver['pic']) : '';
        }
        if ($now_adver['currency'] == 0) {
            $now_adver['area'] = [$now_adver['province_id'], $now_adver['city_id']];
        }
        $arr['now_adver'] = $now_adver;
        if (empty($now_adver)) {
            throw new \think\Exception('该广告不存在');
        }
        return $arr;
    }


    public function getCount($where)
    {
        $count = $this->groupAdverModel->getCount($where);
        return $count;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = [])
    {
        if (empty($where)) {
            return false;
        }

        $result = $this->groupAdverModel->getOne($where, $order);
        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取广告数据
     * @param $where array
     * @return array
     */
    public function getSome($where = [], $field = true, $order = true)
    {
        if (empty($where)) {
            return [];
        }

        $result = $this->groupAdverModel->getSome($where, $field, $order);

        if (empty($result)) {
            return [];
        }

        return $result->toArray();
    }
}
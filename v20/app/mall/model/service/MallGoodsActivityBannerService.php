<?php
/**
 * 平台后台-活动商品推荐-轮播图service
 * Create on 2020/11/16 10:26
 * Created by chenxiang
 */

namespace app\mall\model\service;

use app\common\model\service\UploadFileService;
use app\mall\model\db\MallGoodsActivityBanner;

class MallGoodsActivityBannerService
{
    public function __construct(array $data = [])
    {
        $this->goodsActivityBannerModel = new MallGoodsActivityBanner();
    }

    /**
     * 获取轮播列表
     * @param $cat_id
     * @param $act_type
     * @return array
     */
    public function getBannerList($act_type)
    {
        if (empty($act_type)) {
            throw new \think\Exception('act_type参数缺失');
        }
        $where = [
            'act_type' => $act_type,
            'is_del' => 0
        ];
        $field = 'id,image,url,sort,click_number';
        $arr = $this->goodsActivityBannerModel->getBannerList($where, $field);
        if (!empty($arr)) {
			foreach($arr as $key=>$value){
				$arr[$key]['image'] = replace_file_domain($value['image']);
                //浏览量
                $click_number = (int)$value['click_number'];
                $arr[$key]['click_number'] = $click_number >= 10000 ? round(($click_number / 10000), 1) . '万' : $click_number;
			}
            return $arr;
        } else {
            return [];
        }
    }

    /**
     * 添加 / 编辑轮播图
     * User: chenxiang
     * Date: 2020/11/16 17:45
     * @param $param
     * @return MallGoodsActivityBanner|int|string
     */
    public function addOrEditBanner($param)
    {
        if (empty($param['act_type'])) {
            throw new \think\Exception('act_type参数缺失');
        }
        if (empty($param['image'])) {
            throw new \think\Exception('iamge参数缺失');
        }

        if (!empty($param['id'])) {
            //编辑
            $where = ['id' => $param['id']];
            $result = $this->goodsActivityBannerModel->updateBanner($param, $where);
        } else {
            //添加
            unset($param['id']);
            if (!empty($param['image']) && isset($param['image']['img'])) {
                $param['image']=$param['image']['img'];
            }
            $result = $this->goodsActivityBannerModel->addBanner($param);
        }
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }

    }

    /**
     * 删除轮播图
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
    public function delBanner($where)
    {
        $result = $this->goodsActivityBannerModel->delBanner($where);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }
    }
}
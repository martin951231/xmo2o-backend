<?php
/**
 * GoodsBannerService.php
 * 平台后台-分类管理-轮播图service
 * Create on 2020/9/14 10:26
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\service\UploadFileService;
use app\mall\model\db\GoodsCategoryBanner;

class GoodsBannerService
{
    public function __construct(array $data = [])
    {
        $this->goodsBannerModel = new GoodsCategoryBanner();
    }

    public function getBannerList($cat_id, $type)
    {
        $where = [
            'cat_id' => $cat_id,
            'type' => $type
        ];
        $field = 'id,image,url';
        $arr = $this->goodsBannerModel->getBannerList($where, $field);
        if (!empty($arr)) {
            return $arr;
        } else {
            return [];
        }

    }

    public function addOrEditBanner($arr)
    {
        //先存图片
        $uploadBanner = new UploadFileService();
        $savepath = $uploadBanner->uploadPictures($arr['image'], '/mall/goods_banner');
        $arr['image'] = $savepath;
        if (!empty($arr['id'])) {
            //编辑
            $where = ['id' => $arr['id']];
            $result = $this->goodsBannerModel->updateBanner($arr, $where);
        } else {
            //添加
            $result = $this->goodsBannerModel->addBanner($arr);
        }
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }

    }

    public function delBanner($id)
    {
        $where = ['id' => $id];
        $result = $this->goodsBannerModel->delBanner($where);
        if ($result === false) {
            throw new \think\Exception('操作失败，请重试');
        } else {
            return $result;
        }
    }
}
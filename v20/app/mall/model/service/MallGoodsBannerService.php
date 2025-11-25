<?php
/**
 * MallGoodsBannerService.php
 * 平台后台-分类管理-轮播图service
 * Create on 2020/9/14 10:26
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\common\model\service\UploadFileService;
use app\mall\model\db\MallGoodsCategoryBanner;

class MallGoodsBannerService
{
    public function __construct(array $data = [])
    {
        $this->goodsBannerModel = new MallGoodsCategoryBanner();
    }

    /**
     * 获取轮播列表
     * @param $cat_id
     * @param $type
     * @return array
     */
    public function getBannerList($cat_id, $type)
    {
        $where = [
            'cat_id' => $cat_id,
            'type' => $type
        ];
        $field = 'id,image,url,sort,click_number';
        $arr = $this->goodsBannerModel->getBannerList($where, $field);
        if (!empty($arr)) {
			foreach($arr as $key=>$value){
                $arr[$key]['image'] = $value['image'] ? replace_file_domain($value['image']) : '';
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
     * 添加或编辑轮播图
     * @param $arr
     * @return MallGoodsCategoryBanner|int|string
     * @throws \think\Exception
     */
    public function addOrEditBanner($arr)
    {
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

    /**
     * 删除轮播图
     * @param $id
     * @return bool
     * @throws \think\Exception
     */
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
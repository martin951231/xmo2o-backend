<?php
/**
 * 系统后台导航分类服务
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/21 11:40
 */

namespace app\common\model\service;
use app\common\model\db\SliderCategory as SliderCategoryModel;
class SliderCategoryService {
    public $sliderCategoryModel = null;
    public function __construct()
    {
        $this->sliderCategoryModel = new SliderCategoryModel();
    }
   
    /**
     * 根据分类key获取导航分类
     * @param $catKey
     * @return array
     */
    public function getSliderCategoryByCatKey($catKey) {
        $sliderCategory = $this->sliderCategoryModel->getSliderCategoryByCatKey($catKey);
        if(!$sliderCategory) {
            return [];
        }
        return $sliderCategory->toArray();
    }
}
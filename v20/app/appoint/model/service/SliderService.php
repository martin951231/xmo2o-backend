<?php

declare (strict_types=1);

namespace app\appoint\model\service;

use app\appoint\model\db\Slider;
use app\appoint\model\db\SliderCategory;

class SliderService
{
    protected $cateId;

    public function __construct()
    {
        $this->cateId = SliderCategory::getSliderId();
    }

    public function appointSliderList()
    {
        $sliderList = Slider::where(['cat_id' => $this->cateId])
            ->with(['area_name' => function ($query) {
                $query->withField(['area_id', 'area_name']);
            }])
            ->field('id,name,province_id,city_id, shop_id, url, pic, cat_id, status,last_time, sort, city_ids')
            ->order('sort DESC, id DESC')
            ->paginate(min(100,input('page_size')))
            ->each(function ($item) {
                $item->pic = file_domain() . "/upload/slider/$item->pic";

                if (empty($item->city_id)) {
                    $item->area_name = '通用';
                } else {
                    $areaName = $item->area_name->area_name;
                    unset($item->area_name);
                    $item->area_name = $areaName;
                }
            });

        return $sliderList;
    }

    public function appointSliderDetail(array $params)
    {
        $slider = Slider::where([
            'id'     => $params['slider_id'],
            'cat_id' => $this->cateId
        ])
            ->field('id,name,province_id,city_id, shop_id, url, pic, cat_id, status,last_time, sort, city_ids,
                    wxapp_id, wxapp_page, wxapp_open_type')
            ->order('sort DESC, id DESC')
            ->find();

        if (!empty($slider)) {
            $slider->pic = file_domain() . "/upload/slider/$slider->pic";
        }

        return $slider;
    }

    public function appointSliderAddOrEdit(array $params)
    {
        $params['cat_id']    = $this->cateId;
        $params['last_time'] = time();
        unset($params['id']);
        
        if(!empty($params['pic'])){
            $params['pic'] = str_replace(file_domain(), '', $params['pic']);
            $params['pic'] = str_replace('/upload/slider/', '', $params['pic']);
        }
        
        if (!empty($params['slider_id'])) {
            $slider = Slider::where([
                'id'     => $params['slider_id'],
                'cat_id' => $this->cateId
            ])->find();
        }
        empty($slider) && $slider = new Slider();

        return $slider->data($params)->save();
    }

    public function appointSliderDel(array $params)
    {
        $deleteNums = Slider::where([
            'id'     => $params['slider_id'],
            'cat_id' => $this->cateId
        ])->delete();

        if (empty($deleteNums)) {
            throw_exception(L_('当前导航ID错误！'));
        }

        return true;
    }
}
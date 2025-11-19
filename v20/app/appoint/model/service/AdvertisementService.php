<?php

declare (strict_types=1);

namespace app\appoint\model\service;

use app\appoint\model\db\Adver;
use app\appoint\model\db\AdverCategory;

class AdvertisementService
{
    protected $cateId;

    public function __construct()
    {
        $this->cateId = AdverCategory::getAdverId();
    }

    public function advertisementList()
    {
        $adverList = Adver::where(['cat_id' => $this->cateId])
            ->with(['area_name' => function ($query) {
                $query->withField(['area_id', 'area_name']);
            }])
            ->field('id,name,province_id,city_id, shop_id, complete, url, pic, cat_id, status,last_time, 
                    sub_name, sort, type, city_ids, begin_time, end_time, visit_num, click_num'
            )
            ->order('sort DESC, id DESC')
            ->paginate(min(100,input('page_size')))
            ->each(function ($item) {
                $item->pic = file_domain() . "/upload/adver/$item->pic";

                if (empty($item->city_id)) {
                    $item->area_name = '通用';
                } else {
                    if($item->area_name){
                        $areaName = $item->area_name->area_name;
                        unset($item->area_name);
                        $item->area_name = $areaName;
                    }
                }
            });

        return $adverList;
    }

    public function appointAdvertisementDetail(array $params)
    {
        $adver = Adver::where([
            'id'     => $params['adver_id'],
            'cat_id' => $this->cateId
        ])
            ->field('id,name,province_id,city_id, shop_id, complete, url, pic, cat_id, status,last_time, 
                    sub_name, sort, type, city_ids, begin_time, end_time, visit_num, click_num,wxapp_id,
                     wxapp_page, wxapp_open_type'
            )
            ->order('sort DESC, id DESC')
            ->find();
        
        if(!empty($adver)){
            $adver->pic = file_domain() . "/upload/adver/$adver->pic";
        }

        return $adver;
    }

    public function advertisementAddOrEdit(array $params)
    {
        $params['cat_id'] = $this->cateId;
        $params['last_time'] = time();
        unset($params['id']);
        
        if(!empty($params['pic'])){
            $params['pic'] = str_replace(file_domain(), '', $params['pic']);
            $params['pic'] = str_replace('/upload/adver/', '', $params['pic']);
        }
        if (!empty($params['adver_id'])) {
            $adver = Adver::where([
                'id'     => $params['adver_id'],
                'cat_id' => $this->cateId
            ])->find();
        }
        empty($adver) && $adver = new Adver();

        return $adver->data($params)->save();
    }

    public function advertisementDel(array $params)
    {
        $deleteNums = Adver::where([
            'id'     => $params['adver_id'],
            'cat_id' => $this->cateId
        ])->delete();
        
        if(empty($deleteNums)){
            throw_exception(L_('当前广告ID错误！'));
        }
        
        return true;
    }
}
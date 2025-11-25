<?php

declare (strict_types=1);

namespace app\appoint\model\service;


use app\appoint\model\db\Appoint;
use app\appoint\model\db\AppointNewsflash;
use app\appoint\model\db\AppointProduct;
use think\App;

class AppointNewsflashService
{
    public $pageSize;

    public function __construct()
    {
        $this->pageSize = min(input('page_size'), 100);
    }

    public function saveNews(array $params)
    {
        $appointIds = array_column($params['appoint_json'], 'appoint_id');
        if (count($appointIds) != count(array_unique($appointIds))) {
            throw_exception('相同预约不可重复添加');
        }
        usort($params['appoint_json'], function ($a, $b) {
            return $b['sort'] - $a['sort'];
        });
        $params['appoint_ids'] = implode(',', $appointIds);
        if (!empty($params['id'])) {
            $appointNewsflash = AppointNewsflash::where('id', $params['id'])->find();
        } else {
            $appointNewsflash = new AppointNewsflash();
        }
        $appointNewsflash->setAttrs($params);
        return $appointNewsflash->save();
    }

    public function newsList(array $params)
    {
        $newsList = AppointNewsflash::withSearch(['title','status'], $params)
            ->where('delete_time',0)
            ->withoutField('delete_time, appoint_json')
            ->paginate($this->pageSize);

        return $newsList;
    }
    
    public function newsDetail($newsflashId)
    {
        $news = AppointNewsflash::where('id', $newsflashId)
            ->where('delete_time',0)
            ->withoutField('delete_time')
            ->findOrEmpty()
            ->toArray();
        empty($news) && throw_exception('当前快报资讯不存在');
        if($news['appoint_json']){
            foreach ($news['appoint_json'] as &$appoint){
                $originAppoint = Appoint::where('appoint_id', $appoint['appoint_id'])
                    ->with(['merchant' => function($query){
                        $query->field('mer_id, name');
                    },'appoint_category' => function($query){
                        $query->field('cat_id, is_autotrophic');
                    },'appoint_product'=>function($query){
                        $query->field(['appoint_id', 'price']);
                    }])
                    ->field(['appoint_id', 'appoint_name', 'cat_id','payment_status','pic', 'appoint_price', 
                        '(appoint_sum + virtual_sales ) appoint_sum', 'appoint_type', 'score_all', 'is_appoint_price', 'mer_id'])
                    ->findOrEmpty();
                $originAppoint['merchant_name'] = $originAppoint['merchant']->getAttr('name');
                
                $picArr = explode(';',$originAppoint['pic']);
                $originAppoint['list_pic'] = (new AppointService())->getImageByPath($picArr[0],'s');

                if (empty($originAppoint['is_appoint_price']) && floatval($originAppoint['appoint_price']) <= 0) {
                    $originAppoint['appoint_price'] = '面议';
                }

                $specPrice = AppointProduct::where('appoint_id', $appoint['appoint_id'])->field('price')->column('price');
                if(!empty($specPrice)){
                    $originAppoint['appoint_price'] = min($specPrice);
                }

                $baseUrl =  cfg('system_type') == 'village' ? '/packapp/village/' : '/packapp/plat/';
                $originAppoint['is_autotrophic'] = $originAppoint['appoint_category'] ? $originAppoint['appoint_category']['is_autotrophic'] : 0;
                $autotrophic = $originAppoint['is_autotrophic'] ?  'goodsDetail' : 'productDetail';

                $originAppoint['url'] = cfg('site_url')."{$baseUrl}pages/appoint/goods/{$autotrophic}?appoint_id={$appoint['appoint_id']}&is_autotrophic={$originAppoint['is_autotrophic']}";
                
                $appoint = array_merge($appoint, $originAppoint->toArray());
                unset($appoint['merchant']);
            }
        }
        $news['content'] = $news['content'] ? replace_file_domain_content(htmlspecialchars_decode($news['content'])) : '';
        return $news;
    }
    
    public function delNews(array $params)
    {
        $appointNewsflash = AppointNewsflash::where('id', $params['newsflash_id'])->find();
        $appointNewsflash->delete_time = time();

        return $appointNewsflash->save();
    }
    
    public function appointList(array $params)
    {
        $appointList = Appoint::with(['merchant' => function($query){
                $query->withField(['mer_id', 'name']);
            }])
            ->where([
                'appoint_status' => 0,
                'check_status' => 1
            ])
            ->withSearch(['appoint_name'], $params)
            ->where('end_time', '>', time())
            ->field(['appoint_id', 'appoint_name','appoint_price', 'mer_id', 'sort'])
            ->order('sort DESC, appoint_id DESC')
            ->paginate(min(100,input('page_size')))
            ->each(function($item){
                $item->merchant_name = $item->merchant->name;
                unset($item->merchant);
            });
        
        return $appointList;
    }
}
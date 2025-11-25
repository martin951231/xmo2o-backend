<?php

declare (strict_types=1);

namespace app\appoint\model\service;

use app\appoint\model\db\Appoint;
use app\appoint\model\db\AppointActivity;
use app\appoint\model\db\AppointProduct;
use app\appoint\model\db\AppointRecommend;
use app\appoint\model\db\AppointStore;
use app\common\model\db\AppointCategory;
use app\common\model\db\Config;
use app\common\model\db\Merchant;
use app\common\model\service\decorate\DecorateService;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\percent_rate\PercentRateService;
use app\http\exceptions\ParametersException;
use think\helper\Arr;

class AppointService
{
    //region 预约推荐
    public function appointList(array $params)
    {
        if(!empty($params['keywords'])){
            switch ($params['search_type']){
                case 1:
                    $params['appoint_name'] = $params['keywords'];
                    break;
                case 2:
                    $merId = Merchant::where('name','LIKE',$params['keywords'].'%')
                        ->where('status',1)
                        ->field('mer_id')
                        ->column('mer_id');
                    $params['mer_id'] = $merId;
                    break;
            }
        }
        Appoint::alias('a')
            ->join('merchant m','a.mer_id = m.mer_id')
            ->where(['m.status' => 4])//商家已删除
            ->save(['a.appoint_status' => 1]);//关闭预约
        
        $appointList = Appoint::with(['merchant' => function($query){
                $query->withField(['mer_id', 'name']);
            }])
            ->withSearch(['appoint_name', 'mer_id', 'check_status'], $params)
            ->where([
                'appoint_status' => 0,
                'check_status' => 1
            ])
            ->where('appoint_id', 'NOT IN', function ($query) {
                $query->table(config('database.connections.mysql.prefix').'appoint_recommend')
                    ->where('delete_time', NULL)
                    ->field('appoint_id');
            })
            ->where('end_time', '>', time())
            ->field(['appoint_id', 'appoint_name', 'mer_id', 'sort', 'check_status'])
            ->order('sort DESC, appoint_id DESC')
            ->paginate(min(100,input('page_size')))
            ->each(function($item){
                $item->merchant_name = $item->merchant->name;
                unset($item->merchant);
            });

        return $appointList;
    }
    
    public function appointRecommendList()
    {
        $appointList = AppointRecommend::with(['appoint' => function($query){
                $query->withField(['appoint_name', 'mer_id', 'appoint_id', 'appoint_price'])
                    ->with(['merchant' => function($query){
                        $query->withField(['mer_id', 'name']);
                    }]);
            }])
            ->where(['delete_time' => NULL])
            ->field(['id', 'appoint_id', 'sort', 'status'])
            ->order('sort DESC, id DESC')
            ->paginate(min(100,input('page_size')))
            ->each(function($item){
                $item->appoint_name = $item->appoint->appoint_name;
                $item->appoint_price = $item->appoint->appoint_price;
                $item->merchant_name = $item->appoint->merchant->name;
                unset($item->appoint);
            });

        return $appointList;
    }

    public function appointRecommendAdd(array $params)
    {
        empty($params['appoint_ids']) && throw_exception(L_('预约商品不可为空!'), ParametersException::class);
        
        $appointIds = Appoint::where('appoint_id', 'IN', $params['appoint_ids'])
            ->where([
                'appoint_status' => 0, //活动状态(0,开启；1,关闭)
                'check_status' => 1
            ])
            ->where('end_time', '>', time())
            ->column('appoint_id');
            
        foreach ($appointIds as $id){
            $recommend = AppointRecommend::where(['appoint_id' => $id])->find();
            if(empty($recommend)){
                $recommend = new AppointRecommend();
                $recommend->appoint_id = $id;
            }
            $recommend->delete_time = NULL;
            $recommend->save();            
        }
        
        return true;
    }
    
    public function appointRecommendEdit(array $params)
    {
        $recommend = AppointRecommend::where(['appoint_id' => $params['appoint_id']])->find();
        empty($recommend) && throw_exception('当前预约未推荐！');

        isset($params['sort']) && $recommend->sort = $params['sort'];
        isset($params['status']) && $recommend->status = intval(boolval($params['status']));
        
        return $recommend->save();
    }
    
    public function appointRecommendDel(array $params)
    {
        empty($params['appoint_ids']) && throw_exception(L_('预约商品不可为空!'), ParametersException::class);
        
        foreach ($params['appoint_ids'] as $id){
            $recommend = AppointRecommend::where(['appoint_id' => $id])->find();
            if(!empty($recommend)){
                $recommend->delete_time = date('Y-m-d H:i:s');
                $recommend->save();
            }
        }

        return true;
    }
    
    public function appointCategoryEdit(array $params)
    {
        if(empty($params['category_id'])){
            $appointCategorySet = Config::where('name','=', 'appoint_recommend_category')->find();
            $appointCategorySet->value = json_encode([
                'name' => '精选',
                'wap_title' => $params['wap_title'],
                'status'  => $params['status'],
                'sort' => $params['sort'],
                'style' => $params['style'],
                'category_id' => 0
            ],JSON_UNESCAPED_UNICODE);

            $result = $appointCategorySet->save();

        } else {
            $appointCategorySet = AppointCategory::where('cat_id','=', $params['category_id'])->find();
            empty($appointCategorySet) && throw_exception('当前分类不存在');

            $result = $appointCategorySet->data([
                'cat_status' => $params['status'],
                'cat_sort' => $params['sort'],
                'style' => $params['style'],
                'wap_title' => $params['wap_title'],
            ])->save();
        }
        
        return $result;
    }
    
    public function appointIndexCategory()
    {
        $appointCategorySet = Config::where('name','=', 'appoint_recommend_category')->find();
        $appointRecommendCategory = json_decode($appointCategorySet->value, true);
        
        $appointCategory = AppointCategory::where(['cat_fid' => 0])
            ->field('cat_name name, wap_title, cat_status status, cat_sort sort, style, cat_id category_id')
            ->select()
            ->toArray();
        
        array_unshift($appointCategory,$appointRecommendCategory);
        
        return $appointCategory;
    }
    
    public function appointCategoryList()
    {
        $appointCategorySet = Config::where('name','=', 'appoint_recommend_category')->find();
        $appointRecommendCategory = json_decode($appointCategorySet->value, true);
        
        $appointCategory = AppointCategory::where(['cat_fid' => 0, 'cat_status' => 1])
            ->field('cat_name name, wap_title, cat_status status, cat_sort sort, style, cat_id category_id, cat_url')
            ->select()
            ->toArray();
        
        if(!empty($appointRecommendCategory['status'])){
            array_unshift($appointCategory,$appointRecommendCategory);
        }
        
        $sort = array_column($appointCategory,'sort');
        array_multisort($sort,SORT_DESC,$appointCategory);

        return $appointCategory;
    }
    
    public function getImageByPath($path,$image_type='-1'){
        if(!empty($path)){
            $image_tmp = explode(',',$path);
            if($image_type == '-1'){
                $return['image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/'.$image_tmp['1'];
                $return['m_image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/m_'.$image_tmp['1'];
                $return['s_image'] = file_domain().'/upload/appoint/'.$image_tmp[0].'/s_'.$image_tmp['1'];
            }else{
                if (strstr($image_tmp[0], '/upload/')) {
                    $return = replace_file_domain($image_tmp[0]);
                }else{
                    $return = file_domain().'/upload/appoint/'.$image_tmp[0].'/'.$image_type.'_'.$image_tmp['1'];
                }
            }
            return $return;
        }else{
            return false;
        }
    }
    
    public function recommendAppointList(array $params)
    {
        $recommendAppointList = AppointRecommend::alias('ar')
            ->join('appoint_store s','ar.appoint_id = s.appoint_id')
            ->join('appoint a','ar.appoint_id = a.appoint_id')
            ->join('appoint_category ac','a.cat_id = ac.cat_id')
            ->where([
                's.city_id' => $params['now_city'],
                'a.appoint_status' => 0,
                'ar.status' => 1,
                'a.check_status' => 1,
                'ar.delete_time' => NULL,
            ])
            ->with(['appoint_product'=>function($query){
                $query->field(['appoint_id', 'price']);
            }])
            ->where('a.end_time', '>', time())
            ->order('ar.sort DESC')
            ->group('a.appoint_id')
            ->field([
                'a.appoint_id', 'a.appoint_name', 'a.cat_id','a.payment_status','a.pic', 'a.appoint_price', 'a.mer_id',
                '(a.appoint_sum + a.virtual_sales) appoint_sum', 'a.appoint_type', 'a.score_all', 'a.is_appoint_price, ac.is_autotrophic', 'a.score_percent'
            ])
            ->paginate(min(100, input('page_size')))
            ->each(function ($item){
                $picArr = explode(';',$item->pic);                
                $item->list_pic = $this->getImageByPath($picArr[0],'s');
                
                if (empty($item->is_appoint_price) && floatval($item->appoint_price)<=0) {
                    $item->appoint_price = '面议';
                }
                
                $specPrice = AppointProduct::where('appoint_id', $item->appoint_id)->column('price');
                if(!empty($specPrice)){
                    $item->appoint_price = min($specPrice);
                }
                //积分展示
                $item['score_text'] = '';
                if(customization('goods_show_score') && $scorePrice = floatval($item->appoint_price)){
                    $scorePercent = PercentRateService::scorePercent('appoint', $item->appoint_id, $item['mer_id']);
                    $score = bcmul((string)$scorePrice, (string)$scorePercent, 2);
                    if(cfg('score_round_two')){
                        $score = sprintf('%.2f',floatval($score));
                    }else{
                        $score = round($score);
                    }
                    floatval($score) > 0 && $item['score_text'] = cfg('score_name').'+'.$score;
                }
                $baseUrl =  cfg('system_type') == 'village' ? '/packapp/village/' : '/packapp/plat/';
                $autotrophic = empty($item->is_autotrophic) ? 'productDetail' : 'goodsDetail';
                
                $item->url = cfg('site_url')."{$baseUrl}pages/appoint/goods/{$autotrophic}?appoint_id={$item->appoint_id}&is_autotrophic={$item->is_autotrophic}";
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($item->appoint_price,'appoint',false,$item->score_percent);
                $item->buy_can_get_score = $buy_can_get_score?$buy_can_get_score['score']:0;
                $item->buy_can_get_score_txt = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
            });

        return $recommendAppointList;
    }

    public function appointStoreList(array $params)
    {
        $data = AppointStore::alias('as')
            ->join('merchant_store ms', 'as.store_id = ms.store_id')
            ->where([
                'as.appoint_id' => $params['appoint_id'],
                'ms.mer_id'     => $params['mer_id'],
                'ms.status'     => 1,//0 关闭,1为开启
            ])
            ->field(['long', 'lat', 'name', 'phone', 'adress as address', 'pic_info as pic'])
            ->paginate()
            ->each(function ($item) use ($params) {
                $item->distance        = '';
                $item->distance_origin = 0;
                if (!empty($params['user_long']) && !empty($params['user_lat'])) {
                    $item->distance        = getDistance($params['user_lat'], $params['user_long'], $item->lat, $item->long);
                    $item->distance_origin = $item->distance;
                    if ($item->distance > 1000) {
                        $item->distance = getFormatNumber($item->distance / 1000) . 'km';
                    } else {
                        $item->distance .= 'm';
                    }
                }
                if (!empty($item->pic)) {
                    $pics      = explode(';', $item->pic);
                    $item->pic = file_domain() . array_shift($pics);
                }
            })
            ->toArray();

        $array_column = array_column($data['data'], 'distance_origin');
        array_multisort($array_column, SORT_ASC, $data['data']);

        return $data;
    }
    
    //endregion
    
    //region 预约活动
    
    public function activityList()
    {
        $activityList = AppointActivity::where(['delete_time' => 0])
            ->with(['micro_page'])
            ->withoutField(['delete_time'])
            ->order('id DESC')
            ->paginate(min(100,input('page_size')))
            ->each(function ($item){
                $pageId = $item->micro_page ? $item->micro_page->id : 0;
                $item->url =  get_base_url("pages/customPage/customPage?source=appoint_activity&source_id={$item->id}&pageId={$pageId}");
                $item->qrcode = (new DecorateService())->createGoodsQrcode($item->url);
            });

        return $activityList;
    }

    public function delActivity(array $ids)
    {
        return AppointActivity::update(['delete_time' => time()],['id' => $ids]);
    }
    
    public function saveActivityList(array $params)
    {
        if(!empty($params['id'])){
            $activity = AppointActivity::where(['id' => $params['id']])->find();
        }
        if(empty($activity)){
            $activity = new AppointActivity();
        }
        if($params['show_time_type'] == 1){
            $params['start_show_time'] = date('Y-m-d H:i:s');
            $params['end_show_time'] = date('Y-m-d H:i:s',time() + 3600 * 24 * 365 * 100 );
        }elseif(empty($params['start_show_time']) || empty($params['end_show_time'])){
            throw_exception('显示时长不能为空');
        }
        $params['delete_time'] = 0;
        $activity->setAttrs(Arr::only($params,$activity->getTableFields()));

        return $activity->save();
    }

    public function activityAppointList(array $params)
    {
        empty($params['sort']) && $params['sort'] = '';
        $sort = [
            'create_time'   => 'create_time DESC',
            'views'         => 'hits DESC',
            'price'         => 'appoint_price ASC',
            'goods_comment' => 'score_mean DESC',
        ];
        $order = $sort[$params['sort']] ?? 'sort DESC, appoint_id DESC';
        $appointList = Appoint::with(['merchant' => function($query){
                $query->field(['mer_id', 'name']);
            }, 'appoint_product' => function($query){
                $query->field(['price', 'payment_price', 'name', 'appoint_id']);
            },'appoint_category' => function($query){
                $query->field('cat_id, cat_name, is_autotrophic');
            }])
            ->where([
                'appoint_status' => 0,
                'check_status' => 1
            ])
            ->withSearch(['appoint_name', 'cat_fid', 'ids'], $params)
            ->where('end_time', '>', time())
            ->field(['appoint_id','appoint_content', 'appoint_name', 'cat_id','payment_status','pic', 'appoint_price',
                    'sort', 'score_mean score', 'appoint_sum', 'appoint_type', 'score_all', 'is_appoint_price', 
                    'mer_id', 'update_time, expend_time'
                ])
            ->order($order)
            ->paginate(min(200,input('page_size')))
            ->each(function($item){
                $item->merchant_name = $item->merchant->name;
                $picArr = explode(';',$item['pic']);
                $item['list_pic'] = (new AppointService())->getImageByPath($picArr[0],'s');
                
                if (empty($item['is_appoint_price']) && floatval($item['appoint_price']) <= 0) {
                 $item['appoint_price'] = '面议';
                }
                
                $specPrice = AppointProduct::where('appoint_id', $item['appoint_id'])->column('price');
                if(!empty($specPrice)){
                 $item['appoint_price'] = min($specPrice);
                }
                
                $baseUrl =  cfg('system_type') == 'village' ? '/packapp/village/' : '/packapp/plat/';
                $item['is_autotrophic'] = $item['appoint_category'] ? $item['appoint_category']['is_autotrophic'] : 0;
                $autotrophic = $item['is_autotrophic'] ?  'goodsDetail' : 'productDetail';
                
                $item['url'] = cfg('site_url')."{$baseUrl}pages/appoint/goods/{$autotrophic}?appoint_id={$item['appoint_id']}&is_autotrophic={$item['is_autotrophic']}";
                
                unset($item->merchant);
            });

        return $appointList;
    }
    
    public function categoryList(array $params)
    {
        $categoryList = AppointCategory::where([
                'cat_fid' => 0,
                'cat_status' => 1,
            ])
            ->withSearch(['ids'], $params)
            ->field('cat_id, cat_name, cat_pic, create_time, merchant_id, is_autotrophic, pc_title, wap_title')
            ->order('cat_sort DESC')
            ->append(['appoint_num'])
            ->select()
            ->toArray();
        if(!empty($params['ids'])){
            $list = array_column($categoryList, null,'cat_id');
            $categoryList = [];
            foreach ($params['ids'] as $id){
                array_push($categoryList, $list[$id]);
            }
        }
        
        return $categoryList;
    }
    //endregion

    /*
     * 获取商品积分比例
     */
    public function getGoodsScore($goodsId)
    {
        $scorePercent = Appoint::where('appoint_id', $goodsId)->value('score_percent');
        if(strpos((string)$scorePercent,'%')){
            $scorePercent = bcdiv((string)$scorePercent,100,2);
        }
        if(empty(floatval($scorePercent))){
            return -1;
        }
        return $scorePercent;
    }

    /**
     * 获取预约关联商品
     * @param $params
     */
    public function getRelatedGoods($params)
    {
        $appoint_id = $params['appoint_id'];
        $product_recommendation = (new Appoint())->where(['appoint_id' => $appoint_id])->value('product_recommendation');
        $product_recommendation = $product_recommendation ? explode(',', $product_recommendation) : [];
        if (!$product_recommendation) {
            return [];
        }
        $recommendAppointList = Appoint::alias('a')
            ->join('appoint_category ac', 'a.cat_id = ac.cat_id')
            ->where([
                ['a.appoint_status', '=', 0],
                ['a.appoint_id', 'in', $product_recommendation],
            ])
            ->with(['appoint_product' => function ($query) {
                $query->field(['appoint_id', 'price']);
            }])
            ->where('a.end_time', '>', time())
            ->order('a.sort DESC,a.appoint_id DESC')
            ->field([
                'a.appoint_id', 'a.appoint_name', 'a.cat_id', 'a.payment_status', 'a.pic', 'a.appoint_price', 'a.mer_id',
                '(a.appoint_sum + a.virtual_sales) appoint_sum', 'a.appoint_type', 'a.score_all', 'a.is_appoint_price, ac.is_autotrophic', 'a.score_percent'
            ])
            ->select()
            ->each(function ($item) {
                $picArr = explode(';', $item->pic);
                $item->list_pic = $this->getImageByPath($picArr[0], 's');

                if (empty($item->is_appoint_price) && floatval($item->appoint_price) <= 0) {
                    $item->appoint_price = '面议';
                }

                $specPrice = AppointProduct::where('appoint_id', $item->appoint_id)->column('price');
                if (!empty($specPrice)) {
                    $item->appoint_price = min($specPrice);
                }
                //积分展示
                $item['score_text'] = '';
                if (customization('goods_show_score') && $scorePrice = floatval($item->appoint_price)) {
                    $scorePercent = PercentRateService::scorePercent('appoint', $item->appoint_id, $item['mer_id']);
                    $score = bcmul((string)$scorePrice, (string)$scorePercent, 2);
                    if (cfg('score_round_two')) {
                        $score = sprintf('%.2f', floatval($score));
                    } else {
                        $score = round($score);
                    }
                    floatval($score) > 0 && $item['score_text'] = cfg('score_name') . '+' . $score;
                }
                $baseUrl = cfg('system_type') == 'village' ? '/packapp/village/' : '/packapp/plat/';
                $autotrophic = empty($item->is_autotrophic) ? 'productDetail' : 'goodsDetail';

                $item->url = cfg('site_url') . "{$baseUrl}pages/appoint/goods/{$autotrophic}?appoint_id={$item->appoint_id}&is_autotrophic={$item->is_autotrophic}";
                //消费一元获得积分
                $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($item->appoint_price, 'appoint', false, $item->score_percent);
                $item->buy_can_get_score = $buy_can_get_score ? $buy_can_get_score['score'] : 0;
                $item->buy_can_get_score_txt = $buy_can_get_score ? $buy_can_get_score['score_txt'] : '';
            });

        return $recommendAppointList;
    }
}
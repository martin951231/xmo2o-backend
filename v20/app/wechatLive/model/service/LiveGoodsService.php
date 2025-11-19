<?php
declare (strict_types=1);

namespace app\wechatLive\model\service;

use app\appoint\model\db\Appoint;
use app\common\model\db\Config;
use app\group\model\db\Group;
use app\http\exceptions\ParametersException;
use app\mall\model\db\MallGoods;
use app\mall\model\db\VillageGroupGoods;
use app\shop\model\db\ShopGoods;
use app\wechatLive\model\db\WechatLiveGoods;
use think\facade\Db;
use wechatLive\MiniLive;

class LiveGoodsService
{
    public function wechatLiveList(array $params)
    {
        $cacheKey = md5(json_encode($params));
        if ($data = cache($cacheKey)) {
            return $data;
        }

        $liveList     = app(MiniLive::class)->getShowList($params['page'], $params['page_size']);
        $liveStatus   = [
            '101' => '直播中',
            '102' => '未开始',
            '103' => '已结束',
            '104' => '禁播',
            '105' => '暂停',
            '106' => '异常',
            '107' => '已过期',
        ];
        $return       = [];
        $customParams = urlencode(json_encode(['from' => 'wechat_live'], JSON_UNESCAPED_UNICODE));
        foreach ($liveList['room_info'] as $key => $value) {
            $return[] = [
                'roomid'          => $value['roomid'],
                'live_status_txt' => $liveStatus[$value['live_status']] ?? '未知',
                'live_status'     => $value['live_status'],
                'start_time'      => date('Y-m-d H:i:s', $value['start_time']),
                'cover_img'       => $value['cover_img'],
                'name'            => $value['name'],
                'anchor_name'     => $value['anchor_name'],
                'url'             => "plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin?room_id={$value['roomid']}&custom_params={$customParams}",
            ];
        }
        $data = [
            'total'    => $liveList['total'],
            'per_page' => $params['page_size'],
            'data'     => $return
        ];
        cache($cacheKey, $data, 5);

        return $data;
    }
    
    public function hasWechatLiveOnline()
    {
        $liveShow = Config::where('name','live_show_icon')->field('value')->find();
        if(empty($liveShow['value'])){
            return [
                'show_live' => 0,
                'live_show_icon' => ''
            ];
        }
        $cacheKey = 'hasWechatLiveOnline';
        if ($data = cache($cacheKey)) {
            return json_decode($data,true);
        }

        static $page = 1;
        $liveList = app(MiniLive::class)->getShowList($page, 100);
        $hasWechatLiveOnline = 0;
        foreach ($liveList['room_info'] as $key => $value) {
           if($value['live_status'] == '101'){
               $hasWechatLiveOnline = 1;
               break;
           }
        }
        if(empty($hasWechatLiveOnline)){
            $maxPage = intval(ceil(bcdiv((string)$liveList['total'], '100')));
            if($page < $maxPage){
                $page ++;
                return $this->hasWechatLiveOnline();
            }
        }
        $data = [
            'show_live' => $hasWechatLiveOnline,
            'live_show_icon' => replace_file_domain($liveShow['value'])
        ];
        cache($cacheKey, json_encode($data,256), 5);
        
        return $data;
    }

    public function merchantGoodsList(array $params)
    {
        $goodsType = [
            'shop' => ShopGoods::class,
            'mall' => MallGoods::class,
            'village_group' => VillageGroupGoods::class,
            'group' => Group::class,
            'appoint' => Appoint::class,
        ];

        !isset($goodsType[$params['goods_type']]) && throw_exception('商品类型参数错误！', ParametersException::class);

        return app($goodsType[$params['goods_type']])->merchantGoodsList($params['mer_id'], $params['page_size'], $params);
    }

    public function liveGoodsList(array $params)
    {
        return WechatLiveGoods::withSearch(['mer_id', 'keywords', 'goods_name', 'goods_type', 'check_status'], $params)
            ->with(['merchantStore' => function ($query) {
                $query->field('store_id, name');
            }, 'merchant'           => function ($query) {
                $query->field('mer_id, name');
            }])
            ->field(
                'id, mer_id, store_id,goods_id, goods_name,goods_type,platform_status,create_time,
                goods_image,old_price,price,check_status,fail_reason,path,status, commission_rate,price_type,price2'
            )
            ->order('id DESC')
            ->paginate(min($params['page_size'], 100))
            ->each(function ($item) {
                $item['merchant_name'] = $item['merchant']['name'];
                $item['store_name']    = $item['merchantStore']['name'];
                $item['goods_image']   = replace_file_domain($item['goods_image']);
                unset($item['merchantStore'], $item['merchant']);
            });
    }

    public function liveGoodsAdd(array $params)
    {
        $where = [
            'goods_id'   => $params['goods_id'],
            'store_id'   => $params['store_id'],
            'goods_type' => $params['goods_type']
        ];
        $cacheKey = 'liveGoodsAdd'.md5(json_encode($where));
        if(!empty(cache($cacheKey))){
            throw_exception('商品正在添加中，请勿多次提交！');
        }
        cache($cacheKey,1,5);
        $liveGoods = WechatLiveGoods::where($where)->find();
        !empty($liveGoods) && throw_exception('当前商品已存在于直播商品库！');

        $siteUrl = cfg('site_url');
        $path    = [
            'shop' => sprintf('pages/shop_new/goodsDetail/goodsDetail?store_id=%d&product_id=%d&from=wechat_live', $params['store_id'], $params['goods_id']),
            'mall' => sprintf('pages/shopmall_third/commodity_details?goods_id=%d&from=wechat_live', $params['goods_id']),
            'group' => sprintf('platn/pages/group/v1/groupDetail/index?group_id=%d&store_id=%d&from=wechat_live', $params['goods_id'], $params['store_id']),
            'village_group' => "pages/webview/webview?webview_url=" . urlencode("{$siteUrl}/packapp/community_group/index.html?c=Village_group&a=index#/goods?goodsId={$params['goods_id']}") . "&from=wechat_live&webview_title=" . urlencode($params['goods_name']),
        ];

        if ($params['price_type'] == 1) {
            //一口价
            $params['price2'] = 0;
        } elseif ($params['price_type'] == 2 || $params['price_type'] == 3) {
            //价格区间、显示折扣价
            if ($params['price2'] < 0.01) {
                throw_exception('价格设置有误');
            }
        } else {
            throw_exception('价格形式选择有误');
        }

        try {
            Db::transaction(function ()use($params, $path){
                $liveGoods = WechatLiveGoods::create([
                    'mer_id'      => $params['mer_id'],
                    'store_id'    => $params['store_id'],
                    'goods_id'    => $params['goods_id'],
                    'goods_name'  => $params['goods_name'],
                    'goods_type'  => $params['goods_type'],
                    'goods_image' => $params['goods_image'],
                    'old_price'   => $params['old_price'],
                    'price_type'       => $params['price_type'],
                    'price'       => $params['price'],
                    'price2'       => $params['price2'],
                    'path'        => $path[$params['goods_type']]
                ]);

                $wechatData = app(MiniLive::class)->addGoods($params['goods_image'], $params['goods_name'], $params['price'], $params['price2'], $path[$params['goods_type']],$params['price_type']);

                $liveGoods->wechat_goods_id = $wechatData['goodsId'];
                $liveGoods->wechat_audit_id = $wechatData['auditId'];
                $liveGoods->check_status    = 1;
                $liveGoods->save();
            });
        } catch (\Exception $e) {
            fdump_api([$params, $e->getMessage()], 'wechatLive/add_goods', 1);
           
            throw_exception($e->getMessage());
        }

        return true;

    }

    public function liveGoodsEdit(array $params)
    {
        $liveGoods = WechatLiveGoods::where([
            'goods_id'   => $params['goods_id'],
            'store_id'   => $params['store_id'],
            'goods_type' => $params['goods_type']
        ])->find();
        empty($liveGoods) && throw_exception('当前商品不存在！');

        $siteUrl = cfg('site_url');
        $path    = [
            'mall'          => "pages/shopmall_third/commodity_details?goods_id={$params['goods_id']}&from=live",
            'shop'          => "pages/webview/webview?webview_url=".urlencode("{$siteUrl}/wap.php?g=Wap&c=Mall&a=detail&goods_id={$params['goods_id']}")."&from=live&webview_title=".urlencode($params['goods_name']),
            'village_group' => "pages/webview/webview?webview_url=".urlencode("{$siteUrl}/packapp/community_group/index.html?c=Village_group&a=index#/goods?goodsId={$params['goods_id']}")."&from=live&webview_title=".urlencode($params['goods_name']),
        ];

        try {
            Db::transaction(function () use ($liveGoods, $params, $path) {
                $liveGoods->price = $params['price'];
                $liveGoods->save();
                if($liveGoods->status){
                    app(MiniLive::class)->updateGoods(
                        $liveGoods->wechat_goods_id,
                        $params['goods_image'],
                        $params['goods_name'],
                        $params['price'],
                        '',
                        $path[$params['goods_type']]
                    );
                }
            });
        } catch (\Exception $e) {
            $liveGoods->fail_reason = $e->getMessage();
            $liveGoods->save();
            throw_exception($e->getMessage());
        }

        return true;

    }

    public function liveGoodsDelete(array $params)
    {
        $liveGoods = WechatLiveGoods::where([
            'id'       => $params['id'],
            'goods_id' => $params['goods_id'],
            'store_id' => $params['store_id']
        ])->find();
        empty($liveGoods) && throw_exception('当前商品不存在！');
        try{
            Db::transaction(function () use ($liveGoods, $params) {
                $liveGoods->status = 0;
                $liveGoods->delete();
                app(MiniLive::class)->delGoods($liveGoods['wechat_goods_id']);
            });
        }catch (\Exception $e){
            if(strpos($e->getMessage(),'300015') >= 0){//微信商品库不存在当前商品了
                $liveGoods = WechatLiveGoods::where([
                    'id'       => $params['id'],
                    'goods_id' => $params['goods_id'],
                    'store_id' => $params['store_id']
                ])->find();
                $liveGoods->status = 0;
                $liveGoods->delete();
            }else{
                throw_exception($e->getMessage());
            }
        }
       
        return true;
    }

    public function liveGoodsChangeStatus(array $params, $platform = false)
    {
        $liveGoods = WechatLiveGoods::where([
            'id'       => $params['id'],
            'goods_id' => $params['goods_id'],
            'store_id' => $params['store_id']
        ])->find();
        empty($liveGoods) && throw_exception('当前商品不存在！');

        Db::transaction(function () use ($liveGoods, $params, $platform) {
            if ($platform) {//平台
                $liveGoods->platform_status = $params['status'];
                $params['status'] == WechatLiveGoods::GOODS_DOWN_SHELVES && $liveGoods->status = WechatLiveGoods::GOODS_DOWN_SHELVES;
            } else if ($liveGoods->platform_status == WechatLiveGoods::GOODS_DOWN_SHELVES) {
                throw_exception('当前商品平台已禁止上架！');
            } else {
                $liveGoods->status = $params['status'];
            }
            
            if ($params['status'] == WechatLiveGoods::GOODS_UP_SHELVES) {//上架
                $wechatData = app(MiniLive::class)->addGoods($liveGoods['goods_image'], $liveGoods['goods_name'], $liveGoods['price'], '', $liveGoods['path']);

                $liveGoods->wechat_goods_id = $wechatData['goodsId'];
                $liveGoods->wechat_audit_id = $wechatData['auditId'];
                $liveGoods->check_status    = 1;
            } else if ($params['status'] == WechatLiveGoods::GOODS_DOWN_SHELVES) {//下架
                app(MiniLive::class)->delGoods($liveGoods['wechat_goods_id']);
                $liveGoods->check_status    = 4;
            }
            $liveGoods->save();
        });
        return true;
    }

    public function liveGoodsSetCommissionRate(array $params)
    {
        $liveGoods = WechatLiveGoods::where([
            'id'       => $params['id'],
            'goods_id' => $params['goods_id']
        ])->find();
        empty($liveGoods) && throw_exception('当前商品不存在！');

        $liveGoods->commission_rate = $params['commission_rate'];
        $liveGoods->save();

        return true;
    }
    
    public function liveGoodsStatusUpdate($merId)
    {
        $cacheKey = 'liveGoodsStatusUpdate'.$merId;
        if(!empty(cache($cacheKey))){
            return [
                'msg' => '商品状态正在同步中！10分钟内只可点击一次！'
            ];
        }
        cache($cacheKey,1,60*10);
        
        $count = WechatLiveGoods::where('mer_id',$merId)
            ->whereIn('check_status',[0, 1])
            ->count();
       
        $pageCount = intval(ceil(bcdiv((string)$count,'20',3)));
      
        for ($i = 1; $i <= $pageCount; $i++){
            $wechatGoodsId = WechatLiveGoods::where('mer_id',$merId)
                ->whereIn('check_status',[0,1])
                ->limit(($i-1)*20,20)
                ->field('wechat_goods_id')
                ->column('wechat_goods_id');
            
            $this->wechatLiveGoodsSync($wechatGoodsId);
        }

        return [
            'msg' => '商品状态同步成功！10分钟内只可同步一次！'
        ];
    }
    
    public function wechatLiveGoodsSync(array $wechatGoodsId)
    {
       $data = app(MiniLive::class)->getGoodsState($wechatGoodsId);
       foreach ($data as $k=>$v){
          $liveGoods = WechatLiveGoods::where('wechat_goods_id',$k)->find();
          if($liveGoods){
              $liveGoods->check_status = $v;
              $liveGoods->save();
          }
       }
       
       return true;
    }
}
<?php


namespace app\common\model\service;


use app\appoint\model\db\Appoint;
use app\appoint\model\service\AppointService;
use app\common\model\db\MerchantStoreShop;
use app\common\model\db\PlatRecommend;
use app\group\model\db\Group;
use app\group\model\service\GroupImageService;
use app\shop\model\db\ShopGoods;
use think\facade\Db;

class HouseGuessLikeGoodsService
{
    /**
     * 社区首页猜你喜欢-商品列表
     * @param $params
     * @return array
     */
    public function getGoodsList($params){
        $lng = $params['lng'];
        $lat = $params['lat'];
        $set_num = $params['set_num']?:4;
        $page = $params['page']?:1;
        $pageSize = $params['pageSize']?:3;
        $where[] = ['m.status','=',1];
        $where[] = ['s.status','=',1];
        $field = 's.store_id,s.have_shop,s.have_group';
        if($lng&&$lat){
            $field .= ",ACOS(SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180)) * 6370.996 as distance";
            $sort = "distance ASC,p.store_sort DESC";
            $where[] = ['','exp',Db::raw("ACOS(SIN(({$lat} * 3.1415) / 180) * SIN((s.`lat` * 3.1415) / 180) + COS(({$lat} * 3.1415) / 180) * COS((s.`lat` * 3.1415) / 180) * COS(({$lng} * 3.1415) / 180 - (s.`long` * 3.1415) / 180)) * 6370.996 < 10")];
        }else{
            $field .= ',0 as distance';
            $sort = 'p.store_sort DESC';
        }
        $store_list = (new PlatRecommend())->getGuessLikeList($where,$field,0,0,$sort);
        $store_list = $this->checkStore($store_list);
        $now_store_list = array_splice($store_list,($page-1)*$pageSize,$pageSize);
        $all_goods_list = [];
        foreach ($now_store_list as $key=>$item){
            $item_goods_list = $this->businessProcessing($item,$set_num);
            if($item_goods_list&&$item_goods_list['goods_list']){
                $all_goods_list = array_merge($all_goods_list,$item_goods_list['goods_list']);
            }
        }
        return [
            'data'=>$all_goods_list,
            'total'=>count($store_list),
            'per_page'=>$pageSize,'current_page'=>$page,
            'last_page'=>ceil(count($store_list)/$pageSize)
        ];
    }

    /**
     * 获取各业务商品数量
     * @param $item
     * @param $set_num
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function businessProcessing($item,$set_num){
        $shop_goods_list = $group_goods_list = $appoint_goods_list = [];
        $all_goods_list = [];
        $now_num = $set_num;
        //获取外卖商品
        if($item['have_shop']){
            $shop_goods_list = (new ShopGoods())->field('*,"shop" as business_type')->where([['status','=',1],['store_id','=',$item['store_id']]])->order('sort desc,sell_count desc')->limit($set_num)->select()->toArray();
        }
        if($shop_goods_list){
            $deliver_time = $this->mealSpeed($item);
            $all_goods_list[] = $shop_goods_list[0];
            unset($shop_goods_list[0]);
            $now_num = $set_num-1;
        }
        //团购商品
        if($item['have_group']&&$now_num>0){
            $group_where = [
                ['g.status', '=', 1],
                ['g.begin_time', '<', time()],
                ['g.end_time', '>', time()],
                ['g.group_cate','=','normal'],
                ['gs.store_id','=',$item['store_id']]
            ];
            $group_goods_list = (new Group())->alias('g')->field('g.*,"group" as business_type')
                ->join('group_store gs','g.group_id = gs.group_id')
                ->where($group_where)
                ->order('sort desc,sale_count_total desc')->limit($now_num)->select()->toArray();
        }
        if($group_goods_list){
            $all_goods_list[] = $group_goods_list[0];
            unset($group_goods_list[0]);
            $now_num -= 1;
        }
        //预约商品
        if($now_num>0){
            $appoint_where = [
                ['a.appoint_status', '=', 1],
                ['a.start_time', '<', time()],
                ['a.end_time', '>', time()],
                ['s.store_id','=',$item['store_id']]
            ];
            $appoint_goods_list = (new Appoint())->alias('a')->field('a.*,"appoint" as business_type')
                ->join('appoint_store s','s.appoint_id = a.appoint_id')
                ->where($appoint_where)
                ->order('sort desc,score_mean desc')
                ->limit($now_num)->select()->toArray();
        }
        if($appoint_goods_list){
            $all_goods_list[] = $appoint_goods_list[0];
            unset($appoint_goods_list[0]);
        }
        if($shop_goods_list&&count($all_goods_list)!=$set_num){
            $all_goods_list = array_merge($all_goods_list,array_slice($shop_goods_list,0,($set_num-count($all_goods_list))));
        }
        if($group_goods_list&&count($all_goods_list)!=$set_num){
            $all_goods_list = array_merge($all_goods_list,array_slice($group_goods_list,0,($set_num-count($all_goods_list))));
        }
        if($appoint_goods_list&&count($all_goods_list)!=$set_num){
            $all_goods_list = array_merge($all_goods_list,array_slice($appoint_goods_list,0,($set_num-count($all_goods_list))));
        }
        $business_name = [
            'shop' => cfg('shop_alias_name')?:'',
            'group' => cfg('group_alias_name')?:'',
            'appoint' => cfg('appoint_alias_name')?:'',
        ];
        $goods_list = [];
        foreach ($all_goods_list as $val){
            $goods = [
                'goods_id' => 0,
                'image' => '',
                'goods_name' => '',
                'sale_count' => 0,
                'price' => 0,
                'dining_out_txt' => '',
                'business_type' => $val['business_type']?:'',
                'business_type_txt' => $business_name[$val['business_type']]??'',
                'store_id' => $item['store_id'],
                'url' => '',
            ];
            if($val['business_type']=='shop'){
                $img = $val['image']?explode(';',$val['image']):[];
                $goods['goods_id'] = $val['goods_id'];
                $goods['goods_name'] = $val['name'];
                $goods['price'] = $val['price'];
                $goods['image'] = $img?replace_file_domain($img[0]):'';
                $goods['dining_out_txt'] = $deliver_time?$deliver_time['meal_speed_time_txt']:'';
                $goods['sale_count'] = $val['sell_count']?:0;
                $goods['url'] = get_base_url('pages/shop_new/goodsDetail/goodsDetail?store_id='.$item['store_id'].'&product_id='.$val['goods_id']);
            }elseif($val['business_type']=='group'){
                $img = $val['pic']?explode(';',$val['pic']):[];
                $img = $img?((new GroupImageService())->getImageByPath($img[0], 's')):'';
                $goods['goods_id'] = $val['group_id'];
                $goods['image'] = $img?replace_file_domain($img):'';
                $goods['goods_name'] = $val['name'];
                $goods['price'] = $val['price'];
                $goods['sale_count'] = $val['sale_count_total'];
                $goods['url'] = get_base_url('pages/group/v1/groupDetail/index?group_id='.$val['group_id'],1);
            }elseif($val['business_type']=='appoint'){
                $goods['image'] = $val['pic']?(new AppointService())->getImageByPath($val['pic'],0):'';
                $goods['goods_id'] = $val['appoint_id'];
                $goods['goods_name'] = $val['appoint_name'];
                $goods['price'] = $val['payment_money']+$val['appoint_price'];
                $goods['sale_count'] = $val['appoint_sum']?:0;
                $goods['url'] = get_base_url('pages/appoint/goods/productDetail?appoint_id='.$val['appoint_id']);
            }
            $goods_list[] = $goods;
        }
        
        return ['goods_list'=>$goods_list,'total'=>count($goods_list)];
    }

    /**
     * 计算外卖大概出餐时长
     * @param $item
     * @return array
     */
    public function mealSpeed($item){
        $shop_store = (new MerchantStoreShop())->field('send_time_type,work_time')->where([['store_id','=',$item['store_id']]])->findOrEmpty()->toArray();
        $speed = cfg('deliver_speed_hour');
        $time_consuming = 0;
        $time_consuming_txt = '';
        if($shop_store){
            $deliver_time = $speed?($item['distance']/$speed):0;
            if ($shop_store['send_time_type'] == 1){
                $time_consuming = ceil(($shop_store['work_time'] + $deliver_time));
                $time_consuming_txt = $time_consuming.'小时';
            }elseif ($shop_store['send_time_type'] == 2){
                $time_consuming = ceil(($shop_store['work_time'] + $deliver_time/24));
                $time_consuming_txt = $time_consuming.'天';
            }elseif ($shop_store['send_time_type'] == 3){
                $time_consuming = ceil(($shop_store['work_time'] + $deliver_time/24/7));
                $time_consuming_txt = $time_consuming.'周';
            }elseif ($shop_store['send_time_type'] == 4){
                $time_consuming = ceil(($shop_store['work_time'] + $deliver_time/24/30));
                $time_consuming_txt = $time_consuming.'月';
            }else{
                $time_consuming = ceil(($shop_store['work_time'] + $deliver_time * 60));
                $time_consuming_txt = $time_consuming.'分钟';
            }
        }
        $data = [
            'meal_speed_time' => $time_consuming,
            'meal_speed_time_txt' => $time_consuming_txt
        ];
        return $data;
    }

    /**
     * 过滤没有商品的店铺
     * @param $store_list
     * @return array
     */
    public function checkStore($store_list){
        foreach ($store_list as $k=>$v){
            $shop_goods_list = $group_goods_list = $appoint_goods_list = 0;
            if($v['have_shop']){
                $shop_goods_list = (new ShopGoods())
                    ->where([['status','=',1],['store_id','=',$v['store_id']]])
                    ->count();
            }
            if($v['have_group']){
                $group_where = [
                    ['g.status', '=', 1],
                    ['g.begin_time', '<', time()],
                    ['g.end_time', '>', time()],
                    ['g.group_cate','=','normal'],
                    ['gs.store_id','=',$v['store_id']]
                ];
                $group_goods_list = (new Group())->alias('g')->field('g.*,"group" as business_type')
                    ->join('group_store gs','g.group_id = gs.group_id')
                    ->where($group_where)
                    ->count();
            }
            $appoint_where = [
                ['a.appoint_status', '=', 1],
                ['a.start_time', '<', time()],
                ['a.end_time', '>', time()],
                ['s.store_id','=',$v['store_id']]
            ];
            $appoint_goods_list = (new Appoint())->alias('a')->field('a.*,"appoint" as business_type')
                ->join('appoint_store s','s.appoint_id = a.appoint_id')
                ->where($appoint_where)
                ->count();
            if($shop_goods_list<=0&&$group_goods_list<=0&&$appoint_goods_list<=0){
                unset($store_list[$k]); 
            }
        }
        return $store_list?array_values($store_list):[];
    }
}
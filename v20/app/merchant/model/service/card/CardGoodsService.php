<?php
/**
 * 积分商品service
 */

namespace app\merchant\model\service\card;

use app\common\model\db\CardNewCoupon;
use app\common\model\service\coupon\MerchantCouponService;
use app\merchant\model\db\CardGoods;
use app\merchant\model\db\CardGoodsExchange;
use app\merchant\model\db\CardGoodsType;
use app\merchant\model\db\CardNewRecord;
use app\merchant\model\db\CardUserlist;
use app\merchant\model\db\Merchant;
use app\merchant\model\db\MerchantStoreStaff;
use think\facade\Db;

class CardGoodsService {
    public $cardGoodsModel = null;
    public $cardGoodsTypeModel = null;
    public $cardGoodsExchangeModel = null;
    public $cardNewCouponModel = null;
    public $cardUserlistModel = null;
    public $cardNewRecordModel = null;
    public function __construct()
    {
        $this->cardGoodsModel = new CardGoods();
        $this->cardGoodsTypeModel = new CardGoodsType();
        $this->cardGoodsExchangeModel = new CardGoodsExchange();
        $this->cardNewCouponModel = new CardNewCoupon();
        $this->cardUserlistModel = new CardUserlist();
        $this->cardNewRecordModel = new CardNewRecord();
    }

    public function goodsTypeList($params,$order='sort desc')
    {
        $key = $params['key'] ?? '';
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 0;
        $mer_id = $params['mer_id'] ?? 0;
        if(!$mer_id){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        $where[] = ['mer_id','=',$mer_id];
        $where[] = ['is_del','=',0];
        if($key){
            $where[] = ['name','like','%'.$key.'%'];
        }
        $field = 'id,name,sort,create_time';
        if($page){
            $data = $this->cardGoodsTypeModel->where($where)->field($field)->order($order)->paginate($pageSize);
        }else{
            $data = $this->cardGoodsTypeModel->where($where)->field($field)->order($order)->select()->toArray();
        }
        foreach ($data as &$v) {
            $v['create_time'] = date('Y.m.d H:i',$v['create_time']);
        }
        return $data;
    }

    public function goodsTypeAdd($params)
    {
        $params['mer_id'] = $params['mer_id'] ?? 0;
        $params['name'] = $params['name'] ?? '';
        $params['sort'] = $params['sort'] ?? 0;
        if(!$params['mer_id']){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$params['name']){
            throw new \think\Exception(L_("类型名称不能为空"), 1005);
        }
        $add = $this->cardGoodsTypeModel->insert([
            'mer_id'=>$params['mer_id'],
            'name'=>$params['name'],
            'sort'=>$params['sort'],
            'create_time'=>time()
        ]);
        if(!$add){
            throw new \think\Exception(L_("新增失败"), 1005);
        }
        return ['msg'=>'success'];
    }

    public function goodsTypeEdit($params)
    {
        $params['id'] = $params['id'] ?? 0;
        $params['mer_id'] = $params['mer_id'] ?? 0;
        $params['name'] = $params['name'] ?? '';
        $params['sort'] = $params['sort'] ?? 0;
        if(!$params['mer_id']){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$params['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        if(!$params['name']){
            throw new \think\Exception(L_("类型名称不能为空"), 1005);
        }
        $edit = $this->cardGoodsTypeModel->updateThis(['id'=>$params['id'],'mer_id'=>$params['mer_id']],[
            'name'=>$params['name'],
            'sort'=>$params['sort'],
            'update_time'=>time()
        ]);
        if(!$edit){
            throw new \think\Exception(L_("编辑失败"), 1005);
        }
        return ['msg'=>'success'];
    }

    public function goodsTypeDel($params)
    {
        $params['id'] = $params['id'] ?? [];
        $params['mer_id'] = $params['mer_id'] ?? 0;
        if(!$params['mer_id']){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$params['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        $edit = $this->cardGoodsTypeModel->updateThis([['id','IN',$params['id']],['mer_id','=',$params['mer_id']]],[
            'is_del'=>1,
            'update_time'=>time()
        ]);
        if(!$edit){
            throw new \think\Exception(L_("编辑失败"), 1005);
        }
        return ['msg'=>'success'];
    }

    public function goodsList($params)
    {
        $key = $params['key'] ?? '';
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 0;
        $mer_id = $params['mer_id'] ?? 0;
        $type = $params['type'] ?? 0;
        $goodsTypeId = $params['goods_type_id'] ?? 0;
        $order = $params['order'] ?? 'a.sort desc';
        if(!$mer_id){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        $where[] = ['a.mer_id','=',$mer_id];
        $where[] = ['a.is_del','=',0];
        if($key){
            $where[] = ['a.name','like','%'.$key.'%'];
        }
        if($goodsTypeId){
            $where[] = ['a.goods_type_id','=',$goodsTypeId];
        }
        if($type){
            $where[] = ['a.type','=',$type];
        }
        $field = 'a.id,a.name,a.type,a.points,a.num,a.address,a.sort,b.num as coupon_num,a.sale,a.image,b.img,b.had_pull,a.content';
        $data = $this->cardGoodsModel->goodsList($where,$field,$order,$pageSize);
        foreach ($data as &$v){
            $v['num'] = $v['num'] - $v['sale'];
            $v['num'] = $v['num'] < 0 ? 0 : $v['num'];
        }
        return $data;
    }

    public function couponList($params)
    {
        $mer_id = $params['mer_id'] ?? 0;
        if(!$mer_id){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        $where[] = ['mer_id','=',$mer_id];
        $where[] = ['status','=',1];
        $field = 'coupon_id,name';
        $order = 'coupon_id desc';
        $data = $this->cardNewCouponModel->where($where)->field($field)->order($order)->select()->toArray();
        return $data;
    }

    public function goodsAdd($params)
    {
        $params['mer_id'] = $params['mer_id'] ?? 0;
        $params['type'] = $params['type'] ?? '';
        $params['goods_type'] = $params['goods_type'] ?? 0;
        $params['points'] = $params['points'] ?? 0;
        $params['effective_days'] = $params['effective_days'] ?? 0;
        $params['sort'] = $params['sort'] ?? 0;
        $params['coupon_id'] = $params['coupon_id'] ?? '';
        $params['name'] = $params['name'] ?? '';
        $params['num'] = $params['num'] ?? 0;
        $params['address'] = $params['address'] ?? '';
        $params['long'] = $params['long'] ?? '';
        $params['lat'] = $params['lat'] ?? '';
        $params['image'] = $params['image'] ?? '';
        $params['content'] = $params['content'] ?? '';
        if(!$params['mer_id']){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$params['type']){
            throw new \think\Exception(L_("请选择类型"), 1005);
        }
        if(!$params['goods_type']){
            throw new \think\Exception(L_("请选择商品分类"), 1005);
        }
        if($params['type'] == 1){//商品
            if(!$params['name']){
                throw new \think\Exception(L_("商品名称不能为空"), 1005);
            }
            if(!$params['num']){
                throw new \think\Exception(L_("商品库存必须大于0"), 1005);
            }
            if(!$params['address']){
                throw new \think\Exception(L_("领取地址不能为空"), 1005);
            }
            if(!$params['long'] || !$params['lat']){
                throw new \think\Exception(L_("领取地址经纬度不能为空"), 1005);
            }
            if(!$params['image']){
                throw new \think\Exception(L_("商品图片不能为空"), 1005);
            }
            if(!$params['effective_days']){
                throw new \think\Exception(L_("有效天数必须大于0"), 1005);
            }
        }else{//优惠券
            if(!$params['coupon_id']){
                throw new \think\Exception(L_("请选择商家优惠券"), 1005);
            }
            //查询优惠券信息
            $couponInfo = $this->cardNewCouponModel->where('coupon_id',$params['coupon_id'])->field('name')->find();
            if(!$couponInfo){
                throw new \think\Exception(L_("未查询到优惠券信息"), 1005);
            }
            $params['name'] = $couponInfo['name'];
        }
        $add = $this->cardGoodsModel->insert([
            'mer_id'=>$params['mer_id'],
            'name'=>$params['name'],
            'type'=>$params['type'],
            'goods_type_id'=>$params['goods_type'],
            'points'=>$params['points'],
            'num'=>$params['num'],
            'address'=>$params['address'],
            'long'=>$params['long'],
            'lat'=>$params['lat'],
            'effective_days'=>$params['effective_days'],
            'image'=>$params['image'],
            'coupon_id'=>$params['coupon_id'],
            'sort'=>$params['sort'],
            'content'=>$params['content'],
            'create_time'=>time()
        ]);
        if(!$add){
            throw new \think\Exception(L_("新增失败"), 1005);
        }
        return ['msg'=>'success'];
    }

    public function goodsDetail($params)
    {
        $mer_id = $params['mer_id'] ?? 0;
        $uid = $params['uid'] ?? 0;
        $id = $params['id'] ?? 0;
        $cardId = $params['cardId'] ?? 0;
        if(!$mer_id && !$uid){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$id){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        $cardUserInfo = [];
        if($uid && $cardId){
            $cardUserInfo = $this->cardUserlistModel->where(['id'=>$cardId])->field('card_score,mer_id')->find();
            if(!$cardUserInfo){
                throw new \think\Exception(L_("未获取到会员卡信息"), 1005);
            }
        }
        $field = 'id,type,name,goods_type_id as goods_type,points,num,address,long,lat,effective_days,image,coupon_id,sort,sale,content';
        $data = $this->cardGoodsModel->where('id',$id)->field($field)->find();
        if(!$data){
            throw new \think\Exception(L_("未查询到操作对象"), 1005);
        }
        $data['num'] = $data['num'] - $data['sale'];
        //商品详情中图片返回全路径
        if($uid){
            $data['can_exchange'] = $cardUserInfo ? ($cardUserInfo['card_score']>=$data['points'] ? 1 : 0) : 0;
            //查询优惠券信息
            if($data['type'] == 2){
                $coupon = (new CardNewCoupon())->where('coupon_id',$data['coupon_id'])->field('img,num,had_pull')->find();
                if(!$coupon){
                    throw new \think\Exception(L_("未查询到优惠券信息"), 1005);
                }
                $data['image'] = $coupon['img'];
                $data['num'] = $coupon['num'] - $coupon['had_pull'];
            }
        }
        $data['num'] = $data['num'] < 0 ? 0 : $data['num'];
        $data['content'] = replace_file_domain_content_img(urldecode($data['content']));
        $data['image'] = replace_file_domain($data['image']);
        return $data;
    }

    public function goodsEdit($params)
    {
        $params['mer_id'] = $params['mer_id'] ?? 0;
        $params['id'] = $params['id'] ?? '';
        $params['type'] = $params['type'] ?? '';
        $params['goods_type'] = $params['goods_type'] ?? 0;
        $params['points'] = $params['points'] ?? 0;
        $params['effective_days'] = $params['effective_days'] ?? 0;
        $params['sort'] = $params['sort'] ?? 0;
        $params['coupon_id'] = $params['coupon_id'] ?? '';
        $params['name'] = $params['name'] ?? '';
        $params['num'] = $params['num'] ?? 0;
        $params['address'] = $params['address'] ?? '';
        $params['long'] = $params['long'] ?? '';
        $params['lat'] = $params['lat'] ?? '';
        $params['image'] = $params['image'] ?? '';
        $params['edit_type'] = $params['edit_type'] ?? 0;
        $params['content'] = $params['content'] ?? '';
        if(!$params['mer_id']){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$params['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        if($params['edit_type']){//修改排序
            $updateData = [
                'sort'=>$params['sort'],
                'create_time'=>time()
            ];
        }else{
            if(!$params['type']){
                throw new \think\Exception(L_("请选择类型"), 1005);
            }
            if(!$params['goods_type']){
                throw new \think\Exception(L_("请选择商品分类"), 1005);
            }
            if($params['type'] == 1){//商品
                if(!$params['name']){
                    throw new \think\Exception(L_("商品名称不能为空"), 1005);
                }
                if(!$params['num']){
                    throw new \think\Exception(L_("商品库存必须大于0"), 1005);
                }
                if(!$params['address']){
                    throw new \think\Exception(L_("领取地址不能为空"), 1005);
                }
                if(!$params['long'] || !$params['lat']){
                    throw new \think\Exception(L_("领取地址经纬度不能为空"), 1005);
                }
                if(!$params['image']){
                    throw new \think\Exception(L_("商品图片不能为空"), 1005);
                }
                if(!$params['effective_days']){
                    throw new \think\Exception(L_("有效天数必须大于0"), 1005);
                }
            }else{//优惠券
                if(!$params['coupon_id']){
                    throw new \think\Exception(L_("请选择商家优惠券"), 1005);
                }
            }
            //查询商品信息
            $goodsInfo = $this->cardGoodsModel->where(['id'=>$params['id'],'mer_id'=>$params['mer_id']])->field('sale')->find();
            if(!$goodsInfo){
                throw new \think\Exception(L_("未查询到商品信息"), 1005);
            }
            $sale = $goodsInfo['sale'];
            $updateData = [
                'name'=>$params['name'],
                'type'=>$params['type'],
                'goods_type_id'=>$params['goods_type'],
                'points'=>$params['points'],
                'num'=>$params['num']+$sale,
                'address'=>$params['address'],
                'long'=>$params['long'],
                'lat'=>$params['lat'],
                'effective_days'=>$params['effective_days'],
                'image'=>$params['image'],
                'coupon_id'=>$params['coupon_id'],
                'sort'=>$params['sort'],
                'content'=>$params['content'],
                'create_time'=>time()
            ];
        }
        $add = $this->cardGoodsModel->updateThis(['id'=>$params['id'],'mer_id'=>$params['mer_id']],$updateData);
        if(!$add){
            throw new \think\Exception(L_("新增失败"), 1005);
        }
        return ['msg'=>'success'];
    }

    public function goodsDel($params)
    {
        $params['id'] = $params['id'] ?? [];
        $params['mer_id'] = $params['mer_id'] ?? 0;
        if(!$params['mer_id']){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$params['id']){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        $edit = $this->cardGoodsModel->updateThis([['id','IN',$params['id']],['mer_id','=',$params['mer_id']]],[
            'is_del'=>1,
            'update_time'=>time()
        ]);
        if(!$edit){
            throw new \think\Exception(L_("编辑失败"), 1005);
        }
        return ['msg'=>'success'];
    }

    public function goodsExchangeList($params)
    {
        $key = $params['key'] ?? '';
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 0;
        $mer_id = $params['mer_id'] ?? 0;
        $type = $params['type'] ?? 0;
        if(!$mer_id){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        $where[] = ['a.mer_id','=',$mer_id];
        if($key){
            $where[] = ['b.name','like','%'.$key.'%'];
        }
        if($type){
            $where[] = ['b.type','=',$type];
        }
        $field = 'a.id,b.name,b.type,b.points,a.num,a.create_time,a.use_time,a.staff_id,a.status';
        $order = 'a.id desc';
        $data = $this->cardGoodsExchangeModel->goodsExchangeList($where,[],$field,$order,$pageSize);
        foreach ($data as &$v){
            $v['use_time'] = $v['use_time']?date('Y-m-d H:i',$v['use_time']):'';
            $staff_name = '';
            if($v['status']==1&&$v['staff_id']){
                $staff_name = (new MerchantStoreStaff())->where(['id'=>$v['staff_id']])->value('name');
            }
            $v['staff_name'] = $staff_name?:'';
            $v['type'] = $v['type'] == 1 ? '商品' : '优惠券';
            $v['create_time'] = date('Y.m.d H:i',$v['create_time']);
        }
        return $data;
    }

    public function mine($params)
    {
        $uid = $params['uid'] ?? 0;
        $userInfo = $params['userInfo'] ?? [];
        $nickname = $userInfo ? ($userInfo['nickname'] ?: $userInfo['phone']) : '用户';
        $avatar = $userInfo ? ($userInfo['avatar'] ?: cfg('site_url').'/static/images/user_avatar.jpg') : cfg('site_url').'/static/images/user_avatar.jpg';
        $cardId = $params['cardId'] ?? '';
        if(!$uid){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$cardId){
            throw new \think\Exception(L_("未获取到会员卡号"), 1005);
        }
        //获取我的信息
        $data = [
            'nickname' => $nickname,
            'avatar' => $avatar,
            'score' => 0,//总积分
            'score_today' => 0,//今日积分收入
            'score_yesterday' => 0,//昨日积分收入
            'type_list'=>[],//类型列表
            'mer_id' => 0
        ];
        $cardUserInfo = $this->cardUserlistModel->where(['id'=>$cardId])->field('card_score,mer_id')->find();
        if(!$cardUserInfo){
            throw new \think\Exception(L_("未获取到会员卡信息"), 1005);
        }
        $data['mer_id'] = $cardUserInfo['mer_id'];
        $data['score'] = $cardUserInfo['card_score'];
        $todayTimeS = strtotime(date('Y-m-d'));
        $todayTimeE = strtotime(date('Y-m-d 23:59:59'));
        $yesterdayTimeS = strtotime(date('Y-m-d', strtotime('-1 day')));
        //查询昨日和今日积分收入
        $data['score_today'] = $this->cardNewRecordModel->where([['time','>=',$todayTimeS],['time','<=',$todayTimeE],['card_id','=',$cardId]])->sum('score_add');
        $data['score_yesterday'] = $this->cardNewRecordModel->where([['time','>=',$yesterdayTimeS],['time','<',$todayTimeS],['card_id','=',$cardId]])->sum('score_add');
        //获取商品类型列表
        $list = $this->goodsTypeList(['mer_id'=>$cardUserInfo['mer_id']],'sort desc');
        $data['type_list'] = $list;
        return $data;
    }

    public function getGoods($params)
    {
        $cardId = $params['cardId'] ?? '';
        $type = $params['type'] ?? 0;
        $pageSize = $params['pageSize'] ?? 10;
        $page = $params['page'] ?? 0;
        if(!$type){
            throw new \think\Exception(L_("未获取到商品类型"), 1005);
        }
        $cardUserInfo = $this->cardUserlistModel->where(['id'=>$cardId])->field('card_score,mer_id')->find();
        if(!$cardUserInfo){
            throw new \think\Exception(L_("未获取到会员卡信息"), 1005);
        }
        $merId = $cardUserInfo['mer_id'];
        $score = $cardUserInfo['card_score'];
        $data = $this->goodsList(['page'=>$page,'pageSize'=>$pageSize,'goods_type_id'=>$type,'mer_id'=>$merId,'order'=>'a.sort desc']);
        foreach ($data as &$v) {
            $v['image'] = replace_file_domain($v['image']);
            $v['can_exchange'] = ($score >= $v['points']) ? 1 : 0;
            unset($v['sale']);
        }
        return $data;
    }

    public function exchange($params)
    {
        $uid = $params['uid'] ?? '';
        $num = $params['num'] ?? 1;
        $cardId = $params['cardId'] ?? '';
        $goodsId = $params['goods_id'] ?? 0;
        $cardUserInfo = $this->cardUserlistModel->where(['id'=>$cardId])->field('card_score,mer_id')->find();
        if(!$cardUserInfo){
            throw new \think\Exception(L_("未获取到会员卡信息"), 1005);
        }
        $merId = $cardUserInfo['mer_id'];
        $score = $cardUserInfo['card_score'];
        //查询商品信息
        $field = "is_del,num,sale,effective_days,points,type,coupon_id,name";
        $goodsInfo = $this->cardGoodsModel->where('id',$goodsId)->field($field)->find();
        if(!$goodsInfo){
            throw new \think\Exception(L_("未获取到商品信息！"), 1005);
        }
        $goodsNum = $goodsInfo['num'];
        $goodsSale = $goodsInfo['sale'];
        $goodsName = $goodsInfo['name'];
        $effectiveDay = time()+$goodsInfo['effective_days']*24*60*60;
        if($goodsInfo['type'] == 2){//获取优惠券信息
            $couponInfo = $this->cardNewCouponModel->where('coupon_id',$goodsInfo['coupon_id'])->field('status,end_time,num,had_pull')->find();
            if(!$couponInfo){
                throw new \think\Exception(L_("未获取到优惠券信息！"), 1005);
            }
            if($couponInfo['status'] == 0){
                throw new \think\Exception(L_("优惠券未启用，无法兑换！"), 1005);
            }
            if($couponInfo['status'] == 2){
                throw new \think\Exception(L_("优惠券已过期，兑换失败！"), 1005);
            }
            if($couponInfo['status'] == 3){
                throw new \think\Exception(L_("优惠券已被领空，兑换失败！"), 1005);
            }
            $effectiveDay = $couponInfo['end_time'];
            $goodsNum = $couponInfo['num'];
            $goodsSale = $couponInfo['had_pull'];
        }
        if($goodsInfo['is_del'] == 1){
            throw new \think\Exception(L_("商品已被删除，请刷新页面！"), 1005);
        }
        if($goodsInfo['points'] > $score){
            throw new \think\Exception(L_("积分不足，兑换失败！"), 1005);
        }
        $nowNum = $goodsNum - $goodsSale;
        if($nowNum < $num){
            throw new \think\Exception(L_("库存不足，兑换失败！"), 1005);
        }
        $sign = date('YmdHis').rand(1000,9999);
        Db::startTrans();
        try {
            //生成积分变动记录
            $addCardNewRecord = $this->cardNewRecordModel->insert([
                'card_id'=>$cardId,
                'type'=>2,
                'score_use'=>$goodsInfo['points'],
                'time'=>time(),
                'order_id'=>$sign,
                'order_type'=>30,
                'desc'=>'兑换商品【'.$goodsName.'】支付'.$goodsInfo['points'].'积分',
            ]);
            if(!$addCardNewRecord){
                throw new \think\Exception(L_("生成积分变动记录失败！"), 1005);
            }

            //库存减1
            $update = $this->cardGoodsModel->updateThis(['id'=>$goodsId],[
                'sale' => $goodsInfo['sale']+1,
                'update_time' => time()
            ]);
            if(!$update){
                throw new \think\Exception(L_("库存变动失败！"), 1005);
            }

            //积分总数减
            if($goodsInfo['points'] > 0){
                $updateScore = $this->cardUserlistModel->setDec(['id'=>$cardId],'card_score',$goodsInfo['points']);
                if(!$updateScore){
                    throw new \think\Exception(L_("积分总数变动失败！"), 1005);
                }
            }
            $couponRecordId = 0;
            if($goodsInfo['type'] == 2){//优惠券领取
                $couponInfo = (new MerchantCouponService())->receiveCoupon($uid,$goodsInfo['coupon_id']);
                if(!$couponInfo){
                    throw new \think\Exception(L_("优惠券兑换失败！"), 1005);
                }
                $couponRecordId = $couponInfo['add_id'];
            }
            //生成兑换记录
            $addExchange = $this->cardGoodsExchangeModel->insert([
                'mer_id' => $merId,
                'sign' => $sign,
                'goods_id' => $goodsId,
                'uid' => $uid,
                'num' => $num,
                'card_id' => $cardId,
                'coupon_record_id' => $couponRecordId,
                'create_time' => time(),
                'closing_time' => $effectiveDay
            ]);
            if(!$addExchange){
                throw new \think\Exception(L_("生成兑换记录失败！"), 1005);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
        return ['sign'=>$sign];
    }

    public function exchangeList($params)
    {
        $cardId = $params['cardId'] ?? '';
        $pageSize = $params['pageSize'] ?? 10;
        $type = $params['type'] ?? 1;
        $cardUserInfo = $this->cardUserlistModel->where(['id'=>$cardId])->field('card_score,mer_id')->find();
        if(!$cardUserInfo){
            throw new \think\Exception(L_("未获取到会员卡信息"), 1005);
        }
        $where[] = ['a.mer_id','=',$cardUserInfo['mer_id']];
        $where[] = ['a.card_id','=',$cardId];
        $field = 'a.id,a.goods_id,b.name,b.type,b.points,a.num,b.image,a.closing_time,b.address,b.long,b.lat,c.coupon_url,a.status,c.img,c.coupon_id,e.is_use';
        $order = 'a.id desc';
        $whereOr = [];
        if($type == 2){//已使用
            $where[] = ['a.status|e.is_use','=',1];
        }else{
            $where[] = ['a.status','=',0];
        }
        if($type == 1 || !$type){//未使用
            $where[] = ['a.closing_time','>=',time()];
            $whereOr = $where;
            $where[] = ['e.is_use','<>',1];
            $whereOr[] = ['e.is_use','=',null];
        }elseif($type == 3){//已过期
            $where[] = ['a.closing_time','<',time()];
            $whereOr = $where;
            $where[] = ['e.is_use','<>',1];
            $whereOr[] = ['e.is_use','=',null];
        }

        $item = $this->cardGoodsExchangeModel->goodsExchangeList($where,$whereOr,$field,$order,$pageSize);
        foreach ($item as &$v){
            if($v['status'] == 1){
                $v['status'] = 2;
            }else{
                if($v['type'] == 1){//商品
                    $v['status'] = ($v['closing_time'] >= time()) ? 1 : 3;
                }else{//优惠券
                    $v['status'] = $v['is_use'] ? 2 : (($v['closing_time']>= time()) ? 1 : 3);
                }
            }
            $v['image'] = $v['type'] == 1 ? replace_file_domain($v['image']) : replace_file_domain($v['img']);
            $v['closing_time'] = $v['closing_time'] ? date('Y.m.d H:i:s',$v['closing_time']) : 0;
            $v['use_url'] = $v['type'] == 1 ? get_base_url('pages/my/pointExchange/exchangeDetail?id='.$v['id']) : ($v['coupon_url'] ?: get_base_url('pages/store/storeList?coupon_id=' . $v['coupon_id']));
            $v['type_msg'] = $v['type'] == 1 ? '商品' : '优惠券';
            unset($v['coupon_url'],$v['img']);
        }
        $data = [
            'list'=>[
                [
                'id'=>1,
                'title'=>'未使用',
                ],
                [
                'id'=>2,
                'title'=>'已使用',
                ],
                [
                'id'=>3,
                'title'=>'已过期',
                ]
            ],
            'item'=>$item
        ];
        return $data;
    }

    public function exchangeDetail($params)
    {
        $id = $params['id'] ?? 0;
        if(!$id){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        $where = ['a.id'=>$id];
        $field = 'a.id,a.sign,b.type,b.name,b.image,a.num,a.closing_time,a.mer_id as mer_name,b.address,b.long,b.lat,a.status,b.points,a.create_time,a.use_time';
        $data = $this->cardGoodsExchangeModel->exchangeDetail($where,$field);
        if(!$data){
            throw new \think\Exception(L_("未查询到兑换记录"), 1005);
        }
        //判断当前状态
        if($data['status'] != 1){
            $data['status'] = ($data['closing_time'] >= time()) ? 0 : 2;
        }
        $data['closing_time'] = $data['closing_time'] ? date('Y-m-d H:i:s',$data['closing_time']) : '';
        //获取商家名称和联系电话
        $merchantInfo = (new Merchant())->where('mer_id',$data['mer_name'])->field('name,phone')->find();
        if(!$merchantInfo){
            throw new \think\Exception(L_("未查询到商家信息"), 1005);
        }
        $data['mer_name'] = $merchantInfo['name'];
        $data['phone'] = $merchantInfo['phone'];
        $data['image'] = replace_file_domain($data['image']);
        $data['qr_code'] = $this->getQrCode('cardgoods_'.$data['sign']);
        $data['create_time'] = $data['create_time']?date('Y-m-d H:i',$data['create_time']):'';
        $data['use_time'] = $data['use_time']?date('Y-m-d H:i',$data['use_time']):'';
        return $data;
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        require_once '../extend/phpqrcode/phpqrcode.php';
        $date = date('Y-m-d');
        $time = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/card/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/card/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/card/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;
    }

    public function scoreList($params)
    {
        $cardId = $params['cardId'] ?? '';
        $pageSize = $params['pageSize'] ?? 10;
        $where[] = ['card_id','=',$cardId];
        $where[] = ['score_add|score_use','>',0];
        $field = 'id,desc,type,score_add,score_use,time';
        $data = $this->cardNewRecordModel->where($where)->field($field)->order('id desc')->paginate($pageSize);
        foreach ($data as &$v) {
            $v['is_add'] = $v['type'] == 1 ? 1 : 0;
            $v['score'] = $v['type'] == 1 ? $v['score_add'] : $v['score_use'];
            $v['create_time'] = $v['time'] ? date('Y-m-d H:i:s',$v['time']) : '';
            unset($v['type'],$v['score_add'],$v['score_use'],$v['time']);
        }
        return $data;
    }

    public function exchangeStaffList($params)
    {
        $merId = $params['mer_id'] ?? '';
        $pageSize = $params['pageSize'] ?? 10;
        $key = $params['key'] ?? '';
        if(!$merId){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        //获取该商家下的所有商品兑换
        $where[] = ['a.mer_id','=',$merId];
        $where[] = ['b.type','=',1];
        if($key){
            $where[] = ['b.name|d.nickname|d.phone','=',$key];
        }
        $field = 'a.id,b.name,b.image,b.points,a.num,d.nickname,d.phone,a.closing_time,a.status,a.use_time,a.staff_id,a.create_time';
        $order = 'a.id desc';
        $data = $this->cardGoodsExchangeModel->goodsExchangeList($where,[],$field,$order,$pageSize);
        foreach ($data as &$v){
            $v['use_time'] = $v['use_time']?date('Y-m-d H:i',$v['use_time']):'';
            $v['create_time'] = $v['create_time']?date('Y-m-d H:i',$v['create_time']):'';
            $staff_name = '';
            if($v['status']==1&&$v['staff_id']){
                $staff_name = (new MerchantStoreStaff())->where(['id'=>$v['staff_id']])->value('name');
            }
            $v['staff_name'] = $staff_name?:'';
            $v['status'] = $v['status']==1 ? 2 : ($v['closing_time'] >= time() ? 1 : 3);
            $v['image'] = replace_file_domain($v['image']);
            unset($v['closing_time']);
        }
        return $data;
    }

    public function useOrder($params)
    {
        $staffId = $params['staff_id'] ?? '';
        $id = $params['id'] ?? '';
        $sign = $params['sign'] ?? '';
        if(!$staffId){
            throw new \think\Exception(L_("未登录"), 1005);
        }
        if(!$id && !$sign){
            throw new \think\Exception(L_("操作对象不能为空"), 1005);
        }
        if($id){
            $where = ['id'=>$id];
        }
        if($sign){
            $where = ['sign'=>$sign];
        }
        //查询兑换记录
        $info = $this->cardGoodsExchangeModel->where($where)->field('closing_time,mer_id,status')->find();
        if(!$info){
            throw new \think\Exception(L_("未查询到操作记录信息"), 1005);
        }
        if($info['status'] == 1){
            throw new \think\Exception(L_("已兑换，请勿重复操作"), 1005);
        }
        if($info['closing_time'] < time()){
            throw new \think\Exception(L_("已过期，兑换失败"), 1005);
        }
//        Db::startTrans();
//        try {
            //修改兑换记录
            $update = $this->cardGoodsExchangeModel->updateThis($where,[
                'status'=>1,
                'staff_id'=>$staffId,
                'use_time'=>time()
            ]);
            if(!$update){
                throw new \think\Exception(L_("兑换失败!"), 1005);
            }
//            Db::commit();
//        } catch (\Exception $e) {
//            Db::rollback();
//            throw new \think\Exception($e->getMessage());
//        }
        return true;
    }
}
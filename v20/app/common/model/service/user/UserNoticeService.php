<?php


namespace app\common\model\service\user;

use app\appoint\model\db\Appoint;
use app\common\model\db\AppointOrder;
use app\common\model\db\MerchantStore;
use app\common\model\db\ShopOrder;
use app\common\model\db\UserNotice;
use app\common\model\db\UserNoticeType;
use app\common\model\db\VillageGroupGoods;
use app\common\model\db\VillageGroupOrder;
use app\group\model\db\Group;
use app\group\model\db\GroupOrder;
use app\life_tools\model\db\LifeTools;
use app\mall\model\db\MallOrder;
use app\villageGroup\model\db\VillageGroupOrderDetail;
use app\villageGroup\model\db\VillageGroupPickAddress;
use think\facade\Db;

class UserNoticeService
{
    /**
     * 查询消息类型列表
     */
    public function getNoticeType($param)
    {
        $where = [];
        $where['a.is_del'] = 0;
        $where['a.uid'] = $param['uid'];
        $list = (new UserNoticeType())->getList($where,'a.id,a.type,a.store_id,a.new_time,a.content,b.name as title,b.logo as store_img,b.pic_info as mer_img,a.is_top',$param['pageSize']);

        $list['total_num'] = 0;
        foreach ($list['data'] as &$v){
            $whereNum = [];
            $whereNum[] = ['is_read','=',0];
            $whereNum[] = ['uid','=',$param['uid']];
            $whereNum[] = ['type','=',$v['type']];
            $v['new_time'] = $v['new_time'] > strtotime(date('Y-m-d')) ? date('H:i',$v['new_time']) : date('Y-m-d',$v['new_time']);
            $v['link'] = '';
            if($v['type'] > 0){
                $v['content'] = json_decode($v['content']);
                if(isset($v['content']->type) && $v['content']->type == 'image'){
                    $v['content'] = '[图片]';
                }elseif(isset($v['content']->type) && $v['content']->type == 'recommend_goods'){
                    $v['content'] = '[商品]';
                }elseif(isset($v['content']->type) && $v['content']->type == 'text'){
                    $v['content'] = $v['content']->text;
                }elseif(isset($v['content']->type) && $v['content']->type == 'video'){
                    $v['content'] = '[视频]';
                }else{
                    $v['content'] = '[消息]';
                }
                if($v['type'] == 4){//分类审核结果
                    $v['title'] = cfg('classify_name').'通知';
                    $v['img'] = cfg('site_url').'/static/user-notice/reject.png';
                    $v['link'] ='';
                }else
                if($v['type'] == 2){//平台客服
                    $toUser = (new UserNotice())->where(['uid'=>$param['uid'],'type'=>2])->order('id desc')->value('kefu_id');
                    $v['title'] = '平台客服';
                    $v['img'] = cfg('site_url').'/static/user-notice/platform.png';
                    $v['link'] = build_im_chat_url('user_'.$param['uid'],'plat_0_'.$toUser,'user2plat');
                }else{//店铺客服
                    $whereNum[] = ['store_id','=',$v['store_id']];
                    $toUser = (new UserNotice())->where(['uid'=>$param['uid'],'type'=>1])->order('id desc')->value('kefu_id');
                    $v['img'] = $v['store_img'] ?: $v['mer_img'];
                    $v['img'] = replace_file_domain($v['img']);
                    $v['link'] = build_im_chat_url('user_'.$param['uid'],'store_'.$v['store_id'],'user2store',['from'=>'meal']);
                }
            }else{//订单动态
                $v['title'] = '订单动态';
                $v['img'] = cfg('site_url').'/static/user-notice/order.png';
            }

            if($v['type'] == 0){
                $v['num'] = (new UserNotice())->where($whereNum)->group('order_id,business')->count();
            }else{
                $v['num'] = (new UserNotice())->where($whereNum)->count();
            }
            unset($v['store_img']);
            unset($v['mer_img']);
            $list['total_num'] = intval(bcadd($list['total_num'],$v['num'],0));
            $v['num'] = $v['num']>99 ? '99+' : $v['num'];
        }
        $list['total_num'] = $list['total_num']>99 ? '99+' : $list['total_num'];
        return $list;
    }

    /**
     * 消息类型-置顶
     */
    public function topNoticeType($param)
    {
        $noticeType = (new UserNoticeType())->where(['id'=>$param['id'],'is_del'=>0])->find();
        if(!$noticeType){
            throw new \think\Exception('消息板块不存在或者已删除，请刷新后重试!');
        }
        if($noticeType['uid'] != $param['uid']){
            throw new \think\Exception('非本账号消息板块无法操作，请刷新后重试!');
        }
        $update = (new UserNoticeType())->where('id',$param['id'])->update([
            'is_top'=>$param['status'],
            'update_time'=>time()
        ]);
        if(!$update){
            throw new \think\Exception('操作失败，请刷新后重试!');
        }
        return [];
    }

    /**
     * 消息类型-删除
     */
    public function delNoticeType($param)
    {
        $noticeType = (new UserNoticeType())->where(['id'=>$param['id'],'is_del'=>0])->find();
        if(!$noticeType){
            throw new \think\Exception('消息板块不存在或者已删除，请刷新后重试!');
        }
        if($noticeType['uid'] != $param['uid']){
            throw new \think\Exception('非本账号消息板块无法操作，请刷新后重试!');
        }
        $update = (new UserNoticeType())->where('id',$param['id'])->update([
            'is_del'=>1,
            'update_time'=>time()
        ]);
        if(!$update){
            throw new \think\Exception('删除失败，请刷新后重试!');
        }
        return [];
    }

    /**
     * 查询订单消息列表（查询是否已读，没的话，改为已读）
     */
    public function getOrderNoticeList($param)
    {
        $where = [];
        $where['type'] = $param['type'];
        $where['uid'] = $param['uid'];
        $field = 'id,business,order_id,store_id,tools_id,title,content,create_time,is_read,village_id';
        $list = (new UserNotice())->getList($where,$field,$param['pageSize']);
        
        foreach ($list['data'] as &$v){
            $name = '';
            $img = '';
            $v['business_msg'] = (new UserNotice())->getBusiness($v['business']);
            //查询店铺名称/团购社区名称/景区名称,图片
            if(in_array($v['business'],['mall','shop','group','appoint']) && $v['store_id']){
                $atoreInfo = (new MerchantStore())->where('store_id',$v['store_id'])->field('name,logo,pic_info')->find();
                $name = $atoreInfo['name'] ?? '';
                $img = $atoreInfo['logo'] ?? '';
                if(!$img && isset($atoreInfo['pic_info'])){
                    $img = $atoreInfo['pic_info'];
                }
            }elseif($v['business'] == 'village_group'){
                $name = $v['village_id'] ? (new VillageGroupPickAddress())->where('id',$v['village_id'])->value('name') : '';
            }elseif($v['business'] == 'scenic'){
                $name = $v['tools_id'] ? (new LifeTools())->where('tools_id',$v['tools_id'])->value('title') : '';
                $img = $v['tools_id'] ? (new LifeTools())->where('tools_id',$v['tools_id'])->value('cover_image') : '';
            }
            $appointId = 0;
            if(in_array($v['business'],['group','village_group','appoint'])){
                $orderInfo = $this->getOrderInfo($v['business'],$v['order_id']);
                $appointId = $orderInfo['appoint_id'] ?? 0;
                $img = $orderInfo['img'] ?? '';
            }
            $v['store_name'] = $name;
            $v['img'] = $img ? replace_file_domain($img) : '';
            $v['create_time'] = $v['create_time'] ? date('Y.m.d H:i',$v['create_time']) : '';
            $v['link'] = $this->getBusinessLink($v['business'],$v['order_id'],$appointId);
            $v['icon'] = $this->getBusinessIcon($v['business']);
            if(!$v['is_read']){
                $this->readNotice(['order_id'=>$v['order_id'],'business'=>$v['business']]);
            }
        }
        //修改已读状态
        return $list;
    }

    /**
     * 获取订单信息
     */
    public function getOrderInfo($business,$orderId)
    {
        $orderInfo = [];
        switch ($business){
            case 'mall':
                $orderInfo = (new MallOrder())->where('order_id',$orderId)->field('mer_id,store_id,uid')->find();
                break;
            case 'shop':
                $orderInfo = (new ShopOrder())->where('order_id',$orderId)->field('mer_id,store_id,uid')->find();
                break;
            case 'group':
                $orderInfo = (new GroupOrder())->where('order_id',$orderId)->field('mer_id,store_id,uid,group_id')->find();
                if($orderInfo){
                    $img = (new Group())->where('group_id',$orderInfo['group_id'])->value('pic');
                    $img = explode(';',$img)[0];
                    $orderInfo['img'] = $img;
                }
                break;
            case 'village_group':
                $orderInfo = (new VillageGroupOrder())->where('order_id',$orderId)->field('uid,start_uid')->find();
                if($orderInfo){
                    $villageId = (new VillageGroupPickAddress())->where('header_uid',$orderInfo['start_uid'])->value('id');
                    $orderInfo['village_id'] = $villageId;
                    $goodsId = (new VillageGroupOrderDetail())->where('order_id',$orderId)->value('goods_id');
                    $img = $goodsId ? (new VillageGroupGoods())->where('goods_id',$goodsId)->value('image') : '';
                    $orderInfo['img'] = $img;
                }
                break;
            case 'appoint':
                $orderInfo = (new AppointOrder())->where('order_id',$orderId)->field('appoint_id')->find();
                if($orderInfo){
                    $img = (new Appoint())->where('appoint_id',$orderInfo['appoint_id'])->value('pic');
                    $img = explode(';',$img)[0];
                    $orderInfo['img'] = $img;
                }
                break;
        }
        return $orderInfo;
    }


    /**
     * 获取不同业务的跳转链接
     */
    public function getBusinessLink($business,$orderId,$appointId=0)
    {
        $link = '';
        switch ($business){
            case 'mall':
                $link = get_base_url('pages/shopmall_third/orderDetails?order_id='.$orderId);
                break;
            case 'shop':
                $link = get_base_url('pages/shop_new/orderDetail/orderDetail?order_id='.$orderId);
                break;
            case 'group':
                $link = cfg("site_url")."/wap.php?c=Groupnew&a=order_detail&order_id=".$orderId;
                break;
            case 'village_group':
                $link = cfg("site_url")."/wap.php?c=Village_group&a=orderDetail&order_id=".$orderId;
                break;
            case 'appoint':
                $link = get_base_url('pages/appoint/goods/submitDetail?orderid='.$orderId.'&appoint_id='.$appointId);
                break;
            case 'scenic':
                $link = get_base_url('pages/lifeTools/order/orderDetail?order_id='.$orderId);
                break;
        }
        return $link;
    }
    
    /**
     * 获取不同业务的图标
     */
    public function getBusinessIcon($business)
    {
        $icon = '';
        switch ($business){
            case 'mall':
                $icon = rtrim(cfg('site_url'),'/').'/static/user-notice/mall.png';
                break;
            case 'shop':
                $icon = rtrim(cfg('site_url'),'/').'/static/user-notice/shop.png';
                break;
            case 'group':
                $icon = rtrim(cfg('site_url'),'/').'/static/user-notice/group.png';
                break;
            case 'village_group':
                $icon = rtrim(cfg('site_url'),'/').'/static/user-notice/village_group.png';
                break;
            case 'appoint':
                $icon = rtrim(cfg('site_url'),'/').'/static/user-notice/appoint.png';
                break;
            case 'scenic':
                $icon = rtrim(cfg('site_url'),'/').'/static/user-notice/scenic.png';
                break;
        }
        return $icon;
    }
    
    /**
     * 新增用户订单消息通知
     */
    public function addNotice($param)
    {
        fdump_api($param,'userNotice',1);
        $is_throw_err = $param['is_throw_err']??1;
        $addData = [];
        $addData['type'] = $param['type'];
        $addData['create_time'] = time();
        $addData['uid'] = $param['uid']??0;
        $where = [];
        if($param['type'] == 0){//订单状态
            $addData['business'] = $param['business'];
            $addData['order_id'] = $param['order_id'];
            $addData['mer_id'] = $param['mer_id']??0;
            $addData['store_id'] = $param['store_id']??0;
            $addData['tools_id'] = $param['tools_id']??0;
            $addData['village_id'] = $param['village_id']??0;
            $addData['title'] = $param['title']??'';
            $addData['content'] = $param['content']??'';
            $addData['create_time'] = time();
            if(!$addData['uid']){
                $orderInfo = $this->getOrderInfo($param['business'],$param['order_id']);
                if(!$orderInfo){
                    return false;
                }
                $addData['uid'] = $orderInfo['uid'];
                $addData['mer_id'] = $addData['mer_id'] ?: ($orderInfo['mer_id']??0);
                $addData['store_id'] = $addData['store_id'] ?: ($orderInfo['store_id']??0);
                $addData['tools_id'] = $addData['tools_id'] ?: ($orderInfo['tools_id']??0);
                $addData['village_id'] = $addData['village_id'] ?: ($orderInfo['village_id']??0);
            }
        }elseif($param['type'] == 1){//店铺客服
            $where['store_id'] = $param['store_id'];
            
            $addData['store_id'] = $param['store_id'];
            $addData['kefu_id'] = $param['kefu_id'];
            $addData['content'] = $param['content'];
            $addData['is_read'] = $param['is_read']??0;
        }elseif($param['type'] == 2){//平台客服
            $addData['kefu_id'] = $param['kefu_id'];
            $addData['content'] = $param['content'];
            $addData['is_read'] = $param['is_read']??0;
        }
        
        //查询消息板块
        $where['uid'] = $addData['uid'];
        $where['type'] = $param['type'];
        $userNoticeTypeId = (new UserNoticeType())->where($where)->value('id');
        
        Db::startTrans();
        try {
            //写入消息通知列表
            $add = (new UserNotice())->insert($addData);
            if(!$add){
                fdump_api([$addData,$add,(new UserNotice())->getLastSql()],'userNoticeError',1);
                if($is_throw_err){
                    throw new \think\Exception('写入消息通知列表失败');
                }
            }
            if(!$userNoticeTypeId){//创建消息板块
                $isTop = $param['type']==0 ? 1 : 0;
                $update = (new UserNoticeType())->insert([
                    'type'=>$param['type'],
                    'store_id'=>$param['store_id']??0,
                    'update_time'=>time(),
                    'new_time'=>time(),
                    'content'=>$param['content'],
                    'is_del'=>0,
                    'is_top'=>$isTop,
                    'uid'=>$param['uid']
                ]);
            }else{//编辑消息最新更新时间
                $update = (new UserNoticeType())->where('id',$userNoticeTypeId)->update([
                    'update_time'=>time(),
                    'new_time'=>time(),
                    'content'=>$param['content'],
                    'is_del'=>0
                ]);
            }
            if(!$update){
                fdump_api((new UserNoticeType())->getLastSql(),'userNoticeError',1);
//                throw new \think\Exception('编辑消息板块信息失败');
            }
            Db::commit();
            fdump_api([$param,'成功'],'userNoticeError',1);
        } catch (\Exception $e) {
            Db::rollback();
            fdump_api([$param,$e->getMessage()],'userNoticeError',1);
            if($is_throw_err){
                throw new \think\Exception($e->getMessage());
            }
        }
        return [];
    }
    
    /**
     * 消息读取
     */
    public function readNotice($param)
    {
        fdump_api($param,'readUserNotice',1);
        $id = $param['order_id'] ?? 0;
        $business = $param['business'] ?? '';
        $uid = $param['uid'] ?? 0;
        $readAll = $param['read_all'] ?? 0;
        $where = [];
        $where[] = ['is_read','=',0];
        if($uid && $readAll){//全部已读
            $where[] = ['uid','=',$uid];
        }elseif($id && $business){
            $where[] = ['order_id','=',$id];
            $where[] = ['business','=',$business];
        }else{//平台或者客服消息读取时，全部读取
            $type = $param['type'] ?: 1;
            $store_id = $param['store_id'] ?: 0;
            $kefu_id = $param['kefu_id'] ??null;
            $uid = $param['uid'] ?: 0;
            $where[] = ['type','=',$type];
            if($type == 1){
                $where[] = ['store_id','=',$store_id];
            }
            if (!is_null($kefu_id)) {
                $where[] = ['kefu_id', '=', $kefu_id];
            }
           
            $where[] = ['uid','=',$uid];
            $where[] = ['is_read','=',0];
        }
        (new UserNotice())->where($where)->update([
            'is_read'=>1,
            'read_time'=>time()
        ]);
        return true;
    }
    
    /**
     * 增加消息板块
     */
    public function addNoticeType($param)
    {
        $uid = $param['uid'] ?? 0;
        $type = $param['type'] ?? 2;
        $storeId = $param['store_id'] ?? 0;
        if(!$uid){
            return false;
        }
        $where = ['uid'=>$uid,'type'=>$type];
        if($type == 1 && $storeId){
            $where['store_id'] = $storeId;
        }
        //查询消息板块是否已存在
        $noticeType = (new UserNoticeType())->where($where)->find();
        if(!$noticeType){//新增板块
            $adData = [
                'type'=>$type,
                'store_id'=>$storeId,
                'create_time'=>time(),
                'update_time'=>time(),
                'new_time'=>time(),
                'uid'=>$uid
            ];
            (new UserNoticeType())->insert($adData);
        }elseif($noticeType['is_del'] == 1){//已删除的恢复展示
            (new UserNoticeType())->where($where)->update([
                'is_del'=>0,
                'update_time'=>time(),
                'new_time'=>time(),
            ]);
        }
        return true;
    }
}
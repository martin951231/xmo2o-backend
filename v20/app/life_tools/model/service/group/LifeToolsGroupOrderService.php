<?php
/**
 * 景区订单子表-团体票
 */

namespace app\life_tools\model\service\group;

use app\common\model\service\image\ImageService;
use app\life_tools\model\db\LifeToolsGroupOrder;
use app\life_tools\model\db\LifeToolsGroupOrderTourists;
use app\life_tools\model\db\LifeToolsGroupSetting;
use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderDetail;
use app\life_tools\model\service\LifeToolsOrderDetailService;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;
use app\life_tools\model\service\LifeToolsTicketService;

class LifeToolsGroupOrderService
{
    public $lifeToolsGroupOrderModel = null;
    public $lifeToolsGroupOrderTouristsModel = null;
    public $lifeToolsGroupSettingModel = null;
    public $lifeToolsOrderModel = null;
    public $lifeToolsOrderDetailModel = null;
    public $lifeToolsOrderDetailMod = null;

    public $statusMap = [
        1   =>  0,      //待提交
        2   =>  10,     //待审核
        3   =>  20,     //审核通过
        4   =>  30,     //审核不通过
        5   =>  40      //已过期
    ];

    public function __construct()
    {
        $this->lifeToolsGroupOrderModel = new LifeToolsGroupOrder();
        $this->lifeToolsGroupOrderTouristsModel = new LifeToolsGroupOrderTourists();
        $this->lifeToolsGroupSettingModel = new LifeToolsGroupSetting();
        $this->lifeToolsOrderModel = new LifeToolsOrder();
        $this->lifeToolsOrderDetailModel = new LifeToolsOrderDetailService();
        $this->lifeToolsOrderDetailMod = new LifeToolsOrderDetail();
    }


    /**
     * 获取订单状态
     */
    public function getGroupOrderStatusList($params)
    {
        $condition = [];
        $condition[] = ['o.uid', '=', $params['uid']];
        $condition[] = ['o.is_group', '=', 1];
        $condition[] = ['o.paid', '=', 2];
        $statusArr = [];
        $statusArr[] = [
            'status' => 0, 
            'text' => '全部',
            'nums' => $this->lifeToolsGroupOrderModel->getDataOrNumByCondition($condition, 0)
        ];

        $group_status = $this->lifeToolsGroupOrderModel->status_text;
        $status_map = array_flip($this->statusMap);
        foreach($group_status as $key => $val){
            $statusArr[] = [
                'status' => $status_map[$key], 
                'text' => $val,
                'nums' => $this->lifeToolsGroupOrderModel->getDataOrNumByCondition(array_merge($condition, [['go.group_status', '=', $key]]), 0)
            ];
        }

        return $statusArr;
    }

    /**
     * 获取订单列表
     */
    public function getGroupOrderList($params)
    {
        $condition = [];
        $condition[] = ['o.uid', '=', $params['uid']];
        $condition[] = ['o.is_group', '=', 1];
        $condition[] = ['o.paid', '=', 2];
        if(!empty($params['status']) && in_array($params['status'], [1, 2, 3, 4, 5])){
            $condition[] = ['go.group_status', '=', $this->statusMap[$params['status']]];
        }
        
        $data = $this->lifeToolsGroupOrderModel->getDataOrNumByCondition($condition, 1, $params['page_size']);
 
        $statusColorMap = [
            0   =>  '#ff624e',
            10  =>  '#000000',
            20  =>  '#1987fd',
            30  =>  '#ff624e',
            40  =>  '#000000'
        ];
        $return = [];
        foreach($data as $key => $val){
            $remain_time = $val['submit_audit_time'] - time();
            $tmp = $val->toArray();
            $tmp['remain_time'] = $remain_time <= 0 ? 0 : $remain_time;
            $tmp['total_group_price'] = formatNumber($val['group_price'] * $val['num']);
            $tmp['cover_image'] = replace_file_domain($val['cover_image']);
            $tmp['status']['text'] = $this->lifeToolsGroupOrderModel->status_text[$val['group_status']] ?? '未知状态';
            $tmp['status']['color'] = $statusColorMap[$val['group_status']] ?? '#000000';
            $tmp['btn'] = $this->getBtn($val['group_status']);
            $return[] = $tmp;
        }
        return $return;
    }

    /**
     * 添加一条数据
     */
    public function add($data)
    {
        $res = $this->lifeToolsGroupOrderModel->add($data);
        return $res;
    }

    /**
     * 更新数据
     */
    public function updateThis($where, $data)
    {
        if(empty($where) || empty($data)){
            return false;
        }
        
        $res = $this->lifeToolsGroupOrderModel->updateThis($where, $data);
        return $res;
    }
    
    /**
     * 获得一条数据
     */
    public function getOne($where)
    {
        $res = $this->lifeToolsGroupOrderModel->where($where)->find();
        return $res;
    }

    /*
     * 提交审核
     */
    public function submitAudit($params)
    {
        $order = $this->lifeToolsGroupOrderModel->getOne($params['order_id']);
        if(!$order){
            throw new \think\Exception("订单不存在！");
        }

        if($order->group_status != 0){
            throw new \think\Exception("当前订单状态不支持此操作！");
        }
        
        if($order['submit_audit_time'] != 0 && $order['submit_audit_time'] < time()){
            throw new \think\Exception("订单已过期！");
        }

        if($order['group_status'] != 0){
            throw new \think\Exception("订单状态不支持审核！");
        }

        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $condition[] = ['is_del', '=', 0];
        if($this->lifeToolsGroupOrderTouristsModel->where($condition)->count() != $order['num']){
            throw new \think\Exception("购买数量与填写数量不符!");
        }
        
        
        $setting = $this->lifeToolsGroupSettingModel->where('mer_id', $order['mer_id'])->find();
        if(!$setting){
            throw new \think\Exception("未找到配置信息！");
        }

        $groupOrder = $this->lifeToolsGroupOrderModel->where('order_id', $order['order_id'])->find();
        //1-自动审核，0-需要审核
        //团体票状态0待提交审核10-已提交审核20审核通过30审核不通过40已过期
        if($setting->buy_audit == 1 ){
            $groupOrder->group_status = 20;

            $touristsIds = $this->lifeToolsGroupOrderTouristsModel->where($condition)->column('id');
            $this->lifeToolsOrderDetailMod->where('order_id', $params['order_id'])->select()->each(function($item, $key) use($touristsIds){
                $item->tourists_id = $touristsIds[$key] ?? 0;
                $item->save();
            });
       
        }else{
            $groupOrder->group_status = 10;
        } 
        $groupOrder->save();
        return true;
    }
    
    /**
     * 支付成功二维码页面接口
     */
    public function paySuccess($param){
        $uid = $param['uid'] ?? 0;
        $orderId = $param['order_id'] ?? 0;

        $order = (new LifeToolsOrderService())->getOne(['order_id'=>$orderId,'uid'=>$uid]);
        if(empty($order)){
            throw new \think\Exception("订单不存在", 1003);
        }

        if(!$order['paid']){
            throw new \think\Exception("订单未支付", 1003);
        }

        $orderDetail = $this->getOne(['order_id'=>$orderId]);
        if(empty($orderDetail)){
            throw new \think\Exception("订单不存在", 1003);
        }

        $returnArr['order_id'] = $orderDetail['order_id'];
        $returnArr['group_status'] = $orderDetail['group_status'];// 团体票状态0待提交审核10-已提交审核20审核通过30审核不通过40已过期
        $returnArr['qrcode'] = cfg('site_url').'/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page='.urlencode('/pages/lifeTools/scenic/groupTicket/order/touristsInfoSubmit?order_id='.$orderId);

        // 过期时间
        if($orderDetail['submit_audit_time'] == 0){// 不显示
            $returnArr['expiration_time'] = -1;
        }else{
            $returnArr['expiration_time'] = ceil(($orderDetail['submit_audit_time'] - time())/60);            
            if($returnArr['expiration_time'] <= 0){
                $returnArr['expiration_time'] = 0;
            }
        }

        // 生成海报
        $lifeTools = (new LifeToolsService())->getOne(['tools_id'=>$order['tools_id']]);
        $ticket = (new LifeToolsTicketService())->getOne(['ticket_id'=>$order['ticket_id']]);
        $params = [
            'tools_title' => $lifeTools['title'] ?? '',
            'cover_image' => $lifeTools['cover_image'] ? replace_file_domain($lifeTools['cover_image']) : '',
            'ticket_title' => $ticket['title'] ?? '',
        ];
        $returnArr['image'] = $this->createImage($orderId, $params) ?? '';

        return $returnArr;
    }

    // 生成分享海报
    public function createImage($orderId, $params){
        //二维码图片      
        $qrcodePath = cfg('site_url').'/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page='.urlencode('/pages/lifeTools/scenic/groupTicket/order/touristsInfoSubmit?order_id='.$orderId);

        // 生成图片路径
        $filename = '../../runtime/lifetools/group/image/'.$orderId;

        // 生成图片名称
        $image_name = 'wxapp_image.png';

        // 创建目录
        if(!file_exists($filename))
            mkdir($filename,0777,true);
        
        // 图片已存在直接返回
        if(file_exists($filename.'/'.$image_name))
            return cfg('site_url').'/runtime/lifetools/group/image/'.$orderId.'/'.$image_name;

        // 图片最后保存路径
        $imgFriendPath = $filename.'/'.$image_name;

        //创建主图
        $img = imagecreatetruecolor(600,300);
        $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
        imagefill ( $img ,  0 ,   0 ,  $white );

        // 创建二维码图像
        $filePath =  $filename.'/wxapp_qrcode.png';
        if(!file_exists($filePath)){            
            $image = file_get_contents($qrcodePath);
            file_put_contents($filePath, $image);
           (new ImageService())->scaleImg($filePath, $filePath, 140, 140);
        }
        if(file_exists($filePath)){
            $src_im = imagecreatefromstring(file_get_contents($filePath));
            imagecopy($img,$src_im,430,130,0,0,140,140);
        }

        // 景区图片
        if($params['cover_image']){
            // 压缩裁剪图片
           (new ImageService())->thumb2($params['cover_image'], $filename.'/cover_image.jpg','',300, 300); 
           if(file_exists($filename.'/cover_image.jpg')){
                $src_im = imagecreatefromstring(file_get_contents( $filename.'/cover_image.jpg'));
                imagecopy($img,$src_im,0,0,0,0,300,300);
           }
        }

        //字体
        $font = realpath('../../static/fonts/PingFang Regular.ttf');

        // 景区名称
        $name = $params['tools_title'];
        if(mb_strlen($name, 'utf-8') > 15) {
            $name = msubstr($name,0,9);
        }
        $fontSize = 20;//像素字体
        $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
        $string = $name;
        $top = '30'; //距离顶部距离
        $font_width = ImageFontWidth($fontSize);
        $font_height = ImageFontHeight($fontSize);
        //取得 str 2 img 后的宽度
        $temp = imagecreatetruecolor($font_height, $font_width);
        imagefttext($img, $fontSize, 0, 320, $top + $font_height, $fontColor, $font, $string);
        imagefttext($img, $fontSize, 0, 321, $top + $font_height, $fontColor, $font, $string);// 加粗

        $top += $font_height; //距离顶部距离

        //门票名称
        $ticketTitle = $params['ticket_title'];
        $fontSize = 18;//像素字体
        $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
        $string = $ticketTitle;
        $top += 30; //距离顶部距离
        $font_width = ImageFontWidth($fontSize);
        $font_height = ImageFontHeight($fontSize);
        //取得 str 2 img 后的宽度
        $temp = imagecreatetruecolor($font_height, $font_width);
        imagefttext($img, $fontSize, 0, 320, $top + $font_height, $fontColor, $font, $string);

        $top += $font_height + 80; //距离顶部距离

        //分享语句
        $fontSize = 15;//像素字体
        $string = '扫码填写';
        $font_width = ImageFontWidth($fontSize);
        $font_height = ImageFontHeight($fontSize);
        $fontColor = imagecolorallocate ($img, 158,158,158 );//字的RGB颜色
        $temp = imagecreatetruecolor($font_height, $font_width);    
        imagefttext($img, $fontSize, 0,320, $top + $font_height, $fontColor, $font, $string);

        $top += $font_height + 20; //距离顶部距离

        $fontSize = 15;//像素字体
        $string = '入园信息';
        $font_width = ImageFontWidth($fontSize);
        $font_height = ImageFontHeight($fontSize);
        $fontColor = imagecolorallocate ($img, 158,158,158 );//字的RGB颜色
        $temp = imagecreatetruecolor($font_height, $font_width);
        imagefttext($img, $fontSize, 0, 320, $top + $font_height, $fontColor, $font, $string);

        //保存主图
        imagepng($img,$imgFriendPath);        

        return cfg('site_url').'/runtime/lifetools/group/image/'.$orderId.'/'.$image_name;
    }

    /**
     * 获取按钮
     */
    private function getBtn($status)
    {
        $btn = [];
        switch($status){
            case 0:
                $btn['a'] = [
                    'text'  =>  '查看游客信息',
                    'color' =>  '#1682E6',
                    'url'   =>  ''
                ];
                $btn['btn'] = [
                    'text'  =>  '提交审核',
                    'color' =>  '#1682E6',
                    'url'   =>  ''
                ];
                break;
            case 10:
                $btn['a'] = [
                    'text'  =>  '查看游客信息',
                    'color' =>  '#1682E6',
                    'url'   =>  ''
                ];
                break;
            case 20:
                $btn['a'] = [
                    'text'  =>  '查看游客信息',
                    'color' =>  '#1682E6',
                    'url'   =>  ''
                ];
                $btn['btn'] = [
                    'text'  =>  '查看出票',
                    'color' =>  '#1682E6',
                    'url'   =>  ''
                ];
                break;
        }
        return $btn;
    }

    /**
     * 获取审核团体票订单列表
     * @author nidan
     * @date 2022/3/24
     */
    public function getAuditGroupOrderList($params)
    {
        $where = [];
        if(isset($params['mer_id']) && $params['mer_id']){
            $where[] = ['b.mer_id' ,'=', $params['mer_id']];
        }
        if(isset($params['keyword_scenic_name']) && $params['keyword_scenic_name']){
            $where[] = ['d.title' ,'like', $params['keyword_scenic_name'].'%'];
        }
        if(isset($params['keyword_ticket_name']) && $params['keyword_ticket_name']){
            $where[] = ['c.ticket_title' ,'like', '%'.$params['keyword_ticket_name'].'%'];
        }
        if(isset($params['status']) && $params['status'] !== 'all'){
            $where[] = ['a.group_status' ,'=', $params['status']];
        }
        if(isset($params['start_time']) && $params['start_time'] && isset($params['end_time']) && $params['end_time']){
            $where[] = ['c.ticket_time' ,'between', $params['start_time'].','.$params['end_time']];
        }

        $where[] = ['b.is_del' ,'=', 0];
        $where[] = ['c.is_group' ,'=', 1];
        $where[] = ['c.order_status' ,'>', 10];
        $field = ['a.id','a.order_id','a.tour_guide_custom_form','d.title as tool_title','c.ticket_title','c.num','c.price','c.ticket_time as add_time','a.audit_time','a.audit_msg','a.group_status'];
        $order = 'c.add_time desc';
        $data = $this->lifeToolsGroupOrderModel->getAuditList($where, $field, $order,$params['page_size']);
        $orderIdAry = [];
        foreach ($data as $v){
            $v['tour_guide_custom_form'] = $v['tour_guide_custom_form'] ? json_decode($v['tour_guide_custom_form'],true) : '';
            $v['audit_time'] = $v['audit_time'] ? date('Y.m.d',$v['audit_time']) : '';
            $orderIdAry[] = $v['order_id'];
        }
        $touristsAry = [];
        if($orderIdAry){//查询所有的游客填写信息
            $touristsInfo = $this->lifeToolsGroupOrderTouristsModel->field(['tourists_custom_form','order_id'])->where('is_del',0)->whereIn('order_id',$orderIdAry)->select();
            foreach ($touristsInfo as $tourists){
                $touristsAry[$tourists['order_id']][] = $tourists['tourists_custom_form'];
            }
        }
        if($touristsAry){
            foreach ($data as &$v){
                $v['tourists_custom_form'] = $touristsAry[$v['order_id']]??[];
            }
        }
        return $data;
    }

    /**
     * 审核团体票订单
     * @author nidan
     * @date 2022/3/24
     */
    public function audit($params)
    {
        $where = [];
        if(!in_array($params['status'],[20,30])){
            throw new \think\Exception(L_('审核状态异常，请重新提交'), 1001);
        }
        if(isset($params['group_order_id']) && $params['group_order_id']){
            $where[] = ['id' ,'=', $params['group_order_id']];
        }else{
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $data = [
            'audit_time'    =>  time(),
            'group_status'    =>  $params['status'],
            'audit_msg'    =>  $params['note']
        ];
        //查询团体票信息
        $info = $this->getOne($where);
        if(!$info){
            throw new \think\Exception(L_('未查询到操作的订单'), 1001);
        }
        if($info['group_status'] == 0){
            throw new \think\Exception(L_('订单未提交审核'), 1001);
        }
        if($info['group_status'] == 40){
            throw new \think\Exception(L_('订单已过期，无法审核'), 1001);
        }
        $this->lifeToolsGroupOrderModel->startTrans();//开启事务
        try {
            //编辑团体票状态
            $update = $this->lifeToolsGroupOrderModel->updateThis($where, $data);
            if(!$update){
                throw new \think\Exception(L_('编辑团体票审核状态失败'), 1001);
            }
            if($params['status'] == 30){
                //查询订单价格
                $orderInfo = $this->lifeToolsOrderModel->getOne(['order_id'=>$info['order_id']]);
                //审核未通过，订单取消，库存回滚，钱原路返回
                $updateRefundMoney = $this->lifeToolsOrderModel->updateThis(['order_id'=>$info['order_id']],['refund_money'=>$orderInfo['price']]);
                $LifeToolsOrderService = new LifeToolsOrderService();
                $refund = $LifeToolsOrderService->changeOrderStatus($info['order_id'],50,'');
                if(!$refund){
                    throw new \think\Exception(L_('退款失败'), 1001);
                }
            }else{
                //审核通过
                //查询订单游客信息id
                $tourists = $this->lifeToolsGroupOrderTouristsModel->getSome([
                    'order_id' => $info['order_id'],
                    'is_del' => 0
                ],'id');
//                $idCardAry = [];
                $touristsIdAry = [];
                foreach ($tourists as $v){
                    $touristsIdAry[]=$v['id'];
//                    foreach ($v['tourists_custom_form'] as $parm){
//                        if($parm['type'] == 'idcard' && $parm['status'] == 1){
//                            $idCardAry[] = $parm['show_value'];
//                        }
//                    }
                }
                $updateDetailAry = [];//需要修改的订单详情数组
                if($touristsIdAry){//将游客信息id和订单详情记录id一一匹配
                    $orderDetail = $this->lifeToolsOrderDetailModel->getSome([
                        'order_id' => $info['order_id']
                    ],'detail_id');
                    foreach ($orderDetail as $detail){
                        foreach ($touristsIdAry as $key=>$touristsId){
                            if(isset($touristsIdAry[$key]) && $touristsIdAry[$key] && isset($orderDetail[$key]['detail_id']) && $orderDetail[$key]['detail_id']){
                                $updateDetailAry[$key] = [
                                    'touristsId'=>$touristsIdAry[$key],
                                    'detail_id'=>$orderDetail[$key]['detail_id'],
                                ];
                            }
                        }
                    }
                }
                if($updateDetailAry){//修改订单详情
                    foreach ($updateDetailAry as $updateInfo){
                        $updateOrderDetail = $this->lifeToolsOrderDetailMod->where('detail_id',$updateInfo['detail_id'])->update(['tourists_id'=>$updateInfo['touristsId']]);
                        if(!$updateOrderDetail){
                            throw new \think\Exception(L_('订单匹配游客信息失败'), 1001);
                        }
                    }
                }

            }
            $this->lifeToolsGroupOrderModel->commit();
        } catch (\Exception $e) {
            $this->lifeToolsGroupOrderModel->rollback();
            throw new \think\Exception(L_($e->getMessage()), 1001);
        }
        return ['msg' => '操作成功'];
    }

    /**
     * 团体票统计数据
     */
    public function getStatisticsData($mer_id)
    {
        $data = $condition = [];
        $condition[] = ['mer_id', '=', $mer_id];
        $condition[] = ['is_group', '=', 1];

        //总订单（已核销的订单总数量）
        $data['total_order'] = $this->lifeToolsOrderModel->where($condition)->where('order_status', 'in', [30, 40, 50, 70])->count();

        //总收入（已核销的订单总金额）
        $total_order_money = $this->lifeToolsOrderModel->where($condition)->where('order_status', 'in', [30, 40, 50, 70])->sum('price');
        $refundCondition = [];
        $refundCondition[] = ['o.mer_id', '=', $mer_id];
        $refundCondition[] = ['o.is_group', '=', 1];
        $refundCondition[] = ['o.order_status', 'in', [30, 40, 50]];
        $refundCondition[] = ['od.status', '=', 3];
        $refundPrice = $this->lifeToolsOrderModel->sumPrice($refundCondition); 
        $data['total_order_money'] = formatNumber($total_order_money - $refundPrice);
        
        //未完成的订单(已付款但未核销)
        $data['unfinish_order'] = $this->lifeToolsOrderModel->where($condition)->where('order_status', 'in', [20])->count();

        //未完成的订单金额(已付款但未核销金额)
        $data['unfinish_order_money'] = $this->lifeToolsOrderModel->where($condition)->where('order_status', 'in', [20])->sum('price');

        return $data;
    }

    /**
     * 获取订单列表
     */
    public function getOrderList($params)
    {
        $condition = [];
        $condition[] = ['o.mer_id', '=', $params['mer_id']];
        $condition[] = ['o.is_group', '=', 1];
        $condition[] = ['o.paid', '=', 2];
        //搜索
        if(!empty($params['search'])){
            switch($params['search_type']){
                case 0: //名称
                    $condition[] = ['o.nickname', 'like', "%{$params['search']}%"];
                    break;
                case 1: //手机号
                    $condition[] = ['o.phone', 'like', "%{$params['search']}%"];
                    break;
                case 2: //景区名称
                    $condition[] = ['l.title', 'like', "%{$params['search']}%"];
                    break;
                case 3: //套餐名称
                    $condition[] = ['t.title', 'like', "%{$params['search']}%"];
                    break;
            }
            
        }
        
        //状态 '订单状态 10未支付不显示 20未消费已付款，30已消费未评价,40已消费已评价, 45申请退款中，50已退款  60未付款已过期 70已付款已过期 80已全部转赠'
        if(!empty($params['status'])){
            switch($params['status']){
                case 1: //待核销
                    $condition[] = ['o.order_status', '=', 20];
                    break;
                case 2: //已核销
                    $condition[] = ['o.order_status', 'in', [30, 40]];
                    break;
                case 3: //已退款
                    $condition[] = ['o.order_status', 'in', [50]];
                    break;
                case 4: //已过期
                    $condition[] = ['o.order_status', 'in', [70]];
                    break;
            }
            
        }

        if(!empty($params['start_date']) && !empty($params['end_date'])){
            $condition[] = ['o.ticket_time', 'between', [$params['start_date'], $params['end_date']]];
        }

        $data = $this->lifeToolsGroupOrderModel->getDataOrNumByCondition($condition, 1, $params['page_size']);

        $order_status = [ //10未支付不显示 20未消费已付款，30已消费未评价,40已消费已评价 45申请退款中 50用户取消已退款  60未付款已过期 70已付款已过期 80已全部转赠
            '10' => '待支付', //待付款
            '20' => '未核销已付款', //待核销
            '30' => '已核销未评价', //已核销
            '40' => '已核销已评价', //已核销
            '45' => '申请退款中', //售后中
            '50' => '已退款', //已退款
            '60' => '未付款已过期', //已取消
            '70' => '已付款已过期', //已过期
            '80' => '已全部转赠', //全部转赠
        ];
        foreach($data as $key => $val){
            $data[$key]['status_text'] = $order_status[$val['order_status']] ?? '未知状态';
            $data[$key]['unverify_num'] = $val['num'] - $val['verify_num'];
            $data[$key]['is_refund_btn'] =  ($val['order_status'] == 70 || $val['order_status'] == 50 || $val['order_status'] == 30 || $val['order_status'] == 40) ? 1 : 0;
            // $data[$key]['has_refund_num'] =  $this->lifeToolsOrderDetailMod->where('order_id', $val['order_id'])->where('status', '<>', 3)->count();
            if($val['order_status'] == 50){
                $refund_num = $this->lifeToolsOrderDetailMod->where('order_id', $val['order_id'])->where('status', '=', 3)->count();
                $data[$key]['status_text'] =  '已退款（'.$refund_num.'）';
                if($refund_num >= $val['num']){
                    $data[$key]['is_refund_btn'] = 0;
                }
            }
            
        }
        return $data;
    }

    /**
     * 团体票退款
     */
    public function groupOrderRefand($params)
    {
        $order = $this->lifeToolsOrderModel->where('order_id', $params['order_id'])->find();
        if(!$order){
            throw new \think\Exception('订单不存在！');
        }
        if(empty($params['num'])){
            throw new \think\Exception('num不能为空！');
        }

        $condition = [];
        $condition[] = ['order_id', '=', $params['order_id']];
        $condition[] = ['status', '<>', 3];
        $order_detail = $this->lifeToolsOrderDetailMod->where($condition)->limit($params['num'])->select()->toArray();
        if(count($order_detail) < $params['num']){
            throw new \think\Exception('最大可退款数量为' . count($order_detail));
        }

        $lifeToolsOrderService = new LifeToolsOrderService();
        foreach($order_detail as $key => $val){
            $lifeToolsOrderService->agreeOutRefund($val['detail_id']);
        }
        return true;
    }

}
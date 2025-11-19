<?php

/**
 * 三级分销分销员service
 */

namespace app\life_tools\model\service\distribution;


use app\common\model\service\image\ImageService;
use app\life_tools\model\db\LifeToolsDistributionOrder;
use app\life_tools\model\db\LifeToolsDistributionSetting;
use app\life_tools\model\db\LifeToolsDistributionUser;
use app\life_tools\model\db\LifeToolsDistributionUserShare;
use app\life_tools\model\service\LifeToolsOrderService;
use app\life_tools\model\service\LifeToolsService;
use app\life_tools\model\service\LifeToolsTicketService;

class LifeToolsDistributionOrderService
{

    public $lifeToolsDistributionOrderModel = null;
    public $lifeToolsDistributionUserModel = null;
    public $lifeToolsDistributionUserShareModel = null;
    public $lifeToolsDistributionSettingModel = null;

    public function __construct()
    {
        $this->lifeToolsDistributionOrderModel = new LifeToolsDistributionOrder();
        $this->lifeToolsDistributionUserModel = new LifeToolsDistributionUser();
        $this->lifeToolsDistributionUserShareModel = new LifeToolsDistributionUserShare();
        $this->lifeToolsDistributionSettingModel = new LifeToolsDistributionSetting();
    }

    /**
     * 获取指定分销员的分销清单
     */
    public function getList($params)
    {
        if (!$params['mer_id'] || !$params['user_id']) {
            throw new \think\Exception('参数缺失！');
        }
        $where = [
            ['a.mer_id','=',$params['mer_id']],
            ['a.user_id','=',$params['user_id']],
            ['a.status','=',$params['status']],
        ];
        if($params['start_time'] && $params['end_time']){
            $where[] = ['b.add_time' ,'>=', strtotime($params['start_time'].' 00:00:00')];
            $where[] = ['b.add_time' ,'<=', strtotime($params['end_time'].' 23:59:59')];
        }
        $field = ['a.id','b.real_orderid','b.ticket_title','c.title as tools_title','b.nickname','b.phone','b.num','b.price','a.commission_level_1','a.commission_level_2','a.status','b.add_time','a.note'];
        $info = $this->lifeToolsDistributionOrderModel->getListByDistributor($where,$field,$params['page'],$params['page_size'],$params['get_all']);
        foreach ($info as &$v){
            $v['add_time'] = date('Y.m.d H:i',$v['add_time']);
        }
        if($params['get_all']){
            $count = count($info);
            $data['count'] = $count;
            $data['per_page'] = $count;
            $data['current_page'] = 1;
            $data['last_page'] = 1;
            $data['data'] = $info;
        }else{
            $data = $info;
        }
        return $data;
    }

    /**
     * 修改订单备注
     */
    public function updateOrderNote($params)
    {
        if (!$params['distribution_order_id']) {
            throw new \think\Exception('参数缺失！');
        }
        //查询订单状态
        $orderInfo = $this->lifeToolsDistributionOrderModel->getOne(['id'=>$params['distribution_order_id']],'status');
        if(!$orderInfo){
            throw new \think\Exception('未查询到操作对象！');
        }
        if($orderInfo['status'] > 0){
            $statusMsg = $orderInfo['status'] == 1 ? '结算中' : '已结算';
            throw new \think\Exception($statusMsg.'订单无法修改备注！');
        }
        //修改
        $update = $this->lifeToolsDistributionOrderModel->updateThis(
            ['id'=>$params['distribution_order_id']],
            ['note' => $params['note']]
        );
        if(!$update){
            throw new \think\Exception('操作失败！');
        }
        return ['msg' => '操作成功！'];
    }

    /**
     * 获取分享海报页面
     */
    public function getShareInfo($param)
    {
        $uid = $param['uid'] ?? 0;
        $id = $param['tools_id'] ?? 0;
        if(!$id){
            throw new \think\Exception('参数缺失！');
        }
        $lifeTools = (new LifeToolsService())->getOne(['tools_id'=>$id]);
        //查询海报设置信息
        $shareSet = (new LifeToolsDistributionSettingService())->getDataDetail(['mer_id'=>$lifeTools['mer_id']]);
        //获取用户头像
        $params = [
            'spread_uid' => $uid,
            'tools_title' => $lifeTools['title'] ?? '',
            'cover_image' => $lifeTools['cover_image'] ? replace_file_domain($lifeTools['cover_image']) : '',
            'price' => $lifeTools['money'] ?? 0,
            'avatar' => $param['avatar'] ? $param['avatar'] : cfg('site_url'). '/static/images/user_avatar.jpg',
            'uid' => $uid,
            'share_type' => $shareSet['share_type'] ?? 2,
            'status_show_avatar' => $shareSet['status_show_avatar'] ?? 1,
            'status_show_price' => $shareSet['status_show_price'] ?? 1,
        ];
        $returnArr['image'] = $this->createImage($id, $params) ?? '';
        //查询分销员信息
        $whereUser = [
            'is_del'=>0,
            'is_cert'=>1,
            'status'=>1
        ];
        $user = $this->lifeToolsDistributionUserModel->getOne($whereUser,'user_id');
        if(!$user){
            throw new \think\Exception('您还未成为分销员，无法分享！');
        }
        //查询分销设置
        $setInfo = $this->lifeToolsDistributionSettingModel->getOne(['mer_id'=>$lifeTools['mer_id']],'effective_time');
        $effectiveTime = $setInfo['effective_time'] ?? 0;
        $expirationTime = $effectiveTime ? time() + $effectiveTime * 60 : 0;
        //查询分享记录
        $where = [
            'uid'=>$uid,
            'user_id'=>$user['user_id'],
            'tools_id'=>$id,
        ];
        $shareList = $this->lifeToolsDistributionUserShareModel->getOne($where,'share_id');
        if($shareList){
            $update = $this->lifeToolsDistributionUserShareModel->updateThis($where,[
                'url'=>cfg('site_url').'/packapp/plat/pages/lifeTools/tools/detail?id='.$id.'&distribution_invite_id='.$params['spread_uid'],
                'expiration_time'=>$expirationTime,
                'last_time'=>time()
            ]);
        }else{
            $update = $this->lifeToolsDistributionUserShareModel->insert([
                'uid'=>$uid,
                'user_id'=>$user['user_id'],
                'tools_id'=>$id,
                'url'=>cfg('site_url').'/packapp/plat/pages/lifeTools/tools/detail?id='.$id.'&distribution_invite_id='.$params['spread_uid'],
                'expiration_time'=>$expirationTime,
                'add_time'=>time(),
                'last_time'=>time()
            ]);
        }
        if(!$update){
            throw new \think\Exception('分享海报生成失败！');
        }
        return $returnArr;
    }
    // 生成分享海报
    public function createImage($id, $params){
        //不同配置名称不同
        $suffix = $params['share_type'].$params['status_show_avatar'].$params['status_show_price'];
        //二维码图片
        $qrcodePath = cfg('site_url').'/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page='.urlencode('/pages/lifeTools/tools/detail?id='.$id.'&distribution_invite_id='.$params['spread_uid']);

        // 生成图片路径
        $filename = '../../runtime/lifetools/distribution/image/'.$id.'_'.$params['spread_uid'];

        // 生成图片名称
        $image_name = 'wxapp_image_'.$suffix.'.png';

        // 创建目录
        if(!file_exists($filename))
            mkdir($filename,0777,true);

        // 图片已存在直接返回
        if(file_exists($filename.'/'.$image_name))
            return cfg('site_url').'/runtime/lifetools/distribution/image/'.$id.'_'.$params['spread_uid'].'/'.$image_name;

        // 图片最后保存路径
        $imgFriendPath = $filename.'/'.$image_name;

        //创建主图
        $imgH = 871;
        if(!$params['status_show_price']){
            $imgH = 810;
        }
        if($params['share_type'] == 2){
            $imgH = 781;
            if(!$params['status_show_price'] && !$params['status_show_avatar']){
                $imgH = 720;
            }
        }
        $img = imagecreatetruecolor(602,$imgH);
        $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
        imagefill ( $img ,  0 ,   0 ,  $white );

        // 景区图片
        if($params['cover_image']){
            // 压缩裁剪图片
            (new ImageService())->thumb2($params['cover_image'], $filename.'/cover_image.jpg','',602, 602);
            if(file_exists($filename.'/cover_image.jpg')){
                $src_im = imagecreatefromstring(file_get_contents( $filename.'/cover_image.jpg'));
                imagecopy($img,$src_im,0,0,0,0,602,602);
            }
        }
        if($params['share_type'] == 2){
            //添加黑色背景蒙版
            imagefilledrectangle($img, 0, 0, 602, 602, imagecolorallocatealpha($img, 0, 0, 0, 50));
            //添加白色背景
            imagefilledrectangle($img, 178, 118, 422, 362, imagecolorallocatealpha($img, 250, 250, 250, 0));
        }
        //字体
        $font = realpath('../../static/fonts/PingFang Regular.ttf');

        // 景区名称
        $name = $params['tools_title'];
        if(mb_strlen($name, 'utf-8') > 10) {
            $name = msubstr($name,0,9);
        }
        $fontSize = 26;//像素字体
        $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
        $string = $name;
        $top = '635'; //距离顶部距离
        $font_width = ImageFontWidth($fontSize);
//        $font_height = ImageFontHeight($fontSize);
        $font_height = 33;
        //取得 str 2 img 后的宽度
        $temp = imagecreatetruecolor($font_height, $font_width);
        imagefttext($img, $fontSize, 0, 43, $top + $font_height, $fontColor, $font, $string);

        $top += $font_height; //距离顶部距离

        if($params['status_show_price']){
            //价格
            $price = $params['price'];
            $fontSize = 35;//像素字体
            $fontColor = imagecolorallocate ($img, 255, 0, 0 );//字的RGB颜色
            $fontColorRight = imagecolorallocate ($img, 160, 160, 160 );//字的RGB颜色
            $string = $price;
            $leftStr = '￥';
            $rightStr = '起';
            $top += 30; //距离顶部距离
            $font_width = ImageFontWidth($fontSize);
            $w = strlen($params['price'])*38;
//            $font_height = ImageFontHeight($fontSize);
            //取得 str 2 img 后的宽度
            $temp = imagecreatetruecolor($font_height, $font_width);
            imagefttext($img, $fontSize, 0, 43 + 18, $top + $font_height, $fontColor, $font, $string);
            imagefttext($img, 18, 0, 43, $top + $font_height, $fontColor, $font, $leftStr);
            imagefttext($img, 18, 0, 43 + $w, $top + $font_height, $fontColorRight, $font, $rightStr);

            imagefttext($img, $fontSize, 0, 43 + 18 + 1, $top + $font_height, $fontColor, $font, $string);
            imagefttext($img, 18, 0, 43 + 1, $top + $font_height, $fontColor, $font, $leftStr);
            imagefttext($img, 18, 0, 43 + $w + 1, $top + $font_height, $fontColorRight, $font, $rightStr);

            imagefttext($img, $fontSize, 0, 43 + 18 + 2, $top + $font_height, $fontColor, $font, $string);
            imagefttext($img, 18, 0, 43 + 2, $top + $font_height, $fontColor, $font, $leftStr);
            imagefttext($img, 18, 0, 43 + $w + 2, $top + $font_height, $fontColorRight, $font, $rightStr);
            $top += $font_height; //距离顶部距离
        }

        // 创建二维码图像
        $filePath =  $filename.'/wxapp_qrcode_'.$suffix.'.png';
        $codeSize = 90;
        $lefLength = 43;
        $topLength = $top + 29;

        if($params['share_type'] == 2){
            $codeSize = 244;
            $lefLength = 178;
            $topLength = 118;
        }
        if(!file_exists($filePath)){
            $image = file_get_contents($qrcodePath);
            file_put_contents($filePath, $image);
            (new ImageService())->scaleImg($filePath, $filePath, $codeSize, $codeSize);
        }
        if(file_exists($filePath)){
            $src_im = imagecreatefromstring(file_get_contents($filePath));
            imagecopy($img,$src_im,$lefLength,$topLength,0,0,$codeSize,$codeSize);
        }

        //二维码说明
        $codeMsgTop = '打开微信扫一扫';
        $codeMsgBottom = '即可快速购票';
        $fontSize = 24;//像素字体
        $codeMsgLeft = 43 + 19 + 90;
        $top = 131 + 602; //距离顶部距离
        if($params['status_show_price']){
            $top = 191 + 602;
        }
        $fontColor = imagecolorallocate ($img, 100, 100, 100 );//字的RGB颜色
        if($params['share_type'] == 2){
            $codeMsgLeft = 209;
            $top = 415;
            $fontSize = 26;
            $fontColor = imagecolorallocate ($img, 200, 200, 200 );//字的RGB颜色

        }

        $font_width = ImageFontWidth($fontSize);
//            $font_height = ImageFontHeight($fontSize);
        //取得 str 2 img 后的宽度
        $temp = imagecreatetruecolor($font_height, $font_width);

        imagefttext($img, 18, 0, $codeMsgLeft, $top, $fontColor, $font, $codeMsgTop);
        imagefttext($img, 18, 0, $codeMsgLeft, $top + $font_height, $fontColor, $font, $codeMsgBottom);
        imagefttext($img, 18, 0, $codeMsgLeft + 1, $top, $fontColor, $font, $codeMsgTop);
        imagefttext($img, 18, 0, $codeMsgLeft + 1, $top + $font_height, $fontColor, $font, $codeMsgBottom);

        if($params['status_show_avatar']){
            //头像
            // 压缩裁剪图片
            $avatar = $filename.'/avatar.'.$params['uid'].'.jpg';
            (new ImageService())->thumb2($params['avatar'], $avatar,'',102, 102);
            if(file_exists($filename.'/avatar.'.$params['uid'].'.jpg')){
                $src_ims = imagecreatefromstring(file_get_contents( $avatar));
                $src_ims = (new ImageService())->changeCircularImg($avatar);
                //图片处理成圆形
                $top = 42 + 602;
                if($params['share_type'] == 1) {
                    $top = 35 + 602;
                }
                imagecopy($img,$src_ims,459,42 + 602,0,0,102,102);
            }
        }

        //保存主图
        imagepng($img,$imgFriendPath);

        return cfg('site_url').'/runtime/lifetools/distribution/image/'.$id.'_'.$params['spread_uid'].'/'.$image_name;
    }
}

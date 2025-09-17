<?php


namespace app\store_marketing\model\service;


use app\common\model\db\ShortLink;
use app\common\model\db\User;
use app\group\model\db\Group;
use app\group\model\db\GroupOrder;
use app\mall\model\db\MallGoods;
use app\mall\model\db\MallOrderDetail;
use app\merchant\model\db\StoreMarketingPerson;
use app\new_marketing\model\db\NewMarketingPerson;
use app\new_marketing\model\service\RegionalAgencyService;
use app\store_marketing\model\db\StoreMarketingPersonSetprice;
use app\store_marketing\model\db\StoreMarketingPersonStore;
use app\store_marketing\model\db\StoreMarketingRecord;
use app\store_marketing\model\db\StoreMarketingShareLog;
use file_handle\FileHandle;
use net\Http;
use think\facade\Db;

require_once '../extend/phpqrcode/phpqrcode.php';

class StoreMarketingPersonService
{

    /**
     * 业务员改价
     */
    public function setPrice($param)
    {
        $person = (new StoreMarketingPerson())->getOne(['sp.uid' => $param['uid'], 's.is_del' => 0], 'sp.*');
        if (empty($person)) {
            $assign['status'] = 0;
            $assign['msg'] = "业务员没有绑定店铺";
            return $assign;
        }
        if ($param['objective'] == 'detail') {
            $data['share_code'] = (new RegionalAgencyService())->makeInvitationCode();
            $data['person_id'] =$person['id'];
            $data['goods_id'] = $param['goods_id'];
            $data['goods_type'] = $param['goods_type'];
            $data['create_time'] = time();
            $ret = (new StoreMarketingShareLog())->add($data);
            if ($ret) {
                (new StoreMarketingPersonSetprice())->add(['goods_id' => $param['goods_id'], 'price' => $param['price'], 'person_id' => $person['id'], 'share_id' => $ret, 'goods_type' => $param['goods_type'], 'create_time' => time()]);
            }
            $assign['person_id'] = $person['id'];
            $assign['share_code'] = $data['share_code'];
            $assign['status'] = 1;
            $assign['msg'] = "获取成功";
        } else {
                $data['share_code'] = (new RegionalAgencyService())->makeInvitationCode();
                $param['person_id'] =$data['person_id'] = $person['id'];
                $data['goods_id'] = $param['goods_id'];
                $data['goods_type'] = $param['goods_type'];
                $param['create_time'] =$data['create_time'] = time();
                $ret = (new StoreMarketingShareLog())->add($data);
                $param['share_id'] = $ret;
                //改价之前先要删除之前没有分享出去的旧数据
                //(new StoreMarketingPersonSetprice())->delData(['goods_id'=>$param['goods_id'],'person_id'=>$param['person_id'],'share_id'=>0,'goods_type'=>$param['goods_type']]);
                unset($param['uid']);
                unset($param['objective']);
                if (!empty($param['specs_id'])) {
                    $de_json = $param['specs_id'];
                    foreach ($de_json as $k => $v) {
                        $param['specs_id'] = $v['specs_id'];
                        $param['price'] = $v['price'];
                        $param['person_id'] =$person['id'];
                        (new StoreMarketingPersonSetprice())->add($param);
                    }
                } else {
                    (new StoreMarketingPersonSetprice())->add($param);
                }
                $assign['person_id'] = $person['id'];
                $assign['share_code'] = $data['share_code'];
                $assign['status'] = 1;
                $assign['msg'] = "改价成功";
        }
        return $assign;
    }


    /**
     * @return \json
     * 佣金记录
     */
    public function storeMarketingRecord($uid, $page, $pageSize)
    {
        $person = (new StoreMarketingPerson())->getOne(['sp.uid' => $uid, 's.is_del' => 0], 'sp.*');
        if (empty($person)) {
            $out['status'] = 0;
            $out['msg'] = '';//去掉弹窗 “分销员数据异常”
            return $out;
        }
        $today_start = strtotime(date('Y-m-d 00:00:00'));
        $today_end = time();
        //今日收益
        $out['header']['today_income'] = (new StoreMarketingRecord())->getSum([['arrival_time', 'between', [$today_start, $today_end]], ['person_id', '=', $person['id']], ['is_arrival', '=', 1], ['is_del', '=', 0]], 'percentage');

        $month_start = strtotime(date('Y-m-01 00:00:00'));
        $month_end = strtotime(date('Y-m-d H:i:s'));
        //月收益
        $out['header']['month_income'] = (new StoreMarketingRecord())->getSum([['arrival_time', 'between', [$month_start, $month_end]], ['person_id', '=', $person['id']], ['is_arrival', '=', 1], ['is_del', '=', 0]], 'percentage');
        //总收益
        $out['header']['total_income'] = (new StoreMarketingRecord())->getSum([['person_id', '=', $person['id']], ['is_arrival', '=', 1], ['is_del', '=', 0]], 'percentage');
        //佣金记录
        $out['record'] = (new StoreMarketingRecord())->getSome(['s.person_id' => $person['id']], 's.*,u.avatar as image,u.nickname as name', 's.create_time desc', $page, $pageSize);
        if (!empty($out['record']['list'])) {
            foreach ($out['record']['list'] as $k => $v) {
                if ($v['create_time']) {
                    $out['record']['list'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                }
                if($v['create_time']){
                    $out['record']['list'][$k]['time'] = date('Y-m-d H:i:s', $v['create_time']);
                }
                if (empty($v['is_arrival'])) {
                    $out['record']['list'][$k]['percentage'] = "未到账";
                }

                if (!empty($v['image'])) {
                    $out['record']['list'][$k]['image'] = replace_file_domain($v['image']);
                }else{
                    $out['record']['list'][$k]['image'] =cfg('site_url')."/static/images/user_avatar.jpg";
                }
            }
        }
        $out['status'] = 1;
        return $out;
    }

    /**
     * @return \json
     * 商家后台佣金记录列表
     */
    public function merchantStoreMarketingRecord($param, $page, $pageSize)
    {
        $where[] = [['s.store_id', '=', $param['store_id']]];
        if (!empty($param['person_id'])) {
            array_push($where, ['s.person_id', '=', $param['person_id']]);
        }
        if (!empty($param['goods_type'])) {
            array_push($where, ['s.goods_type', '=', $param['goods_type']]);
        }
        if (!empty($param['goods_name'])) {
            array_push($where, ['s.goods_name', 'like', '%' . $param['goods_name'] . '%']);
        }
        if (!empty($param['start_time']) && !empty($param['end_time'])) {
            $param['start_time'] = strtotime($param['start_time']);
            array_push($where, ['s.create_time', '>', $param['start_time']]);
        }

        if (!empty($param['end_time']) && $param['start_time'] != $param['end_time']) {
            $param['end_time'] = strtotime($param['end_time'] . " 23:59:59");
            array_push($where, ['s.create_time', '<=', $param['end_time']]);
        }
        $out['person_sel'] = (new StoreMarketingPerson())->getSome(['s.store_id' => $param['store_id'], 's.is_del' => 0], 'sp.id as person_id,sp.name')['list'];
        $list = (new StoreMarketingRecord())->getSome($where, 's.*,u.nickname,sp.name', 's.create_time desc', $page, $pageSize);
        if (!empty($list['list'])) {
            foreach ($list['list'] as $k => $v) {
                if ($v['create_time']) {
                    $list['list'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                }
                $list['list'][$k]['site_url'] = cfg('site_url');
                if ($v['goods_type'] == 1) {
                    $list['list'][$k]['goods_type_text'] = "团购";
                } else {
                    $list['list'][$k]['goods_type_text'] = "商城";
                }
            }
        }
        $out['list'] = $list['list'];
        $out['count'] = $list['total'];
        return $out;
    }

    /**
     * 分享海报
     */
    public function share($param)
    {
        $share_code = $param['share_code'];
        $user = (new User())->getOne(['uid' => $param['uid']])->toArray();
        if ($param['goods_type'] == 1) {//团购
            $now_group = (new Group())->getOne(['group_id' => $param['goods_id']]);
            if (!empty($now_group)) {
                $now_group = $now_group->toArray();
                $group_id = $now_group['group_id'];
                $group_name = $now_group['name'];
                $group_price = floatval($now_group['price']);
                $group_old_price = floatval($now_group['old_price']);
                $goodMainPic = empty($this->get_allImage_by_path($now_group['pic'])) ? "" : $this->get_allImage_by_path($now_group['pic'])[0];
                //$wxapp_path = 'pages/webview/webview?webview_url=' . urlencode(cfg('site_url') . '/wap.php?c=Groupnew&a=detail&s=1&group_id=' . $group_id . "&share_id=" . $share_code);
                $wxapp_path = 'pages/webview/webview?webview_url=' . urlencode(cfg('site_url') . "/packapp/platn/pages/group/v1/groupDetail/index?group_id=".$group_id."&share_id=".$share_code);
                //生成网页二维码
                $qrCon =cfg('site_url') ."/packapp/platn/pages/group/v1/groupDetail/index?group_id=".$group_id."&share_id=".$share_code;
                //$qrCon = cfg('site_url') . "/wap.php?g=Wap&c=Groupnew&a=detail&group_id=" . $group_id . "&share_id=" . $share_code;
                //换短链
                $url ="/packapp/platn/pages/group/v1/groupDetail/index?group_id=".$group_id."&share_id=".$share_code;
                //$url = '/wap.php?c=Groupnew&a=detail&s=1&group_id=' . $group_id . "&share_id=" . $share_code;
                $sale_num = (new GroupOrder())->getSum([['group_id', '=', $now_group['group_id']], ['paid', '=', 1], ['is_del', '=', 0], ['is_del', '=', 0], ['is_marketing_goods', '=', 1]], 'num');
            } else {
                $out['status'] = 0;
                $out['msg'] = "团购商品不存在";
                return $out;
            }
        } elseif ($param['goods_type'] == 2) {//商城
            $now_group = (new MallGoods())->getOne($param['goods_id']);
            if (empty($now_group)) {
                $out['status'] = 0;
                $out['msg'] = "商城商品不存在";
                return $out;
            } else {
                $group_id = $now_group['goods_id'];
                $group_name = $now_group['name'];
                $group_price = floatval($now_group['price']);
                $group_old_price = floatval($now_group['price']);
                $goodMainPic = empty($now_group['image']) ? "" : replace_file_domain($now_group['image']);
                $wxapp_path = 'pages/webview/webview?webview_url=' . urlencode(cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $group_id . "&share_id=" . $share_code);
                $qrCon = cfg('site_url') . '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $group_id . "&share_id=" . $share_code;
                //换短链
                $url = '/packapp/plat/pages/shopmall_third/commodity_details?goods_id=' . $group_id . "&share_id=" . $share_code;
                $sale_num = (new MallOrderDetail())->getSum([['goods_id', '=', $now_group['goods_id']], ['status', '>=', 0], ['status', '<', 50], ['is_marketing_goods', '=', 1]], 'num');
            }
        } else {
            $out['status'] = 0;
            $out['msg'] = "商品类型错误";
            return $out;
        }
        Db::startTrans();
        try {
            /*$update_ret = (new StoreMarketingPersonSetprice())->updateThis(['person_id' => $param['person_id'], 'goods_id' => $param['goods_id'], 'share_id' => 0], ['share_id' => $share_code]);
            if ($update_ret === false) {
                $out['status'] = 0;
                $out['msg'] = "更新改价分享失败";
                return $out;
            }*/

            //将oss上的图不做处理直接使用
            $file_handle = new FileHandle();
            if (!$file_handle->check_open_oss()) {
                $goodMainPic = '.' . str_replace(cfg('site_url'), '', $goodMainPic);
            }

            $img_rand_path = sprintf("%09d", $group_id);
            $rand_num = substr($img_rand_path, 0, 3) . '/' . substr($img_rand_path, 3, 3) . '/' . substr($img_rand_path, 6, 3);

            $imgFriendPath = 'upload/wxapp_group/' . $rand_num . '/friend_' . $group_id . '.png';
            $imgGroupPath = 'upload/wxapp_group/' . $rand_num . '/group_' . $group_id . '.png';
            $imgGroupPathShare = 'runtime/wxapp_group/' . $rand_num . '/group_' . $group_id . '_' . $param['person_id'] . '.png';

            if (!file_exists(dirname($imgGroupPathShare))) {
                mkdir(dirname($imgGroupPathShare), 0777, true);
            }
            /*
             *   分享 600*822 图片
             */
            if (!file_exists($imgGroupPath)) {
                if (!file_exists(dirname($imgGroupPath))) {
                    mkdir(dirname($imgGroupPath), 0777, true);
                }

                $img = imagecreatetruecolor(600, 822);

                $white = imagecolorallocate($img, 255, 255, 255);
                imagefill($img, 0, 0, $white);

                //背景图片绘制
                $src_im = imagecreatefrompng('static/mall/wxapp_group_bg.png');
                //裁剪
                imagecopy($img, $src_im, 0, 0, 0, 0, 600, 822);
                //商品图片绘制
                $info = getimagesize($goodMainPic);
                $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                $src_im = call_user_func_array($fun, array($goodMainPic));

                //创建新图像
                $newimg = imagecreatetruecolor(510, 284);
                // 调整默认颜色
                $color = imagecolorallocate($newimg, 255, 255, 255);
                imagefill($newimg, 0, 0, $color);
                //裁剪
                imagecopyresampled($newimg, $src_im, 0, 0, 0, 0, 510, 284, $info[0], $info[1]);
                imagedestroy($src_im); //销毁原图
                imagecopy($img, $newimg, 45, 125, 0, 0, 510, 284);

                //商品标题
                $font = realpath('static/fonts/PingFang Regular.ttf');
                $tmpGoodName = $group_name;
                $goodNameArr = array();
                $goodNameArr[] = mb_strimwidth($tmpGoodName, 0, 41, '', 'utf-8');
                if ($group_name != $goodNameArr[0]) {
                    $tmpGoodName = str_replace($goodNameArr[0], '', $tmpGoodName);
                    $goodNameArr[] = mb_strimwidth($tmpGoodName, 0, 41, '...', 'utf-8');
                }
                $good_name = implode($goodNameArr, "\n");
                $fontSize = 18;//像素字体
                $fontColor = imagecolorallocate($img, 0, 0, 0);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 45, 460, $fontColor, $font, $good_name);

                //实际价格
                $fontSize = 24;//像素字体
                $fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 40, 560, $fontColor, $font, cfg('Currency_symbol') . $group_price);

                if ($param['origin'] == 'web') { //  $origin  web 网页二维码   mini 小程序二维码
                    if (!file_exists('./runtime/tempQrcode')) {
                        mkdir('./runtime/tempQrcode', 0777, true);
                    }
                    $filePath = './runtime/tempQrcode/' . uniqid() . '.png';
                    $qrcode = new \QRcode();
                    $errorLevel = "L";
                    $qrcode->png($qrCon, $filePath, $errorLevel, 7);
                    $src_im = imagecreatefrompng($filePath);
                } else {
                    //小程序二维码
                    //短链接
                    $link_id = (new ShortLink())->add(['link_url' => $url]);
                    if (!$link_id) {
                        $out['status'] = 0;
                        $out['msg'] = "小程序码生成失败";
                        return $out;
                    }

                    //小程序路径
                    $wxapp_path = "pages/plat_menu/index" . '?redirect=webview&webview_url=' . urlencode('/short_' . $link_id);

                    $qrcode = $this->get_wxapp_qrcode($wxapp_path, 280);
                    if (strpos($qrcode, '{"') === 0) {
                        $qrcodeArr = json_decode($qrcode, true);
                        if ($qrcodeArr && $qrcodeArr['errcode']) {
                            $wxapp_access_token =invoke_cms_model('Access_token_wxapp_expires/get_access_token');
                            if ($wxapp_access_token['errcode']) {
                                $out['status'] = 0;
                                $out['msg'] = "小程序二维码生成失败";
                                return $out;
                            } else {
                                $wxapp_access_token = $wxapp_access_token['access_token'];
                            }

                            $tmpUrlArr = parse_url($wxapp_path);
                            $urlParam = convertUrlQuery($tmpUrlArr['query']);
                            if ($urlParam['redirect'] != 'webview') {
                                return [];
                            }
                            $page = 'pages/webview/webview';
                            $url = urldecode($urlParam['webview_url']);
                            $link_id = (new ShortLink())->add(['link_url' => $url]);
                            if (!$link_id) {
                                $out['status'] = 0;
                                $out['msg'] = "小程序二维码生成失败";
                                return $out;
                            }
                            $scene = '/short_' . $link_id;
                            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $wxapp_access_token;
                            $postData = array(
                                'scene' => $scene,
                                'page' => $page,
                                'width' => 150
                            );

                            $qrcode = Http::curlPostOwn($url, json_encode($postData));
                        }
                    }
                    if (!file_exists('./runtime/tempQrcode')) {
                        mkdir('./runtime/tempQrcode', 0777, true);
                    }
                    $filePath = './runtime/tempQrcode/' . uniqid() . '.jpg';
                    file_put_contents($filePath, $qrcode);
                    $src_im = imagecreatefromjpeg($filePath);
                }

                //创建新图像
                $newimg = imagecreatetruecolor(120, 120);
                // 调整默认颜色
                $color = imagecolorallocate($newimg, 255, 255, 255);
                imagefill($newimg, 0, 0, $color);
                //裁剪
                imagecopyresampled($newimg, $src_im, 0, 0, 0, 0, 120, 120, 280, 280);
                imagedestroy($src_im); //销毁原图

                //二位码位置
                imagecopy($img, $newimg, 45, 630, 0, 0, 120, 120);

                //分享语句
                $fontSize = 14;//像素字体
                $fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 120, 100, $fontColor, $font, '好友分享 下单更优惠哦~');

                //引导识别二维码
                $fontSize = 12;//像素字体
                $fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 180, 690, $fontColor, $font, '长按识别二位码');
                imagettftext($img, 10, 0, 180, 705, $fontColor, $font, '超值好物优惠不停');

                //保存主图
                imagepng($img, $imgGroupPath);

            }

            $img = imagecreatetruecolor(600, 822);

            $white = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $white);

            //背景图片绘制
            $src_im = imagecreatefrompng($imgGroupPath);
            imagecopy($img, $src_im, 0, 0, 0, 0, 600, 822);

            $font = realpath('./static/fonts/PingFang Regular.ttf');
            //用户头像
            if (!empty($user['avatar'])) {
                $avatar_imag = replace_file_domain($user['avatar']);
            } else {
                $avatar_imag = cfg('site_url') . '/static/img/images/nohead.png';
            }


            $avatar = Http::curlGet($avatar_imag);

            if (!file_exists('./runtime/tempQrcode')) {
                mkdir('./runtime/tempQrcode', 0777, true);
            }

            $filePath = './runtime/tempQrcode/' . uniqid() . '.jpg';
            file_put_contents($filePath, $avatar);
            $image_type=getimagesize($filePath);
            if($image_type['mime']=="image/png"){
                imagecreatefrompng($filePath);
            }else{
                $src_im = imagecreatefromjpeg($filePath);
            }

            //创建新图像
            $newimg = imagecreatetruecolor(60, 60);
            // 调整默认颜色
            $color = imagecolorallocate($newimg, 255, 255, 255);
            imagefill($newimg, 0, 0, $color);
            //裁剪
            imagecopyresampled($newimg, $src_im, 0, 0, 0, 0, 60, 60, 132, 132);
            imagedestroy($src_im); //销毁原图

            imagecopy($img, $newimg, 45, 45, 0, 0, 60, 60);

            //用户昵称
            $fontSize = 14;//像素字体
            $fontColor = imagecolorallocate($img, 0, 0, 0);//字的RGB颜色
            imagettftext($img, $fontSize, 0, 120, 72, $fontColor, $font, 'Mrdeng');

            $hitsTxt = "已售" . $sale_num . "单";
            $fontBox = imagettfbbox($fontSize, 0, $font, $hitsTxt);//文字水平居中实质*/
            $groupPriceWidth = $fontBox[2];
            $fontSize = 14;//像素字体
            $fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
            imagettftext($img, $fontSize, 0, 550 - $groupPriceWidth, 557, $fontColor, $font, $hitsTxt);

            imagepng($img, $imgGroupPathShare);

            /* 朋友圈图片结束 */
            /*
             *   分享好友 500*400 图片
             */
            if (!file_exists($imgFriendPath)) {
                if (!file_exists(dirname($imgFriendPath))) {
                    mkdir(dirname($imgFriendPath), 0777, true);
                }

                $img = imagecreatetruecolor(500, 400);

                $white = imagecolorallocate($img, 255, 255, 255);
                imagefill($img, 0, 0, $white);

                //背景图片绘制
                $src_im = imagecreatefrompng('static/mall/wxapp_friend_bg.png');
                //裁剪
                imagecopy($img, $src_im, 0, 0, 0, 0, 500, 400);

                //商品图片绘制
                $info = getimagesize($goodMainPic);
                $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
                $src_im = call_user_func_array($fun, array($goodMainPic));


                //创建新图像
                $newimg = imagecreatetruecolor(554, 308);
                // 调整默认颜色
                $color = imagecolorallocate($newimg, 255, 255, 255);
                imagefill($newimg, 0, 0, $color);
                //裁剪
                imagecopyresampled($newimg, $src_im, 0, 0, 0, 0, 554, 308, $info[0], $info[1]);
                imagedestroy($src_im); //销毁原图
                imagecopy($img, $newimg, -27, 0, 0, 0, 554, 308);

                //实际价格
                $fontSize = 20;//像素字体
                $fontColor = imagecolorallocate($img, 238, 0, 0);//字的RGB颜色
                imagettftext($img, $fontSize, 0, 10, 366, $fontColor, $font, cfg('Currency_symbol') . $group_price);

                //原价格
                // $fontBox = imagettfbbox($fontSize, 0, $font,C('config.Currency_symbol').$group_price);//文字水平居中实质
                //$groupPriceWidth = $fontBox[2];
                // $fontSize = 12;//像素字体
                //$fontColor = imagecolorallocate($img, 153, 153, 153);//字的RGB颜色
                // imagettftext ($img, $fontSize, 0, 30 + $groupPriceWidth, 363, $fontColor, $font, C('config.Currency_symbol').$group_old_price);

                $fontBox = imagettfbbox($fontSize, 0, $font, cfg('Currency_symbol') . $group_old_price);//文字水平居中实质
                $groupOldPriceWidth = $fontBox[2];
                $groupOldPriceBegin = 26 + $groupPriceWidth;
                imageline($img, $groupOldPriceBegin, 357, $groupOldPriceBegin + $groupOldPriceWidth + 10, 357, $fontColor);

                //保存主图
                imagepng($img, $imgFriendPath);
            }
            $share_img_arr = array(
                'share_title' => '我推荐商品',
                'good_img' => $goodMainPic . '?' . date('YmdHi'),
                'friend_img' => cfg('site_url') . '/v20/public/' . $imgFriendPath . '?' . date('YmdHi'),
                'group_img' => cfg('site_url') . '/v20/public/' . $imgGroupPathShare . '?' . date('YmdHi'),
                'wxapp_path' => $wxapp_path,
                'title' => '邀请好友一起抢',
            );

            $out['status'] = 1;
            $out['data'] = $share_img_arr;
            //分享记录新增
            (new StoreMarketingShareLog())->updateThis(['share_code' => $share_code, 'person_id' => $param['person_id'], 'goods_id' => $param['goods_id'], 'goods_type' => $param['goods_type']],['create_time' => time()]);
            Db::commit();
            return $out;
        } catch (\Exception $e) {
            Db::rollback();
            throw new \think\Exception($e->getMessage());
        }
    }


    /*根据商品数据表的图片字段来得到图片*/
    public function get_allImage_by_path($path)
    {
        $return = array();
        if (!empty($path)) {
            $tmp_pic_arr = explode(';', $path);
            foreach ($tmp_pic_arr as $key => $value) {
                $return[$key] = replace_file_domain($value);
            }
            return $return;
        } else {
            return false;
        }
    }

    /**
     * @param $path
     * @param $width
     * @return array|bool|string
     * 获得二维码
     */
    protected function get_wxapp_qrcode($path, $width)
    {
        $wxapp_access_token = invoke_cms_model('Access_token_wxapp_expires/get_access_token');
        $wxapp_access_token = $wxapp_access_token['retval']['access_token'];
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $wxapp_access_token;
        $postData = array(
            'path' => $path,
            'width' => $width
        );

        $qrcode = Http::curlPostOwn($url, json_encode($postData));
        if (is_null(json_decode($qrcode))) {
            return $qrcode;
        }

        //若返回报错，则尝试生成无数量限制的
        $tmpUrlArr = parse_url($path);
        $urlParam = convertUrlQuery($tmpUrlArr['query']);
        if ($urlParam['redirect'] != 'webview') {
            return [];
        }

        $page = 'pages/webview/webview';
        $scene = urldecode($urlParam['webview_url']);
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $wxapp_access_token;
        $postData = array(
            'scene' => $scene,
            'page' => $page,
            'width' => $width
        );
        $qrcode = Http::curlPostOwn($url, json_encode($postData));
        return $qrcode;
    }
}
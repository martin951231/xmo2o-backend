<?php
/**
 * 餐饮
 * @author 张涛
 * @date 2020/07/06
 */

namespace app\foodshop\controller\merchant;

use app\foodshop\model\service\store\FoodshopTableService;
use app\foodshop\model\service\store\FoodshopTableTypeService;
use app\foodshop\model\service\store\MealStoreCategoryRelationService;
use app\foodshop\model\service\store\MealStoreCategoryService;
use app\foodshop\model\service\store\MerchantStoreFoodshopService;
use app\merchant\controller\merchant\AuthBaseController;
use app\merchant\model\service\MerchantStoreService;
use app\merchant\model\service\storeImageService;
use app\merchant\model\service\weixin\MerchantQrcodeService;
use app\shop\model\service\goods\GoodsImageService as  GoodsImageService;

use think\Exception;
use think\facade\Env;
use ZipArchive;
require_once '../extend/phpqrcode/phpqrcode.php';

class FoodshopStoreController extends AuthBaseController
{

    /**
     * 获取餐饮店铺列表
     * @author 张涛
     * @date 2020/07/06
     */
    public function getStoreList()
    {
        $merId = $this->merchantUser['mer_id'] ?? 0;
        if ($merId < 1) {
            return api_output(1003, [], '商家ID不存在');
        }
        $page = $this->request->param("page", "1", "intval");
        $lists = (new MerchantStoreFoodshopService())->getFoodshopStoreListByMerId($merId, $page, 15);
        return api_output(0, $lists);
    }

    /**
     * 餐饮店铺编辑
     * @author 张涛
     * @date 2020/07/09
     */
    public function shopEdit()
    {
        $post = $this->request->param();
        isset($post['is_book']) && $post['is_book'] = (bool)$post['is_book'];
        isset($post['print_type']) && $post['print_type'] = $post['print_type'] ? 1 : 0;
        isset($post['take_seat_by_scan']) && $post['take_seat_by_scan'] = (bool)$post['take_seat_by_scan'];
        isset($post['open_online_pay']) && $post['open_online_pay'] = (bool)$post['open_online_pay'];
        try {
            (new MerchantStoreFoodshopService)->foodshopEdit($post);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 获取店铺信息详情
     * @author 张涛
     * @date 2020/07/09
     */
    public function getShopDetail()
    {
        $storeId = $this->request->param('store_id', 0, 'intval');
        //店铺基础信息
        $storeInfo = (new MerchantStoreService())->getStoreInfo($storeId);
        //餐饮店铺基础信息
        $foodshopInfo = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);
        //获取当前店铺分类
        $cateIds = (new MealStoreCategoryRelationService)->getCategoryRelationByStoreId([$storeId]);
        $currentCate = [];
        foreach ($cateIds as $c) {
            $currentCate[] = $c['cat_fid'] . '_' . $c['cat_id'];
        }

        $category = (new MealStoreCategoryService())->getAllCategory();
        foreach ($category as $k => $v) {
            $category[$k]['value'] = $v['cat_id'];
            $category[$k]['title'] = $v['cat_name'];
            $category[$k]['key'] = $v['cat_id'];
            foreach ($category[$k]['category_list'] as $k2 => $v2) {
                $category[$k]['category_list'][$k2]['value'] = $v['cat_id'] . '_' . $v2['cat_id'];
                $category[$k]['category_list'][$k2]['title'] = $v2['cat_name'];
                $category[$k]['category_list'][$k2]['key'] = $v['cat_id'] . '_' . $v2['cat_id'];
            }
        }
        $rs = [
            'name' => $storeInfo['name'],
            'logo' => $storeInfo['pic_info'] ? replace_file_domain(explode(';',$storeInfo['pic_info'])[0]) : '',
            'store_notice' => $foodshopInfo['store_notice'] ?? '',
            'is_book' => $foodshopInfo['is_book'] ?? 0,
            'book_type' => $foodshopInfo['book_type'] ?? 1,
            'book_time' => $foodshopInfo['book_time'] ?? 0,
            'book_day' => $foodshopInfo['book_day'] ?? 0,
            'book_start' => $foodshopInfo['book_start'] ?? '00:00',
            'book_stop' => $foodshopInfo['book_stop'] ?? '00:00',
            'cancel_time' => $foodshopInfo['cancel_time'] ?? 0,
            'take_seat_by_scan' => $foodshopInfo['take_seat_by_scan'] ?? 0,
            'settle_accounts_type' => $foodshopInfo['settle_accounts_type'] ?? 1,
            'dining_type' => $foodshopInfo['dining_type'] ?? 1,
            'share_table_type' => $foodshopInfo['share_table_type'] ?? 1,
            'open_online_pay' => $foodshopInfo['open_online_pay'] ?? 1,
            'mean_money' => $foodshopInfo['mean_money'] ?? 0,
            'print_type' => $foodshopInfo['print_type'] ?? 0,
            'category' => $category,
            'current_cate' => $currentCate,
            'queue_is_open' => isset($foodshopInfo['queue_is_open']) ? $foodshopInfo['queue_is_open'] : 0,
            'queue_content' => isset($foodshopInfo['queue_content']) ? $foodshopInfo['queue_content'] : '',
            'scan_order_bg_img' => $foodshopInfo['scan_order_bg_img'] ? replace_file_domain($foodshopInfo['scan_order_bg_img']) : '', //扫码点餐背景图
        ];
        return api_output(0, $rs);
    }


    /**
     * 获取餐饮店铺桌台分类
     * @author 张涛
     * @date 2020/07/10
     */
    public function tableTypeList()
    {
        $storeId = $this->request->param('store_id');
        $rs = (new FoodshopTableTypeService())->getTableTypeListByCondition(['store_id' => $storeId]);
        return api_output(0, $rs);
    }

    /**
     * 获取桌位类型详情
     * @author 张涛
     * @date 2020/07/10
     */
    public function getTableType()
    {
        $id = $this->request->param('id');
        if ($id < 1) {
            return api_output(1000, [], '请选择一条记录');
        }
        $info = (new FoodshopTableTypeService())->geTableTypeById($id);
        return api_output(0, $info);
    }

    /**
     * 保存桌位类型  新增+修改
     * @author 张涛
     * @date 2020/07/10
     */
    public function saveTableType()
    {
        $post = $this->request->param();
        if (empty($post)) {
            return api_output(1003, [], '参数有误');
        }
        try {
            (new FoodshopTableTypeService())->saveTableType($post);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 删除桌台类型
     * @return \json
     * @throws \think\Exception
     * @author 张涛
     * @date 2020/07/10
     */
    public function delTableType()
    {
        $id = $this->request->param('id', 0, 'intval');
        $storeId = $this->request->param('store_id', 0, 'intval');
        if ($id < 1 || $storeId < 1) {
            return api_output(1003, [], '参数有误');
        }
        try {
            (new FoodshopTableTypeService())->delTableType($id, $storeId);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 获取桌台列表
     * @author 张涛
     * @date 2020/07/10
     */
    public function tableList()
    {
        $storeId = $this->request->param('store_id', 0, 'intval');
        if ($storeId < 1) {
            return api_output(1000, [], '店铺ID不能为空');
        }
        $rs = (new FoodshopTableService())->getTableByStoreId($storeId);
        return api_output(0, $rs);
    }

    /**
     * 批量下载桌台码
     * User: chenxiang
     * Date: 2020/8/26 14:51
     */
    public function downloadQrcodeTable() {

        $storeId = $this->request->param('store_id', 0, 'intval');
        $isCommon = $this->request->param('is_common', 2, 'intval'); // 是否是普通码 1普通  2已装修
        $tableIds = $this->request->param('table_ids',[]);


        //店铺基础信息
        $storeInfo = (new MerchantStoreService())->getStoreInfo($storeId);
        $goodsImageService = new GoodsImageService();
        // 图片
        if($storeInfo['image'] != '' || $storeInfo['logo']) {
            $storeInfo['image'] = $storeInfo['logo'] ? $storeInfo['logo'] : $storeInfo['image'];
            $tmpPicArr = $goodsImageService->getAllImageByPath($storeInfo['image'], 's');
            //        $returnArr['image_url'] = thumb_img($tmpPicArr[0],180,180,'fill');
            $data['logo_url'] = $tmpPicArr[0]; //店铺logo 地址
        } else {
            $data['logo_url'] = '';
        }


        //获取桌台列表
        $table_list = (new FoodshopTableService())->getTableByStoreId($storeId);
        if(empty($table_list)){
            throw new \think\Exception(L_('请先创建桌台'), 1003);
        }

        $type = '';
        //循环生成桌台的二维码
        foreach($table_list as $key => $value) {
            if ($tableIds && !in_array($value['id'], $tableIds)) {
                continue;
            }

            if($isCommon == 1) { //生成普通二维码

                //生成桌台二维码
                $qrCon = cfg('site_url').'/v20/public/index.php/foodshop/api.foodshopTable/scanCode?store_id='.$storeId. '&table_id=' . $value['id'].'&order_from=1';
                //桌台二维码名称
                // $table_qrcode_name = $storeInfo['name'].'-'.$value['type_name'].'-'.$value['id'];
                $table_qrcode_name = uniqid($storeInfo['store_id'].$value['id']);

                $filename = '../../runtime/qrcode/store/'.$storeId.'/table/normal';

                if(!file_exists($filename)){
                    mkdir($filename,0777,true);
                }

                $qrcode = new \QRcode();
                $errorLevel = "L";
                $size = "9";
                $filename_url = '../../runtime/qrcode/store/'.$storeId.'/table/normal/'.$table_qrcode_name.'.png';
                $qrcode->png($qrCon, $filename_url, $errorLevel, $size);
                $QR = 'runtime/qrcode/store/'.$storeId.'/table/normal/'.$table_qrcode_name.'.png';;        //已经生成的原始二维码图片文件
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;

                $table_info = (new FoodshopTableService())->geTableById($value['id']);

                if($table_info['normal_qrcode_url'] != $data['qrcode']) {
                    //更新图片地址
                    (new FoodshopTableService())->updateTable(['id'=>$value['id'],'normal_qrcode_url'=>$data['qrcode']]);
                }

                $type = 'normal';
            } elseif($isCommon == 2) {//生成已装修的二维码
                //生成桌台二维码
                $qrCon = cfg('site_url') . '/v20/public/index.php/foodshop/api.foodshopTable/scanCode?store_id=' . $storeId . '&table_id=' . $value['id'].'&order_from=1';
                //桌台二维码名称
                // $table_qrcode_name = $storeInfo['name'].'-'.$value['type_name'].'-'.$value['id'];
                $table_qrcode_name = uniqid($storeInfo['store_id'].$value['id']);
                $filename = '../../runtime/store/'.$storeId.'/table/normal';

                if(!file_exists($filename)){
                    mkdir($filename,0777,true);
                }
                $qrcode = new \QRcode();
                $errorLevel = "L";
                $size = "9";
                $filename_url = '../../runtime/store/'.$storeId.'/table/normal/'.$table_qrcode_name.'.png';
                $qrcode->png($qrCon, $filename_url, $errorLevel, $size);

                $QR = 'runtime/store/'.$storeId.'/table/normal/'.$table_qrcode_name.'.png';        //已经生成的原始二维码图片文件
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;

                $imgPath = '../../runtime/store/'.$storeId.'/table/abnormal';
                $data['absolute_path'] = '../../' . $QR;

                if(!file_exists($imgPath)){
                    mkdir($imgPath,0777,true);
                }

                $imgFriendPath = '../../runtime/store/'.$storeId.'/table/abnormal/'.$table_qrcode_name.'.png';

                //绘制装修桌台码
                $img = imagecreatetruecolor(680,798);
                $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
                imagefill ( $img ,  0 ,   0 ,  $white );
                //背景图片绘制
                $src_im = imagecreatefrompng('static/table/table_qrcode_bg.png');

                imagecopy($img,$src_im,0,0,0,0,680,798);


                $filePath = $data['absolute_path'];
                $src_im = imagecreatefrompng($filePath);

                //创建新图像
                $newimg = imagecreatetruecolor(605,605);

                // 调整默认颜色
                $color = imagecolorallocate($newimg, 255, 255, 255);
                imagefill($newimg, 0, 0, $color);
                imagecopy($img,$src_im,118,232,0,0,440,440);


                //桌台名称
                $font = realpath('../../static/fonts/PingFang Regular.ttf');

                $name = $storeInfo['name'];
                if(mb_strlen($name, 'utf-8') > 15) {
                    $name = msubstr($name,0,10);
                }
                $fontSize = 25;//像素字体
                $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色

                //文字居中
                $string = mb_convert_encoding($name, 'html-entities', 'UTF-8');
                $top = '65'; //距离顶部距离
                $font_width = ImageFontWidth($fontSize);
                $font_height = ImageFontHeight($fontSize);
                //取得 str 2 img 后的宽度
                $temp = imagecreatetruecolor($font_height, $font_width);
                $res = imagefttext($temp, $fontSize, 0, (imagesx($img) - $font_width) / 2, $top + $font_height, $fontColor, $font, $string);
                $strImgWidth = $res[2] - $res[0];

                imagefttext($img, $fontSize, 0, (imagesx($img) - $strImgWidth) / 2, $top + $font_height, $fontColor, $font, $string);

                //店铺logo
                if($data['logo_url'] != '') {
                    $filePath = $data['logo_url'];
                    // $temp = explode(".", $filePath); //判断图片文件后缀名
                    // $extension = end($temp);
                    $imageType = getimagesize($filePath);
                    if(stripos($imageType['mime'],'jpg') != false || stripos($imageType['mime'],'jpeg') != false){
                        $src_im = imagecreatefromjpeg($filePath);
                    }elseif(stripos($imageType['mime'],'png') != false) {
                        $src_im = imagecreatefrompng($filePath);
                    } elseif(stripos($imageType['mime'],'webp') != false) {
                        $src_im = imagecreatefromwebp($filePath);
                    } else {
                        $src_im = imagecreatefromgif($filePath);
                    }

                    // //创建新图像
                    // $newimg = imagecreatetruecolor(60,60);
                    // // 调整默认颜色
                    // $color = imagecolorallocate($newimg, 0, 0, 0);
                    // imagefill($newimg, 0, 0, $color);

                    //中心裁剪 圆形
                    $originWidth = imagesx($src_im);
                    $originHeight = imagesy($src_im);

                    $r = 30;
                    $min_r = min($originWidth, $originHeight);
                    if($min_r < $r*2) {
                        $r = intval($min_r/2); //计算半径
                    }

                    $left = intval(($originWidth - 2 * $r) / 2);
                    $top = intval(($originHeight - 2 * $r) / 2);

                    $newpic = imagecreatetruecolor(2 * $r, 2 * $r);
                    imagealphablending($newpic, false);
                    $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
                    for ($x = $left; $x < $originWidth; $x++) {
                        for ($y = $top; $y < $originHeight; $y++) {
                            $c = imagecolorat($src_im, $x, $y);
                            $_x = $x - $r - $left;
                            $_y = $y - $r - $top;
                            if ((($_x * $_x) + ($_y * $_y)) < ($r * $r)) {
                                imagesetpixel($newpic, $x - $left, $y - $top, $c);
                            } else {
                                imagesetpixel($newpic, $x - $left, $y - $top, $transparent);
                            }
                        }
                    }
                    imagesavealpha($newpic, true);
                    imagedestroy($src_im);
                    imagecopy($img,$newpic,(imagesx($img) - $strImgWidth)/2-2*$r-20,35,0,0,$r*2,$r*2);

//                         //保存主图
//                        header('Content-type: image/png');
//                        imagepng($img);
//                        die;

                    // //中心裁剪
                    // $srcWidth = imagesx($src_im);
                    // $srcHeight = imagesy($src_im);

                    // $maxWidth=60; $maxHeight=60;
                    // $scale = max($maxWidth / $srcWidth, $maxHeight / $srcHeight); // 计算缩放比例
                    // //判断原图和缩略图比例 如原图宽于缩略图则裁掉两边 反之..
                    // if($maxWidth / $srcWidth > $maxHeight / $srcHeight){
                    //     //高于
                    //     $srcX = 0;
                    //     $srcY = ($srcHeight - $maxHeight / $scale) / 2 ;
                    //     $cutWidth = $srcWidth;
                    //     $cutHeight = $maxHeight / $scale;
                    // }else{
                    //     //宽于
                    //     $srcX = ($srcWidth - $maxWidth / $scale) / 2;
                    //     $srcY = 0;
                    //     $cutWidth = $maxWidth / $scale;
                    //     $cutHeight = $srcHeight;
                    // }

                    // imagecopyresampled($newimg, $src_im, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $cutWidth, $cutHeight);
                    // $background_color = imagecolorallocate($newimg, 0, 255, 0);  //  指派一个绿色
                    // imagecolortransparent($newimg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
                    // imagedestroy($src_im); //销毁原图

                    // imagecopy($img,$newimg,60,60,0,0,60,60);

                }


                //桌台号
                $table_name = $value['name'];
                $fontSize = 25;//像素字体
                $fontColor = imagecolorallocate ($img, 255, 255, 255 );//字的RGB颜色

                //文字居中
                $string = mb_convert_encoding($table_name, 'html-entities', 'UTF-8');
                $top = '185'; //距离顶部距离
                $font_width = ImageFontWidth($fontSize);
                $font_height = ImageFontHeight($fontSize);
                //取得 str 2 img 后的宽度
                $temp = imagecreatetruecolor($font_height, $font_width);
                $res = imagefttext($temp, $fontSize, 0, (imagesx($img) - $font_width) / 2, $top + $font_height, $fontColor, $font, $string);
                $strImgWidth = $res[2] - $res[0];

                imagefttext($img, $fontSize, 0, (imagesx($img) - $strImgWidth) / 2, $top + $font_height, $fontColor, $font, $string);

                //分享语句
                $fontSize = 24;//像素字体
                $fontColor = imagecolorallocate ($img, 66,139,202 );//字的RGB颜色
                imagettftext($img, $fontSize, 0, 210, 700, $fontColor, $font, '手机扫码开始点餐');

                //保存主图
                $temp = explode(".", $filePath); //判断图片文件后缀名
                $extension = end($temp);

                if(in_array($extension, array('jpg', 'jpeg'))){
                    imagejpeg($img,$imgFriendPath);
                } else {
                    imagepng($img,$imgFriendPath);
                }

                $tab_qr_dir = 'runtime/store/'.$storeId.'/table/abnormal/'.$table_qrcode_name.'.png';

                $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$tab_qr_dir;
                $table_info = (new FoodshopTableService())->geTableById($value['id']);

                if($table_info['abnormal_qrcode_url'] != $data['qrcode']) {
                    //更新图片地址
                    (new FoodshopTableService())->updateTable(['id'=>$value['id'],'abnormal_qrcode_url'=>$data['qrcode']]);
                }

                $type = 'abnormal';
            }
        }

        //下载压缩文件
        $data_url = $this->codeExport($storeId, $type, $storeInfo['name'],$tableIds);

        return api_output(0, $data_url);
    }


    /**
     * 下载桌台二维码图片压缩包
     * User: chenxiang
     * Date: 2020/8/26 17:01
     */
    public function codeExport($storeId,$type = 'normal',$storeName = '',$tableIds=[])
    {

        set_time_limit(0);
        $bak_name = 'table_qrcode_' . date('His') . $storeId . '.zip';
        $zip_name_path = '../../runtime/table_qrcode/';
        $img_name_path = '../../runtime/table/'.$type.'/download/';

        if (!is_dir($zip_name_path)){
            mkdir($zip_name_path,0777,true);
        }

        if (!is_dir($img_name_path)){
            mkdir($img_name_path,0777,true);
        }
        $zip_name_path .= $bak_name;
        $zip = new \ZipArchive();
        if($zip->open($zip_name_path, ZipArchive::CREATE)=== TRUE){
            $rs = (new FoodshopTableService())->getTableByStoreId($storeId);

            if (!empty($rs)) {

                $arrContextOptions = array(
                    "ssl"   => array(
                        "verify_peer"       =>  false,
                        "verify_peer_name"  =>  false,
                    ),
                ); 

                foreach ($rs as $value) {
                    if ($tableIds && !in_array($value['id'], $tableIds)) {
                        continue;
                    }

                    if($type == 'normal') {
                        $image_path = $value['normal_qrcode_url'];
                    } else {
                        $image_path = $value['abnormal_qrcode_url'];
                    }

                    $img = file_get_contents($image_path, false, stream_context_create($arrContextOptions));

                    $table_qrcode_name = $storeName.'-'.$value['type_name'].'-'.$value['name'];

                    file_put_contents($img_name_path.$table_qrcode_name.'.png',$img);

                    $image_name = $table_qrcode_name.'.png';
                    $zip->addFile($img_name_path.$table_qrcode_name.'.png', $image_name);

                }
            }
            $zip->close();
        }

        $download_url = cfg('site_url').'/'.trim($zip_name_path, './');
        $data = ['download_url'=>$download_url];
        return $data;
    }


    /**
     * 下载通用码
     * User: chenxiang
     * Date: 2020/8/27 15:34
     */
    public function downloadQrcodeStore() {

        $storeId = $this->request->param('store_id', 0);
        $isCommon = $this->request->param('is_common', 2, 'intval'); // 是否是普通码 1普通  2已装修

        //店铺基础信息
        $storeInfo = (new MerchantStoreService())->getStoreInfo($storeId);
        $goodsImageService = new storeImageService();
        // 图片
        if($storeInfo['image']) {
            $tmpPicArr = $goodsImageService->getAllImageByPath($storeInfo['image'], 's');
            // $tmpPicArr = $goodsImageService->getAllImageByPath($storeInfo['logo'], 's');
            // $image_url = thumb_img($tmpPicArr[0],60,60,'fill');
            $img = $data['logo_url'] = $tmpPicArr[0]; //店铺image 地址
            if(stripos($data['logo_url'], 'c=Image&a=thumb') !== false){
                $url_arr = parse_url($data['logo_url']);
                if($url_arr['query']){
                    $queryParts = explode('&', $url_arr['query']);

                    $params = array();
                    foreach ($queryParts as $param) {
                        $item = explode('=', $param);
                        $params[$item[0]] = $item[1];
                    }
                    $urlQuery = $params;
                    if($urlQuery['url']){
                        $img = urldecode($urlQuery['url']);
                        $data['logo_url'] = thumb_img($img,60,60,'fill');
                    }
                }
            }
        } else {
            $data['logo_url'] = '';
        }

        if($isCommon == 1) { //生成普通二维码

            //生成通用二维码
            $qrCon = cfg('site_url') . '/v20/public/index.php/foodshop/api.foodshopTable/scanCode?store_id=' . $storeId .'&order_from=4';

            //二维码名称
            $store_qrcode_name = $storeInfo['name']; //店铺名称
            $filename = '../../runtime/qrcode/store/'.$storeId.'/table/normal';

            if(!file_exists($filename)){
                mkdir($filename,0777,true);
            }
            $qrcode = new \QRcode();
            $errorLevel = "L";
            $size = "9";
            $filename_url = '../../runtime/qrcode/store/'.$storeId.'/table/normal/'.$store_qrcode_name.'.png';
            $qrcode->png($qrCon, $filename_url, $errorLevel, $size);
            $QR = 'runtime/qrcode/store/'.$storeId.'/table/normal/'.$store_qrcode_name.'.png';        //已经生成的原始二维码图片文件
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;

            $store_info = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);

            if($store_info['normal_qrcode_url'] != $data['qrcode']) {
                //更新图片地址
                (new MerchantStoreFoodshopService())->updateThis(['store_id'=>$storeId],['normal_qrcode_url'=>$data['qrcode']]);
            }

            $type = 'normal';
        } elseif($isCommon == 2) {//生成已装修的二维码
            //生成桌台二维码
            $qrCon = cfg('site_url') . '/v20/public/index.php/foodshop/api.foodshopTable/scanCode?store_id=' . $storeId .'&order_from=4';

            //桌台二维码名称
            $store_qrcode_name = $storeInfo['name'];
            if (cfg('open_multilingual')) {
                $store_qrcode_name = str_replace(' ', '', $storeInfo['name']);
            } else {
                if(mb_strlen($store_qrcode_name, 'utf-8') > 15) {
                    $store_qrcode_name = msubstr($storeInfo['name'], 0,10);
                }
            }

            $filename = '../../runtime/qrcode/store/'.$storeId.'/table/normal';

            if(!file_exists($filename)){
                mkdir($filename,0777,true);
            }
            $store_qrcode_name = uniqid($storeId);
            $qrcode = new \QRcode();
            $errorLevel = "L";
            if(strlen($qrCon) > 106){
                $size = 9; //测试环境 $size = 9 是正常尺寸
            }else{
                $size = 10;
            }
            $filename_url = '../../runtime/qrcode/store/'.$storeId.'/table/normal/'.$store_qrcode_name.'.png';
            $qrcode->png($qrCon, $filename_url, $errorLevel, $size);

            $QR = 'runtime/qrcode/store/'.$storeId.'/table/normal/'.$store_qrcode_name.'.png';        //已经生成的原始二维码图片文件
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;

            $imgPath = '../../runtime/qrcode/store/'.$storeId.'/table/abnormal';

            if(!file_exists($imgPath)){
                mkdir($imgPath,0777,true);
            }

            $imgFriendPath = $imgPath.'/'.$store_qrcode_name.'.png';

            //绘制装修通用码
            $img = imagecreatetruecolor(680,798);
            $white  =  imagecolorallocate ( $img ,  255 ,  255 ,  255 );
            imagefill ( $img ,  0 ,   0 ,  $white );
            //背景图片绘制
            $src_im = imagecreatefrompng('static/table/table_qrcode_bg.png');

            imagecopy($img,$src_im,0,0,0,0,680,798);

//                header('Content-type: image/png');
//                imagepng($img);
//                die;

//            if(!file_exists(dirname($imgFriendPath))){
//                mkdir(dirname($imgFriendPath),0777,true);
//            }

            $filePath = $data['qrcode'];
            $src_im = imagecreatefrompng($filePath);

            //创建新图像
            $newimg = imagecreatetruecolor(605,605);

            // 调整默认颜色
            $color = imagecolorallocate($newimg, 255, 255, 255);
            imagefill($newimg, 0, 0, $color);

            imagecopy($img,$src_im,118,232,0,0,440,440);


            //桌台名称
            $font = realpath('../../static/fonts/PingFang Regular.ttf');

            $name = $storeInfo['name'];
            $fontSize = 25;//像素字体
            $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
//                $fontBox = imagettfbbox($fontSize, 0, $font,$name);//文字水平居中实质
//                imagettftext( $img, $fontSize, 0, 140, 90, $fontColor, $font, $name);
            //文字居中
            $string = mb_convert_encoding($name, 'html-entities', 'UTF-8');
            $top = '65'; //距离顶部距离
            $font_width = ImageFontWidth($fontSize);
            $font_height = ImageFontHeight($fontSize);
            //取得 str 2 img 后的宽度
            $temp = imagecreatetruecolor($font_height, $font_width);
            $res = imagefttext($temp, $fontSize, 0, (imagesx($img) - $font_width) / 2, $top + $font_height, $fontColor, $font, $string);
            $strImgWidth = $res[2] - $res[0];

            imagefttext($img, $fontSize, 0, (imagesx($img) - $strImgWidth) / 2, $top + $font_height, $fontColor, $font, $string);

            //店铺logo
            if($data['logo_url'] != '') {
                $filePath = $data['logo_url'];
                // $temp = explode(".", $filePath); //判断图片文件后缀名
                // $extension = end($temp);
                
                $imageType = getimagesize($filePath);
                if(stripos($imageType['mime'],'jpg') != false || stripos($imageType['mime'],'jpeg') != false){
                    $src_im = imagecreatefromjpeg($filePath);
                }elseif(stripos($imageType['mime'],'png') != false) {
                    $src_im = imagecreatefrompng($filePath);
                } elseif(stripos($imageType['mime'],'webp') != false) {
                    $src_im = imagecreatefromwebp($filePath);
                } else {
                    $src_im = imagecreatefromgif($filePath);
                }
                // //创建新图像
                // $newimg = imagecreatetruecolor(60,60);
                // 调整默认颜色
                // $color = imagecolorallocate($newimg, 255, 255, 255);
                // imagefill($newimg, 0, 0, $color);
                //裁剪
                // imagecopyresampled($newimg, $src_im, 0, 0, 0,0, 60,60,imagesx($src_im),imagesy($src_im));
                // imagedestroy($src_im); //销毁原图

                // imagecopy($img,$newimg,60,60,0,0,60,60);


                //中心裁剪 圆形
                $originWidth = imagesx($src_im);
                $originHeight = imagesy($src_im);

                $r = 30;
                $min_r = min($originWidth, $originHeight);
                if($min_r < $r*2) {
                    $r = intval($min_r/2); //计算半径
                }

                $left = intval(($originWidth - 2 * $r) / 2);
                $top = intval(($originHeight - 2 * $r) / 2);

                $newpic = imagecreatetruecolor(2 * $r, 2 * $r);
                imagealphablending($newpic, false);
                $transparent = imagecolorallocatealpha($newpic, 0, 0, 0, 127);
                for ($x = $left; $x < $originWidth; $x++) {
                    for ($y = $top; $y < $originHeight; $y++) {
                        $c = imagecolorat($src_im, $x, $y);
                        $_x = $x - $r - $left;
                        $_y = $y - $r - $top;
                        if ((($_x * $_x) + ($_y * $_y)) < ($r * $r)) {
                            imagesetpixel($newpic, $x - $left, $y - $top, $c);
                        } else {
                            imagesetpixel($newpic, $x - $left, $y - $top, $transparent);
                        }
                    }
                }
                imagesavealpha($newpic, true);
                imagedestroy($src_im);
//                    imagecopy($img,$newpic,60,60,((imagesx($img) - $strImgWidth) / 2)-2*$r+10,0,$r*2,$r*2);
                imagecopy($img,$newpic,((imagesx($img) - $strImgWidth) / 2)-2*$r-20,35,0,0,$r*2,$r*2);

                ////中心裁剪 正方形
                // $srcWidth = imagesx($src_im);
                // $srcHeight = imagesy($src_im);
                // $maxWidth=60; $maxHeight=60;
                // $scale = max($maxWidth / $srcWidth, $maxHeight / $srcHeight); // 计算缩放比例
                // //判断原图和缩略图比例 如原图宽于缩略图则裁掉两边 反之..
                // if($maxWidth / $srcWidth > $maxHeight / $srcHeight){
                //     //高于
                //     $srcX = 0;
                //     $srcY = ($srcHeight - $maxHeight / $scale) / 2 ;
                //     $cutWidth = $srcWidth;
                //     $cutHeight = $maxHeight / $scale;
                // }else{
                //     //宽于
                //     $srcX = ($srcWidth - $maxWidth / $scale) / 2;
                //     $srcY = 0;
                //     $cutWidth = $maxWidth / $scale;
                //     $cutHeight = $srcHeight;
                // }

                // imagecopyresampled($newimg, $src_im, 0, 0, $srcX, $srcY, $maxWidth, $maxHeight, $cutWidth, $cutHeight);
                // $background_color = imagecolorallocate($newimg, 0, 255, 0);  //  指派一个绿色
                // imagecolortransparent($newimg, $background_color);  //  设置为透明色，若注释掉该行则输出绿色的图
                // imagedestroy($src_im); //销毁原图

                // imagecopy($img,$newimg,60,60,0,0,60,60);

            }


            //分享语句
            $fontSize = 24;//像素字体
            $fontColor = imagecolorallocate ($img, 66,139,202 );//字的RGB颜色
            imagettftext($img, $fontSize, 0, 210, 700, $fontColor, $font, '手机扫码开始点餐');


            $temp = explode(".", $filePath); //判断图片文件后缀名
            $extension = end($temp);
            if(in_array($extension, array('jpg', 'jpeg'))){
                imagejpeg($img,$imgFriendPath);
            } else {
                imagepng($img,$imgFriendPath);
            }

            //保存主图
//
//                header('Content-type: image/png');
//                imagepng($img);
//                die;

            $data['qrcode'] =  $http_type.$_SERVER["HTTP_HOST"].'/'.$imgFriendPath;

            $store_info = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);

            if($store_info['abnormal_qrcode_url'] != $data['qrcode']) {
                //更新图片地址
                (new MerchantStoreFoodshopService())->updateThis(['store_id'=>$storeId],['abnormal_qrcode_url'=>$data['qrcode']]);
            }

            $type = 'abnormal';
        }

        //下载地址
        $download_url = $data['qrcode'];
        $data_url = ['download_url'=>$download_url,'img_name'=>$storeInfo['name']];

//        //下载压缩文件
//        $data_url = $this->codeStoreExport($storeId, $type, $storeInfo['name']);
        return api_output(0, $data_url);
    }



    public function radius_img($imgpath, $radius = 15) {
        $src_img = null;
        $info = getimagesize($imgpath);
        $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        // if ($val['stream']) {   //如果传的是字符串图像流
        //     $info = getimagesizefromstring($val['url']);
        //     $function = 'imagecreatefromstring';
        // }
        $src_img = $function($imgpath);
        $w = $info[0];
        $h = $info[1];
        // $radius = $radius == 0 ? (min($w, $h) / 2) : $radius;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        if(!file_exists('./runtime/circle_image')){
            mkdir('./runtime/circle_image',0777,true);
        }
        $md5_title = md5(time().rand(0,100));
        imagepng($img,'./runtime/circle_image/qrcode_'.$md5_title.'.png');
        return './runtime/circle_image/qrcode_'.$md5_title.'.png';
    }

    /**
     * 下载店铺通用二维码图片压缩包
     * User: chenxiang
     * Date: 2020/8/26 17:01
     */
    public function codeStoreExport($storeId,$type = 'normal',$storeName = '')
    {

        set_time_limit(0);

        $bak_name = 'store_qrcode_' . date('His') . $storeId . '.zip';
//        $zip_name_path = './runtime/code'.date('Ymd').'/'.$_GET['id'].'/';
//        $img_name_path = './runtime/code'.date('Ymd').'/'.$_GET['id'].'/image/';
        $zip_name_path = '../../runtime/'.$storeId.'/table/';
        $img_name_path = '../../runtime/store/'.$storeId.'/table/'.$type.'/download/';

        if (!is_dir($zip_name_path)){
            mkdir($zip_name_path,0777,true);
        }

        if (!is_dir($img_name_path)){
            mkdir($img_name_path,0777,true);
        }

        $zip_name_path .= $bak_name;
        $zip = new \ZipArchive();
        if($zip->open($zip_name_path, ZipArchive::CREATE)=== TRUE){
            $rs = (new MerchantStoreFoodshopService())->getStoreByStoreId($storeId);

            if (!empty($rs)) {
                if($type == 'normal') {
                    $image_path = $rs['normal_qrcode_url'];
                } else {
                    $image_path = $rs['abnormal_qrcode_url'];
                }
                $img = file_get_contents($image_path);

                $store_qrcode_name = $storeName;

                file_put_contents($img_name_path.$store_qrcode_name.'.png',$img);

                $image_name = $store_qrcode_name.'.png';
                $zip->addFile($img_name_path.$store_qrcode_name.'.png', $image_name);
            }
            $zip->close();
        }

        $download_url = cfg('site_url').'/'.trim($zip_name_path, '.');
        $data = ['download_url'=>$download_url];

        return $data;
    }


    /**
     * 获取桌位类型详情
     * @author 张涛
     * @date 2020/07/10
     */
    public function getTable()
    {
        $id = $this->request->param('id');
        if ($id < 1) {
            return api_output(1000, [], '请选择一条记录');
        }
        $info = (new FoodshopTableService())->geTableById($id);
        return api_output(0, $info);
    }

    /**
     * 保存桌位  新增+修改
     * @author 张涛
     * @date 2020/07/10
     */
    public function saveTable()
    {
        $post = $this->request->param();
        if (empty($post)) {
            return api_output(1003, [], '参数有误');
        }
        try {
            (new FoodshopTableService())->saveTable($post);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 删除桌位
     * @return \json
     * @throws \think\Exception
     * @author 张涛
     * @date 2020/07/10
     */
    public function delTable()
    {
        $id = $this->request->param('id', 0, 'intval');
        $storeId = $this->request->param('store_id', 0, 'intval');
        if ($id < 1 || $storeId < 1) {
            return api_output(1003, [], '参数有误');
        }
        try {
            (new FoodshopTableService())->delTable($id, $storeId);
            return api_output(0, []);
        } catch (\Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }

    }

    /**
     * desc: 扫码登录获取二维码接口
     * return :array
     * Author: hengtingmei
     * Date Time: 2020/07/06 10:58
     */
    public function seeQrcode(){

        $param['store_id'] = $this->request->param("store_id", "", "intval");

        $merchantStoreFoodshopService = new MerchantStoreFoodshopService();
        try {
            $qrcodeReturn = $merchantStoreFoodshopService->seeQrcode($param['store_id']);
        } catch (\Exception $e) {
            return api_output_error(1005, $e->getMessage());
        }

        return api_output(0, $qrcodeReturn);
    }
}

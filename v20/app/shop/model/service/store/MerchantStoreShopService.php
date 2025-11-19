<?php
/**
 * 外卖店铺service
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/19 20:08
 */

namespace app\shop\model\service\store;
use app\common\model\service\image\ImageService;
use app\shop\model\db\MerchantStoreShop;
use app\shop\model\db\MerchantStoreShop as MerchantStoreShopModel;
use app\shop\model\service\goods\ShopGoodsSortService as ShopGoodsSortService;
class MerchantStoreShopService{
    public $merchantStoreShopModel = null;
    public $sendTimeType = null;
    public function __construct()
    {
		$this->merchantStoreShopModel = new MerchantStoreShopModel();
		
		$this->sendTimeType = [
			'分钟',
			'小时',
			'天',
			'周',
			'月',
		];
	}
	
    /**
     * 根据店铺id获取店铺列表
     * @param $storeId
     * @return array
     */
	public function getStoreByStoreId($storeId){
		$shopStore = $this->merchantStoreShopModel->getStoreByStoreId($storeId);
		if(!$shopStore) {
            return [];
        }
		return $shopStore->toArray();
	}

    /**
     * 获得店铺列表
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $shopStore = $this->merchantStoreShopModel->getSome($where,$field,$order,$page,$limit);
        if(!$shopStore) {
            return [];
        }
        return $shopStore->toArray();
    }

    /**
     * 获取快店店铺详情
     * @param $storeId
     * @author: 张涛
     * @date: 2020/8/25
     */
    public function getStoreShopDetailByStoreId($storeId, $fields = '*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $store = $this->merchantStoreShopModel
            ->alias('shop')
            ->join([$prefix . 'merchant_store' => 'store'], 'store.store_id = shop.store_id')
            ->where(['store.store_id' => $storeId])
            ->field($fields)
            ->find();
        return $store ? $store->toArray() : [];
    }
    
    // 生成分享海报
    public function shopSharePoster($storeId, array $params = []){
        //二维码图片      
        $qrcodePath = cfg('site_url').'/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page='.urlencode('/pages/shop_new/shopDetail/shopDetail?store_id='.$storeId);

        // 生成图片路径
        $filename = '../../runtime/shop/sharePoster/image';
        $md5string = md5(json_encode($params,256));
        // 生成图片名称
        $image_name = "shop_poster{$storeId}{$md5string}.png";

        // 创建目录
        if(!file_exists($filename))
            mkdir($filename,0755,true);

        // 图片已存在直接返回
        if(file_exists($filename.'/'.$image_name)){
            return cfg('site_url').'/runtime/shop/sharePoster/image/'.$image_name;
        }
        // 图片最后保存路径
        $imgFriendPath = $filename.'/'.$image_name;
        $bgImgSrc = '../../static/shop/poster_bg.png';
        //创建主图
        $img = imagecreatetruecolor(750,1240);
        
        if(file_exists($bgImgSrc)){//背景图
            $src_im = imagecreatefrompng($bgImgSrc);
            imagecopy($img,$src_im,0,0,0,0,750,1240);
        }

        // 创建二维码图像
        $filePath = "{$filename}/wxapp_qrcode{$storeId}.png";
        if(!file_exists($filePath)){
            $image = file_get_contents($qrcodePath);
            file_put_contents($filePath, $image);
            (new ImageService())->scaleImg($filePath, $filePath, 180, 180);
        }
       
        if(file_exists($filePath)){
            $src_im = imagecreatefromstring(file_get_contents($filePath));
            imagecopy($img,$src_im,285,960,0,0,180,180);
        }

        // 店铺图片
        if(!empty($params['image'])){
            // 压缩裁剪图片
            (new ImageService())->thumb2($params['image'], $filename.'/cover_image.jpg','',130, 115);
            if(file_exists($filename.'/cover_image.jpg')){
                $src_im = imagecreatefromstring(file_get_contents( $filename.'/cover_image.jpg'));
                imagecopy($img,$src_im,140,380,0,0,130,115);
            }
        }

        //字体
        $font = realpath('../../static/fonts/PingFang Regular.ttf');

        // 景区名称
        $name = $params['name'];
        if(mb_strlen($name, 'utf-8') > 15) {
            $name = msubstr($name,0,9);
        }
        
        $fontSize = 16;//像素字体
        $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
        $string = $name;
        $top = 230; //距离顶部距离
        $font_height = ImageFontHeight($fontSize);
        
        imagefttext($img, $fontSize, 0, (750-$fontSize*mb_strlen($name, 'utf-8'))/2, $top + $font_height, $fontColor, $font, $string);
        imagefttext($img, $fontSize, 0, (750-$fontSize*mb_strlen($name, 'utf-8'))/2, $top + $font_height, $fontColor, $font, $string);
        
        //店铺名称
        $string = $params['name'];
        $fontSize = 14;//像素字体
        $fontColor = imagecolorallocate ($img, 0, 0, 0 );//字的RGB颜色
       
        $top = 385; //距离顶部距离
        $font_height = ImageFontHeight($fontSize);
        //取得 str 2 img 后的宽度
        imagefttext($img, $fontSize, 0, 280, $top + $font_height, $fontColor, $font, $string);
        imagefttext($img, $fontSize, 0, 280, $top + $font_height, $fontColor, $font, $string);

        $top += $font_height + 15; //距离顶部距离

        //优惠信息
        $fontSize = 14;//像素字体
        $string = $params['star'].'分';
        $font_height = ImageFontHeight($fontSize);
        $fontColor = imagecolorallocate ($img, 255,96,0 );//字的RGB颜色
        imagefttext($img, $fontSize, 0,280, $top + $font_height, $fontColor, $font, $string);
        imagefttext($img, $fontSize, 0,280, $top + $font_height, $fontColor, $font, $string);
        
//        $string = '已售'.$params['month_sale_count'];
//        $font_height = ImageFontHeight($fontSize);
        $fontColor = imagecolorallocate ($img, 102,102,102 );//字的RGB颜色
//        imagefttext($img, $fontSize, 0, 330, $top + $font_height, $fontColor, $font, $string);
        $fontSize = 12;//像素字体
        $top += 30; //距离顶部距离
        $string = '起送￥'.$params['delivery_price'];
        $font_height = ImageFontHeight($fontSize);
        imagefttext($img, $fontSize, 0, 280, $top + $font_height, $fontColor, $font, $string);
        
        $fontWidth = $fontSize*mb_strlen($string, 'utf-8');
        $string = '配送￥'.$params['delivery_money'];
        $font_height = ImageFontHeight($fontSize);
        imagefttext($img, $fontSize, 0, 300+$fontWidth, $top + $font_height, $fontColor, $font, $string);

        $top += 30; //距离顶部距离
        if(!empty($params['coupon_list'])){
            $fontColor = imagecolorallocate ($img, 250,75,20 );//字的RGB颜色
            $string = '';
            is_string($params['coupon_list']) && $params['coupon_list'] = json_decode($params['coupon_list'],true);
            
            foreach($params['coupon_list'] as $k => $v){
                $fontWidth = $fontSize*mb_strlen($string, 'utf-8')+30;
                $string = $v['value'];
                $font_height = ImageFontHeight($fontSize);
               
                if(280+$fontWidth*$k > 500){
                    if($top + $font_height + $k*15 > 490){
                        break;
                    }
                    imagefttext($img, $fontSize, 0, 280, $top + $font_height + $k*15, $fontColor, $font, $string);
                }else{
                    imagefttext($img, $fontSize, 0, 280+$fontWidth*$k, $top + $font_height, $fontColor, $font, $string);
                }
            }
        }
        
        //保存主图
        imagepng($img,$imgFriendPath);
        imagedestroy ($img);
        
        return cfg('site_url').'/runtime/shop/sharePoster/image/'.$image_name;
    }
}
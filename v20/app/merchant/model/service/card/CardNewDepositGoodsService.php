<?php
/**
 * 商家寄存商品service
 * Author: fenglei
 * Date Time: 2021/11/04 11:32
 */

namespace app\merchant\model\service\card;

use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\CardNewDepositGoodsBindUser;
use app\merchant\model\db\CardNewDepositGoodsVerification;
use think\facade\Cache;

require_once '../extend/phpqrcode/phpqrcode.php';

class CardNewDepositGoodsService 
{
    public $cardNewDepositGoods = null;
    public $CardNewDepositGoodsBindUser = null;
    public $CardNewDepositGoodsVerification = null;

    public function __construct()
    {
        $this->cardNewDepositGoods = new CardNewDepositGoods();
        $this->CardNewDepositGoodsBindUser = new CardNewDepositGoodsBindUser();
        $this->CardNewDepositGoodsVerification = new CardNewDepositGoodsVerification();
    }


    /**
     * 商品列表
     */
    public function getGoodsList($params)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['is_del', '=', 0];
        return $this->cardNewDepositGoods->with(['sort'])->where($condition)->order('create_time desc')->paginate($params['page_size'])->each(function($item, $key){
            $item['expiry_date'] = date('Y/m/d', $item['start_time']) . " ~ " . date('Y/m/d', $item['end_time']);
            return $item;
        });
    }
 


    /**
     *  添加更新商品
     */
    public function goodsEdit($params)
    { 
        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);
        if($params['end_time'] < strtotime(date('Y-m-d'))){
            throw new \Exception('不可添加已过期的商品！');
        }
        if(empty($params['goods_id'])){
            //添加
            $params['create_time'] = time();
            $this->cardNewDepositGoods->save($params);
        }else{
            //更新
            $condition[] = ['mer_id', '=', $params['mer_id']];
            $condition[] = ['goods_id', '=', $params['goods_id']];
            $info = $this->cardNewDepositGoods->where($condition)->find();
            if(!$info){
                throw new \Exception('更新记录不存在！');
            }
            $params['update_time'] = time();
            $info->save($params);
            
        }
    }

    /**
     * 获取详情
     */
    public function getGoodsDetail($params)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['goods_id', '=', $params['goods_id']];
        $data = $this->cardNewDepositGoods->where($condition)->find();
        if(!$data){
            throw new \Exception('内容不存在！');
        }
        $data->sort;
        $data->start_time = date('Y-m-d', $data->start_time);
        $data->end_time = date('Y-m-d', $data->end_time);
        $data->site_url = cfg('site_url');
        return $data;
    }

    /**
     * 删除商品
     */
    public function delGoods($params)
    {
        $condition = array();
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['goods_id', '=', $params['goods_id']];
        $condition[] = ['is_del', '=', 0];
        $info = $this->cardNewDepositGoods->where($condition)->find();
        if(!$info){
            throw new \Exception('内容不存在！');
        }
        $info->is_del = 1;
        return $info->save();
    }


    /**
     * 客户端获取商品详情
     */
    public function getDepositGoodsDetail($params)
    {
        $condition = array();
        $condition[] = ['id', '=', $params['bind_id']];

        $with = array(
            'merchant' => function($query){
                $query->withField(['mer_id','name','pic_info']);
            },
            'store' => function($query){
                $query->withField(['store_id','name','adress','long','lat','pic_info']);
            },
            'goods' => function($query){
                $query->withField(['goods_id','name','image']);
            },
            'bindgoods' => function($query){
                $bind_goods_condition = array();
                $bind_goods_condition[] = ['is_used', '=', 0];
                $query->field(['id','bind_id','number'])->where($bind_goods_condition);
            }
        );
        $data = $this->CardNewDepositGoodsBindUser
                ->field(['id','mer_id','store_id','card_id','goods_id','start_time','end_time','(num - use_num) as has_num,number'])
                ->with($with)
                ->where($condition)
                ->find();
 
        if(!$data){
            throw new \Exception('内容不存在！');
        }
        //解析商家图片 
        $images = (new \app\merchant\model\service\storeImageService())->getAllImageByPath($data->merchant->pic_info);
        $data->merchant->pic_info = $images[0] ?? '';
        //格式化时间
        $data->start_time = date('Y.m.d', $data->start_time);
        $data->end_time = date('Y.m.d', $data->end_time);

        //生成二维码
        $data->qrcode_url = $this->generateQrcode($data->id, $data->number);

        //写入缓存,以便判断核销状态
        $verification_condition = array();
        $verification_condition[] = ['bind_id', '=', $params['bind_id']];
        $verification_id = $this->CardNewDepositGoodsVerification->where($verification_condition)->order('use_time desc')->value('id');
        Cache::set('deposit_'.$params['bind_id'], $verification_id ?: -1, 3600);
        return $data;
    }

    /**
     * 核销状态
     */
    public function getVerificationStatus($params)
    {
        //读取缓存
        $cache_verification_id = Cache::get('deposit_'.$params['bind_id']);

        if(!$cache_verification_id){
            throw new \Exception('');
        }

        $verification_condition = array();
        $verification_condition[] = ['bind_id', '=', $params['bind_id']];
        $verification = $this->CardNewDepositGoodsVerification->where($verification_condition)->order('use_time desc')->find();

        $returnArr = array();
        $returnArr['bind_id'] = $params['bind_id'];
        //核销中
        if(($cache_verification_id == -1 && !$verification) ||  $verification->id == $cache_verification_id){
            $returnArr['status'] = 0;
            $returnArr['status_text'] = "核销中";
        }else if($verification->status == 1){ //核销成功
            $returnArr['status'] = 1;
            $returnArr['status_text'] = "核销成功";
        }else{//核销失败
            $returnArr['status'] = 2;
            $returnArr['status_text'] = "核销失败";
        }

        return $returnArr;
    }




    /**
     * 生成二维码
     */
    private function generateQrcode($bind_id, $text)
    { 
        $table_qrcode_name = md5($bind_id);

        $filename = '../../runtime/qrcode/card/'. date('Y-m-d') . '/' . $bind_id.'/deposit';

        if(!file_exists($filename)){
            mkdir($filename,0777,true);
        }

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        $file =  $filename . '/' . $table_qrcode_name.'.png';
        if(file_exists($file)){
            return $http_type.$_SERVER["HTTP_HOST"].'/'.$file;
        }


        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $filename_url = $file;
        $qrcode->png($text, $filename_url, $errorLevel, $size);
        
        return $http_type.$_SERVER["HTTP_HOST"].'/'.$filename_url;
    }
}
<?php
/**
 * 微信app授权相关
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/10/22
 */

namespace app\common\model\service\weixin;
use app\common\model\db\RecognitionWxapp as RecognitionWxappModel;
use net\Http as Http;
use think\Exception;

class RecognitionWxappService {
    public $recognitionWxappModel = null;

    //微信生成二维码前缀
    const TICKET_PREFIX = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=';

    public function __construct()
    {
        $this->recognitionWxappModel = new RecognitionWxappModel();
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->recognitionWxappModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }
    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->recognitionWxappModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     * 更新数据
     * @param $data array 数据
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->recognitionWxappModel->updateThis($where,$data);

        return $result;

    }
}
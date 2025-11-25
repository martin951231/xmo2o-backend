<?php
/**
 * ExpressTemplateService.php
 * 运费模板service
 * Create on 2020/10/24 13:11
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\mall\model\db\ExpressTemplate;
use app\mall\model\db\ExpressTemplateArea;
use app\mall\model\db\ExpressTemplateValue;
use app\mall\model\db\ShopGoods;
use app\mall\model\service\MerchantStoreMallService;

class ExpressTemplateService
{
    public function __construct()
    {
        $this->expressTemplateModel = new ExpressTemplate();
        $this->ExpressTemplateAreaModel = new ExpressTemplateArea();
        $this->ExpressTemplateValueModel = new ExpressTemplateValue();
    }

    /**
     * @param $mer_id
     * @return array
     * 获取某个商家的运费模板
     */
    public function getMerchantET($mer_id)
    {
        $where = ['mer_id' => $mer_id];
        $arr = $this->expressTemplateModel->getETByMerId($where);
        return $arr;
    }

    /**
     * @param $id
     * @return mixed
     * 根据id获取运费模板
     */
    public function getETById($id)
    {
        $where = ['id' => $id];
        $arr = $this->expressTemplateModel->getOne($where);
        return $arr;
    }

    /**
     * 计算快递费(卢敏添加)
     * $store_id   店铺ID
     * $param  格式如下：
     *         [
     *             [
     *                 fright_id:1,//运费模板ID
     *                 num:1,//对应数量
     *                 other_area_fright:1,//其他区域运费
     *             ],...
     *         ]
     * $province_id 发货至  省份ID
     * $city_id 发货至  城市ID
     * @return float 运费
     */
    public function computeFee($store_id, $param = [], $city_id = 0,$province_id = 0){
        $fee = 0;//运费
        if(empty($store_id) || empty($param) || empty($city_id)) return $fee;
        //获取店铺计算运费的配置
        $now_store_config = (new MerchantStoreMallService)->getMallStoreInfo($store_id);

        //获取运费模板信息
        $fee_arr = [];
        foreach ($param as $info) {
            $template = $this->getETById($info['fright_id']);
            $template_area = $this->getTemplateArea($info['fright_id']);
            $template_value = $this->getTemplateValue($info['fright_id']);
            if(empty($template) || empty($template_area) || empty($template_value)){
                $fee_arr[] = $info['other_area_fright'] ?? 0;
                continue;
            }
            $vid = 0;
            //先城市匹配,后匹配省份
            foreach ($template_area as $tarea) {
                if($tarea['area_id'] == $city_id){
                    $vid = $tarea['vid'];
                    break;
                }
            }
            if(!$vid){//未匹配到城市，再匹配省份
                foreach ($template_area as $tarea) {
                    if($tarea['area_id'] == $province_id){
                        $vid = $tarea['vid'];
                        break;
                    }
                }
            }
            //省市都未匹配到，匹配同城
            if (!$vid) {
                foreach ($template_area as $tarea) {
                    if ($tarea['area_id'] == 0 && isset($now_store_config['city_id']) && $city_id == $now_store_config['city_id']) {
                        $vid = $tarea['vid'];
                        break;
                    }
                }
            }

            if($vid == '0'){//未匹配到
                $fee_arr[] = $info['other_area_fright'] ?? 0;
                continue;
            }
            $find_rule = $template_value[$vid];
            if(empty($find_rule)){//未匹配到
                $fee_arr[] = $info['other_area_fright'] ?? 0;
                continue;
            }
            if($template['freight_type']==1){//计件
                if($info['num'] <= $find_rule['first_weight'] || $find_rule['add_weight'] == '0' || $find_rule['add_freight'] == '0'){
                    $fee_arr[] = $find_rule['first_freight'];
                    continue;
                }
                elseif($info['num'] > $find_rule['first_weight']){
                    $fee_arr[] = $find_rule['first_freight'] + ceil(($info['num'] - $find_rule['first_weight'])/$find_rule['add_weight'])*$find_rule['add_freight'];
                }
            }elseif($template['freight_type']==2){//计重
                $where=[
                    ['goods_id','=',$info['goods_id']]
                ];

                $goods_arr=(new ShopGoods())->getOne($where);
                $weight_unit=$goods_arr['weight_unit'];//单位1 g 2 kg
                $weight=$goods_arr['weight'];//重量
                if($weight_unit==1){//单位g
                    $weight=$goods_arr['weight']/1000;
                }
                $total_weight=$info['num']*$weight;
                if($total_weight <= $find_rule['first_weight'] || $find_rule['add_weight'] == '0' || $find_rule['add_freight'] == '0'){
                    $fee_arr[] = $find_rule['first_freight'];
                    continue;
                }elseif($total_weight > $find_rule['first_weight']){
                    $left_weight=$total_weight-$find_rule['first_weight'];//除去首重
                    if($left_weight<=$find_rule['add_weight']){//剩余重量小于续重重量，则加上续费就行
                        $fee_arr[] = $find_rule['first_freight'] + ceil($find_rule['add_freight']);
                    }else{//剩余重量大于续重重量，则还要加上续费，向上取整ceil
                        $fee_arr[] = $find_rule['first_freight'] +ceil($left_weight/$find_rule['add_weight'])*$find_rule['add_freight'];
                    }
                }else{
                    continue;
                }
            }else{//不存在方式
                continue;
            }
        }

        switch ($now_store_config['delivery_fee_type']) {
            case '1'://单独计算，取总和
                $fee = array_sum($fee_arr);
                break;
            case '2'://最大值计算，取最大
                rsort($fee_arr);
                $fee = $fee_arr[0];
                break;
        }
        return $fee;
    }

    public function getTemplateValue($tid){
        $where = [
            ['tid', '=', $tid]
        ];
        $data = $this->ExpressTemplateValueModel->getSome($where);
        $return = [];
        if($data){
            $data = $data->toArray();
            foreach ($data as $key => $value) {
                $return[$value['id']] = $value;
            }
        }
        return $return;
    }

    public function getTemplateArea($tid){
        $where = [
            ['tid', '=', $tid]
        ];
        $data = $this->ExpressTemplateAreaModel->getSome($where);
        return $data ? $data->toArray() : [];
    }
}
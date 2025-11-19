<?php
/**
 * MallEInvoiceService.php
 * 商家发票service
 * Create on 2020/10/13 14:30
 * Created by zhumengqun
 */

namespace app\mall\model\service;

use app\community\model\service\DesSecurityService;
use app\mall\model\db\MallEConfig;
use app\mall\model\db\MallERecord;
use app\mall\model\db\MallGoods;

class MallEInvoiceService
{
    public function __construct()
    {
        $this->mallERecordModel = new MallERecord();
    }

    /**
     * 获取该商家的所有发票纪录
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    public function getERecord($param)
    {
        if (empty($param['mer_id'])) {
            throw new \think\Exception('缺少mer_id参数');
        }
        $where = [['r.mer_id', '=', $param['mer_id']]];
        if (!empty($param['start_time'])) {
            array_push($where, ['r.create_time', '>', strtotime($param['start_time'])]);
        }
        if (!empty($param['end_time'])) {
            array_push($where, ['r.create_time', '<', strtotime($param['end_time'])]);
        }
        switch ($param['search_type']) {
            case 1:
                array_push($where, ['o.order_no', 'like', '%' . $param['keyword'] . '%']);
                break;
            case 2:
                array_push($where, ['o.username', 'like', '%' . $param['keyword'] . '%']);
                break;
            case 3:
                array_push($where, ['m.name', 'like', '%' . $param['keyword'] . '%']);
                break;
            case 4:
                array_push($where, ['o.phone', 'like', '%' . $param['keyword'] . '%']);
                break;
        }
        if (empty($param['keyword'])) {
            $where = ['r.mer_id' => $param['mer_id']];
            $data = $this->mallERecordModel->getAllERecord($where, 1, 10);
        } elseif ($param['search_type'] == 3) {
            $data = $this->mallERecordModel->getSearch1($where, 1, 10);
        } else {
            $data = $this->mallERecordModel->getSearch2($where, 1, 10);
        }
        if (!empty($data)) {
            $orderService = new MallOrderService();
            $storeService = new MerchantStoreService();
            $count = 0;
            foreach ($data as $key => $val) {
                //根据订单id获取订单编号 订单总额 开票总额 下单时间 下单人 手机号码
                $orderInfo = $orderService->getOne($val['order_id']);
                $data[$key]['order_no'] = $orderInfo['order_no'];
                $data[$key]['money_total'] = $orderInfo['money_total'];
                $data[$key]['money_real'] = $orderInfo['money_real'];
                $data[$key]['username'] = $orderInfo['username'];
                $data[$key]['phone'] = $orderInfo['phone'];
                //根据店铺id 获取店铺名称
                $store = $storeService->getStoreByStoreId($val['store_id']);
                $data[$key]['store_name'] = $store[0]['name'];
                $count++;
            }
            $list['total'] = $count;
            $list['data'] = $data;
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 根据流水号获取电子发票pdf
     * @param $fpqqlsh
     * @return mixed
     * @throws \think\Exception
     */
    public function getInvoice($fpqqlsh)
    {
        if (empty($fpqqlsh)) {
            throw new \think\Exception('缺少流水号参数');
        }
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/v20/runtime/pdf/';
        if (file_exists($file_path . iconv('UTF-8', 'GBK', $fpqqlsh) . "." . 'pdf')) {
            return cfg('site_url') . '/v20/runtime/pdf/' . iconv('UTF-8', 'GBK', $fpqqlsh) . "." . 'pdf';
        } else {
            $arr['identity'] = cfg('house_e_invoice');
            $order[] = $fpqqlsh;
            $arr['fpqqlsh'] = $order;
            $des_security = new DesSecurityService();
            $arr = $des_security->encrypt($arr);
            $url = cfg('house_e_invoice_url2');
            $response = http_request($url, 'POST', ['order' => $arr]);
            $res = $this->downImgRar(json_decode($response[1], true)['list'][0]['c_url'], $fpqqlsh, 'pdf');
            return cfg('site_url') . '/' . $res;
        }
    }

    /**
     * @param $filename
     * @return bool|false|int
     * 下载生成的发票PDF
     */
    function downImgRar($url, $rename, $ext)
    {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/v20/runtime/pdf/';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $rawdata = curl_exec($ch);
        curl_close($ch);
        // 使用中文文件名需要转码
        if (!is_dir($file_path)) {
            mkdir($file_path);
        }
        $fp = fopen($file_path . iconv('UTF-8', 'GBK', $rename) . "." . $ext, 'w+');
        fwrite($fp, $rawdata);
        fclose($fp);
        // 返回路径
        return '/v20/runtime/pdf/' . $rename . "." . $ext;
    }

    /**
     * 用户申请开发票
     * @param $post_params
     * @return bool|\json
     * @throws \think\Exception
     */
    public function addInvoice($post_params)
    {
        if (empty($post_params['uid'])) {
            throw new \think\Exception('uid参数缺失');
        }
        $ele_config = new MallEConfig();
        $e_config = $ele_config->getEConfig($post_params['mer_id']);
        if (empty($e_config) || empty($e_config['duty']) || empty($e_config['account_number']) || empty($e_config['seller_phone']) || empty($e_config['seller_address']) || $e_config['is_open'] != 1) {
            throw new \think\Exception('商家电子发票参数缺失或未开启发票功能');
        }
        if (!$post_params['invoice_title'] || !$post_params['head_up_type'] || !$post_params['detail']) {
            throw new \think\Exception('必传参数缺失');
        }
        if ($post_params['head_up_type'] == 2) {
            if (empty($post_params['tax_num']))
                throw new \think\Exception('必传参数缺失');
        }
        $arr['identity'] = cfg('house_e_invoice');
        $orderService = new MallOrderService();
        $orderInfo = $orderService->getOne($post_params['order_id']);
        if (empty($orderInfo['phone']) || empty($orderInfo['order_no'])) {
            throw new \think\Exception('必传参数缺失');
        }
        $merService = new MerchantService();
        $mer_name = $merService->getByMerId($post_params['mer_id'])['name'];
        if (empty($mer_name)) {
            throw new \think\Exception('必传参数缺失');
        }
        //购方
        $order['buyername'] = $post_params['invoice_title'];
        $order['phone'] = $orderInfo['phone'];
        $order['order_no'] = $orderInfo['order_no'];
        $order['invoicedate'] = date('Y-m-d H:i:s', time());
        $order['clerk'] = $mer_name;
        $order['payee'] = $mer_name;
        $order['checker'] = $mer_name;
        if ($post_params['head_up_type'] == 2) {
            $order['taxnum'] = $post_params['tax_num']; //抬头为企业类型时税号必填
            $order['address'] = $post_params['user_address'];  //抬头为企业类型时企业地址必填
            $order['account'] = $post_params['account_number'];  //抬头为企业类型时企业账号必填
            $order['telephone'] = $post_params['user_phone'];
            $order['bankaccount'] = $post_params['deposit_bank'];
        }
        //销方
        $order['saletaxnum'] = $e_config['duty'];
        $order['saleaccount'] = $e_config['account_number'];
        $order['salephone'] = $e_config['seller_phone'];
        $order['saleaddres'] = $e_config['seller_address'];
        $order['kptype'] = 1;
        $goodsModel = new MallGoods();
        foreach ($post_params['detail'] as $k => $v) {
            //查单位
            $goods = $goodsModel->getOne($v['goods_id']);
            $order['detail'][$k]['unit'] = $goods ? $goods['unit'] : '';
            $order['detail'][$k]['goodsname'] = $v['name'];
            $order['detail'][$k]['hsbz'] = 0;
            $order['detail'][$k]['taxrate'] = 0.13;
            $order['detail'][$k]['spbm'] = 304080101;
            $order['detail'][$k]['fphxz'] = 0;
            $order['detail'][$k]['num'] = $v['num'];
            $order['detail'][$k]['price'] = $v['price'];
        }
        $arr['order'] = $order;
        $res = $this->sendInvoice($arr, cfg('house_e_invoice_url'));
        if ($res['status'] == '0000') {
            $post_params['fpqqlsh'] = $res['fpqqlsh'];
            unset($post_params['detail']);
            //如果设为默认的话，就将其他的默认解除
            if ($post_params['is_default'] === 1) {
                $data_default = ['is_default' => 0];
                $where_default = ['uid' => $post_params['uid']];
                $default_res = $this->mallERecordModel->updateOne($data_default, $where_default);
                if ($default_res === false) {
                    throw new \think\Exception('操作失败，请重试');
                }
            }
            $result = $this->mallERecordModel->addErecord($post_params);
            if ($result === false) {
                throw new \think\Exception('操作失败，请重试');
            } else {
                //更新订单表的is_invoice状态
                $where = ['order_id' => $post_params['order_id']];
                $data = ['is_invoice' => 1];
                $result_order = $orderService->updateMallOrder($where, $data);
                if ($result_order === false) {
                    throw new \think\Exception('操作失败，请重试');
                } else {
                    return true;
                }
            }
        } else {
            throw new \think\Exception($res['message']);
        }
    }

    /**
     * 调第三方开发票接口
     * @param $order
     * @param $url
     * @return mixed
     */
    public function sendInvoice($order, $url)
    {
        $des_security = new DesSecurityService();
        $order = $des_security->encrypt($order);
        $response = http_request($url, 'POST', ['order' => $order]);
        return json_decode($response[1], true);
    }

    /**
     * 展示发票信息
     * @param $post_params
     * @return array
     * @throws \think\Exception
     */
    public function showInvoice($post_params)
    {
        if (empty($post_params['uid'])) {
            throw new \think\Exception('uid参数缺失');
        }
        if (empty($post_params['order_id'])) {
            throw new \think\Exception('order_id参数缺失');
        }
        $where = ['uid' => $post_params['uid'], 'order_id' => $post_params['order_id']];
        $field = true;
        $list = $this->mallERecordModel->showInvoice($where, $field);
        if (!empty($list)) {
            $list['create_time'] = date('Y-m-d H:i:s', time());
            return $list;
        } else {
            return [];
        }
    }

    /**
     * @param $uid
     * 获取默认信息
     */
    public function getDefaultInfo($uid)
    {
        if (empty($uid)) {
            throw new \think\Exception('uid参数缺失');
        }
        $where = ['uid' => $uid, 'is_default' => 1];
        $arr = $this->mallERecordModel->showInvoice($where, true);
        return $arr;
    }
}
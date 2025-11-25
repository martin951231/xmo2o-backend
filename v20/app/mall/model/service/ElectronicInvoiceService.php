<?php


namespace app\mall\model\service;

use app\mall\model\db\HouseVillageERecord;
use app\mall\model\db\HouseEConfig;
use app\mall\model\db\HouseVillageDetailRecord;
use app\mall\model\service\ConfigService;

class ElectronicInvoiceService
{
    public $model = '';
    public $villageEModel = '';
    public $villageRecordModel = '';

    public function __construct()
    {
        $this->model = new HouseVillageERecord();
        $this->villageEModel = new HouseEConfig();
        $this->villageRecordModel = new HouseVillageDetailRecord();
    }

    /**
     * 获取小区电子发票配置
     * @author lijie
     * @date_time 2020/07/07
     * @param $village_id
     * @return array|mixed|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getEConfig($village_id)
    {
        $where['village_id'] = $village_id;
        $config = $this->villageEModel->getConfig($where);
        return $config;
    }

    /**
     * 获取用户最后一次开电子发票的信息
     * @author lijie
     *@date_time 2020/07/06
     * @param $user_id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLastRecord($pigcms_id)
    {
        $where['pigcms_id'] = $pigcms_id;
        $data = $this->model->getUserLastRecord($where,'id DESC');
        return $data;
    }

    /**
     * 调接口开个人/企业发票
     * @author lijie
     * @date_time 2020/07/07
     * @param $e_config
     * @param $user_info
     * @param $post
     * @return mixed
     */
    public function addInvoice($e_config,$user_info,$post)
    {
        $config = $this->getConfig();
        $arr['identity'] = $config['house_e_invoice'];
        if($post['head_up_type'] == 2){
            $order['address'] = $post['address'];
            $order['account'] = $post['account_number'];
        }
        $order['buyername'] = $post['name'];
        $order['taxnum'] = $post['taxnum'];
        $order['phone'] = $post['receive_tel'];
        $order['order_no'] = $post['order_no'];
        $order['invoicedate'] = date('Y-m-d H:i:s',time());
        $order['clerk'] = 'name';
        $order['saletaxnum'] = $e_config['duty_paragraph'];
        $order['kptype'] = 1;
        foreach ($post['detail'] as $k=>$v){
            $order['detail'][$k]['goodsname'] = $v['name'];
            $order['detail'][$k]['hsbz'] = 0;
            $order['detail'][$k]['taxrate'] = 0.13;
            $order['detail'][$k]['spbm'] = 304080101;
            $order['detail'][$k]['fphxz'] = 0;
            $order['detail'][$k]['num'] = 1;
            $order['detail'][$k]['price'] = $v['price'];
        }
        $arr['order'] = $order;
        $res = $this->sendInvoice($arr,$config['house_e_invoice_url']);
        return $res;
    }

        /**
        * 调第三方开发票接口
        * @author lijie
        * @date_time 2020/07/06
        * @param $order
         * @param $url
        * @return mixed
        */
        public function sendInvoice($order,$url)
        {
            $des_security = new DesSecurityService();
            $order = $des_security->encrypt($order);
            $response = http_request($url,'POST',['order'=>$order]);
            return json_decode($response[1],true);
        }

        public function addErecord($e_config,$user_info,$post,$head_up_type)
        {
            if($head_up_type == 1){
                //$post['taxnum'] = $user_info['id_card'];
            }
            $post['duty_paragraph'] = $e_config['duty_paragraph'];
            $post['create_time'] = time();
            $goods_detail = $post['detail'];
            unset($post['detail']);
            $post['property_type'] = '*';
            foreach ($goods_detail as $k=>$v){
                $post['property_type'] .= $v['name'].'*';
            }
            $post['property_type'] = rtrim($post['property_type'],'*');
            $id = $this->model->addErecord($post);
            foreach ($goods_detail as $k=>$v){
                $insertAll[$k]['order_id'] = $v['order_id'];
                $insertAll[$k]['property_type'] = $v['name'];
                $insertAll[$k]['price'] = $v['price'];
                $insertAll[$k]['e_record_id'] = $id;
                $insertAll[$k]['create_time'] = time();
            }
            $this->villageRecordModel->addEDetailRecord($insertAll);
        }

    /**
     * @根据pigcms_id 获取所有的电子发票开具记录
     * @author lijie
     * @date_time 2020/07/07
     * @param $pigcms_id
     * @return mixed
     */
        public function getAllERecord($pigcms_id)
        {
            $where['pigcms_id'] = $pigcms_id;
            $data = $this->model->getAllERecord($where);
            return $data;
        }

    /**
     * 根据流水号获取电子发票pdf
     * @author lijie
     * @date_time 2020/07/07
     * @param $fpqqlsh
     * @return array|\returns|string
     */
        public function getInvoice($fpqqlsh)
        {
            $config = $this->getConfig();
            $arr['identity'] = $config['house_e_invoice'];
            $order[] = $fpqqlsh;
            $arr['fpqqlsh'] = $order;
            $des_security = new DesSecurityService();
            $arr = $des_security->encrypt($arr);
            $url = $config['house_e_invoice_url2'];
            $response = http_request($url,'POST',['order'=>$arr]);
            return json_decode($response[1],true);
        }

    /**
     * 获取历史开票记录详情
     * @author lijie
     * @date_time 2020/07/10
     * @param $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
        public function detailInvoice($id)
        {
            $where['id'] = $id;
            $res = $this->model->detailInvoice($where);
            return $res;
        }

        public function getConfig()
        {
            $e_config = new ConfigService();
            $where['tab_id'] = 'e_invoice';
            $config = $e_config->get_config_list($where,'name,value');
            return $config;
        }
}
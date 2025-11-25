<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillageERecord;
use app\community\model\db\HouseEConfig;
use app\community\model\db\HouseVillageDetailRecord;
use app\community\model\service\ConfigService;

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
     * @param $village_id
     * @return mixed
     */
    public function addInvoice($e_config,$user_info,$post,$village_id)
    {
        $config = $this->getConfig($village_id);
        $arr['identity'] = $config['identity'];
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
            $village_id=$user_info['village_id'];
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
                $insertAll[$k]['village_id'] = $village_id;
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
     * @param $village_id
     * @return array|\returns|string
     */
        public function getInvoice($fpqqlsh,$village_id)
        {
            if (empty($fpqqlsh)) {
                throw new \think\Exception('缺少流水号参数');
            }
            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/v20/runtime/pdf/';
            if (file_exists($file_path . iconv('UTF-8', 'GBK', $fpqqlsh) . "." . 'pdf')) {
                return cfg('site_url') . '/v20/runtime/pdf/' . iconv('UTF-8', 'GBK', $fpqqlsh) . "." . 'pdf';
            }
            $config = $this->getConfig($village_id);
            $arr['identity'] = $config['identity'];
            $order[] = $fpqqlsh;
            $arr['fpqqlsh'] = $order;
            $des_security = new DesSecurityService();
            $arr = $des_security->encrypt($arr);
            $url = $config['house_e_pdf_url'];
            $response = http_request($url,'POST',['order'=>$arr]);
            $res = $this->downImgRar(json_decode($response[1], true)['list'][0]['c_url'], $fpqqlsh, 'pdf');
            return cfg('site_url').$res;
        }

    /**
     * @param $url
     * @param $rename
     * @param $ext
     * @return string
     */
    public function downImgRar($url, $rename, $ext)
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

        public function getConfig($village_id)
        {
            $service_house_village = new HouseVillageService();
            $village_info = $service_house_village->getHouseVillageInfo(['village_id'=>$village_id],'property_id');
            $service_house_property = new HousePropertyService();
            $config = $service_house_property->getFind(['id'=>$village_info['property_id']]);
            return $config;
        }
}
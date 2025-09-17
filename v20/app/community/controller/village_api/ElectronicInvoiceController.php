<?php

namespace app\community\controller\village_api;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\ElectronicInvoiceService;
use app\community\model\service\HouseVillageUserService;

class ElectronicInvoiceController extends BaseController
{
    /**
     * 获取用户最后一次开电子发票的信息
     * @author lijie
     *@date_time 2020/07/06
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInvoiceInfo()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(empty($pigcms_id))
            return api_output_error(1002,'用户权限错误');
        $e_incoice = new ElectronicInvoiceService();
        $data = $e_incoice->getUserLastRecord($pigcms_id);
        if(empty($data))
            $data = array();
        $list['data'] = $data;
        $list['type'] = ['个人','企业'];
        return api_output(0,$list,'获取成功');
    }

    /**
     * 用户申请开电子发票
     * @author lijie
     * @date_time 2020/07/07
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addInvoice()
    {
        $user_id = $this->request->log_uid;
        /*if(empty($user_id))
            return api_output_error(1002,'用户权限错误');*/
        $post_params = $this->request->post();
        unset($post_params['Device-Id']);
        unset($post_params['app_type']);
        unset($post_params['app_version']);
        unset($post_params['networkType']);
        unset($post_params['now_city']);
        unset($post_params['now_lang']);unset($post_params['ticket']);unset($post_params['wxapp_type']);unset($post_params['v20_ticket']);
       if(!isset($post_params['pigcms_id']))
           return api_output_error(1001,'必传参数缺失');
       $house_village_user = new HouseVillageUserService();
       $user_info = $house_village_user->getHouseUserBindWhere(['pigcms_id'=>$post_params['pigcms_id']],'id_card,village_id,name');
       if(empty($user_info['village_id']))
           return api_output_error(1001,'数据获取不正常');
       $ele_config = new ElectronicInvoiceService();
       $config = $ele_config->getEConfig($user_info['village_id']);
       if(empty($config))
           return api_output_error(1001,'数据获取不正常');
        $head_up_type = isset($post_params['head_up_type'])?$post_params['head_up_type']:1;
        $post_params['order_no'] = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        if(!$post_params['name'] || !$post_params['money'] || !$post_params['receive_tel'] || !$post_params['detail'] || !$post_params['head_up_type'])
            return api_output_error(1001,'必传参数缺失');
        if($head_up_type == 2){
            if(empty($post_params['taxnum']) || !$post_params['address'] || !$post_params['account_number'])
                return api_output_error(1001,'必传参数缺失');
        }
        $e_incoice = new ElectronicInvoiceService();
        $res = $e_incoice->addInvoice($config,$user_info,$post_params,$user_info['village_id']);
        if($res['status'] == '0000'){
            $post_params['fpqqlsh'] = $res['fpqqlsh'];
            $e_incoice->addErecord($config,$user_info,$post_params,$head_up_type);
            return api_output(0,'','提交成功，请注意短信查收');
        }else{
            return api_output_error(1003,$res['message']);
        }
    }

    /**
     * @根据pigcms_id 获取所有的电子发票开具记录
     * @author lijie
     * @date_time 2020/07/07
     * @return \json
     */
    public function getAllERecord()
    {
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$pigcms_id)
            return api_output_error(1001,'必传参数缺失');
        $e_incoice = new ElectronicInvoiceService();
        $data = $e_incoice->getAllERecord($pigcms_id);
        return api_output(0,$data,'获取成功');
    }

    /**
     * 根据流水号获取电子发票pdf
     * @author lijie
     * @date_time 2020/07/07
     * @return \json
     * @throws \think\Exception
     */
    public function getInvoice()
    {
        $fpqqlsh = $this->request->post('fpqqlsh',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$fpqqlsh || !$pigcms_id)
            return api_output_error(1001,'必传参数缺失');
        $house_village_user = new HouseVillageUserService();
        $user_info = $house_village_user->getHouseUserBindWhere(['pigcms_id'=>$pigcms_id],'village_id');
        $e_incoice = new ElectronicInvoiceService();
        $res = $e_incoice->getInvoice($fpqqlsh,$user_info['village_id']);
        if($res)
            return api_output(0,$res,'获取成功');
    }

    /**
     * 获取历史开票记录详情
     * @author lijie
     * @date_time 2020/07/10
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detailInvoice()
    {
        $id = $this->request->param('id',0);
        if(!$id)
            return api_output_error(1001,'必传参数缺失');
        $e_incoice = new ElectronicInvoiceService();
        $res = $e_incoice->detailInvoice($id);
        if($res)
            return api_output(0,$res,'获取成功');
    }
}
<?php
namespace app\merchant\model\service\yibao;

use app\common\model\db\Area;
use app\common\model\service\UploadFileService;
use app\merchant\model\db\AdapayBank;
use app\merchant\model\db\MerchantYibaoInfo;
use mallbook\Mallbook;
use mallbook\MallbookApi;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MerchantYibaoService
{
    /**
     * 获取银行编号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBankCode(){
        $rs = (new AdapayBank())->order('sort DESC')->field('bank_name AS text,bank_code AS value')->select()->toArray();
        return $rs;
    }

    public function getBankNameByCode($code)
    {
        $bankCodes = $this->getBankCode();
        $bankIndex = array_column($bankCodes, 'text', 'value');
        return $bankIndex[$code] ?? '';
    }

    /**
     * 获取区域列表
     */
    public function getAreaList(){
        $area_list = (new Area())->field('`area_id`,`area_pid`,`area_name`,`huifu_code`')->where([['is_open','=',1],['area_type','<',4]])->order('area_type ASC')->select()->toArray();
        $tmp_area_list = array();
        foreach($area_list as $key=>$value){
            $tmp_area_list[$value['area_pid']][] = $value;
        }
        $new_area_list = $tmp_area_list[0];

        foreach($new_area_list as $key=>$value){
            if(empty($tmp_area_list[$value['area_id']])){
                unset($new_area_list[$key]);
            }else{
                $new_area_list[$key]['children'] = $tmp_area_list[$value['area_id']];
                $tmp_new_area_list[] = array('children'=>$tmp_area_list[$value['area_id']],'huifu_code'=>$value['huifu_code'],'area_id'=>$value['area_id'],'area_name'=>$value['area_name']);
            }
        }

        
        
        foreach($tmp_new_area_list as $key=>&$value){
            $value['value'] = $value['area_id'];
            $value['text'] = $value['area_name'];
            unset($value['area_id'],$value['area_name'],$value['area_pid']);
            foreach($value['children'] as $k=>&$v){
                $v['value'] = $v['area_id'];
                $v['text'] = $v['area_name'];
                unset($v['area_id'],$v['area_name'],$v['area_pid']);
                
            }
        }

        return $tmp_new_area_list;
    }

    /**
     * 商家入网
     * @param $params
     * @throws \think\Exception
     */
    public function applyYibao($params){
        $data = $params;
        fdump_api($data,'mallBook/ruwang',1);
        //入网接口请求信息
        $yibao_data = [
            'merUserId' => $data['mer_id'],
            'phone' => $data['phone'],
            'userName' => $data['userName'],
            'userType' => $data['userType'],
            'provCode' => $data['provCode'],
            'areaCode' => $data['areaCode'],
            'socialCreditCode' => $data['socialCreditCode'],
            'socialCreditCodeExpires' => $data['socialCreditCodeExpires']?date('Ymd',strtotime($data['socialCreditCodeExpires'])):'',
            'businessScope' => $data['businessScope'],
            'legalPerson' => $data['legalPerson'],
            'legalPhone' => $data['legalPhone'],
            'certId' => $data['certId'],
            'certIdExpires' => $data['certIdExpires']?date('Ymd',strtotime($data['certIdExpires'])):'',
            'address' => $data['address'],
            'file_no' => $data['fileNo']?:'',
            'notifyUrl' => cfg('site_url').'/v20/public/index.php/merchant/api.MerchantYibao/notify',
        ];
        fdump_api($yibao_data,'mallBook/ruwang',1);
        
        //保存到本地信息
        $save_data = [
            'mer_id' => $data['mer_id'],
            'phone' => $data['phone'],
            'user_name' => $data['userName'],
            'user_type' => $data['userType'],
            'prov_code' => $data['provCode'],
            'area_code' => $data['areaCode'],
            'social_credit_code' => $data['socialCreditCode'],
            'social_credit_code_expires' => $data['socialCreditCodeExpires']?strtotime($data['socialCreditCodeExpires']):'',
            'business_scope' => $data['businessScope'],
            'legal_person' => $data['legalPerson'],
            'legal_phone' => $data['legalPhone'],
            'legal_cert_id' => $data['certId'],
            'legal_cert_id_expires' => $data['certIdExpires']?strtotime($data['certIdExpires']):'',
            'address' => $data['address'],
            'apply_time' => time(),
            'audit_state' => 0,
            'audit_desc' => '待审核',
            'file_no' => $data['fileNo']?:'',
            'file_url' => isset($data['fileUrl'])&&$data['fileUrl']?implode(';',$data['fileUrl']):'',
        ];
        fdump_api($save_data,'mallBook/ruwang',1);
        try {
            $res_regis = (new MallbookApi())->register('register'.time().$data['mer_id'],$yibao_data);
            fdump_api($res_regis,'mallBook/ruwang',1);
            if(!$res_regis){
                throw new \think\Exception(L_('信息有误！'));
            }
            if($res_regis['code']!=='0000'){
                throw new \think\Exception(L_($res_regis['msg']));
            }
            $res_regis_result = json_decode($res_regis['result'],true);
            fdump_api($res_regis_result,'mallBook/ruwang',1);
            $merchant_info = (new MerchantYibaoInfo())->where(['mer_id'=>$data['mer_id']])->findOrEmpty()->toArray();
            if($merchant_info){
                $res = (new MerchantYibaoInfo())->where(['mer_id'=>$data['mer_id']])->save($save_data);
                $save_data['user_id'] = $merchant_info['user_id']??0;
            }else{
                $save_data['user_id'] = $res_regis_result['userId']??0;
                $res = (new MerchantYibaoInfo())->save($save_data);
            }
            if($res||$res!==false){
                return true;
            }else{
                throw new \think\Exception(L_('操作失败！'));
            }
            return true;
        }catch (\Exception $e){
            throw new \think\Exception($e->getMessage());
        }
    }

    /**
     * 绑定银行卡
     * @param $params
     * @throws \think\Exception
     */
    public function bindBank($params){
        $data = $params;
        //绑定银行卡信息
        $bind_bank_data = [
            'userId' => '',
            'cardName' => $data['cardName'],
            'cardNo' => $data['cardNo'],
            'bankAcctType' => $data['bankAcctType'],
            'phone' => $data['bankPhone'],
            'bankName' => $data['bankName'],
            'bankCode' => $data['bankCode'],
            'provCode' => $data['bankProvCode'],
            'areaCode' => $data['bankAreaCode'],
            'certId' => '',
        ];
        fdump_api($bind_bank_data,'mallBook/ruwang',1);
        $merchant_info = (new MerchantYibaoInfo())->where(['mer_id'=>$data['mer_id']])->findOrEmpty()->toArray();
        if(!$merchant_info){
            throw new \think\Exception(L_('该商家未入网，不能绑定银行卡！'));
        }
        if($merchant_info['audit_state']!=1){
            throw new \think\Exception(L_('未入网成功，不能绑定银行卡！'));
        }
        $bind_bank_data['certId'] = $merchant_info['legal_cert_id'];
        $bind_bank_data['userId'] = $merchant_info['user_id'];
        $res_bind_bank = (new MallbookApi())->bindAccount('bank'.time().$data['mer_id'],$bind_bank_data);
        fdump_api($res_bind_bank,'mallBook/ruwang',1);
        if(!$res_bind_bank){
            throw new \think\Exception(L_('信息有误！'));
        }
        if($res_bind_bank['code']!='0000'){
            throw new \think\Exception(L_($res_bind_bank['msg']));
        }
        $res_bind_bank_result = json_decode($res_bind_bank['result'],true);
        fdump_api($res_bind_bank_result,'mallBook/ruwang',1);
        if($res_bind_bank_result['status']!=1){
            throw new \think\Exception(L_($res_bind_bank_result['errMsg']));
        }
        $bank_data = [
            'bank_prov_code' => $data['bankProvCode'],
            'bank_area_code' => $data['bankAreaCode'],
            'bank_phone' => $data['bankPhone'],
            'bank_code' => $data['bankCode'],
            'bank_name' => $data['bankName'],
            'bank_acct_type' => $data['bankAcctType'],
            'card_no' => $data['cardNo'],
            'card_name' => $data['cardName'],
            'update_time' => time(),
        ];
        $res = (new MerchantYibaoInfo())->where(['mer_id'=>$data['mer_id']])->save($bank_data);
        if($res||$res!==false){
            return true;
        }else{
            throw new \think\Exception(L_("绑定失败！"));
        }
    }

    /**
     * 入网详情
     * @param $params
     * @return mixed
     */
    public function getYibaoInfo($params)
    {
        $merid = $params['mer_id'];
        if(!$merid){
            throw new \think\Exception(L_('商家不存在！'));
        }
        $yibaoInfo = (new MerchantYibaoInfo())->where(['mer_id'=>$merid])->findOrEmpty()->toArray();
        $data = null;
        if($yibaoInfo){
            if ($yibaoInfo['prov_code']) {
                $yibaoInfo['prov_id'] = (new Area())->where(['huifu_code' => $yibaoInfo['prov_code']])->value('area_id');
            }
            if ($yibaoInfo['area_code']) {
                $area_info = (new Area())->field('area_id,area_pid')->where(['huifu_code' => $yibaoInfo['area_code']])->findOrEmpty()->toArray();
                $yibaoInfo['city_id'] = $area_info?$area_info['area_id']:0;
            }
            /*if(!empty($area_info)){
                $yibaoInfo['city_id'] = (new Area())->where(['area_id' => $area_info['area_pid']])->value('area_id');
            }*/
            if ($yibaoInfo['bank_prov_code']) {
                $yibaoInfo['bank_prov_id'] = (new Area())->where(['huifu_code' => $yibaoInfo['bank_prov_code']])->value('area_id');
            }
            if ($yibaoInfo['bank_area_code']) {
                $brank_area_info = (new Area())->field('area_id,area_pid')->where(['huifu_code' => $yibaoInfo['bank_area_code']])->findOrEmpty()->toArray();
                $yibaoInfo['bank_city_id'] = $brank_area_info?$brank_area_info['area_id']:0;
            }
            /*if(!empty($brank_area_info)){
                $yibaoInfo['bank_city_id'] = (new Area())->where(['area_id' => $brank_area_info['area_pid']])->value('area_id');
            }*/
            $file_pic = [];
            if($yibaoInfo['file_url']){
                $file_arr = explode(';',$yibaoInfo['file_url']);
                foreach ($file_arr as $item){
                    $file_pic[] = replace_file_domain($item);
                }
            }
            $data = [
                'id' => $yibaoInfo['id'],
                'mer_id' => $yibaoInfo['mer_id'],
                'phone' => $yibaoInfo['phone'],
                'userName' => $yibaoInfo['user_name'],
                'userType' => $yibaoInfo['user_type'],
                'provCode' => $yibaoInfo['prov_code'],
                'areaCode' => $yibaoInfo['area_code'],
                'prov_id' => $yibaoInfo['prov_id']??0,
                'area_id' => $yibaoInfo['area_id']??0,
                'city_id' => $yibaoInfo['city_id']??0,
                'socialCreditCode' => $yibaoInfo['social_credit_code'],
                'socialCreditCodeExpires' => $yibaoInfo['social_credit_code_expires']?date('Y/m/d',$yibaoInfo['social_credit_code_expires']):'',
                'businessScope' => $yibaoInfo['business_scope'],
                'legalPerson' => $yibaoInfo['legal_person'],
                'legalPhone' => $yibaoInfo['legal_phone'],
                'certId' => $yibaoInfo['legal_cert_id'],
                'certIdExpires' => $yibaoInfo['legal_cert_id_expires']?date('Y/m/d',$yibaoInfo['legal_cert_id_expires']):'',
                'address' => $yibaoInfo['address'],
                'bankPhone' => $yibaoInfo['bank_phone'],
                'bankCode' => $yibaoInfo['bank_code'],
                'bankName' => $yibaoInfo['bank_name'],
                'bankAcctType' => $yibaoInfo['bank_acct_type'],
                'cardNo' => $yibaoInfo['card_no'],
                'cardName' => $yibaoInfo['card_name'],
                'apply_time' => $yibaoInfo['apply_time']?date('Y/m/d',$yibaoInfo['apply_time']):'',
                'audit_state' => $yibaoInfo['audit_state'],
                'audit_desc' => $yibaoInfo['audit_desc'],
                'audit_time' => $yibaoInfo['audit_time']?date('Y/m/d',$yibaoInfo['audit_time']):'',
                'bankProvCode' => $yibaoInfo['bank_prov_code'],
                'bankAreaCode' => $yibaoInfo['bank_area_code'],
                'bank_prov_id' => $yibaoInfo['bank_prov_id']??0,
                'bank_area_id' => $yibaoInfo['bank_area_id']??0,
                'bank_city_id' => $yibaoInfo['bank_city_id']??0,
                'fileNo' => $yibaoInfo['file_no']?:'',
                'fileUrl' => $file_pic,
            ];
        }
        return $data;
    }

    /**
     * 上传图片
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function uploadFile($params){
        $mer_id = $params['mer_id'];
        $file = $params['file'];
        if(empty($params['file'])||!is_array($file)){
            throw new \think\Exception(L_('请上传文件！'));
        }
        $data['file'] = [];
        $data['zipPath'] = $_SERVER['DOCUMENT_ROOT'].'/runtime';
        $data['uploadPath'] = $_SERVER['DOCUMENT_ROOT'].'/runtime';
        $data['mer_id'] = $mer_id;
        $return_file = [];
        $i = 1;
        foreach ($file as $item){
            if(!empty($item)){
                $savepath = (new UploadFileService())->uploadPictures($item, '/yibao/'.date('Ymd'),0,0,'',0,$i.time().$mer_id);
                $data['file'][] = $_SERVER['DOCUMENT_ROOT'].$savepath;
                $return_file[] = $savepath;
                $i++;
            }
        }
        if(!$data['file']){
            throw new \think\Exception(L_('请上传文件！'));
        }
        $res = (new MallbookApi())->fileUpload('upload'.time().$mer_id,$data);
        if(!$res){
            throw new \think\Exception(L_('文件上传失败！'));
        }
        if($res['code']!='0000'){
            throw new \think\Exception(L_($res['msg']));
        }
        $res_bind_bank_result = json_decode($res['result'],true);
        return ['fileNo'=>$res_bind_bank_result['fileNo'],'fileUrl'=>$return_file];
    }
    
    public static function getMallbookMerUid($merId)
    {
       return MerchantYibaoInfo::where($merId)->where('audit_state', 1)->value('user_id');
    }
    
    public function updataBankcode(){
        $param['file_url'] = '/runtime/list.xlsx';
        $data = $this->importGoodsArea($param);
        if($data){
            (new AdapayBank())->saveAll($data);
        }
        return true;
    }

    public function importGoodsArea($param){
        $file_url = $param['file_url']?:'';
        if(!$file_url){
            throw new \think\Exception(L_('请导入文件！'));
        }
        if(file_exists($_SERVER['DOCUMENT_ROOT'].$file_url)){
            $file_url = $_SERVER['DOCUMENT_ROOT'].$file_url;
        }else{
            $file_url = replace_file_domain($file_url);
        }
        $file_arr=explode('/',$file_url);
        $count=count($file_arr);
        $file_type = '';
        if ($count>1){
            $file_data=$file_arr[$count-1];
            $file_arr = explode('.',$file_data);
            if(count($file_arr)==2)
            {
                $file_type = $file_arr[1];
            }
        }
        if (!$file_type){
            throw new \think\Exception(L_('文件上传错误，导入失败！'));
        }
        if ($file_type!='xlsx'){
            throw new \think\Exception(L_('文件格式错误，请上传xlsx格式文件！'));
        }

        $filed = [
            'A' => 'bank_name',
            'B' => 'bank_code',
        ];
        if($file_type=='xlsx'){
            $read = IOFactory::createReader('Xlsx');
        }elseif($file_type=='xls'){
            $read = IOFactory::createReader('Xls');
        }

        $PHPExcel = $read->load($file_url);
        $sheet = $PHPExcel->getSheet(0); //0-页面展示银行列表，1-全部
        $highestRow = $sheet->getHighestDataRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) //行号从1开始
        {
            for ($column = 'A'; $column <= $highestColumm; $column++) //列数是以A列开始
            {
                $data[$row][$filed[$column]] = $sheet->getCell($column . $row)->getValue();
            }
        }
        if(!$data){
            throw new \think\Exception(L_('表格内容格式错误！'));
        }
        return $data;
    }

    /**
     * 异步回调审核结果
     */
    public function notify($params){
        /*if (!is_array($params)) { //同步返回json，异步返回form表单
            $params = json_decode($params,true);
        }*/
        $sign = $params['sign'];
        unset($params['sign']);
        $params['result'] = htmlspecialchars_decode(html_entity_decode($params['result']));
        if ((new Mallbook())->verify($params,$sign)) {
            if($params['code']=='0000'){
                $result = $params['result']?json_decode($params['result'],true):[];
                fdump_api($result,'yibao/notify',1);
                if($result){
                    $res = (new MerchantYibaoInfo())->where(['mer_id'=>$result['merUserId']])->save(['user_id'=>$result['userId'],'audit_state'=>$result['status'],'audit_time'=>time(),'audit_desc'=>($result['status']==1?'审核成功':'审核失败,原因：'.$result["errMsg"])]);
                    fdump_api($res,'yibao/notify',1);
                }
            }
        }else{
            fdump_api([$params,'返回值验签失败'],'yibao/notify',1);
        }
    }
}
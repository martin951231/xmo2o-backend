<?php


namespace app\community\controller\manage_api\v1;

use app\community\controller\manage_api\BaseController;
use app\community\model\service\ContentEngineService;
use app\community\model\service\EnterpriseWeChatService;
use app\community\model\service\HouseVillageService;

class ContentEngineController extends BaseController
{
    /**
     * Notes:获取内容
     * @datetime: 2021/3/23 11:08
     * @return \json
     */
    public function getContentList()
    {
//        $village_id = $this->request->param('village_id','','int');
//        $property_id = $this->request->param('property_id','','int');
        $from_id = $this->request->param('from_id','','int');
        $from_type = $this->request->param('from_type','','int');
        $page = $this->request->param('page','1','int');
        $title = $this->request->param('title','','trim');
        $type = $this->request->param('type','','int');
        $gid = $this->request->param('gid','0','int');
        $limit = 10;
        if(!in_array($from_type,[1,2]) && !$from_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $sContentEngineService = new ContentEngineService();
        $data =[
            'from_id'=>$from_id,
            'from_type'=>$from_type,
            'title'=>$title,
            'type'=>$type,
            'gid'=>$gid,
        ];
        try {
            $list = $sContentEngineService->getGroupContent($data,$page, $limit);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes:获取分组
     * @datetime: 2021/3/23 11:08
     * @return \json
     */
    public function getSelectMenuList()
    {
//        $village_id = $this->request->param('village_id','','int');
//        $property_id = $this->request->param('property_id','','int');
        $from_id = $this->request->param('from_id','','int');
        $from_type = $this->request->param('from_type','','int');
        if(!in_array($from_type,[1,2]) || !$from_id){
            return api_output_error(1001,'必传参数（from_id || from_type）缺失');
        }
        $sContentEngineService = new ContentEngineService();
        try {
            $list = $sContentEngineService->getGroupList($from_type,$from_id);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes:获取头部type
     * @datetime: 2021/3/23 11:09
     * @return \json
     */
    public function getHeadType()
    {
        $sContentEngineService = new ContentEngineService();
        try {
            $list = $sContentEngineService->getHeadType();
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$list);
    }
    /**
     * Notes:获取企业微信配置参数
     * @datetime: 2021/3/25 18:01
     * @return \json
     */
    public function getQyWxConfig(){
        $from_id = $this->request->param('from_id','','int');
        $from_type = $this->request->param('from_type','','int');
        $wx_api_type = $this->request->param('wx_api_type','','int');
        $timestamp = $this->request->param('timestamp','');
        $nonceStr = $this->request->param('nonceStr','');
        if(!$from_id && !$from_type){
            return api_output_error(1001,'必传参数缺失');
        }
        if(!in_array($wx_api_type,[1,2])){
            return api_output_error(1001,'必传参数异常');
        }
        $sEnterpriseWeChatService = new EnterpriseWeChatService();
		fdump([$from_id, $from_type, $wx_api_type, $timestamp, $nonceStr], 'getQyWxConfig', true);
        try{
            $data = $sEnterpriseWeChatService->getWxConfig($from_id,$from_type,$wx_api_type,$timestamp,$nonceStr);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes:登录
     * @datetime: 2021/3/25 18:12
     * @return \json
     */
    public function qyWxLoginApi()
    {
        $from_id = $this->request->param('from_id','','int');
        $from_type = $this->request->param('from_type','','int');
        $code = $this->request->param('code','','trim');
        if(!$from_id && !$from_type){
            return api_output_error(1001,'必传参数缺失');
        }
        $sEnterpriseWeChatService = new EnterpriseWeChatService();
        try{
            $data = $sEnterpriseWeChatService->qyloginApi($from_id,$from_type,$code);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$data);
    }
    /**
     * Notes:企业微信登录（直接登录页面）
     * @datetime: 2021/3/23 11:09
     * @return \json
     */
    public function qyWxLogin()
    {
        $from_id = $this->request->param('from_id','','int');
        $from_type = $this->request->param('from_type','','int');
        if(!$from_id && !$from_type){
            return api_output_error(1001,'必传参数缺失');
        }
        $sEnterpriseWeChatService = new EnterpriseWeChatService();
        try{
            $sEnterpriseWeChatService->getUserCode($from_id,$from_type);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }

    }

    /**
     * Notes:获取人员信息
     * @datetime: 2021/3/23 11:09
     * @return \json
     */
    public function getUserData()
    {
        $code = $this->request->param('code','','trim');
        $property_id = $this->request->param('property_id','','int');
        $from_type = $this->request->param('from_type','','int');
        $sEnterpriseWeChatService = new EnterpriseWeChatService();
        try{
            $userInfo = $sEnterpriseWeChatService->getUserInfo($code,$property_id,$from_type);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
    }

    /**
     * Notes:发送之前准备
     * @datetime: 2021/3/23 11:09
     * @return \json
     */
    public function sendSetOut()
    {
        $id = $this->request->param('id','','int');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $sContentEngineService = new ContentEngineService();
        try{
            $matterInfo = $sContentEngineService->uploadMatter($id);
        }catch (\Exception $e){
            return api_output_error(1001,$e->getMessage());
        }
        if($matterInfo){
            return api_output(0,$matterInfo);
        }else{
            return api_output(0,[]);
        }
    }
}
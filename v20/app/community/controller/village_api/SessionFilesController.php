<?php


namespace app\community\controller\village_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\EnterpriseWeChatService;
use app\community\model\service\SessionFileService;
class SessionFilesController extends CommunityBaseController
{
    /**
     * Notes:会话配置
     * @datetime: 2021/4/1 15:54
     */
    public function conversationSet()
    {
        $property_id =  $this->adminUser['property_id'];
        $secret= $this->request->param('secret','','trim');
        $version_number= $this->request->param('version_number','','trim');
        $is_checked= $this->request->param('is_checked','','trim');
        $id= $this->request->param('id','','int');
        $audit_id= $this->request->param('audit_id','','int');
        if(!$secret || !$version_number){
            return api_output_error(1001,'必传参数错误');
        }
        $serviceSessionFile = new SessionFileService();
        try{
            $res = $serviceSessionFile->setConversation($property_id,$secret,$version_number,$id,$audit_id,$is_checked);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }
    public function getConversationSet()
    {

        $property_id =  $this->adminUser['property_id'];
        $serviceSessionFile = new SessionFileService();
        try{
            $data = $serviceSessionFile->getSetConversation($property_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    /**
     * Notes:获取 已开通会话存档功能的企业成员
     * @datetime: 2021/3/31 19:38
     */
    public function getMemberList()
    {
        $property_id =  $this->adminUser['property_id'];
        $serviceEnterpriseWeChat = new EnterpriseWeChatService();
        try {
            $list = $serviceEnterpriseWeChat->getPermitUserList($property_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }
    
    //获取聊天对应的人
    public function getChatMember()
    {
        $type = $this->request->param('type','0','int');
        $name = $this->request->param('name','','trim');
        $work_id = $this->request->param('work_id','','trim');//工作人员id
        $property_id =  $this->adminUser['property_id'];
        if(!in_array($type,[0,1,2,3])){
            return api_output_error(1001,'必传参数错误');
        }
        $serviceSessionFile = new SessionFileService();
        $param = [
            'type'=>$type,
            'name'=>$name,
            'work_id'=>$work_id,
            'property_id'=>$property_id,
        ];
        try{
            $list = $serviceSessionFile->getChatMemberList($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    //获取聊天消息
    public function getChatMsg()
    {
        $property_id =  $this->adminUser['property_id'];
        $from_id = $this->request->param('from_id','','trim');//发送者id
        $to_id = $this->request->param('to_id','','trim');//接受者 id
        $msg_type = $this->request->param('msg_type','','trim');
        $type = $this->request->param('type','0','int');//0员工 1客户 2员工群 3客户群
        $page = $this->request->param('page',1,'int');
        $search_name = $this->request->param('search_name','','trim');
        $start_date = $this->request->param('start_date');
        $end_date = $this->request->param('end_date');
        $chat_id = $this->request->param('chat_id','','int');
        $chat_from_id = $this->request->param('chat_from_id','','int');
        if($type == 0 || $type == 1)
        {
            if (!$from_id || !$to_id) {
                return api_output_error(1001, '必传参数错误');
            }
        }
        if(!in_array($type,[0,1,2,3])){
            return api_output_error(1001,'必传参数错误');
        }
        if(($type == 2 || $type == 3) && !$chat_id){
            return api_output_error(1001,'必传参数错误');
        }
        if($start_date) {
            $start_date = strtotime($start_date)*1000;
        }
        if($end_date) {
            $end_date = strtotime($end_date.' 23:59:59')*1000;
        }
        $param['from_id'] = $from_id;
        $param['to_id'] = $to_id;
        $param['msg_type'] = $msg_type;
        $param['property_id'] = $property_id;
        $param['chat_id'] = $chat_id;
        $param['type'] = $type;
        $param['chat_from_id'] = $chat_from_id;
        if($search_name){
            $param['search_name'] = $search_name;
        }
        if($start_date){
            $param['start_date'] = $start_date;
        }
        if($end_date){
            $param['end_date'] = $end_date;
        }
        $limit = 20;
        $serviceSessionFile = new SessionFileService();
        try{
            $list = $serviceSessionFile->getMsgList($param,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
    public function getChatGroupInfo()
    {
        $property_id =  $this->adminUser['property_id'];
        $chat_id = $this->request->param('chat_id','','trim');
        $type = $this->request->param('type','','int');
        $name = $this->request->param('name','','trim');
        $page = $this->request->param('page',0,'int');
        $page_size = $this->request->param('page_size','20','int');
        if(!$chat_id ){ //|| !in_array($type,[2,3])
            return api_output_error(1001,'必传参数错误');
        }
        $param['chat_id'] = $chat_id;
        $param['type'] = $type;
        $param['name'] = $name;
        $serviceSessionFile = new SessionFileService();
        try{
            $list = $serviceSessionFile->getGroupList($param,$page,$page_size);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }
}
<?php
namespace app\community\controller\village_api;
use app\community\controller\CommunityBaseController;
use app\community\model\db\HouseVillage;
use app\community\model\service\ContentEngineService;
use app\community\model\service\EnterpriseWeChatService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\QywxService;
use file_handle\FileHandle;
use think\Exception;
class ContentEngineController extends CommunityBaseController
{
    /**
     * Notes:内容引擎分组
     * @return \json
     * @author: weili
     * @datetime: 2021/3/11 16:50
     */
    public function engineMenuList()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $gid = $this->request->param('gid',0,'int');
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
        } else {
            $type = 1;//小区
        }
        $serviceContentEngine = new ContentEngineService();
        if(!$village_id && !$property_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = $serviceContentEngine->getGroupMenu($type,$property_id,$village_id,$gid);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes: 添加编辑分组
     * @datetime: 2021/3/11 18:06
     * @return \json
     */
    public function subEngineGroup()
    {
        $village_id = $this->adminUser['village_id'];
        $name = $this->request->param('name','','trim');
        $id = $this->request->param('id','','int');
        $pid = $this->request->param('pid','0','int');
        if(!$name){
            return api_output_error(1001,'请输入分组名称');
        }
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        $serviceContentEngine = new ContentEngineService();
        $data = [
            'name'=>$name,
            'pid'=>$pid,
            'type'=>$type,
        ];
        try{
            $res = $serviceContentEngine->subGroup($id,$data,$param_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes:获取分组详情
     * @datetime: 2021/3/11 18:06
     * @return \json
     */
    public function getGroupInfo()
    {
        $id = $this->request->param('id','','int');
        if(!$id){
            return api_output_error(1001,'请输入分组名称');
        }
        $serviceContentEngine = new ContentEngineService();
        try{
            $data = $serviceContentEngine->getGroupInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes:删除分组
     * @datetime: 2021/3/11 18:07
     * @return \json
     */
    public function delGroup()
    {
        $id = $this->request->param('id','','int');
        if(!$id){
            return api_output_error(1001,'必传参数异常');
        }
        $serviceContentEngine = new ContentEngineService();
        $info = $serviceContentEngine->getGroupInfo($id);
        if (empty($info) || empty($info['info']) ) {
            return api_output_error(1001,'当前删除对象不存在或者已经被删除');
        }
        $info = $info['info'];
        $login_role = $this->login_role;
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        if (in_array($login_role,$this->propertyRole)) {
            //物业
            if ($property_id!=$info['property_id']) {
                return api_output_error(1001,'您没有权限删除当前对象');
            }
        } else {
            //小区
            if ($village_id!=$info['village_id']) {
                return api_output_error(1001,'您没有权限删除当前对象');
            }
        }
        try{
            $data = $serviceContentEngine->delGroup($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }
    //-------------------------内容引擎 内容-----------------------------

    /**
     * Notes:获取内容引擎列表
     * @return \json
     */
    public function getContentList()
    {
        $gid = $this->request->param('gid','','int');
        $type = $this->request->param('type','','int');
        $title = $this->request->param('title','','trim');
        $page = $this->request->param('page','1','int');
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $serviceContentEngine = new ContentEngineService();
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $from_type = 2;//物业
            $from_id = $property_id;
        } else {
            $from_type = 1;//小区
            $from_id = $village_id;
        }
        if(!$from_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $param['gid'] = $gid;
            $param['type'] = $type;
            $param['title'] = $title;
            $param['from_id'] = $from_id;
            $param['from_type'] = $from_type;
            $param['page'] = $page;
            $param['uid']  = intval($this->_uid);
            $list = $serviceContentEngine->getContentList($param,$village_id,$property_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes:获取内容引擎的详情
     * @datetime: 2021/3/20 15:24
     * @return \json
     */
    public function getContentInfo()
    {
        $id = $this->request->param('id','','int');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceContentEngine = new ContentEngineService();
        try{
            $data = $serviceContentEngine->getContent($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data);
    }

    /**
     * Notes:添加编辑内容引擎内容
     * @return \json
     */
    public function subContent()
    {
        $id = $this->request->param('id','','int');
        $type = $this->request->param('type','','int');
        $gid = $this->request->param('gid','','int');
        $title = $this->request->param('title','','trim');
        $content = $this->request->param('content');
        $share_title = $this->request->param('share_title');
        $share_dsc = $this->request->param('share_dsc');
        $share_img = $this->request->param('share_img');
        if($type == 1) {
            if (!$title) {
                return api_output_error(1001, '请输入标题');
            }
//            if (!$content) {
//                return api_output_error(1001, '请输入内容');
//            }
        }
        if($type == 2 && !$content){
            return api_output_error(1001, '请上传图片');
        }
        if($type == 3 && !$content){
            return api_output_error(1001, '请上传文件');
        }
        //功能库
        if($type == 4){
            if (!$title) {
                return api_output_error(1001, '请输入标题');
            }
            if(!$content){
                return api_output_error(1001, '请输入或选择分享链接');
            }
            if (!$share_title) {
                return api_output_error(1001, '请输入分享标题');
            }
            if (!$share_img) {
                return api_output_error(1001, '请上传分享图片');
            }
        }
        if(!$gid){
            return api_output_error(1001,'请选择分组');
        }
        $adminUser = $this->adminUser;
        $userId = intval($this->_uid);
        $login_role = intval($this->login_role);
        if(in_array($login_role,$this->villageRole)){
            $from_id = $adminUser['village_id'];
            $from_type = 1;
        }
        if(in_array($login_role,$this->propertyRole)){
            $from_id = $adminUser['property_id'];
            $from_type = 2;
        }
        $data = [
            'type'=>$type,
            'gid'=>$gid,
            'title'=>$title,
            'content'=>$content,
            'from_id'=>$from_id,
            'from_type'=>$from_type,
            'add_uid'=>$userId,
        ];
        if($type == 4){
            $data['share_title'] = $share_title;
            $data['share_dsc'] = $share_dsc;
            $data['share_img'] = $share_img;
        }
        $serviceContentEngine = new ContentEngineService();
        try{
            $res = $serviceContentEngine->subContent($id,$data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes:删除内容引擎内容
     * @return \json
     */
    public function delContent()
    {
        $id = $this->request->param('id','0','int');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceContentEngine = new ContentEngineService();
        try{
            $res = $serviceContentEngine->delContent($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes: 上传指定图片
     * @return \json
     */
    public function uploadFile()
    {
        $file = $this->request->file('file');
        $pathname=$this->request->param('pathname','','trim');
        if($pathname){
            $pathname=strval($pathname);
            $pathname=strip_tags($pathname);
            $pathname=htmlspecialchars($pathname,ENT_QUOTES);
        }
        if(!$file){
            return api_output_error(1001,'请上传图片');
        }
        try {
            validate(['imgFile' => [
                'fileSize' => 1024 * 1024 * 10,
                'fileExt' => 'jpg,png,jpeg,gif',
//                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon',
            ]])->check(['imgFile' => $file]);

            $imgName = $file->getOriginalName();
            $filePath=!empty($pathname) ? $pathname:'qyWx';
            
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( $filePath,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $params = ['savepath' => $data['url']];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            $data['name'] = $imgName;
            $data['path'] = replace_file_domain($data['url']);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**
     * Notes:上传文件
     * @return \json
     */
    public function uploadFiles()
    {
        $file = $this->request->file('file');
        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'doc,docx,xls,xlsx,ppt,pptx,txt,pdf,xmind',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode('.',$fileName);
            if(count($file_arr)==2)
            {
                $file_type = $file_arr[1];
            }else{
                return api_output_error(1001,'请上传有效文件');
            }

            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( 'qyWx/file',$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $data['name'] = $fileName;
            $data['file_type'] = $file_type;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**
     * Notes:上传视频
     * @return \json
     */
    public function uploadVideo()
    {
        $file = $this->request->file('file');
        $pathname=$this->request->param('pathname','','trim');
        if($pathname){
            $pathname=strval($pathname);
            $pathname=strip_tags($pathname);
            $pathname=htmlspecialchars($pathname,ENT_QUOTES);
        }
        if(!$file){
            return api_output_error(1001,'请上传视频');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 10,
                'fileExt' => 'mp4,avi,mp3',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode('.',$fileName);
            if(count($file_arr)==2)
            {
                $file_type = $file_arr[1];
            }else{
                return api_output_error(1001,'请上传有效视频');
            }
            $filePath=!empty($pathname) ? $pathname : 'qyWx/video';
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile( $filePath,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data['url'] = '/upload/'.$savename;
            $data['name'] = $fileName;
            $data['file_type'] = $file_type;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**
     * Notes:上传excel文件
     * @return \json
     */
    public function uploadExcel()
    {
        $file = $this->request->file('file');
        $pathname=$this->request->param('pathname','','trim');
        if($pathname){
            $pathname=strval($pathname);
            $pathname=strip_tags($pathname);
            $pathname=htmlspecialchars($pathname,ENT_QUOTES);
        }
        if (!$file) {
            return api_output_error(1001, '请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'xls,xlsx',
            ]])->check(['file' => $file]);
            $filePath=!empty($pathname) ? $pathname : 'qyWx/file';
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile($filePath,$file);
            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }
            $data=array();
            $url = 'upload/'.$savename;
            $data['url'] = $url;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    /**
     * Notes:上传导入文本
     * @return \json
     */
    public function importExcel()
    {
        $adminUser = $this->adminUser;
        $userId = intval($this->_uid);
        $login_role = intval($this->login_role);
        if(in_array($login_role,$this->villageRole)){
            $from_id = $adminUser['village_id'];
            $type = 1;
        }
        if(in_array($login_role,$this->propertyRole)){
            $from_id = $adminUser['property_id'];
            $type = 2;
        }
        $savename = $this->request->param('file_url','','trim');
        $gid = $this->request->param('gid','','trim');
        if(!$savename){
            return api_output_error(-1, '请上传有效的Excel文件');
        }
        $path =  public_path().'../../'.$savename;
        $serviceContentEngine = new ContentEngineService();
        try{
            $res = $serviceContentEngine->importExcel($path,$from_id,$type,$userId,$gid);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res, "成功");
    }

    /**
     * Notes:下载表格
     */
    public function downloadExcel()
    {
        $serviceContentEngine = new ContentEngineService();
        $serviceContentEngine->downloadTab();
    }

    /**
     * Notes: 获取分组下拉数据
     * @return \json
     */
    public function getMenuSelect()
    {
        $village_id = $this->adminUser['village_id'];
        $serviceContentEngine = new ContentEngineService();
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        if(!$param_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $list = $serviceContentEngine->getGroupSelect($type,$property_id,$village_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes:功能库数据
     * @return \json
     */
    public function functionLibrary()
    {
        $from_id = $this->adminUser['village_id'];
//        $from_id = 13;
        $from_type = 1;
        $serviceContentEngine = new ContentEngineService();
        $data = $serviceContentEngine->functionLibrary($from_id,$from_type);
        return api_output(0,$data);
    }

    /**
     * Notes:子集功能库
     * @return \json
     */
    public function childLibrary()
    {
        $village_id = $this->adminUser['village_id'];
        $serviceContentEngine = new ContentEngineService();
        $type = $this->request->param('type','','trim');
        $id = $this->request->param('id','0','int');
        $data = $serviceContentEngine->childLibrary($village_id,$type,$id);
        return api_output(0,$data);
    }
    //--------------------------渠道码---------------------

    /**
     * Notes: 获取渠道码列表（暂未使用）
     * @return \json
     */
    public function getChannelCodeList()
    {
        $gid = $this->request->param('gid','','int');
        $type = $this->request->param('type','','int');
        $title = $this->request->param('title','','trim');
        $page = $this->request->param('page','','int');
        $village_id = $this->adminUser['village_id'];
        $from_type = 1;//小区
        $serviceContentEngine = new ContentEngineService();
        if(!$village_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        try{
            $param['gid'] = $gid;
            $param['type'] = $type;
            $param['title'] = $title;
            $param['from_id'] = $village_id;
            $param['from_type'] = $from_type;
            $param['page'] = $page;

            $list = $serviceContentEngine->getChannelCode($param);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$list);
    }

    /**
     * Notes:删除渠道码
     * @return \json
     */
    public function delChannelCode()
    {
        $id = $this->request->param('id','0','int');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceContentEngine = new ContentEngineService();
        try{
            $res = $serviceContentEngine->delChannelCode($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res);
    }

    /**
     * Notes:上传txt文件
     * @return \json
     */
    public function uploadFileTxt()
    {
        $file = $this->request->file('file');
        if(!$file){
            return api_output_error(1001,'请上传文件');
        }
        try {
            validate(['file' => [
                'fileSize' => 1024 * 1024 * 20,
                'fileExt' => 'txt',
            ]])->check(['file' => $file]);

            $fileName = $file->getOriginalName();

            $file_arr = explode('.',$fileName);
            if(count($file_arr)!=2)
            {
                return api_output_error(1001,'请上传有效文件');
            }

            $savename = \think\facade\Filesystem::disk('base_upload')->putFileAs('', $file,$fileName,[],true);

            if(strpos($savename,"\\") !== false){
                $savename = str_replace('\\','/',$savename);
            }

            $data['url'] = '/'.$savename;
            $data['name'] = $fileName;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");

    }

    /**
     * Notes:保存上传txt文件记录
     * @return \json
     */
    public function butSet()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $from_id = $property_id;
        } else {
            $type = 1;//小区
            $from_id = $village_id;
        }
        $url = $this->request->param('url','','trim');
        $serviceContentEngine = new ContentEngineService();
        try {
            $res = $serviceContentEngine->chatColumn($url, $from_id, $type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res, "成功");
    }

    /**
     * Notes:聊天侧边栏的项目数据
     * @return \json
     */
    public function setColumn()
    {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            $type = 1;//小区
            $param_id = $village_id;
        }
        if(!$param_id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceContentEngine = new ContentEngineService();
        try {
            $data = $serviceContentEngine->setColumn($param_id,$type);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $data, "成功");
    }

    public function addAgent() {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (in_array($login_role,$this->propertyRole)) {
            $type = 2;//物业
            $param_id = $property_id;
        } else {
            return api_output_error(1001,'小区当前不支持此操作');
        }
        $id = $this->request->param('id','','intval');
        $agentid = $this->request->param('agentid','','trim');
        $secret = $this->request->param('secret','','trim');
        if(!$param_id)
        {
            return api_output_error(1002,'请重新登录');
        }
        if(!$agentid || (!$agentid && !$secret))
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceContentEngine = new ContentEngineService();
        try {
            $data = [
                'id' => $id ? $id : 0,
                'agentid' => $agentid,
                'secret' => $secret,
            ];
            $msg = $serviceContentEngine->addAgent($param_id,$type,$data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $msg, "成功");
    }

    public function getAgentList() {
        $village_id = $this->adminUser['village_id'];
        $property_id =  $this->adminUser['property_id'];
        $login_role = $this->login_role;
        if (! in_array($login_role,$this->propertyRole)) {
            return api_output_error(1001,'小区当前不支持此操作');
        }
        $serviceQywx = new QywxService();
        $arr = [];
        try {
            $list = $serviceQywx->getThirdAgentList($property_id);
            $arr['list'] = $list;
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $arr, "成功");
    }

}

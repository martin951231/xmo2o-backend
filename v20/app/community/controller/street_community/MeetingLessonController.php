<?php
/**
 * 三会一课相关
 * @author weili
 * @date 2020/9/18
 */

namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\MeetingLessonService;
use file_handle\FileHandle;


class MeetingLessonController extends CommunityBaseController
{
    /**
     * Notes: 分类
     * @return \json
     * @author: weili
     * @datetime: 2020/9/18 13:10
     */
    public function getLessonClassList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMeetingLesson = new MeetingLessonService();
        $page = $this->request->param('page',1,'intval');
        $name = $this->request->param('cat_name','','trim');
        $limit = $this->request->param('limit',10,'intval');
        try {
            $list = $serviceMeetingLesson->getLessonClass($street_id,$name,$page, $limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        return api_output(0,$list,'成功');
    }

    /**
     * Notes: 获取分类详情
     * @return \json
     * @author: weili
     * @datetime: 2020/9/18 13:26
     */
    public function getClassInfo()
    {
        $id = $this->request->param('cat_id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $info = $serviceMeetingLesson->getClassInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info,'成功');
    }

    /**
     * Notes: 添加/修改三会一课分类
     * @return \json
     * @author: weili
     * @datetime: 2020/9/18 13:41
     */
    public function subLessonClass()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $cat_name = $this->request->param('cat_name','','trim');
        $cat_sort = $this->request->param('cat_sort',0,'intval');
        $cat_status = $this->request->param('cat_status',0,'intval');
        $cat_id = $this->request->param('cat_id',0,'intval');
        if(!$cat_name){
            return api_output_error(1001,'必传参数缺失');
        }
        $type = $this->request->param('type',0,'intval');
        if(!$type){
            return api_output_error(1001,'请选择类型');
        }
        $data =[
            'cat_name'=>$cat_name,
            'cat_sort'=>$cat_sort,
            'cat_status'=>$cat_status,
            'area_id'=>$street_id,
            'type'=>$type,
            'last_time'=>time()
        ];
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $info = $serviceMeetingLesson->saveClass($data,$cat_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($info)
        {
            return api_output(0,$info,'成功');
        }else{
            return api_output_error(-1,'失败');
        }

    }

    /**
     * Notes: 删除分类
     * @return \json
     * @author: weili
     * @datetime: 2020/9/18 16:52
     */
    public function delLessonClass()
    {
        $id = $this->request->param('id',0,'intval');
        $serviceMeetingLesson = new MeetingLessonService();
        $res = $serviceMeetingLesson->delClass($id);
        if($res){
            return api_output(0,$res,'成功');
        }else{
            return api_output_error(-1,'失败');
        }
    }
    //会议列表
    public function getMeetingList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $cat_id = $this->request->param('cat_id',0,'intval');
        if(!$cat_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $title = $this->request->param('title','','trim');
        $date = $this->request->param('date','','trim');
        $page = $this->request->param('page',1,'intval');
        $limit = 10;
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $list = $serviceMeetingLesson->getMeetingList($cat_id,$street_id,$title,$date,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        $list['cat_id'] = $cat_id;
        return api_output(0,$list,'成功');
    }

    /**
     * Notes: 获取会议详情
     * @return \json
     * @author: weili
     * @datetime: 2020/9/18 15:12
     */
    public function getMeetingInfo()
    {
        $meeting_id = $this->request->param('meeting_id',0,'intval');
        if(!$meeting_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $data = $serviceMeetingLesson->getMeetingInfo($meeting_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$data,'成功');
    }

    /**
     * Notes: 添加编辑会议
     * @return \json
     * @author: weili
     * @datetime: 2020/9/18 15:12
     */
    public function subMeeting()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $title = $this->request->param('title','','trim');
        $content = $this->request->param('content');
        $status = $this->request->param('status',0,'intval');
        $is_hot = $this->request->param('is_hot',0,'intval');
        $cat_id = $this->request->param('cat_id',0,'intval');
        $title_img = $this->request->param('title_img','','trim');
        $meeting_id = $this->request->param('meeting_id','','trim');

        if(!$title)
        {
            return api_output_error(1001,'请填写会议名称');
        }
        if(!$content)
        {
            return api_output_error(1001,'请填写会议内容');
        }
        $data = [
            'title'=>$title,
            'content'=>$content,
            'status'=>$status,
            'is_hot'=>$is_hot,
            'cat_id'=>$cat_id,
            'title_img'=>$title_img,
            'area_id'=>$street_id,
        ];

        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $res = $serviceMeetingLesson->saveMeeting($data,$meeting_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res){
            return api_output(0,$res,'成功');
        }else{
            return api_output_error(-1, '失败');
        }
    }
    public function delMeeting()
    {
        $id = $this->request->param('id',0,'intval');
        $serviceMeetingLesson = new MeetingLessonService();
        $res = $serviceMeetingLesson->delMeeting($id);
        if($res){
            return api_output(0,$res,'成功');
        }else{
            return api_output_error(-1,'失败');
        }
    }

    /**
     * Notes: 微信群发通知(未完成)
     * @author: weili
     * @datetime: 2020/9/18 17:15
     */
    public function weChatNotice()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $res = $serviceMeetingLesson->sendNotice($id,$street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res){
            return api_output(0,$res,'成功');
        }else{
            return api_output_error(-1,'失败');
        }
    }
    /**
     * Notes: 上传图片
     * @return \json
     * @author: weili
     * @datetime: 2020/9/9 11:00
     */
    public function upload(){
        $file = $this->request->file('img');
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public_upload')->putFile( 'meeting',$file);
        if(strpos($savename,"\\") !== false){
            $savename = str_replace('\\','/',$savename);
        }
        $imgurl = '/upload/'.$savename;
        $params = ['savepath'=>'/upload/' . $imgurl];
        $file_handle = new FileHandle();
        if($file_handle->check_open_oss()) {
            invoke_cms_model('Image/oss_upload_image', $params);
        }
        return api_output(0, $imgurl, "成功");
    }
    //回复列表
    public function getReplyList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMeetingLesson = new MeetingLessonService();
        $page = $this->request->param('page',1,'intval');
        $meeting_id = $this->request->param('meeting_id',0,'intval');
        $limit = 10;
        try {
            $list = $serviceMeetingLesson->meetingReplyList($street_id,$meeting_id,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        $list['meeting_id'] = $meeting_id;
        return api_output(0,$list,'成功');
    }
    //是否开启回复总开关
    public function isOpenReplySwitch()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $status = $this->request->param('status',0,'intval');
        if(!$status){
            return api_output_error(1001,'必传参数缺失');
        }
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $res = $serviceMeetingLesson->replySwitch($street_id,$status);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res,'成功');
    }
    //1删除 2已读 3前端是否显示
    public function actionReply()
    {
        $id = $this->request->param('id',0,'intval');
        $type = $this->request->param('type',0,'intval');
        $status = $this->request->param('status',0,'intval');
        if(!$id || !$type){
            return api_output_error(1001,'必传参数缺失');
        }
        if($type == 3 && !$status)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        switch ($type){
            case '1':
                $data['status'] = -1;
                break;
            case '2':
                $data['is_read'] = 1;
                break;
            case '3':
                $data['status'] = $status;
                break;
        }
        $serviceMeetingLesson = new MeetingLessonService();
        try {
            $res = $serviceMeetingLesson->meetingAction($id,$data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res,'成功');
    }

    /**
     * 三会一课类型
     * @author: liukezhu
     * @date : 2022/5/6
     * @return \json
     */
    public function getPartyType(){
        try {
            $info = (new MeetingLessonService())->PartyBranchType;
        }catch (\Exception  $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $info, "成功");
    }
}

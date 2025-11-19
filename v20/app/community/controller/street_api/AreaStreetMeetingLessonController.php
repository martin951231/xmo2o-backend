<?php


namespace app\community\controller\street_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetMeetingLessonService;

class AreaStreetMeetingLessonController extends CommunityBaseController
{
    /**
     * 三会一课分类
     * @author lijie
     * @date_time 2020/09/17
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeetingLessonCategoryLists()
    {
        $street_id = $this->request->post('area_street_id',0);
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        $where['area_id'] = $street_id;
        $where['cat_status'] = 1;
        $field = 'cat_id,cat_name';
        $data = $area_street_meeting_lesson_service->getMeetingLessonCategoryLists($where,$field,'cat_sort DESC');
        return api_output(0,$data);
    }

    /**
     * 三会一课详情
     * @author lijie
     * @date_time 2020/09/17
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeetingLessonDetail()
    {
        $meeting_id = $this->request->post('meeting_id',0);
        if(!$meeting_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        $where['meeting_id'] = $meeting_id;
        $field = 'title,content,meeting_id,add_time,read_sum';
        $data = $area_street_meeting_lesson_service->getMeetingLessonDetail($where,$field);
        return api_output(0,$data);
    }

    /**
     * 三会一课列表
     * @author lijie
     * @date_time 2020/09/17
     * @return \json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeetingLessonLists()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $cat_id = $this->request->post('cat_id',0);
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        if($cat_id)
            $where['cat_id'] = $cat_id;
        $where['status'] = 1;
        $field = 'title,add_time,title_img,meeting_id';
        $data = $area_street_meeting_lesson_service->getMeetingLessonLists($where,$field,$page,$limit,'is_hot,meeting_id DESC');
        return api_output(0,$data);
    }

    /**
     * 三会一课评论
     * @author lijie
     * @date_time 2020/09/17
     * @return \json
     */
    public function getMeetingLessonReply()
    {
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $meeting_id = $this->request->post('meeting_id',0);
        if(!$meeting_id)
            return api_output_error(1001,'缺少必传参数');
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        $where['r.meeting_id'] = $meeting_id;
        $where['r.status'] = 1;
        $field = 'r.content,r.add_time,u.nickname,u.avatar';
        $data = $area_street_meeting_lesson_service->getMeetingLessonReplyLists($where,$field,$page,$limit,'r.pigcms_id DESC');
        return api_output(0,$data);
    }

    /**
     * 添加三会一课留言
     * @author lijie
     * @date_time 2020/09/17
     * @return \json
     */
    public function addReply()
    {
        $street_id = $this->request->post('area_street_id',0);
        $meeting_id = $this->request->post('meeting_id',0);
        $content = $this->request->post('content','');
        if(!$street_id || !$meeting_id || !$content)
            return api_output_error(1001,'缺少必传参数');
        $uid = intval($this->request->log_uid);
        if (!$uid) {
            return api_output_error(1002, "没有登录");
        }
        $data['uid'] = $uid;
        $data['area_id'] = $street_id;
        $data['content'] = $content;
        $data['status'] = 1;
        $data['meeting_id'] = $meeting_id;
        $data['add_time'] = time();
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        $res = $area_street_meeting_lesson_service->addReply($data);
        if($res)
            return api_output(0,'','留言成功');
        return api_output_error(1001,'服务异常');
    }

    /**
     * 获取最新的4个三会一课
     * @author lijie
     * @date_time 2020/12/03
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFourLesson()
    {
        $street_id = $this->request->post('area_street_id',0);
        $area_street_meeting_lesson_service = new AreaStreetMeetingLessonService();
        $data = $area_street_meeting_lesson_service->getMeetingLessonLists(['status'=>1,'area_id'=>$street_id],true,1,4,'meeting_id DESC');
        return api_output(0,$data);
    }
}
<?php


namespace app\community\model\service;

use app\community\model\db\AreaStreetMeetingLessonReply;
use app\community\model\db\AreaStreetMeetingLesson;
use app\community\model\db\AreaStreetMeetingLessonCategory;

class AreaStreetMeetingLessonService
{
    private $db_area_street_meeting_lesson = '';
    private $db_area_street_meeting_lesson_category = '';
    private $db_area_street_meeting_lesson_reply = '';

    /**
     * 初始化数据
     * AreaStreetMeetingLessonService constructor.
     */
    public function __construct()
    {
        $this->db_area_street_meeting_lesson = new AreaStreetMeetingLesson();
        $this->db_area_street_meeting_lesson_category = new AreaStreetMeetingLessonCategory();
        $this->db_area_street_meeting_lesson_reply = new AreaStreetMeetingLessonReply();
    }

    /**
     * 三会一课列表
     * @author lijie
     * @date_time 2020/09/17
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeetingLessonLists($where,$field=true,$page=1,$limit=20,$order='is_hot,meeting_id DESC')
    {
        $data = $this->db_area_street_meeting_lesson->getLists($where,$field,$page,$limit,$order);
        if($data){
            $site_url = cfg('site_url');
            $static_resources = static_resources(true);
            foreach ($data as $k=>$v){
                $data[$k]['label'] = '三会一课';
                if (isset($v['add_time']) && $v['add_time']) {
                    $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
                }
                if (isset($v['meeting_id']) && $v['meeting_id']) {
                    $this->db_area_street_meeting_lesson->incReadNum(['meeting_id'=>$v['meeting_id']]);
                }
                if (isset($v['title_img']) && $v['title_img']) {
                    if (strpos($v['title_img'],'/v20/') !== false) {
                        $data[$k]['title_img'] = cfg('site_url') . $v['title_img'];
                    } else {
                        $data[$k]['title_img'] = replace_file_domain($v['title_img']);
                    }
                } else {
                    $data[$k]['title_img'] =  $site_url . $static_resources . 'images/meeting.png';
                }
            }
        }
        return $data;
    }

    /**
     * 获取三会一课分类列表
     * @author lijie
     * @date_time 2020/09/17
     * @param $where
     * @param bool $field
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeetingLessonCategoryLists($where,$field=true,$order='cat_sort DESC')
    {
        $data = $this->db_area_street_meeting_lesson_category->getLists($where,$field,$order);
        return $data;
    }

    /**
     * 三会一课评论列表
     * @author lijie
     * @date_time 2020/09/17
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return mixed
     */
    public function getMeetingLessonReplyLists($where,$field=true,$page=1,$limit=20,$order='r.pigcms_id DESC')
    {
        $data = $this->db_area_street_meeting_lesson_reply->getLists($where,$field,$page,$limit,$order);
        if($data){
            foreach ($data as $k=>$v){
                $data[$k]['add_time'] = date('Y-m-d',$v['add_time']);
            }
        }
        return $data;
    }

    /**
     * 三会一课详情
     * @author lijie
     * @date_time 2020/09/17
     * @param $where
     * @param $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMeetingLessonDetail($where,$field=true)
    {
        $data = $this->db_area_street_meeting_lesson->getOne($where,$field);
        if($data){
            $data['add_time'] = date('Y-m-d H:i:s',$data['add_time']);
        }
        return $data;
    }

    /**
     * 添加评论
     * @author lijie
     * @date_time 2020/09/17
     * @param $data
     * @return int|string
     */
    public function addReply($data)
    {
        $res = $this->db_area_street_meeting_lesson_reply->saveOne($data);
        return $res;
    }

}
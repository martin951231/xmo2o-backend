<?php
/**
 * 智慧党建之党内活动管理
 * @author weili
 * @date 2020/9/22
 */

namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\PartyActivitiesService;
use file_handle\FileHandle;

class PartyActivitiesController extends CommunityBaseController
{
    /**
     * Notes:获取党内活动列表
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 11:36
     */
    public function getPartyActivityList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyActivities = new PartyActivitiesService();
        $name = $this->request->param('name','','trim');
        $page = $this->request->param('page',0,'intval');
        $limit = 10;
        try{
            $list = $servicePartyActivities->PartyActivityList($street_id,$name,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        return api_output(0,$list,'成功');
    }

    /**
     * Notes: 获取详情
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 11:40
     */
    public function getPartyActivityInfo()
    {
        $id = $this->request->param('party_activity_id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyActivities = new PartyActivitiesService();
        try{
            $info = $servicePartyActivities->getActivityInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($info)
        {
            return api_output(0,$info,'成功');
        }else{
            return api_output_error(-1, '失败');
        }
    }

    /**
     * Notes: 添加编辑
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 16:43
     */
    public function subPartyActivity()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $name = $this->request->param('name','','trim');
        $max_num = $this->request->param('max_num',0,'intval');
        $id_card_status = $this->request->param('id_card_status',2,'intval');
        $sort = $this->request->param('sort',0,'intval');
        $party_activity_id = $this->request->param('party_activity_id',0,'intval');
        $desc = $this->request->param('desc');
        $status = $this->request->param('status',1,'intval');
        $activity_time = $this->request->param('activity_time');
        $close_time = $this->request->param('close_time','','trim');
        $img_arr = $this->request->param('img_arr');
        if(!$name || count($activity_time)<=0 || !$close_time){
            return api_output_error(1001,'必传参数缺失');
        }
        $data = [
            'name'=>$name,
            'img_arr'=>$img_arr,
            'activity_time'=>$activity_time,
            'close_time'=>$close_time,
            'max_num'=>$max_num,
            'sort'=>$sort,
            'status'=>$status,
            'id_card_status'=>$id_card_status,
            'desc'=>$desc,
        ];
        $servicePartyActivities = new PartyActivitiesService();
        try {
            $res = $servicePartyActivities->subPartyActivity($data, $party_activity_id,$street_id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res,'成功');
    }

    /**
     * Notes:删除数据
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 16:20
     */
    public function delPartyActivity()
    {
        $id = $this->request->param('id',0,'intval');
        if(!$id){
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyActivities = new PartyActivitiesService();
        $res = $servicePartyActivities->delActivity($id);
        return api_output(0,$res,'成功');
    }
    /**
     * Notes: 上传图片
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 13:04
     */
    public function upload(){
        $file = $this->request->file('active_img');
        try {
            // 验证
//            validate(['active_img' => [
//                'fileSize' => 1024 * 1024 * 10,   //10M
//                'fileExt' => 'jpg,png,jpeg,gif,ico',
//                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
//            ]])->check(['active_img' => $file]);
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile('partyActivity', $file);
            if (strpos($savename, "\\") !== false) {
                $savename = str_replace('\\', '/', $savename);
            }
            $imgurl = '/upload/' . $savename;
            $params = ['savepath'=>'/upload/' . $imgurl];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            return json($imgurl);
//        return api_output(0, $imgurl, "成功");
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * Notes: 获取报名列表
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 18:06
     */
    public function getApplyList()
    {
        $street_id = $this->adminUser['area_id'];
        if(!$street_id){
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyActivities = new PartyActivitiesService();
        $name = $this->request->param('user_name','','trim');
        $phone = $this->request->param('user_phone','','trim');
        $id = $this->request->param('party_activity_id','','trim');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $page = $this->request->param('page',0,'intval');
        $limit = 10;
        $data = [
            'name'=>$name,
            'phone'=>$phone,
        ];
        try{
            $list = $servicePartyActivities->getActivityApplyList($id,$data,$page,$limit);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        $list['total_limit'] = $limit;
        $list['party_activity_id'] = $id;
        return api_output(0,$list,'成功');
    }

    /**
     * Notes: 获取报名信息详情
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 18:07
     */
    public function getApplyInfo()
    {
        $id = $this->request->param('id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyActivities = new PartyActivitiesService();
        try {
            $info = $servicePartyActivities->getActivityApplyInfo($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$info,'成功');
    }

    /**
     * Notes: 修改报名信息
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 18:07
     */
    public function subApply()
    {
        $id = $this->request->param('id',0,'intval');
        $user_name = $this->request->param('user_name','','trim');
        $user_phone = $this->request->param('user_phone','','trim');
        $id_card = $this->request->param('id_card','','trim');
        $desc = $this->request->param('desc');
        $status = $this->request->param('status',0,'intval');
        if(!$id || !$user_name || !$user_phone || !$id_card || !$status)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $data = [
            'user_name'=>$user_name,
            'user_phone'=>$user_phone,
            'id_card'=>$id_card,
            'desc'=>$desc,
            'status'=>$status,
        ];
        $servicePartyActivities = new PartyActivitiesService();
        try {
            $res = $servicePartyActivities->editApply($data, $id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res,'成功');
    }

    /**
     * Notes: 软删除
     * @return \json
     * @author: weili
     * @datetime: 2020/9/22 18:07
     */
    public function delApply()
    {
        $id = $this->request->param('id',0,'intval');
        if(!$id)
        {
            return api_output_error(1001,'必传参数缺失');
        }
        $servicePartyActivities = new PartyActivitiesService();
        try {
            $res = $servicePartyActivities->delApply($id);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0,$res,'成功');
    }
}
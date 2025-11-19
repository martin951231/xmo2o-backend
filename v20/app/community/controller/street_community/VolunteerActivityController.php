<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/5/29 13:05
 */

namespace app\community\controller\street_community;

use app\community\controller\CommunityBaseController;
use app\community\model\service\VolunteerService;

use file_handle\FileHandle;
use think\facade\Filesystem;

class VolunteerActivityController extends CommunityBaseController
{
    /**
     * 获取志愿者活动列表
     * @param 传参
     * array (
     *  'key_val'=> '查询关键字',
     *  'value'=> '对应查询关键字的内容',
     *  'page'=> '查询页数 必传',
     *  'status'=> '对应查询状态',
     *  'ticket' => '', 登录标识 必传
     * )
     * @author: wanziyang
     * @date_time: 2020/5/27 17:48
     * @return \json
     */
    public function getList() {
        $info = $this->adminUser;
        $service_volunteer = new VolunteerService();

        $where = [];
        if (0==$info['area_type']) {
            $where[] = ['bind_id','=',$info['area_id']];
            $where[] = ['bind_type','=',0];
        } elseif(1==$info['area_type']) {
            $where[] = ['bind_id','=',$info['area_id']];
            $where[] = ['bind_type','=',1];
        }
        // 页数
        $page = $this->request->param('page','','intval');
        // 查询关键字
        $key_val = $this->request->param('key_val','','trim');
        // 对应查询关键字的内容
        $search_val = $this->request->param('value','','trim');
        if ('active_name'==$key_val && $search_val) {
            $where[] = ['active_name', 'like', '%'.$search_val.'%'];
        }
        // 对应查询状态
        $status = $this->request->param('status','','intval');
        if ($status) {
            $where[] = ['status','=',$status];
        }
        // 对应查询时间
        $date = $this->request->param('date');
        if ($date && $date[0] && $date[1]) {
            $where[] = ['add_time','between',[strtotime($date[0]),strtotime(date('Y-m-d 23:59:59',strtotime($date[1])))]];
        }

        $out = $service_volunteer->getLimitVolunteerActivityList($where, $page);
        return api_output(0,$out);
    }

    /**
     * 添加编辑志愿者活动
     * @author: wanziyang
     * @date_time: 2020/5/29 15:30
     * @return \json
     */
    public function addVolunteerActivity() {
        $info = $this->adminUser;
        $service_volunteer = new VolunteerService();
        $data = [];
        if (0==$info['area_type']) {
            $data['bind_id'] = $info['area_id'];
            $data['bind_type'] = 0;
        } elseif(1==$info['area_type']) {
            $data['bind_id'] = $info['area_id'];
            $data['bind_type'] = 1;
        }
        // 活动id
        $activity_id = $this->request->param('activity_id','','intval');
        if (!empty($activity_id)) {
            $data['activity_id'] = $activity_id;
        }
        // 活动名称
        $active_name = $this->request->param('active_name','','trim');
        if (empty($active_name)) {
            return api_output(1001,[], '请填写活动名称');
        }
        $data['active_name'] = $active_name;
        // 活动内容
        $richText = $this->request->param('richText','','trim');
        if ($richText) {
            $data['richText'] = htmlspecialchars($richText);
        }
        // 活动时间
        $start_time = $this->request->param('start_time','','trim');
        if (empty($start_time)) {
            return api_output(1001,[], '请填写活动开始时间');
        }
        $end_time = $this->request->param('end_time','','trim');
        if (empty($end_time)) {
            return api_output(1001,[], '请填写活动结束时间');
        }
        $data['start_time'] = strtotime($start_time);
        $data['end_time'] = strtotime($end_time);
        if($data['end_time']<=$data['start_time']){
            return api_output(1001,[], '活动结束时间不能小于等于开始时间！');
        }
        $close_time = $this->request->param('close_time','','trim');
        if(empty($close_time)){
            return api_output(1001,[], '请填写活动报名截止时间');
        }
        $data['close_time'] =strtotime($close_time);
            // 最多限制人数报名
        $max_num = $this->request->param('max_num','-1','intval');
        if ($max_num!=-1) {
            $data['max_num'] = $max_num;
        }
        $is_need = $this->request->param('is_need','','intval');
        if ($is_need) {
            $data['is_need'] = $is_need;
        }
        $status = $this->request->param('status','','intval');
        if ($is_need) {
            $data['status'] = $status;
        }
        $sort = $this->request->param('sort','-1','intval');
        if ($sort!=-1) {
            $data['sort'] = $sort;
        }
        // 请上传图片  以数组信息
        $img_arr = $this->request->param('img_arr');
        if ($img_arr && is_array($img_arr)) {
            $data['img'] = serialize($img_arr);
        }
        $data['is_repeat'] = $this->request->param('is_repeat','','intval');
        try {
            $volunteer = $service_volunteer->addAndEditVolunteer($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        if($volunteer) {
            return api_output(0, $data, "操作成功");
        }
        return api_output_error(-1, "操作失败");
    }

    /**
     * 获取志愿者活动详情
     * @author: wanziyang
     * @date_time: 2020/5/29 16:09
     * @return \json
     */
    public function getVolunteerDetail() {
        $info = $this->adminUser;
        $service_volunteer = new VolunteerService();

        $where = [];
        if (0==$info['area_type']) {
            $where[] = ['bind_id','=',$info['area_id']];
            $where[] = ['bind_type','=',0];
        } elseif(1==$info['area_type']) {
            $where[] = ['bind_id','=',$info['area_id']];
            $where[] = ['bind_type','=',1];
        }
        // 活动id
        $activity_id = $this->request->param('activity_id','','intval');
        if (empty($activity_id)) {
            return api_output(1001,[], '缺少活动id');
        }
        $where = [];
        $where[] = ['activity_id', '=', $activity_id];

        $detail = $service_volunteer->getVolunteerActivity($where);
        $out['info'] = $detail;
        return api_output(0,$out);
    }

    /**
     * 删除志愿者活动
     * @author: wanziyang
     * @date_time: 2020/5/29 16:21
     * @return \json
     */
    public function delVolunteerActivity() {
        $info = $this->adminUser;
        $service_volunteer = new VolunteerService();

        $where = [];
        if (0==$info['area_type']) {
            $where[] = ['bind_id','=',$info['area_id']];
            $where[] = ['bind_type','=',0];
        } elseif(1==$info['area_type']) {
            $where[] = ['bind_id','=',$info['area_id']];
            $where[] = ['bind_type','=',1];
        }
        // 活动id
        $activity_id = $this->request->param('activity_id','','intval');
        if (empty($activity_id)) {
            return api_output(1001,[], '缺少活动id');
        }
        $where = [];
        $where[] = ['activity_id', '=', $activity_id];
        $detail = $service_volunteer->getVolunteerActivity($where);
        if (empty($detail)) {
            return api_output(1001,[], '活动不存在或者已经被删除');
        }
        $del = $service_volunteer->delVolunteerActivity($where);
        if ($del) {
            return api_output(0,['activity_id' => $activity_id]);
        } else {
            return api_output(1001,[], '删除失败，请重试');
        }
    }
    //上传图片   （废弃）
    public function uploadImgApis() {
        $info = $this->adminUser;
        $out['post'] = $_POST;
        $files = $this->request->file('active_img');
        $out['file'] = $_FILES;
        $out['files'] = $files;

        $file = $_FILES['active_img'];

        if (!$file) {
            return api_output(1001,[],'未上传图片');
        }

        $temp = explode(".", $file["name"]);
        $extension = end($temp);

        if(!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'ico'))){
            return api_output(1002,[],'上传图片不合法');
        }

        $path = 'volunteer';
        $img_id = sprintf("%09d", 1001);
        $rand_num = substr($img_id, 0, 3) . '/' . substr($img_id, 3, 3) . '/' . substr($img_id, 6, 3);
        $upload_dir = "/{$path}/{$rand_num}/";

        $saveName = \think\facade\Filesystem::putFile( 'image', $file);

        $out['upload_dir'] = $upload_dir;
        $out['saveName'] = $saveName;
        return api_output(0,$out);
    }

    /**
     * Notes: 上传图片 新增
     * @return \json
     * @author: weili
     * @datetime: 2020/9/11 11:57
     */
    public function uploadImgApi()
    {
        $info = $this->adminUser;
        $files = $this->request->file('active_img');
        $file = $this->request->file('img');
        if($file)
        {
            $files = $file;
        }
        $path = 'volunteer';
        $img_id = sprintf("%09d", 1111);
        $rand_num = substr($img_id, 0, 3) . '/' . substr($img_id, 3, 3) . '/' . substr($img_id, 6, 3);
        $upload_dir = "/{$path}/{$rand_num}";
        $saveName = \think\facade\Filesystem::disk('public_upload')->putFile( $upload_dir,$files);
        if(strpos($saveName,"\\") !== false){
            $saveName = str_replace('\\','/',$saveName);
        }
        $imgurl = '/upload/'.$saveName;
        if($file)
        {
            $params = ['savepath'=>'/upload/' . $imgurl];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            $data['url'] =dispose_url($imgurl);
            return json($data);
        }else{
            return json($imgurl);
        }
        $out['saveName'] = $imgurl;
        return api_output(0,$out);
    }
    /**
     * 获取志愿者活动报名列表
     * @param 传参
     * array (
     *  'key_val'=> '查询关键字',
     *  'value'=> '对应查询关键字的内容',
     *  'page'=> '查询页数 必传',
     *  'status'=> '对应查询状态',
     *  'ticket' => '', 登录标识 必传
     * )
     * @author: wanziyang
     * @date_time: 2020/5/27 17:48
     * @return \json
     */
    public function getActiveJoinList() {
        $service_volunteer = new VolunteerService();
        $where = [];
        $where[] = ['join_type','=',0];
        // 活动id
        $activity_id = $this->request->param('activity_id','','intval');
        if ($activity_id) {
            $where[] = ['activity_id','=',$activity_id];
        }
        // 页数
        $page = $this->request->param('page','','intval');
        // 查询参加人姓名
        $join_name = $this->request->param('join_name','','trim');
        if ($join_name) {
            $where[] = ['join_name', 'like', '%'.$join_name.'%'];
        }
        // 查询参加人联系电话
        $join_phone = $this->request->param('join_phone','','trim');
        if ($join_phone) {
            $where[] = ['join_phone', 'like', '%'.$join_phone.'%'];
        }
        // 对应查询状态
//        $status = $this->request->param('status','','intval');
//        if ($status) {
//            $where[] = ['status','=',$status];
//        }

        $status = $this->request->param('status',1,'intval');
        if($status > 1){
            $examine_arr=[
                2=>0,
                3=>1,
                4=>2,
            ];
            if(isset($examine_arr[$status])){
                $where[] = ['join_examine','=',$examine_arr[$status]];
            }
        }
        
        $where[] = ['join_status','in',[1,2]];
        // 对应查询时间
        $date = $this->request->param('date');
        if ($date && $date[0] && $date[1]) {
            $where[] = ['add_time','between',[strtotime($date[0]),strtotime(date('Y-m-d 23:59:59',strtotime($date[1])))]];
        }
        $out = $service_volunteer->getLimitVolunteerActivityJoinList($where, $page);
        return api_output(0,$out);
    }


    /**
     * 报名详情
     * @author: liukezhu
     * @date : 2022/6/24
     * @return \json
     * @throws \think\Exception
     */
    public function getVolunteerActiveJoinInfo(){
        $join_id = $this->request->param('join_id',0,'intval');
        $activity_id = $this->request->param('activity_id',0,'intval');
        if(empty($join_id) || empty($activity_id)){
            return api_output(1001,[], '缺少必要参数');
        }
        $list=(new VolunteerService())->getActiveJoinInfo($join_id, $activity_id);
        return api_output(0,['info'=>$list]);
    }

    /**
     * 删除报名信息
     * @author: liukezhu
     * @date : 2022/2/10
     * @return \json
     * @throws \think\Exception
     */
    public function delActivityJoin(){
        $join_id = $this->request->param('join_id',0,'intval');
        $activity_id = $this->request->param('activity_id',0,'intval');
        if(empty($join_id)){
            return api_output(1001,[], '删除失败，请重试');
        }
        $del=(new VolunteerService())->delActivityJoin($join_id, ['join_status'=>4]);
        if ($del) {
            return api_output(0,['activity_id' => $activity_id]);
        } else {
            return api_output(1001,[], '删除失败，请重试');
        }
    }


    /**
     * 获取志愿者活动加入者详情
     * @author: wanziyang
     * @date_time: 2020/5/29 16:09
     * @return \json
     */
    public function getVolunteerActiveJoinDetail() {
        $service_volunteer = new VolunteerService();

        $where = [];
        $where[] = ['join_type','=',0];
        // 加入者id
        $join_id = $this->request->param('join_id','','intval');
        if (empty($join_id)) {
            return api_output(1001,[], '缺少活动id');
        }
        $where = [];
        $where[] = ['join_id', '=', $join_id];
        $detail = $service_volunteer->getVolunteerActivity($where);
        $out['info'] = $detail;
        return api_output(0,$out);
    }

    /**
     * Notes: 修改报名信息
     * @return \json
     */
    public function subActiveJoin()
    {
        $join_id = $this->request->param('join_id','','intval');
        $join_name = $this->request->param('join_name','','trim');
        $join_phone = $this->request->param('join_phone','','trim');
        $join_id_card = $this->request->param('join_id_card','','trim');
        $join_remark = $this->request->param('join_remark','','trim');
        $join_status = $this->request->param('join_status','','intval');
        $join_examine = $this->request->param('join_examine',0,'intval');
        if(!$join_id || !$join_name ||!$join_phone ){
            return api_output(1001,[], '必传参数不能为空');
        }
        $data = [
            'join_name'=>$join_name,
            'join_phone'=>$join_phone,
            'join_id_card'=>$join_id_card,
            'join_remark'=>$join_remark,
            'join_status'=>$join_status,
            'join_examine'=>$join_examine,
            'join_last_time'=>time(),
        ];
        $service_volunteer = new VolunteerService();
        try {
            $res = $service_volunteer->editActivityJoin($join_id, $data);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
        if($res){
            return api_output(0,$res);
        }else{
            return api_output(1001,[], '失败');
        }
    }
}
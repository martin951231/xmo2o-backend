<?php
/**
 * Created by PhpStorm.
 * User: guoxiansen
 * Date: 2020/5/30
 * Time: 16:34
 */
namespace app\community\controller\street_community;


use app\community\controller\CommunityBaseController;
use app\community\model\service\PartyActivityService;
use app\common\model\service\PartyActivityJoinService;

class PartyActivityController extends CommunityBaseController
{
    /**
     * @author 郭先森
     * @desc 获取党内活动列表
     * @return json
     */
    public function getList()
    {
        $data = [];
        $party_list = PartyActivityService::getPartyActivityList($data);

        return api_output(0,$party_list);
    }


    /**
     * @desc 获取党内活动新建
     * @return json
     */
    public function addPartyActivity()
    {
        $partyActivityService = new PartyActivityService();
        if(IS_POST){
            $data = [];
            $name = $this->request->param('name','');
            if(empty($name)){
               return api_output(1001,[],'活动名称不能为空!');
            }

            $img = $this->request->param('img','');
            if(empty($img)){
                return api_output(1001,[],'图片不能为空!');
            }

            $start_time = $this->request->param('start_time','');
            if(empty($start_time)){
                return api_output(1001,[],'活动开始时间不能为空!');
            }

            $end_time = $this->request->param('end_time','');
            if(empty($end_time)){
                return api_output(1001,[],'活动结束时间不能为空!');
            }

            $close_time = $this->request->param('close_time','');
            if(empty($close_time)){
                return api_output(1001,[],'活动截止时间不能为空!');
            }

            $sign_up_num = $this->request->param('sign_up_num','');
            if(empty($sign_up_num)){
                return api_output(1001,[],'活动人数不能为空!');
            }



            $data['name'] = $name;
            $data['img'] = $img;
            $data['desc'] = $this->request->param('desc','');
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;
            $data['close_time'] = $close_time;
            $data['add_time'] = time();
            $data['sign_up_num'] = $sign_up_num;
            $data['sort'] = $this->request->param('sort','');
            $data['status'] = $this->request->param('status','');

            $addPartyActivity = $partyActivityService->addPartyActivityService($data);
            if($addPartyActivity) {
                return api_output(0, $data, "操作成功");
            }
            return api_output_error(-1, "操作失败");
        }


    }

    /**
     * @desc 获取党内活动修改
     * @return json
     */
    public function editPartyActivity()
    {
        $service_activity = new PartyActivityService();
        // 活动id
        $party_activity_id = $this->request->param('party_activity_id','','intval');
        if (empty($party_activity_id)) {
            return api_output(1001,[], '缺少活动id');
        }
        $data = [];
        $name = $this->request->param('name','');
        if(empty($name)){
            return api_output(1001,[],'活动名称不能为空!');
        }

        $img = $this->request->param('img','');
        if(empty($img)){
            return api_output(1001,[],'图片不能为空!');
        }

        $start_time = $this->request->param('start_time','');
        if(empty($start_time)){
            return api_output(1001,[],'活动开始时间不能为空!');
        }

        $end_time = $this->request->param('end_time','');
        if(empty($end_time)){
            return api_output(1001,[],'活动结束时间不能为空!');
        }

        $close_time = $this->request->param('close_time','');
        if(empty($close_time)){
            return api_output(1001,[],'活动截止时间不能为空!');
        }

        $sign_up_num = $this->request->param('sign_up_num','');
        if(empty($sign_up_num)){
            return api_output(1001,[],'活动人数不能为空!');
        }

        $data['name'] = $name;
        $data['img'] = $img;
        $data['desc'] = $this->request->param('desc','');
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['close_time'] = $close_time;
        $data['add_time'] = time();
        $data['sign_up_num'] = $sign_up_num;
        $data['sort'] = $this->request->param('sort','');
        $data['status'] = $this->request->param('status','');
        $res = $service_activity->editPartyActivityService(array('party_activity_id'=>$party_activity_id),$data);
        if($res){
            return api_output(0, [], "操作成功");
        }else{
            return api_output_error(-1, "操作失败");
        }

    }

    /**
     * @desc 获取党内活动删除
     * @return json
     */
    public function delPartyActivity()
    {

        $service_activity = new PartyActivityService();

        $where = [];
        // 活动id
        $party_activity_id = $this->request->param('party_activity_id','','intval');
        if (empty($party_activity_id)) {
            return api_output(1001,[], '缺少活动id');
        }
        $where = [];
        $where[] = ['party_activity_id', '=', $party_activity_id];
        $del = $service_activity->delPartyActivityService($where);
        if ($del) {
            return api_output(0,['party_activity_id' => $party_activity_id]);
        } else {
            return api_output(1001,[], '删除失败，请重试');
        }
    }

    /**
     * @desc 获取党内活动详情
     * @return json
     */
    public function partyActivityInfo()
    {
        $partyActivityService = new PartyActivityService();
        $where = [];
        $info = $partyActivityService->getInfo($where);
        $out = [];
        $out['info'] = $info;
        return api_output(0,$out);
    }


    public function uploadImgApi() {
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

        $path = 'party';
        $img_id = sprintf("%09d", 1001);
        $rand_num = substr($img_id, 0, 3) . '/' . substr($img_id, 3, 3) . '/' . substr($img_id, 6, 3);
        $upload_dir = "/{$path}/{$rand_num}/";

        $saveName = \think\facade\Filesystem::putFile( 'image', $file);

        $out['upload_dir'] = $upload_dir;
        $out['saveName'] = $saveName;
        return api_output(0,$out);
    }


    /**
     * @desc 报名列表
     */
    public function getActivityJoinList()
    {
        $party_activity_json_service = new PartyActivityJoinService();
        // 活动id
        $party_activity_id = $this->request->param('party_activity_id','','intval');
        if (empty($party_activity_id)) {
            return api_output(1001,[], '缺少活动id');
        }
        $where = [];
        // 页数
        $page = $this->request->param('page','','intval');
        // 查询参加人姓名
        $user_name = $this->request->param('user_name','','trim');
        if ($user_name) {
            $where[] = ['user_name', 'like', '%'.$user_name.'%'];
        }
        // 查询参加人联系电话
        $user_phone = $this->request->param('user_phone','','trim');
        if ($user_phone) {
            $where[] = ['user_phone', 'like', '%'.$user_phone.'%'];
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

        $out = $party_activity_json_service->getPartyActivityJoinService($where, $page);
        return api_output(0,$out);

        

    }
    
}
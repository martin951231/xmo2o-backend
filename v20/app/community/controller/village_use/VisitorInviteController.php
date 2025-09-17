<?php
/**
 * 访客邀请
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/7 10:59
 */

namespace app\community\controller\village_use;
use app\community\controller\CommunityBaseController;
use app\community\model\service\FaceDoorD6SDKService;
use app\community\model\service\VisitorInviteService;
use think\App;

class VisitorInviteController  extends CommunityBaseController
{
    protected $uid;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->uid = $this->request->log_uid;
    }

    /**
     * 访客邀请
     * @author:zhubaodi
     * @date_time: 2022/1/7 11:02
     */
    public function index(){
        $uid = $this->uid;
        if (!$uid) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $village_id = $this->request->param('village_id', 0);
        if (!$village_id) {
            return api_output_error(1001, '未获取到小区信息');
        }
        $pigcms_id = $this->request->param('pigcms_id', 0);
        if (!$pigcms_id) {
            return api_output_error(1001, '未获取到房间信息');
        }
        $visitor_invite_service = new VisitorInviteService();
        try {
            $list = $visitor_invite_service->getBaseInfo($village_id,$pigcms_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);

    }


    /**
     * 查询二维码生成纪录
     * @author:zhubaodi
     * @date_time: 2022/1/7 13:57
     */
    public function getQrcodeList(){
        $uid = $this->uid;
        if (!$uid) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $village_id = $this->request->param('village_id', 0);
        if (!$village_id) {
            return api_output_error(1001, '未获取到小区信息');
        }
        $pigcms_id = $this->request->param('pigcms_id', 0);
        if (!$pigcms_id) {
            return api_output_error(1001, '未获取到房间信息');
        }
        $visitor_invite_service = new VisitorInviteService();
        try {
            $list = $visitor_invite_service->getQrcodeList($uid,$village_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

    /**
     * 生成访客邀请二维码
     * @author:zhubaodi
     * @date_time: 2022/1/7 15:00
     */
    public function addVisitorQrcode(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', 0);
        if (!$data['village_id']) {
            return api_output_error(1001, '未获取到小区信息');
        }
        $data['pigcms_id'] = $this->request->param('pigcms_id', 0);
        if (!$data['pigcms_id']) {
            return api_output_error(1001, '未获取到房间信息');
        }
        $data['invite_date'] = $this->request->param('invite_date', 0);
        if (!$data['invite_date']) {
            return api_output_error(1001, '邀请日期不能为空');
        }
        $data['start_time'] = $this->request->param('invite_start_time', 0);
        if (!$data['start_time']) {
            return api_output_error(1001, '邀请开始时间不能为空');
        }
        $data['end_time'] = $this->request->param('invite_end_time', 0);
        if (!$data['end_time']) {
            return api_output_error(1001, '邀请结束时间不能为空');
        }
        $data['type'] = 1;
        $visitor_invite_service = new VisitorInviteService();
        try {
            $res = $visitor_invite_service->addQrcode($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 生成业主或物业工作人员开门二维码
     * @author:zhubaodi
     * @date_time: 2022/1/7 15:00
     */
    public function addQrcode(){
        $data['uid'] = $this->uid;
        if (!$data['uid']) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $data['village_id'] = $this->request->param('village_id', 0);
        if (!$data['village_id']) {
            return api_output_error(1001, '未获取到小区信息');
        }
        $data['pigcms_id'] = $this->request->param('pigcms_id', 0);
        if (!$data['pigcms_id']) {
            return api_output_error(1001, '未获取到房间信息');
        }
        $data['type'] = 2;
        $visitor_invite_service = new VisitorInviteService();
        try {
            $res = $visitor_invite_service->doorQrcode($data);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $res);
    }

    /**
     * 查询二维码详情
     * @author:zhubaodi
     * @date_time: 2022/1/8 17:54
     */
    public function getQrcodeInfo(){
        $uid = $this->uid;
        if (!$uid) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $qrcode_id = $this->request->param('qrcode_id', 0);
        if (!$qrcode_id) {
            return api_output_error(1001, '未获取到小区信息');
        }
        $visitor_invite_service = new VisitorInviteService();
        try {
            $list = $visitor_invite_service->getQrcodeInfo($uid,$qrcode_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }
    public function share_info(){
        $uid = $this->uid;
        if (!$uid) {
            return api_output_error(1002, '未登陆或者登陆失效');
        }
        $village_id = $this->request->param('village_id', 0);
        if (!$village_id) {
            return api_output_error(1001, '未获取到小区信息');
        }
        $pigcms_id = $this->request->param('pigcms_id', 0);
        if (!$pigcms_id) {
            return api_output_error(1001, '未获取到房间信息');
        }
        $visitor_invite_service = new VisitorInviteService();
        try {
            $list = $visitor_invite_service->share_info($uid,$village_id,$pigcms_id);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
        return api_output(0, $list);
    }

}

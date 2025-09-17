<?php
/**
 * 商体育健身场馆接口控制器
 */

namespace app\life_tools\controller\api;

use app\life_tools\model\db\LifeToolsMember;
use app\life_tools\model\service\LifeToolsMemberService;
use app\life_tools\model\service\LifeToolsService;

class StadiumController extends ApiBaseController
{
    /**
     * 场馆列表
     */
    public function stadiumList()
    {
        $param['cat_id']   = $this->request->param('cate_id', 0, 'intval');
        $param['long']     = $this->request->param('long', '', 'trim');
        $param['lat']      = $this->request->param('lat', '', 'trim');
        $param['page']     = $this->request->param('page', 1, 'intval');
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');
        $param['is_sports_activity'] = $this->request->param('is_sports_activity', 0, 'intval'); //是否只显示约战列表1-是0-否
        try {
            $arr = (new LifeToolsService())->getToolsList($param, 'stadium');
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 联系人列表
     */
    public function memberList()
    {
        $this->checkLogin();
        try {
            $arr = (new LifeToolsMember())->getSome(['uid' => $this->_uid, 'is_del' => 0], true, 'member_id asc');
            if (!empty($arr)) {
                $arr = $arr->toArray();
            } else {
                $arr = [];
            }
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加联系人
     */
    public function addMember()
    {
        $this->checkLogin();
        $name  = $this->request->param('name', '', 'trim');
        $phone = $this->request->param('phone', '', 'trim');
        $idcard = $this->request->param('idcard', '', 'trim');
        if (empty($name) || empty($phone) || empty($idcard)) {
            return api_output_error(1003, '姓名手机号身份证号必填');
        }
        if(!is_idcard($idcard)){
            return api_output_error(1003, '身份证号不正确');
        }
        try {
            (new LifeToolsMember())->add([
                'uid'      => $this->_uid,
                'name'     => $name,
                'phone'    => $phone,
                'idcard'   => $idcard,
                'add_time' => time()
            ]);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 编辑联系人
     */
    public function editMember()
    {
        $this->checkLogin();
        $name      = $this->request->param('name', '', 'trim');
        $phone     = $this->request->param('phone', '', 'trim');
        $member_id = $this->request->param('member_id', 0, 'intval');
        $idcard = $this->request->param('idcard', '', 'trim');
        if (empty($member_id)) {
            return api_output_error(1003, '联系人ID必传');
        }
        if (empty($name) || empty($phone)) {
            return api_output_error(1003, '姓名和手机号必填');
        }
        try {
            (new LifeToolsMember())->updateThis(['member_id' => $member_id], ['name' => $name, 'phone'=> $phone,'idcard'=>$idcard]);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 删除联系人
     */
    public function delMember()
    {
        $this->checkLogin();
        $member_ids = $this->request->param('member_ids', '', 'trim');
        try {
            (new LifeToolsMember())->updateThis([['member_id', 'in', $member_ids]], ['is_del' => 1]);
            return api_output(0, [], 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

}

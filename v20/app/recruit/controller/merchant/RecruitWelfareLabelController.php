<?php
/**
 * HR管理controller
 * Author: wangchen
 * Date Time: 2021/06/22
 */

namespace app\recruit\controller\merchant;

use app\recruit\model\service\RecruitWelfareLabelService;

class RecruitWelfareLabelController extends ApiBaseController
{

    /**
     * 列表
     */
    public function getRecruitHrList(){
        $cont = $this->request->param('cont', '', 'trim');
        $page = $this->request->param('page', 1, 'trim');
        $pageSize = $this->request->param('pageSize', 10, 'trim');
        $result = (new RecruitWelfareLabelService())->getRecruitHrList($cont, $page, $pageSize);
        return api_output(1000, $result);
    }

    /**
     * 保存
     */
    public function getRecruitHrCreate(){
        $id = $this->request->param('id', 0, 'trim');
        $params['first_name'] = $this->request->param('first_name', '', 'trim');
        $params['last_name'] = $this->request->param('last_name', '', 'trim');
        $params['sex'] = $this->request->param('sex', 0, 'trim');
        $params['phone'] = $this->request->param('phone', '', 'trim');
        $params['position'] = $this->request->param('position', '', 'trim');
        $params['wechat'] = $this->request->param('wechat', '', 'trim');
        $params['email'] = $this->request->param('email', '', 'trim');
        $params['qq'] = $this->request->param('qq', '', 'trim');
        $params['tel'] = $this->request->param('tel', '', 'trim');
        $result = (new RecruitWelfareLabelService())->getRecruitHrCreate($id, $params);
        return api_output(1000, $result);
    }

    /**
     * 单条
     */
    public function getRecruitHrInfo(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new RecruitWelfareLabelService())->getRecruitHrInfo($id);
        return api_output(1000, $result);
    }

    /**
     * 移除
     */
    public function getRecruitHrDel(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new RecruitWelfareLabelService())->getRecruitHrDel($id);
        return api_output(1000, $result);
    }
}

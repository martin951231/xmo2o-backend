<?php
/**
 * HR管理controller
 * Author: wangchen
 * Date Time: 2021/06/22
 */

namespace app\recruit\controller\merchant;

use app\recruit\model\service\NewRecruitHrService;
use think\Exception;

class NewRecruitHrController extends ApiBaseController
{

    /**
     * 列表
     */
    public function getRecruitHrList(){
        $mer_id = $this->merId;
        $cont = $this->request->param('cont', '', 'trim');
        $page = $this->request->param('page', 1, 'trim');
        $pageSize = $this->request->param('pageSize', 10, 'trim');
        $result = (new NewRecruitHrService())->getRecruitHrList($cont, $mer_id, $page, $pageSize);
        return api_output(1000, $result);
    }

    /**
     * 保存
     */
    public function getRecruitHrCreate(){
        try {
            $id = $this->request->param('id', 0, 'trim');
            $params['first_name'] = $this->request->param('first_name', '', 'trim');
            $params['last_name'] = $this->request->param('last_name', '', 'trim');
            $params['name'] =  $params['first_name'].$params['last_name'];
            $params['sex'] = $this->request->param('sex', 0, 'trim');
            $params['phone'] = $this->request->param('phone', '', 'trim');
            $params['position'] = $this->request->param('position', '', 'trim');
            $params['wechat'] = $this->request->param('wechat', '', 'trim');
            $params['email'] = $this->request->param('email', '', 'trim');
            $params['qq'] = $this->request->param('qq', '', 'trim');
            $params['tel'] = $this->request->param('tel', '', 'trim');
            $params['mer_id'] = $this->merId;
            if(empty($params['first_name'])){
                return api_output_error(1001, L_('姓不能为空'));
            }
            if(empty($params['last_name'])){
                return api_output_error(1001, L_('名不能为空'));
            }
            if(empty($params['position'])){
                return api_output_error(1001, L_('职位不能为空'));
            }
            $result = (new NewRecruitHrService())->getRecruitHrCreate($id, $params);
            return api_output(1000, $result);
        } catch (Exception $e) {
            return api_output(1003, [], $e->getMessage());
        }
    }

    /**
     * 单条
     */
    public function getRecruitHrInfo(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new NewRecruitHrService())->getRecruitHrInfo($id);
        return api_output(1000, $result);
    }

    /**
     * 移除
     */
    public function getRecruitHrDel(){
        $id = $this->request->param('id', 0, 'trim');
        $result = (new NewRecruitHrService())->getRecruitHrDel($id);
        return api_output(1000, $result);
    }
}

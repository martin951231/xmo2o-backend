<?php


namespace app\warn\controller\merchant;


use app\merchant\controller\merchant\AuthBaseController;
use app\warn\model\service\WarnService;

class NoticeController extends AuthBaseController
{

    /**
     * 商家消息通知列表
     * @return \think\response\Json
     */
    public function getNoticeList(){
        $params['page'] = $this->request->param('page',1,'trim,intval');
        $params['pageSize'] = $this->request->param('pageSize',10,'trim,intval');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new WarnService())->getNoticeList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 消息已读
     */
    public function read()
    {
        $params['id'] = $this->request->param('id');
        $params['is_all'] = $this->request->param('is_all');
        $params['mer_id'] = $this->merId;
        try {
            $data = (new WarnService())->read($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }

    /**
     * 未读消息数量
     */
    public function unreadNum()
    {
        $params['mer_id'] = $this->merId;
        try {
            $data = (new WarnService())->unreadNum($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
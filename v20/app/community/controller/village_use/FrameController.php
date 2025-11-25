<?php


namespace app\community\controller\village_use;

use app\community\controller\CommunityBaseController;
use app\community\model\service\PileUserService;

class FrameController extends CommunityBaseController
{
    /**
     * 获取下行指令
     * @author lijie
     * @date_time 2021/05/27
     * @return \json
     */
    public function getFrame()
    {
        $service_pile_user = new PileUserService();
        $data = $service_pile_user->tcp_download();
        return api_output(0,$data);
    }

    /**
     * 添加上行指令
     * @author lijie
     * @date_time 2021/05/27
     * @return \json
     */
    public function addFrame()
    {
        $data = $this->request->post();
        $service_pile_user = new PileUserService();
        $res = $service_pile_user->addUploadFrame($data);
        return api_output(0,$res);
    }
}
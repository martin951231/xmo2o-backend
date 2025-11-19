<?php
/**
 * 后台机器人
 * author by hengtingmei
 */
namespace app\common\controller\platform;
use app\common\model\service\SystemRobotService;

class RobotController extends AuthBaseController
{

    /**
     * 获得机器人列表
     * @author: 衡婷妹
     * @date: 2020/12/23
     */
    public function getRobotList()
    {
        $param = $this->request->param();
        $result = (new SystemRobotService())->getRobotList($param);

        return api_output(0, $result);
    }

    /**
     * 获得随机姓名
     * @author: 衡婷妹
     * @date: 2020/12/24
     */
    public function getRandName()
    {
        $param = $this->request->param();
        $result = (new SystemRobotService())->getRandName($param);

        return api_output(0, $result);
    }


    /**
     * 添加编辑机器人
     * @author: 衡婷妹
     * @date: 2020/12/23
     */
    public function editRobot()
    {
        $param = $this->request->param();
        $result = (new SystemRobotService())->editRobot($param);

        return api_output(0, $result);
    }

    /**
     * 删除机器人
     * @author: 衡婷妹
     * @date: 2020/12/23
     */
    public function delRobot()
    {
        $param = $this->request->param();
        $result = (new SystemRobotService())->delRobot($param);

        return api_output(0, $result);
    }

    /**
     * 机器人详情
     * @author: 衡婷妹
     * @date: 2020/12/23
     */
    public function getRobotDetail()
    {
        $param = $this->request->param();
        $result = (new SystemRobotService())->getRobotDetail($param);

        return api_output(0, $result);
    }
}
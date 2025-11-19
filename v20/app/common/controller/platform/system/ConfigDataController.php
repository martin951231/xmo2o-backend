<?php

/**
 * 后台配置项
 * Author: 衡婷妹
 * Date Time: 2021/03/29 09:23
 */
namespace app\common\controller\platform\system;

use app\common\controller\CommonBaseController;
use app\common\model\service\ConfigDataService;

class ConfigDataController extends CommonBaseController
{

    /**
     * 系统后台获取某个分组的数据
     * @author: 衡婷妹
     * @date: 2020/03/29 09:33
     * @return \json
     */
    public function index() {
        $param = $this->request->param();
        $configService = new ConfigDataService();
        $returnArr = $configService->getDataList($param);
        return api_output(0, $returnArr);
    }

    /**
     * 提交表单
     * User: 衡婷妹
     * Date: 2021/03/29
     * @return \json
     */
    public function amend() {
        $param = $this->request->param();
        $configService = new ConfigDataService();
        $res =  $configService->amend($param);
        return api_output(1000, ['msg'=>'修改成功！']);

    }
}

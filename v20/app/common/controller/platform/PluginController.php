<?php
/**
 * 后台应用中心
 * author by hengtingmei
 */
namespace app\common\controller\platform;

use app\common\model\service\IndexService;
use app\common\model\service\plugin\HotMenuService;
use app\common\model\service\plugin\PluginService;

class PluginController extends AuthBaseController {
    public function initialize()
    {
        parent::initialize();
    }

    /**
	 * author by 衡婷妹
     * 获得应用中心所有菜单
     * return :object
     */
	public function getAllMenuTree(){
		$param = $this->request->param();
        $param['from'] = 'system';
        $returnArr = (new PluginService())->getAllMenuTree($param,$this->systemUser);
		return api_output(0, $returnArr);
	}

    /**
     * author by 衡婷妹
     * 获得应用中心常用菜单
     * return :object
     */
    public function getHotMenu(){
        $returnArr = (new HotMenuService())->getList('system',$this->systemUser);
        return api_output(0, $returnArr);
    }

    /**
     * author by 衡婷妹
     * 编辑应用中心常用菜单
     * return :object
     */
    public function editHotMenu(){
        $param = $this->request->param();
        $returnArr = (new HotMenuService())->editHotMenu($param);
        return api_output(0, $returnArr);
    }

}
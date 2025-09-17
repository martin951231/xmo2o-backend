<?php
/**
 * 后台菜单管理
 * author by hengtingmei
 */
namespace app\common\controller\platform;

use app\common\controller\platform\AuthBaseController;
use app\common\model\service\admin_user\SystemMenuService;
class SystemMenuController extends AuthBaseController{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * desc: 返回网站菜单信息,用于菜单展示
     * return :array
     */
    public function menuList(){
        
        // 菜单
        $systemMenuService = new SystemMenuService();
        $systemMenu = $systemMenuService->getShowMenuList($this->systemUser);
        $systemMenu = $systemMenuService->formartMenuList($systemMenu,$this->systemUser);        
        $returnArr['systemMenu'] = $systemMenu;
        return api_output(0, $returnArr);
    }

    /**
     * desc: 返回网站左侧信息
     * return :array
     */
    public function getRightMenu(){
        $param = [];
        $returnArr = (new SystemMenuService())->getRightMenu($param,$this->systemUser);
        return api_output(0, $returnArr);
    }
}
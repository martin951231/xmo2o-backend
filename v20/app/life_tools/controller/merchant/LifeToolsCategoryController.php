<?php
namespace app\life_tools\controller\merchant;

use app\merchant\controller\merchant\AuthBaseController;
use app\life_tools\model\service\LifeToolsCategoryService;

/**
 * 分类
 */
class LifeToolsCategoryController extends AuthBaseController
{
    /**
     * 获取分类列表
     */
    public function getCategoryList()
    {
        $params = []; 
        $params['type'] = $this->request->post('type', 'stadium', 'trim');
        try {
            $data = (new LifeToolsCategoryService)->getCategoryList($params);
        } catch (\Exception $e) {
            return api_output_error(1001, $e->getMessage());
        }
        return api_output(0, $data);
    }
}
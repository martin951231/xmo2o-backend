<?php

namespace app\classify\controller\platform;

use app\classify\model\service\ClassifyMenu as ClassifyMenuService;
use app\classify\validate\ClassifyMenu as ClassifyMenuValidate;

class ClassifyMenuController extends ClassifyBaseController
{
    public function initialize()
    {
        parent::initialize();
        $this->classifyValidate = ClassifyMenuValidate::class;
    }

    /**
     * 分类套餐项目
     * @return \think\response\Json
     */
    public function saveClassifyMenu()
    {
        $params = $this->validateParameter();
        
        foreach ($params['classify_detail_list'] as $v){
            $this->validateParameter('classify_detail_list',$v);
        }
        
        $data = app(ClassifyMenuService::class)->saveClassifyMenu($params);

        return api_output(self::STATUS_OK, $data);
    }

    public function delClassifyMenu()
    {
        $params = $this->validateParameter();

        $data = app(ClassifyMenuService::class)->delClassifyMenu($params['menu_id']);

        return api_output(self::STATUS_OK, $data);
    }
    
    /**
     * 分类列表
     * @return \think\response\Json
     */
    public function classifyMenuList()
    {
        $params = input('post.');
        $data = app(ClassifyMenuService::class)->classifyMenuList($params);
        return api_output(self::STATUS_OK, $data);
    }

    public function classifyCategory()
    {
        $data = app(ClassifyMenuService::class)->classifyCategory();
        return api_output(self::STATUS_OK, $data);
    }
}
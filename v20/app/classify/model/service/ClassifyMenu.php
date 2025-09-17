<?php

namespace app\classify\model\service;

use app\classify\model\db\ClassifyMenu as ClassifyMenuModel;
use app\classify\model\db\ClassifyMenuDetail;
use app\classify\model\db\ClassifyMenuDetail as ClassifyMenuDetailModel;
use app\common\model\db\ClassifyCategory;
use think\facade\Db;

class ClassifyMenu
{
    private $pageSize;
    public function __construct()
    {
        $this->pageSize = min(input('page_size'),100);
    }
    /**
     * 分类套餐项目
     * @param array $params
     * @return bool
     */
    public function saveClassifyMenu(array $params)
    {
        try {
            Db::transaction(function () use ($params) {
                if (!empty($params['menu_id'])) {
                    $classify = ClassifyMenuModel::where(['id' => $params['menu_id'], 'delete_time' => 0])->find();
                } else {
                    $classify = new ClassifyMenuModel();
                }
                foreach ($params['classify_ids'] as &$v){
                   $classifyIdsArr = explode('-',$v);
                   $v = $classifyIdsArr[1];
                   
                   if(!empty($params['menu_id'])){
                       $menu = ClassifyMenuModel::where("FIND_IN_SET({$v}, classify_ids)")
                           ->where('id','<>', $params['menu_id'])
                           ->where('delete_time' , 0)
                           ->find();
                   }else{
                       $menu = ClassifyMenuModel::where("FIND_IN_SET({$v}, classify_ids)")->where('delete_time' , 0)->find();
                   }
                   if($menu){
                       $cate = ClassifyCategory::where('cid', $v)->find();
                       throw_exception('【'.$cate['cat_name'].'】分类已存在套餐，不可设置多个套餐');
                   }
                }
                $classify->setAttrs($params);
                $classify->save();

                $params['menu_id'] = $classify->id;
                ClassifyMenuDetailModel::where(['menu_id' => $classify->id, 'delete_time' => 0])->update(['delete_time' => time()]);

                foreach ($params['classify_detail_list'] as $v) {
                    $classifyDetail = NULL;
                    if (!empty($v['id'])) {
                        $classifyDetail = ClassifyMenuDetailModel::where(['menu_id' => $classify->id, 'id' => $v['id']])->find();
                    }
                    empty($classifyDetail) && $classifyDetail = new ClassifyMenuDetailModel();
                    $v['menu_id'] = $classify->id;
                    $v['delete_time'] = 0;
                    $classifyDetail->setAttrs($v);
                    $classifyDetail->save();
                }
            });
        } catch (\Exception $e) {
            throw_exception($e->getMessage());
        }

        return true;
    }

    /**
     * 删除套餐项目
     * @param $menuID
     */
    public function delClassifyMenu($menuID)
    {
        try{
            Db::transaction(function ()use($menuID){
                $classify = ClassifyMenuModel::where(['id' => $menuID, 'delete_time' => 0])->find();
                if(empty($classify)){
                    throw_exception('分类套餐类目不存在');
                }
                $classify->delete_time = time();

                ClassifyMenuDetailModel::where(['menu_id' => $menuID, 'delete_time' => 0])->update(['delete_time' => time()]);

                $classify->save();
            });
        }catch (\Exception $e){
            throw_exception($e->getMessage());
        }
        
        return true;
    }
    
    /**
     * 套餐列表
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function classifyMenuList(array $params = [])
    {
        $classifyList = ClassifyMenuModel::where(['delete_time' => 0])
            ->withSearch(['classify_id'], $params)
            ->field('id, name, classify_ids, create_time')
            ->append(['classify'])
            ->with(['classify_detail_list' => function($query){
                $query->field('id, menu_id, name, price, effective_days');
            }]);
        if(!empty($params['classify_id'])){
            $classifyList = $classifyList->find();
        }else{
            $classifyList = $classifyList->paginate($this->pageSize)
                ->each(function ($item){
                    $classifyCate = $item->classify->toArray();
                    $item->classify_id_arr = array_column($classifyCate,'cate_id');
                });
        }
            
        return $classifyList;
    }

    public function classifyCategory()
    {
        $categoryList = [];
        $categoryLevelList = app(ClassifyCategory::class)->classifyCategory(1);
        foreach ($categoryLevelList as $v){
            $children = [];
            $categoryLevel2List = app(ClassifyCategory::class)->classifyCategory(2,['fcid' => $v['cid']]);
            foreach ($categoryLevel2List as $vv){
                array_push($children, [
                    'title' => $vv['cat_name'],
                    'key' => "{$v['cid']}-{$vv['cid']}"
                ]);
            }
            if(!empty($children)){
                array_push($categoryList, [
                    'title' => $v['cat_name'],
                    'key' => $v['cid'],
                    'children' => $children
                ]);
            }
        }
        
        return $categoryList;
    }
}
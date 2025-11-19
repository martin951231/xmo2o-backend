<?php
/**
 * 课程、体育馆、景区 分类service
 * @date 2021-12-20 
 */

namespace app\life_tools\model\service;

use app\life_tools\model\db\LifeTools;
use app\life_tools\model\db\LifeToolsCategory;

class LifeToolsCategoryService
{
    public $lifeToolsCategoryModel = null; 
    public $lifeToolModel = null; 
    public function __construct()
    {
        $this->lifeToolsCategoryModel = new LifeToolsCategory();
        $this->lifeToolModel = new LifeTools();
         
    }

    /**
     *获取分类列表 
     */
    public function getCategoryList($params)
    {
        $type = $params['type'] ?? '';
        $condition = [];
        $condition[] = ['is_del', '=', 0];
        $condition[] = ['type', '=', $type];
        $data = $this->lifeToolsCategoryModel->where($condition)->order('sort DESC')->select();

        foreach($data as $cat){
            $cat->add_time = date('Y-m-d H:i:s', $cat->add_time);
        }
        return $data;
    }

    /**
     * 获得分类详情
     * @param $param
     * @return array
     */
    public function getDetail($param = [])
    {
        $catId = $param['cat_id'] ?? 0;
        if(empty($catId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询数据
        $where = [
            'cat_id' => $catId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在或已删除'), 1001);
        }
        return $detail;
    }

    /**
     * 添加编辑分类
     * @param $param
     * @return array
     */
    public function addOrEdit($param = [])
    {
        $catId = $param['cat_id'] ?? 0;

        $data['sort'] = $param['sort'] ?? 0;// 排序值
        $data['cat_name'] = $param['cat_name'] ?? '';// 标题
        $data['type'] = $param['type'] ?? '';// 类型：scenic-景区，stadium-场馆，course-课程

        if(empty($data['cat_name']) || empty($data['type'])){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查重
        $checkWhere = [
            ['cat_name', '=', $data['cat_name']],
            ['type', '=', $data['type']],
            ['is_del', '=', 0]
        ];
        if($catId){
            $checkWhere[] = ['cat_id', '<>', $catId];
        }
        $check = $this->getOne($checkWhere);
        if($check){
            throw new \think\Exception(L_('分类名不能重复'), 1003);
        }

        $msg = L_('新增');
        if($catId){// 编辑
            $msg = L_('编辑');
            $where = [
                'cat_id' => $catId,
                'is_del' => 0
            ];
            $detail = $this->getOne($where);
            if(empty($detail)){
                throw new \think\Exception(L_('数据不存在'), 1001);
            }
            $res = $this->updateThis($where, $data);
        }else{
            $res = $catId = $this->add($data);
        }

        if($res === false){
            throw new \think\Exception($msg.L_('失败'), 1003);
        }

        $returnArr['msg'] = $msg.L_('成功');
        return  $returnArr;
    }

    /**
     * 编辑分类排序值
     * @param $param
     * @return array
     */
    public function editSort($param = [])
    {
        $catId = $param['cat_id'] ?? 0;
        $data['sort'] = $param['sort'] ?? 0;// 排序值
       
        $where = [
            'cat_id' => $catId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在'), 1001);
        }

        $res = $this->updateThis($where, $data);

        if($res === false){
            throw new \think\Exception(L_('操作失败'), 1003);
        }

        $returnArr['msg'] = L_('编辑成功');
        return $returnArr;
    }
    
    /**
     * 删除分类
     * @param $param
     * @return array
     */
    public function del($param = [])
    {
        
        $catId = $param['cat_id'] ?? 0;
        if(empty($catId)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }

        // 查询原来数据
        $where = [
            'cat_id' => $catId,
            'is_del' => 0
        ];
        $detail = $this->getOne($where);
        if(empty($detail)){
            throw new \think\Exception(L_('数据不存在或已删除'), 1001);
        }

        // 假删除
        $data = [
            'is_del' => 1
        ];
        $res = $this->updateThis($where, $data);     
        if($res === false){
            throw new \think\Exception(L_('删除失败'), 1003);
        }

        $returnArr['msg'] = L_('删除成功');
        return $returnArr;
    }
    
    /**
     *插入一条数据
     * @param array $data 
     * @return int|bool
     */
    public function add($data){
        if(empty($data)){
            return false;
        }

        $data['add_time'] = time();

        $id = $this->lifeToolsCategoryModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *获取一条条数据
     * @param array $where 
     * @return array
     */
    public function getOne($where){
        $result = $this->lifeToolsCategoryModel->getOne($where);
        if(empty($result)) return [];
        return $result->toArray();
    }

    /**
     *获取多条条数据
     * @param array $where 
     * @return array
     */
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->lifeToolsCategoryModel->getSome($where,$field, $order, $page,$limit);
        if(empty($result)) return [];
        return $result->toArray();
    }  

    /**
     *获取数据总数
     * @param array $where 
     * @return array
     */
    public function getCount($where = []){
        $result = $this->lifeToolsCategoryModel->getCount($where);
        if(empty($result)) return 0;
        return $result;
    }

    /**
     * 更新数据
     * @param $where array
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data) {
        if(empty($where) || empty($data)){
            return false;
        }

        $result = $this->lifeToolsCategoryModel->updateThis($where, $data);
        if($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * 体育健身课程/场馆/活动/咨讯-分类列表
     */
    public function getCateList($type, $uid = 0)
    {
        switch ($type) {
            case 1: //课程
            case 2: //场馆
                $data = $this->lifeToolsCategoryModel->getSome(['type' => $type == 1 ? 'course' : 'stadium', 'is_del' => 0], 'cat_id as value,cat_name as title', 'sort DESC');
                if (!empty($data)) {
                    $data = $data->toArray();
                } else {
                    $data = [];
                }
                break;
            case 3: //活动
            case 4: //咨讯
                $data = [
                    [
                        'value' => 1,
                        'title' => '人气排行'
                    ]
                ];
                break;
            case 5: //团体票
                $data = $this->lifeToolsCategoryModel
                             ->alias('c')
                             ->field('distinct(c.cat_id) as value,c.cat_name as title')
                             ->join($this->lifeToolsCategoryModel->dbPrefix().'life_tools l ', 'l.cat_id = c.cat_id')
                             ->join($this->lifeToolsCategoryModel->dbPrefix().'life_tools_group_ticket t ', 'l.tools_id = t.tools_id')
                             ->where([['c.type', '=', 'scenic'], ['c.is_del', '=', 0]])
                             ->order('c.sort DESC,c.cat_id DESC')
                             ->select();
                if (!empty($data)) {
                    $data = $data->toArray();

                } else {
                    $data = [];
                }
                break;
            case 6: //景区
                $data = $this->lifeToolsCategoryModel
                            ->field('distinct(cat_id) as value,cat_name as title')
                            ->where('type', 'scenic')
                            ->where('is_del', 0)
                            ->order('sort DESC')
                            ->select()
                            ->toArray();
                foreach($data as $key => $val){
                    $condition = [];
                    $condition[] = ['l.type', '=', 'scenic'];
                    $condition[] = ['l.is_del', '=', 0];
                    $condition[] = ['t.status', '=', 1];
                    $condition[] = ['t.is_del', '=', 0];
                    $condition[] = ['ubm.uid', '=', $uid];
                    $condition[] = ['l.cat_id', '=', $val['value']];
                    $num = $this->lifeToolModel->getShareScenicNum($condition);
                    if($num <= 0){
                        unset($data[$key]);
                    }
                }
                $data = array_values($data);
                
                break;
            default:
                throw new \think\Exception(L_('参数有误'), 1003);
                break;
        }
        $arr = [
            [
                'value' => 0,
                'title' => '全部'
            ]
        ];
        $data = array_merge($arr, $data);
        return $data;
    }

    
}
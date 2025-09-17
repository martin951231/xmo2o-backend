<?php
/**
 * 图文管理分类标签Service
 * Author: wangchen
 * Date Time: 2021/5/26
 */

namespace app\atlas\model\service;

use app\atlas\model\db\AtlasSpecial;

class AtlasSpecialService {
    public $atlasSpecialModel = null;
    public function __construct()
    {
        $this->atlasSpecialModel = new AtlasSpecial();
    }

    /**
     * 获得图文管理分类标签列表
     * @param $where array 条件
     * @return array
     */
    public function getAtlasSpecialList($cat_id){
        $order = 'sort DESC, id DESC';
        $where = array('cat_id'=>$cat_id);
        $return = $this->atlasSpecialModel->getAtlasSpecialList($where, $order);
        return $return;
    }

    /**
     * 获得图文管理一条分类标签数据
     */
    public function getAtlasSpecialInfo($id){
        $where = array('id'=>$id);
        $result = $this->atlasSpecialModel->getAtlasSpecialInfo($where);
        return $result;
    }

    /**
     * 图文管理分类标签修改/添加
     */
    public function getAtlasSpecialCreate($id, $cat_id, $name, $sort, $type_id, $content){
        $result = $this->atlasSpecialModel->getAtlasSpecialCreate($id, $cat_id, $name, $sort, $type_id, $content);
        return $result;
    }
    
    /**
     * 图文管理分类标签删除
     */
    public function getAtlasSpecialDel($id){
        $result = $this->atlasSpecialModel->getAtlasSpecialDel($id);
        return $result;
    }
}
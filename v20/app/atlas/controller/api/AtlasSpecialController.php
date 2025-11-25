<?php
/**
 * 图文管理分类标签controller
 * Author: wangchen
 * Date Time: 2021/05/26
 */

namespace app\atlas\controller\api;

use app\atlas\model\service\AtlasSpecialService;

class AtlasSpecialController extends ApiBaseController
{
    /**
     * 获得图文管理分类标签列表
     */
    public function getAtlasSpecialList(){
        $cat_id = $this->request->param("cat_id", 0, "intval");
        $result = (new AtlasSpecialService())->getAtlasSpecialList($cat_id);
        return api_output(1000, $result);
    }

    /**
     * 获得图文管理一条分类标签数据
     */
    public function getAtlasSpecialInfo(){
        $id = $this->request->param("id", 0, "intval");
        if(!$id){
            $res = array(
                'id' => 0,
                'name' => '',
                'sort' => '',
                'content' => '',
                'type_id' => 0,
            );
            return api_output(1000, $res);
        }
        $result = (new AtlasSpecialService())->getAtlasSpecialInfo($id);
        return api_output(1000, $result);
    }

    /**
     * 图文管理分类标签修改/添加
     */
    public function getAtlasSpecialCreate(){
        $id = $this->request->param("id", 0, "intval");
        $cat_id = $this->request->param("cat_id", 0, "intval");
        $name = $this->request->param("name");
        $sort = $this->request->param("sort", 0, "intval");
        $type_id = $this->request->param("type_id", 0, "intval");
        $content = $this->request->param("content");
        $result = (new AtlasSpecialService())->getAtlasSpecialCreate($id, $cat_id, $name, $sort, $type_id, $content);
        return api_output(1000, $result);
    }

    /**
     * 图文管理分类标签删除
     */
    public function getAtlasSpecialDel(){
        $id = $this->request->param("id", 0, "intval");
        $result = (new AtlasSpecialService())->getAtlasSpecialDel($id);
        return api_output(1000, $result);
    }
}

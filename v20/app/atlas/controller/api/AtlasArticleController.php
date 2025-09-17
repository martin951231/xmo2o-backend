<?php
/**
 * 图文管理controller
 * Author: wangchen
 * Date Time: 2021/05/21
 */

namespace app\atlas\controller\api;

use app\atlas\model\service\AtlasArticleService;
use app\atlas\model\service\AtlasCategoryService;
use app\atlas\model\service\AtlasArticleCollectService;
use app\atlas\model\service\AtlasSpecialService;

class AtlasArticleController extends ApiBaseController
{
    /**
     * 图文管理列表
     */
    public function getAtlasArticleList(){
        $page = $this->request->param('page',1,'trim');
        $pageSize = $this->request->param('pageSize',20,'trim');
        $pageSize = $pageSize-1;
        $name = $this->request->param('name',0,'trim');
        $edit_time = $this->request->param('edit_time',0,'trim');
        $cat_id = $this->request->param('cat_id',0,'trim');
        $cat_fid = $this->request->param('cat_fid',0,'trim');
        $where = [['status','=',0]];
        if(!empty($name)){
            $where = [['status','=',0]];
        }
        if (isset($name) && !empty($name)) {
            array_push($where, ['title', 'like', '%'.$name.'%']);
        }
        if (isset($edit_time) && !empty($edit_time)) {
            $edit_time = explode("T", $edit_time)[0];
            $start_time = strtotime($edit_time." 00:00:00");
            $end_time = strtotime($edit_time." 23:59:59");
            array_push($where, ['edit_time', '>=', $start_time]);
            array_push($where, ['edit_time', '<=', $end_time]);
        }
        if(!empty($cat_id)){
            array_push($where, ['cat_id', '=', $cat_id]);
        }elseif(!empty($cat_fid)){
            $cat_list = (new AtlasCategoryService()) ->getAtlasArticleSecond($cat_fid);
            if($cat_list){
                $catIds = [];
                foreach($cat_list as $v){
                    $catIds[] = $v['cat_id'];
                }
                array_push($where, ['cat_id', 'in', $catIds]);
            }else{
                array_push($where, ['cat_id', 'in', '9999999999']);
            }
        }
        try {
            $arr = (new AtlasArticleService())->getAtlasArticleList($where,$page,$pageSize);
            // 设置第一个为新增图文
            $catList = [['name'=>'新增图文']];
            $arr['list'] = array_merge($catList,$arr['list']);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 图文管理保存
     */
    public function getAtlasArticleCreate()
    {
        $param['id'] = $this->request->param("id", "0", "intval");
        $cat_ids = $this->request->param("cat_id", "0", "intval");
        $param['title'] = $this->request->param("title", "", "trim");
        $param['description'] = $this->request->param("description", "", "trim");
        $param['content'] = $this->request->param("content", "", "trim");
        $param['edit_time'] = $param['add_time'] = time();   
        $img = $this->request->param("pic");

        if(is_array($cat_ids)){
            $cat_id = count($cat_ids)>1 ? $cat_ids[1] : 0 ;
        }else{
            $cat_id = $cat_ids;
        }
        $param['cat_id'] = $cat_id;
        if(!empty($cat_id)){
            $owner = [];
            $optionlist =(new AtlasSpecialService())->getAtlasSpecialList($cat_id);
            if($optionlist){
                foreach($optionlist as $k=>$v){
                    $owners = $this->request->param("owner".$v['id'], "0", "intval");
                    if($owners){
                        $owner[$k]['id'] = $v['id'];
                        $owner[$k]['owner'] = $owners;
                    }
                }
            }
            $param['owner'] = $owner;
        }

        if($param['content']){
            $param['content'] = serialize($param['content']);
        }

        $param['pic'] = '';
        if(!empty($img)){
            foreach($img as $k=>$v){
                if($k==0){
                    $param['pic'] .= $v;
                }else{
                    $param['pic'] .= ';'.$v;
                }
            }
        }
        if (empty($param['pic'])) {
            return api_output(1001, [], L_('请上传图片'));
        }
        // 获得列表
        $list =(new AtlasArticleService())->getAtlasArticleCreate($param);

        return api_output(0, $list);
    }  

    /**
     * 获得一条图文管理
     */
    public function getAtlasArticleDetail(){
        $id = $this->request->param('id', '0', 'intval');
        $result = (new AtlasArticleService())->getAtlasArticleDetail($id);
        return api_output(1000, $result);

    }

    /**
     * 图文管理分类
     */
    public function getAtlasArticleClass(){
        $result = (new AtlasArticleService())->getAtlasArticleClass();
        return api_output(1000, $result);
    }

    /**
     * 图文管理分类标签
     */
    public function getAtlasArticleOption(){
        $value = $this->request->param("value", "", "trim");
        $id = $this->request->param("id", 0, "trim");
        if(count($value) < 2){
            return api_output(1000, []);
        }
        $result = (new AtlasArticleService())->getAtlasArticleOption($value[1],$id);
        return api_output(1000, $result);
    }

    /**
     * 图文管理删除
     */
    public function getAtlasArticleDel(){
        $id = $this->request->param('id', '0', 'intval');
        if ($id < 1) {
            return api_output(1001, [], '缺少参数');
        }
        $result = (new AtlasArticleService())->getAtlasArticleDel($id);
        return api_output(1000, $result);
    }

    /**
     * 获得图文管理详情
     */
    public function atlasArticleDetail(){
        $id = $this->request->param('id',0,'trim');
        if ($id < 1) {
            return api_output(1001, [], '缺少参数');
        }
        if(empty($this->userInfo)){
            $uid = 0;
        }else{
            $uid = $this->userInfo;
        }
        $result = (new AtlasArticleService())->atlasArticleDetail($id,$uid);
        return api_output(1000, $result);

    }

    /**
     * 图文管理列表
     */
    public function atlasArticleList(){
        $params['cat_id'] = $this->request->param("cat_id", "0", "intval");
        $params['page'] = $this->request->param("page", "1", "intval");
        $params['pageSize'] = $this->request->param("pageSize", "10", "intval");
        $params['special'] = $this->request->param("special", "", "trim");
        try {
            $arr = (new AtlasArticleService())->atlasArticleList($params);
            return api_output(0, $arr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 收藏 取消收藏
     */
    public function collectArticle(){
        if(empty($this->userInfo)){
            throw new \think\Exception(L_('未登录'),1002);
        }
        $articleId = $this->request->param('article_id', '0', 'intval');

        $result = (new AtlasArticleCollectService())->collectArticle($this->userInfo['uid'],$articleId);
        return api_output(1000, $result);
    }
}

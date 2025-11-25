<?php
/**
 * 图文管理Service
 * Author: wangchen
 * Date Time: 2021/5/21
 */

namespace app\atlas\model\service;

use app\atlas\model\db\AtlasArticle;
use app\atlas\model\db\AtlasCategory;
use app\atlas\model\db\AtlasArticleOption;
use app\atlas\model\db\AtlasArticleCollect;

class AtlasArticleService {

    /**
     *图文管理列表
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function getAtlasArticleList($where = [], $page=0,$pageSize=0){
        $list = (new AtlasArticle())->getAtlasArticleList($where, $page, $pageSize);
        if (!empty($list)) {
            return $list;
        } else {
            return [];
        }
    }

    /**
     * 图文管理保存
     * @param $param
     * @return array
     */
    public function getAtlasArticleCreate($param) {
        $id = isset($param['id']) ? $param['id'] : '0';
        if(empty($param['title'])){
            throw new \think\Exception("请输入搜索词名称！",1005);
        }
        if(empty($param['content'])){
            throw new \think\Exception("内容不能为空！",1005);
        }
        $params = array(
            'cat_id' => $param['cat_id'],
            'title' => $param['title'],
            'description' => $param['description'],
            'content' => $param['content'],
            'pic' => $param['pic'],
            'edit_time' => $param['edit_time'],
            'add_time' => $param['add_time'],
        );
        $owner = empty($param['owner']) ? [] : $param['owner'];
        (new AtlasArticleOption())->getAtlasArticleOptionCreate($id, $owner);
        $res = (new AtlasArticle())->getAtlasArticleCreate($id, $params);
        return $res; 
    } 

    
    /**
    * 获取一条图文管理
    * @param $where array 条件
    * @return array
    */
    public function getAtlasArticleDetail($id){
        if(empty($id)){
            throw new \think\Exception(L_('缺少参数'), 1001);
        }
        $where = ['id' => $id,];
        $returnArr = (new AtlasArticle())->getAtlasArticleDetail($where);
        
        if(empty($returnArr)){
            return [];
        }
   
        return $returnArr;
    }

    /**
     * 图文管理获取分类
     */
    public function getAtlasArticleClass()
    {
        $list = (new AtlasArticle())->getAtlasArticleClass();
        return $list;
    }

    /**
     * 图文管理获取分类标签
     */
    public function getAtlasArticleOption($cat_id,$id)
    {
        $list = (new AtlasArticle())->getAtlasArticleOption($cat_id,$id);
        return $list;
    }

    /**
     * 图文管理删除
     */
    public function getAtlasArticleDel($id)
    {
        $list = (new AtlasArticle())->getAtlasArticleDel($id);
        return $list;
    }

    /**
     * 获得图集详情
     * @param $where array 条件
     * @return array
     */
    public function atlasArticleDetail($id,$uid){
        // 图集文章信息
        $where = ['id'=>$id,'status'=>0];
        $result = (new AtlasArticle())->atlasArticleDetail($where);
        $result['edit_time'] = date('Y-m-d H:i:s',$result['edit_time']);
        if($result['content']){
            $content = unserialize($result['content']);
            $result['content'] = str_replace('<video','<video autoplay preload="auto" ',replace_file_domain_content_img($content));
        }
        if(empty($result)){
            return [];
        }
        // 图集分类信息
        $res = (new AtlasCategory())->field('*')->where(['cat_id'=>$result['cat_id'],'cat_status'=>1])->find();
        $result['cat_name'] = empty($res['cat_name']) ? '' : $res['cat_name'];
        $result['cat_pic'] = empty($res['cat_pic']) ? '' : replace_file_domain($res['cat_pic']);
        $result['pic'] = empty($result['pic']) ? '' : replace_file_domain($result['pic']);

        // 图集标签信息
        $option = (new AtlasArticleOption())->atlasArticleOptionList($id);
        $result['option_list'] = $option;

        // 是否点赞
        $result['is_collect'] = false;
        if($uid){
            $where2 = ['uid'=>$uid,'article_id'=>$id,'status'=>0];
            $collect = (new AtlasArticleCollect())->getOptionOne($where2);
            if($collect){
                $result['is_collect'] = true;
            }
        }
        // 增加查看数
        (new AtlasArticle())->atlasArticleViewsNum($id);

        return $result;
    }  

    /**
     *图文管理列表
     * @param array $where 
     * @param string $field 
     * @param array $order 
     * @param int $page 
     * @param int $limit 
     * @return array
     */
    public function atlasArticleList($params){
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $where = [['a.status','=','0']];
        if($params['cat_id']){
            array_push($where, ['a.cat_id', '=', $params['cat_id']]);
        }
        $type = 0;
        if($params['special']){
            $type = 1;
        }
        $field = 'a.id,a.cat_id,a.title,a.pic,a.description,a.content,a.views_num,a.edit_time';
        $order = 'a.sort DESC, a.id DESC';
        $list = (new AtlasArticle())->atlasArticleList($page, $pageSize,$where,$field,$order,$type,$params['special']);
        if (!empty($list)) {
            foreach($list['list'] as $k=>$v){
                $list['list'][$k]['edit_time'] = date('Y-m-d H:i:s',$v['edit_time']);
                $list['list'][$k]['pic'] = replace_file_domain($v['pic']);
            }
            return $list;
        } else {
            return [];
        }
    }

}
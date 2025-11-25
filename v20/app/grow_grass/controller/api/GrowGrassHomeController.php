<?php

/**
 * 种草前端首页
 */
namespace app\grow_grass\controller\api;

use app\grow_grass\model\service\GrowGrassArticleService;
use app\grow_grass\model\service\GrowGrassHomeService;

class GrowGrassHomeController extends ApiBaseController
{
    /**
     * @return \json
     * 分类栏
     */
    public function categoryList()
    {
        $assign['title'] = cfg('grow_grass_alias');//页面标题
        $assign['list'] = (new GrowGrassHomeService())->categoryList();
        $assign['share_info'] = (new GrowGrassHomeService())->shareInfo();
        $assign['sort_info'] = (new GrowGrassHomeService())->sortInfo();
        return api_output(0, $assign);
    }

    /**
     * @return \json
     * 首页文章列表
     */
    public function articleList()
    {
        try{
            $param['category_id'] = $this->request->param('category_id', 0, 'intval');//分类id
            $param['page'] = $this->request->param('page', 1, 'intval');//页码
            $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');//页码
            $param['lng'] = $this->request->param('lng', 0, 'trim');//经度
            $param['lat'] = $this->request->param('lat', 0, 'trim');//纬度
            $param['find_status'] = $this->request->param('find_status', 0, 'intval');//0文章 1用户
            $param['content'] = $this->request->param('content', '', 'trim');//搜索内容
            $param['type'] = $this->request->param('type','','trim,string');//排序类型
            $list = (new GrowGrassHomeService())->articleList($param);
            return api_output(0, $list);
        }catch (\Exception $e){
            dd($e);
        }
    }


    /**
     *
     *关注用户
     */
    public function growGrassFollow()
    {
        $param['uid'] = $this->request->param('follow_uid', 0, 'intval');//被关注的用户id
        if($param['uid']){
            $ret=(new GrowGrassArticleService())->updateGrowGrassFollow($param['uid']);
            if($ret){
                $data['msg']="操作成功";
            }else{
                $data['msg']="操作失败";
            }
            return api_output(0, $data);
        }else{
            return api_output(1003, "被关注的用户id缺失");
        }

   }
}
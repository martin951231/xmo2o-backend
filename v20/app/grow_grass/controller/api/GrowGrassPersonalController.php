<?php

/**
 * 个人中心
 */

namespace app\grow_grass\controller\api;


use app\grow_grass\model\service\GrowGrassHomeService;
use app\grow_grass\model\service\GrowGrassPersonalService;

class GrowGrassPersonalController extends ApiBaseController
{
    /**
     * @return \json
     * 我的关注列表,我的粉丝列表
     */
    public function myFollowList()
    {
        try {
            $user = request()->user;
            $uid = $user['uid'] ?? 0;
            $param['page'] = $this->request->param('page', 1, 'intval');//页
            $param['pageSize'] = $this->request->param('pageSize', '10', 'intval');//每页显示条数
            $param['user_id'] = $this->request->param('user_id', '0', 'intval');//看别人的
            $param['find_status'] = $this->request->param('find_status', 0, 'intval');//0关注页 1粉丝页
                if ($uid) {
                        $list = (new GrowGrassPersonalService())->myFollowList($param, $uid);
                        return api_output(0, $list);
                } else {
                    return api_output(1003, L_("用户id缺失"));
                }

        } catch (\Exception $e) {
            dd($e);
        }
    }

    /**
     * @return \json
     * 我的发布
     */
    public function myPublish()
    {
        $param['page'] = $this->request->param('page', 1, 'intval');//页
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');//每页显示条数
        $param['find_status'] = $this->request->param('find_status', 0, 'intval');//0文章
        $param['source'] = $this->request->param('source', 'publish', 'trim');//0文章
        $param['uid'] = $this->request->param('uid',0,'trim,intval'); //查看他人发布页
        $user = request()->user;
        $param['uid'] = $param['uid']?:($user['uid'] ?? 0);
        $param['is_manuscript'] = 0;
        $list = (new GrowGrassHomeService())->articleList($param);
        return api_output(0, $list);
    }

    /**
     * @return \json
     * 草稿箱
     */
    public function manuscript()
    {
        try{
        $param['page'] = $this->request->param('page', 1, 'intval');//页
        $param['pageSize'] = $this->request->param('pageSize', 10, 'intval');//每页显示条数
        $user = request()->user;
        $uid = $user['uid'] ?? 0;
        if ($uid) {
            $param['uid'] = $uid;
            $list = (new GrowGrassPersonalService())->manuscript($param);
            return api_output(0, $list);
        } else {
            return api_output(1003, L_("用户id缺失"));
        }
        }catch (\Exception $e){
          dd($e);
        }
    }

    /**
     * @return \json
     * 删除
     */
    public function delArticle()
    {
        $user = request()->user;
        $uid = $user['uid'] ?? 0;
        $param['article_id'] = $this->request->param('article_id', 0, 'intval');//页
        if ($uid && $param['article_id']) {
            $param['uid'] = $uid;
            $list = (new GrowGrassPersonalService())->delArticle($param);
            return api_output(0, $list, "删除成功");
        } else {
            return api_output(1003, L_("id缺失"));
        }
    }

    /**
     * @return \json
     * 个人中心用户的动态以及相册
     */
    public function myArticleList()
    {
        try {
            $user = request()->user;
            $param['uid'] = $user['uid'] ?? 0;
            $param['user_uid'] = $this->request->param('user_uid', 0, 'intval');//传进来的id
            $param['find_status'] = $this->request->param('find_status', 0, 'intval');//0动态 1相册
            $list = (new GrowGrassPersonalService())->getArticleByUidList($param);
            return api_output(0, $list);
        } catch (\Exception $e) {
            dd($e);
            //return api_output(1003, $e->getMessage());
        }
    }

    /**
     * @param $where
     * @param $data
     * @return bool
     * 种草心情话
     */
    public function updateHeartMsg()
    {
        try {
            $user = request()->user;
            $param['uid'] = $user['uid'] ?? 0;
            $data['heart_msg'] = $param['heart_msg'] = $this->request->param('heart_msg', 0, 'trim');//传进来的id
            $where = [['uid', '=', $param['uid']]];
            if (empty($param['heart_msg'])) {
                return api_output_error(1001, "缺少心情话");
            }
            $ret = (new GrowGrassPersonalService())->updateHeartMsg($where, $data);
            if ($ret) {
                return api_output(0, [], "编辑成功");
            } else {
                return api_output(0, [], "编辑失败");
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 添加/编辑种草个人中心背景图
     */
    public function addCenterImg(){
        $user = request()->user;
        $params['uid'] = $user['uid'] ?? 0;
        $params['grow_grass_bg_img'] = $this->request->param('grow_grass_bg_img', '', 'trim,string');//背景图
        if (empty($params['uid'])) {
            return api_output(1003, L_("用户id缺失"));
        }
        try {
            $res = (new GrowGrassPersonalService())->addCenterImg($params);
            return api_output(0, $res);
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }
}
<?php


namespace app\grow_grass\model\service;


use app\common\model\db\User;
use app\grow_grass\model\db\GrowGrassArticle;
use app\grow_grass\model\db\GrowGrassArticleLike;
use app\grow_grass\model\db\GrowGrassFile;
use app\grow_grass\model\db\GrowGrassFollow;

class GrowGrassPersonalService
{
    /**
     * @param array $param
     * @param $uid
     * @return mixed
     * 我的关注列表,粉丝列表
     */
    public function myFollowList($param = [], $uid)
    {
        $field = "u.uid,u.nickname,u.avatar,a.follow_uid";
        $order = "a.create_time desc";
        if(isset($param['user_id']) && $param['user_id']){//别人的粉丝列表。关注列表
            $user_id=$param['user_id'];
            if (isset($param['find_status']) && $param['find_status'] == 0) {//关注列表
                $where = [['a.uid', '=', $user_id], ['a.is_del', '=', 0]];
                $list['list'] = (new GrowGrassFollow())->myFollowList($where, $field, $order, $param['page'], $param['pageSize'], $sqlField = 'follow_uid');
                foreach ($list['list'] as $key => $val) {
                    $where2=[['uid','=',$uid],['follow_uid','=',$val['follow_uid']],['is_del', '=', 0]];
                    $ret=(new GrowGrassFollow())->getOne($where2);
                    if(empty($ret)){
                        $list['list'][$key]['follow_status'] = 0;
                    }else{
                        $list['list'][$key]['follow_status'] = 1;
                    }
                    /*$list['list'][$key]['follow_status'] = 1;*/
                }
            } else {//粉丝列表
                $field = "u.uid,u.nickname,u.avatar";
                $order = "a.create_time desc";
                $where = [['a.follow_uid', '=', $user_id], ['a.is_del', '=', 0]];
                $list['list'] = (new GrowGrassFollow())->myFollowList($where, $field, $order, $param['page'], $param['pageSize'], $sqlField = 'uid');
                foreach ($list['list'] as $key => $val) {
                    $where = [['follow_uid', '=', $uid], ['uid', '=', $val['uid']], ['is_del', '=', 0]];//有没有互关
                    $status = (new GrowGrassFollow())->getOne($where);
                    $list['list'][$key]['follow_status'] = empty($status) ? 0 : 1;
                }
            }
            $where1 = [['uid', '=', $user_id], ['is_del', '=', 0]];
            $list['follow_nums'] = (new GrowGrassFollow())->getCount($where1);//关注数

            $where1 = [['follow_uid', '=', $user_id], ['is_del', '=', 0]];
            $list['follow_others'] = (new GrowGrassFollow())->getCount($where1);//粉丝数
            $list['pageSize'] = $param['pageSize'] > 0 ? $param['pageSize'] : 10;
            return $list;
        }else{//本人的粉丝列表
            if (isset($param['find_status']) && $param['find_status'] == 0) {//关注列表
                $where = [['a.uid', '=', $uid], ['a.is_del', '=', 0]];
                $list['list'] = (new GrowGrassFollow())->myFollowList($where, $field, $order, $param['page'], $param['pageSize'], $sqlField = 'follow_uid');
                foreach ($list['list'] as $key => $val) {
                    $list['list'][$key]['follow_status'] = 1;
                }
            } else {//粉丝列表
                $where = [['a.follow_uid', '=', $uid], ['a.is_del', '=', 0]];
                $list['list'] = (new GrowGrassFollow())->myFollowList($where, $field, $order, $param['page'], $param['pageSize'], $sqlField = 'uid');
                foreach ($list['list'] as $key => $val) {
                    $where = [['follow_uid', '=', $val['uid']], ['uid', '=', $uid], ['is_del', '=', 0]];//有没有互关
                    $status = (new GrowGrassFollow())->getOne($where);
                    $list['list'][$key]['follow_status'] = empty($status) ? 0 : 1;
                }
            }
            $where1 = [['uid', '=', $uid], ['is_del', '=', 0]];
            $list['follow_nums'] = (new GrowGrassFollow())->getCount($where1);//关注数

            $where1 = [['follow_uid', '=', $uid], ['is_del', '=', 0]];
            $list['follow_others'] = (new GrowGrassFollow())->getCount($where1);//粉丝数
            $list['pageSize'] = $param['pageSize'] > 0 ? $param['pageSize'] : 10;
            return $list;
        }

    }

    /**
     * @return \json
     * 删除
     */
    public function delArticle($param)
    {
        $where = [['uid', '=', $param['uid']], ['article_id', '=', $param['article_id']]];
        $data['is_del'] = 1;
        $ret = (new GrowGrassArticle())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取种草文章列表
     * @param $where array 条件
     * @return array
     */
    public function getArticleByUidList($param = [])
    {
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 10;
        $where = '';
        $field = "a.*,u.nickname,u.avatar";
        $user = [
            'res_status' => 0,//0未认证 1已经认证
            'avatar' => '',
            'nickname' => '',
            'follow_status' => 0,//是否被关注 0没关注 1关注了 2自己不需要关注
            'sex' => 0,
            'city' => '',
            'job' => '',
            'store' => '',
            'heart_msg' => '',
            'list' => [
                'fans_nums' => 0,//粉丝数
                'follow_nums' => 0,//关注数
                'like_nums' => 0,//获赞数
            ]
        ];

        if (isset($param['user_uid']) && $param['user_uid']) {
            $where_user = [['uid', '=', $param['user_uid']]];
            $us = (new User())->getOne($where_user);
            $where_f = [['uid', '=', $param['uid']], ['follow_uid', '=', $param['user_uid']], ['is_del', '=', 0]];
            $ret_find = (new GrowGrassFollow())->getOne($where_f);
            if (empty($ret_find)) {
                $user['follow_status'] = 0;//没关注
            } else {
                $user['follow_status'] = 1;//已经关注
            }
        } else {
            $where_user = [['uid', '=', $param['uid']]];
            $us = (new User())->getOne($where_user);
            $user['follow_status'] = 2;//自己看自己
        }
        if (!empty($us)) {//头部用户信息
            $us = $us->toArray();
            $user['nickname'] = $us['nickname'];
            $user['avatar'] = $us['avatar'];
            $user['sex'] = $us['sex'];//1男 2女 0未知
            $user['city'] = $us['city'];
            $user['heart_msg'] = $us['heart_msg'] ?? "拍好看的风景,拍好看的人物";
            $user['list']['fans_nums'] = $us['follow_nums'];
            $where_ar = [['uid', '=', $us['uid']]];
            $ar_list = (new GrowGrassArticle())->getSome($where_ar, 'article_id')->toArray();
            $my_ar = array();
            if (!empty($ar_list)) {
                foreach ($ar_list as $key => $val) {
                    $my_ar[] = $val['article_id'];
                }
                $where_ar = [['article_id', 'in', $my_ar], ['is_del', '=', 0]];
                $count = (new GrowGrassArticleLike())->getCount($where_ar);
                $user['list']['like_nums'] = $count;

                $where_ar = [['follow_uid', '=', $us['uid']], ['is_del', '=', 0]];
                $count = (new GrowGrassFollow())->getCount($where_ar);
                $user['list']['follow_nums'] = $count;
            }
        } else {
            $returnArr = [
                'list' => [],
                'pageSize' => $pageSize,
                'user' => $user
            ];
            return $returnArr;
        }

        if ($param['find_status'] == 0) {
            $order = "a.publish_time DESC";
            $where .= "a.uid=" . $us['uid'] . " AND " . "a.status=20";
            $list = (new GrowGrassArticle())->getArticleListCategoryCondition($where, $field, $order, $page, $pageSize);
            $returnArr = [
                'list' => (new GrowGrassArticleService())->formatDataList($list),
                'pageSize' => $pageSize,
                'user' => $user
            ];
        } else {//相册
            $where = [['uid', '=', $us['uid']]];
            $field1 = "url,video_url,file_style";
            $order = "id desc";
            $list = (new GrowGrassFile())->getSome($where, $field1, $order, ($page - 1) * $pageSize, $pageSize)->toArray();
            if (!empty($list)) {
                foreach ($list as $key => $val) {
                    $list[$key]['url'] = empty($val['url']) ? "" : replace_file_domain($val['url']);
                    $list[$key]['video_url'] = empty($val['video_url']) ? "" : replace_file_domain($val['video_url']);
                }
            }
            $returnArr = [
                'list' => $list,
                'pageSize' => $pageSize,
                'user' => $user
            ];
        }
        return $returnArr;
    }

    /**
     * @param $where
     * @param $data
     * @return bool
     * 种草心情话
     */
    public function updateHeartMsg($where, $data)
    {
        $ret = (new User())->updateThis($where, $data);
        if ($ret !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function manuscript($param)
    {
        $where = [['uid', '=', $param['uid']], ['is_manuscript', '=', 1], ['is_del', '=', 0], ['is_system_del', '=', 0]];
        $list = (new GrowGrassArticleService())->manuscript($where, 'article_id,name,add_time,content,img,video_url,category_id', true, $param['page'], $param['pageSize']);
        return $list;
    }

    /**
     * 添加/编辑种草个人中心背景图
     * @param $params
     * @return bool
     * @throws \think\Exception
     */
    public function addCenterImg($params){
        $grow_grass_bg_img = $params['grow_grass_bg_img'];
        $uid = $params['uid'];
        if(!$grow_grass_bg_img){
            throw new \think\Exception(L_('请上传背景图'));
        }
        try {
            (new User())->where(['uid'=>$uid])->save(['grow_grass_bg_img'=>$grow_grass_bg_img]);
        }catch (\Exception $e){
            throw new \think\Exception(L_($e->getMessage()));
        }
        return true;
    }
}
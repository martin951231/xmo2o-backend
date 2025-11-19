<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2020/3/31
 * Time: 13:50
 */
class House_streetAction extends BaseAction {

    /**
     * Notes: 街道新闻获取
     * @author: wanzy
     * @date_time: 2020/8/24 13:19
     */
    public function village_street_newslist()
    {
        $village_id = I('village_id', 0, 'intval');
        // 街道新闻
        $street_id = I('street_id',0,'intval');
        $cat_id = I('cat_id', 0);
        $page = I('page', 1);
        if (!$street_id && !$village_id){
            $this->returnCodeError('参数错误');
        }elseif (!$street_id && $village_id) {
            $now_village = D('House_village')->get_one($village_id);
            $street_id = $now_village['street_id'];
        }
        $arr = [];
        $category_list = D('Area_street_news_category')
            ->field(true)
            ->where(array('area_id'=>$street_id,'cat_status'=>'1'))
            ->order('`cat_sort` DESC,`cat_id` DESC')
            ->limit(10)
            ->select();

        if ($category_list) {

            $arr['category_list'] = $category_list;

            $cat_id = $cat_id ? $cat_id : $category_list[0]['cat_id'];

            $news_list = D('Area_street_news')->get_list_by_cid($cat_id,$street_id, $page);

            foreach ($news_list as $k => &$v) {
                $v['add_time'] = date('m-d H:i', $v['add_time']);
                if ($v['title_img']) {
                    $v['title_img'] = replace_file_domain($v['title_img']);
                }
            }

            $arr['news_list'] = $news_list;
            $this->returnCode(0, $arr,'');
        } else {
            $arr['news_list'] = [];
            $this->returnCode(0, $arr,'');
        }
    }

    /**
     * Notes: 社区新闻获取
     * @author: wanzy
     * @date_time: 2020/8/24 13:19
     */
    public function village_newslist()
    {
        $village_id = I('village_id', 0, 'intval');
        // 街道新闻
        $community_id = I('community_id',0,'intval');
        $cat_id = I('cat_id', 0);
        $page = I('page', 1);
        if (!$community_id && !$village_id){
            $this->returnCodeError('参数错误');
        }elseif (!$community_id && $village_id) {
            $now_village = D('House_village')->get_one($village_id);
            $community_id = $now_village['community_id'];
        }
        $arr = [];
        $category_list = D('Area_street_news_category')
            ->field(true)
            ->where(array('area_id'=>$community_id,'cat_status'=>'1'))
            ->order('`cat_sort` DESC,`cat_id` DESC')
            ->limit(10)
            ->select();

        if ($category_list) {

            $arr['category_list'] = $category_list;

            $cat_id = $cat_id ? $cat_id : $category_list[0]['cat_id'];

            $news_list = D('Area_street_news')->get_list_by_cid($cat_id,$community_id, $page);

            foreach ($news_list as $k => &$v) {
                $v['add_time'] = date('m-d H:i', $v['add_time']);
                if ($v['title_img']) {
                    $v['title_img'] = replace_file_domain($v['title_img']);
                }
            }

            $arr['news_list'] = $news_list;
            $this->returnCode(0, $arr,'');
        } else {
            $arr['news_list'] = [];
            $this->returnCode(0, $arr,'');
        }
    }


    /**
     * Notes: 获取新新闻详情
     * @author: wanzy
     * @date_time: 2020/8/24 13:19
     */
    public function village_news()
    {
        $news_id = I('news_id', 0, 'intval');
        if (empty($news_id)) {
            $this->returnCode('71000020');
        }
        $page = I('page', 1);
        $arr = [];
        $now_news = D('Area_street_news')->get_one_new($news_id);
        M('Area_street_news')->where(array('news_id' => $news_id))->setInc('read_sum');
        if ($now_news) {
            if ($now_news['title_img']) {
                $now_news['title_img'] = replace_file_domain($now_news['title_img']);
            }
            $news_reply = M('Area_street_news_reply')->where(array('news_id' => $news_id,'status'=>1))->page($page, 12)->select();
            if ($news_reply) {
                $news_reply_list = [];
                foreach ($news_reply as $item) {
                    $list = array();
                    $list['is_read'] = $item['is_read'];
                    $user = D('User')->field(array('nickname', 'avatar'))->where(array('uid' => $item['uid']))->find();
                    if ($user['nickname']) {
                        $list['nickname'] = $user['nickname'];
                    } else {
                        $list['nickname'] = '';
                    }
                    if ($user['avatar']) {
                        $list['avatar'] = replace_file_domain($user['avatar']);
                    } else {
                        $list['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                    }
                    $list['content'] = replace_file_domain_content(htmlspecialchars_decode($item['content']));
                    $news_reply_list[] = $list;

                }
                $now_news['news_reply_list'] = $news_reply_list;
            } else {
                $now_news['news_reply_list'] = [];
            }
            $now_news['content'] = replace_file_domain_content(htmlspecialchars_decode($now_news['content']));
            $now_news['content'] = preg_replace("/<img.*?src=(\"|\')(.*?)(\"|\').*?\/\>/is",'<img style="max-width:100%;vertical-align:top;" src="$2" />', $now_news['content']);
        }
        if (empty($now_news)) {
            $arr['now_news'] = [];
        } else {
            $now_news['add_time'] = date('m-d H:i', $now_news['add_time']);
            $arr['now_news'] = $now_news;
            if ($now_news['area_id']>0) {
                $area_name = M('Area_street')->where(['area_id' => $now_news['area_id']])->getField('area_name');
                $arr['area_name'] = $area_name ? $area_name : '';
            }
        }
        $this->returnCode(0, $arr);
    }

    /**
     * Notes: 添加新闻评论
     * @author: wanzy
     * @date_time: 2020/8/24 13:20
     */
    public function add_new()
    {
        $news_id = I('news_id', 0, 'intval');
        if (empty($news_id)) {
            $this->returnCode('71000020');
        }
        $content = I('content', '', 'trim');
        if (empty($content)) {
            $this->returnCode('20070002');
        }
        if (empty($this->_uid)) {
            $this->returnCode('20000009');
        }
//        $pigcms_id = I('pigcms_id', '');//房间号
//        if (empty($pigcms_id)) {
//            $this->returnCode('20120004');
//        }
        $check_msg = D('House_village')->msgSecCheck($content);
        if ($check_msg['errcode'] != 0) {
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
            fdump('add_new____'.__LINE__,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
            fdump($_GET,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
            fdump($_POST,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
            fdump($check_msg,'api/log/'.date('Ymd').'/msgSecCheck_err_log',true);
            $this->returnCodeError('内容含有违法违规内容');
        }
        $news_info = M('Area_street_news')->where(array('news_id' => $news_id))->find();
        $area_id = $news_info['area_id'];
        $data = [];
        $now_config = D('Area_street_config')->where(['area_id' => $area_id])->find();
        if($now_config['news_is_need_check'] == 1){
            $data['status'] = 0;
        }else{
            $data['status'] = 1;
        }
        $data['uid'] = $this->_uid;
        $data['area_id'] = $area_id;
        $data['news_id'] = $news_id;
        $data['content'] = $content;
        $data['add_time'] = time();
        $data['is_read'] = 0;
        $news_reply = M('Area_street_news_reply')->data($data)->add();
        if (empty($news_reply)) {
            $this->returnCode('10100101');
        } else {
            $arr = [];
            if ($data['status'] == 1) {
                $user = D('User')->field(array('nickname', 'avatar'))->where(array('uid' => $this->_uid))->find();
                if ($user['nickname']) {
                    $arr['nickname'] = $user['nickname'];
                } else {
                    $arr['nickname'] = '';
                }
                if ($user['avatar']) {
                    $arr['avatar'] = replace_file_domain($user['avatar']);
                } else {
                    $arr['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
                }
                $arr['content'] = $content;
            }
            $this->returnCode(0, $arr);
        }
    }
}
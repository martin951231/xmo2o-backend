<?php
class Comm_group_expansionAction extends BaseAction{
    /**
     * [dynamic_add 动态-帖子添加]
     * @return [type] [description]
     */
    public function dynamic_add(){
        $data['community_id'] = $_POST['community_id'] ? $_POST['community_id'] : 0;//群ID
        $data['user_id'] = $this->_uid;//用户ID
        $data['content'] = $_POST['content'];//动态内容
        $data['addtime'] = time();
        $ret = D('Community_file')->msgSecCheck($data['content']);
        if ($ret['errcode'] != 0) {
            $this->returnCode(1, array(), L_('内容存在违规！请重试~'));
        }
        if($_POST['pigcms_id']){//用户是否上传了图片
            $arr = $_POST['pigcms_id'] ? $_POST['pigcms_id'] : array();//传递的图片数组 pigcms_id为image表的自增id
            $where['pigcms_id'] = array('in',$arr);
            $where['status'] = array('neq',4);
            $res = D('Image')->field('pic')->where($where)->select();
            foreach($res as $k=>$v){
                $data['img'][] = $v['pic'];
            }
            $data['img'] = serialize($data['img']);
        }
        
        $file_model = D('Community_file');
        if($_POST['file_id']){//用户是否上传了文件
            $file_id = $_POST['file_id'] ? $_POST['file_id'] : '';//传递的文件ID
            $where['file_id'] = $file_id;
            $where['file_status'] = 1;
            $res = $file_model->field('file_id,file_remark,file_url,file_type,file_suffix,oss_url')->where($where)->find();

            $data['file']['file_remark'] = $res['file_remark'];
            $data['file']['file_url'] = $res['oss_url'] ? $res['oss_url'] : $res['file_url'];
            $data['file']['file_id'] = $res['file_id'];
            $file_info= $file_model->type_and_img($res['file_type'],$res['file_suffix'],true);
            $data['file']['file_img'] = $file_info['url'];
            $data['file']['is_img'] = $file_info['is_img'];
            $data['file'] = serialize($data['file']);
        }
        
        $result = D('Community_dynamic')->add($data);

        if($_POST['topic_id'] && $result){
            $array = array_unique(explode(',',$_POST['topic_id']));
            foreach($array as $key=>$value){
                $msg[$key]['dynamic_id'] = $result;
                $msg[$key]['topic_id'] = $value;
            }
            $final = D('Community_dynamic_topic_bind')->addAll($msg);
            if(!$final){
                $this->returnCode(1,array(),L_('添加话题绑定信息失败!'));
            }
        }
        
        if($result != false){
            $des = L_('发布了一条动态，点击查看详情');
            D('Community_file')->create_plan($result, $des);
            $this->returnCode(0,L_('添加成功!'));
        }else{
            $this->returnCode(1,array(),L_('添加失败!'));
        }
    }

    /**
     * [upload_img 上传图片]
     * @return [type] [description]
     */
    public function upload_img(){
        if(!empty($_FILES) && $_FILES['file']['error'] != 4){
            $image = D('Image')->handle($this->_uid, 'comm', 2, array('size' => 10), false,true);
            if (!$image['error']) {
                $this->returnCode(0,$image['pigcms_id']);
            } else {
                $this->returnCode(1,array(), $image['message']);
            }
        }
    }

    /**
     * [del_img 删除图片]
     * @return [type] [description]
     */
    public function del_img(){
        $where['pigcms_id']  = $_POST['pigcms_id'];
        $data['status']  = 4;
        if($where['pigcms_id']){
            $result = D('Image')->where($where)->save($data);
            
            if($result != false){
                $this->returnCode(0,L_('删除成功!'));
            }else{
                $this->returnCode(1,array(),L_('删除图片失败!'));
            }
        }else{
            returnCode('100001',array(),L_('传递参数错误!'));
        }
    }


    /**
     * [dynamic_list 动态列表]
     * @return [type] [description]
     */
    public function dynamic_list(){
        $where['a.community_id'] = $_POST['community_id'];
        $where['a.is_del'] = 0;
        $pageSize = 10;
        $page= $_POST['page'] ? $_POST['page'] : 1;
        $group_owner_uid = D('Community_info')->where(array('community_id'=>$_POST['community_id']))->getField('group_owner_uid');
        $dynamic_model = D('Community_dynamic');
        // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
        if ($pageSize > 0) {
            $total =$dynamic_model->where(array('community_id'=>$_POST['community_id'],'is_del'=>0))->count();
            $page = isset($page) ? intval($page) : 1;
            $totalPage = ceil($total / $pageSize);
            $firstRow = $pageSize * ($page - 1);

           
            $Distinct = 'false';
            $field='a.*,b.avatar,b.nickname';
            $join='join pigcms_user as b on a.user_id=b.uid';
            $order = 'a.addtime desc';
            $limit = $firstRow.','.$pageSize;
            $list = $dynamic_model->dynamic_select($Distinct,$field,$join,$where,$order,$limit);

            $reply_model = D('Community_reply');
            $user_model = D('User');
            $user_field = 'nickname';
            $praise_model = D('Community_praise');
            foreach($list as $key=>$value){
                $maple['dynamic_id'] = $value['id'];
                $maple['reply_is_del'] = 0;
                $dynamic_reply = $reply_model->reply_select('','','',$maple,'','');
                foreach($dynamic_reply as $kk=>$vv){
                	$user_where1= array('uid'=>$dynamic_reply[$kk]['reply_user_id']);
                	$user_where2= array('uid'=>$dynamic_reply[$kk]['reply_to_user_id']);
                    $user = $user_model->user_find($user_field,$user_where1);
                    $to_user = $user_model->user_find($user_field,$user_where2);
                    if($this->_uid != $vv['reply_user_id']){
                        $dynamic_reply[$kk]['can_reply'] = true;
                    }else{
                        $dynamic_reply[$kk]['can_reply'] = false;
                    }
                    $dynamic_reply[$kk]['user'] = $user['nickname'];
                    $dynamic_reply[$kk]['to_user'] = $to_user['nickname'];
                }
                if($value['img']){
                    $list[$key]['img'] = unserialize($value['img']);
                    $dynamic_img = $list[$key]['img'];
                    $list[$key]['img'] = array();
                    foreach($dynamic_img as $k=>$v){
                        if(!(strpos($v,'http') === 0)) {
                            $list[$key]['img'][] = replace_file_domain($v);
                        } else {
                            $list[$key]['img'][] = $v;
                        }
                    }
                }
                if($value['file']){
                    $list[$key]['file'] = unserialize($value['file']);
                    $dynamic_file = $list[$key]['file'];
                    $list[$key]['file'] = array();

                    if(!(strpos($dynamic_file['file_url'],'http') === 0)) {
                        $list[$key]['file']['file_url'] = replace_file_domain($dynamic_file['file_url']);
                    } else {
                        $list[$key]['file']['file_url'] = $dynamic_file['file_url'];
                    }

                    $list[$key]['file']['file_remark'] = $dynamic_file['file_remark'];
                    $list[$key]['file']['file_id'] = $dynamic_file['file_id'];
                    $list[$key]['file']['file_img'] = $dynamic_file['file_img'];
                    $list[$key]['file']['is_img'] = $dynamic_file['is_img'];
                }
                if($value['application_detail']){
                    $list[$key]['application_detail'] = unserialize($value['application_detail']);
                }
                $praise_field = 'b.nickname';
                $praise_join = 'join pigcms_user as b on a.praise_user_id=b.uid';
                $praise_where = array('dynamic_id'=>$value['id']);          
                $praise_dynamic = $praise_model->praise_select('true',$praise_field,$praise_join,$praise_where,'','');//动态的点赞
                
                $arr = D('Community_dynamic_topic_bind')->field('topic_id')->where(array('dynamic_id'=>$value['id']))->select();
                $array=array();
                foreach ($arr as $kkk => $vvv) {
                    $array[] =  $vvv['topic_id']; 
                }
                
                $map['topic_id'] = array('in',$array);
                $topic = D('Community_topic')->field('topic_id,topic_title')->where($map)->select();
                $list[$key]['addtime'] = time_info($list[$key]['addtime']);
                $list[$key]['reply'] = $dynamic_reply;
                $list[$key]['praise'] = $praise_dynamic;
                $list[$key]['topic_list'] = $topic;

                //是否可以为用户本人
                $dynamic_user_id = $dynamic_model->dynamic_find('','user_id','',array('id'=>$list[$key]['id']));
                $dynamic_praise = $praise_model->praise_find('','','',array('dynamic_id'=>$value['id'],'praise_user_id'=>$this->_uid));
                if(!$dynamic_praise){
                    $list[$key]['can_praise'] = true;
                }else{
                    $list[$key]['can_praise'] = false;
                }
                //是否为群主或该动态的发布者,用有删除的去权限
                if($this->_uid == $group_owner_uid || $this->_uid == $dynamic_user_id['user_id']){
                    $list[$key]['can_del'] = true;
                }else{
                    $list[$key]['can_del'] = false;
                }
            }
            $info_list = array(
                'total' => $total,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $list
            );
        }
        $this->returnCode(0,$info_list);
    }

    /**
     * [dynamic_detail 动态详情]
     * @return [type] [description]
     */
    public function dynamic_detail(){
            $where['a.community_id'] = $_POST['community_id'];
            $where['a.is_del'] = 0;
            $where['a.id'] = $_POST['id'];
            if(!$_POST['community_id'] || !$_POST['id']){
                $this->returnCode(1,L_('缺少参数!'));
            }
            $group_owner_uid = D('Community_info')->get_community_info(array('community_id'=>$_POST['community_id']));

            $praise_model = D('Community_praise');
            $field = 'a.*,b.avatar,b.nickname,c.community_name';
            $join = 'join pigcms_user as b on a.user_id=b.uid join pigcms_community_info as c on a.community_id=c.community_id';
            $list = D('Community_dynamic')->dynamic_select(false,$field,$join,$where,'','');
            foreach($list as $key=>$value){
                $maple['dynamic_id'] = $value['id'];
                $maple['reply_is_del'] = 0;
                $dynamic_reply = D('Community_reply')->where($maple)->select();
                foreach($dynamic_reply as $kk=>$vv){
                    $user = D('User')->field('nickname')->where(array('uid'=>$dynamic_reply[$kk]['reply_user_id']))->find();
                    $to_user = D('User')->field('nickname')->where(array('uid'=>$dynamic_reply[$kk]['reply_to_user_id']))->find();
                    if($this->_uid != $vv['reply_user_id']){
                        $dynamic_reply[$kk]['can_reply'] = true;
                    }else{
                        $dynamic_reply[$kk]['can_reply'] = false;
                    }
                    $dynamic_reply[$kk]['user'] = $user['nickname'];
                    $dynamic_reply[$kk]['to_user'] = $to_user['nickname'];
                }

                if($value['img']){
                    $list[$key]['img'] = unserialize($value['img']);
                    $dynamic_img = $list[$key]['img'];
                    $list[$key]['img'] = array();
                    foreach($dynamic_img as $k=>$v){
                        if(!(strpos($v,'http') === 0)) {
                            $list[$key]['img'][] = replace_file_domain($v);
                        } else {
                            $list[$key]['img'][] = $v;
                        }
                    }
                }
                if($value['file']){
                    $list[$key]['file'] = unserialize($value['file']);
                    $dynamic_file = $list[$key]['file'];
                    $list[$key]['file'] = array();
                    if(!(strpos($dynamic_file['file_url'],'http') === 0)) {
                        $list[$key]['file']['file_url'] = replace_file_domain($dynamic_file['file_url']);
                    } else {
                        $list[$key]['file']['file_url'] = $dynamic_file['file_url'];
                    }
                    $list[$key]['file']['file_remark'] = $dynamic_file['file_remark'];
                    $list[$key]['file']['file_id'] = $dynamic_file['file_id'];
                    $list[$key]['file']['file_img'] = $dynamic_file['file_img'];
                    $list[$key]['file']['is_img'] = $dynamic_file['is_img'];
                }
                if($value['application_detail']){
                    $list[$key]['application_detail'] = unserialize($value['application_detail']);
                }
                $praise_field = 'b.nickname';
                $praise_join = 'join pigcms_user as b on a.praise_user_id=b.uid';
                $praise_where = array('dynamic_id'=>$value['id']);          
                $praise_dynamic = $praise_model->praise_select('true',$praise_field,$praise_join,$praise_where,'','');//动态的点赞

                $arr = D('Community_dynamic_topic_bind')->field('topic_id')->where(array('dynamic_id'=>$value['id']))->select();
                $array=array();
                foreach ($arr as $kkk => $vvv) {
                    $array[] =  $vvv['topic_id']; 
                }
                
                $map['topic_id'] = array('in',$array);
                $topic = D('Community_topic')->field('topic_id,topic_title')->where($map)->select();
                $list[$key]['addtime'] = time_info($list[$key]['dynamic_addtime']);
                $list[$key]['reply'] = $dynamic_reply;
                $list[$key]['praise'] = $praise_dynamic;
                $list[$key]['topic_list'] = $topic;
                $dynamic_praise = D('Community_praise')->where(array('dynamic_id'=>$value['id'],'praise_user_id'=>$this->_uid))->find();
                if(!$dynamic_praise){
                    $list[$key]['can_praise'] = true;
                }else{
                    $list[$key]['can_praise'] = false;
                }
                //是否为用户本人
                $dynamic_user_id = D('Community_dynamic')->field('user_id')->where(array('id'=>$list[$key]['id']))->find();
                //是否为群主或该动态的发布者,用有删除的去权限
                if($this->_uid == $group_owner_uid['group_owner_uid'] || $this->_uid == $dynamic_user_id['user_id']){
                    $list[$key]['can_del'] = true;
                }else{
                    $list[$key]['can_del'] = false;
                }
            }
            $info_list = array(
                'list' => $list
            );
            $this->returnCode(0,$info_list);
    }



    /**
     * [select_praise 动态点赞]
     * @return [type] [description]
     */
    public function add_praise(){
        $data['topic_id'] = $_POST['topic_id'] ? $_POST['topic_id'] : 0;
        $uid = $this->_uid;
        if(!$_POST['id']){
            $this->returnCode(1,L_('缺少参数!'));
        }
        $praise_model = D('Community_praise');
        if($data['topic_id'] != 0){
            $where['praise_user_id'] = $uid;
            $where['dynamic_id'] = $_POST['id'];
            foreach($data['topic_id'] as $key =>$value){
                $where['topic_id'] = $value;
                $res = $praise_model->where($where)->find();
                if($res){//如果存在该话题已经被该用户点赞 则取消
                    $result_delete[] = $praise_model->where($where)->delete();
                }else{
                    $result_add[] = $praise_model->add($where);
                }
            }
            if($result_delete){
                $this->returnCode(0,L_('取消点赞成功'));
            }
            if($result_add){
                $this->returnCode(0,L_('点赞成功'));
            }
        }else{
            $data['dynamic_id'] = $_POST['id'];
            $data['praise_user_id'] = $uid;
            $res = $praise_model->where($data)->find();
            if($res){
                $result_delete = $praise_model->where($data)->delete();
            }else{
                $result_add = $praise_model->add($data);
            }
            if($result_delete){
                $this->returnCode(0,L_('取消点赞成功'));
            }
            if($result_add){
                $this->returnCode(0,L_('点赞成功'));
            }
        }
    }



    /**
     * [add_reply 评论添加]
     */
    public function add_reply(){
        $data['reply_user_id'] = $this->_uid ? $this->_uid : '';
        $data['dynamic_id'] = $_POST['id'] ? $_POST['id'] : '';
        $data['reply_content'] = $_POST['reply_content'] ? $_POST['reply_content'] : '';
        $data['reply_to_user_id'] = $_POST['reply_to_user_id'] ? $_POST['reply_to_user_id'] : 0;
        $data['topic_id'] = $_POST['topic_id'] ? $_POST['topic_id'] : 0;

        $result = D('Community_reply')->add($data);
        if($result != false){
            $this->returnCode(0,L_('评论成功!'));
        }else{
            $this->returnCode(1,array(),L_('评论失败'));
        }
    }


    /**
     * [del_topic 动态-帖子删除]
     * @return [type] [description]
     */
    public function del_dynamic(){
        $data['id'] = $_POST['id'];
        $data['is_del'] = 1;//0不删除1删除
        $data['user_id'] = $this->_uid;
        $data['community_id'] = $_POST['community_id'] ? $_POST['community_id'] : 0;
        if($data){
            $dynamic_user_id = D('Community_dynamic')->field('user_id')->where(array('id'=>$data['id']))->find();
            
            $group_owner_uid = D('Community_info')->field('group_owner_uid')->where(array('community_id'=>$data['community_id']))->find();
            if(!$dynamic_user_id){
                $this->returnCode('300002',array(),L_('查询用户信息失败!'));
            }
            if(!$group_owner_uid){
                $this->returnCode('300001',array(),L_('查询群主信息失败!'));
            }

            $arr=array_merge($dynamic_user_id,$group_owner_uid);
            if(!in_array($data['user_id'], $arr)){
                $this->returnCode('200001',array(),L_('无权删除!'));
            }
            
            $result1 = D('Community_dynamic')->save($data);
            $result2 = D('Community_dynamic_topic_bind')->where(array('dynamic_id'=>$data['id']))->delete();
            if($result1 != false){
                D('Community_reply')->where(array('dynamic_id'=>$data['id']))->delete();//删除帖子的同时把该帖的评论删除
                D('Community_praise')->where(array('dynamic_id'=>$data['id']))->delete();//删除帖子的同时把该帖的点赞删除
                $this->returnCode(0,L_('删除成功!'));
            }else{
                $this->returnCode(1,array(),L_('删除失败!'));
            }
        }else{
            $this->returnCode('100001',array(),L_('传递参数错误!'));
        }
    }

    /**
     * [del_reply 删除评论]
     * @return [type] [description]
     */
    // public function del_reply(){
    //  $data['reply_id'] = $_POST['reply_id'];
    //  $data['reply_user_id'] = $this->_uid;
    //  $data['community_id'] = $_POST['community_id'];
    //  if($data['community_id']){
    //      $group_owner_uid = D('Community_info')->field('group_owner_uid')->where(array('community_id'=>$data['community_id']))->find();
    //  }

    //  if($data){
 //            $reply_user_id = D('Community_reply')->field('reply_user_id')->where(array('reply_id'=>$data['reply_id']))->find();

 //            if(!$reply_user_id){
 //             $arr=array($reply_user_id);
 //                $this->returnCode('300001',array(),'查询用户信息失败!');
 //            }
 //            if(!$group_owner_uid){
 //                $this->returnCode('300001',array(),'查询群主信息失败!');
 //            }else{
 //             $arr=array_merge($reply_user_id,$group_owner_uid);
 //            }
 //            ;
 //            if(!in_array($data['reply_user_id'], $arr)){
 //                $this->returnCode('200001',array(),'无权删除!');
 //            }

 //            $result = D('Community_reply')->where(array('reply_id'=>$data['reply_id']))->save(array('reply_is_del'=>1));
 //            if($result != false){
 //                $this->returnCode(0,'删除成功!');
 //            }else{
 //                $this->returnCode(1,array(),'删除失败!');
 //            }
 //        }else{
 //            $this->returnCode('100001',array(),'传递参数错误!');
 //        }
    // }

//--------------------------------------------群话题start---------------------------------------------------------------//
    
    /**
     * [add_topic 添加话题]
     */
    public function add_topic(){
        $data['topic_cate_id'] = $_POST['topic_cate_id'];
        $data['topic_user_id'] = $this->_uid;
        $data['topic_title'] = $_POST['topic_title'];
        $data['topic_content'] = $_POST['topic_content'];
        $data['community_id'] = $_POST['community_id'];
        $data['topic_addtime'] = time();
        if(!$data['topic_cate_id'] || !$data['topic_user_id'] || !$data['topic_title'] || !$data['topic_content']){
            $this->returnCode('100001',array(),L_('传递参数错误!'));
        }
       
        $where['pigcms_id'] = $_POST['pigcms_id'];
        $where['status'] = array('neq',4);
        $res = D('Image')->field('pic')->where($where)->select();
        foreach($res as $k=>$v){
            $data['topic_img'][] = $v['pic'];
        }

        $data['topic_img'] = serialize($data['topic_img']);
        
        $info_list = D('Community_topic')->add($data);
        if($info_list){
            $this->returnCode(0,L_('添加话题成功!'));
        }
    }


    /**
     * [report_cate_list 举报分类列表]
     * @return [type] [description]
     */
    public function report_cate_list(){
        $where['report_cate_is_del'] = 0;
        $info_list = D('Community_report_cate')
                    ->where($where)
                    ->select();
        $this->returnCode(0,$info_list);
    }

    /**
     * [add_report 添加举报]
     */
    public function add_report(){
        $data['user_id'] = $this->_uid;
        $data['report_cate_id'] = $_POST['report_cate_id'];
        $data['topic_id'] = $_POST['topic_id'];
        $data['dynamic_id'] = $_POST['dynamic_id'];
        $data['report_addtime'] = time();
        
        if(!$data['user_id'] || !$data['report_cate_id'] || !$data['topic_id'] || !$data['dynamic_id']){
            $this->returnCode('100001',array(),L_('传递参数错误!'));
        }
        $list = D('Community_report_user_bind')->add($data);
        if($list){
            $this->returnCode(0,L_('举报成功!后台将及时审核并处理~'));
        }else{
            $this->returnCode(1,array(),L_('举报失败!'));
        }
    }

    /**
     * [topic_cate_list 话题分类列表]
     * @return [type] [description]
     */
    public function topic_cate_list(){
        $where['topic_cate_is_del'] = 0;
        $info_list = D('Community_topic_cate')
                    ->where($where)
                    ->select();
        $this->returnCode(0,$info_list);
    }

       /**
     * [topic_list 话题列表]
     * @return [type] [description]
     */
    public function topic_list(){
        $where['topic_is_del'] = 0;
        $where['community_id'] = $_POST['community_id'];
        if(!$_POST['community_id']){
            $this->returnCode(L_('传递参数错误!'));
        }
        if($_POST['topic_cate_id']){
            $where['topic_cate_id'] = $_POST['topic_cate_id'];
        }
        if ($_POST['key_words']) {
            $where['topic_title'] = array('like','%'.$_POST['key_words'].'%');
        }
        $pageSize = 10;
        $total = D('Community_topic')->where($where)->count();
        $page= $_POST['page'] ? $_POST['page'] : 1;
        $page = isset($page) ? intval($page) : 1;
        $totalPage = ceil($total / $pageSize);
        $firstRow = $pageSize * ($page - 1);
        $str='';
        if($page > 0){
            $list = D('Community_topic')
                    ->order('topic_addtime desc')
                    ->where($where)
                    ->limit($firstRow.','.$pageSize)
                    ->select();
            foreach($list as $key=>$value){
                //帖子总数
                $note_total_count = D('Community_dynamic_topic_bind')->where(array('topic_id'=>$value['topic_id']))->count();

                if($value['topic_img']){
                    $list[$key]['topic_img'] = unserialize($value['topic_img']);
                    $topic_img = $list[$key]['topic_img'];
                    $list[$key]['topic_img'] = array();
                    foreach($topic_img as $k=>$v){
                        $list[$key]['topic_img'][] = replace_file_domain($v);
                    }
                }
                //创建人
                $people_topic = D('Community_topic')->field('topic_user_id as user_id')->where(array('topic_id'=>$value['topic_id'],'community_id'=>$_POST['community_id']))->select();
                //帖子人数                                              
                $people_dynamic_topic = D('Community_dynamic')
                                        ->alias('a')
                                        ->Distinct(true)
                                        ->field('a.user_id')
                                        ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                        ->where(array('b.topic_id'=>$value['topic_id']))
                                        ->select();
                
                $reply_count = D('Community_reply')->Distinct(true)->field('reply_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//回复人数
                $praise_count = D('Community_praise')->Distinct(true)->field('praise_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//点赞人数
                if(!$people_dynamic_topic){
                    $people_dynamic_topic = array();
                }
                if(!$reply_count){
                    $reply_count = array();
                }
                if(!$praise_count){
                    $praise_count = array();
                }
                if(!$people_topic){
                    $people_topic = array();
                }
                
                $people_count=array();
                $people_count = array_merge($people_dynamic_topic,$reply_count,$praise_count,$people_topic);

                $temp=array();
                foreach ($people_count as $v) { 
                    $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
                    $temp[] = $v; 
                } 

                $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
                foreach ($temp as $k => $v){
                    $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
                }
                $list[$key]['people_count']  = count($temp);
                $list[$key]['note_total_count'] = $note_total_count;//帖子总数
                }
        }
        $info_list = array(
                'total' => $total,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $list
        );
        $this->returnCode(0,$info_list);
    }

    /**
    * [type_topic_list 我关注的、我创建的、最近的话题]
    * @return [type] [description]
    */
    public function type_topic_list(){
    //全部分类
    if($_POST['type'] == 'all'){
        $pageSize = 10;
        $page= $_POST['page'] ? $_POST['page'] : 1 ;
        // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
        if ($pageSize > 0) {
            $where['community_id'] = $_POST['community_id'];
            if(!$_POST['community_id']){
                returnCode(L_('没有接收到参数!'));
            }
            $total = D('Community_topic')->count();
            $page = isset($page) ? intval($page) : 1;
            $totalPage = ceil($total / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            $list  = D('Community_topic')
                ->where($where)
                ->order('topic_addtime desc')
                ->limit($firstRow.','.$pageSize)
                ->select();
            foreach($list as $key=>$value){
            //帖子总数
                $note_total_count = D('Community_dynamic_topic_bind')->where(array('topic_id'=>$value['topic_id']))->count();

                if($value['topic_img']){
                    $list[$key]['topic_img'] = unserialize($value['topic_img']);
                    $topic_img = $list[$key]['topic_img'];
                    $list[$key]['topic_img'] = array();
                    foreach($topic_img as $k=>$v){
                        $list[$key]['topic_img'][] = replace_file_domain($v);
                    }
                }
                //创建人
                $people_topic = D('Community_topic')->field('topic_user_id as user_id')->where(array('topic_id'=>$value['topic_id'],'community_id'=>$_POST['community_id']))->select();
        
                //帖子人数                                              
                $people_dynamic_topic = D('Community_dynamic')
                                        ->alias('a')
                                        ->Distinct(true)
                                        ->field('a.user_id')
                                        ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                        ->where(array('b.topic_id'=>$value['topic_id']))
                                        ->select();
                
                $reply_count = D('Community_reply')->Distinct(true)->field('reply_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//回复人数
                $praise_count = D('Community_praise')->Distinct(true)->field('praise_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//点赞人数
                if(!$people_dynamic_topic){
                    $people_dynamic_topic = array();
                }
                if(!$reply_count){
                    $reply_count = array();
                }
                if(!$praise_count){
                    $praise_count = array();
                }
                if(!$people_topic){
                    $people_topic = array();
                }
                
                $people_count=array();
                $people_count = array_merge($people_dynamic_topic,$reply_count,$praise_count,$people_topic);

                $temp=array();
                foreach ($people_count as $v) { 
                    $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
                    $temp[] = $v; 
                } 

                $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
                foreach ($temp as $k => $v){
                    $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
                }
                $list[$key]['people_count']  = count($temp);
                $list[$key]['note_total_count'] = $note_total_count;//帖子总数
                }
        }
    }
        //我关注的话题
        if($_POST['type'] == 'focus'){
            $where['a.topic_is_del'] = 0;
            $where['b.user_id'] = $this->_uid;
            $where['a.community_id'] = $_POST['community_id'];
            if(!$_POST['community_id']){
                returnCode(L_('没有接收到参数!'));
            }
            $pageSize = 10;
            $page= $_POST['page'] ? $_POST['page'] : 1;
            // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
            if ($pageSize > 0) {
                $total = D('Community_topic')->alias('a')->join('join pigcms_community_user_topic_bind as b on a.topic_id=b.topic_id')->where($where)->count();
                $page = isset($page) ? intval($page) : 1;
                $totalPage = ceil($total / $pageSize);
                $firstRow = $pageSize * ($page - 1);
                $list = D('Community_topic')
                        ->alias('a')
                        ->field('a.*,b.topic_last_time')
                        ->join('join pigcms_community_user_topic_bind as b on a.topic_id=b.topic_id')
                        ->where($where)
                        ->limit($firstRow.','.$pageSize)
                        ->select();
                foreach ($list as $key => $value) {
                    $new_time = D('Community_dynamic')
                    ->alias('a')
                    ->field('a.content,a.addtime,b.nickname')
                    ->join('join pigcms_user as b on a.user_id=b.uid')
                    ->join('join pigcms_community_dynamic_topic_bind as c on a.id=c.dynamic_id')
                    ->where(array('c.topic_id'=>$value['topic_id']))
                    ->order('addtime desc')
                    ->find();
                    $list[$key]['new_message'] = $new_time;

                    if($value['topic_img']){
                        $list[$key]['topic_img'] = unserialize($value['topic_img']);
                        $topic_img = $list[$key]['topic_img'];
                        $list[$key]['topic_img'] = array();
                        foreach($topic_img as $k=>$v){
                            $list[$key]['topic_img'][] = replace_file_domain($v);
                        }
                    }

                //帖子总数
                $note_total_count = D('Community_dynamic_topic_bind')->where(array('topic_id'=>$value['topic_id']))->count();

                if($value['topic_img']){
                    $list[$key]['topic_img'] = unserialize($value['topic_img']);
                    $topic_img = $list[$key]['topic_img'];
                    $list[$key]['topic_img'] = array();
                    foreach($topic_img as $k=>$v){
                        $list[$key]['topic_img'][] = replace_file_domain($v);
                    }
                }
                //创建人
                $people_topic = D('Community_topic')->field('topic_user_id as user_id')->where(array('topic_id'=>$value['topic_id'],'community_id'=>$_POST['community_id']))->select();
                //帖子人数                                              
                $people_dynamic_topic = D('Community_dynamic')
                                        ->alias('a')
                                        ->Distinct(true)
                                        ->field('a.user_id')
                                        ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                        ->where(array('b.topic_id'=>$value['topic_id']))
                                        ->select();
                
                $reply_count = D('Community_reply')->Distinct(true)->field('reply_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//回复人数
                $praise_count = D('Community_praise')->Distinct(true)->field('praise_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//点赞人数
                if(!$people_dynamic_topic){
                    $people_dynamic_topic = array();
                }
                if(!$reply_count){
                    $reply_count = array();
                }
                if(!$praise_count){
                    $praise_count = array();
                }
                if(!$people_topic){
                    $people_topic = array();
                }
                
                $people_count=array();
                $people_count = array_merge($people_dynamic_topic,$reply_count,$praise_count,$people_topic);

                $temp=array();
                foreach ($people_count as $v) { 
                    $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
                    $temp[] = $v; 
                } 

                $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
                foreach ($temp as $k => $v){
                    $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
                }
                $list[$key]['people_count']  = count($temp);
                $list[$key]['note_total_count'] = $note_total_count;//帖子总数
                }     
            }
            
        }
        //我发布的话题
        if($_POST['type'] == 'found'){
            $where['topic_is_del'] = 0;
            $pageSize = 10;
            $page= $_POST['page'] ? $_POST['page'] : 1;
            $where['topic_user_id'] = $this->_uid;
            $where['community_id'] = $_POST['community_id'];
            if(!$_POST['community_id']){
                returnCode(L_('没有接收到参数!'));
            }
            // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
            if ($pageSize > 0) {
                $total = D('Community_topic')->where($where)->count();
                $page = isset($page) ? intval($page) : 1;
                $totalPage = ceil($total / $pageSize);
                $firstRow = $pageSize * ($page - 1);
                $list  = D('Community_topic')->where($where)->limit($firstRow.','.$pageSize)->select();
                foreach($list as $key=>$value){
                    if($value['topic_img']){
                        $list[$key]['topic_img'] = unserialize($value['topic_img']);
                        $topic_img = $list[$key]['topic_img'];
                        $list[$key]['topic_img'] = array();
                        foreach($topic_img as $k=>$v){
                            $list[$key]['topic_img'][] = replace_file_domain($v);
                        }
                    }
                //帖子总数
                $note_total_count = D('Community_dynamic_topic_bind')->where(array('topic_id'=>$value['topic_id']))->count();

                if($value['topic_img']){
                    $list[$key]['topic_img'] = unserialize($value['topic_img']);
                    $topic_img = $list[$key]['topic_img'];
                    $list[$key]['topic_img'] = array();
                    foreach($topic_img as $k=>$v){
                        $list[$key]['topic_img'][] = replace_file_domain($v);
                    }
                }
                
                //创建人
                $people_topic = D('Community_topic')->field('topic_user_id as user_id')->where(array('topic_id'=>$value['topic_id'],'community_id'=>$_POST['community_id']))->select();
                //帖子人数                                              
                $people_dynamic_topic = D('Community_dynamic')
                                        ->alias('a')
                                        ->Distinct(true)
                                        ->field('a.user_id')
                                        ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                        ->where(array('b.topic_id'=>$value['topic_id']))
                                        ->select();
                
                $reply_count = D('Community_reply')->Distinct(true)->field('reply_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//回复人数
                $praise_count = D('Community_praise')->Distinct(true)->field('praise_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//点赞人数
                if(!$people_dynamic_topic){
                    $people_dynamic_topic = array();
                }
                if(!$reply_count){
                    $reply_count = array();
                }
                if(!$praise_count){
                    $praise_count = array();
                }
                if(!$people_topic){
                    $people_topic = array();
                }
                
                $people_count=array();
                $people_count = array_merge($people_dynamic_topic,$reply_count,$praise_count,$people_topic);

                $temp=array();
                foreach ($people_count as $v) { 
                    $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
                    $temp[] = $v; 
                } 

                $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
                foreach ($temp as $k => $v){
                    $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
                }
                $list[$key]['people_count']  = count($temp);
                $list[$key]['note_total_count'] = $note_total_count;//帖子总数
                }
            }
        }
        //最近的话题
        if($_POST['type'] == 'recent'){
            $pageSize = 10;
            $page= $_POST['page'] ? $_POST['page'] : 1 ;
            // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
            if ($pageSize > 0) {
                $where['community_id'] = $_POST['community_id'];
                if(!$_POST['community_id']){
                    returnCode(L_('没有接收到参数!'));
                }
                $total = 5;
                $page = isset($page) ? intval($page) : 1;
                $totalPage = ceil($total / $pageSize);
                $firstRow = $pageSize * ($page - 1);
                $list  = D('Community_topic')
                    ->where($where)
                    ->order('topic_addtime desc')
                    ->limit(0,5)
                    ->select();
                foreach($list as $key=>$value){
                    if($value['topic_img']){
                        $list[$key]['topic_img'] = unserialize($value['topic_img']);
                        $topic_img = $list[$key]['topic_img'];
                        $list[$key]['topic_img'] = array();
                        foreach($topic_img as $k=>$v){
                            $list[$key]['topic_img'][] = replace_file_domain($v);
                        }
                    }
                //帖子总数
                $note_total_count = D('Community_dynamic_topic_bind')->where(array('topic_id'=>$value['topic_id']))->count();

                if($value['topic_img']){
                    $list[$key]['topic_img'] = unserialize($value['topic_img']);
                    $topic_img = $list[$key]['topic_img'];
                    $list[$key]['topic_img'] = array();
                    foreach($topic_img as $k=>$v){
                        $list[$key]['topic_img'][] = replace_file_domain($v);
                    }
                }
        
                //帖子人数                                              
                $people_dynamic_topic = D('Community_dynamic')
                                        ->alias('a')
                                        ->Distinct(true)
                                        ->field('a.user_id')
                                        ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                        ->where(array('b.topic_id'=>$value['topic_id']))
                                        ->select();
                
                $reply_count = D('Community_reply')->Distinct(true)->field('reply_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//回复人数
                $praise_count = D('Community_praise')->Distinct(true)->field('praise_user_id as user_id')->where(array('topic_id'=>$value['topic_id']))->select();//点赞人数
                if(!$people_dynamic_topic){
                    $people_dynamic_topic = array();
                }
                if(!$reply_count){
                    $reply_count = array();
                }
                if(!$praise_count){
                    $praise_count = array();
                }
                
                $people_count=array();
                $people_count = array_merge($people_dynamic_topic,$reply_count,$praise_count);

                $temp=array();
                foreach ($people_count as $v) { 
                    $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
                    $temp[] = $v; 
                } 

                $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
                foreach ($temp as $k => $v){
                    $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
                }
                $list[$key]['people_count']  = count($temp);
                $list[$key]['note_total_count'] = $note_total_count;//帖子总数
                }
            }
        }
            $info_list = array(
                    'total' => $total,
                    'pageTotal' => $totalPage,
                    'has_more' => $totalPage > $page ? true : false,
                    'list' => $list
            );
            if($info_list != false){
                    $this->returnCode(0,$info_list);
                }else{
                    $this->returnCode(1,array(),L_('没有查询到结果!'));
            }
    }


    

    /**
     * [note_list 帖子列表]
     * @return [type] [description]
     */
    public function note_list(){
        D('Community_user_topic_bind')->where(array('topic_id'=>$_POST['topic_id'],'user_id'=>$this->_uid))->data(array('topic_last_time'=>time()))->save();
        
        $whe['a.topic_is_del'] = 0;
        $whe['a.topic_id'] = $_POST['topic_id'];
        $whe['a.community_id'] = $_POST['community_id'];
        if(!$_POST['community_id']){
            $this->returnCode(L_('传递参数错误!'));
        }
        //该话题的信息
        $list_info = D('Community_topic')
                ->alias('a')
                ->field('a.*,b.nickname,b.avatar')
                ->join('join pigcms_user as b on a.topic_user_id=b.uid')
                ->where($whe)
                ->find();
        if($_POST['topic_id'] && $this->_uid){
            $is_concern = D('Community_user_topic_bind')->where(array('topic_id'=>$_POST['topic_id'],'user_id'=>$this->_uid))->find();

            if($is_concern){
                $list_info['concern'] = true;
            }else{
                $list_info['concern'] = false;
            }
        }        
        if($list_info['topic_img']){
            $list_info['topic_img'] = unserialize($list_info['topic_img']);
            $topic_img = $list_info['topic_img'];
            $list_info['topic_img'] = array();
            foreach($topic_img as $k=>$v){
                $list_info['topic_img'][] = replace_file_domain($v);
            }
        }
        
        $dynamic_topic_people = D('Community_dynamic')
                                ->alias('a')
                                ->Distinct(true)
                                ->field('a.id,a.user_id,c.nickname,c.avatar')
                                ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                ->join('join pigcms_user as c on a.user_id=c.uid')
                                ->where(array('b.topic_id'=>$list_info['topic_id']))
                                ->select();
        foreach ($dynamic_topic_people as $kk => $vv) {
            $where1['a.dynamic_id'] = $vv['id'];
            $where1['a.reply_is_del'] = 0;
            //回复的人
            $people_reply = D('Community_reply')
                            ->alias('a')
                            ->Distinct(true)
                            ->field('a.reply_user_id as user_id,b.avatar,b.nickname')
                            ->join('join pigcms_user as b on a.reply_user_id=b.uid')
                            ->where($where1)
                            ->select();
            //点赞的人
            $where2['a.dynamic_id'] = $vv['id'];
            $people_praise = D('Community_praise')
                            ->alias('a')
                            ->Distinct(true)
                            ->field('a.praise_user_id as user_id,b.avatar,b.nickname')
                            ->join('join pigcms_user as b on a.praise_user_id=b.uid')
                            ->where($where2)
                            ->select();
            $arrs[$kk]['avatar'] = $vv['avatar'];
            $arrs[$kk]['nickname'] = $vv['nickname'];
        }
        foreach($people_reply as $k=>$v){
            $arrs_reply[$k]['avatar'] = $v['avatar'];
            $arrs_reply[$k]['nickname'] = $v['nickname'];
        }
        foreach($people_praise as $k=>$v){
            $arrs_praise[$k]['avatar'] = $v['avatar'];
            $arrs_praise[$k]['nickname'] = $v['nickname'];
        }
        $info['people_avatar'] = array_unique($arrs,SORT_REGULAR);
        $info['people_reply'] = array_unique($arrs_reply);
        $info['people_praise'] = array_unique($arrs_praise);
        if(!$info['people_avatar'] && $info['people_reply'] && $info['people_praise']){
            $datas = array_merge($info['people_reply'],$info['people_praise']);
        }
        if(!$info['people_reply'] && $info['people_avatar'] && $info['people_praise']){
            $datas = array_merge($info['people_avatar'],$info['people_praise']);
        }
        if(!$info['people_praise'] && $info['people_avatar'] && $info['people_reply']){
            $datas = array_merge($info['people_avatar'],$info['people_reply']);
        }
        if(!$info['people_praise'] && !$info['people_reply']){
            $datas = $info['people_avatar'];
        }
        if($info['people_avatar'] && $info['people_reply'] && $info['people_praise']){
            $datas = array_merge($info['people_avatar'],$info['people_reply'],$info['people_praise']);
        }
        
        foreach($datas[0] as $k => $v){
            $arr_inner_key[]= $k;   //先把二维数组中的内层数组的键值记录在在一维数组中
        }
        foreach ($datas as $k => $v){
            $v =join(",",$v);    //降维 用implode()也行
            $temp[$k] =$v;      //保留原来的键值 $temp[]即为不保留原来键值
        }
        $temp =array_unique($temp);    //去重：去掉重复的字符串
        foreach ($temp as $k => $v){
            $a = explode(",",$v);   //拆分后的重组 如：Array( [0] => james [1] => 30 )
            $arr_after[$k]= array_combine($arr_inner_key,$a);  //将原来的键与值重新合并
        }
        $temp=array();
        $list_info['people_info'] = $arr_after;
        //创建人
        $people_topic = D('Community_topic')->field('topic_user_id as user_id')->where(array('topic_id'=>$list_info['topic_id'],'community_id'=>$_POST['community_id']))->select();
        //帖子人数                                              
        $people_dynamic_topic = D('Community_dynamic')
                                ->alias('a')
                                ->Distinct(true)
                                ->field('a.user_id')
                                ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                                ->where(array('b.topic_id'=>$list_info['topic_id']))
                                ->select();
        
        $reply_count = D('Community_reply')->Distinct(true)->field('reply_user_id as user_id')->where(array('topic_id'=>$list_info['topic_id']))->select();//回复人数
        $praise_count = D('Community_praise')->Distinct(true)->field('praise_user_id as user_id')->where(array('topic_id'=>$list_info['topic_id']))->select();//点赞人数
        if(!$people_dynamic_topic){
            $people_dynamic_topic = array();
        }
        if(!$reply_count){
            $reply_count = array();
        }
        if(!$praise_count){
            $praise_count = array();
        }
        if(!$people_topic){
            $people_topic = array();
        }
        
        $people_count=array();
        $people_count = array_merge($people_dynamic_topic,$reply_count,$praise_count,$people_topic);

        $temp=array();
        foreach ($people_count as $v) { 
            $v = join(",",$v); //降维,也可以用implode,将一维数组转换为用逗号连接的字符串 
            $temp[] = $v; 
        } 

        $temp = array_unique($temp); //去掉重复的字符串,也就是重复的一维数组 
        foreach ($temp as $k => $v){
            $temp[$k] = explode(",",$v); //再将拆开的数组重新组装 
        }
        $list_info['people_count']  = count($temp);
//------------------------------------------------------------------------------
        //-----帖子列表的信息------//
        $where['a.is_del'] = 0;
        if($_POST['topic_id']){
            $where['a.topic_id'] = $_POST['topic_id'];
        }

        $pageSize = 10;
        $page= $_POST['page'] ? $_POST['page'] : 1;
        
        // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
        if ($pageSize > 0) {
            $where_dynamic['_string'] = '`topic_id` in (' . $_POST['topic_id'] .')';

            $total = D('Community_dynamic_topic_bind')->where($where_dynamic)->count();

            $page = isset($page) ? intval($page) : 1;
            $totalPage = ceil($total / $pageSize);
            $firstRow = $pageSize * ($page - 1);
            
            $list = D('Community_dynamic')
                        ->alias('a')
                        ->field('a.*,b.topic_id,c.avatar,c.nickname')
                        ->join('join pigcms_community_dynamic_topic_bind as b on a.id=b.dynamic_id')
                        ->join('join pigcms_user as c on a.user_id=c.uid')
                        ->order('a.addtime desc')
                        ->where(array('a.is_del'=>0,'b.topic_id'=>$_POST['topic_id']))
                        ->limit($firstRow.','.$pageSize)
                        ->select();//符合话题的帖子包括动态
            foreach($list as $key=>$value){
                if($value['id']){
                    $maple['_string'] = '`dynamic_id` in (' . $value['id'] .')';
                }
                $maple['reply_is_del'] = 0;
                $reply = D('Community_reply')->where($maple)->select();
                foreach($reply as $kk=>$vv){
                    $user = D('User')->field('nickname')->where(array('uid'=>$reply[$kk]['reply_user_id']))->find();
                    $to_user = D('User')->field('nickname')->where(array('uid'=>$reply[$kk]['reply_to_user_id']))->find();
                    if($this->_uid != $vv['reply_user_id']){
                        $reply[$kk]['can_reply'] = true;
                    }else{
                        $reply[$kk]['can_reply'] = false;
                    }
                    $reply[$kk]['user'] = $user['nickname'];
                    $reply[$kk]['to_user'] = $to_user['nickname'];
                }
                
                if($value['img']){
                    $list[$key]['img'] = unserialize($value['img']);
                    $img = $list[$key]['img'];
                    $list[$key]['img'] = array();
                    foreach($img as $k=>$v){
                        if(!(strpos($v,'http') === 0)) {
                            $list[$key]['img'][] = replace_file_domain($v);
                        } else {
                            $list[$key]['img'][] = $v;
                        }
                    }
                }

                if($value['file']){
                    $list[$key]['file'] = unserialize($value['file']);
                    $dynamic_file = $list[$key]['file'];
                    $list[$key]['file'] = array();
                    if(!(strpos($dynamic_file['file_url'],'http') === 0)) {
                        $list[$key]['file']['file_url'] = replace_file_domain($dynamic_file['file_url']);
                    } else {
                        $list[$key]['file']['file_url'] = $dynamic_file['file_url'];
                    }
                    $list[$key]['file']['file_remark'] = $dynamic_file['file_remark'];
                    $list[$key]['file']['file_id'] = $dynamic_file['file_id'];
                    $list[$key]['file']['file_img'] = $dynamic_file['file_img'];
                    $list[$key]['file']['is_img'] = $dynamic_file['is_img'];
                }
                if($value['id']){
                    $praise_field = 'b.nickname';
                    $praise_join = 'join pigcms_user as b on a.praise_user_id=b.uid';
                    $praise_where = array('dynamic_id'=>$value['id']);  
                    $praise = D('Community_praise')->praise_select('true',$praise_field,$praise_join,$praise_where,'','');//动态的点赞
                   
                    $dynamic_praise = D('Community_praise')->where(array('dynamic_id'=>$value['id'],'praise_user_id'=>$this->_uid))->find();
                    if(!$dynamic_praise){
                        $list[$key]['can_praise'] = true;
                    }else{
                        $list[$key]['can_praise'] = false;
                    }           
                }
                $arr = D('Community_dynamic_topic_bind')->field('topic_id')->where(array('dynamic_id'=>$value['id']))->select();
                $array=array();
                foreach ($arr as $kkk => $vvv) {
                    $array[] =  $vvv['topic_id']; 
                }
                
                $map['topic_id'] = array('in',$array);
                $topic = D('Community_topic')->field('topic_id,topic_title')->where($map)->select();
                $list[$key]['note_addtime'] = time_info($list[$key]['note_addtime']);
                $list[$key]['reply'] = $reply;
                $list[$key]['praise'] = $praise;
                $list[$key]['topic_list'] = $topic;//每个帖子所关联的话题包括动态的帖子
                //是否可以为动态用户本人或群主
                if($list[$key]['id']){
                    $group_owner_uid = D('Community_info')->field('group_owner_uid')->where(array('community_id'=>$list[$key]['community_id']))->find();
                    
                    $dynamic_user_id = D('Community_dynamic')->field('user_id')->where(array('id'=>$list[$key]['id']))->find();//动态用户
                    if($this->_uid == $group_owner_uid['group_owner_uid'] || $this->_uid == $dynamic_user_id['user_id']){
                        $list[$key]['can_del'] = true;
                    }else{
                        $list[$key]['can_del'] = false;
                    }
                }
            }
            $info_list = array(
                'total' => $total,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $list,
                'topic_info' => $list_info,
            );
            $this->returnCode(0,$info_list);
        }
        
    }


    /**
    * [note_detail 帖子详情]
    * @return [type] [description]
    */
    public function note_detail(){
        if($_POST['id']){
            $wh['a.id'] = $_POST['id'];
            $wh['a.is_del'] = 0;
            $wh['c.topic_id'] = $_POST['topic_id'];
            $list = D('Community_dynamic')
                    ->alias('a')
                    ->field('a.*,b.avatar,b.nickname')
                    ->join('join pigcms_user as b on a.user_id=b.uid')
                    ->join('join pigcms_community_dynamic_topic_bind as c on a.id=c.dynamic_id')
                    ->where($wh)
                    ->order('a.addtime desc')
                    ->select();
        }
        foreach($list as $key=>$value){
            $maple=array();
            
            if($value['id']){
                $maple['_string'] = '`dynamic_id` in (' . $value['id'] .')';
            }
            $maple['reply_is_del'] = 0;
            $reply = D('Community_reply')->where($maple)->select();

            foreach($reply as $kk=>$vv){
                $user = D('User')->field('nickname')->where(array('uid'=>$reply[$kk]['reply_user_id']))->find();
                $to_user = D('User')->field('nickname')->where(array('uid'=>$reply[$kk]['reply_to_user_id']))->find();
                if($this->_uid != $vv['reply_user_id']){
                    $reply[$kk]['can_reply'] = true;
                }else{
                    $reply[$kk]['can_reply'] = false;
                }
                $reply[$kk]['user'] = $user['nickname'];
                $reply[$kk]['to_user'] = $to_user['nickname'];
                
            }
            
            if($value['img']){
                $list[$key]['img'] = unserialize($value['img']);
                $img = $list[$key]['img'];
                $list[$key]['img'] = array();
                foreach($img as $k=>$v){
                    if(!(strpos($v,'http') === 0)) {
                        $list[$key]['img'][] = replace_file_domain($v);
                    } else {
                        $list[$key]['img'][] = $v;
                    }
                }
            }
            if($value['file']){
                    $list[$key]['file'] = unserialize($value['file']);
                    $dynamic_file = $list[$key]['file'];
                    $list[$key]['file'] = array();
                    if(!(strpos($dynamic_file['file_url'],'http') === 0)) {
                        $list[$key]['file']['file_url'] = replace_file_domain($dynamic_file['file_url']);
                    } else {
                        $list[$key]['file']['file_url'] = $dynamic_file['file_url'];
                    }
                    $list[$key]['file']['file_remark'] = $dynamic_file['file_remark'];
                    $list[$key]['file']['file_id'] = $dynamic_file['file_id'];
                    $list[$key]['file']['file_img'] = $dynamic_file['file_img'];
                    $list[$key]['file']['is_img'] = $dynamic_file['is_img'];
                }
            if($value['id']){
                $praise_field = 'b.nickname';
                $praise_join = 'join pigcms_user as b on a.praise_user_id=b.uid';
                $praise_where = array('dynamic_id'=>$value['id']);  
                $praise = D('Community_praise')->praise_select('true',$praise_field,$praise_join,$praise_where,'','');//动态的点赞
                $dynamic_praise = D('Community_praise')->where(array('dynamic_id'=>$value['id'],'praise_user_id'=>$this->_uid))->find();
                if(!$dynamic_praise){
                    $list[$key]['can_praise'] = true;
                }else{
                    $list[$key]['can_praise'] = false;
                }           
            }
            $arr = D('Community_dynamic_topic_bind')->field('topic_id')->where(array('dynamic_id'=>$value['id']))->select();
            $array=array();
            foreach ($arr as $kkk => $vvv) {
                $array[] =  $vvv['topic_id']; 
            }
            
            $map['topic_id'] = array('in',$array);
            $topic = D('Community_topic')->field('topic_id,topic_title')->where($map)->select();
            $list[$key]['note_addtime'] = time_info($list[$key]['note_addtime']);
            $list[$key]['reply'] = $reply;
            $list[$key]['praise'] = $praise;
            $list[$key]['topic_list'] = $topic;
            //是否可以为动态用户本人或群主
                if($list[$key]['id']){
                    $group_owner_uid = D('Community_info')->field('group_owner_uid')->where(array('community_id'=>$list[$key]['community_id']))->find();
                    
                    $dynamic_user_id = D('Community_dynamic')->field('user_id')->where(array('id'=>$list[$key]['id']))->find();//动态用户
                    if($this->_uid == $group_owner_uid['group_owner_uid'] || $this->_uid == $dynamic_user_id['user_id']){
                        $list[$key]['can_del'] = true;
                    }else{
                        $list[$key]['can_del'] = false;
                    }
                }
            $info_list = array(
                'list' => $list
            );
            $this->returnCode(0,$info_list);
        }
    }


    /**
     * [del_note 帖子删除]
     * @return [type] [description]
     */
    public function del_note(){
        $data['user_id'] = $this->_uid;
        $data['community_id'] = $_POST['community_id'];  
        $data['id'] = $_POST['id'];  

        if($data){
            //动态处理逻辑
            if($data['community_id'] && $data['id']){
                $group_owner_uid = D('Community_info')->field('group_owner_uid')->where(array('community_id'=>$data['community_id']))->find();
                $dynamic_user_id = D('Community_dynamic')->field('user_id')->where(array('id'=>$data['id']))->find();
                if(!$dynamic_user_id){
                    $this->returnCode('300001',array(),L_('查询用户信息失败!'));
                }
                if(!$group_owner_uid){
                    $this->returnCode('300001',array(),L_('查询群主信息失败!'));
                }

                $arr=array_merge($dynamic_user_id,$group_owner_uid);
                
                if(!in_array($data['user_id'], $arr)){
                    $this->returnCode('200001',array(),L_('无权删除!'));
                }
                
                $result = D('Community_dynamic')->where(array('id'=>$data['id']))->data(array('is_del'=>1))->save();
            }
            
            if($result != false){
                $this->returnCode(0,L_('删除成功!'));
            }else{
                $this->returnCode(1,array(),L_('删除失败!'));
            }
        }else{
            $this->returnCode('100001',array(),L_('参数传递错误！'));
        }
        
    }

    /**
     * [focus_topic 话题关注]
     * @return [type] [description]
     */
    public function focus_topic(){
        $data['topic_id'] = $_POST['topic_id'];
        $data['user_id'] = $this->_uid;
        if(!$data){
            $this->returnCode('100001',array(),L_('传递参数错误！'));
        }
        $res = D('Community_user_topic_bind')->where(array('topic_id'=>$_POST['topic_id'],'user_id'=>$this->_uid))->find();
        if($res){
            $final = D('Community_user_topic_bind')->where(array('topic_id'=>$_POST['topic_id'],'user_id'=>$this->_uid))->delete();
            if($final){
                $this->returnCode(0,L_('已取消关注!'));
            }
        }
        $result = D('Community_user_topic_bind')->add($data);
        if($result){
            $this->returnCode(0,L_('关注成功!'));
        }else{
            $this->returnCode(1,array(),L_('关注失败!'));
        }
    }


//--------------------------------------------群话题end---------------------------------------------------------------//



    // 群广场功能区块
    /**
     * 获取群的信息
     */
    public function community_single_info() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $community_info = D('Community_info');
        $community_info_single = $community_info->get_community_info($community_id, 'community_id');
        if (empty($community_info_single)) {
            $this->returnCode('70000005');
        }
        $user = M('user')->where(array('uid' => $community_info_single['group_owner_uid']))->field('nickname, avatar')->find();
        if (!empty($user)) {
            $community_info_single['owner_user'] = $user;
        } else {
            $community_info_single['owner_user'] = array();
        }
        // 获取分类标签
        $community_category_info = D('Community_bind_category')->community_category_info($community_id);
        if (!empty($community_category_info)) {
            $community_info_single['community_category_info'] = $community_category_info;
        } else {
            $community_info_single['community_category_info'] = array();
        }
        if ($community_info_single['community_avatar']) {
            $community_info_single['community_avatar'] = file_domain() . '/upload/comm/' . $community_info_single['community_avatar'];
        }
        if ($community_info_single['avatar']) {
            $community_info_single['avatar'] = replace_file_domain($community_info_single['avatar']);
        }
        unset($community_info_single['openGId']);
        unset($community_info_single['income_money']);

        // 查询一下是否加入当前群
        $where_join = array('add_uid' => $uid, 'add_status' => array('in', array(2,3)));
        $where_join['community_id'] = $community_id;
        if (M('Community_join')->where($where_join)->count()) {
            $community_info_single['is_join'] = true;
        } else {
            $community_info_single['is_join'] = false;
        }

        $this->returnCode(0, $community_info_single);
    }

    /**
     * 删除群于分类的关联-一旦删除无法复原
     */
    public function del_community_category(){
        $bind_id = intval($_POST['bind_id']);
        // 查询单条信息-确认是否存在该信息
        $community_bind_category = D('Community_bind_category');
        $bind_info = $community_bind_category->get($bind_id);
        if (!$bind_info) {
            $this->returnCode('72000002');
        }
        if($community_bind_category->del($bind_id)){
            $this->returnCode(0, L_('删除成功！'));
        }else{
            $this->returnCode(1, L_('删除失败！请重试~'));
        }
    }

    /**
     * 群-获取二级分类类别
     */
    public function community_category() {
        $community_category = D('Community_category');
        $community_category_info = $community_category->category_info_list(array('subdir' => 1,'cat_status'=>1));
        if (!empty($community_category_info)) {
            $tmp = array();
            $site_url = file_domain() . '/upload/system/';
            foreach ($community_category_info as $vv) {
                $subdir_info = $community_category->get_Subdirectory($vv['cid'], 2);
                if ($vv['cat_pic']) $vv['cat_pic'] = $site_url . $vv['cat_pic'];
                if ($vv['cat_select_pic']) $vv['cat_select_pic'] = $site_url . $vv['cat_select_pic'];
                if (!empty($subdir_info)) {
                    $vv['subdir_info'] = $subdir_info;
                    $tmp[] = $vv;
                } else {
                    $vv['subdir_info'] = array();
                    $tmp[] = $vv;
                }
            }
        } else {
            $tmp = array();
        }
        $this->returnCode(0, $tmp);
    }

    /**
     * 获取发现首页信息
     */
    public function discover_category_index() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_category = D('Community_category');
        $community_info = D('Community_info');
        $community_bind_category = D('Community_bind_category');
        $lat = empty($_POST['lat']) ? '' : $_POST['lat'];
        $lng = empty($_POST['lng']) ? '' : $_POST['lng'];
        $data = array();
        // 获取热门分类
        $hot_category = $community_category->hot_category_list();
        if ($hot_category) {
            $data['hot_category'] = $hot_category;
        } else {
            $data['hot_category'] = array();
        }
        if ($lat && $lng && floatval($lat) > 0 && floatval($lng) > 0) {
            $data['is_location'] = true;
        } else {
            $data['is_location'] = false;
        }
        // 获取附近的群
        if ($lat && $lng) {
            $nearby = $community_info->hasCommunity($lat, $lng, 3);
            if ($nearby) {
                foreach ($nearby as &$val) {
                    if ($lat && $lng && floatval($val['latitude']) > 0 && floatval($val['longitude']) > 0) {
                        $val['distance'] = getDistance($lat, $lng, $val['latitude'], $val['longitude']) / 1000;
                    }
                    if ($val['avatar']) $val['avatar'] = replace_file_domain($val['avatar']);
                    if ($val['community_avatar']) $val['community_avatar'] = file_domain() . '/upload/comm/' . $val['community_avatar'];
                    if ($val['community_id']) $val['community_category_info'] = $community_bind_category->community_category_info($val['community_id']);
                }
                $data['nearby'] = $nearby;
            } else {
                $data['nearby'] = array();
            }
        } else {
            $data['nearby'] = array();
        }
        // 获取群推荐
        $recommend = $community_info->recommend_community($this->_uid);
        if ($recommend) {
            foreach ($recommend as &$value) {
                if ($lat && $lng && floatval($value['latitude']) > 0 && floatval($value['longitude']) > 0) {
                    $value['distance'] = getDistance($lat, $lng, $value['latitude'], $value['longitude']) / 1000;
                } else {
                    $value['distance'] = '';
                }
                if ($value['avatar']) $value['avatar'] = replace_file_domain($value['avatar']);
                if ($value['community_avatar']) $value['community_avatar'] =  file_domain() . '/upload/comm/' . $value['community_avatar'];
                if ($value['community_id']) $value['community_category_info'] = $community_bind_category->community_category_info($value['community_id']);
            }
            $data['recommend'] = $recommend;
        } else {
            $data['recommend'] = array();
        }
        $this->returnCode(0, $data);
    }

    /**
     * 获取对应分类下的群信息
     */
    public function category_community_info() {
        if (!$_POST['cid']) {
            $this->returnCode('72000001');
        }
        $uid = 0;
        if ($this->_uid) {
            $uid = $this->_uid;
        }
        $lat = empty($_POST['lat']) ? '' : $_POST['lat'];
        $lng = empty($_POST['lng']) ? '' : $_POST['lng'];
        $page = empty($_POST['page']) ? 1 : intval($_POST['page']);
        $cid = $_POST['cid'];
        $community_bind_category = D('Community_bind_category');
        $where = '`cbd`.`cid` = ' . $cid;
        $community_msg = $community_bind_category->category_community_info_list($where, 10, $page);
        if (!empty($community_msg['list'])) {
            // 计算一下群与当前用户的距离
            foreach ($community_msg['list'] as &$val) {
                if ($lat && $lng && floatval($val['latitude']) > 0 && floatval($val['longitude']) > 0) {
                    $val['distance'] = getDistance($lat, $lng, $val['latitude'], $val['longitude']) / 1000;
                } else {
                    $val['distance'] = '';
                }
                if ($val['avatar']) $val['avatar'] = replace_file_domain($val['avatar']);
                if ($val['community_avatar']) $val['community_avatar'] = file_domain() . '/upload/comm/' . $val['community_avatar'];
                $val['community_category_info'] = $community_bind_category->community_category_info($val['community_id']);
            }
        }
        $this->returnCode(0, $community_msg);
    }

    /**
     * 修改群信息
     */
    public  function community_edit() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $uid = $this->_uid;
        $database_info = M('Community_info');
        // 查询一下该群是否正常存在
        $community_info_single = $database_info->where(array('community_id'=> $community_id))->find();
        if (!$community_info_single) {
            $this->returnCode('70000005');
        }
        if ($community_info_single['status'] == 2) {
            $this->returnCode('70000007');
        }
        if ($community_info_single['status'] == 3) {
            $this->returnCode('70000008');
        }
        // 群地址
        $data['address'] = $_POST['address'] ? $_POST['address'] : '';
        $data['longitude'] = $_POST['longitude'] ? $_POST['longitude'] : '';
        $data['latitude'] = $_POST['latitude'] ? $_POST['latitude'] : '';
        $data['province_name'] = $_POST['province_name'] ? $_POST['province_name'] : '';
        $data['city_name'] = $_POST['city_name'] ? $_POST['city_name'] : '';
        $data['area_name'] = $_POST['area_name'] ? $_POST['area_name'] : '';
        // 群介绍
        $data['community_des'] = $_POST['community_des'] ? $_POST['community_des'] : '';
        if ($data['community_des']) {
            $check = D('Community_file')->msgSecCheck($data['community_des']);
            if ($check['errcode'] != 0) {
                $this->returnCode(1,array(),L_('群介绍内容中含有违法违规内容'));
            }
        }
        // 群头像
        if(!empty($_FILES) && $_FILES['file']['error'] != 4){
            $image = D('Image')->handle($uid, 'comm', 1, array('size' => 10), false, true);
            if (!$image['error']) {
                $_POST = array_merge($_POST, str_replace('/upload/comm/', '', $image['url']));
            } else {
                $this->returnCode(1,array(), $image['message']);
            }
        }
        // 群头像
        if ($_POST['image']) {
            $data['community_avatar'] = $_POST['image'];
        }
        $data['set_time'] = time();
        $set = $database_info->where(array('community_id'=> $community_id))->data($data)->save();
        if ($set) {
            // 判断是否传值分类id  cid_arr [1,2,3]
            $cid_arr = json_decode(htmlspecialchars_decode($_POST['cid_arr']), true);
            if (!empty($cid_arr) && is_array($cid_arr)) {
                $community_bind_category = D('Community_bind_category');
                $m_community_bind_category = M('Community_bind_category');
                $cout = $community_bind_category->community_category_num($community_id);
                $cid_count = count($cid_arr);
                if ($cout && (intval($cout) + intval($cid_count)) > 3) {
                    $this->returnCode(1,array(), L_('分类标签数不得大于3个！请重试'));
                }
                $cid_str = implode(',', $cid_arr);
                $community_category_count = M('community_category')->where(array('cid'=>array('in', $cid_str), 'cat_status' => 1))->count();
                if ($community_category_count != $cid_count) {
                    $this->returnCode(1,array(), L_('所选标签存在被禁用状态！请重试'));
                }
                // 去除已经添加上的标签
                $add_info = array(
                    'community_id' => $community_id,
                    'add_time' => time()
                );
                $where =array(
                    'community_id' => $community_id
                );
                foreach ($cid_arr as $val) {
                    $add_info['cid'] = $val;
                    $where['cid'] = $val;
                    if (!$m_community_bind_category->field('bind_id')->where($where)->find()) {
                        $m_community_bind_category->data($add_info)->add();
                    }
                }
            }
            $this->returnCode(0,array('community_id'=>$community_id));
        } else {
            $this->returnCode(1,array(),L_('编辑失败！请重试'));
        }
    }

    /**
     * 按照群名称搜索群
     */
    public function search_community() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $lat = empty($_POST['lat']) ? '' : $_POST['lat'];
        $lng = empty($_POST['lng']) ? '' : $_POST['lng'];
        if (!empty($_POST['community_name'])) {
            $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;

//            $community_join_info = M('Community_join')->field('community_id')->where(array('add_uid' => $uid, 'add_status' => array('in', array(2,3))))->select();
//            $community_join_str = '';
//            // 查询一下关联的分类信息
//            $community_join_arr = array();
//            if ($community_join_info) {
//                foreach ($community_join_info as $val) {
//                    $community_join_arr[] = $val['community_id'];
//                }
////              只查询自己未加入的群
//              $community_join_str = implode(',', $community_join_arr);
//            }
            $where = "`ci`.`openGId` <> ''";
            $where .= ' AND `ci`.`status` = 1 AND `ci`.`group_mode` = 2 AND `ci`.`community_name` like "%' . $_POST['community_name'] . '%"';

//            if ($community_join_str) {
////              只查询自己未加入的群
//                $where .= ' AND `ci`.`community_id` NOT IN (' . $community_join_str . ')';
//            }
            $info = D('Community_info')->community_msg_list($where, 8, $page);
            if (!empty($info['list'])) {
                $d_community_bind_category = D('Community_bind_category');
                foreach ($info['list'] as &$val) {
                    $val['category_info'] = $d_community_bind_category->community_category_info($val['community_id']);
                    if ($val['avatar']) $val['avatar'] = replace_file_domain($val['avatar']);
                    if ($val['community_avatar']) $val['community_avatar'] = file_domain() . '/upload/comm/' . $val['community_avatar'];
                    if ($lat && $lng && floatval($val['latitude']) > 0 && floatval($val['longitude']) > 0) {
                        $val['distance'] = getDistance($lat, $lng, $val['latitude'], $val['longitude']) / 1000;
                    } else {
                        $val['distance'] = '';
                    }
                }
            }
            $this->returnCode(0,$info);
        } else {
            $this->returnCode(0,array());
        }
    }


    // 群文件功能区块
    /**
     * 创建群文件夹
     */
    public function add_folder(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $folder_name = $_POST['folder_name'];
        if (empty($folder_name)) {
            $this->returnCode('72000003');
        }
        if ($folder_name) {
            $check = D('Community_file')->msgSecCheck($folder_name);
            if ($check['errcode'] != 0) {
                $this->returnCode(1,array(),L_('群文件夹名称中含有违法违规内容'));
            }
        }
        if (M('Community_folder')->where(array('folder_name' =>$folder_name, 'folder_status' => array('neq', 3), 'community_id' => $community_id))->find()) {
            $this->returnCode('72000024');
        }
        $uid = $this->_uid;
        // 获取群部分信息和群关于群文件夹权限信息
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        if (!empty($community_development['is_add_folder']) && $community_development['is_add_folder'] == 2 && $uid != $community_development['group_owner_uid']) {
            $this->returnCode('72000018');
        }
        $data = array();
        $data['community_id'] = $community_id;
        $data['folder_uid'] = $uid;
        $data['folder_name'] = $folder_name;
        if (!empty($_POST['other_is_upload']) && intval($_POST['other_is_upload']) == 1) {
            $data['other_is_upload'] = 1;
        } else {
            $data['other_is_upload'] = 2;
        }
        $data['folder_status'] = 1;
        $data['add_time'] = time();
        $folder_id = M('Community_folder')->data($data)->add();
        if ($folder_id) {
            $this->returnCode(0,array('folder_id'=>$folder_id));
        } else {
            $this->returnCode(1,array(),L_('添加失败！请重试'));
        }
    }

    /**
     * 编辑群文件夹
     */
    public function edit_folder(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $folder_id = intval($_POST['folder_id']);
        if (empty($folder_id)) {
            $this->returnCode('72000004');
        }
        $m_community_folder = M('Community_folder');
        $folder_single = $m_community_folder->where(array('folder_id' => $folder_id))->find();
        if (!$folder_single || intval($folder_single['folder_status']) >= 2) {
            $this->returnCode('72000005');
        }
        if ($uid != $folder_single['folder_uid']) {
            $this->returnCode('72000006');
        }
        $data = array();
        if (!empty($_POST['folder_name']) && $_POST['folder_name'] != $folder_single['folder_name']) {
            $folder_name = $_POST['folder_name'];
            if (M('Community_folder')->where(array('folder_name' =>$folder_name, 'folder_status' => array('neq', 3), 'community_id' => $folder_single['community_id']))->find()) {
                $this->returnCode('72000024');
            }
            $data['folder_name'] = $folder_name;
            if ($folder_name) {
                $check = D('Community_file')->msgSecCheck($folder_name);
                if ($check['errcode'] != 0) {
                    $this->returnCode(1,array(),L_('群文件夹名称中含有违法违规内容'));
                }
            }
        }
        if (!empty($_POST['other_is_upload']) && intval($_POST['other_is_upload']) != intval($folder_single['other_is_upload'])) {
            if (in_array(intval($_POST['other_is_upload']), array(1, 2))) {
                $data['other_is_upload'] = $_POST['other_is_upload'];
            }
        }
        if (!empty($data)) {
            $folder_id = $m_community_folder->where(array('folder_id' => $folder_id))->data($data)->save();
            if ($folder_id) {
                foreach ($data as $k => $v) {
                    if (array_key_exists($k, $folder_single)) {
                        $folder_single[$k] = $v;
                    }
                }
                $community_info = M('Community_info')->field('group_owner_uid')->where(array('community_id' => $folder_single['community_id']))->find();
                $user = M('User')->where(array('uid' => $folder_single['folder_uid']))->field('nickname')->find();
                $folder_single['time_info'] = time_info($folder_single['add_time']);
                $folder_single['group_owner_uid'] = $community_info['group_owner_uid'];
                $folder_single['nickname'] = $user['nickname'];
                if ($folder_single['folder_uid'] == $uid) {
                    $folder_single['is_del'] = true;
                    $folder_single['is_edit'] = true;
                } elseif($community_info && $community_info['group_owner_uid'] == $uid) {
                    $folder_single['is_del'] = true;
                    $folder_single['is_edit'] = false;
                } else {
                    $folder_single['is_del'] = false;
                    $folder_single['is_edit'] = false;
                }
                $this->returnCode(0, $folder_single);
            } else {
                $this->returnCode(1,array(),L_('编辑失败！请重试'));
            }
        } else {
            $this->returnCode(0,array('folder_id'=>$folder_id));
        }
    }

    /**
     * 删除文件夹 群主和创建者均可删除，后台也可以进行禁用操作
     */
    public function del_folder(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $folder_id = intval($_POST['folder_id']);
        if (empty($folder_id)) {
            $this->returnCode('72000004');
        }
        $m_community_folder = M('Community_folder');
        $folder_single = $m_community_folder->where(array('folder_id' => $folder_id))->find();
        if (!$folder_single || intval($folder_single['folder_status']) >= 2) {
            $this->returnCode('72000005');
        }
        $community_info = M('Community_info')->where(array('community_id' => $folder_single['community_id'], 'group_owner_uid' => $uid))->field('community_name')->find();
        // 如果既不是创建者，也不是群主，无删除此文件夹权限
        if ($uid != $folder_single['folder_uid'] && !$community_info) {
            $this->returnCode('72000007');
        }
        $data = array('folder_status' => 3);
        $folder_del = $m_community_folder->where(array('folder_id' => $folder_id))->data($data)->save();
        if ($folder_del) {
            $this->returnCode(0,array('folder_id'=>$folder_id));
        } else {
            $this->returnCode(1,array(),L_('删除失败！请重试'));
        }
    }

    /**
     * 获取群文件夹列表
     */
    public function folder_list(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        // 获取群部分信息和群关于群文件夹权限信息
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        $is_add_folder = true;
        if (!empty($community_development['is_add_folder']) && $community_development['is_add_folder'] == 2 && $uid != $community_development['group_owner_uid']) {
            $is_add_folder = false;
        }
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $time_order = '';
        // 时间顺序查询
        if (!empty($_POST['time_order']) && in_array(strtolower($_POST['time_order']), array('desc', 'asc'))) {
            $time_order = strtolower($_POST['time_order']);
        }
        $order = '`cf`.`add_time` DESC';
        if ($time_order) {
            $order = '`cf`.`add_time` ' . $time_order;
        }
        // 群文件名称 模糊查询
        if (!empty($_POST['folder_name'])) {
            $where = '`cf`.`folder_status` = 1  AND `cf`.`community_id` = ' . $community_id .' AND `cf`.`folder_name` like "%' . $_POST['folder_name'] . '%"';
        } else {
            $where = '`cf`.`folder_status` = 1 AND `cf`.`community_id` = ' . $community_id;
        }
        $d_community_folder = D('Community_folder')->folder_info_list($where, 10, $page, $order);
        // 群主和群文件创建者可以删除， 创建者还可以编辑
        if (!empty($d_community_folder['list'])) {
            foreach ($d_community_folder['list'] as &$val) {
                $val['time_info'] = time_info($val['add_time']);
                if ($val['folder_uid'] == $uid) {
                    $val['is_del'] = true;
                    $val['is_edit'] = true;
                } elseif($val['group_owner_uid'] == $uid) {
                    $val['is_del'] = true;
                    $val['is_edit'] = false;
                } else {
                    $val['is_del'] = false;
                    $val['is_edit'] = false;
                }
            }
        }
        $d_community_folder['is_add_folder'] = $is_add_folder;
        $this->returnCode(0,$d_community_folder);
    }

    /**
     * 上传文件 现在 规定上传的群文件必须归属于群和对应的群文件下
     */
    public function add_file(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $folder_id = intval($_POST['folder_id']);
        if (empty($folder_id)) {
            $this->returnCode('72000004');
        }
        $uid = $this->_uid;
        $m_community_folder = M('Community_folder');
        $folder_single = $m_community_folder->where(array('folder_id' => $folder_id))->find();
        if (!$folder_single || intval($folder_single['folder_status']) >= 2) {
            $this->returnCode('72000005');
        }
        if ($uid != $folder_single['folder_uid'] && $folder_single['other_is_upload'] == 1) {
            $this->returnCode('72000008');
        }
        $d_community_file = D('Community_file');
        $msg = $d_community_file->community_file_handle($uid, 'comm_file', array('size' => 50), $community_id, $folder_id);
        if ($msg['error'] == 0) {
            if ($msg['file_id_arr']) {
                $file_num = count($msg['file_id_arr']);
                $m_community_folder->where(array('folder_id' => $folder_id))->setInc('file_num', $file_num);
            }
            $this->returnCode(0,array('file_id_arr'=>$msg['file_id_arr']));
        } else {
            $this->returnCode(1,array(),$msg['message']);
        }
    }


    /**
     * 单上传文件 现在 规定上传的群文件必须归属于群和对应的群文件下
     */
    public function single_add_file(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $folder_id = intval($_POST['folder_id']);
        if (empty($folder_id)) {
            $this->returnCode('72000004');
        }
        $uid = $this->_uid;
        $m_community_folder = M('Community_folder');
        $folder_single = $m_community_folder->where(array('folder_id' => $folder_id))->find();
        if (!$folder_single || intval($folder_single['folder_status']) >= 2) {
            $this->returnCode('72000005');
        }
        if ($uid != $folder_single['folder_uid'] && $folder_single['other_is_upload'] == 1) {
            $this->returnCode('72000008');
        }
        $d_community_file = D('Community_file');
        $msg = $d_community_file->community_file_handle($uid, 'comm_file', array('size' => 50), $community_id, $folder_id);
        if ($msg['error'] == 0) {
            if ($msg['file_id_arr']) {
                $file_num = count($msg['file_id_arr']);
                $m_community_folder->where(array('folder_id' => $folder_id))->setInc('file_num', $file_num);
            }
            // 发送一条消息到群聊中
            D('Community_file')->file_info_send($folder_single['folder_name'], $msg['file_id'], $community_id, $uid);
            $this->returnCode(0,array('file_id'=>$msg['file_id']));
        } else {
            $this->returnCode(1,array(),$msg['message']);
        }
    }


    /**
     * 获取对应群中群文件列表
     */
    public function file_all_list(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $uid = $this->_uid;
        $d_community_file = D('Community_file');
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $time_order = '';
        // 时间顺序查询
        if (!empty($_POST['time_order']) && in_array(strtolower($_POST['time_order']), array('desc', 'asc'))) {
            $time_order = strtolower($_POST['time_order']);
        }
        $order = '`cfl`.`add_time` DESC';
        if ($time_order) {
            $order = '`cfl`.`add_time` ' . $time_order;
        }
        // 群文件名称 模糊查询
        if (!empty($_POST['file_name'])) {
            $where = '`cfl`.`file_status` = 1 AND `cfl`.`file_remark` like "%' . $_POST['file_name'] . '%" AND `ci`.`community_id`='. $community_id;
        } else {
            $where = '`cfl`.`file_status` = 1 AND `ci`.`community_id`='. $community_id;
        }
        $community_file= $d_community_file->file_info_list($where, 10, $page, $order);
        // 群主可以删除文件， 上传者可以删除文件， 文件夹创建者可以删除文件
        if (!empty($community_file['list'])) {
            foreach ($community_file['list'] as &$val) {
                $val['time_info'] = time_info($val['add_time']);
                if ($val['oss_url']) {
                    $val['file_url'] = $val['oss_url'];
                } else if ($val['file_url']) {
                    $val['file_url'] = replace_file_domain($val['file_url']);
                }
                if ($val['file_extra']) {
                    $file_extra = unserialize($val['file_extra']);
                    foreach ($file_extra as $k => $v) {
                        $val[$k] = $v;
                    }
                    unset($val['file_extra']);
                }
                if ($val['folder_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($val['group_owner_uid'] && $val['group_owner_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($val['file_uid'] == $uid) {
                    $val['is_del'] = true;
                } else {
                    $val['is_del'] = false;
                }
                $val['file_img'] = $d_community_file->type_and_img($val['file_type'], $val['file_suffix']);
            }
        }
        $this->returnCode(0,$community_file);
    }

    /**
     * 获取对应群文件夹中群文件列表
     */
    public function file_list(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $folder_id = intval($_POST['folder_id']);
        if (empty($folder_id)) {
            $this->returnCode('72000004');
        }
        $uid = $this->_uid;
        $d_community_folder = D('Community_folder');
        $d_community_file = D('Community_file');
        $folder_single = $d_community_folder->folder_community_info($folder_id);
        if (!$folder_single || intval($folder_single['folder_status']) >= 2) {
            $this->returnCode('72000005');
        }
        $other_is_upload = true;
        if ($uid != $folder_single['folder_uid'] && $folder_single['other_is_upload'] == 1) {
            $other_is_upload = false;
        }
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $time_order = '';
        // 时间顺序查询
        if (!empty($_POST['time_order']) && in_array(strtolower($_POST['time_order']), array('desc', 'asc'))) {
            $time_order = strtolower($_POST['time_order']);
        }
        $order = '`cfl`.`add_time` DESC';
        if ($time_order) {
            $order = '`cfl`.`add_time` ' . $time_order;
        }
        // 群文件名称 模糊查询
        if (!empty($_POST['file_name'])) {
            $where = '`cfl`.`file_status` = 1 AND `cfl`.`file_remark` like "%' . $_POST['file_name'] . '%" AND `cfl`.`folder_id` = ' . $folder_id;
        } else {
            $where = '`cfl`.`file_status` = 1 AND `cfl`.`folder_id` = ' . $folder_id;
        }
        $community_file= $d_community_file->file_info_list($where, 10, $page, $order);
        // 群主可以删除文件， 上传者可以删除文件， 文件夹创建者可以删除文件
        if (!empty($community_file['list'])) {
            foreach ($community_file['list'] as &$val) {
                $val['time_info'] = time_info($val['add_time']);
                if ($val['oss_url']) {
                    $val['file_url'] = $val['oss_url'];
                } else if ($val['file_url']) {
                    $val['file_url'] = replace_file_domain($val['file_url']);
                }
                if ($val['file_extra']) {
                    $file_extra = unserialize($val['file_extra']);
                    foreach ($file_extra as $k => $v) {
                        $val[$k] = $v;
                    }
                    unset($val['file_extra']);
                }
                if ($val['folder_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($folder_single['community_info'] && $folder_single['community_info']['group_owner_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($val['file_uid'] == $uid) {
                    $val['is_del'] = true;
                } else {
                    $val['is_del'] = false;
                }
                $val['file_img'] = $d_community_file->type_and_img($val['file_type'], $val['file_suffix']);
            }
        }
        $community_file['other_is_upload'] = $other_is_upload;
        $this->returnCode(0,$community_file);
    }

    /**
     * 删除文件 群主可以删除文件， 上传者可以删除文件， 文件夹创建者可以删除文件 后台也可以禁用
     */
    public function del_file(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $file_id = intval($_POST['file_id']);
        if (empty($file_id)) {
            $this->returnCode('72000019');
        }
        $m_community_file = M('Community_file');
        $file_single = $m_community_file->where(array('file_id' => $file_id))->find();
        if (!$file_single || intval($file_single['file_status']) >= 2) {
            $this->returnCode('72000009');
        }
        $d_community_folder = D('Community_folder');
        $folder_single = $d_community_folder->folder_community_info($file_single['folder_id']);
        // 如果既不是创建者，也不是群主，无删除此文件夹权限
        if ($uid != $folder_single['folder_uid'] && $folder_single['community_info'] && $folder_single['community_info']['group_owner_uid'] != $uid && $file_single['file_uid'] != $uid) {
            $this->returnCode('72000010');
        }
        $data = array('file_status' => 3);
        $file_del = $m_community_file->where(array('file_id' => $file_id))->data($data)->save();
        if ($file_del) {
            $this->returnCode(0,array('file_id'=>$file_id));
        } else {
            $this->returnCode(1,array(),L_('删除失败！请重试'));
        }
    }

    /**
     * 获取文件详情
     */
    public function file_detail(){
        $file_id = intval($_POST['file_id']);
        if (empty($file_id)) {
            $this->returnCode('72000058');
        }
        $d_community_file = D('Community_file');
        $file_single = $d_community_file->file_detail($file_id);
        if (!$file_single || intval($file_single['file_status']) >= 2) {
            $this->returnCode('72000009');
        }
        $this->returnCode(0,$file_single);
    }



    // 群相册区块

    /**
     * 创建群相册
     */
    public function add_album(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $album_name = $_POST['album_name'];
        if (empty($album_name)) {
            $this->returnCode('72000011');
        }
        if ($album_name) {
            $check = D('Community_file')->msgSecCheck($album_name);
            if ($check['errcode'] != 0) {
                $this->returnCode(1,array(),L_('群相册名称中含有违法违规内容'));
            }
        }
        if (M('Community_album')->where(array('album_name' =>$album_name, 'album_status' => array('neq', 3), 'community_id' => $community_id))->find()) {
            $this->returnCode('72000025');
        }
        $uid = $this->_uid;
        // 获取群部分信息和群关于群相册权限信息
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        if (!empty($community_development['is_add_album']) && $community_development['is_add_album'] == 2 && $uid != $community_development['group_owner_uid']) {
            $this->returnCode('72000056');
        }
        $data = array();
        $data['community_id'] = $community_id;
        $data['album_uid'] = $uid;
        $data['album_name'] = $album_name;
        if (!empty($_POST['album_des'])) {
            $data['album_des'] = $_POST['album_des'];
            if ($data['album_des']) {
                $check = D('Community_file')->msgSecCheck($data['album_des']);
                if ($check['errcode'] != 0) {
                    $this->returnCode(1,array(),L_('群相册描述中含有违法违规内容'));
                }
            }
        }
        if (!empty($_POST['other_is_upload']) && intval($_POST['other_is_upload']) == 1) {
            $data['other_is_upload'] = 1;
        } else {
            $data['other_is_upload'] = 2;
        }
        $data['album_status'] = 1;
        $data['add_time'] = time();
        $album_id = M('Community_album')->data($data)->add();
        if ($album_id) {
            $this->returnCode(0,array('album_id'=>$album_id));
        } else {
            $this->returnCode(1,array(),L_('添加失败！请重试'));
        }
    }

    /**
     * 编辑群相册
     */
    public function edit_album(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $m_community_album = M('Community_album');
        $album_single = $m_community_album->where(array('album_id' => $album_id))->find();
        if (!$album_single || intval($album_single['album_status']) >= 2) {
            $this->returnCode('72000013');
        }
        if ($uid != $album_single['album_uid']) {
            $this->returnCode('72000014');
        }
        $data = array();
        if (!empty($_POST['album_name']) && $_POST['album_name'] != $album_single['album_name']) {
            $album_name = $_POST['album_name'];
            if (M('Community_album')->where(array('album_name' =>$album_name, 'album_status' => array('neq', 3), 'community_id' => $album_single['community_id']))->find()) {
                $this->returnCode('72000025');
            }
            $data['album_name'] = $album_name;
            if ($album_name) {
                $check = D('Community_file')->msgSecCheck($album_name);
                if ($check['errcode'] != 0) {
                    $this->returnCode(1,array(),L_('群相册名称中含有违法违规内容'));
                }
            }
        }
        if (!empty($_POST['album_des']) && $_POST['album_des'] != $album_single['album_des']) {
            $data['album_des'] = $_POST['album_des'];
            if ($data['album_des']) {
                $check = D('Community_file')->msgSecCheck($data['album_des']);
                if ($check['errcode'] != 0) {
                    $this->returnCode(1,array(),L_('群相册描述中含有违法违规内容'));
                }
            }
        }
        if (!empty($_POST['other_is_upload']) && intval($_POST['other_is_upload']) != intval($album_single['other_is_upload'])) {
            if (in_array(intval($_POST['other_is_upload']), array(1, 2))) {
                $data['other_is_upload'] = $_POST['other_is_upload'];
            }
        }
        if (!empty($data)) {
            $album_id = $m_community_album->where(array('album_id' => $album_id))->data($data)->save();
            if ($album_id) {
                $this->returnCode(0,array('album_id'=>$album_id));
            } else {
                $this->returnCode(1,array(),L_('编辑失败！请重试'));
            }
        } else {
            $this->returnCode(0,array('album_id'=>$album_id));
        }
    }

    /**
     * 删除相册 群主和创建者均可删除，后台也可以进行禁用操作
     */
    public function del_album(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $m_community_album = M('Community_album');
        $album_single = $m_community_album->where(array('album_id' => $album_id))->find();
        if (!$album_single || intval($album_single['album_status']) >= 2) {
            $this->returnCode('72000013');
        }
        $community_info = M('Community_info')->where(array('community_id' => $album_single['community_id'], 'group_owner_uid' => $uid))->field('group_owner_uid, community_name')->find();
        // 如果既不是创建者，也不是群主，无删除此相册权限
        if ($uid != $album_single['album_uid'] && !$community_info) {
            $this->returnCode('72000015');
        }
        $data = array('album_status' => 3);
        $folder_del = $m_community_album->where(array('album_id' => $album_id))->data($data)->save();
        if ($folder_del) {
            $this->returnCode(0,array('album_status'=>$album_id));
        } else {
            $this->returnCode(1,array(),L_('删除失败！请重试'));
        }
    }

    /**
     * 获取群相册列表 群主和群相册创建者可以删除， 创建者还可以编辑
     */
    public function album_list(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $time_order = '';
        // 时间顺序查询
        if (!empty($_POST['time_order']) && in_array(strtolower($_POST['time_order']), array('desc', 'asc'))) {
            $time_order = strtolower($_POST['time_order']);
        }
        $order = '`ca`.`add_time` DESC';
        if ($time_order) {
            $order = '`ca`.`add_time` ' . $time_order;
        }
        // 群相册名称 模糊查询
        if (!empty($_POST['album_name'])) {
            $where = '`ca`.`album_status` = 1  AND `ca`.`community_id` = ' . $community_id .' AND `ca`.`album_name` like "%' . $_POST['album_name'] . '%"';
        } else {
            $where = '`ca`.`album_status` = 1 AND `ca`.`community_id` = ' . $community_id;
        }
        $community_album = D('Community_album')->album_info_list($where, 10, $page, $order);
        // 群主和群文件创建者可以删除， 创建者还可以编辑
        $where_num = array(
            'file_sign' => array('neq', ''),
            'file_status' => 1,
        );
        $d_community_file = D('Community_file');
        if (!empty($community_album['list'])) {
            foreach ($community_album['list'] as &$val) {
//                $where_num['album_id'] = $val['album_id'];
//                $val['pic_num'] = $d_community_file->change_num($where_num);
                $val['album_img'] = $d_community_file->album_img($val['album_id']);
            }
        }
        // 获取群部分信息和群关于群相册权限信息
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        $community_album['is_add_album'] = true;
        if (!empty($community_development['is_add_album']) && $community_development['is_add_album'] == 2 && $uid != $community_development['group_owner_uid']) {
            $community_album['is_add_album'] = false;
        }
        $this->returnCode(0,$community_album);
    }

    /**
     * 单个上传图片文件，返回图片文件id
     */
    function add_single_img() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $uid = $this->_uid;
        $m_community_album = M('Community_album');
        $album_single = $m_community_album->where(array('album_id' => $album_id))->find();
        if (!$album_single || intval($album_single['album_status']) >= 2) {
            $this->returnCode('72000013');
        }
        if ($uid != $album_single['album_uid'] && $album_single['other_is_upload'] == 1) {
            $this->returnCode('72000057');
        }
        $d_community_file = D('Community_file');
        $msg = $d_community_file->community_file_handle($uid, 'comm_file', array('size' => 5), $community_id, 0, $album_id, 2, '');
        if ($msg['error'] == 0) {
            $this->returnCode(0,$msg);
        } else {
            $this->returnCode(1,array(),$msg['message']);
        }
    }

    /**
     * 上传图片 现在 规定上传的相册图片必须归属于群和对应的群相册下
     */
    public function add_album_img(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $file_id_arr = $_POST['file_id_arr'];
        if (empty($file_id_arr)) {
            $this->returnCode('72000020');
        }
        $d_community_file = M('Community_file');
        // 查询一下图片id信息
        $where = array('file_id' => array('in', $file_id_arr), 'community_id' => $community_id, 'album_id' => $album_id);
        $file = $d_community_file->where($where)->select();

        $file_url_arr = array();
        if (count($file_id_arr) != count($file)) {
            $this->returnCode('72000027');
        }
        foreach ($file as $val) {
            if (!$val || intval($val['album_id']) == 0 || intval($val['file_status']) >= 2) {
                $this->returnCode('72000026');
            }
            if ($val['oss_url']) {
                $file_url_arr[] = $val['oss_url'];
            } else if ($val['file_url']) {
                $file_url_arr[] = $val['file_url'];
            }
        }
        $is_add_dynamic = 2;
        if ($_POST['is_add_dynamic'] && intval($_POST['is_add_dynamic']) == 1) {
            $is_add_dynamic = 1;
        }
        $file_des = '';
        if ($_POST['file_des']) {
            $file_des = $_POST['file_des'];
        }
        $uid = $this->_uid;
        $m_community_album = M('Community_album');
        $album_single = $m_community_album->where(array('album_id' => $album_id))->find();
        if (!$album_single || intval($album_single['album_status']) >= 2) {
            $this->returnCode('72000013');
        }
        if ($uid != $album_single['album_uid'] && $album_single['other_is_upload'] == 1) {
            $this->returnCode('72000057');
        }
        $file_sign = time();
        $data = array(
            'is_add_dynamic' => $is_add_dynamic,
            'file_des' =>$file_des,
            'file_sign' => $file_sign,
            'file_status' => 1,
            'add_time' => time(),
            'set_time' => time(),
        );
        $msg = $d_community_file->where($where)->data($data)->save();
        if ($msg) {
            if (count($file_id_arr) > 0) {
                $file_num = count($file_id_arr);
                $m_community_album->where(array('album_id' => $album_id))->setInc('pic_num', $file_num);
            }
            // 判断是否同时发布到群动态
            // 为 1 不同步到动态  2同步到动态
            $dynamic_id = 0;
            if ($is_add_dynamic == 2) {
                $data['community_id'] = $community_id;//群ID
                $data['user_id'] = $uid;//用户ID
                $data['content'] = $file_des;//图片的介绍->动态内容
                $data['addtime'] = time();
                if ($file_url_arr) {
                    $data['img'] = serialize($file_url_arr);
                }
                $dynamic_id = M('Community_dynamic')->add($data);
                if(!$dynamic_id) {
                    $this->returnCode('72000066');
                }
            }
            $file_uid = reset($file)['file_uid'];
            $bind_data = array(
                'dynamic_id' => $dynamic_id,
                'file_sign' => $file_sign,
                'file_uid' => $file_uid,
            );
            M('Community_user_file_bind')->data($bind_data)->add();
            $des = L_('在【X1】上传了X2张照片',array('X1'=>$album_single['album_name'],'X2'=>count($file_url_arr)));
            D('Community_file')->create_plan($dynamic_id, $des);
            $this->returnCode(0,array('file_id_arr'=>$file_id_arr ,'dynamic_id'=>$dynamic_id));
        } else {
            $this->returnCode('72000067');
        }
    }

    /**
     * 获取对应群相册中用户分批次上传的群图片列表
     */
    public function album_img_list(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $uid = $this->_uid;
        $d_community_album = D('Community_album');
        $album_single = $d_community_album->album_community_info($album_id);
        if (!$album_single || intval($album_single['album_status']) >= 2) {
            $this->returnCode('72000013');
        }
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $where = '`cfl`.`file_sign` IS NOT NULL AND `cfl`.`file_status` = 1 AND `cfl`.`album_id` = ' . $album_id;

        $d_community_file = D('Community_file');
        $community_file = D('Community_file')->file_info_user($where, 5, $page);
        if (!empty($community_file['list'])) {
            foreach ($community_file['list'] as &$val) {
                $val['img_info'] = $d_community_file->file_by_user($val['file_sign'], $album_id, $val['file_uid']);
                $val['img_num'] = count($val['img_info']);
            }
        }
        // 群主可以删除文件， 上传者可以删除文件， 文件夹创建者可以删除文件
        if (!empty($community_file['list'])) {
            foreach ($community_file['list'] as &$val) {
                if ($val['album_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($album_single['community_info'] && $album_single['community_info']['group_owner_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($val['file_uid'] == $uid) {
                    $val['is_del'] = true;
                } else {
                    $val['is_del'] = false;
                }
            }
        }
        // 处理群相册是否可以删除和编辑
        $album_info = $album_single;

        if ($album_info['album_uid'] == $uid) {
            $album_info['is_del'] = true;
            $album_info['is_edit'] = true;
        } elseif($album_info['community_info']['group_owner_uid'] == $uid) {
            $album_info['is_del'] = true;
            $album_info['is_edit'] = false;
        } else {
            $album_info['is_del'] = false;
            $album_info['is_edit'] = false;
        }
        // 重新计算一下数目
        $where_num = array(
            'file_sign' => array('neq', ''),
            'file_status' => 1,
            'album_id' => $album_id
        );
        $album_info['pic_num'] = $d_community_file->change_num($where_num);
        unset($album_info['community_info']);
        $community_file['album_info'] = $album_info;

        // 获取关于上传群图片信息权限
        $other_is_upload = true;
        if ($uid != $album_single['album_uid'] && $album_single['other_is_upload'] == 1) {
            $other_is_upload = false;
        }
        $community_file['other_is_upload'] = $other_is_upload;


        $this->returnCode(0,$community_file);
    }


    /**
     * 获取对应群相册中所有群图片列表
     */
    public function album_aLL_img_list(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $uid = $this->_uid;
        $d_community_album = D('Community_album');
        $album_single = $d_community_album->album_community_info($album_id);
        if (!$album_single || intval($album_single['album_status']) >= 2) {
            $this->returnCode('72000013');
        }
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;

        $where = '`cfl`.`file_status` = 1 AND `cfl`.`album_id` = ' . $album_id;

        $d_community_file = D('Community_file');
        $community_file = $d_community_file->img_info_list($where, 15, $page);
        // 群主可以删除文件， 上传者可以删除文件， 文件夹创建者可以删除文件
        if (!empty($community_file['list'])) {
            foreach ($community_file['list'] as &$val) {
                $val['time_info'] = time_info($val['add_time']);
                if ($val['oss_url']) {
                    $val['file_url'] = $val['oss_url'];
                } else if ($val['file_url']) {
                    $val['file_url'] = replace_file_domain($val['file_url']);
                }
                if ($val['file_extra']) {
                    $file_extra = unserialize($val['file_extra']);
                    foreach ($file_extra as $k => $v) {
                        $val[$k] = $v;
                    }
                    unset($val['file_extra']);
                }
                if ($val['album_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($album_single['community_info'] && $album_single['community_info']['group_owner_uid'] == $uid) {
                    $val['is_del'] = true;
                } elseif($val['file_uid'] == $uid) {
                    $val['is_del'] = true;
                } else {
                    $val['is_del'] = false;
                }
                unset($val['folder_id']);
                unset($val['ip']);
            }
        }
        // 重新计算一下数目
        $where_num = array(
            'file_sign' => array('neq', ''),
            'file_status' => 1,
            'album_id' => $album_id
        );
        $album_info['pic_num'] = $d_community_file->change_num($where_num);
        $this->returnCode(0,$community_file);
    }

    /**
     * 删除图片 群主可以删除图片， 上传者可以删除图片， 文件夹创建者可以删除图片 后台也可以禁用
     */
    public function del_album_img(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $file_id = intval($_POST['file_id']);
        if (empty($file_id)) {
            $this->returnCode('72000020');
        }
        $m_community_file = M('Community_file');
        $file_single = $m_community_file->where(array('file_id' => $file_id))->find();
        if (!$file_single || intval($file_single['album_id']) == 0 || intval($file_single['file_status']) >= 2) {
            $this->returnCode('72000021');
        }
        $d_community_folder = D('Community_folder');
        $folder_single = $d_community_folder->folder_community_info($file_single['folder_id']);
        // 如果既不是创建者，也不是群主，无删除此文件夹权限
        if ($uid != $folder_single['folder_uid'] && $folder_single['community_info'] && $folder_single['community_info']['group_owner_uid'] != $uid && $file_single['file_uid'] != $uid) {
            $this->returnCode('72000022');
        }
        $data = array('file_status' => 3);
        $file_del = $m_community_file->where(array('file_id' => $file_id))->data($data)->save();
        if ($file_del) {
            $this->returnCode(0,array('file_id'=>$file_id));
        } else {
            $this->returnCode(1,array(),L_('删除失败！请重试'));
        }
    }

    /**
     * 获取批量删除的图片
     */
    public function batch_del_list() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $file_sign = intval($_POST['file_sign']);
        if (empty($file_sign)) {
            $this->returnCode('72000028');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $d_community_file = D('Community_file');
        $albumM_info = D('Community_album')->album_community_info($album_id);
        if ($albumM_info && $albumM_info['community_info'] && $albumM_info['community_info']['group_owner_uid'] == $uid) {
            $user = 0;
        } else {
            $user = $uid;
        }
        $msg = $d_community_file->file_by_user($file_sign, $album_id, $user);
        // 重新计算一下数目
        $where_num = array(
            'file_sign' => array('neq', ''),
            'file_status' => 1,
            'album_id' => $album_id
        );
        $album_info['pic_num'] = $d_community_file->change_num($where_num);
        $this->returnCode(0, $msg);
    }

    /**
     * 批量删除图片 群主可以删除图片， 上传者可以删除图片， 文件夹创建者可以删除图片 后台也可以禁用
     * $file_id_str 字段为  array(1,2,3,4)
     */
    public function batch_del_album_img(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $file_id_arr = $_POST['file_id_arr'];
        if (empty($file_id_arr)) {
            $this->returnCode('72000004');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $m_community_file = M('Community_file');
        $file_list = $m_community_file->where(array('file_id' => array('in', $file_id_arr)))->select();
        $d_community_album = D('Community_album');
        foreach ($file_list as $item) {
            if (!$item || intval($item['album_id']) == 0 || intval($item['file_status']) >= 2) {
                $this->returnCode('72000021');
            }
            if (intval($item['album_id']) != $album_id) {
                $this->returnCode('72000029');
            }
            $album_single = $d_community_album->album_community_info($item['album_id']);
            // 如果既不是创建者，也不是群主，无删除此图片文件权限
            if ($uid != $album_single['folder_uid'] && !$album_single['group_owner_uid'] != $uid && $item['file_uid'] != $uid) {
                $this->returnCode('72000022');
            }
        }
        $data = array('file_status' => 3);
        $file_del = $m_community_file->where(array('file_id' => array('in', $file_id_arr)))->data($data)->save();
        if ($file_del) {
            $m_community_album = M('Community_album');
            $m_community_album->where(array('album_id' => $album_id))->setDec('pic_num', count($file_id_arr));
            $this->returnCode(0,$file_del);
        } else {
            $this->returnCode(1,array(),L_('删除失败！请重试'));
        }
    }

    /**
     * 获取当前用户可以转移的相册 图片转移到别的相册 群主可以转移图片， 允许其他人上传的相册可以转移图片， 相册创建者可以转移图片
     * $file_id_str 字段为  array(1,2,3,4)
     */
    public function change_album(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $page = !empty($_POST['page']) ? intval($_POST['page']) : 1;
        $time_order = '';
        // 时间顺序查询
        if (!empty($_POST['time_order']) && in_array(strtolower($_POST['time_order']), array('desc', 'asc'))) {
            $time_order = strtolower($_POST['time_order']);
        }
        $order = '`ca`.`add_time` DESC';
        if ($time_order) {
            $order = '`ca`.`add_time` ' . $time_order;
        }
        $where = '(`ci`.`group_owner_uid` = '. $uid . ' OR `ca`.`album_uid` = ' . $uid . ' OR `ca`.`other_is_upload` = 2) AND `ca`.`album_id`<>' . $album_id;
        // 群相册名称 模糊查询
        if (!empty($_POST['album_name'])) {
            $where .= ' AND `ca`.`album_status` = 1  AND `ca`.`community_id` = ' . $community_id .' AND `ca`.`album_name` like "%' . $_POST['album_name'] . '%"';
        } else {
            $where .= ' AND `ca`.`album_status` = 1 AND `ca`.`community_id` = ' . $community_id;
        }
        $community_album = D('Community_album')->album_info_list($where, 10, $page, $order);
        $this->returnCode(0,$community_album);
    }

    /**
     * 图片转移到别的相册 群主可以转移图片， 上传者可以转移图片， 文件夹创建者可以转移图片
     * $file_id_str 字段为  array(1,2,3,4)
     */
    public function img_change_album(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $file_id_arr = $_POST['file_id_arr'];
        if (empty($file_id_arr)) {
            $this->returnCode('72000004');
        }
        $album_id = intval($_POST['album_id']);
        if (empty($album_id)) {
            $this->returnCode('72000012');
        }
        $m_community_file = M('Community_file');
        $file_list = $m_community_file->where(array('file_id' => array('in', $file_id_arr)))->select();
        $d_community_album = D('Community_album');
        foreach ($file_list as $item) {
            if (!$item || intval($item['album_id']) == 0 || intval($item['file_status']) >= 2) {
                $this->returnCode('72000021');
            }
            $album_single = $d_community_album->album_community_info($item['album_id']);
            // 如果既不是创建者，也不是群主，无转移此图片权限
            if ($uid != $album_single['folder_uid'] && !$album_single['group_owner_uid'] != $uid && $item['file_uid'] != $uid) {
                $this->returnCode('72000023');
            }
            $d_community_album->where(array('album_id' => $item['album_id']))->setDec('pic_num', 1);
        }
        $data = array('album_id' => $album_id);
        $file_change = $m_community_file->where(array('file_id' => array('in', $file_id_arr)))->data($data)->save();
        $d_community_album->where(array('album_id' => $album_id))->setInc('pic_num', count($file_id_arr));
        if ($file_change) {
            $this->returnCode(0,$file_change);
        } else {
            $this->returnCode(1,array(),L_('转移失败！请重试'));
        }
    }



    // 群名片区块
    /**
     * 单图片文件上传
     * */
    public function single_img_post() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        // 名片背景图
        $image = array();
        if(!empty($_FILES) && $_FILES['file']['error'] != 4){
            $image = D('Image')->handle($uid, 'comm', 1, array('size' => 10), false, true);
            if ($image['error']) {
                $this->returnCode(1,array(), $image['message']);
            }
        }
        if ($image) {
            $ret = array(
                'img_id' => $image['pigcms_id'],
                'url' => replace_file_domain($image['url']['image']),
            );
        } else {
            $ret = array();
        }
        $this->returnCode(0, $ret);
    }


    /**
     * 添加个人名片信息
     */
    public function add_personal_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $m_community_personal_card = M('Community_personal_card');
        $where = array('uid' => $uid);
        $personal_single = $m_community_personal_card->where($where)->find();
        if ($personal_single) {
            $this->returnCode('72000039');
        }
        // 信息过滤
        $data = array();
        if ($_POST['wx_ID']) {
            $data['wx_ID'] = $_POST['wx_ID'];
        }
        if ($_POST['job']) {
            $data['job'] = $_POST['job'];
        }
        if ($_POST['company']) {
            $data['company'] = $_POST['company'];
        }
        if ($_POST['address']) {
            $data['address'] = $_POST['address'];
        }
        if ($_POST['email']) {
            $data['email'] = $_POST['email'];
        }
        if ($_POST['lng']) {
            $data['lng'] = $_POST['lng'];
        }
        if ($_POST['lat']) {
            $data['lat'] = $_POST['lat'];
        }

        // 名片背景图
        if(!empty($_POST['bgimg_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['bgimg_id']))->find();
            // 名片背景图
            if ($image) {
                $data['bgimg'] = $image['pic'];
            }
        }
        // 名片个人头像，没传取默认的用户头像
        $user = M('User')->field('avatar, nickname')->where(array('uid'=>$uid))->find();
        if(!empty($_POST['avatar_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['avatar_id']))->find();
            // 名片背景图
            if ($image) {
                $data['avatar'] = $image['pic'];
            }
        } else {
            $data['avatar'] = $user['avatar'];
        }
        // 名片个人姓名，没传取默认的用户昵称
        $user = M('User')->field('avatar, nickname')->where(array('uid'=>$uid))->find();
        if(!empty($_POST['nickname'])){
            $data['nickname'] = $_POST['nickname'];
        } else {
            $data['nickname'] = $user['nickname'];
        }

        if (!empty($data)) {
            $data['add_time'] = time();
            $data['uid'] = $uid;
            $personal_id = M('Community_personal_card')->data($data)->add();
            if ($personal_id) {
                $this->returnCode(0,array('personal_id'=>$personal_id));
            } else {
                $this->returnCode(1,array(),L_('添加失败！请重试'));
            }
        } else {
            $this->returnCode('72000030');
        }
    }

    /**
     * 编辑个人名片信息
     */
    public function edit_personal_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $m_community_personal_card = M('Community_personal_card');
        $where = array('uid' => $uid);
        $personal_single = $m_community_personal_card->where($where)->find();
        if (!$personal_single) {
            $this->returnCode('72000032');
        }
        if ($uid != $personal_single['uid']) {
            $this->returnCode('72000014');
        }
        // 信息过滤
        $data = array();
        if ($_POST['wx_ID'] && $_POST['wx_ID'] != $personal_single['wx_ID']) {
            $data['wx_ID'] = $_POST['wx_ID'];
        }elseif (empty($_POST['wx_ID'])) {
            $data['wx_ID'] = '';
        }
        if ($_POST['job'] && $_POST['job'] != $personal_single['job']) {
            $data['job'] = $_POST['job'];
        }elseif (empty($_POST['job'])) {
            $data['job'] = '';
        }
        if ($_POST['company'] && $_POST['company'] != $personal_single['company']) {
            $data['company'] = $_POST['company'];
        } elseif (empty($_POST['company'])) {
            $data['company'] = '';
        }
        if ($_POST['address'] && $_POST['address'] != $personal_single['address']) {
            $data['address'] = $_POST['address'];
        } elseif (empty($_POST['address'])) {
            $data['address'] = '';
        }
        if ($_POST['email'] && $_POST['email'] != $personal_single['email']) {
            $data['email'] = $_POST['email'];
        } elseif (empty($_POST['email'])) {
            $data['email'] = '';
        }
        if ($_POST['lng'] && $_POST['lng'] != $personal_single['lng']) {
            $data['lng'] = $_POST['lng'];
        }
        if ($_POST['lat'] && $_POST['lat'] != $personal_single['lat']) {
            $data['lat'] = $_POST['lat'];
        }

        // 名片背景图
        if(!empty($_POST['bgimg_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['bgimg_id']))->find();
            // 名片背景图
            if ($image) {
                $data['bgimg'] = $image['pic'];
            }
        }
        // 名片个人头像，没传取默认的用户头像
        $user = M('User')->field('avatar, nickname')->where(array('uid'=>$uid))->find();
        if(!empty($_POST['avatar_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['avatar_id']))->find();
            // 名片背景图
            if ($image) {
                $data['avatar'] = $image['pic'];
            }
        } elseif(!$personal_single['avatar']) {
            $data['avatar'] = $user['avatar'];
        }
        // 名片个人姓名，没传取默认的用户昵称
        $user = M('User')->field('avatar, nickname')->where(array('uid'=>$uid))->find();
        if(!empty($_POST['nickname'])){
            $data['nickname'] = $_POST['nickname'];
        } elseif(!$personal_single['nickname']) {
            $data['nickname'] = $user['nickname'];
        }

        if (!empty($data)) {
            $data['add_time'] = time();
            $data['uid'] = $uid;
            $personal_id = $m_community_personal_card->where($where)->data($data)->save();
            if ($personal_id) {
                $this->returnCode(0,array('personal_id'=>$personal_id));
            } else {
                $this->returnCode(1,array(),L_('编辑失败！请重试'));
            }
        } else {
            $this->returnCode(0,array('personal_id'=>$personal_single['personal_id']));
        }
    }

    /**
     * 获取个人名片信息
     * */
     public function personal_detail(){
         if (!$this->_uid) {
             $this->returnCode('20044013');
         }
         $uid = $this->_uid;
         $m_community_personal_card = M('Community_personal_card');
         $where = array('uid' => $uid);
         $personal_single = $m_community_personal_card->where($where)->find();
         if (!$personal_single) {
             $this->returnCode(0, array());
         }
         if ($personal_single['bgimg']) $personal_single['bgimg'] = replace_file_domain($personal_single['bgimg']);
         if ($personal_single['avatar']) $personal_single['avatar'] = replace_file_domain($personal_single['avatar']);
         $this->returnCode(0, $personal_single);
     }


    /**
     * 添加群名片的时候获取到的数据
     * */
    public function info_add_card() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $community_info = M('Community_info')->field('group_owner_uid, community_name')->where(array('community_id' => $community_id))->find();
        if ($community_info['group_owner_uid'] != $uid) {
            $this->returnCode('72000042');
        }
        $word = $this->card_word(true);
        $this->returnCode(0,$word);
    }

    /**
     * 添加群名片
     * */
    public function add_community_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $m_community_card = M('Community_card');
        $where = array(
            'community_id' => $community_id
        );
        $card_single = $m_community_card->where($where)->find();
        if ($card_single) {
            $this->returnCode('72000040');
        }
        // 群名片名称处理
        $card_name = $_POST['card_name'];
        if (empty($card_name)) {
            $this->returnCode('72000033');
        }
        // 群名片填写项处理
        $option = json_decode(htmlspecialchars_decode($_POST['option']), true);
        if (empty($option)) {
            $this->returnCode('72000034');
        }
        // 名片背景图
        $image = array();
        if(!empty($_FILES) && $_FILES['file']['error'] != 4){
            $image = D('Image')->handle($uid, 'comm', 1, array('size' => 10), false, true);
            if ($image['error']) {
                $this->returnCode(1,array(), $image['message']);
            }
        }
        // 名片背景图
        if ($image) {
            $data['community_card_bgimg'] = $image['url']['image'];
        }
        // 简介
        if ($_POST['community_card_des']) {
            $data['community_card_des'] = $_POST['community_card_des'];
        }
        $data['add_time'] = time();
        $data['community_id'] = $community_id;
        $data['card_name'] = $card_name;
        D()->startTrans();
        $community_card_id = $m_community_card->data($data)->add();
        $msg['id'] = $community_card_id;
        $msg['type'] = 'community_card';
        if(!$data['community_card_bgimg']){
            $com_img = '/static/community/chat/comm_card.png';
        }else{
            $com_img = $data['community_card_bgimg'];
        }
        $msg['img'] = replace_file_domain($com_img);
        $msg['content'] = $data['community_card_des'];
        $dynamic_data=array(
            'community_id'  =>  $data['community_id'],
            'user_id'       =>  $this->_uid,
            'application_detail'  =>  serialize($msg),
            'addtime'       =>  time()
        );
        $dynamic = D('Community_dynamic')->data($dynamic_data)->add();
        if(!$dynamic){
            $this->returnCode(1,array(),L_('同步到动态失败!请重试~'));
        }
        if ($community_card_id) {
            //添加选项
            $option_data = [];
            if ($option) {
                $option_name = array();
                $default_word = array();
                foreach ($option as $key => $val) {
                    if ($option_name && in_array($val['option_name'], $option_name)) {
                        D()->rollback();
                        $this->returnCode('72000037');
                    }
                    if ($default_word && in_array($val['default_word'], $default_word)) {
                        D()->rollback();
                        $this->returnCode('72000045');
                    }
                    $option_name[] = $val['option_name'];
                    if ($val['default_word']) $default_word[] = $val['default_word'];
                    $option_data[$key]['community_card_id'] = $community_card_id;
                    $option_data[$key]['community_id'] = $community_id;
                    $option_data[$key]['option_uid'] = $uid;
                    $option_data[$key]['option_name'] = $val['option_name'];
                    $option_data[$key]['is_required'] = in_array(intval($val['is_required']), array(1,2)) ? intval($val['is_required']) : 1; // 是否必填1-不必填，2-必填
                    $option_data[$key]['is_look'] = in_array(intval($val['is_look']), array(1,2)) ? intval($val['is_look']) : 1; // 群成员是否可查看（隐私问题） 1 可以查看  2 不可查看
                    $option_data[$key]['option_type'] = in_array(intval($val['option_type']), array(1,2)) ? intval($val['option_type']) : 1; // 默认单行文本（类型1-单行文本，2-多行文本）
                    $option_data[$key]['option_content'] = $val['option_content'] ? json_encode($val['option_content']) : '';
                    $option_data[$key]['default_word'] = $val['default_word'] ? $val['default_word'] : '';
                    $option_data[$key]['add_time'] = time();
                }
                $option_data = array_values($option_data);
                $addOption = M('Community_card_option_bind')->addAll($option_data);
                if (!$addOption) {
                    D()->rollback();
                    $this->returnCode(1,array(),L_(L_('添加失败！请重试')));
                }
            } else {
                D()->rollback();
                $this->returnCode(1,array(),L_('添加失败！请重试'));
            }
            D()->commit();
            // 发送消息
            D('Community_card')->card_info_send($community_card_id, $uid);
            $this->returnCode(0,array('community_card_id'=>$community_card_id));
        } else {
            $this->returnCode(1,array(),'添加失败！请重试');
        }

    }


    /**
     * 获取群名片编辑信息
     * */
    public function community_card_info(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $m_community_card = M('Community_card');
        $where = array(
            'community_id' => $community_id
        );
        $community_info = M('Community_info')->field('group_owner_uid, community_name')->where(array('community_id' => $community_id))->find();
        if ($community_info['group_owner_uid'] != $uid) {
            $this->returnCode(0,array('msg' => L_('抱歉，您不是群主，不能编辑群名片！'), 'community_name' => $community_info['community_name'], 'community_id' => $community_id));
        }
        $card_single = $m_community_card->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        // 获取选项信息
        $m_community_card_option_bind = M('Community_card_option_bind');
        $single_where = array('community_id'=>$community_id,'community_card_id'=>$card_single['community_card_id'],'option_status'=>1);
        $option_detail = $m_community_card_option_bind->where($single_where)->order('option_id asc')->select();
//        foreach($option_detail as $key=>$value){
//            if($value['is_required'] == 1){
//                $option_detail[$key]['is_required'] = false;
//            }else{
//                $option_detail[$key]['is_required'] = true;
//            }
//            if($value['is_look'] == 1){
//                $option_detail[$key]['is_look'] = true;
//            }else{
//                $option_detail[$key]['is_look'] = false;
//            }
//        }

        $already_word = array();
        if ($option_detail) {
            foreach ($option_detail as $val) {
                if ($val['default_word']) {
                    $already_word[] = $val['default_word'];
                }
            }
        }
        $word_select = $this->card_word(true, $already_word);
        if (!$word_select) $word_select = array();

        if ($card_single['community_card_bgimg']) {
            $card_single['community_card_bgimg'] = replace_file_domain($card_single['community_card_bgimg']);
        }
        $info =array(
            'community_card' => $card_single,
            'option_detail' => $option_detail,
            'word_select' => $word_select
        );
        $this->returnCode(0, $info);
    }

    /**
     * 编辑群名片
     * */
    public function edit_community_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $edit_data = array();
        $m_community_card = M('Community_card');
        $where = array(
            'community_id' => $community_id
        );
        $card_single = $m_community_card->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        // 群名片名称处理
        if ($_POST['card_name'] && $_POST['card_name'] != $card_single['card_name']) {
            $edit_data['card_name'] = $_POST['card_name'];
        }
        // 名片背景图
        $image = array();
        if(!empty($_FILES) && $_FILES['file']['error'] != 4){
            $image = D('Image')->handle($uid, 'comm', 1, array('size' => 10), false, true);
            if ($image['error'])  {
                $this->returnCode(1,array(), $image['message']);
            }
        }
        // 名片背景图
        if ($image['url']['image'] && $image['url']['image'] != $card_single['community_card_bgimg']) {
            $edit_data['community_card_bgimg'] = $image['url']['image'];
        }
        // 简介
        if ($_POST['community_card_des'] && $_POST['community_card_des'] != $card_single['community_card_des']) {
            $edit_data['community_card_des'] = $_POST['community_card_des'];
        }
        // 群名片填写项处理
        $option = json_decode(htmlspecialchars_decode($_POST['option']), true);

        D()->startTrans();
        if (!empty($edit_data)) {
            $community_card_id = $m_community_card->where($where)->data($edit_data)->save();
        }
        $community_card_id = $card_single['community_card_id'];
        if ($community_card_id) {
            //修改选项
            $m_community_card_option_bind = M('Community_card_option_bind');
            $single_where = array('community_id'=>$community_id,'community_card_id'=>$community_card_id,'option_status'=>1);
            $option_detail = $m_community_card_option_bind->where($single_where)->select();

            // 已有自定义名称
            $old_option_name = array();
            // 新的自定义项名称
            $new_option_name = array();
            // 新编辑的选项内容处理
            $option_new = array();
            if ($option) {
                $default_word = array();
                foreach ($option as $key => $val) {
                    if ($new_option_name && in_array($val['option_name'], $new_option_name)) {
                        D()->rollback();
                        $this->returnCode('72000037');
                    }
                    if ($default_word && in_array($val['default_word'], $default_word)) {
                        D()->rollback();
                        $this->returnCode('72000045');
                    }
                    if ($val['default_word']) $default_word[] = $val['default_word'];
                    $new_option_name[] = $val['option_name'];
                    $option_new[$val['option_name']] = array(
                        'option_name' => $val['option_name'], //选项名称
                        'is_look'     => in_array(intval($val['is_look']), array(1,2)) ? intval($val['is_look']) : 1, // 群成员是否可查看（隐私问题） 1 可以查看  2 不可查看
                        'option_type' => in_array(intval($val['option_type']), array(1,2)) ? intval($val['option_type']) : 1, //类型1-单行文本，2-多行文本
                        'is_required' => in_array(intval($val['is_required']), array(1,2)) ? intval($val['is_required']) : 1, //是否必填0-不必填，1-必填
                        'option_content' => $val['option_content'] ? json_encode($val['option_content']) : '',
                        'default_word' => $val['default_word'] ? $val['default_word'] : '',
                    );
                }
            }
            if ($option_detail) {
                //删除 编辑选项
                $where_detail = array('community_card_id'=>$community_card_id);
                foreach ($option_detail as $key => $val) {
                    $old_option_name[] = $val['option_name'];
                    $where_detail['option_id'] = $val['option_id'];
                    if (in_array($val['option_name'], $new_option_name)) { // 编辑选项
                        $arr = $option_new[$val['option_name']];
                        $arr['add_time'] = time();
                        $opt = $m_community_card_option_bind->where($where_detail)->data($arr)->save();
                    } else { // 删除选项
                        $opt = $m_community_card_option_bind->where($where_detail)->data(array('option_status'=>2))->save();
                    }
                    if (!$opt) {
                        D()->rollback();
                        $this->returnCode(1,array(),L_('编辑失败！请重试'));
                    }
                }
            }
            // 新增选项
            if ($option) {
                foreach ($option as $key => $val) {
                    if (!in_array($val['option_name'], $old_option_name)) {
                        $option_data = array(
                            'option_name' => $val['option_name'], //选项名称
                            'is_look'     => in_array(intval($val['is_look']), array(1,2)) ? intval($val['is_look']) : 1, // 群成员是否可查看（隐私问题） 1 可以查看  2 不可查看
                            'option_type' => in_array(intval($val['option_type']), array(1,2)) ? intval($val['option_type']) : 1, //类型1-单行文本，2-多行文本
                            'is_required' => in_array(intval($val['is_required']), array(1,2)) ? intval($val['is_required']) : 1, //是否必填0-不必填，1-必填
                            'community_id' => $community_id,
                            'community_card_id' => $community_card_id,
                            'option_content' => $val['option_content'] ? json_encode($val['option_content']) : '',
                            'default_word' => $val['default_word'] ? json_encode($val['default_word']) : '',
                            'option_uid' => $uid,
                            'add_time' => time(),
                        );
                        $add = $m_community_card_option_bind->data($option_data)->add();
                        if (!$add) {
                            D()->rollback();
                            $this->returnCode(1,array(),L_(L_('编辑失败！请重试')));
                        }
                    }
                }
            }
            D()->commit();
            $this->returnCode(0,array('community_card_id'=>$community_card_id));
        } else {
            $this->returnCode(1,array(),'编辑失败！请重试');
        }
    }

    /**
     * 群名片信息
     * */
    public function single_community_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $m_community_card = M('Community_card');
        $where = array(
            'community_id' => $community_id
        );
        $community_info = M('Community_info')->field('group_owner_uid, community_name')->where(array('community_id' => $community_id))->find();
        $card_single = $m_community_card->where($where)->find();
        $is_owner = false;
        if ($community_info['group_owner_uid'] == $uid) {
            $is_owner = true;
            $card_single['owner_avatar'] = M('User')->where(array('uid'=>$community_info['group_owner_uid']))->getField('avatar');
        }
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        if ($card_single['community_card_bgimg']) {
            $card_single['community_card_bgimg'] = replace_file_domain($card_single['community_card_bgimg']);
        }

        $info =array(
            'community_card' => $card_single,
            'is_owner' => $is_owner
        );
        $this->returnCode(0, $info);
    }

    /**
     * 用户加入群名片
     * */
    public function join_community_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        // 验证是否是群成员
        $join_info = M('Community_join')->where(array('community_id'=>$community_id,'add_uid'=>$uid, 'add_status' => 3))->find();
        if (!$join_info ) {
            $this->returnCode('70000009', array('community_id' => $community_id));
        }
        $m_community_card = M('Community_card');
        $m_community_card_bind = M('Community_card_bind');
        $m_community_card_option_bind = M('Community_card_option_bind');
        $where = array(
            'community_id' => $community_id
        );
        $card_single = $m_community_card->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        $where['join_uid'] = $uid;
        $where['community_id'] = $community_id;
        $where['join_status'] = 1;
        $info = $m_community_card_bind->where($where)->find();
        if ($info) {
            $this->returnCode('72000038');
        }
        // 名片背景图
        if(!empty($_POST['bgimg_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['bgimg_id']))->find();
            // 名片背景图
            if ($image) {
                $data['join_bgimg'] = $image['pic'];
            }
        } elseif($_POST['join_bgimg']) {
            $data['join_bgimg'] = $_POST['join_bgimg'];
        }
        // 名片个人头像，没传取默认的用户头像
        if(!empty($_POST['avatar_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['avatar_id']))->find();
            // 名片背景图
            if ($image) {
                $data['join_avatar'] = $image['pic'];
            }
        } elseif($_POST['join_bgimg']) {
            $data['join_bgimg'] = $_POST['join_bgimg'];
        }
        if ($_POST['lng']) {
            $data['lng'] = $_POST['lng'];
        }
        if ($_POST['lat']) {
            $data['lat'] = $_POST['lat'];
        }
        $single_where = array('community_id'=>$community_id,'community_card_id'=>$card_single['community_card_id'],'option_status'=>1);
        $card_option = $m_community_card_option_bind->where($single_where)->select();
        $nickname = '';
        $phone = '';
        if ($card_option) {
            $option = $_POST['option'];
            foreach ($card_option as &$val) {
                if ($val['is_required'] == 2 && !$option[$val['option_name']]) {
                    $this->returnCode(1, array(),L_('X1为必填项',array('X1'=>$val['option_name'])));
                }
                // 如果是姓名、昵称 特殊处理
                if ($val['default_word'] == 'nickname') {
                    $nickname = $option[$val['option_name']];
//                    unset($option[$val['option_name']]);
                } else if ($val['default_word'] == 'phone') {
                    // 如果是手机号 特殊处理
                    $phone = $option[$val['option_name']];
//                    unset($option[$val['option_name']]);
                }
            }
            if ($nickname) {
                $data['nickname'] = $nickname;
            }
            if ($phone) {
                $data['phone'] = $phone;
            }
            $data['content'] = json_encode($option);
        }
        if (!empty($data)) {
            $data['join_time'] = time();
            $data['join_uid'] = $uid;
            $data['community_id'] = $community_id;
            $data['community_card_id'] = $card_single['community_card_id'];
            $join_id = $m_community_card_bind->data($data)->add();
            if ($join_id) {
                $community_card_id = $m_community_card->where($where)->setInc('join_num');
                if (!$community_card_id) {
                    $m_community_card_bind->change_num($where);
                }
                $this->returnCode(0,array('join_id'=>$join_id));
            } else {
                $this->returnCode(1,array(),L_('添加失败！请重试'));
            }
        } else {
            $this->returnCode('72000030');
        }

    }

    /**
     * 获取群名片及群名片下的群成员名片信息
     * */
    public function community_card_join_info() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $community_info = M('Community_info')->field('group_owner_uid')->where(array('community_id' => $community_id))->find();
        if (!$community_info) {
            $this->returnCode('70000005');
        }
        // 验证是否是群成员
        $join_msg = M('Community_join')->where(array('community_id'=>$community_id,'add_uid'=>$uid))->find();
        if (!$join_msg || $join_msg['add_status'] != 3) {
            if (!$join_msg) $join_msg = array();
            $this->returnCode(0, array('community_id' => $community_id, 'join_info' => $join_msg, 'error' => true, 'errmsg' => L_('用户未加入该群！')));
        }
        $m_community_card = M('Community_card');
        $d_community_card_bind = D('Community_card_bind');
        $where = array(
            'community_id' => $community_id
        );
        $is_owner = false;
        if ($uid == $community_info['group_owner_uid']) {
            $is_owner = true;
        }
        $card_single = $m_community_card->where($where)->find();
        if (!$card_single) {
            $return = array(
                'community_card' => array(),
                'join_info' => array(),
                'is_owner' => $is_owner,
                'is_join' => false
            );
            $this->returnCode(0, $return);
        }
        $page = $_POST['page'] ? $_POST['page'] : 1;
        $where = array(
            'join_status' => 1,
            'community_id' => $community_id,
            'community_card_id' => $card_single['community_card_id']
        );
        if ($_POST['nickname']) {
            $where['nickname'] = '%' . $_POST['nickname'] . '%';
        }
        $join_info = $d_community_card_bind->join_card_list($where, 8, $page);
        if (!$join_info) $join_info = array();
        // 查询一下当前用户是否加入
        $where_join = array(
            'join_status' => 1,
            'community_id' => $community_id,
            'community_card_id' => $card_single['community_card_id'],
            'join_uid' => $uid
        );
        $join = $d_community_card_bind->where($where_join)->field('join_uid')->find();
        $is_join = false;
        if ($join) {
            $is_join = true;
        }
        $where_num = array(
            'join_status' => 1,
            'community_id' => $community_id,
            'community_card_id' => $card_single['community_card_id']
        );
        $m_community_card->where($where_num)->setInc('browse_num');
        if (!$is_owner) {
            unset($card_single['excel_url']);
        }
        // 获取群主的头像和昵称
        $user = M('User')->field('nickname, avatar')->where(array('uid' => $community_info['group_owner_uid']))->find();
        if ($card_single['community_card_bgimg']) {
            $card_single['community_card_bgimg'] = replace_file_domain($card_single['community_card_bgimg']);
        }
        $return = array(
            'owner' => $user,
            'community_card' => $card_single,
            'join_info' => $join_info,
            'is_owner' => $is_owner,
            'is_join' => $is_join
        );
        $this->returnCode(0, $return);
    }

    /**
     * 获取群名片成员详细信息
     * */
    public function community_card_member_detail() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $join_id = intval($_POST['join_id']);
        if (empty($join_id)) {
            $this->returnCode('72000041');
        }
        $where = array(
            'community_id' => $community_id
        );
        $m_community_card = M('Community_card');
        $card_single = $m_community_card->field('card_name')->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        $community_info = M('Community_info')->field('group_owner_uid, community_name')->where(array('community_id' => $community_id))->find();
        if (!$community_info) {
            $this->returnCode('70000005');
        }
        $d_community_card_bind = D('Community_card_bind');
        $card_where['community_id'] = $community_id;
        $card_where['community_card_id'] = $card_single['community_card_id'];
        $community_card_bind = $d_community_card_bind->where($card_where)->setInc('browse_number');//单个群名片浏览量
        $where_join = array(
            'join_id' => $join_id
        );
        $join = $d_community_card_bind->where($where_join)->field(true)->find();
        $is_owner = false;
        if ($uid == $community_info['group_owner_uid']) {
            $is_owner = true;
        }
        // 获取群名片的选项
        $where['option_status'] = 1;
        $option = M('Community_card_option_bind')->field('option_name, is_look')->where($where)->find();
        $option_info = array();
        foreach ($option as $val) {
            $option_info[$val['option_name']] = $option_info[$val['is_look']];
        }
        if ($join) {
            // 如果不是群主且群成员不可看的隐私内容，过滤掉
            if ($join['content']) {
                $join['content']  = json_decode($join['content'], true);
                if (!$is_owner && $join['join_uid'] != $uid) {
                    foreach ($join['content'] as $k => $v) {
                        if (!$option_info[$k] || $option_info[$k] == 2) {
                            unset($join['content'][$k]);
                        }
                    }
                }
            }
        }
        // 单独判断手机号和昵称
        $card_word = $this->card_word(true);
        if (!$join['nickname']){
            $join['nickname'] = M('User')->where(array('uid'=>$join['join_uid']))->getField('nickname');
        }
        if ($join && $option_info[$card_word['nickname']['word']] == 1 && !$join['content'][$card_word['nickname']['word']]) {
            if (!$join['content']) $join['content'] = array();
            $join['content'][$card_word['nickname']['word']] = $join['nickname'];
        }
        if ($join && $option_info[$card_word['phone']['word']] == 1 && !$join['content'][$card_word['phone']['word']]) {
            if (!$join['content']) $join['content'] = array();
            $join['content'][$card_word['phone']['word']] = $join['phone'];
        }
        if ($join['join_uid'] != $uid) {
            $join['is_user'] = false;
        } else {
            $join['is_user'] = true;
        }
        $join['card_name'] = $card_single['card_name'];
        $join['community_name'] = $community_info['community_name'];
        $join['card_praise'] = D('Community_card_praise')->where(array('join_id'=>$join_id))->count();
        $can_praise = D('Community_card_praise')->where(array('join_id'=>$join_id,'card_praise_user_id'=>$this->_uid))->find();
        if($can_praise){
            $join['can_praise'] = false;
        }else{
            $join['can_praise'] = true;
        }
        if ($join['join_avatar']) $join['join_avatar'] = replace_file_domain($join['join_avatar']);
        if ($join['join_bgimg']) $join['join_bgimg'] = replace_file_domain($join['join_bgimg']);
        if($join['join_uid'] && !$join['join_avatar']){
            $join['join_avatar'] = M('User')->where(array('uid'=>$join['join_uid']))->getField('avatar');
        }
        $this->returnCode(0, $join);
    }

    /**
     * 获取群名片成员详细编辑信息
     * */
    public function community_card_edit_info() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $join_id = intval($_POST['join_id']);
        if (empty($join_id)) {
            $this->returnCode('72000041');
        }
        $m_community_card_bind = M('Community_card_bind');
        $where_join = array(
            'join_id' => $join_id
        );
        $join = $m_community_card_bind->where($where_join)->field(true)->find();
        if (!$join) {
            $this->returnCode('72000050');
        }
        if ($join['join_uid'] != $uid) {
            $this->returnCode('72000051');
        }
        $where = array(
            'community_id' => $join['community_id']
        );
        $m_community_card = M('Community_card');
        $card_single = $m_community_card->field('card_name')->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        $community_info = M('Community_info')->field('group_owner_uid, community_name')->where(array('community_id' => $join['community_id']))->find();
        if (!$community_info) {
            $this->returnCode('70000005');
        }
        $content = $join['content']  = json_decode($join['content'], true);
        // 获取选项
        $m_community_card_option_bind = M('Community_card_option_bind');
//        $m_community_personal_card = M('Community_personal_card');
//        $where_card = array('uid' => $uid);
//        $personal_single = $m_community_personal_card->where($where_card)->find();
        $single_where = array('community_id'=>$join['community_id'],'community_card_id'=>$join['community_card_id'],'option_status'=>1);
        $card_option = $m_community_card_option_bind->where($single_where)->select();
        $content_info = array();
        if ($card_option) {
            $card_word = $this->card_word(true);
            foreach ($card_option as $key => &$val) {
                $content_info[$val['option_name']] = array();
                $content_info[$val['option_name']]['is_required'] = $val['is_required'];
                $content_info[$val['option_name']]['option_type'] = $val['option_type'];
                $content_info[$val['option_name']]['is_look'] = $val['is_look'];
                if ($content[$val['option_name']]) {
                    $content_info[$val['option_name']]['value'] = $content[$val['option_name']];
                } elseif ($val['option_name'] == $card_word['nickname']['word']) {
                    $content_info[$val['option_name']]['value'] = $join['nickname'];
                } elseif ($val['option_name'] == $card_word['phone']['word']) {
                    $content_info[$val['option_name']]['value'] = $join['phone'];
                } else {
                    $content_info[$val['option_name']]['value'] = '';
                }
            }
        }
        $join['card_option'] = $card_option;
        $join['content_info'] = $content_info;
        if ($join['join_uid'] != $uid) {
            $join['is_user'] = false;
        } else {
            $join['is_user'] = true;
        }
        $join['card_name'] = $card_single['card_name'];
        $join['community_name'] = $community_info['community_name'];
        if ($join['join_avatar']) $join['join_avatar'] = replace_file_domain($join['join_avatar']);
        if ($join['join_bgimg']) $join['join_bgimg'] = replace_file_domain($join['join_bgimg']);
        $this->returnCode(0, $join);
    }

    /**
     * 用户编辑群名片个人名片信息
     * */
    public function edit_join_community_card(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $join_id = intval($_POST['join_id']);
        if (empty($join_id)) {
            $this->returnCode('72000041');
        }
        $m_community_card = M('Community_card');
        $m_community_card_bind = M('Community_card_bind');
        $m_community_card_option_bind = M('Community_card_option_bind');
        $where_join = array(
            'join_id' => $join_id
        );
        $join = $m_community_card_bind->where($where_join)->field(true)->find();
        if (!$join) {
            $this->returnCode('72000050');
        }
        if ($join['join_uid'] != $uid) {
            $this->returnCode('72000051');
        }
        $where = array(
            'community_id' => $join['community_id'],
            'community_card_id' => $join['community_card_id']
        );
        $card_single = $m_community_card->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        // 名片背景图
        if(!empty($_POST['join_bgimg_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['join_bgimg_id']))->find();
            // 名片背景图
            if ($image) {
                $data['join_bgimg'] = $image['pic'];
            }
        }
        // 名片个人头像，没传取默认的用户头像
        if(!empty($_POST['join_avatar_id'])){
            $image = M('Image')->field('pic')->where(array('pigcms_id' => $_POST['join_avatar_id']))->find();
            // 名片背景图
            if ($image) {
                $data['join_avatar'] = $image['pic'];
            }
        }
        if ($_POST['lng'] && $_POST['lng'] != $join['lng']) {
            $data['lng'] = $_POST['lng'];
        }
        if ($_POST['lat'] && $_POST['lat'] != $join['lat']) {
            $data['lat'] = $_POST['lat'];
        }
        if (!empty($_POST['option']) && json_decode($join['content'], true) != $_POST['option']) {
            $single_where = array('community_id'=>$join['community_id'],'community_card_id'=>$join['community_card_id'],'option_status'=>1);
            $card_option = $m_community_card_option_bind->where($single_where)->select();
            $nickname = '';
            $phone = '';
            if ($card_option) {
                $option = $_POST['option'];
                foreach ($card_option as &$val) {
                    if ($val['is_required'] == 2 && !$option[$val['option_name']]) {
                        $this->returnCode(1, array(),L_('X1为必填项',array('X1'=>$val['option_name'])));
                    }
                    // 如果是姓名、昵称 特殊处理
                    if ($val['default_word'] == 'nickname') {
                        $nickname = $option[$val['option_name']];
//                    unset($option[$val['option_name']]);
                    } else if ($val['default_word'] == 'phone') {
                        // 如果是手机号 特殊处理
                        $phone = $option[$val['option_name']];
//                    unset($option[$val['option_name']]);
                    }
                }
                $data['content'] = json_encode($option);
            }
            if ($nickname) {
                $data['nickname'] = $nickname;
            }
            if ($phone) {
                $data['phone'] = $phone;
            }
        }
        if (!empty($data)) {
            $data['join_time'] = time();
            $join_id = $m_community_card_bind->where($where_join)->data($data)->save();
            if ($join_id) {
                $this->returnCode(0,array('join_id'=>$join_id));
            } else {
                $this->returnCode(1,array(),L_('编辑失败！请重试'));
            }
        } else {
            $this->returnCode('72000030');
        }

    }

    /**
     * 获取用户需要填写的字段信息
     */
    public function card_join_content() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $m_community_card = M('Community_card');
        $m_community_card_option_bind = M('Community_card_option_bind');
        $where = array(
            'community_id' => $community_id
        );
        $card_single = $m_community_card->where($where)->find();
        if (!$card_single) {
            $this->returnCode('72000036');
        }
        $m_community_personal_card = M('Community_personal_card');
        $where_card = array('uid' => $uid);
        $personal_single = $m_community_personal_card->where($where_card)->find();
        $single_where = array('community_id'=>$community_id,'community_card_id'=>$card_single['community_card_id'],'option_status'=>1);
        $card_option = $m_community_card_option_bind->where($single_where)->select();
        if ($card_option) {
            foreach ($card_option as &$val) {
                if ($val['default_word'] && $personal_single[$val['default_word']]) {
                    $val['word_value'] = $personal_single[$val['default_word']];
                }
            }
        }
        $card_option['avatar'] = M('User')->where(array('uid'=>$uid))->getField('avatar');
        $this->returnCode(0, $card_option);
    }

    /**
     * 名片导出
     * */
    public function card_export(){
        // 判断用户是否登录
        if (empty($this->_uid)) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (empty($_POST['community_id'])) {
            $this->returnCode('70000004');
        }
        $community_id = $_POST['community_id'];
        $m_community_card = M('Community_card');
        $card_info = $m_community_card->where(array('community_id' => $community_id))->find();
        if (empty($card_info)) {
            $this->returnCode('72000036');
        }
        $community_info = M('Community_info')->field('group_owner_uid, community_name')->where(array('community_id' => $community_id))->find();
        if ($community_info['group_owner_uid'] != $uid) {
            $this->returnCode('72000043');
        }
        // 查询一下选项信息
        $option_where = array('community_card_id' => $card_info['community_card_id'], 'community_id' => $community_id, 'option_status' => 1);
        $community_card_option = M('Community_card_option_bind')->field('option_name')->where($option_where)->select();
        $where = array(
            'community_card_id' => $card_info['community_card_id'],
            'community_id' => $community_id,
            'join_status' => 1
        );
        $card_join = M('Community_card_bind')->where($where)->order('join_time ASC')->select();
        $join_number = count($card_join);
        if(count($card_join) <= 0){
            $this->returnCode('72000044');
        }

        $title_msg = L_('【X1】群名片名称： X2；共计X3成员加入。',array('X1'=>$community_info['community_name'],'X2'=>$card_info['card_name'],'X3'=>$join_number));
        $col_num = count($community_card_option) + 5;

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

        $title = L_('X1名片-成员列表',array('X1'=>$card_info['card_name']));

        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);

        $length = ceil(count($card_join)/1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle($title);
            $objActSheet = $objExcel->getActiveSheet();
            // 添加第一行表头
            $objActSheet->mergeCellsByColumnAndRow('0', '1', '' . $col_num, '1');
            $objActSheet->setCellValueByColumnAndRow('0', '1', $title_msg);
            $objActSheet->setCellValueByColumnAndRow('0', '2', L_('序号'));
            $objActSheet->setCellValueByColumnAndRow('1', '2', L_('姓名'));
            $objActSheet->setCellValueByColumnAndRow('2', '2',L_('手机号'));
            $column = 2;
            if (!empty($community_card_option)) {
                foreach ($community_card_option as $key => $val) {
                    $column = $key + 3;
                    $col_str = '' . $column;
                    $objActSheet->setCellValueByColumnAndRow($col_str, '2', $val['option_name']);
                }
            }
            $col_str = '' . ($column + 1);
            $objActSheet->setCellValueByColumnAndRow($col_str, '2', L_('加入时间'));

            if (!empty($card_join)) {
                $index = 3;

                $cell_list = range(0,$col_num);
                foreach ($cell_list as $cell) {
                    $objActSheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($cell))->setWidth(20);
                    $objActSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex($cell))->getAlignment()->setWrapText(true);
                }

                foreach ($card_join as $value) {
                    $objActSheet->setCellValueExplicitByColumnAndRow('0', $index, trim($value['join_id']));
                    $objActSheet->setCellValueExplicitByColumnAndRow('1', $index, trim($value['nickname']));
                    $objActSheet->setCellValueExplicitByColumnAndRow('2', $index, trim($value['phone']));
                    $columns = 2;
                    if (!empty($community_card_option)) {
                        $content = !empty($value['content']) ? json_decode($value['content'], true) : '';
                        foreach ($community_card_option as $key => $val) {
                            $columns = $key + 3;
                            if (!empty($content) && $content[$val['option_name']]) {
                                $content_info = $content[$val['option_name']];
                                if (is_array($content_info)) {
                                    $content_info = implode("/", $content_info);
                                }
                                $content_info = $this->filterEmoji($content_info);
                                $objActSheet->setCellValueExplicitByColumnAndRow('' . $columns, $index, trim($content_info));
                            } else {
                                $objActSheet->setCellValueExplicitByColumnAndRow('' . $columns, $index, '');
                            }
                        }
                    }
                    $objActSheet->setCellValueExplicitByColumnAndRow('' . ($columns + 1), $index, date("Y-m-d H:i:s", $value['join_time']));
                    $index++;
                }
            }
        }
        $file_id = sprintf("%09d", $community_id);
        $rand_num = substr($file_id, 0, 3) . '/' . substr($file_id, 3, 3) . '/' . substr($file_id, 6, 3);
        $title_info = $community_id . '.xlsx';
        $upload_dir = "upload/comm/community_card/{$rand_num}/";
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        $_savePath = $upload_dir . $title_info;
        ob_end_clean();
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save($_savePath);

        $excel_url = '/' . $_savePath;
        $m_community_card->where($where)->data(array('excel_url' => $excel_url))->save();
        $this->returnCode(0, array('excel_url'=> C('config.site_url') . $excel_url));
    }


    /**
     *  群名片字段
     * @param  bool $get_word
     * @param  array $already_word  已经有了的字段
     * @return array
     */
    private function card_word($get_word = true, $already_word = array()) {
        $word = array(
            'job' => array('val' => 'job', 'word' => L_('职务'), 'tip' => L_('填写职务职称')),
            'company' => array('val' => 'company', 'word' => L_('公司'), 'tip' => L_('填写所在公司名称')),
            'address' => array('val' => 'address', 'word' => L_('地址'), 'tip' => L_('填写地址')),
            'email' => array('val' => 'email', 'word' => L_('邮箱'), 'tip' => L_('填写联系邮箱')),
            'nickname' => array('val' => 'nickname', 'word' => L_('姓名'), 'tip' => L_('填写姓名')),
            'phone' => array('val' => 'phone', 'word' => L_('手机号'), 'tip' => L_('填写手机号'))
        );
        $word_key = array(
            'job' => L_('职务'),
            'company' => L_('公司'),
            'address' => L_('地址'),
            'email' => L_('邮箱'),
            'nickname' => L_('姓名'),
            'phone' => L_('手机号')
        );
        if ($get_word) {
            if (!empty($already_word)) {
                foreach ($word as $key=>$val) {
                    if (in_array($key, $already_word)) {
                        unset($word[$key]);
                    }
                }
            }
            $ret = $word;
        } else {
            $ret = $word_key;
        }
        return $ret;
    }

    // 过滤表情
    private function filterEmoji($str) {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }


    // 群应用拓展管理

    /**
     * 群应用拓展管理控制信息获取
     */
    public function control_application_development() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        if ($this->_uid != $community_development['group_owner_uid']) {
            $this->returnCode(0, array('error' => 1, 'errmsg' => L_('抱歉，您不是群主不能查看此页信息')));
        }
        $community_development = $d_community_info->application_control($community_id);
        $this->returnCode(0, $community_development);
    }


    /**
     * 群应用拓展排序管理控制信息获取
     */
    public function sort_application_development() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        if ($this->_uid != $community_development['group_owner_uid']) {
            $this->returnCode(0, array('error' => 1, 'errmsg' => L_('抱歉，您不是群主不能查看此页信息')));
        }
        $community_development = $d_community_info->application_info($community_id, 'all');
        $this->returnCode(0, $community_development);
    }


    /**
     * 群应用拓展管理
     */
    public function switch_application_development() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        unset($_POST['community_id']);

        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        if ($this->_uid != $community_development['group_owner_uid']) {
            $this->returnCode('72000043');
        }

        // 处理上传数据
        $switch_data = $this->filter_word($_POST, $community_development);
        $where = array('community_id'=>$community_id);
        $switch = false; // 切换成果
        $change = false;  // 是否有所改变
        if ($switch_data) {
            $change = true;
            $switch_data['set_time'] = time();
            $switch = $d_community_info->where($where)->data($switch_data)->save();
        }

        if (!$change) {
            $this->returnCode(0,array('community_id'=>$community_id));
        } else if ($switch) {
            $this->returnCode(0,array('community_id'=>$community_id));
        } else {
            $this->returnCode(1,array(),L_('修改失败！请重试'));
        }
    }

    /**
     * 群应用拓展管理信息获取
     */
    public function single_application_development() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        $d_community_info = D('Community_application_development');
        $community_development = $d_community_info->community_application_development($community_id);
        if (empty($community_development) || $community_development['status'] != 1) {
            $this->returnCode('72000016');
        }
        if ($this->_uid != $community_development['group_owner_uid']) {
            $this->returnCode(0, array('error' => 1, 'errmsg' => L_('抱歉，您不是群主不能查看此页信息')));
        }
        $this->returnCode(0, $community_development);
    }

    /**
     * 获取聊天页面的群应用
     */
    public function application_chat_info(){
        if(!$_POST['community_id']){
            $this->returnCode('71000029');
        }
        $info = D('Community_application_development')->application_info($_POST['community_id'], 'index');
        $this->returnCode(0, $info);
    }
    /**
     * 获取群主页页面的群应用
     */
    public function application_more_info(){
        if(!$_POST['community_id']){
            $this->returnCode('71000029');
        }
        $info = D('Community_application_development')->application_info($_POST['community_id'], 'more');
        $this->returnCode(0, $info);
    }
    /**
     * 获取群所有群应用
     */
    public function application_all_info(){
        if(!$_POST['community_id']){
            $this->returnCode('71000029');
        }
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        $community_id = intval($_POST['community_id']);
        $community_info = M('Community_info')->field('group_owner_uid')->where(array('community_id' => $community_id))->find();
        if (!$community_info) {
            $this->returnCode('70000005');
        }
        $info = D('Community_application_development')->application_info($community_id, 'all');
        $site_url = C('config.site_url') ;
        if ($uid == $community_info['group_owner_uid'] && $info) {
            $info[] = array
            (
                'title' => L_('应用开关'),
                'url' => '/pages/group/goupDevelopment/goupDevelopment?community_id='.$community_id,
                'icon' => $site_url . '/static/community/application/app_admin_switch.png'
            );
            $info[] = array
            (
                'title' => L_('应用排序'),
                'url' => '/pages/group/groupDevelopmentSort/groupDevelopmentSort?community_id='.$community_id,
                'icon' => $site_url . '/static/community/application/app_admin_sort.png'
            );
        } else if($uid == $community_info['group_owner_uid']) {
            $info = array();
            $info[] = array
            (
                'title' => L_('应用开关'),
                'url' => '/pages/group/goupDevelopment/goupDevelopment?community_id='.$community_id,
                'icon' => $site_url . '/static/community/application/app_admin_switch.png'
            );
            $info[] = array
            (
                'title' => L_('应用排序'),
                'url' => '/pages/group/groupDevelopmentSort/groupDevelopmentSort?community_id='.$community_id,
                'icon' => $site_url . '/static/community/application/app_admin_sort.png'
            );
        }

        $this->returnCode(0, $info);
    }
    /**
     * 群应用排序
     */
    public function application_change_sort(){
        if(!$_POST['community_id']){
            $this->returnCode('71000029');
        }
        $community_id = intval($_POST['community_id']);
        if(!$_POST['order_word']){
            $this->returnCode('72000052');
        }
        $order_word = $_POST['order_word'];
        $order_info = array(
            'order_vote', 'order_notice', 'order_activity', 'order_shop', 'order_dynamic', 'order_topic', 'order_album', 'order_file', 'order_card'
        );
        if (!in_array($order_word, $order_info)) {
            $this->returnCode('72000055');
        }
        if(!$_POST['order_num'] && $_POST['order_num'] != 0){
            $this->returnCode('72000053');
        }
        $order_num = intval($_POST['order_num']);
        // 暂时排序值只有 0 到 8
        if (!($order_num >= 0 && $order_num < 9)) {
            $this->returnCode('72000054');
        }
        $info = D('Community_application_development')->application_change_sort($community_id, $order_word, $order_num);
        $this->returnCode(0, $info);
    }


    /**
     *  群应用字段过滤
     * @param $word_array 过滤的字段
     * @param $now_word_array 原来的字段
     * @return array
     */
    private function filter_word($word_array, $now_word_array) {
        $word = array(
            'is_community_vote',
            'is_community_notice',
            'is_community_activity',
            'is_community_shop',
            'is_community_dynamic',
            'is_community_topic',
            'is_community_album',
            'is_community_file',
            'is_community_card',
            'is_add_folder',
            'is_add_album',
        );
        // 返回值初始化
        $return_word = array();
        foreach ($word_array as $key => $val) {
            if (in_array($key, $word) && intval($val) != $now_word_array[$key]) {
                if (in_array(intval($val), array(1,2))) {
                    $return_word[$key] = $val;
                } else {
                    $this->returnCode('72000017');
                }
            }
        }
        return $return_word;
    }



    // 群消息模块
    /**
     * 在聊天群里面发布图片消息
     */
    public function chat_img_info() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (empty($_POST['community_id'])) {
            $this->returnCode('70000004');
        }
        $community_id = intval($_POST['community_id']);
        // 名片背景图
        $image = array();
        if(!empty($_FILES) && $_FILES['file']['error'] != 4){
            $image = D('Image')->handle($uid, 'comm', 1, array('size' => 10), false, true);
            if ($image['error']) {
                $this->returnCode(1,array(), $image['message']);
            }
        }
        if ($image) {
            $url = replace_file_domain($image['url']['image']);
            $img_id = $image['pigcms_id'];
            $info = M('Image')->where(array('pigcms_id' => $img_id))->field('img_width', 'img_height')->find();
            $img_width = 0;
            $img_height = 0;
            if ($info) {
                $img_width = $info['img_width'] ? $info['img_width'] : 0;
                $img_height = $info['img_height'] ? $info['img_height'] : 0;
            }
            // 同步发消息到云通讯
            $group_id = $community_id;
            $msg_body = array();
            $msgType = array();
            $msgType['MsgType'] = 'TIMTextElem';
            $msgType['MsgContent'] = array(
                'Text' => '【￥image￥】&' . $img_id .  '&' . urlencode($url) . '&' . $img_width . '&' . $img_height
            );
            $msg_body[] = $msgType;
            $database_info = D('Community_info');
            $ret_group = $database_info->qcloud_send_group_msg($group_id, $msg_body, $uid);
            if (!empty($ret_group) && $ret_group['ActionStatus'] == 'OK') {
                $this->returnCode(0, array(
                    'url' => $url,
                    'img_id' => $img_id
                ));
            } else {
                $this->returnCode(1, array(), L_('图片发送失败！请重试~'));
            }
        } else {
            $this->returnCode(1, array(), L_('图片发送失败！请重试~'));
        }
    }

    /**
     * 撤销自己发的消息,相当于删除此条消息
     */
    public function revoke_message() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (empty($_POST['community_id'])) {
            $this->returnCode('70000004');
        }
        $community_id = intval($_POST['community_id']);
        if (empty($community_id)) {
            $this->returnCode('70000004');
        }
        // 消息id
        $mid = intval($_POST['mid']);
        if (empty($mid)) {
            $this->returnCode('72000046');
        }
        // 用户身份
        $user = $_POST['user'];
        if (empty($user)) {
            $this->returnCode('72000047');
        }
        if ('user_' . $uid != $user) {
            $this->returnCode('72000048');
        }
        // 消息时间
//        $time = $_POST['time'];
//        if ($time) {
//            // 暂时不做时间限制，后期添加时间限制
//        }
        $revoke =  D('Community_info')->qcloud_delete_group_msg_recall($community_id, $mid);
        if (!empty($revoke) && $revoke['ActionStatus'] == 'OK') {
            $this->returnCode(0, array(
                'mid' => $mid
            ));
        } else {
            $this->returnCode(1, array(), L_('删除消息失败！请重试~'));
        }
    }

    /**
     * @人 的成员列表
     */
    public function reminding_members_list()
    {
        // 判断用户是否登录
        if (empty($this->_uid)) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (empty($_POST['community_id'])) {
            $this->returnCode('70000012');
        }
        $where = array();
        // 昵称模糊查询
        if ($_POST['comm_nickname']) {
            $where['comm_nickname'] = array('like', '%' . $_POST['comm_nickname'] . '%');
        }
        // 查询一下群信息
        $database_community_info = D('Community_info');
        $community_info = $database_community_info->where(array('community_id' => $_POST['community_id']))->find();
        if (empty($community_info)) {
            $this->returnCode('70000007');
        }
        if ($community_info['status'] == 3) {
            $this->returnCode('70000024');
        }
        // 分页处理

        $database_Community_join = D('Community_join');

        $where['community_id'] = $_POST['community_id'];
        $where['group_owner'] = 1;
        $where['add_status'] = 3;
        $where['add_uid'] = array('neq', $uid);
        $members = array();
        // 获取群成员信息
        $join_count = $database_Community_join->where(array('add_status' => 3, 'community_id' => $_POST['community_id']))->count();
        import('@.ORG.comm_page');
        $p = new Page($join_count, 15);
        $firstRow = $p->firstRow;
        $members['totalPage'] = $p->totalPage;
        // 获取分页首页信息 且 不是群主时候
        if ($firstRow == 0 && $community_info['group_owner_uid'] != $uid) {
            $listRows = 14;
            // 需要获取群主信息
            $get_owner = true;
        } else {
            $listRows = $p->listRows;
            $get_owner = false;
        }
        $group_members_info = $database_Community_join->where($where)->limit($firstRow . ',' . $listRows)->select();

        $database_User = D('User');
        $site_url = C('config.site_url');
        if (!empty($group_members_info)) {
            $members['list'] = array();
            foreach ($group_members_info as $val) {
                $add_user = $database_User->field('nickname, phone, avatar')->where(array('uid' => $val['add_uid']))->find();
                if (!empty($add_user)) {
                    if ($add_user['nickname']) $val['nickname'] = $add_user['nickname'];
                    if ($add_user['phone']) $val['phone'] = substr_replace($add_user['phone'], '****', 3, 4);
                    if (empty($add_user['avatar'])) {
                        // 没有头像取默认头像
                        $val['avatar'] = $site_url . '/static/avatar.jpg';
                    } else {
                        $val['avatar'] = $add_user['avatar'];
                    }
                } else {
                    // 没有头像取默认头像
                    $val['avatar'] = $site_url . '/static/avatar.jpg';
                }
                $members['list'][] = $val;
            }
        } else {
            $members['list'] = array();
        }
        // 获取者不是群主 且需要获取群主信息的时候获取
        if ($get_owner && $community_info['group_owner_uid'] != $uid) {
            $where['group_owner'] = 2;
            $group_members_owner = $database_Community_join->where($where)->find();
            if (!empty($group_members_owner)) {
                $owner_user = $database_User->field(true)->where(array('uid' => $group_members_owner['add_uid']))->find();
                if ($owner_user['nickname']) $group_members_owner['nickname'] = $owner_user['nickname'];
                if ($owner_user['phone']) $group_members_owner['phone'] = substr_replace($owner_user['phone'], '****', 3, 4);
                if (empty($owner_user['avatar'])) {
                    // 没有头像取默认头像
                    $group_members_owner['avatar'] = $site_url . '/static/avatar.jpg';
                } else {
                    $group_members_owner['avatar'] = $owner_user['avatar'];
                }
                $group_members_owner['is_owner'] = true;
                array_unshift($members['list'], $group_members_owner);
            }
        }
        // 判断是否为群主
        if ($community_info['group_owner_uid'] == $uid) {
            $members['is_owner'] = true;
        } else {
            $members['is_owner'] = false;
        }
        $members['community_info'] = array();
        if ($community_info['community_avatar']) {
            $members['community_info']['avatar'] = file_domain() . '/upload/comm/' . $community_info['community_avatar'];
        } elseif ($community_info['avatar']){
            $members['community_info']['avatar'] = file_domain() . $community_info['avatar'];
        } else {
            $members['community_info']['avatar'] = '';
        }

        $this->returnCode(0, $members);
    }


    /**
     * 消息@人，处理为发送推送消息给对应的人  可单人，可多人，可全部（除自己以外）@人提醒--即推送消息
     * comm_target   all_member 全体  members 对应群成员
     * message    具体的消息信息
     */
    public function reminding_others() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (empty($_POST['community_id'])) {
            $this->returnCode('70000004');
        }
        $community_id = intval($_POST['community_id']);
        // 首先查询群信息
        $community_info = M('Community_info')->where(array('community_id' => $community_id))->field('group_owner_uid')->find();
        if (empty($community_info)) {
            $this->returnCode('70000005');
        }
        if ($community_info['group_owner_uid'] != $uid) {
            $this->returnCode(0, array('community_id' => $community_id));
        }
        //  @人目标 分 对应人和全体
        if (empty($_POST['comm_target']) || !in_array($_POST['comm_target'], array('all_member', 'members'))) {
            $this->returnCode('72000062');
        }
        $target = $_POST['comm_target'];
        $join_id_arr = array();
        //  如果@人目标为对应人 即存在对应成员join_id数组，单人或者多人
        if (!empty($target) && $target == 'members') {
            if (empty($_POST['join_id_arr'])) {
                $this->returnCode('72000063');
            }
            $join_id_arr = $_POST['join_id_arr'];
        }
        // 具体的消息信息
        if (empty($_POST['message'])) {
            $this->returnCode('72000064');
        }
        $message = $_POST['message'];
        $reminding_others_info = D('Community_join')->create_comm_plan($community_id, 'comm_reminding_others', $join_id_arr, 0, $uid, $message);
        $this->returnCode(0, array('community_id' => $community_id));

    }


    // 邮件发送区域

    // 群名片发送邮件
    public function card_mail()
    {
           
        if (!empty($_POST)) {
            // 处理添加的数据
            $database_card = D('Community_card');
            $database_mail = D('Community_mail');
            //群ID
            if (empty($_POST['community_id'])) {
                $this->returnCode('70000004');
            }
            //邮箱
            if (empty($_POST['mail'])) {
                $this->returnCode('71000065');
            }
            //查询名片
            $card = $database_card->field('excel_url, card_name')->where(array('community_id'=>$_POST['community_id']))->find();
            $community_name = D('Community_info')->field('community_name')->where(array('community_id'=>$_POST['community_id']))->find();
            if (!$card) {
                $this->returnCode('71000030');
            }
            $excel_info = array();
            if ($card['excel_url']) {
                $excel_info['excel_url'] = replace_file_domain($card['excel_url']);
            } else {
                $this->returnCode('72000049');
            }
            $where = array(
                'community_id' => $_POST['community_id'],
                'join_status' => 1,
            );
            $join_count = D('Community_card_bind')->where($where)->count();
            $excel_url = $excel_info['excel_url'];
            $content = L_('<h2>【X1】群名片【X2】（共X3人）</h2><p><strong>群名片名单：</strong></p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="X4">X4</a></p><p style="color:red;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;点击链接下载群名片Excel表格汇总</p>',array(
                'X1'=>$community_name['community_name'],
                'X2'=>$card['card_name'],
                'X3'=>$join_count,
                'X4'=>$excel_url,
            ));
            $res = $database_mail->send($_POST['mail'],L_('群名片数据-小猪社群！'),$content);
            
            if ($res === true ) {
                $this->returnCode(0, $res);
            } else {
                $this->returnCode(1, array(),L_('发送失败，请重试！'));
            }
        }
    }

    // 群文件发送邮件
    public function file_mail()
    {
        if (!empty($_POST)) {
            // 处理添加的数据
            $database_file = D('Community_file');
            $database_mail = D('Community_mail');
            //文件ID
            if (empty($_POST['file_id'])) {
                $this->returnCode('70000004');
            }
            //邮箱
            if (empty($_POST['mail'])) {
                $this->returnCode('71000065');
            }
            //查询文件
            $file = $database_file->field('community_id,file_url,file_name')->where(array('file_id'=>$_POST['file_id']))->find();
            $community_name = D('Community_info')->field('community_name')->where(array('community_id'=>$file['community_id']))->find();
            if (!$file) {
                $this->returnCode('71000030');
            }
            $excel_info = array();
            if ($file['file_url']) {
                $excel_info['file_url'] = replace_file_domain($file['file_url']);
            } else {
                $this->returnCode('72000009');
            }
            $file_url = $excel_info['file_url'];
            $content = L_('<h2>【X1】群文件【X2】</h2><p><strong>群文件：</strong></p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="X3">X3</a></p><p style="color:red;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;点击链接下载群文件Excel表格汇总</p>',array(
                'X1'=>$community_name['community_name'],
                'X2'=>$file['file_name'],
                'X3'=>$file_url,
            ));
            $res = $database_mail->send($_POST['mail'],L_('群文件数据-小猪社群！'),$content);
            if ($res === true ) {
                $this->returnCode(0, $res);
            } else {
                $this->returnCode(1, array(),L_('发送失败，请重试！'));
            }
        }
    }

    /**
     * [card_praise 单人名片点赞]
     * @return [type] [description]
     */
    public function card_praise(){
        $data['card_praise_user_id'] = $this->_uid;
        $data['join_id'] = intval($_POST['join_id']);
        if(!$data['join_id']){
            $this->returnCode(L_('缺少群名片成员ID参数!'));
        }
        $data['community_id'] = $_POST['community_id'];
        if(!$data['community_id']){
            $this->returnCode(L_('缺少群ID参数!'));
        }
        $card_praise_model = D('Community_card_praise');
        $res = $card_praise_model->where($data)->find();
        if($res){
            $result = $card_praise_model->where($data)->delete();
            if($result != false){
                $this->returnCode(0,L_('点赞已取消!'));
            }
        }else{
            $result = $card_praise_model->add($data);
            if($result != false){
                $this->returnCode(0,L_('点赞成功!'));
            }else{
                $this->returnCode(1,L_('点赞失败!'));
            }
        }
    }

    // 群成员
    public function group_members_list()
    {
        // 判断用户是否登录
        if (empty($this->_uid)) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (empty($_POST['community_id'])) {
            $this->returnCode('70000012');
        }
        $where = array();
        // 昵称模糊查询  上传的字段存在nickname  就赋予条件
        if (array_key_exists('nickname', $_POST)) {
            $where['comm_nickname'] = array('like', '%' . $_POST['nickname'] . '%');
        }
        // 查询一下群信息
        $database_community_info = D('Community_info');
        $community_info = $database_community_info->where(array('community_id' => $_POST['community_id']))->find();
        if (empty($community_info)) {
            $this->returnCode('70000007');
        }
        if ($community_info['status'] == 3) {
            $this->returnCode('70000024');
        }
        // 分页处理

        $database_Community_join = D('Community_join');
        $m_community_card = M('Community_card');
        $d_community_card_bind = M('Community_card_bind');
        $d_community_card_praise = D('Community_card_praise');
        $where['community_id'] = $_POST['community_id'];
        $where['group_owner'] = 1;
        $where['add_status'] = 3;
        $members = array();
        $member_number = $database_Community_join->where(array('add_status' => 3, 'community_id' => $_POST['community_id']))->count();
        $join_count = $database_Community_join->where(array('add_status' => 3, 'community_id' => $_POST['community_id']))->count();
        import('@.ORG.comm_page');
        $p = new Page($join_count, 15);
        $firstRow = $p->firstRow;
        $members['totalPage'] = $p->totalPage;
        $listRows = $p->listRows;
        $get_owner = true;
        if (array_key_exists('comm_nickname', $where)) {
            unset($where['group_owner']);
            $get_owner = false;
        }
        $group_members_info = $database_Community_join->where($where)->limit($firstRow . ',' . $listRows)->select();

        $database_User = D('User');
        if (!empty($group_members_info)) {
            $members['list'] = array();
            foreach ($group_members_info as $val) {
                $add_user = $database_User->field('nickname, phone, avatar')->where(array('uid' => $val['add_uid']))->find();
                if (!empty($add_user)) {
                    if ($add_user['nickname']) $val['nickname'] = $add_user['nickname'];
                    if ($add_user['phone']) $val['phone'] = substr_replace($add_user['phone'], '****', 3, 4);
                    if (empty($add_user['avatar'])) {
                        // 没有头像取默认头像
                        $val['avatar'] = C('config.site_url') . '/static/avatar.jpg';
                    } else {
                        $val['avatar'] = $add_user['avatar'];
                    }
                } else {
                    // 没有头像取默认头像
                    $val['avatar'] = C('config.site_url') . '/static/avatar.jpg';
                }
                $val['is_owner'] = false;
                if ($val['add_uid'] == $uid) {
                    $val['is_user'] = true;
                } else {
                    $val['is_user'] = false;
                }
                if($val['add_uid'] && $val['community_id']){
                    $is_card = $d_community_card_bind
                            ->where(array('join_uid'=>$val['add_uid'],'community_id'=>$val['community_id'],'join_status'=>1))
                            ->find();
                    if($is_card){
                        $val['card_praise'] = $d_community_card_praise->where(array('community_id'=>$_POST['community_id'],'join_id'=>$is_card['join_id']))->count();
                        $val['browse_number'] = $is_card['browse_number'];
                        $val['is_join'] = true;
                        $val['join_id'] = $is_card['join_id'];
                    }else{
                        $val['card_praise'] = '';
                        $val['browse_number'] = '';
                        $val['is_join'] = false;
                        $val['join_id'] = '';
                    }
                }   

                $members['list'][] = $val;
            }
        } else {
            $members['list'] = array();
        }
        if ($get_owner) {
            unset($where['comm_nickname']);
            $where['group_owner'] = 2;
            $group_members_owner = $database_Community_join->where($where)->find();
            if (!empty($group_members_owner)) {
                $owner_user = $database_User->field(true)->where(array('uid' => $group_members_owner['add_uid']))->find();
                if ($owner_user['nickname']) $group_members_owner['nickname'] = $owner_user['nickname'];
                if ($owner_user['phone']) $group_members_owner['phone'] = substr_replace($owner_user['phone'], '****', 3, 4);
                if (empty($owner_user['avatar'])) {
                    // 没有头像取默认头像
                    $group_members_owner['avatar'] = C('config.site_url') . '/static/avatar.jpg';
                } else {
                    $group_members_owner['avatar'] = $owner_user['avatar'];
                }
                $group_members_owner['is_owner'] = true;
                if ($group_members_owner['add_uid'] == $uid) {
                    $group_members_owner['is_user'] = true;
                } else {
                    $group_members_owner['is_user'] = false;
                }
                if($group_members_owner['add_uid'] && $group_members_owner['community_id']){
                    $owner_card = $d_community_card_bind
                            ->where(array('join_uid'=>$group_members_owner['add_uid'],'community_id'=>$group_members_owner['community_id'],'join_status'=>1))
                            ->find();
                    $group_members_owner['card_praise'] = $d_community_card_praise->where(array('community_id'=>$_POST['community_id'],'join_id'=>$owner_card['join_id']))->count();
                    if($owner_card){
                        $group_members_owner['browse_number'] = $owner_card['browse_number'];
                        $group_members_owner['is_join'] = true;
                        $group_members_owner['join_id'] = $owner_card['join_id'];
                    }else{
                        $group_members_owner['browse_number'] = '';
                        $group_members_owner['is_join'] = false;
                        $group_members_owner['join_id'] = '';
                    }
                }
            }
        }
        
        // 判断是否为群主
        if ($community_info['group_owner_uid'] == $uid) {
            $members['is_owner'] = true;
        } else {
            $members['is_owner'] = false;
        }
        if ($community_info['member_number'] != $member_number) {
            // 如果统计数据出错，重写改写一下
            $community_info = $database_community_info->where(array('community_id' => $_POST['community_id']))->data(array('member_number' => $member_number))->save();
        }
        $group_members_myself = $database_Community_join->where(array('community_id' => $_POST['community_id'], 'add_uid' => $uid))->find();
        $members['community_name'] = $community_info['community_name'];
        $members['comm_nickname'] = $group_members_myself['comm_nickname'];
        $members['community_id'] = $_POST['community_id'];
        $members['is_nickname'] = ($community_info['is_nickname'] == 2) ? true : false;

        //群名片的基础信息
        $card_single = $m_community_card->where(array('community_id'=>$_POST['community_id']))->find();

        // 查询一下当前用户是否加入群
        $join_comm = $database_Community_join->where(array('add_uid' => $uid, 'community_id'=>$_POST['community_id']))->field('add_status')->find();

        // 查询一下当前用户是否加入群名片
        $where_join = array(
            'join_status' => 1,
            'community_id' => $_POST['community_id'],
            'community_card_id' => $card_single['community_card_id'],
            'join_uid' => $uid
        );
        $join = $d_community_card_bind->where($where_join)->field('join_uid')->find();
        $members['is_join'] = false;
        if ($join) {
            if ($join_comm['add_status'] && $join_comm['add_status'] == 3) {
                $members['is_join'] = true;
            } else {
                $members['is_join'] = false;
            }
        }
        $where_num = array(
            'join_status' => 1,
            'community_id' => $_POST['community_id'],
            'community_card_id' => $card_single['community_card_id']
        );
        $community_card_id = $m_community_card->where($where_num)->setInc('browse_num');
        
        // 获取群主的头像和昵称
        $user = M('User')
                ->field('nickname, avatar')
                ->where(array('uid' => $community_info['group_owner_uid']))
                ->find();
        $user_message = M('Community_card_bind')
                        ->field('join_id,browse_number')
                        ->where(array('join_uid'=>$community_info['group_owner_uid'],'community_id'=>$_POST['community_id']  ))
                        ->find();   
        $user['join_id'] =  $user_message['join_id'];              
        $user['browse_number'] =  $user_message['browse_number'];              
        if ($card_single['community_card_bgimg']) {
            $card_single['community_card_bgimg'] = replace_file_domain($card_single['community_card_bgimg']);
        }
        $user['card_praise'] = $d_community_card_praise->where(array('community_id'=>$_POST['community_id'],'join_id'=>$user['join_id']))->count();
        $members['community_card'] = $card_single;
        $members['owner'] = $user;
        $this->returnCode(0, $members);
    }


    // pc端上传功能
    // 收集测试小程序跳转信息
    public function small_program_activity() {
//        fdump('收集测试小程序跳转信息   ' . __LINE__, 'small_program_jump', true);
//        fdump('$_POST   ' . __LINE__, 'small_program_jump', true);
//        fdump($_POST , 'small_program_jump', true);
//        fdump('$_GET   ' . __LINE__, 'small_program_jump', true);
//        fdump($_GET , 'small_program_jump', true);
//        fdump('$_FILES   ' . __LINE__, 'small_program_jump', true);
//        fdump($_FILES , 'small_program_jump', true);
//        fdump('$_SERVER   ' . __LINE__, 'small_program_jump', true);
//        fdump($_SERVER , 'small_program_jump', true);
//        fdump('$_SESSION   ' . __LINE__, 'small_program_jump', true);
//        fdump($_SESSION , 'small_program_jump', true);
    }

    // 小程序端接口确认登录pc端上传页面
    public function pc_confirm_login() {
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $uid = $this->_uid;
        if (!$_POST['file_ticket']) {
            $this->returnCode('72000059');
        }
        $file_ticket = $_POST['file_ticket'];
        if (!intval($_POST['id'])) {
            $this->returnCode('72000059');
        }
        $id = intval($_POST['id']);
        $m_community_file_qrcode = M('Community_file_qrcode');
        $where = array(
            'ticket' => $file_ticket,
            'id' => $id
        );
        // 二维码过期时间
        // 获取配置有效时间（单位： 秒）
        $community_code_effective_time = C('config.community_code_effective_time');
        if ($community_code_effective_time && intval($community_code_effective_time) > 0) {
            $effective_time = intval($community_code_effective_time);
        } else {
            $effective_time = 1800; // 取默认的半小时
        }
        $msg = $m_community_file_qrcode->where($where)->find();
        if ($msg['add_time'] && intval($_SERVER['REQUEST_TIME']) - intval($msg['add_time']) > $effective_time) {
            $m_community_file_qrcode->where(array('add_time'=>array('lt',($_SERVER['REQUEST_TIME']-$effective_time))))->delete();
            $this->returnCode('72000060');
        }
        if ($msg['uid']) {
            $this->returnCode(0, array('uid' => $msg['uid']));
        }
        $qrcode_id = $m_community_file_qrcode->where($where)->data(array('uid' => $uid))->save();
        if(empty($qrcode_id)){
            $this->returnCode('72000061');
        }
        $this->returnCode(0, array('uid' => ``));
    }

    // 小程序端获取pc端上传地址
    public function pc_url() {
        $url = C('config.site_url') . '/index.php?c=CommFilePc';
        $this->returnCode(0, array('url' => $url));
    }

    //群首页
    public function community_index(){
        $join_community_id = D('Community_join')->field('community_id')->where(array('add_uid'=>$this->_uid,'add_status'=>3))->select();
        foreach ($join_community_id as $key => $value) {
            $ids[] = $value['community_id'];
        }
        $where['a.community_id'] = array('in',$ids);
        $where['a.is_del'] = 0;
        $pageSize = 10;
        $page= $_POST['page'] ? $_POST['page'] : 1;
        $dynamic_model = D('Community_dynamic');
        // 如果 $pageSize 大于 0 分页 ，小于等于 0 查询全部
        if ($pageSize > 0) {
            $total =$dynamic_model->alias('a')->where($where)->count();
            $page = isset($page) ? intval($page) : 1;
            $totalPage = ceil($total / $pageSize);
            $firstRow = $pageSize * ($page - 1);

            $Distinct = 'false';
            $field='a.*,b.avatar,b.nickname,c.community_name';
            $join='join pigcms_user as b on a.user_id=b.uid join pigcms_community_info as c on a.community_id=c.community_id';
            $order = 'a.addtime desc';
            $limit = $firstRow.','.$pageSize;
            $list = $dynamic_model->dynamic_select($Distinct,$field,$join,$where,$order,$limit);

            $reply_model = D('Community_reply');
            $user_model = D('User');
            $user_field = 'nickname';
            $praise_model = D('Community_praise');
            $community_card = D('Community_card_bind');
            $community_join = D('Community_join');
            foreach($list as $key=>$value){
                $maple['dynamic_id'] = $value['id'];
                $maple['reply_is_del'] = 0;
                $dynamic_reply = $reply_model->reply_select('','','',$maple,'','');
                foreach($dynamic_reply as $kk=>$vv){
                    $user_where1= array('uid'=>$dynamic_reply[$kk]['reply_user_id']);
                    $user_where2= array('uid'=>$dynamic_reply[$kk]['reply_to_user_id']);
                    $user = $user_model->user_find($user_field,$user_where1);
                    $to_user = $user_model->user_find($user_field,$user_where2);
                    if($this->_uid != $vv['reply_user_id']){
                        $dynamic_reply[$kk]['can_reply'] = true;
                    }else{
                        $dynamic_reply[$kk]['can_reply'] = false;
                    }
                    $dynamic_reply[$kk]['user'] = $user['nickname'];
                    $dynamic_reply[$kk]['to_user'] = $to_user['nickname'];
                }
                if($value['img']){
                    $list[$key]['img'] = unserialize($value['img']);
                    $dynamic_img = $list[$key]['img'];
                    $list[$key]['img'] = array();
                    foreach($dynamic_img as $k=>$v){
                        if(!(strpos($v,'http') === 0)) {
                            $list[$key]['img'][] = replace_file_domain($v);
                        } else {
                            $list[$key]['img'][] = $v;
                        }
                    }
                }
                if($value['file']){
                    $list[$key]['file'] = unserialize($value['file']);
                    $dynamic_file = $list[$key]['file'];
                    $list[$key]['file'] = array();
                    if(!(strpos($dynamic_file['file_url'],'http') === 0)) {
                        $list[$key]['file']['file_url'] = replace_file_domain($dynamic_file['file_url']);
                    } else {
                        $list[$key]['file']['file_url'] = $dynamic_file['file_url'];
                    }
                    $list[$key]['file']['file_remark'] = $dynamic_file['file_remark'];
                    $list[$key]['file']['file_id'] = $dynamic_file['file_id'];
                    $list[$key]['file']['file_img'] = $dynamic_file['file_img'];
                    $list[$key]['file']['is_img'] = $dynamic_file['is_img'];
                }
                if($value['application_detail']){
                    $list[$key]['application_detail'] = unserialize($value['application_detail']);
                }
                $praise_field = 'b.nickname';
                $praise_join = 'join pigcms_user as b on a.praise_user_id=b.uid';
                $praise_where = array('dynamic_id'=>$value['id']);          
                $praise_dynamic = $praise_model->praise_select('true',$praise_field,$praise_join,$praise_where,'','');//动态的点赞
                
                $arr = D('Community_dynamic_topic_bind')->field('topic_id')->where(array('dynamic_id'=>$value['id']))->select();
                $array=array();
                foreach ($arr as $kkk => $vvv) {
                    $array[] =  $vvv['topic_id']; 
                }
                
                $map['topic_id'] = array('in',$array);
                $topic = D('Community_topic')->field('topic_id,topic_title')->where($map)->select();
                $list[$key]['addtime'] = time_info($list[$key]['addtime']);
                $list[$key]['reply'] = $dynamic_reply;
                $list[$key]['praise'] = $praise_dynamic;
                $list[$key]['topic_list'] = $topic;

                //是否可以为用户本人
                $dynamic_user_id = $dynamic_model->dynamic_find('','user_id','',array('id'=>$list[$key]['id']));
                $dynamic_praise = $praise_model->praise_find('','','',array('dynamic_id'=>$value['id'],'praise_user_id'=>$this->_uid));
                if(!$dynamic_praise){
                    $list[$key]['can_praise'] = true;
                }else{
                    $list[$key]['can_praise'] = false;
                }
                $group_owner_uid = D('Community_info')->where(array('community_id'=>$value['community_id']))->getField('group_owner_uid');
                //是否为群主或该动态的发布者,用有删除的去权限
                if($this->_uid == $group_owner_uid || $this->_uid == $dynamic_user_id['user_id']){
                    $list[$key]['can_del'] = true;
                }else{
                    $list[$key]['can_del'] = false;
                }
                $is_join = $community_card->where(array('join_uid'=>$value['user_id'],'community_id'=>$value['community_id'],'join_status'=>1))->find();
                $add_id = $community_join->where(array('community_id'=>$value['community_id'],'add_uid'=>$value['user_id'],'add_status'=>3,'del_info'=>1))->getField('add_id');
                if($is_join){
                    $list[$key]['is_join'] = true;
                    $list[$key]['add_id'] = $add_id;
                    $list[$key]['join_id'] = $is_join['join_id'];
                }else{
                    $list[$key]['is_join'] = false;
                    $list[$key]['add_id'] = $add_id;
                    $list[$key]['join_id'] = $is_join['join_id'];
                }
            }
            $info_list = array(
                'total' => $total,
                'pageTotal' => $totalPage,
                'has_more' => $totalPage > $page ? true : false,
                'list' => $list
            );
        }
        $this->returnCode(0,$info_list);
    }

    public $a4_face_class = '';
    /**
     * 测试接口
     */
    public function test_wanzy() {
        // A4 调试接口

        if (!$this->a4_face_class) {
            import('@.ORG.face.a4_face');
            $this->a4_face_class = new a4_face();
        }
        $secret_key = $this->a4_face_class->secret_key;
        $apikey = $this->a4_face_class->apikey;
        $url = $this->a4_face_class->url;

        // 参数处理部分
        $accountName = 'wang';
        $password = '123456';
        $password = md5($password);
        $timestamp = strval(time());
        $arrContent = array(
            'apikey'=> $apikey,
            'timestamp'=>$timestamp,
            'method'=>'loginManagement',
            'accountName'=> $accountName,
            'password'=> $password
        );
        // 参数加密部分
        $sign = $this->a4_face_class->getSign($secret_key, $url, $arrContent);
        $arrContent['sign'] = $sign;
        // 上传参数处理部分
        $postData = $this->a4_face_class->get_encrypt_key($arrContent);
        $return =  $this->a4_face_class->curlPost($url, $postData);
        dump($return);
    }

    public function test_one() {
       D('House_village_face_door')->get_a3_device(0);
    }

    public function getProvinces() {
        $provinces_data = D('Face_door_a4_service')->getAreas(3401,34);
        dump($provinces_data);
    }


    public function addCommunity() {
        $community_data = D('Face_door_a4_service')->addA4Community(42);
        dump($community_data);
    }
    public function addA4Zone() {
        $community_data = D('Face_door_a4_service')->addA4Zone(42);
        dump($community_data);
    }
    public function addA4Building() {
        $community_data = D('Face_door_a4_service')->addA4Building(39);
        dump($community_data);
    }
    public function addA4Unit() {
        $community_data = D('Face_door_a4_service')->addA4Unit(36);
        dump($community_data);
    }
    public function addA4Room() {
        $community_data = D('Face_door_a4_service')->addA4Room(8748);
        dump($community_data);
    }

    public function addA4RoomHolds() {
        $community_data = D('Face_door_a4_service')->addA4RoomHolds(14);
//        dump($community_data);
    }

    public function addA4Card() {
        $community_data = D('Face_door_a4_service')->addA4CommunityAccount('42');
        dump($community_data);
    }

    public function test1() {
        import('@.ORG.face.word_encryption');
        $word_encryption = new word_encryption();
        $word = 'http://o2o-static.pigcms.com/upload/house/000/000/000/5f4e0534bbbd2281.png?x-oss-process=image/resize,m_fill,w_200,h_200';
        $word_encryption_data =  $word_encryption->text_encryption($word);
        dump($word_encryption_data);
        $word_text_decrypt =  $word_encryption->text_decrypt($word_encryption_data);
        dump($word_text_decrypt);
    }

    public function wan_test() {
        import('@.ORG.qywx.config');
        $config = new config();
        $info = $config->qyRegister();
        dump('引导注册》》》》》》》》》》');
        dump($info);
        $html = '<a target="_blank" data-url="'.$info['url'].'" href="'.$info['url'].'"><img src="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=register&c=blue&s=large" srcset="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=register&c=blue&s=large@2x 2x" referrerpolicy="unsafe-url" alt="企业微信"></a>';
        echo $html;
        $infos = $config->qyInstall();
        dump('引导下载》》》》》》》》》》');
        dump($infos);
        $html = '<a target="_blank" data-url="'.$infos['url'].'" href="'.$infos['url'].'"><img src="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=install&c=white&s=large" srcset="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=install&c=white&s=large@2x 2x" referrerpolicy="unsafe-url" alt="企业微信"></a>';
        echo $html;
        $info1 = $config->qyLogin();
        dump('登录下载》》》》》》》》》》');
        dump($info1);
        $html = '<a data-url="'.$info1['url'].'" href="'.$info1['url'].'"><img src="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=login&c=white&s=large" srcset="https://open.work.weixin.qq.com/service/img?id=ww21001813f8f04566&t=login&c=white&s=large@2x 2x" referrerpolicy="unsafe-url" alt="企业微信"></a>';
        echo $html;
    }


    public function qywork_reg(){
        import('@.ORG.qywx.config');
        $config = new config();
        $info = $config->qyRegister();
        if($info['url']){
            redirect($info['url']);
        }else{
            exit('链接生成失败，请刷新页面重试。');
        }
    }


    public function wan_test1() {
//        $device_sn = "F71957697";
//        $groupId = "529236b4c2674d7389dc15328af0c558";
//        $member_id = '14959';
        $device_sn = 'D63313968';
        $group_id_arr = M('Face_bind_device')->where(['device_sn' => $device_sn])->find();
        if ($group_id_arr) {
            foreach ($group_id_arr as $item) {
                // 查询权限下发状况
                $info = D('Face_door_a5_service')->getAuthSearchStatus($item['group_id']);
                dump('对应权限组:'.$item['group_id'].'<>对应设备'.$device_sn);
                dump($info);
            }
        }
        // 查询设备状态
        $msg = D('Face_door_a5_service')->getEquipmentInfo($device_sn);
//        // 查询人员详情
//        $msg1 = D('Face_door_a5_service')->getMember($member_id);
//        dump($msg);
//        dump($msg1);
    }



    function wanzytest11() {
        $img = "http://o2o-static.pigcms.com/upload/houseface/000/003/229/20200715101952_5f0e67c84d9bf.png";
        dump(microtime(true));
        $info = D('Face_door_a5_service')->faceAnalysisScore($img);
        dump(microtime(true));
        dump($info);
    }

    /**
     * Notes: 16进制异或计算
     * @param $byte1
     * @param $byte2
     * @return string
     * @author: wanzy
     * @date_time: 2021/1/20 18:27
     */
    function hexOr($byte1, $byte2)
    {
        $result='';
        $byte1= str_pad(base_convert($byte1, 16, 2), '8', '0', STR_PAD_LEFT);
        $byte2= str_pad(base_convert($byte2, 16, 2), '8', '0', STR_PAD_LEFT);
        $len1 = strlen($byte1);
        for ($i = 0; $i < $len1 ; $i++) {
            $result .= $byte1[$i] == $byte2[$i] ? '0' : '1';
        }
        return strtoupper(base_convert($result, 2, 16));
    }


    public function repairErrImportMeterData() {
        // 2021-01-04客户抄表录入导入错误数据
        $fileName = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/api/log/' . date('Ymd') . '/1238888NetRepairErrImportMeterData.php';
        $dirName = dirname($fileName);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        file_put_contents($fileName,PHP_EOL.date('Y-m-d H:i:s'));
        if(!file_exists($fileName)){
            file_put_contents($fileName,'<?php');
        }

        $village_id = 39;
        $where = "`a`.`action` = '导入抄表单-增加' AND `a`.`add_time` >= '1641250800' AND `a`.`money` > '0' AND `a`.`type` = '2'";
        $field = 'a.id as copy_record_id, a.action, a.pigcms_id as bind_id, a.name,a.phone,a.money,
        b.name as bindName,b.phone as bindPhone,b.electric_price,b.uid,b.single_id,b.floor_id,b.layer_id,b.vacancy_id,b.village_id';
        $sql = "SELECT {$field}";
        $sql .= " FROM copy_pigcms_house_village_user_edit_price_record AS a ";
        $sql .= " LEFT JOIN pigcms_house_village_user_bind AS b ON a.pigcms_id=b.pigcms_id";
        $sql .= " WHERE " . $where;
        $sql .= ' ORDER BY `a`.`add_time` ASC LIMIT 2000';
        $list = D()->query($sql);
        if ($list) {
            $db_house_village = D('House_village');
            $db_house_village_user_bind = D('House_village_user_bind');
            $txt_arr = [];
            $user_arr = [];
            foreach ($list as $val) {
                $address = $db_house_village->get_single_floor_room($val['single_id'],$val['floor_id'],$val['layer_id'],$val['vacancy_id'],$val['village_id']);
                $txt = '对应住户信息：';
                if ($val['bindName']) {
                    $txt .= PHP_EOL."姓名[{$val['bindName']}]";
                }
                if ($val['phone']) {
                    $txt .= PHP_EOL."手机[{$val['phone']}]";
                }
                if ($val['uid']) {
                    $txt .= PHP_EOL."平台用户uid[{$val['uid']}]";
                }
                if ($val['bind_id']) {
                    $txt .= PHP_EOL."住户绑定ID[{$val['bind_id']}]";
                }
                if ($address) {
                    $txt .= PHP_EOL."详细地址[{$address}]";
                }
                if ($val['electric_price']) {
                    $txt .= PHP_EOL."住户当前电费【{$val['electric_price']}】元";
                }
                if ($val['money']) {
                    $txt .= PHP_EOL."错误添加电费【{$val['money']}】元";
                }
                $is_top = false;
                if (!isset($user_arr[$val['bind_id']]) && !$user_arr[$val['bind_id']]) {
                    $user_arr[$val['bind_id']] = ['bind_id' =>$val['bind_id'],'is_top' =>$is_top,'txt'=>$txt];
                }
                if (floatval($val['electric_price'])<floatval($val['money'])) {
                    $txt .= PHP_EOL."客户已经缴纳了费用无法还原费用【{$val['money']}】元";
                    $is_top = true;
                    if (isset($user_arr[$val['bind_id']]) && $user_arr[$val['bind_id']]&&$user_arr[$val['bind_id']]['noReductionMoney']) {
                        $user_arr[$val['bind_id']]['noReductionMoney'] += floatval($val['money']);
                    } elseif (isset($user_arr[$val['bind_id']]) && $user_arr[$val['bind_id']]&&!$user_arr[$val['bind_id']]['noReductionMoney']) {
                        $user_arr[$val['bind_id']]['noReductionMoney'] = floatval($val['money']);
                    }
                } else {
//                    $db_house_village_user_bind->where(array('pigcms_id' => $val['bind_id']))->setDec('electric_price', $val['money']);
                    $txt .= PHP_EOL."客户错误费用还原减少电费【{$val['money']}】元";
                    if (isset($user_arr[$val['bind_id']]) && $user_arr[$val['bind_id']]&&$user_arr[$val['bind_id']]['reductionMoney']) {
                        $user_arr[$val['bind_id']]['reductionMoney'] += floatval($val['money']);
                    } elseif (isset($user_arr[$val['bind_id']]) && $user_arr[$val['bind_id']]&&!$user_arr[$val['bind_id']]['reductionMoney']) {
                        $user_arr[$val['bind_id']]['reductionMoney'] = floatval($val['money']);
                    }
                }
                if ($is_top) {
                    array_unshift($txt_arr,['bind_id' =>$val['bind_id'],'is_top' =>$is_top,'txt'=>$txt]);
                } else {
                    $txt_arr[] = ['bind_id' =>$val['bind_id'],'is_top' =>$is_top,'txt'=>$txt];
                }
            }
            file_put_contents($fileName,PHP_EOL.'********************************************下面为整合数据********************************************'.PHP_EOL,FILE_APPEND);
            foreach ($user_arr as $item1) {
                if (isset($item1['noReductionMoney'])) {
                    $item1['txt'] .= PHP_EOL."客户已经缴纳了费用无法还原总费用【{$item1['noReductionMoney']}】元";
                }
                if (isset($item1['reductionMoney'])) {
                    $item1['txt'] .= PHP_EOL."客户错误费用还原减少总电费【{$item1['reductionMoney']}】元";
                }
                if (!$item1['is_top']&&$item1['bind_id']&&$item1['txt']) {
                    $moneys = $db_house_village_user_bind->where(array('pigcms_id' => $item1['bind_id']))->getField('electric_price');;
                    $item1['txt'] .= PHP_EOL."住户操作后电费【{$moneys}】元";
                }
                file_put_contents($fileName,PHP_EOL.$item1['txt'].PHP_EOL,FILE_APPEND);
            }
            file_put_contents($fileName,PHP_EOL.'********************************************下面为具体明细********************************************'.PHP_EOL,FILE_APPEND);
            foreach ($txt_arr as $item) {
                if (!$item['is_top']&&$item['bind_id']&&$item['txt']) {
                    $moneys = $db_house_village_user_bind->where(array('pigcms_id' => $item['bind_id']))->getField('electric_price');;
                    $item['txt'] .= PHP_EOL."住户操作后电费【{$moneys}】元";
                }
                file_put_contents($fileName,PHP_EOL.$item['txt'].PHP_EOL,FILE_APPEND);
            }
        }
        fdump_api(['repairErrImportMeterData:'.__LINE__,$list,$user_arr],'RepairErrImportMeterDataLog');
    }

    public function wordTo16() {
//      --AA55(包头) 16(流水号) 64(地址) 00(业务类型) 27(命令) 0016(长度) 01000100BBB6D3ADB9E2C1D9A3ACCDEE413132333435(内容) B696(校验) AF(结束符)
//      发送 AA5516640027001601000100BBB6D3ADB9E2C1D9A3ACCDEE413132333435B696AF
        $word = '欢迎光临，皖A12345';
        dump('AA5516640027001601000100BBB6D3ADB9E2C1D9A3ACCDEE413132333435B696AF>>>>>>>>>>>>>>>>>>>>>>>>');
        import('@.ORG.park.ParkShowScreen');
        $parkShowScreens = new ParkShowScreen();
        $txtOder = 16;// 流水号
        $string = $parkShowScreens->showVoiceTxt($word,1,$txtOder);
        dump($string);
        $translation = $parkShowScreens->translation($string);
        dump($translation);
    }

    public function pileJx()
    {
        $data = "7e300003132e6da37e440e6f2430020303041e6613849104929722050000011c000000de00000000000000000daa2aab07337f";
        $order_no = $this->getString($data,38,16);
        dump('$order_no>>>>>');
        dump($order_no);
        $port = hexdec($this->getString($data,54,2));
        dump('$port>>>>>');
        dump($port);
        $use_ele = sprintf("%.2f",hexdec($this->getString($data,56,8))/3600000);
        dump('$use_ele>>>>>');
        dump($use_ele);
        $use_time = hexdec($this->getString($data,64,8));
        dump('$use_time>>>>>');
        dump($use_time);
        $max_power = hexdec($this->getString($data,72,8));
        dump('$max_power>>>>>');
        dump($max_power);
        $end_power = hexdec($this->getString($data,80,8));
        dump('$end_power>>>>>');
        dump($end_power);
        $end_time = hexdec($this->getString($data,88,8));
        dump('$end_time>>>>>');
        dump($end_time);
        $end_reason = $this->getString($data,96,2);
        dump('$end_reason>>>>>');
        dump($end_reason);
        if($end_reason == '01')
            $reason = '充电时间/电量用完';
        elseif ($end_reason == '02')
            $reason = '过载';
        elseif ($end_reason == '03')
            $reason = '充满';
        elseif ($end_reason == '04')
            $reason = '终止';
        elseif ($end_reason == '05')
            $reason = '过充';
        elseif ($end_reason == '06')
            $reason = '通道故障';
        elseif ($end_reason == '07')
            $reason = '空载';
        elseif ($end_reason == '08')
            $reason = '退款';
        else
            $reason = '充电时间/电量用完';
        dump('$end_reason>>>>>');
        dump($end_reason);
    }

    public function getString($string='',$begin=0,$length=1)
    {
        return substr($string,$begin,$length);
    }


    public function tessss() {
        $txt = "9779,9802,9853,9854,9861,9864,9870,9877,9878,9881,9882,9901,9903,9904,9906,9907,9908,9909,9910,9911,9912,9913,9915,9917,9918,9920,9922,9923,9924,9925,9927,9929,9931,9936,9949,9950,9953,9954,9955,9956,9957,9962,9963,9964,9966,9968,9976,9979,9980,9982,9983,9986,9988,9989,9991,9995,9996,9997,9998,9999,10002,10005,10007,10008,10011,10014,10015,10024,10025,10026,10027,10028,10030,10044,10054,10055,10059,10062,10063,10074,10076,10079,10080,10081,10084,10086,10090,10091,10092,10093,10094,10095,10096,10097,10098,10100,10106,10107,10108,10112,10114,10116,10117,10118,10120,10121,10123,10124,10126,10127,10128,10129,10131,10133,10135,10136,10137,10139,10140,10142,10145,10147,10149,10151,10153,10154,10155,10156,10158,10160,10161,10164,10165,10166,10167,10168,10169,10171,10174,10176,10177,10178,10179,10181,10184,10185,10186,10192,10193,10194,10195,10196,10197,10198,10199,10201,10202,10203,10204,10205,10206,10207,10208,10209,10210,10211,10212,10214,10215,10216,10217,10218,10220,10221,10222,10223,10226,10227,10228,10229,10230,10231,10232,10233,10234,10235,10236,10237,10238,10247,10249,10250,10251,10252,10253,10254,10255,10258,10259,10262,10263,10265,10266,10267,10268,10269,10270,10271,10272,10273,10274,10277,10282,10283,10284,10285,10286,10287,10288,10290,10292,10293,10294,10295,10296,10297,10298,10299,10301,10303,10306,10308,10311,10312,10313,10315,10316,10317,10318,10319,10320,10321,10324,10325,10326,10327,10329,10331,10332,10333,10334,10335,10336,10337,10338,10341,10342,10343,10345,10346,10347,10348,10349,10350,10351,10352,10353,10354,10355,10356,10357,10358,10359,10360,10362,10363,10366,10369,10372,10373,10374,10376,10377,10378,10379,10380,10381,10382,10383,10384,10385,10386,10387,10388,10389,10391,10393,10394,10396,10398,10400,10401,10402,10403,10404,10405,10410,10413,10414,10415,10417,10418,10419,10420,10421,10424,10425,10426,10427,10428,10429,10430,10431,10432,10434,10435,10436,10437,10438,10439,10442,10443,10444,10447,10451,10452,10453,10454,10456,10457,10458,10460,10461,10462,10463,10464,10465,10466,10467,10468,10469,10470,10471,10472,10474,10475,10476,10478,10479,10480,10482,10484,10485,10487,10488,10489,10490,10491,10492,10493,10494,10495,10496,10499,10501,10502,10503,10504,10505,10509,10510,10511,10512,10513,10514,10515,10517,10518,10519,10526,10527,10530,10532,10533,10535,10536,10537,10539,10541,10543,10545,10546,10548,10549,10550,10551,10552,10554,10555,10556,10557,10558,10559,10560,10561,10563,10566,10567,10570,10571,10572,10573,10574,10575,10576,10577,10578,10584,10589,10590,10593,10594,10595,10596,10599,10601,10602,10604,10605,10606,10607,10608,10609,10610,10612,10614,10615,10618,10620,10621,10622,10623,10626,10628,10634,10636,10637,10638,10639,10640,10641,10642,10643,10644,10645,10647,10648,10649,10650,10651,10656,10657,10659,10665,10666,10668,10669,10670,10671,10672,10673,10675,10677,10678,10679,10680,10681,10683,10685,10687,10688,10689,10691,10692,10694,10695,10696,10698,10700,10701,10702,10704,10705,10706,10707,10708,10709,10710,10712,10713,10714,10717,10731,10746,10748,10755,10763,10813,10814,10815,10816,10817,10819,10821,10825,10837,10839,10840,10847,10853,10855,10874,10883,10899,10901,10906,10909,10910,10911,10913,10914,10918,10922,10923,10925,10933,10955,10958,10959,10962,10963,10964,10965,10966,10968,10969,10970,10974,10976,10977,10978,10980,10981,10982,10983,10985,10988,10994,10996,10997,10998,10999,11000,11002,11004,11008,11009,11010,11011,11015,11017,11022,11023,11025,11026,11027,11028,11031,11037,11038,11040,11043,11044,11045,11047,11048,11049,11050,11054,11057,11060,11061,11063,11064,11065,11067,11068,11071,11072,11073,11074,11075,11077,11081,11083,11084,11085,11087,11088,11089,11090,11094,11095,11096,11097,11098,11099,11100,11103,11105,11106,11107,11108,11109,11110,11111,11114,11116,11117,11119,11120,11122,11123,11124,11125,11127,11129,11130,11131,11132,11133,11134,11135,11136,11137,11138,11139,11140,11142,11143,11146,11151,11154,11157,11158,11159,11160,11161,11162,11163,11165,11166,11168,11171,11172,11173,11175,11177,11178,11179,11180,11182,11183,11188,11190,11191,11193,11194,11196,11197,11200,11201,11204,11207,11210,11212,11218,11219,11220,11221,11222,11223,11226,11227,11234,11236,11237,11238,11240,11274,11278,11279,11280,11281,11284,11285,11287,11288,11289,11290,11291,11293,11296,11297,11298,11304,11307,11308,11309,11310,11312,11313,11316,11317,11318,11319,11323,11325,11327,11330,11333,11334,11339,11340,11343,11345,11346,11347,11348,11350,11351,11352,11353,11354,11355,11358,11359,11363,11367,11368,11369,11370,11371,11372,11373,11374,11375,11376,11378,11379,11393,11394,11396,11399,11400,11401,11404,11407,11408,11410,11414,11415,11416,11424,11430,11431,11442,11445,11454,11465,11466,11469,11470,11472,11474,11475,11477,11478,11479,11482,11484,11486,11488,11492,11493,11496,11497,11525,11526,11527,11528,11529,11533,11534,11536,11537,11538,11544,11547,11548,11549,11551,11553,11554,11555,11562,11588,11597,11602,11627,11670,11672,11674,11689,11690,11694,11702,11706,11709,11710,11717,11731,11733,11751,11755,11756,11766,11767,11768,11769,11771,11772,11776,11779,11780,11781,11785,11789,11790,11791,11795,11809,11816,11817,11818,11821,11822,11823,11824,11827,11831,11833,11839,11844,11846,11853,11856,11861,11862,11865,11867,11869,11871,11874,11875,11882,11883,11885,11907,11909,11916,11918,11920,11921,11922,11923,11924,11927,11928,11929,11931,11933,11934,11935,11939,11943,11944,11948,11949,11950,11953,11955,11957,11958,11967,11971,11976,11977,11981,11982,11983,11985,11986,11987,11988,11992,11993,11999,12002,12006,12011,12022,12032,12034,12035,12036,12039,12040,12057,12092,12093,12094,12095,12099,12150,12152,12194,12252,12265,12266,12267,12268,12269,12270,12280,12285,12290,12299,12300,12301,12302,12304,12305,12307,12309,12311,12314,12327,12328,12337,12338,12339,12341,12376,12378,12379,12380,12382,12442,12443,12463,12489,12545,12553,12554,12632,12672,12697,12719,12724,12731,12732,12812,12837,13052,13053,13100,13102,13103,13105,13113,13123,13142,13159,13168,13261,13308,13309,13336,13347,13353,13361,13362,13363,13412,13427,13522,13538,13540,13543,13545,13546,13553,13571,13582,13584,13585,13586,13587,13607,13613,13614,13615,13618,13619,13620,13621,13704,13706,13729,13740,13768,13769,13801,13803,13843,13902,13927,13969,14072,14103,14167,14202,14235,14236,14261,14266,14267,14268,14319,14320,14330,14360,14363,14368,14369,14402,14403,14410,14411,14441,14443,14444,14445,14456,14470,14475,14476,14477,14478,14482,14493,14494,14504,14597,14630,14633,14635,14663,14666,14668,14676,14700,14701,14702,14703,14706,14715,14716,14717,14740,14741,14743,14752,14756,14766,14770,14793,14795,14796,14797,14798,14814,14828,14832,14847,14849,14851,14863,14868,14869,14880,14885,14889,14890,14894,14910,14927,14928,14932,14951,14952,14953,14955,14963,14969,14970,14977,14978,14979,14980,14992,14993,14994,14995,15000,15008,15011,15012,15014,15041,15119,15121,15126,15131,15154,15156,15159,15161,15179,15200,15201,15208,15210,15211,15234,15252,15253,15254,15256,15265,15266,15271,15272,15280,15284,15285,15294,15295,15296,15297,15300,15305,15312,15313,15314,15315,15321,15323,15324,15348,15355,15357,15360,15365,15381,15394,15408,15423,15460,15461,15463,15467,15469,15476,15482,15489,15495,15537,15546,15550,15551,15555,15558,15559,15560,15561,15785,15786,15866,16253,16254,16345,16372,16378,16382,16383,16441,16472,16487,16565,16593,16643,16651,16662,16666,16667,16726,16727,16753,16754,16755,16762,16768,16853,16859,16860,16881,16887,16931,16983,17033,17034,17035,17036,17037,17038,17083,17112,17113,17114,17116,17125,17160,17161,17162,17163,17180,17208,17213,17223,17233,17240,17245,17356,17397,17398,17441,17464,17495,17521,17543,17546,17552,17582,17588,17589,17597,17621,17622,17623,17636,17646,17660,17706,17715,17721,17724,17770,17779,17780,17781,17783,17788,17812,17827,17828,17829,17844,17845,17846,17881,17895,17903,17978,17986,18044,18047,18123,18141,18144,18146,18147,18148,18149,18160,18316,18317,18326,18349,18397,18436,18452,18535,18551,18575,18647,18715,18720,18731,18757,18782,18783,18784,18795,18796,18883,18884,18885,18918,18941,18982,19003,19068,19077,19106,19154,19198,19219,19220,19221,19222,19231,19253,19254,19275,19293,19364,19835,19850,19857,19880,19881,19937,19938,20022,20062,20094,20095,20101,20102,20104,20195,20210,20219,20287,20289,20290,20291,20293,20295,20454,20458,20468,20513,20615,20694,20742,20743,20751,20752,20753,20754,20755,20759,20761,20762,20763,20775,20783,20787,20788,20794,20795,20796,20797,20798,20803,20805,20807,20809,20820,20821,20848,20860,20885,20886,20889,20895,20926,21014,21025,21026,21027,21028,21029,21030,21031,21038,21048,21055,21057,21058,21059,21060,21116,21118,21159,21161,21162,21163,21245,21259,21261,21267,21273,21274,21283,21286,21298,21305,21307,21312,21324,21331,21338,21339,21340,21342,21347,21368,21369,21378,21380,21383,21384,21396,21408,21409,21410,21411,21426,21427,21437,21455,21468,21470,21471,21472,21473,21474,21477,21483,21489,21492,21493,21494,21495,21496,21497,21498,21499,21500,21501,21503,21505,21506,21507,21510,21512,21518,21521,21533,21534,21536,21542,21545,21546,21547,21553,21555,21556,21562,21565,21568,21575,21579,21580,21582,21583,21584,21586,21587,21588,21595,21596,21597,21599,21601,21602,21611,21618,21620,21621,21627,21628,21637,21638,21639,21642,21643,21644,21647,21648,21649,21650,21652,21653,21654,21664,21665,21666,21667,21668,21669,21670,21671,21672,21673,21674,21675,21676,21680,21686,21688,21689,21690,21691,21692,21694,21695,21696,21697,21705,21715,21725,21739,21744,21748,21750,21759,21762,21768,21769,21780,21784,21787,21797,21799,21800,21801,21806,21808,21812,21819,21820,21822,21824,21828,21837,21838,21849,21855,21859,21871,21872,21881,21884,21885,21886,21887,21889,21890,21904,21905,21909,21920,21939,21992,22003,22023,22053,22054,22055,22074,22075,22076,22077,22101,22105,22113,22130,22131,22154,22155,22156,22164,22165,22166,22173,22174,22175,22176,22177,22182,22196,22197,22198,22199,22217,22222,22223,22224,22225,22226,22228,22229,22230,22236,22238,22243,22245,22246,22247,22295,22299,22300,22331,22344,22351,22361,22378,22379,22380,22381,22382,22387,22398,22403,22405,22408,22412,22413,22416,22424,22427,22428,22429,22430,22431,22435,22436,22437,22438,22439,22440,22441,22442,22443,22444,22445,22446,22447,22448,22450,22453,22455,22456,22458,22461,22465,22466,22467,22468,22469,22476,22477,22478,22479,22481,22482,22483,22484,22492,22493,22494,22495,22496,22497,22498,22499,22501,22502,22503,22505,22506,22509,22510,22513,22527,22528,22537,22538,22540,22541,22545,22553,22555,22556,22557,22558,22559,22562,22565,22566,22584,22590,22594,22596,22602,22603,22605,22606,22607,22608,22609,22610,22611,22612,22614,22615,22616,22620,22621,22623,22626,22630,22631,22633,22634,22635,22636,22637,22640,22641,22643,22644,22645,22646,22647,22648,22649,22650,22651,22652,22653,22654,22656,22657,22658,22661,22662,22663,22664,22666,22667,22668,22669,22670,22672,22674,22675,22677,22678,22680,22682,22683,22684,22685,22686,22689,22690,22691,22693,22694,22695,22696,22697,22698,22702,22704,22705,22706,22707,22713,22714,22715,22716,22718,22726,22727,22729,22730,22731,22732,22738,22741,22742,22748,22750,22751,22752,22753,22754,22755,22757,22759,22760,22779,22784,22785,22787,22788,22791,22792,22793,22794,22795,22799,22801,22806,22807,22811,22813,22814,22815,22821,22822,22824,22825,22826,22833,22847,22856,22864,22865,22866,22867,22868,22877,22878,22879,22882,22883,22884,22885,22888,22890,22897,22904,22905,22908,22912,22913,22918,22920,22921,22929,22935,22936,22937,22944,22951,22952,22953,22961,22962,22963,22967,22975,22976,22986,22987,22988,22989,22993,22999,23000,23001,23009,23010,23015,23016,23017,23026,23027,23030,23034,23035,23036,23041,23053,23058,23060,23061,23063,23064,23068,23075,23076,23077,23078,23079,23081,23085,23090,23091,23092,23098,23105,23111,23112,23115,23136,23137,23139,23140,23155,23156,23180,23181,23182,23183,23184,23189,23191,23198,23199,23200,23201,23202,23203,23213,23214,23215,23216,23223,23224,23238,23239,23241,23242,23246,23255,23265,23275,23288,23289,23290,23291,23300,23302,23304,23307,23308,23309,23310,23311,23321,23323,23325,23329,23333,23334,23337,23338,23346,23347,23348,23350,23351,23352,23353,23396,23400,23418,23421,23425,23438,23439,23440,23449,23459,23483,23484,23497,23500,23501,23510,23511,23519,23520,23523,23528,23533,23536,23537,23538,23539,23540,23541,23549,23551,23562,23563,23564,23565,23566,23567,23568,23569,23570,23571,23579,23580,23587,23588,23589,23600,23601,23604,23607,23626,23632,23641,23642,23644,23645,23663,23666,23667,23668,23671,23672,23673,23678,23680,23685,23691,23703,23737,23763,23799,23800,23801,23802,23810,23811,23840,23853,23855,23856,23860,23888,23893,23896,23899,23919,23928,23979,23983,23990,23991,23993,23994,24010,24013,24014,24015,24016,24017,24018,24019,24020,24021,24028,24029,24032,24033,24034,24055,24056,24063,24067,24068,24069,24070,24075,24093,24118,24176,24223,24235,24240,24260,24281,24286,24288,24298,24302,24303,24312,24318,24330,24358,24386,24428,24433,24463,24470,24478,24491,24493,24494,24513,24515,24522,24523,24524,24525,24538,24550,24556,24557,24563,24564,24566,24573,24579,24663,24708,24710,24711,24712,24758,24759,24769,24772,24778,24786,24798,24799,24803,24805,24840,24855,24865,24866,24874,24886,24887,24991,25073,25074,25098,25105,25197,25205,25212,25249,25268,25271,25333,25339,25362,25369,25370,25371,25372,25379,25406,25489,25496,25501,25510,25519,25568,25569,25570,25571,25579,25581,25589,25597,25612,25613,25649,25659,25679,25680,25689,25690,25693,25701,25730,25733,25742,25750,25754,25759,25785,25815,25816,25844,25878,25882,25891,25905,25906,25924,25925,25926,25927,25928,25971,26018,26019,26025,26026,26031,26032,26033,26034";
        $txtArr = explode(',',$txt);
        dump(count($txtArr));
    }
    public function repairDeviceErrBindAuth() {
        $village_id = I('village_id');
        $device_sn = I('device_sn');
        $is_sys = I('is_sys');
        if ($is_sys) {
            $is_sys = true;
        } else {
            $is_sys = false;
        }
        $info = D('Face_door_a5_service')->repairDeviceErrBindAuth($village_id,$device_sn,$is_sys);
        dump($info);
    }

    public function removeDevices() {
        $groupId = I('groupId');
        $device_sn = I('device_sn');
        $id = I('id');
        $deviceSerials = [$device_sn];
        $ids = [$id];
        $info = D('Face_door_a5_service')->removeDevices($deviceSerials,$groupId,$ids);
        dump($info);
    }

    public function issuedByGroup() {
        $groupId = I('groupId');
        $info = D('Face_door_a5_service')->issuedByGroup($groupId);
        dump($info);
    }


    public function repairGetAuthGroupList() {
        $db_face_bind_about_info = M('Face_bind_about_info');
        $msg1 = D('Face_door_a5_service')->getAuthGroupList(1,100);
        dump('权限组1');
        dump($msg1);
        $msg2 = D('Face_door_a5_service')->getAuthGroupList(2,100);
        dump('权限组2');
        dump($msg2);
        $msg2 = D('Face_door_a5_service')->getAuthGroupList(3,100);
        $val2 = [];
        dump('权限组3');
        dump($msg2);
        $msg2 = D('Face_door_a5_service')->getAuthGroupList(4,100);
        $val2 = [];
        dump('权限组4');
        dump($msg2);
        $msg2 = D('Face_door_a5_service')->getAuthGroupList(5,100);
        $val2 = [];
        dump('权限组5');
        dump($msg2);
        $msg2 = D('Face_door_a5_service')->getAuthGroupList(6,100);
        $val2 = [];
        dump('权限组6');
        dump($msg2);
    }

    public function getAuthGroupEquipmentList() {
        $groupId = I('groupId');
        $devices = D('Face_door_a5_service')->getAuthGroupEquipmentList($groupId,1,100);
        dump('$devices>>>>>>>>>>>>>>');
        dump($devices);
    }

    public function transferToNewGroup() {
        $groupId = I('groupId');
        $persons = D('Face_door_a5_service')->getAuthGroupMemberList($groupId,1,100);
        dump('$persons>>>>>>>>>>>>>>');
        dump($persons);
        $bindGroupInfo = D('Face_bind_about_info')->where(['group_id' => $groupId])->find();
        $otherBindGroup = D('Face_bind_about_info')->where(['group_id' => ['neq',$groupId], 'bind_id' => $bindGroupInfo['bind_id'],'bind_type'=>3])->find();
        if ($otherBindGroup && $otherBindGroup['group_id']) {
            sleep(2);
            $otherPersons = D('Face_door_a5_service')->getAuthGroupMemberList($otherBindGroup['group_id'],1,100);
            dump($otherBindGroup['group_id'].'        $otherPersons>>>>>>>>>>>>>>');
            dump($otherPersons);
        }
        if (isset($_GET['is_del'])&&$_GET['is_del']) {
            sleep(2);
            $delInfo = D('Face_door_a5_service')->delAuthGroup($groupId);
            dump('$delInfo>>>>>>>>>>>>>>');
            dump($delInfo);
            if ($bindGroupInfo && $delInfo['code']==200) {
                $delData = [
                    'bind_type' => 44,
                    'bind_id' => -$bindGroupInfo['bind_id'],
                    'bind_name' => -$bindGroupInfo['bind_name'],
                    'device_type' => -$bindGroupInfo['device_type'],
                    'device_id' => -$bindGroupInfo['device_id'],
                    'group_id' => $bindGroupInfo['group_id'].'_del',
                    'last_time' => time(),
                ];
                D('Face_bind_about_info')->where(['group_id' => $groupId])->save($delData);
                D('Face_user_bind_device')->where(['group_id' => $groupId])->delete();
                D('Face_bind_device')->where(['group_id' => $groupId])->delete();

                if ($otherBindGroup&&isset($otherBindGroup['group_id'])) {
                    sleep(2);
                    $info = D('Face_door_a5_service')->issuedByGroup($otherBindGroup['group_id']);
                    dump('$info>>>>>>>>>>>>>>');
                    dump($info);
                }
            }
        }
    }

    public function authGroupBindDevice() {
        $groupId = I('groupId');
        $device_id = I('device_id');
        $devices = D('Face_door_a5_service')->authGroupBindDevice($groupId,$device_id);
        dump($devices);
    }

    public function remMember() {
        $employeeNoArrs = array (
            22705,22707,22713,22715,22726,22729,22732,22738,22750,22754,22759,22760,22785,22792,22807,22814,22815,22822,22884,22967,23191
        );
        foreach ($employeeNoArrs as $item) {
            sleep(1);
            $persons = D('Face_door_a5_service')->deleteMember($item);
        }
        $groupId = 'e24640cfe82d4ac0aa2968007b973fd6';
        dump($employeeNoArrs);
        $persons = D('Face_door_a5_service')->remMember($employeeNoArrs,$groupId);
        dump($persons);
        $info = D('Face_door_a5_service')->issuedByGroup($groupId);
        dump($info);
    }
    
    public function bindUserToGroup() {
        $group_id = 'd8d7efc6387f4498aadcf8d16160deaa';
        $employeeNos = array (
            0 => '25132',
            1 => '26446',
            2 => '26489',
            3 => '27240',
            4 => '27332',
            5 => '32291',
            6 => '32779',
            7 => '35452',
            8 => '36806',
            9 => '37063',
            10 => '37095',
            11 => '37106',
            12 => '37116',
            13 => '37117',
            14 => '37119',
        );
        $device_sn = 'J60643385';
        $result = D('Face_door_a5_service')->bindUserToGroup($employeeNos, $group_id, $device_sn);
        dump("<-group_id->↓");
        dump($group_id);
        dump("<-result->↓");
        dump($result);
        exit('获取结束');
    }

    public function sqlSearchData() {
        $users = '10019,10445,11035,11320,11530,11545,14174,14731,15034,15229,15292,15540,15570,17647,17648,17649,17700,18545,18929,19233,19888,19973,20545,20573,21293,21414,21428,21723,21829,21831,22292,22337,22376,22395,22578,22708,22717,22775,22898,23250,23401,23779,24143,24149,24150,24361,24422,24423,24431,24439,24448,24474,24502,24511,24545,24561,24562,24673,24674,24818,24906,24934,25206,25207,25213,25312,25314,25448,25502,25507,25512,25528,25692,25737,25738,25739,25809,25932,25996,26196,26197,26202,26215,26226,26295,26296,26300,26338,26342,26343,26427,26477,26478,26491,26594,26595,26629,26638,26640,26660,27078,27092,27119,27128,27129,27130,27162,27163,27167,27169,27173,27174,27180,27185,27186,27187,27199,27231,27234,27237,27239,27240,27254,27257,27258,27259,27263,27265,27299,27300,27313,27314,27315,27316,27317,27318,27326,27532,27852,27853,8629,8633,8634,8639,8648,8748,8850,8851,8852,8853,8854,8855,8857,8867,8869,8873,8874,8875,8876,8923,8935,8938,8939,8940,8941,8942,8973,9001,9462,9820';
        $usersArrs = explode(',',$users);
        $where = [
            'pigcms_id' => ['in', $usersArrs],
            'type' => ['neq', 4]
        ];
        $userss = M('House_village_user_bind')->where($where)->getField('pigcms_id',true);
        fdump($userss,'$userss');
    }


    public function repairUserBindToDevice() {
        $village_id= I('village_id',0);
        $type= I('type',-1);
        $limit= I('limit',-1);
        $where = [
            'status' => 1,
            'face_img_reason' => '人脸图片大于200kb限制'
        ];
        if ($village_id) {
            $where['village_id'] = $village_id;
        }
        if ($type!=-1) {
            $where['type'] = $type;
        }
        $field = 'pigcms_id,uid,village_id,status,type,face_img_status,face_img_reason,name,parent_id,phone';
        if ($limit>0) {
            $list = M('House_village_user_bind')->where($where)->field($field)->limit($limit)->select();
        } else {
            $list = M('House_village_user_bind')->where($where)->field($field)->select();
        }
        fdump_api(['$list' => $list,'$_POST' => $_POST],'houseToDevice/repairUserBindToDevice',1);
        foreach ($list as $item) {
            usleep(80000);//休眠80毫秒
            $info = D('House_face_img')->house_user_face_device_info($item['uid'],$item['pigcms_id']);
            if($info && (!isset($info['err_msg']) || !$info['err_msg'])) {
                if ($item['type'] != 0) {
                    $parent_user = M('House_village_user_bind')->where(['pigcms_id' => $item['parent_id']])->field('uid,pigcms_id')->find();
                } else {
                    $parent_user = $item;
                }
                $str = '业主将' . (($item['type'] == 1) ? '家人' : '租客') . $item['name'] . '的人脸照片同步至门禁设备中';
                D('House_user_trajectory_log')->addLog(4, $item['village_id'], 0, $parent_user['uid'], $parent_user['pigcms_id'], 1, $str, '后台更新了人员数据');
            }
        }
        dump($list);
    }



    public function moveHikOldGroup() {
        $village_id = I('village_id');
        if (! $village_id) {
            exit('不执行');
        }
        $db_house_village_floor = M('House_village_floor');
        $whereFloor = [
            'village_id' => $village_id,
        ];
        $floorIds = $db_house_village_floor->where($whereFloor)->getField('floor_id', true);
        $db_face_bind_about_info = M('Face_bind_about_info');
        $whereAbout = [
            'bind_type' => 3,
            'bind_id' => ['in', $floorIds],
        ];
        $aboutGroupIds = $db_face_bind_about_info->where($whereAbout)->getField('group_id', true);
        dump("<-floorIds->↓");
        dump($floorIds);
        dump("<-aboutGroupIds->↓");
        dump($aboutGroupIds);
        // 删除对应权限组
        $db_face_bind_device = M('Face_bind_device');
        foreach ($aboutGroupIds as $groupId) {
            $delInfo = D('Face_door_a5_service')->delAuthGroup($groupId);
            if (isset($delInfo['code']) && $delInfo['code']) {
                $whereAbout = [
                    'bind_type' => 3,
                    'bind_id' => ['in', $floorIds],
                    'group_id' => $groupId,
                ];
                $info =  $db_face_bind_about_info->where($whereAbout)->find();
                if ($info) {
                    $dataSave = [
                        'bind_type' => 44,
                        'bind_id' => -$info['bind_id'],
                        'group_id' => $groupId . '_del',
                    ];
                    $db_face_bind_about_info->where($whereAbout)->save($dataSave);
                    $db_face_bind_device->where(['group_id' => $groupId])->delete();
                    dump("<-删除对应对象->↓");
                    dump($info);
                } else {
                    dump("<-缺少删除对应对象->↓");
                    dump($groupId);
                }
            }
        }
        exit('处理结束');
    }

    public function getAllGroupIds() {
        $db_face_door_a5_service = D('Face_door_a5_service');
        $msg1 = $db_face_door_a5_service->getAuthGroupList(1,100);
        dump("<-page1->↓");
        dump($msg1);
        $msg2 = $db_face_door_a5_service->getAuthGroupList(2,100);
        dump("<-page2->↓");
        dump($msg2);
        $msg3 = $db_face_door_a5_service->getAuthGroupList(3,100);
        dump("<-page3->↓");
        dump($msg3);
        $msg4 = $db_face_door_a5_service->getAuthGroupList(4,100);
        dump("<-page4->↓");
        dump($msg4);
        $msg5 = $db_face_door_a5_service->getAuthGroupList(5,100);
        dump("<-page5->↓");
        dump($msg5);
        $msg6 = $db_face_door_a5_service->getAuthGroupList(6,100);
        dump("<-page6->↓");
        dump($msg6);
        $msg7 = $db_face_door_a5_service->getAuthGroupList(7,100);
        dump("<-page7->↓");
        dump($msg7);
        exit('获取结束');
    }

    public function getMemberToAuthGroup() {
        $group_id = I('group_id');
        $deviceSerial = I('deviceSerial');
        $id_del = I('id_del');
        $arr = D('House_face_device_log')->getMemberToAuthGroup($group_id, '', $deviceSerial);
        dump("<-arr->↓");
        dump($arr);
        if (intval($id_del) == 4) {
            $delInfo = D('Face_door_a5_service')->delAuthGroup($group_id);
            dump("<-delInfo->↓");
            dump($delInfo);
        }
        exit('获取结束');
    }


    public function delAuthGroup() {
        $group_id = I('group_id');
        $delInfo = D('Face_door_a5_service')->delAuthGroup($group_id);
        dump($delInfo);
    }

    public function getPigcmsProcessSubPlan() {
        $count = M('Process_sub_plan')->count();
        exit($count);
    }

    public function testssss() {
        $num = '6fe62a45-9b83b679';
        dump($this->NumToHex($num,16));
    }
    
    public function NumToHex($num, $len)
    {
        $num_hex = base_convert($num, 10, 16);
        if (strlen($num_hex) != $len) {
            $add = '';
            for ($i = 0; $i < $len - strlen($num_hex); $i++) {
                $add .= '0';
            }
            $num_hex = $add . $num_hex;
        }
        return $num_hex;
    }
 
    
    public function repairUserImg() {
        $userList = M('House_village_user_bind')->where(['uid' => 1, 'phone' => ['neq', '15181718882']])->select();
        $replace = "https://huizhi123-1306019201.cos.ap-chengdu.myqcloud.com";
        $nowTime = time();
        foreach ($userList as $item) {
            // SELECT * FROM `pigcms_user` WHERE `phone` = '13550585705' AND `source` = 'houseautoreg_duopu' ORDER BY `uid` DESC LIMIT 50
            if (isset($item['phone']) && $item['phone']) {
                $user = M('User')->where(['phone' => $item['phone'], 'source' => 'houseautoreg_duopu'])->field('uid, avatar')->find();
            } elseif (isset($item['name']) && $item['name']) {
                $user = M('User')->where(['nickname' => $item['name'], 'source' => 'houseautoreg_duopu'])->order('uid DESC')->field('uid, avatar')->find();
            }
            if (isset($user) && $user['avatar']) {
                $face_img = [
                    'uid'      => $user['uid'],
                    'img_url'  => str_replace($replace, '', $user['avatar']),
                    'add_time' => $nowTime,
                    'status'   => 0,
                    'bind_pigcms_id'=>$item['pigcms_id']
                ];
                $faceId = M('House_face_img')->add($face_img);
                if ($faceId) {
                    $update = M('House_village_user_bind')->where(['pigcms_id' => $item['pigcms_id']])->save(['uid'=>$user['uid']]);
                    if ($update !== false) {
                        D('House_face_img')->house_user_face_device_info($item['pigcms_id'], $user['uid']);
                    }
                }
            }
        }
        dump($userList);
        echo '结束了';
    }
    
    
    public function getThirdImportDataSwitch1() {
        $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
        if (!$third_import_data_switch_judge) {
            return false;
        }
        $village_id = I('village_id');
        $where_village = [
            'village_id' => $village_id
        ];
        $bindList = D('House_village_bind_position')->where($where_village)->select();
        $db_house_village_user_bind =  M('House_village_user_bind');
        $db_house_new_pay_order = M('House_new_pay_order');
        $orderList = [];
        foreach ($bindList as $item) {
            $user_id     = $item['user_id'];
            $position_id = $item['position_id'];
            $userBind = $db_house_village_user_bind->where(['pigcms_id' => $user_id])->field('pigcms_id, vacancy_id, name, phone')->find();
            if (!empty($userBind)) {
                $whereOrderInfo = [
                    'village_id'  => $village_id,
                    'room_id'     => 0,
                    'position_id' => $position_id,
                ];
                $orderList = $db_house_new_pay_order->where($whereOrderInfo)->field('order_id, pigcms_id, name, phone')->select();
                $saveOrder1 = [
                    'room_id'   => $userBind['vacancy_id'],
                ];
                foreach ($orderList as $order) {
                    $saveOrder = $saveOrder1;
                    if (!$order['pigcms_id']) {
                        $saveOrder['pigcms_id'] = $user_id;
                    }
                    if (!$order['name'] && $userBind['name']) {
                        $saveOrder['name'] = $userBind['name'];
                    }
                    if (!$order['phone'] && $userBind['phone']) {
                        $saveOrder['phone'] = $userBind['phone'];
                    }
                    $db_house_new_pay_order->where(['order_id' => $order['order_id']])->save($saveOrder);
                }
                $db_house_new_pay_order_summary = M('House_new_pay_order_summary');
                $whereSummary = [
                    'village_id'  => $village_id,
                    'room_id'     => 0,
                    'position_id' => $position_id,
                ];
                $saveSummary = [
                    'room_id'   => $userBind['vacancy_id'],
                    'pigcms_id' => $user_id,
                ];
                $db_house_new_pay_order_summary->where($whereSummary)->save($saveSummary);
            }
        }
        exit(json_encode($bindList));
    }


    public function parkD3OrderRepair() {
        $village_id = I('village_id', 47);
        $db_house_new_pay_order = M('House_new_pay_order');
        $db_in_park = M('In_park');
        $db_out_park = M('Out_park');
        $db_house_village_car_access_record = M('House_village_car_access_record');
        $where_order = [
            'village_id' => $village_id,
            'is_paid'    => 1,
            'car_type'   => 'temporary_type',
        ];
        $orderList = $db_house_new_pay_order->where($where_order)->field('summary_id, order_id, car_number, total_money')->select();

        $whereInPark = [
            'park_id'      => $village_id,
            'pay_order_id' => ['gt', 0],
            'is_paid'      => ['gt', 0],
        ];
        $inParkArr = $db_in_park->where($whereInPark)->getField('pay_order_id, id,order_id, car_number, pay_order_id', true);
        $inParkWhere = $db_in_park->where($whereInPark)->getField('order_id', true);
        $whereOutPark = [
            'park_id'      => $village_id,
            'order_id'     => ['in', $inParkWhere],
        ];
        $outParkArr = $db_out_park->where($whereOutPark)->getField('order_id, id, car_number, order_id, total', true);

        $whereRecord = [
            'business_type' => 0,
            'business_id'   => $village_id,
            'from_id'       => ['gt', 0],
            'accessType'    => 2,
        ];
        $recordArr = $db_house_village_car_access_record->where($whereRecord)->getField('from_id, record_id,order_id, car_number, total, prepayTotal, accessTime', true);
        $whereInParkRecord = [
            'business_type' => 0,
            'business_id'   => $village_id,
            'from_id'       => ['gt', 0],
            'accessType'    => 1,
        ];
        $recordInParkArr = $db_house_village_car_access_record->where($whereInParkRecord)->getField('from_id,order_id, record_id, car_number, total, prepayTotal, accessTime', true);

        $whereOutParkRecord = [
            'business_type' => 0,
            'business_id'   => $village_id,
            'accessType'    => 2,
            'total'    => ['gt', 0],
        ];
        $recordOrderArr = $db_house_village_car_access_record->where($whereOutParkRecord)->getField('order_id, record_id, accessType, car_number, total, prepayTotal, accessTime', true);


        $payOrder = [];
        $errOrder = [];
        $errOrderNotOut = [];
        $payMoneyTotal = 0;
        $errMoneyTotalNotOut = 0;
        $errMoneyTotal = 0;
        $outMoneyTotal = 0;
        $outMoneyTotal1 = 0;

        $parkRecordMoney = 0;
        foreach ($orderList as $order) {
            $summary_id = $order['summary_id'];
            if ($inParkArr && isset($inParkArr[$summary_id])) {
                $inPark = $inParkArr[$summary_id];
                $order['inPark'] = $inPark;
                $park_order_id = isset($inParkArr[$summary_id]['order_id']) ? $inParkArr[$summary_id]['order_id'] : '';
                if ($outParkArr && isset($outParkArr[$park_order_id])) {
                    $outPark = $outParkArr[$park_order_id];
                    $total1       = isset($recordArr[$outPark['id']]['total'])       && $recordArr[$outPark['id']]['total']       ? $recordArr[$outPark['id']]['total']       : 0;
                    $prepayTotal1 = isset($recordArr[$outPark['id']]['prepayTotal']) && $recordArr[$outPark['id']]['prepayTotal'] ? $recordArr[$outPark['id']]['prepayTotal'] : 0;
                    unset($recordOrderArr[$recordArr[$outPark['id']]['order_id']]);
                    $outMoneyTotal1 += $total1;
                    $outMoneyTotal1 += $prepayTotal1;
                    $parkRecordMoney += $total1;
                    $parkRecordMoney += $prepayTotal1;
                    $outPark['total1']       = $total1;
                    $outPark['prepayTotal1'] = $prepayTotal1;
                    $outPark['repair']       = ($outPark['total'] != $outPark['total1']) ? true : false;
                    $order['outPark'] = $outPark;
                    $outMoneyTotal += $outPark['total'];
                    $payOrder[] = $order;
                    $payMoneyTotal += $order['total_money'];
                } else {
                    $record = isset($recordInParkArr[$inPark['id']])       && $recordInParkArr[$inPark['id']]       ? $recordInParkArr[$inPark['id']]       : [];
                    if (!empty($record)) {
                        $outParkRecord = isset($recordOrderArr[$record['order_id']]) && $recordOrderArr[$record['order_id']] ? $recordOrderArr[$record['order_id']] : [];
                        $order['outParkRecord'] = $outParkRecord;
                        $total1       = isset($outParkRecord['total'])       && $outParkRecord['total']       ? $outParkRecord['total']       : 0;
                        $prepayTotal1 = isset($outParkRecord['prepayTotal']) && $outParkRecord['prepayTotal'] ? $outParkRecord['prepayTotal'] : 0;
                        $outMoneyTotal1 += $total1;
                        $outMoneyTotal1 += $prepayTotal1;
                        $parkRecordMoney += $total1;
                        $parkRecordMoney += $prepayTotal1;
                        $payOrder[] = $order;
                        $payMoneyTotal += $order['total_money'];
                        unset($recordOrderArr[$record['order_id']]);
                    } else {
                        $errOrderNotOut[] = $order;
                        $errMoneyTotalNotOut += $order['total_money'];
                    }
                }
            } else {
                $errOrder[] = $order;
                $errMoneyTotal += $order['total_money'];
            }
        }
        $recordOrderArr = array_values($recordOrderArr);
        fdump_api([
            'payOrder' => $payOrder,
            'errOrder' => $errOrder,
            'errOrderNotOut' => $errOrderNotOut,
            '$recordOrderArr' => $recordOrderArr,
            'payMoneyTotal' => $payMoneyTotal,
            'errMoneyTotalNotOut' => $errMoneyTotalNotOut,
            'errMoneyTotal' => $errMoneyTotal,
            'outMoneyTotal' => $outMoneyTotal,
            'parkRecordMoney' => $parkRecordMoney,
        ], '$parkD3OrderRepair');
        return [
            'payOrder' => $payOrder,
            'errOrder' => $errOrder,
        ];
    }
    
    public function repairFaceImg() {
        $page = I('page', 1);
        $db_house_face_img = M('House_face_img');
        $imgList = $db_house_face_img
            ->where(array('status' => ['in', [0,3]], 'img_url' => array('neq', '')))
            ->page($page, 5000)
            ->order('id DESC')
            ->field('id , img_url, uid, status')
            ->select();
        $replaceArr = [];
        if ($imgList) {
            $nowTime = time();
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            foreach ($imgList as $val) {
                if (3 == $val['status']) {
                    // 加密的图片解密
                    $img_url = $word_encryption->text_decrypt($val['img_url']);
                } else {
                    $img_url = $val['img_url'];
                }
                if (strstr($img_url, 'thumbImg/1920_1080/') !== false) {
                    $newImg = str_replace('thumbImg/1920_1080/', '', $img_url);
                    $saveData = [
                        'last_time' => $nowTime,
                    ];
                    if (3 == $val['status']) {
                        $save_img_url = $word_encryption->text_encryption($newImg);
                    } else {
                        $save_img_url = $newImg;
                    }
                    $saveData['img_url'] = $save_img_url;
                    $replaceArr[] = [
                        'org' => $img_url,
                        'newImg' => $newImg,
                        'id' => $val['id'],
                        'uid' => $val['uid'],
                        'img_url' => $val['img_url'],
                        'saveData' => $saveData,
                    ];
                    $db_house_face_img->where(['id' => $val['id']])->save($saveData);
                }
            }
        }
        dump($replaceArr);
        fdump_api($replaceArr, 'image'.$page, 1);
        $hrefUrl = "https://hz.huizhisq.com/appapi.php?c=Comm_group_expansion&a=repairFaceImg&page=" . ($page+1);
        echo '当前页面已跑完，正在跳转下一页<script>location.href="'.$hrefUrl.'";</script>';
    }
    
    public function repairYasuoImg() {
        $path = './upload/qrcode';
        $data = $this->$this->scanFile($path);
        dump($data);
    }

    public function scanFile($path) {
        $result = [];
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($path . '/' . $file)) {
                    $this->scanFile($path . '/' . $file);
                } else {
                    $result[] = basename($file);
                }
            }
        }
        return $result;
    }
    
    public function moveImgs() {
        $page = I('page', 1);
        $db_wisdom_qrcode_record_log = M('Wisdom_qrcode_record_log');
        $where = [
            'village_id' => ['in', [35,36,37,38,39,40,41,42,43,44,45,46,50,55]],
            'entication_field' => ['like', "'%upload%'"],
        ];
        $logList = $db_wisdom_qrcode_record_log->where($where)->page($page, 100)->select();
        $imgData = [];
        foreach ($logList as $log) {
            $entication_field_arr = $log['entication_field'] ? unserialize($log['entication_field']) : [];
            foreach ($entication_field_arr as $item) {
                $record_cate_field_config = isset($item['record_cate_field_config']) ? $item['record_cate_field_config'] : [];
                foreach ($record_cate_field_config as $val) {
                    if (intval($val['type']) === 2 && is_array($val['value'])) {
                        foreach ($val['value'] as $img) {
                            $imgData[] = $img;
                        }
                    }
                }
            }
        }
        dump($logList);
        dump($imgData);
//        echo '当前页面已跑完，正在跳转下一页<script>location.reload();</script>';
    }
}
?>
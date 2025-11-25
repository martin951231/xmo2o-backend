<?php
class BbsModel extends Model
{
	public function	get_category_list($where,$field = '*',$order = 'cat_order desc,cat_id desc'){
		if(empty($where)){
			return	false;
		}
		$bbs_category	=	M('Bbs_category')->where($where)->field($field)->order($order)->select();
		if($bbs_category){
            foreach ($bbs_category as $bkk=>$bvv){
                if (strstr($bvv['cat_logo'],'upload')) {
                    $bbs_category[$bkk]['cat_logo'] = replace_file_domain($bvv['cat_logo']);
                }else{
                    $cat_logo = C('config.site_url').$bvv['cat_logo'];
                    $bbs_category[$bkk]['cat_logo'] = str_replace('//tpl','/tpl',$cat_logo);
                }
            }
		}
        return $bbs_category;
	}
	//	查询分类列表(带分页)
	public function bbs_category_page_list($where , $field = true , $order = 'cat_order desc,cat_id desc',$pageSize = 20){
        if(!$where){
            return false;
        }
        import('@.ORG.merchant_page');
        $count = M('Bbs_category')->where($where)->count();
        $p = new Page($count,$pageSize,'page');
        $village_express_list = M('Bbs_category')->field($field)->where($where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach ($village_express_list as &$value) {
        	if (strstr($value['cat_logo'],'upload')) {
        		$value['cat_logo'] = replace_file_domain($value['cat_logo']);
        	}else{
                $value['cat_logo'] = C('config.site_url').$value['cat_logo'];
                $value['cat_logo'] = str_replace('//tpl','/tpl',$value['cat_logo']);
            }
        	$value['cat_aricle_total'] = M('Bbs_aricle')->where(array('cat_id'=>$value['cat_id']))->count();
        }
        $list['list'] = $village_express_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }
    //	查询文章列表(带分页)
	public function bbs_aricle_page_list($where , $field = true , $order = 'a.aricle_id desc',$pageSize = 20,$village_id=0){
        if(!$where){
            return false;
        }
        if ($where['cat_id']) {
    		$condition_where = " a.cat_id=".$where['cat_id'];
        }

        if ($where['aricle_status']) {
    		$condition_where = " a.aricle_status=".$where['aricle_status'];
        }

    	$field = '`b`.`type` AS ban_type ,b.status as ban_status, b.days as ban_days, b.time as ban_time,a.*';
    	$left_join = "as a left join ".C('DB_PREFIX').'bbs_ban_list as b on `a`.`uid` = `b`.`uid` left join '.C('DB_PREFIX').'bbs_category as c on `a`.`cat_id` = `c`.`cat_id` left join '.C('DB_PREFIX').'bbs as bbs on `bbs`.`bbs_id` = `c`.`bbs_id`';
    	
    	$where['village_id'] && $condition_where .= ' AND `bbs`.`third_id`='.$where['village_id'];
    	$order = 'a.aricle_sort DESC, `a`.`aricle_id` DESC';
        import('@.ORG.merchant_page');
        $count = M('Bbs_aricle')->join($left_join)->where($condition_where)->count();
        // var_dump(M('Bbs_aricle')->_sql());
        $p = new Page($count,$pageSize,'page');
        $village_express_list = M('Bbs_aricle')->field($field)->join($left_join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $list['list'] = $village_express_list;
        $list['pagebar'] = $p->show();
        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }
    //	查询评论列表(带分页)
	public function bbs_comment_page_list($where , $field = true , $order = 'b.comment_id desc',$pageSize = 20){
        if(!$where){
            return false;
        }
        
        if(!$where){
            return false;
        }
        if ($where['aricle_id']) {
    		$condition_where = " b.aricle_id=".$where['aricle_id'];
        }

        if ($where['comment_status']) {
    		$condition_where = " b.comment_status=".$where['comment_status'];
        }

    	$field = '`b`.*  ,u.nickname';
    	$left_join = "as b left join ".C('DB_PREFIX').'user as u on `b`.`uid` = `u`.`uid`';
        $count = M('Bbs_comment')->join($left_join)->where($condition_where)->count();
        $p = new Page($count,$pageSize,'page');
        $village_express_list = M('Bbs_comment')->field($field)->join($left_join)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $list['list'] = $village_express_list;
        $list['pagebar'] = $p->show();

        if($list){
            return array('status'=>1,'list'=>$list);
        }else{
            return array('status'=>0,'list'=>$list);
        }
    }
	/*
	*	从这里开始是web接口
	*
	*/
	//	获取小区和论坛关系
	public function isVillage($village_id=0,$group_name='house'){
		if($group_name	==	'house'){
			$aHouseVillage['aHouseVillage'] = M('House_village')->where(array('village_id'=>$village_id))->find();
		}
		unset($aHouseVillage['pwd']);
		if(empty($aHouseVillage)){
			return	array('status'=>0,'list'=>L_('该X1不存在！',C('config.house_name')));
		}else{
			$aBbs	=	M('Bbs')->field(array('bbs_id,index_icon,index_name,third_type','auto_verify_post','auto_verify_reply'))->where(array('third_id'=>$village_id,'third_type'=>$group_name))->find();
		}
		return	$aBbs;
	}
	//	查询论坛分类
	public function indexType($aBbs=0,$village_id=0,$limit='20',$cat_id=''){
		if(empty($aBbs)){
			return	array('status'=>0,'list'=>L_('该X1邻里没有分类标签',C('config.house_name')));
		}else{
            $categoryWhere= array('bbs_id'=>$aBbs,'cat_status'=>1);
            if($cat_id){
                $categoryWhere['cat_id']=$cat_id;
            }
			$indexType	=	M('Bbs_category')->order('cat_order desc,last_time desc')->limit($limit)->where($categoryWhere)->select();
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'myPosts',
                'isAll' => true,
                'urlParam' => 'village_id='.$village_id,
            ];
            $myPostsUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
			foreach($indexType as $k=>$v){
				$indexType[$k]['url']	=	'/wap.php?g=Wap&c=Bbs&a=web_bbs_aricle&cat_id='.$v['cat_id'].'&village_id='.$village_id;
                $indexType[$k]['my_posts_url'] = $myPostsUrl;
			}
		}
		return	$indexType;
	}
	//	最新文章列表
	public function newBbsAricle($aBbs=0,$page=0,$limit=10,$id='bbs_id'){
		$aBbsAricle	=	M('Bbs_aricle')->order('aricle_sort desc,update_time desc')->page($page,$limit)->where(array($id=>$aBbs,'aricle_status'=>1))->select();
		if(empty($aBbsAricle)){
			return	false;
		}else{
			foreach($aBbsAricle as $k=>$v){
				//	查找发布文章的用户
				$aBbsAricle[$k]['uid']	=	D('User')->field(array('uid','nickname','last_time','avatar'))->where(array('uid'=>$v['uid']))->find();
				//	查找文章所属分类
				$aBbsAricle[$k]['cat_id']	=	D('Bbs_category')->field(array('cat_id','cat_name'))->where(array('cat_id'=>$v['cat_id']))->find();

			}
		}
		return	$aBbsAricle;
	}

	//	最新文章列表社区3.0
	public function newBbsAricle_list($aBbs=0,$page=0,$limit=10,$id='bbs_id'){
		$aBbsAricle	=	M('Bbs_aricle')->order('aricle_sort desc,update_time desc')->page($page,$limit)->where(array($id=>$aBbs,'aricle_status'=>1))->select();
		if(empty($aBbsAricle)){
			return	false;
		}else{
			foreach($aBbsAricle as $k=>$v){
				//	查找发布文章的用户
				$aBbsAricle[$k]['uid']	=	D('User')->field(array('uid','nickname','last_time','avatar'))->where(array('uid'=>$v['uid']))->find();
				//	查找文章所属分类
				$aBbsAricle[$k]['cat_id']	=	D('Bbs_category')->field(array('cat_id','cat_name'))->where(array('cat_id'=>$v['cat_id']))->find();

			}
		}
		return	$aBbsAricle;
	}

	//	新增文章
	public function newBbsAricleWite($aAricleData=array(),$img=array()){
		if(empty($aAricleData)){
			return	false;
		}else{
			if($img[0]){
				$aricleAdd	=	M('Bbs_aricle')->add($aAricleData);
				if($aricleAdd){
					foreach($img as $v){
						$aricleImg	=	array(
							'aricle_id'	=>	$aricleAdd,
							'aricle_img'	=>	$v,
						);
						$aricleImgAdd	=	M('Bbs_aricle_img')->add($aricleImg);
						if(empty($aricleImgAdd)){
							return	false;
						}
					}
				}else{
					return	false;
				}
			}else{
				$aricleAdd	=	M('Bbs_aricle')->add($aAricleData);
				if(empty($aricleAdd)){
					return	false;
				}
			}
		}
		return	true;
	}
	//	文章详情
	public function bbsAricleDetails($aricele_id=0){
		$aBbsAricleDetails	=	M('Bbs_aricle')->where(array('aricle_id'=>$aricele_id))->find();
		$aBbsAricleDetails['uid']	=	D('User')->field(array('uid','nickname','last_time','avatar'))->where(array('uid'=>$aBbsAricleDetails['uid']))->find();
		if(empty($aBbsAricleDetails)){
			return	array('status'=>0,'list'=>L_('文章没有详情'));
		}else{
			$aBbsAricleetailsImg	=	D('Bbs_aricle_img')->where(array('aricle_id'=>$aricele_id))->select();
			if(empty($aBbsAricleetailsImg)){
				$aBbsAricleDetails['img']	=	array();
				return	$aBbsAricleDetails;
			}else{
				$aBbsAricleDetails['img']	=	$aBbsAricleetailsImg;

				$bbs_praise_info = D('Bbs_praise')->where(array('aricle_id'=>$aricele_id,'uid'=>$_SESSION['user']['uid']))->find();
				if($bbs_praise_info){
					$aBbsAricleDetails['is_bbs_praise']= true;
				}else{
					$aBbsAricleDetails['is_bbs_praise']= false;
				}

				return	$aBbsAricleDetails;
			}
		}
	}

	//	文章详情
	public function bbsAricleDetailsNew($aricele_id=0){
		$aBbsAricleDetails	=	M('Bbs_aricle')->where(array('aricle_id'=>$aricele_id))->find();
		$user	=	D('User')->field(array('uid','nickname','last_time','avatar'))->where(array('uid'=>$aBbsAricleDetails['uid']))->find();
		if($user['avatar']){
			$user['avatar'] = replace_file_domain($user['avatar']);
		}else{
			$user['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
		}
		$aBbsAricleDetails['user'] = $user;
		if(empty($aBbsAricleDetails)){
			return	array('status'=>0,'list'=>[]);
		}else{
			$aBbsAricleetailsImg	=	D('Bbs_aricle_img')->where(array('aricle_id'=>$aricele_id))->select();
			if(empty($aBbsAricleetailsImg)){
				$aBbsAricleDetails['img']	=	array();
				return	$aBbsAricleDetails;
			}else{
				$aBbsAricleDetails['img']	=	$aBbsAricleetailsImg;

				$bbs_praise_info = D('Bbs_praise')->where(array('aricle_id'=>$aricele_id,'uid'=>$_SESSION['user']['uid']))->find();
				if($bbs_praise_info){
					$aBbsAricleDetails['is_bbs_praise']= true;
				}else{
					$aBbsAricleDetails['is_bbs_praise']= false;
				}

				return	$aBbsAricleDetails;
			}
		}
	}
	//	文章评论
	public function bbsAricleComment($aricele_id=0,$page=0,$limit=10){
		$where = array('aricle_id'=>$aricele_id,'comment_status'=>1);
//		$aBbscomment	=	M('Bbs_comment')->order('comment_fid asc,create_time asc')->page($page,$limit)->where($where)->select();
		$aBbscomment	=	M('Bbs_comment')->order('create_time asc')->page($page,$limit)->where($where)->select();
		if($aBbscomment && is_array($aBbscomment)){
			foreach($aBbscomment as $k=>$v){
				$aBbscomment[$k]['uid']	=	D('User')->field(array('uid','nickname','last_time','avatar'))->where(array('uid'=>$v['uid']))->find();
			}
			return	array('status'=>1,'list'=>$aBbscomment);
		}else{
			return	array('status'=>0,'list'=>[]);
		}
	}

	//	文章评论
	public function bbsAricleCommentNew($aricele_id=0,$page=0,$limit=10){
		$where = array('aricle_id'=>$aricele_id,'comment_status'=>1);
		$aBbscomment	=	M('Bbs_comment')->order('comment_fid asc,create_time asc')->page($page,$limit)->where($where)->select();
//		$aBbscomment	=	M('Bbs_comment')->order('comment_id asc')->page($page,$limit)->where($where)->select();

		if($aBbscomment && is_array($aBbscomment)){
			foreach($aBbscomment as $k=>$v){
				$aBbscomment[$k]['uid']	=	D('User')->field(array('uid','nickname','last_time','avatar'))->where(array('uid'=>$v['uid']))->find();
			}
			return	$aBbscomment;
		}else{
			return	[];
		}
	}

	//	文章写评论
	public function bbsAricleCommentWite($wite=array()){
		if(empty($wite)){
			return	false;
		}
		if(empty($wite['comment_fid'])){
			$aCommentWite	=	M('Bbs_comment')->add($wite);
		}else{
			$aCommentFind	=	M('Bbs_comment')->where(array('comment_id'=>$wite['comment_fid'],'comment_status'=>1))->find();
			if(empty($aCommentFind)){
				return	false;
			}else{
				$aCommentWite	=	M('Bbs_comment')->add($wite);
			}
		}
		if(empty($aCommentWite)){
			return	false;
		}else{
			M('Bbs_aricle')->where(array('aricle_id'=>$wite['aricle_id']))->setInc('aricle_comment_total');
			if($wite['comment_status'] == 1){
				M('Bbs_aricle')->where(array('aricle_id'=>$wite['aricle_id']))->setInc('aricle_comment_num');
			}
			$arr	=	array(
				'comment_id'	=>	$aCommentWite,
				'aricle_id'		=>	$wite['aricle_id'],
				'create_time'	=>	time()
			);
			M('Bbs_log')->add($arr);
			return	true;
		}
	}
	//	对文章进行赞
	public function bbsAricleZan($aricle_id,$uid){
		$arr	=	array(
			'aricle_id'	=>	$aricle_id,
			'uid'	=>	$uid,
			//'create_time'	=>	time()
		);

		$info = M('Bbs_praise')->where($arr)->find();
		if($info){
			return false;
		}

		$arr['create_time']	=	time();
		$add	=	M('Bbs_praise')->add($arr);
		if($add){
			$aricle_praise_num	=	M('Bbs_aricle')->where(array('aricle_id'=>$aricle_id))->setInc('aricle_praise_num');
			return	$aricle_praise_num;
		}else{
			return	false;
		}
	}
	//	获取文章的赞
	public function bbsAricleGetZan($aricle_id,$page=1,$limit=10){
		$aricle_praise	=	M('Bbs_praise')->order('create_time desc')->page($page,$limit)->where(array('aricle_id'=>$aricle_id))->select();
		foreach($aricle_praise as $k=>$v){//replace_file_domain
			$user	=	M('User')->field(array('uid','nickname','avatar','last_time'))->where(array('uid'=>$v['uid']))->find();
			if($user['avatar']){
				$user['avatar'] = replace_file_domain($user['avatar']);
			}else{
				$user['avatar'] = C('config.site_url') . '/static/images/user_avatar.jpg';
			}
			$aricle_praise[$k]['uid'] = $user;
		}
		if($aricle_praise){
			return	$aricle_praise;
		}else{
			return	false;
		}
	}

	//	对文章进行赞
	public function NewbbsAricleZan($aricle_id,$uid){
		$arr	=	array(
			'aricle_id'	=>	$aricle_id,
			'uid'	=>	$uid,
			//'create_time'	=>	time()
		);

		$info = M('Bbs_praise')->where($arr)->find();
		if($info){
			return false;
		}

		$arr['create_time']	=	time();
		$add	=	M('Bbs_praise')->add($arr);
		if($add){
			$aricle_praise_num	=	M('Bbs_aricle')->where(array('aricle_id'=>$aricle_id))->setInc('aricle_praise_num');
			return	$aricle_praise_num;
		}else{
			return	false;
		}
	}

	//	对文章进行取消赞
	public function NewbbsAricleZanCancel($aricle_id,$uid){
		$arr	=	array(
			'aricle_id'	=>	$aricle_id,
			'uid'	=>	$uid,
		);
		$model	=	M('Bbs_praise')->where($arr)->delete();
		if($model){
			$aricle_praise_num	=	M('Bbs_aricle')->where(array('aricle_id'=>$aricle_id))->setDec('aricle_praise_num');
			return	$aricle_praise_num;
		}else{
			return	false;
		}
	}
	//	获取文章的本人赞
	public function bbsAricleGetMeZan($aricle_id,$uid){
		$aricle_praise	=	M('Bbs_praise')->where(array('aricle_id'=>$aricle_id,'uid'=>$uid))->find();
		if($aricle_praise){
			return	true;
		}else{
			return	false;
		}
	}
	//	获取热门帖子
	public function bbsHotAricle($third_type,$third_id,$limit=4){
		$hot	=	M('Bbs')->where(array('third_type'=>$third_type,'third_id'=>$third_id))->find();
		if(empty($hot)){
			return	false;
		}else{
			$hot['index_icon']	=	C('config.site_url').$hot['index_icon'];
		}
		$where	=	array(
			'bbs_id'	=>	$hot['bbs_id'],
			'aricle_img'	=>	array('exp','is not null'),
			'aricle_status'=>1
		);
		$hotAricle	=	M('Bbs_aricle')->order('aricle_sort desc')->limit($limit)->where($where)->select();
		foreach($hotAricle as $k=>$v){
			$hotAricle[$k]['aricle_img']	=	C('config.site_url').$v['aricle_img'];
			$hotAricle[$k]['url']	=	C('config.site_url').'/wap.php?g=Wap&c=Bbs&a=web_bbs_aricele_details&status=3&aricle_id='.$v['aricle_id'].'&village_id='.$hot['third_id'].'&cat_id='.$v['cat_id'];
		}
		if($hotAricle){
			$arr	=	array(
				'bbs_url'	=>	C('config.site_url').'/wap.php?g=Wap&c=Bbs&a=web_index&village_id='.$third_id,
				'third'		=>	$hot,
				'aricle'	=>	$hotAricle,
			);
			return	$arr;
		}else{
			return	false;
		}
	}
	//	删除文章
	public function bbsAricleDelete($aricle_id){
		if(empty($aricle_id)){
			return	false;
		}else{
			$cat_id	=	M('Bbs_aricle')->field('cat_id')->where(array('aricle_id'=>$aricle_id))->find();
			if($cat_id){
				$sSave	=	M('Bbs_aricle')->where(array('aricle_id'=>$aricle_id))->save(array('aricle_status'=>4));
			}
		}
		if(empty($sSave)){
			return	false;
		}else{
			$setDec	=	M('Bbs_category')->where(array('cat_id'=>$cat_id['cat_id']))->setDec('cat_aricle_num');
		}
		if($setDec){
			return	true;
		}else{
			return	false;
		}
	}
	//	删除评论和评论数量
	public function bbsCommentDelete($comment_id){
		$aComment	=	M('Bbs_comment')->where(array('comment_id'=>$comment_id))->find();
		if(empty($aComment)){
			return false;
		}else{
			$commentSave	=	M('Bbs_comment')->where(array('comment_id'=>$comment_id))->data(array('comment_status'=>4))->save();
		}
		if(empty($commentSave)){
			return	false;
		}else{
			M('Bbs_aricle')->where(array('aricle_id'=>$aComment['aricle_id']))->setDec('aricle_comment_num');
			return	true;
		}
	}
}

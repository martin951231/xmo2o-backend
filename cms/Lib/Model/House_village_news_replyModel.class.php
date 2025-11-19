<?php
class House_village_news_replyModel extends Model{
	
	public function getlist($column,$pagesize=20){
		if(!$column['village_id']){
			return '';
		}
		$condition_table  = array(C('DB_PREFIX').'house_village_news'=>'n',C('DB_PREFIX').'house_village_news_category'=>'c',C('DB_PREFIX').'house_village_news_reply'=>'r',C('DB_PREFIX').'user'=>'u');
		$condition_where = " n.village_id = r.village_id  AND n.village_id = c.village_id  AND n.news_id = r.news_id  AND r.uid = u.uid AND  n.cat_id = c.cat_id AND c.cat_status=1 AND n.type=1 AND n.village_id=".$column['village_id'];
		if($column['status']){
			$condition_where .= " AND n.status = ".intval($column['status']);
		}
		$condition_field = 'n.title,c.cat_name,r.*,u.nickname,u.avatar';
		
		$order = ' r.is_read ASC,r.pigcms_id DESC ';
		import('@.ORG.merchant_page');
		$count_news = D('')->table($condition_table)->where($condition_where)->count();
		$p = new Page($count_news,$pagesize,'page');
		$reply_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        foreach($reply_list as &$val) {
            $val['content'] = strip_tags(htmlspecialchars_decode($val['content']));
            if (isset($val['images']) && $val['images']) {
                $images = json_decode($val['images'],true);
                $val['images'] = [];
                foreach ($images as $image) {
                    $val['images'][] =  replace_file_domain($image);
                }
            }
        }

        $return['count_reply'] = $count_news;
		$return['pagebar'] = $p->show();
		$return['reply_list'] = $reply_list;
		
		return $return;
	}

	//todo 平台新闻评论列表
    public function get_platform_list($pagesize=20){
        $where=array(
            'n.type'=>0
        );
        $count_village =M('House_village_news_reply as r')
            ->join(C('DB_PREFIX').'house_village_news as n on n.news_id = r.news_id')
            ->join(C('DB_PREFIX').'user as u on u.uid = r.uid')
            ->where($where)->count();

        import('@.ORG.system_page');
        $p = new Page($count_village, $pagesize);
        $list = M('House_village_news_reply as r')
            ->join(C('DB_PREFIX').'house_village_news as n on n.news_id = r.news_id')
            ->join(C('DB_PREFIX').'user as u on u.uid = r.uid')
            ->where($where)
            ->field('n.title,r.*,u.nickname,u.avatar')
            ->order('r.is_read ASC,r.pigcms_id DESC')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        return ['list'=>$list,'page'=>$p->show()];
    }


    public function get_ajax_list($column,$list_num=0,$amount=5){
        if(!$column['village_id']){
            return '';
        }
        if(!$column['news_id']){
            return '';
        }

        $condition_table  = array(C('DB_PREFIX').'house_village_news'=>'n',C('DB_PREFIX').'house_village_news_category'=>'c',C('DB_PREFIX').'house_village_news_reply'=>'r',C('DB_PREFIX').'user'=>'u');
        $condition_where = " n.village_id = r.village_id  AND n.village_id = c.village_id  AND n.news_id = r.news_id  AND r.uid = u.uid AND  n.cat_id = c.cat_id AND c.cat_status=1  AND n.village_id=".$column['village_id']." AND r.news_id=".$column['news_id'];
        if($column['status']){
            $condition_where .= " AND n.status = ".intval($column['status'])." AND r.status = ".intval($column['status']);

        }
        $condition_field = 'n.title,c.cat_name,r.*,u.nickname,u.avatar';

        $order = 'r.pigcms_id DESC,r.add_time DESC ';

        $count_news = D('')->table($condition_table)->where($condition_where)->count();
        $reply_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($list_num.','.$amount)->select();
        if(empty($reply_list))
        {
            return 'no_more';
        }else{

            foreach($reply_list as $key => $val){
                $reply_list[$key]['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }

        $return['count_reply'] = $count_news;
        $return['reply_list'] = $reply_list;
        return $return;
    }
	 
}
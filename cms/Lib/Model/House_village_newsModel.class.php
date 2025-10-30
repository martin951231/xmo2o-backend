<?php
class House_village_newsModel extends Model{

    protected $tableName = 'house_village_news';

    public function getlist($column,$type=1){
        if($type == 1){
            if(!$column['village_id']){
                return '';
            }
            $condition_table  = array(C('DB_PREFIX').'house_village_news'=>'n',C('DB_PREFIX').'house_village_news_category'=>'c',C('DB_PREFIX').'house_village'=>'v');
            $condition_where = " n.village_id = v.village_id AND n.type = 1 AND n.village_id = c.village_id AND  n.cat_id = c.cat_id AND c.cat_status=1  AND n.village_id=".$column['village_id'];
            if($column['status']){
                $condition_where .= " AND n.status = ".intval($column['status']);
            }
            $condition_field = 'n.*,c.cat_name';

            $order = ' n.is_hot DESC,n.news_id DESC ';
        }else{
            /**
             * todo 新增平台发布新闻 参数判断
             */
            $condition_table  = array(C('DB_PREFIX').'house_village_news'=>'n');
            $condition_where = 'n.type = 0';
            $condition_field = 'n.*';
            $order = 'n.news_id DESC ';
        }

        if($type == 1){
            import('@.ORG.merchant_page');
            $count_news = D('')->table($condition_table)->where($condition_where)->count();
            $p = new Page($count_news,20,'page');
        }else{
            import('@.ORG.system_page');
            $count_news = D('')->table($condition_table)->where($condition_where)->count();
            $p = new Page($count_news, 20);
        }

        $village_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        $return['pagebar'] = $p->show();
        $return['news_list'] = $village_list;

        return $return;
    }

    /*得到小区的新闻列表*/
    public function get_limit_list($village_id,$limit){
        $condition_table = array(C('DB_PREFIX').'house_village_news'=>'n',C('DB_PREFIX').'house_village_news_category'=>'c');
        $condition_where = 'n.village_id='.$village_id.' AND n.status=1 AND n.village_id = c.village_id AND  n.cat_id = c.cat_id AND c.cat_status=1';
        $order = '`is_hot` DESC,`add_time` DESC';
        return D('')->table($condition_table)->where($condition_where)->order($order)->limit($limit)->select();

        //return $this->field(true)->where(array('village_id'=>$village_id,'status'=>'1'))->order('`is_hot` DESC,`add_time` DESC')->limit($limit)->select();
    }

    /*得到分类下的新闻列表*/
    public function get_list_by_cid($cat_id, $floor_id=0,$news_id=false)
    {
        $where = array(
            'a.status'=>1,
            'b.cat_status'=>1,
        );
        if($cat_id == '_99'){
            $where['a.type']=0;
        }else{
            if( $cat_id != '0' ){
                $where['a.cat_id']=$cat_id;
            }
        }

        if($news_id != false){
            $ids=implode(',',$news_id);
            $where['_string'] = "((a.news_id in (".$ids.") and a.is_whole = 1) OR a.is_whole = 0 )";
        }else{
            $where['_string'] =' (a.is_whole = 0 )';
        }
        //到期时间判断
        $where['_string'].=' and ( (a.end_time = 0) or (a.end_time >= '.time().') )';
        if ($floor_id>0) {
//            $where['_string'] .= "FIND_IN_SET('$floor_id', floor_txt) OR floor_txt=''";
//            $where['_string'] = " FIND_IN_SET('$floor_id', floor_txt) OR floor_txt='' OR floor_txt IS NULL OR floor_txt=0";
        } else {
            //$where['_string'] .= "floor_txt=''";
//            $where['_string'] = "floor_txt='' OR floor_txt IS NULL OR floor_txt=0";
        }

        $list = $this
            ->join('as a LEFT JOIN ' . C('DB_PREFIX') . 'house_village_news_category as b ON a.cat_id=b.cat_id')
            ->field('a.*,b.cat_name')
            ->where($where)
            ->order('a.is_hot DESC,a.add_time DESC')
//            ->buildSql();
            ->select();
        return $list;
    }

    public function get_one($news_id){
        return $this->field(true)->where(array('status'=>'1','news_id'=>$news_id))->find();
    }

    public function get_one_new($news_id,$village_id=''){
        $where = array('status'=>'1','news_id'=>$news_id);
        if($village_id){
            $where['village_id'] = $village_id;
        }
        //到期时间判断
        $where_str='( (end_time = 0) or (end_time >= '.time().') )';
        return $this->field(true)->where($where)->where($where_str)->find();
    }



    public function get_notice_new($village_id, $floor_id=0,$news_id=false)
    {
        $where = array(
            'a.status'=>1,
//            'b.cat_status'=>1,
            'a.village_id'=>array(0,$village_id,'or')
        );
        if($news_id != false){
            $ids=implode(',',$news_id);
            $where['_string'] = "(a.news_id in (".$ids.") and a.is_whole = 1) OR (a.is_whole = 0) ";
        }else{
            $where['_string'] ='(a.is_whole = 0)';
        }
        $where['_string'].=' and (( a.type = 1 AND b.cat_status = 1 ) OR a.type = 0)';
        //到期时间判断
        $where['_string'].=' and ( (a.end_time = 0) or (a.end_time >= '.time().') )';
//        if ($floor_id>0) {
//            $where['_string'] = "FIND_IN_SET('$floor_id', a.floor_txt) OR a.floor_txt='' OR a.floor_txt IS NULL OR a.floor_txt=0";
//        } else {
//            $where['_string'] = "a.floor_txt='' OR a.floor_txt IS NULL OR a.floor_txt=0";
//        }
        $new = $this
            ->join('as a LEFT JOIN ' . C('DB_PREFIX') . 'house_village_news_category as b ON a.cat_id=b.cat_id')
            ->field('a.*')
            ->where($where)->order('a.news_id desc')->limit(1)->find();
        if($new){
            $base_url = D('House_village')->base_url;
            $site_url = C('config.site_url');
            $new['add_time'] = date('m-d',$new['add_time']);
            $new['notice_url'] = $site_url . $base_url . 'pages/village/index/notice?village_id='.$village_id;
            $new['notice_detail'] = $site_url . $base_url . "pages/village/index/villagecontent?news_id={$new['news_id']}";
            return $new;
        }

        return array();
    }

    public function get_popup_new_list($village_id,$time, $floor_id=0,$news_id=false)
    {
        $where = array(
            'a.add_time'=>['gt', $time],
            'a.status'=>1,
//            'b.cat_status'=>1,
            'a.village_id'=>array(0,$village_id,'or'),
            'a.is_important_notice'=>1
        );
        if($news_id != false){
            $ids=implode(',',$news_id);
            $where['_string'] = "(a.news_id in (".$ids.") and a.is_whole = 1) OR (a.is_whole = 0 )";
        }else{
            $where['_string'] ='(a.is_whole = 0)';
        }
        $where['_string'].=' and (( a.type = 1 AND b.cat_status = 1 ) OR a.type = 0)';
//        if ($floor_id>0) {
//            $where['_string'] = "FIND_IN_SET('$floor_id', a.floor_txt) OR a.floor_txt='' OR a.floor_txt IS NULL OR a.floor_txt=0";
//        } else {
//            $where['_string'] = "a.floor_txt='' OR a.floor_txt IS NULL OR a.floor_txt=0";
//        }
        $new_list = $this
            ->join('as a LEFT JOIN ' . C('DB_PREFIX') . 'house_village_news_category as b ON a.cat_id=b.cat_id')
            ->field('a.*')
            ->where($where)->order('a.news_id desc')->limit(3)->select();
        if (!$new_list) {
            $new_list = [];
        } else {
            $base_url = D('House_village')->base_url;
            $site_url = C('config.site_url');
            foreach($new_list as $k=>&$v){
                $v['add_time'] = date('m-d',$v['add_time']);
                $v['notice_url'] = $site_url . $base_url . 'pages/village/index/notice?village_id='.$village_id;
//            $v['url'] = $site_url . $base_url . "pages/village/index/villagecontent?news_id={$v['news_id']}";
                $v['notice_detail'] = $site_url . $base_url . "pages/village/index/villagecontent?news_id={$v['news_id']}";
            }
            $new_list = array_values($new_list);
        }
        return $new_list;
    }


    public function news_edit($where,$is_device_show){
        return  $this->where($where)->data(['is_device_show'=>$is_device_show])->save();
    }

    public function get_news($where,$field=true){
        return  $this->where($where)->field($field)->find();
    }



}
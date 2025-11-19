<?php

class Discuz_apiModel extends Model{

    public $http='';
    public $domain ='';
    public $forum_classify_url='';
    public $forum_post_url='';

    protected $TencentCall=[];
    public function __construct(){
        parent::__construct();
        import('ORG.Net.Http');
        $this->http = new Http();
        $this->domain='https://bbs.zhongxiang2020.com/';
        $this->forum_classify_url= $this->domain.'/api.php?mod=o2o&type=1';
        $this->forum_post_url= $this->domain.'/api.php?mod=o2o&type=2';
    }


    public function get_string_between($string, $start, $end){
        $string = " ".$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }


    //todo url 拼接域名
    public  function GetUrl($str){
        if(!empty($str)){
            if(strpos($str,'http') !== false){
                return $str;
            }else{
                return $this->domain.$str;
            }
        }else{
            return $str;
        }
    }

    /**
     * 计算两个时间戳之差
     * @param $begin_time
     * @param $end_time
     * @return string
     */
    public function timeDiff( $begin_time, $end_time ){
        if ( $begin_time < $end_time ) {
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        $timediff = $endtime - $starttime;
        $days = intval( $timediff / 86400 );
        $remain = $timediff % 86400;
        $hours = intval( $remain / 3600 );
        $remain = $remain % 3600;
        $mins = intval( $remain / 60 );
        $secs = $remain % 60;
        if($days < 1){
            if($hours < 1){
                if($mins < 1){
                    $str=$secs.'秒前';
                }else{
                    $str=$mins.'分钟前';
                }
            }elseif($hours >= 1 && $hours <= 12){
                $str=$hours.'小时前';
            }else{
                $str='昨天 '.date('H:i',$starttime);
            }
        }
        elseif ($days == 1){
            $str='昨天 '.date('H:i',$starttime);
        }
        elseif ($days == 2){
            $str='前天 '.date('H:i',$starttime);
        }
        elseif ($days >= 3 && $days <= 7){
            $str=$days.'天前';
        }
        else{
            $str=date('Y-m-d H:i:s',$starttime);
        }
        return $str;
    }


    //todo 查询分类
    public function getForumClassify($site_url){
        $results = $this->http->curlGet($this->forum_classify_url);
        if(!$results){
            return ['error'=>false,'data'=>'请求数据为空'];
        }
        $results=json_decode($results,true);
        $list=[];
        if(!empty($results['data'])){
            $list[]=[
                'classify_id'=>0,
                'title'=>'论坛首页',
                'url'=>self::get_forum_url($site_url,'https://bbs.zhongxiang2020.com/forum.php?mod=guide&view=hot&mobile=2')
            ];
            foreach ($results['data'] as $v){
                if($v['name'] != '站务中心'){
                    $list[]=[
                        'classify_id'=>$v['fid'],
                        'title'=>$v['name'],
                        'url'=>self::get_forum_url($site_url,'https://bbs.zhongxiang2020.com/forum.php?mod=guide&view=hot&mobile=2')
                    ];
                }

            }
        }
        return $list;
    }

    public function get_forum_url($site_url,$path){
//        return $site_url.U('Wap/Discuz/main',array('forum_url'=>urlencode($path)));
        return $site_url.'/wap.php?g=Wap&c=Discuz&a=main&forum_url='.urlencode($path);
    }


    //todo 通过分类查询帖子列表
    public function getForumPost($site_url,$fid=0,$start,$page){
        $url=$this->forum_post_url;
        if(!empty($fid)){
            $url.='&fid='.$fid;
        }
        $url.='&start='.$start.'&page='.$page;
        $time=time();
        $results = $this->http->curlGet($url);
        if(!$results){
            return ['error'=>false,'data'=>'请求数据为空'];
        }
        $results=json_decode($results,true);
        $list=[];
        if(!empty($results['data'])){
            foreach ($results['data'] as $v){
                $pic=[];
                $pic_count=0;
                if(!empty($v['pic'])){
                    foreach ($v['pic'] as $k2=>$v2){
                        if($k2 > 2){
                            continue;
                        }
                        $pic[]=[
                            'attachment'=>self::GetUrl('data/attachment/forum/'.$v2['attachment']),
                            'width'=>$v2['width'],
                            'is_size'=>(intval($v2['width']) >= 1000 ? 1:0 ),
                            'thumb'=>$v2['thumb']
                        ];
                    }
                    if(count($pic) == 2){
                        unset($pic[1]);
                    }
                    $pic_count=count($pic);
                }
                $list[]=[
                        'authorid'      =>$v['authorid'],                 //楼主uid
                        'author'        =>$v['author'],                  //楼主昵称
                        'authorlevel'   =>$v['grouptitle'],                  //楼主昵称
                        'avatar'        =>self::GetUrl('uc_server/avatar.php?uid='.$v['authorid'].'&size=small'),    //楼主头像
                        'authorurl'     =>self::get_forum_url($site_url,self::GetUrl('home.php?mod=space&uid='.$v['authorid'].'&do=profile')), //楼主链接
                        'url'           =>self::get_forum_url($site_url,self::GetUrl('forum.php?mod=viewthread&tid='.$v['tid'])),       //帖子链接
                        'title'         =>$v['title'],                   //帖子标题
                        'summary'       =>$v['summary'],                 //帖子内容
                        'pic'           =>$pic,                         //附件图片
                        'pic_count'     =>$pic_count,                  //附件图片
                        'dateline'      =>self::timeDiff($v['dateline'],$time),                //主题发布时间
                        'views'         =>$v['views'],                   //主题查看数
                        'replies'       =>$v['replies'],                 //主题回复数
                        'forumname'     =>empty($v['forumname']) ? '' : '#'.$v['forumname'],               //版块名称
                        'forumurl'      =>self::get_forum_url($site_url,self::GetUrl('forum.php?mod=forumdisplay&fid='.$v['fid'])),  //版块URL
                ];
            }
        }
        return $list;
    }



    //todo 链接获取数据  【接口废弃】
    public function getUrlData($url){
        import('ORG.Net.Http');
        $http = new Http();
        $time=time();
        $results = $http->curlGet($url);
        if(!$results){
            return ['error'=>false,'data'=>'请求数据为空'];
        }
        $string=$this->get_string_between($results,'_public_','_public_');
        if(empty($string)){
            return ['error'=>false,'data'=>'数据解析失败'];
        }
        $string=explode('_line_',$string);
        if(empty($string)){
            return ['error'=>false,'data'=>'数据解析为空'];
        }
        $data=[];
        foreach ($string as $v){
            if(empty($v)){
                continue;
            }
            $arr=explode('_php_',$v);
            if(!is_array($arr) || empty($arr)){
                continue;
            }
            list($authorid,$author,$avatar,$url,$title,$summary,$pic,$dateline,$views,$replies,$forumname,$forumurl) = $arr;
            $data[]=[
                'authorid' =>$authorid,                 //楼主uid
                'author'    =>$author,                  //楼主昵称
                'avatar'    =>$avatar,                  //楼主头像
                'authorurl' =>self::GetUrl('home.php?mod=space&uid='.$authorid.'&do=profile'), //楼主链接
                'url'        =>self::GetUrl($url),       //帖子链接
                'title'      =>$title,                   //帖子标题
                'summary'   =>$summary,                 //帖子内容
                'pic'       =>self::GetUrl($pic),       //附件图片
                'dateline'  =>self::timeDiff(strtotime($dateline),$time),                //主题发布时间
                'views'     =>$views,                   //主题查看数
                'replies'   =>$replies,                 //主题回复数
                'forumname' =>empty($forumname) ? '' : '#'.$forumname,               //版块名称
                'forumurl'  =>self::GetUrl($forumurl),  //版块URL
            ];
        }
        return ['error'=>true,'data'=>$data];
    }
}
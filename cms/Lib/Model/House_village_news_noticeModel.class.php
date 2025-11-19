<?php
/**
 * @author liukezhu
 */

class House_village_news_noticeModel extends Model{

    /**
     * 选择对象数据写入
     * @author liukezhu
     * @date 2021/4/12
     * @param $village_id
     * @param $mark_id
     */
    public function choose_floor_add($village_id,$mark_id,$news_id=false){
        $data=[];
        if($news_id){
            $where=array(
                'uid'=>$village_id,
                'news_id'=>$news_id
            );
        }else{
            $where=array(
                'uid'=>$village_id,
                'news_id'=>0
            );
        }
        //已写入的数据
        $notice_ = D('House_village_news_notice')->where($where)->field('uid,village_id,single_id,floor_id,layer_id,room_id,news_id')->select();
        foreach ($mark_id as $v){
            $v=explode('-',$v);
            $news_id=($news_id) ? $news_id : 0;
            $data[]=array(
                'uid'=>$village_id,
                'village_id'=>$v[0],
                'single_id'=>$v[1],
                'floor_id'=>$v[2],
                'layer_id'=>$v[3],
                'room_id'=>$v[4],
                'news_id'=>$news_id,
                'type'=>1
            );
        }
        //去重
        $rel = array_filter($data, function($v) use ($notice_) { return ! in_array($v, $notice_);});
        if($rel){
            foreach ($rel as &$v){
                $v['add_time']=time();
            }
            unset($v);
            return M('House_village_news_notice')->addAll(array_values($rel));
        }else{
            return false;
        }
    }


    //todo 平台新闻绑定的小区
    public function news_notice_bind($where=[],$news_id=0){
        $dbhouse_village = D('House_village');
        $dbHouse_village_news_notice = D('House_village_news_notice');
        $village_id_all=$dbHouse_village_news_notice->where(['type'=>0,'news_id'=>$news_id])->getField('village_id',true);
        $where['status']=1;
        $where['village_id']=['in',$village_id_all];
        $count_village = $dbhouse_village->where($where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count_village,20,'page');
        $field = "village_id,village_name,village_address";
        $list = $dbhouse_village->field($field)->where($where)->order('village_id desc')->limit($p->firstRow . ',' . $p->listRows)->select();
        return ['list'=>$list,'page'=>$p->show()];
    }

    //todo 平台新闻解绑小区
    public function news_notice_unbind($village_id){
        $dbHouse_village_news_notice = D('House_village_news_notice');
        $dbHouse_village_news_notice->where([
            'type'=>0,
            'village_id'=>$village_id
        ])->delete();
        return true;
    }

    //todo 平台新闻发布绑定小区数据
    public function news_notice_add($post){
        $village_id=explode(',',$post['mark_id']);
        if(isset($post['news_id']) && !empty($post['news_id'])){
            $news_id=$post['news_id'];
        }
        $data=[];
        foreach ($village_id as $v){
            $d=array(
                'uid'=>0,
                'type'=>0,
                'village_id'=>$v,
                'room_id'=>0,
                'add_time'=>time()
            );
            if(!empty($news_id)){
                $d['news_id']=$news_id;
            }
            $data[]=$d;
        }

//        $users = D('House_village_user_bind')->where([
//            'village_id'=>['in',$village_id],
//            'status'=>['in',[0,1]],
//            'type'=>['in',[0,1,2,3]],
//            'vacancy_id'=>['gt',0],
//            'uid'=>['gt',0]
//        ])->field('vacancy_id,village_id')->distinct('vacancy_id')->order('vacancy_id desc')->select();
//        if(!$users){
//            return ['error'=>false,'msg'=>'该小区下暂无人员'];
//        }
//        $news_id=0;
//        if(isset($post['news_id']) && !empty($post['news_id'])){
//            $news_id=$post['news_id'];
//        }
//        $data=[];
//        foreach ($users as $v){
//            $d=array(
//                'uid'=>0,
//                'type'=>0,
//                'village_id'=>$v['village_id'],
//                'room_id'=>$v['vacancy_id'],
//                'add_time'=>time()
//            );
//            if(!empty($news_id)){
//                $d['news_id']=$news_id;
//            }
//            $data[]=$d;
//        }
        M('House_village_news_notice')->addAll($data);
        return ['error'=>true,'msg'=>'绑定成功'];
    }


    //todo 平台新闻发布
    public function news_notice_send($news_id){
        ini_set('max_execution_time',0);
        ini_set('memory_limit',-1);
        $dbHouse_village_news = D('house_village_news');
        $dbHouse_village_news_notice = D('House_village_news_notice');
        $news_info = $dbHouse_village_news->where(['news_id'=>$news_id,'type'=>0,'is_notice'=>0])->find();
        if(!$news_info){
            return ['error'=>false,'msg'=>'新闻不存在'];
        }
        $where=[];

        if($news_info['is_whole'] == 1 ){
           $village_id= $dbHouse_village_news_notice->where(['type'=>0,'news_id'=>$news_info['news_id']])->getField('village_id',true);
           if($village_id){
               $where['b.village_id']=['in',$village_id];
           }
        }
        $where['b.status'] = 1;
        $where['b.type'] = ['in',[0,1,2,3]];
        $users=M('house_village_user_bind as b')
            ->join(C('DB_PREFIX').'user as u on u.uid=b.uid')
            ->join(C('DB_PREFIX').'house_village as v on v.village_id=b.village_id')
            ->where($where)->where('u.openid is not null and u.openid <> ""')
            ->field('b.pigcms_id,b.uid,u.openid,u.nickname,u.device_id,u.client,b.type,b.village_id,v.property_id')
            ->group('u.openid,b.type')
            ->select();
        if(empty($users)){
            return ['error'=>false,'msg'=>'暂无发送对象'];
        }
        $dbHouse_village_news->where(['news_id'=>$news_info['news_id']])->save(array('is_notice'=>1));
        $site_url = C('config.site_url');
        $base_url = D('House_village')->base_url;
        import('@.ORG.Apppush');
        $apppush = new Apppush();
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $param = [
            'plan_send' =>1
        ];
        $addData = [];
        foreach ($users as $userInfo){
            $href = $site_url . $base_url . 'pages/village/index/villagecontent?village_id=' . $userInfo['village_id'] . ($userInfo['pigcms_id'] ? '&pigcms_id=' . $userInfo['pigcms_id'] : '') . '&news_id=' . $news_id;
            $data=array(
                'href' => $href,
                'wecha_id' => $userInfo['openid'],
                'first' => '您好，平台发布了一条新的消息【'.$news_info['title'].'】\n',
                'keyword1' => ''.C('config.house_name').'新闻',
                'keyword2' =>'已发送',
                'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                'keyword4' => $userInfo['nickname'], 'remark' => '\n请点击查看详细信息！',
                'new_info' => [//新版本发送需要的信息
                    'tempKey'=>'44716',//新模板号
                    'thing12'=>$news_info['title']?:(C('config.house_name').'新闻'),//服务项目
                    'phrase2'=>'新闻',//订单类型
                    'time13'=>date('H:i',$_SERVER['REQUEST_TIME']),//服务时间
                    'thing11'=>'无',//服务地址
                ],
            );
//            // 新增模板消息（消息发送状态提醒）
//           $model->sendTempMsg('OPENTM405462911', $data,0,$userInfo['property_id'],2,$param);
           $now_order=array(
                'bind_id'=>$userInfo['village_id'] . '_' . $userInfo['pigcms_id'],
                'url'=>$href,
                'title'=>'新闻通知',
                'uid'=>$userInfo['uid'],
                'msg'=>$data['first']
            );
//           $apppush->send($now_order, 'house_notice');
//           unset($data,$now_order,$userInfo);

            $addData[] = [
                'village_id'=>$userInfo['village_id'],
                'property_id'=>$userInfo['property_id'],
                'temp_key'=>'OPENTM405462911',
                'temp_data'=>json_encode($data,JSON_UNESCAPED_UNICODE),
                'app_push_data'=>json_encode($now_order,JSON_UNESCAPED_UNICODE),
                'add_time'=>time()
            ];
        }
        //写入到待处理表中慢慢处理
        if($addData){
            M('Notice_send')->addAll($addData);
        }
        return ['error'=>true,'msg'=>'发送成功'];
    }


    /**
     * 读取已绑定数据
     * @author liukezhu
     * @date 2021/4/13
     * @param $village_id
     * @param array $where
     * @param int $pageSize
     */
    public function choose_bind_list($village_id,$where=array(),$pageSize=20){
        $where_ = array(
            'n.uid'=>$village_id,
            'n.type'=>1
        );
        if(!empty($where)){
            $where_= array_merge($where_,$where);
        }
        import('@.ORG.merchant_page');
        $count = M('House_village_news_notice as n')
            ->join(C('DB_PREFIX').'house_village_user_vacancy as r on r.pigcms_id = n.room_id')
            ->join(C('DB_PREFIX').'house_village_floor as f on f.floor_id = n.floor_id')
            ->join(C('DB_PREFIX').'house_village_single as s on s.id = n.single_id')
            ->where($where_)
            ->count();
        $p = new Page($count,$pageSize,'page');

        $list = M('House_village_news_notice as n')
            ->join(C('DB_PREFIX').'house_village_user_vacancy as r on r.pigcms_id = n.room_id')
            ->join(C('DB_PREFIX').'house_village_floor as f on f.floor_id = n.floor_id')
            ->join(C('DB_PREFIX').'house_village_single as s on s.id = n.single_id')
            ->where($where_)
            ->field('n.pigcms_id as notice_id,r.village_id,r.pigcms_id,f.floor_id, f.single_id,s.single_name as floor_layer,r.layer_id, f.floor_name,r.room')
            ->order('n.add_time asc')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        if($list){
            //查询楼层数据
            $layer_arr = M('House_village_layer')->where([
                'status'=>1,
                'village_id'=>$village_id
            ])->getField('id,layer_name');
            foreach($list as &$row){
                if ($row['layer_id'] && $layer_arr[$row['layer_id']]) {
                    $row['layer'] = $layer_arr[$row['layer_id']];
                }
            }
            unset($row);
            return array('status'=>1,'list'=>$list,'pagebar'=>$p->show());
        }else{
            return array('status'=>0,'list'=>$list,'pagebar'=>$p->show());
        }
    }

    /**
     * 选择对象读取
     * @author liukezhu
     * @date 2021/4/12
     * @param $village_id
     * @param array $where
     * @param int $pageSize
     */
    public function choose_floor_list($village_id,$where=array(),$pageSize=20){
        $where_ = array(
            'f.village_id'=>$village_id,
            'f.status'=>1,
            'r.is_del'=>0,
            'r.status'=>array('in','1,3')
        );
        if(!empty($where)){
            $where_= array_merge($where_,$where);
        }
        import('@.ORG.merchant_page');

        $count = M('house_village_user_vacancy as r')
            ->join(C('DB_PREFIX').'house_village_floor as f on f.floor_id = r.floor_id')
            ->join(C('DB_PREFIX').'house_village_single as s on s.id = r.single_id')
            ->where($where_)
            ->count();
        $p = new Page($count,$pageSize,'page');

        $list = M('house_village_user_vacancy as r')
            ->join(C('DB_PREFIX').'house_village_floor as f on f.floor_id = r.floor_id')
            ->join(C('DB_PREFIX').'house_village_single as s on s.id = r.single_id')
            ->where($where_)
            ->field('r.village_id,r.pigcms_id,f.floor_id, f.single_id, s.single_name as floor_layer,r.layer_id, f.floor_name,r.room')->order('r.add_time asc')
            ->limit($p->firstRow.','.$p->listRows)
            ->select();
        if($list){
            //查询楼层数据
            $layer_arr = M('House_village_layer')->where([
                'status'=>1,
                'village_id'=>$village_id
            ])->getField('id,layer_name');
            foreach($list as &$row){
                if ($row['layer_id'] && $layer_arr[$row['layer_id']]) {
                    $row['layer'] = $layer_arr[$row['layer_id']];
                }
            }
            unset($row);
            return array('status'=>1,'list'=>$list,'pagebar'=>$p->show());
        }else{
            return array('status'=>0,'list'=>$list,'pagebar'=>$p->show());
        }
    }

    /**
     * 删除绑定
     * @author liukezhu
     * @date 2021/4/13
     */
    public function choose_remove($village_id,$notice){
        $list = D('House_village_news_notice')->where(['uid'=>$village_id,'pigcms_id'=>$notice])->find();
        if(!$list){
            return false;
        }
        return D('House_village_news_notice')->where(['pigcms_id'=>$list['pigcms_id']])->delete();
    }

}

?>
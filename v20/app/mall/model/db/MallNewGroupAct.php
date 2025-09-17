<?php


namespace app\mall\model\db;
use app\common\model\service\GoodsScoreService;
use app\common\model\service\percent_rate\PercentRateService;
use app\mall\model\service\MallOrderService;
use think\facade\Db;
use think\Model;
use think\facade\Config;
class MallNewGroupAct extends Model
{
    public function getOne($goods_id,$store_id){
        $where[] = ['','exp',Db::raw("FIND_IN_SET($goods_id,good_id)")];
        $result1=[];
        $arr= $this->where($where)->find();
        if($arr){
            $result1['act_type']=0;
            $result1['name']="活动";
            $result1['msg']="满".round($arr['goods_money'])."送".round($arr['nums'])."件";
        }
        return $result1;
    }
//拼团活动基本情况
    public function getBase($act_id){
        $where[] = ['id','=',$act_id];
        $arr= $this->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }
        return $arr;
    }
/*我的拼团全部列表*/
    public function getList($uid,$status,$page,$limit=0,$addr){
        $prefix = config('database.connections.mysql.prefix');
        $field = "o.user_id,s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,o.order_id,m.user_id as team_uid,m.id as team_id,
        p.price as goods_price,s.complete_num,p.goods_type, od.mer_id,
        m.status as activity_status";
        $order="m.id desc";//最新拼团要展示在最前面
        $whereor=array();
        if($uid!=0){//我的拼团
            $where[]=["o.user_id",'=',$uid];
            $where[]=["o.type",'=',0];
        }
        $where[]=["a.type",'=','group'];
        if($addr==0){
            $where[]=[ 'a.start_time','<',time()];
            $where[]=[ 'a.end_time','>=',time()];
            $where[]=[ 'm.start_time','<',time()];
            $where[]=[ 'm.end_time','>=',time()];
            $where[]=[ 'm.status','=',0];
            $where[]=['od.refund_status','<>',2];
        }

        switch ($status){
            case 0://全部
                break;
            case 1://1进行中
                $where[]=["m.status",'=',0];
                $where[]=["gd.status",'<>',2];
                $where[]=[ 'm.start_time','<',time()];
                $where[]=[ 'm.end_time','>=',time()];
                $where[]=['od.status','>',0];
                $where[]=['od.status','<=',40];
                break;
            case 2:// 2已完成
                $where[]=["m.status",'=',1];
                $where[]=['od.refund_status','<>',2];
                break;
            case 3://3已过期已失败
                $beforetime = time() - 86400*7;     //7天前的时间戳=当前时间戳-7天时间戳
                $where[]=["m.status",'<>',1];
                $where[]=[ 'm.end_time','<',time()];
                $where[] = ['m.end_time','between',[$beforetime,time()]];
                $whereor=[['od.status','>',40],['m.end_time','between',[$beforetime,time()]]];
                break;
        }
        if($limit==0){
            $return = $this ->alias('s')
                ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
                ->join($prefix.'mall_new_group_team'.' m','s.id = m.act_id')
                ->join($prefix.'mall_new_group_team_user'.' o','o.tid = m.id')
                ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
                ->join($prefix.'mall_new_group_order'.' gd','gd.tid = m.id')
                ->join($prefix.'mall_order'.' od','od.order_id = gd.order_id')
                ->field($field)
                ->where($where)
                ->whereOr($whereor)
                ->group('m.id')
                ->order($order);
            $count = $return->count();
            $list = $return->page($page,Config::get('api.page_size'))
                ->select()->toArray();
        }else{
            $return = $this ->alias('s')
                ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
                ->join($prefix.'mall_new_group_team'.' m','s.id = m.act_id')
                ->join($prefix.'mall_new_group_team_user'.' o','o.tid = m.id')
                ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
                ->join($prefix.'mall_new_group_order'.' gd','gd.tid = m.id')
                ->join($prefix.'mall_order'.' od','od.order_id = gd.order_id')
                ->field($field)
                ->where($where)
                ->whereOr($whereor)
                ->group('m.id')
                ->order($order);
            $count = 2;
            $list = $return->page($page,$count)
                ->select()->toArray();
        }
        foreach ($list as $key=>&$val){
            if($val['user_id']!=$uid){
                unset($list[$key]);
                continue;
            }
            if($status==1){
                $where0=[['uid','=',$uid],['order_id','=',$val['order_id']]];
                $msg=(new MallNewGroupOrder())->getOneOrder($where0,$field="*");
                if($msg['status']!=1){//未支付过滤
                    unset($list[$key]);
                    continue;
                }
            }
            $where1=[['goods_id','=',$val['goods_id']],['order_id','=',$val['order_id']]];
            $group_price=(new MallOrderDetail())->getOneOrderMsg($where1);
           // $list[$key]['group_price']=$group_price<0?0:$group_price;
            $list[$key]['group_price']=get_format_number($group_price);
            $list[$key]['goods_price']=get_format_number($val['goods_price']);
            $where2=[['act_id','=',$val['id']]];
            $list[$key]['success_peoples']=(new MallNewGroupTeam())->getGroupNumSucessMan($where2,$sum='num');
            $list[$key]['goods_image']=$val['goods_image'] ? replace_file_domain($val['goods_image']) : '';
            $list[$key]['goods_type']=$val['goods_type'];

            //积分展示
            $val['score_text'] = '';
            if(customization('goods_show_score') && $scorePrice = floatval($val['goods_price'])){
                $scorePercent = PercentRateService::scorePercent('mall', $val['goods_id'], $val['mer_id']);
                $score = bcmul((string)$scorePrice, (string)$scorePercent, 2);
                if(cfg('score_round_two')){
                    $score = sprintf('%.2f',floatval($score));
                }else{
                    $score = round($score);
                }
                floatval($score) > 0 && $val['score_text'] = cfg('score_name').'+'. $score;
            }
        }
        array_values($list);
        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'list'        =>  $list
        ];
        return $return;
    }

    public function getRecGroupList($uid,$status,$page,$limit,$addr){
        //$prefix = config('database.connections.mysql.prefix');
        $field = "s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image, p.mer_id,
        p.price as goods_price,s.complete_num,p.goods_type, p.score_percent,p.goods_type";
        $where[]=["s.is_recommend",'=',1];
        $where[]=["s.recommend_start_time",'<',time()];
        $where[]=["s.recommend_end_time",'>=',time()];
        $order="s.sort desc";
        $where[]=["a.type",'=','group'];
        $where[]=[ 'a.start_time','<',time()];
        $where[]=[ 'a.end_time','>=',time()];
        $where[]=[ 'a.status','=',1];

        $return = $this ->alias('s')
            ->join('mall_activity'.' a','a.act_id = s.id')
            ->join('mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->order($order);
        $count = $return->count();
        $list = $return->page($page,Config::get('api.page_size'))
        ->select()->toArray();

        foreach ($list as $key=>&$val){
            $where1=[['act_id','=',$val['id']],['goods_id','=',$val['goods_id']]];
            $list[$key]['goods_image']=$val['goods_image'] ? replace_file_domain($val['goods_image']) : '';
            $group_price=(new MallNewGroupSku())->getPice($where1);
            $list[$key]['act_stock_num']=(new MallNewGroupSku())->getSum($where1,$field='act_stock_num');
            $list[$key]['goods_price']=get_format_number($val['goods_price']);
            $list[$key]['group_price']=get_format_number($group_price);
            $where2=[['act_id','=',$val['id']],['status','=',1]];
            $list[$key]['success_peoples']=(new MallNewGroupTeam())->getGroupNumSucessMan($where2,$sum='complete_num');
            $list[$key]['goods_type']=$val['goods_type'];
            //积分展示
            $val['score_text'] = '';
            if(customization('goods_show_score') && $scorePrice = floatval($val['goods_price'])){
                $scorePercent = PercentRateService::scorePercent('mall', $val['goods_id'], $val['mer_id']);
                $score = bcmul((string)$scorePrice, (string)$scorePercent, 2);
                if(cfg('score_round_two')){
                    $score = sprintf('%.2f',floatval($score));
                }else{
                    $score = round($score);
                }
                floatval($score) > 0 && $val['score_text'] = cfg('score_name').'+'. $score;
            }
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($val['group_price'],'mall',($val['goods_type']=='sku'?true:false),$val['score_percent']);
            $val['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $val['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }
        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'list'        =>  $list
        ];
        return $return;
    }
    public function getRecGroupListDec(){
        $field = "s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,
        p.price as goods_price,s.complete_num,p.goods_type";
        $where[]=["s.is_recommend",'=',1];
        $where[]=["s.recommend_start_time",'<',time()];
        $where[]=["s.recommend_end_time",'>=',time()];
        $order="s.sort desc";
        $where[]=["a.type",'=','group'];
        $where[]=[ 'a.start_time','<',time()];
        $where[]=[ 'a.end_time','>=',time()];
        $where[]=[ 'a.status','=',1];
        $where[]=[ 'ms.status','=',1];
        $return = $this ->alias('s')
            ->join('mall_activity'.' a','a.act_id = s.id')
            ->join('merchant_store'.' ms','ms.store_id = a.store_id')
            ->join('mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->order($order);
        $list = $return->page(1,4)
            ->select()->toArray();
        foreach ($list as $key=>$val){
            $list[$key]['goods_image']=$val['goods_image'] ? replace_file_domain($val['goods_image']) : '';
        }
        return $list;
    }
    /*拼团连接详情*/
    public function group_detail($uid,$orderid,$groupid,$teamid,$uid1){
        $rets=(new MallOrderService())->getOrderDetail($orderid);
        if(!empty($rets['detail'])) {
            $sku_id=$rets['detail'][0]['sku_id'];
            $where[]=[ 'sku.sku_id','=',$sku_id];
        }
     //连接必须包含：拼主的uid,订单orderid,活动groupid
        $prefix = config('database.connections.mysql.prefix');
        $field = "s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,
        sku.price as goods_price,r.price as group_price,s.complete_num as man_num,
        m.status as activity_status,m.start_time,m.end_time,r.sku_info,p.goods_type";
        $where[]=[ 'r.order_id','=',$orderid];
        $where[]=[ 's.id','=',$groupid];
        $where[]=[ 'm.id','=',$teamid];
        $return['groups'] = $this ->alias('s')
            ->join($prefix.'mall_new_group_team'.' m','s.id = m.act_id')
            ->join($prefix.'mall_new_group_team_user'.' o','o.tid = m.id')
            ->join($prefix.'mall_order_detail'.' r','r.order_id = o.order_id')
            ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
            ->join($prefix.'mall_goods_sku'.' sku','sku.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->order('s.id asc')
            ->find();
        if(!empty($return['groups'])){
            $return['groups']=$return['groups']->toArray();
            if(!empty($sku_id)) {
                $where2=[['act_id','=',$groupid],['goods_id','=',$return['groups']['goods_id']],['sku_id','=',$sku_id]];
            }else{
                $where2=[['act_id','=',$groupid],['goods_id','=',$return['groups']['goods_id']]];
            }
            $group_price=(new MallNewGroupSku())->getPice($where2);
            $return['groups']['group_price']=get_format_number($group_price);
            $return['groups']['goods_price']=get_format_number($return['groups']['goods_price']);
            /*if($uid!=$uid1){//不是团长打开的链接
                $rets=(new MallOrderService())->getOrderDetail($orderid);
                if(!empty($rets['detail'])) {
                    $sku_id=$rets['detail'][0]['sku_id'];
                    $where2=[['act_id','=',$groupid],['goods_id','=',$return['groups']['goods_id']],['sku_id','=',$sku_id]];
                }else{
                    $where2=[['act_id','=',$groupid],['goods_id','=',$return['groups']['goods_id']]];
                }
                $return['groups']['group_price']=(new MallNewGroupSku())->getPice($where2);
            }*/
            $return['groups']['goods_image']=$return['groups']['goods_image'] ? thumb_img($return['groups']['goods_image'], 320, 320, 'fill') : '';
        }
        $arrs=(new MallNewGroupTeamUser())->getUserList($teamid,$orderid,$uid,$groupid);
        $arrs1=(new MallNewGroupTeamUser())->getSuccessCount($groupid);
        $return['group_member_list']=$arrs;
        $return['now_num']=count($arrs);
        $return['success_peoples']=$return['success_num']=$arrs1;
       //参团活动状态
       if(empty($return['groups'])){
           $return['activity_status']=0;
           $team_msg=(new MallNewGroupTeam())->getOne($teamid);
           $return['groups']['end_time']=$team_msg['end_time'];
       }else{
           $return['activity_status']= $return['groups']['activity_status'];
       }
        return $return;
    }

    /**
     * @param $condition
     * @param $team_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * 根据条件活动活动详情
     */
    public function getDetail($condition,$team_id,$condition1=[],$act_id=0,$skuid=0)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field='s.id,s.complete_num,s.limit_num,s.is_discount_share,s.discount_card,s.team_discount_price,s.discount_coupon,m.*';
        $result['list'] = $this ->alias('s')
            ->join($prefix.'mall_new_group_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->field($field)
            ->order('act_price asc')
            ->find();
        if(!empty($result['list'])){
            $result['list']=$result['list']->toArray();
            if($act_id!=0 && $skuid==0){
                $where=[['act_id','=',$act_id]];
                $min=(new MallNewGroupSku())->getPice($where);
                if($min!=-1){
                    $result['list']['act_stock_num']=(new MallNewGroupSku())->getSum($where,$field='act_stock_num');
                }else{
                    $result['list']['act_stock_num']=-1;
                }
            }
        }

        $condition2[]=['status','=',1];
        //成团人数
        $result['sale_num'] =(new MallNewGroupTeam())->getGroupNumSucess($condition2);

        //拼团列表
        $result['group_list']=(new MallNewGroupTeam())->groupList($condition1);
        return $result;
    }

    public function getSkuList($condition)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field='m.*';
        $result = $this ->alias('s')
            ->join($prefix.'mall_new_group_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods'.' g','g.goods_id = m.goods_id')
            ->where($condition)
            ->field($field)
            ->order('act_price asc')
            ->select()
            ->toArray();
        $arr=array();
        if(!empty($result)){
            foreach ($result as $key=>$val){
                $li['sku_id']=$val['sku_id'];
                $li['act_price']=$val['act_price'];
                $li['team_price']=$val['team_price'];
                $li['act_stock']=$val['act_stock_num'];
                $arr[]=$li;
            }
        }
        return $arr;
    }

     /** 添加数据 获取插入的数据id
     * User: chenxiang
     * Date: 2020/10/14 14:58
     * @param $data
     * @return int|string
     */
    public function add($data) {
        return $this->insertGetId($data);
    }


    /**
     * 根据id 获取活动数据
     * User: chenxiang
     * Date: 2020/11/2 10:43
     * @param $condition
     * @param bool $field
     * @return array
     */
    public function getInfoById($condition, $field = true) {

        $result = $this->field($field)->where($condition)->find();
        if(!empty($result)) {
            $result = $result->toArray();
        }
        return $result;
    }

    /**
     * 查询活动基本信息
     * User: chenxiang
     * Date: 2020/11/4 9:40
     * @param $where
     * @param string $fields
     * @return mixed
     */
    public function getInfo($where,$fields='*')
    {
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('g')
            ->join($prefix.'mall_activity'.' m','g.id = m.act_id')
            ->field($fields)
            ->where($where)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 修改数据
     * User: chenxiang
     * Date: 2020/11/5 11:00
     * @param $data
     * @param $where
     * @return MallNewGroupAct
     */
    public function updateGroup($data,$where) {
        $result = $this->where($where)->data($data)->update();
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 拼团活动成团信息
     */
     public function getGroupTeamMasg($where){
         $prefix = config('database.connections.mysql.prefix');
         $field="s.*,m.*,t.*,t.start_time as team_start_time,t.end_time as team_end_time,t.id as team_id,s.id as group_act_id";
         $result = $this ->alias('s')
             ->join($prefix.'mall_activity'.' m','s.id = m.act_id')
             ->join($prefix.'mall_new_group_team'.' t','t.act_id = s.id')
             ->field($field)
             ->where($where)
             ->group('t.id')
             ->select();
         if(!empty($result)){
             $result=$result->toArray();
         }
         return $result;
    }

    /**
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     * 查询符合条件的拼团列表
     */
    public function getGroupActList($where,$field){
        $result = $this->field($field)->where($where)->select()->toArray();
        return $result;
    }

    /**
     * @param $where
     * @param bool $field
     * @param string $order
     * @param $page
     * @param $pageSize
     * @return mixed
     * 自定义页面接口
     */
    public function getInfoCount($where,$field=true,$order='s.id asc',$page, $pageSize){
        $return = $this ->alias('s')
            ->join('mall_activity'.' a','a.act_id = s.id')
            ->join('mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->order($order);

        $ret['count'] = $return->count();
        if($page!=0 && $pageSize!=0){
            $ret['list']=$return->page($page, $pageSize)->select()->toArray();
        }

        return $ret;
    }

}
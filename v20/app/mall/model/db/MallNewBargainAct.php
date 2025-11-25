<?php


namespace app\mall\model\db;

use app\common\model\service\GoodsScoreService;
use Exception;
use think\Model;
use think\facade\Config;
class MallNewBargainAct extends Model
{
    /*protected $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.mysql.prefix');
    }*/
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

    /**
     * @param $act_id
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author mrdeng
     */
    public function getAct($act_id){
        $where[] = ['id','=',$act_id];
        $arr= $this->where($where)->find();
        if(!empty($arr)){
            $arr=$arr->toArray();
        }
        return $arr;
    }

    /**
     * @param $goods_id
     * @param $activity_id
     * @param $uid
     * @param $team_id
     * @return mixed
     * 商品详情砍价信息
     */
    public function getBargainDetail($goods_id,$activity_id,$uid,$team_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $condition[] = ['m.goods_id','=',$goods_id];
        if(!empty($activity_id)){
            $condition[] = ['s.activity_id','=',$activity_id];
        }
        $field="m.*";
        $result = $this ->alias('s')
            ->field($field)
            ->join($prefix.'mall_new_bargain_sku'.' m','s.id = m.act_id')
            ->where($condition)
            ->order('m.act_price asc')
            ->select();
        if(!empty($result)){
            $result=$result->toArray();
        }

          //我的砍价进度条
        $result['bargain_rate']=array();
        $result['help_list']=array();//好友助力列表
        if(!empty($uid) && !empty($team_id)){
            $field1="m.bar_total_price,m.floor_price";
            $condition1[]=['s.goods_id','=',$goods_id];
            $condition1[]=['m.user_id','=',$uid];
            $condition1[]=['m.id','=',$team_id];
            $result1 = $this ->alias('s')
                ->field($field1)
                ->join($prefix.'mall_new_bargain_team'.' m','s.id = m.act_id')
                ->where($condition)
                ->select()
                ->find();
            if(!empty($result1)){
                $result1=$result1->toArray();
            }
            $result['bargain_rate']=$result1;
            //好友助力列表
            $result['help_list']=(new MallNewBargainTeam())->helpList($team_id);
        }

        $result['act_price']=$result[0]['act_price'];

       return $result;
    }

    /**
     * @param $condition
     * @return array
     * 活动商品规格
     */
    public function getSkuList($condition)
    {
        $prefix = config('database.connections.mysql.prefix');
        $field='m.*';
        $result = $this ->alias('s')
            ->join($prefix.'mall_new_bargain_sku'.' m','s.id = m.act_id')
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
                $li['act_stock']=$val['act_stock_num'];
                $arr[]=$li;
            }
        }
        return $arr;
    }

    /**
     * @param $condition
     * @return mixed
     * 根据条件活动活动详情
     */
    public function getDetail($condition,$team_id,$uid=0,$act_id=0){
       $prefix = config('database.connections.mysql.prefix');
        $result['list'] = $this ->alias('s')
            ->join($prefix.'mall_new_bargain_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_goods_sku'.' g','g.sku_id = m.sku_id')
            ->where($condition)
            ->find();
        if(!empty($result['list'])){
            $result['list'] = $result['list']->toArray();
        }
        if(!empty($result['list'])){
            //砍成数量
            $condition1[] = ['status', 'in', [1,2,3]];
            $condition1[] = ['act_id', '=', $act_id];
            $result['bargain_num'] =(new MallNewBargainTeam())->getBargainNum($condition1);

            //砍价进度条
            $condition2[]=['s.id','=',$result['list']['act_id']];
            $condition2[]=['m.user_id','=',$uid];
            $condition2[]=['m.start_time','<',time()];
            $condition2[]=['m.end_time','>=',time()];
            $condition2[]=['m.status','=',0];
            $field1="m.bar_total_price,m.floor_price,m.id as team_id,m.start_time,m.end_time";
            $result['bargain_rate'] = $this ->alias('s')
                ->field($field1)
                ->join($prefix.'mall_new_bargain_team'.' m','s.id = m.act_id')
                ->where($condition2)
                ->find();
            //var_dump($this ->getLastSql());
            if(!empty($result['bargain_rate'])){
                $result['bargain_rate']=$result['bargain_rate']->toArray();
            }
            //好友助力列表
            $arr_team=(new MallNewBargainTeam())->isManInBarginTeam($act_id,$uid);
            if($uid>0){
                $result['help_list']=(new MallNewBargainTeamUser())->helpList($arr_team['id']);
            }else{
                $result['help_list']=[];
            }
        }
        else{
            $result=array();
        }
        return $result;
    }

    /*我的拼团全部列表*/
    /**
     * @param $uid
     * @param $status
     * @param $page
     * @param int $limit
     * @param $addr
     * @return array
     * 我的砍价列表
     */
    //s.complete_num,
    public function getMyBargainList($uid,$status,$page,$limit=0,$addr){
        $prefix = config('database.connections.mysql.prefix');
        //fdump($prefix,"dedededw22",1);
        //$prefix="pigcms_";
        $field = "p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,p.price as goods_price,m.floor_price as bargain_price,
        (p.price-m.floor_price-m.bar_total_price) as left_bargain,
        (select count(*) from ".$prefix."mall_new_bargain_tearm_user) as success_peoples,
        m.status as bargain_status,
        m.bar_total_price as already_bargain,(m.end_time-m.start_time) as left_time,(select count(*) from ".$prefix."mall_bargain_tearm_user) as bargain_peoples,m.id as team_id";
        $where[]=["a.type",'=','bargain'];
        if($uid!=0){
            $where[]=["o.user_id",'=',$uid];
        }
        if($addr==0){
            $where[]=[ 'a.end_time','>=',time()];
        }
        //$where[]=[ 's.is_del','=',0];
        switch ($status){
            case 0://全部
                break;
            case 1://1进行中
                $where[]=["m.status",'=',0];
                break;
            case 2:// 2已完成
                $where[]=["m.status",'=',1];
                break;
            case 3://3已过期
                $where[]=["m.status",'=',2];
                $where[]=[ 'm.end_time','<',time()];
                break;
        }
        if($limit==0){
            $return = $this ->alias('s')
                ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
                ->join($prefix.'mall_new_bargain_team'.' m','s.id = m.act_id')
                ->join($prefix.'mall_new_bargain_tearm_user'.' o','o.tid = m.id')
                ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
                ->field($field)
                ->where($where)
                ->order('s.id asc');
            // fdump($this->getLastSql(),"deded121312",1);
            $count = $return->count();
            $list = $return->page($page,Config::get('api.page_size'))
                ->select()->toArray();
        }else{
            $return = $this ->alias('s')
                ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
                ->join($prefix.'mall_new_bargain_team'.' m','s.id = m.act_id')
                ->join($prefix.'mall_new_bargain_tearm_user'.' o','o.tid = m.id')
                ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
                ->field($field)
                ->where($where)
                ->order('s.id asc');
            $count = 2;
            $list = $return->page($page,$count)
                ->select()->toArray();
        }


        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'list'        =>  $list
        ];
        //查不出数据置空
        if(count($return['list'])==1 && $return['list'][0]['goods_id']==0){
            $return=[];
        }
        return $return;
    }

    public function getList($uid,$status,$page,$limit=0,$addr,$is_share=0,$goods_id,$act_id){
        $prefix = config('database.connections.mysql.prefix');
        $field = "s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,p.price as goods_price,m.act_stock_num,p.score_percent,p.goods_type";
        $where[]=["a.type",'=','bargain'];
        $where[]=["a.status",'<>',2];
        $where[]=[ 'a.start_time','<',time()];
        $where[]=[ 'a.end_time','>=',time()];
        $where[]=[ 'a.is_del','=',0];
        $where[]=[ 's.is_recommend','=',1];
        $where[]=[ 's.recommend_start_time','<',time()];
        $where[]=[ 's.recommend_end_time','>=',time()];

        $return = $this ->alias('s')
            ->join($prefix.'mall_new_bargain_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
            ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->group('s.id')
            ->order('s.id asc');
        $count = $return->count();
        $list = $return->page($page,Config::get('api.page_size'))
            ->select()->toArray();
        $arr=array();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $demo['id']=$v['id'];
                $demo['goods_id']=$v['goods_id'];
                $demo['goods_image']=$v['goods_image'] ? replace_file_domain($v['goods_image']) : '';
                $demo['goods_name']=$v['goods_name'];
                $demo['act_stock_num']=$v['act_stock_num'];
                $where1=[
                    ['act_id','=',$v['id']],
                    ['goods_id','=',$v['goods_id']]
                ];

                $field="act_price";
                $bargain_price=(new MallNewBargainSku())->getBySkuId($where1,$field);
                if(empty($bargain_price)){
                    $bargain_price['act_price']=0;
                }
                $demo['bargain_price']=get_format_number($bargain_price['act_price']);
                $demo['goods_price']=get_format_number($v['goods_price']);
                $where2=[['bg.act_id','=',$v['id']]];
                $demo['bargain_peoples']=(new MallNewBargainTeamUser())->getSumBargainUser($where2);
                $demo['score_percent'] = $v['score_percent'];
                $demo['goods_type'] = $v['goods_type'];
                $arr[]=$demo;
            }
        }else{
            $arr=[];
        }

        //如果是朋友推荐分享进来的，则要对排序进行处理
        if($is_share && !empty($arr)){
            $arr1=$this->getListByOneShare($act_id,$goods_id);
            $arr=array_merge($arr1,$arr);
            $arr=array_slice($arr,0,2);
        }
        foreach ($arr as &$item){
            //消费一元获得积分
            $buy_can_get_score = (new GoodsScoreService())->getGoodsScore($item['bargain_price'],'mall',($item['goods_type']=='sku'?true:false),$item['score_percent']);
            $item['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
            $item['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
        }
        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'list'        =>  $arr
        ];
        //查不出数据置空
       /* if(empty($return['list'])){
            $return=[];
        }*/
        return $return;
    }

    public function getListDec(){
        $prefix = config('database.connections.mysql.prefix');
        $field = "s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,p.price as goods_price,m.act_stock_num";
        $where[]=["a.type",'=','bargain'];
        $where[]=[ 'a.start_time','<',time()];
        $where[]=[ 'a.end_time','>=',time()];
        $where[]=[ 'a.is_del','=',0];
        $where[]=[ 'ms.status','=',1];
        $where[]=[ 's.is_recommend','=',1];
        $where[]=[ 's.recommend_start_time','<',time()];
        $where[]=[ 's.recommend_end_time','>=',time()];

        $return = $this ->alias('s')
            ->join($prefix.'mall_new_bargain_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
            ->join($prefix.'merchant_store'.' ms','ms.store_id = a.store_id')
            ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->group('s.id')
            ->order('s.id asc');
        $list = $return->page(1,4)
            ->select()->toArray();
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['goods_image']=$v['goods_image'] ? replace_file_domain($v['goods_image']) : '';
            }
        }
        return $list;
    }
    public function getListByOneShare($act_id,$goods_id){
        $prefix = config('database.connections.mysql.prefix');
        $field = "s.id,p.name as goods_name,p.goods_id,p.store_id,p.store_id,p.image as goods_image,p.price as goods_price,p.score_percent,p.goods_type";
        $where[]=["a.type",'=','bargain'];
        $where[]=[ 'a.start_time','<',time()];
        $where[]=[ 'a.end_time','>=',time()];
        $where[]=[ 's.id','=',$act_id];
        $where[]=[ 's.goods_id','=',$goods_id];
        $return = $this ->alias('s')
            ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
            ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->order('s.id asc')
            ->find();
        if(!empty($return)) {
            $return = $return->toArray();
            $demo['id'] = $return['id'];
            $demo['goods_id'] = $return['goods_id'];
            $demo['goods_image'] =$return['goods_image'] ? replace_file_domain($return['goods_image']) : '';
            $where1[] = ['act_id', '=', $return['id']];
            $where1[] = ['goods_id', '=', $return['goods_id']];
            $field = "act_price";
            $bargain_price = (new MallNewBargainSku())->getBySkuId($where1, $field);
            if (empty($bargain_price)) {
                $bargain_price['act_price'] = 0;
            }
            $demo['bargain_price'] = $bargain_price['act_price'];
            $demo['goods_price'] = $return['goods_price'];
            $where2[] = ['bg.act_id', '=', $return['id']];
            $demo['bargain_peoples'] = (new MallNewBargainTeamUser())->getSumBargainUser($where2);
            $demo['score_percent'] = $return['score_percent'];
            $demo['goods_type'] = $return['goods_type'];
            $arr[]=$demo;
        }else{
            $arr=[];
        }

        return $arr;
    }

    /**
     * 添加数据 获取插入的数据id
     * User: chenxiang
     * Date: 2020/10/13 16:38
     * @param $data
     * @return int|string
     */
    public function add($data) {
        return $this->insertGetId($data);
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
        $result = $this ->alias('b')
            ->join($prefix.'mall_activity'.' m','b.id = m.act_id')
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
     * Date: 2020/11/9 13:43
     * @param $data
     * @param $where
     * @return MallNewBargainAct
     */
    public function updateBargain($data,$where) {
        $result = $this->where($where)->data($data)->update();
        return $result;
    }

    /**
     * @param $where
     * @param $fields
     * @return mixed
     * 获取活动及商品的信息
     * @author mrdeng
     */
    public function getBargainActSkuMsg($where,$fields){
        $prefix = config('database.connections.mysql.prefix');
        $result = $this ->alias('g')
            ->join($prefix.'mall_activity'.' a','a.act_id = g.id')
            ->join($prefix.'mall_new_bargain_sku'.' m','g.id = m.act_id')
            ->field($fields)
            ->where($where)
            ->find();
        if(!empty($result)){
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * @param $where
     * @return mixed
     * 关联商品表
     */
    public function getInfoCount($where,$field=true,$order='s.id asc',$page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $return = $this ->alias('s')
            ->join($prefix.'mall_new_bargain_sku'.' m','s.id = m.act_id')
            ->join($prefix.'mall_activity'.' a','a.act_id = s.id')
            ->join($prefix.'mall_goods'.' p','s.goods_id = p.goods_id')
            ->field($field)
            ->where($where)
            ->group('s.id')
            ->order($order);
        $ret['count'] = $return->count();
        if($page!=0 && $pageSize!=0){
            $ret['list']=$return->page($page, $pageSize)->select()->toArray();
        }

       return $ret;
    }

    public function getInfo1($where, $field, $order, $page, $pageSize)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('act')
            ->join($prefix . 'shop_goods gd', 'act.goods_id = gd.goods_id')
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        return $arr;
    }
}
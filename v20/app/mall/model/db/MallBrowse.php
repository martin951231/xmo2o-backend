<?php

/**
 * @Author: jjc
 * @Date:   2020-06-11 10:42:09
 * @desc:   商城商品浏览记录
 * @Last Modified time: 2020-06-22 14:58:35
 */
namespace app\mall\model\db;
use think\Exception;
use think\Model;

use think\facade\Config;

class MallBrowse extends Model {

	protected $autoWriteTimestamp = false;

	//获取分页数据
	public function getList($where,$page=1,$order='b.update_time desc'){
		
		// 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $field = 'b.id as record_id,b.goods_id,g.name,g.store_id,b.create_time,g.images,g.min_price,g.stock_num,g.status';
        $where[] = ['m.have_mall','=',1];
		$result = $this ->alias('b')
            ->join($prefix.'mall_goods'.' g','b.goods_id = g.goods_id')
            ->join($prefix.'merchant_store'.' m','g.store_id = m.store_id')
            ->field($field)
            ->where($where);
        $count = $result->count();

        $list  = $result
            ->order($order)
            ->select()->toArray();
        $res=array();
        /*------逻辑还有待修改------------------*/
        $group=$result->order($order)
            ->group('b.create_time')
            ->select()->toArray();
        foreach ($group as $key=>$val){
            $data=array();
            if(date("Y-m-d",$val['create_time'])==date("Y-m-d",time())){
                $data['record_day']="今天";
            }else{
                $data['record_day']=date("m-d",$val['create_time']);
                $arr=explode('-',$data['record_day']);
                $data['record_day']=$arr[0]*1 ."月". $arr[1].'日';
            }
            foreach($list as $k=>$v){
                    if($val['create_time']==$v['create_time']){
                        $data['record_list'][$k]['act_price']="";
                        $data['record_list'][$k]['create_time']=$v['create_time'];
                        $data['record_list'][$k]['store_id']=$v['store_id'];
                        $data['record_list'][$k]['record_id']=$v['record_id'];
                        $data['record_list'][$k]['goods_id']=$v['goods_id'];
                        $data['record_list'][$k]['name']=$v['name'];
                        if(!empty($v['images'])){
                            $v['images']=(explode(';',$v['images']))[0];
                        }
                        $data['record_list'][$k]['images']=$v['images'] ? replace_file_domain($v['images']) : '';
                        $data['record_list'][$k]['min_price']=get_format_number($v['min_price']);
                        $data['record_list'][$k]['stock_num']=$v['stock_num'];
                        $data['record_list'][$k]['status']=$v['status'];
                    }
            }
            $data['record_list']=array_values($data['record_list']);
            $res[]=$data;
        }
     
        if(!empty($res)){
            foreach ($res as $key=>$val){
                foreach ($val['record_list'] as $kes=>$ves){
                    $act_id=0;
                    $condition3=[//条件
                        ['s.store_id','=',$ves['store_id']],
                        ['m.goods_id','=',$ves['goods_id']],
                        ['s.status','=',1],
                        ['s.start_time','<',time()],
                        ['s.end_time','>=',time()],
                        ['s.is_del','=',0],
                        ['s.type','in',['bargain','group','limited','prepare','periodic']],
                    ];
                    $ret=(new MallActivity())->getActByGoodsID($condition3,$field='*');
                    if(empty($ret)){//周期购不能时间判断
                        $res[$key]['record_list'][$kes]['activity_type']='normal';
                        $condition4=[//条件
                            ['s.store_id','=',$ves['store_id']],
                            ['m.goods_id','=',$ves['goods_id']],
                            ['s.status','=',1],
                            ['s.type','=','periodic'],
                            ['s.is_del','=',0],
                        ];
                        $ret1=(new MallActivity())->getActByGoodsID($condition4,$field='*');
                        if(!empty($ret1)){
                            $act_id=$ret1['act_id'];
                            $res[$key]['record_list'][$kes]['activity_type']=$ret1['type'];
                            $where3=[['s.id','=',$act_id],['m.goods_id','=',$ves['goods_id']]];
                            $li=(new MallNewPeriodicPurchase())->getGoodsAndPeriodic($where3,$field="s.periodic_count,m.min_price");
                            $res[$key]['record_list'][$kes]['act_price']=$li['periodic_count']*$li['min_price'];
                        }
                    }
                    else{
                        $act_id=$ret['act_id'];
                        $res[$key]['record_list'][$kes]['activity_type']=$ret['type'];
                    }
                    $res[$key]['record_list'][$kes]['act_price']="";
                    if($res[$key]['record_list'][$kes]['activity_type'] !='normal'){//活动商品给价格
                        switch ($res[$key]['record_list'][$kes]['activity_type']){
                            case 'bargain':
                                $where=[['act_id','=',$act_id]];
                                $arr=(new MallNewBargainSku())->getBySkuId($where,$field="act_price");
                                $res[$key]['record_list'][$kes]['act_price']=get_format_number($arr['act_price']);
                                break;
                            case 'group':
                                $where=[['act_id','=',$act_id]];
                                $price=(new MallNewGroupSku())->getPice($where);
                                $res[$key]['record_list'][$kes]['act_price']=get_format_number($price);
                                break;
                            case 'limited':
                                $price=(new MallLimitedSku())->limitMinPrice($act_id,$ves['goods_id']);
                                $res[$key]['record_list'][$kes]['act_price']=get_format_number($price);
                                break;
                            case 'prepare':
                                $price=(new MallPrepareActSku())->prepareMinPrice($act_id,$ves['goods_id']);
                                $res[$key]['record_list'][$kes]['act_price']=get_format_number($price);
                                break;
                            default:
                                break;
                        }

                    }
                }
            }
        }

        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'goods_list'  =>  $res
        ];
        return $return;
	}

	public function recList($where,$page=1,$order='create_time desc'){
        // 表前缀
        $prefix = env('DATABASE_PREFIX');

        $field = 'b.id as record_id,b.goods_id,g.name,b.create_time,g.images,g.min_price,g.stock_num';

        $result = $this ->alias('b')
            ->join($prefix.'mall_goods'.' g','b.goods_id = g.goods_id')
            ->field($field)
            ->where($where);

        $count = $result->count();

        $list  = $result->page($page,Config::get('api.page_size'))
            ->order($order)
            ->select();

        $return = [
            'total_count' =>  $count,
            'page_count'  =>  intval(ceil($count/Config::get('api.page_size'))),
            'now_page'    =>  $page,
            'list'        =>  $list
        ];
        return $return;
    }

    //获取15天前数据
    public function getMyList($where,$page=1,$order='create_time desc'){
        $field='cate_second';
        //15天前的时间戳
        $begintime=strtotime(date('Y-m-d H:i:s', strtotime('- 15 day')));
        //当前时间戳
        $endtime=time();
        array_push($where,['create_time','between',[$begintime,$endtime]]);
         $result= $this->where($where)
            ->field($field)->order('id desc')->select()->toArray();
        return $result;
    }

	//更新数据
	public function _updateData($where,$data){
		$msg= $this->where($where)->update($data);
		return $msg;
	}

	//插入数据
	public function insert_record($uid,$goods_id,$cate_second,$today){
		$data = [
			'uid' => $uid,
			'goods_id' => $goods_id,
			'create_time' => $today,
            'update_time' => time(),
            'cate_second'=>$cate_second,
             'is_del'=>0
		];
        $ret=$this->save($data);
		return $ret;
	}

	//获取单条数据
	public function getOne($where){
		return $this->where($where)->find();
	}

	//获取全部数据
	public function getAll($where,$field="*"){
		return $this->where($where)->select()->toArray();
	}

}
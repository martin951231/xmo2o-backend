<?php

/**
 * @Author: jjc
 * @Date:   2020-06-11 10:39:52
 * @Last Modified by:   jjc
 * @Last Modified time: 2020-06-15 16:51:20
 */

namespace app\mall\model\service;
use app\mall\model\db\MallBrowse as  MallBrowseModel;
use app\mall\model\db\MallBrowseNew;
use app\mall\model\db\MallGoods;

class MallBrowseService{

	public $MallBrowseModel = null;
	public $MallBrowseNewModel = null;

    public function __construct()
    {
        $this->MallBrowseModel = new MallBrowseModel();
        $this->MallBrowseNewModel = new MallBrowseNew();
    }


	public function getList($condition,$page){

    	$where = $this->__dealSearchCondition($condition);
    	$list = $this->MallBrowseModel->getList($where,$page);
    	return $list;
    }

    public function recList($condition,$page){

        $where = $this->__dealSearchCondition($condition);
        $list = $this->MallBrowseModel->recList($where,$page);
        return $list;
    }

    public function getMyList($condition,$page){
        $where = $this->__dealSearchCondition($condition);
        $list = $this->MallBrowseModel->getMyList($condition,$page);
        if(count($list)<5){
            $MallCategoryService=new MallCategoryService();
            $list1 = $MallCategoryService->getLevel2($list,count($list),2);
            //不足5条，取其他的合并5条
            if(count($list)==0){
                $list=$list1;
            }else{
                $list=array_merge($list,$list1);
            }

        }
        $con=[];
        if(!empty($list)){
            foreach ($list as $key => $val){
                $con[$key]=$val['cate_second'];
            }
        }
        //var_dump("2");
        $con=array_unique($con);
        return implode(',',$con);
    }

    private function __dealSearchCondition($condition){
        $thirty_day = strtotime(date("Y-m-d",strtotime("-30 day")));
    	$where=[
    		['b.is_del','=',0],
            ['b.create_time','>',$thirty_day],
    	];
    	foreach ($condition as $key => $val) {
    		if($val[0]=='uid'){
    			$where[] = ['b.uid',$val[1],$val[2]];
    		}
    	}

    	return $where;
    }

    //更新数据，主要是在删除记录的时候使用
    public function updateData($ids,$data){
        try {
            $result = $this->MallBrowseModel->_updateData($ids,$data);
        }catch (\Exception $e) {
            return false;
        }
        
        return $result;
    }

    //新增浏览记录

    /**
     * @param $uid 用户id
     * @param $goods_id 商品id
     * @param $cate_second 商品二级分类id
     * @return bool 返回布尔值
     * @author mrdeng
     */
    public function insertRecord($uid,$goods_id,$cate_second){
    	$today = strtotime(date('Y-m-d'));
    	$exist_where = [
    		['uid','=',$uid],
    		['goods_id','=',$goods_id],
            ['cate_second','=',$cate_second],
    		['create_time','=',$today],
    	];
    	if($uid>0){
            $exist = $this->MallBrowseModel->getOne($exist_where);
            if(!$exist){
                return $this->MallBrowseModel->insert_record($uid,$goods_id,$cate_second,$today);
            }else{
                $where=[['uid','=',$uid],['goods_id','=',$goods_id],['create_time','=',$today]];
                $data['update_time']=time();
                return $this->MallBrowseModel->_updateData($where,$data);
            }
        }else{
            return false;
        }
    }
    /**
     * @param $uid 用户id
     * @param $goods_id 商品id
     * @param $cate_second 商品二级分类id
     * @return bool 返回布尔值
     * @author mrdeng
     */
    public function insertNewRecord($uid,$goods_id,$cate_second){
        return $this->MallBrowseNewModel->insert([
            'uid' => $uid,
            'goods_id' => $goods_id,
            'cate_second' => $cate_second,
            'create_time' => time()
        ]);
    }

    //获取用户所有浏览过的商品id集合
    public function getAllList($uid){
        $where = [
            ['uid','=',$uid],
        ];
        $list =  $this->MallBrowseModel->getAll($where,'goods_id');
        return array_column($list, 'goods_id');
    }


    //更新商品浏览数量(数据来自mall_browse表)
    public function updateGoodsBrowseNumOld($goods_id)
    {
        $browseNum = $this->MallBrowseModel->where('goods_id', $goods_id)->count('id');
        $MallGoods = (new MallGoods())->where('goods_id', $goods_id)->find();
        if($MallGoods){
            $MallGoods->browse_num = $browseNum;
            $MallGoods->save();
        }
    }
    //更新商品浏览数量
    public function updateGoodsBrowseNum($goods_id,$updateTodayBrowseNum=0,$updateTodayBrowseNumStatus = false)
    { 
        $MallGoods = (new MallGoods())->where('goods_id', $goods_id)->find();
        if($MallGoods){
            $MallGoods->browse_num ++;
            $MallGoods->save();
        }
        if($MallGoods && $updateTodayBrowseNumStatus){
            $MallGoods->browse_num ++;
            $MallGoods->browse_num_today = $updateTodayBrowseNum;
            $MallGoods->save();
        }
    }


}

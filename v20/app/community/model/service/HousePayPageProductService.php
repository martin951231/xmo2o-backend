<?php

namespace app\community\model\service;
use app\appoint\model\db\Appoint;
use app\appoint\model\service\AppointService;
use app\common\model\db\VillageGroupGoods;
use app\community\model\db\HousePayPageProduct;
use app\community\model\db\HousePayPageProductTovillage;
use app\mall\model\db\MallGoods;
use app\community\model\db\HouseVillage;
use think\Exception;

class HousePayPageProductService
{
    public  $goodsTypeText = ['village_group' =>'社区团购', 'mall' =>'商城', 'appoint' =>'预约'];
    public function  getProductsList($where, $field = '*', $orderby = 'id DESC', $page = 1, $page_size = 10){
        $returnArr= [
            'total' => 0,
            'list' => array(),
            'page_size' => $page_size,
        ];
        $housePayPageProductDb= new HousePayPageProduct();
        $count=$housePayPageProductDb->getCount($where);
        $returnArr['total']=$count>0 ? $count:0;
        $listObj=$housePayPageProductDb->getListData($where,$field,$orderby,$page,$page_size);
        if($listObj && !$listObj->isEmpty()){
            $list=$listObj->toArray();

            foreach ($list as $kk=>$vv){
                $goods_param=$vv['goods_param'] ? json_decode($vv['goods_param'],true):'';
                $list[$kk]['image']=$goods_param && isset($goods_param['image']) ? $goods_param['image']:'';
                $list[$kk]['status_str']=$vv['status']>0 ? '已上架':'已下架';
                $list[$kk]['goods_type_str']=isset($this->goodsTypeText[$vv['goods_type']]) ? $this->goodsTypeText[$vv['goods_type']]:'';
                unset($list[$kk]['goods_param']);
            }
            $returnArr['list']=$list;
        }
        return $returnArr;
    }
    public function  addPayPageProduct($goods,$goods_type='',$order_type='pile'){
        $returnArr= [
            'tipmsg' => '商品信息处理成功',
        ];
        if(empty($goods) || empty($goods_type)){
            return $returnArr;
        }
        $failCount=0;
        $addCount=0;
        $updateCount=0;
        $housePayPageProductDb= new HousePayPageProduct();
        $nowtime=time();
        foreach ($goods as $pvv){
            if(isset($pvv['goods_id']) && $pvv['goods_id']>0){
                 $whereArr=array(['goods_id','=',$pvv['goods_id']],['goods_type','=',$goods_type]);
                 $goodsObj=$housePayPageProductDb->getOneData($whereArr);
                 $saveArr=array('goods_type'=>$goods_type,'goods_id'=>$pvv['goods_id'],'product_name'=>$pvv['goods_name']);
                 $saveArr['xsort']=0;
                 $saveArr['order_type']=$order_type;
                 $saveArr['status']=1;
                 $saveArr['del_time']=0;
                 $saveArr['goods_param']=json_encode($pvv,JSON_UNESCAPED_UNICODE);
                if($goodsObj && !$goodsObj->isEmpty()){
                    $saveArr['update_time']=$nowtime;
                    $housePayPageProductDb->saveOneData(array('id'=>$goodsObj['id']),$saveArr);
                    $updateCount++;
                 }else{
                    $saveArr['add_time']=$nowtime;
                    $housePayPageProductDb->addOneData($saveArr);
                    $addCount++;
                 }
            }else{
                $failCount++; 
            }
        }
        $msg='';
        if($failCount<1){
            if($addCount>0 && $updateCount<1){
                $msg='商品添加成功';
            }else if($addCount>0 && $updateCount>1 ){
                $msg='成功添加'.$addCount.'商品，更新了'.$updateCount.'个商品信息';
            }else if($addCount<1 && $updateCount>1 ){
                $msg='成功更新了'.$updateCount.'商品信息';
            }
        }else{
            if($addCount>0 && $updateCount<1 ){
                $msg='成功添加'.$addCount.'商品，失败'.$failCount.'个';
            }else if($addCount>0 && $updateCount>1 ){
                $msg='成功添加'.$addCount.'商品，更新了'.$updateCount.'个商品信息，失败'.$failCount.'个';
            }else if($addCount<1 && $updateCount>1 ){
                $msg='成功更新了'.$updateCount.'商品信息，失败'.$failCount.'个';
            }else if($addCount<1 && $updateCount<1 ){
                $msg='商品信息处理失败了';
            }
            
        }
        if($msg){
            $returnArr['tipmsg']=$msg;
        }
        return $returnArr;
    }
    public function updatePayPageProductInfo($idd,$field_up=array()){
        $housePayPageProductDb= new HousePayPageProduct();
        $whereArr=array(['id','=',$idd]);
        $returnArr= [
            'err_code'=>1,
            'tipmsg' => '操作失败了！',
        ];
        $goodsObj=$housePayPageProductDb->getOneData($whereArr);
        if($goodsObj && !$goodsObj->isEmpty()){
            $saveArr=array();
            foreach ($field_up as $key=>$kv){
                if(empty($key) || !is_string($key)){
                    continue;
                }
                $key=trim($key);
                if(is_numeric($kv)){
                    $kv=intval($kv);
                }else if(is_string($kv)){
                    $kv=trim($kv);
                }
                $saveArr[$key]=$kv;
            }
            if($saveArr){
                $housePayPageProductDb->saveOneData(array('id'=>$goodsObj['id']),$saveArr);
                $returnArr['err_code']=0;
                $returnArr['tipmsg']='操作成功！';
            }
        }else{
            $returnArr['tipmsg']='操作的数据不存在！';
        }
        return $returnArr;
    }

    public function delProductInfo($idd,$goods_type=''){
        $housePayPageProductDb= new HousePayPageProduct();
        $whereArr=array(['id','=',$idd]);
        $returnArr= [
            'err_code'=>1,
            'tipmsg' => '操作失败了！',
        ];
        if($idd<1 || empty($goods_type)){
            return $returnArr;
        }
        $goodsObj=$housePayPageProductDb->getOneData($whereArr);
        if($goodsObj && !$goodsObj->isEmpty()){
            $nowtime=time();
            $saveArr=array('del_time'=>$nowtime);
            $housePayPageProductDb->saveOneData(array('id'=>$goodsObj['id']),$saveArr);
            //删除关联的小区
            $housePayPageProductTovillageDb= new HousePayPageProductTovillage();
            $whereArr=array(['pppid','=',$idd],['goods_id','=',$goodsObj['goods_id']],['goods_type','=',$goodsObj['goods_type']]);
            $whereArr[]=array('village_id','>',0);
            $whereArr[]=array('del_time','<',2);
            $housePayPageProductTovillageDb->updateOneData($whereArr,array('del_time'=>$nowtime));
            $returnArr['err_code']=0;
            $returnArr['tipmsg']='操作成功！';
        }else{
            $returnArr['tipmsg']='操作的数据不存在！';
        }
        return $returnArr;
    }
    public function getProductBindVillages($goods_id,$goods_type='',$order_type='pile'){
        $returnArr= [
            'bindProductVillages'=>array(),
            'goods_id'=>$goods_id,
            'goods_type'=>$goods_type,
        ];
        if($goods_id>0 && !empty($goods_type)){
            $housePayPageProductTovillageDb= new HousePayPageProductTovillage();
            $whereArr=array(['goods_id','=',$goods_id],['goods_type','=',$goods_type]);
            if($order_type){
                $whereArr[]=array('order_type','=',$order_type);
            }
            $whereArr[]=array('village_id','>',0);
            $whereArr[]=array('del_time','<',2);
            $bindVillagesObj=$housePayPageProductTovillageDb->getListData($whereArr,'village_id','id DESC',0,1000);
            if($bindVillagesObj && !$bindVillagesObj->isEmpty()){
                $bindVillages=$bindVillagesObj->toArray();
                $bindVillageArr=array();
                foreach ($bindVillages as $bvv){
                    $bindVillageArr[]=$bvv['village_id'];
                }
                $bindVillageArr=$bindVillageArr ? array_unique($bindVillageArr):array();
                $returnArr['bindProductVillages']=$bindVillageArr;
            }
        }
        return $returnArr;
    }
    public function  getProductBindVillageLists($param,$page=1,$limit=10){
        $returnArr= [
            'total' => 0,
            'list' => array(),
            'page_size' => $limit,
        ];
        $whereArr=array();
        if(isset($param['id']) && $param['id']){
            $whereArr[]=array('id','=',$param['id']);
        }
        if(isset($param['goods_id']) && $param['goods_id']){
            $whereArr[]=array('goods_id','=',$param['goods_id']);
        }
        if(isset($param['goods_type']) && $param['goods_type']){
            $whereArr[]=array('goods_type','=',$param['goods_type']);
        }
        if($whereArr){
            $housePayPageProductDb= new HousePayPageProduct();
            $houseVillageDb=new HouseVillage();
            $housePayPageProductTovillageDb= new HousePayPageProductTovillage();
            $payPageProduct=$housePayPageProductDb->getOneData($whereArr);
            if($payPageProduct && !$payPageProduct->isEmpty()){
                $whereTempArr=array(['pppid','=',$payPageProduct['id']],['goods_id','=',$payPageProduct['goods_id']]);
                $whereTempArr[]=array('goods_type','=',$payPageProduct['goods_type']);
                $whereTempArr[]=array('village_id','>',0);
                $whereTempArr[]=array('del_time','<',2);
                $productTovillageObj=$housePayPageProductTovillageDb->getListData($whereTempArr,'*','id DESC',$page,$limit);
                if($productTovillageObj && !$productTovillageObj->isEmpty()){
                    $total=$housePayPageProductTovillageDb->getCount($whereTempArr);
                    $returnArr['total']=$total>0 ? $total:0;
                    $productTovillage=$productTovillageObj->toArray();
                    foreach ($productTovillage as $kk=>$vv){
                        $houseVillageObj=$houseVillageDb->getInfo(array('village_id'=>$vv['village_id']),'village_id,village_name,village_address');
                        $village_name='';
                        $village_address='';
                        if($houseVillageObj && !$houseVillageObj->isEmpty()){
                            $village_name=$houseVillageObj['village_name'];
                        }
                        $productTovillage[$kk]['goods_type_str']=isset($this->goodsTypeText[$vv['goods_type']]) ? $this->goodsTypeText[$vv['goods_type']]:'';
                        $productTovillage[$kk]['product_name']=$payPageProduct['product_name'];
                        $productTovillage[$kk]['village_name']=$village_name;
                        $productTovillage[$kk]['add_time_str']=$vv['add_time'] ? date('Y-m-d H:i:s',$vv['add_time']):'';
                    }
                    $returnArr['list']=$productTovillage;
                }
            }
        }
        return $returnArr;
    }
    public function bindProductToVillages($id=0,$opt_type=0,$goods_id=0,$village_id=0,$goods_type=''){
        $returnArr= [
            'err_code'=>1,
            'tipmsg' => '操作失败了！',
        ];
        if(empty($goods_id) || empty($village_id)|| empty($goods_type)){
            $returnArr['tipmsg']='参数错误！';
            return $returnArr;
        }
        if(!in_array($opt_type,array(1,2))){
            $returnArr['tipmsg']='操作错误！';
            return $returnArr;
        }
        $housePayPageProductDb= new HousePayPageProduct();
        $whereArr=array();
        if($id>0){
            $whereArr[]=array('id','=',$id);
        }
        if($goods_id>0){
            $whereArr[]=array('goods_id','=',$goods_id);
        }
        if($goods_type){
            $whereArr[]=array('goods_type','=',$goods_type);
        }
        $payPageProduct=$housePayPageProductDb->getOneData($whereArr);
        $order_type='';
        $pppid=0;
        if($payPageProduct && !$payPageProduct->isEmpty()){
            $order_type=$payPageProduct['order_type'];
            $pppid=$payPageProduct['id'];
        }
        if($pppid<1){
            $returnArr['tipmsg']='商品数据不存在！';
            return $returnArr;
        }
        $village_ids=array();
        if(is_array($village_id)){
            $village_ids=$village_id;
        }else{
            $village_ids[]=$village_id;
        }
        $housePayPageProductTovillageDb= new HousePayPageProductTovillage();
        foreach ($village_ids as $village_id_v){
            $whereArr=array(['pppid','=',$pppid],['goods_id','=',$goods_id],['goods_type','=',$goods_type]);
            $whereArr[]=array('village_id','=',$village_id_v);
            $whereArr[]=array('del_time','<',2);
            $bindVillageObj=$housePayPageProductTovillageDb->getOneData($whereArr,'*');
            $saveArr=array('goods_id'=>$goods_id,'village_id'=>$village_id_v,'goods_type'=>$goods_type,'del_time'=>'0');
            $saveArr['pppid']=$pppid;
            if($order_type){
                $saveArr['order_type']=$order_type;
            }
            $idd=0;
            if($bindVillageObj && !$bindVillageObj->isEmpty()){
                if($opt_type==2){
                    //解绑
                    $saveArr['del_time']=time();
                }
                $idd=$housePayPageProductTovillageDb->saveOneData(array('id'=>$bindVillageObj['id']),$saveArr);
            }else if($opt_type==1){

                $saveArr['add_time']=time();
                $idd=$housePayPageProductTovillageDb->addOneData($saveArr);
            }
        }
        $returnArr['err_code']=0;
        $returnArr['tipmsg']='操作成功';
        $returnArr['idd']=$idd;
        return $returnArr;
    }

    public function getVillageRecommendGoods($village_id,$order_type='pile'){
        $returnArr=array('village_id'=>$village_id,'list'=>array());
        if($village_id<1){
            return $returnArr;
        }
        $housePayPageProductTovillageDb= new HousePayPageProductTovillage();
        $whereArr=array(['tv.village_id','=',$village_id]);
        if($order_type){
            $whereArr[]=array('p.order_type','=',$order_type);
            $whereArr[]=array('tv.order_type','=',$order_type);
        }
        $whereArr[]=array('tv.del_time','<',1);
        $whereArr[]=array('p.del_time','<',1);
        $whereArr[]=array('p.status','>',0);

        $recommendGoodsObj=$housePayPageProductTovillageDb->getVillageRecommendGoods($whereArr,'p.*','xsort DESC,id DESC',1,2);
        if($recommendGoodsObj && !$recommendGoodsObj->isEmpty()){
            $recommendGoods=$recommendGoodsObj->toArray();
            $goodsList=array();
            foreach ($recommendGoods as $item){
                switch ($item['goods_type']) {
                    case 'village_group':
                        $villageGroupGoods = VillageGroupGoods::where('goods_id', $item['goods_id'])
                            ->field('goods_id, name, price, image')
                            ->find();
                        $villageGroupGoods['jump_link'] = cfg('site_url').'/packapp/community_group/index.html?c=Village_group&a=index#/goods?goodsId='.$villageGroupGoods['goods_id'];
                        $villageGroupGoods['goods_type']='village_group';
                        $villageGroupGoods['goods_type_txt']=cfg('village_group_alias') ?? '社区团购';
                        $villageGroupGoods['image'] = replace_file_domain($villageGroupGoods['image']);
                        $goodsList[]=$villageGroupGoods;
                        break;
                    case 'appoint':
                        $appoint = Appoint::where('appoint_id', $item['goods_id'])
                            ->alias('a')
                            ->join('appoint_category ac ','a.cat_id = ac.cat_id')
                            ->field('a.appoint_id goods_id, a.appoint_name name, a.appoint_price price, a.pic image, ac.is_autotrophic')
                            ->find();
                        if ($appoint) {
                            $baseUrl =  cfg('system_type') == 'village' ? '/packapp/village/' : '/packapp/plat/';
                            $autotrophic = empty($appoint['is_autotrophic']) ? 'productDetail' : 'goodsDetail';
                            $appoint['jump_link'] = cfg('site_url')."{$baseUrl}pages/appoint/goods/{$autotrophic}?appoint_id={$appoint['goods_id']}&is_autotrophic={$appoint['is_autotrophic']}";
                            $picArr = explode(';', $appoint['image']);
                            $appoint['image'] = (new AppointService())->getImageByPath($picArr[0], 's');
                        }
                        $appoint['goods_type']='appoint';
                        $appoint['goods_type_txt']=cfg('appoint_alias_name') ?? '预约';
                        $goodsList[]=$appoint;
                        break;
                    case 'mall':
                        $mallGoods = MallGoods::where('goods_id', $item['goods_id'])
                            ->field('goods_id, name, price, image')
                            ->find();
                        $mallGoods['jump_link'] = cfg('site_url').'/packapp/plat/pages/shopmall_third/commodity_details?goods_id='.$mallGoods['goods_id'];
                        $mallGoods['goods_type']='mall';
                        $mallGoods['goods_type_txt']=cfg('mall_alias_name') ?? '商城';
                        $mallGoods['image'] = replace_file_domain($mallGoods['image']);
                        $goodsList[]=$mallGoods;
                        break;
                }
            }
            $returnArr['list']=$goodsList;
        }
        return $returnArr;
    }
}
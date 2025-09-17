<?php
/*用友erp系统对接api*/
class ErpapiAction extends BaseAction{
	//接口密钥
	private $key;
    public function __construct(){
        parent::__construct();
        $this->key=$this->config['yongyou_erp_key'];
    }

	/*接口校验和请求数据格式化*/
    private function api_init(){
        $postStr=file_get_contents("php://input");
        fdump($postStr,'erp_data',1);
        parse_str($postStr);
        $logistics_interface=urldecode($logistics_interface);
        $data_digest=urldecode($data_digest);
        
    	// 格式化xml格式的数据
    	$strPost=simplexml_load_string($logistics_interface);
    	$postData=json_decode(json_encode($strPost),true);
        if($this->config['open_yongyou_erp']!=1){
            $this->ajaxXmlReturn($postData['@attributes']['service'],'ERR','',L_('接口未开放'));            
        }
        $tdata_digest=base64_encode(md5($logistics_interface.$this->key));
        if($data_digest!=$tdata_digest){
    		$this->ajaxXmlReturn($postData['@attributes']['service'],'ERR','',L_('签名错误'));
    	}
    	return $postData;
	}

	/*商品档案同步*/
    public function save_store_goods(){
    	//初始化
        $postData=$this->api_init();

        $mer_id=I('get.mer_id',0,'intval');
    	$store_id=I('get.store_id',0,'intval');
    	if(empty($mer_id)){
    		$this->ajaxXmlReturn('ITEM_SERVICE','ERR','',L_('缺少商家ID'));	
    	}
    	if(empty($store_id)){
    		$this->ajaxXmlReturn('ITEM_SERVICE','ERR','',L_('缺少店铺ID'));
    	}    	
    	$list=$postData['Body']['ItemRequest']['Items'];
    	$result=array();
    	$model=M("Shop_goods");
    	$category_model=M("Goods_category");

    	$goods_list=$model->where(array('store_id'=>$store_id))->getfield('number,spec_value,goods_id',true);
		$head='OK';
    	foreach ($list as $k => $v) {
            $info=$goods_list[$v['BarCode']];
    		$res_arr=array();
    		$res_arr['SkuNo']=$v['SkuNo'];
    		if(empty($info)){
    			$menu_arr=explode('/', $v['GoodsCat']);
    			$data=array();
                $data['store_id']=$store_id;
                $data['sort_id']=$this->get_sort_id($menu_arr,$store_id);
    			/*仅有二级分类*/
    			$fid=0;
                foreach ($menu_arr as $k3 => $v3) {
    				if($k3>1){
    					break;
    				}
    				$where=array('name'=>$v3);
    				$where['fid']=$fid;
    				$cid=$category_model->where($where)->getfield('id');
					if(empty($cid)){
						$category_data=array('fid'=>$fid,'name'=>$v3,'status'=>1,'dateline'=>$_SERVER['REQUEST_TIME']);
						$cid=$category_model->data($category_data)->add();
					}
					$fid=$cid;
					if($k3==0){
						$data['cat_fid']=$cid;
					}else{
						$data['cat_id']=$cid;
					}
    			}
    			$data['name']=$v['GoodsName'];
    			$data['number']=$v['BarCode'];
                $data['SkuNo']=$v['SkuNo']; //商品编号（对应规格编号）
    			$data['cost_price']=$v['Cost'];
    			$data['price']=floatval($v['SalePrice']);
                $data['unit']=$v['Unit'];
                $data['sell_type']=$v['isWeightGoods']?$v['isWeightGoods']:1; //1：非称重商品,2：称重商品
                if($data['sell_type']==2){
                    $scale=$this->get_weight_scale($data['unit']);
                    $data['unit_price']=$data['price']/$scale*500;
                    $data['weight']=$v['Weight']*$scale;
                    $data['weight_unit']=1; //单位统一转换为g
                }
                $data['stock_num']=0;   //初始化库存为0
                $data['original_stock']=0;
                $data['last_time']=$_SERVER['REQUEST_TIME'];
                $goods_id=$model->data($data)->add();
    			/*记录商品的规格信息*/
                if(!empty($v['Spec']) && $data['sell_type']==1){
                    $this->add_goods_reg($store_id,$goods_id,$v['Spec'],$v['Cost'],$v['SalePrice'],$v['Weight'],$v['SkuNo']);
                }
    			$res_arr['Result']=1;
    			$res_arr['Note']='';
    		}else{
                $data=array();
                $data['name']=$v['GoodsName'];
                $data['number']=$v['BarCode'];
                $data['SkuNo']=$v['SkuNo']; //商品编号（对应规格编号）
                $data['cost_price']=$v['Cost'];
                $data['price']=floatval($v['SalePrice']);
                $data['unit']=$v['Unit'];
                $data['sell_type']=$v['isWeightGoods']?$v['isWeightGoods']:1; //1：非称重商品,2：称重商品
                if($data['sell_type']==2){
                    $scale=$this->get_weight_scale($data['unit']);
                    $data['unit_price']=$data['price']/$scale*500;
                    $data['weight']=$v['Weight']*$scale;
                    $data['weight_unit']=1; //单位统一转换为g
                }
                $data['last_time']=$_SERVER['REQUEST_TIME'];
                $goods_id=$info['goods_id'];
                $res=$model->where(array('goods_id'=>$goods_id))->save($data);
                if($res!==false){
                    /*记录商品的规格信息*/
                    if(!empty($v['Spec']) && $data['sell_type']==1){
                        $this->add_goods_reg($store_id,$goods_id,$v['Spec'],$v['Cost'],$v['SalePrice'],$v['Weight'],$v['SkuNo'],$info['spec_value']);
                    }
                    $res_arr['Result']=1;
                    $res_arr['Note']='';
                }else{
                    $res_arr['Result']=2;
                    $res_arr['Note']=L('更新失败');
                    $head='PART';
                }
    		}
    		$result[]=$res_arr;
    	}
    	/*格式化返回的信息*/
    	$body='<temResponse><Items>';
    	foreach ($result as $k => $v) {
            $body.='<Item>';
    		foreach ($v as $k2 => $v2) {
    			$body.='<'.$k2.'>'.$v2.'</'.$k2.'>';
    		}
            $body.='</Item>';            
    	}
    	$body.='</Items></temResponse>';
        $this->ajaxXmlReturn('ITEM_SERVICE',$head,$body,'');
    }

    //获取单位转换比例
    private function get_weight_scale($unit=''){
        $scale=1;
        //防止字母大小写问题
        $unit=strtolower($unit);
        if($unit==L_('斤')){
            $scale=500;
        }elseif($unit==L_('公斤') || $unit=='kg'){
            $scale=1000;
        }
        return $scale;
    }

    //获取当前商品在快店的分类
    public function get_sort_id($menu_arr,$store_id){
        $model=M('Shop_goods_sort');
        $where=array();
        $where['store_id']=$store_id;
        $fid=0;
        $count=count($menu_arr);
        foreach ($menu_arr as $k => $v) {
            $where['level']=$k+1;
            $where['sort_name']=$v;
            $where['fid']=$fid;
            $sort_id=$model->where($where)->getfield('sort_id');
            if(empty($sort_id)){
                $data=$where;
                $data['status']=1;
                if($k+1==$count){
                    $data['operation_type']=0;    
                }else{
                    $data['operation_type']=1;    
                }
                
                $sort_id=$model->data($data)->add();
            }
            $fid=$sort_id;
        }
        return $sort_id;
    }

    /*写入商品规格*/
    public function add_goods_reg($store_id,$goods_id='',$spec_info='',$cost_price=0,$price=0,$weight=0,$SkuNo='',$spec_value=''){
    	$spec_str='';
    	if(empty($spec_info)){
    		return $spec_str;
    	}
        $weight=empty($weight)?0:$weight;
        $price=round($price,2);
    	$spec_model=M('Shop_goods_spec');
    	$spec_value_model=M('Shop_goods_spec_value');
    	$spec_arr=explode(';', $spec_info);
        //之前的规格
        $where=array();
        $where['store_id']=$store_id;
        $where['goods_id']=$goods_id;
        $old_spec_arr=$spec_model->where($where)->getfield('name,id',true);
        $old_spec_arr=(array)$old_spec_arr;
        
        $spec_value_arr=array();
        /*写入规格和规格值*/
        foreach ($spec_arr as $k => $v) {
            $reg_arr=explode(':', $v);
            if(!empty($old_spec_arr)){
                $sid=$old_spec_arr[$reg_arr['0']];
                if($sid>0){
                    unset($old_spec_arr[$reg_arr['0']]);
                }else{
                    $spec_data=array('store_id'=>$store_id,'goods_id'=>$goods_id,'name'=>$reg_arr['0']);
                    $sid=$spec_model->data($spec_data)->add();
                }
            }else{
                $spec_data=array('store_id'=>$store_id,'goods_id'=>$goods_id,'name'=>$reg_arr['0']);
                $sid=$spec_model->data($spec_data)->add();
            }
            if($sid>0){
                $spec_value_data=array('sid'=>$sid);
                $old_value_arr=$spec_value_model->where($spec_value_data)->getfield('name,id',true);
                $spec_value_id=$old_value_arr[$reg_arr['1']];
                if($spec_value_id>0){
                   //unset($old_value_arr[$reg_arr['1']]); 
                }else{
                    $spec_value_data['name']=$reg_arr['1'];
                    $spec_value_id=$spec_value_model->data($spec_value_data)->add();
                }
                $spec_value_arr[]=$spec_value_id;
            }
        }
        if(!empty($old_spec_arr)){
            $spec_model->where(array('id'=>array('in',$old_spec_arr)))->delete();
            $spec_value_model->where(array('sid'=>array('in',$old_spec_arr)))->delete();
        }
    	$spec_str.=implode(':', $spec_value_arr);
        $stock_num=0;
        if(!empty($spec_value)){
            $last_arr=explode('|', $spec_value);
            if($last_arr['0']==$spec_str){
                $last_arr2=explode(':', $last_arr['1']);
                $stock_num=$last_arr2['3'];     //之前同步的商品库存
            }
        }
    	$spec_str.='|'.$cost_price.':'.$price.':0:'.$stock_num.':'.$cost_price.':0:'.$weight;
    	$spec_str.='||'.$SkuNo;
    	$res=M('Shop_goods')->data(array('goods_id'=>$goods_id,'spec_value'=>$spec_str))->save();
    	return $res;
    }

    /*可售量同步api*/
	public function save_goods_num(){
		//初始化
    	$postData=$this->api_init();
        $mer_id=I('get.mer_id',0,'intval');
        $store_id=I('get.store_id',0,'intval');
        if(empty($mer_id)){
            $this->ajaxXmlReturn('ITEM_SERVICE','ERR','','缺少商家ID'); 
        }
        if(empty($store_id)){
            $this->ajaxXmlReturn('ITEM_SERVICE','ERR','','缺少店铺ID');
        }

        $model=M("Shop_goods");
    	$list=$postData['Body']['StockChangeRequest']['Items']['Item'];
        if(empty($list)){
            $this->ajaxXmlReturn($postData['@attributes']['service'],'ERR','',L_('缺少商品信息'));
        }
    	$result=array();
    	foreach ($list as $k => $v) {
    		$where=array();
            $where['store_id']=$store_id;
    		$where['SkuNo']=$v['SkuNo'];
    		$info=$model->where($where)->find();
            $res_arr=array();
    		$res_arr['SkuNo']=$v['SkuNo'];
    		if(!empty($info)){
                if(!empty($info['spec_value'])){
                    $spec_arr=explode('|', $info['spec_value']);
                    $reg_arr=explode(':', $spec_arr['1']);
                    $reg_arr['3']=$v['Quantity'];
                    
                    $reg_str=implode(':', $reg_arr);
                    $spec_arr['1']=$reg_str;
                    $spec_str=implode('|', $spec_arr);
                    $info['spec_value']=$spec_str;
                }
                $info['stock_num']=$v['Quantity'];
                $info['original_stock']=$v['Quantity'];
    			if($info['sell_type']==2){
                    $scale=$this->get_weight_scale($info['unit']);
                    $info['stock_weight']=$v['Quantity']*$scale;
                    $info['original_weight']=$v['Quantity']*$scale;
                }
                $res=$model->data($info)->save();
    			if($res!==false){
    				$res_arr['Result']=1;
    				$res_arr['Note']='';	
    			}else{
    				$res_arr['Result']=2;
    				$res_arr['Note']=L_('更新失败！');
    			}
    		}else{
    			$res_arr['Result']=2;
    			$res_arr['Note']=L_('商品不存在');
    		}
    		$result[]=$res_arr;
    	}
    	/*格式化返回的信息*/
    	$body='<StockChangeResponse><Items>';
    	foreach ($result as $k => $v) {
            $body.='<Item>';
            foreach ($v as $k2 => $v2) {
    			$body.='<'.$k2.'>'.$v2.'</'.$k2.'>';
    		}
            $body.='</Item>';
        }
    	$body.='</Items></StockChangeResponse>';
    	$this->ajaxXmlReturn('STOCK_CHANGE_SERVICE','OK',$body,'');
	}

    /*返回响应数据*/
    public function ajaxXmlReturn($service='',$Head='',$Body='',$Error=''){
    	$data=array();
    	if(!empty($Head)){
    		$data['Head']=$Head;
    	}
    	if(!empty($Body)){
    		$data['Body']=$Body;
    	}
    	if(!empty($Error)){
    		$data['Error']=$Error;	
    	}
    	$responseStr='<Response service="'.$service.'" lang="zh-CN">';
    	foreach ($data as $k => $v) {
    		$responseStr.='<'.$k.'>'.$v.'</'.$k.'>';
    	}
    	$responseStr.='</Response>';
    	exit($responseStr);
	}
}
?>
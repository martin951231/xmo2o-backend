<?php
class SearchAction extends BaseAction{
	public function index()
    {
		//热门搜索词
		$type = I('type') == 'meal' ? 1 : 0;
    	//$search_hot_list = D('Search_hot')->get_list(18,$type,true);
		$search_hot_list = D('Search_hot')->get_list(18,-1,true);
        if(empty($search_hot_list)){
			$this->returnCode(0,[]);
            $this->returnCode('20000001');
        }
        foreach($search_hot_list as $k=>$v){
            $search_hot_list[$k]['url'] =   $this->config['site_url'].urldecode($v['url']);
        }
        $this->returnCode(0,$search_hot_list);
	}
	public function group()
    {
        if($this->app_version > 55){
            $this->group_version_55();
            exit();
        }
		$keywords = htmlspecialchars(I('w'));
//        $_GET['page']   =   I('page');
//		$group_category = D('Group_category')->field('`cat_url`')->where(array('cat_name'=>$keywords,'cat_status'=>'1'))->find();
//		if($group_category['cat_url']){
//			redirect(U('Group/index',array('cat_url'=>$group_category['cat_url'],'w'=> urlencode($keywords))));exit;
//		}
		$sort = I('sort','default');
		//得到搜索的团购列表
        C('config.group_page_row',10);
		$group_return = D('Group')->get_group_list_by_keywords($keywords,$sort,true,'',0,0,1);
        if($group_return['group_list']  == null){
            $this->returnCode('20000003');
        }else{
            foreach($group_return['group_list'] as $key => $arr){
                $group_return['group_list'][$key]['url']  =   $this->config['site_url'].str_replace('appapi.php','wap.php',$arr['url']);
                $group_return['group_list'][$key]['price']   =   rtrim(rtrim(number_format($arr['price'],2,'.',''),'0'),'.');
                $group_return['group_list'][$key]['wx_cheap']   =   rtrim(rtrim(number_format($arr['wx_cheap'],2,'.',''),'0'),'.');
                $group_return['group_list'][$key]['sale_count']   =   $arr['sale_count']+$arr['virtual_num'];
            }
            $this->returnCode(0,$group_return);
        }
	}

    public function check_group_category(){
        $keywords = htmlspecialchars(I('w'));
        $group_category = D('Group_category')->field('`cat_url`')->where(array('cat_name'=>$keywords,'cat_status'=>'1'))->find();
        if($group_category['cat_url']){
            $arr['cat_url'] =$group_category['cat_url'];
            $arr['is_group_category'] =1;
            $this->returnCode(0,$arr);
        }else{
            $arr['is_group_category'] =0;
            $this->returnCode(0,$arr);
        }
    }
    public function group_version_55()
    {
        $keywords = htmlspecialchars(I('w'));
        $_GET['page']   =   I('page');
        $sort = I('sort','default');
        //得到搜索的团购列表
        C('config.group_page_row',min(I('page_size',10),20));
        $group_return = D('Group')->get_group_list_by_keywords($keywords,$sort,true);
        if($group_return['group_list']  == null){
            $this->returnCode('20000003');
        }else{
            foreach($group_return['group_list'] as $key => $arr){
                $group_return['group_list'][$key]['url']  =   $this->config['site_url'].str_replace('appapi.php','wap.php',$arr['url']);
                $group_return['group_list'][$key]['price']   =   rtrim(rtrim(number_format($arr['price'],2,'.',''),'0'),'.');
                $group_return['group_list'][$key]['wx_cheap']   =   rtrim(rtrim(number_format($arr['wx_cheap'],2,'.',''),'0'),'.');
            }
            $this->returnCode(0,$group_return);
        }
    }
	
	public function meal()
	{
		$keywords = htmlspecialchars(I('w'));
		$sort = I('sort','default');
        $_GET['page']   =   I('page');
		//得到搜索的店铺列表
        C('config.group_page_row',min(I('page_size',10),20));
		$return = D('Merchant_store')->get_list_by_search($keywords, $sort, true);
        if($return['group_list'] == null){
            $this->returnCode('20000001');
        }else{
            foreach($return['group_list'] as $key => $arr){
                // $return['group_list'][$key]['mean_money'] = $arr['permoney'];//使用店铺人均消费替换注释了的餐饮人均消费
                //$return['group_list'][$key]['url']  =  C('config.site_url'). D('House_village')->base_url . 'pages/store/homePage?store_id=' . $arr['store_id'];
                $return['group_list'][$key]['url']  =  get_base_url('pages/store/v1/home/index?store_id=' . $arr['store_id'], true);
            }
            $this->returnCode(0,$return);
        }
	}
	
	public function appoint()
	{
        //搜索关键字
		$keywords = htmlspecialchars(I('w'));
        //排序方式
		$sort = I('sort','default');
        $_GET['page']   =   I('page');
		//得到搜索的店铺列表
        C('config.appoint_page_row',min(I('page_size',10),20));

        //商家入驻缴费开关
		$merchant_settle_in = C('customization.merchant_settle_in');

		$return = D('Appoint')->get_list_by_search($keywords, $sort, true);
        if($return['group_list'] == null){
            $this->returnCode('20046003');
        }else{
            foreach($return['group_list'] as $key => &$arr){
                $return['group_list'][$key]['url'] = (substr($arr['url'], 0, 4) == 'http' ? '' : $this->config['site_url']) . str_replace('appapi.php', 'wap.php', $arr['url']);
                $return['group_list'][$key]['payment_money']   =   rtrim(rtrim(number_format($arr['payment_money'],2,'.',''),'0'),'.');
                $return['group_list'][$key]['appoint_sum'] += intval($arr['virtual_sales']);
                
                if (empty($arr['is_appoint_price']) && floatval($arr['appoint_price'])<=0) {
                    $arr['appoint_price'] = '面议';
                }
                $specPrice = D('Appoint_product')
                    ->where(['appoint_id' => $arr['appoint_id']])
                    ->field('price')
                    ->select();
                $specPrice = array_column($specPrice, 'price');
                if(!empty($specPrice)){
                    $arr['appoint_price'] = min($specPrice);
                }
                //商家入驻缴费标志
                if($merchant_settle_in){
				    $return['group_list'][$key]['settle_in_sign'] = $return['group_list'][$key]['settle_in_money'] > 0 ? $return['group_list'][$key]['settle_in_sign'] : '';
				    unset($return['group_list'][$key]['settle_in_money']);
                }

            }
            $this->returnCode(0,$return);
        }
	}


}
?>
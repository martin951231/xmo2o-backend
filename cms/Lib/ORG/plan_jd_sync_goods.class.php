<?php

class plan_jd_sync_goods extends plan_base{
    public function runTask(){
        //如果关闭对接则直接返回ture
        if(C('config.is_open_jd_goods')==0){
            return true;
        }
        //初始化
        G('begin');
        set_time_limit(0);
        $access_token = C('config.jd_access_token');
		fdump_sql('时间：'.date('Y-m-d H:i:s',time()),'jd_start');
		
        $data_sku['token'] = $access_token;
        $data_sku['pageNo'] = 1;
        $data_detail['token'] = $access_token;
        $data_detail['isShow'] = true;
        $data_category['token'] = $access_token;
        $data_price['token'] = $access_token;
        $data_price['queryExts'] =  'containsTax';
        $data_image['token'] = $access_token;

        $url = "https://bizapi.jd.com/api/product/getPageNum";
        $url_sku = "https://bizapi.jd.com/api/product/getSkuByPage";
        $url_detail = "https://bizapi.jd.com/api/product/getDetail";
        $url_category = "https://bizapi.jd.com/api/product/getCategory";
        $url_price = "https://bizapi.jd.com/api/price/getSellPrice";
        $url_image = 'https://bizapi.jd.com/api/product/skuImage';

        $shop_goods_db = M('Shop_goods');
        //先读取出所有京东分类
        $category_db = M('Goods_category');
        $res = $category_db->where(array('jd_id'=>array('neq',0)))->select();
        $cate_arr = array();
        foreach($res as $k =>$v){
            $this->keepThread(); //保持单进程
           $cate_arr[$v['jd_id']] = intval($v['id']);
        }
        //所有同步京东的店铺
        $mers = M('Merchant')->field('mer_id')->where(array('is_open_jd'=>'1'))->select();
        $sql = "SELECT s.store_id From ". C('DB_PREFIX') ."merchant_store_shop AS s LEFT JOIN ". C('DB_PREFIX') ."merchant_store as m on m.store_id = s.store_id ";
        $sres = [];
        foreach($mers as $mer){
            $this->keepThread(); //保持单进程
            $sql_where = "WHERE m.status=1 AND m.mer_id =".$mer['mer_id'];
            $sql_where = $sql.$sql_where;
            $res_shop =  M('')->query($sql_where);
            $sres = array_merge($sres,$res_shop);
            unset($sql_where);
        }
        //京东分类
        $shop_cates = array();
        $shop_goods_cate_db = M('Shop_goods_sort');
        $data['sort_name'] = '京东直供';
        $data['status'] = 1;
        $data['fid'] = 0;
        foreach($sres as $shop){
            $this->keepThread(); //保持单进程
            $shop_cate = $shop_goods_cate_db ->where(array('sort_name'=>'京东直供','store_id'=>$shop['store_id']))->getField('sort_id');
            if($shop_cate){
                $shop_cates[$shop['store_id']] = $shop_cate;
            }else{
                $data['store_id'] = $shop['store_id'];
                $shop_cate = $shop_goods_cate_db ->add($data);
                $shop_cates[$shop['store_id']] = $shop_cate;
            }
        }
        //end
		$this->keepThread(); //保持单进程

        import('ORG.Net.Http');
        $data_token = array(
            'token'=>$access_token
        );
        //商品池
        $return = Http::curlPost($url,$data_token);
        if($return['success']==true){
		   fdump_sql('请求商品池成功：'.date('Y-m-d H:i:s',time()),'jd_log');
           $page_nums = $return['result'];
           foreach($page_nums as $num){
				$this->keepThread(); //保持单进程
				$data_sku['pageNum'] = $num['page_num'];
				//获取商品sku_ids
				$return_sku = Http::curlPost($url_sku,$data_sku);
				if($return_sku['success']==false){
					$arr = json_encode($return_sku);
					fdump_sql($arr,'jd_skuIds');
				}
				$this->keepThread(); //保持单进程

				foreach($return_sku['result']['skuIds']  as $key => $sku){
					$this->keepThread(); //保持单进程
                    $data_detail['sku'] = $sku;
                    /**
                     * 获取商品详情
                     */
                    $return_detail = Http::curlPost($url_detail,$data_detail);
					if($return_detail['success']==false){
                        $return_detail['data_detail'] = $data_detail;
						$arr = json_encode($return_detail);
						fdump_sql($arr,'jd_detail');
                        if($return_detail['resultCode']==2007 || $return_detail['resultCode']==5001){
                            fdump_sql('---任务中止---','jd_task_end');
                            G('end');
                            $time = G('begin','end',6);
                            $mem = G('begin','end','m');
                            fdump_sql('时间：'.$time.' 内存：'.$mem ,'jd_syncInfo');
                            return true;
                        }
						continue;
					}
					$this->keepThread(); //保持单进程
                    //判断已有商品的店铺
                    $add_shops = array();
                    foreach($sres as $shop){
                        if(!$shop_goods_db->where(array('store_id'=>$shop['store_id'],'jd_sku_id'=>$return_detail['result']['sku']))->find()){
							$add_shops[] = $shop;
                        }
						$this->keepThread(); //保持单进程
                    }
                    if(empty($add_shops)){
                        continue;
                    }

                    $category = explode(';',$return_detail['result']['category']);
                    $fid = $cate_arr[$category[0]];
                    $cid = $cate_arr[$category[2]];
                    if(!$fid){
                        //一级分类
                        $data_category['cid'] = $category[0];
                        $return_cate = Http::curlPost($url_category,$data_category);
                        $cate_jd['jd_id'] = $return_cate['result']['catId'];
                        $cate_jd['fid'] = 0;
                        $cate_jd['name'] = $return_cate['result']['name'];
                        $cate_jd['status'] = 1;
                        $cate_jd['dateline'] = time();
                        $fid = $category_db->add($cate_jd);
                        $cate_arr[$return_cate['result']['catId']] = $fid;
						$this->keepThread(); //保持单进程
                        //二级分类
                        $data_category['cid'] = $category[2];
                        $return_cate = Http::curlPost($url_category,$data_category);
                        $cate_jd['jd_id'] = $return_cate['result']['catId'];
                        $cate_jd['fid'] = $fid;
                        $cate_jd['name'] = $return_cate['result']['name'];
                        $cate_jd['status'] = 1;
                        $cate_jd['dateline'] = time();
                        $cid = $category_db->add($cate_jd);
                        $cate_arr[$return_cate['result']['catId']] = $cid;
						$this->keepThread(); //保持单进程
                    }else{
                        if(!$cid){
                            $data_category['cid'] = $category[2];
                            $return_cate = Http::curlPost($url_category,$data_category);
                            $cate_jd['jd_id'] = $return_cate['result']['catId'];
                            $cate_jd['fid'] = $fid;
                            $cate_jd['name'] = $return_cate['result']['name'];
                            $cate_jd['status'] = 1;
                            $cate_jd['dateline'] = time();
                            $cid = $category_db->add($cate_jd);
                            $cate_arr[$return_cate['result']['catId']] = $cid;
							$this->keepThread(); //保持单进程
                        }
                    }
					/**
					 * 增加商品
					 */
					//请求价格
					$data_price['sku']=$return_detail['result']['sku'];
					$return_price = Http::curlPost($url_price,$data_price);
					//价格加成
					$percent = floatval(C('config.jd_price_add_percent'));
                    if($return_price['result'][0]['price']>=1000 && $return_price['result'][0]['price']<2000){
                        $percent = floatval(C('config.jd_price_add_percent_gt1000'));
                    }elseif($return_price['result'][0]['price']>=2000 && $return_price['result'][0]['price']<3000){
                        $percent = floatval(C('config.jd_price_add_percent_gt2000'));
                    }elseif($return_price['result'][0]['price']>=3000){
                        $percent = floatval(C('config.jd_price_add_percent_gt3000'));
                    }
					if($percent <= 0){
						$percent = 1;
					}else{
						$percent = 1 + ($percent / 100);
					}
					$goods['price'] = round($return_price['result'][0]['price'] * $percent,2);
					$goods['cost_price'] = $return_price['result'][0]['price'];
					//end
					$this->keepThread(); //保持单进程
					//请求图片
					$data_image['sku']=$return_detail['result']['sku'];
					$return_images = Http::curlPost($url_image,$data_image);
					$img_arr = $return_images['result'][$return_detail['result']['sku']];
					$images = '';
					foreach($img_arr as $kk => $img){
						$images .= $img['path'].';';
						if($kk == 4) break;
					}
					$goods['image'] = substr($images,0,-1);
					//end
					$this->keepThread(); //保持单进程
					
					$goods['name'] = $return_detail['result']['name'];
					$goods['unit'] = $return_detail['result']['saleUnit'] ? $return_detail['result']['saleUnit'] : '';
					$goods['des'] = $return_detail['result']['param'].$return_detail['result']['appintroduce'];
					$goods['status'] = 1;
					$goods['stock_num'] = -1;
					$goods['original_stock'] = -1;
					$goods['number'] = $return_detail['result']['upc'];
					$goods['cat_fid'] = $fid;
					$goods['cat_id'] = $cid;
					$goods['jd_sku_id'] = $return_detail['result']['sku'];
					$goods['last_time'] = time();
					foreach($add_shops as $shop){
						$goods['sort_id'] = $shop_cates[$shop['store_id']];
						$goods['store_id'] = $shop['store_id'];
						$res_add = $shop_goods_db->add($goods);
						if(!$res_add){
							fdump(json_encode($res_add,'jd_goods_add'));
						}
						$this->keepThread(); //保持单进程
					}
					if($return_cate && $return_cate['success']==false){
						$arr = json_encode($return_cate);
						fdump_sql($arr,'jd_category');
					}
					$this->keepThread(); //保持单进程
               }
           }
        }else{
            $arr = json_encode($return);
            fdump_sql($arr,'jd_pageNum');
        }
		$this->keepThread(); //保持单进程
        G('end');
        $time = G('begin','end',6);
        $mem = G('begin','end','m');
        fdump_sql('时间：'.$time.' 内存：'.$mem ,'jd_syncInfo');
        return true;
    }
}
?>
<?php
/*
 * 
 * 小区对外相关接口
 * 
 */
class HouseApiAction extends BaseAction{
    public function outputJson($datas=array()){
        header("Content-type: application/json");
        $dataJson=json_encode($datas,JSON_UNESCAPED_UNICODE);
        echo $dataJson;
        exit;
    }
	public function getVillageList(){
        $house_villageDb=M('House_village');
        $village_name=isset($_GET['keyname']) ? trim($_GET['keyname']):'';
        if($village_name){
            $village_name=strip_tags($village_name);
            $village_name=htmlspecialchars($village_name,ENT_QUOTES);
        }
        $whereArr=array('village_id'=>array('gt',0),'status'=>1);
        if($village_name){
            $whereArr['village_name'] = array('like', '%' . $village_name . '%');
        }
        $areaDb=M('Area');
        $page=isset($_GET['page']) ? intval($_GET['page']) :0;
        $page=$page>0 ? $page:1;
        $page_num=10;
        $limitOffist=$page_num*($page-1);
        $fieldStr='village_id,village_name,village_address,province_id,city_id,area_id,`long`,`lat`';
        $total_count=$house_villageDb->where($whereArr)->count();
        $page_total=0;
        if($total_count>0){
            $page_total=$total_count/$page_num;
            $page_total=ceil($page_total);
            $villages= $house_villageDb->where($whereArr)->field($fieldStr)->order('village_id DESC')->limit($limitOffist. ',' . $page_num)->select();
            foreach ($villages as $kk=>$vv){
                if(empty($vv['long']) || $vv['long']<=0){
                    $villages[$kk]['long']='';
                }
                if(empty($vv['lat']) || $vv['lat']<=0){
                    $villages[$kk]['lat']='';
                }
                $villages[$kk]['province_str']='';
                if($vv['province_id']>0){
                    $province=$areaDb->where(array('area_id'=>$vv['province_id']))->field('area_id,area_pid,area_name')->find();
                    if($province){
                        $villages[$kk]['province_str']=$province['area_name'];
                    }
                }
                unset($villages[$kk]['province_id']);
                $villages[$kk]['city_str']='';
                if($vv['city_id']>0){
                    $city=$areaDb->where(array('area_id'=>$vv['city_id']))->field('area_id,area_pid,area_name')->find();
                    if($city){
                        $villages[$kk]['city_str']=$city['area_name'];
                    }
                }
                unset($villages[$kk]['city_id']);
                $villages[$kk]['area_str']='';
                if($vv['area_id']>0){
                    $areaTmp=$areaDb->where(array('area_id'=>$vv['area_id']))->field('area_id,area_pid,area_name')->find();
                    if($areaTmp){
                        $villages[$kk]['area_str']=$areaTmp['area_name'];
                    }
                }
                unset($villages[$kk]['area_id']);
            }
        }
        
        $outputArr=array('current_page'=>$page,'page_total'=>$page_total,'page_num'=>$page_num,'total_count'=>$total_count,'list'=>$villages);
        $this->outputJson($outputArr);
	}
    public function getVillageStatistics(){
        $house_villageDb=M('House_village');
        $statisticsArr=array('village_total'=>0,'room_total'=>0,'owner_total'=>0,'work_order_total'=>0,'current_month_work_order_count'=>0,'current_day_work_order_count'=>0,'current_month_finish_work_order_count'=>0,'current_day_finish_work_order_count'=>0);
        $whereArr=array('village_id'=>array('gt',0),'status'=>1);
        $total_count=$house_villageDb->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['village_total']=$total_count;
        }
        $whereArr=array('village_id'=>array('gt',0),'single_id'=>array('gt',0),'is_del'=>array('lt',1));
        $total_count=M('House_village_user_vacancy')->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['room_total']=$total_count;
        }

        $whereArr=array('village_id'=>array('gt',0),'status'=>array('lt',3),'type'=>array('in',array(0,1,2,3)));
        $total_count=M('House_village_user_bind')->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['owner_total']=$total_count;
        }
        $repair_works_orderDb=M('House_new_repair_works_order');
        $whereArr=array('village_id'=>array('gt',0));
        $total_count=$repair_works_orderDb->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['work_order_total']=$total_count;
        }
        $current_month=date('Y-m').'-01 00:00:00';
        $current_month_time=strtotime($current_month);
        $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_month_time));
        $total_count=$repair_works_orderDb->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['current_month_work_order_count']=$total_count;
        }
        $current_month=date('Y-m').'-01 00:00:00';
        $current_month_time=strtotime($current_month);
        $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_month_time),'_string'=>'event_status>=40');
        $total_count=$repair_works_orderDb->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['current_month_finish_work_order_count']=$total_count;
        }
        
        $current_day=date('Y-m-d').' 00:00:00';
        $current_day_time=strtotime($current_day);
        $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_day_time));
        $total_count=$repair_works_orderDb->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['current_day_work_order_count']=$total_count;
        }
        $current_day=date('Y-m-d').' 00:00:00';
        $current_day_time=strtotime($current_day);
        $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_day_time),'_string'=>'event_status>=40');
        $total_count=$repair_works_orderDb->where($whereArr)->count();
        if($total_count>0){
            $statisticsArr['current_day_finish_work_order_count']=$total_count;
        }
        $houseNewRepairCateDb=M('House_new_repair_cate');
        $tmpCateNameArr=array('室内报修','公区报修');
        $current_month=date('Y-m').'-01 00:00:00';
        $current_month_time=strtotime($current_month);   //本月
        $current_year=date('Y').'-01-01 00:00:00';
        $current_year_time=strtotime($current_year); //今年
        $statisticsArr['current_month_completed_repair_count']=0;
        $statisticsArr['current_month_repair_count']=0;
        $statisticsArr['current_year_completed_repair_count']=0;
        $statisticsArr['current_year_repair_count']=0;
        $subject_info=$houseNewRepairCateDb->where(array('parent_id'=>0,'village_id'=>array('gt',0),'status'=>array('lt',4),'cate_name'=>array('in',$tmpCateNameArr)))->field('id,cate_name')->select();
        if($subject_info){
            $cat_fids=array();
            foreach ($subject_info as $cvv){
                $cat_fids[]=$cvv['id'];
            }
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_month_time),'cat_fid'=>array('in',$cat_fids));
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_month_repair_count']=$total_count;
            }
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_month_time),'cat_fid'=>array('in',$cat_fids),'_string'=>'event_status>=40');
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_month_completed_repair_count']=$total_count;
            }
            
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_year_time),'cat_fid'=>array('in',$cat_fids));
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_year_repair_count']=$total_count;
            }
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_year_time),'cat_fid'=>array('in',$cat_fids),'_string'=>'event_status>=40');
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_year_completed_repair_count']=$total_count;
            }
        }
        
        $tmpCateNameArr=array('平台投诉','400投诉');
        $statisticsArr['current_month_completed_complaint_count']=0;
        $statisticsArr['current_month_complaint_count']=0;
        $statisticsArr['current_year_completed_complaint_count']=0;
        $statisticsArr['current_year_complaint_count']=0;
        $subject_info=$houseNewRepairCateDb->where(array('parent_id'=>0,'village_id'=>array('gt',0),'status'=>array('lt',4),'cate_name'=>array('in',$tmpCateNameArr)))->field('id,cate_name')->select();
        if($subject_info){
            $cat_fids=array();
            foreach ($subject_info as $cvv){
                $cat_fids[]=$cvv['id'];
            }
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_month_time),'cat_fid'=>array('in',$cat_fids));
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_month_complaint_count']=$total_count;
            }
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_month_time),'cat_fid'=>array('in',$cat_fids),'_string'=>'event_status>=40');
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_month_completed_complaint_count']=$total_count;
            }
            
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_year_time),'cat_fid'=>array('in',$cat_fids));
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_year_complaint_count']=$total_count;
            }
            $whereArr=array('village_id'=>array('gt',0),'add_time'=>array('egt',$current_year_time),'cat_fid'=>array('in',$cat_fids),'_string'=>'event_status>=40');
            $total_count=$repair_works_orderDb->where($whereArr)->count();
            if($total_count>0){
                $statisticsArr['current_year_completed_complaint_count']=$total_count;
            }
        }
        $this->outputJson($statisticsArr);
    }
    public function getVillageDetail(){
        $house_villageDb=M('House_village');
        $village_id=isset($_GET['village_id']) ? trim($_GET['village_id']):0;
        $village_id=$village_id ? intval($village_id):0;
        $detailJson=array('village_id'=>$village_id,'detail'=>'');
        if($village_id>0){
            $house_villageDb=M('House_village');
            $whereArr=array('village_id'=>$village_id,'status'=>1);
            $fieldStr='village_id,village_name,village_address,province_id,city_id,area_id,`long`,`lat`';
            $detailArr= $house_villageDb->where($whereArr)->field($fieldStr)->find();
            if($detailArr){
                $areaDb=M('Area');
                if(empty($detailArr['long']) || $detailArr['long']<=0){
                    $detailArr['long']='';
                }
                if(empty($detailArr['lat']) || $detailArr['lat']<=0){
                    $detailArr['lat']='';
                }
                $detailArr['province_str']='';
                if($detailArr['province_id']>0){
                    $province=$areaDb->where(array('area_id'=>$detailArr['province_id']))->field('area_id,area_pid,area_name')->find();
                    if($province){
                        $detailArr['province_str']=$province['area_name'];
                    }
                }
                unset($detailArr['province_id']);
                $detailArr['city_str']='';
                if($detailArr['city_id']>0){
                    $city=$areaDb->where(array('area_id'=>$detailArr['city_id']))->field('area_id,area_pid,area_name')->find();
                    if($city){
                        $detailArr['city_str']=$city['area_name'];
                    }
                }
                unset($detailArr['city_id']);
                $detailArr['area_str']='';
                if($detailArr['area_id']>0){
                    $areaTmp=$areaDb->where(array('area_id'=>$detailArr['area_id']))->field('area_id,area_pid,area_name')->find();
                    if($areaTmp){
                        $detailArr['area_str']=$areaTmp['area_name'];
                    }
                }
                unset($detailArr['area_id']);
                
                $detailArr['intro_remark']='';
                $village_info=M('House_village_info')->where(array('village_id'=>$village_id))->field('*')->find();
                if($village_info && $village_info['remark']){
                    $detailArr['intro_remark']=$village_info['remark'];
                }
                $whereArr=array('village_id'=>$village_id,'single_id'=>array('gt',0),'is_del'=>array('lt',1));
                $total_count=M('House_village_user_vacancy')->where($whereArr)->count();
                $detailArr['village_room_total']=0;
                if($total_count>0){
                    $detailArr['village_room_total']=$total_count;
                }
                
                $whereArr=array('village_id'=>$village_id,'status'=>array('lt',3),'type'=>array('in',array(0,1,2,3)));
                $total_count=M('House_village_user_bind')->where($whereArr)->count();
                $detailArr['village_owner_total']=0;
                if($total_count>0){
                    $detailArr['village_owner_total']=$total_count;
                }

                $whereArr=array('village_id'=>$village_id,'status'=>array('lt',3),'is_del'=>array('lt',1));
                $total_count=M('House_worker')->where($whereArr)->count();
                $detailArr['village_worker_total']=0;
                if($total_count>0){
                    $detailArr['village_worker_total']=$total_count;
                }
                
                $repair_works_orderDb=M('House_new_repair_works_order');
                $whereArr=array('village_id'=>$village_id,'event_status'=>array('lt',20));
                $total_count=$repair_works_orderDb->where($whereArr)->count();
                $detailArr['not_accept_work_order_count']=0;
                if($total_count>0){
                    $detailArr['not_accept_work_order_count']=$total_count;
                }

                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=20 AND event_status<40');
                $total_count=$repair_works_orderDb->where($whereArr)->count();
                $detailArr['processing_work_order_count']=0;
                if($total_count>0){
                    $detailArr['processing_work_order_count']=$total_count;
                }
                
                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=40');
                $total_count=$repair_works_orderDb->where($whereArr)->count();
                $detailArr['finish_work_order_count']=0;
                if($total_count>0){
                    $detailArr['finish_work_order_count']=$total_count;
                }
                $detailArr['village_worker_timeliness_rate']=0;
                $whereArr=array('village_id'=>$village_id,'is_status'=>array('in',[0,2]));
                $timeliness_count=$repair_works_orderDb->where($whereArr)->count();
                if($timeliness_count>0){
                    $whereArr=array('village_id'=>$village_id);
                    $all_order_count=$repair_works_orderDb->where($whereArr)->count();
                    $village_worker_timeliness_rate=$timeliness_count/$all_order_count;
                    $village_worker_timeliness_rate=$village_worker_timeliness_rate*100;
                    $detailArr['village_worker_timeliness_rate']=$village_worker_timeliness_rate>0 ? round($village_worker_timeliness_rate,2):0;
                }
                
                $current_day=date('Y-m-d').' 00:00:00';
                $current_day_time=strtotime($current_day);
                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=40 AND update_time>='.$current_day_time);
                $total_count=$repair_works_orderDb->where($whereArr)->count();
                $detailArr['today_work_order_count']=0;
                if($total_count>0){
                    $detailArr['today_work_order_count']=$total_count;
                }
                $detailJson['detail']=$detailArr;
                $houseNewRepairCateDb=M('House_new_repair_cate');
                $tmpCateNameArr=array('室内报修','公区报修','平台投诉','400投诉');
                /*
                $subject_info=$houseNewRepairCateDb->where(array('parent_id'=>0,'village_id'=>$village_id,'status'=>array('lt',4),'cate_name'=>array('in',$tmpCateNameArr)))->field('id,cate_name')->select();
                */
                $subject_info=$houseNewRepairCateDb->where(array('parent_id'=>0,'village_id'=>$village_id,'status'=>array('lt',4)))->field('id,cate_name')->select();
                $tmpCateOrderArr=array('室内报修'=>array('today_order'=>array('cate_name'=>'室内报修','total_count'=>0),'current_month_order'=>array('cate_name'=>'室内报修','total_count'=>0)),
                    '公区报修'=>array('today_order'=>array('cate_name'=>'公区报修','total_count'=>0),'current_month_order'=>array('cate_name'=>'公区报修','total_count'=>0)),
                    '平台投诉'=>array('today_order'=>array('cate_name'=>'平台投诉','total_count'=>0),'current_month_order'=>array('cate_name'=>'平台投诉','total_count'=>0)),
                    '400投诉'=>array('today_order'=>array('cate_name'=>'400投诉','total_count'=>0),'current_month_order'=>array('cate_name'=>'400投诉','total_count'=>0)),
                    'qi_ta'=>array('today_order'=>array('cate_name'=>'其他','total_count'=>0),'current_month_order'=>array('cate_name'=>'其他','total_count'=>0)),
                );
                $detailJson['detail']['work_order_today_total_count']=0;
                $detailJson['detail']['work_order_current_month_total_count']=0;
                $detailJson['detail']['work_order_today_complete_count']=0;
                $detailJson['detail']['work_order_current_month_complete_count']=0;
                $tmpOrderCateIds=array();
                $current_day=date('Y-m-d').' 00:00:00';
                $current_day_time=strtotime($current_day);
                $current_month=date('Y-m').'-01 00:00:00';
                $current_month_time=strtotime($current_month);
                $work_order_allcate=array();
                if($subject_info){
                    foreach ($subject_info as $cvv){
                        
                        $whereArr=array('village_id'=>$village_id,'cat_fid'=>$cvv['id'],'_string'=>'event_status>=10 AND add_time>='.$current_day_time);
                        $today_count=$repair_works_orderDb->where($whereArr)->count();
                        $today_count=$today_count>0 ? $today_count:0;
                        
                        $whereArr=array('village_id'=>$village_id,'cat_fid'=>$cvv['id'],'_string'=>'event_status>=10 AND add_time>='.$current_month_time);
                        $month_count=$repair_works_orderDb->where($whereArr)->count();
                        $month_count=$month_count>0 ? $month_count:0;
                        
                        $cate_name=trim($cvv['cate_name']);
                        if(in_array($cate_name,$tmpCateNameArr)){
                            $tmpCateOrderArr[$cate_name]['today_order']['total_count']+=$today_count;
                            $tmpCateOrderArr[$cate_name]['current_month_order']['total_count']+=$month_count;
                            $tmpOrderCateIds[]=$cvv['id'];
                        }
                        $work_order_allcate[]=array('cat_id'=>$cvv['id'],'cate_name'=>$cate_name,'today_total_count'=>$today_count,'current_month_total_count'=>$month_count);
                    }
                }
                //其他
                $whereQtArr=array('village_id'=>$village_id,'_string'=>'event_status>=10 AND add_time>='.$current_day_time);
                $whereQtMArr=array('village_id'=>$village_id,'_string'=>'event_status>=10 AND add_time>='.$current_month_time);
                if(!empty($tmpOrderCateIds)){
                    $whereQtArr=array('village_id'=>$village_id,'cat_fid'=>array('not in',$tmpOrderCateIds),'_string'=>'event_status>=10 AND add_time>='.$current_day_time);
                    $whereQtMArr=array('village_id'=>$village_id,'cat_fid'=>array('not in',$tmpOrderCateIds),'_string'=>'event_status>=10 AND add_time>='.$current_month_time);
                }
                $qita_count=$repair_works_orderDb->where($whereQtArr)->count();
                $qita_count=$qita_count>0 ? $qita_count:0;
                $tmpCateOrderArr['qi_ta']['today_order']['total_count']=$qita_count;
                $qita_m_count=$repair_works_orderDb->where($whereQtMArr)->count();
                $qita_m_count=$qita_m_count>0 ? $qita_m_count:0;
                $tmpCateOrderArr['qi_ta']['current_month_order']['total_count']=$qita_m_count;
                $detailJson['detail']['work_order_cate_count']=array_values($tmpCateOrderArr);
                $detailJson['detail']['work_order_allcate_count']=$work_order_allcate;
                //今日
                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=10 AND add_time>='.$current_day_time);
                $today_count=$repair_works_orderDb->where($whereArr)->count();
                $today_count=$today_count>0 ? $today_count:0;
                $detailJson['detail']['work_order_today_total_count']=$today_count;
                //今日订单今日完结
                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=40 AND add_time>='.$current_day_time);
                $today_complete_count=$repair_works_orderDb->where($whereArr)->count();
                $today_complete_count=$today_complete_count>0 ? $today_complete_count:0;
                $detailJson['detail']['work_order_today_complete_count']=$today_complete_count;
                //本月
                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=10 AND add_time>='.$current_month_time);
                $month_count=$repair_works_orderDb->where($whereArr)->count();
                $month_count=$month_count>0 ? $month_count:0;
                $detailJson['detail']['work_order_current_month_total_count']=$month_count;
                //本月订单本月完结
                $whereArr=array('village_id'=>$village_id,'_string'=>'event_status>=40 AND add_time>='.$current_month_time);
                $month_complete_count=$repair_works_orderDb->where($whereArr)->count();
                $month_complete_count=$month_complete_count>0 ? $month_complete_count:0;
                $detailJson['detail']['work_order_current_month_complete_count']=$month_complete_count;

            }
        }
        $this->outputJson($detailJson);
    }
    public function getWorkerOrderList(){
        $village_id=isset($_GET['village_id']) ? trim($_GET['village_id']):0;
        $village_id=$village_id ? intval($village_id):0;
        $start_time = isset($_GET['start_time']) ? trim($_GET['start_time']):'';
        $start_time=$start_time ? strtotime($start_time):0;
        $end_time = isset($_GET['end_time']) ? trim($_GET['end_time']):'';
        $end_time=$end_time ? strtotime($end_time):0;
        $page_num=isset($_GET['page_num']) ? intval($_GET['page_num']):0;
        if($end_time>100){
            $end_time=$end_time+86399;
        }
        $page=isset($_GET['page']) ? intval($_GET['page']) :0;
        $page=$page>0 ? $page:1;
        $page_num=$page_num>0 ? $page_num:10;
        $repair_works_orderDb=M('House_new_repair_works_order');
        $limitOffist=$page_num*($page-1);
        $fieldStr='order_id,village_id,property_id,cat_fid,cat_id,label_txt,order_content,phone,name,now_role,worker_id,event_status,add_time,address_type,address_id,public_id,single_id,floor_id,layer_id,room_id';
        $whereArr=array('order_id'=>array('gt',0));
        if($village_id>0){
            $whereArr['village_id']=$village_id;
        }
        if($start_time>100 && $end_time<1) {
            $whereArr['add_time'] =array('egt',$start_time);
        }else if($start_time<1 && $end_time>100) {
            $whereArr['add_time'] =array('elt',$end_time);
        }else if($start_time>100 && $end_time>100) {
            $whereArr['_string'] ='add_time>='.$start_time.' AND add_time<='.$end_time;
        }
        $order_status_txt = [
            '10'=> '待处理',
            '11'=> '待指派',// 工作人员 驳回给处理中心
            '14'=> '待指派',// 工作人员 拒绝
            '15'=> '待指派',// 工作人员 抢单中
            '16'=> '转供暖站',// 工作人员转单指派给了供暖站 供暖站工作人员 抢单中
            '17'=> '转开发商',// 工作人员转单指派给了开发商 开发商工作人员 抢单中

            '20' => '已指派',
            '21' => '已指派',// 物业管理员指派
            '22' => '已指派',// 物业工作人员指派
            '23' => '已指派',// 小区管理员指派
            '24' => '已指派',// 工作人员转单指派
            '25' => '已指派',// 工作人员拒绝转单指派
            '26' => '转供暖站',// 工作人员转单指派给了供暖站
            '27' => '转开发商',// 工作人员转单指派给了开发商
            '28' => '待审核',// 工作人员转单指派给了其他工作人员
            '29' => '待审核',// 工作人员转单指派给了其他工作人员（开发商）
            '30' => '处理中',
            '34' => '处理中', // 工作人员拒绝
            '35' => '处理中', // 工作人员添加收费项
            '36' => '处理中', // 用户同意收取工单费用
            '37' => '用户拒绝', // 用户拒绝 重新提供方案

            '40' => '已办结',// 工作人员办结
            '41' => '已办结',// 物业管理员回复 办结
            '42' => '已办结',// 物业工作人员回复 办结
            '43' => '已办结',// 小区管理员回复 办结
            '44' => '已办结',// 线上支付 办结
            '45' => '已办结',// 线下已支付 办结

            '50' => '已撤回',
            '60' => '已关闭',
            '70' => '已评价'
        ];
        $total_count=$repair_works_orderDb->where($whereArr)->count();
        $page_total=0;
        $houseNewRepairCateDb=M('House_new_repair_cate');
        $houseNewRepairCateCustomDb=M('House_new_repair_cate_custom');
        $houseVillageUserVacancyDb=M('House_village_user_vacancy');
        $house_village_singleDb=M('House_village_single');
        $house_village_layerDb=M('House_village_layer');
        $house_village_floorDb = M('House_village_floor');
        $house_villageModel =D('House_village');
        $house_village_public_areaDb=M('House_village_public_area');
        if($total_count>0){
            $page_total=$total_count/$page_num;
            $page_total=ceil($page_total);
            $works_order= $repair_works_orderDb->where($whereArr)->field($fieldStr)->order('order_id DESC')->limit($limitOffist. ',' . $page_num)->select();
            $d_house_village=D('House_village');
            $village_info = $d_house_village->field('*')->where(array('village_id'=>$village_id))->find();
            foreach ($works_order as $kk=>$vv){
                $works_order[$kk]['add_time_txt'] =date('Y-m-d H:i:s',$vv['add_time']);
                //$works_order[$kk]['status_txt'] = $order_status_txt[$vv['event_status']];
                if($vv['event_status']<20){
                    $works_order[$kk]['status_txt']='待指派';
                }else if($vv['event_status']>=20&& $vv['event_status']<40){
                    $works_order[$kk]['status_txt']='处理中';
                }else{
                    $works_order[$kk]['status_txt']='已完结';
                }
                if(isset($order_status_txt[$vv['event_status']])){
                    $works_order[$kk]['status_txt']=$order_status_txt[$vv['event_status']];
                }
                $cate_name = '';
                if(isset($vv['cat_id'])){
                    $subject_info=$houseNewRepairCateDb->where(array('id'=>$vv['cat_id']))->field('id,cate_name')->find();
                    $cate_name=isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                $works_order[$kk]['cate_name'] = $cate_name;
                $works_order[$kk]['subject_name']=$cate_name;
                if(isset($vv['cat_fid'])){
                    $subject_info=$houseNewRepairCateDb->where(array('id'=>$vv['cat_fid']))->field('id,cate_name')->find();
                    $works_order[$kk]['subject_name'] = isset($subject_info['cate_name']) ? $subject_info['cate_name'] : '';
                }
                $works_order[$kk]['address_txt'] ='';
                if (isset($vv['address_type']) && $vv['address_type'] == 'room') {
                    $vacancy_info=$houseVillageUserVacancyDb->where(array('pigcms_id'=>$vv['room_id']))->field('village_id,single_id,floor_id,layer_id,pigcms_id')->find();
                    $word_data = array();
                    $singleInfo=$house_village_singleDb->where(array('id'=>$vacancy_info['single_id']))->field('single_name')->find();
                    $sing_name=$singleInfo ? $singleInfo['single_name']:'';
                    
                    $floor_info = $house_village_floorDb->where(array('floor_id' => $vacancy_info['floor_id']))->field('floor_name')->find();
                    
                    $layer_info = $house_village_layerDb->where(array('id' => $vacancy_info['layer_id']))->field('layer_name')->find();
                    $layer_num=$layer_info ? $layer_info['layer_name']:'';
                    if(is_numeric($layer_num)){
                        $layer_num=$layer_num.'层';
                    }
                    $word_data['layer'] = $layer_num;
                    $word_data['room'] = $vacancy_info['room'];
                    if (!empty($floor_info)) {
                        $word_data['floor_name'] = $floor_info['floor_name'];
                        if(is_numeric($word_data['floor_name'])){
                            $word_data['floor_name']=$word_data['floor_name'].($house_villageModel->getHouseAlias('floor_name',$vv['village_id'])?:'单元');
                        }
                        if (!$sing_name) {
                            $sing_name = $floor_info['floor_layer'];
                        }
                    }
                    if(is_numeric($sing_name)){
                        $sing_name=$sing_name.($house_villageModel->getHouseAlias('single_name',$vv['village_id'])?:'栋');
                    }else{
                        $lastchar=substr($sing_name,-1,1);
                        if(is_numeric($lastchar)){
                            $sing_name=$sing_name.($house_villageModel->getHouseAlias('single_name',$vv['village_id'])?:'栋');
                        }
                    }
                    $word_data['single_name'] = $sing_name;
                    $room_diy_name = $house_villageModel->word_replce_msg($word_data,$vv['village_id']);
                    $works_order[$kk]['address_txt'] = $room_diy_name;
                    
                }
                if (isset($vv['address_type']) && $vv['address_type'] == 'public') {
                    $public_info=$house_village_public_areaDb->where(array('public_area_id' => $vv['public_id']))->field('public_area_name')->find();
                    $address =$public_info ?  $public_info['public_area_name']:'';
                    $works_order[$kk]['address_txt'] =   $address;
                }
                if($vv['label_txt']){
                    $works_order[$kk]['order_content'] = '';
                    $label_txt_arr = explode(',', $vv['label_txt']);
                    foreach ($label_txt_arr as $v1) {
                        $label_info =$houseNewRepairCateCustomDb->where(array('id'=>$v1))->field('id,name')->find();
                        if($label_info){
                            $works_order[$kk]['order_content'] .= $label_info['name'] . ';';
                        }
                    }
                    $works_order[$kk]['order_content'] = rtrim($vv['order_content'], ';');
                }
                if (empty($vv['order_content'])) {
                    $works_order[$kk]['order_content'] = '无';
                }
                if (isset($vv['phone']) && !empty($vv['phone'])) {
                    $works_order[$kk]['phone'] = $d_house_village->phone_desensitization_v($vv['phone'],$vv['village_id'],$village_info);
                }
            }
        }
        $outputArr=array('current_page'=>$page,'page_total'=>$page_total,'page_num'=>$page_num,'total_count'=>$total_count,'list'=>$works_order);
        $this->outputJson($outputArr);
    }
}

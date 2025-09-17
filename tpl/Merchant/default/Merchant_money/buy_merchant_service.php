<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("购买系统服务")}</a></li>
			
		</ul>
		<a href="{pigcms{:U('Merchant_money/mer_recharge')}">{pigcms{:L_("充值")}</a>
	</div>
	<style>
	.mainnav_title{
		margin-top:20px;
	}
	.mainnav_title ul a {
		padding: 15px 20px;
	}
	ul, ol {
		margin-bottom: 15px;
	}
	.mainnav_title span{
		color:#7EBAEF;
	}
	.mainnav_title a.on div{
		color:#C1BEBE;
	}
	button{
		padding: 6px;
		background-color: rgba(255, 255, 255, 0);
		box-sizing: border-box;
		border-width: 1px;
		border-style: solid;
		border-color: rgba(121, 121, 121, 1);
		border-radius: 2px;
		-moz-box-shadow: none;
		-webkit-box-shadow: none;
		box-shadow: none;
		font-size: 14px;
		color: #666666;
		cursor: pointer;

	}
	.mainnav_title {line-height:40px;/* height:40px; */border-bottom:1px solid #eee;color:#31708f;}
	.mainnav_title a {color:#004499;margin:0 5px;padding:4px 7px;background:#d9edf7;}
	.mainnav_title a:hover ,.mainnav_title a.on{background:#498CD0;color:#fff;text-decoration: none;}
	#pro .table{    margin: 0px;
		overflow: hidden;}
	#pro .table li{
			float:left;
			list-style: none;
			display: inline-block;
			width: auto;
		margin-right:15px
	}
.pro_content .swiper-slide{width:auto !important;}
	#pro .pro_header{
		border-bottom: 2px solid #dedede;
		height: 45px;
		line-height: 38px;
	}
	#pro .pro_header a{color:#777;}
	#pro .pro_header a:hover{color:#4C69FC;}
	#pro .pro_header a.activited{color:#4C69FC;}
	.swiper-slide .p20{padding:15px}
		.pro_content{padding-top:10px}
		.pb_b0{padding-bottom:0px}
		.outTime{color: #980101;
			background: #8397F3;
			border-radius: 4px;
			padding: 3px 5px;}
	.pd0{    padding-bottom: 0px !important;}
	</style>
      

	
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content pb_b0" >
		<div class="page-content-area pb_b0" >
			<div class="row">
				<div id="pro">
					<div class="pro_header">
						<ul class="table">
							<li>
								<a href="javascript:;" class="activited" data-c="all_order">{pigcms{:L_("订购权限套餐")}</a>
							</li>
							<li>
								<a href="{pigcms{:U('auth_gorup_order_list')}" >{pigcms{:L_("我订购的权限套餐")}</a>
							</li>
						</ul>
					</div>
					<div class="pro_content">
						<div class="swiper-container swiper-container-model all_order order">
							<div class="swiper-wrapper">
							<volist name="group" id="vo">
								
								<div class="swiper-slide">
									<div data-v-455236be="" class="el-col el-col-6" >
										<div data-v-455236be="" class="grid-content bg-purple">
											<div data-v-455236be="" class="createtopbg">
												 <if condition="$vo.is_default eq 1 OR $vo['price'] eq 0"><img src="/tpl/Merchant/default/static/images/new/my_free.png"/></if>
												<div data-v-455236be="" class="tc pt30 fontbai f24">{pigcms{$vo.name}</div>
												<if condition="$now_merchant['authority_group_id'] eq $vo['id'] AND $vo['is_default'] eq 0 AND $now_merchant['merchant_end_time'] gt 0">
												<div data-v-455236be="" class="tc p20 fontbai f14 pd0">
													<div><span class="outTime">{pigcms{:L_("当前套餐:将于")}{pigcms{$now_merchant.merchant_end_time|date="Y-m-d H:i:s",###}{pigcms{:L_("过期")}</span></div>
												</div>
												</if>
												<div data-v-455236be="" class="tc p20 fontbai f14">
													{pigcms{$vo.des}
												</div>
											</div>
											<div data-v-455236be="" class="createContent">
												<div data-v-455236be="" class="p30 pl40 createFont">
													<ul data-v-455236be="">
														<volist name="vo.detail" id="vv">
														<if condition="$vv">
															<li data-v-455236be="">
																<img data-v-455236be="" src="/tpl/Merchant/default/static/images/new/createico1.png">
																{pigcms{$vv}
															</li>
															</if>
														</volist>
														
													</ul>
												</div>
											</div>
											<div data-v-455236be="" class="createPrice mt10">
												<div data-v-455236be="" class="f30 tc price">
													<span data-v-455236be="" class="f20">{pigcms{$config.Currency_symbol}</span>{pigcms{$vo.price}<if condition="$vo.is_default eq 0">/<span data-v-455236be="">{pigcms{$time_type[$vo['time_type']]}</span></if>
												</div>
											</div>
											<div data-v-455236be="" class="oldPrice">
												<div data-v-455236be="" class="f12 tc">
													<span data-v-455236be="" class="f14" style="color:#000;">{pigcms{:L_("平台抽成比例")}{pigcms{$vo.percent}%</span>
												</div>
											</div>
											<div data-v-455236be="" class="tc  model_button"<if condition="$vo['is_default'] eq 1 AND $now_merchant['authority_group_id'] eq $vo['id']"><else /> onclick="artconfirms({pigcms{$vo.id},1<if condition="$vo.is_try eq 1 AND $vo.had_try neq 1">,1</if>);"</if>>
												<button data-v-455236be="" type="button" class="el-button el-button--primary el-button--medium">
													<span >
													<if condition="$vo['is_default'] eq 1 AND $now_merchant['authority_group_id'] eq $vo['id']">
													{pigcms{:L_("正在使用")}
													<elseif condition=" $vo['id'] eq $now_merchant['authority_group_id']" />
													{pigcms{:L_("去续费")}
													<elseif condition="$vo.is_try eq 1 AND $vo.had_try neq 1" />
													{pigcms{:L_("免费试用")}
													<else />
													{pigcms{:L_("立即使用")}
													</if>
													
													</span>
												</button>
												<if condition="$vo.is_try eq 1 AND $vo.had_try neq 1"><p style="color:#FD65CA;padding-top:6px">{pigcms{:L_("可试用")}{pigcms{$vo.try_time}{pigcms{$time_type[$vo['try_time_type']]}</p></if>
											</div>
										</div>
									</div>
								</div>
							
								</volist>
							
								
							</div>
						</div>
						<div class="col-sm-12 my_order order" style="display:none">
							<div class="tabbable" >
								<div class="row">
									<div class="col-xs-12">
										<div class="grid-view pb_b0">
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th>{pigcms{:L_("创建时间")}</th>
														<th >{pigcms{:L_("套餐名称")}</th>
														<th>{pigcms{:L_("服务周期")}</th>
														<th>{pigcms{:L_("是否试用")}</th>
														<th>{pigcms{:L_("费用(元")})</th>
														<th >{pigcms{:L_("订购状态")}</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>2019-12-12</td>
														<td >{pigcms{:L_("内容咨询")}<span style="background:#06C1AE;color:#fff;padding:3px 5px;    margin-left: 5px;    border-radius: 5px;">{pigcms{:L_("单前套餐")}</span></td>
														<td>100年</td>
														<td>{pigcms{:L_("是")}<span style="color:#2a6496">({pigcms{:L_("试用期7天")})</span></td>
														<td>0.33</td>
														<td>
															<span style="color:#0AB86D">{pigcms{:L_("订购成功")}</span>
														</td>
													</tr>
													<tr>
														<td>2019-12-12</td>
														<td >{pigcms{:L_("内容咨询")}<span style="background:#06C1AE;color:#fff;padding:3px 5px;    margin-left: 5px;    border-radius: 5px;">{pigcms{:L_("单前套餐")}</span></td>
														<td>100年</td>
														<td>{pigcms{:L_("是")}<span style="color:#2a6496">({pigcms{:L_("试用期7天")})</span></td>
														<td>0.33</td>
														<td>
															<span style="color:#2a6496">{pigcms{:L_("待支付")}</span>
														</td>
													</tr>
												</tbody>
											</table>
											<div  class="clearfix bg-fff pb20 pt20 tc">
												<ul class="pagination">
													<li class="disabled"><a href="#" aria-label="Previous"><span aria-hidden="true">«</span></a></li>
													<li class="active"><a href="javascript:;">1</a></li>
													<li><a href="javascript:;">2</a></li>
													<li><a href="javascript:;">3</a></li>
													<li><a href="javascript:;">4</a></li>
													<li><a href="javascript:;">5</a></li>
													<li><a href="javascript:;" aria-label="Next"><span aria-hidden="true">»</span></a></li>
												</ul>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-sm-12" style="display:none">
					<div class="tabbable" >
								<div class="row">					
									<div class="col-xs-12">		
										<div class="grid-view">
											<table class="table table-striped table-bordered table-hover">
												<thead>
													
													<tr>
														<th>{pigcms{:L_("编号")}</th>
														<th >{pigcms{:L_("权限分类")}</th>
														<th>{pigcms{:L_("权限子分类")}</th>
														<th>{pigcms{:L_("权限子子分类")}</th>
														<th>{pigcms{:L_("价格")}</th>
														<th >{pigcms{:L_("操作")}</th>
													</tr>
												</thead>
												<tbody>
												<if condition="is_array($menus_group)">
													<volist name="menus_group" id="vc">
														<tr> 
															<td>{pigcms{$vc.id}</td>
													
															<td >{pigcms{:L_("权限套餐")} <a class="layer-iframe" href="{pigcms{:U('service_detail',array('menu_group'=>$vc['id']))}" >{pigcms{:L_("查看详情")}</a></td>
														
															<td>{pigcms{$vc.name}</td>
															<td></td>
															<td><if condition="empty($vv['menu_list'])">{pigcms{$vc.price|floatval}{pigcms{$config['Currency_txt']}</if></td>
															
															<td><if condition="empty($vv['menu_list'])"><a class="btn btn-success" onclick="artconfirms({pigcms{$vc.id},1);">{pigcms{:L_("购买")}</a></if></td>
															
														</tr>
														
													</volist>
													</if>
													<if condition="is_array($menus)">
														<volist name="menus" id="vo">
															<tr >
																<td>{pigcms{$vo.id}</td>
														
																<td >{pigcms{$vo.name}</td>
																<td></td>
															
																<td></td>
																<td></td>
																
																<td></td>
																
															</tr>
															<if condition="$vo.menu_list">
															
																<volist name="vo.menu_list" id="vv">
																	<tr <php>if(in_array($vv['id'],$mer_menus)){</php>style="display:none"<php>}</php>> 
																		<td>{pigcms{$vv.id}</td>
																
																		<td ></td>
																	
																		<td>{pigcms{$vv.name}</td>
																		<td></td>
																		<td><if condition="empty($vv['menu_list'])">{pigcms{$vv.price|floatval}{pigcms{$config['Currency_txt']}</if></td>
																		
																		<td><if condition="empty($vv['menu_list'])"><a class="btn btn-success" onclick="artconfirms({pigcms{$vv.id});">{pigcms{:L_("购买")}</a></if></td>
																		
																	</tr>
																	<if condition="$vv.menu_list">
															
																		<volist name="vv.menu_list" id="vd">
																			<tr <php>if(in_array($vd['id'],$mer_menus)){</php>style="display:none"<php>}</php>>
																				<td>{pigcms{$vd.id}</td>
																		
																			
																				<td > </td>
																				<td></td>
																				<td>{pigcms{$vd.name}</td>
																				<td>{pigcms{$vd.price|floatval}{pigcms{$config['Currency_txt']}</td>
																				
																				<td><a class="btn btn-success" onclick="artconfirms({pigcms{$vd.id});">{pigcms{:L_("购买")}</a></td>
																				
																			</tr>
																			
																		</volist>
																	</if>
																</volist>
															</if>
														</volist>
														
													<else/>
														<tr><td class="textcenter red" colspan="6">{pigcms{:L_("列表为空！")}</td></tr>
													</if>
												
														
														
												
												</tbody>
											</table>
										</div>						
									</div>
									<!--div class="col-xs-2" style="margin-top: 15px;">
										<a class="btn btn-success" href="#">{pigcms{:L_("导出成")}excel</a>
									</div-->
								</div>
							</div>
						</div>
					</div>	
				</div>
			</div>
		</div>

	</div>

</div>
<script type="text/javascript">

	function artconfirms(auth_id,type,is_try) {
		$.post('{pigcms{:U('check_before_buy')}',{auth_id:auth_id,is_try:is_try},function(date){
			
		change_money = date.info;
		
		if(change_money==-1){
			notice_msg = '{pigcms{:L_("您确定购买吗？")}';
		
			layer.confirm(notice_msg, {
				btn: ['{pigcms{:L_("确认','取消")}'] //"按钮")}
			}, function(){
				if(typeof(type)!='undefined'){			
					
					window.location.href="{pigcms{$config.site_url}/merchant.php?c=Merchant_money&a=pay_merchant_service&menu_group="+auth_id+'&is_try='+is_try+'&change_money='+change_money;
				}else{
					// window.location.href="{pigcms{:U('pay_merchant_service')}&auth_id="+auth_id
				}
			}, function(){
				window.location.reload()
			});
		}else{
			if(change_money>0){
			
				var html_notice='<div>{pigcms{:L_("您当前的套餐价格低于订购的套餐价格，需另外补足")}{pigcms{$config.Currency_symbol}'+change_money+'{pigcms{$Currency_txt}</div><hr>'
					change_money =1;
			}else{
				var html_notice='<p>{pigcms{:L_("当前的套餐价格低于订购的套餐价格,可直接试用该套餐,但多余的钱将无法退回")}</p>'
				change_money =0;
			}
			
			layer.confirm(html_notice, {
				btn: ['{pigcms{:L_("确认','取消")}'] //"按钮")}
			}, function(){
				if(typeof(type)!='undefined'){			
					
					window.location.href="{pigcms{$config.site_url}/merchant.php?c=Merchant_money&a=pay_merchant_service&menu_group="+auth_id+'&is_try='+is_try+'&change_money='+change_money;
				}else{
					// window.location.href="{pigcms{:U('pay_merchant_service')}&auth_id="+auth_id
				}
			}, function(){
				window.location.reload()
			});
		}
		
			},'json')
	}
	
	function check_money(auth_id){
		// $.post('{pigcms{:U('check_before_buy')}',{auth_id:auth_id},function(date){
			// return date.info;
		// },'json')
		
		
		$.ajax({ 
			type: "post",
			url: "{pigcms{:U('check_before_buy')}", 
			async:true, 
			dataType: 'json',
			date:{auth_id:auth_id},

			 success: function(date){ 
				return date.info;
			} 

		});
	}


	var mySwiperModel = new Swiper('.swiper-container-model',{
		slidesPerView :'auto',
		spaceBetween: 30,
		slidesPerGroup : 1,
		loop: false,
		loopFillGroupWithBlank: false,
		navigation: {
			nextEl: '.swiper-button-next',
			prevEl: '.swiper-button-prev'
		},
	})
  $(".page-content .page-content-area").css({
     'min-height':$(window).height()+'px',
	  'background':'#fff'
	})
</script>
<include file="Public:footer"/>

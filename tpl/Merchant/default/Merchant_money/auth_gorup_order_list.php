<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->

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
	/* #pro .pro_header a:visited{color:#4C69FC;} */
	#pro .pro_header a.activited{color:#4C69FC;}
		.pro_content{padding-top:10px}
		.pb_b0{padding-bottom:0px}


	</style>
      

	
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content pb_b0" >
		<div class="page-content-area pb_b0" >
			<div class="row">
				<div id="pro">
					<div class="pro_header">
						<ul class="table">
							<li>
								<a href="{pigcms{:U('buy_merchant_service')}" >{pigcms{:L_("订购权限套餐")}</a>
							</li>
							<li>
								<a href="{pigcms{:U('auth_gorup_order_list')}" class="activited">{pigcms{:L_("我订购的权限套餐")}</a>
							</li>
						</ul>
					</div>
					<div class="pro_content">
						
						<div class="col-sm-12 my_order order">
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
														<th>{pigcms{:L_("费用")}({pigcms{$config.Currency_txt})</th>
														<th >{pigcms{:L_("订购状态")}</th>
													</tr>
												</thead>
												<tbody>
												
													<volist name="order_list" id="vo">
														<tr>
															<td>{pigcms{$vo['add_time']|date="Y-m-d H:i:s",###}</td>
															<td >{pigcms{$vo.name}</td>
															<td>
															<if condition="$vo.is_default eq 1">
															{pigcms{:L_("永久")}
															<elseif condition="$vo.num gt 0" />{pigcms{$vo.num}{pigcms{$time_type[$vo['time_type']]}
															<else />
															-
															</if></td>
															<td><if condition="$vo.is_try eq 1">{pigcms{:L_("是")}<span style="color:#2a6496">({pigcms{:L_("试用期")}{pigcms{$vo['try_time']}{pigcms{$time_type[$vo['try_time_type']]})</span><else />{pigcms{:L_("否")}</if></td>
															<td>{pigcms{$vo.money}</td>
															<td>
																<span ><if condition="$vo.paid eq 1">
																<font color="#0AB86D">{pigcms{:L_("订购成功")}</font><else /><font color="#2a6496">{pigcms{:L_("待支付")}</font></if></span>
															</td>
														</tr>
													</volist>
												
												</tbody>
											</table>
											<div  class="clearfix bg-fff pb20 pt20 tc">
											{pigcms{$pagebar}
											</div>
										</div>
									</div>
								</div>
							</div>
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
	var refresh = {pigcms{$_GET['refresh']};
	if(refresh){
		if (window!=top) { 
			window.parent.location.reload();
		}
	}
	function artconfirms(auth_id,type) {
		// if(typeof(type)!='undefined'){
			// notice_msg = '{pigcms{:L_("如果您的商家余额充足将直接使用商家余额购买，不足请充值后购买。并且购买后，现有的商家后台权限会被购买的套餐权限替换掉，确定购买？")}';
		// }else{
			 notice_msg = '{pigcms{:L_("您确定购买吗？")}';
		// }
		layer.confirm(notice_msg, {
			btn: ['{pigcms{:L_("确认','取消")}'] //"按钮")}
		}, function(){
			if(typeof(type)!='undefined'){			
				window.location.href="{pigcms{:U('pay_merchant_service')}&menu_group="+auth_id
			}else{
				// window.location.href="{pigcms{:U('pay_merchant_service')}&auth_id="+auth_id
			}
		}, function(){
			window.location.reload()
		});
	}
   // $(".table a").click(function(){
       // var $class=$(this).attr('data-c');
	   // $(".table a").removeClass('activited');
	   // $(this).addClass("activited");
	   // $(".order").hide();
	   // $('.'+$class).show()
	// })
	var $html='<div>{pigcms{:L_("您当前的套餐价格低于订购的套餐价格，需另外补足¥120元")}</div><hr>'+'<p>{pigcms{:L_("您当前的套餐价格低于订购的套餐价格,可直接试用该套餐,但多余的钱将无法退回")}</p>'
	// $(".el-button").click(function(){
		// layer.confirm($html, {
			// btn: ['{pigcms{:L_("确定订购','取消")}'] //"按钮")}
		// }, function(){

		// }, function(){

		// });
	// })

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

</script>
<include file="Public:footer"/>

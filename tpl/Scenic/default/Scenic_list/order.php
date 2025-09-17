<include file="Public/header"/>
	<!--获取门票的价格序列化-->
	<script>
		var user	=	"{pigcms{$_SESSION['user']}";
		$(document).ready(function(){
			if(!user){
				$(".board").show();
				$(".mark").show();
			}
		})
		var jsonTimeStr = '{pigcms{$scenic_ticket.serialize}';
		var jsonTimeArr = $.parseJSON(jsonTimeStr);
		var ticket_price	=	"{pigcms{$scenic_ticket['ticket_price']}";
		function selectTime(){
			pickerEvent.setPriceArr(jsonTimeArr,ticket_price);
			pickerEvent.Init("calendar");
		}
	</script>
	<!--购买导航-->
	<section class="search">
		<div class="w1200 clr">
			<div class="fl">
				<div class="logo fl">
					<a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}" width=160 height=43></a>
				</div>
			</div>
			<div class="fr">
				<ul class="arrow clr">
					<li class="clr on">
						<i>1</i>
						<span>选择景区</span>
					</li>
					<li class="clr ou">
						<i>2</i>
						<span>填写订单</span>
					</li>
					<li class="clr">
						<i>3</i>
						<span>在线支付</span>
					</li>
					<li class="clr">
						<i>4</i>
						<span>支付成功</span>
					</li>
				</ul>
			</div>
		</div>
	</section>
	<!--订单填写-->
	<section class="form">
		<div class="w1200 clr">
			<div class="form_list fl">
				<dl>
					<dt>
						<h2>{pigcms{$scenic_list['scenic_title']}</h2>
						<div class="date datetc">
							<span class="on fl">出行日期：</span>
							<div class="p80">
								<input type="text" readonly="readonly" id="calendar" name="calendar" readonly="readonly" onclick="selectTime();" class="choice" placeholder="请选择游玩日期">
							</div>
						</div>
						<div class="date">
							<span class="on on1 fl">购买数量：</span>
							<div class="p80">
								<div class="plus clr">
									<a href="javascript:void(0)" class="jian">-</a>
									<input type="text" value="1" readonly="readonly">
									<a href="javascript:void(0)" class="jia">+</a>
								</div>
							</div>
						</div>
					</dt>
					<dd>
						<div class="dd_top clr">
							<div class="fl icon">
								<h2 class="fl travel">游客信息</h2>
								<span class="fl remark">请准确填写游客信息</span>
								<a href="{pigcms{$static_public}/file/visitor_upload.xls">请通过Excel上传游客信息（上传格式示例）
							</div>
						</div>
						<div class="common">
							<h2>常用旅客</h2>
							<div class="enger clr">
								<volist name="scenic_family" id="vv">
									<a href="javascript:void(0)">{pigcms{$vv['family_name']}<span style="display:none;">{pigcms{$vv['family_id']}</span></a>
								</volist>
							</div>
						</div>
						<div class="date_list">
							<div class="date">
								<h2>快速添加出行人</h2>
								<div class="surface clr">
									<span class="on fl"><em>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名：</em></span>
									<div class="p105 clr">
										<input type="text" placeholder="请输入姓名" name="name" id="text1">
										<em>请输入姓名</em>
									</div>
								</div>
								<div class="surface clr">
									<span class="on fl"><em>性&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp别：</em></span>
									<div class="p105 clr menw">
										<span class="on">男</span>
										<span>女</span>
									</div>
								</div>
								<div class="surface clr">
									<span class="on fl"><em>手机号码：</em></span>
									<div class="p105 clr">
										<input type="text" placeholder="请输入手机号码" id="text2">
										<em>请输入手机号码</em>
									</div>
								</div>
								<div class="surface clr">
									<span class="on fl"><em>身份证号：</em></span>
									<div class="p105 clr">
										<input type="text" placeholder="请输入身份证号" id="text3">
										<em>请输入身份证号</em>
									</div>
								</div>
							</div>
						</div>
						<a href="javascript:void(0)" class="add addson">添加出行人</a>
					</dd>
					<dd>
						<div class="dd_top clr">
							<div class="fl icon">
								<h2 class="fl infor">取票人信息</h2>
								<span class="fl remark">用于接收门票信息</span>
							</div>
						</div>
						<div class="date_list">
							<div class="date">
								<h2>取票人</h2>
								<div class="surface clr">
									<span class="on fl"><i></i><em>姓&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp名：</em></span>
									<div class="p105 clr">
										<input type="text" id="name" placeholder="请输入姓名" value="{pigcms{$_SESSION['user']['truename']}" />
										<em id="name_prompt">请输入姓名</em>
									</div>
								</div>
								<div class="surface clr">
									<span class="on fl"><i></i><em>手机号码：</em></span>
									<div class="p105 clr">
										<input type="text" id="phone" placeholder="请输入手机号码" value="{pigcms{$_SESSION['user']['phone']}" />
										<em id="phone_prompt">请输入手机号码</em>
									</div>
								</div>
							</div>
						</div>
					</dd>
					<if condition="$scenic_park AND $scenic_list['is_parking'] eq 1">
						<dd>
							<div class="dd_top clr">
								<div class="fl icon">
									<h2 class="fl stop">停车场</h2>
									<span class="fl remark">用于接收门票信息</span>
								</div>
								<div class="fr rig20">
									<span class="price">￥<em>{pigcms{$scenic_list['parking_price']}</em>/次</span>
									<input type="checkbox" id="checkbox_c1" class="chk_4 chk_5" checked="checkbox">
									<label for="checkbox_c1"></label>
									<i class="close open">开启</i>
								</div>
							</div>
							<div class="gclose">
								<div class="date">
									<h2>景区停车位<span>(车位仅限中大型轿车以下)</span></h2>
									<div class="stop_list">
										<div class="surface clr">
											<span class="on ou fl"><i></i><em>车牌号：</em></span>
											<div class="p105 clr">
												<input type="text" placeholder="请输入车牌号">
												<em>请输入车牌号</em>
											</div>
										</div>
									</div>
								</div>
								<a href="javascript:void(0)" class="add addcar">添加车牌号</a>
							</div>
						</dd>
					</if>
					<if condition="$scenic_guide AND $scenic_list['is_guide'] eq 1">
						<dd>
							<div class="dd_top clr">
								<div class="fl icon">
									<h2 class="fl guide">景内导游</h2>
								</div>
								<div class="fr rig20">
									<span class="price">￥<em>{pigcms{$scenic_list['guide_price']}</em>/次</span>
									<input type="checkbox" id="checkbox_c2" class="chk_4 chk_6" checked="checkbox">
									<label for="checkbox_c2"></label>
									<i class="close open">开启</i>
								</div>
							</div>
							<div class="gclose">
								<div class="date_list">
									<div class="date clr">
										<div class="name fl">
											<div class="name_top">
												<span>{pigcms{$scenic_guide[0]['guide_name']}</span><if condition="$scenic_guide[0]['guide_gender'] eq 1"><i class="on"></i><elseif condition="$scenic_guide[0]['guide_gender'] eq 2"/><i></i></if>
											</div>
											<div class="name_end">
												<span class="fl">服务次数：</span>
												<p class="p2">共讲解<i>{pigcms{$scenic_guide[0]['guide_service_number']}</i>次</p>
												<p class="none" style="display:none;">{pigcms{$scenic_guide[0]['guide_id']}</p>
											</div>
										</div>
										<div class="fr img">
											<img src="{pigcms{$scenic_guide[0]['pic']}" width="120" height="120">
										</div>
									</div>
								</div>
								<a style="margin-top:-39px;" href="javascript:void(0)" class="add replace">更换导游</a>
								<div class="layer">
									<div class="lay_top  clr">
										<h5 class="fl">导游列表</h5>
										<a href="javascript:void(0)" class="fr"></a>
									</div>
									<div class="lay_end">
										<ul>
											<volist name="scenic_guide" id="vo">
												<li <if condition="$key eq 0">class="clr on"<else/>class="clr"</if>>
													<div class="name fl">
														<div class="name_top">
															<span>{pigcms{$vo['guide_name']}</span><if condition="$vo['guide_gender'] eq 1"><i class="on"></i><elseif condition="$vo['guide_gender'] eq 2"/><i></i></if>
														</div>
														<div class="name_end">
															<span class="fl">服务次数：</span>
															<p class="p2">共讲解<i>{pigcms{$vo['guide_service_number']}</i>次</p>
															<p class="none" style="display:none;">{pigcms{$vo['guide_id']}</p>
														</div>
													</div>
													<div class="img">
														<img src="{pigcms{$vo['pic']}" width="100" height="100">
													</div>
												</li>
											</volist>
										</ul>
									</div>
								</div>
							</div>
						</dd>
					</if>
					<dd>
						<div class="dd_top clr">
							<div class="fl icon">
								<h2 class="fl stop">发布游伴信息</h2>
							</div>
							<div class="fr rig20">
								<input type="checkbox" id="checkbox_c3" class="chk_4 chk_7" checked="checkbox">
								<label for="checkbox_c3"></label>
								<i id="checkbox_c3i" class="close open">开启</i>
							</div>
						</div>
						<div class="gclose">
							<div class="date">
								<h2>结伴要求</h2>
								<div class="stop_list">
									<div class="surface clr">
										<textarea id="checkbox_c3_text" placeholder="请输入结伴要求"></textarea>
									</div>
								</div>
							</div>
						</div>
					</dd>
				</dl>
				<div class="return clr">
					<div class="return_n clr">
						<div class="fl">
							<a href="{pigcms{:U('Scenic_list/details',array('scenic_id'=>$scenic_list['scenic_id']))}" class="rehui">返回上一页</a>
						</div>
						<div class="fr clr">
							<div class="fl total">
								<span>应付总价</span>
								<i>￥<em id="total_price_s">{pigcms{$scenic_ticket['ticket_price']+$scenic_list['parking_price']+$scenic_list['guide_price']}</em></i>
							</div>
							<div class="fr" onclick="sumbit()">
								<a class="submit" href="javascript:void(0)">同意以下协议，提交订单</a>
							</div>
						</div>
					</div>
					<div id="clause" class="read fr on">我已阅读并核对订单信息，接受以下合同条款中所有内容</div>
				</div>
				<div class="park">
					<ul class="clr">
						<li class="on">预订须知</li>
						<li>入园方式</li>
						<li>入园提示</li>
					</ul>
					<div class="park_list">
						<div class="park_list_n">{pigcms{$scenic_ticket['ticket_explain']}</div>
					</div>
					<div class="park_list" style="display: none;">
						<div class="park_list_n">{pigcms{$scenic_ticket['ticket_cue']}</div>
					</div>
					<div class="park_list" style="display: none;">
						<div class="park_list_n">{pigcms{$scenic_ticket['park_intr']}</div>
					</div>
				</div>
			</div>
			<div class="form_right fr">
				<h2>结算信息</h2>
				<dl>
					<dd class="clr">
						<span class="fl">门票单价</span>
						<span class="fr">￥<i class="on" id="ticket_price">{pigcms{$scenic_ticket['ticket_price']}</i> X <span id="ticket_number">1</span></span>
					</dd>
					<if condition="$scenic_park AND $scenic_list['is_parking'] eq 1">
						<dd class="clr">
							<span class="fl">停车位</span>
							<span class="fr">￥<i id="park_price">{pigcms{$scenic_list['parking_price']}</i> X <span id="park_number">1</span></span>
						</dd>
					</if>
					<if condition="$scenic_guide AND $scenic_list['is_guide'] eq 1">
						<dd class="clr">
							<span class="fl">景内导游</span>
							<span class="fr">￥<i id="guide_price">{pigcms{$scenic_list['guide_price']}</i></span>
						</dd>
					</if>
				</dl>
				<dl class="on">
					<dd class="clr">
						<span class="fl">订单金额</span>
						<span class="fr">￥<i class="on" id="total_price">{pigcms{$scenic_ticket['total_price']}</i></span>
					</dd>
				</dl>
			</div>
		</div>
	</section>
<script type="text/javascript">
	var number = 0;
	var generate_status	=	true;
	/*数量加减*/
	$(document).ready(function(){
		//加门票
		$(".jia").click(function(){
			var calendar	=	$('#calendar').val();
			if(calendar == ''){
				alert('出行日期不能为空');
				return false;
			}
			var n=$(this).siblings("input").val();
			var num=parseInt(n)+1;
			$(this).siblings("input").val(num);
			if(num>1){
				$(this).siblings(".jian").addClass("on");
			}
			var ticket_price	=	$("#ticket_price").text()*1;
			var total_price	=	$("#total_price").text()*1;
			$("#ticket_number").text(num);
			$("#total_price").text(((total_price-0)+(ticket_price-0)).toFixed(2));
			$("#total_price_s").text(((total_price-0)+(ticket_price-0)).toFixed(2));
		});
		//减门票
		$(".jian").click(function(){
			var calendar	=	$('#calendar').val();
			if(calendar == ''){
				alert('出行日期不能为空');
				return false;
			}
			var n=$(this).siblings("input").val();
			var num=parseInt(n)-1;
			if(num==0){ alert("不能为0"); return; $(this).addClass("on");}
			$(this).siblings("input").val(num);
			if(num<2){
				$(this).removeClass("on");
			}
			var ticket_price	=	$("#ticket_price").text()*1;
			var total_price	=	$("#total_price").text()*1;
			$("#ticket_number").text(num);
			$("#total_price").text(((total_price-0)-(ticket_price-0)).toFixed(2));
			$("#total_price_s").text(((total_price-0)-(ticket_price-0)).toFixed(2));
		});
		$(".chk_7").attr("checked",true);
		var scenic_guide	=	"{pigcms{$scenic_guide}";
		var is_guide	=	"{pigcms{$scenic_list['is_guide']}";
		if(scenic_guide == 'Array' && is_guide != 1){
			$(".chk_6").attr("checked",false);
		}else{
			$(".chk_6").attr("checked",true);
		}
		var scenic_park	=	"{pigcms{$scenic_park}";
		var is_parking	=	"{pigcms{$scenic_list['is_parking']}";
		if(scenic_park == 'Array' && is_parking != 1){
			$(".chk_5").attr("checked",false);
		}else{
			$(".chk_5").attr("checked",true);
		}
	});
	/*常用旅客*/
	$(".enger a,.return .read").click(function(){
		if($(this).hasClass("on")){
			$(this).removeClass("on");
		}else{
			$(this).addClass("on");
		}
	})
	/*停车场关闭按钮*/
	$(".chk_5").click(function(){
		var ticket_price	=	$("#ticket_price").text()*1;
		var ticket_number	=	$("#ticket_number").text()*1;
		var guide_display	=	$(".form_right dd:eq(2)").css('display'); //选中向导
		if(guide_display == 'block'){
			var guide_price	=	$("#guide_price").text()*1;
		}
		if($(this).is(":checked")){
			$(this).parents(".dd_top").siblings(".gclose").slideDown();
			$(this).siblings(".close").addClass("open").text("开启");
			var park_price	=	$("#park_price").text()*1;
			var park_number	=	$("#park_number").text()*1;
			$(".form_right dd:eq(1)").css('display','block');
			if(guide_display == 'block'){
				$("#total_price").text((ticket_price*ticket_number+guide_price+park_price*park_number).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number+guide_price+park_price*park_number).toFixed(2));
			}else{
				$("#total_price").text((ticket_price*ticket_number+park_price*park_number).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number+park_price*park_number).toFixed(2));
			}
		}else{
			$(this).parents(".dd_top").siblings(".gclose").slideUp();
			$(this).siblings(".close").removeClass("open").text("关闭");
			$(".form_right dd:eq(1)").css('display','none');
			if(guide_display == 'block'){
				$("#total_price").text((ticket_price*ticket_number+guide_price).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number+guide_price).toFixed(2));
			}else{
				$("#total_price").text((ticket_price*ticket_number).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number).toFixed(2));
			}
		}
	})
	/*导游关闭按钮*/
	$(".chk_6").click(function(){
		var ticket_price	=	$("#ticket_price").text()*1;
		var ticket_number	=	$("#ticket_number").text()*1;
		var park_display	=	$(".form_right dd:eq(1)").css('display'); //选中车位
		if(park_display == 'block'){
			var park_price	=	$("#park_price").text()*1;
			var park_number	=	$("#park_number").text()*1;
		}
		if($(this).is(":checked")){
			$(this).parents(".dd_top").siblings(".gclose").slideDown();
			$(this).siblings(".close").addClass("open").text("开启");
			var guide_price	=	$("#guide_price").text()*1;
			$(".form_right dd:eq(2)").css('display','block');
			if(park_display == 'block'){
				$("#total_price").text((ticket_price*ticket_number+guide_price+park_price*park_number).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number+guide_price+park_price*park_number).toFixed(2));
			}else{
				$("#total_price").text((ticket_price*ticket_number+guide_price).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number+guide_price).toFixed(2));
			}
		}else{
			$(this).parents(".dd_top").siblings(".gclose").slideUp();
			$(this).siblings(".close").removeClass("open").text("关闭");
			$(".form_right dd:eq(2)").css('display','none');
			if(park_display == 'block'){
				$("#total_price").text((ticket_price*ticket_number+park_price*park_number).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number+park_price*park_number).toFixed(2));
			}else{
				$("#total_price").text((ticket_price*ticket_number).toFixed(2));
				$("#total_price_s").text((ticket_price*ticket_number).toFixed(2));
			}
		}
	})
	/*添加出行人*/
	$(".addson").click(function(){
		if(!user){
			$(".board").show();
			$(".mark").show();
			return false;
		}
		number = number+1;
		if(generate_status == true){
			generate_status	=	false;
			var family_name	=	$('#text1').val();
			var certificates=	$('#text3').val();
			var phone		=	$('#text2').val();
			var gender		=	$('.surface .p105 span.on').text();
			if(family_name == ''){
				generate_status	=	true;
				alert('姓名不能为空');
				return false;
			}
			var check	=	checkPhone(phone);
			if(check == false){
				generate_status	=	true;
				alert('请填写正确的手机号码');
				return false;
			}
			var check = checkCardId(certificates);
			if(check == false){
				generate_status	=	true;
				return false;
			}
			if(gender == '男'){
				gender	=	1;
			}else{
				gender	=	2;
			}
			$.ajax({
				type : "post",
				url : "{pigcms{:U('Scenic_user/add_travel_json')}",
				dataType : "json",
				data:{
					family_name	:	family_name,
					certificates	:	certificates,
					phone	:	phone,
					gender	:	gender,
				},
				success : function(result){
					if(result.errorCode == 0){
						var rideList	=	result.result.family_id;
						$(".enger").append("<a href='javascript:void(0)' class='"+number+"s' onclick='add("+number+")'>"+family_name+"<span style='display:none;'>"+rideList+"</a>");
						$(".date_list .date").find("input").val("");
						generate_status	=	true;
					}else{
						alert(result.errorMsg);
						generate_status	=	true;
					}
				},
				error:function(){
					alert('提交订单失败');
					generate_status	=	true;
				}
			})
		}
	})
	/*男女切换*/
	$(".menw span").click(function(){
		$(this).addClass("on").siblings("span").removeClass("on");
	})
	function add(number){
		if($("."+number+"s").hasClass("on")){
			$("."+number+"s").removeClass("on");
		}else{
			$("."+number+"s").addClass("on");
		}
	}
	/*添加停车位*/
	$(".addcar").click(function(){
		number = number+1;
		$(this).siblings(".date").append("<div id='"+number+"' class='stop_list'><div class='surface clr'><span class='on ou fl'><i></i><em>车牌号：</em></span><div class='p105 clr'><input type='text' placeholder='请输入车牌号'><em>请输入车牌号</em></div>    </div><a href='javascript:void(0)' class='del' onclick='removes("+number+")'>删除</a></div>");
		var park_price	=	$("#park_price").text()*1;
		var total_price	=	$("#total_price").text()*1;
		var park_number	=	$("#park_number").text()*1;
		$("#park_number").text(park_number+1);
		$("#total_price").text((total_price+park_price).toFixed(2));
		$("#total_price_s").text((total_price+park_price).toFixed(2));
	})
	/*删除停车位*/
	function removes(number){
		$("#"+number+"").fadeOut(function(){
			$("#"+number+"").remove();
		});
		var park_price	=	$("#park_price").text()*1;
		var total_price	=	$("#total_price").text()*1;
		var park_number	=	$("#park_number").text()*1;
		$("#park_number").text(park_number-1);
		$("#total_price").text((total_price-park_price).toFixed(2));
		$("#total_price_s").text((total_price-park_price).toFixed(2));
	}
	/*更换导游*/
	$(".replace").click(function(){
		$(".layer").fadeIn();
		$(".lay_top a").click(function(){
			$(".layer").fadeOut();
		})
		$(".lay_end li").click(function(){
			$(".layer").fadeOut();
			$(this).addClass("on").siblings("li").removeClass("on");
			var src = $(this).find("img").attr('src'),p1=$(this).find(".p1").html(),p2=$(this).find(".p2").html(),name_top=$(this).find(".name_top").html(),none=$(this).find(".none").html();
			$(".gclose .date").find(".p1").html(p1);
			$(".gclose .date").find(".p2").html(p2);
			$(".gclose .date").find(".name_top").html(name_top);
			$(".gclose .date").find("img").attr('src',src);
			$(".gclose .date").find(".none").html(none);
		})
	})
	/*预订须知切换*/
	$(".park li").click(function(){
		$(this).addClass("on").siblings().removeClass("on");
		var index=$(this).index();
		$(".park_list").eq(index).show().siblings(".park_list").hide();
	});
	/*游伴 按钮*/
	$(".chk_7").click(function(){
		if($(this).is(":checked")){
			$(this).parents(".dd_top").siblings(".gclose").slideDown();
			$(this).siblings(".close").addClass("open").text("开启");
		}else{
			$(this).parents(".dd_top").siblings(".gclose").slideUp();
			$(this).siblings(".close").removeClass("open").text("关闭");
		}
	})
	/*提交按钮*/
	function sumbit(){
		if(!user){
			$(".board").show();
			$(".mark").show();
			return false;
		}
		if(generate_status == true){
			generate_status	=	false;
			var calendar	=	$('#calendar').val();	//预定日期
			if(calendar == ''){
				generate_status	=	true;
				alert('出行日期不能为空');
				return false;
        	}
			var data_split	=	calendar.split('-');
			var mydate		=	new Date();
			var get_year	=	mydate.getFullYear();
			var get_moth	=	mydate.getMonth()+1; //获取当前月份(0-11,0代表1月)
			var get_date	=	mydate.getDate(); //获取当前日(1-31)
			if(data_split[0] < get_year){
				generate_status	=	true;
				alert('不能选择已过期年份');
				return false;
			}else if(data_split[0] == get_year){
				if(data_split[1] < get_moth){
					generate_status	=	true;
					alert('不能选择已过期月份');
					return false;
				}else if(data_split[1] == get_moth){
					if(data_split[2] < get_date){
						generate_status	=	true;
						alert('不能选择已过期日期');
						return false;
					}
				}
			}
			var ticket_number	=	$('#ticket_number').text(); //门票数量
			if(ticket_number < 1){
				generate_status	=	true;
				alert('门票数量不能少于1');
				return false;
			}
			var travel	=	'';
			var travel_number	=	0;
			$(".common .enger a.on").each(function(){
				var on	=	$(this).text();
				if(on){
					travel	=	travel+','+$(this).children('span').text();
					travel_number	=	travel_number+1;
				}
            });
			if(!travel_number){
				generate_status	=	true;
				alert('请选择出行人');
				return false;
        	}
			if(ticket_number > travel_number){
				generate_status	=	true;
				alert('出行人少于门票数量，请添加出行人');
				return false;
			}else if(ticket_number < travel_number){
				generate_status	=	true;
				alert('门票少于出行人数量，请添加门票');
				return false;
			}
			var name	=	$('#name').val();		//取票人
			if(!name){
				generate_status	=	true;
				alert('请填写取票人');
				return false;
			}
			var phone	=	$("#phone").val();		//取票人电话
			if(!phone){
				generate_status	=	true;
				alert('请填写取票人电话');
				return false;
			}
			var check	=	checkPhone(phone);
			if(check == false){
				generate_status	=	true;
				alert('请填写正确的手机号码');
				return false;
			}
			var park_display	=	$(".form_right dd:eq(1)").css('display'); //选中车位
			var park_id	=	'';
			var park_number	=	0;
			if(park_display == 'block'){
				$(".gclose .date .stop_list .surface .p105").each(function(){
					var park_val	=	$(this).children('input').val();
					if(!park_val){
						generate_status	=	true;
						alert('请填写车牌号');
						return false;
					}
					park_id	=	park_id+','+park_val;	//车牌号
					park_number	=	park_number+1;
				});
			}
			var guide_display	=	$(".form_right dd:eq(2)").css('display'); //选中向导
			var guide_id	=	0;
			if(guide_display == 'block'){
				guide_id	=	$(".gclose .date").find(".none").text();//向导ID
			}
			var mate_display	=	$("#checkbox_c3i").text(); //选中游伴
			var checkbox_c3_text	=	'';
			if(mate_display == '开启'){
				checkbox_c3_text	=	$("#checkbox_c3_text").val();
				if(!checkbox_c3_text){
					generate_status	=	true;
					alert('请填写结伴要求');
					return false;
				}
			}
			var clause	=	$("#clause").attr("class");
			if(clause == 'read fr'){
				generate_status	=	true;
				alert('请勾选合同条款');
				return false;
			}
			$.ajax({
				type : "post",
				url : "{pigcms{:U('order_json')}",
				dataType : "json",
				data:{
					ticket_id	:	'{pigcms{$scenic_ticket.ticket_id}',
					ticket_time	:	calendar,	//出行时间
					num		:	ticket_number,	//门票数量
					family_id	:	travel,		//出行人ID
					truename	:	name,	//取票人
					phone		:	phone,	//取票电话
					park	:	park_number,//车位数量
					park_id	:	park_id,	//车位车牌号
					park_ids	:	'{pigcms{$scenic_park.parking_id}',	//车位车牌号
					guide_id	:	guide_id,	//导游ID
					mate		:	checkbox_c3_text,	//游玩要求
				},
				success : function(result){
					var rideList	=	result;
					if(result.errorCode == 0){
						window.location.href	=	rideList.result;
					}else if(result.errorCode == '40000038'){
						window.location.href	=	"{pigcms{:U('Scenic_list/details',array('scenic_id'=>$scenic_ticket['scenic_id']))}";
						alert(result.errorMsg);
						generate_status	=	true;
					}else{
						alert(result.errorMsg);
						generate_status	=	true;
					}
				},
				error:function(){
					alert('提交订单失败');
					generate_status	=	true;
				}
			})
		}
	}
</script>
<!-- 日历 -->
<include file="Public/footer"/>
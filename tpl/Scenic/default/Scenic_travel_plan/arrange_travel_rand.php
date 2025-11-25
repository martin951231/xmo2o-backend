<include file="Public/header"/>
<include file="Public/header_city"/>
<link href="{pigcms{$static_path}css/mobiscroll.2.13.2.css" rel="stylesheet"/>
<script src="{pigcms{$static_path}js/mobiscroll.2.13.2.js"></script>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li>
					<a href="{pigcms{:U('Index/index')}">网站首页</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_list/index')}">景点门票</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--要求规划行程-->
	<form id="myform">
	<section class="groom">
		<div class="w1060">
			<div class="groom_n">
				<h2>旅行要求规划行程</h2>
				<div class="present">
					<ul>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>当前城市：</em>
							</div>
							<div class="p112">
								<h5>{pigcms{$now_city['area_name']}</h5>
							</div>
						</li>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>开始日期：</em>
							</div>
							<div class="p112">
								<input type="text" name="start_time" class="date_pre" placeholder="请选择您的出行日期" id="demo">
								<em class="date_pre"></em>
							</div>
						</li>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>总&nbsp&nbsp天&nbsp数：</em>
							</div>
							<div class="p112">
								<input name="day" type="text" class="w138" value="1"><i>天</i>
							</div>
						</li>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>是否开车：</em>
							</div>
							<div class="p112 menw">
								<span class="on">是</span>
								<span>否</span>
							</div>
						</li>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>预&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp算：</em>
							</div>
							<div class="p112">
								<input name="total_money" type="text" placeholder="请输入预算">
							</div>
						</li>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>星&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp座：</em>
							</div>
							<div class="p112 pr_ljt">
								<input type="text" placeholder="请选择星座" id="xz_dummy">
								<select id="xz"  class="demo-test-select" data-role="none" style="display: none;">
									<option value="">白羊座</option>
									<option>金牛座</option>
									<option>双子座</option>
									<option>巨蟹座</option>
									<option>狮子座</option>
									<option>处女座</option>
									<option>天秤座</option>
									<option>天蝎座</option>
									<option>射手座</option>
									<option>摩羯座</option>
									<option>水瓶座</option>
									<option>双鱼座</option>
								</select>
							</div>
						</li>
						<li class="clr">
							<div class="fl clr pre_let">
								<i></i>
								<em>总&nbsp&nbsp人&nbsp数：</em>
							</div>
							<div class="p112">
								<input name="people_num" type="text" placeholder="请输入总数">
							</div>
						</li>
						<div class="Plan" id="buttom">开始规划行程</div>
						<!--<li class="clr" style="padding-left:50px;">
							<a href="javascript:void(0)"><div class="buttom" style="color:#ffffff;font-size:18px;font-weight:bold;line-height:54px;text-align:center;background:#fe6903;width:30%">开始规划行程</div></a>
						</li>-->
					</ul>
				</div>
			</div>
		</div>
	</section>
	</form>
	<div id="pwd_bg" class="bg bg_style" style="display:none;"></div>
	<img class="bg" src="{pigcms{$static_path}images/loadh.gif"  style="display: none;width:200px; height:200px; border-radius:50%;top:60%;position: absolute;left:40%;z-index: 9999;">
	<script type="text/javascript">
		$(".menw span").click(function(){
			$(this).addClass("on").siblings().removeClass("on");
		})
		var user_info	=	"{pigcms{$_SESSION['user']}";
		//插件日历
		$(function(){
			var currYear = new Date().getFullYear();
			var opt = {
				'dateYMD': {
					preset: 'date',
					dateFormat: 'yyyy-mm-dd',
					theme: 'android-ics light', //皮肤样式
					display: 'modal',           //显示方式
					mode: 'scroller',           //日期选择模式
					showNow: true,
					nowText: "今天",
					startYear: currYear,    //开始年份
					endYear: currYear + 10, //结束年份
					minDate: new Date(),    //只能选择
				},'select': {
					preset: 'select'
				}
			}
			$('#demo').scroller($.extend(opt['dateYMD'],opt['default']));
			$('.demo-test-select').scroller($.extend(opt['select'],opt['default']));
			var submit_status	=	true;
			$('#buttom').click(function(){
				if(!user_info){
					$(".board").show();
					$(".mark").show();
					return false;
				}
				if(submit_status == true){
					submit_status	=	false;
					$('#dirving').attr('value',$('.car').find('.fr .on').attr('data-id'));
					$.ajax({
						url: "{pigcms{:U('Scenic_travel_plan/arrange_travel_rand')}",
						type: 'POST',
						dataType: 'json',
						data: $('#myform').serialize(),
						beforeSend:function(){
							$('.bg').show();
						},
						success:function(data){
							if(data.error_code){
								submit_status	=	true;
								$('.bg').hide();
								alert(data.msg);
							}else{
								location.href = "{pigcms{:U('Scenic_travel_plan/travel_plan_detial')}&sor=1&id="+data.id;
							}
						}
					});
				}
			});
		});
	</script>
<include file="Public/footer"/>
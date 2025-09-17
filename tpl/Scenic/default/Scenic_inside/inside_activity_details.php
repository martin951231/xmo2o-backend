<include file="Public/header"/>
<include file="Public/header_city"/>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li>
					<a href="{pigcms{:U('Index/index')}">网站首页</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_list/index')}">景点门票</a>
				</li>
				<li>
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
	<!--小导航-->
	<section class="ticklist">
		<div class="w1200">
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('Index/index')}">网站首页</a> > <a href="{pigcms{:U('Scenic_list/index')}">景点门票</a> > <span class="now">活动详情</span>
			</div>
		</div>
	</section>
	<!--活动详情-->
	<section class="carousel">
		<div class="w1200 clr">
			<div class="carousel_n clr">
				<div class="fl">
					<div class="game">
						<ul class="bigImg">
							<volist name="pic" id="vo">
								<li>
									<img src="{pigcms{$vo}">
								</li>
							</volist>
						</ul>
						<div class="smallScroll">
							<a class="sPrev" href="javascript:void(0)"></a>
								<div class="smallImg">
									<ul>
										<volist name="pic" id="vv">
											<li><img src="{pigcms{$vv}"></li>
										</volist>
									</ul>
								</div>
							<a class="sNext" href="javascript:void(0)"></a>
						</div>
						<div class="pageState"></div>
					</div>
				</div>
				<div class="p590">
					<h1>{pigcms{$activity['activity_title']}</h1>
					<p class="p_text">{pigcms{$activity['activity_stitle']}</p>
					<div class="text_bot clr">
						<div class="fl">
							<div class="bot_list">
								<span class="fl">活动时间：</span>
								<div class="p85">{pigcms{$activity['start_time']}</div>
							</div>
							<div class="bot_list2">
								<span class="fl">已报名数：</span>
								<div class="p85"><i>{pigcms{$activity['sign_number']}</i>人</div>
							</div>
						</div>
						<div class="fr">
							<if condition="$activity['status'] eq 1"><div class="jx rig">正在进行</div><else/>
								<div class="js rig">活动结束</div>
							</if>
						</div>
					</div>
					<if condition="$activity['status'] eq 1">
						<if condition="$is_enroll eq 0"><div class="sub jx" id="sign_up">我要报名</div>
						<else/><div class="sub jx">您已报名</div>
						</if>
					<else/>
						<div class="sub js">活动结束</div>
					</if>
				</div>
			</div>
		</div>
	</section>
	<!--景点推荐-->
	<section class="exercise">
		<div class="w1200 clr">
			<div class="fl w908">
				<div class="exe_top clr">
					<span>活动介绍</span>
				</div>
				<div class="exe_text">{pigcms{$activity['activity_content']}</div>
			</div>
			<if condition="$groom_activity">
				<div class="fr w268">
					<div class="w268_top">
						<span>其他活动</span>
					</div>
					<div class="public_end activity clr">
						<dl class="clr">
							<volist name="groom_activity" id="vc">
								<dd>
									<a href="#">
										<div class="img">
											<img src="{pigcms{$vc['pic']}" width=100% height=153>
										</div>
										<div class="text">
											<h5>{pigcms{$vc['activity_title']}</h5>
											<div class="text_bot">
												<div class="bot_list clr">
													<span class="fl">活动时间：</span>
													<div class="p85">{pigcms{$vc['start_time']}</div>
												</div>
												<div class="bot_list2 clr">
													<span class="fl">已报名数：</span>
													<div class="p85"><i>{pigcms{$vc['sign_number']}</i>人</div>
												</div>
											</div>
										</div>
									</a>
									<div class="posi jx">正在进行</div>
								</dd>
							</volist>
						</dl>
					</div>
				</div>
			</if>
		</div>
	</section>
	<script type="text/javascript">
		var user	=	"{pigcms{$_SESSION['user']}";
		jQuery(".game").slide({ titCell:".smallImg li", mainCell:".bigImg", effect:"left"});
		jQuery(".game .smallScroll").slide({ mainCell:"ul",effect:"left",scroll:1,vis:4,pnLoop:false,autoPage:true,prevCell:".sPrev",nextCell:".sNext" });
		var generate_status = true;
		$("#sign_up").click(function(){
			if(user){
				if(generate_status == true){
					generate_status = false;
					$.ajax({
						type : "post",
						url : "{pigcms{:U('Scenic_inside/enroll_json')}",
						dataType : "json",
						data:{
							activity_id	:	"{pigcms{$activity['activity_id']}",
						},
						async:false,
						success : function(date){
							if(date.errorCode == 0){
								window.location.reload();
							}else if(date.errorCode == '40000026'){
								alert('请先购买活动当天的门票');
								generate_status	=	true;
								return false;
							}else{
								alert(date.errorMsg);
								generate_status	=	true;
								return false;
							}
						},
						error:function(){
							alert('网络错误');
							generate_status	=	true;
							return false;
						}
					})
				}
			}else{
				$(".board").show();
				$(".mark").show();
			}
		})
	</script>
<include file="Public/footer"/>
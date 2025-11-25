<include file="Public/header"/>
<include file="Public/header_city"/>
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
	<!--形成规划-->
	<section class="plan">
		<div class="w1200">
			<a href="{pigcms{:U('select_scenic')}" class="p_url spot">按景区推荐行程</a>
			<a href="{pigcms{:U('arrange_travel_rand')}" class="p_url want">按旅行要求推荐行程</a>
		</div>
	</section>
	<!--热门行程-->
	<section class="hotpaln">
		<div class="w1200">
			<div class="hotpaln_top clr">
				<h2 class="fl">热门行程</h2>
				<a id="my_plan" href="#" class="fr">查看我的行程 ></a>
			</div>
			<div class="hotpaln_end">
				<ul class="clr">
					<volist name="travel_plan" id="vo">
						<li>
							<a href="{pigcms{:U('Scenic_travel_plan/travel_plan_detial',array('id'=>$vo['id']))}">
								<div class="img">
									<img src="{pigcms{$vo['scenic_pics']}" width="100%" height="138">
									<div class="hot_guide clr">
										<div class="imgs fl">
											<img src="{pigcms{$vo['avatar']}"  width="50" height="50">
										</div>
										<div class="p55">
											<h3>{pigcms{$vo['nickname']}</h3>
											<p>{pigcms{$vo['update_times']}</p>
										</div>
									</div>
									<div class="bj"></div>
								</div>
								<div class="text">
									<h2>{pigcms{$vo['city_name']}{pigcms{$vo['day']}日行程计划</h2>
									<div class="trip clr">
										<span class="fl">行程路线：</span>
										<p class="p70"><volist name="vo['scenic_name']" id="voo"><if condition="$key neq 0"> > </if><i>{pigcms{$voo}</i></volist></p>
									</div>
									<div class="trip clr">
										<span class="fl">行程天数：</span>
										<p class="p70">{pigcms{$vo['day']}天</p>
									</div>
								</div>
								<div class="eye">{pigcms{$vo['see_number']}</div>
							</a>
						</li>
					</volist>
				</ul>
			</div>
			<div class="pages">
				<span class="num">{pigcms{$pagebar}</span>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		var user	=	"{pigcms{$_SESSION['user']}";
		$(".hotpaln_end li:nth-child(3n)").css("margin-right","0px");
		$("#my_plan").click(function(){
			if(!user){
				$(".board").show();
				$(".mark").show();
				return false;
			}
			window.location.href	=	"{pigcms{:U('Scenic_user/manager_travel')}";
		})
	</script>
<include file="Public/footer"/>
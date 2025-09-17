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
			<div class="banimg" style="background:url({pigcms{$scenic_advert['advert_img']}) top center no-repeat;background-size:100%;margin-top:10px;"></div>
			<div class="tklist_top">
				<span>您当前位置：</span><a href="{pigcms{:U('Index/index')}">网站首页</a> > <a href="{pigcms{:U('Scenic_list/index')}">景点门票</a> > <span class="now">活动列表</span>
			</div>
		</div>
	</section>
	<!--活动列表-->
	<section class="flexible">
		<div class="w1200">
			<div class="flexible_ul">
				<ul class="clr">
					<a href="{pigcms{:U('Scenic_inside/inside_activity',array('scenic_id'=>$scenic_id))}"><li <if condition="!$_GET['status']">class="on"</if>>全部</li></a>
					<a href="{pigcms{:U('Scenic_inside/inside_activity',array('scenic_id'=>$scenic_id,'status'=>1))}"><li <if condition="$_GET['status'] eq 1">class="on"</if>>正在进行</li></a>
					<a href="{pigcms{:U('Scenic_inside/inside_activity',array('scenic_id'=>$scenic_id,'status'=>2))}"><li <if condition="$_GET['status'] eq 2">class="on"</if>>往期活动</li></a>
				</ul>
			</div>
			<div class="flexible_end">
				<ul>
					<volist name="activity" id="vo">
						<a href="{pigcms{$vo['url']}">
							<li>
								<div class="fl">
									<img src="{pigcms{$vo['pic']}" width="392" height="260">
								</div>
								<div class="p420">
									<h1><if condition="$vo['status'] eq 1"><span class="jx">正在进行</span><else/><span class="js">活动结束</span></if>{pigcms{$vo['activity_title']}</h1>
									<p class="p_text">{pigcms{$vo['activity_stitle']}</p>
									<div class="text_bot clr">
										<div class="fl">
											<div class="bot_list">
												<span class="fl">活动时间：</span>
												<div class="p85">{pigcms{$vo['start_time']}</div>
											</div>
											<div class="bot_list2">
												<span class="fl">已报名数：</span>
												<div class="p85"><i>{pigcms{$vo['sign_number']}</i>人</div>
											</div>
										</div>
									</div>
								</div>
							</li>
						</a>
					</volist>
				</ul>
			</div>
			<style type="text/css">
				.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
			</style>
			<div class="pages">
				<span class="num">{pigcms{$pagebar}</span>
			</div>
		</div>
	</section>
<include file="Public/footer"/>
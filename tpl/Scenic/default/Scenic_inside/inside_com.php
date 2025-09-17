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
				<span>您当前位置：</span><a href="{pigcms{:U('Scenic_list/index')}">景点门票</a> > <a href="{pigcms{:U('Scenic_list/details',array('scenic_id'=>$find['scenic_id']))}">门票详情 </a> > <span class="now">{pigcms{$find['cat_name']}列表</span>
			</div>
		</div>
	</section>
	<!--推荐列表-->
    <section class="share_list">
        <div class="w1200">
            <ul class="clr">
            	<volist name="activity" id="vo">
	                <li>
	                    <a href="{pigcms{$vo['url']}">
	                        <div class="img">
	                            <img src="{pigcms{$vo['com_img']}" width="250" height="202">
	                        </div>
	                        <div class="text">
	                            <h2>{pigcms{$vo['com_name']}</h2>
	                            <div class="clr text_bot">
	                                <div class="fl">￥<i>{pigcms{$vo['price']}</i></div>
<!--	                                <div class="fr">已售400</div>-->
	                            </div>
	                        </div>
	                    </a>
	                </li>
                </volist>
            </ul>
			<style type="text/css">
				.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
			</style>
			<div class="pages">
				<span class="num">{pigcms{$pagebar}</span>
			</div>
        </div>
    </section>
<include file="Public/footer"/>
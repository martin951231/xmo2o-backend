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
				<li>
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--导游列表-->
    <section class="chart">
        <div class="ban_img">
            <img src="{pigcms{$static_path}images/tp_02.jpg" width="100%" height="260">
        </div>
        <div class="filter">
            <div class="w1200 clr">
                <div class="fl clr">
                    <span class="fl sexual">向导性别</span>
                    <div class="p72">
                        <a href="{pigcms{:U('Scenic_aguide/index')}"><span <if condition="!$_GET['guide_sex']">class="on"</if>>全部<i></i></span></a>
                        <a href="{pigcms{:U('Scenic_aguide/index',array('guide_sex'=>1))}"><span <if condition="$_GET['guide_sex'] eq 1">class="on"</if>>男<i></i></span></a>
                        <a href="{pigcms{:U('Scenic_aguide/index',array('guide_sex'=>2))}"><span <if condition="$_GET['guide_sex'] eq 2">class="on"</if>>女<i></i></span></a>
                    </div>
                </div>
                <div id="search_guide" class="fr fil_input">
                    <input id="name" type="text" placeholder="导游名字">
                    <a id="submit" href="javascript:void(0)"></a>
                </div>
            </div>
        </div>
        <div class="chart_list">
            <div class="w1200">
                <ul class="clr">
                	<volist name="list" id="vo">
	                    <li>
	                        <a href="{pigcms{:U('Scenic_aguide/details',array('guide_id'=>$vo['guide_id']))}">
	                            <div class="img">
	                                <img src="{pigcms{$vo['guide_pics']}" width="383" height="194">
	                            </div>
	                            <div class="text">
	                                <div class="text_top">
	                                    <span class="name">{pigcms{$vo['guide_name']}</span>
	                                    <span class="old">{pigcms{$vo['date']}</span>
	                                </div>
	                                <div class="text_p">{pigcms{$vo['guide_autograph']}</div>
	                                <div class="call">
	                                    <if condition="$vo['real_name'] eq 1"><span>实名</span></if>
	                                    <if condition="$vo['guide_status'] eq 1"><span>导游证</span></if>
	                                </div>
	                                <div class="chief">
	                                    <span class="price">￥<i>{pigcms{$vo['guide_price']}</i></span>/天
	                                </div>
	                            </div>
	                            <div class="pox">
	                                <div class="pox_img">
	                                    <img src="{pigcms{$vo['avatar']}" width="74" height="74">
	                                    <if condition="$vo['guide_sex'] eq 1"><i class="man"></i><else/><i class="woman"></i></if>
	                                </div>
	                            </div>
	                        </a>
	                    </li>
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
    <script type="text/javascript">
        //$(".filter .p72 span").click(function(){
//            $(this).addClass("on").siblings("span").removeClass("on");
//        })
        $(".chart_list li:nth-child(3n)").css("margin-right","0px");
        $("#submit").click(function(){
			var name	=	$("#name").val();
			if(!name){
				alert('导游名字不能为空');
				return false;
			}
			window.location.href	=	'{pigcms{$site_url}'+'scenic.php?c=Scenic_aguide&a=index&guide_name='+name;
		})
		$("#search_guide").keydown(function(event){
			if (event.keyCode == "13") {//keyCode=13是回车键
				var name	=	$("#name").val();
				if(!name){
					alert('导游名字不能为空');
					return false;
				}
				window.location.href	=	'{pigcms{$site_url}'+'scenic.php?c=Scenic_aguide&a=index&guide_name='+name;
			}
		});
    </script>
<include file="Public/footer"/>
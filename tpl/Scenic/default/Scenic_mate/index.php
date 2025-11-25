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
				<li>
					<a href="{pigcms{:U('Scenic_travel_plan/index')}">行程规划</a>
				</li>
				<li class="pull on">
					<a href="{pigcms{:U('Scenic_mate/index')}">寻找游伴</a>
				</li>
				<li>
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--banner-->
	<section class="seek">
		<h2 class="clr"><i>我们共有</i><span>{pigcms{$scenic_count}</span><i>个结伴</i></h2>
		<p class="f36">想去看世界 还差一个你</p>
		<p class="f14">{pigcms{$people_number[0]['people_number_s']}人报名 · {pigcms{$people_number[0]['words_number_s']}人留言</p>
	</section>
        <section class="apellation">
            <div class="apell_top">
                <div class="w1200">
                    <div class="apell clr">
                        <div class="fl postion">
                            <input type="text" class="view" placeholder="景点名称" readonly="readonly">
                            <div class="absolu clr">
                                <ul class="all">
                                    <a href="{pigcms{:U('Scenic_mate/index')}">
                                        <span class="num"><em>{pigcms{$scenic_count}</em>个结伴</span>
                                        全部
                                    </a>
                                </ul>
                                <ul class="col1">
                                	<volist name="area" id="vv">
                                		<if condition="$vv['scenic_list']">
		                                    <li <if condition="$key eq 0">class="on"</if>>
		                                        <a href="javascript:void(0)">
		                                            {pigcms{$vv['area_name']}
		                                            <i class="icon-arrow"></i>
		                                        </a>
		                                    </li>
	                                    </if>
                                    </volist>
                                </ul>
                                <volist name="area" id="vvv">
	                            	<if condition="$vvv['scenic_list']">
	                                	<ul class="col2" <if condition="$key neq 0">style="display:none;"</if>>
	                                		<volist name="vvv['scenic_list']" id="vvo">
			                                    <li>
			                                        <a href="javascript:void(0)" onclick="fix({pigcms{$vvo['scenic_id']})" class="clr">
			                                            <span class="num"><em>{pigcms{$vvo['mate_number']}</em> 结伴</span>
			                                            <i>{pigcms{$vvo['scenic_title']}</i>
			                                        </a>
			                                    </li>
		                                    </volist>
	                                	</ul>
	                                </if>
                                </volist>
                            </div>
                        </div>
                        <!--<div class="fl">
                            <input type="text" class="employ" placeholder="游玩时间" id="demo">
                        </div>-->
                        <ul class="clr fl cli">
                        	<a onclick="fixs()" href="javascript:void(0)">
	                            <li <if condition="!$_GET['status']">class="on"</if>>
	                                即将出发
	                                <i></i>
	                            </li>
                            </a>
                        	<a onclick="fixs('hot')" href="javascript:void(0)">
	                            <li <if condition="$_GET['status'] eq 'hot'">class="on"</if>>
	                                热门结伴
	                                <i></i>
	                            </li>
                            </a>
                        	<a onclick="fixs('new')" href="javascript:void(0)">
	                            <li <if condition="$_GET['status'] eq 'new'">class="on"</if>>
	                                最新发布
	                                <i></i>
	                            </li>
                            </a>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="h82"></div>
            <div class="apell_end">
                <div class="w1200">
                    <ul class="clr">
                    	<volist name="scenic_mate" id="vo">
	                        <li>
	                            <a href="{pigcms{$vo['url']}">
	                                <div class="ape_top">
	                                    <h2>{pigcms{$vo['scenic_list']['scenic_title']}</h2>
	                                    <div class="clr">
	                                        <div class="fl">游玩时间：{pigcms{$vo['start_time']}</div>
	                                        <div class="fr">
	                                            <span class="rt">{pigcms{$vo['people_number']}</span>
	                                            <span class="pl">{pigcms{$vo['words_number']}</span>
	                                        </div>
	                                    </div>
	                                </div>
	                                <div class="text_p">{pigcms{$vo['mate_intr']}</div>
	                                <div class="pho clr">
	                                    <div class="img fl">
	                                        <img src="{pigcms{$vo['scenic_user']['avatar']}" width="74" height="74">
	                                    </div>
	                                    <div class="p85">
	                                        <h2>{pigcms{$vo['scenic_user']['nickname']}</h2>
	                                        <p>发布时间：{pigcms{$vo['create_time']}</p>
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
		$(".firespot li:last-child").css("margin-right","0px");
		$(".apell_end li:nth-child(3n)").css("margin-right","0px");
		//插件日历
		$(function () {
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
		});
		$(window).scroll(function(){
			if ($(window).scrollTop() > $(".apellation").offset().top){
				$(".apellation .apell_top").addClass("apell_top_on");
				$(".h82").show();
			}else{
				$(".apellation .apell_top").removeClass("apell_top_on");
				$(".h82").hide();
			}
		})
		$(".view").click(function(event){
			event.stopPropagation();
			$(".absolu").show();
			$(".col1 li").click(function(event){
				event.stopPropagation();
				var index = $(this).index();
				$(this).addClass("on").siblings("li").removeClass("on");
				$(".col2").eq(index).show().siblings(".col2").hide();
			})
			$(".col2 a").click(function(event){
				event.stopPropagation();
				$(".absolu").hide();
				var text=$(this).find("i").text();
				$(".view").val(text);
			})
		})
		function fix(scenic_id){
			var status	=	"{pigcms{$_GET['status']}";
			if(status){
				window.location.href	=	"{pigcms{:U('Scenic_mate/index')}&scenic_id="+scenic_id+"&status="+status;
			}else{
				window.location.href	=	"{pigcms{:U('Scenic_mate/index')}&scenic_id="+scenic_id;
			}
		}
		function fixs(status){
			var scenic_id	=	"{pigcms{$_GET['scenic_id']}";
			if(scenic_id){
				window.location.href	=	"{pigcms{:U('Scenic_mate/index')}&scenic_id="+scenic_id+"&status="+status;
			}else{
				if(status){
					window.location.href	=	"{pigcms{:U('Scenic_mate/index')}&status="+status;
				}else{
					window.location.href	=	"{pigcms{:U('Scenic_mate/index')}";
				}
			}
		}
		$(document).click(function(event){
                $('.absolu').hide();
            })
	</script>
<include file="Public/footer"/>




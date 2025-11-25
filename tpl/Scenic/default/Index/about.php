<include file="Public/header"/>
<include file="Public/header_city"/>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li class="pull on">
					<a href="https://www.0855mlh.com/">网站首页</a>
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
				<li>
					<a href="{pigcms{:U('Scenic_aguide/index')}">当地向导</a>
				</li>
			</ul>
		</div>
	</section>
	<!--关于我们-->
    <section class="about">
        <div class="w1200 clr">
            <div class="fl about_l">
                <ul>
                	<if condition="$_GET['com_id'] AND $_GET['about_id']">
                		<volist name="about_category" id="v" key="k">
		                    <li <if condition="$v['is_default'] eq 1">class="on"</if>>
		                        <span>{pigcms{$v['com_title']}</span>
			                    <dl <if condition="$v['is_default'] eq 1">style="display:block;"</if>>
			                        <volist name="v['about']" id="vv" key="kk">
			                            <dd onclick="about({pigcms{$vv['about_id']})" <if condition="$vv['is_default'] eq 1">class="on"</if>>{pigcms{$vv['about_title']}</dd>
	                        		</volist>
			                    </dl>
		                    </li>
	                    </volist>
	                <else/>
	                	<volist name="about_category" id="v" key="k">
		                    <li <if condition="$k eq 1">class="on"</if>>
		                        <span>{pigcms{$v['com_title']}</span>
			                    <dl <if condition="$k eq 1">style="display: block;"</if>>
			                        <volist name="v['about']" id="vv" key="kk">
			                            <dd onclick="about({pigcms{$vv['about_id']})" <if condition="$kk eq 1">class="on"</if>>{pigcms{$vv['about_title']}</dd>
	                        		</volist>
			                    </dl>
		                    </li>
	                    </volist>
                    </if>
                </ul>
            </div>
            <div class="about_r">
	            <ul>
		            <li>
		                <div id="top" class="top">{pigcms{$is_default}</div>
		                <div id="end" class="end">{pigcms{$is_default_son}</div>
		            </li>
	            </ul>
            </div>
        </div>
    </section>
	<script type="text/javascript">
		$(".about_l li span").click(function(){
			$(this).siblings("dl").slideDown();
			$(this).parents("li").addClass("on").siblings("li").removeClass("on").find("dl").slideUp();
		})
		$(".about_l li dd").click(function(){
			$(this).parents("li").siblings("li").find("dd").removeClass("on");
			$(this).addClass("on").siblings("dd").removeClass("on");
		})
		function about(about_id){
			$.ajax({
				type : "post",
				url : "{pigcms{:U('about_json')}",
				dataType : "json",
				data:{
					about_id		:	about_id,
				},
				async:false,
				success : function(result){
					if(result.errorCode == 0){
						$("#top").text(result.result.about_title);
						$("#end").html(result.result.about_text);
					}
				},
				error:function(){
					alert('获取失败');
				}
			})
		}
	</script>
<include file="Public/footer"/>
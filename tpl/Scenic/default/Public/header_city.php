	<!--搜索-->
	<section class="search">
		<div class="w1200 clr">
			<div class="fl">
				<div class="logo fl">
					<a href="{pigcms{:U('Index/index')}"><img src="{pigcms{$config['site_logo']}" width=160 height=43></a>
				</div>
				<div class="land fl">
					<i></i>
					<span><b>{pigcms{$scenic_now_city['area_name']}</b></span>
					<em style="z-index:1554;"></em>
					 <if condition="$config['many_city'] eq 1">
					<div class="landname">
						<if condition="$hot_area">
							<div class="lname_top">
								<h5>热门城市</h5>
								<ul class="clr">
									<volist name="hot_area" id="vv">
										<li onclick="now_city({pigcms{$vv['area_id']})">{pigcms{$vv['area_name']}</li>
									</volist>
								</ul>
							</div>
						</if>
						<div class="lname_end">
							<ul  class="clr ul_nav">
								<li class="on">ABCDE</li>
								<li>FGHJ</li>
								<li>KLMNP</li>
								<li>QRSTW</li>
								<li>XYZ</li>
							</ul>
							<volist name="all_area" id="vo">
								<dl <if condition="$key neq 'ABCDE'">style="display:none;"</if>>
									<volist name="vo" id="voo">
										<dd>
											<h5>{pigcms{$key}</h5>
											<ul class="clr">
												<volist name="voo" id="vooo">
													<li onclick="now_city({pigcms{$vooo['area_id']})" <if condition="$vooo['is_hot'] eq 1">style="color:red"</if>>{pigcms{$vooo['area_name']}</li>
												</volist>
											</ul>
										</dd>
									</volist>
								</dl>
							</volist>
						</div>
					</div>
					<else />
						<style>
							.search .land span:after{
								display:none !important;
							}
						</style>
					</if>
				</div>
			
				<if condition="isset($weather['info'])">
					<div class="clr forecast fl">
						<div class="img fl">
							<img src="{pigcms{$weather['img']}" width=30 height=30>
						</div>
						<div class="text">
							<h2><i>{pigcms{$weather['info']}</i> {pigcms{$weather['direct']}{pigcms{$weather['power']}</h2>
							<p>{pigcms{$weather['min']}°C~{pigcms{$weather['max']}°C</p>
						</div>
					</div>
				</if>
			</div>
			<div class="fr clr">
				<div id="search_index" class="search_n clr fl">
					<input id="search_vl" type="text" placeholder="搜索景点、景区地址、景区介绍" class="input_s fl">
					<a onclick="search_scenic()" href="javascript:void(0)" class="fr">搜索</a>
				</div>
				<div class="photo fl">
					<h2>客户服务电话</h2>
					<p>{pigcms{$config['site_phone']}</p>
				</div>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		/*地址选择*/
		$(".land").hover(function(){
			$(this).addClass("on").find(".landname").show();
		},function(){
			$(this).removeClass("on").find(".landname").hide();
		})
		$(".landname dl li").click(function(){
			var text=$(this).text();
			$(this).parents(".landname").hide().parents(".land").removeClass("on").find("span").text(text);
		})
		$(".ul_nav li").click(function(){
			$(this).addClass("on").siblings("li").removeClass("on");
			var index=$(this).index();
			$(".lname_end dl").eq(index).slideDown().siblings("dl").slideUp();
		})
		function now_city(city){
			location.href = "/scenic.php?g=Scenic&c=Index&a=index&now_city="+city;
		}
		function search_scenic(){
			var	search_vl	=	$("#search_vl").val();
			if(!search_vl){
				alert('搜索关键词不能为空');
				return false;
			}
			location.href = "/scenic.php?g=Scenic&c=Scenic_list&a=index&search_vl="+search_vl;
		}
		$("#search_index").keydown(function(event){
			if (event.keyCode == "13") {//keyCode=13是回车键
				search_scenic();
			}
		});
	</script>
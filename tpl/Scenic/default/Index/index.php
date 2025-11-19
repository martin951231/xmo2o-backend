<include file="Public/header"/>
<include file="Public/header_city"/>
	<if condition="$config['wap_redirect']">
		<script>
			if(/(iphone|ipod|android|windows phone)/.test(navigator.userAgent.toLowerCase())){
				<if condition="$config['wap_redirect'] eq 1">
					window.location.href = './wap.php?g=Wap&c=Scenic_index';
				<else/>
					if(confirm('系统检测到您可能正在使用手机访问，是否要跳转到手机版网站？')){
						window.location.href = './wap.php?g=Wap&c=Scenic_index';
					}
				</if>
			}
		</script>
	</if>
	<!--导航-->
	<section class="navigation">
		<div class="w1200">
			<ul class="clr nav_top">
				<li class="pull on">
					<a href="https://www.0855mlh.com/">网站首页</a><!--{pigcms{:U('Index/index')}-->
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
	<!--广告图-->
	<section class="banner">
		<div class="tulb clr">
			<ul class="tulb_ul">
				<volist name="city_advert" id="aa">
					<li style="background: url({pigcms{$aa['advert_img']}) center no-repeat;"><a href="{pigcms{$aa['advert_url']}" style="display: block; height: 100%"></a></li>
				</volist>
			</ul>
			<div class="hd">
				<ul>
					<volist name="city_advert" id="bb">
						<li></li>
					</volist>
				</ul>
			</div>
			<div class="Tclick">
				<a class="prev" href="javascript:void(0)"></a>
				<a class="next" href="javascript:void(0)"></a>
			</div>
		</div>
	</section>
	<!--4个小广告-->
    <section class="a_list">
        <div class="w1200 clr">
        	<volist name="centre_city_advert" id="vq">
	            <a href="{pigcms{$vq['advert_url']}" style="background: url({pigcms{$vq['advert_img']}) center no-repeat;"></a>
            </volist>
        </div>
    </section>
	<!--本市景点推荐-->
	<if condition="$scenic_list">
		<section class="city">
			<div class="w1200">
				<div class="public_top clr">
					<div class="fl">
						<span class="text">本市推荐</span>
						<i class="text_n">/ recommended </i>
					</div>
				</div>
				<div class="public_end clr">
					<dl class="clr">
						<volist name="scenic_list" id="ll">
							<if condition="$key eq 0">
								<dt>
									<a href="{pigcms{$ll['url']}">
										<img src="{pigcms{$ll['pic']}" width=100% height=240>
										<div class="pub_pos clr">
											<h2 class="fl">{pigcms{$ll['scenic_title']}</h2>
											<span class="fr"><i>￥</i>{pigcms{$ll['money']}</span>
										</div>
									</a>
								</dt>
							<else/>
								<dd>
									<a href="{pigcms{$ll['url']}">
										<div class="img">
											<img src="{pigcms{$ll['pic']}" width=100% height=138>
										</div>
										<div class="text">
											<h2>{pigcms{$ll['scenic_title']}</h2>
											<div class="clr textend">
												<div class="fl"><span>{pigcms{$ll['money']}</span>起</div>
												<div class="fr">
													<span>评分:</span>
													<span class="score">{pigcms{$ll['score_mean']}</span>
												</div>
											</div>
										</div>
									</a>
								</dd>
							</if>
						</volist>
					</dl>
				</div>
			</div>
		</section>
	</if>
	<!--热门景点推荐-->
	<section class="city hot">
		<div class="w1200">
			<div class="public_top clr">
				<div class="fl">
					<span class="text">热门推荐</span>
					<i class="text_n">/ Hot recommended </i>
				</div>
			</div>
			<div class="public_end clr">
				<div class="fl spot">
				<a href="{pigcms{$left_city_advert[0]['advert_url']}">
					<div class="spot_end">
						<img src="{pigcms{$left_city_advert[0]['advert_img']}" width=100% height=594>
					</div></a>
				</div>
				<div class="fr hot_list">
					<div class="jump hd">
						<ul class="clr">
							<volist name="hot_scenic_area" id="vk">
								<li>{pigcms{$vk}</li>
							</volist>
						</ul>
					</div>
					<div class="hot_dl">
						<volist name="hot_scenic_list" id="vj">
							<dl class="clr">
								<volist name="vj" id="vh">
									<if condition="$key eq 0">
										<dt>
											<a href="{pigcms{$vh['url']}">
												<div class="img">
													<img src="{pigcms{$vh['pic']}" width=100% height=227>
												</div>
												<div class="text">
													<h2>{pigcms{$vh['scenic_title']}</h2>
													<div class="clr textend">
														<div class="fl">￥<span>{pigcms{$vh['money']}</span>起</div>
														<div class="fr">
															<span>评分:</span>
															<span class="score">{pigcms{$vh['score_mean']}</span>
														</div>
													</div>
												</div>
											</a>
									<elseif condition="$key eq 1"/>
											<a href="{pigcms{$vh['url']}" class="bot">
												<div class="img fl">
													<img src="{pigcms{$vh['pic']}" width=264 height=149>
												</div>
												<div class="bot_text">
													<h2>{pigcms{$vh['scenic_title']}</h2>
													<div class="price">￥<span>{pigcms{$vh['money']}</span>起</div>
												</div>
											</a>
										</dt>
									<else />
										<dd>
											<a href="{pigcms{$vh['url']}">
												<div class="img">
													<img src="{pigcms{$vh['pic']}" width=100% height=150>
												</div>
												<div class="text">
													<h2>{pigcms{$vh['scenic_title']}</h2>
													<div class="clr textend">
														<div class="fl">￥<span>{pigcms{$vh['money']}</span>起</div>
														<div class="fr">
															<span>评分:</span>
															<span class="score">{pigcms{$vh['score_mean']}</span>
														</div>
													</div>
												</div>
											</a>
										</dd>
									</if>
								</volist>
							</dl>
						</volist>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!--商品推荐-->
	<section class="city book">
		<div class="w1200">
			<div class="public_top clr">
				<div class="fl">
					<span class="text">商品推荐</span>
					<i class="text_n">/ commodity RECOMMENDED</i>
				</div>
			</div>
			<div class="public_end clr">
				<div class="fl spot">
				<a href="{pigcms{$left_city_advert[1]['advert_url']}">
					<div class="spot_end">
						<img src="{pigcms{$left_city_advert[1]['advert_img']}" width=100% height=594>
					</div></a>
				</div>
				<div class="fr hot_list">
					<div class="jump hd">
						<ul class="clr">
							<volist name="hot_scenic_groom_name" id="vd">
								<li>{pigcms{$vd}</li>
							</volist>
						</ul>
					</div>
					<div class="hot_dl">
						<volist name="hot_scenic_groom" id="vs">
							<dl class="clr">
								<volist name="vs" id="va">
									<if condition="$key eq 0">
										<dt>
											<a target="_blank" href="{pigcms{$va['url']}">
												<div class="img">
													<img src="{pigcms{$va['com_img']}" width=100% height=227>
												</div>
												<div class="text">
													<h2>{pigcms{$va['com_name']}</h2>
													<div class="clr textend">
														<div class="fl">￥<span>{pigcms{$va['price']}</span>起</div>
													</div>
												</div>
											</a>
									<elseif condition="$key eq 1"/>
											<a target="_blank" href="{pigcms{$va['url']}" class="bot">
												<div class="img fl">
													<img src="{pigcms{$va['com_img']}" width=264 height=149>
												</div>
												<div class="bot_text">
													<h2>{pigcms{$va['com_name']}</h2>
													<div class="price">￥<span>{pigcms{$va['price']}</span>起</div>
												</div>
											</a>
										</dt>
									<else/>
										<dd>
											<a target="_blank" href="{pigcms{$va['url']}">
												<div class="img">
													<img src="{pigcms{$va['com_img']}" width=100% height=150>
												</div>
												<div class="text">
													<h2>{pigcms{$va['com_name']}</h2>
													<div class="clr textend">
														<div class="fl">￥<span>{pigcms{$va['price']}</span>起</div>
													</div>
												</div>
											</a>
										</dd>
									</if>
								</volist>
							</dl>
						</volist>
					</div>
				</div>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		/*首页控制css*/
		jQuery(".tulb").slide({mainCell:".tulb_ul",effect:"left",autoPlay:true});
		jQuery(".city").slide({mainCell:".hot_dl"})
		$(".hotspot dt li:nth-child(2)").css("margin","0 24px");
		$(".a_list a:last-child()").css("margin-right","0px");
		$(".public_end dd:last-child()").css("margin-right","0px");
		$(".jump li:first-child()").css("margin-left","0px");
		$(".hot_list dd:nth-child(3n)").css("margin-right","0px");
		$(".tulb .hd").each(function(){
			$(this).css("margin-left",-$(this).width()/2);
		})
	</script>
<include file="Public/footer"/>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>管理页面</title>
		<meta name="renderer" content="webkit">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
		<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css"  media="all"/>
		<link rel="stylesheet" href="{pigcms{$plugin_public}index/css/base.css"  media="all"/>
	</head>
	<body>
		<div class="layui-main layui-anim layui-anim-up">
			<div id="LAY_preview">
				<volist name="plugin_list" id="vo">
					<if condition="$vo['plugin_list']">
						<div class="application-list">
							<div class="application-title">
								<span class="title-main">{pigcms{$vo.cat_name}</span>
								<span class="title-desc">{pigcms{$vo.cat_desc}</span>
							</div>
							<div class="application-list-content clearfix">
								<volist name="vo['plugin_list']" id="voo">
									<li class="application-item" title="{pigcms{$voo.plugin_name}">
										<a onclick="goUrl('{pigcms{$voo.url}','{pigcms{$voo.new_blank}','{pigcms{$voo.new_tab}','{pigcms{$voo.new_page}')" class="application-entry">
											<div class="application-logo">
												<img src="{pigcms{$config.site_url}/plugin/logo/{pigcms{$voo.plugin_label}.png"/>
											</div>
											<div class="application-name">
												<span class="application-name-content">{pigcms{$voo.plugin_name}</span>
												<div class="zent-popover-wrapper zent-pop-wrapper" style="display:inline-block;">
													<if condition="isset($voo['plugin_ico_arr']['wxapp'])">
														<span class="support-wxapp"></span>
													</if>
												</div>
											</div>
											<div class="application-desc">{pigcms{$voo.plugin_txt}</div>
										</a>
									</li>
								</volist>
							</div>
						</div>
					</if>
				</volist>
			</div>
		</div>
		<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
		<script>
			layui.$('.application-item').width((layui.$('.application-list').width() - 16*4) / 4);
			
			var layer_tips_index = null;
			layui.$('.support-wxapp').hover(function(){
				layer_tips_index = layer.tips('小程序已支持', layui.$(this), {
				  tips: [1, '#1686ef']
				});
			},function(){
				layer.close(layer_tips_index);
			});
			function goUrl(url,new_blank,new_tab,new_page) {
				if(new_blank>0){
					window.open(url)
				}else if(new_tab){
					top.openNewTab(url)

				}else if(new_page>0){
                    window.top.location.href = url
                }else{
					window.top.contentScrollToTop()
					location.href=url

				}
			}
		</script>
	</body>
</html>
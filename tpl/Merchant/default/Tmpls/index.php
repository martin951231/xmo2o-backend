<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Tmpls/index')}">{pigcms{:L_("微网站")}</a>
			</li>
			<li class="active">{pigcms{:L_("模板选择")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">	
							<li class="active">
								<a href="javascript:void(0);">1.{pigcms{:L_("栏目首页风格")}</a>
							</li>	
							<!-- <li>
								<a href="{pigcms{:U('Config/store_add')}">2.{pigcms{:L_("图文列表模板风格")}</a>
							</li>	
							<li>
								<a href="{pigcms{:U('Config/store_add')}">3.{pigcms{:L_("图文详细页模板风格")}</a>
							</li>	
							<li>
								<a href="{pigcms{:U('Config/store_add')}">4.{pigcms{:L_("颜色风格")}&{pigcms{:L_("预览")}</a>
							</li> -->
						</ul>
					
						<div class="tab-content">
							<div class="tab-pane active">
					            <fieldset>
					                <!--<div class="g filterBox">
					                  <h1>{pigcms{:L_("按级别选择")}:</h1>
					                  <ul class="filterBtn">	 					
					                  	<li class="filter" data-filter="ck"><p>{pigcms{:L_("我选中的模版")}</p><i></i></li>
					                    <li class="filter on active" data-filter="all"><p>{pigcms{:L_("全部模版")}</p><i></i></li>
					                    <li class="filter" data-filter="sub"><p>{pigcms{:L_("可显示两级分类")}</p><i></i></li>
					                    <li class="filter" data-filter="focu"><p>{pigcms{:L_("支持幻灯片")}</p><i></i></li>
					                    <li class="filter" data-filter="bg"><p>{pigcms{:L_("支持自定义背景")}</p><i></i></li>
					                    <li class="filter" data-filter="thumb"><p>{pigcms{:L_("带缩略图")}</p><i></i></li>
										<li class="filter" data-filter="filt"><p>{pigcms{:L_("半透明版块")}</p><i></i></li>
										<li class="filter" data-filter="bgs"><p>{pigcms{:L_("支持背景音乐")}</p><i></i></li>
					                    <li class="filter" data-filter="slip"><p>{pigcms{:L_("支持横向滑动")}</p><i></i></li>
					                  </ul>
					                  <h1>{pigcms{:L_("按行业选择")}:</h1>
					                  <ul class="filterBtn">
					                    <li class="filter" data-filter="mix"><p>{pigcms{:L_("常用模板")}</p><i></i></li>
					                    <li class="filter" data-filter="hotel"><p>{pigcms{:L_("酒店")}</p><i></i></li>
					                    <li class="filter" data-filter="car"><p>{pigcms{:L_("汽车")}</p><i></i></li>
					                    <li class="filter" data-filter="tour"><p>{pigcms{:L_("旅游")}</p><i></i></li>
										<li class="filter" data-filter="restaurant"><p>{pigcms{:L_("餐饮")}</p><i></i></li>
					                    <li class="filter" data-filter="estate"><p>{pigcms{:L_("房地产")}</p><i></i></li>
					                    <li class="filter" data-filter="health"><p>{pigcms{:L_("医疗保健")}</p><i></i></li>
										<li class="filter" data-filter="edu"><p>{pigcms{:L_("教育培训")}</p><i></i></li>
										<li class="filter" data-filter="beauty"><p>{pigcms{:L_("健身美容")}</p><i></i></li>
					                    <li class="filter" data-filter="wedding"><p>{pigcms{:L_("婚纱摄影")}</p><i></i></li>
					                    <li class="filter" data-filter="other"><p>{pigcms{:L_("其他行业")}</p><i></i></li>
					
					                  </ul>
					
					                </div>-->
									
									
									<div style="clear:both"></div>
									
									<ul class="cateradio g grid" id="grid">
										
										<volist id="tpl" name="tpl">
											<li class="mix {pigcms{$tpl.attr}<?php if($info['tpltypeid'] == $tpl['tpltypeid']){echo ' ck active';} ?>">
												<div class="mbtip">{pigcms{$tpl.tpldesinfo|default='暂无模板描述'}</div>
												<label>
													<img src="{pigcms{$config.site_url}/static/tmpls/images/loading.png" data-original="{pigcms{$config.site_url}/static/images/site/{pigcms{$tpl.tplview}">
													<input class="radio" type="radio"<if condition="$info['tpltypeid'] eq $tpl['tpltypeid']"> checked</if> name="optype" value="{pigcms{$tpl.tpltypeid}">
													{pigcms{:L_("模板")}{pigcms{$tpl.sort}
													</label>
											</li>
											
										</volist>
										<div style="clear:both"></div>
					                </ul>
								</fieldset>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
KindEditor.ready(function(K) {
	var editor = K.editor({
		allowFileManager : true
	});

	K('#image').click(function() {
		editor.loadPlugin('image', function() {
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#img').val(),
				clickFn : function(url, title, width, height, border, align) {
					K('#img').val(url);
					var show_img = '<img src = "' + url + '" width="80" height="80" />';
					$('#show_img').html(show_img);
					editor.hideDialog();
				}
			});
		});
	});
});
$(function(){
	//"列表hover效果")}
	$(".grid li").hover(function(){$(this).addClass("hover");},function(){$(this).removeClass("hover");});
	$(".prdInfo").click(function(){return false;});
	$('#grid').mixitup({layoutMode: 'grid'});
});

$(function(){
	$("img").lazyload();
});
$(".radio").click(function(){
	$(".cateradio li").each(function(){
		$(this).removeClass("active ck");
	});
	$(this).parents("li").addClass("active ck");
	var myurl='merchant.php?g=Merchant&c=Tmpls&a=add&style='+$(this).val()+'&r='+Math.random(); 
	$.ajax({url:myurl,async:false});
});
</script>
<include file="Public:footer"/>
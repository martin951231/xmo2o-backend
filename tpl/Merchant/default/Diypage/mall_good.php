<?php if(empty($_GET['not_first'])){ ?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("微页面")}</title>
		<link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				
				$('.js-modal iframe',parent.document).height($('body').height()+16);
				$('.modal-header .close').live('click',function(){
					// var index = parent.layer.getFrameIndex(window.name); //"先得到当前iframe层的索引")}
					// parent.layer.close(index); //"再执行关闭")}
					window.top.closeiframe()
				});
				$('button.js-choose').live('click',function(){ 
					// var index = parent.layer.getFrameIndex(window.name);
					var topIframe = window.top.getTopIframe()
					topIframe.login_box_after('{pigcms{$_GET.number}','good',$(this).data('title'),'{pigcms{$config.site_url}/wap.php?c=Mall&a=detail&goods_id='+$(this).data('id'),$(this).data('id'),0);
					window.top.closeiframe()
				});
				$('.js-page-list a').live('click',function(e){
					if(!$(this).hasClass('active')){
						var input_val = $('.js-modal-search-input').val();
						$('body').html('<div class="loading-more"><span></span></div>');
						$('body').load("{pigcms{:U('mall_good',array('type'=>$_GET['type'],'selecteditems'=>$_GET['selecteditems'],'store_id'=>$now_store['store_id'],'is_edit'=>$_GET['is_edit'],'get_reg'=>$_GET['get_reg'],'not_first'=>1,'spec_id'=>$_GET['spec_id']))}",{page:$(this).data('page-num'),'keyword':input_val},function(){
							$('.js-modal iframe',parent.document).height($('body').height());
						});
					}
				});
				$('.js-modal-search').live('click',function(e){
					var input_val = $('.js-modal-search-input').val();
					$('body').html('<div class="loading-more"><span></span></div>');
					$('body').load("{pigcms{:U('mall_good',array('type'=>$_GET['type'],'selecteditems'=>$_GET['selecteditems'],'store_id'=>$now_store['store_id'],'is_edit'=>$_GET['is_edit'],'get_reg'=>$_GET['get_reg'],'not_first'=>1,'spec_id'=>$_GET['spec_id']))}",{'keyword':input_val},function(){
						$('.js-modal iframe',parent.document).height($('body').height());
					});
					return false;
				});
				$('.js-confirm-choose').live('click',function(){
					var data_arr = [];
					$.each($('.js-choose.btn-primary'),function(i,item){
						data_arr[i] = {'id':$(item).data('id'),'title':$(item).data('title'),'image':$(item).data('image'),'price':$(item).data('price'),'url':'{pigcms{$config.site_url}/wap.php?c=Mall&a=detail&goods_id='+$(this).data('id')};
					});
					parent.widget_box_after('{pigcms{$_GET.number}',data_arr);
				});
				
				$(document).on('click','.show_reg',function(res){
					var id=$(this).data('id');
					if(id>0){
						var show=$(this).data('show');
						if(show==1){
							$('tr.box'+id).show();
							$(this).data('show',0).text('{pigcms{:L_("关闭")}');	
						}else{
							$('tr.box'+id).hide();
							$(this).data('show',1).text('{pigcms{:L_("打开")}');
						}
					}
				});
				//"选取规格")}
				$(document).on('click','button.js-choose2',function(res){
					// var index = parent.layer.getFrameIndex(window.name);
					var topIframe = window.top.getTopIframe()
					var goods_id=$(this).data('goods_id');
					var spec_id=$(this).data('spec_id');
					var spec_name=$(this).data('spec_name');
					var name=$(this).data('name');
					var price=$(this).data('price');
					topIframe.login_box_after(goods_id,spec_id,spec_name,name,price,'{pigcms{$_GET.is_edit}',0);
				});
			});
		</script>
	</head>
	<body style="background-color:#ffffff;">
<?php } ?>
		<div class="modal-header">
			<a class="close js-news-modal-dismiss">×</a>
			<!-- {pigcms{:L_("顶部")}tab -->
			<ul class="module-nav modal-tab">
				<li class="active"><a href="javascript:void(0);" class="js-modal-tab">{pigcms{:L_("商品列表")}</a> |</li>
				<li><a href="{pigcms{:U('Mall/goods_sort',array('store_id'=>$_GET['store_id']))}" target="_blank" class="new_window">{pigcms{:L_("新建商品")}</a></li>
			</ul>
		</div>
		<div class="modal-body">
			<div class="tab-content">
				<div id="js-module-feature" class="tab-pane module-feature active">
					<table class="table">
						<colgroup>
							<col class="modal-col-title">
							<col class="modal-col-time">
							<col class="modal-col-action">
						</colgroup>
						<!-- {pigcms{:L_("表格头部")} -->
						<thead>
							<tr>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("标题")}</span> <a class="js-update" href="javascript:window.location.reload();">{pigcms{:L_("刷新")}</a>
									</div>
								</th>
								<th class="time" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("最后修改时间")}</span>
									</div>
								</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" onsubmit="return false;">
											<div class="input-append">
												<input class="input-small js-modal-search-input" type="text" style="border-radius:4px 0px 0px 4px;"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" style="border-radius:0 4px 4px 0;margin-left:0px;">{pigcms{:L_("搜")}</a>
											</div>
										</form>
									</div>
								</th>
							</tr>
						</thead>
						<!-- {pigcms{:L_("表格数据区")} -->
						<tbody>
							<volist name="good_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">
											<a target="_blank" class="new_window" href="{pigcms{$config.site_url}/wap.php?c=Mall&a=detail&goods_id={pigcms{$vo.goods_id}">{pigcms{$vo.name}</a>
										</div>
									</td>
									<td class="time">
										<div class="td-cont">
											<span>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</span>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
											<eq name="vo.choice_status" value='0'>
												<button class="btn js-choose" disabled="true">{pigcms{$vo.tip}</button>
											<else/>
												<empty name='vo.reg_list'>
													<if condition="$get_reg eq 1">
														<eq name='vo.is_bargain' value='1'>
															<button class="btn js-choose2" disabled="true">{pigcms{:L_("已选择")}</button>
														<else/>
															<button class="btn js-choose2" data-goods_id='{pigcms{$vo.goods_id}' data-spec_id='' data-spec_name='' data-name='{pigcms{$vo.name}' data-price='{pigcms{$vo.price}'>{pigcms{:L_("选取")}</button>
														</eq>
													<else/>
														<button class="btn js-choose" data-id="{pigcms{$vo.goods_id}" data-title="{pigcms{$vo.name}" data-price="{pigcms{$vo.price}" data-image="{pigcms{$vo.pic_arr.0.url.m_image}">{pigcms{:L_("选取")}</button>
													</if>
												<else/>
													<button data-id="{pigcms{$vo.goods_id}" data-show='1' class="btn show_reg" style="background-color:#87B87F;">{pigcms{:L_("打开")}</button>
												</empty>
											</eq>
										</div>
									</td>
								</tr>
								<volist name='vo.reg_list' id='vo2'>
									<tr class="box{pigcms{$vo.goods_id}" style="display:none;">
										<td class="title" style="max-width:300px;">
											<div class="td-cont">
												&nbsp;&nbsp;&nbsp;{pigcms{$vo2.reg_name}
											</div>
										</td>
										<td class="time">
											<span class="td-cont">
												{pigcms{$config['Currency_symbol']}{pigcms{$vo2.price}
											</span>&nbsp;&nbsp;&nbsp;
											<span class="td-cont">
												{pigcms{:L_("库存数：")}<eq name='vo2.stock_num' vlaue='-1'>{pigcms{:L_("无限制")}<else/>{pigcms{$vo2.stock_num}</eq>
											</span>
										</td>
										<td class="opts">
											<div class="td-cont">
												<eq name='vo2.is_bargain' value='1'>
													<button class="btn js-choose2" disabled="true">{pigcms{:L_("已选择")}</button>
												<else/>
													<button class="btn js-choose2"	 data-goods_id='{pigcms{$vo.goods_id}' data-spec_id='{pigcms{$vo2.spec_id}' data-spec_name='{pigcms{$vo2.reg_name}' data-name='{pigcms{$vo.name}' data-price='{pigcms{$vo2.price}'>{pigcms{:L_("选取")}</button>
												</eq>
											</div>
										</td>
									</tr>
								</volist>
							</volist>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<div style="display:none;" class="js-confirm-choose left">
				<input type="button" class="btn btn-primary" value="{pigcms{:L_("确定使用")}">
			</div>
			<div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$page_bar}</div>
		</div>
<?php if(empty($_GET['not_first'])){ ?>
	</body>
</html>
<?php } ?>
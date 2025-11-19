<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>选择商品</title>
		<link href="./tpl/Merchant/default/static/diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="./tpl/Merchant/default/static/js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				var from_page = '{pigcms{$_GET.from_page}';
				var active_item = "{pigcms{$_GET['active']|default='group'}";
				$('.item_'+active_item).addClass('active');
			
				$('button.js-choose').live('click',function(){
					var parentIframe = window.o2oTop.getParentIframe()
					if(parentIframe){
						console.log($(this).data('data'));
						parentIframe.choose_product($.parseJSON($(this).siblings('.data').text()));
						window.o2oTop.closeiframe()
					}else{
						alert('团购');
					}
				});
				$('.js-page-list a').live('click',function(e){
					if(!$(this).hasClass('active')){
						var input_val = $('.js-modal-search-input').val();
						var url = $('.module-nav .active a').attr('href')+'&page='+$(this).data('page-num')+'&keyword='+encodeURIComponent(input_val);
						
						location.href = url;
					}
				});
				$('.js-modal-search').live('click',function(e){
					var input_val = $('.js-modal-search-input').val();
					var url = $('.module-nav .active a').attr('href')+'&keyword='+encodeURIComponent(input_val);
					location.href = url;
				});
			});
		</script>
		<style type="text/css">
			.module-nav>li.active>a {
				 color: #009adb; 
			}
			.module-nav>li>a{
				color: inherit;
			}
		</style>
	</head>
	<body style="background-color:#ffffff;">
		<div class="modal-header">
			<!-- 顶部tab -->
			<ul class="module-nav modal-tab">
				<li class="item_group"><a href="{pigcms{:U('choose_product',array('active'=>'group','city_id'=>$_GET['city_id'],'from_page'=>$_GET['from_page']))}" class="js-modal-tab">{pigcms{$config.group_alias_name}商品列表</a> |</li>
				<li class="item_shop"><a href="{pigcms{:U('choose_product',array('active'=>'shop','city_id'=>$_GET['city_id'],'from_page'=>$_GET['from_page']))}" class="js-modal-tab">{pigcms{$config.shop_alias_name}商品列表</a> |</li>
				<li class="item_mall"><a href="{pigcms{:U('choose_product',array('active'=>'mall','city_id'=>$_GET['city_id'],'from_page'=>$_GET['from_page']))}" class="js-modal-tab">{pigcms{$config.mall_alias_name}商品列表</a> |</li>
                <li class="item_new_mall"><a href="{pigcms{:U('choose_product',array('active'=>'new_mall','city_id'=>$_GET['city_id'],'from_page'=>$_GET['from_page']))}" class="js-modal-tab">{pigcms{$config.mall_alias_name_new}商品列表</a> </li>
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
						<!-- 表格头部 -->
						<thead>
							<tr>
                <th class="title" style="background-color:#f5f5f5;width: 20%;">
									<div class="td-cont">
										<span>名称</span> <a class="js-update" href="javascript:window.location.reload();">刷新</a>
									</div>
								</th>
                <th class="time" style="background-color:#f5f5f5;width: 20%;">
                  <div class="td-cont">
                    <span>店铺名称</span>
                  </div>
                </th>
                <th class="time" style="background-color:#f5f5f5;width: 20%;">
									<div class="td-cont">
										<span>创建时间</span>
									</div>
								</th>
                <th style="background-color:#f5f5f5;width: 20%;">
									<div class="td-cont">
										<span>价格</span>
									</div>
								</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" onsubmit="return false;">
											<div class="input-append">
												<input class="input-small js-modal-search-input" type="text" style="border-radius:4px 0px 0px 4px;" value="{pigcms{$_GET.keyword}"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" style="border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
											</div>
										</form>
									</div>
								</th>
							</tr>
						</thead>
						<!-- 表格数据区 -->
						<tbody>
							<volist name="product_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">
											<a target="_blank" class="new_window" href="{pigcms{$vo.product_link}">{pigcms{$vo.product_name}</a>
										</div>
									</td>
                  <td class="time">
                    <div class="td-cont">
                      <span>{pigcms{$vo.store_name}</span>
                    </div>
                  </td>
									<td class="time">
										<div class="td-cont">
											<span>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</span>
										</div>
									</td>
									<td class="time">
										<div class="td-cont">
											<span>{pigcms{$vo.product_price}</span>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
											<span class="data" style="display:none;">{pigcms{:json_encode($vo)}</span>
											<button class="btn js-choose">选取</button>
										</div>
									</td>
								</tr>
							</volist>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<div style="display:none;" class="js-confirm-choose left">
				<input type="button" class="btn btn-primary" value="确定使用">
			</div>
			<div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$page_bar}</div>
		</div>
	</body>
</html>
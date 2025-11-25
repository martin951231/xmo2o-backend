<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("微页面")}</title>
		<link href="/tpl/Merchant/default/static/diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript">
			$(function(){
				
			});
		</script>
	</head>
	<body style="background-color:#ffffff;">
		<div class="modal-header"> 
			
		</div>
		<div class="modal-body">
			<div class="tab-content">
				<div id="js-module-feature" class="tab-pane module-feature active">
					<table class="table">
						<colgroup>
							<col class="modal-col-title">
							<col class="modal-col-action">
						</colgroup>
						<!-- {pigcms{:L_("表格头部")} -->
						<thead>
							<tr>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("名称")}</span>
									</div>
								</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" method="get" name="formsearch">
											<div class="input-append">
												<input type="hidden" name="c" value="MarketingActivity" />
												<input type="hidden" name="a" value="select_city" />
												<input type="hidden" name="selected" value="{pigcms{$selected}" />
												<input type="hidden" name="area_id" />
												<input type="hidden" name="area_name" />
												<input type="hidden" name="page" value="{pigcms{$page}" />
												<input class="input-small js-modal-search-input" name="keyword" value="{pigcms{$keyword}" type="text" style="border-radius:4px 0px 0px 4px;"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" onclick="formsearch.submit();" style="border-radius:0 4px 4px 0;margin-left:0px;">{pigcms{:L_("搜")}</a>
											</div>
										</form>
									</div>
								</th>
							</tr>
						</thead>
						<!-- {pigcms{:L_("表格数据区")} -->
						<tbody>
							<volist name="city_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.area_name}</div>
									</td>
									<td class="opts">
										<div class="td-cont">
											<eq name='vo.select' value='1'>
												<button class="btn js-choose2" disabled="true">{pigcms{:L_("已选择")}</button>
											<else/>
												<button class="btn js-choose2 selectThis" data-goods_id='{pigcms{$vo.area_id}' data-area_name='{pigcms{$vo.area_name}'  onclick="returnHomepage('{pigcms{$vo.area_id}','{pigcms{$vo.area_name}','{pigcms{$vo.area_pid}')">{pigcms{:L_("选取")}</button>
											</eq>
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
			<div class="pagenavi js-page-list" style="margin-top:0;padding-top:2px;">{pigcms{$pagebar}</div>
		</div>
	</body>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">
		var contentId=art.dialog.data('contentId');
		var city_id=art.dialog.data('city_id');
		var origin = artDialog.open.origin;
		var name_div = origin.document.getElementById(contentId);
		// alert(name_div)
		// 返回数据到主页面
		function returnHomepage(area_id,area_name,area_pid){		
			setTimeout("art.dialog.close()", 100 )
			var a={};
			a.area_id = area_id;
			a.area_name = area_name;
			a.area_pid = area_pid;
			addTag(a);			
		} 

		function addTag(obj) {
			var selected = $('[name="selected"]').val();
			_selected_city = selected;
			var area_name = obj.area_name;
			var area_id =obj.area_id;
			var area_pid =obj.area_pid;
			$(name_div).find("#choose_city").html(area_name);
			$(name_div).find('input[name="city[]"]').val(area_id);
			$(name_div).find('input[name="province[]"]').val(area_pid);
			$(name_div).find('.choose_goods_btn').remove();
			if (city_id != area_id) {
				// 城市改变了，清空优惠券
				$(name_div).siblings('div').find("input[name='coupon[]']").val('');
				$(name_div).siblings('div').find("table").find('tbody').html('');

			}
		}
	</script>
</html>
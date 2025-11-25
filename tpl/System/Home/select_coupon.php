<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("微页面")}</title>
		<link href="/tpl/Merchant/default/static/diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				
			});
		</script>
	</head>
	<body style="background-color:#ffffff;">
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
								<th>ID</th>
								<th>名称</th>
								<th>满减条件</th>
								<th>有效时间</th>
								<th>使用平台</th>
								<th>使用类别</th>
								<th>使用分类</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" method="get" name="formsearch">
											<div class="input-append">
												<input type="hidden" name="c" value="Home" />
												<input type="hidden" name="a" value="select_coupon" />
												<input type="hidden" name="selected" value="{pigcms{$selected}" />
												<input type="hidden" name="type" value="{pigcms{$type}" />
												<input type="hidden" name="page" value="{pigcms{$page}" />
												<input type="hidden" name="is_hide" value="{pigcms{$_GET['is_hide']}" />
												<input class="input-small js-modal-search-input" name="keyword" value="{pigcms{$keyword}" type="text" style="border-radius:4px 0px 0px 4px;"/><a href="javascript:void(0);" class="btn js-fetch-page js-modal-search" onclick="formsearch.submit();" style="border-radius:0 4px 4px 0;margin-left:0px;">搜</a>
											</div>
										</form>
									</div>
								</th>
							</tr>
						</thead>
						<!-- {pigcms{:L_("表格数据区")} -->
						<tbody>
							<volist name="list" id="vo">
								<tr class="user" data-uid="{pigcms{$vo.coupon_id}">
									<td>{pigcms{$vo.coupon_id}</td>
									<td>{pigcms{$vo.name}</td>
									<td><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></td>
									<td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
									<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
									<td><if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if></td>
									<td><if condition="$vo.cate_id eq '0'">全部分类<else />{pigcms{$vo['cate_id']}</if></td>
									<td class="opts">
										<div class="td-cont">
											<eq name='vo.select' value='1'>
												<button class="btn js-choose2" disabled="true">已选择</button>
											<else/>
												<button class="btn js-choose2 selectThis" data-coupon_id='{pigcms{$vo.coupon_id}' data-type='{pigcms{$type}'>选取</button>
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
	<script type="text/javascript">
		function redirectUrl(t){
			var selected = $.trim($("[name='selected']").val());
			var href = $(t).attr('href');
			$(t).attr('href', href+"&selected="+selected);
			return true;
		}
		function in_array(search,array){
		    for(var i in array){
		        if(array[i]==search){
		            return true;
		        }
		    }
		    return false;
		}
		$(function(){
			$('.js-page-list a').live('click',function(e){
				if(!$(this).hasClass('active')){
					$('[name="page"]').val($(this).data('page-num'));
					$(".js-modal-search").trigger('click');
				}
			});

			$(".selectThis").bind("click", function(){
				var selected = $.trim($('[name="selected"]').val());
				var str = $(this).data('coupon_id');
				var selected_arr = selected.split(",");
				if(!in_array(str, selected_arr)){
					selected_arr.push(str);
				}
				$('[name="selected"]').val(selected_arr.join(','));
				$(this).removeClass('selectThis').attr("disabled",true).text('已选择');
			})
		})
	</script>
</html>
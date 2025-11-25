<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("微页面")}</title>
		<link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				
			});
		</script>
	</head>
	<body style="background-color:#ffffff;">
		<div class="modal-header">
			<!-- {pigcms{:L_("顶部")}tab -->
			<ul class="module-nav modal-tab">
<!--				<li <if condition="$type eq 'mall'">class="active"</if>><a <if condition="$type eq 'mall'">href="javascript:void(0);"<else />href="{pigcms{:U('select_goods?type=mall')}" onclick="redirectUrl(this);"</if> class="js-modal-tab">{pigcms{:L_("商城商品")}</a> |</li>-->
				<li <if condition="$type eq 'group'">class="active"</if>><a <if condition="$type eq 'group'">href="javascript:void(0);"<else />href="{pigcms{:U('select_goods?type=group')}" onclick="redirectUrl(this);"</if> class="js-modal-tab">{pigcms{:L_("团购商品")}</a> |</li>
				<li <if condition="$type eq 'shop'">class="active"</if>><a <if condition="$type eq 'shop'">href="javascript:void(0);"<else />href="{pigcms{:U('select_goods?type=shop')}" onclick="redirectUrl(this);"</if> class="js-modal-tab">{pigcms{:L_("外卖商品")}</a> |</li>
				<li <if condition="$type eq 'mall3'">class="active"</if>><a <if condition="$type eq 'mall3'">href="javascript:void(0);"<else />href="{pigcms{:U('select_goods?type=mall3')}" onclick="redirectUrl(this);"</if> class="js-modal-tab">{pigcms{:L_("新版商城商品")}</a> |</li>
				<!-- <li <if condition="$type eq 'meal'">class="active"</if>><a <if condition="$type eq 'meal'">href="javascript:void(0);"<else />href="{pigcms{:U('select_goods?type=meal')}" onclick="redirectUrl(this);"</if> class="js-modal-tab">{pigcms{:L_("餐饮商品")}</a></li> -->
			</ul>
		</div>
		<div class="modal-body">
			<div class="tab-content">
				<div id="js-module-feature" class="tab-pane module-feature active">
					<table class="table">
						<colgroup>
							<col class="modal-col-title">
							<col class="modal-col-title">
							<col class="modal-col-time">
							<col class="modal-col-action">
						</colgroup>
						<!-- {pigcms{:L_("表格头部")} -->
						<thead>
							<tr>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("标题")}</span>
									</div>
								</th>
								<if condition="$type neq 'group'">
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("所属店铺")}</span>
									</div>
								</th>
								</if>
								<th class="time" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("最后修改时间")}</span>
									</div>
								</th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" method="get" name="formsearch">
											<div class="input-append">
												<input type="hidden" name="c" value="Liveshow" />
												<input type="hidden" name="a" value="select_goods" />
												<input type="hidden" name="selected" value="" />
												<input type="hidden" name="type" value="{pigcms{$type}" />
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
							<volist name="good_list" id="vo">
								<tr>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.name}</div>
									</td>
									<if condition="$type neq 'group'">
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.store_name}</div>
									</td>
									</if>
									<td class="time">
										<div class="td-cont">
											<span>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</span>
										</div>
									</td>
									<td class="opts">
										<div class="td-cont">
											<eq name='vo.select' value='1'>
												<button class="btn js-choose2" disabled="true">{pigcms{:L_("已选择")}</button>
											<else/>
												<button class="btn js-choose2 selectThis" data-goods_id='{pigcms{$vo.goods_id}' data-type='{pigcms{$type}'>{pigcms{:L_("选取")}</button>
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
			var href = $(t).attr('href');
			$(t).attr('href', href);
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
				var str = $(this).data('goods_id');
				var selected_arr = selected.split(",");
				if(!in_array(str, selected_arr)){
					selected_arr.push(str);
				}
				$('[name="selected"]').val(selected_arr.join(','));
				$(this).removeClass('selectThis').attr("disabled",true).text('{pigcms{:L_("已选择")}');
			})
		})
	</script>
</html>
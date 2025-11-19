<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("选择预约商品")}</title>
		<link href="{pigcms{$static_path}diypage/css/customField.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript">
			$(function(){
				
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
		<div class="modal-body">
			<div class="tab-content">
				<div id="js-module-feature" class="tab-pane module-feature active">
					<table class="table">
						<colgroup>
							<col class="modal-col-id">
							<col class="modal-col-name">
                            <col class="modal-col-price">
                            <col class="modal-col-status">
							<col class="modal-col-action">
						</colgroup>
						<!-- {pigcms{:L_("表格头部")} -->
						<thead>
							<tr>
                                <th class="title" style="background-color:#f5f5f5;">
                                    <div class="td-cont">
                                        <span>{pigcms{:L_("ID")}</span>
                                    </div>
                                </th>
								<th class="title" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("服务名称")}</span>
									</div>
								</th>
								<th class="time" style="background-color:#f5f5f5;">
									<div class="td-cont">
										<span>{pigcms{:L_("价格")}</span>
									</div>
								</th>
                                <th class="title" style="background-color:#f5f5f5;">
                                    <div class="td-cont">
                                        <span>{pigcms{:L_("预约状态")}</span>
                                    </div>
                                </th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" method="get" name="formsearch">
											<div class="input-append">
												<input type="hidden" name="c" value="Appoint" />
												<input type="hidden" name="a" value="select_goods" />
												<input type="hidden" name="selected" value="{pigcms{$selected}" />
												<input type="hidden" name="page" value="{pigcms{$page}" />
                                                <input type="hidden" name="id" value="{pigcms{$id}" />
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
                                        <div class="td-cont">{pigcms{$vo.goods_id}</div>
                                    </td>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.name}</div>
									</td>
									<td class="time">
										<div class="td-cont">
											<span>
                                                {pigcms{:L_("定金")}：<if condition="$vo.payment_status eq 0">--<else/>{pigcms{$config['Currency_symbol']}{pigcms{$vo.payment_money}</if>
                                                <br/>
                                                {pigcms{:L_("全价")}：<if condition="$vo.is_appoint_price eq 0">面议<else/>{pigcms{$config['Currency_symbol']}{pigcms{$vo.appoint_price}</if>
                                            </span>
										</div>
									</td>
                                    <td>
                                        <if condition="$vo['appoint_status'] eq 0"><span style="color:green">{pigcms{:L_("开启")}</span>
                                            <elseif condition="$vo['appoint_status'] eq 1" /><span style="color:red">{pigcms{:L_("关闭")}</span>
                                        </if>
                                    </td>
									<td class="opts">
										<div class="td-cont">
											<eq name='vo.select' value='1'>
												<button class="btn js-choose2" disabled="true">{pigcms{:L_("已选择")}</button>
											<else/>
												<button class="btn js-choose2 selectThis" data-goods_id='{pigcms{$vo.goods_id}' >{pigcms{:L_("选取")}</button>
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
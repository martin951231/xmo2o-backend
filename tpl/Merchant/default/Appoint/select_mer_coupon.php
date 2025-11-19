<!doctype html>
<html>
	<head>
		<meta charset="utf-8"/> 
		<title>{pigcms{:L_("选择优惠券")}</title>
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
            .readySelectThis{
                cursor: default !important;
                color: #999 !important;
                background-color: #f0f0f0 !important;
                border-color: #ddd !important;
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
										<span>{pigcms{:L_("名称")}</span>
									</div>
								</th>
                                <th class="title" style="background-color:#f5f5f5;">
                                    <div class="td-cont">
                                        <span>{pigcms{:L_("状态")}</span>
                                    </div>
                                </th>
								<th class="opts" style="background-color:#f5f5f5;">
									<div class="td-cont" style="padding:7px 0 3px 10px;">
										<form class="form-search" method="get" name="formsearch">
											<div class="input-append">
												<input type="hidden" name="c" value="Appoint" />
												<input type="hidden" name="a" value="select_mer_coupon" />
												<input type="hidden" name="coupon_id" value="{pigcms{$coupon_id}" />
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
                                        <div class="td-cont">{pigcms{$vo.goods_id}</div>
                                    </td>
									<td class="title" style="max-width:300px;">
										<div class="td-cont">{pigcms{$vo.name}</div>
									</td>
                                    <td>
                                        <if condition="$vo['status'] eq 1"><span style="color:green">{pigcms{:L_("开启")}</span>
                                        <else/><span style="color:red">{pigcms{:L_("不可用")}</span>
                                        </if>
                                    </td>
									<td class="opts">
										<div class="td-cont">
											<eq name='vo.select' value='1'>
												<button class="btn js-choose2 readySelectThis">{pigcms{:L_("已选择")}</button>
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
		$(function(){
            $('.js-page-list a').live('click',function(e){
                if(!$(this).hasClass('active')){
                    $('[name="page"]').val($(this).data('page-num'));
                    $(".js-modal-search").trigger('click');
                }
            });
			$(".selectThis").bind("click", function(){
				var selected = $(this).data('goods_id');
				$('[name="coupon_id"]').val(selected);
                $('.readySelectThis').removeClass('readySelectThis').addClass('selectThis').text('{pigcms{:L_("选取")}');
				$(this).addClass('readySelectThis').text('{pigcms{:L_("已选择")}');
			})
		})
	</script>
</html>
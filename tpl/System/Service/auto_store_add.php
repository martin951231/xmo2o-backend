<include file="Public:header"/>
		<div class="mainbox">
			<form class="frame_form" action="#" method="get">
				<input type="hidden" name="c" value="Service"/>
				<input type="hidden" name="a" value="auto_store_add"/>
				<input type="hidden" name="cid" value="{pigcms{$_GET['cid']}"/>
				<input type="text" placeholder="请输入名称搜索词" value="{pigcms{$_GET['search']}" class="input input-image" name="search">
				<button class="btnGrayS" style="height: 29px;">搜索</button>
			</form>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th width="20%">店铺名称</th>
								<th width="20%">所属商家</th>
								<th width="20%">店铺营业时间</th>
								<th width="20%">商家到期时间</th>
								<th  width="20%" class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="$store_list">
								<volist name="store_list" id="vo">
									<tr>
										<td>{pigcms{$vo.name}</td>
										<td>{pigcms{$vo.merchant_name}</td>
										<td>
											<if condition="empty($vo['open_time_list'])">
                                                数据未导入
                                            <else/>
                                                <select>
                                                <volist name="vo.open_time_list" id="ti">
                                                <option disabled="disabled"<if condition="$ti.current eq 1"> selected="selected"</if>>{pigcms{$ti.week_show}&nbsp;&nbsp;{pigcms{$ti.time_show}</option>
                                                </volist>
                                                </select>
                                            </if>
										</td>
										<td>
											<if condition="$vo['merchant_end_time'] eq 0">
												无限期
											<else />
												<if condition="$vo['merchant_end_time'] gt time()" >
													<span style="color: green;">{pigcms{$vo.merchant_end_time|date="Y-m-d H:i:s",###}</span>
												<else />
													<span style="color: red;">于{pigcms{$vo.merchant_end_time|date="Y-m-d H:i:s",###}到期</span>
												</if>
											</if>
										</td>
										<td class="textcenter">
											<if condition="$vo['select'] eq 1">
												<input type="button" class="button" disabled="true" value="已选择"/>
											<else />
												<input type="button" class="button select_row product-{pigcms{$vo.store_id}" data-name="{pigcms{$vo.name}" data-image="{pigcms{$vo.image}" data-id="{pigcms{$vo.store_id}" value="选中"/>
											</if>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="4">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="4">店铺列表为空</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			var cid = "{pigcms{$_GET['cid']}";
			$(function(){
				// var product = window.top.frames[window.top.nowTab].get_selected_product();
				// for(var i in product){
				// 	$('.product-'+product[i]).val('已选择').prop('disabled',true);
				// }

				$('.select_row').click(function(){
					$(this).val('已选择').prop('disabled',true);
					var store_id = $(this).attr("data-id");
					var data = {
						store_id,
						cid
					};

					$.post("{pigcms{:U('ajax_add_store')}",data,function(res){
						if (res.status==1) {

						}
						console.log(res)
					})
					// window.top.frames[window.top.nowTab].select_product($(this).data('id'),$(this).data('name'),$(this).data('image'));
				});
			});
		</script>
<include file="Public:footer"/>
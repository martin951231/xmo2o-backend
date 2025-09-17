<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.appoint_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_('返回首页')}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_('刷新页面')}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink" data-url="{pigcms{:U('appoint_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink cur" data-url="{pigcms{:U('appoint_find')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("查找订单")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('allot_appoint_list')}">
						<div class="icon merchant"></div>
						<div class="text">{pigcms{:L_("商家派发订单")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
					<form id="find-form" method="post">
						<select name="find_type" id="find_type" class="col-sm-2" style="margin-right:10px;height:42px;">
							<optgroup label="{pigcms{:L_("预约")}">
								<option value="1">{pigcms{:L_("消费密码")}</option>
							</optgroup>
							<optgroup label="{pigcms{:L_("通用")}">
								<option value="2">{pigcms{:L_("订单")}ID</option>
								<option value="3">{pigcms{:L_("预约")}ID</option>
								<option value="4">{pigcms{:L_("用户")}ID</option>
								<option value="5">{pigcms{:L_("用户昵称")}</option>
								<option value="6">{pigcms{:L_("手机号码")}</option>
							</optgroup>
						</select>
						<input class="col-sm-4" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:30px;"/>
						<button class="btn btn-success" type="submit" id="find_submit">{pigcms{:L_("查找订单")}</button>
					</form>
				</div>
				<div id="order_list" class="grid-view" style="display:none;">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th>{pigcms{:L_("订单编号")}</th>
								<th>{pigcms{:L_("预约名称")}</th>
								<th>{pigcms{:L_("订单信息")}</th>
								<th>{pigcms{:L_("服务状态")}</th>
								<th>{pigcms{:L_("用户信息")}</th>
								<th>{pigcms{:L_("订单状态")}</th>
								<th class="button-column">{pigcms{:L_("操作")}</th>
							</tr>
						</thead>
						<tbody id="order_html">
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<script id="find_html" type="text/html">
			{{# for(var i = 0, len = d.list.length; i < len; i++){ }}
				<tr class="{{# if(i%2==1){ }}odd{{# }else{ }}even{{# } }}">
					<td width="100">{{ d.list[i].order_id }}</td>
					<td width="200"><a href="{pigcms{$config.site_url}/index.php?g=Appoint&c=Detail&appoint_id={{ d.list[i].appoint_id }}" target="_blank">{{ d.list[i].appoint_name }}</a></td>
					<td width="150">
						{pigcms{:L_("定金：")}{{d.list[i].payment_money}}<br/>
						{pigcms{:L_("总价：")}{{d.list[i].appoint_price}}<br/>
					</td>
					<td width="150">
						{{# if(d.list[i].last_staff == null){ }}
							<span class="red">{pigcms{:L_("未验证服务")}</span>
						{{# }else{ }}
							{pigcms{:L_("操作店员：")}{{d.list[i].last_staff}}<br/>
							{{# if(d.list[i].pay_time !=''){ }}{pigcms{:L_("消费时间：")}{{d.list[i].pay_time}}<br/>{{# } }}
						{{# } }}
					</td>
					<td width="180">
						{pigcms{:L_("用户ID：")}{{d.list[i].uid}}<br/>
						{pigcms{:L_("用户名：")}{{d.list[i].nickname}}<br/>
						{pigcms{:L_("用户手机号：")}{{d.list[i].phone}}<br/>
					</td>
					<td width="200">
						{{# if(d.list[i].paid ==0){ }}
							{{# if(d.list[i].payment_money != '0.00'){ }}
								<font color="red">{pigcms{:L_("未支付")}</font>
							{{# } }}
								{{# if(d.list[i].service_status ==0){ }}
									<font color="red">{pigcms{:L_("未服务")}</font>
									<a href="{pigcms{:U('Store/appoint_verify')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a>
								{{# }else if(d.list[i].service_status ==1){ }}
									<font color="green">{pigcms{:L_("已服务")}</font>
								{{# } }}
						{{# }else if(d.list[i].paid ==1){ }}
							<font color="green">{pigcms{:L_("已支付")}</font>
								{{# if(d.list[i].service_status ==0){ }}
									<font color="red">{pigcms{:L_("未服务")}</font>
									<a href="{pigcms{:U('Store/appoint_verify')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a>
								{{# }else if(d.list[i].service_status ==1){ }}
									<font color="green">{pigcms{:L_("已服务")}</font>
								{{# } }}
						{{# }else if(d.list[i].paid ==2){ }}
								<font color="red">{pigcms{:L_("已退款")}</font>
						{{# } }}<br/>
						{pigcms{:L_("下单时间：")}{{d.list[i].order_time}}<br/>
						{{# if(d.list[i].pay_time !=''){ }}{pigcms{:L_("付款时间：")}{{d.list[i].pay_time}}{{# } }}
					</td>
					<td class="button-column" width="40">
						<a title="{pigcms{:L_("查看订单详情")}"  data-title="{pigcms{:L_("订单详情")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('appoint_detail')}&order_id={{ d.list[i].order_id }}">
							<i class="shortBtn">{pigcms{:L_("查看详情")}</i>
						</a>
					</td>
				</tr>
			{{# } }}
		</script>
		<script src="{pigcms{$static_public}js/laytpl.js"></script>
		<script type="text/javascript">
			$('#find_type').change(function(){
				if($(this).val() != '1'){
					$('#find_value').val($('#find_value').val().replace(/\s+/g,""));
				}else{
					$('#find_value').val($('#find_value').val().replace(/\s+/g,"").replace(/(\d{4})/g,'$1 '));
				}
			});
			$('#find_value').focus().keyup(function(){
				if($('#find_type').val() == '1'){
					if($(this).val().substr(-1) == ' '){
						$(this).val($(this).val().substr(0,($(this).val().length-1)));
					}else{
						$(this).val($(this).val().replace(/\s+/g,"").replace(/(\d{4})/g,'$1 '));
					}
				}
			});
			$('#find-form').submit(function(){
				var find_value = $('#find_value');
				find_value.val($.trim(find_value.val()));
				if(find_value.val().length < 1){
					alert('{pigcms{:L_("请输入查找内容！")}');
					find_value.focus();
					return false;
				}
				
				var post_type = $('#find_type').val();
				var post_value = $('#find_value').val().replace(/\s+/g,"");
				$('#find_submit').removeClass('btn-success').addClass('btn-error').prop('disabled',true).html('{pigcms{:L_("请求中")}...');
				$('#order_html').empty();
				$('#order_list').hide();
				$.post("{pigcms{:U('Store/appoint_find')}",{find_type:post_type,find_value:post_value},function(result){
					$('#find_submit').removeClass('btn-error').addClass('btn-success').prop('disabled',false).html('{pigcms{:L_("查找订单")}');
					data = $.parseJSON(result);
					if(data.row_count > 0){
						laytpl(document.getElementById('find_html').innerHTML).render(data, function(html){
							document.getElementById('order_html').innerHTML = html;
							$('#order_list').show();
						});
					}else{
						alert('{pigcms{:L_("未查找到内容！")}');
					}
				});
				
				return false;
			});
			$('.group_verify_btn').live('click',function(){
				var verify_btn = $(this);
				verify_btn.html('{pigcms{:L_("验证中")}..');
				$.get(verify_btn.attr('href'),function(result){
					var content = result.info;
					alert(content);
					if(result.status == 1){
						window.location.href = window.location.href;
					}else{
						verify_btn.html('{pigcms{:L_("验证消费")}');
					}
				});
				return false;
			});
		</script>
	</body>
</html>
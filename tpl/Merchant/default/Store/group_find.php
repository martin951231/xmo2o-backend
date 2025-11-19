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
        <style>
            .group_combine_verify{
                text-align: center;
            }
            .group_combine_verify .title{
                font-size: 16px;
                margin-bottom: 2px;
            }
        </style>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.group_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink" data-url="{pigcms{:U('group_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
                    <li class="urlLink" data-url="{pigcms{:U('group_coupon')}">
                        <div class="icon coupon"></div>
                        <div class="text">{pigcms{:L_("团购券管理")}</div>
                    </li>
					<li class="urlLink cur" data-url="{pigcms{:U('group_find')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("查找订单")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
					<form id="find-form" method="post">
						<select name="find_type" id="find_type" class="col-sm-2" style="margin-right:10px;height:42px;">
							<optgroup label="{pigcms{$config.group_alias_name}">
								<option value="1">{pigcms{:L_("消费密码")}</option>
							</optgroup>
							<optgroup label="{pigcms{:L_("实物")}">
								<option value="2">{pigcms{:L_("快递单号")}</option>
							</optgroup>
							<optgroup label="{pigcms{:L_("通用")}">
								<option value="3">{pigcms{:L_("订单编号")}</option>
								<option value="4">{pigcms{$config.group_alias_name}ID</option>
								<option value="5">{pigcms{:L_("用户")}ID</option>
								<option value="6">{pigcms{:L_("用户昵称")}</option>
								<option value="7">{pigcms{:L_("手机号码")}</option>
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
								<th>{pigcms{$config.group_alias_name}{pigcms{:L_("名称")}</th>
								<th>{pigcms{:L_("订单信息")}</th>
								<th>{pigcms{:L_("订单类型")}</th>
								<th>{pigcms{:L_("验证消费")}</th>
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
        <div id="group_combine_verify" class="grid-view" style="display:none;">
            <div></div>
            <div></div>

        </div>
		<script id="find_html" type="text/html">
			{{# for(var i = 0, len = d.list.length; i < len; i++){ }}
				<tr class="{{# if(i%2==1){ }}odd{{# }else{ }}even{{# } }}">
					<td width="100">{{ d.list[i].real_orderid }}</td>
					<td width="200"><a href="{pigcms{$config.site_url}/index.php?g=Group&c=Detail&group_id={{ d.list[i].group_id }}" target="_blank">{{ d.list[i].s_name }}</a></td>
					<td width="150">
						{pigcms{:L_("数量：")}{{ d.list[i].num }}<br/>
						{pigcms{:L_("总价：")}{{ d.list[i].total_money }}<br/>
					</td>
					<td width="80">
						{{# if(d.list[i].tuan_type == '0'){ }}
							{pigcms{$config.group_alias_name}券
						{{# }else if(d.list[i].tuan_type == '1'){ }}
							{pigcms{:L_("代金券")}
						{{# }else if(d.list[i].tuan_type == '2'){ }}
							{pigcms{:L_("实物")}
						{{# } }}
						
						<!--{!{# if(d.list[i].tuan_type != '2'){ }!}
							<br/><br/>{pigcms{:L_("消费密码：")}{{ d.list[i].group_pass }!}
						{!{# }else if(d.list[i].express_id != ''){ }}
							<br/><br/>{pigcms{:L_("快递单号：")}{{ d.list[i].express_id }!}
						{!{# } }!}-->
					</td>
					<td width="150">
					  {{# if(d.list[i].last_staff !=''){ }}
						{pigcms{:L_("操作店员：")}{{ d.list[i].last_staff }}<br/>
						{pigcms{:L_("消费时间：")}{{ d.list[i].use_time }}<br/>
						{{# }else{ }}
						 <span class="red">{pigcms{:L_("未验证消费")}</span>
						{{# } }}
					</td>
					<td width="180">
						{{# if(d.list[i].paid == '1'){ }}
							{pigcms{:L_("用户ID：")}{{ d.list[i].uid }}<br/>
							{pigcms{:L_("用户名：")}{{ d.list[i].nickname }}<br/>
							{pigcms{:L_("订单手机号：")}{{ d.list[i].group_phone }}<br/>
						{{# }else{ }}
							{pigcms{:L_("未付款用户无法查看")}
						{{# } }}
					</td>
					<td width="200">
						{{# if(d.list[i].paid == '1'){ }}
							{{# if(d.list[i].pay_type == 'offline' && d.list[i].third_id ==0 && d.list[i].status != 3){ }}
								<font color="red">{pigcms{:L_("线下未付款")}</font>

								{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
									<a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
								{{# }else{ }}
									<a href="{pigcms{:U('Store/group_verify')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("验证付款")}</a>
								{{# } }}
								
							{{# }else if(d.list[i].status == '0'){ }}
								<font color="green">{pigcms{:L_("已付款")}</font>
								
								{{# if(d.list[i].tuan_type != '2'){ }}
									<font color="red">{pigcms{:L_("未消费")}</font>
									{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
										<a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
									{{# }else{ }}
										<a href="{pigcms{:U('Store/group_verify')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("验证消费")}</a>
									{{# } }}
								{{# }else{ }}
									{{# if(d.list[i]['is_pick_in_store']){ }}
										<font color="red">{pigcms{:L_("未取货")}</font>
									{{# }else{ }}
										<font color="red">{pigcms{:L_("未发货")}</font>
									{{# } }}
								{{# } }}
							{{# }else if(d.list[i].status == '1'){ }}
								<font color="green">{pigcms{:L_("已消费")}</font>
								<font color="red">{pigcms{:L_("待评价")}</font>
								{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
									<a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
								{{# } }}
							{{# }else if(d.list[i].status == '3'){ }}
								<font color="red">{pigcms{:L_("已退款")}</font>
								{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
									<a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
								{{# } }}
							{{# }else if(d.list[i].status == '4'){ }}
								<font color="red">{pigcms{:L_("用户已取消")}</font>
								{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
									<a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
								{{# } }}
							{{# }else if(d.list[i].status == '7'){ }}
                                <font color="green">{pigcms{:L_("已付款")}</font>
                                <font color="red">{pigcms{:L_("未消费")}</font>
								{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
                                    <a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
                                {{# } }}
                            {{# }else{ }}
								<font color="green">{pigcms{:L_("已完成")}</font>
								{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
									<a  class="green handle_btn" data-title="{pigcms{:L_("查看消费码详情")}"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
								{{# } }}
							{{# } }}
						{{# }else{ }}
							<font color="red">{pigcms{:L_("未付款")}</font>
							{{# if((d.list[i].pass_array && d.list[i].num>1) || d.list[i].pass_num>1){ }}
								<a  class="green handle_btn"  href="{pigcms{:U('Store/group_pass_array')}&order_id={{ d.list[i].order_id }}" class="group_verify_btn">{pigcms{:L_("查看消费码详情")}</a>
							{{# } }}
					{{# } }}
						<br/>
						{pigcms{:L_("下单时间：")}{{ d.list[i].add_time }}<br/>
						{{# if(d.list[i].paid == '1'){ }}
							{pigcms{:L_("付款时间：")}{{ d.list[i].pay_time }}
						{{# } }}
					</td>
					<td class="button-column" width="40">
						<a title="{pigcms{:L_("查看订单详情")}"  data-title="{pigcms{:L_("订单详情")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Store/group_edit')}&order_id={{ d.list[i].order_id }}">
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
					var result = $(this).val();
                    console.log('val', $(this).val());
                    if($(this).val().substr(0, 1) != 'h'){
						console.log('e.keyCode', e.keyCode);
                        if(e.keyCode != 8){
                            $(this).val($(this).val().replace(/\s+/g,"").replace(/(\d{4})/g,'$1 '));
                        }
					}
				}
			});
            function getParam(url, name) {
                var reg = new RegExp("[&|?]" + name + "=([^&$]*)", "gi");
                var a = reg.test(url);
                return a ? RegExp.$1 : "";
            }
			$('#find-form').submit(function(){
				var find_value = $('#find_value');
				find_value.val($.trim(find_value.val()));
				if(find_value.val().length < 1){
					alert('{pigcms{:L_("请输入查找内容！")}');
					find_value.focus();
					return false;
				}
				
				//匹配网址情况
				var order_id = getParam(find_value.val(), 'id');
                if (order_id) {
                    find_value.val(order_id);
                }
				
				var post_type = $('#find_type').val();
				var post_value = $('#find_value').val().replace(/\s+/g,"");
				$('#find_submit').removeClass('btn-success').addClass('btn-error').prop('disabled',true).html('{pigcms{:L_("请求中")}...');
				$('#order_html').empty();
				$('#order_list').hide();
				$.post("{pigcms{:U('Store/group_find')}",{find_type:post_type,find_value:post_value},function(result){
					$('#find_submit').removeClass('btn-error').addClass('btn-success').prop('disabled',false).html('{pigcms{:L_("查找订单")}');
                    console.log(result,'result')
					data = $.parseJSON(result);
					console.log(data,'data')
					var flag =0
					if(data.row_count > 0){
					    if(data.search_type == 'group_combine'){
                            layer.open({
                                content: ' <div id="group_combine_verify" class="group_combine_verify">' +
                                    '            <div class="title">'+data.data.group_name+'【'+data.data.title+'】</div>' +
                                    '            <div>消费码：'+data.data.group_pass+'</div>' +
                                    '            ' +
                                    '        </div>'
                                ,btn: ['验证','取消']
                                ,yes: function(index, layero){
                                    //按钮【按钮一】的回调
                                    layer.confirm('{pigcms{:L_("是否验证")}?', {
                                        btn: ['{pigcms{:L_("是','否")}'] //"按钮")}
                                    }, function(){
                                    	if(flag == 1){
                                    		return false
                                    	}
                                    	flag = 1
                                        $.post("{pigcms{:U('group_combine_verify')}",{order_id:data.data.order_id},function(result){
                                            if(result.status == 0){
                                                alert(result.info);
                                            }else{
                                                layer.msg('{pigcms{:L_("验证成功")}', {icon: 1});
                                                // window.location.href = window.location.href;
                                            }
                                        },'json');
                                    }, function(){
                                        layer.msg('{pigcms{:L_("已取消验证")}', {icon: 2});
                                    });
                                }
                                ,cancel: function(){
                                    //右上角关闭回调

                                    //return false 开启该代码可禁止点击该按钮关闭
                                }
                            });


                        }else{
                            laytpl(document.getElementById('find_html').innerHTML).render(data, function(html){
                                document.getElementById('order_html').innerHTML = html;
                                $('#order_list').show();
                            });
                        }
					}else{
						alert('{pigcms{:L_("未查找到内容！")}');
					}
				});
				
				return false;
			});

			$('.group_verify_btn').live('click',function(){
				var verify_btn = $(this);
				layer.confirm('{pigcms{:L_("是否验证")}?', {
					  btn: ['{pigcms{:L_("是','否")}'] //"按钮")}
					}, function(){
					  	verify_btn.html('{pigcms{:L_("验证中")}..');
						$.get(verify_btn.attr('href'),function(result){
							if(result.status == 0){
                                alert(result.info);
							}else{
								//verify_btn.html('{pigcms{:L_("验证消费")}');
								layer.msg('{pigcms{:L_("验证成功")}', {icon: 1});
                                window.location.href = window.location.href;
							}
						});
					}, function(){
					 	layer.msg('{pigcms{:L_("已取消验证")}', {icon: 2});
				});
				return false;
			});
		</script>
	</body>
</html>
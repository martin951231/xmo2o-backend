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
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.appoint_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('appoint_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<p>&nbsp;</p>{pigcms{:L_("此页面只列出已归属到此店铺的订单。若想验证新订单或查找订单，请点击上面按钮。")}
				<div class="grid-view">
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
						<tbody>
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td width="100">{pigcms{$vo.order_id}</td>
									<td width="200"><a href="{pigcms{$config.site_url}/index.php?g=Appoint&c=Detail&appoint_id={pigcms{$vo.appoint_id}" target="_blank">{pigcms{$vo.appoint_name}</a></td>
									<td width="150">
										{pigcms{:L_("定金：")}{pigcms{:floatval($vo['payment_money'])}{pigcms{$config['Currency_txt']}<br/>
										{pigcms{:L_("总价：")}{pigcms{:floatval($vo['appoint_price'])}{pigcms{$config['Currency_txt']}<br/>
										<if condition='$vo["type"] != 2'>{pigcms{:L_("预约时间：")}{pigcms{$vo.appoint_date}&nbsp;{pigcms{$vo.appoint_time}</if>
									</td>
									<td width="150">
										<if condition="empty($vo['last_staff']) OR $vo['service_status'] eq 0">
											<span class="red">{pigcms{:L_("未验证服务")}</span>
										<else/>
											{pigcms{:L_("操作店员：")}{pigcms{$vo['last_staff']}<br/>
											{pigcms{:L_("消费时间：")}{pigcms{$vo['last_time']|date='Y-m-d H:i:s',###}<br/>
										</if>
									</td>
									<td width="180">
										{pigcms{:L_("用户ID：")}{pigcms{$vo.uid}<br/>
										{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>
										{pigcms{:L_("用户手机号：")}{pigcms{$vo.phone}<br/>
									</td>
									<td width="200">
									<if condition="$vo['paid'] == 0" >
										<font color="red">{pigcms{:L_("未支付")}</font>
										<if condition="$vo['service_status'] == 0" >
											<font color="red">{pigcms{:L_("未服务")}</font>
											<if condition='($vo["is_del"] eq 0) && ($vo["payment_status"] eq 0)'><a href="{pigcms{:U('appoint_verify',array('order_id'=>$vo['order_id']))}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a></if>
										<elseif condition="$vo['service_status'] == 1" />
											<font color="green">{pigcms{:L_("已服务")}</font>
										<elseif condition="$vo['service_status'] == 2" />
											<font color="green">{pigcms{:L_("已评价")}</font>
										</if>
									<elseif condition="$vo['paid'] == 1" />
										<font color="green">{pigcms{:L_("已支付")}</font>
										<if condition="$vo['service_status'] == 0" >
											<font color="red">{pigcms{:L_("未服务")}</font>
											<if condition='$vo["is_del"] eq 0'><a href="{pigcms{:U('appoint_verify',array('order_id'=>$vo['order_id']))}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a></if>
										<elseif condition="$vo['service_status'] == 1" />
											<font color="green">{pigcms{:L_("已服务")}</font>
										<elseif condition="$vo['service_status'] == 2" />
											<font color="green">{pigcms{:L_("已评价")}</font>
										</if>
									<elseif condition="$vo['paid'] == 2" />
										<font color="red">{pigcms{:L_("已退款")}</font>
									<elseif condition="$vo['paid'] == 3" />
										<font color="orange">{pigcms{:L_("用户已取消")}</font>
									<else/>
										<font color="red">{pigcms{:L_("订单异常")}</font>
									</if><br/>
										{pigcms{:L_("下单时间：")}{pigcms{$vo['order_time']|date='Y-m-d H:i:s',###}<br/>
										<if condition="$vo['pay_time']">{pigcms{:L_("付款时间：")}{pigcms{$vo['pay_time']|date='Y-m-d H:i:s',###}<br/></if>
										<if condition='$vo["is_del"] neq 0'>
											<font color="red">
												<switch name='vo["is_del"]'>
													<case value="1">{pigcms{:L_("已取消【用户】【PC端】")}</case>
													<case value="2">{pigcms{:L_("已取消【平台】")}</case>
													<case value="3">{pigcms{:L_("已取消【商家】")}</case>
													<case value="4">{pigcms{:L_("已取消【店员】")}</case>
													<case value="5">{pigcms{:L_("已取消【用户】【WAP端】")}</case>
												</switch>
											</font>
										</if>
									</td>
									<td class="button-column" width="40">
										<a title="{pigcms{:L_("查看订单详情")}"  data-title="{pigcms{:L_("订单详情")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('appoint_detail',array('order_id'=>$vo['order_id']))}">
											<i class="shortBtn">{pigcms{:L_("查看详情")}</i>
										</a>
									   <if condition='($vo["is_del"] eq 0) && ($vo["paid"] eq 0)'>
											<a href="javascript:void(0)" data-order-id="{pigcms{$vo['order_id']}" class="appoint_del" style="padding-right:8px;margin-top:20px;display:block;" class="red" title="{pigcms{:L_("取消订单")}">
												<i class="shortBtn">{pigcms{:L_("取消订单")}</i>
											</a>
									   </if>
									</td>
								</tr>
							</volist>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>
	<script>
	$(function(){
		$('.group_verify_btn').live('click',function(){
			var verify_btn = $(this);
			verify_btn.html('{pigcms{:L_("验证中")}..');
			$.get(verify_btn.attr('href'),function(result){
				if(result.status == 1){
					var icon = 'succeed';
					var button = [{
									name:'{pigcms{:L_("确定")}',
									callback:function () {  
										window.location.href = window.location.href;
									},
									focus:true
								}];
				}else{
					var icon = 'error';
					var button = [{name:'{pigcms{:L_("关闭")}'}];
					verify_btn.html('{pigcms{:L_("验证服务")}');
				}
				var content = result.info;
				alert(content);
			});
			return false;
		});
	});
	$('.appoint_del').click(function(){
		var url ="{pigcms{:U('ajax_staff_del')}";
		var order_id = $(this).data('order-id');
		if(confirm('{pigcms{:L_("取消后，将无法恢复，是否确认取消？")}')){
			$.post(url,{'order_id':order_id},function(data){
				alert(data.msg);
				if(data.status){
					location.reload();
				}
			},'json')
		}
		
	});
</script>
</html>
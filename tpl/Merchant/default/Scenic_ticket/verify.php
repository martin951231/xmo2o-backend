<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-home"></i>
				<a href="{pigcms{:U('order_list')}">景区管理</a>
			</li>
			<li class="active">待验证订单列表</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">


				<form id="find-form" method="post">
					<select name="find_type" id="find_type"  class="col-xs-1" style="height: 42px;margin-right: 16px;" >
						<optgroup label="景区门票">
							<option value="1" <if condition="$find_type eq 1">selected="selected"</if>>消费密码</option>
						</optgroup>

						<optgroup label="通用">
							<option value="2" <if condition="$find_type eq 2">selected="selected"</if>>订单编号</option>
							<option value="3" <if condition="$find_type eq 3">selected="selected"</if>>用户ID</option>
							<option value="4" <if condition="$find_type eq 4">selected="selected"</if>>用户昵称</option>
							<option value="5" <if condition="$find_type eq 5">selected="selected"</if>>手机号码</option>
						</optgroup>
					</select>
					<input class="col-sm-4" name="find_value" id="find_value" type="text" value="{pigcms{$find_value}" style="margin-right:10px;font-size:18px;height:42px;"/>
					<button class="btn btn-success" type="submit" id="find_submit">查找订单</button>
				</form>
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>编号</th>
									<th>门票标题</th>
									<th>门票数量</th>
									<th>门票信息</th>
									<th>订单信息</th>
									<th>用户信息</th>
									<th class="button-column" style="width:100px;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="now_order">
									<volist name="now_order" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td width="50">{pigcms{$vo.order_id}</td>
											<td>{pigcms{$vo.ticket_title}</td>
											<td>
												门票数量：{pigcms{$vo.ticket_count}张<br/>
												车位数量：<if condition="$vo['park_count'] eq 0">未预定<else/>{pigcms{$vo.park_count}位</if><br/>
												向导预定：<if condition="$vo['guide'] eq 0">未预定<else/><span class="red">已预定</span></if><br/>
											</td>
											<td>
												总价：{pigcms{$vo.order_total}<br/>
												余额支付：{pigcms{$vo.balance_pay}<br/>
												在线支付：{pigcms{$vo.payment_money}<br/>
											</td>
											<td>
												用户ID：{pigcms{$vo.user_id}<br/>
												用户名：{pigcms{$vo.nickname}<br/>
												手机号：{pigcms{$vo.phone}<br/>
											</td>
											<td>
												状态：<if condition="$vo.paid eq 1"><font color="red">未支付</font><else/><font color="green">已支付</font></if>
												<if condition="$vo.order_status eq 1"><font color="green">未消费</font>
													<elseif condition="$vo.order_status eq 2"/><font color="green">未评价</font>
													<elseif condition="$vo.order_status eq 3"/><font color="green">已评价</font>
													<elseif condition="$vo.order_status eq 4"/><font color="red">退款中</font>
													<elseif condition="$vo.order_status eq 5"/><font color="red">已退款</font>
												</if>
												<br/>下单时间：{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}<br/>
												支付时间：{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}<br/>
											</td>
											<td class="button-column" width="40">
												<a title="操作订单" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('order_detail',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130"></i>
												</a>
												<a href=""  onclick="verify({pigcms{$vo.order_id})">验证消费</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >无内容</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	$('.handle_btn').live('click',function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'操作订单',
			padding: 0,
			width: 720,
			height: 520,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});

	$('#ticket_id').change(function(){
		$('#frmselect').submit();
	});
	$('#type_id').change(function(){
		$('#frmselect').submit();
	});
});

function verify(order_id){
	$.ajax({
		type : "post",
		url : "{pigcms{:U('ajax_verify_ticket')}",
		dataType : "json",
		data:{

			order_id :order_id,
		},
		async:false,
		success : function(result){
			var rideList	=	result;
			if(result.errorCode == 0){
				alert(result.errorMsg);
				window.location.href	=	rideList.result;
			}else{
				alert(result.errorMsg)
			}
		},
		error:function(){
			alert('提交订单失败');
		}
	})
}
function CreateShop(){
	window.location.href = "{pigcms{:U('add')}";
}
</script>
<include file="Public:footer"/>

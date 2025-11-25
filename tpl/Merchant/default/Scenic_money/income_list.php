<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('index')}">景区余额</a></li>
			<li class="active">收入记录</li>

		</ul>
	</div>
	<style>
	.mainnav_title{
		margin-top:20px;
	}
	.mainnav_title ul a {
		padding: 15px 20px;
	}
	ul, ol {
		margin-bottom: 15px;
	}
	.mainnav_title span{
		color:#7EBAEF;

	}
	.mainnav_title a.on div{
		color:#C1BEBE;
	}
	.all{
		border-collapse:collapse;
		border:none;
	}
	.all td{
		border:solid #000 1px;
		border-color:"#cccc99";
		height: 20px;
		text-align: center;
	}
	.all th{
		border:solid #000 1px;
		border-color:"#cccc99";
		height: 20px;
	}
	button{
		padding: 6px;
		background-color: rgb(241, 235, 235);;
		box-sizing: border-box;
		border-width: 1px;
		border-style: solid;
		border-color: rgba(121, 121, 121, 1);
		border-radius: 2px;
		-moz-box-shadow: none;
		-webkit-box-shadow: none;
		box-shadow: none;
		font-size: 14px;
		color: #666666;
		cursor: pointer;

	}

	#myform div{
		margin-top:10px;
	}
	</style>


        <div style="margin:10px;">
				<div class="info" style="font-size:16px;font-family: 'Arial Negreta','Arial';font-weight: 700;">收入记录  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="exports();"><button>导出EXCEL</button></a></div>
				<form id="myform" method="post" action="{pigcms{:U('income_list')}" style="display:inline;">
					<div>
						业务类型：
						<select name="order_type">
							<volist name="alias_name" id="vo">
								<option value="{pigcms{$key}" <if condition="$order_type eq $key">selected=selected</if>>{pigcms{$vo}</option>
							</volist>
						</select>
					</div>
					<div>
						订&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;单：<input type="text" name="order_id" value="{pigcms{$order_id}" placeholder="输入订单号" >
					</div>
					<input type="hidden" name="mer_id" value="{pigcms{$mer_id}">
					<div>
					<div style="float:left"><font color="#000">时间筛选 ：</font></div>
					<input type="text" class="input fl" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
					<input type="text" class="input fl" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
					<input type="submit" value="查询">
					</div>
				</form>

        </div>


	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					<div class="tabbable" >
								<div class="row">
									<div class="col-xs-12">
										<div class="grid-view">

											<table class="table table-striped table-bordered table-hover">
												<thead>

													<tr>

														<th>订单号</th>
														<th>订单详情</th>
														<th>总额</th>
														<th>平台抽成</th>
														<th>支付时间</th>
														<th>当前景区余额</th>
														<th>操作</th>
													</tr>
												</thead>
												<tbody>
													<if condition="$income_list">
														<volist name="income_list" id="vo">
															<tr>

																<td><if condition="$vo.type eq 'withdraw'">w_{pigcms{$vo.id}<else />{pigcms{$vo.order_id}</if></td>
																<td>
																	{pigcms{:msubstr($vo['desc'],0,50,true,'utf-8')}
																</td>
																<td><if condition="$vo.type eq 1"><font color="#2bb8aa">+{pigcms{$vo.money|floatval}</font><elseif condition="$vo.type eq 2" /><font color="#f76120">-{pigcms{$vo.money|floatval}</font></if></td>
																<td>{pigcms{$vo.system_take}&nbsp;&nbsp;&nbsp;&nbsp;<font color="blue">({pigcms{$vo.percent} %)</font></td>
																<td><if condition="$vo['time'] gt 0">{pigcms{$vo.time|date="Y-m-d H:i:s",###}</if></td>
																<td><font color="#5167de">{pigcms{$vo.now_scenic_money}</font></td>
																<td style="font-size:12px;">
																	<if condition="$vo.order_type neq 'withdraw'">
																	<a title="操作订单" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Scenic_ticket/order_detail',array('order_id'=>$vo['order_id']))}">
																		<i class="ace-icon fa fa-search bigger-130"></i>
																	</a>
																	</if>
																</td>
															</tr>
														</volist>


														<tr class="odd">
															<td colspan="10" id="show_count"></td>
														</tr>
														<tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
													<else />
														<tr class="odd"><td class="textcenter red" colspan="10" >暂时还没有收入记录</td></tr>
													</if>
												</tbody>
											</table>
										</div>
									</div>
									<!--div class="col-xs-2" style="margin-top: 15px;">
										<a class="btn btn-success" href="#">导出成excel</a>
									</div-->
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
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


});

function exports(){
	var order_type = $('select[name="order_type"]').val();
	var order_id = $('input[name="order_id"]').val();
	var begin_time = $('input[name="begin_time"]').val();
	var end_time = $('input[name="end_time"]').val();
	var store_id = $('#store_id').val();
	if(order_type=='all'&&order_id!=''){
		alert('该分类下没有不能填订单ID');
	}else{
		var export_url ="{pigcms{:U('export',array('scenic_id'=>$scenic_id, 'type' => 'income'))}&order_type="+order_type+'&order_id='+order_id+'&begin_time='+begin_time+'&end_time='+end_time;
		window.location.href = export_url;
	}
}




</script>
<include file="Public:footer"/>

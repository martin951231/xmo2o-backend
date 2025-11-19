<include file="Giftpick:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('index')}">自提点管理</a>
			</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<form action="{pigcms{:U('index')}" method="get">
					<input type="hidden" name="c" value="Giftpick"/>
					<input type="hidden" name="a" value="index"/>
					
					
					搜索: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<select name="searchtype">
						<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
						<option value="order_name" <if condition="$_GET['searchtype'] eq 'order_name'">selected="selected"</if>>订单名称</option>
						<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>客户名称</option>
						<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>客户电话</option>
					</select>
					<font color="#000">日期筛选：</font>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
					<input type="submit" value="查询" class="button"/>　　
					
					
				</form>
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>订单编号</th>
									<th>{pigcms{$config.gift_alias_name}信息</th>
									<th>订单信息</th>
									<th>订单用户</th>
									<!-- <th>订单状态</th> -->
									<th>下单时间</th>
									<th>查看订单详情</th>
									<th width="200" style="text-align:center;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.order_id}</td>
											<td>{pigcms{$config.gift_alias_name}ID：{pigcms{$vo.gift_id}　
											<br/>{pigcms{$config.gift_alias_name}名称：{pigcms{$vo.gift_name}</td>
											<td>数量：{pigcms{$vo.num}<br/>
											总{pigcms{$config['score_name']}：{pigcms{$vo.total_integral|floatval=###}
											<if condition="$vo['exchange_type'] eq 1">
												&nbsp;&nbsp;&nbsp;&nbsp;总价：{pigcms{$config['Currency_symbol']}{pigcms{$vo.total_price|floatval=###}
											</if>
											</td>
											<td>
												<if condition="$vo['mer_id']">
												商家名：{pigcms{$vo.name}<br/>商家手机号：{pigcms{$vo.m_phone}
												<else/>
												用户名：{pigcms{$vo.nickname}<br/>用户手机号：{pigcms{$vo.phone}
												</if>
											</td>
											<td>{pigcms{$vo['order_time']|date='Y-m-d H:i:s',###}</td>
											<td>
												<a title="操作订单" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('order_detail',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130"></i>
												</a>
											</td>
											<td>
											<if condition="$vo['status'] eq 2">
												<a title="已接单" class="green edit_btn" style="padding-right:8px;" href="javascript:;" >完成</a>
											<else/>
												<if condition="$vo['pstatus'] eq 0">
												<span style="color: green">待分配到自提点</span>
												<elseif condition="$vo['pstatus'] eq 1" />
												<a title="操作订单" class="green edit_btn js-add-order js-add-order-{pigcms{$vo.p_order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.p_order_id}">接货</a>
												<elseif condition="$vo['pstatus'] eq 2" />
													<a title="操作订单" class="green edit_btn js-send-order js-add-order-{pigcms{$vo.p_order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.p_order_id}">确认提货</a>
												<elseif condition="$vo['pstatus'] eq 3" />
												<a title="已接单" class="green edit_btn" style="padding-right:8px;" href="javascript:;" >完成</a>
												</if>
											</if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >暂时没有订单！</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	$(function(){
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.o2oTop.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'操作',
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
				opacity:'0.4', close: function(){location.reload();}
			});
			return false;
		});
		$('.js-add-order').click(function(){
			var order_id = $(this).attr('js-order');
			$('.js-add-order-'+order_id).html('处理中');
			$.post("{pigcms{:U('apick')}",{order_id:order_id},function(result){
				if(result.status == 1){
					$('.js-add-order-'+order_id).unbind('click');
					$('.js-add-order-'+order_id).removeClass('js-add-order').addClass('js-send-order').html('确认提货');
				}else{
					bootbox.alert(result.info);
					$('.js-add-order-'+order_id).html('接货');
				}
			}, 'json');
		});
		$('.js-send-order').live('click',function(){
			var obj = $(this);
			var order_id = $(this).attr('js-order');
			obj.html('处理中');
			$.post("{pigcms{:U('check')}",{order_id:order_id},function(result){
				if(result.status == 1){
// 					$('.js-add-order-'+order_id).unbind('click');
// 					obj.unbind('click');
					obj.parent('td').html('<span style="color: green">完成</span>');
// 					obj.removeClass('js-send-order');
				}else{
					bootbox.alert(result.info);
					obj.html('确认提货');
				}
			});
		});
	});
</script>
<include file="Giftpick:footer"/>

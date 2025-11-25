<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Mall/group_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("拼团列表")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Mall/group_order_list',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("团购小组列表")}</a>
			</li>
			<li class="active">{pigcms{:L_("团购小组详情")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
				
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
								
									<th>{pigcms{:L_("参团用户昵称")}</th>
									<th>{pigcms{:L_("手机")}</th>
							
									<th>{pigcms{:L_("购买时间")}</th>
									<th>{pigcms{:L_("消费状态")}</th>
									<th>{pigcms{:L_("团员身份")}</th>
									<th>{pigcms{:L_("订单详情")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										
											<td>{pigcms{$vo['nickname']}</td>
											<td>{pigcms{$vo['phone']}</td>								
											<td>
											<if condition="$vo.type eq 0">{pigcms{$vo['pay_time']|date="Y-m-d H:i:s",###}</if></td>

											<td>
											<if condition="$vo.type eq 0">
											<!-- 0{pigcms{:L_("：未使用，1：商家确认，2：已使用，3：已评论，:4：退款，5：取消")} -->
												<if condition="$vo.status eq 0">
													<font color="red">{pigcms{:L_("未使用")}</font>
												<elseif condition="$vo.status eq 1" />
													<font color="blue">{pigcms{:L_("商家确认")}</font>
												<elseif condition="$vo.status eq 2" />
													<font color="green">{pigcms{:L_("已使用")}</font>
												<elseif condition="$vo.status eq 3" />
													<font color="blue">{pigcms{:L_("已评论")}</font>
												<elseif condition="$vo.status eq 4" />
													<font color="red">{pigcms{:L_("已退款")}</font>
												<elseif condition="$vo.status eq 5" />
													<font color="red">{pigcms{:L_("已取消")}</font>
												</if>
											</if>
											</td>
											<td>
												<if condition="$vo.buy_type eq 1">
													<font color="green">{pigcms{:L_("团长")}</font>
												<elseif condition="$vo.type eq 1" />
													<font color="red">{pigcms{:L_("机器人(商家手动生成")})</font>
												<else />
													<font color="blue">{pigcms{:L_("成员")}</font>
												</if>
											</td>
											<td><if condition="$vo.type eq 0">
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id']))}">
													<i class="ace-icon fa fa-search bigger-130"></i>
												</a></if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("没有团购小组")}</td></tr>
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
		$('.group_name').hover(function(){
			var top = $(this).offset().top;
			var left = $(this).offset().left+$(this).width()+10;
			$('body').append('<div id="group_name_div" style="position:absolute;z-index:5555;background:white;top:'+top+'px;left:'+left+'px;border:1px solid #ccc;padding:10px;"><div style="margin-bottom:10px;"><b>{pigcms{:L_("商品标题：")}</b>'+$(this).data('title')+'</div><div><b>{pigcms{:L_("商品图片：")}</b><img src="'+$(this).data('pic')+'" style="width:180px;"/></div></div>');
		},function(){
			$('#group_name_div').remove();
		});	
		$('.handle_btn').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 700,
				height: 500,
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
</script>
<include file="Public:footer"/>

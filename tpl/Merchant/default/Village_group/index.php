<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Group/index')}">{pigcms{$config.group_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("商品列表")}</li>
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
					<div class="clearfix">
						
						<form action="{pigcms{:U('Village_group/index')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="index"/>
							{pigcms{:L_("日期筛选：")}
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time|default="date('Y-m-d')"}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							{pigcms{:L_("筛选")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("商品名称")}</option>
								<!--option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>{pigcms{:L_("商品编号")}</option-->
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单")}ID</option>lue="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("商品标题")}</option>
							</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("商品名称")}</th>
									<th>{pigcms{:L_("订单")}ID</th>
									<!--th>{pigcms{:L_("团长信息")}</th>
									<th>{pigcms{:L_("用户信息")}</th>
									<th>{pigcms{:L_("自提地址")}</th-->
									<th>{pigcms{:L_("订单总金额")}</th>
									<!--th>{pigcms{:L_("总金额")}</th-->
									<th>{pigcms{:L_("购买数量")}</th>
									<th>{pigcms{:L_("支付时间")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
									<th>{pigcms{:L_("配送状态")}</th>
								</tr>
							</thead>
							<tbody>
								
								
								<if condition="is_array($start_list)">
									<volist name="start_list" id="vo">
										<tr>
											<if condition="!$_GET['goods_id']">
                                                <th>{pigcms{$vo.name}<if condition="$vo.village_group_type eq 1"><span style="color: red;">{pigcms{:L_("（周期购）")}</span></if></th>
											</if>
											<td class="order">{pigcms{$vo.order_id}</td>
											<!--td>
												{pigcms{:L_("团长")}:{pigcms{$vo.header_info.name}<br>
												{pigcms{:L_("手机")}:{pigcms{$vo.header_info.phone}<br>
											</td>
											<td>
												{pigcms{:L_("用户")}id:{pigcms{$vo.uid}<br>
												{pigcms{:L_("用户手机")}:{pigcms{$vo.phone}<br>
												{pigcms{:L_("用户昵称")}:{pigcms{$vo.nickname}<br>
											</td>
											<td>{pigcms{$vo.header_info.pick_address.pick_addr}</td-->
											<td>{pigcms{$vo.all_price|floatval}</td>
											<!--td>{pigcms{$vo.all_price|floatval}</td-->
											<td>{pigcms{$vo.all_num|floatval}</td>
											<td>{pigcms{$vo.pay_time|date="Y-m-d H:i:s",###}</td>
											<td>	
												<if condition="$vo.status eq 2 ">
													<font color="green">{pigcms{:L_("已完成")}</font>
												<elseif condition="$vo['status'] egt 1 AND $vo['status'] lt 10" />
													<font color="green">{pigcms{:L_("已支付")}</font>
												<elseif condition="$vo['status'] eq 11 " />
													<font color="red">{pigcms{:L_("已取消")}</font>
												<elseif condition="$vo['status'] eq 10 " />
													<font color="red">{pigcms{:L_("超时未支付")}</font>
												<elseif condition="$vo['status'] eq 0 " />
													<font color="red">{pigcms{:L_("未支付")}</font>
												</if>
											</td>
											<td>
                                                <if condition="$vo.village_group_type eq 1">
                                                    <a title="{pigcms{:L_("操作订单")}" class="green handle_btn" href="{pigcms{:U('Village_group/join_order_detail', array('order_id' => $vo['order_id']))}">{pigcms{:L_("查看详情")}</a>
                                               <elseif condition="$vo.deliver_status eq 0"/>
													<font color="red">{pigcms{:L_("未发货")}</font>
												<elseif condition="$vo.deliver_status eq 1" />
													<font color="green">{pigcms{:L_("已发货")}</font>
												<elseif condition="$vo.deliver_status eq 2" />
													<font color="green">{pigcms{:L_("团长收货 待自提")}</font>
												<elseif condition="$vo.deliver_status eq 3 || $vo.deliver_status eq 4" />
													<font color="green">{pigcms{:L_("已取货")}</font>
												</if>
											</td>
											<!--td class="textcenter">
												<a href="javascript:void(0)" onclick="window.top.artiframe('{pigcms{:U('Village_group/join_order_detail',array('order_id'=>$vo['order_id']))}','订单详情',800,600,true,false,false,editbtn,'store_add',true);">订单详情</a>  
											</td-->
										</tr>
									</volist>
								
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("暂无订货信息！")}</td></tr>
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
	function CreateShop(){
		window.location.href = "{pigcms{:U('Group/add')}";
	}
	$(function(){
		$('.group_name').hover(function(){
			var top = $(this).offset().top;
			var left = $(this).offset().left+$(this).width()+10;
			$('body').append('<div id="group_name_div" style="position:absolute;z-index:5555;background:white;top:'+top+'px;left:'+left+'px;border:1px solid #ccc;padding:10px;"><div style="margin-bottom:10px;"><b>{pigcms{:L_("商品标题：")}</b>'+$(this).data('title')+'</div><div><b>{pigcms{:L_("商品图片：")}</b><img src="'+$(this).data('pic')+'" style="width:180px;"/></div></div>');
		},function(){
			$('#group_name_div').remove();
		});	
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 430,
				height: 433,
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
        $('.handle_btn').live('click',function(){
            console.log('handle_btn1');
            art.dialog.open($(this).attr('href'),{
                init: function(){
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle',iframe);
                },
                id: 'handle',
                title:'{pigcms{:L_("查看详情")}',
                padding: 0,
                width: 800,
                height: 600,
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

<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("商家余额")}</a></li>
			<li class="active">{pigcms{:L_("收入记录")}</li>

		</ul>
	</div>
	<style>
		.item{
			margin-bottom: 10px;
		}

		.title a{
			color: #393939;
			font-weight: normal;
			margin-right: 20px;
		}

		.title a.on{
			color: #428bca;
		}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					<div style="margin:10px;">
						<div class="info " style="font-size:16px;font-family: 'Arial Negreta','Arial';font-weight: 700;">
							<div class="title item">
								<a href="{pigcms{:U('Merchant_money/card_union_statistics',array('union_type'=>0))}" class="<if condition="!$_GET['union_type']">on</if>" >{pigcms{:L_("我待兑现给其他商家的统计")} </a>
								<a href="{pigcms{:U('Merchant_money/card_union_statistics',array('union_type'=>1))}" class="<if condition="$_GET['union_type'] eq 1">on</if>"> {pigcms{:L_("其他商家待兑现给我的统计")} </a>
							</div>
						</div> 
						<form id="myform" method="get" action="{pigcms{:U('Merchant_money/card_union_statistics')}" style="display:inline;">
							<input type="hidden" name="c" value="Merchant_money"/>
							<input type="hidden" name="a" value="card_union_statistics"/>
							<div class="item">
								{pigcms{:L_("业务类型：")}
								<select name="order_type">
									<volist name="alias_name" id="vo">
										<option value="{pigcms{$key}" <if condition="$order_type eq $key">selected=selected</if>>{pigcms{$vo}</option>
									</volist>
								</select>
							</div>
							<div class="item">
								选择商家：
								<span class="fl">全部商家</span><input type="radio"  class="input-text fl " href="{pigcms{:U('mer_list',array('union_type'=>$_GET['union_type']))}" data-type="all_mer" name="mer_list" value="-1" <if condition="$_GET['mer_list'] eq -1 OR !isset($_GET['mer_list'])">checked </if>>
								<span class="fl">部分商家</span><input type="radio"  class="input-text fl "  href="{pigcms{:U('mer_list',array('union_type'=>$_GET['union_type']))}" id="part_mer" data-type="part_mer" name="mer_list"  <if condition="$_GET['mer_list'] neq -1 AND !empty($_GET['mer_list'])">checked value="{pigcms{$_GET['mer_list']}" <else />value="0"</if> >
								<a href="javascript:void(0)" id="edit_mer" style="display:none;line-height: 16px;" data-type="part_mer">编辑</a>
							</div>

							<input type="hidden" name="mer_id" value="{pigcms{$mer_id}">
							<div>
								<div style="float:left"><font color="#000">{pigcms{:L_("时间筛选 ：")}</font></div>
								<input type="text" class="input fl" name="begin_time" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
								<input type="text" class="input fl" name="end_time" id="d4312" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
								<input type="submit" value="{pigcms{:L_("查询")}">

								<span style="font-size:18px;color:red;">{pigcms{:L_("待兑现总计")}：{pigcms{$income_sum}
									<div style="font-size:12px;display:inline">
										<a href="javascript:void(0);" onclick="exports();" style="float: right;">{pigcms{:L_("导出")}EXCEL</a>
									</div>
								</span>
							</div>
						</form>
					</div>
					<div class="tabbable" >
						<div class="row">					
							<div class="col-xs-13">		
								<div class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
												<th>{pigcms{:L_("订单号")}</th>
												<th>{pigcms{:L_("订单类型")}</th>
												<th>{pigcms{:L_("商户信息")}</th>
												<th>{pigcms{:L_("订单总额")}</th>
												<th>{pigcms{:L_("商家会员卡消费总额")}</th>
												<th>{pigcms{:L_("平台佣金")}</th>
												<th>{pigcms{:L_("支付时间")}</th>
												<!-- <th class="am-text-middle">{pigcms{:L_("操作")}</th> -->
											</tr>
										</thead>
										<tbody>
											<if condition="$income_list">
												<volist name="income_list" id="vo">
													<tr>
														<td><if condition="$vo.type eq 'withdraw'">w_{pigcms{$vo.id}<else />{pigcms{$vo.order_id}</if></td>
														<td>
                                                            <?php echo $alias_name[$vo['type']];?>
                                                        </td>
														<td>
															{pigcms{:L_("商家名称")}：{pigcms{$vo['name']}
															<if condition="$_GET['union_type'] eq 1">
																<br/>
															{pigcms{:L_("店铺名称")}：{pigcms{$vo['store_name']}
															</if>
														</td>
														<td>{pigcms{$vo.total_money|floatval}</td>
														<td><if condition="$vo.income eq 1"><font color="#2bb8aa">+{pigcms{$vo.money|floatval}</font><elseif condition="$vo.income eq 2" /><font color="#f76120">-{pigcms{$vo.money|floatval}</font></if></td>	
														<td><font color="#5167de">{pigcms{$vo.system_take} <if condition="$vo['system_take'] gt 0" >{pigcms{:L_("（抽成比例")} {pigcms{$vo.percent} %）</if></font></td>
														<td><if condition="$vo['use_time'] gt 0">{pigcms{$vo.use_time|date="Y-m-d H:i:s",###}</if></td>
													
													</tr>
												</volist>
										
												
												<tr class="odd">
													<td colspan="13" id="show_count"></td>
												</tr>
												<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>	
											<else />
												<tr class="odd"><td class="textcenter red" colspan="13" >{pigcms{:L_("暂时还没有收入记录")}</td></tr>
											</if>
										</tbody>
									</table>
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
	
	$('input:radio').click(function(){
		var obj = $(this);		
		var title= '选择商家';		
		var type= $(obj).data('type');	
		
		if(type=='part_mer'){
			window.top.art.dialog.data('mer_list', $(obj).val());			
			var href= $(obj).attr('href')+'&type'+type+'&mer_list='+$(obj).val();
			$('#edit_mer').show();
			
			window.top.art.dialog.open(href,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: type,
				title:title,
				padding: 0,
				width: 600,
				height: 500,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4',
				close:function(){					
					var mer_list = window.top.art.dialog.data('mer_list'); 
					$(obj).val(mer_list)
					$('#part_store').val(0)
					$("input[name='store_list']:eq(0)").attr("checked",'checked'); 
					$("input[name='store_list']:eq(1)").removeAttr("checked");
					$('#edit_store').hide();					
				}
			});
						
		}else{	
			$('#edit_mer').hide();
			
		}
		
	})
	
	$('#edit_mer').click(function(){
		var obj = $('#part_mer');
		var title= '选择商家'
		var type= $(obj).data('type')
		window.top.art.dialog.data('mer_list', $(obj).val());			
		var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&mer_list='+$(obj).val();

		window.top.art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: type,
			title:title,
			padding: 0,
			width: 600,
			height: 500,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4',
			close:function(){
				var mer_list = window.top.art.dialog.data('mer_list'); 
				$(obj).val(mer_list)			
			}
		});
	})
	
	

});

		 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>

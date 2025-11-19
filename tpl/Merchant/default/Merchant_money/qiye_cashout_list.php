<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("商家余额")}</a></li>
			<li class="active">{pigcms{:L_("提现记录")}</li>

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
	button{
		padding: 6px;
		background-color: rgba(255, 255, 255, 0);
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
	</style>
       
     
	
        <style type="text/css">
            .mainnav_title {line-height:40px;/* height:40px; */border-bottom:1px solid #eee;color:#31708f;}
            .mainnav_title a {color:#004499;margin:0 5px;padding:4px 7px;background:#d9edf7;}
            .mainnav_title a:hover ,.mainnav_title a.on{background:#498CD0;color:#fff;text-decoration: none;}
        </style>
      

	
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
			<select id="withdraw_type" name="withdraw_type" >	
				<option value="0">{pigcms{:L_("==提现方式==")}</option>
				<option value="1" <if condition="$_GET['type'] eq 1" >selected="selected"</if>>{pigcms{:L_("提现至银行卡")}</option>
				<option value="2" <if condition="$_GET['type'] eq 2" >selected="selected"</if>>{pigcms{:L_("提现至支付宝")}</option>
				
			</select>
			
				<div class="col-sm-12">
					<div class="tabbable" >
								<div class="row">					
									<div class="col-xs-12">		
										<div class="grid-view">
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th>{pigcms{:L_("申请时间")}</th>
														<th>{pigcms{:L_("提现时间")}</th>
														<th>{pigcms{:L_("提现金额")}</th>
														<th>{pigcms{:L_("状态")}</th>
														<th>{pigcms{:L_("备注")}</th>
													</tr>
												</thead>
												<tbody>
													<if condition="$cashout_list">
														<volist name="cashout_list" id="vo">
															<tr>
																<td>{pigcms{$vo.add_time|date="Y/m/d H:i",###}</td>
																<td><if condition="$vo.cashout_time neq 0">{pigcms{$vo.cashout_time}<else/>----</if></td>
																<td>{pigcms{$vo['money']}{pigcms{$config['Currency_txt']}</td>
																<td>
																	<if condition="$vo.status eq 0">
																		<font color="red">{pigcms{:L_("审核中")}...</font>
																	<elseif condition="$vo.status eq 1" />
																		<font color="green">{pigcms{:L_("已通过")}</font>
																	<elseif condition="$vo.status eq 2 OR $vo.status eq 4" />
																		<font color="red">{pigcms{:L_("被驳回")}</font>
																	</if>
																	
																</td>
																<td>{pigcms{$vo.remark}</td>
																
															</tr>
														</volist>
														<tr><td class="textcenter pagebar" colspan="5">{pigcms{$pagebar}</td></tr>	
													<else/>
														<tr class="odd"><td class="textcenter red" colspan="17" >{pigcms{:L_("没有提现记录")}</td></tr>
													</if>
												
														
														
												
												</tbody>
											</table>
										</div>						
									</div>
									<!--div class="col-xs-2" style="margin-top: 15px;">
										<a class="btn btn-success" href="#">{pigcms{:L_("导出成")}excel</a>
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
			title:'{pigcms{:L_("操作订单")}',
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
	
	$('#group_id').change(function(){
		$('#frmselect').submit();
	});	
	
	$('#withdraw_type').change(function(){
		window.location.href="{pigcms{:U('qiye_cashout_list')}&type="+$(this).val();
	});
	
});




</script>
<include file="Public:footer"/>

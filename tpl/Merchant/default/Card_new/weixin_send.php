<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Card_new/card_new_coupon')}">{pigcms{:L_("会员卡优惠券")}</a>
			</li>
			<li class="active">{pigcms{:L_("派发优惠券")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">
					
					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab">
							<li >
								<a  href="{pigcms{:U('Card_new/send_coupon')}&tab=1">
									{pigcms{:L_("分组派发")}
								</a>
							</li>
							<li>
								<a  href="{pigcms{:U('Card_new/send_coupon')}&tab=2" >
									{pigcms{:L_("个人派发")}
								</a>
							</li>
							
							<li>
								<a href="{pigcms{:U('Card_new/send_all')}" >
									{pigcms{:L_("全部派发")}
								</a>
							</li>
							<li class="active">
								<a href="{pigcms{:U('Card_new/weixin_send')}" >
									{pigcms{:L_("微信购买派发")}
								</a>
							</li>
							<li>
								<a href="{pigcms{:U('Card_new/send_history')}" >
									{pigcms{:L_("派发记录")}
								</a>
							</li>
						
						</ul>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane <if condition="$_GET['tab'] eq 1 OR !isset($_GET['tab'])">active</if>">
								<div class="widget-box">
									<div class="widget-body" id="main" style="padding:20px;width:100%;overflow:auto">
										{pigcms{:L_("微信购买金额设置：")}<input type="text" name="money" value="{pigcms{$now_card.weixin_send_money}"> {pigcms{$config['Currency_txt']}({pigcms{:L_("请填写大于等于0.01的数字")})
									
									</div>
									
									<table class="table table-striped table-bordered table-hover" id="user_table" >
										<thead>
											<tr>
												<th>{pigcms{:L_("勾选优惠券")}</th>
												
												<th>{pigcms{:L_("优惠券名称")}</th>
												<th>{pigcms{:L_("优惠券描述")}</th>
												
											</tr>
										</thead>
										<tbody id="user_list">
										<volist name="coupon_list" id="vo">
											
											
												<tr class="even"><td style="width: 120px"><input type="checkbox" name="coupon_id[]" value="{pigcms{$vo.coupon_id}" id="coupon{pigcms{$vo.id}" <if condition="in_array($vo['coupon_id'],$now_card['weixin_send_couponlist'])">checked="checked" </if>></td><td style="width: 120px">{pigcms{$vo.name}</td><td style="width: 120px">{pigcms{$vo.des}</td></tr>
										</volist>
										</tbody>
									</table>
								</div>	
								<div class="row">					
									<div class="row">					
									<div class="col-xs-12">		
										<div class="grid-view">
											<button class="btn btn-info" type="submit" id="save_btn" style="margin-left: 25%;" href="">
												<i class="ace-icon fa fa-check bigger-110"></i>
												{pigcms{:L_("保存")}
											</button>
										</div>						
									</div>
								
								</div>
								
								</div>
							</div>
							
							<style type="text/css">
								.radio {
								    min-height: 27px;
								}
							</style>
							
							
						</div>
					</div>	
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
	
	var tab = $('#myTab .active a').attr('data-id');
	$(function(){
		
		$('#save_btn').click(function(){
			var send_url = "{pigcms{:C('config.site_url')}{pigcms{:U('Card_new/weixin_send')}";
			
			if($("input[name='coupon_id[]']:checked").length==0){
				alert('{pigcms{:L_("没有勾选优惠券")}');return false;				
			}
			

            var coupon_id='';
		 	$('input[name="coupon_id[]"]').each(function () {
                if ($(this).is(":checked")) {
                    coupon_id +=$(this).val()+',';
                }
            });
			var money = $('input[name="money"]').val();
			if(Number(money)<0.01){
				alert('{pigcms{:L_("最小金额为")}0.01');return false;		
			}
            coupon_id=coupon_id.substring(0,coupon_id.length-1);
			$.post(send_url, {money: money,coupon_id:coupon_id}, function(data, textStatus, xhr) {
				if(!data.error_code){
					window.location.reload();
				}else{
					alert(data.msg);
				}
			});
        	
		});


		
	});
</script>


<include file="Public:footer"/>

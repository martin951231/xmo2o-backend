<include file="Public:header"/>
	<style>
	.line_m{/*text-decoration:line-through*/text-align:center} 
		.table_form{border:1px solid #ddd;}
		.tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
		.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
		.tab_ul>li>a {
			position: relative;
			display: block;
			padding: 10px 15px;
			margin-right: 2px;
			line-height: 1.42857143;
			border: 1px solid transparent;
			border-radius: 4px 4px 0 0;
			padding: 7px 12px 8px;
			min-width: 50px;
			text-align: center;
		}
		.tab_ul>li>a, .tab_ul>li>a:focus {
			border-radius: 0!important;
			border-color: #c5d0dc;
			background-color: #F9F9F9;
			color: #999;
			margin-right: -1px;
			line-height: 18px;
			position: relative;
		}
		.tab_ul>li>a:focus, .tab_ul>li>a:hover {
			text-decoration: none;
			background-color: #eee;
		}
		.tab_ul>li>a:hover {
			border-color: #eee #eee #ddd;
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #555;
			background-color: #fff;
			border: 1px solid #ddd;
			border-bottom-color: transparent;
			cursor: default;
		}
		.tab_ul>li>a:hover {
			background-color: #FFF;
			color: #4c8fbd;
			border-color: #c5d0dc;
		}
		.tab_ul>li:first-child>a {
			margin-left: 0;
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #576373;
			border-color: #c5d0dc #c5d0dc transparent;
			border-top: 2px solid #4c8fbd;
			background-color: #FFF;
			z-index: 1;
			line-height: 18px;
			margin-top: -1px;
			box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #555;
			background-color: #fff;
			border: 1px solid #ddd;
			border-bottom-color: transparent;
			cursor: default;
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #576373;
			border-color: #c5d0dc #c5d0dc transparent;
			border-top: 2px solid #4c8fbd;
			background-color: #FFF;
			z-index: 1;
			line-height: 18px;
			margin-top: -1px;
			box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
		}
		.tab_ul:before,.tab_ul:after{
			content: " ";
			display: table;
		}
		.tab_ul:after{
			clear: both;
		}
	</style>
	<form id="myform"  frame="true" refresh="">
	  	<ul class="tab_ul">
			<li <if condition="$_GET['type'] eq 'alipay'">class="active"</if>><a data-toggle="tab" href="{pigcms{:U('Merchant/edit_pay_rates',array('type'=>'alipay','mer_id'=>$mer_id))}" >支付宝支付配置</a></li>
	  	</ul>
	  	<input type="hidden" name="mer_id" value="{pigcms{$_GET['mer_id']}">
	  	<input type="hidden" name="store_id" value="{pigcms{$_GET['store_id']}">
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_alipay" style="display: table;">
		   	<tbody>
				<tr>
				 	<th width="160">是否开启手续费：</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr[$_GET['type'].'_pay_rate']>=0&&$mer_pr[$_GET['type'].'_pay_rate']!=''){</php>selected<php>}</php>">
								<span>设置</span>
								<input type="radio" name="open_pay_rate" value="1" <php>if($mer_pr[$_GET['type'].'_pay_rate']>=0&&$mer_pr[$_GET['type'].'_pay_rate']!=''){</php>checked="checked"<php>}</php>/>
							</label>
						</span>			     
						<span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr[$_GET['type'].'_pay_rate']<0||$mer_pr[$_GET['type'].'_pay_rate']==''){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_pay_rate" value="0"   <php>if($mer_pr[$_GET['type'].'_pay_rate']<0||$mer_pr[$_GET['type'].'_pay_rate']==''){</php>checked="checked"<php>}</php>/>
							</label>
						</span>				
						
					</td>
				</tr>
				<tr class="pay_rate pay_type" <php>if($mer_pr[$_GET['type'].'_pay_rate']<0||$mer_pr[$_GET['type'].'_pay_rate']==''){</php>style="display:none"<php>}</php>>
				 	<th width="160">手续费支付方：</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr[$_GET['type'].'_pay_type']==1){</php>selected<php>}</php>">
								<span>店铺支付</span>
								<input type="radio" name="{pigcms{$_GET['type']}_pay_type" value="1" <php>if($mer_pr[$_GET['type'].'_pay_type']==1){</php>checked="checked"<php>}</php>/>
							</label>
						</span>			     
						<span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr[$_GET['type'].'_pay_type']==0){</php>selected<php>}</php>">
								<span>用户支付</span>
								<input type="radio" name="{pigcms{$_GET['type']}_pay_type" value="0"   <php>if($mer_pr[$_GET['type'].'_pay_type']==0){</php>checked="checked"<php>}</php>/>
							</label>
						</span>
					</td>
				</tr>
				<tr class="pay_rate pay_percent" <php>if($mer_pr[$_GET['type'].'_pay_rate']<0||$mer_pr[$_GET['type'].'_pay_rate']==''){</php>style="display:none"<php>}</php>>
				 	<th width="160">手续费比例：</th>
					<td>
						<input type="text" id="scan_percent" class="input-text" name="<php>echo $_GET['type'].'_pay_rate';</php>"  value="<php>echo $mer_pr[$_GET['type'].'_pay_rate'];</php>" size="10" validate="number:true,max:100" tips="商家独立支付的手续费（点击设置后，请填写百分比，不要填写%，选择跳过则跳过" />
					</td>
				</tr>
				<tr>
				 	<th width="160">superpay 开启：</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['is_open']==1){</php>selected<php>}</php>">
								<span>是</span>
								<input type="radio" name="is_open" value="1" <php>if($mer_pr['is_open']==1){</php>checked="checked"<php>}</php>/>
							</label>
						</span>			     
						<span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['is_open']==0){</php>selected<php>}</php>">
								<span>否</span>
								<input type="radio" name="is_open" value="0"   <php>if($mer_pr['is_open']==0){</php>checked="checked"<php>}</php>/>
							</label>
						</span>
					</td>
				</tr>
				<tr>
				 	<th width="160">superpay商户号：</th>
					<td>
						<input type="text" id="superpay_mchid" class="input-text" name="superpay_mchid"  value="{pigcms{$mer_pr['superpay_mchid']}" size="30" tips="Superpay 平台分配的mch_id" />
					</td>
				</tr>
				<tr>
				 	<th width="160">superpay签名秘钥：</th>
					<td>
						<input type="text" id="superpay_key" class="input-text" name="superpay_key"  value="{pigcms{$mer_pr['superpay_key']}" size="30" tips="Superpay 平台分配的key" />
					</td>
				</tr>
				<tr>
				 	<th width="160">superpay receive_id：</th>
					<td>
						<input type="text" id="superpay_receive_id" class="input-text" name="superpay_receive_id"  value="{pigcms{$mer_pr['superpay_receive_id']}" size="30" tips="Superpay 平台分配的receive_id" />
					</td>
				</tr>
				<tr>
				 	<th width="160">superpay店铺名称：</th>
					<td>
						<input type="text" id="superpay_storename" class="input-text" name="superpay_storename"  value="{pigcms{$mer_pr['superpay_storename']}" size="30" tips="Superpay 平台分配的store_name" />
					</td>
				</tr>
		   	</tbody>
		</table>
		
		<div class="btn ">
			<input  type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		</div>
	</form>

	<script>
		$(function(){
			
			$('a[data-toggle="tab"]').click(function(){
				layer.load(1, {
					shade: [0.1,'#fff']
				});
				var href = $(this).attr('href');
				setTimeout(function(){
					location.href = href;
				},100);
				return false;
			});

			$('.table_form:eq(0)').show();
			
			var pay_rate = $('.pay_rate');
			var open_pay_percent=$('.pay_percent').find('input').val();
			
	
			$('.pay_percent').find('input').blur(function(){
				open_pay_percent = $(this).val();
			});
			
			$('input[name="open_pay_rate"]').click(function(){
				if($(this).val()==1){
					pay_rate.show();
					if(open_pay_percent<0){
						$('.pay_percent').find('input').val('');
					}else{
						$('.pay_percent').find('input').val(open_scan_percent);
					}
				}else{
					pay_rate.hide();
					$('.pay_percent').find('input').attr('value',-1);
				}
			});
		});
	</script>
<include file="Public:footer"/>
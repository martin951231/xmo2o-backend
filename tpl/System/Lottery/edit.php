<include file="Public:header"/>
	<style>.frame_form td{vertical-align:middle;}
		select{width:80px;}
		textarea{width:300px;height:80px;}
		.textIamge{
			background-image:none!important;
		}
		
		.mini_img{
			width:60px;
			height:30px;
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Lottery/edit')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<input type="hidden" name="id" value="{pigcms{$lottery.id}">
			<tr style="display: none;">
				<td width="100">{pigcms{$config.shop_alias_name}详情顶部文字</td>
				<td>
				{pigcms{$lottery.detail_msg}
				</td>
			</tr>
			<tr>
				<td width="100">抽奖页自定义标题</td>
				<td>
				{pigcms{$lottery.lottery_msg}
				</td>
			</tr>
			<tr>
				<td width="100">抽奖规则</td>
				<td>
				{pigcms{$lottery.lottery_rule}
				</td>
				
			</tr>
			<tr>
				<td width="100">活动时间</td>
				<td>
					<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="<if condition='$lottery["start_time"]'>{pigcms{$lottery.start_time|date="Y-m-d H:i",###} </if> " onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>-
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="<if condition='$lottery["end_time"]'>{pigcms{$lottery.end_time|date="Y-m-d H:i",###}</if> " onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"/>
				</td>
				
			</tr>
			<tr>
				<td width="100">活动总金额</td>
				<td>
					<input type="text" class="input fl" name="total_money" value="{pigcms{$lottery.total_money}">
				</td>
			</tr>
			
			
			
		
			<tr class="wx_lottery">
				<td colspan="3">
				
				<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
					<tr>
						<td colspan="2" style="text-align:center;font-weight: bold;">
							系统抽奖配置
						</td>
					<tr>
					<if condition="$lottery.sys_content neq ''">
						
						<volist name="lottery.sys_content" id="vv">
							<tr class="plus textIamge" >
								<td><label>{pigcms{$i}</label></td>
								<td>
									<table style="width:100%;">
										<if condition="$i eq 1">
										<tr>
											<td width="20">奖品类型</td>
											<td width="20">抽奖比例</td>
											<td width="36">图片<font color="#ada9a9">(可传,不传只显示文字,红包默认有图片)</font></td>
											<td width="36">广告图片<font color="red">(选择广告时,填写)</font></td>
											<td width="36">广告链接<font color="red">(选择广告时,填写)</font></td>
											<td width="30">标题<font color="#ada9a9">(不要超过五个字)</font></td>
											<td width="35">概率值</td>
											<td width="35">概率</td>
										</tr>
										</if>
										<tr class="textIamge">
											<td width="20">
												<select name="type[]">
													<option value="0" <if condition="$vv.type eq 0">selected="selected"</if>>红包</option>
													<option value="1" <if condition="$vv.type eq 1">selected="selected"</if>>优惠券</option>
													<option value="2" <if condition="$vv.type eq 2">selected="selected"</if>>广告</option>
												</select>
											</td>
											<td width="150"  ><input type="text"  name="redpack_percent[]" class="input" value="{pigcms{$vv.redpack_percent}"></td>
											<td width="150"  ><input type="text"  name="image_url[]" class="input input-image" value="{pigcms{$vv.image_url}"  readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a></td>
											<td width="150"  >

												<input type="text" name="advertisement[]" class="input input-images" value="{pigcms{$vv.advertisement}"  readonly>&nbsp;&nbsp;<a href="javascript:void(0)" <if condition="$vv.type neq 2">style="display:none;background: #87b87f!important;border-color: #87b87f;color:#fff;"<else/>style="background: #87b87f!important;border-color: #87b87f;color:#fff;"</if> class="adv btn btn-sm btn-success J_selectImages">上传广告图片</a>
											</td>
											<td width="40">
												<input type="text"   class="input" name="link[]" id="urls{pigcms{$i}" value="{pigcms{$vv.link}" <if condition="$vv.type neq 2">readonly</if> />
												<a href="#modal-table" <if condition="$vv.type neq 2">style="display:none"</if> class="addLinks" class="btn btn-sm btn-success" onclick="addLinks('urls{pigcms{$i}',0)" data-toggle="modal">选功能库</a>
											</td>
											<td width="40">
												<input type="text"   class="input" name="title[]" id="url{pigcms{$i}" value="{pigcms{$vv.title}" <if condition="$vv.type eq 1">readonly</if> />
												<input type="hidden" name="coupon_id[]" id="url{pigcms{$i}_code" value="{pigcms{$vv.coupon_id}" />
												<a href="#modal-table" <if condition="$vv.type neq 1">style="display:none"</if> class="addLink" class="btn btn-sm btn-success" onclick="addLink('url{pigcms{$i}',0)" data-toggle="modal">选优惠券</a>

											</td>
											<td width="20"><input type="text" style="width:30px;" class="input" name="probability[]" value="{pigcms{$vv.probability}"></td>
											<td width="20">中奖概率{pigcms{$vv['probability']/$lottery['probability_all']*100|round=###,2}%</td>
										<tr/>
									</table>
								</td>
							</tr>
						</volist>
					<else />
						<for start="1" end="5">
							<tr class="splus textIamge" >
								<td><label>{pigcms{$i}</label></td>
								<td>
									<table style="width:100%;">
										<if condition="$i eq 1">
										<tr>
											<td width="20">奖品类型</td>
											<td width="20">抽奖比例</td>
											<td width="180">图片<font color="#ada9a9">(可传,不传只显示文字,红包默认有图片)</font></td>
											<td width="180">广告图片<font color="red">(选择广告时,填写)</font></td>
											<td width="180">广告链接<font color="red">(选择广告时,填写)</font></td>
											<td width="60">标题<font color="#ada9a9">(不要超过五个字)</font></td>
											<td width="30">概率值</td>
										</tr>
										</if>
										<tr class="textIamge">
											<td width="20">
												<select name="type[]">
													<option value="0" selected="selected">红包</option>
													<option value="1">优惠券</option>
													<option value="2">广告</option>
												</select>
											</td>
											<td width="150"  ><input type="text"  name="redpack_percent[]" class="input" value="{pigcms{$vv.redpack_percent}"></td>
											<td width="150"  >
												<input type="text"  name="image_url[]" class="input input-image" value=""   readonly>&nbsp;&nbsp;
												<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
											</td>

											<td width="150">
												<input type="text"  name="advertisement[]" class=" input input-images" value=""   readonly>&nbsp;&nbsp;
												<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImages adv"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;display:none">上传广告图片</a>
											</td>
											<td width="40">
												<input type="text"   class="input" name="link[]" id="urls{pigcms{$i}" readonly value="" />

												<a href="#modal-table" style="display:none" class="addLinks" class="btn btn-sm btn-success" onclick="addLinks('urls{pigcms{$i}',0)" data-toggle="modal">选功能库连接</a>
											</td>
											<td width="40">
												<input type="text"   class="input" name="title[]" id="url{pigcms{$i}" value="{pigcms{$vo.title}" />
												<input type="hidden" name="coupon_id[]" id="url{pigcms{$i}_code" value="{pigcms{$vo.coupon_id}" />
												<a href="#modal-table" style="display:none" class="addLink" class="btn btn-sm btn-success" onclick="addLink('url{pigcms{$i}',0)" data-toggle="modal">选优惠券</a>

											</td>
											<td width="20"><input type="text" style="width:30px;" class="input" name="probability[]" value="0"></td>
										
										</tr>
									</table>
								</td>
							</tr>
						</for>
					
					</if>
				<tr>
					<td colspan="2"  style="text-align:center;font-weight: bold;">
						商家抽奖配置信息
					</td>
				<tr>
					<volist name="lottery.content" id="vo">
					<tr class="plus textIamge" >
						<td width="5" ><label>{pigcms{$i}</label></td>
						<td>
							<table style="width:100%;">
								<if condition="$i eq 1">
									<tr>
										<td width="20">是否中奖</td>
										<td width="20">奖品类型</td>
										<td width="36">图片</td>
										<td width="36">广告图片</td>
										<td width="36">广告链接</td>
										<td width="30">标题<font color="#ada9a9">(不要超过五个字)</font></td>
										<td width="35">概率值</td>
										<td width="35">概率</td>
										
									</tr>
								</if>
								<tr class="textIamge">
									<td width="20">
										 <if condition="$vo.is_win eq 0">否<else />是</if>
									
									</td>
									<td width="20">
										 <if condition="$vo.type eq 0">
											 商家优惠券
										<elseif condition="$vo.type eq 2"/>
											 广告
										<else/>
											 自定义
										 </if>
									
									</td>
									<td width="60"><if condition="$vo.image_url neq ''"><img class="mini_img" src="{pigcms{$vo.image_url}"><else />无</if></td>
									<td width="60"><if condition="$vo.advertisement neq ''"><img class="mini_img" src="{pigcms{$vo.advertisement}"><else />无</if></td>
									<td width="50">
										<if condition="$vo.link neq ''"><a target="_blank" href="{pigcms{$vo.link}">链接</a><else/>无</if>

									</td>
									<td width="50">
										<if condition="$vo.title neq ''">{pigcms{$vo.title}<else/>无</if>
									</td>

									<td width="80">{pigcms{$vo.probability}</td>
									<td width="30">中奖概率{pigcms{$vo['probability']/$lottery['probability_all']*100|round=###,2}%</td>
								</tr>
								
							</table>
						</td>
					</tr>
					</volist>
				</table>
				</td>
			</tr>
			
			<tr>
				<td width="100">状态</td>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$lottery['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$lottery['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$lottery['status'] eq 2">selected</if>"><span>关闭</span><input type="radio" name="status" value="2" <if condition="$lottery['status'] eq 2">checked="checked"</if>/></label></span>
				</td>
			</tr>
			
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
		
	</form>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		$('select[name="type[]"]').change(function(){
			if ($(this).val() == '0') {
				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').val('').attr('readonly',false);
				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').css('background','#fff');
			}else{

				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').val('无需设置').attr('readonly',true).css('background','#dcdcdc');
				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').css('background','#dcdcdc');
			}
		})
		$.each($('select[name="type[]"]'),function(){
			if ($(this).val() == '0') {
				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').attr('readonly',false);
			}else{
				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').val('无需设置').attr('readonly',true).css('background','#dcdcdc');
				$(this).parent('td').siblings('td').find('input[name="redpack_percent[]"]').css('background','#dcdcdc');
			}
		})
		KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.J_selectImage').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
				editor.loadPlugin('image', function(){
					editor.plugin.imageDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-image').val(site_url+url);
							editor.hideDialog();
						}
					});
				});
			});

		});
		KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.J_selectImages').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
				editor.loadPlugin('image', function(){
					editor.plugin.imageDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-images').val(site_url+url);
							editor.hideDialog();
						}
					});
				});
			});

		});
			
			$(document).ready(function() {
				$('select[name="type[]"]').change(function(event) {
					var lottery_type = $(this).val();
					console.log(lottery_type);
					var addLink  = $(this).parent().parent().find('.addLink');
					var addLinks  = $(this).parent().parent().find('.addLinks');
					var adv  = $(this).parent().parent().find('.adv');
					var advertisement  = $(this).parent().parent().find('input[name="advertisement[]"]');
					var title  = $(this).parent().parent().find('input[name="title[]"]');
					
					if(lottery_type==1){
						addLink.show();
						addLinks.hide();
						adv.hide();
						title.val('');
						advertisement.val('');
						title.attr('readonly',true);
					}else if(lottery_type==2){
						addLink.hide();
						addLinks.show();
						adv.show();
						title.val('');
						title.attr('readonly',false);
					}else if(lottery_type==0){
						addLink.hide();
						addLinks.hide();
						adv.hide();
						title.val('红包');
						advertisement.val('');
						title.attr('readonly',false);
					}
				});		
			});
		
		function plus(){
			var item = $('.splus:last');
			var newitem = $(item).clone(true);
			var No = parseInt(item.find("label").html())+1;
			$('.delete').children().show();
			if(No>4){
				alert('不能超过4条信息');
			}else{
				$(item).after(newitem);
				newitem.find('input').attr('value','');
				newitem.find('textarea').attr('value','');
				newitem.find("#addLink").attr('onclick',"addLink('url"+No+"',0)");
				newitem.find("label").html(No);
				newitem.find('input[name="url[]"]').attr('id','url'+No);
				newitem.find('.delete').children().show();
			}
		}
		function del(obj){
			if($('.splus').length<=1){
				$('.delete').children().hide();
			}else{
				if($('.splus').length==2){
					$('.delete').children().hide();
				}
				$(obj).parents('.splus').remove();
				$.each($('.splus'), function(index, val) {
					var No =index+1;
					$(val).find('label').html(No);
					$(val).find('input[name="url[]"]').attr('id','url'+No);
					$(val).find("#addLink").attr('onclick',"addLink('url"+No+"',0)");
				});
			}
		}
		function addLink(domid,iskeyword){
			art.dialog.data('domid', domid);
			art.dialog.open('?g=Admin&c=Link&a=Coupon&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
		}


		function addLinks(domid, iskeyword) {
			art.dialog.data('domid', domid);
				art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword=' + iskeyword, {
					lock: true,
					title: '插入链接或关键词',
					width: 800,
					height: 500,
					yesText: '关闭',
					background: '#000',
					opacity: 0.45
				});
		}
	</script>

	
<include file="Public:footer"/>


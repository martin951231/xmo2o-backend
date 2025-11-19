<include file="Public:header"/>
<style type="text/css">
	input:disabled{
		background:#eaeaea !important;
	}
	.jifen_box,.coupon_box{
		display:none;
	}
</style>
	<form id="myform" method="post" action="{pigcms{:U('Config/sign_setting')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="id" value="{pigcms{$info.id}"/>
			<tr>
				<th width="80">添加天数</th>
				<td><input type="text" class="input fl" name="day" value="第{pigcms{$info.day}天" disabled="disabled" size="20" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr>
				<th width="80">赠送类型</th>
				<td class="radio_box">
					<label style="float:left;width:80px" class="checkbox_status">
						<input type="radio" class="input_radio" name="type" value="1" validate=" maxlength:1" <neq name="is_edit" value='1'>disabled='disabled'</neq> <eq name="info.type" value='1'>checked="checked"</eq> /> 积分
					</label>
					<label style="float:left;width:80px" class="checkbox_status">
						<input type="radio" class="input_radio" name="type" value="2" <neq name="is_edit" value='1'>disabled='disabled'</neq> <eq name="info.type" value='2'>checked="checked"</eq> />平台优惠券
					</label>		
				</td>
			</tr>
			<tr class="jifen_box">
				<th width="80">平台积分值</th>
				<td><input type="text" class="input fl" name="reward_info[jifen]" size="10" value="{pigcms{$info.reward_info.jifen}" tips="" <neq name="is_edit" value='1'>disabled='disabled'</neq> /></td>
			</tr>
			<tr class="coupon_box">
				<th width="80">选择平台优惠券</th>
				<td>
					<button type="button" <eq name="is_edit" value='1'>onclick="addCoupon()"</eq> class="a_choose class_1">添加平台优惠券</button>&nbsp;&nbsp;
					<div class="tags class_1" id="tags" tabindex="1"> 
						<span class="tag">
							<notempty name='info.show_name'>
								{pigcms{$info.show_name}
								<eq name="is_edit" value='1'>
									&nbsp<button class='close' data-coupon_id='{pigcms{$info.reward_info.coupon_id}' type='button'>×</button>
								</eq>
							</notempty>
						</span>
						<input type="hidden" class="px" id="coupon_id" value="{pigcms{$info.reward_info.coupon_id}" name="reward_info[coupon_id]" style="width:300px">
					</div>
				</td>
			</tr>			
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<link rel="stylesheet" href="http://www.group.com/static/js/artdialog/skins/mydialog.css?4.1.7">
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$(function(){
	//去除选中的卡券
	$('.tag').on('click','.close',function(){
		$('#coupon_id').val('');
		$('.tag').html('');
		$('#deliver_type').show();
	});
	var type={pigcms{$info.type|default=1};
	if(type==1){
		$('.jifen_box').show();
		$('.jifen_box').find('input').attr('validate',"required:true,number:true,maxlength:6");
		$('.coupon_box').hide();
	}else{
		$('.jifen_box').hide();
		$('.jifen_box').find('input').attr('validate',"");
		$('.coupon_box').show();
	}
	//选择签到奖励类型
	$('input[name=type]').click(function(){
		var type=$(this).val();
		if(type==1){
			$('.jifen_box').show();
			$('.jifen_box').find('input').attr('validate',"required:true,number:true,maxlength:6");
			$('.coupon_box').hide();
		}else{
			$('.jifen_box').hide();
			$('.jifen_box').find('input').attr('validate',"");
			$('.coupon_box').show();
		}
	});
});
</script>
<script type="text/javascript">
	function addCoupon(){
		art.dialog.data('titledom', 'titledom');
		art.dialog.data('imgids', 'imgids');
		art.dialog.data('multinews', 'multinews');
		art.dialog.data('singlenews', 'singlenews');
		art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
		art.dialog.data('multione', 'multione');
		art.dialog.open('?g=System&c=Coupon&a=select_sign_coupon&type=sign',{lock:true,title:'选择平台优惠券',width:900,height:600,yesText:'关闭',background: '#000',opacity: 0.45});
	}
</script>
<include file="Public:footer"/>
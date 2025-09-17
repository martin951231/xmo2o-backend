<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Partition_coupon/add')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">	
			<tr>
				<td width="100">瓜分券名称：</td>
				<td>
					<input type="text" style="width:300px;" class="input fl" name="name" value=""  validate="required:true" autocomplete="off" maxlength="9" />
				</td>
				<td>上限9个汉字长度</td>
			</tr>
			<tr>
				<td width="100">瓜分券数量：</td>
				<td><input type="text" class="input fl" name="num" value=""  validate="required:true,number:true,min:1" autocomplete="off" /></td>		
			</tr>
			
			<tr>
				<td width="100">瓜分券活动时间：</td>
				<td colspan="2">
					<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="开始时间"/>-
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})"  placeholder="结束时间" validate="required:true"/>
				</td>
			</tr>

			<tr>
				<td width="100">瓜分券有效期：</td>
				<td colspan="2">
					<select name='day'>
						<for start='0' end='16'>
							<option value="{pigcms{$i}" <eq name='info.day' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
						</for>
					</select>
					<span>天</span>
					<select name='hour'>
						<for start='0' end='24'>
							<option value="{pigcms{$i}" <eq name='info.hour' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
						</for>
					</select>
					<span>时</span>
					<select name='minute'>
						<for start='0' end='60'>
							<option value="{pigcms{$i}" <eq name='info.minute' value="$i">selected='selected'</eq> >{pigcms{$i}</option>
						</for>
					</select>
					<span>分</span>
				</td>
			</tr>

			<tr>
				<td width="100">瓜分方式：</td>
				<td colspan="2">
					<label><input type="radio" name="type" value="0" checked="checked"/><span>好友均分（每人领取金额相同）</span></label><br/><br/>
					<label><input type="radio" name="type" value="1"/><span>拼手气（每人领取金额随机）</span></label>
				</td>
			</tr>
			
			
			<tr class="rand_send1">
				<td width="100">瓜分内容：</td>
				<td>
					<div style="margin-bottom: 10px;"><input type="text" class="input fl" name="user_num" value="" validate="required:true,number:true,min:1" autocomplete="off" />名好友，</div>
					瓜分<input type="text" class="input" name="money" value=""  validate="required:true,number:true,min:0.01" autocomplete="off" />元
				</td>
			</tr>
			
			<tr class="">
				<td width="100" >可用券时间</td>
				<td >
					<label>
						<input type="radio" name="time_type" value="0" checked="checked" />
						<input type="text" class="input-text" name="use_start_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="开始时间" />-
						<input type="text" class="input-text" name="use_end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="结束时间"/>
					</label><br/><br/>
					<label><input type="radio" name="time_type" value="1"/>成功瓜分当日起<input type="text" class="input" name="use_time" value="" autocomplete="off" />天内可用</label>
				</td>
			</tr>
			<tr class="">
				<td width="100" >用券门槛</td>
				<td >
					<label><input type="radio" name="use_condition" value="0" checked="checked" />无使用门槛</label><br/><br/>
					<label><input type="radio" name="use_condition" value="1" />
					订单满<input type="text" class="input" name="order_money" value="" autocomplete="off"/>元</label>
				</td>
			</tr>
			
			<tr>
				<td width="100">每人限瓜分次数：</td>
				<td colspan="2">
					<label>	<input type="radio" name="partition_times_type" value="0" checked="checked"/>不限次数</label><br/><br/>
					<label>	
						<input type="radio" name="partition_times_type" value="1" />
						<input type="text" class="input" name="partition_times" autocomplete="off"/>次
					</label>
				</td>
			</tr>
			<tr>
				<th width="15%">分享图片</th>
                <td>
                	<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</a>&nbsp;&nbsp;推荐尺寸500*400
	                <input type="hidden" class="input-image" name="img_url" id="pic_list" value="{pigcms{$detail['img_url']}">
	                <ul class="image_product_list" id="avatar_detail">

	                </ul>
	                <ul class="avatar_list" id="avatar_show" >
	                </ul>
                </td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
			var editor = K.editor({
				allowFileManager : true
			});
            $('.J_selectImage').click(function(){
                var upload_file_btn = $(this);
                editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=partition_coupon";
                editor.loadPlugin('image', function(){
                    editor.plugin.imageDialog({
                        showRemote : false,
                        clickFn : function(url, title, width, height, border, align) {
                            var avatar_detail_length = $('#avatar_detail').find('li').length;
                            var li_length = upload_file_btn.siblings('.avatar_list').find('li').length+avatar_detail_length;
                                $('#avatar_show').show();
                              
                                upload_file_btn.siblings('.input-image').val(url);
                                
                                var html='<li class="upload_pic_li" style="float:left;list-style:none;"><img src="'+url+'" style="width:250px;height:200px;margin-right:5px;"><br></li>';
                                upload_file_btn.siblings('.avatar_list').html(html)
                                editor.hideDialog();
                                

                        }
                    });
                });
            });
        })
		
	</script>
<include file="Public:footer"/>


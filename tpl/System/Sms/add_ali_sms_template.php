<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_ali_sms_template')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="id" value="{pigcms{$_GET['id']}"/>
			<tr>
				<th width="80">模板名称</th>
				<td><input type="text" class="input fl" name="name" id="name" size="35" value="{pigcms{$sms.name}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			<tr>
				<th width="80">简易模板内容</th>
				<td>{pigcms{$sms.simple_content|default='只有编辑时能查看，此项不能填写。'}</td>
			</tr>
			<if condition="isset($config['open_aliyun_sms']) || isset($config['open_ali_national_sms'])">
				<tr>
					<th colspan="2">==============================================================================================</th>
				</tr>
				<tr>
					<th width="80">阿里云模板CODE</th>
					<td><input type="text" class="input fl" name="templateCode" id="templateCode" size="50" value="{pigcms{$sms.templateCode}" placeholder="" validate="maxlength:40" tips=""/></td>
				</tr>
				<tr>
					<th width="80">阿里云模板内容</th>
					<td>
						<textarea name="content" rows="3" cols="60">{pigcms{$sms.content}</textarea>			
					</td>
				</tr>
			</if>
			<if condition="isset($config['open_huaweiyun_sms'])">
				<tr>
					<th colspan="2">==============================================================================================</th>
				</tr>
				<tr>
					<th width="80">华为云模板CODE</th>
					<td><input type="text" class="input fl" name="huawei_templateCode" id="huawei_templateCode" size="50" value="{pigcms{$sms.huawei_templateCode}" placeholder="" validate="maxlength:40" tips=""/></td>
				</tr>
				<tr>
					<th width="80">华为云模板内容</th>
					<td>
						<textarea name="huawei_content" rows="3" cols="60">{pigcms{$sms.huawei_content}</textarea>
						<br/>
						<p><a href="https://support.huaweicloud.com/msgsms_faq/sms_faq_0003.html" target="_blank" style="color:blue;">华为云模板格式规范</a>。${TXT_数字}，${NUM_数字}，${DATE}，${TIME}</p>
					</td>
				</tr>
			</if>
			<tr>
				<th colspan="2">==============================================================================================</th>
			</tr>
			<tr>
				<th width="80">模板状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$sms.status eq 1 OR empty($sms)">selected</if>"><span>正常</span><input type="radio" name="status" value="1" <if condition="$sms.status eq 1 OR empty($sms)">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$sms.status eq 0 AND isset($sms['status'])">selected</if>"><span>禁止</span><input type="radio" name="status" value="0" <if condition="$sms.status eq 0 AND isset($sms['status'])">checked="checked"</if>/></label></span>
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
			
		
	</script>
	
	
<include file="Public:footer"/>
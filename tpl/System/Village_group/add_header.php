<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_header')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">团长名称</th>
                <td><input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$group.name}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
            </tr>
            <if condition="$area_level">
                <tr>
                    <th width="80">团长当前等级</th>
                    <td><if condition="$area_level['lname']">{pigcms{$area_level.lname}<else/>{pigcms{$area_level.level}</if></td>
                </tr>
                <tr>
                    <th width="80">享受等级佣金</th>
                    <td>{pigcms{$area_level['leader_money']}%</td>
                </tr>
            </if>
			<tr>
				<th width="80">手机号码</th>
				<td><input type="text" class="input fl" name="phone" id="phone" size="25" value="{pigcms{$group.phone}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			
			<tr>
				<th width="80">社区</th>
				<td>
				 <if condition="$group.pick_name neq ''">
					{pigcms{$group.pick_name}
				 <else />
					<input type="text" class="input fl" name="home" id="home" size="25" value="{pigcms{$group.home}" placeholder="" validate="maxlength:20,required:true" tips=""/>
				</if>
				
				</td>
			</tr>
			
			<tr>
				<th width="80">地址</th>
				<td>
				<if condition="$group.pick_name neq ''">
					{pigcms{$group.pick_addr}
				<else />
					<input type="text" class="input fl" name="address" id="address" size="25" value="{pigcms{$group.address}" placeholder="" validate="maxlength:20,required:true" tips="" /></td>
				</if>
			</tr>
			
			
		
			
			<tr>
				<th width="80">微信号</th>
				<td><input type="text" class="input fl" name="wx_number" id="wx_number" size="25" value="{pigcms{$group.wx_number}" placeholder="" validate="maxlength:20" tips=""/></td>
			</tr>
			
			<tr>
				<th width="80">提货时间段</th>
				<td><input type="text" class="input fl" name="start_time" size="25" value="<if condition="$group.start_time neq '00:00:00'">{pigcms{$group.start_time|default="08:00"}<else />08:00</if>" placeholder="开始时间" onfocus="WdatePicker({readOnly:true,dateFmt:'H:m'})"/>至<input type="text" class="input" name="end_time" size="25" value="<if condition="$group.end_time  neq '00:00:00'">{pigcms{$group.end_time|default="20:00"}<else />20:00</if>" placeholder="结束时间" onfocus="WdatePicker({readOnly:true,dateFmt:'H:m'})"/></td>
			</tr>
			
			<input type="hidden" name="id" id="id" value="{pigcms{$_GET.id}">
			<input type="hidden" name="pick_id" id="pick_id" value="{pigcms{$group.pick_id}">
			<tr>
				<th width="80">绑定用户(填写uid)</th>
				<td>
				<if condition="$group.uid gt 0">
				{pigcms{$group.uid}
				<input type="hidden" name="uid"  value="{pigcms{$group.uid}"/>
				<else />
				<input type="text" class="input fl" name="uid" id="uid" size="25" value="{pigcms{$group.uid}" placeholder="" validate="maxlength:20,required:true" tips="" />
				
				</if>
				</td>
			</tr>
			
			<tr>
				<th width="80">团长佣金比例</th>
				<td><input type="text" class="input fl" name="money" id="money" size="25" value="{pigcms{$group.money}" placeholder="" validate="maxlength:20" tips="每一单团长所得佣金比例，请填写0-100的数字，如10代表10%。不填写代表佣金设置比例为0%"/></td>
			</tr>
		
			
			<tr>
				<th width="80">团长状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$group.status eq 1 OR empty($group)">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$group.status eq 1 OR empty($group)">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$group['status'] eq 0 AND isset($group['status'])">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$group['status'] eq 0 AND isset($group['status'])">checked="checked"</if>/></label></span> 
                </td>
			</tr>
            <tr>
                <th width="80">团长排序</th>
                <td><input type="text" class="input fl" name="sort" id="sort" value="{pigcms{$group.sort}" size="10" placeholder="团长排序" validate="maxlength:11,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
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
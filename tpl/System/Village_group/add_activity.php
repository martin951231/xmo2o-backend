<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_activity')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

			<tr>
				<th width="80">活动名称</th>
				<td><input type="text" class="input fl" name="activity_name" id="activity_name" size="50" value="{pigcms{$detail.activity_name}" placeholder="请填写活动名称"  tips="" validate="required:true"/></td>
			</tr>
            <tr >
                <th width="15%">所在区域</th>
                <td id="choose_cityarea" province_id="{pigcms{$detail.province_id}" city_id="{pigcms{$detail.city_id}" area_id="{pigcms{$detail.area_id}" circle_id="-1" circle="-1"></td>
            </tr>
			<tr>
				<th width="80">活动副标题</th>
				<td><input type="text" class="input fl" name="sub_name" id="sub_name" size="50" value="{pigcms{$detail.sub_name}" placeholder="请填写活动副标题"  tips="" /></td>
			</tr>
			<tr>
				<th width="80">活动类别</th>
				<td>
					<if condition="$edit_this">
					<select name="type" id="type">
                        <option value="1" <if condition="$detail['type'] eq 1">selected="selected"</if>>每日必买</option>
                        <option value="2" <if condition="$detail['type'] eq 2">selected="selected"</if>>限时抢购</option>
                    </select>
                    <else />
                    <if condition="$detail['type'] eq 1">每日必买<else />限时抢购</if>
                	</if>
				</td>
			</tr>
			<tr id="top_activity" <if condition="$detail['type'] neq 2"><else/>style="display: none;"</if>>
				<th width="80">活动置顶</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$detail['is_top'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_top" value="1" <if condition="$detail['is_top'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$detail['is_top'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_top" value="0" <if condition="$detail['is_top'] eq 0">checked="checked"</if> /></label></span>
					<em class="notice_tips" tips="置顶后，本活动将会在社区拼团首页展示"></em>
				</td>
			</tr>
			<tr>
				<th width="80">活动状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$detail['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$detail['status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$detail['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$detail['status'] eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>
			<tr>
				<th width="80">活动开始时间</th>
				<td>
					<if condition="$edit_this">
					<input type="text" class="input fl" name="start_time" size="25" placeholder="开始时间" value="<if condition='$detail.start_time'>{pigcms{$detail.start_time}</if>" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" validate="required:true"  tips="时间跨度需为同一天" />
					<else />
                    {pigcms{$detail.start_time}
                	</if>
				</td>
			</tr>
			<tr>
				<th width="80">活动结束时间</th>
				<td>
					<if condition="$edit_this">
					<input type="text" class="input fl" name="end_time" size="25" placeholder="结束时间" value="<if condition='$detail.end_time'>{pigcms{$detail.end_time}</if>" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" validate="required:true"  tips="时间跨度需为同一天" />
					<else />
                    {pigcms{$detail.end_time}
                	</if>
				</td>
			</tr>
			<tr class="daybuydiv" style="display:<if condition="$detail['type'] eq 2">none;</if>">
				<th width="80">活动图片</th>
				<td>
					<input type="text"  style="width:200px;" name="img" class="input input-image" value="{pigcms{$detail.img}"   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
					<p style="display:block;clear:both;padding-top:10px;">建议尺寸：640*240px</p>
				</td>
			</tr>
			<tr class="daybuydiv" style="display:<if condition="$detail['type'] eq 2">none;</if>">
				<th width="80">分享活动图片</th>
				<td>
					<input type="text"  style="width:200px;" name="share_img" class="input input-image" value="{pigcms{$detail.share_img}"   readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>
					<p style="display:block;clear:both;padding-top:10px;">建议尺寸：500*400px</p>
				</td>
			</tr>
			<tr class="daybuydiv" style="display:<if condition="$detail['type'] eq 2">none;</if>">
				<th width="80">分享活动标题</th>
				<td><input type="text" class="input fl" name="share_title" id="share_title" size="50" value="{pigcms{$detail.share_title}" placeholder="请填写分享活动标题"  tips="" /></td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="hidden" name="activity_id" value="{pigcms{$detail.activity_id}" />
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
								upload_file_btn.siblings('.input-image').val(url);
								editor.hideDialog();
							}
						});
					});
				});

			});

		$(function(){
			$('[name="type"]').bind("change", function(){
				if($(this).find(":selected").val() == '1'){
					$("#top_activity").show();
					$(".daybuydiv").show();
				}
				else{
					$("#top_activity").hide();	
					$(".daybuydiv").hide();
				}
			})
		})
	</script>
	
<include file="Public:footer"/>
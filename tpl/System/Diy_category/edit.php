<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pcap.js?13"></script>
	<form id="myform" method="post" action="{pigcms{:U('amend')}" enctype="multipart/form-data">
		<input type="hidden" name="pigcms_id" value="{pigcms{$now_group.pigcms_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">城市</th>
				<td>
					<div id="choose_pca" province_idss="{pigcms{$now_group.province_id}" city_idss="{pigcms{$now_group.city_id}"  style="display:inline"></div>
				</td>
			</tr>
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" size="20" validate="maxlength:20,required:true" tips="名称" value="{pigcms{$now_group.name}"/></td>
			</tr>
		
			<tr>
				<th width="80">开始时间</th>
				<td><input type="text" class="input fl" name="begin_time" style="width:120px;" id="d4311" validate="required:true" tips="活动开始时间，开始时间必须大于上次活动结束时间" value="{pigcms{$now_group.begin_time|date='Y-m-d H:i',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',maxDate:'#F{$dp.$D(\'d4312\')}'})"/></td>
			</tr>
			<tr>
				<th width="80">结束时间</th>
				<td><input type="text" class="input fl" name="end_time" style="width:120px;" id="d4312" validate="required:true" tips="活动结束时间" value="{pigcms{$now_group.end_time|date='Y-m-d H:i',###}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',minDate:'#F{$dp.$D(\'d4311\')}'})"/></td>
			</tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition='$now_group["status"] eq 1'>selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition='$now_group["status"] eq 1'>checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition='$now_group["status"] eq 0'>selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition='$now_group["status"] eq 0'>checked="checked"</if>/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="submit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
			<!--<a class="button" href="{pigcms{:U('index',['type'=>$now_group['type']])}">返回列表页</a>-->
		</div>
	</form>
<include file="Public:footer"/>
<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pcap.js?13"></script>
	<form id="myform" method="post" action="{pigcms{:U('modify')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">城市</th>
				<td>
					<div id="choose_pca" province_idss="{pigcms{$_GET.province_id}" city_idss="{pigcms{$_GET.city_id}"  style="display:inline"></div>
				</td>
			</tr>
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" size="20" validate="maxlength:20,required:true" tips="分类名称"/></td>
			</tr>
			
			<tr>
				<th width="80">开始时间</th>
				<td><input type="text" class="input fl" name="begin_time" style="width:120px;" id="d4311" validate="required:true" tips="活动开始时间，开始时间必须大于上次活动结束时间" onfocus="WdatePicker({isShowClear:false,readOnly:true,<if condition="$next_time">minDate:'{pigcms{$next_time|date='Y-m-d H:i',###}',</if>dateFmt:'yyyy-MM-dd HH:mm',maxDate:'#F{$dp.$D(\'d4312\')}'})"/></td>
			</tr>
			<tr>
				<th width="80">结束时间</th>
				<td><input type="text" class="input fl" name="end_time" style="width:120px;" id="d4312" validate="required:true" tips="活动结束时间" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm',minDate:'#F{$dp.$D(\'d4311\')}'})"/></td>
			</tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked"/></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0"/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="hidden" name="type" value="{pigcms{$type}"/>
			<input type="submit"  id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
			<!--<a class="button" href="{pigcms{:U('index',['type'=>$type])}">返回列表页</a>-->
		</div>
	</form>
<include file="Public:footer"/>
<include file="Public:header"/>
	<div class="mainbox">
		<form method="post"  id="myform" action="__SELF__" refresh="true" enctype="multipart/form-data" >
			<table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<input type="hidden" class="input-text" name="pick_id" value="{pigcms{$pick_addr.pick_id}" />
				<tr>
					<th width="100">自提点</th>
					<td>
						<fieldset id="choose_cityarea" province_id="{pigcms{$pick_addr.province_id}" city_id="{pigcms{$pick_addr.city_id}" area_id="{pigcms{$pick_addr.area_id}"></fieldset>
					</td>
				</tr>
				<tr>
					<th width="100">自提点经纬度</th>
					<td id="choose_map" default_long_lat="{pigcms{$pick_addr.long},{pigcms{$pick_addr.lat}"></td>
				</tr>

				<tr>
					<th width="100">详细地址</th>
					<td>
						<input type="text" class="input-text" name="pick_addr" value="{pigcms{$pick_addr.pick_addr}" />
					</td>
				</tr>
				<tr>
					<th width="100">电话号码</th>
					<td>
						<input type="text" class="input-text" name="phone" value="{pigcms{$pick_addr.phone}" />
					</td>
				</tr>
				<tr>
					<th width="100">状态</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$pick_addr['is_open'] eq '1'">selected</if>"><span>启用</span><input type="radio" name="is_open" value="1" <if condition="$pick_addr['is_open'] eq '1'"> checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$pick_addr['is_open'] eq '0'">selected</if>"><span>关闭</span><input type="radio" name="is_open" value="0" <if condition="$pick_addr['is_open'] eq '0'"> checked="checked"</if> /></label></span>
					</td>
				</tr>
			</table>
			<div class="btn hidden">
				<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
				<input type="reset" value="取消" class="button" />
			</div>
		</form>
	</div>
	<script type="text/javascript">
		var choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
	</script>
<include file="Public:footer"/>
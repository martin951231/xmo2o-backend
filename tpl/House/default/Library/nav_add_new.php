<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
	}
</style>
<if condition="$row_type neq single AND $yingshan AND $jsdhwy_new_wap_index lt 1">
	<div style="margin-left: 10px;color: #9a9999">
		<span> 说明：</span>
		<p>1.角标最多只能输入四位</p>
		<p>2.角标只填写角标名称时，默认背景是红色</p>
	</div>
</if>
	<form id="myform" method="post" action="{pigcms{:U('Library/nav_modify_new')}" enctype="multipart/form-data">
		<input type="hidden" name="row_type" value="{pigcms{$row_type}"/>
        <input type="hidden"  name="block_type_id" value="{pigcms{$block_type_id}"/>
        <input type="hidden"  name="is_community_platform" value="{pigcms{$is_community_platform}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr>
				<th width="80">图标</th>
				<td><input type="file" class="input fl" name="img" style="width:200px;height:35px;" placeholder="请上传图片" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="80">链接</th>
				<td>
					<input type="text" class="input fl" name="url" id="url" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:2000,required:true"/>
					<?php if(!C('butt_open')){ ?>
						<if condition="$now_category['cat_type'] neq '1'">
							<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 0)" data-toggle="modal">从功能库选择</a>
						<else />
							<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 1)" data-toggle="modal">从功能库选择</a>
						</if>
					<?php } ?>
				</td>
			</tr>
            <tr class="wxapp_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="wxapp_id" id="wxapp_id" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}">{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr style="display: none;" class="wxapp_open_wxapp wxapp_page">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value=""  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="0" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">是否开启</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>

			<if condition="$row_type neq single AND $yingshan AND $jsdhwy_new_wap_index lt 1">
				<tr>
					<th width="80">角标名称</th>
					<td><input type="text" class="input fl" name="mark_name" onblur="if(value.length>4) value=value.slice(0,4)" size="20" placeholder="请输入名称"/></td>
				</tr>

				<tr>
					<th width="80">角标背景颜色</th>
					<td>
						<input type="text" readonly class="input fl" name="mark_bg" id="mark_bg" style="width:120px;height:35px;" placeholder="不可填写" />&nbsp;&nbsp;
						<a href="javascript:void(0);" id="mark_bg_color" style="line-height:28px;">点击选择颜色</a>
					</td>
				</tr>
			</if>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>



<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<script>

    $("#yes").click(function () {
        $("#adver_region").hide();
    })

    $("#no").click(function () {
        $("#adver_region").show();
    })

    $('#mark_bg_color').click(function(){
      window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'mark_bg',function(color){
        $("#mark_bg").val(color);
        var cssText = $("#mark_bg").attr("style") + ";background-color:"+ color +" !important;";
        $("#mark_bg").css("cssText",cssText);
      })
    });

$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})
//	var admin_area_id = {pigcms{$system_session.area_id};
//	if(admin_area_id > 0){
//		$("#adver_currency").hide();
//		$("#no").trigger('click');
//	}
</script>
<include file="Publics:footer"/>
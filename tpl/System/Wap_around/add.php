<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Wap_around/modify')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">附近模块分类</th>
				<td>
					<select name="name">
						<option value="group">{pigcms{$config.group_alias_name}</option>
						<option value="shop">{pigcms{$config.shop_alias_name}</option>
						<option value="meal">{pigcms{$config.meal_alias_name}</option>
						<option value="appoint">{pigcms{$config.appoint_alias_name}</option>
						<option value="merchant">商家</option>
					</select>
				</td>
			</tr>
			<if condition="$many_city eq 1">
				<tr id="adver_currency">
					<th width="15%">通用广告</th>
					<td width="35%" class="radio_box">
						<span class="cb-enable"><label class="cb-enable selected"><span>通用</span><input id="yes" type="radio" name="currency" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>不通用</span><input id="no" type="radio" name="currency" value="2" /></label></span>
					</td>
				</tr>
				<tr id="adver_region" style="display:none;">
					<th width="15%">所在区域</th>
					<td width="85%" colspan="3" id="choose_cityareass" province_idss="" city_idss=""></td>
				</tr>
			</if>
			<tr>
				<th width="80">模块描述</th>
				<td><input type="text" class="input fl" name="des" style="width:80px;" value="" validate="maxlength:10,required:true"/></td>
			</tr>
			<tr>
				<th width="80">模块图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:180px;" placeholder="请上传图片" tips="可不上传"/></td>
			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
				<input type="text" class="input fl" name="url" id="url" style="width:180px;" placeholder="请填写链接地址" validate="maxlength:200,required:true,url:true"/>
				<if condition="$now_category['cat_type'] neq 1">
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
				<else />
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 1)" data-toggle="modal">从功能库选择</a>
				</if>
				</td> 
			</tr>
			<tr>
				<th width="80">模块排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="0" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">模块状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
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
<script>
function addLink(domid,iskeyword, type){
	art.dialog.data('domid', domid);
	if (type == 1) {
		art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	} else {
		art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	}
}
$(function(){
	$("#yes").click(function () {
        $("#adver_region").hide();
    })

    $("#no").click(function () {
        $("#adver_region").show();
    })
	
	var admin_area_id = {pigcms{$system_session.area_id};
	if(admin_area_id > 0){
		$("#adver_currency").hide();
		$("#no").trigger('click');
	}
})
</script>
<include file="Public:footer"/>
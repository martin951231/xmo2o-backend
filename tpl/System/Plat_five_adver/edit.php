<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pcap.js?13"></script>
	<form id="myform" method="post" action="{pigcms{:U('amend')}" enctype="multipart/form-data">
		<input type="hidden" name="pigcms_id" value="{pigcms{$now_group.pigcms_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <if condition="$is_area_many_city neq 1 ">
                <tr id="adver_currency">
                    <th width="15%">通用广告</th>
                    <td width="35%" class="radio_box">
                        <span class="cb-enable"><label class="cb-enable <if condition="$now_group['city_id'] eq 0">selected</if>"><span>通用</span><input id="yes" type="radio" name="currency" value="1" <if condition="$now_group['city_id'] eq 0">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$now_group['city_id'] neq 0">selected</if>"><span>不通用</span><input id="no" type="radio" name="currency" value="2" <if condition="$now_group['city_id'] neq 0">checked="checked"</if> /></label></span>
                        <if condition="C('customization.open_view_multi_city') eq 1">
                        <em for="currency" generated="true" class="error tips" style="position: relative;color: red;font-weight: bold;">选择不通用时请在列表页配置指定区域</em>
                        </if>
                    </td>
                </tr>
            <else/>
                <input id="no" type="hidden" name="currency" value="2" checked="checked" />
            </if>
            <if condition="C('customization.open_view_multi_city') neq 1">
			<tr id="adver_region" <if condition="$now_group['city_id'] eq 0 or $is_area_many_city eq 1">style="display:none;"</if>>
				<th width="15%">城市</th>
				<td width="85%" colspan="3" id="choose_pca" province_idss="{pigcms{$now_group['province_id']}" city_idss="{pigcms{$now_group['city_id']}"></td>
			</tr>
            </if>
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" size="20" validate="maxlength:20,required:true" tips="活动名称" value="{pigcms{$now_group.name}"/></td>
			</tr>
			<tr>
				<th width="80">广告现图</th>
				<td><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$now_group.pic}" style="width:260px;height:80px;" class="view_msg"/></td>
			</tr>
			<tr>
				<th width="80">活动图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传图片" tips="建议尺寸为466*343（宽*高）"/></td>
			</tr>
			<tr>
				<th width="80">主图链接</th>
				<td>
					<input type="text" class="input fl" name="pic_link" id="pic_link" value="{pigcms{$now_group.pic_link}" size="50" validate="url:true" placeholder="点击主图跳转的链接" tips="点击主图跳转的链接"/>
					<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('pic_link', 0, 0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
			<tr>
				<th width="80">更多按钮的链接</th>
				<td>
					<input type="text" class="input fl" name="link" id="link" value="{pigcms{$now_group.link}" size="50" validate="url:true" placeholder="可以不填，则不显示" tips="跳转的链接"/>
					 <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('link', 0, 0)" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
			<tr>
				<th width="80">内容来源</th>
				<td>
					<select name="from" id="from" tips="" disabled="disabled">
	                   	<option value="1" <if condition='$now_group["from"] eq 1'>selected</if>>广告图</option>
	                   	<option value="0" <if condition='$now_group["from"] eq 0'>selected</if>>商品</option>            
					</select>
					<em>编辑时无法修改内容来源</em>
				</td>
			</tr>
            <tr>
                <th width="80">排序</th>
                <td>
                    <input type="text" class="input fl" name="sort" id="sort" value="{pigcms{$now_group.sort}" size="10" validate="sort:true" placeholder="0" tips="值越大越靠前"/>
                </td>
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
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script>
		var pageDomId = ''
		function addLink(domid, iskeyword, type){
			pageDomId = domid;
			art.dialog.data('domid', domid);
			parent.setLinkBases(window, 'platform', 'h5');
		}
		//链接库回调方法
		function getLinkBasesUrl(url){
			$('#' + pageDomId).val(url);
		}

		var admin_area_id = {pigcms{$system_session.area_id};
        var is_area_many_city = <?php echo $is_area_many_city ?>;
        if((admin_area_id > 0) && (is_area_many_city != 1)){
			$("#adver_currency").hide();
			$("#adver_region").show();
			$("#no").trigger('click');
		}

	    $("#yes").click(function () {
	        $("#adver_region").hide();
	    })

	    $("#no").click(function () {
            if(is_area_many_city==0){
                $("#adver_region").show();
            }
	    })
	</script>
<include file="Public:footer"/>
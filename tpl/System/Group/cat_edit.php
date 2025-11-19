<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Group/cat_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="cat_id" value="{pigcms{$now_category.cat_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">分类名称</th>
				<td><input type="text" class="input fl" name="cat_name" id="cat_name" value="{pigcms{$now_category.cat_name}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
            <tr>
                <th width="80">分类描述</th>
                <td><input type="text" class="input fl" name="cat_des" id="cat_des" maxlength="100" value="{pigcms{$now_category.cat_des}" size="25" placeholder=""  tips="用于描述分类的副标题，吸引客户,限制100字以内"/></td>
            </tr>
			<tr>
				<th width="80">短标记(url)</th>
				<td><input type="text" class="input fl" name="cat_url" id="cat_url" value="{pigcms{$now_category.cat_url}" size="25" placeholder="英文或数字" validate="maxlength:20,required:true,en_num:true" tips="只能使用英文或数字，用于网址（url）中的标记！建议使用分类的拼音"/></td>
			</tr>
				<if condition="!empty($now_category['cat_pic'])">
					<tr>
						<th width="80">分类现图</th>
						<td><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$now_category.cat_pic}" style="width:50px;height:50px;" class="view_msg"/></td>
					</tr>
				</if>
				<tr>
					<th width="80">分类LOGO图标</th>
					<td><input type="file" class="input fl" name="cat_pic" style="width:200px;" placeholder="分类LOGO图片" <if condition="empty($now_category['cat_pic'])">validate="required:true"</if> tips="分类LOGO小图标，建议尺寸100*100"/></td>
				</tr>

            <if condition="empty($now_category['cat_fid'])">
                <if condition="!empty($now_category['cat_ad_pic'])">
                    <tr>
                        <th width="80">分类广告现图</th>
                        <td><img src="{pigcms{:file_domain()}/upload/system/{pigcms{$now_category.cat_ad_pic}" style="width:50px;height:50px;" class="view_msg"/></td>
                    </tr>
                </if>
                <tr>
                    <th width="80">分类广告图</th>
                    <td><input type="file" class="input fl" name="cat_ad_pic" style="width:200px;" placeholder="分类广告图" tips="分类广告图，建议尺寸900*500"/></td>
                </tr>
            </if>

			<tr>
				<th width="80">分类排序</th>
				<td><input type="text" class="input fl" name="cat_sort" value="{pigcms{$now_category.cat_sort}" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
			</tr>
			<tr>
				<th width="80">是否热门</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_category['is_hot'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_hot" value="1" <if condition="$now_category['is_hot'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_category['is_hot'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_hot" value="0"  <if condition="$now_category['is_hot'] eq 0">checked="checked"</if> /></label></span>
					<em class="notice_tips" tips="如果选择热门，颜色会有变化"></em>
				</td>
			</tr>
			<tr>
				<th width="80">分类状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_category['cat_status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="cat_status" value="1"  <if condition="$now_category['cat_status'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_category['cat_status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="cat_status" value="0"  <if condition="$now_category['cat_status'] eq 0">checked="checked"</if> /></label></span>
				</td>
			</tr>
			<if condition="!$_GET['cat_fid']">
			<tr>
				<th width="80">是否是酒店</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_category['is_hotel'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_hotel" value="1" <if condition="$now_category['is_hotel'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_category['is_hotel'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_hotel" value="0"  <if condition="$now_category['is_hotel'] eq 0">checked="checked"</if> /></label></span>
					<em class="notice_tips" tips="如果是酒店，酒店首页将显示该分类"></em>
				</td>
			</tr>
			</if>


            <if condition="$config['group_content_switch'] eq 1 && empty($_GET['cat_fid'])">
                <tr>
                    <th width="80">编辑器数量</th>
                    <td><input type="number" min="1" max="5" class="input fl" value="{pigcms{$now_category.editor_num}" name="editor_num" style="width:200px;" placeholder="请填写编辑器数量" tips="填写编辑其数量后请填写对应的编辑器头部标题"/></td>
                </tr>
                <tr class="editor_title_info">
                    <th width="80">编辑器标题</th>
                    <td>
                        <input type="text" class="input fl editor_title_0" value="{pigcms{$now_category['editor_title'][0]}" name="editor_title[0]" style="width:200px;margin-bottom: 10px;" placeholder="请填写第一个编辑器头标题"/>
                        <input type="text" class="input fl editor_title_1" value="{pigcms{$now_category['editor_title'][1]}" name="editor_title[1]" style="width:200px;margin-bottom: 10px;<if condition="$now_category['editor_title'][1] eq ''">display: none;</if>" placeholder="请填写第二个编辑器头标题"/>
                        <input type="text" class="input fl editor_title_2" value="{pigcms{$now_category['editor_title'][2]}" name="editor_title[2]" style="width:200px;margin-bottom: 10px;<if condition="$now_category['editor_title'][2] eq ''">display: none;</if>" placeholder="请填写第三个编辑器头标题"/>
                        <input type="text" class="input fl editor_title_3" value="{pigcms{$now_category['editor_title'][3]}" name="editor_title[3]" style="width:200px;margin-bottom: 10px;<if condition="$now_category['editor_title'][3] eq ''">display: none;</if>" placeholder="请填写第四个编辑器头标题"/>
                        <input type="text" class="input fl editor_title_4" value="{pigcms{$now_category['editor_title'][4]}" name="editor_title[4]" style="width:200px;<if condition="$now_category['editor_title'][4] eq ''">display: none;</if>" placeholder="请填写第五个编辑器头标题"/>
                    </td>
                </tr>
            </if>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>

<script>
    $("input[name='editor_num']").change(function () {
        console.log('val', $(this).val());
        var val = $(this).val();
        if (val>0) {
            if (val>5) {
                alert('编辑器数量暂时只支持最多5个！')
            }
            $('.editor_title_info').show();
            $('.editor_title_info input').hide();
            $('.editor_title_info input').val('');
            if (val > 1) {
                for (var i=0;i<val;i++) {
                    $('.editor_title_info .editor_title_'+i).show();
                }
            } else {
                $('.editor_title_info .editor_title_1').show();
            }
        } else {
            $(this).val('');
            alert('请填写大于0的数量')
        }
    })
</script>
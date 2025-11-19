
<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pcap.js?13"></script>
<form id="myform" method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">编号</th>
            <td>
                {pigcms{$list.id}
            </td>
        </tr>
        <if condition="empty($_GET['is_classfiy'])">
		<tr>
			<th width="160">区域</th>
			<td>
				<if condition="$admin_area neq 3">
				<div id="choose_pca" province_idss="{pigcms{$list.province_id}" city_idss="{pigcms{$list.city_id}"  style="display:inline">
				</div>
				</if>
			</td>
		</tr>
        <else/>
            <input type="hidden" name="province_idss" value="{pigcms{$list.province_id}"/>
            <input type="hidden" name="city_idss" value="{pigcms{$list.city_id}"/>
        </if>
        <if condition="!empty($list['f_cate_name'])">
        <tr>
            <th width="80">所属分类</th>
            <td>
                {pigcms{$list.f_cate_name}
            </td>
        </tr>
        </if>
        <tr>
            <th width="80">分类名称</th>
            <td><input type="text" class="input fl" name="cate_name" id="cate_name" value="{pigcms{$list.cate_name}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
		 <tr>
            <th width="80">排序</th>
            <td><input type="text" class="input fl" name="sort" id="sort" value="{pigcms{$list.sort}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">状态</th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$list['status'] eq 0">selected</if>"><span>开启</span><input name="status" type="radio" value="0" <if condition="$list['status'] eq 0">checked="checked"</if> /></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$list['status'] eq 1">selected</if>"><span>关闭</span><input name="status" type="radio" value="1" <if condition="$list['status'] eq 1">checked="checked"</if> /></label></span>
            </td>
        </tr>
        <tr>
            <th width="80">添加时间</th>
            <td>
                {pigcms{$list.add_time|date='Y-m-d H:i:s',###}
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>
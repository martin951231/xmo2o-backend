<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Employee/cat_modify')}" >
        <input type="hidden" name="cat_fid" value="{pigcms{$_GET.cat_fid}"/>
        <input type="hidden" name="cat_pfcid" value="{pigcms{$_GET.cat_pfcid}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">分类名称</th>
                <td><input type="text" class="input fl" name="cat_name" id="cat_name" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
            </tr>
            <tr>
                <th width="80">分类描述</th>
                <td><input type="text" class="input fl" name="cat_des" id="cat_des" maxlength="100" size="25" placeholder=""  tips="用于描述分类的副标题，吸引客户,限制100字以内"/></td>
            </tr>
			<tr>
				<th width="80">短标记(url)</th>
				<td><input type="text" class="input fl" name="cat_url" id="cat_url" size="25" placeholder="英文或数字" validate="maxlength:20,required:true,en_num:true" tips="只能使用英文或数字，用于网址（url）中的标记！建议使用分类的拼音"/></td>
			</tr>
<!--            <if condition="empty($_GET['cat_fid'])">-->
                <tr>
                    <th width="80">分类LOGO图标</th>
                    <td><input type="file" class="input fl" name="cat_pic" style="width:200px;" placeholder="分类LOGO图片" validate="required:true"  tips="分类LOGO小图标，建议尺寸100*100"/></td>
                </tr>
<!--            </if>-->

<!--            <if condition="empty($_GET['cat_fid'])">-->
<!--                <tr>-->
<!--                    <th width="80">分类广告图</th>-->
<!--                    <td><input type="file" class="input fl" name="cat_ad_pic" style="width:200px;" placeholder="分类广告图" tips="分类广告图，建议尺寸950*400"/></td>-->
<!--                </tr>-->
<!--            </if>-->
			<tr>
				<th width="80">分类排序</th>
				<td><input type="text" class="input fl" name="cat_sort" value="0" size="10" placeholder="分类排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
			</tr>
			<tr>
				<th width="80">是否热门</th>
				<td>
					<span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="is_hot" value="1" /></label></span>
					<span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="is_hot" value="0" checked="checked" /></label></span>
					<em class="notice_tips" tips="如果选择热门，颜色会有变化"></em>
				</td>
			</tr>
			<tr>
				<th width="80">分类状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="cat_status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="cat_status" value="0" /></label></span>
				</td>
			</tr>

            <if condition="empty($_GET['cat_fid']) && empty($_GET['cat_pfcid'])">
                <tr>
                    <th width="80">评论和评论图片上传</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="is_comment" value="1" checked="checked"/></label></span>
                        <span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="is_comment" value="2"/></label></span>
                        <span><input type="hidden" tips="用来设置评论和评论上传图片功能，如果开启，最下级分类的设置才有用，如果关闭那么以上级分类设置为准。"/></span>
                    </td>
                </tr>

                <tr>
                    <th width="80">关联的店铺分类</th>
                    <td>
                        <if condition="$merchant_category">
                            <select name="merchant_cat_id" style="padding: 5px;height: 30px;" tips="该从业者分类关联商家一个一级分类后，其他从业者分类无法再次关联商家的这个已被关联的一级分类。">
                                <option value="-1">不关联分类</option>
                                <volist name="merchant_category" id="vo">
                                    <option value="{pigcms{$vo['cat_id']}" <if condition="$now_category['merchant_cat_id'] eq $vo['cat_id']">selected="selected"</if>>{pigcms{$vo['cat_name']}</option>
                                </volist>
                            </select>
                        </if>
                    </td>
                </tr>
            </if>


            <if condition="!empty($_GET['cat_fid']) && !empty($_GET['cat_pfcid'])">
                <tr>
                    <th width="80">是否开启悬浮广告</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input name="is_open_adver" type="radio" value="1"  checked="checked"/></label></span>
                        <span class="cb-disable"><label class="cb-disable "><span>关闭</span><input name="is_open_adver" type="radio" value="0" /></label></span>
                    </td>
                </tr>
                <tr>
                    <th width="80">悬浮框背景色</th>
                    <td>
                        <input type="text" class="input fl" name="adver_color" id="choose_color1" style="width:120px;background: {pigcms{$employee_list['adver_color']}" placeholder="可不填写" tips="请点击右侧按钮选择颜色，用途为个人详情页悬浮广告背景色使用"/>&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box1" style="line-height:28px;">点击选择颜色</a>
                    </td>
                </tr>
                <tr>
                    <th width="80">悬浮框文字</th>
                    <td><input type="text" class="input fl" name="adver_info" id="adver_info" maxlength="20" size="25" placeholder=""  tips="填写悬浮内容，如不填写不显示。长度限制为20字"/></td>
                </tr>
                <tr>
                    <th width="80">悬浮框链接</th>
                    <td>
                        <input type="text" class="input fl" name="adver_url" id="url" size="25" placeholder=""  tips="点击悬浮广告跳转链接，如不填写前端只展示点击无反应"/>
                        <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">从功能库选择</a>
                    </td>
                </tr>
            </if>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    $('#choose_color_box1').click(function(){
        window.o2oTop.showTopColorPanel('Openadd',0,$(this).height(),$(this).offset().left,'choose_color1')
    });
    function addLink(domid,iskeyword){
        art.dialog.data('domid', domid);
        art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
    }
</script>
<include file="Public:footer"/>
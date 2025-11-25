<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/level_gift_add')}" enctype="multipart/form-data">
		<input type="hidden" name="gift_id" value="{pigcms{$level_gift.gift_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">礼包名称</th>
				<td><input type="text" value="{pigcms{$level_gift.gift_name}" class="input fl" name="gift_name" size="20" maxlength="15" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>

            <tr>
                <th width="80">选择会员等级</th>
                <td>
                    <select name="level_id" id="level_id" tips="">
                        <option value="">选择会员等级</option>
                        <volist name='user_level' id='vo'>
                            <option value="{pigcms{$vo.id}" <if condition="$level_gift['level_id'] eq $vo['id']">selected="selected"</if>>{pigcms{$vo.lname}</option>
                        </volist>
                    </select>
                </td>
            </tr>
            <if condition="$level_gift['gift_img'] neq ''">
                <tr>
                    <th width="80">现有图片</th>
                    <td><img src="{pigcms{:file_domain()}/{pigcms{$level_gift['gift_img']}" style="width:260px;height:80px;" class="view_msg"/></td>
                </tr>
            </if>
			<tr>
				<th width="80">图片</th>
				<td>
                    <input type="file" class="input fl" name="gift_img" style="width:200px;" placeholder="请上传图片" <if condition="$level_gift['gift_img'] eq ''">validate="required:true"</if>/>
                    <span>&nbsp;&nbsp;&nbsp;建议尺寸360*240</span>
                </td>
			</tr>

            <tr>
                <th width="80">图片链接地址</th>
                <td>
                    <input type="text" class="input fl" name="gift_url" value="{pigcms{$level_gift['gift_url']}" id="url" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,required:true,url:true"/>
                    <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
                </td>
            </tr>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$level_gift['gift_status'] eq 1 OR !$level_gift">selected</if>"><span>启用</span><input type="radio" name="gift_status" value="1" <if condition="$level_gift['gift_status'] eq 1  OR !$level_gift">checked="checked" </if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$level_gift['gift_status'] eq 2">selected</if>"><span>关闭</span><input type="radio" name="gift_status" value="2" <if condition="$level_gift['gift_status'] eq 2">checked="checked" </if>/></label></span>
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
    function addLink(domid, iskeyword, type) {
        art.dialog.data('domid', domid);
        if (type == 1) {
            art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        } else {
            art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword=' + iskeyword, {
                lock: true,
                title: '插入链接或关键词',
                width: 800,
                height: 500,
                yesText: '关闭',
                background: '#000',
                opacity: 0.45
            });
        }
    }
</script>
<include file="Public:footer"/>
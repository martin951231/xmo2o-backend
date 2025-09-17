<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('PreventionControl/modify')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">名称</th>
                <td><input type="text" class="input fl" name="name" id="name" size="25" placeholder="请填写对应车牌号或者其他名称" validate="maxlength:20,required:true" tips=""/></td>
            </tr>
            <tr>
                <th width="80">分类</th>
                <td>
                    <select name="type">
                        <volist name="type_info" id="type">
                            <option value="{pigcms{$key}">{pigcms{$type}</option>
                        </volist>
                    </select>
                </td>
            </tr>
            <tr>
                <th width="80">联系电话</th>
                <td>
                <input type="text" class="input fl" name="phone" size="20" placeholder="请填写联系电话"/>
            </td>
            </tr>
            <tr>
                <th width="80">绑定管理员</th>
                <td>
                    <select name="bind_admin">
                        <volist name="admin_log" id="item">
                            <option value="{pigcms{$item['id']}">
                                {pigcms{$item['realname']}
                            </option>
                        </volist>
                    </select>
                </td>
            </tr>

            <tr>
                <th width="80">绑定店铺id</th>
                <td>
                <input type="text" class="input fl" name="bind_id" size="20" placeholder="请填写绑定店铺id" tips="用于使用店铺的快速买单功能"/></td>
            </tr>
            <tr>
                <th width="80">支付二维码图片</th>
                <td><input type="file" class="input fl" name="pay_img" style="width:200px;" placeholder="支付二维码图片" tips="分类广告图，建议尺寸400*400"/></td>
            </tr>


            <tr>
                <th width="80">备注</th>
                <td><textarea name="remark"/></textarea> </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
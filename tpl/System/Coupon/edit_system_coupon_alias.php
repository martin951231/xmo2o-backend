<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('Coupon/edit_system_coupon_alias')}"  enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">名称设置</th>
            <td><input type="text" class="input fl" name="coupon_main_system_coupon_alias" value="{pigcms{$coupon_main_system_coupon_alias}" placeholder="请输入名称" validate="required:true"/></td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>
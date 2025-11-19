<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('Coupon/set_center_decoration_share')}"  enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">标题</th>
            <td><input type="text" class="input fl" name="coupon_main_title" value="{pigcms{$config.coupon_main_title}" placeholder="请输入标题" validate="required:true"/></td>
        </tr>
        <tr>
            <th width="80">副标题</th>
            <td><input type="text" class="input fl" name="coupon_main_sub_title" value="{pigcms{$config.coupon_main_sub_title}" placeholder="请输入副标题" validate="required:true"/></td>
        </tr>
        <tr>
            <th width="80">分享图片</th>
            <td>
                <input type="file" class="input fl" name="coupon_main_share_pic" style="width:200px;" placeholder="请上传图片" />
                <input type="hidden" name="coupon_main_share_pic" value="{pigcms{$config.coupon_main_share_pic}"/>
            </td>
        </tr>
        <tr>
            <th width="80">预览分享图</th>
            <td>
                <img style="max-width: 150px" src="{pigcms{$config.coupon_main_share_pic|replace_file_domain}"/>
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>
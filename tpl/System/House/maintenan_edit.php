<include file="Public:header"/>

<form id="myform" method="post" action="{pigcms{:U('House/maintenan_edit')}" enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="100">姓名</th>
            <td><input type="text" class="input fl" value="{pigcms{$res['name']}" name="name" size="20" placeholder="请输入姓名" validate="maxlength:20,required:true"/></td>
        </tr>
        <input type="hidden" name="id" value="{pigcms{$res['id']}" >
        <tr>
            <th width="100">手机号</th>
            <td><input type="text" class="input fl" value="{pigcms{$res['phone']}" name="phone" size="20" placeholder="请输入手机号" validate="maxlength:30,required:true"/></td>
        </tr>

        <tr>
            <th width="120">状态</th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$res['status'] eq 1">selected</if>"><span>正常</span>
                    <input type="radio" name="status" value="1" <if condition="$res['status'] eq 1">checked="checked"</if>/></label></span>
                <span class="cb-disable " ><label class="cb-disable  <if condition="$res['status'] eq 0">selected</if>"><span>禁用</span>
                    <input type="radio" name="status" value="0" <if condition="$res['status'] eq 0">checked="checked"</if>/></label></span>
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
<style>
    .fl{
        width: 250px;
    }

</style>
<include file="Public:footer"/>
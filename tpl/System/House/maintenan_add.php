<include file="Public:header"/>

<form id="myform" method="post" action="{pigcms{:U('House/maintenan_add')}" enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="100">姓名</th>
            <td><input type="text" class="input fl" name="name" size="20" placeholder="请输入姓名" validate="maxlength:20,required:true"/></td>
        </tr>
        <tr>
            <th width="100">手机号</th>
            <td><input type="text" class="input fl" name="phone" size="20" placeholder="请输入手机号" validate="maxlength:30,required:true"/></td>
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
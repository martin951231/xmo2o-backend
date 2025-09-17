<include file="Public:header"/>
<style>
    .sub_mch{
        display:none
    }
</style>
<div id="nav" class="mainnav_title">
    <ul>
        <a href="{pigcms{:U('Deliver/handleRegister')}&id={pigcms{$_GET['id']}&frame={pigcms{$_GET['frame']}">配送员审核信息</a>|
        <!-- <a href="{pigcms{:U('Deliver/handleRegisterFields')}&id={pigcms{$_GET['id']}&frame={pigcms{$_GET['frame']}" class="on">注册信息</a> -->
    </ul>
</div>
<form id="myform" method="post" action="{pigcms{:U('Deliver/handleRegister')}" frame="true" refresh="true">
    <input type="hidden" name="id" value="{pigcms{$register.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="15%">姓名</th>
            <td colspan="3">{pigcms{$register.truename}</td>
        </tr>
        <tr>
            <th width="15%">手机号</th>
            <td colspan="3">{pigcms{$register.phone}</td>
        </tr>
        <tr>
            <th width="15%">身份证号</th>
            <td colspan="3">{pigcms{$register.card_number}</td>
        </tr>
        <php>if($now_user['user_field']){</php>
            {foreach $now_user['user_field'] as $v}
                <tr>
                    <th width="15%">{pigcms{$v['name']}</th>
                    <td colspan="3">{pigcms{$v['value']}</td>
                </tr>
            {/foreach}
        <php>}</php>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>

<include file="Public:footer"/>
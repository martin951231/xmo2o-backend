<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('Mall/check_id_card')}" frame="true" refresh="true">
    <input type="hidden" name="goods_id" value="{pigcms{$goods_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="120" rowspan="2">是否开启实名认证</th>
            <td height="50">
                <input name="is_open" type="radio" value="1" <if condition="$check_config['is_open_check'] eq 1">checked</if>/> 开启
                <input name="is_open" type="radio" style="margin-left: 20px;" value="0" <if condition="$check_config['is_open_check'] eq 0">checked</if>/> 关闭
            </td>
        </tr>
        <tr>
            <td height="50">
                <p>
                    <input name="img_check" type="radio" value="0" <if condition="$check_config['img_check'] eq 0">checked</if>/> 姓名 + 身份证号
                </p>
                <p style="margin-top: 10px;">
                    <input name="img_check" type="radio" value="1" <if condition="$check_config['img_check'] eq 1">checked</if>/> 姓名 + 身份证号 + 身份证正反面
                </p>
            </td>
        </tr>

    </table>
    <div class="btn hidden">
        <input type="submit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>
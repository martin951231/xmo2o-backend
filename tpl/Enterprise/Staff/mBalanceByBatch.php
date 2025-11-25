<include file="Public:lite_header"/>
<style type="text/css">
    #myform {
        margin-top: 20px;
    }

    #myform th {
        padding: 15px;
        font-weight: unset;
        width: 20%;
    }

    .notice {
        font-size: 1rem;
        padding-left: 10px;
    }

    .must {
        color: #FF0000;
        padding-left: 10px;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('Staff/add')}" frame="true" refresh="true">
    <input type="hidden" name="staff_ids" id="staff_ids" value="{pigcms{$staffIds}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="100">选择人数</th>
            <td>
                {pigcms{$count}人
            </td>
        </tr>
        <tr>
            <th width="100">操作<span class="must">*</span></th>
            <td>
                <select name="operate" id="operate">
                    <option value="0">请选择</option>
                    <option value="1">预存</option>
                    <option value="2">扣减</option>
                </select>
            </td>
        </tr>
        <tr>
            <th width="100">金额<span class="must">*</span></th>
            <td>
                <input id="money" type="number" precision="2" class="input fl" name="money" size="25" validate="required:true"/>
            </td>
        </tr>
        <tr>
            <th width="100">备注<span class="must">*</span></th>
            <td>
                <input id="remark" type="text" class="input fl" name="remark" size="25" validate="required:true"/>
            </td>
        </tr>
        <tr>
            <th width="100">操作密码<span class="must">*</span></th>
            <td>
                <input id="password" type="password" class="input fl" name="password" size="25" value="" validate="required:true" autocomplete="new-password"/>
            </td>
        </tr>

    </table>
</form>
<include file="Public:footer"/>
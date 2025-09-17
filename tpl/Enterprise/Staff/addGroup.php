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
    <input type="hidden" name="group_id" value="{pigcms{$group.group_id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="100">组别名称<span class="must">*</span></th>
            <td>
                <input id="name" type="text" class="input fl" name="name" size="25" value="{pigcms{$group.name|default=''}"
                       validate="required:true"/>
            </td>
        </tr>

        <tr>
            <th width="100">组别备注<span class="must">*</span></th>
            <td><input id="remark" type="text" class="input fl" name="remark" size="25" value="{pigcms{$group.remark|default=''}" validate="required:true"/>
            </td>
        </tr>
    </table>
</form>
<include file="Public:footer"/>
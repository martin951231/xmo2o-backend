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
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="100">卡号/工号/ID<span class="must">*</span></th>
            <td>
                <input id="staff_number" type="text" class="input fl" name="staff_number" size="25"
                       validate="required:true" value="{pigcms{$staffInfo.staff_number}"/>
                <span class="notice">不能和已有ID重复</span>
            </td>
        </tr>

        <tr>
            <th width="100">姓名<span class="must">*</span></th>
            <td><input id="name" type="text" class="input fl" name="name" size="25" validate="required:true"
                       value="{pigcms{$staffInfo.name}"/>
            </td>
        </tr>

        <tr>
            <th width="100">手机号码</th>
            <td>
                <p class="input fl" style="padding: 5px 4px 6px;font-size: 14px;margin: 0">{pigcms{$staffInfo.phone}</p>
            </td>
        </tr>

        <tr>
            <th width="100">预存余额</th>
            <td>
                <p class="input fl" style="padding: 5px 4px 6px;font-size: 14px;margin: 0">{pigcms{$staffInfo.money} <font color="red">(其中待审核到账金额{pigcms{$staffInfo.unpaid_money})</font></p>
            </td>
        </tr>

        <tr>
            <th width="100">实体卡ID</th>
            <td>
                <input id="card_id" type="text" class="input fl" name="card_id" size="25" validate="required:true"
                       value="{pigcms{$staffInfo.card_id}"/>
                <span class="notice">物理卡号，可以为空</span>
            </td>
        </tr>

        <tr>
            <th width="100">员工组别</th>
            <td>
                <select name="group_id" id="group_id">
                    <option value="0">请选择</option>
                    <volist name="groups" id="g">
                        <option value="{pigcms{$g.group_id}"
                        <php>if($staffInfo['group_id'] == $g['group_id']){echo 'selected';}</php>
                        >{pigcms{$g.name}</option>
                    </volist>

                </select>
            </td>
        </tr>

        <tr>
            <th width="100">备注</th>
            <td><input id="remark" type="text" class="input fl" name="remark" size="25" validate="required:true"
                       value="{pigcms{$staffInfo.remark}"/>
            </td>
        </tr>

        <tr>
            <th width="100">状态<span class="must">*</span></th>
            <td>
                <select name="status" id="status">
                    <option value="1"
                    <php>if($staffInfo['status'] == 1){echo 'selected';}</php>
                    >正常</option>
                    <option value="0"
                    <php>if($staffInfo['status'] == 0){echo 'selected';}</php>
                    >禁用</option>
                </select>
            </td>
        </tr>

    </table>
</form>
<include file="Public:footer"/>
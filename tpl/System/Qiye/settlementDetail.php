<include file="Public:header"/>
<style>
    .sub_mch {
        display: none
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('Qiye/handleSettlement')}" frame="true" refresh="true">
    <input type="hidden" name="id" value="{pigcms{$info.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="160">企业名称</th>
            <td>{pigcms{$info.qiye_name}</td>
        </tr>
        <tr>
            <th width="160">区域</th>
            <td>{pigcms{$info.address}</td>
        </tr>
        <tr>
            <th width="160">结算金额</th>
            <td>{pigcms{$info.money}</td>
        </tr>
        <tr>
            <th width="160">付款时间</th>
            <td>{pigcms{$info.pay_date}</td>
        </tr>

        <tr>
            <th width="160">付款凭证</th>
            <td><a target="_blank" src="{pigcms{$info.pic_info}"><img src="{pigcms{$info.pic_info}"
                                                                      style="height: 90px"/></a></td>
        </tr>

        <tr>
            <th width="160">备注</th>
            <td>{pigcms{$info.remark}</td>
        </tr>

        <tr>
            <th width="160">申请时间</th>
            <td>{pigcms{$info.apply_time|date='Y-m-d H:i:s',###}</td>
        </tr>

        <tr>
            <th width="160">IP</th>
            <td>{pigcms{$info.ip}</td>
        </tr>


        <if condition="$info['status'] NEQ 0">
            <tr>
                <th width="160">审核备注</th>
                <td>{pigcms{$info.handle_remark}</td>
            </tr>

            <tr>
                <th width="160">审核时间</th>
                <td>{pigcms{$info.handle_time|date='Y-m-d H:i:s',###}</td>
            </tr>

            <tr>
                <th width="160">审核人</th>
                <td>{pigcms{$info.admin_account}</td>
            </tr>
        </if>


    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button"/>
        <input type="reset" value="取消" class="button"/>
    </div>
</form>
<script>

</script>

<include file="Public:footer"/>
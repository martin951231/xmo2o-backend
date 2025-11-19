<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {
                    display: none;
                }
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <button class="btn btn-success" onclick="apply_settlement()">{pigcms{:L_("提交结算凭证")}</button>
                    <div id="shopList" class="grid-view">
                        <p style="font-size: 1.5rem;font-weight: bold;margin-right: 35px;padding: 2px 6px;">
                            未结算金额:{pigcms{$money|floatval}</p>
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("提交时间")}</th>
                                <th>{pigcms{:L_("结算金额")}</th>
                                <th>{pigcms{:L_("备注")}</th>
                                <th>{pigcms{:L_("付款时间")}</th>
                                <th>{pigcms{:L_("凭证图片")}</th>
                                <th>{pigcms{:L_("状态")}</th>
                                <th>{pigcms{:L_("审核备注")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <volist name="lists" id="l">
                                <tr>
                                    <td>{pigcms{$l.apply_time|date='Y-m-d H:i:s',###}</td>
                                    <td>{pigcms{$l.money|floatval}</td>
                                    <td>{pigcms{$l.remark}</td>
                                    <td>{pigcms{$l.pay_date}</td>
                                    <td><a target="_blank" href="{pigcms{$l.pic_info}"><img
                                                    style="width: 35px;height: 35px" src="{pigcms{$l.pic_info}"></a>
                                    </td>
                                    <td>
                                        <if condition="$l['status'] eq 0">
                                            待审核
                                            <elseif condition="$l['status'] EQ 1"/>
                                            通过
                                            <else/>
                                            驳回
                                        </if>
                                    </td>
                                    <td>{pigcms{$l.handle_remark}</td>
                                </tr>
                            </volist>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function apply_settlement() {
        location.href = "{pigcms{:U('Analysis/applySettlement')}";
        return false;
    }


</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>

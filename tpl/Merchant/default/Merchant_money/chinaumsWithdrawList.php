<include file="Public:header" />
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
            <li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("商家余额")}</a></li>
            <li class="active">{pigcms{:L_("银联提现记录")}</li>

        </ul>
    </div>
    <style>
        ul,
        ol {
            margin-bottom: 15px;
        }

        button {
            padding: 6px;
            background-color: rgba(255, 255, 255, 0);
            box-sizing: border-box;
            border-width: 1px;
            border-style: solid;
            border-color: rgba(121, 121, 121, 1);
            border-radius: 2px;
            -moz-box-shadow: none;
            -webkit-box-shadow: none;
            box-shadow: none;
            font-size: 14px;
            color: #666666;
            cursor: pointer;

        }
    </style>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-sm-12">
                    <div class="tabbable">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>{pigcms{:L_("提现时间")}</th>
                                                <th>{pigcms{:L_("提现金额")}</th>
                                                <th>{pigcms{:L_("状态")}</th>
                                                <th>{pigcms{:L_("备注")}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <if condition="$withdraw_list">
                                                <volist name="withdraw_list" id="vo">
                                                    <tr>
                                                        <td>{pigcms{$vo.withdraw_time|date="Y/m/d H:i",###}</td>
                                                        <td>{pigcms{$vo['money']/100}{pigcms{$config['Currency_txt']}</td>
                                                        <td>
                                                            <if condition="$vo.status eq 0">
                                                                <font color="red">{pigcms{:L_("审核中")}...</font>
                                                                <elseif condition="$vo.status eq 1" />
                                                                <font color="green">{pigcms{:L_("已通过")}</font>
                                                                <elseif condition="$vo.status eq 2 OR $vo.status eq 4" />
                                                                <font color="red">{pigcms{:L_("被驳回")}</font>
                                                                <elseif condition="$vo.status eq 3" />
                                                                <font color="green">{pigcms{:L_("已提现")}</font>
                                                                <elseif condition="$vo.status eq 10" />
                                                                <font color="red">{pigcms{:L_("提现中")}</font>
                                                                <elseif condition="$vo.status eq 20" />
                                                                <font color="red">{pigcms{:L_("提现失败")}</font>
                                                            </if>

                                                        </td>
                                                        <td>
                                                            <if condition="$vo.desc neq ''"> {pigcms{$pay_type[$vo['pay_type']]}| {pigcms{$vo.desc} |</if>{pigcms{$vo.remark}
                                                        </td>

                                                    </tr>
                                                </volist>

                                                <tr class="odd">
                                                    <td colspan="4" id="show_count"></td>
                                                </tr>
                                                <tr>
                                                    <td class="textcenter pagebar" colspan="4">{pigcms{$pagebar}</td>
                                                </tr>
                                                <else />
                                                <tr class="odd">
                                                    <td class="textcenter red" colspan="17">{pigcms{:L_("没有提现记录")}</td>
                                                </tr>
                                            </if>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer" />
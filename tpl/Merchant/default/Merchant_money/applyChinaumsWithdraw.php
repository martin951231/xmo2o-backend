<include file="Public:header" />

<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
            <li class="active"><a href="{pigcms{:U('Merchant_money/index')}">{pigcms{:L_("商家余额")}</a></li>
            <li class="active">{pigcms{:L_("银联申请提现")}</li>
        </ul>
    </div>
    <div class="page-content form-horizontal ">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form enctype="multipart/form-data" class="form-horizontal" method="post" action="" onsubmit="return checkSubmit()" id="">
                        <div class="tab-content card_new">
                            <div class="headings gengduoxian">{pigcms{:L_("您的余额为：")}{pigcms{$config['Currency_symbol']}{pigcms{$chinaums_money} <!--<if condition="$config['company_pay_mer_percent'] gt 0 AND $config['merchant_withdraw_fee_type'] eq 0">{pigcms{:L_("提现手续费比例：")}{pigcms{$config['company_pay_mer_percent']}
                                    <elseif condition="$config['company_pay_mer_money'] gt 0 AND $config['merchant_withdraw_fee_type'] eq 1" />{pigcms{:L_("提现手续费金额：")}{pigcms{$config['company_pay_mer_money']}{pigcms{$config['Currency_txt']}
                                    <else />{pigcms{:L_("无手续费")}
                                </if>-->
                            </div>
                            <div class="form-group">
                                <label class="tiplabel"><label>{pigcms{:L_("金额：")}</label></label>
                                <input type="text" class="px" name="money" id="money" value="" />{pigcms{$config['Currency_txt']}&nbsp;<label id="percent">({pigcms{:L_("最低提现额：")}<if condition="$config['company_least_money'] gt $config['min_withdraw_money'] ">{pigcms{$config['company_least_money']}
                                        <else />{pigcms{$config['min_withdraw_money']}
                                    </if>{pigcms{$config['Currency_txt']})</label>
                            </div>
                            <div class="form-group">
                                <label class="tiplabel" style="vertical-align:top;"><label>{pigcms{:L_("备注：")}</label></label>
                                <textarea name="info" id="info" class="px" style="width:410px;height:120px;"></textarea>
                            </div>
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info" type="submit">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        {pigcms{:L_("保存")}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .paytype {
        display: none;
    }

    #bank input {
        margin-right: 5px;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    var flag = true;
    function checkSubmit() {
        if(!$('#money').val()){
            layer.msg('请输入金额')
            return false
        }
        if(!$('#info').val()){
            layer.msg('请输入备注')
            return false
        }
        if (!flag) {
            return false;
        }
        flag = false
    }
</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css" />
<include file="Public:footer" />
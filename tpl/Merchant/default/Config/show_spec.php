<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script src="{pigcms{$static_public}js/layer/layer.js"></script>
    <title>{pigcms{$config.site_name} - {pigcms{:L_("店铺管理中心")}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
</head>
<body>

<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12" style="padding-left:0px;padding-right:0px;">

                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">

                            <thead>
                            <tr>
                                <th>规格</th>
                                <th>操作前价格</th>
                                <th>操作后价格</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="is_array($specs)">
                                <volist name="specs" id="vo">
                                    <tr>
                                        <td>{pigcms{$vo.str}</td>
                                        <td>{pigcms{$vo.old_price|getFormatNumber}</td>
                                        <td>{pigcms{$vo.new_price|getFormatNumber}</td>
                                    </tr>
                                </volist>
                                <tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
                                <else/>
                                <tr><td class="textcenter red" colspan="9">{pigcms{:L_("列表为空！")}</td></tr>
                            </if>
                            </tbody>

                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .statist-wrap{width:15%;margin-right:2.3%;padding:0 15px;border:1px solid #ccc;border-radius:5px;margin-bottom:15px}.sta-mon{font-size:25px;color:#333;width:100%;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1}.sta-num{font-size:18px;color:#999;width:100%;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sta-total{background:#1c84c6;padding:2px 7px;color:#fff}.sta-trade{background:#23c6c8;padding:2px 7px;color:#fff}.sta-refund{background:#ed5565;padding:2px 7px;color:#fff}.time-choice-wrap{margin:0 10px;border-radius:3px;border:1px solid #ccc}.time-choice{float:left;display:block;padding:0 10px;line-height:29px;font-size:12px;color:#333;outline:0;cursor:pointer}.time-choice.time-current{color:#fff;background:#1ab394}.bor-gray{border-left:1px solid #ccc;border-right:1px solid #ccc}.fl{margin-right:5px;margin-bottom:5px;}.plat_form{display:none;}
</style>
<include file="Public:footer"/>

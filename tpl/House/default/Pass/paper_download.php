<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<script src="{pigcms{$static_path}js/ySelect.js"></script>
<link href="{pigcms{$static_path}css/ySelect.css" rel="stylesheet" type="text/css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
    }
</style>
<div class="page-content" style="padding-top: 10px;">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" method="post" onSubmit="return check_submit()">
                    <div class="tab-content">
                        <div class="tab-pane active">
                            <div class="form-group" style="margin-left: 20px;">
                                <div style="float: left;">
                                    <h4>出入证码导出</h4>
                                    <label for="name">导出后可用于下发物料中使用</label>
                                </div>
                                <div style="float: right;margin-top: 10px;">
                                    <input class="ui-button-app" onclick="exports()" type="button" value="导出" style="text-align:center;line-height: 32px;padding: 1px 20px 1px 20px;">
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane active">
                            <div class="form-group" style="margin-left: 10px;">
                                <div style="float: left;">
                                    <h4>物料下载</h4>
                                    <label for="name">下载下来后，可使用该物料打印纸质版出入证，方便人员使用</label>
                                    <br/>
                                    <img src="{pigcms{$static_path}images/pass.png?t=001">
                                </div>
                                <div style="float: right;margin-top: 10px;">
                                    <a class="ui-button-app" href="{pigcms{$static_path}file/pass_material.psd?t=01" download="pass_material.psd" style="text-align:center;line-height: 32px;padding: 10px 20px 10px 20px;">psd下载</a>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>
</div>

<script type="text/javascript">
    function check_submit() {

    }
    //导出
    function exports() {
        window.location.href="{pigcms{:U('pass_exports')}";
    }
</script>
<style>
    .ui-button-app {
        background-color: #1686ef;
        background-size: 15px;
        background-repeat: no-repeat;
        background-position: 5% 50%;
        border-radius: 6px;
        text-align: right;
        border: 0;
        color: #fff;
        margin-right: 30px;
    }
</style>
<include file="Public:footer"/>



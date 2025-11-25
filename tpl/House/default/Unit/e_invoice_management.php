<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Unit/e_invoice_management')}">物业管理</a>
            </li>
            <li class="active">电子发票</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="tabbable">
                    <ul id="myTab" class="nav nav-tabs">
                        <li class="active">
                            <a href="#basicinfo" data-toggle="tab">基本设置</a>
                        </li>
                    </ul>
                </div>
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Unit/e_edit')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <div class="form-group">
                                <label class="col-sm-1"><label for="has_property_pay">是否开启电子发票功能</label></label>
                                <div class="radio">
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_open" value="1" type="radio" class="ace" <if condition="$data.is_open eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="is_open" value="2" type="radio" class="ace" <if condition="$data.is_open eq 12">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="has_property_pay">开票方式</label></label>
                                <div class="radio">
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_tui" value="1" type="radio" class="ace" <if condition="$data.is_tui eq 1">checked</if> /><span class="lbl" style="z-index: 1">自动推送</span></label>
                                    <label style="padding-left:0px;"><input name="is_tui" value="2" type="radio" class="ace" <if condition="$data.is_tui eq 2">checked</if>/><span class="lbl" style="z-index: 1">手动开票</span></label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="seller_name">销售方单位名称</label></label>
                                <input class="col-sm-2" size="20" name="seller_name" id="seller_name" value="{pigcms{$data.seller_name}" type="text" style=""/>
                                <span class="form_tips" style="color: red;margin-left:1px;">*</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="duty_paragraph">税号</label></label>
                                <input class="col-sm-2" size="20" name="duty_paragraph" id="duty_paragraph" value="{pigcms{$data.duty_paragraph}" type="text" style=""/>
                                <span class="form_tips" style="color: red;margin-left:1px;">*</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="seller_address">销售方地址</label></label>
                                <input class="col-sm-2" size="20" name="seller_address" id="seller_address" value="{pigcms{$data.seller_address}" type="text" style=""/>
                                <span class="form_tips" style="color: red;margin-left:1px;">*</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="seller_phone">销售方电话</label></label>
                                <input class="col-sm-2" size="20" name="seller_phone" id="seller_phone" value="{pigcms{$data.seller_phone}" type="text" style=""/>
                                <span class="form_tips" style="color: red;margin-left:1px;">*</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="bank_of_deposit">开户银行</label></label>
                                <input class="col-sm-2" size="20" name="bank_of_deposit" id="bank_of_deposit" value="{pigcms{$data.bank_of_deposit}" type="text" style=""/>
                                <span class="form_tips" style="color: red;margin-left:1px;">*</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="account_number">开户账号</label></label>
                                <input class="col-sm-2" size="20" name="account_number" id="account_number" value="{pigcms{$data.account_number}" type="text" style=""/>
                                <span class="form_tips" style="color: red;margin-left:1px;">*</span>
                            </div>
                        </div>
                        <input type="hidden" name="id" value="{pigcms{$data.id}" id="id" />
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <button type="submit" class="btn btn-info">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    保存
                                </button>
                            </div>
                        </div>
                        </div>
                </form>
            </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
    var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
    var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>

<style>
    .BMap_cpyCtrl{display:none;}
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF!important;
        font-family: "sans serif",tahoma,verdana,helvetica;
        font-size: 12px;
        line-height: 24px;
        height: 24px;
        padding: 2px 4px;
        border-color: #848484 #E0E0E0 #E0E0E0 #848484;
        border-style: solid;
        border-width: 1px;
        display: -moz-inline-stack;
        display: inline-block;
        vertical-align: middle;
        zoom: 1;
    }
    .form-group>label{font-size:12px;line-height:24px;}
    #upload_pic_box{margin-top:20px;height:150px;}
    #upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
    #upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<script type="text/javascript">
    var lock = 0, test;
    KindEditor.ready(function(K){
        $('#edit_form').submit(function(){

            var is_open = $("input[name='is_open']:checked").val();
            if(is_open == ''){
                alert('是否开启该功能不能为空');
                return false;
            }

            var is_tui = $("input[name='is_tui']:checked").val();
            if(is_tui == ''){
                alert('是否设置推送不能为空');
                return false;
            }

            var seller_name = $("#seller_name").val();
            if(seller_name == ''){
                alert('销售方单位名称不能为空');
                return false;
            }
            var duty_paragraph = $("#duty_paragraph").val();
            if(duty_paragraph == ''){
                alert('税号不能为空！');
                return false;
            }
            var seller_address = $("#seller_address").val();
            if(seller_address == ''){
                alert('销售方地址不能为空！');
                return false;
            }
            var seller_phone = $("#seller_phone").val();
            if(seller_phone == ''){
                alert('销售方电话不能为空！');
                return false;
            }
            var bank_of_deposit = $("#bank_of_deposit").val();
            if(bank_of_deposit == ''){
                alert('开户银行不能为空！');
                return false;
            }
            var account_number = $("#account_number").val();
            if(account_number == ''){
                alert('开户账号！');
                return false;
            }

            $('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
            if (lock = 1) {
                return;
            }
            lock = 1;

            $.post("{pigcms{:U('Unit/e_edit')}",$('#edit_form').serialize(),function(result){
                lock = 0;
                if(result.status == 1){
                    window.location.href = "{pigcms{:U('Unit/e_invoice_management')}";
                }else{
                    $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                    alert(result.info);
                }
            })
            return false;
        });
    });

</script>

<include file="Public:footer"/>
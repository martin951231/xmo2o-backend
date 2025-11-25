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
                    <div class="tabbable">
                        <ul class="nav nav-tabs" id="myTab">
                            <li class="active">
                                <a data-toggle="tab" href="#basicinfo">{pigcms{:L_("提交结算凭证")}</a>
                            </li>
                        </ul>
                    </div>
                    <form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
                        <input type="hidden" name="qiye_id" value="{pigcms{$qiye.qiye_id}"/>
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("结算金额")}</label></label>
                                    <input class="col-sm-2" size="30" name="money" value="" id="money" type="text" placeholder="最多可填写{pigcms{$maxApplyMoney}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("备注")}</label></label>
                                    <input class="col-sm-2" size="30" name="remark" value="" id="remark" type="text" placeholder="如：19年7月账单余额"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="pay_date">{pigcms{:L_("付款时间")}</label></label>
                                    <input type="text" id="pay_date" name="pay_date"/>
                                </div>

                                <div class="form-group" style="display:block;">
                                    <label class="col-sm-1">{pigcms{:L_("付款凭证")}</label>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectLogo">{pigcms{:L_("上传图片")}</a>
                                </div>
                                <div class="form-group" style="display:block;">
                                    <label class="col-sm-1">{pigcms{:L_("凭证预览")}</label>
                                    <div id="upload_pic_box">
                                        <ul id="upload_logo_ul">

                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9" style="margin-left: 0px">
                                <button class="btn btn-info" type="submit">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    {pigcms{:L_("提交")}
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
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF !important;
        font-family: "sans serif", tahoma, verdana, helvetica;
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

    .form-group > label {
        font-size: 12px;
        line-height: 24px;
    }

    #upload_pic_box {
        margin-top: 20px;
        height: 150px;
    }

    #upload_pic_box .upload_pic_li {
        width: 130px;
        float: left;
        list-style: none;
    }

    #upload_pic_box img {
        width: 100px;
        height: 70px;
    }

    #upload_pic_box #upload_logo_ul img {
        width: 70px;
        height: 70px;
    }

    .small_btn {
        margin-left: 10px;
        padding: 6px 8px;
        cursor: pointer;
        display: inline-block;
        text-align: center;
        line-height: 1;
        letter-spacing: 2px;
        font-family: Tahoma, Arial/9 !important;
        width: auto;
        overflow: visible;
        color: #333;
        border: solid 1px #999;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        border-radius: 5px;
        background: #DDD;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='#DDDDDD');
        background: linear-gradient(top, #FFF, #DDD);
        background: -moz-linear-gradient(top, #FFF, #DDD);
        background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#FFF), to(#DDD));
        text-shadow: 0px 1px 1px rgba(255, 255, 255, 1);
        box-shadow: 0 1px 0 rgba(255, 255, 255, .7), 0 -1px 0 rgba(0, 0, 0, .09);
        -moz-transition: -moz-box-shadow linear .2s;
        -webkit-transition: -webkit-box-shadow linear .2s;
        transition: box-shadow linear .2s;
        outline: 0;
    }

    .small_btn:active {
        border-color: #1c6a9e;
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#33bbee', endColorstr='#2288cc');
        background: linear-gradient(top, #33bbee, #2288cc);
        background: -moz-linear-gradient(top, #33bbee, #2288cc);
        background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#33bbee), to(#2288cc));
    }
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<script type="text/javascript">

    jeDate("#pay_date",{
        onClose:false,
        minDate:'2019-01-01 00:00:00',
        maxDate:'2099-01-01 23:59:59',
        format: 'YYYY-MM-DD hh:mm:ss'
    });

    KindEditor.ready(function (K) {
        var editor = K.editor({
            allowFileManager: true
        });
        K('#J_selectImage').click(function () {
            if ($('#upload_pic_ul .upload_pic_li').size() >= 10) {
                alert('{pigcms{:L_("最多上传10个图片！")}');
                return false;
            }
            editor.uploadJson = "{pigcms{:U('Merchant/Config/ajax_upload_pic')}";
            editor.loadPlugin('image', function () {
                editor.plugin.imageDialog({
                    showRemote: false,
                    clickFn: function (url, title, width, height, border, align) {
                        $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="' + url + '"/><input type="hidden" name="pic[]" value="' + title + '"/><br/><a href="#" onclick="deleteImage(\'' + title + '\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
                        editor.hideDialog();
                    }
                });
                setTimeout(function () {
                    $('.ke-dialog').css('top', (($(window).height() - $('.ke-dialog').height()) / 2) - 5 + 'px');
                }, 200);
            });
        });

        K('#J_selectLogo').click(function () {
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic',array('dir'=>'image'))}";
            editor.loadPlugin('image', function () {
                editor.plugin.imageDialog({
                    showRemote: false,
                    clickFn: function (url, title, width, height, border, align) {
                        $('#upload_logo_ul').html('<li class="upload_pic_li"><img src="' + url + '"/><input type="hidden" name="pic_info" value="' + url + '"/></li>');
                        editor.hideDialog();
                    }
                });
                setTimeout(function () {
                    $('.ke-dialog').css('top', (($(window).height() - $('.ke-dialog').height()) / 2) - 5 + 'px');
                }, 200);
            });
        });

        $('#edit_form').submit(function () {
            $.post("{pigcms{:U('Analysis/applySettlement')}", $('#edit_form').serialize(), function (result) {
                if (result.status == 1) {
                    alert(result.info);
                    window.location.href = "{pigcms{:U('Analysis/settlement')}";
                } else {
                    alert(result.info);
                }
            })
            return false;
        });

        $('#indexsort_edit_btn').click(function () {
            $(this).prop('disabled', true).html('{pigcms{:L_("提交中")}...');
            $.post("{pigcms{:U('Config/merchant_indexsort')}", {
                group_indexsort: $('#group_indexsort').val(),
                indexsort_groupid: $('#indexsort_groupid').val()
            }, function (result) {
                alert('{pigcms{:L_("处理完成！正在刷新页面。")}');
                window.location.href = window.location.href;
            });
        });
    });

    function deleteImage(path, obj) {
        $.post("{pigcms{:U('Config/ajax_del_pic')}", {path: path});
        $(obj).closest('.upload_pic_li').remove();
    }
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    var loop_time = null, art_index = null;
    $(function () {
        $('.see_qrcode').click(function () {
            art.dialog.open($(this).attr('href'), {
                init: function () {
                    loop_time = setInterval(is_bind, 1500);
                    var iframe = this.iframe.contentWindow;
                    window.top.art.dialog.data('iframe_handle', iframe);
                },
                id: 'handle',
                title: '{pigcms{:L_("绑定管理账号的二维码")}',
                padding: 0,
                width: 430,
                height: 433,
                lock: true,
                resize: false,
                background: 'black',
                button: null,
                fixed: false,
                close: function () {
                    clearInterval(loop_time);
                },
                left: '50%',
                top: '38.2%',
                opacity: '0.4'
            });
            return false;
        });

        $('#delete_bind').click(function () {
            art.dialog.confirm('{pigcms{:L_("你确定要解除绑定的用户吗？")}', function () {
                $.get("{pigcms{:U('Config/ajax_del_binduser')}", function (response) {
                    if (response.error_code) {
                        art.dialog.tips(response.msg);
                    } else {
                        $('#has_user').hide();
                        $('#no_user').show();
                    }
                }, 'json');
            }, function () {
            });
        });
    });
    var countdown = 60;

    function sendsms(val) {

        var phone = $("input[name='old_phone']").val();

        if (phone == '') {
            alert('{pigcms{:L_("手机号码不能为空！")}');
        } else {


            if (countdown == 60) {
                $.ajax({
                    url: '{pigcms{:U('sendsms')}',
                    type: 'POST',
                    dataType: 'json',
                    data: {phone: phone},
                    success: function (date) {
                        if (date.status == 0) {
                            // alert(date.info)
                        }
                    }

                });
            }
            if (countdown == 0) {
                val.removeAttribute("disabled");
                $(val).html("{pigcms{:L_("
                验证短信
                ")}"
            )
                ;
                countdown = 60;
                //clearTimeout(t);
            } else {
                val.setAttribute("disabled", true);
                $(val).html("{pigcms{:L_("
                重新发送
                ")}(" + countdown + ")"
            )
                ;
                countdown--;
                setTimeout(function () {
                    sendsms(val);
                }, 1000)
            }
        }
    }

    var countdown2 = 60;

    function sendsms2(val) {


        var phone = $("input[name='new_phone']").val()


        if (phone == '') {
            alert('{pigcms{:L_("手机号码不能为空！")}');
        } else {


            if (countdown2 == 60) {
                $.ajax({
                    url: '{pigcms{:U('sendsms2')}',
                    type: 'POST',
                    dataType: 'json',
                    data: {newphone: phone},
                    success: function (date) {
                        if (date.status == 0) {
                            // alert(date.info)
                        }
                    }

                });
            }
            if (countdown2 == 0) {
                val.removeAttribute("disabled");
                $(val).html("{pigcms{:L_("
                验证短信
                ")}"
            )
                ;
                countdown2 = 60;
                //clearTimeout(t);
            } else {
                val.setAttribute("disabled", true);
                $(val).html("{pigcms{:L_("
                重新发送
                ")}(" + countdown2 + ")"
            )
                ;
                countdown2--;
                setTimeout(function () {
                    sendsms2(val);
                }, 1000)
            }
        }
    }

    function is_bind() {
        $.get("{pigcms{:U('Config/has_bind')}", function (response) {
            if (response.error_code == false) {
                $('#bind_usernickname').html(response.nickname);
                $('#has_user').show();
                $('#no_user').hide();
                clearInterval(loop_time);
                art.dialog.list['handle'].close();
            }
        }, 'json');
    }
</script>
<include file="Public:footer"/>

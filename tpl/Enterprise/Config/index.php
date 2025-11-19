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
                                <a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本设置")}</a>
                            </li>
                            <li>
                                <a data-toggle="tab" href="#txtpwd">{pigcms{:L_("修改密码")}</a>
                            </li>
                            <li>
                                <a data-toggle="tab" href="#txtPhone">{pigcms{:L_("修改手机")}</a>
                            </li>
                            <li>
                                <a data-toggle="tab" href="#bindUser">{pigcms{:L_("管理员列表")}</a>
                            </li>
                        </ul>
                    </div>
                    <form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("企业ID")}</label></label>
                                    <span style="line-height:32px;color:#939192;">{pigcms{$qiyeinfo.qiye_id}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("企业账号")}</label></label>
                                    <span style="line-height:32px;color:#939192;">{pigcms{$qiyeinfo.account}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("企业名称")}</label></label>
                                    <span style="line-height:32px;color:#939192;">{pigcms{$qiyeinfo.name}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("消费方式")}</label></label>
                                    <span style="line-height:32px;color:#939192;">
                                        <if condition="$qiyeinfo['settlement_type'] eq 1">
                                            先消费后结算
                                        <elseif condition="$qiyeinfo['settlement_type'] eq 2"/>
                                            先结算后消费
                                        </if>
                                    </span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("企业简介")}</label></label>
                                    <input class="col-sm-2" size="30" name="intro" value="{pigcms{$qiyeinfo.intro}" id="s_txt" type="text"/>
                                    <span class="form_tips">{pigcms{:L_("请填写简洁有力的介绍，限30字")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("企业邮箱")}</label></label>
                                    <input class="col-sm-2" size="20" name="email" value="{pigcms{$qiyeinfo.email}"
                                           id="email" type="text"/>
                                    <span class="form_tips">{pigcms{:L_("可选，建议填写")}</span>
                                </div>


                                <div class="form-group" style="display:block;">
                                    <label class="col-sm-1">{pigcms{:L_("企业")}LOGO</label>
                                    <a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectLogo">{pigcms{:L_("上传图片")}</a>
                                </div>
                                <div class="form-group" style="display:block;">
                                    <label class="col-sm-1">LOGO{pigcms{:L_("预览")}</label>
                                    <div id="upload_pic_box">
                                        <ul id="upload_logo_ul">
                                            <if condition="$qiyeinfo['logo']">
                                                <li class="upload_pic_li">
                                                    <img src="{pigcms{$qiyeinfo.logo|replace_file_domain=###}"/>
                                                    <input type="hidden" name="logo"
                                                           value="{pigcms{$now_merchant.logo}"/>
                                                </li>
                                            </if>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div id="txtpwd" class="tab-pane">
                                <h4>修改登录密码</h4>
                                <!--<div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("原密码")}</label></label>
                                    <input class="col-sm-2" size="20" name="old_pass" type="password"/>
                                </div>-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("新密码")}</label></label>
                                    <input class="col-sm-2" size="20" name="new_pass" type="password"/>
                                    <span class="form_tips">{pigcms{:L_("不修改密码请留空，最少6个字符")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("确认密码")}</label></label>
                                    <input class="col-sm-2" size="20" name="re_pass" type="password"/>
                                </div>
                                <h4>修改操作密码</h4>
                                <!--<if condition="$qiyeinfo['operate_pwd'] NEQ ''">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("原密码")}</label></label>
                                    <input class="col-sm-2" size="20" name="old_operate_pass" type="password"/>
                                </div>
                                </if>-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("新密码")}</label></label>
                                    <input class="col-sm-2" size="20" name="new_operate_pass" type="password"/>
                                    <span class="form_tips">{pigcms{:L_("不修改密码请留空，最少6个字符")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("确认密码")}</label></label>
                                    <input class="col-sm-2" size="20" name="re_operate_pass" type="password"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("验证码")}</label></label>
                                    <input class="col-sm-2" size="20" name="qiye_sms_code" type="text" value=""/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <input name="qiye_phone" type="hidden" value="{pigcms{$qiyeinfo['phone']}"/>
                                    <a href="javascript:void(0)" onclick="sendsms(this,0)" class="btn btn-sm btn-success">{pigcms{:L_("发送验证码至").$qiyeinfo['phone']}</a>
                                </div>
                            </div>


                            <div id="txtPhone" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("原手机")}</label></label>
                                    <input class="col-sm-2" size="20" readonly="true" name="origin_phone" type="text" value="{pigcms{$qiyeinfo.phone}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("验证码")}</label></label>
                                    <input class="col-sm-2" size="20" name="origin_phone_code" type="text"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <a href="javascript:void(0)" onclick="sendsms(this,1)" class="btn btn-sm btn-success">{pigcms{:L_("发送验证码")}</a>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("新手机")}</label></label>
                                    <input class="col-sm-2" size="20" name="new_phone" type="text" value=""/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("验证码")}</label></label>
                                    <input class="col-sm-2" size="20" name="phone_code" type="text"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <a href="javascript:void(0)" onclick="sendsms2(this)" class="btn btn-sm btn-success">{pigcms{:L_("发送验证码")}</a>
                                </div>
                            </div>

                            <div id="bindUser" class="tab-pane">

                            </div>

                        </div>


                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9" style="margin-left: 0px">
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
            editor.uploadJson = "http://www.group.com/merchant.php?g=Merchant&c=Config&a=ajax_upload_pic&dir=image')}";
            editor.loadPlugin('image', function () {
                editor.plugin.imageDialog({
                    showRemote: false,
                    clickFn: function (url, title, width, height, border, align) {
                        $('#upload_logo_ul').html('<li class="upload_pic_li"><img src="' + url + '"/><input type="hidden" name="logo" value="' + url + '"/></li>');
                        editor.hideDialog();
                    }
                });
                setTimeout(function () {
                    $('.ke-dialog').css('top', (($(window).height() - $('.ke-dialog').height()) / 2) - 5 + 'px');
                }, 200);
            });
        });

        $('#edit_form').submit(function () {
            $.post("{pigcms{:U('Config/index')}", $('#edit_form').serialize(), function (result) {
                if (result.status == 1) {
                    alert(result.info);
                    window.location.href = "{pigcms{:U('Config/index')}";
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

    function sendsms(val,check_new_phone = 0) {

        var phone = $("input[name='qiye_phone']").val();
        if (check_new_phone == 1 ) {
            var new_phone = $("input[name='new_phone']").val();
            if(new_phone == ''){
                alert('{pigcms{:L_("新手机不能为空！")}');
                return false;
            }
            /*if(new_phone == phone){
                alert('{pigcms{:L_("新手机与原手机一样，无需修改！")}');
                return false;
            }*/
        }

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
                $(val).html("验证短信");
                countdown = 60;
                //clearTimeout(t);
            } else {
                val.setAttribute("disabled", true);
                $(val).html("重新发送(" + countdown + ")");
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
                    data: {newphone: phone,type:3},
                    success: function (date) {
                        if (date.status == 0) {
                            // alert(date.info)
                        }
                    }

                });
            }
            if (countdown2 == 0) {
                val.removeAttribute("disabled");
                $(val).html("验证短信");
                countdown2 = 60;
                //clearTimeout(t);
            } else {
                val.setAttribute("disabled", true);
                $(val).html("重新发送(" + countdown2 + ")");
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

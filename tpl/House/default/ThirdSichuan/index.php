<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('ThirdSichuan/index')}">四川人口信息</a>
            </li>
            <li class="active">配置</li>
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
                        <if condition="$chinapopinOpen==1">
                            <li class="active">
                                <a href="#basicinfo" data-toggle="tab">基本设置</a>
                            </li>
                            <else/>
                            <li class="active">
                                <a href="#basicinfo" data-toggle="tab">基本设置</a>
                            </li>
                            <li>
                                <a href="{pigcms{:U('ThirdSichuan/upload_file')}">文件上传</a>
                            </li>
                            <li>
                                <a href="{pigcms{:U('ThirdSichuan/handle_third_user_list')}">上传记录</a>
                            </li>
                            <li>
                                <a href="{pigcms{:U('ThirdSichuan/moveConfig')}">迁移配置</a>
                            </li>
                            <li>
                                <a href="{pigcms{:U('ThirdSichuan/moveUploadFile')}">迁移文件上传</a>
                            </li>

                        </if>
                    </ul>
                </div>
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('ThirdSichuan/edit')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <if condition="$chinapopinOpen==1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="has_property_pay">是否开启</label></label>
                                        <div class="radio">
                                            <label style="padding-left:0px;padding-right:20px;"><input name="status" value="1" type="radio" class="ace" <if condition="$third_config.status eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                            <label style="padding-left:0px;"><input name="status" value="0" type="radio" class="ace" <if condition="$third_config.status eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="village_num">小区编码</label></label>
                                        <input class="col-sm-3" name="village_num" id="village_num" value="{pigcms{$third_config.village_num}" type="text" placeholder="请填写省厅小区编号"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="clientId">clientId</label></label>
                                        <input class="col-sm-3" name="clientId" id="clientId" value="{pigcms{$third_config.clientId}" type="text" placeholder="请填写clientId"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="clientSecret">clientSecret</label></label>
                                        <input class="col-sm-3" name="clientSecret" id="clientSecret" value="{pigcms{$third_config.clientSecret}" type="text" placeholder="请填写clientSecret"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="encrypted_public_key">RSA加密公钥</label></label>
                                        <input class="col-sm-3" name="encrypted_public_key" id="encrypted_public_key" value="{pigcms{$third_config.encrypted_public_key}" type="text" placeholder="请填写RSA加密公钥"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label>下发人员接收接口</label></label>
                                        <input class="col-sm-5" value="{pigcms{$third_config['addr']}" type="text" readonly/>
                                        <br/><br/>
                                        <span class="form_tips" style="margin-left:1px;">下发人员接收接口为小区业务系统实现，用于接收通过小区服务二维码扫码登记到“智慧平安小区3.0”的人员信息</span>
                                    </div>
                                <else/>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_property_pay">是否开启</label></label>
                                    <div class="radio">
                                        <label style="padding-left:0px;padding-right:20px;"><input name="status" value="1" type="radio" class="ace" <if condition="$third_config.status eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                        <label style="padding-left:0px;"><input name="status" value="0" type="radio" class="ace" <if condition="$third_config.status eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="village_num">省厅小区编码</label></label>
                                    <input class="col-sm-3" name="village_num" id="village_num" value="{pigcms{$third_config.village_num}" type="text" placeholder="请填写省厅小区编号"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="city_village_num">市级小区编码</label></label>
                                    <input class="col-sm-3" name="city_village_num" id="city_village_num" value="{pigcms{$third_config.city_village_num}" type="text" placeholder="请填写市级小区编号"/>
                                    <span class="form_tips" style="margin-left:10px;">若对接绵阳市级平台，请填写市级小区编码。若对接省厅则无需填写。</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="username">账号</label></label>
                                    <input class="col-sm-3" name="username" id="username" value="{pigcms{$third_config.username}" type="text" placeholder="请填写账号"/>
                                    <span class="form_tips" style="margin-left:10px;">若对接绵阳市级平台请填写市级参数。若对接省厅则填写省厅参数。</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="password">密码</label></label>
                                    <input class="col-sm-3" name="password" id="password" value="{pigcms{$third_config.password}" type="text" placeholder="请填写密码"/>
                                    <span class="form_tips" style="margin-left:10px;">若对接绵阳市级平台请填写市级参数。若对接省厅则填写省厅参数。</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="clientId">clientId</label></label>
                                    <input class="col-sm-3" name="clientId" id="clientId" value="{pigcms{$third_config.clientId}" type="text" placeholder="请填写clientId"/>
                                    <span class="form_tips" style="margin-left:10px;">若对接绵阳市级平台请填写市级参数。若对接省厅则填写省厅参数。</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="clientSecret">clientSecret</label></label>
                                    <input class="col-sm-3" name="clientSecret" id="clientSecret" value="{pigcms{$third_config.clientSecret}" type="text" placeholder="请填写clientSecret"/>
                                    <span class="form_tips" style="margin-left:10px;">若对接绵阳市级平台请填写市级参数。若对接省厅则填写省厅参数。</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="encrypted_public_key">RSA加密公钥</label></label>
                                    <input class="col-sm-3" name="encrypted_public_key" id="encrypted_public_key" value="{pigcms{$third_config.encrypted_public_key}" type="text" placeholder="请填写RSA加密公钥"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="face_url">人脸登记网址</label></label>
                                    <input class="col-sm-5" name="face_url" id="face_url" value="{pigcms{$third_config.face_url}" type="text" placeholder="请复制四川人口网的登记网址"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>相片接收接口</label></label>
                                    <input class="col-sm-5" value="{pigcms{$third_config['addr']}" type="text" readonly/>
                                    <br/><br/>
                                    <span class="form_tips" style="margin-left:1px;">相片接收接口为小区业务系统实现，用于接收通过标准地址二维码扫码登记到AI能力层的人员信息和照片,接口通讯采用HTTPS方式，需要固定IP</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>IP地址</label></label>
                                    <input class="col-sm-3" value="{pigcms{$third_config['ip']}" type="text" readonly/>
                                </div>                            
                            </if>
                        </div>
                        <input type="hidden" name="third_id" value="{pigcms{$third_config.third_id}" id="third_id" />
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

<style>
    .form-group>label{font-size:12px;line-height:24px;}
</style>

<script type="text/javascript">
    var lock = 0, test;
    $('#edit_form').submit(function(){
        var village_num = $("#village_num").val();
        if(village_num == ''){
            alert('请填写小区编码');
            return false;
        }
        var username = $("#username").val();
        if(username == ''){
            alert('请填写账号');
            return false;
        }
        var password = $("#password").val();
        if(password == ''){
            alert('请填写密码');
            return false;
        }
        var clientId = $("#clientId").val();
        if(clientId == ''){
            alert('请填写clientId');
            return false;
        }
        var clientSecret = $("#clientSecret").val();
        if(clientSecret == ''){
            alert('请填写clientSecret！');
            return false;
        }
        var encrypted_public_key = $("#encrypted_public_key").val();
        if(encrypted_public_key == ''){
            alert('请填写RSA加密公钥！');
            return false;
        }
        var face_url = $("#face_url").val();
        if(face_url == ''){
            alert('请填写人脸登记网址！');
            return false;
        }

        $('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
        if (lock==1) {
            return;
        }
        lock = 1;

        $.post("{pigcms{:U('ThirdSichuan/edit')}",$('#edit_form').serialize(),function(result){
            lock = 0;
            if(result.status == 1){
                alert(result.info);
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
            }else{
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                alert(result.info);
            }
        })
        return false;
    });

</script>

<include file="Public:footer"/>
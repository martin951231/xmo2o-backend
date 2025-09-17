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
                            <li>
                                <a href="{pigcms{:U('ThirdSichuan/index')}">基本设置</a>
                            </li>
                            <li  class="active">
                                <a href="#basicinfo" data-toggle="tab">文件上传</a>
                            </li>
                            <else/>
                            <li>
                                <a href="{pigcms{:U('ThirdSichuan/index')}">基本设置</a>
                            </li>
                            <li  class="active">
                                <a href="#basicinfo" data-toggle="tab">文件上传</a>
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
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('ThirdSichuan/upload_file')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <div class="form-group">
                                <label class="col-sm-1"><label for="rar_file">同步文件上传</label></label>
                                <input type="file" class="input fl" name="rar_file" id="rar_file" style="width:200px;height:35px;" placeholder="选择上传图片" validate="required:true"/>
                                <span class="form_tips">此处上传从四川人口网获取的信息rar压缩文件，上传后信息即开始同步，同步过程中请勿关闭网页</span>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button type="submit" class="btn btn-info">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </button>
                        </div>
                    </div>
                </form>
                </div>
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
        var rar_file = $("#rar_file").val();
        console.log('rar_file', rar_file);
        if(rar_file == ''){
            alert('请填写选择文件');
            return false;
        }
        $('#edit_form button[type="submit"]').prop('disabled',true).html('上传同步中...');
        if (lock==1) {
            return false;
        }
        lock = 1;
    });

</script>

<include file="Public:footer"/>
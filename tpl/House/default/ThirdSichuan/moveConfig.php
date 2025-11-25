<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('ThirdSichuan/index')}">四川人口信息</a>
            </li>
            <li class="active">迁移配置</li>
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
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/index')}">基本设置</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/upload_file')}">文件上传</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/handle_third_user_list')}">上传记录</a>
                        </li>
                        <li class="active">
                            <a href="#basicinfo" data-toggle="tab">迁移配置</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/moveUploadFile')}">迁移文件上传</a>
                        </li>
                    </ul>
                </div>
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('ThirdSichuan/moveConfig')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="provincialStandardAddress">省厅标准5段式地址 </label></label>
                                <input class="col-sm-3" name="provincialStandardAddress" id="provincialStandardAddress" value="{pigcms{$third_config.provincialStandardAddress}" type="text" placeholder="请填写省厅标准5段式地址"/>
                                <span class="form_tips">必填，举例：四川省绵阳市三台县北坝镇耀森路371号</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="judgeStandardAddress">标准后2段式地址 </label></label>
                                <input class="col-sm-3" name="judgeStandardAddress" id="judgeStandardAddress" value="{pigcms{$third_config.judgeStandardAddress}" type="text" placeholder="请填写标准后2段式地址"/>
                                <span class="form_tips">必填，(镇路号)举例：北坝镇耀森路371号</span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="reconstructedCommunity"></label></label>
                                <button id="reconstructedCommunity" type="button" class="btn btn-info">点击导出改建小区初始化包</button>
                            </div>
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
        var provincialStandardAddress = $("#provincialStandardAddress").val();
        if(provincialStandardAddress == ''){
            alert('请填写省厅标准5段式地址');
            return false;
        }
        $('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
        if (lock==1) {
            return;
        }
        lock = 1;
        $.post("{pigcms{:U('ThirdSichuan/moveConfig')}",$('#edit_form').serialize(),function(result){
            console.log('result',result);
            lock = 0;
            if(result.status == 1){
                alert(result.info);
                window.location.reload();
            }else{
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                alert(result.info);
            }
        })
        return false;
    });
    $('#reconstructedCommunity').click(function (){
        $('#reconstructedCommunity').prop('disabled',true).html('导出中...');
        if (lock==1) {
            return;
        }
        lock = 1;
        $.post("{pigcms{:U('ThirdSichuan/userDataProvincialToMunicipal')}",$('#edit_form').serialize(),function(result){
            lock = 0;
            console.log('result',result);
            if(result.status == 1 && result.info.zip_url){
                window.location.href=result.info.zip_url;
                $('#reconstructedCommunity').prop('disabled',false).html('点击导出改建小区初始化包');
            }else{
                $('#reconstructedCommunity').prop('disabled',false).html('点击导出改建小区初始化包');
                alert(result.info);
            }
        })
        return false;
    })

</script>

<include file="Public:footer"/>
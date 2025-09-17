<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('ThirdSichuan/index')}">第三方接入</a>
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
                        <li class="active">
                            <a href="#basicinfo" data-toggle="tab">配置项</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdZatao/handle_third_user_list')}">同步记录</a>
                        </li>
                    </ul>
                </div>
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('ThirdZatao/edit')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
							<div class="form-group">
                                <label class="col-sm-1"><label for="village_num">组织编码:</label></label>
                                <input class="col-sm-3" name="orgCode" id="orgCode" value="{pigcms{$third_config.orgCode}" type="text" placeholder="请填写组织编码"/>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="clientId">客户ID:</label></label>
                                <input class="col-sm-3" name="clientId" id="clientId" value="{pigcms{$third_config.clientId}" type="text" placeholder="请填写客户ID"/>
                            </div>
                        <input type="hidden" name="third_id" value="{pigcms{$third_config.id}" id="third_id" />
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
        var orgCode = $("#orgCode").val();
        if(orgCode == ''){
            alert('请填写组织编码');
            return false;
        }
        var clientId = $("#clientId").val();
        if(clientId == ''){
            alert('请填写客户ID');
            return false;
        }

        $('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
        if (lock==1) {
            return;
        }
        lock = 1;

        $.post("{pigcms{:U('ThirdZatao/edit')}",$('#edit_form').serialize(),function(result){
            lock = 0;
            if(result.status == 1){
                alert(result.info);
                location.reload();
            }else{
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                alert(result.info);
            }
        })
        return false;
    });

</script>

<include file="Public:footer"/>
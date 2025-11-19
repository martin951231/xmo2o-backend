<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
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
<!-- 内容头部 -->
<!-- 内容头部 -->
<div class="page-content">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
            }
        </style>
        <div class="row">
            <form class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
                <div class="col-xs-12" id="orderCheckControl">
                    <input id="pigcms_id" type="hidden" value="{pigcms{$pigcms_id}">
                    <div class="form-group" style="margin-left: 30px;margin-top: 20px">
                        <label class="col-sm-2"><label for="contract_time"><span style="color: red">*</span>修改服务时间</label></label>
                        <input type="text" id="service_start_time" class="input-text" value="{pigcms{$village_about_info.service_start_time}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="服务开始时间"/>
                        &nbsp;—&nbsp;
                        <input type="text" id="service_end_time" class="input-text" value="{pigcms{$village_about_info.service_end_time}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="服务结束时间"/>&nbsp;
                    </div>
                    <div class="form-group" style="margin-left: 30px;margin-top: 20px">
                        <label class="col-sm-2"><label for="contract_time"><span style="color: red">*</span>申请原因</label></label>
                        <textarea class="col-sm-2" style="height: 100px;width: 362px" id="reply_reason" placeholder="请填写申请原因">{pigcms{$village_about_info['remark']}</textarea>
                    </div>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-info" type="submit">
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

<script type="text/javascript">
    //保存数据设置
    var isposting=false;
    function check_submit(){
        if(isposting){
            layer.alert('正在保存数据，请稍等...');
            return false;
        }
        var postArr={};
        var findnoselect=false;
        if(findnoselect){
            isposting=false;
            return false;
        }

        postArr.service_start_time=$('#service_start_time').val();
        postArr.service_end_time=$('#service_end_time').val();
        postArr.reply_reason = $('#reply_reason').val();
        postArr.pigcms_id = $('#pigcms_id').val();
        if(postArr.service_start_time == '' || postArr.service_end_time==''){
            layer.alert('请填写物业服务时间');
            return false;
        }
        if(postArr.reply_reason == ''){
            layer.alert('请填写申请修改原因');
            return false;
        }
        isposting=true;
        $.post("{pigcms{:U('User/update_service_time')}",postArr,function(result){
            isposting=false;
            if(result.status == 1){
                layer.open({
                    content: '操作成功！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.open({
                    content: result.info,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        //parent.location.reload();
                         layer.close(index);
                    }
                })
            }
        })
        return false;
    }
</script>

<include file="Public:footer"/>
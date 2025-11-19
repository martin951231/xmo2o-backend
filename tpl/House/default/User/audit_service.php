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
                    <div class="form-group" id="status" style="margin-left: 30px;margin-top: 20px">
                        <label class="col-sm-2"><label for="contract_time"><span style="color: red">*</span>审核状态</label></label>
                        <label style="padding-left:0px;padding-right:20px;"><input name="status" value="3" type="radio" class="ace " checked /><span class="lbl" style="z-index: 1"> 不通过 </span></label>
                        <label style="padding-left:0px;"><input name="status" value="2" type="radio" class="ace" /><span class="lbl" style="z-index: 1"> 通过 </span></label>
                        <span class="form_tips"></span>
                    </div>
                    <div class="form-group" style="margin-left: 30px;margin-top: 20px">
                        <label class="col-sm-2"><label for="contract_time"><span style="color: red">*</span>审核说明</label></label>
                        <textarea class="col-sm-2" style="height: 100px;width: 362px" id="bak" placeholder="请输入审核说明"></textarea>
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

        postArr.status=$('#status input:checked').val();
        postArr.bak=$('#bak').val();
        postArr.pigcms_id = $('#pigcms_id').val();
        if(postArr.status == 3 && postArr.bak == ''){
            layer.alert('请填写不通过原因');
            return false;
        }
        isposting=true;
        $.post("{pigcms{:U('User/audit_service')}",postArr,function(result){
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
                        parent.location.reload();
                    }
                })
            }
        })
        return false;
    }
</script>

<include file="Public:footer"/>
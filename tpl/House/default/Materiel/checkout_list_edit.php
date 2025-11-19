<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <form  id="add_info" class="form-horizontal" method="post" action="__SELF__">
                <input  name="id" type="hidden"  value="{pigcms{$_GET['id']}"/>
                <input  name="type_id" type="hidden"  value="{pigcms{$_GET['type_id']}"/>

                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-1"><label for="id">序号&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;：</label></label>
                        <input class="col-sm-2" size="20" type="text" value="{pigcms{$msg['id']}" readonly/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="id">物料类别：</label></label>
                        <input class="col-sm-2" size="20" type="text" value="{pigcms{$type_msg['type_name']}" readonly/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="id">领用人&nbsp;&nbsp;&nbsp;：</label></label>
                        <input class="col-sm-2" size="20" type="text" name="receive_name" value="{pigcms{$msg['receive_name']}" />
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="id">联系方式：</label></label>
                        <input class="col-sm-2" size="20" type="text" name="receive_phone" value="{pigcms{$msg['receive_phone']}" />
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="id">领用时间：</label></label>
       
                         <input type="text" name="receive_time" class="input-text" value="{pigcms{$msg['receive_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="请选择领取时间"/>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-2 col-sm-2 col-md-1 col-lg-1"><label for="status">状态</label></label>
                        <label><input name="status" onclick="change_status(0)" type="radio" value="0" <if condition="$msg.status eq 0">checked</if> />&nbsp;&nbsp;已出库</label>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <label><input name="status" onclick="change_status(1)" type="radio" value="1" <if condition="$msg.status eq 1">checked</if> />&nbsp;&nbsp;报废</label>
                    </div>

                    <div class="form-group scrap_reason_box" <if condition="$msg['status'] neq 1">style="display:none;"</if>>
                        <label class="col-sm-1"><label for="scrap_reason">报废原因</label></label>
                        <textarea name="scrap_reason" id="scrap_reason"  style="width:300px; height:80px">{pigcms{$msg.scrap_reason}</textarea>
                    </div>

                </div>
                <div class="space"></div>
                <div class="clearfix form-actions">
                    <div class="col-md-offset-3 col-md-9">
                        <button class="btn btn-info" type="submit">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                            保存
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">

    $(document).ready(function (){


        $('#add_info').submit(function(){
            $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
                console.log('result', result);
                if (result && result.status==1) {
                    layer.open({
                        content: '操作成功！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.parent.close_all_page(result);
                        }
                    })
                } else if (result.info) {
                    layer.open({
                        content: result.info,
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['知道了']
                    })
                } else {
                    layer.open({content: '添加失败'});
                }
            });
            return false;
        });

    });

    function change_status(status) {
        if (0==status) {
            $('.scrap_reason_box').hide();
        } else {
            $('.scrap_reason_box').show();
        }
    }

    $('#receive_id').change(function(){

    })
</script>
<include file="Publics:footer"/>
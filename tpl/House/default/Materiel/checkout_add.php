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
                        <label class="col-sm-1"><label for="receive_id">领用人：</label></label>
                        <select class="col-sm-2" name="receive_id" id="receive_id" >
                            <option style="display: none;">请选择领用人</option>
                            <volist name="worker_list" id="worker">
                                <option value="{pigcms{$worker['wid']}">{pigcms{$worker['name']}</option>
                            </volist>
                        </select>
                        <volist name="worker_list" id="worker">
                            <input type="hidden" class="worker_name_{pigcms{$worker['wid']}" value="{pigcms{$worker['name']}"/>
                            <input type="hidden" class="worker_phone_{pigcms{$worker['wid']}" value="{pigcms{$worker['phone']}"/>
                        </volist>
                        <label><span class="red">*必选</span></label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="receive_phone">联系方式：</label></label>
                        <input class="col-sm-2" size="20" name="receive_phone" id="receive_phone" placeholder="请填写联系方式" type="text" value="{pigcms{$msg['receive_phone']}"/>
                        <input class="col-sm-2" size="20" name="receive_name" id="receive_name" type="hidden" value="{pigcms{$msg['receive_name']}"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="receive_num">领取数量：</label></label>
                        <input class="col-sm-2" size="20" name="receive_num" id="receive_num" placeholder="请填写大于0的领取数量" type="text" value="{pigcms{$msg['receive_num']}"/>
                        <label><span class="red">*必填</span></label>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="receive_time">领取时间：</label></label>
                        <input type="text" name="receive_time" class="input-text" value="{pigcms{$msg['receive_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="请选择领取时间"/>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-1"><label for="remark">备注</label></label>
                        <textarea name="remark" id="remark"  style="width:300px; height:80px">{pigcms{$msg.remark}</textarea>
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


    $('#receive_id').change(function(){
        var val = $(this).val();
        var name = $('.worker_name_'+val).val();
        var phone = $('.worker_phone_'+val).val();
        console.log('val',val);
        console.log('name',name);
        console.log('phone',phone);
        $('#receive_phone').val(phone);
        $('#receive_name').val(name);
    })
</script>
<include file="Publics:footer"/>
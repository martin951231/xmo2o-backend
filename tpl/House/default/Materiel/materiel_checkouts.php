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
                        <input id="typeHiddenValue" type="hidden" value="0" style="display: none;"/>
                        <div style="display: flex;">
                            <label class="col-sm-1"><label for="receive_num">领取数量：</label></label>
                            <div>
                                <div>
                                    <select class="col-sm-2 selectMsgType" name="type[0][type_id]" id="type_id_0" style="height:32px;">
                                        <option value="0" data-key="0">请选择领用物料类型</option>
                                        <volist name="typeMsgList" id="typeMsg">
                                            <option value="{pigcms{$typeMsg['type_id']}" data-key="0">{pigcms{$typeMsg['type_name']}</option>
                                        </volist>
                                    </select>
                                    <input style="height:32px;width: 180px;" class="col-sm-2" name="type[0][receive_num]" id="receive_num_0" placeholder="请填写大于0的领取数量" type="number"/>
                                </div>
                                <div style="margin-top: 2px;">
                                    <textarea name="type[0][remark]" id="remark_0" placeholder="请输入备注" style="width:348px; height:45px"></textarea>
                                </div>
                            </div>
                            &nbsp;&nbsp;&nbsp;<a style="line-height: 8px;" class="btn btn-success" onclick="typeBoxAdd()">添加</a>
                        </div>
                        <div class="select-box-type">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="receive_time">领取时间：</label></label>
                        <input type="text" name="receive_time" class="input-text" value="{pigcms{$msg['receive_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="请选择领取时间"/>
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

    var typeMsgListJson = {pigcms{$typeMsgListJson};
    var typeChooseBox = {};

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
    
    $('#type_id_0').change(function (){
        var val = $(this).val();
        if (val && typeChooseBox[val]) {
            layer.alert('不能选择相同的物料[类别]...');
            $('#type_id_0').val('')
            return false;
        }
        var length = $(".selectMsgType").length;
        for(var i=0;i<length;i++){
            var lenVal = $(".selectMsgType")[i].value;
            if (lenVal) {
                typeChooseBox[lenVal] = 1;
            }
        }
        console.log('typeChooseBox',typeChooseBox);
        console.log('length_0',length);
        var typeHiddenValue = $(this).find("option:selected").attr('data-key');
        console.log('typeHiddenValue',typeHiddenValue);
    })

    function typeBoxAdd() {
        var typeHiddenValue = parseInt($('#typeHiddenValue').val()) + 1;
        console.log('typeHiddenValue',typeHiddenValue);
        var typeHtml ='<div style="display: flex;">';
        typeHtml +=     '<div style="padding: 5px 0 0 95px;" class="typeBox'+typeHiddenValue+'">';
        typeHtml +=       '<div>';
        typeHtml +=          '<select class="col-sm-2 selectMsgType" name="type['+typeHiddenValue+'][type_id]" id="type_id_'+typeHiddenValue+'" style="height:32px;" data-key="">';
        typeHtml +=             '<option value="0" data-key="0">请选择领用物料类型</option>';
        if(typeMsgListJson && typeMsgListJson.length>0){
            for(var ii=0;ii<typeMsgListJson.length;ii++){
                typeHtml +=      '<option value="'+typeMsgListJson[ii].type_id+'" data-key="'+typeHiddenValue+'">'+typeMsgListJson[ii].type_name+'</option>';
            }
        }
        typeHtml +=           '</select>';
        typeHtml +=           '<input style="height:32px;width: 180px;" class="col-sm-2" name="type['+typeHiddenValue+'][receive_num]" id="receive_num_'+typeHiddenValue+'" placeholder="请填写大于0的领取数量" type="number"/>';
        typeHtml +=       '</div>';
        typeHtml +=       '<div style="margin-top: 2px;">'
        typeHtml +=         '<textarea name="type['+typeHiddenValue+'][remark]" id="remark_'+typeHiddenValue+'" placeholder="请输入备注" style="width:348px; height:45px"></textarea>';
        typeHtml +=       '</div>';
        typeHtml +=    '</div>';
        typeHtml +=     '&nbsp;&nbsp;<a style="line-height: 8px;margin-top: 6px;" class="btn btn-danger" onclick="typeBoxDelete('+typeHiddenValue+')">删除</a>';
        typeHtml += '</div>';
        $('.select-box-type').append(typeHtml);
        $('#typeHiddenValue').val(typeHiddenValue)

        $('#type_id_' + typeHiddenValue).change(function (){
            var val = $(this).val();
            var typeHiddenValue = $(this).find("option:selected").attr('data-key');
            if (val && typeChooseBox[val]) {
                layer.alert('不能选择相同的物料[类别]...');
                $('#type_id_' + typeHiddenValue).val('')
                return false;
            }
            console.log('type_id_' + typeHiddenValue,val);
            console.log('typeHiddenValue' + typeHiddenValue,typeHiddenValue);
            var length = $(".selectMsgType").length;
            for(var i=0;i<length;i++){
                var lenVal = $(".selectMsgType")[i].value;
                if (lenVal) {
                    typeChooseBox[lenVal] = 1;
                }
            }
            console.log('length_' + typeHiddenValue,length);
        })
    }
    function typeBoxDelete(typeHiddenValue) {
        $('.typeBox' + typeHiddenValue).remove();    
    }
</script>
<include file="Publics:footer"/>
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
                <input  name="checkout_list_id" type="hidden"  value="{pigcms{$_GET['checkout_list_id']}"/>

                <div class="tab-content">
                    <div class="form-group">
                        <label class="col-sm-1"><label for="repair_time">维修时间：</label></label>
                        <input type="text" name="repair_time" class="input-text" value="{pigcms{$msg['repair_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" placeholder="请选择维修时间"/>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1"><label for="repair_money">维修费用：</label></label>
                        <input class="col-sm-2" size="20" name="repair_money" id="repair_money" placeholder="请填写大于0的维修费用" type="text" value="{pigcms{$msg['repair_money']}"/>
                        <label><span class="red">*必填</span></label>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-1"><label for="remark">备注</label></label>
                        <textarea name="remark" id="remark"  style="width:300px; height:80px">{pigcms{$msg.remark}</textarea>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-1">附件</label>
                        <a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">上传图片</a>
                        最多上传5张
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1">图片预览</label>
                        <div id="upload_pic_box">
                            <ul id="upload_pic_ul">
                                <volist name="msg['appendix_file']" id="pic">
                                    <li class="upload_pic_li">
                                        <img src="{pigcms{:replace_file_domain($pic)}">
                                        <input type="hidden" value="{pigcms{$pic}" name="pic[]"><br>
                                        <a onclick="deleteImg('{pigcms{$pic}',this);return false;" href="#">[ 删除 ]</a>
                                    </li>
                                </volist>
                            </ul>
                        </div>
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

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">


    function deleteImg(path,obj){
        $.post("{pigcms{:U('ajax_del_pic')}",{path:path});
        $(obj).closest('.upload_pic_li').remove();
    }
    $(document).ready(function (){



        KindEditor.ready(function(K){
            var kind_editor = K.editor({
                allowFileManager : true
            });

            K('#J_selectImage').click(function(){

                if($('.upload_pic_li').size() >= 5){
                    alert('最多上传5个图片！');
                    return false;
                }
                kind_editor.uploadJson ="{pigcms{$config.site_url}{pigcms{:U('Materiel/ajax_upload_pic')}";
                kind_editor.loadPlugin('image', function(){
                    kind_editor.plugin.imageDialog({
                        showRemote : false,
                        imageUrl : K('#course_pic').val(),
                        clickFn : function(url, title, width, height, border, align) {
                            console.log('url',url);
                            console.log('title',title);
                            $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImg(\''+title+'\',this);return false;">[ 删除 ]</a></li>');
                            kind_editor.hideDialog();
                        }
                    });
                });
            });
        });


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
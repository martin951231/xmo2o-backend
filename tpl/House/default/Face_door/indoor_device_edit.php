<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content" style="height:408px !important;">
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" method="post">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-1" style="width: 15% !important;"><label for="garage_num">室内机设备号</label></label>
                                <input class="col-sm-5" size="20" name="device_sn" id="device_sn" type="text"  value="{pigcms{$list.device_sn}" maxlength="50"/>
                            </div>
                            <input type="hidden" name="id" value="{pigcms{$list.id}">
                            <div class="form-group">
                                <label class="col-sm-1" style="width: 15% !important;">状态</label>

                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$list["status"] egt 1'>checked="checked"</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$list["status"] eq 0'>checked="checked"</if> name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="space"></div>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9" style="text-align: center">
                            <button class="btn btn-info submit_info" type="button">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                提交
                            </button>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>
<script type="text/javascript">
    $('.submit_info').click(function(){
        var device_sn = $('#device_sn').val(),id = $('input[name=id]').val(),status =$(':radio[name="status"]:checked').val();
        if(!device_sn){
            layer.msg('请输入室内机设备号',{icon:2});
            return false;
        }
        var loading = layer.msg('提交中', {
            icon: 16,
            shade: [0.1, '#000'],
            time: false  // 取消自动关闭
        });
        $.post("{pigcms{:U('indoor_device_edit')}",{'id':id,'device_sn':device_sn,'status':status},function(data){
            if(data.status == 1){
                layer.msg(data.info,{icon: 1,time : 1000},function(){
                    window.parent.location.reload();//刷新父页面
                });
            }
            if(data.status == 0){
                layer.msg(data.info,{icon: 2});
            }
            layer.close(loading);
        },'json');
    })
</script>

<include file="Public:footer"/>
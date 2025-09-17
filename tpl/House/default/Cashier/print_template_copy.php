<include file="Public:header"/>

<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form  class="form-horizontal" method="post">
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="car_number">选择小区：</label></label>
                                    <select id="village_list" class="fl">
                                        <option value="0">请选择</option>
                                        <volist name="house_village" id="vo">
                                            <option value="{pigcms{$vo.village_id}">{pigcms{$vo.village_name}</option>
                                        </volist>
                                    </select>
                                    <span style="line-height: 35px;margin-left: 10px;">仅展示同一个物业下的小区</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="car_number">选择模板：</label></label>
                                    <select id="print_list" class="fl">
                                        <option value="0">请选择</option>
                                    </select>
                                </div>

                            </div>

                        </div>
                        <div class="space"></div>
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <button class="btn btn-info submit_info" type="button">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    增加
                                </button>
                            </div>
                        </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<script type="text/javascript">

    $("#village_list").change(function(){
        var selected=$(this).children('option:selected').val();
        if(selected > 0){
            $.post("{pigcms{:U('print_template_choice')}",{village_id:selected},function(data){
                var str = '',rr=data.data;
               if(data.code == 1){
                   for(var i in rr){
                       str+='<option value="'+rr[i]["template_id"]+'">'+rr[i]["title"]+'</option>';
                   };
               }else{
                   layer.msg(rr,{icon:2});
               }
                $("#print_list").html(str);
            },'json')
        }
    });

    $('.submit_info').click(function(){
        var village_id =$("#village_list").val(),template_id =$("#print_list").val();
        if(template_id < 1){
            layer.msg('请选择模板',{icon:2});
            return false;
        }
        var index = layer.load(1, {shade: [0.1,'#000']});
        $.post("{pigcms{:U('print_template_copy')}",{'village_id':village_id,'template_id':template_id},function(data){
            layer.close(index)
            if(data.code == 0){
                layer.msg(data.data,{icon:2});
            }else{
                layer.msg(data.data,{icon:6});
                setTimeout(function(){ parent.location.reload();},800);

            }
        },'json');
    });
</script>

<include file="Public:footer"/>
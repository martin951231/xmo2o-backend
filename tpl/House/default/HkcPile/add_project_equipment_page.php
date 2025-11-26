<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}layer/css/layui.css"  media="all">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
        margin-top: 30px;
    }
   .tab-content{ border:none;}
</style>
<div class="page-content">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" id="haokuaichong_equipment_form" method="post" onSubmit="return check_submit()">
                    <div class="tab-content">
                        <div id="basicinfo" class="tab-pane active">
                            <div class="form-group">
                                <label class="col-sm-2"><label for="equipment_name">设备名称</label></label>
                                <input class="col-sm-5"  name="equipment_name" id="equipment_name" type="text" value="{pigcms{$equipment['equipment_name']}" required/>
                                <span style="margin-left: 20px;">必填项</span>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="area_id">充电区</label></label>
                                 <select id="area_id" name="area_id" class="col-sm-3" required  <if condition="$equipment AND $equipment['equipment_num']"> disabled="disabled" </if> >
                                     <volist name="haokuaichong_region" id="vo">
                                        <option value="{pigcms{$vo['area_id']}"  <if condition="$vo['area_id'] eq $equipment['area_id'] "> selected="selected" </if> >{pigcms{$vo['area_name']}</option>
                                    </volist>
                                    </select>
                                   &nbsp;&nbsp;&nbsp;<span>请选择设备要添加到的充电区<if condition="$equipment AND $equipment['equipment_num']">（不可修改编辑） </if></span>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2"><label for="equipment_num">16进制设备号</label></label>
                                <input class="col-sm-4"  name="equipment_num" id="equipment_num" type="text" value="{pigcms{$equipment['equipment_num']}" required  <if condition="$equipment AND $equipment['area_id']"> disabled="disabled" </if>  />&nbsp;&nbsp;&nbsp;<span>必填项，请填写16进制设备号<if condition="$equipment AND $equipment['equipment_num']">（不可修改编辑） </if></span>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-2"><label for="equipment_sn">10进制设备号</label></label>
                                <input class="col-sm-4"  name="equipment_sn" id="equipment_sn" type="text" value="{pigcms{$equipment['equipment_sn']}" required  <if condition="$equipment AND $equipment['area_id']"> disabled="disabled" </if>  />&nbsp;&nbsp;&nbsp;<span>选填项，10进制设备号，我们将转换成16进制设备号<if condition="$equipment AND $equipment['equipment_sn']">（不可修改编辑） </if></span>
                            </div>
                            
                            <div class="form-group">
                                <label class="col-sm-2"><label for="long_lat">经纬度</label></label>
                                <input class="col-sm-4"  name="lng_lat" id="long_lat" type="text" readonly value="<if condition="$equipment['lng'] gt 0">{pigcms{$equipment.lng},{pigcms{$equipment.lat}</if>"/>
                                &nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
                            </div>
                        </div>
                    </div>
                    <div class="space"></div>
                    <input  name="idd" id="idd" type="hidden"  value="{pigcms{$idd}"/>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-1 col-md-9">
                            <a class="btn btn-info"  onclick="check_submit()">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </a>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>
</div>
<div id="modal-table" class="modal fade" tabindex="-1" style="display:block;">
    <div class="modal-dialog" style="width:80%;">
        <div class="modal-content" style="width:100%;">
            <div class="modal-header no-padding" style="width:100%;">
                <div class="table-header">
                    <button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
                        <span class="white">&times;</span>
                    </button>
                    (用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。
                </div>
            </div>
            <div class="modal-body no-padding" style="width:100%;">
                <form id="map-search" style="margin:10px;">
                    <input id="map-keyword" type="textbox" style="width:500px;" placeholder="尽量填写城市、区域、街道名"/>
                    <input type="submit" value="搜索"/>
                </form>
                <div style="width:100%;height:600px;min-height:600px;" id="cmmap"></div>
            </div>
            <div class="modal-footer no-margin-top">
                <button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    关闭
                </button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=03"></script>
<script type="text/javascript">
    function check_submit(){
        var params = $('#haokuaichong_equipment_form').serialize();
        $.post("{pigcms{:U('HkcPile/add_project_equipment')}", params, function(data) {
            if(data.err_code == 0){
                layer.open({
                    content: data.err_msg,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.open({
                    content:  data.err_msg,
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        layer.closeAll();
                    }
                })
            }
        },'JSON');
        return false;
    }
    
  $('#equipment_sn').on('input propertychange', function() {
        var number10 = $(this).val()||'';
        if(!number10){
            $('#equipment_num').val('');
            return true;
        }
        number10= number10*1;
        var  number16=number10.toString(16).toUpperCase();
        number16=number16.replace('0x','');
        number16=number16.replace('0X','');
        var lenTmp=number16.length;
        if(lenTmp<6){
             $('#equipment_num').val('');
             return true;
        }
        var tmpIndex=lenTmp-2;
        var  last2str= number16.substr(tmpIndex,2);
        var startstr=number16.substring(0,tmpIndex);

        var tmpIndex=startstr.length-2;
        var new_number16= last2str+''+startstr.substr(tmpIndex,2)+''+startstr.substring(0,tmpIndex);
       
        new_number16=new_number16+'04';
        $('#equipment_num').val(new_number16);
    })
    
</script>

<include file="Public:footer"/>
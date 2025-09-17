<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tablet"></i>
	            <a href="{pigcms{:U('Charging/equipment_list')}">智能充电桩管理</a>
            </li>
            <li class="active">编辑智能充电桩</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <form  class="form-horizontal" method="post" onSubmit="return check_submit()"  action="{pigcms{:U('equipment_edit')}">
                        <div class="tab-content">
                            <input  type="hidden" name='id' value="{pigcms{$_GET['id']}"/>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="name">设备名称</label></label>
                                <input class="col-sm-2" size="20" name="equipment_name" id="equipment_name" type="text" placeholder="请输入设备名称" value="{pigcms{$info['equipment_name']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="equipment_sn">SN编号</label></label>
                                <input class="col-sm-2" size="20" name="equipment_sn" id="equipment_sn" readonly placeholder="请输入SN编号" type="number" value="{pigcms{$info['equipment_sn']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="equipment_serial">设备唯一编码</label></label>
                                <input class="col-sm-2" size="20" name="equipment_serial" readonly id="equipment_serial" unselectable="on" placeholder="请输入设备唯一编码" type="text" value="{pigcms{$info['equipment_serial']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1">设备类型</label>
                                <select class="col-xs-4 col-sm-4 col-md-2" name="device_type" id="device_type">
                                    <option value="0">请选择设备类型</option>
                                    <volist name="device_type" id="vo">
                                        <option value="{pigcms{$vo.device_type}" <if condition="$info['type'] eq $vo['device_type']">selected="selected"</if>>{pigcms{$vo['name']}</option>
                                    </volist>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1">工作模式</label>
                                <select class="col-sm-2" name="work_type" id="work_type" placeholder="请选择" value="{pigcms{$info['work_type']}">
                                    <option value="1" <if condition="$info['work_type'] eq 1 || !$info">selected="selected"</if>>计时版</option>
                                    <option value="2" <if condition="$info['work_type'] eq 2">selected="selected"</if>>计量版</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-1"><label for="socket_num">插座数</label></label>
                                <input class="col-sm-2" size="20" name="socket_num" id="socket_num" readonly placeholder="请输入插座数" type="text" value="{pigcms{$info['socket_num']}"/>
                            </div>
 

                            <div class="form-group">
                                <label class="col-sm-1"><label for="long_lat">经纬度</label></label>
                                <input class="col-sm-2" size="10" name="lng_lat" id="long_lat" type="text" readonly value="<if condition="$info['lng']">{pigcms{$info.lng},{pigcms{$info.lat}</if>"/>
                                &nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
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
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=03"></script>
<script type="text/javascript">
    function check_submit(){
        if($('#open_type').val() == 0 && $('#open_url').val()==''){
            alert('打开方式为链接，链接不可为空');
            return false;
        }
        if($('#open_type').val() == 1 && $('#wxapp_id').val()==''){
            alert('打开方式为小程序，请选择小陈程序');
            return false;
        }
    }
</script>

<include file="Public:footer"/>
<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('HkcPile/charg_equipment_list')}">智能充电桩(好快充)</a>
            </li>
            <li class="active">充电区设备列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('HkcPile/charg_equipment_list')}" method="get" style="height: 42px;" >
                    <input type="hidden" name="c" value="HkcPile"/>
                    <input type="hidden" name="a" value="charg_equipment_list"/>
                    <select name="find_type" id="find_type" class="col-sm-2" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>设备名称</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>设备16进制编码</option>
                        <option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>设备10进制编码</option>
                        <option value="4" <if condition="$_GET['find_type'] eq 4">selected="selected"</if>>设备所属区域名称</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('HkcPile/charg_equipment_list')}'">重置</a>
                </form>
            </div>
            <if condition="$is_have_region gt 0">
                <button class="btn btn-success" onclick="addHkcEquipment(0)">添加设备</button>
                &nbsp;&nbsp;
                <else/>
                <div class="ant-collapse-content-box">
                    <p style="font-size: 16px;color: red;margin-top: 10px;">您还没有添加充电区，请先去 充电区列表 添加一个充电区</p>
                </div>
            </if>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">序号</th>
                                <th width="15%">设备名称</th>
                                <th width="10%">设备16进制编码</th>
                                <th width="10%">设备10进制编码</th>
                                <th width="12%">设备所属区域</th>
                                <th width="12%">设备状态更新时间</th>
                                <th width="10%">状态</th>
                                <th >操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_num}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_sn}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.area_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.dev_update_time_str}</div></td>
                            <if condition="$vo.status == 1">
                                <td><div class="tagDiv" style="color: green">在线</div></td>
                                <else />
                                <td><div class="tagDiv" style="color: red">离线</div></td>
                            </if>

                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="编辑" style="cursor: pointer" onclick="addHkcEquipment({pigcms{$vo.id})">编辑</a>
                                    &nbsp;
                                    <a title="详情" href="{pigcms{:U('equipment_detail',array('equipment_id'=>$vo['id']))}">详情</a>
                                      &nbsp;
                                    <a title="查看二维码" style="cursor: pointer" onclick="view_img('{pigcms{$vo.qrcode}')">查看二维码</a>
                                    &nbsp;
                                    <a title="删除" style="cursor: pointer" onclick="del('{pigcms{$vo.id}')">删除</a>
      
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有智能充电桩。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    function addHkcEquipment(id){
        if(id==undefined){
            id='';
        }
        var xtitle='添加设备';
        if(id){
            xtitle='编辑设备';
        }
        var content = "{pigcms{:U('HkcPile/add_project_equipment_page')}&id="+id;
        layer.open({
            type: 2,
            title:xtitle,
            content: content,
            area: ['1200px', '700px'],
            maxmin: false
        });
    }
       //预览大图
    function view_img(img_url) {
        var index = layer.open({
            type: 1,
            area: ['360px', '355px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '查看二维码',
            content: '<img src="'+img_url+'" style="width:300px;height:300px;" />',
        });
        layer.style(index, {
            top: '100px',
            'text-align': 'center'
        });
    }

    
    function del(id){
        layer.open({title:'提示',content:'是否确定删除当前设备？',shadeClose:false,btn: ['确定','取消'],yes:function(){
                $.post("{pigcms{:U('HkcPile/equipment_del')}", {'idd':id}, function(data) {
                    layer.closeAll();
                    if(data.status == 1){
                         layer.alert(data.info,function(){window.location.reload();});
                    }else{
                        layer.alert(data.info,function(){window.location.reload();});
                    }
                },'json');
            }});
    }
</script>
<include file="Public:footer"/>

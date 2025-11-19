<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('equipment_list')}">智慧充电桩管理</a>
            </li>
            <li class="active">智慧充电桩列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Charging/equipment_list')}" method="get" style="height: 42px;" >
                    <input type="hidden" name="c" value="Charging"/>
                    <input type="hidden" name="a" value="equipment_list"/>
                    <select name="find_type" id="find_type" class="col-sm-2" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>设备名称</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>SN编号</option>
                        <option value="3" <if condition="$_GET['find_type'] eq 3">selected="selected"</if>>设备唯一编码</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Charging/equipment_list')}'">重置</a>
                </form>
            </div>
            <button class="btn btn-success" onclick="importAdd()">添加设备</button>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">编号</th>
                                <th width="20%">设备名称</th>
                                <th width="10%">SN编号</th>
                                <th width="15%">设备唯一编码</th>
                                <!--<th width="10%">SN编号</th>-->
                                <!--<th width="10%">工作模式</th>-->
                                <th width="15%">心跳时间</th>
                                <th width="15%">状态</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_sn}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_serial}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.last_heart_time}</div></td>
                            <!--<td><div class="tagDiv">{pigcms{$vo.equipment_sn}</div></td>-->
                            <!--<td><div class="tagDiv"><if condition='$vo.work_type eq 1'>计时版<else/>计量版</if></div></td>-->
                            <if condition="$vo.statusHeart == 44">
                                <td><div class="tagDiv" style="color: red">设备离线【{pigcms{$vo.statusHeartTime}】</div></td>
                            <elseif condition="$vo.status == 1" />
                                <td><div class="tagDiv" style="color: green">正常</div></td>
                             <elseif  condition="$vo.status == 2" />
                                <td><div class="tagDiv" style="color: red">禁用</div></td>
                             <elseif  condition="$vo.status == 3" />
                                <td><div class="tagDiv" style="color: red">恢复中</div></td>
                                <elseif  condition="$vo.status == 4" />
                                <td><div class="tagDiv" style="color: red">停止中</div></td>
                                <else />
                                <td><div class="tagDiv" style="color: red">重启中</div></td>
                            </if>

                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="编辑" href="{pigcms{:U('equipment_edit',array('id'=>$vo['id']))}">编辑</a>
                                    <a title="详情" href="{pigcms{:U('pile_detail',array('equipment_id'=>$vo['id']))}">详情</a>
                                    <a title="功能属性" style="cursor:pointer" onclick="get_default_value('{pigcms{$vo.id}')">功能属性</a>
                                    <a title="查看二维码" style="cursor: pointer" onclick="view_img('{pigcms{$vo.qrcode}')">查看二维码</a>
                                    <a title="删除" style="cursor: pointer" onclick="if(confirm('确认删除该充电桩？')){location.href=\'{pigcms{:U('equipment_del',array('id'=>$vo['id']))}\'}">删除</a>
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
    function importAdd(){
        window.location.href = "{pigcms{:U('equipment_add')}";
    }
    function look(look_url) {
        $.post(look_url,{},function(data){
            console.log('data', data);
            if (data && data.look_img) {
                layer.open({
                    type: 1,
                    area: ['450px', '500px'],
                    content: '<div style="text-align:center;"><img src="'+data['look_img']+'"/> </div>'
                });
            }
        },'json');
        console.log('look_url', look_url);
    }
    function get_default_value(id) {
        var url = "{pigcms{:U('Charging/e_attribute_list')}";
        var href = url+'&equipment_id='+id;
        layer.open({
            type: 2,
            title:'功能属性',
            content: href,
            area: ['600px', '450px'],
            maxmin: false,

        });
    }
    //预览大图
    function view_img(img_url) {
        /*window.top.art.dialog({
            padding: 0,
            title: '大图',
            content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:300px;height:300px;" /></a>',
            lock: true
        });*/
        // var params = {};
        // params['url'] = img_url;
        // params['title'] = '查看二维码';
        // params['height'] = 315;
        // params['width'] = 400;
        // window.top.layer.open(params);

        var index = layer.open({
            type: 1,
            area: ['360px', '355px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '查看二维码',
            content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:300px;height:300px;" /></a>',
        });
        layer.style(index, {
            top: '100px',
            'text-align': 'center'
        });
    }
</script>
<include file="Public:footer"/>

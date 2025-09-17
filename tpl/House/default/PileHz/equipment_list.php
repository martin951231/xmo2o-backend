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
            <button class="btn btn-success" onclick="importAdd()">添加设备</button>
            &nbsp;
            <button class="btn btn-success" onclick="qcodeNum()">生成设备二维码序列号</button>
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
                                <th width="10%">设备编号</th>
                                <th width="20%">最后一次心跳时间</th>
                                <th width="20%">联网状态</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.num}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.eqDeviceId}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.endTime}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.state}</div></td>

                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="编辑" href="{pigcms{:U('queDelete',array('num'=>$vo['num']))}">删除</a>
                                    <a title="详情" href="{pigcms{:U('getPorts',array('deviceId'=>$vo['eqDeviceId']))}">插座详情</a>
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
        var url = "{pigcms{:U('Pile/e_attribute_list')}";
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
        window.top.art.dialog({
            padding: 0,
            title: '大图',
            content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:300px;height:300px;" /></a>',
            lock: true
        });
    }
    
    function qcodeNum() {
        var url = "{pigcms{:U('Pile/qcode_num')}";
        var href = url;
        layer.open({
            type: 2,
            title:'设备二维码序列号',
            content: href,
            area: ['600px', '300px'],
            maxmin: false,

        });
    }
</script>
<include file="Public:footer"/>

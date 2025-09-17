<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->

	<!-- 内容头部 -->
	<div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                             <li <if condition="!$_GET['type'] || $_GET['type'] eq 'temp'"> class="active" </if> >
                            <a href="{pigcms{:U('Park/park_rule_list')}&type=temp">临时车收费规则</a>
                                </li>
                            <li <if condition="$_GET['type'] eq 'mouth' "> class="active" </if>>
                            <a href="{pigcms{:U('Park/park_rule_list')}&type=mouth">月租车收费规则</a>
                            </li>
                            <li <if condition="$_GET['type'] eq 'free' "> class="active" </if>>
                            <a href="{pigcms{:U('Park/park_rule_list')}&type=free">免费车管理</a>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div <if condition="$_GET['type'] eq 'free'"> class="tab-pane active" <else/> class="tab-pane" </if> id="free">
                        <label class="col-sm-8" style="background-color:#D9EDF7;padding: 10px 10px 5px;width: 98.75%">
                            免费车说明：免费车管理设置了车辆信息后，支持车辆免费进出，免费车信息受黑名单限制（同一车辆在免费车管理、黑名单，都有车辆信息，不支持车辆免费进出）。
                        </label>
                        <span><button class="btn btn-success" type="button" onclick="free_add()">添加免费车</button></span>
                        <div id="shopList" class="grid-view">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th width="20%">编号</th>
                                    <th width="20%">车牌类型</th>
                                    <th width="20%">车牌包含</th>
                                    <th width="20%">添加时间</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.park_type}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.free_name}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.updateTime|date='Y-m-d H:i:s',###}</div></td>
                                <td class="button-column">
                                    <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="edit_free({pigcms{$vo.id})">编辑</a>
                                    <if condition="in_array(31,$house_session['menus'])">
                                        <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('del_free',array('id'=>$vo['id'],'park_month_id'=>$_GET['park_month_id']))}'}">删除</a>
                                    </if>
                                </td>
                                </tr>
                                </volist>
                                <else/>
                                <tr class="odd"><td class="button-column" colspan="12" >没有任何缴费优惠。</td></tr>
                                </if>
                                </tbody>
                            </table>
                            {pigcms{$list.pagebar}
                        </div>
                    </div>
                        <div <if condition="$_GET['type'] eq 'mouth'"> class="tab-pane active" <else/> class="tab-pane" </if> id="mouth">
                        <span><button class="btn btn-success" type="button" onclick="mouth_add()">添加月租车收费规则</button></span>&nbsp;&nbsp;&nbsp;&nbsp;
                        <div id="shopList" class="grid-view">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th width="5%">编号</th>
                                    <th width="20%">收费标准名称</th>
                                    <th width="10%">缴费金额</th>
                                    <th width="15%">停车缴费周期</th>
                                    <th width="15%">赠送停车时间</th>
                                    <th width="10%">添加时间</th>
                                    <th width="10%">状态</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.park_month_name}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.park_month_price}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.park_month_num}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.presented_park_month_num}</div></td>
                                <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                                <td><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                <td class="button-column">
                                    <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="edit_mouth({pigcms{$vo.id})">编辑</a>
                                    <if condition="in_array(31,$house_session['menus'])">
                                        <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('preferential_del',array('id'=>$vo['id'],'park_month_id'=>$_GET['park_month_id']))}'}">删除</a>
                                    </if>
                                </td>
                                </tr>
                                </volist>
                                <else/>0
                                <tr class="odd"><td class="button-column" colspan="12" >没有任何缴费优惠。</td></tr>
                                </if>
                                </tbody>
                            </table>
                            {pigcms{$list.pagebar}
                        </div>
                    </div>
                    <div <if condition="!$_GET['type'] || $_GET['type'] eq 'temp'"> class="tab-pane active" <else/> class="tab-pane" </if> id="temp">
                    <span><button class="btn btn-success" type="button" onclick="passage_add()">添加临时车收费规则</button></span>&nbsp;&nbsp;&nbsp;&nbsp;

                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="8%">ID</th>
                                    <th width="20%">收费标准名称</th>
                                    <th width="20%">收费标准类型</th>
                                    <th width="15%">状态</th>
                                    <th width="20%">最后修改时间</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.charge_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.charge_type}</div></td>
                                            <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                                             <td><div class="tagDiv">{pigcms{$vo.updateTime}</div></td>
                                            <td class="button-column">
                                                <a href="javascript:;" style="width: 60px;" class="label label-sm label-info" title="修改" onclick="edit_bill({pigcms{$vo.id})">修改</a>
                                                &nbsp&nbsp;
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('del_rule',array('id'=>$vo['id']))}'}">删除</a>
                                            </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
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
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">

function park_month_add(){
	var url = "{pigcms{:U('park_month_add')}";
    layer.open({
        type: 2,
        title:'添加',
        content: url,
        area: ['750px', '550px'],
        maxmin: false
    });
//	window.location.href = url;
}

function passage_add(){
    var url = "{pigcms{:U('Park/rule_add')}";
    layer.open({
        type: 2,
        title:'添加临时车收费规则',
        content: url,
        area: ['750px', '700px'],
        maxmin: false
    });

}
function mouth_add(){
    var url = "{pigcms{:U('Park/rule_mouth_add')}";
    layer.open({
        type: 2,
        title:'添加月租车收费规则',
        content: url,
        area: ['750px', '500px'],
        maxmin: false
    });

}

function free_add() {
    var url = "{pigcms{:U('Park/free_add')}";
    layer.open({
        type: 2,
        title:'添加免费车',
        content: url,
        area: ['750px', '500px'],
        maxmin: false
    });
}
function edit_free(id) {
    var url = "{pigcms{:U('Park/free_edit')}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'修改',
        content: href,
        area: ['750px', '500px'],
        maxmin: false
    });
}
function edit_mouth(id) {
    var url = "{pigcms{:U('Park/rule_mouth_edit')}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'修改',
        content: href,
        area: ['750px', '500px'],
        maxmin: false
    });
}
function edit_bill(id){
    var url = "{pigcms{:U('Park/rule_edit')}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'修改',
        content: href,
        area: ['750px', '700px'],
        maxmin: false
    });

}

function open_gate(id) {
    $.post("{pigcms{:U('Park/open_gate')}",{id:id},function(result){
        alert(result.info);
    })
    return false;
}

function close_all_page(id,type) {
    layer.closeAll();
    var url = "{pigcms{:U('Park/park_rule_list')}";
    var href  = url + '&type='+type
    window.location.href = href;
}
$('.see_qrcode').live('click',function(){
    var url = '{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode_html&down=true&qrCon=';
    var qrcode_urls = "{pigcms{$qrcode_url}";
    var hou_url = qrcode_urls + $(this).attr('href');
    var qrcode_url = url+encodeURIComponent(hou_url);
    art.dialog.open(qrcode_url,{
        init: function(){
            var iframe = this.iframe.contentWindow;
            window.top.art.dialog.data('iframe_handle',iframe);
        },
        id: 'handle',
        title:'{pigcms{:L_("如需保存，鼠标右键点击图片另存为即可")}',
        padding: 0,
        width: 312,
        height: 312,
        lock: true,
        resize: false,
        background:'black',
        button: null,
        fixed: false,
        close: null,
        left: '50%',
        top: '38.2%',
        opacity:'0.4'
    });
    return false;
});
</script>

<include file="Public:footer"/>
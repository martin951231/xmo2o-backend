<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Materiel/index')}">物料管理</a>
            </li>
            <li class="active">物料类型列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
                .btn-materiel {line-height: 18px;height: 20px;}
            </style>
            <div>
                <a class="btn btn-success" onclick="materiel_add()">添加物料类别</a>
                <if condition="$isAiHorse">
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="btn btn-success" onclick="materiel_checkouts('{pigcms{$house_session.village_id}', '{pigcms{$house_session.property_id}')">批量领用</a>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('stock_export',$_GET)}'">导出类别剩余库存</a>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="btn btn-success" href="{pigcms{:U('checkoutList')}">领用记录列表</a>
                </if>
            </div>
            <div class="row">
                <div style="padding-top:20px;" class="form-group">
                    <form method="get" action="{pigcms{:U('Materiel/index')}">
                        <input type="hidden" value="Materiel" name="c">
                        <input type="hidden" value="index" name="a">
                        <div style="height:32px; width:90px;line-height: 30px;padding-left: 0" class="col-sm-1">类别名称:</div>
                        <input type="text" style="margin-right:10px;font-size:16px;height:32px;" id="type_name" name="type_name" class="col-sm-1" value="{pigcms{$_GET['type_name']}">
                        &nbsp;&nbsp;<input type="submit" value="查询" id="find_submit" class="btn btn-success"/>
                    </form>
                </div>
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view" style="padding-top:0;">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>类别名称</th>
                                    <th>入库</th>
                                    <if condition="$isAiHorse">
                                        <th>出库管理</th>
                                    </if>
                                    <th>出库详情</th>
                                    <th>剩余库存</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.type_name}</div></td>
                                            <td>
                                                <div class="tagDiv">
                                                    <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="管理" onclick="warehousing_index('{pigcms{$vo.type_id}')">管理</a>
                                                </div>
                                            </td>
                                            <if condition="$isAiHorse">
                                                <td>
                                                    <div class="tagDiv">
                                                        <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="管理" onclick="checkoutList('{pigcms{$vo.type_id}')">管理</a>
                                                    </div>
                                                </td>
                                            </if>
                                            <td>
                                                <div class="tagDiv">
                                                    <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="出库详情" onclick="checkout_index('{pigcms{$vo.type_id}')">出库详情</a>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="tagDiv">
                                                    {pigcms{$vo['surplus_num']}
                                                </div>
                                            </td>
                                            <td>
                                                <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="编辑" onclick="materiel_add('{pigcms{$vo.type_id}')">编辑</a>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="10" >还没有物料类别</td></tr>
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
<script type="text/javascript">
    function materiel_add(type_id) {
        var url = "{pigcms{:U('Materiel/add',array('ftype_id'=>0))}";
        if (type_id && type_id>0) {
            url += "&type_id=" + type_id;
        }
        console.log('url',url);
        var index = layer.open({
            type: 2,
            title:'添加物料类别',
            content: url,
            area: ['500px', '310px'],
            maxmin: false,
        });
        layer.style(index, {
            top: '100px'
        });
    }


    function materiel_checkouts(village_id, property_id){
        console.log('materiel_checkouts-village_id', village_id)
        var url = parent.location.href.split("#")[0]+'#/village/village.materiel/materielCheckouts'
        if (village_id > 0) {
            url += '?village_id=' + village_id
        } else if (property_id > 0) {
            url += '?property_id=' + property_id
        }
        var index1 = layer.open({
            type: 2,
            area: ['80%', '750px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '批量领用',
            content: url
        });
        layer.style(index1, {
            top: '50px',
        });
    }
    
    function materiel_checkouts_bak() {
        var url = "{pigcms{:U('Materiel/materiel_checkouts')}";
        var index = layer.open({
            type: 2,
            title:'批量领用',
            content: url,
            area: ['750px', '650px'],
            maxmin: false
        });
        layer.style(index, {
            top: '100px'
        });
    }


    function warehousing_index(type_id) {
        console.log('type_id',type_id);
        var url = "{pigcms{:U('Materiel/warehousing_index')}&type_id=" + type_id;
        console.log('url',url);
        window.location.href = url;
    }
    
    function checkoutList(type_id) {
        console.log('type_id',type_id);
        var url = "{pigcms{:U('Materiel/checkoutList')}&type_id=" + type_id;
        console.log('url',url);
        window.location.href = url;
    }

    function checkout_index(type_id) {
        console.log('type_id',type_id);
        var url = "{pigcms{:U('Materiel/checkout_index')}&type_id=" + type_id;
        console.log('url',url);
        window.location.href = url;
    }


    function close_all_page() {
        layer.closeAll();
        window.location.reload()
    }
</script>
<include file="Public:footer"/>

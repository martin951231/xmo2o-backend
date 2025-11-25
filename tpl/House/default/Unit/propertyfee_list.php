<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/index')}">物业管理</a>
            </li>
            <li class="active">收费标准列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <if condition="in_array(29,$house_session['menus'])">
                <button class="btn btn-success" onclick="importAdd()">添加收费标准</button>
                <else/>
                <button class="btn btn-success disabled" disabled="disabled">添加收费标准</button>
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
                                <th width="20%">名称</th>
                                <th width="20%">绑定房间</th>
                                <th width="10%">状态</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list['list']">
                                <volist name="list['list']" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.name} </div></td>
                            <td><a style="width: 60px;color: blue" title="管理" href="{pigcms{:U('propertyfee_manage',array('id'=>$vo['id']))}">管理</a></td>
                            <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                            <td class="button-column">
                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="edit('{pigcms{$vo.id}')">编辑</a>
                                <if condition="in_array(31,$house_session['menus'])">
                                    <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('propertyfee_del',array('id'=>$vo['id']))}'}">删除</a>
                                </if>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有任何收费标准。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
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
        var url = "{pigcms{:U('Unit/propertyfee_add')}";
        layer.open({
            type: 2,
            title:'添加物业费标准',
            content: url,
            area: ['950px', '555px'],
            maxmin: false
        });
    }

    function edit(id){
        var url = "{pigcms{:U('Unit/propertyfee_edit')}";
        if (id && id>0) {
            url += "&id=" + id;
        }
        layer.open({
            type: 2,
            title:'编辑物业费标准',
            content: url,
            area: ['950px', '555px'],
            maxmin: false
        });
    }

    function importUserDetail(){
        window.location.href = "{pigcms{:U('User/detail_import')}";
    }
</script>
<include file="Public:footer"/>

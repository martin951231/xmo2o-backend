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
            </div>
            <div class="row">
                <div style="padding-top:20px;" class="form-group">
                    <form method="get" action="{pigcms{:U('Materiel/index')}">
                        <input type="hidden" value="Materiel" name="c">
                        <input type="hidden" value="index" name="a">
                        <div style="height:32px; width:90px;line-height: 30px;padding-left: 0" class="col-sm-1">类别名称:</div>
                        <input type="text" style="margin-right:10px;font-size:16px;height:32px;width: 220px;" id="type_name" name="type_name" class="col-sm-1" value="{pigcms{$_GET['type_name']}">
                        &nbsp;&nbsp;<input type="submit" value="查询" id="find_submit" class="btn btn-success"/>
                    </form>
                </div>
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view" style="padding-top:0;">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>类别名称</th>
                                    <th>子类别数量</th>
                                    <th>排序</th>
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
                                                    <a style="color: #1890ff;" class="" title="点击查看子类别" href="{pigcms{:U('Materiel/index_sub',array('ftype_id'=>$vo['type_id']))}">{pigcms{$vo.sub_type_count}（点击查看）</a>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="tagDiv">
                                                  {pigcms{$vo.xsort}
                                                </div>
                                            </td>
                                            <td>
                                                <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="编辑" onclick="materiel_add('{pigcms{$vo.type_id}')">编辑</a>
                                                <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="删除" onclick="materiel_dell('{pigcms{$vo.type_id}',this)">删除</a>
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

   function materiel_dell(type_id,obj){
         var url = "{pigcms{:U('Materiel/delMaterielType')}";
        layer.confirm("是否确认删除此条类别数据?",{
            btn: ['确认', '取消']
        }, function () {
            $.post(url,{'type_id': type_id},function(ret){
                layer.msg(ret.message);
                if(ret.err_code<1){
                    $(obj).parent('td').parent('tr').remove();
                    setTimeout(function () {
                            window.location.reload()
                    },2000)
                }
            },'json')
        }, function(){

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

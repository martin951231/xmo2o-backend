<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Materiel/warehousing_index')}">物料入库管理</a>
            </li>
            <li class="active">物料入库列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <a href="javascript:void(0);" onclick="return_index();">返回上一级</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-success" onclick="warehousing_add()">添加物料入库</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-success" onclick="location.href='{pigcms{:U('warehousing_export',$_GET)}'">导出入库记录</a>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>物料数量</th>
                                    <th>物料单价</th>
                                    <th>供应商名称</th>
                                    <th>供应商联系方式</th>
                                    <th>入库时间</th>
                                    <th>备注</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.price}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.supplier_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.supplier_phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.warehousing_time|date='Y-m-d H:i',###}</div></td>
                                            <td><div class="tagDiv"><?php echo  msubstr($vo['remark'],0,20);?></div></td>
                                            <td>
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="warehousing_add('{pigcms{$vo.id}')">编辑</a>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="10" >还没有对应物料入库</td></tr>
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
    function warehousing_add(id) {
        var url = "{pigcms{:U('Materiel/warehousing_add')}";
        if (id && id>0) {
            url += "&id=" + id;
        }
        var name = "{pigcms{$type_msg['type_name']}";
        var type_id = "{pigcms{$_GET['type_id']}";
        if (type_id && type_id>0) {
            url += "&type_id=" + type_id;
        }
        var title = '';
        if (name) {
            title = '添加【'+name+'】物料入库';
        } else {
            title = '添加物料入库';
        }
        console.log('url',url);
        layer.open({
            type: 2,
            title:title,
            content: url,
            area: ['750px', '600px'],
            maxmin: false
        });
    }

    function return_index() {
        var url = "{pigcms{:U('Materiel/index')}";
        
        <if condition="isset($_GET['ftype_id']) AND $_GET['ftype_id']">
            url = "{pigcms{:U('Materiel/index_sub',array('ftype_id'=>$_GET['ftype_id']))}";
        </if>

        window.location.href = url;
    }

    function close_all_page() {
        layer.closeAll();
        window.location.reload()
    }
</script>
<include file="Public:footer"/>

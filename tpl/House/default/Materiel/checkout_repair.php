<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Materiel/checkout_repair')}">物料维修管理</a>
            </li>
            <li class="active">物料维修列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <a class="btn btn-success" onclick="repair_add()">添加维修记录</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="javascript:void(0);" onclick="return_index();">返回上一级</a>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>物料类别</th>
                                    <th>维修时间</th>
                                    <th>维修费用</th>
                                    <th>备注</th>
                                    <th>附件</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$type_msg['type_name']|default=''}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.repair_time|date='Y-m-d H:i',###}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.repair_money}</div></td>
                                            <td><div class="tagDiv"><?php echo  msubstr($vo['remark'],0,20);?></div></td>
                                            <td>
                                                <div class="tagDiv">
                                                    <a style="color: #3a87ad!important; cursor:pointer;text-align: center;width: 100%;" onclick="look_img('{pigcms{$vo.id}')">查看</a>
                                                </div>
                                            </td>
                                            <td>
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="repair_add('{pigcms{$vo.id}')">编辑</a>
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
    function repair_add(id) {
        var url = "{pigcms{:U('Materiel/repair_add')}";
        if (id && id>0) {
            url += "&id=" + id;
        }
        var name = "{pigcms{$type_msg['type_name']}";
        var type_id = "{pigcms{$_GET['type_id']}";
        if (type_id && type_id>0) {
            url += "&type_id=" + type_id;
        }
        var checkout_list_id = "{pigcms{$_GET['id']}";
        if (checkout_list_id && checkout_list_id>0) {
            url += "&checkout_list_id=" + checkout_list_id;
        }
        var title = '';
        if (name) {
            title = '添加【'+name+'】物料维修记录';
        } else {
            title = '添加物料维修记录';
        }
        console.log('url',url);
        layer.open({
            type: 2,
            title:title,
            content: url,
            area: ['750px', '550px'],
            maxmin: false
        });
    }

    function look_img(id) {
        var url = "{pigcms{:U('Materiel/img_info')}";
        if (id && id>0) {
            url += "&id=" + id;
        }
        layer.open({
            type: 2,
            title:'查看附件',
            content: url,
            area: ['750px', '550px'],
            maxmin: false
        });
    }

    function return_index() {
        var url = "{pigcms{:U('Materiel/checkout_index')}";

        <if condition="isset($_GET['ftype_id']) AND $_GET['ftype_id']">
            url = "{pigcms{:U('Materiel/checkout_index',array('ftype_id'=>$_GET['ftype_id']))}";
        </if>

        var type_id = "{pigcms{$_GET['type_id']}";
        if (type_id && type_id>0) {
            url += "&type_id=" + type_id;
        }
        window.location.href = url;
    }


    function close_all_page() {
        layer.closeAll();
        window.location.reload()
    }
</script>
<include file="Public:footer"/>

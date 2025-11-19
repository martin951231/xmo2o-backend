<include file="NewPublic:header"/>

<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">小区管理</a>
            </li>
            <li class="active">小区列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="row">
                <hr>
                <div style="display: flex;">
                    <h2 style="font-size:16px;margin-left:17px;">组织架构</h2>
                    <if condition="$is_syn">
                        <div style="float: right;line-height: 54px;text-align: right;width: 80%;">
                            <button class="btn btn-info sys_qy_weixin_group">
                                <img width="18" style="margin-bottom: 2px;" src="{pigcms{$static_path}images/qyWeixin/qyweixinIcon1.png">
                                同步信息至企业微信
                            </button>
                        </div>
                    </if>
                </div>
                <div style="color: green;margin-left:17px;">1.可以看到本物业下所有相关组织和人员</div>
                <div style="color: green;margin-left:17px;">2.可以管理本物业下所有组织和人员（增删改）</div>
                <div style="color: green;margin-left:17px;">3.下面归属小区需要先行在【添加子组织】中选择类型为【小区】的然后选择对应小区进行添加，对应小区才可以进行相关管理；</div>
                <div style="color: green;margin-left:17px;margin-bottom: 10px;">4.对应小区的工作人员需要加入对应类型为小区的组织下面</div>

                <div class="col-sm-4" style="width: 100%;clear: both;">
<!--                    <h2>组织架构列表</h2>-->
                    <div id="treeview-checkable" class=""></div>
                </div>
<!--                <div class="col-sm-4">-->
<!--                    <h2>Events</h2>-->
<!--                    <div id="checkable-output"></div>-->
<!--                </div>-->
            </div>
            

        </div>
    </div>
</div>
<!--	组织架构-->
<!--<link href="https://cdn.bootcss.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">-->
<link href="{pigcms{$static_path}propery_group/js/zx.css" rel="stylesheet">
<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript" src="/tpl/System/Static/js/area_pca.js"></script>

<script src="{pigcms{$static_path}propery_group/js/jquery-2.1.0.min.js"></script>
<script src="{pigcms{$static_path}propery_group/js/bootstrap-treeview.js"></script>
<script src="{pigcms{$static_path}layer/skin/layui.js"></script>
<!--<link href="{pigcms{$static_path}layer/css/layui.css" rel="stylesheet">-->
<script type="text/javascript">
    $(document).ready(function (){
        $.ajax({
            type: "POST",
            url: "{pigcms{:U('PropertyGroup/index_new')}",
            dataType:"json",
            success: function(data){
                tree_list(data)
            }
        });

        // 组织架构同步企业微信
        $('.sys_qy_weixin_group').click(function () {
            var that = $(this);
            that.html('正在同步中......');
            var index = layer.open({
                title: '提示'
                ,type:1
                ,content: '<div style="width:250px;height:80px;text-align:center;margin-top:30px;">同步中,请勿关闭网页</div>'
            });
            layer.style(index, {
                top: '20px'
            });

            $.post('{pigcms{:U("PropertyGroup/sys_qy_weixin_group")}',{type:'all'},function(data){
                console.log(data)
                layer.closeAll();
                if (data.code==1) {
                    that.html('已同步成功');
                    setTimeout(function () {
                        that.html('重新同步信息至企业微信');
                    },1000);
                }else{
                    that.html('重新同步信息至企业微信');
                    layer.alert(data.msg)
                }
            },'json')
            return false;
        });

    });
function tree_list(defaultData){
    var $checkableTree = $('#treeview-checkable').treeview({


        data: defaultData,
        showIcon: false,
        showCheckbox: false,
        selectedBackColor: '#ffffcc',
        selectedColor: '#000000',
        showTags: true,
        onNodeChecked: function (event, node) {
            //$('#checkable-output').prepend('<p>' + node.text + ' was checked</p>');
            // console.log(event);
            // console.log(node.nodes);

            // $.each(node.nodes, function (index, value, array) {
            //   console.log(value);

            // });
            checkAllParent(node);
            // checkAllSon(node);
        },
        onNodeUnchecked: function (event, node) {
            //$('#checkable-output').prepend('<p>' + node.text + ' was unchecked</p>');

            uncheckAllParent(node);
            uncheckAllSon(node);
        }

    });
}

    $('#treeview-checkable').on('nodeSelected', function (event, data) {
        // Your logic goes here
        //   console.log('222222222222222222');
        console.log(data.id);
    });



    var nodeCheckedSilent = false;
    function nodeChecked(event, node) {
        if (nodeCheckedSilent) {
            return;
        }
        nodeCheckedSilent = true;
        checkAllParent(node);
        checkAllSon(node);
        nodeCheckedSilent = false;
    }

    var nodeUncheckedSilent = false;
    function nodeUnchecked(event, node) {
        if (nodeUncheckedSilent)
            return;
        nodeUncheckedSilent = true;
        uncheckAllParent(node);
        uncheckAllSon(node);
        nodeUncheckedSilent = false;
    }

    //选中全部父节点
    function checkAllParent(node) {
        $('#treeview-checkable').treeview('checkNode', node.nodeId, { silent: true });
        var parentNode = $('#treeview-checkable').treeview('getParent', node.nodeId);
        if (!("nodeId" in parentNode)) {
            return;
        } else {
            checkAllParent(parentNode);
        }
    }
    //取消全部父节点
    function uncheckAllParent(node) {
        $('#treeview-checkable').treeview('uncheckNode', node.nodeId, { silent: true });
        var siblings = $('#treeview-checkable').treeview('getSiblings', node.nodeId);
        var parentNode = $('#treeview-checkable').treeview('getParent', node.nodeId);
        if (!("nodeId" in parentNode)) {
            return;
        }
        var isAllUnchecked = true;  //是否全部没选中
        for (var i in siblings) {
            if (siblings[i].state.checked) {
                isAllUnchecked = false;
                break;
            }
        }
        if (isAllUnchecked) {
            uncheckAllParent(parentNode);
        }

    }

    //级联选中所有子节点
    function checkAllSon(node) {
        $('#treeview-checkable').treeview('checkNode', node.nodeId, { silent: true });
        if (node.nodes != null && node.nodes.length > 0) {
            for (var i in node.nodes) {
                checkAllSon(node.nodes[i]);
            }
        }
    }
    //级联取消所有子节点
    function uncheckAllSon(node) {
        $('#treeview-checkable').treeview('uncheckNode', node.nodeId, { silent: true });
        if (node.nodes != null && node.nodes.length > 0) {
            for (var i in node.nodes) {
                uncheckAllSon(node.nodes[i]);
            }
        }
    }


    var now_node = '';
    var add_num = 'permission_text_';
    var add_text = '';


    function addPropertyGroup(id,village_id, node){
        // var url = "{pigcms{:U('PropertyGroup/add')}";
        // window.location.href = url + '&id='+id+'&village_id='+village_id;
        now_node = node;
        var index_num = $(node).parent().prev().children('.indent').length;
        var url = "{pigcms{:U('PropertyGroup/add')}";
        var href = url + '&id='+id+'&village_id='+village_id + '&index_num='+index_num;
        layer.open({
            type: 2,
            title:'添加',
            content: href,
            area: ['750px', '485px'],
            maxmin: false
        });
    }

    function close_all_page(info) {
        if (now_node && info && info['html']) {
            $(now_node).parents('span').after(info['html']);
            $(now_node).parent().prev().children('.icon.glyphicon').addClass('icon expand-icon glyphicon glyphicon-minus');
        }
        if (add_text && $('#' + add_text)) {
            var num = $('#' + add_text).html();
            num = parseInt(num) + 1;
            $('#' + add_text).html(num);
        }

        add_text = '';
        layer.closeAll();
    }

    //小区
    function addHouseWorker(village_id,type){
        // var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        // window.location.href = url + '&type='+type+'&village_id='+village_id;
        var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        var href = url + '&type='+type+'&village_id='+village_id;
        layer.open({
            type: 2,
            title:'添加',
            content: href,
            area: ['750px', '550px'],
            maxmin: false
        });
    }
    //小区部门
    function addHouseWorkerDep(village_id,type,department_id,node){
        // var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        // window.location.href = url + '&type='+type+'&village_id='+village_id+'&department_id='+department_id;
        now_node = node;
        add_text = add_num + '' + department_id;
        var index_num = $(node).parent().prev().children('.indent').length;
        var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        var href = url + '&type='+type+'&village_id='+village_id+'&department_id='+department_id + '&index_num='+index_num;
        layer.open({
            type: 2,
            title:'添加',
            content: href,
            area: ['750px', '550px'],
            maxmin: false
        });
    }
    //物业部门
    function addPropertyDep(type,department_id, node){
        // var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        // window.location.href = url + '&type='+type+'&department_id='+department_id;
        now_node = node;
        add_text = add_num + '' + department_id;
        var index_num = $(node).parent().prev().children('.indent').length;
        var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        var href = url + '&type='+type+'&department_id='+department_id + '&index_num='+index_num;
        layer.open({
            type: 2,
            title:'添加',
            content: href,
            area: ['750px', '550px'],
            maxmin: false
        });
    }
    //添加物业
    function addHouseWorkers(type,fid, node){
        // var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        // window.location.href = url + '&type='+type+'&department_id='+fid;
        now_node = node;
        add_text = add_num + '' + fid;
        var url = "{pigcms{:U('PropertyGroup/worker_add')}";
        var href = url + '&type='+type+'&department_id='+fid;
        layer.open({
            type: 2,
            title:'添加',
            content: href,
            area: ['750px', '550px'],
            maxmin: false
        });
    }

    //删除组织
    function del(id){
       console.log(id);
        layer.open({
            content: '确认删除,会把下面的所有数据都删除,删除后数据无法回复！请谨慎操作！',
            shadeClose: false,
            closeBtn: 0
            ,btn: ['确定', '取消']
            ,yes:function(index){
                //layer.load(0);
                layer.msg('正在删除，请稍等...', {time:0,shade: [0.5, '#000']});
                $.ajax({
                    type : "POST",
                    url : "{pigcms{:U('PropertyGroup/del')}",
                    data : {"id":id},
                    dataType:'json',
                    success : function(res) {
                        layer.closeAll();
                        console.log(res);
                        if(res.code == 1){
                            window.location.href="{pigcms{:U('PropertyGroup/index',array('iframe' => $_GET['iframe']))}"
                        }else if(res.code == 3){
                            alert(res.msg)
                        }else{
                            layer.msg('删除失败!',{time:3000,shade: false});
                        }
                    },
                    error: function(res) {
                        layer.closeAll();
                        layer.msg('删除失败!',{time:3000,shade: false});
                    }
                });
            }
        });
    }
    //删除人员
    function del_people(id){
        console.log(id);
        layer.open({
            content: '确认删除吗！删除后数据无法回复！',
            shadeClose: false,
            closeBtn: 0
            ,btn: ['确定', '取消']
            ,yes:function(index){
                //layer.load(0);
                layer.msg('正在删除，请稍等...', {time:0,shade: [0.5, '#000']});
                $.ajax({
                    type : "POST",
                    url : "{pigcms{:U('PropertyGroup/del_people')}",
                    data : {"id":id},
                    dataType:'json',
                    success : function(res) {
                        layer.closeAll();
                        if(res.code == 1){
                            window.location.href="{pigcms{:U('PropertyGroup/index',array('iframe' => $_GET['iframe']))}"
                        }else{
                            layer.msg('删除失败!',{time:3000,shade: false});
                        }
                    },
                    error: function(res) {
                        layer.closeAll();
                        layer.msg('删除失败!',{time:3000,shade: false});
                    }
                });
            }
        });
    }

    function info_people(id){
        var url = "{pigcms{:U('PropertyGroup/info_people_new')}";
        layer.open({
            type: 2,
            title:'查看人员信息',
            content: url+'&id='+id,
            area: ['650px', '450px'],
            maxmin: false
        });
    }


    function edit_people(id){
        // var url = "{pigcms{:U('PropertyGroup/edit_people')}";
        // window.location.href = url+'&id='+id;
        var url = "{pigcms{:U('PropertyGroup/edit_people')}";
        var href = url+'&id='+id;
        layer.open({
            type: 2,
            title:'编辑',
            content: href,
            area: ['750px', '485px'],
            maxmin: false
        });
    }

    function edit_property_group(id){
        // var url = "{pigcms{:U('PropertyGroup/edit_property_group')}";
        // window.location.href = url+'&id='+id;
        var url = "{pigcms{:U('PropertyGroup/edit_property_group')}";
        var href = url+'&id='+id;
        layer.open({
            type: 2,
            title:'编辑',
            content: href,
            area: ['750px', '485px'],
            maxmin: false
        });
    }


</script>


<include file="NewPublic:footer"/>

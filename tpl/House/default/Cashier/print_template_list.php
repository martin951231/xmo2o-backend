<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="javascript:void(0);">收费管理</a>
            </li>
            <li class="active">打印模板设置</li>
        </ul>
    </div>
    <div class="alert alert-info" style="margin:10px;">
        <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>自定义设置打印模板，可解决在电脑上打印账单，同时也支持针式三联打印机！不建议使用该打印模板设置<br>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <button class="btn btn-success" onclick="Add()" <if condition="!in_array(87,$house_session['menus'])">disabled="disabled"</if>>添加打印模板</button>
            <button style="background-color: #169BD5 !important;border-color:#169BD5 !important;margin-left: 10px" class="btn btn-success" onclick="village_copy()" <if condition="!in_array(87,$house_session['menus'])">disabled="disabled"</if>>从其他小区复制</button>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="15%">编号</th>
                                    <th width="15%">模板名称</th>
                                    <th width="15%">打印标题</th>
                                    <th width="15%">一行列数</th>
                                    <th width="20%">说明</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.template_id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.title}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.top_title}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.col_num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.desc}</div></td>
                                            <td>
                                                <a style="width: 60px;" class="label label-sm label-info" title="预览" href="javascript:void(0)" url="{pigcms{:U('print_template_detail',array('template_id'=>$vo['template_id']))}" onclick="detail(this)">预览</a> &nbsp; 
                                                <if condition="in_array(88,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('print_template_add',array('template_id'=>$vo['template_id']))}">编辑</a> &nbsp;
                                                </if>
                                                <if condition="in_array(89,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认要删除吗？')){location.href='{pigcms{:U('print_template_del',array('template_id'=>$vo['template_id']))}'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="4" >没有任何打印模板。</td></tr>
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
function Add(){
    window.location.href = "{pigcms{:U('print_template_add')}";
}

//复制小区打印模板
function village_copy() {

    layer.open({
        type: 2,
        title: '从其他小区复制',
        shadeClose: true,
        shade: 0.8,
        area: ['50%', '50%'],
        content: "{pigcms{:U('print_template_copy')}" //iframe的url
    });
}

function detail(obj){
    var url = $(obj).attr('url');
    art.dialog.open(url, {
        title: "打印预览",
        lock: true,
        width: 800,
        height: 370,
        button:[{name:'关闭'}],
    },true);
}
</script>
<include file="Public:footer"/>

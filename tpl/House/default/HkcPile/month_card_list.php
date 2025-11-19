<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('month_card_list')}">智慧充电桩管理</a>
            </li>
            <li class="active">智慧充电桩月卡列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <button class="btn btn-success" onclick="importAdd()">添加月卡</button>
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
                                <th width="10%">月卡名称</th>
                                <th width="10%">价格</th>
                                <th width="10%">使用次数</th>
                                <th width="10%">状态</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.card_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.price}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.all_num}</div></td>
                            <td><if condition="$vo.status eq 1"><div class="tagDiv" style="color: green">开启</div><else /><div class="tagDiv" style="color: red">关闭</div></if></td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <a title="编辑" href="{pigcms{:U('month_card_add',array('id'=>$vo['id']))}">编辑</a>
                                    <a title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该月卡？')){location.href=\'{pigcms{:U('Pile/month_card_del',array('id'=>$vo['id']))}\'}">删除</a>
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有月卡。</td></tr>
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
        window.location.href = "{pigcms{:U('month_card_add')}";
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
</script>
<include file="Public:footer"/>

<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Index/index')}">停车管理</a>
            </li>
            <li class="active">缴费优惠列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
<!--            <button class="btn " onclick="park_config()">停车费设置</button>&nbsp;&nbsp;-->
<!--            <button class="btn " onclick="preferential_list()">月租车管理</button>&nbsp;&nbsp;-->
<!--            <button class="btn " onclick="temporary_list()">临时停车管理</button>&nbsp;&nbsp;-->
<!--            <button class="btn " onclick="vehicle_management()">车辆管理</button>&nbsp;&nbsp;-->

            <style>
                .ace-file-input a {display:none;}
            </style>

            <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('temporary_charge_add',array('park_month_id'=>$_GET['park_month_id']))}'">添加</button></span>&nbsp;&nbsp;&nbsp;&nbsp;
            <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('mass_add',array('park_month_id'=>$_GET['park_month_id']))}'">批量添加</button></span>&nbsp;&nbsp;&nbsp;&nbsp;
            <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('park_temporary_list')}'">临时车管理</button></span>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">名称</th>
                                    <th width="20%">截止时间(分钟)</th>
									<th width="20%">收费(元)</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">

                                            <td><div class="tagDiv">{pigcms{$vo.change_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.change_time}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.change_money}</div></td>


<!--                                            <td>-->
<!--                                                <div class="tagDiv">-->
<!--                                                    <if condition='$vo["status"] eq 1'>-->
<!--                                                        <span class="green">开启</span>-->
<!--                                                        <else />-->
<!--                                                        <span class="red">关闭</span>-->
<!--                                                    </if>-->
<!--                                                </div>-->
<!--                                            </td>-->
                                            <td class="button-column">
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('temporary_charge_edit',array('id'=>$vo['id'],'park_month_id'=>$_GET['park_month_id']))}">编辑</a>
                                                <if condition="in_array(31,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('temporary_charge_del',array('id'=>$vo['id']))}'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有任何停车缴费标准。</td></tr>
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
    function park_config(){
        window.location.href = "{pigcms{:U('park_config')}";
    }

    function preferential_list(){
        window.location.href = "{pigcms{:U('preferential_list')}";
    }

    function temporary_list(){
        window.location.href = "{pigcms{:U('temporary_list')}";
    }

    function vehicle_management(){
        window.location.href = "{pigcms{:U('Unit/vehicle_management')}";
    }

</script>
<include file="Public:footer"/>

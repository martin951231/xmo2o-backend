<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Materiel/checkout_index')}">物料出库详情</a>
            </li>
            <li class="active">物料出库列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <a href="javascript:void(0);" onclick="return_index();">返回上一级</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-success" onclick="checkout_add()">添加物料出库</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div style="padding-top:20px;" class="form-group">
                    <form method="get" action="{pigcms{:U('Materiel/checkout_index')}">
                        <input type="hidden" value="Materiel" name="c">
                        <input type="hidden" value="checkout_index" name="a">
                        <input type="hidden" value="{pigcms{$_GET['type_id']}" name="type_id">
                        <div style="height:32px; width:70px;line-height: 30px;padding-left: 0" class="col-sm-1">领用人:</div>
                        <input type="text" style="margin-right:10px;font-size:16px;height:32px;" id="receive_name" name="receive_name" class="col-sm-1" value="{pigcms{$_GET['receive_name']}">
                        &nbsp;&nbsp;<div style="height:32px; width:60px;line-height: 30px;padding-left: 10px;" class="col-sm-1">状态:</div>
                        <select style="margin-right:10px;font-size:16px;height:32px;width: 120px;" class="col-sm-1" id="status" name="status">
                            <option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>请选择</option>
                            <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>已出库</option>
                            <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>已报废</option>
                        </select>
                        &nbsp;&nbsp;<div style="height:32px; width:85px;line-height: 30px;padding-left: 10px;" class="col-sm-1">领用时间:</div>
                        <input type="text" name="receive_time_start" id="receive_time_start" class="input-text" value="{pigcms{$_GET['receive_time_start']}"  style="height:35px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="请选择领用开始时间"/>
                        &nbsp;—&nbsp;
                        <input type="text" name="receive_time_end" id="receive_time_end" class="input-text" value="{pigcms{$_GET['receive_time_end']}"  style="height:35px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="请选择领用结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;

                        &nbsp;&nbsp;<input type="submit" value="查询" id="find_submit" class="btn btn-success"/>
                    </form>
                </div>
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>序号</th>
                                    <th>物料类别</th>
                                    <th>领用人</th>
                                    <th>领用人联系方式</th>
                                    <th>领用时间</th>
                                    <th>维修记录</th>
                                    <th>备注</th>
                                    <th>确认状态</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$type_msg['type_name']|default=''}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.receive_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.receive_phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.receive_time|date='Y-m-d H:i',###}</div></td>
                                            <td>
                                                <div class="tagDiv">
                                                    <a style="color: #3a87ad!important; cursor:pointer;text-align: center;width: 100%;" onclick="checkout_repair('{pigcms{$vo.id}')">{pigcms{$vo.repair_num|default='点击添加'}</a>
                                                </div>
                                            </td>
                                            <td><div class="tagDiv"><?php echo  msubstr($vo['remark'],0,15);?></div></td>
                                            <td>
                                                <if condition="$vo['confirm_status'] eq 0">
                                                    <div class="tagDiv" style="color: red;">领取人未确认</div>
                                                    <else/>
                                                    <div class="tagDiv" style="color: #87b87f;">领取人已确认</div>
                                                </if>
                                            </td>
                                            <td>
                                                <if condition="$vo['status'] eq 0">
                                                    <div class="tagDiv" style="color: #87b87f;">已出库</div>
                                                    <else/>
                                                    <div class="tagDiv" style="color: red;">已报废</div>
                                                </if>
                                            </td>

                                            <td>
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" onclick="checkout_list_edit('{pigcms{$vo.id}')">编辑</a>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="10" >还没有对应物料出库</td></tr>
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
    $(".checkbox_all").on('click',function(){
        if($(this).is(':checked')){
            $("input[name='delCheckbox[]']").prop("checked",true);
        }else{
            $("input[name='delCheckbox[]']").prop("checked",false);
        }
    });
    
    function Dcheckbox(e){
        var n=0;
        var len = $("input[name='delCheckbox[]']").length;
        for(var i=0;i<len;i++){
            if($("input[name='delCheckbox[]']")[i].checked) n++;
        }
        if(n==len){
            $(".checkbox_all").prop("checked",true);
        }else{
            $(".checkbox_all").prop("checked",false);
        }
    }
    
    function checkout_add(id) {
        var url = "{pigcms{:U('Materiel/checkout_add')}";
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
            title = '添加【'+name+'】物料出库';
        } else {
            title = '添加物料出库';
        }
        console.log('url',url);
        var index = layer.open({
            type: 2,
            title:title,
            content: url,
            area: ['750px', '550px'],
            maxmin: false
        });
        layer.style(index, {
            top: '100px'
        });
    }

    function checkout_list_edit(id) {
        var url = "{pigcms{:U('Materiel/checkout_list_edit')}";
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
            title = '编辑【'+name+'】物料出库';
        } else {
            title = '编辑物料出库';
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

    function return_index() {
        var checkout_id = "{pigcms{$_GET['checkout_id']}";
        var type_id = "{pigcms{$_GET['type_id']}";
        var url = "{pigcms{:U('Materiel/index')}";

        <if condition="isset($_GET['ftype_id']) AND $_GET['ftype_id']">
            url = "{pigcms{:U('Materiel/index_sub',array('ftype_id'=>$_GET['ftype_id']))}";
        </if>

        if (checkout_id > 0) {
            url = "{pigcms{:U('Materiel/checkoutList')}" + "&type_id=" + type_id;

            <if condition="isset($_GET['ftype_id']) AND $_GET['ftype_id']">
                url = "{pigcms{:U('Materiel/checkoutList',array('ftype_id'=>$_GET['ftype_id']))}"+ "&type_id=" + type_id;;
            </if>

        }
        window.location.href = url;
    }


    function close_all_page() {
        layer.closeAll();
        window.location.reload()
    }
    
    function checkout_repair(id) {
        var url = "{pigcms{:U('Materiel/checkout_repair')}";

       <if condition="isset($_GET['ftype_id']) AND $_GET['ftype_id']">
            url = "{pigcms{:U('Materiel/checkout_repair',array('ftype_id'=>$_GET['ftype_id']))}";
        </if>

        if (id && id>0) {
            url += "&id=" + id;
        }
        var type_id = "{pigcms{$_GET['type_id']}";
        if (type_id && type_id>0) {
            url += "&type_id=" + type_id;
        }
        console.log('url', url);
        window.location.href = url;
    }
</script>
<include file="Public:footer"/>

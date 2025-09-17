<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Materiel/checkout_index')}">物料出库管理</a>
            </li>
            <li class="active">物料出库列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <a href="javascript:void(0);" onclick="return_index();">返回上一级</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <if condition="$type_id gt 0">
            <a class="btn btn-success" onclick="checkout_add()">添加物料出库</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a onclick="location.href='{pigcms{:U('checkout_export',$_GET)}'" class="btn btn-success">导出领用记录</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-success" onclick="setPrintTemplate('{pigcms{$village_id}')">设置打印模板</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-success" onclick="batchPrintTemplate('{pigcms{$village_id}')">批量打印</a>
            <else/>
                <a onclick="location.href='{pigcms{:U('checkout_export',$_GET)}'" class="btn btn-success">导出领用记录</a>
            </if>
            <style>
                .ace-file-input a {display:none;}
                .btn-materiel {line-height: 18px;height: 20px;}
            </style>
            <div class="row">
                <div style="padding-top:20px;" class="form-group">
                    <form method="get" action="{pigcms{:U('Materiel/checkoutList')}">
                        <input type="hidden" value="Materiel" name="c">
                        <input type="hidden" value="checkoutList" name="a">
                        <input type="hidden" value="{pigcms{$_GET['type_id']}" name="type_id">
                         <if condition="$type_id lt 1">
                            <span style="" class="col-sm-2">物料类别：<select name="select_type_id" style="width: 160px;">
                                <option value="0">全部</option>
                                <volist name="type_msgArr" id="tto">
                                <option value="{pigcms{$tto.type_id}"  <if condition="$_GET['select_type_id'] eq $tto['type_id']">selected="true"</if> >{pigcms{$tto.type_name}</option>
                                </volist>
                            </select></span>
                         </if>
                        <div style="height:32px; width:70px;line-height: 30px;padding-left: 0" class="col-sm-1">领用人:</div>
                        <input type="text" style="margin-right:10px;font-size:16px;height:32px;" id="receive_name" name="receive_name" class="col-sm-1" value="{pigcms{$_GET['receive_name']}">
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
                                    <th width="3%" style="text-align:center">
                                        <input type="checkbox" class="checkbox_all" style="width:15px; height:15px;">
                                    </th>
                                    <th>序号</th>
                                    <th>物料类别</th>
                                    <th>领用人</th>
                                    <th>领用人联系方式</th>
                                    <th>出库时间</th>
                                    <th>出库数量</th>
                                    <th>出库详情</th>
                                    <th>剩余库存</th>
                                    <th>备注</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td align="center">
                                                <input type="checkbox" name="materielCheckbox[]" value="{pigcms{$vo.id}" onclick="return Dcheckbox($(this));">
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo['type_name']}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.receive_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.receive_phone}</div></td>
                                            <td>
                                                <if condition="$vo['receive_time'] && $vo['receive_time'] gt 100">
                                                    <div class="tagDiv">{pigcms{$vo.receive_time|date='Y-m-d H:i',###}</div>
                                                    <else/>
                                                    <div class="tagDiv">--</div>
                                                </if>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.receive_num}</div></td>
                                            <td>
                                                <div class="tagDiv">
                                                    <a style="width: 60px;" class="label label-sm label-info btn-materiel" title="出库详情" onclick="checkout_index('{pigcms{$vo.type_id}', '{pigcms{$vo.id}')">出库详情</a>
                                                </div>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.surplus_num}</div></td>
                                            <td><div class="tagDiv"><?php echo  msubstr($vo['remark'],0,15);?></div></td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="9" >还没有对应物料出库</td></tr>
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
            $("input[name='materielCheckbox[]']").prop("checked",true);
        }else{
            $("input[name='materielCheckbox[]']").prop("checked",false);
        }
    });
    
    function Dcheckbox(e){
        var n=0;
        var len = $("input[name='materielCheckbox[]']").length;
        for(var i=0;i<len;i++){
            if($("input[name='materielCheckbox[]']")[i].checked) n++;
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

    function setPrintTemplate(village_id){
        console.log('setPrintTemplate-village_id', village_id)
        var url = parent.location.href.split("#")[0]+'#/village/village.materiel/setPrintTemplate?village_id=' + village_id
        var index1 = layer.open({
            type: 2,
            area: ['45%', '450px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '设置打印模板',
            content: url
        });
        layer.style(index1, {
            top: '50px',
        });
    }
    
    function batchPrintTemplate(village_id){
        console.log('setPrintTemplate-village_id', village_id)
        var len = $("input[name='materielCheckbox[]']:checked").length;
        if(len<=0){
            var index2 = layer.open({
                content:'请至少选择一条物料领用记录!',
                btn: ['好的'],
                offset: 'auto'
            });
            layer.style(index2, {top: '50px'});
            return false;
        }
        var length = $("input[name='materielCheckbox[]']").length;
        let choiceStrings = '';
        for(var i=0;i<length;i++){
            if($("input[name='materielCheckbox[]']")[i].checked) {
                choiceStrings += "," + $("input[name='materielCheckbox[]']")[i].value;
            }
        }
        console.log('choiceStrings-village_id', choiceStrings)
        choiceStrings = choiceStrings.trim().replace(/(^,)|(,$)/g, '');
        console.log('choiceStrings-1-village_id', choiceStrings)
        var url = parent.location.href.split("#")[0]+'#/village/village.materiel/setPrintTemplate?batch=1&choiceStrings=' + choiceStrings
        var index3 = layer.open({
            type: 2,
            area: ['85%', '650px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '批量打印',
            content: url
        });
        layer.style(index3, {
            top: '50px',
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
            title = '添加【'+name+'】物料出库';
        } else {
            title = '添加物料出库';
        }
        console.log('url',url);
         var index4 = layer.open({
            type: 2,
            title:title,
            content: url,
            area: ['750px', '550px'],
            maxmin: false
        });
        layer.style(index4, {
            top: '50px',
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
    
    function checkout_index(type_id, checkout_id) {
        console.log('type_id',type_id);
        console.log('checkout_id',checkout_id);
        var url = "{pigcms{:U('Materiel/checkout_index')}";
                
        <if condition="isset($_GET['ftype_id']) AND $_GET['ftype_id']">
            url = "{pigcms{:U('Materiel/checkout_index',array('ftype_id'=>$_GET['ftype_id']))}";
        </if>

        url=url+"&type_id=" + type_id + '&checkout_id=' + checkout_id;
        console.log('url',url);
        window.location.href = url;
    }
</script>
<include file="Public:footer"/>

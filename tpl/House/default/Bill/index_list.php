<include file="NewPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="">物业管理</a>
            </li>
            <li class="active">物业中心</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" action="{pigcms{:U('Bill/index_list',array('iframe'=> $_GET['iframe']))}" id="find-form">
                    <input type="hidden" name="c" value="Bill"/>
                    <input type="hidden" name="a" value="index_list"/>
                    <input type="hidden" name="iframe" value="{pigcms{$_GET['iframe']}"/>

					<input value="{pigcms{$title}" class="col-sm-2" name="title" id="" type="text" placeholder="请输入费用名称" style="margin-right:10px;font-size:16px;height:35px;"/>
                    状态：
                    <select name="bill_type" style="height:35px">
                        <option value="0" <if condition="$_GET['bill_type'] eq '0'">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['bill_type'] eq '1'">selected="selected"</if>>支出</option>
                        <option value="2" <if condition="$_GET['bill_type'] eq '2'">selected="selected"</if>>收入</option>
                    </select>
                    &nbsp;&nbsp;
                    时间筛选：
                    <input type="text" name="add_time_start" id="add_time_start" class="input-text" value="{pigcms{$add_time_start}"  style="height:35px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
                    &nbsp;—&nbsp;
                    <input type="text" name="add_time_end" id="add_time_end" class="input-text" value="{pigcms{$add_time_end}"  style="height:35px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
					<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('Bill/index_list',array(\'iframe\'=> $_GET[\'iframe\']))}'">重置</a>
				</form>

			</div>

			<button class="btn btn-success" onclick="add_bill()">添加收支记录</button>&nbsp;

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>

                                    <th width="5%">费用名称</th>
                                    <th width="5%">类型</th>
                                    <th width="5%">单价</th>
                                    <th width="5%">数量</th>
                                    <th width="5%">金额</th>
                                    <th width="5%">收款方</th>
                                    <th width="5%">付款方</th>
                                    <th width="5%">付款时间</th>
                                    <th width="5%">记录人</th>
                                    <th width="5%">备注</th>
                                    <th width="5%">附件</th>


                                    <th width="5%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo['title']}</div></td>
                                            <td>
                                                <if condition="$vo['bill_type'] == 1">
                                                    <div class="tagDiv">支出</div>
                                                <else/>
                                                    <div class="tagDiv">收入</div>
                                                </if>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.unit_price}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.num}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.price}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.payee_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.payer_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.payment_time|date="Y-m-d H:i:s",###}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.operation_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.desc}</div></td>
                                            <td>
                                                <if condition="$vo['image'] neq ''">
                                                <div class="tagDiv"><a href="javascript:;" onclick="bill_info({pigcms{$vo.id})">查看附件</a> </div>
                                                <else/>
                                                <div class="tagDiv">暂无附件</div>
                                                </if>
                                            </td>
                                <td>
                                    <div class="tagDiv">
                       <a href="javascript:;" style="width: 60px;" class="label label-sm label-info" title="修改" onclick="edit_bill({pigcms{$vo.id})">修改</a>
                                        &nbsp;
                                        <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('bill_del',array('id'=>$vo['id']))}'}">删除</a>
                                    </div>
                                </td>
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                           
                        </table>
                        <if condition="$dataList">
<!--                            <div style="float: right; padding-left: 5px; ">-->
<!--                                <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">-->
<!--                                <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>-->
<!--                                <script>-->
<!--                                    function checkPage(){-->
<!--                                        var page = $("#page").val();-->
<!--                                        if(page == ''){-->
<!--                                            return false;-->
<!--                                        }-->
<!--                                        location.href = "{pigcms{:U('index')}"+"&page="+page;-->
<!--                                    }-->
<!--                                </script>-->
<!--                            </div>-->
                             {pigcms{$pagebar}
                        </if>
                            <table class="table table-striped table-bordered table-hover" style="position:relative;">
                                <thead style="background-color: #D9EDF7;">
                                <tr>
<!--                                    <th width="33%" style="background-color: #D9EDF7;">统计</th>-->
                                    <th width="33%" style="background-color: #D9EDF7;">统计收入</th>
                                    <th width="33%" style="background-color: #D9EDF7;">统计支出</th>
                                </tr>
                                </thead>
                                <tbody class="class-checkbox" style="background-color: #D9EDF7;">
                                    <tr class="" style="background-color: #D9EDF7;">
<!--                                        <td style="background-color: #D9EDF7;"><div class="tagDiv">合计：{pigcms{$bill_type}</div></td>-->
                                        <td style="background-color: #D9EDF7;">
                                            <if condition="$bill_type0 gt 0">
                                                <div class="tagDiv">{pigcms{$bill_type0}</div>
                                                <else/>
                                                <div class="tagDiv">0.00</div>
                                            </if>
                                        </td>
                                        <td style="background-color: #D9EDF7;">
                                            <if condition="$bill_type1 gt 0">
                                                <div class="tagDiv">{pigcms{$bill_type1}</div>
                                                <else/>
                                                <div class="tagDiv">0.00</div>
                                            </if>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
<script type="text/javascript" src="/tpl/System/Static/js/area_pca.js"></script>
<script>

	var static_path = "{pigcms{$static_path}";


    var property_ids = "{pigcms{$property_id}"
</script>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript" language="javascript">

function add_bill(){
    var url = "{pigcms{:U('Bill/bill_add',array('iframe'=> $_GET['iframe']))}";

    layer.open({
        type: 2,
        title:'添加',
        content: url,
        area: ['750px', '550px'],
        maxmin: false
    });

}

function edit_bill(id){

    var url = "{pigcms{:U('Bill/bill_edit',array('iframe'=> $_GET['iframe']))}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'修改',
        content: href,
        area: ['750px', '550px'],
        maxmin: false
    });

}

function bill_info(id){

    var url = "{pigcms{:U('Bill/bill_info',array('iframe'=> $_GET['iframe']))}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'查看附件',
        content: href,
        area: ['750px', '550px'],
        maxmin: false
    });

}

function close_all_page() {
    layer.closeAll();
    window.location.href = "{pigcms{:U('Bill/index_list',array('iframe'=> $_GET['iframe']))}";
}

$('.bind_info').click(function(){

    var tipTitle = '添加收支记录';
    art.dialog.open($(this).attr('href'),{
        init: function(){
            var iframe = this.iframe.contentWindow;
            window.top.art.dialog.data('iframe_handle_',iframe);
        },
        id: 'handle_',
        title:tipTitle,
        padding: 0,
        width: 800,
        height: 603,
        lock: true,
        resize: false,
        background:'black',
        button: null,
        fixed: false,
        close: null,
        left: '50%',
        top: '38.2%',
        opacity:'0.4',
        cancel: function () {
            window.location.reload()
        }
    });
    return false;
});

//删除
function del_people(id){
    console.log(id);
    layer.open({
        content: '确认删除吗！删除后数据无法回复！',
        shadeClose: false,
        closeBtn: 0
        ,btn: ['确定', '取消']
        ,yes:function(index){
            $.ajax({
                type : "POST",
                url : "{pigcms{:U('Bill/bill_del')}",
                data : {"id":id},
                dataType:'json',
                success : function(res) {
                    console.log(res.code);
                    if(res.code == 1){
                        window.location.href="{pigcms{:U('Bill/bill_list',array('iframe'=> $_GET['iframe']))}"
                    }
                }
            });
        }
    });
}


KindEditor.ready(function(K){
    console.log("{pigcms{$config.site_url}");
    console.log("{pigcms{:U('Bill/ajax_upload_pic')}");


    K('#J_selectImage').click(function(){

        if($('.upload_pic_li').size() >= 5){
            alert('最多上传5个图片！');
            return false;
        }
        kind_editor.uploadJson ="{pigcms{$config.site_url}{pigcms{:U('Bill/ajax_upload_pic')}";
        kind_editor.loadPlugin('image', function(){
            kind_editor.plugin.imageDialog({
                showRemote : false,
                imageUrl : K('#course_pic').val(),
                clickFn : function(url, title, width, height, border, align) {
                    $('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImg(\''+title+'\',this);return false;">[ 删除 ]</a></li>');
                    kind_editor.hideDialog();
                }
            });
        });
    });
});
</script>
<include file="NewPublic:footer"/>

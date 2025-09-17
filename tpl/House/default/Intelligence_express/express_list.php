<include file="Public:header"/>
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
            <button class="btn btn-success" onclick="express_add()">添加智能快递柜</button>&nbsp;

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                            <tr>

                                <th width="5%">序号</th>
                                <th width="5%">设备ID</th>
                                <th width="5%">设备名</th>
                                <th width="5%">屏幕尺寸</th>
                                <th width="5%">状态</th>
                                <th width="5%">位置坐标</th>
                                <th width="5%">添加时间</th>
                                <th width="5%">最后上报时间</th>
                                <th width="5%">快递柜管理</th>
                                <th width="5%">柜子数量</th>



                                <th width="5%">操作</th>
                            </tr>
                            </thead>
                            <tbody class="class-checkbox">
                            <if condition="$dataList">
                                <volist name="dataList" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo['id']}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_id}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.equipment_name}</div></td>
                            <td><div class="tagDiv">
                                    <volist name="device_type_info" id="v1">
                                        <if condition="$v1['value'] eq $vo['screen_size_type']">
                                            <option>{pigcms{$v1['name']}</option>
                                        </if>
                                    </volist>
                                </div>
                            </td>
                            <td>
                                <if condition="$vo['status'] == 1">
                                    <div class="tagDiv">正常</div>
                                    <else/>
                                    <div class="tagDiv">禁止</div>
                                </if>
                            </td>
                            <td>
                                <if condition="$vo.long AND $vo.lat">
                                    <div class="tagDiv">{pigcms{$vo.long},{pigcms{$vo.lat}</div>
                                    <else/>
                                    <div class="tagDiv">-</div>
                                </if>

                            </td>

                            <td>
                                <if condition="$vo['create_time'] gt 0">
                                    <div class="tagDiv">{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}</div>
                                </if>
                            </td>
                            <td>
                                <if condition="$vo['report_time'] gt 0">
                                    <div class="tagDiv">{pigcms{$vo.report_time|date="Y-m-d H:i:s",###}</div>
                                </if>
                            </td>
                            <td>
                                <div class="tagDiv">
                                    <if condition="$vo['status'] eq 1">
                                        <a href="{pigcms{:U('Intelligence_express/express_index',array('id'=>$vo['id']))}" target="" style="color:red;">管理入口</a>
                                        <else/>
                                        禁止状态
                                    </if>
                                </div>
                            </td>
                            <td><div class="tagDiv">{pigcms{$vo.num}</div></td>

                            <td>
                                <div class="tagDiv">
                                    <a href="javascript:;" style="width: 60px;" class="label label-sm label-info" title="修改" onclick="express_edit({pigcms{$vo.id})">修改设备</a>
                                    &nbsp;
                                    <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该快递柜的全部信息,请谨慎操作？')){location.href='{pigcms{:U('express_del',array('id'=>$vo['id']))}'}">删除</a>
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
                            {pigcms{$pagebar}
                        </if>

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

    function express_add(){
        var url = "{pigcms{:U('Intelligence_express/express_add')}";

        layer.open({
            type: 2,
            title:'添加',
            content: url,
            area: ['850px', '650px'],
            maxmin: false
        });

    }

    function express_edit(id){

        var url = "{pigcms{:U('Intelligence_express/express_edit')}";
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

        var url = "{pigcms{:U('Intelligence_express/bill_info')}";
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
        window.location.href = "{pigcms{:U('Intelligence_express/express_list')}";
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
                    url : "{pigcms{:U('Intelligence_express/express_del')}",
                    data : {"id":id},
                    dataType:'json',
                    success : function(res) {
                        console.log(res.code);
                        if(res.code == 1){
                            window.location.href="{pigcms{:U('Intelligence_express/express_list')}"
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
<include file="Public:footer"/>

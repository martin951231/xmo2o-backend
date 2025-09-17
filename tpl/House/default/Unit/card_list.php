<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('import_village')}">物业管理</a>
            </li>
            <li class="active">房间管理</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content" style="height: 440px">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <input  name="type" id="vacancy_id" type="hidden"  value="{pigcms{$_GET['vacancy_id']}" />
            <button class="btn btn-success" onclick="readCard()">添加IC卡</button>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">设备品牌</th>
                                    <th width="5%">设备类型</th>
                                    <th width="5%">IC卡号</th>
                                    <th width="5%">操作</th>

                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.device_brand}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.device_type}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.ic_card}</div></td>
                                            <td class="button-column">
												<div class="tpl-table-black-operation">
													<if condition="in_array(40,$house_session['menus'])">
														&nbsp;&nbsp;<a title="删除" class="item-delete tpl-table-black-operation-del" onclick="delItemData('{pigcms{$vo.ic_card}','{pigcms{$vo.id}')" href="javascript:;" >删除</a>
													</if>
												</div>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何信息。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$result.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose.js?t=001"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>

<script>

    function readCard() {
        console.log(111);
        var url = "{pigcms{:U('User/readCard')}";
        var page = layer.open({
            type: 2,
            area: ['650px', '450px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '读取卡号',
            content: url+'&type=1&vacancy_id='+$('#vacancy_id').val(),
        });
        layer.style(page, {top: '20px'});
    }

    var village_del_url= "{pigcms{:U('iccard_del')}";
    function delItemData(room,idd){
        layer.confirm('确认删除卡号是【'+room+'】这条数据？<br/>一旦删除无法恢复，请谨慎操作。', {
            btn: ['确定','取消'] //按钮
        }, function(){
            $.ajax({
                url:village_del_url+'&id='+idd,
                type:"get",
                dataType:"JSON",
                success:function(data){
                    if(data['status'] == 0){
                        alert(data.msg);
                    }else{
                        alert(data.msg);
                        window.location.reload();
                    }
                }
            });
            // window.location.href=village_del_url+'&id='+idd;
        });
    }
</script>
<include file="Public:footer"/>

<include file="Public:header"/>
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <i class="ace-icon fa fa-cloud"></i>
            <li class="active">{pigcms{:L_("微硬件")}</li>
            <li class="active">{pigcms{:L_("景区闸机管理")}</li>
        </ul>
    </div>

    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <div class="tab-content">
                            <div class="tab-pane active">
                                <a href="{pigcms{:U('Gate/add')}" class="btn btn-success">{pigcms{:L_("添加闸机管理")}</a>
                                <div id="shopList" class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th width="100">{pigcms{:L_("闸机名称")}</th>
                                            <th width="100">{pigcms{:L_("闸机设备ID")}</th>
                                            <th width="100">{pigcms{:L_("添加时间")}</th>
                                            <th width="80" class="button-column">{pigcms{:L_("操作")}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <if condition="$list">
                                            <volist name="list" id="row">
                                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td>{pigcms{$row.name}</td>
                                        <td>{pigcms{$row.gid}</td>
                                        <td>{pigcms{$row.create_time}</td>
                                        <td class="button-column">
                                            <a title="{pigcms{:L_("编辑")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Gate/add', array('id' => $row['id']))}" >
                                                <i class="ace-icon fa fa-pencil bigger-130"></i>
                                            </a>
                                            <a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Gate/delete', array('id' => $row['id']))}">
                                            <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                            </a>
                                        </td>
                                        </tr>
                                        </volist>
                                        <else/>
                                        <tr class="odd"><td class="button-column" colspan="7" >{pigcms{:L_("无内容")}</td></tr>
                                        </if>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function(){
        jQuery(document).on('click','#shopList a.red',function(){
            if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
        });
        updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");
    });
    function drop_confirm(msg, url)
    {
        if (confirm(msg)) {
            window.location.href = url;
        }
    }
    function updateStatus(dom1, dom2, status1, status2, attribute){
        $(dom1).each(function(){
            if($(this).attr("data-status")==status1){
                $(this).attr("checked",true);
            }else{
                $(this).attr("checked",false);
            }
            $(dom2).show();
        }).click(function(){
            var _this = $(this),
                type = 'open',
                id = $(this).attr("data-id");
            _this.attr("disabled",true);
            if(_this.attr("checked")){	//"开启")}
                type = 'open';
            }else{		//"关闭")}
                type = 'close';
            }
            $.ajax({
                url:"{pigcms{:U('Hardware/print_status')}",
                type:"post",
                data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
                dataType:"text",
                success:function(d){
                    if(d != '1'){		//"失败")}
                        if(type=='open'){
                            _this.attr("checked",false);
                        }else{
                            _this.attr("checked",true);
                        }
                        bootbox.alert("{pigcms{:L_("操作失败")}");
                    }
                    _this.attr("disabled",false);
                }
            });
        });
    }
</script>
<include file="Public:footer"/>

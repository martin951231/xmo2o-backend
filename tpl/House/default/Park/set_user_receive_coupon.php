<include file="Public:header"/>

<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Index/index')}">社区管理</a>
            </li>
            <li class="active">基本信息设置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">

        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                            <li <if condition="!$_GET['type'] || $_GET['type'] eq 'basicinfo'"> class="active" </if> >
                                <a href="{pigcms{:U('Park/set_user_receive_coupon')}&type=basicinfo" >店铺列表</a> <!--data-toggle="tab"-->
                            </li>
                            <li <if condition="$_GET['type'] eq 'txtstore' "> class="active" </if>>
                                <a href="{pigcms{:U('Park/set_user_receive_coupon')}&type=txtstore">优惠券列表</a>
                            </li>
                            <li <if condition="$_GET['type'] eq 'txtpwd' "> class="active" </if> >
                                <a href="{pigcms{:U('Park/set_user_receive_coupon')}&type=txtpwd">购买记录</a>
                            </li>
                        </ul>
                    </div>
                        <div class="tab-content">
                            <div <if condition="!$_GET['type'] || $_GET['type'] eq 'basicinfo'"> class="tab-pane active" <else/> class="tab-pane" </if> id="basicinfo">
                                <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                                    <form method="get" action="{pigcms{:U('Park/set_user_receive_coupon')}" id="find-form">
                                        <input type="hidden" name="c" value="Park"/>
                                        <input type="hidden" name="a" value="set_user_receive_coupon"/>
                                        <input type="hidden" name="type" value="basicinfo"/>
                                        <span style="margin-left: 10px;">店铺名称：</span>
                                        <input value="{pigcms{$_GET['name']}" name="name" id="name" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                                        &nbsp;&nbsp;
                                        时间筛选：
                                        <input type="text" name="start_time" class="input-text" value="{pigcms{$_GET['start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
                                        &nbsp;—&nbsp;
                                        <input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                        <input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                                        <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/set_user_receive_coupon')}&type=basicinfo'">重置</button>
                                    </form>
                                </div>
                                <a class="btn btn-success" href="javascript:;" style="margin-bottom: 10px" onclick="add_shop()">添加店铺</a>
                                <div id="shopList" class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th width="20%">店铺名称</th>
                                            <th width="15%">店铺管理二维码</th>
                                            <th width="15%">添加时间</th>
                                            <th width="15%">备注</th>
                                            <th class="button-column" width="15%">操作</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <if condition="$list['list']">
                                            <volist name="list['list']" id="vo">
                                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td><div class="tagDiv">{pigcms{$vo.m_name}</div></td>
                                        <td><div class="tagDiv">
<!--                                                <a href="javascript:;" onclick="look_img('{pigcms{$vo.turn_url}')">查看</a>-->
                                                <a href="{pigcms{$vo.turn_url}" class="see_qrcode">{pigcms{:L_("查看二维码")}</a>
                                            </div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.remark}</div></td>
                                        <td class="button-column">
                                            <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="javascript:;" onclick="edit_shop({pigcms{$vo['m_id']})">编辑</a>&nbsp;
                                            <a style="width: 80px;" class="label label-sm label-info" title="优惠券管理" href="javascript:;" onclick="pack_shop_coupons('{pigcms{$vo.m_id}')">优惠券管理</a>&nbsp;
                                            <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('shop_del',array('m_id'=>$vo['m_id']))}'}">删除</a>
                                        </td>
                                        </tr>
                                        </volist>
                                        <else/>
                                        <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
                                        </if>
                                        </tbody>
                                    </table>
                                    {pigcms{$list.pagebar}
                                </div>
                            </div>
                            <div <if condition="$_GET['type'] eq 'txtstore'"> class="tab-pane active" <else/> class="tab-pane" </if> id="txtstore">
                                <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                                    <form method="get" action="{pigcms{:U('Park/set_user_receive_coupon')}" id="find-form">
                                        <input type="hidden" name="c" value="Park"/>
                                        <input type="hidden" name="a" value="set_user_receive_coupon"/>
                                        <input type="hidden" name="type" value="txtstore"/>
                                        <span style="margin-left: 10px;">优惠券名称：</span>
                                        <input value="{pigcms{$_GET['c_title']}" name="c_title" id="c_title" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                                        &nbsp;&nbsp;
                                        时间筛选：
                                        <input type="text" name="c_start_time" class="input-text" value="{pigcms{$_GET['c_start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
                                        &nbsp;—&nbsp;
                                        <input type="text" name="c_end_time" class="input-text" value="{pigcms{$_GET['c_end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                        <input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                                        <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/set_user_receive_coupon')}&type=txtstore'">重置</button>
                                    </form>
                                </div>
                                <a class="btn btn-success" href="javascript:;" style="margin-bottom: 10px" onclick="add_coupons()">添加优惠券</a>
                                <div id="shopList" class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th width="20%">优惠券名称</th>
                                            <th width="15%">单价</th>
                                            <th width="15%">每次停车免费金额</th>
                                            <th width="10%">添加时间</th>
                                            <th width="10%">备注</th>
                                            <th width="10%">状态</th>
                                            <th class="button-column" width="15%">操作</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <if condition="$list_coupons['list']">
                                            <volist name="list_coupons['list']" id="vo">
                                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td><div class="tagDiv">{pigcms{$vo.c_title}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.c_price}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.c_free_price}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>
                                        <td><div class="tagDiv"><if condition="$vo.remark">{pigcms{$vo.remark}<else/>--</if></div></td>
                                        <td><div class="tagDiv">
                                                <if condition="$vo.status eq 0">
                                                    <span style="color: #276dff">正常</span>
                                                    <else/>
                                                    <span style="color: red">禁止</span>
                                                </if>
                                            </div>
                                        </td>
                                        <td class="button-column">
                                            <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="javascript:;" onclick="edit_coupons({pigcms{$vo['c_id']})">编辑</a>&nbsp;
                                            <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('删除后已购买的优惠券将无法领取,确认删除该优惠券？')){location.href='{pigcms{:U('del_pack_coupons',array('c_id'=>$vo['c_id']))}'}">删除</a>
                                        </td>
                                        </tr>
                                        </volist>
                                        <else/>
                                        <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
                                        </if>
                                        </tbody>
                                    </table>
                                    {pigcms{$list_coupons.pagebar}
                                </div>
                            </div>
                            <div <if condition="$_GET['type'] eq 'txtpwd'"> class="tab-pane active" <else/> class="tab-pane" </if> id="txtpwd">
                                <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                                    <form method="get" action="{pigcms{:U('Park/set_user_receive_coupon')}" id="find-form">
                                        <input type="hidden" name="c" value="Park"/>
                                        <input type="hidden" name="a" value="set_user_receive_coupon"/>
                                        <input type="hidden" name="type" value="txtpwd"/>
                                        <select name="param">
                                            <option value="c_title">优惠券名称</option>
                                            <option value="m_name">店铺名称</option>
                                        </select>
                                        <input value="{pigcms{$_GET['title']}" name="title" id="title" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                                        &nbsp;&nbsp;
                                        时间筛选：
                                        <input type="text" name="pay_start_time" class="input-text" value="{pigcms{$_GET['pay_start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
                                        &nbsp;—&nbsp;
                                        <input type="text" name="pay_end_time" class="input-text" value="{pigcms{$_GET['pay_end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                        <input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                                        <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Park/set_user_receive_coupon')}&type=txtpwd'">重置</button>
                                    </form>
                                </div>
                                <span>总购买数量：{pigcms{$pay_record.record.total_pay_num}；应收金额：{pigcms{$pay_record.record.total_receivable_money}元；实收金额：{pigcms{$pay_record.record.total_paid_money}元</span>
                                <div id="shopList" class="grid-view">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                        <tr>
                                            <th width="10%">编号</th>
                                            <th width="20%">优惠券名称</th>
                                            <th width="15%">购买店铺</th>
                                            <th width="15%">购买数量</th>
                                            <th width="10%">应收金额</th>
                                            <th width="10%">实收金额</th>
                                            <th width="10%">支付时间</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <if condition="$pay_record['list']">
                                            <volist name="pay_record['list']" id="vo">
                                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                    <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                                    <td><div class="tagDiv">{pigcms{$vo.c_title}</div></td>
                                                    <td><div class="tagDiv">{pigcms{$vo.m_name}</div></td>
                                                    <td><div class="tagDiv">{pigcms{$vo.num}</div></td>
                                                    <td><div class="tagDiv">{pigcms{$vo.receivable_money}</div></td>
                                                    <td><div class="tagDiv">{pigcms{$vo.paid_money}</div></td>
                                                    <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>
                                                </tr>
                                            </volist>
                                        <else/>
                                        <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
                                        </if>
                                        </tbody>
                                    </table>
                                    {pigcms{$pay_record.pagebar}
                                </div>

                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<style>
    .BMap_cpyCtrl{display:none;}
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF!important;
        font-family: "sans serif",tahoma,verdana,helvetica;
        font-size: 12px;
        line-height: 24px;
        height: 24px;
        padding: 2px 4px;
        border-color: #848484 #E0E0E0 #E0E0E0 #848484;
        border-style: solid;
        border-width: 1px;
        display: -moz-inline-stack;
        display: inline-block;
        vertical-align: middle;
        zoom: 1;
    }
    .form-group>label{font-size:12px;line-height:24px;}
    #upload_pic_box{margin-top:20px;height:150px;}
    #upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
    #upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>

    $(function(){
        var is_temporary_park_type = "{pigcms{$village_park_config['is_temporary_park_type']}";

        console.log(is_temporary_park_type);
        if(is_temporary_park_type == 1){
            $("#is_temporary_park_type").show();
        }else{
            $("#is_temporary_park_type").hide();
        }

    })


    $('input[name="is_temporary_park_type"]').change(function(){
        if($(this).val()==1){
            $("#is_temporary_park_type").show();
        }else{
            $("#is_temporary_park_type").hide();
        }
    })

    function park_config(){
        window.location.href = "{pigcms{:U('park_config')}";
    }

    function preferential_list(){
        window.location.href = "{pigcms{:U('park_month_list')}";
    }

    function park_temporary_list(){
        window.location.href = "{pigcms{:U('park_temporary_list')}";
    }

    function vehicle_management(){
        window.location.href = "{pigcms{:U('Unit/vehicle_management')}";
    }


</script>
<script type="text/javascript">
    //店铺列表 start
    //添加
    function add_shop(){
        var url = "{pigcms{:U('Park/add_pack_shop')}";
        layer.open({
            type: 2,
            title:'添加店铺',
            content: url,
            area: ['750px', '685px'],
            maxmin: false
        });
    }
    //删除
    function del() {
        layer.open({
            title:'删除',
            area: ['450px', '260px'],
            maxmin: false,
            btn:['确定','取消'],
            shadeClose: false,
            closeBtn: 0,
            yes:function(index,layero){

            }
        });
    }
    //编辑
    function edit_shop(id) {

        var url = "{pigcms{:U('Park/edit_pack_shop')}&m_id="+id;
        layer.open({
            type: 2,
            title:'编辑店铺',
            content: url,
            area: ['750px', '685px'],
            maxmin: false
        });
    }
    function pack_shop_coupons(id) {
        var url = "{pigcms{:U('Park/pack_shop_coupons')}&m_id="+id;
        layer.open({
            type: 2,
            title:'优惠券管理',
            content: url,
            area: ['900px', '700px'],
            maxmin: false
        });
    }
    //优惠券
    //添加
    function add_coupons(){
        var url = "{pigcms{:U('Park/add_pack_coupons')}";
        layer.open({
            type: 2,
            title:'添加优惠券',
            content: url,
            area: ['750px', '485px'],
            maxmin: false
        });
    }
    //删除
    function del_coupons() {
        layer.open({
            title:'删除',
            area: ['450px', '260px'],
            maxmin: false,
            btn:['确定','取消'],
            shadeClose: false,
            closeBtn: 0,
            yes:function(index,layero){

            }
        });
    }
    //编辑
    function edit_coupons(id) {

        var url = "{pigcms{:U('Park/edit_pack_coupons')}&c_id="+id;
        layer.open({
            type: 2,
            title:'编辑优惠券',
            content: url,
            area: ['750px', '485px'],
            maxmin: false
        });
    }

    //查看二维码
    function look_img(url){
        //页面层-图片
        layer.open({
            type: 1,
            title: false,
            area: ['310px', '380px'],
            closeBtn: 0,
            // skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content: '<p style="text-align: center;margin-top: 10px;">点击保存发给人员填写</p><img style="margin-left: 5px;" src='+ url +' /><br><a class="btn btn-success" href="'+url+'" download="'+code_num+'.png" style="margin-left: 40%">点击下载</a>'//
        });
    }
    //商家列表 end
    $(document).ready(function(){

        $('.see_qrcode').live('click',function(){
            // var url = '{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode_html&down=true&qrCon=';
            var url = '/index.php?c=Recognition&a=get_own_qrcode&qrCon=';
            var qrcode_urls = "{pigcms{$qrcode_url}";
            console.log('qrcode_urls',$(this).attr('href'));
            var hou_url = qrcode_urls + $(this).attr('href');
            var qrcode_url = url+encodeURIComponent(hou_url);
            console.log(qrcode_url);
            layer.open({
                type: 1,
                title: '查看',
                area: ['350px', '420px'],
                closeBtn: 0,
                shadeClose: true,
                content:  '<img style="margin:10px 0px 10px 35px;width: 280px;" src="'+qrcode_url+'&down=true"><br/><p style="margin-left: 24px;width: 310px;font-size: 20px;font-weight:bold">店铺商家扫码，进入该码保存页面链接，可实时查看优惠券使用情况</p>'
            });

            // art.dialog.open(qrcode_url,{
            //     init: function(){
            //         var iframe = this.iframe.contentWindow;
            //         window.top.art.dialog.data('iframe_handle',iframe);
            //     },
            //     id: 'handle',
            //     title:'{pigcms{:L_("如需保存，鼠标右键点击图片另存为即可")}',
            //     padding: 0,
            //     width: 312,
            //     height: 312,
            //     lock: true,
            //     resize: false,
            //     background:'black',
            //     button: null,
            //     fixed: false,
            //     close: null,
            //     left: '50%',
            //     top: '38.2%',
            //     opacity:'0.4'
            // });
            return false;
        });

    });


</script>

<include file="Public:footer"/>
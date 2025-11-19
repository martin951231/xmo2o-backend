<include file="Public:header" />
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="">小区管理</a>
            </li>
            <li class="active">小区中心</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="box">
                <div class="text">
                    该快递柜共{pigcms{$express_num_count}个柜子，其中大柜：{pigcms{$express_num_count_max}个；中柜：{pigcms{$express_num_count_in}个；小柜：{pigcms{$express_num_count_min}个 </br>
                    剩余{pigcms{$express_un_num_count}个柜子，其中大柜：{pigcms{$express_un_num_count_max}个；中柜：{pigcms{$express_un_num_count_in}个；小柜：{pigcms{$express_un_num_count_min}个
                </div>
                <button class="btn1">一键置空</button>
            </div>
            <div class="box box1">
                <volist name="dataList" id="vo">
                    <if condition="$vo['is_null'] eq 1">
                        <div class="big-box" style="background: #73c3e2;">
                            <if condition="$vo['type'] eq 0">
                                <div class="text1" style="color:#000000">{pigcms{$vo.number_id}号柜子(小)</div>
                                <elseif condition="$vo['type'] eq 1"/>
                                <div class="text1" style="color:#000000">{pigcms{$vo.number_id}号柜子(中)</div>
                                <elseif condition="$vo['type'] eq 2"/>
                                <div class="text1" style="color:#000000">{pigcms{$vo.number_id}号柜子(大)</div>
                            </if>

                            <div class="btn-box">
                                <button class="mini-btn">开门</button>
                                <button class="mini-btn"><a href="javascript:;"onclick="express_num_edit({pigcms{$vo.id})">编辑</a></button>
                                <button class="mini-btn"><a href="{pigcms{:U('Intelligence_express/express_num_log_list',array('id'=>$vo['id']))}" target="" style="color:red;">记录</a></button>
                            </div>
                        </div>
                    <else/>
                        <div class="big-box" style="background: #f6f6f6;">
                            <if condition="$vo['type'] eq 0">
                                <div class="text1" style="color:#000000">{pigcms{$vo.number_id}号柜子(小)</div>
                                <elseif condition="$vo['type'] eq 1"/>
                                <div class="text1" style="color:#000000">{pigcms{$vo.number_id}号柜子(中)</div>
                                <elseif condition="$vo['type'] eq 2"/>
                                <div class="text1" style="color:#000000">{pigcms{$vo.number_id}号柜子(大)</div>
                            </if>
                            <div class="btn-box">
                                <button class="mini-btn">开门</button>
                                <button class="mini-btn"><a href="javascript:;"onclick="express_num_edit({pigcms{$vo.id})">编辑</a></button>
                                <button class="mini-btn"><a href="{pigcms{:U('Intelligence_express/express_num_log_list',array('id'=>$vo['id']))}" target="" style="color:red;">记录</a></button>
                            </div>
                        </div>
                    </if>
                </volist>
<!--                <div class="big-box">-->
<!--                    <div class="text1">1号柜子(中)</div>-->
<!--                    <div class="btn-box">-->
<!--                        <button class="mini-btn">开门</button>-->
<!--                        <button class="mini-btn">编辑</button>-->
<!--                        <button class="mini-btn">记录</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                    <div class="text1">1号柜子(中)</div>-->
<!--                    <div class="btn-box">-->
<!--                        <button class="mini-btn">开门</button>-->
<!--                        <button class="mini-btn">编辑</button>-->
<!--                        <button class="mini-btn">记录</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                    <div class="text1">1号柜子(中)</div>-->
<!--                    <div class="btn-box">-->
<!--                        <button class="mini-btn">开门</button>-->
<!--                        <button class="mini-btn">编辑</button>-->
<!--                        <button class="mini-btn">记录</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                    <div class="text1">1号柜子(中)</div>-->
<!--                    <div class="btn-box">-->
<!--                        <button class="mini-btn">开门</button>-->
<!--                        <button class="mini-btn">编辑</button>-->
<!--                        <button class="mini-btn">记录</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                    <div class="text1">1号柜子(中)</div>-->
<!--                    <div class="btn-box">-->
<!--                        <button class="mini-btn">开门</button>-->
<!--                        <button class="mini-btn">编辑</button>-->
<!--                        <button class="mini-btn">记录</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                    <div class="text1">1号柜子(中)</div>-->
<!--                    <div class="btn-box">-->
<!--                        <button class="mini-btn">开门</button>-->
<!--                        <button class="mini-btn">编辑</button>-->
<!--                        <button class="mini-btn">记录</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                        <div class="text1">1号柜子(中)</div>-->
<!--                        <div class="btn-box">-->
<!--                            <button class="mini-btn">开门</button>-->
<!--                            <button class="mini-btn">编辑</button>-->
<!--                            <button class="mini-btn">记录</button>-->
<!--                        </div>-->
<!--                </div>-->
<!--                <div class="big-box">-->
<!--                        <div class="text1">1号柜子(中)</div>-->
<!--                        <div class="btn-box">-->
<!--                            <button class="mini-btn">开门</button>-->
<!--                            <button class="mini-btn">编辑</button>-->
<!--                            <button class="mini-btn">记录</button>-->
<!--                        </div>-->
<!--                </div>-->
            </div>
            <div class="box box2">
                <div class="text2">
                    蓝色背景表示柜子已装入物品,灰色背景表示为空柜子
                </div>
            </div>



            <!-- <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                            
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr>
                                            <td><div class="">开门</div></td>
                                            <td><div class=""> <a href="javascript:;" class="" title="修改" onclick="express_num_edit({pigcms{$vo.id})">编辑</a></div></td>
                                            <td><div class=""><a href="{pigcms{:U('Intelligence_express/express_num_log_list',array('id'=>$vo['id']))}" target="" style="color:red;">记录</a></div></td>
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
                                </if>
                        </table>
                           
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</div>
<style>
    .box {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }

    .text {
        color: black;
        font-weight: 700;
    }

    .btn1 {
        color: #ffffff;
        line-height: 25px;
        border-radius: 10px;
        border: 0;
        background-color: rgb(14, 156, 214);
        padding: 0px 8px;
    }

    .box1 {
        margin-top: 30px;
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    .big-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        /*background: rgb(116, 196, 231);*/
        margin-left: 5px;
        flex-shrink: 0;
        margin-top: 5px;
    }
    /*.big-box:nth-child(3n-1) {*/
        /*background: rgb(246, 246, 246);*/
    /*}*/
    /*.big-box:nth-child(3n-1) .text1 {*/
        /*color: black;*/
    /*}*/
    .text1 {
        color: #ffffff;
        font-weight: 700;
        padding: 40px 15px;
    }

    .btn-box {
        margin: 10px 0px;
        margin-right: 5px;
    }

    .mini-btn {
        color: black;
        padding: 3px 8px;
        border-radius: 5px;
        border: 0;
        margin-left: 5px;
        background: #ffffff;
    }
    .text2 {
        color: black;
        font-weight: 700;
        margin: 20px 0;
    }
    .box2 {
        justify-content: flex-end;
    }
</style>
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

    var express_num_id = "{pigcms{$express_num_id}";
    conosle.log(express_num_id);
    function express_num_edit(id) {

        var url = "{pigcms{:U('Intelligence_express/express_num_edit')}";
        var href = url + '&id=' + id
        layer.open({
            type: 2,
            title: '修改',
            content: href,
            area: ['750px', '550px'],
            maxmin: false
        });

    }



    function close_all_page() {
        layer.closeAll();
        var url = "{pigcms{:U('Intelligence_express/express_index')}"
        var href = url + '&id=' + express_num_id;
        window.location.href = href;
    }





</script>
<include file="Public:footer" />
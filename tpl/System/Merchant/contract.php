<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Merchant/contract')}" class="on">已签署合同商家</a>|
            <a href="{pigcms{:U('Merchant/notContract')}">未签署合同商家</a>|
            <a href="{pigcms{:U('Merchant/contractSetting')}">合同配置</a>|
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <table class="search_table" width="100%">
                    <tr>
                        <td style="width:60%;">
                            <form action="{pigcms{:U('Merchant/contract')}" method="get">
                                <input type="hidden" name="c" value="Merchant"/>
                                <input type="hidden" name="a" value="contract"/>
                                筛选:
                                <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                                <select name="searchtype">
                                    <option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商家名称</option>
                                    <option value="account" <if condition="$_GET['searchtype'] eq 'account'">selected="selected"</if>>商家帐号</option>
                                    <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
                                    <option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
                                </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <input type="submit" value="查询" class="button"/>
                            </form>
                        </td>
                        <td>
                            <a href="javascript:void(0)" onclick="window.o2oTop.artiframe('/v20/public/platform/#/merchant/platform/HistoricalContracts?type=reSign','重签合同','40%',350,true,false,false,false,'edit',true);" class="button" style="float:right;margin-right: 10px;">重签合同</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>协议编号</th>
                    <th>商家名称</th>
                    <th>联系电话</th>
                    <th>签署时间</th>
                    <th>签约次数</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="$merchant_list">
                    <volist name="merchant_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.contract_number}</td>
                            <td>{pigcms{$vo.name}</td>
                            <td>{pigcms{$vo.phone}</td>
                            <td>{pigcms{$vo.sign_time|date='Y-m-d H:i:s',###}</td>
                            <td>{pigcms{$vo.nums}</td>
                            <td class="textcenter">
                                <a href="javascript:void(0);" onclick="window.o2oTop.iframeDrawer({
                                    url: '/v20/public/platform/#/merchant/platform/HistoricalContracts?mer_id={pigcms{$vo['mer_id']}',
                                    title: '历史合同',
                                    id: '1',
                                    placement: 'right',
                                    visible: true,
                                    width: '25%',
                                    zIndex: 999,
                                    drawerIframeStyle:{//iframe样式
                                        width: '100%',
                                        height: '88vh'
                                    }
                                });">历史合同</a> |
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/viewContract',array('mer_id'=>$vo['mer_id']))}','查看合同',900,600,true,false,false,false,'edit',true);">查看合同</a> |
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/downloadContract',array('mer_id'=>$vo['mer_id']))}','查看合同',900,600,true,false,false,false,'edit',true);">下载合同</a> |
                                <a href="javascript:void(0);" onclick="showArt('{pigcms{$vo['mer_id']}')">重签合同</a>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    var mer_id = '';
    function showArt(d){
        mer_id = d;
        window.o2oTop.art.dialog({
            url: '',
            title: '重签合同',
            content: '确认后当前合同会作废，需要重新签署合同！',
            ok: okFun,
            cancel:function(){
            }
        });
    }
    function okFun(){
        $.post('{pigcms{:U("Merchant/singleResign")}', {mer_id: mer_id}, function (data) {
            var res = JSON.parse(data);
            console.log(res);
            if (res.status == 0) {
                layer.alert('操作成功');
                setTimeout(function () {
                    window.location.reload();
                }, 1000)
            }else{
                var msg = res.data;
                console.log(msg);
                layer.alert(msg);
                setTimeout(function () {
                    $('.layui-layer-close').click();
                }, 2000)
            }
        })
    }
</script>
<include file="Public:footer"/>
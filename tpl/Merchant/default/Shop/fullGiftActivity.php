<include file="Public:header"/>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
            	.ace-file-input a {display:none;}
            	.cur_place{
            		padding: 10px 0 20px 0;
            		color:#999
            	}
            	.cur_place span,.cur_place a{
            		padding: 0 5px;
            		 
            	}
               .application-list {
            		margin-bottom: 24px;
            		-webkit-animation: enterIn .2s ease-out;
            		animation: enterIn .2s ease-out;
            		-webkit-animation-fill-mode: forwards;
            		animation-fill-mode: forwards;
            	}
            	.application-title {
            		margin-bottom: 20px;
            		color: #323233;
            	}
            	.application-title .title-main {
            		font-size: 16px;
            		font-weight: 700;
            	}
            	.application-title .title-desc {
            		color: #999;
            		margin-left: 10px;
            		font-size:13px;
            	}
            </style>
            <div class="cur_place">
            	<span style="padding-left: 0;">平台业务</span>/
            	<span>外卖管理</span>/ 
                <a href="{pigcms{:U('Shop/marketingList', array('store_id' => $_GET['store_id']))}">营销活动</a>
            </div>
            <div class="application-title">
            	<span class="title-main">满赠活动</span>
            	<span class="title-desc">吸引顾客提高成交几率</span>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <a class="btn btn-success" style="margin-bottom: 20px" title="{pigcms{:L_("新建活动")}" href="{pigcms{:U('fullGiftAdd', array('store_id' => $storeid))}">{pigcms{:L_("新建活动")}</a>

                    <div class="form-group">
                        <form action="" method="get">
                            <input type="hidden" name="c" value="Shop"/>
                            <input type="hidden" name="a" value="fullGiftActivity"/>
                            <input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>

                            {pigcms{:L_("搜索")}: <input type="text" name="keyword" placeholder="活动名称" class="input-text" value="{pigcms{$_GET['keyword']}"/>

                            <font color="#000">{pigcms{:L_("活动开始时间：")}</font>
                            <input type="text" class="input-text" name="sdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.sdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="sdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.sdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <font color="#000">{pigcms{:L_("活动结束时间：")}</font>
                            <input type="text" class="input-text" name="fdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.fdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="fdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.fdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="submit" value="{pigcms{:L_("查询")}" class="button "/>
                        </form>
                    </div>
                    <div class="alert alert-info" style="margin:20px 0px;">
                        <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
                        1、一个店铺只允许一个进行中的满赠活动，且一个满赠活动里最多可创建三个等级优惠。<br/>
                        2、满赠的门槛金额不包括配送费，餐盒费将计入满减活动的触发条件。<br/>
                        3、每人每天最多享受2单满赠活动，超过2单则即使满足条件也不会有赠送商品。<br/>
                        4、每个满赠活动只支持赠送一个附属菜，且附属菜库存为0时，满赠活动自动结束。<br/>
                        5、门槛条件是（限时优惠、分类折扣、店铺折扣）优惠之后的价格。
                    </div>
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>{pigcms{:L_("编号")}</th>
                                <th>{pigcms{:L_("活动名称")}</th>
                                <th>{pigcms{:L_("开始时间")}</th>
                                <th>{pigcms{:L_("结束时间")}</th>
                                <th>{pigcms{:L_("活动详情")}</th>
                                <th>{pigcms{:L_("支付订单总数")}</th>
                                <th>{pigcms{:L_("参与客户")}</th>
                                <th>{pigcms{:L_("实付总金额")}</th>
                                <th>{pigcms{:L_("开启状态")}</th>
                                <th>{pigcms{:L_("活动状态")}</th>
                                <th>{pigcms{:L_("操作")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$lists">
                                <volist name="lists" id="l">
                                    <tr>
                                        <td>{pigcms{$l.id}</td>
                                        <td>{pigcms{$l.name}</td>
                                        <td>{pigcms{$l.begin_time|date='Y-m-d H:i:s',###}</td>
                                        <td>{pigcms{$l.end_time|date='Y-m-d H:i:s',###}</td>
                                        <td>
                                            <volist name="l.setting" id="s">
                                                满{pigcms{$s.price}赠{pigcms{$s.goods_name}【库存：{pigcms{$s.stock_num}】<br/>
                                            </volist>
                                        </td>
                                        <td>{pigcms{$l.order_count}</td>
                                        <td>{pigcms{$l.user_count}</td>
                                        <td>{pigcms{$l.total_price}</td>
                                        <td><if condition="$l['is_open'] eq 1">开启<else/>关闭</if></td>
                                        <td>{pigcms{$l.status_str}</td>
                                        <td>
                                            <if condition="$l['status'] eq 0">
                                                <a style="width: 60px;background-color:red !important;" onclick="set_del('{pigcms{$storeid}','{pigcms{$l['id']}')" class="label label-sm label-success del" href="javascript:void(0)">{pigcms{:L_("删除")}</a>
                                            <else/>
                                                <a style="width: 60px; !important;" class="label label-sm label-success" href="{pigcms{:U('Shop/fullGiftEdit',array('store_id'=>$storeid,'id'=>$l['id']))}">{pigcms{:L_("编辑")}</a>
                                                <a style="width: 60px; !important;" class="label label-sm label-success invalid" onclick="set_invalid('{pigcms{$l['id']}')" href="javascript:void(0)">{pigcms{:L_("设为失效")}</a>
                                            </if>
                                        </td>
                                    </tr>
                                </volist>
                            <else/>
                                <tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("无内容")}</td></tr>
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
    function set_del(store_id,id) {
        if (!confirm('{pigcms{:L_("是否确定删除，删除后不可恢复")}')) {
            return false;
        } else {
            window.location.href = 'http://www.baidu.com/';
            var url = "{pigcms{:U('Shop/fullGiftDel')}";
            url += '&store_id=' + store_id;
            url += '&id=' + id;
            window.location.href = url;
        }
    }
    function set_invalid(id){
        if (!confirm('{pigcms{:L_("是否确定设为失效，失效后不可恢复")}')) {
            return false;
        } else {
            var url = "{pigcms{:U('Shop/fullGiftInvalid')}";
            url += '&id=' + id;
            window.location.href = url;
        }
    }

    $(function(){
        /*{pigcms{:L_("店铺状态")}*/
        jQuery(document).on('click','#shopList a.red',function(){
            if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
        });

        /*{pigcms{:L_("选择搜索类型")}*/
        $('select[name=search_type]').change(function(){
            var val=$(this).val();
            var tip='';
            if(val=='name'){
                tip='{pigcms{:L_("请输入商品名称！")}';
            }else if(val=='number'){
                tip='{pigcms{:L_("请输入商品条形码！")}';
            }
            $(this).next().attr('placeholder',tip);
        });
    });
</script>
<include file="Public:footer"/>
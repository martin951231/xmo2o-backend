<include file="Public:header"/>
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->

    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-cubes"></i>
                <a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
            </li>
            <li>{pigcms{$now_store['name']}</li>
            <li class="active">{pigcms{:L_("超值换购列表")}</li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
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
                .goods_name{
                    display: inline-block;
                    max-width: 100px;
                    word-break: break-word;
                    vertical-align: middle
                }
                .goods_pic{
                    display: inline-block
                }
            </style>
            <div class="cur_place">
                <span style="padding-left: 0;">平台业务</span>/
                <span>{pigcms{$config.mall_alias_name}管理</span>/
                <a href="{pigcms{:U('Mall/marketingList', array('store_id' => $_GET['store_id']))}">{pigcms{:L_("营销活动")}</a>
            </div>
            <div class="application-title">
                <span class="title-main">{pigcms{:L_("预售活动")}</span>
                <span class="title-desc"></span>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <!--搜索栏-->
                    <div class="form-group">

                        <form action="" method="get">
                            <input type="hidden" name="c" value="Mall"/>
                            <input type="hidden" name="a" value="book_index"/>
                            <input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>


                            {pigcms{:L_("活动状态")}：
                            <select name="status">
                                <option value="0" <if condition="$_GET['status'] eq 0">selected</if>>{pigcms{:L_("请选择")}</option>
                                <option value="1" <if condition="$_GET['status'] eq 1">selected</if>>{pigcms{:L_("待生效")}</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected</if>>{pigcms{:L_("进行中")}</option>
                                <option value="3" <if condition="$_GET['status'] eq 3">selected</if>>{pigcms{:L_("已结束")}</option>
                                <option value="4" <if condition="$_GET['status'] eq 4">selected</if>>{pigcms{:L_("已撤销")}</option>
                            </select>

                            <font color="#000">{pigcms{:L_("活动时间")}：</font>
                            <input type="text" class="input-text" name="start_date" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_date}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            &nbsp;至&nbsp;
                            <input type="text" class="input-text" name="end_date" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_date}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                            {pigcms{:L_("商品名称")}：<input type="text" name="keyword" placeholder="请输入商品名称" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                            <button type="submit" class="btn btn-success" style="margin-bottom: 3px">{pigcms{:L_('查询')}</button>
                           <!-- <button type="reset" class="btn btn-success" style="margin-bottom: 3px">{pigcms{:L_('重置')}</button>-->
                            <span class="btn btn-success" onclick="clear_params()" style="margin-bottom: 3px">{pigcms{:L_('重置')}</span>
                        </form>
                    </div>

                    <!--每单限购总数设置-->
                    <!--<div class="form-group" style="background: #FAFAFA;padding: 15px 5px">
                       {pigcms{:L_('每单可购买折扣商品总数')}&nbsp;&nbsp;&nbsp;
                        <input type="radio" name="limit_total_num_type" value="0" <if condition="$limit_total_config['value'] eq 0">checked</if>/>{pigcms{:L_('不限')}
                        &nbsp;&nbsp;
                        <input type="radio" name="limit_total_num_type" value="1" <if condition="$limit_total_config['value'] neq 0">checked</if>/>{pigcms{:L_('自定义')}
                        <input type="number" name="num" id="limit_total_num" placeholder="{pigcms{:L_('必须为整数')}" <if condition="$limit_total_config['value'] neq 0">value="{pigcms{$limit_total_config.value}"</if>/>
                        <button class="btn btn-success" onclick="update_limit_total_num()">修改</button>
                    </div>-->

                    <div class="form-group">
                        <a href="{pigcms{:U('Mall/book_goods_add',array('store_id'=>$_GET['store_id']))}" class="btn btn-success fl" style="">{pigcms{:L_("创建预售活动")}</a>
                        <a href="{pigcms{:U('Mall/book_order_list',array('store_id'=>$_GET['store_id']))}" class="btn btn-success fl" style="">{pigcms{:L_("预售订单")}</a>
                    </div>

                    <!--列表区-->
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th id="shopList_c1" width="50">{pigcms{:L_("促销商品")}</th>
                                <th id="shopList_c1" width="50">{pigcms{:L_("规格")}</th>
                                <th id="shopList_c3" width="10">{pigcms{:L_("原价")}</th>
                                <th id="shopList_c1" width="20">{pigcms{:L_("定金")}</th>
                                <th id="shopList_c1" width="10">{pigcms{:L_("优惠价格")}</th>
 
                                <th id="shopList_c3" width="10">{pigcms{:L_("当前库存/原始库存")}</th>
                                <th id="shopList_c5" width="20">{pigcms{:L_("已售数量")}</th>
                                <th id="shopList_c7" width="50">{pigcms{:L_("活动时间")}</th>

                                <th id="shopList_c5" width="20">{pigcms{:L_("状态")}</th>
                                <th id="shopList_c5" width="20"></th>
                                <!--<th id="shopList_c5" width="20">{pigcms{:L_("操作")}</th>-->
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$lists">
                                <volist name="lists" id="o">
                                    <if condition="!empty($o['spec_value'])">
                                        <!--有规格-->
                                        <volist name="o.spec_list" id="oo" key="k">
                                            <if condition="$k eq 1">
                                                <tr>
                                                    <td rowspan="{pigcms{$o.spec_count}">
                                                        <div class="goods_pic">
                                                            <img src="{pigcms{$o.image}" style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px"/>
                                                        </div>
                                                        <div  class="goods_name">{pigcms{$o.goods_name}</div>
                                                    </td>
                                                    <td>{pigcms{$oo.str}</td>
                                                    <td>{pigcms{$oo.price}</td>
                                                    <td>{pigcms{$oo.bargin_price}</td>
                                                    <td>
                                                        {pigcms{$oo.discount_price}
                                                    </td>
                                                    
                                                    <td><php>echo $oo['stock'] == -1?'无限':$oo['stock'];</php>/<php>echo $oo['origin_stock'] == -1?'无限':$oo['origin_stock'];</php></td>
                                                    <td>
                                                        <if condition="!empty($oo['today_sale'])">
                                                            {pigcms{$oo.today_sale}
                                                            <else/>
                                                            {pigcms{$o.today_sale}
                                                        </if>
                                                     </td>
                                                    <td>{pigcms{$o.start_time}至{pigcms{$o.end_time}</td>
                                                    
                                                    <td rowspan="{pigcms{$o.spec_count}">
                                                        <if condition="$o['is_del'] eq 1">
                                                            {pigcms{:L_('已撤销')}
                                                            <else/>
                                                            {pigcms{$o.status_str}
                                                            </if>
                                                    </td>
                                                    <td rowspan="{pigcms{$o.spec_count}">
                                                        <if condition="($o['status'] neq 3) and ($o['is_del'] neq 1)">
                                                        <a title="{pigcms{:L_('修改')}" class="green" href="{pigcms{:U('Mall/book_goods_edit',array('store_id'=>$o['store_id'], 'id' => $o['id']))}" style="margin-right: 3px">修改
                                                        </a>
                                                            <if condition="$o['is_del'] eq 0">
                                                            <a title="{pigcms{:L_('撤销')}" class="green set_lose" href="javascript:void(0)" data-id="{pigcms{$o.id}"  style="margin-right: 3px">撤销
                                                            </a>
                                                            </if>

                                                        </if>
                                                        <if condition="$o['is_del'] eq 1">
                                                            <a title="{pigcms{:L_('删除')}" class="green set_del" href="javascript:void(0)" data-id="{pigcms{$o.id}"  style="margin-right: 3px">删除
                                                            </a>
                                                            </if>
                                                    </td>
                                                    
                                                </tr>
                                            <else/>
                                                <tr>
                                                    <td>{pigcms{$oo.str}</td>
                                                    <td>{pigcms{$oo.price}</td>
                                                    <td>{pigcms{$oo.bargin_price}</td>
                                                    <td>
                                                       {pigcms{$oo.discount_price}
                                                    </td>

                                                    <td><php>echo $oo['stock'] == -1?'无限':$oo['stock'];</php>/<php>echo $oo['origin_stock'] == -1?'无限':$oo['origin_stock'];</php></td>
                                                    <td>{pigcms{$oo.today_sale}</td>
                                                    <td>{pigcms{$o.start_time}至{pigcms{$o.end_time}</td>
                                                   
                                                    
                                                </tr>
                                            </if>
                                        </volist>
                                    <else/>
                                        <!--无规格-->
                                        <tr>
                                        <td>
                                            <div class="goods_pic">
                                                <img src="{pigcms{$o.image}" style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px"/>
                                            </div>
                                            <div class="goods_name">{pigcms{$o.goods_name}</div>
                                        </td>
                                        <td>{pigcms{:L_("无")}</td>
                                        <td>{pigcms{$o.price}</td>
                                        <td>{pigcms{$o.bargin_price}</td>
                                        <td>
                                            {pigcms{$o.discount_price}
                                        </td>

                                        <td><php>echo $o['stock'] == -1?'无限':$o['stock'];</php>/<php>echo $o['origin_stock'] == -1?'无限':$o['origin_stock'];</php></td>
                                        <td>{pigcms{$o.today_sale}</td>
                                        <td>{pigcms{$o.start_time}至{pigcms{$o.end_time}</td>
                                        
                                        <td>

                                            <if condition="!empty($o['reason_for_not_sale'])">
                                                <br/><font style="color: #A31B1B;font-weight: bold">不可售卖：{pigcms{$o.reason_for_not_sale}</font>
                                            <else/>
                                                <if condition="$o['is_del'] eq 1">
                                                    {pigcms{:L_('已撤销')}
                                                <else/>
                                                    {pigcms{$o.status_str}
                                                </if>
                                            </if>
                                        </td>
                                        <td>
                                            <if condition="($o['status'] neq 3) and ($o['is_del'] neq 1)">
                                                <a title="{pigcms{:L_('修改')}" class="green" href="{pigcms{:U('Mall/book_goods_edit',array('store_id'=>$o['store_id'], 'id' => $o['id']))}" style="margin-right: 3px">修改
                                                </a>
                                                <if condition="$o['is_del'] eq 0">
                                                    <a title="{pigcms{:L_('撤销')}" class="green set_lose" href="javascript:void(0)" data-id="{pigcms{$o.id}" style="margin-right: 3px">撤销</a>
                                                </if>
                                                
                                            </if>
                                            <if condition="$o['is_del'] eq 1">
                                                    <a title="{pigcms{:L_('删除')}" class="green set_del" href="javascript:void(0)" data-id="{pigcms{$o.id}" style="margin-right: 3px">删除</a>
                                                </if>
                                        </td>
                                        
                                        </tr>
                                    </if>

                                </volist>
                            <else/>
                                <tr class="odd"><td class="button-column" colspan="30" >{pigcms{:L_("暂无预售活动")}</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$page_bar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $('.set_lose').on('click',function(){
        var id = $(this).attr('data-id');
        layer.confirm('{pigcms{:L_("确定撤销吗？")}', {
            btn: ['确认', '取消']
        }, function(index, layero){
            $.post('{pigcms{:U("Mall/book_del")}',{id:id}, function(data){
                if (data.error) {
                    layer.msg(data.msg);
                }else{
                    layer.msg(data.msg);
                    setTimeout(function(){
                        window.location.reload();
                    },3000);
                }
            },'json');
        });
    })

    $('.set_del').on('click',function(){
        var id = $(this).attr('data-id');
        layer.confirm('{pigcms{:L_("确定删除吗？")}', {
            btn: ['确认', '取消']
        }, function(index, layero){
            $.post('{pigcms{:U("Mall/book_delete")}',{id:id}, function(data){
                if (data.error) {
                    layer.msg(data.msg);
                }else{
                    layer.msg(data.msg);
                    setTimeout(function(){
                        window.location.reload();
                    },3000);
                }
            },'json');
        });
    })

    var store_id = "{pigcms{$_GET.store_id|default=0}";

    function clear_params(){
        window.location.href = "{pigcms{:U('Mall/book_index')}" + '&store_id=' + store_id;
    }

    function update_limit_total_num() {
        var type = $('input[name=limit_total_num_type]:checked').val();
        var num = $('#limit_total_num').val();
        var data = {};
        if (type == 0) {
            data = {number: 0};
        } else {
            if (num < 1) {
                alert("{pigcms{:L_('每单可购买折扣商品总数必须是大于0的整数')}");
                return false;
            }
            data = {number: num};
        }
        $.post('{pigcms{:U("Shop/update_limit_total_num")}', data, function (rs) {
            alert(rs.info);
            return false;
        })
    }
</script>
<include file="Public:footer"/>

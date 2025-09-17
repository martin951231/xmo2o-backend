<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Shop/shop_goods')}">平台商品列表 <if condition="$not_audit_num_system gt 0"><span style="min-width:20px;height:20px;border-radius:18px;background:#e83338;color:#fff;padding:0 4px;">{pigcms{$not_audit_num_system}</span></if></a>
            <a href="{pigcms{:U('Shop/store_goods')}" class="on">店铺商品列表 <if condition="$not_audit_num_shop gt 0"><span style="min-width:20px;height:20px;border-radius:18px;background:#e83338;color:#fff;padding:0 4px;">{pigcms{$not_audit_num_shop}</span></if></a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Shop/store_goods')}" method="get">
                    <input type="hidden" name="c" value="Shop"/>
                    <input type="hidden" name="a" value="store_goods"/>
                    搜索商品名称: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    &nbsp;&nbsp;状态：
                    <select name="audit_status">
                        <option value="3" <if condition="$audit_status eq 3">selected="selected"</if>>全部状态</option>
                        <option value="0" <if condition="$audit_status eq 0">selected="selected"</if>>未审核</option>
                        <option value="1" <if condition="$audit_status eq 1">selected="selected"</if>>已审核</option>
                        <option value="2" <if condition="$audit_status eq 2">selected="selected"</if>>审核失败</option>
                    </select>
                    <input type="submit" value="查询" class="button"/>　
                    <a href="javascript:void(0)" id="fast_audit" class="button" style="float:right;margin-right: 10px;">快速审核</a>
                </form>
            </td>
        </tr>
        <td style="width:100px;">

        </td>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th><input type="checkbox" id="all"></th>
                    <th>商品编号</th>
                    <th>商品条形码</th>
                    <th>商品图片</th>
                    <th>商品名称</th>
                    <th>所属店铺</th>
                    <th>所属分类</th>
                    <th>商品单位</th>
                    <th>浏览量</th>
                    <th>零售价({pigcms{$config['Currency_txt']})</th>
                    <th>进价({pigcms{$config['Currency_txt']})</th>
                    <th>利润价({pigcms{$config['Currency_txt']})</th>
                    <th>审核状态</th>
                    <th>审核备注</th>
                    <th>提交时间</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($goods_list)">
                    <volist name="goods_list" id="vo">
                        <tr>
                            <td><input type="checkbox" name="goodsIds[]" value="{pigcms{$vo.goods_id}"></td>
                            <td>{pigcms{$vo.goods_id}</td>
                            <td>{pigcms{$vo.number}</td>
                            <td>
                                <if condition="$vo['image_url']">
                                    <img src="{pigcms{$vo.image_url}" width="50" height="50" class="view_msg"/>
                                    <else/>
                                    未上传图片
                                </if>
                            </td>
                            <td>{pigcms{$vo.name}</td>
                            <td>{pigcms{$vo.store_name}</td>
                            <td>{pigcms{$vo.sort_name}</td>
                            <td>{pigcms{$vo.unit}</td>
                            <td>{pigcms{$vo.browse_num}</td>
                            <td>{pigcms{$vo.price|floatval}</td>
                            <td>{pigcms{$vo.cost_price|floatval}</td>
                            <td>{pigcms{$vo['price']-$vo['cost_price']|floatval}</td>
                            <td>{pigcms{$vo.audit_name}</td>
                            <td>{pigcms{$vo.audit_content}</td>
                            <td>{pigcms{$vo.last_time}</td>
                            <td class="textcenter">
                                <a href="javascript:void(0);" onclick="window.top.artiframe('/merchant.php?g=Merchant&c=Shop&a=goods_edit&is_audit=1&goods_id={pigcms{$vo.goods_id}&store_id={pigcms{$vo.store_id}&mer_id={pigcms{$vo.mer_id}','查看商品信息',1100,600,true,false,false,closebtn,'detail',true);"><if condition="$vo.audit_status eq 0">审核<else/>重新审核</if></a>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="30">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="30">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
    $(document).ready(function(){
        $('#fast_audit').click(function (){
            var goodsIDs = [];
            $("input['name=goodsIds[]']:checked").each(function(){
                if($(this).val()!='on'){
                    goodsIDs.push($(this).val());
                }
            });
            if(goodsIDs.length < 1){
                alert('请先选择商品');
                return false;
            }
            var index = layer.open({
                type : 2,
                title : "快速审核",
                maxmin : true,
                offset: '100px',
                area : [ '600px', '300px' ],
                content : "{pigcms{:U('Shop/goods_fast_audit')}&id="+goodsIDs.toString(),//iframe的url
            });

            layer.iframeAuto(index);//自适应窗口大小
        })
        $('#nav').dblclick(function(){
            $(this).find('.dblShow').show();
        });

        layer.config({extend: 'extend/layer.ext.js'});
        $('#all').click(function(){
            if ($(this).is(':checked')) {
                $('input[type="checkbox"]').attr('checked', true);
            } else {
                $('input[type="checkbox"]').attr('checked', false);
            }
        });
        $('#delete').click(function(){
            var goodsIDs = [];
            $("input['name=goodsIds[]']:checked").each(function(){
                goodsIDs.push($(this).val());
            });
            if(goodsIDs.length < 1){
                alert('请先选择商品');
                return false;
            }
            layer.confirm('确定删除商品吗?', {icon: 3, title:'提示',offset: ['100px', '400px'],}, function(index){
                $.post('{pigcms{:U("Shop/goods_del")}',{'goodsIds':goodsIDs},function(res){
                    if(res.errorno=='0'){
                        alert(res.info);
                    }else{
                        alert(res.info);
                        window.location.reload();
                    }
                },'json');
                layer.close(index);
            });
        })
    });
</script>
<include file="Public:footer"/>
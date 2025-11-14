<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Shop/sys_goods')}" class="on">商品列表</a>
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('goods_add')}','添加商品',600,420,true,false,false,addbtn,'add',true);">添加商品</a>
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('goods_import')}','添加商品',600,420,true,false,false,addbtn,'add',true);">导入商品</a>
                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('unzip')}','上传图片zip压缩包',750,460,true,false,false,addbtn,'add',true);" >上传图片zip压缩包</a>
                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('capture_store_goods')}','抓取店铺最新商品数据',800,600,true,false,false,addbtn,'capture_store_goods',true);" >抓取店铺最新商品数据</a>
				<a href="javascript:void(0);" class="dblShow" style="display:none;-webkit-user-select:none;-webkit-touch-callout:none;" onclick="window.o2oTop.artiframe('{pigcms{:U('merchant_goods_unzip')}','上传商家商品图片',480,260,true,false,false,addbtn,'add',true);" >上传商家商品图片</a>
			</ul>
		</div>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Shop/sys_goods')}" method="get">
						<input type="hidden" name="c" value="Shop"/>
						<input type="hidden" name="a" value="sys_goods"/>
						搜索商品名称: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="sort_id">
							<option value="0" <if condition="$_GET['sort_id'] eq 0">selected="selected"</if>>全部分类</option>
							<volist name="sort_list" id="sort">
							<option value="{pigcms{$sort['sort_id']}" <if condition="$_GET['sort_id'] eq $sort['sort_id']">selected="selected"</if>>{pigcms{$sort['name']}</option>
							</volist>
						</select>
                        &nbsp;&nbsp;状态：
                        <select name="status">
                            <option value="3" <if condition="$status eq 3">selected="selected"</if>>全部状态</option>
                            <option value="0" <if condition="$status eq 0">selected="selected"</if>>未审核</option>
                            <option value="1" <if condition="$status eq 1">selected="selected"</if>>已审核</option>
                            <option value="2" <if condition="$status eq 2">selected="selected"</if>>审核失败</option>
                        </select>
						<input type="submit" value="查询" class="button"/>　
                        <input type="button" value="批量删除" id="delete" class="button"/>
                        
						<if condition="C('config.open_goods_status') eq 1">
						<a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/goods_fast_status')}','快速审核',600,300,true,false,false,editbtn,'edit',true);" class="button" style="float:right;margin-right: 10px;">快速审核</a>
						</if>
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
							<th>所属分类</th>
							<th>商品单位</th>
							<th>零售价({pigcms{$config['Currency_txt']})</th>
							<th>进价({pigcms{$config['Currency_txt']})</th>
							<th>利润价({pigcms{$config['Currency_txt']})</th>
                            <if condition="$open_status eq 1">
                            <th>审核状态</th>
                            <th>审核备注</th>
                            <th>提交时间</th>
                            </if>
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
										<if condition="$vo['image']">
											<img src="{pigcms{$vo.image}" width="50" height="50" class="view_msg"/>
										<else/>
											未上传图片
										</if>
									</td>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.sort_name}</td>
									<td>{pigcms{$vo.unit}</td>
									<td>{pigcms{$vo.price|floatval}</td>
									<td>{pigcms{$vo.cost_price|floatval}</td>
									<td>{pigcms{$vo['price']-$vo['cost_price']|floatval}</td>
                                    <if condition="$open_status eq 1">
                                    <td>{pigcms{$vo.status_name}</td>
                                    <td>{pigcms{$vo.status_content}</td>
                                    <td>{pigcms{$vo.last_time}</td>
                                    </if>
									<td class="textcenter">
                                        <if condition="$open_status eq 1">
                                        <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/goods_edit',array('goods_id'=>$vo['goods_id'],'frame_show'=>true,'is_status'=>1))}','查看商品信息',600,560,true,false,false,closebtn,'detail',true);"><if condition="$vo.status eq 0">审核<else/>重新审核</if></a> |
                                        </if>
                                        <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/goods_edit',array('goods_id'=>$vo['goods_id'],'frame_show'=>true))}','查看商品信息',600,420,true,false,false,false,'detail',true);">查看</a> |
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/goods_edit',array('goods_id'=>$vo['goods_id']))}','编辑商品信息',600,420,true,false,false,editbtn,'edit',true);">编辑</a> | 
										<a href="javascript:void(0);" class="delete_row" parameter="goods_id={pigcms{$vo.goods_id}" url="{pigcms{:U('Shop/goods_del')}">删除</a>
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
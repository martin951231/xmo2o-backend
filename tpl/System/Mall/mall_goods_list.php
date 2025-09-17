<include file="Public:header"/>
	<div class="mainbox">
		<div id="nav" class="mainnav_title">
			<ul>
				<a href="{pigcms{:U('Mall/mall_goods_list')}" class="on">全部商品</a>
				<a href="{pigcms{:U('Mall/mall_seckill_list')}">{pigcms{$config.seckill_alias_name|default='限时购'}商品</a>
				<a href="{pigcms{:U('Mall/mall_group_list')}">拼团商品</a>
				<a href="{pigcms{:U('Mall/mall_cut_list')}">砍价商品</a>
			</ul>
		</div>
		<style type="text/css">
		#edit_mer{
			display: none;
			float: left;
			line-height: 16px;
			text-decoration: underline;
			color: #4ecdbe;
		}
		</style>
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Mall/mall_goods_list')}" method="get">
						<input type="hidden" name="c" value="Mall"/>
						<input type="hidden" name="a" value="mall_goods_list"/>
						<input type="hidden" name="mer_id" value="{pigcms{$_GET.mer_id}"/>
						筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						<select name="searchtype">
							<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商品名称</option>
							<option value="goods_id" <if condition="$_GET['searchtype'] eq 'goods_id'">selected="selected"</if>>商品编号</option>
							<option value="number" <if condition="$_GET['searchtype'] eq 'number'">selected="selected"</if>>商品条形码</option>
						</select>
						<p style="display: inline-block;overflow: auto;vertical-align: middle;">
							<span class="fl" style="margin-left: 30px;">选择商家：</span>
							<label>
								<span class="fl">全部商家</span>
								<input type="radio"  class="input-text fl " href="{pigcms{:U('Analysis/mer_list')}" data-type="all_mer" name="mer_list" value="-1" <if condition="$_GET['mer_list'] eq -1 OR !isset($_GET['mer_list'])">checked </if>>	
							</label>
							<label>
								<span class="fl">部分商家</span>
								<input type="radio" class="input-text fl "  href="{pigcms{:U('Analysis/mer_list')}" id="part_mer" data-type="part_mer" name="mer_list"  <if condition="$_GET['mer_list'] neq -1 AND !empty($_GET['mer_list'])">checked value="{pigcms{$_GET['mer_list']}" <else />value="0"</if> >
								<a href="javascript:void(0)" id="edit_mer" data-type="part_mer">修改</a>
							</label>
							<span class="fl" style="margin-left:30px">选择店铺：</span>
							<label class="all_store_box">
								<span class="fl">全部店铺</span>	
								<input type="radio" class="input-text fl"  href="{pigcms{:U('Analysis/store_list')}" class="input-text fl "  data-type="all_store" name="store_list" value="-1" <if condition="$_GET['store_list'] eq -1 OR !isset($_GET['store_list'])">checked </if> >
							</label>
							<label class="some_store_box">
								<span class="fl">部分店铺</span>
								<input type="radio" class="input-text fl" href="{pigcms{:U('Analysis/store_list')}" class="input-text fl " id="part_store" data-type="part_store" name="store_list" <if condition="$_GET['store_list'] neq -1 AND !empty($_GET['store_list'])">checked value="{pigcms{$_GET['store_list']}" <else />value="0"</if>>
							</label>							
						</p>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						商品分类：
						<select name="cat_fid">
							<option value="">全部</option>
							<volist name='category_list' id='vo'>
								<eq name="vo.fid" value='0'>
									<option value="{pigcms{$vo.id}">{pigcms{$vo.name}</option>
								</eq>
							</volist>
						</select>
						<select name='cat_id'>
							<option value="">全部</option>
							<volist name='category_list' id='vo'>
								<gt name="vo.fid" value='0'>
									<option value="{pigcms{$vo.id}" class="cat_box{pigcms{$vo.fid}" style="display:none;">{pigcms{$vo.name}</option>
								</gt>
							</volist>
						</select>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						首页优选：
						<select name='is_preference'>
							<option value="-1" <if condition="$_GET['is_preference'] EQ -1">selected</if>>全部</option>
							<option value="1" <if condition="$_GET['is_preference'] EQ 1">selected</if>>优选商品</option>
							<option value="0" <if condition="$_GET['is_preference'] EQ 0 && isset($_GET['is_preference'])">selected</if> >非优选商品</option>
						</select>
						<input type="submit" value="查询" class="button"/>
						
					</form>
				</td>
			</tr>
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<style>
				.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
				</style>
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>编号</th>
							<th>首页排序值</th>
							<th>商品名称</th>
							<th>商品类型</th>
							<th>商家名称</th>
							<th>店铺名称</th>							
							<th>现价/{pigcms{$config['Currency_txt']}</th>
							<th>原价/{pigcms{$config['Currency_txt']}</th>
							<th>操作</th>
						</tr>
					</thead>
					<tbody>
						<volist name="list" id='vo'>
							<tr>
								<td>{pigcms{$vo.goods_id}</td>
								<td>{pigcms{$vo.index_sort|default=0}</td>
								<td>{pigcms{$vo.name}</td>
								<td>
									<switch name='vo.goods_type'>
										<case value='1'>{pigcms{$config.seckill_alias_name|default='限时优惠'}</case>
										<case value='2'>拼团</case>
										<case value='3'>砍价</case>
										<default/>普通商品
									</switch>
								</td>
								<td>{pigcms{$vo.mer_name}</td>
								<td>{pigcms{$vo.store_name}</td>								
								<td>{pigcms{$vo.price}</td>
								<td>{pigcms{$vo.old_price}</td>
								<td>
                                    <if condition="$config.open_mall_check_id_card eq 1">
                                        <a href="javascript:void(0);" onclick="check_id_card({pigcms{$vo.goods_id});">实名认证</a>|
                                    </if>
									<a href="{pigcms{:U('Mall/goods_edit',array('mer_id'=>$vo['mer_id'],'goods_id'=>$vo['goods_id']))}" class="edit" data-goods_type="{pigcms{$vo.goods_type}">编辑</a>|
									<a href="{pigcms{:U('Mall/shop_comment_list',array('mer_id'=>$vo['mer_id'],'goods_id'=>$vo['goods_id']))}">查看评论</a>|
									<a href="javascript:void(0);" onclick="index_edit({pigcms{$vo.goods_id});">首页排序设置</a>|
									<if condition="$vo.is_preference eq 1">
										<a href="javascript:void(0);" onclick="set_perfence({pigcms{$vo.goods_id});">取消优选</a>
									<else/>
										<a href="javascript:void(0);" onclick="set_perfence({pigcms{$vo.goods_id});">设为优选</a>
									</if>
								</td>
							</tr>
						</volist>
						<empty name='list'>
							<tr>
								<td colspan="30" style="text-align:center;">暂无商城商品</td>
							</tr>
						<else/>
							<tr><td class="textcenter pagebar" colspan="30">{pigcms{$pagebar}</td></tr>
						</empty>							
					</tbody>
				</table>
			</div>
		</form>
	</div>
<script type="text/javascript">
    var topIfram = window.top.getTopIframe();


$('select[name=cat_fid]').change(function(){
	var cat_fid=$(this).val();
	$('select[name=cat_id]').val('');
	$('select[name=cat_id]').find('option:gt(0)').hide();
	$('select[name=cat_id]').find('option.cat_box'+cat_fid).show();
});
var cat_fid="{pigcms{$cat_fid}";
var cat_id="{pigcms{$cat_id}";
if(cat_fid>0){
	$('select[name=cat_fid]').val(cat_fid);
	$('select[name=cat_fid]').change();
}
if(cat_id>0){
	$('select[name=cat_id]').val(cat_id);
}
var url2="{pigcms{:U('Mall/index_sort_set')}";
function index_edit(goods_id){
	url2+='&goods_id='+goods_id;
	window.top.artiframe(url2,'首页排序',480,260,true,false,false,editbtn,'edit',true);
}

var url3="{pigcms{:U('Mall/check_id_card')}";
function check_id_card(goods_id){
    url3+='&goods_id='+goods_id;
    window.top.artiframe(url3,'实名认证',480,260,true,false,false,editbtn,'edit',true);
}

function set_perfence(goods_id){
	$.post('{pigcms{:U("Mall/set_perfence")}',{'goods_id':goods_id},function(res){
		if(res.errorcode=='1'){
			alert(res.info);
		}else{
			alert(res.info);
			window.location.reload();
		}
	},'json');
}

</script>
<script type="text/javascript">
$(function(){
	$('input:radio').click(function(){
		var obj = $(this);
		if($(obj).attr('name')=='mer_list'){
			var title= '选择商家'
		}else{
			var title= '选择店铺'
		}
		var type= $(obj).data('type');
		
		if(type=='part_mer' || type=='part_store'){
			if(type=='part_mer'){	
				window.art.dialog.data('mer_list', $(obj).val());
				var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&mer_list='+$(obj).val();
				$('#edit_mer').show();
			}else{
				window.art.dialog.data('store_list', $(obj).val());
				var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&store_list='+$(obj).val()+'&mer_list='+$('#part_mer').val();
				$('#edit_store').show();
			}
			
			window.art.dialog.open(href,{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.art.dialog.data('iframe_handle',iframe);
				},
				id: type,
				title:title,
				padding: 0,
				width: 600,
				height: 500,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity:'0.4',
				close:function(){
					if(type=='part_mer'){	
						var mer_list = window.art.dialog.data('mer_list');
						$(obj).val(mer_list)
						$('#part_store').val(0);
						$('input[name=store_list]').each(function(){
							if($(this).val()=='-1'){
								$(this).prop('checked',true);
							}else{
								$(this).prop('checked',false);
							}
						});
						$('#edit_store').hide();
					}else{
						var store_list = window.art.dialog.data('store_list');
						$(obj).val(store_list);
					}
				}
			});						
		}else{
			if(type=='all_mer'){	
				$('#edit_mer').hide();
			}else{
				$('#edit_store').hide();
			}
		}		
	});

	$('#edit_mer').click(function(){
		var obj = $('#part_mer');
		var title= '选择商家'
		var type= $(obj).data('type')
		window.art.dialog.data('mer_list', $(obj).val());
		var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&mer_list='+$(obj).val();

		window.art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.art.dialog.data('iframe_handle',iframe);
			},
			id: type,
			title:title,
			padding: 0,
			width: 600,
			height: 500,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4',
			close:function(){
				var mer_list = window.art.dialog.data('mer_list');
				$(obj).val(mer_list)
				$('#part_store').val(0)
				$('input[name=store_list]').each(function(){
					if($(this).val()=='-1'){
						$(this).prop('checked',true);
					}else{
						$(this).prop('checked',false);
					}
				});
				$('#edit_store').hide();
			}
		});
	});
	$('#edit_store').click(function(){
		var obj = $('#part_store');
		var title= '选择店铺'
		var type= $(obj).data('type')
		window.art.dialog.data('store_list', $(obj).val());
		var href= $(obj).attr('href')+'&province='+$('#choose_provincess').val()+'&city='+$('#choose_cityss').val()+'&area='+$('#choose_areass').val()+'&type'+type+'&store_list='+$(obj).val()+'&mer_list='+$('#part_mer').val();

		window.art.dialog.open(href,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.art.dialog.data('iframe_handle',iframe);
			},
			id: type,
			title:title,
			padding: 0,
			width: 600,
			height: 500,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4',
			close:function(){
				var store_list = window.art.dialog.data('store_list');
				console.log(store_list)
				$(obj).val(store_list)
			}
		});
	});
});
</script>
<include file="Public:footer"/>
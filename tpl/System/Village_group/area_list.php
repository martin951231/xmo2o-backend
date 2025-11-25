<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Village_group/area_list')}" class="on">城市管理</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<if condition="$_GET['type'] gt 1">
							<a href="{pigcms{:U('Village_group/area_list',array('type'=>$_GET['type']-1))}">上一级</a>
						</if>
						<if condition="!$system_session['area_id']">
							<input type="button" value="添加{pigcms{$now_type_str}" class="button" onclick="window.o2oTop.artiframe('{pigcms{:U('area_add',array('type'=>$_GET['type'],'pid'=>$_GET['pid']))}','添加{pigcms{$now_type_str}',800,480,true,false,false,addbtn,'add',true);"/>
						</if>
                        <if condition="$_GET['type'] eq 1">
                            <input type="button" style="float: right;margin-right: 2px;" value="同步平台城市" class="button" onclick="showArt()"/>
                        </if>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<if condition="$_GET['type'] eq 2 || $_GET['type'] eq 3">
								<col/>
							</if>
							<col align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>排序</th>
								<th>编号</th>
								<th>名称</th>
								<if condition="$_GET['type'] eq 1 || $_GET['type'] eq 2">
									<th>进入下级分类</th>
								</if>
                                <th>商品管理</th>
                                <th>团长等级管理</th>
								
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($area_list)">
								<volist name="area_list" id="vo">
									<tr>
										<td>{pigcms{$vo.area_sort}</td>
										<td>{pigcms{$vo.pigcms_id}</td>
										<td><if condition="$vo['is_hot']"><font color="red">{pigcms{$vo.area_name}</font><else/>{pigcms{$vo.area_name}</if></td>
										
										<if condition="$_GET['type'] eq 1 || $_GET['type'] eq 2">
											<td><a href="{pigcms{:U('Village_group/area_list',array('type'=>$_GET['type']+1,'pid'=>$vo['area_id']))}">进入下级</a></td>
										</if>
                                        <td>
                                            <if condition="$city_manage eq 1 and $level eq 1">
                                                <a href="{pigcms{:U('Village_group/index',array('city_id'=>$vo['pigcms_id'], 'origin'=>'city_list'))}">城市商品列表</a>
                                                <else />
                                                <a href="{pigcms{:U('Village_group/area_goods_list',array('city_id'=>$vo['pigcms_id']))}">商品列表</a>
                                            </if>

                                        </td>

                                        <td><a href="{pigcms{:U('Village_group/leader_area_level_list',array('city_id'=>$vo['area_id']))}">团长等级列表</a></td>
										<td>
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('area_edit',array('pigcms_id'=>$vo['pigcms_id']))}','编辑{pigcms{$now_type_str}',450,320,true,false,false,editbtn,'store_add',true);">编辑</a>
											<!-- <a href="javascript:void(0);" class="delete_row" parameter="area_id={pigcms{$vo.area_id}" url="{pigcms{:U('Area/del')}" tip="删除只是进了回收站，所以区域标识未来将无法继续使用，<br/>但是标识无特殊意义，可以填写其他字母数字。<br/>（回收站恢复十分复杂，请勿随意删除区域。）">删除</a> -->
                                        </td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script>
    var mer_id = '';
    function showArt(){
        window.o2oTop.art.dialog({
            url: '',
            title: '确认同步平台城市信息',
            content: '确认后已添加的城市不会被修改，只同步未添加的城市！',
            ok: okFun
        });
    }
    function okFun(){
        $.post('{pigcms{:U("Village_group/copyArea")}', {}, function (data) {
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

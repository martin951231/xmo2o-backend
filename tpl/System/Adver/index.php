<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Adver/index')}" class="on">广告分类列表</a>|
					<!--<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Adver/cat_add')}','添加广告分类',500,320,true,false,false,addbtn,'add',true);">添加广告分类</a>-->
                    <if condition="empty(C('customization.system_adver'))">
                    <a href="javascript:void(0);" onclick="goUrl('{pigcms{$config.site_url}/v20/public/platform/#/activity/platform/adver/List','1','','')">平台弹窗广告</a>|
                    </if>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td style="width:60%;">
						<form action="{pigcms{:U('Adver/index')}" method="get">
							<input type="hidden" name="c" value="Adver"/>
							<input type="hidden" name="a" value="index"/>
							筛选:
							<select name="cat_type">
								<option value="0" <if condition="$_GET['cat_type'] eq 0">selected="selected"</if>>全部广告类别</option>
								<option value="1" <if condition="$_GET['cat_type'] eq '1'">selected="selected"</if>>wap站</option>
								<option value="2" <if condition="$_GET['cat_type'] eq '2'">selected="selected"</if>>pc站</option>
							</select>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							名称：<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="请输入分类名称"/>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>

				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th width="5%">编号</th>
								<th width="10%">类别</th>
								<th width="25%">名称</th>
								<th width="15%">建议尺寸</th>
								<th width="15%">标识</th>
								<th>广告列表</th>
								<th width="15%" class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($category_list)">
								<volist name="category_list" id="vo">
									<tr>
										<td>{pigcms{$vo.cat_id}</td>
										<td><if condition="$vo['cat_type']">PC站广告<else />WAP站广告</if></td>
										<td>{pigcms{$vo.cat_name}</td>
										<td>{pigcms{:str_replace(' ','&nbsp;',$vo['size_info'])}</td>
										<td>{pigcms{$vo.cat_key}</td>
										<td><a href="{pigcms{:U('Adver/adver_list',array('cat_id'=>$vo['cat_id']))}">广告列表</a></td>
										<td class="textcenter">
											<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Adver/cat_edit',array('cat_id'=>$vo['cat_id'],'frame_show'=>true))}','查看广告分类',500,320,true,false,false,false,'add',true);">查看</a><if condition="$system_session['level'] eq 2">&nbsp;|&nbsp;<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Adver/cat_edit',array('cat_id'=>$vo['cat_id']))}','编辑广告分类',500,320,true,false,false,editbtn,'add',true);">编辑</a>&nbsp;|&nbsp;<a href="javascript:void(0);" class="delete_row" parameter="cat_id={pigcms{$vo.cat_id}" url="{pigcms{:U('Adver/cat_del')}">删除</a></if>
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
<include file="Public:footer"/>
<script type="text/javascript" >
    function goUrl(url,new_blank,new_tab,new_page) {
        if(new_blank>0){
            window.open(url)
        }else if(new_tab){
            top.openNewTab(url)

        }else if(new_page>0){
            window.o2oTop.location.href = url
        }else{
            window.o2oTop.contentScrollToTop()
            location.href=url
        }
    }
</script>
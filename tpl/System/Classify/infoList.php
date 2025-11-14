<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('infoList')}" class="on">信息已审核列表</a>
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="/admin.php?g=System&c=Classify&a=infoList" method="get">
							<input type="hidden" value="Classify" name="c"></input>
							<input type="hidden" value="infoList" name="a"></input>
							<span>
								ID：<input type="text" value="{pigcms{$id}" name="id" style="height: 22px;width: 70px;"></input>
							</span>&nbsp;&nbsp;&nbsp;
							<span>
								标题：<input type="text" value="{pigcms{$title}" name="title" style="height: 22px;width: 250px;"></input>
							</span>&nbsp;&nbsp;&nbsp;
							<!--选择城市：
							<select name="city_id" style="width:200px;">
								<option value="0">全部城市</option>
								<if condition="!empty($city_list)">
								<volist name="city_list" id="vo">
								<option value="{pigcms{$vo['area_id']}"  <if condition="$vo['area_id'] eq $city_id">selected="selected"</if>>{pigcms{$vo['area_name']}</option>
								</volist>
								<else/>
								<option value="">无</option>
								</if>
							</select-->&nbsp;&nbsp;&nbsp;
							<if condition="$now_area['area_type'] lt 2 OR $system_session['level'] eq 2 ">
								选择城市：
								<div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}"  style="display:inline"></div>
							</if>
							分类列表：
							<select name="cid" style="width:200px;" onchange="getSubdir(this.value);">
								<option value="">请选择！</option>
								<if condition="!empty($subdir1)">
									<volist name="subdir1" id="vo">
										<option value="{pigcms{$vo['cid']}"  <if condition="$vo['cid'] eq $cid">selected="selected"</if>>{pigcms{$vo['cat_name']}</option>
									</volist>
								<else/>
									<option value="">无</option>
								</if>
							</select>
							<span id="erjisubdir" style="margin:0px 15px;">
								<if condition="!empty($subdir2Arr)">
									<select name="subdir2" style="width:200px;" id="subdir2">
										<volist name="subdir2Arr" id="v2o">
											<option value="{pigcms{$v2o['cid']}" <if condition="$v2o['cid'] eq $subdir2cid">selected="selected"</if>>{pigcms{$v2o['cat_name']}</option>
										</volist>
									</select>
								<else/>
									<select name="subdir2" style="width:200px; display: none;" id="subdir2">
									</select>
								</if>
							</span>
							<span>
								时间：
								<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
								<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							</span>&nbsp;&nbsp;&nbsp;
							<input type="submit" value="查询" class="button"/>
							<span style="float: right;margin-right: 100px;">总赏金：{pigcms{$reward_publish}</span>
                            <input type="button" value="批量删除" id="del_batch" class="button"/>
						</form>
					</td>
				</tr>
			</table>

			
			<!--<table class="search_table" width="100%">
				<tr>
					<td>
					</td>
				</tr>
			</table>-->
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
						</colgroup>
						<thead>
							<tr>
                                <th><input type="checkbox" id="all"></th>
								<th>ID</th>
								<th>一级分类</th>
								<th>二级分类</th>
								<th  width="200">标题</th>
								<th>联系人姓名</th>
								<th>联系人电话</th>
								<th>归属城市</th>
								<th>最后更改时间</th>
								<th>发布费用</th>
								<th width="70">是否置顶</th>
								<th width="190" style="text-align:center;">状态</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="!empty($listdatas)">
								<volist name="listdatas" id="vo">
									<tr>
                                        <td><input type="checkbox" name="ids[]" value="{pigcms{$vo.id}"></td>
									    <td>{pigcms{$vo.id}</td>
										<td>{pigcms{$ClassifyArr[$vo['fcid']]}</td>
										<td>{pigcms{$ClassifyArr[$vo['cid']]}</td>
										<td  width="200"><if condition="!empty($title)"> {pigcms{$vo['title']|str_replace=$title,'<b style="color: red;">'.$title.'</b>',###}<else/>{pigcms{$vo.title}</if></td>
										<td>{pigcms{$vo.lxname}</td>
										<td><if condition="strpos($vo['lxtel'], 'load/telimages')"><img src="{pigcms{$config['site_url']}/{pigcms{$vo['lxtel']}"><else/>{pigcms{$vo.lxtel}</if></td>
										<td>{pigcms{$vo.city_name|default='--'}</td>
										<td>{pigcms{$vo.updatetime|date='Y-m-d H:i:s',###}</td>
										<td><if condition="$vo['reward_type'] eq 2"><span style="color: red">{pigcms{$vo.reward_publish}</span><else/><span style="color: green">免费发布</span></if></td>
										<td style="color: red;"><if condition="$vo['toptime'] gt 0"><a style="color: green;" href="{pigcms{:U('Classify/topList',array('cid'=>$vo['fcid'],'subdir2'=>$vo['cid']))}">已置顶</a><else/>未置顶</if></td>
										<td class="red">已审核&nbsp;&nbsp;&nbsp;<input type="button" value="更新成未审核" style="" class="button" onclick="upStatus({pigcms{$vo.id})"></td>
										<td class="textcenter">
                                            <a href="{pigcms{:U('Classify/replyList',array('vid'=>$vo['id']))}">评论列表</a>&nbsp; |
                                            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Classify/infodetail',array('vid'=>$vo['id']))}','查看信息详情',680,560,true,false,false,closebtn,'edit',true);">查看详细</a>&nbsp; |
                                            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Classify/attrSet',array('vid'=>$vo['id']))}','设置操作',550,350,true,false,false,confirmbtn,'add',true);">设置操作</a>&nbsp; | &nbsp;
                                            <a href="javascript:void(0);" onclick="toDelItem({pigcms{$vo.id});">删除</a>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<style>
    .layui-layer-dialog {
        top: 5% !important;
    }
</style>
<script type="text/javascript">
    $('#all').click(function(){
        if ($(this).is(':checked')) {
            $('input[type="checkbox"]').attr('checked', true);
        } else {
            $('input[type="checkbox"]').attr('checked', false);
        }
    });

    $('#del_batch').click(function(){
        var ids = [];
        $("input[name='ids[]']:checked").each(function(){
            ids.push($(this).val());
        });

        if(ids.length < 1){
            alert('请先选择发布信息');
            return false;
        }
        layer.open({
            title:'确认提醒：'
            ,content:'您确定删除所选吗？一旦删除无法恢复！'
            ,btn: ['确定','取消']
            ,yes: function(index){
                console.log('ids', ids);
                $.post('{pigcms{:U("Classify/del_batch")}',{'vids':ids},function(res){
                    if(res.errorno=='0'){
                        alert(res.info);
                    }else{
                        alert(res.info);
                        window.location.reload();
                    }
                },'json');
                layer.close(index);
            }
        });
    });
/***删除***/
function toDelItem(id){
    if(confirm('您确定删除此项吗？')){
    $.post("{pigcms{:U('Classify/delItem')}",{vid:id},function(data){
	  data=parseInt(data);
	  if(!data){
          window.location.reload();
	   }
     },'JSON');
   }else{
     return false;
   }
}

function getSubdir(cid){
	cid=parseInt(cid);
	if(cid>0){
  $.post("{pigcms{:U('Classify/get2Subdir')}",{cid:cid},function(data){
     if(data){
		 var shtml='<option value="">请选择！</option>';
	    $.each(data,function(kk,vv){
		    shtml+='<option value="'+vv.cid+'">'+vv.cat_name+'</option>';
		});
		$('#subdir2').html(shtml).show();
	 }
  },'JSON');
	}else{
	  $('#subdir2').html('').hide();
	}
}
function upStatus(id){
   if(confirm('您确定将此项信息打入未审核状态吗？')){
    $.post("{pigcms{:U('Classify/toNoVerify')}",{vid:id,sv:0},function(data){
	  data=parseInt(data);
	  if(!data){
          window.location.reload();
	   }
     },'JSON');
   }else{
     return false;
   }
}
</script>

<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js?{pigcms{:time()}"></script>
<include file="Public:footer"/>
<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('HkcPile/charg_rate_list')}">智能充电桩(好快充)</a>
            </li>
            <li class="active">充电区列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
             <div style="margin-bottom: 20px;">
              <if condition="$is_set_ok lt 1">
                    
                    <p style="color: red;color: red;font-size: 16px;font-weight: bold;"> 1、管理员还没配置好对接信息，请联系管理员 在 智慧社区 > 社区配置 > 充电桩(好快充) 进行相关配置</p>
                    <p style="font-size: 16px;"> 2、相关配置完成后，请再到此页面进行小区对接</p>
                
                <elseif  condition="empty($village_haokuaichong) OR empty($village_haokuaichong['project_id'])"/>
                 <div style="font-size: 16px;"> <span>您还没对接小区，请点击按钮进行小区消息对接</span><button style="margin-left: 50px;" class="btn btn-success" onclick="createProjectVillage()">对接充电桩小区消息</button></div>
                <elseif  condition="!empty($village_haokuaichong) AND !empty($village_haokuaichong['project_id'])"/>
                 <div style="font-size: 16px;color: #008123;font-weight: bold;"> <span>您已经对接好小区信息了，充电桩平台小区ID：{pigcms{$village_haokuaichong['project_id']}，小区名称：{pigcms{$village_haokuaichong['project_name']}，联系人：{pigcms{$village_haokuaichong['phone_name']}，手机号：{pigcms{$village_haokuaichong['phone']}</span>
                 <!--<button style="margin-left: 50px;" class="btn btn-success" onclick="getProjectVillage('{pigcms{$village_haokuaichong['project_id']}')">获取已有充电桩小区详情</button>
                 -->
                 </div>   
            </if>
            </div>    
            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('HkcPile/charg_rate_list')}" method="get" style="height: 42px;display: inline-block;" >
                    <input type="hidden" name="c" value="HkcPile"/>
                    <input type="hidden" name="a" value="charg_rate_list"/>
                    <input value="{pigcms{$_GET['area_name']}"  name="area_name" id="area_name" type="text" style="margin-right:10px;font-size:18px;height:40px;" placeholder="请输入费率名称" />
                    <input class="btn btn-success" type="submit" id="find_submit" value="查询" />&nbsp;
                </form>
             <if condition="!empty($village_haokuaichong) AND !empty($village_haokuaichong['project_id'])" >
                 <div style="font-size: 16px;display: inline-block;">
                 <button style="margin-left: 50px;" class="btn btn-success" onclick="addProjectRateSet('{pigcms{$village_haokuaichong['project_id']}')">添加费率</button>
                 </div>   
             </if>
            </div>

            <style>
                .ace-file-input a {display:none;}
                .tpl-table-black-operation a{cursor: pointer;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">费率套餐名称</th>
                                <th width="10%">费率ID</th>
                                <th width="10%">费率模式</th>
                                <th width="15%">费率详情</th>
                                <th width="10%">是否退费</th>
                                <th width="15%">备注</th>
                                <th width="10%">创建时间</th>
           
                                <th class="button-column">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="!empty($haokuaichong_rate)">
                                <volist name="haokuaichong_rate" id="vo">
                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                <td><div class="tagDiv">{pigcms{$vo.rate_name}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.rate_id}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.rate_type_str}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.rate_detail_str}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.is_fund_str}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.remarks}</div></td>
                                <td><div class="tagDiv">{pigcms{$vo.add_time_str}</div></td>
                                <td class="am-text-middle">
                                    <div class="tpl-table-black-operation">
                                        <a title="编辑"  onclick="addProjectRateSet('{pigcms{$vo['project_id']}','{pigcms{$vo['id']}')">编辑</a>
                                        &nbsp;&nbsp;
                                        <a title="删除" style="cursor: pointer" onclick="del('{pigcms{$vo.id}')">删除</a>

                                    </div>
                                </td>
                                </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有充电费率数据</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    function createProjectVillage(){
        window.location.href = "{pigcms{:U('HkcPile/create_project_village')}";
    }
    function addProjectRateSet(project_id,id) {
        if(id==undefined){
            id='';
        }
        var xtitle='添加一个费率套餐';
        if(id){
            xtitle='编辑一个费率套餐';
        }
        var content = "{pigcms{:U('HkcPile/add_project_rate_page',array('project_id'=>$village_haokuaichong['project_id']))}&id="+id;
        layer.open({
            type: 2,
            title:xtitle,
            content: content,
            area: ['1000px', '650px'],
            maxmin: false
        });
    }
    //预览大图
    function view_img(img_url) {
        window.top.art.dialog({
            padding: 0,
            title: '大图',
            content: '<a href="'+img_url+'" target="_blank"><img src="'+img_url+'" style="width:300px;height:300px;" /></a>',
            lock: true
        });
    }

    function del(id){
        layer.open({title:'提示',content:'是否确定删除当前费率数据！',shadeClose:false,btn: ['确定','取消'],yes:function(){
                $.post("{pigcms{:U('HkcPile/del_project_rate')}", {'idd':id}, function(data) {
                    layer.closeAll();
                    if(data.status == 1){
                        layer.alert(data.info,function(){window.location.reload();});
                        
                    }else{
                        layer.alert(data.info,function(){window.location.reload();});
  
                    }
                },'json');
            }});
    }
</script>
<include file="Public:footer"/>

<include file="NewStreetPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewStreet/single_index')}">小区管理</a>
            </li>
            <li class="active">小区中心</li>
        </ul>
    </div>
    <style>
        .crumbs_box {
            padding: 10px 0px 20px;
        }
        .crumbs_box .on {
            color: #1686ef;
        }
        .owner_txt .txt {
            color: #1686ef;
            font-size: 20px;
            font-weight: 500;
        }
    </style>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="crumbs_box">
                <if condition="$area_info['area_type'] eq 0"><a href="{pigcms{:U('NewStreet/community_index',['iframe'=> $_GET['iframe']])}">【{pigcms{$street_name}】社区列表</a> / </if>
                <a href="{pigcms{:U('NewStreet/house_index',array('community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}">【{pigcms{$community_name}】小区列表</a>
                /  <a href="{pigcms{:U('NewStreet/single_index',array('village_id'=>$floor_info['village_id'],'community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}">【{pigcms{$village_name}】楼栋列表</a>
                /  <a href="{pigcms{:U('NewStreet/floor_index',array('community_idss'=>$_GET['community_idss'],'single_idss'=>$floor_info['single_id'],'iframe'=> $_GET['iframe']))}">【{pigcms{$single_name}】单元列表</a>
                /  <a href="{pigcms{:U('NewStreet/layer_index',array('community_idss'=>$_GET['community_idss'],'floor_idss'=>$layer_info['floor_id'],'iframe'=> $_GET['iframe']))}">【{pigcms{$floor_name}】层列表</a>
                /  <a class="on" href="{pigcms{:U('NewStreet/owner_index',array('community_idss'=>$_GET['community_idss'],'layer_ids'=>$_GET['layer_ids'],'iframe'=> $_GET['iframe']))}">【{pigcms{$layer_name}】住户列表</a>
            </div>


            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form method="get" action="{pigcms{:U('NewStreet/owner_index')}" id="find-form">
                    <input type="hidden" name="c" value="NewStreet"/>
                    <input type="hidden" name="a" value="owner_index"/>
                    <input type="hidden" name="iframe" value="{pigcms{$_GET['iframe']}"/>
                    <input type="hidden" name="community_idss" value="{pigcms{$_GET['community_idss']}"/>
                    <input type="hidden" name="floor_idss" value="{pigcms{$_GET['floor_idss']}"/>
                    <input value="{pigcms{$name}" class="col-sm-2" name="name" id="" type="text" placeholder="请输入住户名称" style="margin-right:10px;font-size:16px;height:30px;"/>
                    <input value="{pigcms{$phone}" class="col-sm-2" name="phone" id="" type="text" placeholder="请输入住户手机号" style="margin-right:10px;font-size:16px;height:30px;"/>
                    &nbsp;&nbsp;
                    <input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('NewStreet/owner_index',array('community_idss'=>$_GET['community_idss'],'floor_idss'=>$_GET['floor_idss'],'iframe'=> $_GET['iframe']))}'">重置</a>
                </form>

            </div>

            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>

                                    <th width="10%">姓名</th>
                                    <th width="10%">手机号</th>
                                    <th width="10%">详细地址</th>
                                    <th width="10%">状态</th>
                                    <th width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.address}</div></td>
                                            <td>
                                                <if condition="$vo['status'] eq 0">
                                                    <div class="tagDiv" style="color: red;">禁止</div>
                                                    <elseif condition="$vo['status'] eq 2"/>
                                                    <div class="tagDiv">审核中</div>
                                                    <else/>
                                                    <div class="tagDiv">正常</div>
                                                </if>
                                            </td>
                                            <td class="textcenter">
                                                <a style="color: #1686ef;" href="javascript:void(0);" onclick="location.href='{pigcms{:U('NewStreet/owner_edit',array('pigcms_id'=>$vo['pigcms_id'],'usernum' => $vo['usernum'],'iframe'=> $_GET['iframe']))}'">编辑</a> |
												<a style="color: #1686ef;" href="javascript:void(0);" onclick="del()">删除</a>
                                            </td>
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="5" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                           
                        </table>
                        <if condition="$list">
                        <div style="float: right; padding-left: 5px; ">
                            <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
                            <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
                            <script>
                                function checkPage(){
                                    var page = $("#page").val();
                                    if(page == ''){
                                        return false;
                                    }
                                    location.href = "{pigcms{:U('floor_index',$_GET)}"+"&page="+page;
                                }
                            </script>
                        </div>
                        {pigcms{$pagebar}
                            </if>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
    function del() {
        layer.open({
            content: '需要删除用户信息请联系对应小区进行删除！'
            ,btn: ['确定']
            ,yes: function(index){
                layer.closeAll();
            }
        });
    }
</script>



<include file="NewStreetPublic:footer"/>

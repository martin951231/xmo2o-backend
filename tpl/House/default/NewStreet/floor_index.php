<include file="NewStreetPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewStreet/floor_index',array('community_idss'=>$_GET['community_idss'],'single_idss'=>$_GET['single_idss']))}"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>管理</a>
            </li>
            <li class="active"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>中心</li>
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
                <a href="{pigcms{:U('NewStreet/house_index',array('community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}">【{pigcms{$community_name}】<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>列表</a>
                /  <a href="{pigcms{:U('NewStreet/single_index',array('village_id'=>$single_info['village_id'],'community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}">【{pigcms{$village_name}】<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?>列表</a>
                /  <a class="on" href="{pigcms{:U('NewStreet/floor_index',array('community_idss'=>$_GET['community_idss'],'single_idss'=>$_GET['single_idss'],'iframe'=> $_GET['iframe']))}">【{pigcms{$single_name}】<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元')?>列表</a>
            </div>


            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form method="get" action="{pigcms{:U('NewStreet/floor_index')}" id="find-form">
                    <input type="hidden" name="c" value="NewStreet"/>
                    <input type="hidden" name="a" value="floor_index"/>
                    <input type="hidden" name="iframe" value="{pigcms{$_GET['iframe']}"/>
                    <input type="hidden" name="single_idss" value="{pigcms{$_GET['single_idss']}"/>
                    <input type="hidden" name="community_idss" value="{pigcms{$_GET['community_idss']}"/>
                    <input value="{pigcms{$floor_name}" class="col-sm-2" name="floor_name" id="" type="text" placeholder="请输入<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元')?>名称" style="margin-right:10px;font-size:16px;height:30px;"/>
                    &nbsp;&nbsp;
                    <input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('NewStreet/floor_index',array('community_idss'=>$_GET['community_idss'],'single_idss'=>$_GET['single_idss'],'iframe'=> $_GET['iframe']))}'">重置</a>
                </form>

            </div>

            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>

                                    <th width="5%"><?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元')?>名称</th>
                                    <th width="10%">状态</th>
                                    <th width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                                            <td>
                                                <if condition="$vo['status'] eq 1">
                                                    <div class="tagDiv">正常</div>
                                                    <else/>
                                                    <div class="tagDiv" style="color: red;">禁止</div>
                                                </if>
                                            </td>
                                            <td class="textcenter">
<!--                                                <a style="color: #1686ef;" href="javascript:void(0);" onclick="location.href='{pigcms{:U('NewStreet/owner_index',array('floor_idss'=>$vo['floor_id'],'community_idss'=>$_GET['community_idss']))}'">进入住户列表</a>-->
                                                <a style="color: #1686ef;" href="javascript:void(0);" onclick="location.href='{pigcms{:U('NewStreet/layer_index',array('floor_idss'=>$vo['floor_id'],'community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}'">进入楼层列表</a>
                                            </td>
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="3" >没有任何数据。</td></tr>
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



<include file="NewStreetPublic:footer"/>

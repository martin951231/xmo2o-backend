<include file="NewStreetPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewStreet/house_index',['iframe'=> $_GET['iframe']])}"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>管理</a>
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
    </style>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <if condition="$_GET['community_idss']">
                <div class="crumbs_box">
                    <if condition="$area_info['area_type'] eq 0"><a href="{pigcms{:U('NewStreet/community_index',['iframe'=> $_GET['iframe']])}">【{pigcms{$street_name}】社区列表</a> / </if>
                    <a class="on" href="{pigcms{:U('NewStreet/house_index',$_GET)}">【{pigcms{$community_name}】<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>列表</a>
                </div>
            </if>


        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" action="{pigcms{:U('NewStreet/house_index',['iframe'=> $_GET['iframe']])}" id="find-form">
                    <input type="hidden" name="c" value="NewStreet"/>
                    <input type="hidden" name="a" value="house_index"/>
                    <input type="hidden" name="iframe" value="pigcms{$_GET['iframe']}"/>
                    <input type="hidden" name="community_idss" value="{pigcms{$_GET['community_idss']}"/>
					<input value="{pigcms{$village_name}" class="col-sm-2" name="village_name" id="" type="text" placeholder="请输入<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称" style="margin-right:10px;font-size:16px;height:30px;"/>
					&nbsp;&nbsp;
					<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('NewStreet/house_index',array('community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}'">重置</a>
				</form>

			</div>

            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>

                                    <th width="5%"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>名称</th>
                                    <th width="10%"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区')?>地址</th>
                                    <th width="10%">状态</th>
                                    <th width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.village_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.village_address}</div></td>
                                            <td>
                                                <if condition="$vo['status'] eq 0 || $vo['status'] eq 1">
                                                    <div class="tagDiv">正常</div>
                                                    <else/>
                                                    <div class="tagDiv" style="color: red;">禁止</div>
                                                </if>
                                            </td>
                                            <td class="textcenter"><a style="color: #1686ef;" href="javascript:void(0);" onclick="location.href='{pigcms{:U('NewStreet/single_index',array('village_id'=>$vo['village_id'],'community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}'">进入<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋')?>列表</a></td>
                                        </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="3" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                           
                        </table>
                        <if condition="$dataList">
                        <div style="float: right; padding-left: 5px; ">
                            <input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
                            <button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
                            <script>
                                function checkPage(){
                                    var page = $("#page").val();
                                    if(page == ''){
                                        return false;
                                    }
                                    location.href = "{pigcms{:U('house_index',$_GET)}"+"&page="+page;
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

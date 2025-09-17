<include file="NewStreetPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('NewStreet/index')}">街道/社区管理</a>
            </li>
            <li class="active">街道/社区管理</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
        	<div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
				<form method="get" action="{pigcms{:U('NewStreet/index')}" id="find-form">
                    <input type="hidden" name="c" value="NewStreet"/>
                    <input type="hidden" name="a" value="index"/>
                    <input type="hidden" name="iframe" value="{pigcms_$_GET['iframe']}"/>
					<input value="{pigcms{$area_name}" class="col-sm-2" name="area_name" id="" type="text" placeholder="请输入社区名称" style="margin-right:10px;font-size:16px;height:30px;"/>
					&nbsp;&nbsp;
					<input class="btn btn-success" type="submit" id="" value="查询" />&nbsp;
					<a class="btn btn-success" onclick="location.href='{pigcms{:U('NewStreet/index')}'">重置</a>
				</form>

			</div>

            
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                                <tr>

                                    <th width="5%">社区名称</th>
                                    <th width="10%">状态</th>
                                    <th width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody class="class-checkbox">
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.area_name}</div></td>
                                            <td>
                                                <if condition="$vo.is_open eq 1">
                                                    <div class="tagDiv">
                                                        正常
                                                    </div>
                                                    <else/>
                                                    <div class="tagDiv" style="color: red;">
                                                        关闭
                                                    </div>
                                                </if>
                                            </td>
                                        <td class="textcenter"><a href="javascript:void(0);" onclick="location.href='{pigcms{:U('NewStreet/house_index',array('community_idss'=> $vo['area_id'],'iframe' => $_GET['iframe']))}'">查看小区</a></td>
                                    </tr>
                                    </volist>

                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
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
                                    location.href = "{pigcms{:U('index')}"+"&page="+page;
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

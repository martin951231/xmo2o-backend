<include file="NewStreetPublic:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/index')}">楼栋管理</a>
            </li>
			&nbsp;&nbsp;
            <li class="active">楼栋列表</li>
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
                /  <a class="on" href="{pigcms{:U('NewStreet/layer_index',array('community_idss'=>$_GET['community_idss'],'floor_idss'=>$_GET['floor_idss'],'iframe'=> $_GET['iframe']))}">【{pigcms{$floor_name}】层列表</a>
            </div>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="30%">楼层名称</th>
                                    <th width="30%">状态</th>
                                    <th  width="40%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.layer_name}</div></td>
                                            <td><div class="tagDiv"><if condition='$vo["status"] eq 1'><span class="green">开启</span><else /><span class="red">关闭</span></if></div></td>
                                            <td class="textcenter"><a style="color: #1686ef;" href="javascript:void(0);" onclick="location.href='{pigcms{:U('NewStreet/owner_index',array('layer_ids'=>$vo['id'],'community_idss'=>$_GET['community_idss'],'iframe'=> $_GET['iframe']))}'">进入住户列表</a></td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >没有任何楼层。</td></tr>
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



<include file="NewStreetPublic:footer"/>

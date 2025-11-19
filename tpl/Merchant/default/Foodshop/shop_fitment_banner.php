<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("轮播图")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<style>
			a:hover,a:visited{color:#666;}
			table th{padding:17px 10px 19px;}
			table td{padding:17px 10px 19px 15px;}
			#good_list li{list-style: none;}
			#good_list li .del{color:blue;float:right;cursor:pointer;-webkit-user-select: none;-webkit-touch-callout: none;}
			#good_list li:hover{color:red;}
			#good_list li .del:hover{text-decoration:underline;color:red;}
			.btn-success, .btn-success:focus{
				position: relative;
				width: 78px;
				height: 34px;
				background-color: #87b87f!important;
				border-color: #87b87f;
				text-align: center;
				line-height: 34px;
				color: white;
				cursor: pointer;
			}
			.webuploader-element-invisible {
				position: absolute !important;
				clip: rect(1px 1px 1px 1px);
				clip: rect(1px,1px,1px,1px);
			}
			.webuploader-pick-hover .btn{
				background-color: #629b58!important;
				border-color: #87b87f;
			}
		</style>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/webuploader.min.js"></script>
	</head>
	<body> 
        <button type="button"  id="url" onclick="addBanner()" style="margin-left:0px;" data-toggle="modal">{pigcms{:L_("添加")}</button>
		<form id="myform" method="post" action="{pigcms{:U('shop_fitment_banner_save')}" autocomplete="off">
			<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
			<input type="hidden" name="subject_pic" id="subject_pic" value="{pigcms{$subject_info.subject_pic}"/>
			<table>
				<tr>
					<th width="20%">编号</th>
                    <th width="20%">名称</th>
                    <th width="20%">图片</th>
                    <th width="20%">是否显示</th>
                    <th width="20%">操作</th>
					
				</tr>
                <if condition="$subject_info['banner']">
                    <volist name="subject_info['banner']" id="vo">
                    <tr>
                        <td>{pigcms{$key+1}</td>
                        <td>{pigcms{$vo.name}</td>
                        <td><img src="{pigcms{:replace_file_domain($vo['pic'])}" style="width: 40px;;"></td>
                        <td><if condition="$vo['status'] eq 1">显示<else/>不显示</if></td>
                        <td> 
                            <button type="button" onclick="addBanner('{pigcms{$key}')" style="margin-left:0px;" data-toggle="modal">{pigcms{:L_("编辑")}</button>
                            <button type="button"  id="url" onclick="del('{pigcms{$key}')" style="margin-left:0px;">{pigcms{:L_("删除")}</button>
                        </td>
                    </tr>
                    </volist>
                </if>
					
				
			</tr>
			</table>
		</form>
		<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
        <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
        <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
        <script type="text/javascript">
            function addBanner(id){
                art.dialog.open('{pigcms{:U("shop_fitment_banner_edit")}&store_id={pigcms{$_GET.store_id}&id='+id,{lock:true,title:'{pigcms{:L_("添加轮播图")}',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45,
            	close: function(){/*关闭窗口时的回调函数*/
    				window.location.reload();
    			}
    			});
            }
            
            function del(id){
               layer.confirm('确定删除？', {
                  btn: ['确定', '取消']
                }, function(index, layero){
                  //按钮【按钮一】的回调
                  window.location.href='{pigcms{:U("shop_fitment_banner_del")}&store_id={pigcms{$_GET.store_id}&id='+id;
                }, function(index){

                });
            }
        </script>
	</body>
</html>
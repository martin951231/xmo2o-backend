<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <title>认证审核</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/recruit/css/style.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}/recruit/js/validator.js"></script> 
</head>
<body> 
	<div class="certificationBox">
	  <div class="certificationTop">
	  	<span class="fl">{pigcms{$now_company['add_time']|date='Y-m-d H:i:s',###}</span>
        <if condition='$now_company["status"] eq 0'>
            <a href="#" class="fr certificationStatic">审核中</a>
        <elseif condition='$now_company["status"] eq 1'/>
            <a href="#" class="fr certificationStatic green">已通过</a>
        <else/>
            <a href="#" class="fr certificationStatic red">未通过</a>
        </if>
	  	<div class="clear"></div>
	  </div> 
	  
	   <form id="submitForm" action="/wap.php?g=Wap&c=Employee&a=employee_reply" method="post">
        <div style="padding-bottom:80px">
            <div class="weui-cells weui-cells_form" >
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">未通过原因</label></div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['reason']}
                    </div>
                </div> 
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">企业名称</label></div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['company_name']}
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">法人名称</label>
                    </div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['legal_person_name']}
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">手机号</label>
                    </div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['phone']}
                    </div>
                </div>
                 
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">身份证号</label>
                    </div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['ID_number']}
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">地址</label>
                    </div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['address']}{pigcms{$now_company['detail']}
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">店铺面积</label>
                    </div>
                    <div class="weui-cell__bd">
                       {pigcms{$now_company['acreage']}
                    </div>
                </div> 
                 
                <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <div class="weui-uploader">
                            <div class="weui-uploader__hd">
                                <p class="weui-uploader__title">营业执照上传</p> 
                            </div>
                            <div class="weui-uploader__bd">
                                <ul class="lifePhotos">
                                    <volist name="now_company.license_pic" id="vo">
			                             <li><img class="w100" src="{pigcms{:file_domain().$vo}" style="height: 107px;"></li>
                                    </volist>
			                      </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                 <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <div class="weui-uploader">
                            <div class="weui-uploader__hd">
                                <p class="weui-uploader__title">店铺环境上传</p> 
                            </div>
                            <div class="weui-uploader__bd">
                                 <ul class="lifePhotos">
                                    <volist name="now_company.company_pic" id="vo">
                                         <li><img class="w100" src="{pigcms{:file_domain().$vo}" style="height: 107px;"></li>
                                    </volist>
			                      </ul>
			                      
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <if condition="$now_company['status'] eq 2">
            <a href="{pigcms{:U('company_apply')}" class="weui-btn weui-btn_primary submit">重新提交</a>
        </if>
    </form>
	  
	</div>
	
	 <script>
        $.each($(".lifePhotos img"),function(i,item){
          console.log($(item).width())
            $(item).css('height',$(item).width()+'px')
        })
    </script>
</body>
</html>
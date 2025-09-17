<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
   .form-group{
       display:inline-block;width: 49%;margin-left: 0!important;margin-right: 0!important;padding: 5px;
   }
   .key2{
       text-align: left;padding-left: 8px !important;line-height: 37px;
   }
    .value2{
        background:#e8e8e8;padding: 9px 0 9px 5px;border-radius:8%;
    }
    .tip1{
        box-shadow:-5px 0px 0 0 #1890FF;margin-left: 5px;color: #1890FF;padding-left: 10px;margin-bottom: 20px;
    }
    .img1{
        width: 100%;
    }
    .div_img{
        display: inline-block;width: 42%;margin-right: 50px;
    }
    .img_title{
        background:#e8e8e8;padding: 5px;display: inline-block;color: black;
    }
</style>
<div class="page-content" style="background-color: white; ">
    <div class="row" style="background-color: white;">
        <div class="col-xs-12">
            <div class="tab-content" style="padding-left: 0 !important;border: none;padding-top: 0px !important;">
                <h5 class="tip1">车辆信息</h5>
                <div>
                    <if condition="$details['car_list']">
                        <volist name="details['car_list']" id="vo">
                            <div class="form-group">
                                <span class="col-sm-4 key2">{pigcms{$vo.key}：</span>
                                <span class="col-sm-6 value2">{pigcms{$vo.value}</span>
                            </div>
                        </volist>
                    </if>
                </div>
                <if condition="$details['car_img']">
                <h5 class="tip1">进出抓拍图片</h5>
                <div style="padding-left: 15px">
                        <volist name="details['car_img']" id="vo">
                            <a href="{pigcms{$vo.key}" target="_blank" class="div_img">
                                <img class="img1" src="{pigcms{$vo.key}">
                                <span class="img_title">{pigcms{$vo.value}</span>
                            </a>
                        </volist>
                </div>
                </if>
            </div>
        </div>
    </div>
</div>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<include file="Publics:footer"/>

<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="cat_name">分类名称：</label></label>
                                    <input class="col-sm-2" size="20" name="cate_name" id="cate_name" type="text" value="{pigcms{$detail.cate_name}" maxlength="6"/>
                                </div></div>
                            <input type="hidden" name="id" value="{pigcms{$detail.id}">

                            <div class="form-group">
                                <label class="col-sm-1"><label for="sort">分类排序：</label></label>
                                <input type="text" value="{pigcms{$detail.sort}" id="sort" name="sort" size="20" class="col-sm-2" >
                                <label class="col-sm-3"><span class="red">*&nbsp;&nbsp;可不填写（排序值越大，越靠前显示）</span></label>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1">是否开启</label>

                                <label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$detail.status eq 1'>checked="checked"</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
                                <label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="status" <if condition='$detail.status eq 0'>checked="checked"</if>><span style="z-index: 1" class="lbl">关闭</span></label>
                            </div>
                            <div class="space"></div>
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <if condition="$_GET['cat_id']"><input type="hidden" name="parent_id" value="{pigcms{$_GET.cat_id}"/></if>
                                    <button class="btn btn-info" type="submit">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <style>
        .BMap_cpyCtrl{display:none;}
        input.ke-input-text {
            background-color: #FFFFFF;
            background-color: #FFFFFF!important;
            font-family: "sans serif",tahoma,verdana,helvetica;
            font-size: 12px;
            line-height: 24px;
            height: 24px;
            padding: 2px 4px;
            border-color: #848484 #E0E0E0 #E0E0E0 #848484;
            border-style: solid;
            border-width: 1px;
            display: -moz-inline-stack;
            display: inline-block;
            vertical-align: middle;
            zoom: 1;
        }
        .form-group>label{font-size:12px;line-height:24px;}
        #upload_pic_box{margin-top:20px;height:150px;}
        #upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
        #upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
    </style>
    <script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
    <script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
    <script type="text/javascript">
        function check_submit(){
            if($('#cate_name').val()==''){
                alert('分类名称不能为空！');
                return false;
            }

            if(confirm('确认保存？')){
                return true;
            }else{
                return false;
            }
        }

    </script>




    <include file="Public:footer"/>
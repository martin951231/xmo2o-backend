<include file="Public:header"/>

<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tablet"></i>
                <a href="{pigcms{:U('attribute_list')}">业主属性管理</a>
            </li>
            <li class="active">添加收费标准</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">
                                <input type="hidden" name="id" id="id" value="{pigcms{$_GET['id']}">
                                 <div class="form-group">
                                    <label class="col-sm-1"><label for="name">属性名</label></label>
                                    <input  size="20" name="name" id="name" type="text" value="{pigcms{$attribute_detail['name']}"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="red">（必填项）</span>
                                </div>
                            </div>
                        </div>
                        <div class="space"></div>
                            <div class="clearfix form-actions">
                                <div class="col-md-offset-3 col-md-9">
                                    <if condition="in_array(282,$house_session['menus'])">
                                    <button class="btn btn-info" type="submit">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                    <else/>
                                    <button class="btn btn-info" type="submit" disabled="disabled">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                    </if>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<include file="Public:footer"/>
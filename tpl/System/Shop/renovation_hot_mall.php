<include file="Public:header"/>
<div class="title">热门搜索词：</div>
<form id="myform" method="post" action="{pigcms{:U('Shop/renovation_hot_mall_modify',array('parentid'=>$_GET['parentid']))}" enctype="multipart/form-data">
    <div class="content_list">
        <div class="addItems">
            <div id="InputsWrapper">
                <volist name="renovation_hot_list" id="vo">
                    <div class="input_box" id="field_{pigcms{$vo.id}">
                        <input type="text" name="names_{pigcms{$vo.id}" value="{pigcms{$vo.name}">
                        <input type="button" class="delinput" onclick="delinput('field_{pigcms{$vo.id}')" value="-">
                        <div style="clear:both"></div>
                    </div>
                </volist>
            </div>
            <input type="button" id="AddMoreFileBox" class="add" value="+ 添加搜索词 ( 0/6 )" />
        </div>
    </div>
    <div class="btn hidden">
        <input type="reset" value="取消" class="button" />
        <input type="submit" id="dosubmit" value="提交" class="button" />
    </div>
</form>
<style>
    *{
        background-color: #FFF;
    }
    .title{
        width: 180px;
        height: 350px;
        line-height: 36px;
        float: left;
        font-size: 16px;
        text-align:right;
    }
    .content_list{
        width: 364px;
        height: 350px;
        float: left;
    }
    .input_box{
        width: 100%;
        height: 50px;
    }
    .input_box input{
        width: 290px;
        height:30px;
        line-height: 30px;
        font-size: 14px;
        padding-left: 6px;
        margin-right: 10px;
        border-radius: 4%;
        border: 1px solid #CCC;
        float: left;
    }
    .removeclass input, input.delinput{
        width: 26px;
        height: 26px;
        line-height: 2px;
        color: #999;
        border-radius: 50%;
        border: 3px solid #CCC;
        float: left;
        margin-top:4px
    }
    .removeclass{
        width: 26px;
        height: 26px;
        line-height: 2px;
        color: #999;
        border-radius: 50%;
        display: block;
        float: left;
    }
    .add{
        width: 300px;
        height:32px;
        font-size: 14px;
        border: 1px dashed #CCC;
        
    }
</style>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
function addLink(domid){
    art.dialog.data('domid', domid);
    art.dialog.open('?g=Admin&c=LinkPC&a=insert',{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
}
</script>


<script>
    // 追加input
    $(document).ready(function () {
        var put=document.getElementsByTagName("input");
        var number = (put.length - 5)/2;
        $("#AddMoreFileBox").attr("value","+ 添加搜索词 ( "+number+"/6 )");

        var MaxInputs = 6 - number;                 // 最多可以添加几个
        var InputsWrapper = $("#InputsWrapper");    // 添加的input输入框的外层div的ID
        var AddButton = $("#AddMoreFileBox");       // 添加按钮ID
        var x = InputsWrapper.length;               // 初始文本框计数
        var FieldCount = number;                    // 文本框开始数字

        // 点击添加按钮后执行添加上传表单相关信息
        $(AddButton).click(function (e) {
            var put=document.getElementsByTagName("input");
            var number = (put.length - 3)/2;
            MaxInputs = 6-number+x;

            // 允许的input
            if (x <= MaxInputs) {
                // 文本框增加增量
                FieldCount++;
                // 添加input输入框
                $(this).empty();
                $(InputsWrapper).append(
                    '<div class="input_box"><input type="text" name="name_'+ FieldCount +'" id="field_' +
                    FieldCount +
                    '"/><a href="#" class="removeclass"><input type="button" value="-" ></a><div style="clear:both"></div></div>'
                );
                x++;    // 文本框增量
                $("#AddMoreFileBox").attr("value","+ 添加搜索词 ( "+number+"/6 )");
                
            }
            return false;
        });
        $(".addItems").on("click", ".removeclass", function (e) {   // 用户单击删除文本
            console.log(e);
            if (x > 1) {
                $(this).parent('div').remove(); // 删除文本框
                x--;    // 减量文本框
                var put=document.getElementsByTagName("input");
                var number = (put.length - 5)/2;
                $("#AddMoreFileBox").attr("value","+ 添加搜索词 ( "+number+"/6 )");
            }
            return false;
        })
    });
    function delinput(id){
        $("#"+id).remove();
        var put=document.getElementsByTagName("input");
        var number = (put.length - 5)/2;
        $("#AddMoreFileBox").attr("value","+ 添加搜索词 ( "+number+"/6 )");
    }
</script>
<include file="Public:footer"/>
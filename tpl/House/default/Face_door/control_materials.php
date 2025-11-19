<meta http-equiv="Content-Disposition" content="attachment;">
<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Face_door/door_list')}">硬件设备</a>
            </li>
            <li class="active">门禁物料管理</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">

                        <div class="box" style="width: 700px;margin-top: 15px">
                            <div class="top_div" style="height: 50px">
                                <div style="height: 30px;width: 10px;background-color: dodgerblue;float: left"></div>
                                <div style="float: left;font-size: 15px;line-height: 30px;margin-left: 10px"><b>网页二维码</b></div>
                                <div style="float: left;margin-left: 450px"><button style="height: 37px;width: 70px;background-color: dodgerblue;border-radius: 5px; border-color: white;color: white" type="button"><a href="{pigcms{:U('Face_door/saveImage',array('url'=>$img_url))}">下载</a></button></div>
                            </div>
                            <div class="center_div" style="50px"><span>下载后分享给住户，住户扫码后可申请入驻<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?>，并且可以使用该二维码对应到下方各类物料中</span></div>
                            <div class="bottom_div" style="text-align: center;margin-top: 15px"><img src="{pigcms{$img_url}" height="200px" width="200px"></div>
                        </div>

                        <div class="box" style="width: 700px;margin-top: 15px">
                            <div class="top_div" style="height: 50px">
                                <div style="height: 30px;width: 10px;background-color: dodgerblue;float: left"></div>
                                <div style="float: left;font-size: 15px;line-height: 30px;margin-left: 10px"><b>朋友圈海报</b></div>
                                <div style="float: left;margin-left: 450px"><button style="height: 37px;width: 70px;border-radius: 5px;background-color: dodgerblue;border-color: white;color: white" type="button" onclick="download_seal()">下载</button></div>
                            </div>
                            <div class="center_div" style="50px"><span>下载后分可分享到朋友圈，住户扫码后可申请入驻<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?></span></div>
                            <div class="bottom_div" style="text-align: center;margin-top: 15px"><img src="{pigcms{$static_public}file/psd/seal.png" height="400px" width="200px" id="seal"></div>
                        </div>

                        <div class="box" style="width: 700px;margin-top: 15px">
                            <div class="top_div" style="height: 50px">
                                <div style="height: 30px;width: 10px;background-color: dodgerblue;float: left"></div>
                                <div style="float: left;font-size: 15px;line-height: 30px;margin-left: 10px"><b>X-展架、易拉宝</b></div>
                                <div style="float: left;margin-left: 450px"><button style="height: 37px;width: 70px;border-radius: 5px;background-color: dodgerblue;border-color: white" type="button"><a href="https://cloud.189.cn/t/ABFzQjZRfEvi" download="seal" target="_blank" style="color: white">psd下载</a></button></div>
                            </div>
                            <div class="center_div" style="50px"><span>尺寸：80cm*200cm，应用于<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?>门口，<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?>内道路旁等场景</span></div>
                            <div class="bottom_div" style="text-align: center;margin-top: 15px"><img src="{pigcms{$static_public}file/psd/box.png" height="300px" width="120px"></div>
                        </div>

                        <div class="box" style="width: 700px;margin-top: 15px">
                            <div class="top_div" style="height: 50px">
                                <div style="height: 30px;width: 10px;background-color: dodgerblue;float: left"></div>
                                <div style="float: left;font-size: 15px;line-height: 30px;margin-left: 10px"><b>常规海报</b></div>
                                <div style="float: left;margin-left: 450px"><button style="height: 37px;width: 70px;border-radius: 5px;background-color: dodgerblue;border-color: white" type="button"><a href="https://cloud.189.cn/t/ABFzQjZRfEvi" download="seal" target="_blank" style="color: white">psd下载</a></button></div>
                            </div>
                            <div class="center_div" style="50px"><span>尺寸：50cm*70cm，应用于<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?>门口、电梯、公告栏等场景</span></div>
                            <div class="bottom_div" style="text-align: center;margin-top: 15px"><img src="{pigcms{$static_public}file/psd/seal_.png" height="280px" width="200px"></div>
                        </div>

                        <div class="box" style="width: 700px;margin-top: 15px">
                            <div class="top_div" style="height: 50px">
                                <div style="height: 30px;width: 10px;background-color: dodgerblue;float: left"></div>
                                <div style="float: left;font-size: 15px;line-height: 30px;margin-left: 10px"><b>A4宣传页</b></div>
                                <div style="float: left;margin-left: 450px"><button style="height: 37px;width: 70px;border-radius: 5px;background-color: dodgerblue;border-color: white" type="button"><a href="https://cloud.189.cn/t/ABFzQjZRfEvi" download="seal" target="_blank" style="color: white">psd下载</a></button></div>
                            </div>
                            <div class="center_div" style="50px"><span>尺寸：21cm*29.7cm，应用于<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?>门口、电梯、公告栏等场景</span></div>
                            <div class="bottom_div" style="text-align: center;margin-top: 15px"><img src="{pigcms{$static_public}file/psd/a4.png" height="350px" width="248px"></div>
                        </div>

                        <div class="box" style="width: 700px;margin-top: 15px">
                            <div class="top_div" style="height: 50px">
                                <div style="height: 30px;width: 10px;background-color: dodgerblue;float: left"></div>
                                <div style="float: left;font-size: 15px;line-height: 30px;margin-left: 10px"><b>出入口闸机粘贴材料</b></div>
                                <div style="float: left;margin-left: 450px"><button style="height: 37px;width: 70px;border-radius: 5px;background-color: dodgerblue;border-color: white" type="button"><a href="https://cloud.189.cn/t/ABFzQjZRfEvi" download="seal" target="_blank" style="color: white">psd下载</a></button></div>
                            </div>
                            <div class="center_div" style="50px"><span>尺寸：根据<?php echo D('House_village')->getHouseAlias('house_name',$village_id)?:'小区'?>门禁闸机尺寸进行修改调整</span></div>
                            <div class="bottom_div" style="text-align: center;margin-top: 15px"><img src="{pigcms{$static_public}file/psd/door.png" height="531px" width="189px"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>

    function download_seal() {
        var seal_img = "/static/file/psd/seal1.png";
        var village_id = "{pigcms{$village_id}";
        var logo = "{pigcms{$img_url}";
        var params = {
            'bgimg'	: seal_img,
            'village_id':village_id,
            'logo':logo,
        };
        $.post("{pigcms{:U('Face_door/imgCombine')}", params, function(data) {
            data = JSON.parse(data);
            if(data.status == 1){
                downloadImage(data.info,'poster');
            }
        });
    }

    /**
     * 下载图片
     * @auther lijie
     * @date_time 2020/12/10
     * @param path
     * @param imgName
     */
    function downloadImage(path,imgName) {
        var _OBJECT_URL;
        var request = new XMLHttpRequest();
        request.addEventListener('readystatechange', function (e) {
            if (request.readyState == 4) {
                _OBJECT_URL = URL.createObjectURL(request.response);
                var $a = $("<a></a>").attr("href", _OBJECT_URL).attr("download", imgName);
                $a[0].click();
            }
        });
        request.responseType = 'blob';
        request.open('get', path);
        request.send();
    }
</script>
<include file="Public:footer"/>

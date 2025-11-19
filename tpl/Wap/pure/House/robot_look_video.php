<!DOCTYPE html>
<meta charset="utf-8">
<meta content="yes" name="apple-mobile-web-app-capable">
<meta content="yes" name="apple-touch-fullscreen">
<meta content="telephone=no,email=no" name="format-detection">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>机器人名称</title>
    <style type="text/css">
    .videodiv{
        margin: 0px 10px 0px 10px;
        width: 96%;
        display: inline-block;
        text-align: center;
    }
    video {
        width: 90%;
        min-height: 100px;
        border: 1px solid gray;
        max-height: 520px;
    }
    .btn_class{
        text-align: center;
        margin-top: 20px;
    }
    .start_btn{
        margin-right: 50px;
        background-color: #3C9CFF;
        border: 1px solid #3C9CFF;
        padding: 5px 10px;
        border-radius: 7px;
        color: #ffffff;
    }
    .stop_btn{
        background-color: #F9AE3D;
        border: 1px solid #F9AE3D;
        padding: 5px 10px;
        border-radius: 7px;
        color: #ffffff;
    }
    #tips_class{
        display: none;
        text-align: center;
        color: red;
        margin-bottom: 15px;
    }
    </style>
    <script type="text/javascript" src="./static/js/robot_hariservice.min.js"></script>
</head>
<body>
<div>
    <div class="videodiv" style="display: none;">
        <p>rcu</p>
        <video id="rcuId" autoplay></video>
        <p>ice status: <label></label></p>
    </div>
     <div class="videodiv">
        <p>顶部相机</p>
        <video id="top" autoplay></video>
       <!-- <p>ice status: <label></label></p> -->
    </div>
    <!--<div class="videodiv">
        <p>left</p>
        <video id="left" autoplay></video>
        <p>ice status: <label></label></p>
    </div>
    <div class="videodiv">
        <p>right</p>
        <video id="right" autoplay></video>
        <p>ice status: <label></label></p>
    </div>
    <div class="videodiv">
        <p>front</p>
        <video id="front" autoplay></video>
        <p>ice status: <label></label></p>
    </div>
    <div class="videodiv">
        <p>behind</p>
        <video id="behind" autoplay></video>
        <p>ice status: <label></label></p>
    </div> -->
</div>
<div id="tips_class"><span>请稍等，正在连接服务器，获取视频中...</span></div>
<div class="btn_class">
    <button onclick="start()" class="start_btn" >开始预览</button>
    <button onclick="stop()"  class="stop_btn">停止预览</button>
</div>
</body>

<script>
    
    var service = new OnlineService();
    document.getElementsByTagName("title")[0].innerHTML = '{pigcms{$robot_login_lnfo["robot"]["name"]}' || '机器人名称'
    function messageCB(data) {
        console.log('--messageCB: ', data)
    }
    function timerCB(time){
        console.log('time: '+time);
    }
    function mediaStatusCB(status, name) {
        console.log('--mediaStatusCB: ', status,'name',name)
        var tipsObj = document.getElementById("tips_class");
        tipsObj.style.display='none';
    }
    // 成功连接服务器的回调函数
    function startServiceCB() {
        console.log('--startServiceCB: ')
    }
    function stopServiceCB() {
        console.log('--stopServiceCB: ')
    }
    function tipCB(message, errorcode) {
        console.log('--tipCB: ', message)
    }

    // 开始服务， 若页面加载完毕后调用，则需等待页面完全加载，避免找不到video标签
    // gatewayAddress 提供ws和wss端口， HTTP服务对应ws，HTTPS对应wss，建议使用HTTPS
    function start(){
        var tipsObj = document.getElementById("tips_class");
        tipsObj.style.display='block';
        var userObj = {
          userName: 'juju'+guid(),    // 系统用户名, 
          role: 'view', 
          accountId: '{pigcms{$robot_login_lnfo["accountId"]}' ,   // 'jane-pepper', //
          tenantId: '{pigcms{$robot_login_lnfo["tenantId"]}',
          robotType: '{pigcms{$robot_login_lnfo["robotType"]}',
          sid: '{pigcms{$robot_login_lnfo["sid"]}',  
          token: '{pigcms{$robot_login_lnfo["token"]}',
          rcuVideoId: 'rcuId', //接收声音的Video标签ID
          consoleLevel: 'dev', // dev | prod
          needRcuMedia: 1,   // 默认是否需要接收声音， 0：不需要， 1：需要
          src: 'CRSS',  // 模块名
          version: '3.0',
          serverAddress: '{pigcms{$robot_login_lnfo["signalAddress"]}',
          gatewayAddress: 'wss://fsgia.harix.iamidata.com:20147/hari',  
          stunUrl: "39.100.122.115:3478", 
           watchCameras: [
             {"name": "top", "bandwidth": "high", "videodom":'top'},
          //   {"name": "left", "bandwidth": "low", "videodom":'left'},
          //   {"name": "right", "bandwidth": "low", "videodom":'right'},
          //   {"name": "front", "bandwidth": "low", "videodom":'front'},
          //  {"name": "behind", "bandwidth": "medium", "videodom":'behind'}
           ]
        }
        console.log('---start------');
        service.init(userObj, messageCB, timerCB, mediaStatusCB, startServiceCB, stopServiceCB, tipCB);
    }

    /**
     * 生成uuid
     * @returns {string}
     */
    function guid() {
        function S4() {
            return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
        }
        return (S4() + S4() + '-' + S4() + '-' + S4() + '-' + S4() + '-' + S4() + S4() + S4());
    }

    // 停止服务
    function stop(){
        service.hangUp()
        var tipsObj = document.getElementById("tips_class");
        tipsObj.style.display='none';
    }
    /**
     * 添加日志回调函数
     * @param message 日志信息 string
     * @param mode    模块 string
     * @param type    日志类型 string: info warn error
     */
    function logger(message, mode, type) {
        console.log('---'+mode+'【'+type+'】'+message);
    }
    
    
</script>
</html>

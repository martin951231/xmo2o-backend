// DOM解析完毕
document.addEventListener('DOMContentLoaded',function(){
    // REM自适应
    ;(function(doc,win){
        function change(){
            doc.documentElement.style.fontSize = doc.documentElement.clientWidth/15+'px';
            // 750设计图写15 640设计图写16
        }
        win.addEventListener('resize',change,false);
        change();
    })(document,window);
},false)
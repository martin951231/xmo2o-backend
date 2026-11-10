require('../common/vendor.js');(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/loanModule/components/wxBank"],{"45f5":function(n,t,e){"use strict";e.r(t);var a=e("8d63"),c=e("a1da");for(var o in c)["default"].indexOf(o)<0&&function(n){e.d(t,n,(function(){return c[n]}))}(o);e("a9e6e");var u,r=e("f0c5"),f=Object(r["a"])(c["default"],a["b"],a["c"],!1,null,"4eaf8046",null,!1,a["a"],u);t["default"]=f.exports},"830c":function(n,t,e){},"8d63":function(n,t,e){"use strict";var a;e.d(t,"b",(function(){return c})),e.d(t,"c",(function(){return o})),e.d(t,"a",(function(){return a}));var c=function(){var n=this,t=n.$createElement;n._self._c},o=[]},a1da:function(n,t,e){"use strict";e.r(t);var a=e("c9fe"),c=e.n(a);for(var o in a)["default"].indexOf(o)<0&&function(n){e.d(t,n,(function(){return a[n]}))}(o);t["default"]=c.a},a9e6e:function(n,t,e){"use strict";var a=e("830c"),c=e.n(a);c.a},c9fe:function(n,t,e){"use strict";(function(n){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a={props:{bank_wechat_qrcode:{type:String,default:""}},data:function(){return{}},methods:{savePicture:function(){this.bank_wechat_qrcode&&""!=this.bank_wechat_qrcode?n.getImageInfo({src:e("65fd"),success:function(t){console.log("图片信息：",JSON.stringify(t)),n.downloadFile({url:t.path,success:function(n){200===n.statusCode&&console.log("下载成功")}})}}):n.showToast({title:"图片地址不能为空！",icon:"none"})}}};t.default=a}).call(this,e("543d")["default"])}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/loanModule/components/wxBank-create-component',
    {
        'pages/loanModule/components/wxBank-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("45f5"))
        })
    },
    [['pages/loanModule/components/wxBank-create-component']]
]);

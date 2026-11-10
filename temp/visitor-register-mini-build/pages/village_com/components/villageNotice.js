(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/village_com/components/villageNotice"],{"6fb7":function(t,n,e){"use strict";var o=e("eba1"),u=e.n(o);u.a},7728:function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var o={data:function(){return{}},props:{noticeData:{type:Object,default:function(){return{}}},listData:{type:Object,default:function(){return{}}}},computed:{mtopStyle:function(){var t;return{marginTop:null!==(t=this.listData)&&void 0!==t&&t.jz_open?"20rpx":"15rpx"}}},mounted:function(){},methods:{goUrl:function(t){console.log(t),this.noticeData&&t&&this.common.parseUrl(t,"",!1,"")}}};n.default=o},baa7:function(t,n,e){"use strict";e.r(n);var o=e("cc98"),u=e("de27");for(var a in u)["default"].indexOf(a)<0&&function(t){e.d(n,t,(function(){return u[t]}))}(a);e("6fb7");var c,i=e("f0c5"),r=Object(i["a"])(u["default"],o["b"],o["c"],!1,null,"2e4dac7c",null,!1,o["a"],c);n["default"]=r.exports},cc98:function(t,n,e){"use strict";e.d(n,"b",(function(){return u})),e.d(n,"c",(function(){return a})),e.d(n,"a",(function(){return o}));var o={uNoticeBar:function(){return e.e("uview-ui/components/u-notice-bar/u-notice-bar").then(e.bind(null,"e47d"))}},u=function(){var t=this,n=t.$createElement,e=(t._self._c,t.noticeData.title.length);t.$mp.data=Object.assign({},{$root:{g0:e}})},a=[]},de27:function(t,n,e){"use strict";e.r(n);var o=e("7728"),u=e.n(o);for(var a in o)["default"].indexOf(a)<0&&function(t){e.d(n,t,(function(){return o[t]}))}(a);n["default"]=u.a},eba1:function(t,n,e){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/village_com/components/villageNotice-create-component',
    {
        'pages/village_com/components/villageNotice-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("baa7"))
        })
    },
    [['pages/village_com/components/villageNotice-create-component']]
]);

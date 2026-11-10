(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/runErrands/components/SelectGoods"],{"0445":function(t,n,e){"use strict";(function(t){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e=getApp(),o={name:"SelectGoods",data:function(){return{}},props:{showTxt:{type:String,default:""}},watch:{showTxt:{immediate:!0,handler:function(t){console.log("配送的物品类型、重量",t)}}},mounted:function(){e||(e=getApp())},methods:{selectType:function(){e.globalData.is_login?t.navigateTo({url:"/pages/runErrands/goodsType"}):this.$emit("checkLogin")}}};n.default=o}).call(this,e("543d")["default"])},"31de":function(t,n,e){},"49d0":function(t,n,e){"use strict";var o=e("31de"),a=e.n(o);a.a},9419:function(t,n,e){"use strict";var o;e.d(n,"b",(function(){return a})),e.d(n,"c",(function(){return u})),e.d(n,"a",(function(){return o}));var a=function(){var t=this,n=t.$createElement;t._self._c},u=[]},bea1:function(t,n,e){"use strict";e.r(n);var o=e("0445"),a=e.n(o);for(var u in o)["default"].indexOf(u)<0&&function(t){e.d(n,t,(function(){return o[t]}))}(u);n["default"]=a.a},d7c4:function(t,n,e){"use strict";e.r(n);var o=e("9419"),a=e("bea1");for(var u in a)["default"].indexOf(u)<0&&function(t){e.d(n,t,(function(){return a[t]}))}(u);e("49d0");var r,c=e("f0c5"),i=Object(c["a"])(a["default"],o["b"],o["c"],!1,null,"050b1874",null,!1,o["a"],r);n["default"]=i.exports}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/runErrands/components/SelectGoods-create-component',
    {
        'pages/runErrands/components/SelectGoods-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("d7c4"))
        })
    },
    [['pages/runErrands/components/SelectGoods-create-component']]
]);

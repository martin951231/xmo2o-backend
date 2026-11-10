(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/runErrands/components/TotalPrice"],{"40ce":function(e,n,t){"use strict";t.r(n);var r=t("508b"),c=t("d0ef");for(var o in c)["default"].indexOf(o)<0&&function(e){t.d(n,e,(function(){return c[e]}))}(o);t("458c");var a,i=t("f0c5"),u=Object(i["a"])(c["default"],r["b"],r["c"],!1,null,"76326f5a",null,!1,r["a"],a);n["default"]=u.exports},"458c":function(e,n,t){"use strict";var r=t("4633"),c=t.n(r);c.a},4633:function(e,n,t){},"508b":function(e,n,t){"use strict";var r;t.d(n,"b",(function(){return c})),t.d(n,"c",(function(){return o})),t.d(n,"a",(function(){return r}));var c=function(){var e=this,n=e.$createElement;e._self._c},o=[]},cfe4:function(e,n,t){"use strict";var r=t("4ea4");Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var c=r(t("a3a9")),o={name:"TotalPrice",data:function(){return{showPriceMask:!1,mobile_head_color:""}},props:["priceInfo","currency"],mounted:function(){var e=this;c.default.getConfig((function(n){e.mobile_head_color=n.config.mobile_head_color}))},methods:{openMask:function(){this.showPriceMask=!this.showPriceMask},preventClick:function(){return!1},saveOrder:function(){this.$emit("saveOrder")}}};n.default=o},d0ef:function(e,n,t){"use strict";t.r(n);var r=t("cfe4"),c=t.n(r);for(var o in r)["default"].indexOf(o)<0&&function(e){t.d(n,e,(function(){return r[e]}))}(o);n["default"]=c.a}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/runErrands/components/TotalPrice-create-component',
    {
        'pages/runErrands/components/TotalPrice-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("40ce"))
        })
    },
    [['pages/runErrands/components/TotalPrice-create-component']]
]);

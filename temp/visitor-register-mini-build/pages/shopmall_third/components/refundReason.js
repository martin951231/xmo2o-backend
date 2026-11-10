require('../common/vendor.js');(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/shopmall_third/components/refundReason"],{"04ea":function(e,t,n){},"0bbf":function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var c={props:{translateY:Number,type:{default:"afterSale",type:String}},data:function(){return{modelstatus:!1,reasonList:[{value:"7天无理由",checked:!1},{value:"退运费",checked:!1},{value:"大小/尺寸与商品描述不符",checked:!1}]}},methods:{closemodel:function(e){this.$emit("closefnc",e)},forbid:function(){this.$emit("sliderfnc")},reasonClick:function(e){this.reasonList.forEach((function(e){e.checked=!1})),e.checked=!0,this.closemodel(e.value)}}};t.default=c},"581c":function(e,t,n){"use strict";n.r(t);var c=n("5f58"),a=n("6813");for(var u in a)["default"].indexOf(u)<0&&function(e){n.d(t,e,(function(){return a[e]}))}(u);n("92e1");var r,o=n("f0c5"),f=Object(o["a"])(a["default"],c["b"],c["c"],!1,null,"90c7f434",null,!1,c["a"],r);t["default"]=f.exports},"5f58":function(e,t,n){"use strict";var c;n.d(t,"b",(function(){return a})),n.d(t,"c",(function(){return u})),n.d(t,"a",(function(){return c}));var a=function(){var e=this,t=e.$createElement,c=(e._self._c,n("c6d9"));e.$mp.data=Object.assign({},{$root:{m0:c}})},u=[]},6813:function(e,t,n){"use strict";n.r(t);var c=n("0bbf"),a=n.n(c);for(var u in c)["default"].indexOf(u)<0&&function(e){n.d(t,e,(function(){return c[e]}))}(u);t["default"]=a.a},"92e1":function(e,t,n){"use strict";var c=n("04ea"),a=n.n(c);a.a}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/shopmall_third/components/refundReason-create-component',
    {
        'pages/shopmall_third/components/refundReason-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("581c"))
        })
    },
    [['pages/shopmall_third/components/refundReason-create-component']]
]);

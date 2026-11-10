(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/village_com/newCom/livePlayerCom"],{"0df6":function(e,t,n){},"54c8":function(e,t,n){"use strict";var i;n.d(t,"b",(function(){return a})),n.d(t,"c",(function(){return r})),n.d(t,"a",(function(){return i}));var a=function(){var e=this,t=e.$createElement,n=(e._self._c,e.playerList.length);e.$mp.data=Object.assign({},{$root:{g0:n}})},r=[]},6786:function(e,t,n){"use strict";n.r(t);var i=n("cb35"),a=n.n(i);for(var r in i)["default"].indexOf(r)<0&&function(e){n.d(t,e,(function(){return i[e]}))}(r);t["default"]=a.a},"6ba1":function(e,t,n){"use strict";n.r(t);var i=n("54c8"),a=n("6786");for(var r in a)["default"].indexOf(r)<0&&function(e){n.d(t,e,(function(){return a[e]}))}(r);n("8a2e");var u,c=n("f0c5"),o=Object(c["a"])(a["default"],i["b"],i["c"],!1,null,"59e5e7e8",null,!1,i["a"],u);t["default"]=o.exports},"8a2e":function(e,t,n){"use strict";var i=n("0df6"),a=n.n(i);a.a},cb35:function(e,t,n){"use strict";(function(e){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var i=n("a3a9"),a={name:"livePlayerCom",data:function(){return{playerList:[]}},mounted:function(){this.getLiveList()},methods:{getLiveList:function(){var e=this;i.postV20("/wechatLive/api.Live/liveList",{},(function(t){e.playerList=t}),e,"back")},goUrl:function(e){i.parseUrl(e)},goAllLive:function(){e.navigateTo({url:"/pages/live_and_video/live/viewer/viewer_list"})}}};t.default=a}).call(this,n("543d")["default"])}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/village_com/newCom/livePlayerCom-create-component',
    {
        'pages/village_com/newCom/livePlayerCom-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("6ba1"))
        })
    },
    [['pages/village_com/newCom/livePlayerCom-create-component']]
]);

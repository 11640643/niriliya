(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-567220bd"],{"0c71":function(t,e,n){},eaa9:function(t,e,n){"use strict";n.r(e);var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",[n("div",{staticClass:"searchs"},[n("el-row",[n("el-col",[n("router-link",{attrs:{to:"/image/create/links?type=link"}},[n("el-button",{staticStyle:{"margin-left":"8px"},attrs:{type:"success"}},[t._v("创建底部链接")])],1)],1)],1)],1),n("el-table",{directives:[{name:"loading",rawName:"v-loading",value:t.loading,expression:"loading"}],staticClass:"list",attrs:{data:t.tableData}},[n("el-table-column",{attrs:{prop:"id",label:"编号",align:"center"}}),n("el-table-column",{attrs:{prop:"name",label:"名称",align:"center",width:"200"}}),n("el-table-column",{attrs:{prop:"sort",label:"排序",align:"center",width:"100"}}),n("el-table-column",{attrs:{prop:"url",label:"地址",align:"center",width:"100"}}),n("el-table-column",{attrs:{prop:"status_name",label:"状态",align:"center",width:"100"}}),n("el-table-column",{attrs:{prop:"uptime_date",label:"添加时间",align:"center"}}),n("el-table-column",{attrs:{prop:"address",align:"center",label:"操作"},scopedSlots:t._u([{key:"default",fn:function(e){return[n("el-button",{attrs:{type:"primary",size:"small"},on:{click:function(n){return t.eidt(e.row.id)}}},[t._v("编辑")]),n("el-button",{attrs:{type:"primary",size:"small"},on:{click:function(n){return t.remove(e.row.id)}}},[t._v("删除")])]}}])})],1),n("el-pagination",{staticClass:"pagination",attrs:{background:"",layout:"total, prev, pager, next",total:t.count,"current-page":t.page_curren,"hide-on-single-page":!1},on:{"current-change":t.currentChange}})],1)},r=[],i=(n("8e6e"),n("ac6a"),n("456d"),n("bd86")),c=n("1c1e");function l(t,e){var n=Object.keys(t);if(Object.getOwnPropertySymbols){var a=Object.getOwnPropertySymbols(t);e&&(a=a.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),n.push.apply(n,a)}return n}function o(t){for(var e=1;e<arguments.length;e++){var n=null!=arguments[e]?arguments[e]:{};e%2?l(n,!0).forEach((function(e){Object(i["a"])(t,e,n[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(n)):l(n).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(n,e))}))}return t}var s={data:function(){return{loading:!1,searchs:{name:"",status:"",type:""},tableData:[],count:0,page_curren:1,config:{type:null,status:null}}},mounted:function(){this.FetchList()},methods:{remove:function(t){var e=this;this.loading=!0,Object(c["a"])("/sys/image/remove",{id:t}).then((function(){e.loading=!1,e.FetchList()})).catch((function(){return e.loading=!1}))},eidt:function(t){this.$router.push("/image/update/"+t)},FetchList:function(){var t=this;this.loading=!0,Object(c["a"])("/sys/image/getlist",o({page_curren:this.page_curren},this.searchs,{type:"links"})).then((function(e){t.loading=!1,t.count=e.data.count,t.page_curren=Math.floor(e.data.page_curren),t.tableData=e.data.list,t.config=e.data.config})).catch((function(){return t.loading=!1}))},currentChange:function(t){this.page_curren=t,this.FetchList()},onSubmit:function(){this.page_curren=1,this.FetchList()}}},u=s,p=(n("ef26"),n("2877")),g=Object(p["a"])(u,a,r,!1,null,"7ff331b8",null);e["default"]=g.exports},ef26:function(t,e,n){"use strict";var a=n("0c71"),r=n.n(a);r.a}}]);
//# sourceMappingURL=chunk-567220bd.82c5928e.js.map
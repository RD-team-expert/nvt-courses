import{d as f,c as g,l as y,o as a,u as s,a0 as w,w as _,a as l,f as h,n as m,t as i,e as t,O as b,b as c,F as v,h as k,E as P,P as $,j as B}from"./app-BpR0BuvI.js";import{r as C,a as u,_ as N}from"./AppLogoIcon.vue_vue_type_script_setup_true_lang-BKBtuThP.js";const S=f({__name:"Separator",props:{orientation:{},decorative:{type:Boolean},asChild:{type:Boolean},as:{},class:{},label:{}},setup(p){const e=p,o=g(()=>{const{class:d,...r}=e;return r});return(d,r)=>(a(),y(s(C),w(o.value,{class:s(u)("relative shrink-0 bg-border",e.orientation==="vertical"?"h-full w-px":"h-px w-full",e.class)}),{default:_(()=>[e.label?(a(),l("span",{key:0,class:m(s(u)("absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center bg-background text-xs text-muted-foreground",e.orientation==="vertical"?"w-[1px] px-1 py-2":"h-[1px] px-2 py-1"))},i(e.label),3)):h("",!0)]),_:1},16,["class"]))}}),V={class:"mb-8 space-y-0.5"},j={class:"text-xl font-semibold tracking-tight"},z={key:0,class:"text-sm text-muted-foreground"},L=f({__name:"Heading",props:{title:{},description:{}},setup(p){return(e,o)=>(a(),l("div",V,[t("h2",j,i(e.title),1),e.description?(a(),l("p",z,i(e.description),1)):h("",!0)]))}}),E={class:"px-4 py-6"},F={class:"flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0"},D={class:"w-full max-w-xl lg:w-48"},H={class:"flex flex-col space-x-0 space-y-1"},I={class:"flex-1 md:max-w-2xl"},M={class:"max-w-xl space-y-12"},U=f({__name:"Layout",setup(p){var r;const e=[{title:"Profile",href:"/settings/profile"},{title:"Password",href:"/settings/password"}],o=b(),d=(r=o.props.ziggy)!=null&&r.location?new URL(o.props.ziggy.location).pathname:"";return(x,O)=>(a(),l("div",E,[c(L,{title:"Settings",description:"Manage your profile and account settings"}),t("div",F,[t("aside",D,[t("nav",H,[(a(),l(v,null,k(e,n=>c(s(N),{key:n.href,variant:"ghost",class:m(["w-full justify-start",{"bg-muted":s(d)===n.href}]),"as-child":""},{default:_(()=>[c(s($),{href:n.href},{default:_(()=>[B(i(n.title),1)]),_:2},1032,["href"])]),_:2},1032,["class"])),64))])]),c(s(S),{class:"my-6 md:hidden"}),t("div",I,[t("section",M,[P(x.$slots,"default")])])])]))}});export{U as _};

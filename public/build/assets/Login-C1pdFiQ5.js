import{d as k,c as y,l as u,o as n,u as e,Y as h,w as o,b as a,M as v,C as B,a as C,f as c,e as r,m as V,t as $,p as L,j as l}from"./app-qcpNlNnA.js";import{_ as p,a as _,b as w}from"./Label.vue_vue_type_script_setup_true_lang-BtMq2NgL.js";import{_ as x}from"./TextLink.vue_vue_type_script_setup_true_lang-CrUaimHo.js";import{c as S,S as j,W as P,a as q,j as N,_ as E}from"./AppLogoIcon.vue_vue_type_script_setup_true_lang-CzZNeL1v.js";import{L as M,_ as R}from"./AuthLayout.vue_vue_type_script_setup_true_lang-CmTllCEh.js";import"./index-7ZZPBW3g.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const U=S("CheckIcon",[["path",{d:"M20 6 9 17l-5-5",key:"1gmf2c"}]]),z=k({__name:"Checkbox",props:{defaultChecked:{type:Boolean},checked:{type:[Boolean,String]},disabled:{type:Boolean},required:{type:Boolean},name:{},value:{},id:{},asChild:{type:Boolean},as:{},class:{}},emits:["update:checked"],setup(f,{emit:t}){const m=f,d=t,s=y(()=>{const{class:g,...b}=m;return b}),i=j(s,d);return(g,b)=>(n(),u(e(P),h(e(i),{class:e(q)("peer size-5 shrink-0 rounded-sm border border-input ring-offset-background focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-accent-foreground",m.class)}),{default:o(()=>[a(e(N),{class:"flex h-full w-full items-center justify-center text-current"},{default:o(()=>[v(g.$slots,"default",{},()=>[a(e(U),{class:"size-3.5 stroke-[3]"})])]),_:3})]),_:3},16,["class"]))}}),D={key:0,class:"mb-4 text-center text-sm font-medium text-green-600"},F={class:"grid gap-6"},I={class:"grid gap-2"},W={class:"grid gap-2"},T={class:"flex items-center justify-between"},Y={class:"flex items-center justify-between",tabindex:3},A={class:"text-center text-sm text-muted-foreground"},X=k({__name:"Login",props:{status:{},canResetPassword:{type:Boolean}},setup(f){const t=B({email:"",password:"",remember:!1}),m=()=>{t.post(route("login"),{onFinish:()=>t.reset("password")})};return(d,s)=>(n(),u(R,{title:"Log in to your account",description:"Enter your email and password below to log in"},{default:o(()=>[a(e(V),{title:"Log in"}),d.status?(n(),C("div",D,$(d.status),1)):c("",!0),r("form",{onSubmit:L(m,["prevent"]),class:"flex flex-col gap-6"},[r("div",F,[r("div",I,[a(e(p),{for:"email"},{default:o(()=>s[3]||(s[3]=[l("Email address")])),_:1}),a(e(_),{id:"email",type:"email",required:"",autofocus:"",tabindex:1,autocomplete:"email",modelValue:e(t).email,"onUpdate:modelValue":s[0]||(s[0]=i=>e(t).email=i),placeholder:"email@example.com"},null,8,["modelValue"]),a(w,{message:e(t).errors.email},null,8,["message"])]),r("div",W,[r("div",T,[a(e(p),{for:"password"},{default:o(()=>s[4]||(s[4]=[l("Password")])),_:1}),d.canResetPassword?(n(),u(x,{key:0,href:d.route("password.request"),class:"text-sm",tabindex:5},{default:o(()=>s[5]||(s[5]=[l(" Forgot password? ")])),_:1},8,["href"])):c("",!0)]),a(e(_),{id:"password",type:"password",required:"",tabindex:2,autocomplete:"current-password",modelValue:e(t).password,"onUpdate:modelValue":s[1]||(s[1]=i=>e(t).password=i),placeholder:"Password"},null,8,["modelValue"]),a(w,{message:e(t).errors.password},null,8,["message"])]),r("div",Y,[a(e(p),{for:"remember",class:"flex items-center space-x-3"},{default:o(()=>[a(e(z),{id:"remember",checked:e(t).remember,"onUpdate:checked":s[2]||(s[2]=i=>e(t).remember=i),tabindex:4},null,8,["checked"]),s[6]||(s[6]=r("span",null,"Remember me",-1))]),_:1})]),a(e(E),{type:"submit",class:"mt-4 w-full",tabindex:4,disabled:e(t).processing},{default:o(()=>[e(t).processing?(n(),u(e(M),{key:0,class:"h-4 w-4 animate-spin"})):c("",!0),s[7]||(s[7]=l(" Log in "))]),_:1},8,["disabled"])]),r("div",A,[s[9]||(s[9]=l(" Don't have an account? ")),a(x,{href:d.route("register"),tabindex:5},{default:o(()=>s[8]||(s[8]=[l("Sign up")])),_:1},8,["href"])])],32)]),_:1}))}});export{X as default};

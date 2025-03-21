import{d as k,c as y,l as c,o as l,u as e,a0 as x,w as o,b as a,E as h,C as v,a as B,f as u,e as d,m as C,t as V,p as $,j as m}from"./app-BpR0BuvI.js";import{_}from"./InputError.vue_vue_type_script_setup_true_lang-Od2xTV4R.js";import{_ as L}from"./TextLink.vue_vue_type_script_setup_true_lang-DD3hOw9q.js";import{c as j,S as P,W as S,a as q,j as E,_ as N}from"./AppLogoIcon.vue_vue_type_script_setup_true_lang-BKBtuThP.js";import{_ as p,a as w}from"./Label.vue_vue_type_script_setup_true_lang-DmERjxi4.js";import{L as R,_ as U}from"./AuthLayout.vue_vue_type_script_setup_true_lang-Bo1JO-OO.js";import"./index-BEoY1Vcp.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const z=j("CheckIcon",[["path",{d:"M20 6 9 17l-5-5",key:"1gmf2c"}]]),F=k({__name:"Checkbox",props:{defaultChecked:{type:Boolean},checked:{type:[Boolean,String]},disabled:{type:Boolean},required:{type:Boolean},name:{},value:{},id:{},asChild:{type:Boolean},as:{},class:{}},emits:["update:checked"],setup(f,{emit:t}){const n=f,i=t,s=y(()=>{const{class:g,...b}=n;return b}),r=P(s,i);return(g,b)=>(l(),c(e(S),x(e(r),{class:e(q)("peer size-5 shrink-0 rounded-sm border border-input ring-offset-background focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-accent-foreground",n.class)}),{default:o(()=>[a(e(E),{class:"flex h-full w-full items-center justify-center text-current"},{default:o(()=>[h(g.$slots,"default",{},()=>[a(e(z),{class:"size-3.5 stroke-[3]"})])]),_:3})]),_:3},16,["class"]))}}),I={key:0,class:"mb-4 text-center text-sm font-medium text-green-600"},M={class:"grid gap-6"},W={class:"grid gap-2"},D={class:"grid gap-2"},T={class:"flex items-center justify-between"},A={class:"flex items-center justify-between",tabindex:3},Y=k({__name:"Login",props:{status:{},canResetPassword:{type:Boolean}},setup(f){const t=v({email:"",password:"",remember:!1}),n=()=>{t.post(route("login"),{onFinish:()=>t.reset("password")})};return(i,s)=>(l(),c(U,{title:"Log in to your account",description:"Enter your email and password below to log in"},{default:o(()=>[a(e(C),{title:"Log in"}),i.status?(l(),B("div",I,V(i.status),1)):u("",!0),d("form",{onSubmit:$(n,["prevent"]),class:"flex flex-col gap-6"},[d("div",M,[d("div",W,[a(e(p),{for:"email"},{default:o(()=>s[3]||(s[3]=[m("Email address")])),_:1}),a(e(w),{id:"email",type:"email",required:"",autofocus:"",tabindex:1,autocomplete:"email",modelValue:e(t).email,"onUpdate:modelValue":s[0]||(s[0]=r=>e(t).email=r),placeholder:"email@example.com"},null,8,["modelValue"]),a(_,{message:e(t).errors.email},null,8,["message"])]),d("div",D,[d("div",T,[a(e(p),{for:"password"},{default:o(()=>s[4]||(s[4]=[m("Password")])),_:1}),i.canResetPassword?(l(),c(L,{key:0,href:i.route("password.request"),class:"text-sm",tabindex:5},{default:o(()=>s[5]||(s[5]=[m(" Forgot password? ")])),_:1},8,["href"])):u("",!0)]),a(e(w),{id:"password",type:"password",required:"",tabindex:2,autocomplete:"current-password",modelValue:e(t).password,"onUpdate:modelValue":s[1]||(s[1]=r=>e(t).password=r),placeholder:"Password"},null,8,["modelValue"]),a(_,{message:e(t).errors.password},null,8,["message"])]),d("div",A,[a(e(p),{for:"remember",class:"flex items-center space-x-3"},{default:o(()=>[a(e(F),{id:"remember",checked:e(t).remember,"onUpdate:checked":s[2]||(s[2]=r=>e(t).remember=r),tabindex:4},null,8,["checked"]),s[6]||(s[6]=d("span",null,"Remember me",-1))]),_:1})]),a(e(N),{type:"submit",class:"mt-4 w-full",tabindex:4,disabled:e(t).processing},{default:o(()=>[e(t).processing?(l(),c(e(R),{key:0,class:"h-4 w-4 animate-spin"})):u("",!0),s[7]||(s[7]=m(" Log in "))]),_:1},8,["disabled"])])],32)]),_:1}))}});export{Y as default};

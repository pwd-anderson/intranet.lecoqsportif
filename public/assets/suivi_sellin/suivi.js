(function(){"use strict";
/* Intégration intranet : tout le code est dans une fonction (pas de variables globales, qui écraseraient « $ » de jQuery et celles du thème). Les classes d'état du template (is-home, solo…) vivent sur la racine de l'application et non sur le body de l'intranet. */
const ROOT=document.getElementById('ssg-root');
const LOGO64="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";const WORD64="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";
const $=s=>document.querySelector(s);
const esc=s=>String(s).replace(/[&<>"]/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
const nf0=new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0});
const fE=v=>nf0.format(Math.round(v))+' €';
const fN=v=>nf0.format(Math.round(v));
const fM=v=>{const a=Math.abs(v);if(a>=1e6)return(v/1e6).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+' M€';if(a>=1e3)return nf0.format(v/1e3)+' k€';return fE(v)};
const fS=v=>(v>0?'+':v<0?'−':'')+nf0.format(Math.abs(Math.round(v)));
const sLab=s=>{if(!s)return'—';const p=s.split('-');return p[2]+p[0].slice(2)};

const DIM={fam:'Famille',bm1:'Business model',cg:'Groupe client',n1:'Manager N+1',sales:'Commercial',grp:'Groupement',cust:'Client',age:'Âge',genus:'Genre',line:'Ligne produit',item:'Référence',perim:'Périmètre BOH',sot:'Sales order type'};
const FDIMS=['sot','bm1','cg','n1','sales','grp','fam','age','genus','line','perim'];
const LEVEL_OPTS=['sot','fam','bm1','cg','n1','sales','grp','cust','age','genus','line','item','perim'];
const PRESETS=[
  ['Global',['fam','bm1','cg']],
  ['Par commercial',['n1','sales','cust']],
  ['Par groupement',['grp','cust']],
  ['Détail produit',['fam','age','genus','line']],
  ['Par référence',['line','item']],
  ['Par client',['cust','fam']]
];
let D,C,N,R,CENT=-1;
function setDefaults(){const pi=D.dicts.perim.indexOf('Périmètre BOH');if(pi>=0)F.perim.add(pi);const ps=D.dicts.sot.indexOf('PS');if(ps>=0)F.sot.add(ps)}
function typeLab(){const s=[...F.sot];if(!s.length)return'Tous types';if(s.length<=3)return s.map(i=>D.dicts.sot[i]).join('+');return s.length+' types'}
const S={n:0,p:0,tab:'accueil',levels:PRESETS[0][1].slice(),lostLevels:['bm1','cg','cust'],lostMode:'lost',evoDim:'bm1',evoMetric:'amt',sort:{k:'aN',dir:-1}};
const F={};const EXP={};

function cLabel(dim,i){
  if(dim==='cust'&&i===CENT)return 'Centrale Intersport <span class="meta">n°\u00a04437</span><button type="button" class="fiche" data-c="'+i+'">Fiche</button>';
  if(dim==='cust'){const g=D.custGrp[i];const nn=(NOTES.get(D.dicts.cust[i])||[]).length;const cg=D.dicts.cg[D.custCgI[i]];
    const grp=g?'<button type="button" class="gchip" data-g="'+D.custGrpI[i]+'" title="Ouvrir la fiche du groupement">'+esc(g)+'</button>':'';
    return '<span class="cpair"><b class="cno">'+esc(D.dicts.cust[i])+'</b><span class="ccity">'+esc(D.custCity[i]||'ville non renseignée')+'</span><span class="cgrp">'+grp+'</span><span class="cact">'+(nn?'<span class="nchip" title="'+nn+' note(s) de suivi">'+nn+' note'+(nn>1?'s':'')+'</span>':'')+'<button type="button" class="fiche" data-c="'+i+'" aria-label="Fiche du client '+esc(D.dicts.cust[i])+'">Fiche</button></span></span>'}
  if(dim==='item'){return '<span class="refpair"><b class="refc">'+esc(D.dicts.item[i])+'</b> <span class="refd">'+esc(D.itemDesc[i]||'—')+'</span></span>'}
  if(dim==='grp'&&D.dicts.grp[i]!=='(non renseigné)')return esc(D.dicts.grp[i])+'<button type="button" class="fiche" data-g="'+i+'">Fiche</button>';
  return esc(D.dicts[dim][i]);
}
function cText(dim,i){
  if(dim==='cust')return 'Client n° '+D.dicts.cust[i]+(D.custCity[i]?' · '+D.custCity[i]:'')+(D.custGrp[i]?' · '+D.custGrp[i]:'');
  if(dim==='item')return D.dicts.item[i]+' '+(D.itemDesc[i]||'');
  return D.dicts[dim][i];
}

async function init(){
  D=JSON.parse($('#data').textContent);
  if(D.asof)ASOF=D.asof;
  const bin=Uint8Array.from(atob(D.bin),c=>c.charCodeAt(0));D.bin=null;
  const ab=await new Response(new Blob([bin]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer();
  N=D.n;C={amt:new Float32Array(ab,0,N),qty:new Int32Array(ab,4*N,N)};
  let off=8*N;D.u16.forEach(k=>{C[k]=new Uint16Array(ab,off,N);off+=2*N});D.u8.forEach(k=>{C[k]=new Uint8Array(ab,off,N);off+=N});
  FDIMS.forEach(d=>F[d]=new Set());
  setDefaults();
  CENT=D.dicts.cust.indexOf('4437');
  const ss=D.dicts.season;
  S.n=Math.max(0,ss.indexOf('2027-01-SS'));S.p=Math.max(0,ss.indexOf('2026-01-SS'));
  for(const id of ['seasonN','seasonP']){const el=document.getElementById(id);el.innerHTML=ss.map((s,i)=>`<option value="${i}">${sLab(s)}  ·  ${s}</option>`).join('');}
  $('#seasonP').insertAdjacentHTML('afterbegin','<option value="-1">Aucune · saison seule</option>');
  $('#seasonN').value=S.n;$('#seasonP').value=S.p;
  $('#seasonN').onchange=e=>{S.n=+e.target.value;const s=ss[S.n];const prev=(+s.slice(0,4)-1)+s.slice(4);const j=ss.indexOf(prev);if(j>=0&&S.p>=0){S.p=j;$('#seasonP').value=j}refresh()};
  $('#seasonP').onchange=e=>{S.p=+e.target.value;ROOT.classList.toggle('solo',S.p<0);refresh()};
  buildFilters();
  if(D.conf){document.title='Portefeuille '+D.conf.sales;$('.mast h1').textContent='Portefeuille';$('.mast .sub').textContent=D.conf.sales+' · page confidentielle';['sales','n1'].forEach(d=>{const b=document.getElementById('f-'+d);if(b)b.parentElement.hidden=true})}
  if(CENT<0){const x=$('#exclC');if(x)x.closest('label').hidden=true}
  await initFact();await initRea();initBacklog();await initRob();await initSellout();initMine();initNotes();watchRefs();
  $('#xlsBtn').onclick=exportExcel;
  $('#expBtn').onclick=openExport;
  $('#tabs').onclick=e=>{const b=e.target.closest('.tab');if(!b)return;S.tab=b.dataset.t;document.querySelectorAll('.tab').forEach(t=>t.setAttribute('aria-selected',t===b));renderPanel();const pn=$('#panel');if(pn.getBoundingClientRect().top<0)pn.scrollIntoView({block:'start'})};
  refresh();
}

/* ---------- filters ---------- */
function buildFilters(){
  const w=$('#filters');w.innerHTML='';
  const ico=p=>`<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${p}</svg>`;
  const IC={sot:ico('<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>'),perim:ico('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/>'),
    bm1:ico('<path d="M3 9l1.5-5h15L21 9M3 9h18v11H3zM9 20v-6h6v6"/>'),cg:ico('<path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/>'),grp:ico('<path d="M3 21V10l4-3 4 3v11M13 21V6l4-3 4 3v15M3 21h18"/>'),
    n1:ico('<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 4l1.2 2.4 2.6.4-1.9 1.8.5 2.6-2.4-1.2"/>'),sales:ico('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'),
    fam:ico('<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>'),line:ico('<path d="M12 3 2 8l10 5 10-5z"/><path d="m2 13 10 5 10-5"/>'),
    age:ico('<circle cx="12" cy="6" r="3"/><path d="M9 21v-6l-2-4h10l-2 4v6"/>'),genus:ico('<circle cx="9" cy="14" r="5"/><path d="M13 10l7-7M15 3h5v5"/>')};
  const LAB={sot:'Type de commande',perim:'Périmètre',bm1:'Business model',cg:'Groupe client',grp:'Groupement',n1:'Manager',sales:'Commercial',fam:'Famille',line:'Ligne produit',age:'Âge',genus:'Genre'};
  const GROUPS=[['cmd','Commandes',['sot','perim']],['cli','Clients',['bm1','cg','grp','n1','sales']],['prd','Produits',['fam','line','age','genus']]];
  const boxes={};GROUPS.forEach(([k,l,ds])=>{const g=document.createElement('div');g.className='fgrp g-'+k;g.innerHTML=`<div class="fgrp-h">${l}</div><div class="fgrp-b"></div>`;w.appendChild(g);ds.forEach(d=>boxes[d]=g.querySelector('.fgrp-b'))});
  for(const d of FDIMS){
    const el=document.createElement('div');el.className='ms';
    el.innerHTML=`<button type="button" class="ms-btn" id="f-${d}" aria-expanded="false"><span class="ms-ic">${IC[d]||''}</span><span class="ms-tx"><span class="ms-lab">${LAB[d]||DIM[d]}</span><span class="ms-val"></span></span></button>
      <div class="ms-pop" hidden><div class="ms-head"><b>${LAB[d]||DIM[d]}</b><span class="ms-cnt"></span></div><input type="search" id="fs-${d}" placeholder="Rechercher ${(LAB[d]||DIM[d]).toLowerCase()}…" autocomplete="off"><div class="ms-acts"><button type="button" data-a="vis">Cocher les résultats</button><button type="button" data-a="all">Tout effacer</button></div><div class="ms-list" role="listbox" aria-multiselectable="true"></div></div>`;
    (boxes[d]||w).appendChild(el);
    const btn=el.querySelector('.ms-btn'),pop=el.querySelector('.ms-pop'),list=el.querySelector('.ms-list'),inp=el.querySelector('input');
    let facet=null;
    const calcFacet=()=>{const others=FDIMS.filter(x=>x!==d&&F[x].size).map(x=>{const m=new Uint8Array(D.dicts[x].length);F[x].forEach(i=>m[i]=1);return[C[x],m]});
      const v=new Float64Array(D.dicts[d].length),col=C[d],se=C.season,ex=S.exclC?CENT:-2;
      outer:for(let i=0;i<N;i++){if(se[i]!==S.n||C.cust[i]===ex)continue;for(const[cc,m]of others)if(!m[cc[i]])continue outer;v[col[i]]+=C.amt[i]}return v};
    const visible=()=>{const q=inp.value.trim().toLowerCase();return D.dicts[d].map((v,i)=>i).filter(i=>!q||D.dicts[d][i].toLowerCase().includes(q))};
    const draw=()=>{const ids=visible();const row=i=>`<label class="${F[d].has(i)?'sel':''}${facet[i]>0?'':' zero'}" title="${esc(D.dicts[d][i])}"><input type="checkbox" value="${i}" ${F[d].has(i)?'checked':''}><span class="nm">${esc(D.dicts[d][i])}</span><span class="vv">${facet[i]?fM(facet[i]):'—'}</span></label>`;
      const sel=ids.filter(i=>F[d].has(i)),rest=ids.filter(i=>!F[d].has(i)).sort((a,b)=>facet[b]-facet[a]||D.dicts[d][a].localeCompare(D.dicts[d][b]));
      list.innerHTML=(sel.length?`<div class="ms-sep">Sélection</div>${sel.map(row).join('')}<div class="ms-sep">Autres</div>`:'')+(rest.map(row).join('')||'<div class="ms-sep">Aucun résultat</div>');
      el.querySelector('.ms-cnt').textContent=(F[d].size?F[d].size+' sélectionné'+(F[d].size>1?'s':''):'Tout')+' · CA '+sLab(D.dicts.season[S.n]);};
    btn.onclick=()=>{const open=pop.hidden;closePops();if(open){facet=calcFacet();pop.hidden=false;btn.setAttribute('aria-expanded','true');inp.value='';draw();inp.focus()}};
    inp.onkeydown=e=>{if(e.key==='Enter'){const ids=visible();if(ids.length===1||inp.value.trim()){const i=ids.sort((a,b)=>facet[b]-facet[a])[0];if(i!=null){F[d].add(i);updBtn(d);draw();refreshSoon()}}}};
    inp.oninput=draw;
    list.onchange=e=>{const i=+e.target.value;e.target.checked?F[d].add(i):F[d].delete(i);updBtn(d);draw();refreshSoon()};
    el.querySelector('[data-a=vis]').onclick=()=>{visible().forEach(i=>F[d].add(i));updBtn(d);draw();refreshSoon()};
    el.querySelector('[data-a=all]').onclick=()=>{F[d].clear();draw();updBtn(d);refreshSoon()};
    updBtn(d);
  }
  const r=document.createElement('button');r.type='button';r.className='linkbtn reset';r.id='resetF';r.textContent='Réinitialiser les filtres';
  r.onclick=()=>{FDIMS.forEach(d=>{F[d].clear()});setDefaults();S.exclC=false;$('#exclC').checked=false;FDIMS.forEach(updBtn);closePops();refresh()};
  const cx=document.createElement('label');cx.className='tog';cx.innerHTML='<input type="checkbox" id="exclC"> Exclure la centrale Intersport <span class="meta">(n°\u00a04437)</span>';
  const ft=document.createElement('div');ft.className='ffoot';ft.appendChild(cx);ft.appendChild(r);w.appendChild(ft);cx.querySelector('input').onchange=e=>{S.exclC=e.target.checked;refresh()};
  document.addEventListener('click',e=>{if(!e.target.closest('.ms'))closePops()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closePops()});
}
function closePops(){document.querySelectorAll('.ms-pop').forEach(p=>p.hidden=true);document.querySelectorAll('.ms-btn').forEach(b=>b.setAttribute('aria-expanded','false'))}
function updBtn(d){const b=document.getElementById('f-'+d);const s=F[d];b.classList.toggle('on',s.size>0);b.querySelector('.ms-val').textContent=s.size===0?'Tout':s.size===1?D.dicts[d][[...s][0]]:s.size+' sélectionnés'}
let rT;function refreshSoon(){clearTimeout(rT);rT=setTimeout(refresh,180)}

/* ---------- compute ---------- */
function compute(){
  const masks=FDIMS.filter(d=>F[d].size).map(d=>{const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return[C[d],m]});
  const se=C.season,all=[],iN=[],iP=[];
  const cu=C.cust,ex=S.exclC?CENT:-2;
  outer:for(let i=0;i<N;i++){if(cu[i]===ex)continue;for(let k=0;k<masks.length;k++){if(!masks[k][1][masks[k][0][i]])continue outer}all.push(i);const s=se[i];if(s===S.n)iN.push(i);else if(s===S.p)iP.push(i)}
  const nc=D.dicts.cust.length,cN=new Float64Array(nc),cP=new Float64Array(nc);
  for(const i of iN)cN[C.cust[i]]+=C.amt[i];for(const i of iP)cP[C.cust[i]]+=C.amt[i];
  const st=new Uint8Array(nc);let k={tN:0,tP:0,qN:0,qP:0,dN:0,dP:0,comp:0,compN:0,compP:0,lost:0,lostP:0,nw:0,nwN:0};
  for(let c=0;c<nc;c++){const a=cN[c]>0,b=cP[c]>0;if(a)k.dN++;if(b)k.dP++;if(a&&b){st[c]=1;k.comp++;k.compN+=cN[c];k.compP+=cP[c]}else if(b){st[c]=2;k.lost++;k.lostP+=cP[c]}else if(a){st[c]=3;k.nw++;k.nwN+=cN[c]}}
  for(const i of iN){k.tN+=C.amt[i];k.qN+=C.qty[i]}for(const i of iP){k.tP+=C.amt[i];k.qP+=C.qty[i]}
  R={all,iN,iP,cN,cP,st,k};
}
function mk(dim,key){return{dim,key,aN:0,aP:0,qN:0,qP:0,dN:new Set(),dP:new Set(),ch:new Map()}}
function pivotComp(levels){
  /* comparable calculé à chaque niveau : un magasin compte dans une ligne seulement s'il a commandé (CA > 0) cette ligne sur les deux saisons */
  const root=mk(null,'tot');root.cm=new Map();const amt=C.amt,qty=C.qty,cu=C.cust,cols=levels.map(d=>C[d]);
  const add=(list,isN)=>{for(const i of list){const c=cu[i],a=amt[i],q=qty[i];let nd=root;
    for(let l=-1;l<levels.length;l++){if(l>=0){const key=cols[l][i];let ch=nd.ch.get(key);if(!ch){ch=mk(levels[l],key);ch.cm=new Map();nd.ch.set(key,ch)}nd=ch}
      let v=nd.cm.get(c);if(!v){v=[0,0,0,0];nd.cm.set(c,v)}if(isN){v[0]+=a;v[2]+=q}else{v[1]+=a;v[3]+=q}}}};
  add(R.iN,true);add(R.iP,false);
  (function fin(nd){nd.cm.forEach((v,c)=>{if(v[0]>0&&v[1]>0){nd.aN+=v[0];nd.aP+=v[1];nd.qN+=v[2];nd.qP+=v[3];nd.dN.add(c);nd.dP.add(c)}});delete nd.cm;
    nd.ch.forEach((ch,k)=>{fin(ch);if(!ch.dN.size)nd.ch.delete(k)})})(root);
  return root;
}
function pivot(levels,pred){
  if(pred==='comp')return pivotComp(levels);
  const root=mk(null,'tot');const amt=C.amt,qty=C.qty,cu=C.cust,cols=levels.map(d=>C[d]);
  const add=(list,isN)=>{for(const i of list){const c=cu[i];if(pred&&typeof pred==='function'&&!pred(c))continue;const a=amt[i],q=qty[i];let nd=root;
    for(let l=-1;l<levels.length;l++){if(l>=0){const key=cols[l][i];let ch=nd.ch.get(key);if(!ch){ch=mk(levels[l],key);nd.ch.set(key,ch)}nd=ch}
      if(isN){nd.aN+=a;nd.qN+=q;if(a>0)nd.dN.add(c)}else{nd.aP+=a;nd.qP+=q;if(a>0)nd.dP.add(c)}}}};
  add(R.iN,true);add(R.iP,false);return root;
}

/* ---------- table ---------- */
const evoV=(n,p)=>p?(n-p)/Math.abs(p):(n?Infinity:-Infinity);
const GET={label:n=>cText(n.dim,n.key).toLowerCase(),aN:n=>n.aN,aP:n=>n.aP,diff:n=>n.aN-n.aP,evo:n=>evoV(n.aN,n.aP),qN:n=>n.qN,qP:n=>n.qP,qevo:n=>evoV(n.qN,n.qP),dN:n=>n.dN.size,dP:n=>n.dP.size,ddiff:n=>n.dN.size-n.dP.size};
function chip(n,p){if(!n&&!p)return'<span class="dash">—</span>';if(!p)return'<span class="chip new">nouveau</span>';const r=(n-p)/Math.abs(p);if(!isFinite(r))return'<span class="dash">—</span>';const t=(r>=0?'▲ +':'▼ ')+(r*100).toLocaleString('fr-FR',{minimumFractionDigits:1,maximumFractionDigits:1})+' %';return`<span class="chip ${r>=0?'up':'down'}">${t}</span>`}
const sgn=v=>`<span class="${v>0?'pos':v<0?'neg':'dash'}">${fS(v)}</span>`;
function colDefs(mode){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const all={
    aN:{h:`CA ${n}`,c:'cn',f:x=>fE(x.aN)},aP:{h:`CA ${p}`,c:'cp',f:x=>fE(x.aP)},diff:{h:'Écart €',f:x=>sgn(x.aN-x.aP)},evo:{h:'Évo CA',f:x=>chip(x.aN,x.aP)},
    qN:{h:`Qté ${n}`,c:'cn',f:x=>fN(x.qN)},qP:{h:`Qté ${p}`,c:'cp',f:x=>fN(x.qP)},qevo:{h:'Évo qté',f:x=>chip(x.qN,x.qP)},
    dN:{h:`Portes ${n}`,c:'cn',f:x=>fN(x.dN.size)},dP:{h:`Portes ${p}`,c:'cp',f:x=>fN(x.dP.size)},ddiff:{h:'Écart portes',f:x=>sgn(x.dN.size-x.dP.size)}};
  const keys=mode==='compact'?['aN','aP','diff','evo','dN']:mode==='lost'?['aP','qP','dP']:mode==='new'?['aN','qN','dN']:['aN','aP','diff','evo','qN','qP','qevo','dN','dP','ddiff'];
  const ks=S.p<0?keys.filter(k=>['aN','qN','dN'].includes(k)):keys;
  return ks.map(k=>({k,...all[k]}));
}
const LIMIT=100000;
function drill(levels){const L=levels.slice();if(L.includes('item'))return L;if(!L.includes('cust'))L.push('cust');L.push('item');return L}
function renderPivot(host,id,levels,pred,mode){
  const base=levels;levels=drill(levels);const extra=levels.slice(base.length);
  const root=pivot(levels,pred);const exp=EXP[id]||(EXP[id]=new Set());const cols=colDefs(mode);
  const sk=cols.some(c=>c.k===S.sort.k)||S.sort.k==='label'?S.sort.k:cols[0].k;const g=GET[sk],dir=S.sort.dir;
  const sorter=(a,b)=>{const x=g(a),y=g(b);return(x<y?-1:x>y?1:0)*dir};
  const head=`<tr><th class="lab" data-k="label">${base.map(d=>DIM[d]).join(' › ')}${extra.length?`<span class="meta"> › ${extra.map(d=>d==='cust'?'Magasin':DIM[d]).join(' › ')}</span>`:''}${sk==='label'?`<span class="ar">${dir>0?'▲':'▼'}</span>`:''}</th>${cols.map(c=>`<th data-k="${c.k}" class="${c.c||''}">${c.h}${sk===c.k?`<span class="ar">${dir>0?'▲':'▼'}</span>`:''}<span class="bar"></span></th>`).join('')}</tr>`;
  const rows=[];
  (function walk(nd,depth,path){
    const kids=[...nd.ch.values()].sort(sorter);
    kids.slice(0,LIMIT).forEach(k=>{const p=path+'/'+k.key;const has=k.ch.size>0,open=exp.has(p);
      const lab=`<span style="padding-left:${depth*18}px;display:inline-flex;align-items:baseline;gap:0">${has?`<button type="button" class="tg" data-p="${p}" aria-expanded="${open}" aria-label="Détailler"><i>${open?'−':'+'}</i></button><span class="tgl" data-p="${p}" style="margin-left:6px">${cLabel(k.dim,k.key)}</span>`:`<span class="leaf"></span><span>${cLabel(k.dim,k.key)}</span>`}</span>`;
      rows.push(`<tr class="l${depth}"><td class="lab">${lab}</td>${cols.map(c=>`<td>${c.f(k)}</td>`).join('')}</tr>`);
      if(has&&open)walk(k,depth+1,p)});
    if(kids.length>LIMIT)rows.push(`<tr class="more"><td colspan="${cols.length+1}"><span style="padding-left:${depth*18+22}px">… ${fN(kids.length-LIMIT)} autres lignes, affinez avec les filtres</span></td></tr>`);
  })(root,0,'');
  if(!root.ch.size)rows.push(`<tr class="more"><td colspan="${cols.length+1}">Aucune commande pour cette sélection.</td></tr>`);
  rows.push(`<tr class="tot"><td class="lab">Total</td>${cols.map(c=>`<td>${c.f(root)}</td>`).join('')}</tr>`);
  host.innerHTML=`<div class="scroll"><table class="pv"><thead>${head}</thead><tbody>${rows.join('')}</tbody></table></div>`;
  host.onclick=e=>{const t=e.target.closest('[data-p]');if(t){const p=t.dataset.p;exp.has(p)?exp.delete(p):exp.add(p);renderPivot(host,id,levels,pred,mode);return}
    const th=e.target.closest('th[data-k]');if(th){const k=th.dataset.k;S.sort=S.sort.k===k?{k,dir:-S.sort.dir}:{k,dir:k==='label'?1:-1};renderPanel()}};
  return root;
}

/* ---------- KPIs ---------- */
function renderKPIs(){
  const k=R.k,n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const cls=(a,b)=>!b?'':a>=b?'up':'down';
  const bars=(a,b,fmt)=>{const m=Math.max(Math.abs(a),Math.abs(b),1);return`<div class="kb"><span>${n}</span><span class="tr"><i style="width:${Math.max(2,Math.abs(a)/m*100)}%;background:var(--n)"></i></span><span class="v">${fmt(a)}</span><span>${p}</span><span class="tr"><i style="width:${Math.max(2,Math.abs(b)/m*100)}%;background:var(--p)"></i></span><span class="v">${fmt(b)}</span></div>`};
  const big=(v)=>{const s=v.split(' ');return s.length>1?`${s[0]}<small>${s.slice(1).join(' ')}</small>`:v};
  const tile=(h,a,b,fmtBig,fmt,foot,chipH)=>`<div class="kt ${cls(a,b)}"><div class="kt-h"><h3>${h}</h3>${chipH!=null?chipH:chip(a,b).replace('class="chip','class="chip bigchip')}</div><div class="big">${big(fmtBig(a))}</div>${bars(a,b,fmt)}${foot?`<div class="foot">${foot}</div>`:''}</div>`;
  const pN=k.dN?k.tN/k.dN:0,pP=k.dP?k.tP/k.dP:0;
  if(S.p<0){const st=(h,v,foot)=>`<div class="kt"><div class="kt-h"><h3>${h}</h3></div><div class="big">${big(v)}</div><div class="foot">${foot}</div></div>`;
    $('#kpis').className='kgrid k4';$('#kpis').innerHTML=st(`CA ${typeLab()} ${n}`,fM(k.tN),'saison seule, sans comparaison')+st(`Quantités ${n}`,fN(k.qN),'pièces commandées')+st(`Portes ${n}`,fN(k.dN),'magasins avec une commande')+st('Commande moyenne',fM(pN),'CA divisé par les portes actives');return}
  const mv=`<div class="kt ${k.nw-k.lost>=0?'up':'down'}"><div class="kt-h"><h3>Solde de portes</h3><span class="chip bigchip ${k.nwN-k.lostP>=0?'up':'down'}">${k.nwN-k.lostP>=0?'+':'−'}${fM(Math.abs(k.nwN-k.lostP))}</span></div>
    <div class="big">${fS(k.nw-k.lost)}<span class="u" style="font-size:.45em;margin-left:4px">portes</span></div>
    <div class="kb"><span style="white-space:nowrap">${fN(k.lost)} perd.</span><span class="tr"><i style="width:${Math.max(2,k.lostP/Math.max(k.lostP,k.nwN,1)*100)}%;background:var(--bad)"></i></span><span class="v">−${fM(k.lostP)}</span><span style="white-space:nowrap">${fN(k.nw)} nouv.</span><span class="tr"><i style="width:${Math.max(2,k.nwN/Math.max(k.lostP,k.nwN,1)*100)}%;background:var(--good)"></i></span><span class="v">+${fM(k.nwN)}</span></div>
    <div class="foot">${fN(k.lost)} perdues − ${fN(k.nw)} nouvelles = ${fS(k.nw-k.lost)} portes nettes</div></div>`;
  $('#kpis').className='kgrid';
  $('#kpis').innerHTML=
    tile(`CA ${typeLab()}`,k.tN,k.tP,fM,fM,`Écart ${fS(k.tN-k.tP)} €`)+
    tile('Quantités',k.qN,k.qP,fN,fN,`Écart ${fS(k.qN-k.qP)} pièces`)+
    tile('Portes',k.dN,k.dP,fN,fN,`Écart ${fS(k.dN-k.dP)} portes`)+
    tile('Commande moyenne',pN,pP,fM,fM,'CA divisé par les portes actives')+
    tile('Comparable',k.compN,k.compP,fM,fM,`${fN(k.comp)} portes présentes sur ${n} et ${p}`)+
    mv;
}

/* ================= page d'accueil (cockpit commercial) ================= */
const HOME={user:null};
async function homeUserName(){
  try{
    let U=NUSER;if(!U){const c=window.claude;if(!c||!c.use)return;U=await c.use('user');if(!U)return;NUSER=U}
    let nm='';
    try{const v=await U.me();nm=v&&v.name||''}catch(e){}
    if(!nm&&U.name){try{nm=await U.name()||''}catch(e){}}
    if(!nm){try{const id=MYID||(U.id?await U.id():null);if(id){MYID=MYID||id;const ps=await U.profiles([id]);nm=ps&&ps[id]&&ps[id].name||''}}catch(e){}}
    nm=String(nm||'').trim();if(!nm)return;
    let f=nm.split(/[\s,]+/)[0];if(nm.includes(',')){/* « NOM, Prénom » */f=nm.split(',')[1].trim().split(/\s+/)[0]||f}
    if(f===f.toUpperCase()&&nm.split(/\s+/).length>1){const w=nm.split(/\s+/).find(x=>x!==x.toUpperCase());if(w)f=w}
    HOME.user=f.charAt(0).toUpperCase()+f.slice(1);if(S.tab==='accueil')renderPanel();
  }catch(e){}
}
setTimeout(()=>{homeUserName()},0);
function homeFirstName(){
  if(HOME.user)return HOME.user;
  const s=D.conf?D.conf.sales:(F.sales&&F.sales.size===1?D.dicts.sales[[...F.sales][0]]:null);
  if(!s||s==='(non renseigné)')return null;
  const w=s.split(/\s+/).filter(x=>x&&x!==x.toUpperCase());return w[0]||null;
}
function homeGo(t,pre,after){
  if(pre)pre();S.tab=t;ROOT.classList.remove('home-f');
  document.querySelectorAll('.tab').forEach(b=>b.setAttribute('aria-selected',b.dataset.t===t));
  renderPanel();window.scrollTo({top:0});if(after)setTimeout(after,60);
}
function homeFiltersLine(){
  const LABS={sot:'Type',perim:'Périmètre',bm1:'Business model',cg:'Groupe client',grp:'Groupement',n1:'Manager',sales:'Commercial',fam:'Famille',line:'Ligne',age:'Âge',genus:'Genre'};
  const parts=[];
  FDIMS.forEach(d=>{if(!F[d]||!F[d].size)return;const vals=[...F[d]].map(i=>d==='perim'?'BOH':(D.dicts[d]&&D.dicts[d][i])||'').filter(Boolean);
    parts.push(`<span class="hm-f"><i>${LABS[d]||d}</i> ${esc(vals.length>2?vals.slice(0,2).join(', ')+' +'+(vals.length-2):vals.join(', '))}</span>`)});
  if(S.exclC)parts.push('<span class="hm-f">Hors centrale Intersport</span>');
  return parts.join('')||'<span class="hm-f">Aucun filtre</span>';
}
function homePanel(P){
  const ss=D.dicts.season,n=sLab(ss[S.n]),solo=S.p<0,p=solo?null:sLab(ss[S.p]),k=R.k;
  const prenom=homeFirstName();
  const dLong=new Date(ASOF+'T12:00:00').toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'});
  const pf=D.conf?D.conf.sales:(F.sales&&F.sales.size===1?D.dicts.sales[[...F.sales][0]]:(F.sales&&F.sales.size>1?F.sales.size+' commerciaux':'Toute l’équipe'));
  const ev=(a,b)=>b?(a-b)/Math.abs(b):null;
  const evTxt=r=>r==null?'':`<span class="hm-ev ${r>=0?'up':'down'}">${r>=0?'▲ +':'▼ '}${(r*100).toLocaleString('fr-FR',{minimumFractionDigits:1,maximumFractionDigits:1})} %</span>`;
  const mini=(a,b,fmt)=>{if(b==null)return'';const m=Math.max(Math.abs(a),Math.abs(b),1);return`<div class="hm-bars"><div><span>${n}</span><b style="width:${Math.max(2,Math.abs(a)/m*100)}%;background:var(--n)"></b><em>${fmt(a)}</em></div><div><span>${p}</span><b style="width:${Math.max(2,Math.abs(b)/m*100)}%;background:var(--p)"></b><em>${fmt(b)}</em></div></div>`};
  const na=why=>`<div class="hm-na">Non disponible<br><span>${why}</span></div>`;
  /* alertes : même calcul que l'onglet Alertes */
  let A=null;if(!solo){try{A=alertData()}catch(e){A=null}}
  const crit=A?A.items.filter(x=>x.lvl==='crit'):[],warn=A?A.items.filter(x=>x.lvl==='warn'):[];
  const stake=a=>a.reduce((s,x)=>s+x.stake,0);
  const card=(id,title,big,foot,extra,cls)=>`<button type="button" class="hm-kpi ${cls||''}" data-go="${id}"><span class="hm-kh">${title}<span class="hm-arrow" aria-hidden="true">→</span></span><span class="hm-big">${big}</span>${extra||''}<span class="hm-foot">${foot}</span></button>`;
  const kCA=card('ca',`CA ${typeLab()} ${n}`,fM(k.tN),solo?'saison seule, sans comparaison':`${p} : ${fM(k.tP)} ${evTxt(ev(k.tN,k.tP))}`,solo?'':mini(k.tN,k.tP,fM),solo?'':(k.tN>=k.tP?'up':'down'));
  const kD=card('doors','Portes actives',fN(k.dN),solo?'magasins avec une commande':`${p} : ${fN(k.dP)} · ${fN(k.lost)} perdues, ${fN(k.nw)} nouvelles`,solo?'':mini(k.dN,k.dP,fN),solo?'':(k.dN>=k.dP?'up':'down'));
  const kC=solo?card('comp','CA à périmètre comparable','—','',na('nécessite une saison N-1 (mode « saison seule » actif)'),'off')
    :card('comp','CA à périmètre comparable',fM(k.compN),`${fN(k.comp)} portes présentes sur ${n} et ${p} ${evTxt(ev(k.compN,k.compP))}`,mini(k.compN,k.compP,fM),k.compN>=k.compP?'up':'down');
  const kA=solo||!A?card('al','Alertes prioritaires','—','',na(solo?'nécessite une saison N-1':'calcul indisponible'),'off')
    :card('al','Alertes prioritaires',fN(crit.length),`critiques · ${fM(stake(crit))} en jeu<br>+ ${fN(warn.length)} à surveiller (${fM(stake(warn))})`,'',crit.length?'alert':'up');
  /* priorités */
  let prio='';
  if(solo)prio=`<div class="hm-empty">Les priorités comparent ${n} à une saison N-1. Choisissez une saison N-1 en haut de page pour les afficher.</div>`;
  else if(!A)prio=`<div class="hm-empty">Alertes indisponibles.</div>`;
  else{
    const top=A.items.filter(x=>x.lvl!=='info').slice(0,5);
    if(!top.length)prio=`<div class="hm-empty">Aucune alerte prioritaire sur ce portefeuille. 👍</div>`;
    else prio=top.map((x,i)=>{const a=R.cN[x.c],b=R.cP[x.c];const c=x.c;
      const who=c===CENT?'<b>Centrale Intersport</b> 4437':`<b>${esc(D.dicts.cust[c])}</b> ${esc(D.custCity[c]||'')}`;
      const grp=c!==CENT&&D.custGrp[c]?`<button type="button" class="hm-grp gchip-l" data-g="${D.custGrpI[c]}" title="Ouvrir la fiche du groupement">${esc(D.custGrp[c])}</button>`:'';
      const motif=x.type==='perdue'?`<b>Aucune commande ${typeLab()} sur ${n}</b> · ${fM(b)} en ${p}`:`<b>Baisse de ${Math.abs(Math.round((a-b)/b*100))} %</b> · ${fM(a)} contre ${fM(b)} en ${p}`;
      const sub=[(!F.sales||F.sales.size!==1)&&!D.conf&&D.dicts.sales[x.sales]&&D.dicts.sales[x.sales]!=='(non renseigné)'?esc(D.dicts.sales[x.sales]):'',x.bl>0?`reste à facturer ${fM(x.bl)}`:''].filter(Boolean).join(' · ');
      return`<div class="hm-pr"><span class="hm-rank">${i+1}</span><div class="hm-who">${who} ${grp}<div class="hm-motif">${motif}</div>${sub?`<div class="hm-sub">${sub}</div>`:''}</div>
        <div class="hm-stake"><span>En jeu</span><b>${fM(x.stake)}</b></div><span class="hm-lvl ${x.lvl}">${x.lvl==='crit'?'Critique':'À surveiller'}</span>
        <button type="button" class="hm-btn" data-fiche="${c}">Voir la fiche</button></div>`}).join('');
  }
  const nAl=crit.length+warn.length;
  const quick=[['clients','Mes clients','Rechercher un magasin, hausses et baisses',`${fN(k.dN)} portes actives`,'<path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-7h6v7"/>'],
    ['cmd','Mes commandes','Facturé et reste à livrer',`${typeLab()} ${n}`,'<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>'],
    ['alertes','Mes alertes','Clients et lignes à surveiller',solo?'—':`${fN(nAl)} alertes`,'<path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17h.01"/>'],
    ['acc','Accompagnement','Décotes, retours, avoirs par client','avoirs SEI','<path d="M20 12v8H4v-8M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z"/>'],
    ['adv','Analyses avancées','Synthèse N vs N-1, sell-out, diffusion…','toutes les vues','<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>']];
  P.innerHTML=`<div class="hm">
  <section class="hm-hero"><div><div class="hm-hello">Bonjour${prenom?' '+esc(prenom):''}</div>
    <div class="hm-meta"><span><i>Saison analysée</i> <b class="hm-n">${n}</b>${solo?' <span class="hm-dim">saison seule</span>':` <span class="hm-dim">vs</span> <b class="hm-p">${p}</b>`}</span><span><i>Saison en cours</i> <b>${sLab(curSeasonName())}</b></span><span><i>Portefeuille</i> <b>${esc(pf)}</b></span><span><i>Données ERP au</i> <b>${dLong}</b></span></div></div>
    <div class="hm-filt"><div class="hm-fl">${homeFiltersLine()}</div><button type="button" class="hm-link" id="hmFilt">${ROOT.classList.contains('home-f')?'Masquer les filtres':'Modifier les filtres'}</button></div></section>
  <div class="hm-kpis">${kCA}${kD}${kC}${kA}</div>
  <section class="hm-card"><div class="hm-ch"><h2>Mes priorités</h2><span>${solo?'':`les ${Math.min(5,nAl)} alertes au plus gros montant en jeu`}</span>${!solo&&nAl>5?`<button type="button" class="hm-link" data-go="alertes">Voir les ${fN(nAl)} alertes →</button>`:''}</div>${prio}</section>
  <section><h2 class="hm-h2">Accès rapides</h2><div class="hm-quick">${quick.map(([id,t,d,c,ic])=>`<button type="button" class="hm-q" data-go="${id}"><svg viewBox="0 0 24 24" aria-hidden="true">${ic}</svg><span class="hm-qt">${t}</span><span class="hm-qd">${d}</span><span class="hm-qc">${c}</span></button>`).join('')}</div></section>
  <p class="meta hm-note">Chiffres calculés avec les mêmes règles et les mêmes filtres que les analyses (type de commande ${typeLab()}${F.perim&&F.perim.size?', périmètre BOH':''}). Montant « en jeu » et seuils d'alerte identiques à l'onglet Alertes.</p></div>`;
  P.querySelectorAll('[data-go]').forEach(b=>b.onclick=()=>{const g=b.dataset.go;
    if(g==='ca'||g==='adv')homeGo('synthese');
    else if(g==='doors')homeGo('portes');
    else if(g==='comp')homeGo('synthese',null,()=>{const t=document.getElementById('t2');if(t)t.closest('.card').scrollIntoView({behavior:'smooth',block:'start'})});
    else if(g==='al')homeGo('alertes',()=>{AL.lvl='crit';AL.slvl='crit';AL.typ='all'});
    else if(g==='alertes')homeGo('alertes');
    else if(g==='clients')homeGo('magasins');
    else if(g==='cmd')homeGo('fact',()=>{FAS.view='simple'})
    else if(g==='acc')homeGo('accomp')});
  P.querySelectorAll('[data-fiche]').forEach(b=>b.onclick=e=>{e.stopPropagation();openFiche(+b.dataset.fiche)});
  $('#hmFilt').onclick=()=>{ROOT.classList.toggle('home-f');renderPanel();if(ROOT.classList.contains('home-f'))$('#filters').scrollIntoView({behavior:'smooth',block:'start'})};
}

/* ---------- panels ---------- */
function levelPicker(arr,id){
  const sel=(v,i)=>`<select data-i="${i}" id="${id}-${i}" aria-label="Niveau ${i+1}"><option value="">—</option>${LEVEL_OPTS.map(o=>`<option value="${o}" ${o===v?'selected':''}>${DIM[o]}</option>`).join('')}</select>`;
  const parts=[0,1,2,3].map(i=>sel(arr[i]||'',i));
  return`<div class="levels" id="${id}"><span class="tl">Lignes</span>${parts.join('<span class="sep">›</span>')}<span class="meta">puis ${arr.includes('item')?'':arr.includes('cust')?'› Référence ':'› Magasin › Référence '}en cliquant sur +</span></div>`;
}
function bindLevels(id,arr,cb){document.getElementById(id).onchange=e=>{const i=+e.target.dataset.i;const v=e.target.value;const next=arr.slice(0,4);while(next.length<4)next.push('');next[i]=v;const clean=next.filter(Boolean).filter((x,j,a)=>a.indexOf(x)===j);arr.length=0;arr.push(...(clean.length?clean:['bm1']));cb()}}
function famSeg(){
  const fams=D.dicts.fam;const cur=F.fam.size===1?[...F.fam][0]:(F.fam.size?-2:-1);
  const opts=[[-1,'Toutes familles'],...fams.map((f,i)=>[i,f]).filter(x=>x[1]!=='(non renseigné)'&&x[1]!=='PLV')];
  return`<div class="seg" id="famSeg" role="group" aria-label="Famille">${opts.map(([i,l])=>`<button type="button" data-f="${i}" aria-pressed="${cur===i}">${esc(l)}</button>`).join('')}</div>`;
}
function bindFam(){$('#famSeg').onclick=e=>{const b=e.target.closest('button');if(!b)return;const i=+b.dataset.f;F.fam.clear();if(i>=0)F.fam.add(i);updBtn('fam');refresh()}}

function renderPanel(){
  if(SSG.enabled&&SSG.enabled.length&&!SSG.enabled.includes(S.tab)){$('#panel').innerHTML='<div class="card"><div class="card-b expl"><p><b>Cet onglet arrive bientôt.</b> Il sera branché sur les données après validation des onglets précédents.</p></div></div>';return}
  ROOT.classList.toggle('is-home',S.tab==='accueil');
  ROOT.classList.toggle('no-kpis',S.tab!=='synthese');
  if(S.tab==='accueil'){homePanel($('#panel'));return}
  const P=$('#panel');const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const SOLO_OK=['synthese','fact','reassort','saisons','infos','accomp'];
  if(S.p<0&&!SOLO_OK.includes(S.tab)){P.innerHTML=`<div class="card"><div class="card-b expl"><p><b>Cet onglet compare deux saisons.</b> Vous êtes en mode « saison seule » (${n}). Choisissez une saison N-1 en haut de page, ou revenez à la comparaison :</p><p><button type="button" class="linkbtn" id="soloOff">Comparer ${n} à la saison précédente →</button></p></div></div>`;
    $('#soloOff').onclick=()=>{const ss=D.dicts.season,s=ss[S.n],j=ss.indexOf((+s.slice(0,4)-1)+s.slice(4));S.p=j>=0?j:Math.max(0,S.n-2);$('#seasonP').value=S.p;ROOT.classList.remove('solo');refresh()};return}
  if(S.tab==='synthese'&&S.p<0){
    P.innerHTML=`<nav class="subtabs" id="presets" aria-label="Vues"><span class="vl">Vue</span>${PRESETS.map((x,i)=>`<button type="button" data-i="${i}" aria-pressed="${PRESETS[i][1].join()===S.levels.join()}">${x[0]}</button>`).join('')}</nav>
    <div class="toolbar">${famSeg()}${levelPicker(S.levels,'lv')}</div>
    <div class="card"><div class="card-h"><h2>${typeLab()} ${n} · saison seule</h2><span class="note">Cliquez sur + pour détailler, sur un en-tête pour trier</span></div><div id="t1"></div></div>`;
    bindFam();$('#presets').onclick=e=>{const b=e.target.closest('button');if(!b)return;S.levels=PRESETS[+b.dataset.i][1].slice();renderPanel()};
    bindLevels('lv',S.levels,renderPanel);renderPivot($('#t1'),'t1s:'+S.levels.join(),S.levels,null,'full');return}
  if(S.tab==='synthese'){
    const pid=PRESETS.findIndex(x=>x[1].join()===S.levels.join());
    P.innerHTML=`<nav class="subtabs" id="presets" aria-label="Vues"><span class="vl">Vue</span>${PRESETS.map((x,i)=>`<button type="button" data-i="${i}" aria-pressed="${i===pid}">${x[0]}</button>`).join('')}</nav>
    <div class="toolbar">${famSeg()}${levelPicker(S.levels,'lv')}</div>
    <div class="card"><div class="card-h"><h2>${typeLab()} ${n} vs ${typeLab()} ${p} · tous magasins</h2><span class="note">Cliquez sur + pour détailler, sur un en-tête pour trier</span></div><div id="t1"></div></div>
    <div class="card"><div class="card-h"><h2>Répartition Homme / Femme / Enfant</h2><span class="note">Apparel et Footwear · ${typeLab()} ${n} vs ${p}</span></div><div class="card-b" id="hfe"></div></div>
    <div class="card"><div class="card-h"><h2>Valo comparable</h2><span class="note">Portes identiques en ${n} et ${p} sur chaque ligne</span></div><div class="card-b expl">Sur chaque ligne, on ne garde que les magasins qui ont commandé cette ligne (CA ${typeLab()} &gt; 0) à la fois en ${n} et en ${p} : le nombre de portes est donc le même sur les deux saisons. Exemple : un magasin qui a pris du Footwear sur les deux saisons mais du Textile seulement en ${p} compte dans la ligne Footwear, pas dans la ligne Textile. C'est pourquoi le total (${fN(R.k.comp)} portes ayant commandé sur les deux saisons, toutes familles confondues) n'est pas la somme des lignes.</div><div id="t2"></div></div>
    <div class="card"><div class="card-h"><h2>Écart CA par ${DIM[S.levels[0]].toLowerCase()}</h2><span class="note">10 plus gros écarts, tous magasins</span></div><div class="card-b" id="c1"></div></div>
    <div class="grid2">
      <div class="card"><div class="card-h"><h2>D'où vient l'écart</h2><span class="note">De ${p} à ${n} : portes perdues, comparable, nouvelles portes</span></div><div class="card-b" id="c3"></div></div>
      <div class="card"><div class="card-h"><h2>Comparable par commercial</h2><span class="note">Évolution du CA sur les portes comparables</span></div><div class="card-b" id="c4"></div></div>
    </div>`;
    bindFam();
    $('#presets').onclick=e=>{const b=e.target.closest('button');if(!b)return;S.levels=PRESETS[+b.dataset.i][1].slice();renderPanel()};
    bindLevels('lv',S.levels,renderPanel);
    const LV=F.sot.size>1&&!S.levels.includes('sot')?['sot',...S.levels].slice(0,4):S.levels;
    const root=renderPivot($('#t1'),'t1:'+LV.join(),LV,null,'full');
    renderPivot($('#t2'),'t2:'+LV.join(),LV,'comp','full');
    hfeView($('#hfe'));diffChart($('#c1'),root);cascade($('#c3'));salesChart($('#c4'));
  }else if(S.tab==='portes'){
    const lost=S.lostMode==='lost';
    P.innerHTML=`<div class="toolbar">${famSeg()}<div class="seg" id="lm" role="group"><button type="button" data-m="lost" aria-pressed="${lost}">Portes perdues</button><button type="button" data-m="new" aria-pressed="${!lost}">Nouvelles portes</button></div>${levelPicker(S.lostLevels,'lv2')}</div>
    <div class="card"><div class="card-h"><h2>${lost?`Portes avec ${typeLab()} en ${p} mais pas en ${n}`:`Portes avec ${typeLab()} en ${n} mais pas en ${p}`}</h2><span class="note">${lost?`${fN(R.k.lost)} portes · ${fM(R.k.lostP)} de CA ${p} à reconquérir`:`${fN(R.k.nw)} portes · ${fM(R.k.nwN)} de CA ${n}`}</span></div><div id="t3"></div></div>`;
    bindFam();
    $('#lm').onclick=e=>{const b=e.target.closest('button');if(!b)return;S.lostMode=b.dataset.m;renderPanel()};
    bindLevels('lv2',S.lostLevels,renderPanel);
    const want=lost?2:3;
    renderPivot($('#t3'),'t3:'+S.lostMode+S.lostLevels.join(),S.lostLevels,c=>R.st[c]===want,lost?'lost':'new');
  }else if(S.tab==='clients'){
    gcPanel(P);
  }else if(S.tab==='moi'){
    moiPanel(P);
  }else if(S.tab==='fact'){
    factPanel(P);
  }else if(S.tab==='backlog'){
    blPanel(P);
  }else if(S.tab==='alertes'){
    alertPanel(P);
  }else if(S.tab==='sellout'){
    selloutPanel(P);
  }else if(S.tab==='reassort'){
    rsPanel(P);
  }else if(S.tab==='prix'){
    pvmPanel(P);
  }else if(S.tab==='accomp'){
    accPanel(P);
  }else if(S.tab==='diffusion'){
    difPanel(P);
  }else if(S.tab==='lignes'){
    heatPanel(P);
  }else if(S.tab==='magasins'){
    storePanel(P);
  }else if(S.tab==='saisons'){
    P.innerHTML=`<div class="toolbar">${famSeg()}<div class="seg" id="em" role="group"><button type="button" data-m="amt" aria-pressed="${S.evoMetric==='amt'}">Valeur (€)</button><button type="button" data-m="qty" aria-pressed="${S.evoMetric==='qty'}">Quantités</button></div>
      <label class="levels"><span class="tl">Lignes</span><select id="evoDim">${['sot','bm1','fam','cg','n1','sales','grp','age','genus'].map(o=>`<option value="${o}" ${o===S.evoDim?'selected':''}>${DIM[o]}</option>`).join('')}</select></label></div>
    <div class="card"><div class="card-h"><h2>${S.evoMetric==='amt'?'Valo':'Quantités'} ${typeLab()} par saison</h2><span class="legend"><span><span class="sw n"></span>Saison N (${n})</span><span><span class="sw p"></span>Saison N-1 (${p})</span></span></div><div class="card-b" id="c2"></div></div>
    <div class="card"><div class="card-h"><h2>Détail par ${DIM[S.evoDim].toLowerCase()}</h2><span class="note">Les saisons 2024-01-SS à 2027-02-FW présentes dans l'extraction</span></div><div id="t4"></div></div>`;
    bindFam();
    $('#em').onclick=e=>{const b=e.target.closest('button');if(!b)return;S.evoMetric=b.dataset.m;renderPanel()};
    $('#evoDim').onchange=e=>{S.evoDim=e.target.value;renderPanel()};
    seasonView();
  }else{
    P.innerHTML=aboutHtml();
  }
}

/* ---------- charts ---------- */
const tip=$('#tip');
document.addEventListener('mousemove',e=>{const t=e.target.closest&&e.target.closest('[data-tip]');if(!t){tip.hidden=true;return}tip.innerHTML=t.dataset.tip;tip.hidden=false;const x=Math.min(e.clientX+14,innerWidth-tip.offsetWidth-8);tip.style.left=x+'px';tip.style.top=(e.clientY+14)+'px'});
function diffChart(host,root){
  const items=[...root.ch.values()].map(nd=>({l:cText(nd.dim,nd.key),d:nd.aN-nd.aP,n:nd.aN,p:nd.aP})).sort((a,b)=>Math.abs(b.d)-Math.abs(a.d)).slice(0,10);
  if(!items.length){host.innerHTML='<p class="mutedv">Aucune donnée.</p>';return}
  const W=640,rh=30,top=8,H=top+items.length*rh+8,lw=190,x0=lw+64,x1=W-64,z=(x0+x1)/2,half=(x1-x0)/2;
  const mx=Math.max(...items.map(i=>Math.abs(i.d)))||1;
  let s=`<line x1="${z}" x2="${z}" y1="${top-4}" y2="${H-4}" class="grid"/>`;
  items.forEach((it,i)=>{const y=top+i*rh,w=Math.max(2,Math.abs(it.d)/mx*half),pos=it.d>=0,x=pos?z:z-w;
    const lab=it.l.length>28?it.l.slice(0,27)+'…':it.l;
    const tipT=`<b>${esc(it.l)}</b><br>N : ${fE(it.n)}<br>N-1 : ${fE(it.p)}<br>Écart : ${fS(it.d)} €`;
    s+=`<g data-tip="${esc(tipT)}"><rect x="0" y="${y}" width="${W}" height="${rh}" fill="transparent"/><text x="${lw-8}" y="${y+rh/2+4}" text-anchor="end" class="lb">${esc(lab)}</text>
      <rect x="${x}" y="${y+7}" width="${w}" height="16" rx="3" fill="${pos?'var(--good)':'var(--bad)'}"/>
      <text x="${pos?x+w+6:x-6}" y="${y+rh/2+4}" text-anchor="${pos?'start':'end'}" class="vl">${it.d>=0?'+':'−'}${fM(Math.abs(it.d))}</text></g>`});
  host.innerHTML=`<svg viewBox="0 0 ${W} ${H}" width="100%" style="max-width:700px;display:block" role="img" aria-label="Écart de CA par élément">${s}</svg>`;
}
function niceMax(v){if(v<=0)return 1;const e=Math.pow(10,Math.floor(Math.log10(v)));const f=v/e;return(f<=1?1:f<=2?2:f<=2.5?2.5:f<=5?5:10)*e}
function seasonView(){
  const ss=D.dicts.season,ns=ss.length,metric=S.evoMetric,dim=S.evoDim;
  const tot=new Float64Array(ns),byDim=new Map();const col=C[dim],arr=metric==='amt'?C.amt:C.qty;
  for(const i of R.all){const s=C.season[i];const v=arr[i];tot[s]+=v;const k=col[i];let a=byDim.get(k);if(!a){a=new Float64Array(ns);byDim.set(k,a)}a[s]+=v}
  const fmt=metric==='amt'?fM:fN;
  const W=680,H=270,ml=56,mr=10,mt=24,mb=34,pw=W-ml-mr,ph=H-mt-mb,mx=niceMax(Math.max(...tot));
  let s='';for(let t=0;t<=4;t++){const v=mx*t/4,y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${metric==='amt'?(v/1e6).toLocaleString('fr-FR',{maximumFractionDigits:1})+' M€':fN(v)}</text>`}
  const bw=pw/ns;
  /* empilement par Sales order type quand plusieurs types sont affichés */
  const nt=D.dicts.sot.length,bySot=Array.from({length:nt},()=>new Float64Array(ns));
  for(const i of R.all)bySot[C.sot[i]][C.season[i]]+=arr[i];
  let types=[...Array(nt).keys()].filter(k=>bySot[k].some(v=>v>0)).sort((a,b)=>bySot[b].reduce((x,y)=>x+y,0)-bySot[a].reduce((x,y)=>x+y,0));
  const stacked=types.length>1;
  const PAL=['var(--c1)','var(--c2)','var(--c3)','var(--c4)','var(--c5)','var(--c6)'];
  let series=[];
  if(stacked){const main=types.slice(0,5),rest=types.slice(5);series=main.map((k,j)=>({l:D.dicts.sot[k],c:PAL[j],v:bySot[k]}));
    if(rest.length){const v=new Float64Array(ns);rest.forEach(k=>bySot[k].forEach((x,s)=>v[s]+=x));series.push({l:'Autres',c:'var(--bar)',v})}}
  for(let i=0;i<ns;i++){const v=tot[i],h=ph*Math.max(0,v)/mx,x=ml+i*bw+bw*.18,w=bw*.64,y=mt+ph-h;
    const key=i===S.n?'n':i===S.p?'p':'';
    let marks='',tip=`<b>${sLab(ss[i])}</b> · ${ss[i]}<br>Total : ${metric==='amt'?fE(v):fN(v)+' pièces'}`;
    if(stacked){let base=mt+ph;series.forEach((se,j)=>{const sv=Math.max(0,se.v[i]);if(!sv)return;const sh=ph*sv/mx;const top=base-sh;
        marks+=`<rect x="${x}" y="${top+ (j?1:0)}" width="${w}" height="${Math.max(0.5,sh-(j?1:0))}" rx="${sh>6?2:0}" fill="${se.c}"/>`;base=top;
        tip+=`<br><span style="color:${se.c}">■</span> ${esc(se.l)} : ${metric==='amt'?fE(se.v[i]):fN(se.v[i])} (${Math.round(sv/Math.max(v,1)*100)} %)`});
      if(key)marks+=`<rect x="${x-3}" y="${y-3}" width="${w+6}" height="${h+3}" rx="5" fill="none" stroke="var(--${key})" stroke-width="2"/>`}
    else{const fill=key?`var(--${key})`:'var(--bar)';marks=`<path d="M${x},${mt+ph} V${y+Math.min(4,h)} q0,-4 4,-4 h${w-8} q4,0 4,4 V${mt+ph} Z" fill="${fill}"/>`}
    s+=`<g data-tip="${esc(tip)}"><rect x="${ml+i*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${marks}
    ${v?`<text x="${x+w/2}" y="${y-8}" text-anchor="middle" class="vl">${fmt(v)}</text>`:''}<text x="${x+w/2}" y="${H-12}" text-anchor="middle" class="ax" ${key?`style="fill:var(--${key});font-weight:700"`:''}>${sLab(ss[i])}</text></g>`}
  const legend=stacked?`<div class="legend" style="margin-top:8px">${series.map(se=>`<span><span class="sw" style="background:${se.c}"></span>${esc(se.l)}</span>`).join('')}<span class="meta">· encadré = saison N (bleu) et N-1 (violet)</span></div>`:'';
  $('#c2').innerHTML=`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:520px;max-width:820px;display:block" role="img" aria-label="Valeur par saison">${s}</svg></div>${legend}`;
  const rows=[...byDim.entries()].sort((a,b)=>b[1][S.n]-a[1][S.n]||b[1][S.p]-a[1][S.p]).slice(0,80);
  const cell=v=>v?(metric==='amt'?fE(v):fN(v)):'<span class="dash">—</span>';
  const hc=i=>i===S.n?'cn':i===S.p?'cp':'';
  $('#t4').innerHTML=`<div class="scroll"><table class="pv"><thead><tr><th class="lab">${DIM[dim]}</th>${ss.map((x,i)=>`<th class="${hc(i)}">${sLab(x)}<span class="bar"></span></th>`).join('')}<th>Évo N/N-1</th></tr></thead><tbody>
  ${rows.map(([k,a])=>`<tr><td class="lab">${cLabel(dim,k)}</td>${[...a].map(v=>`<td>${cell(v)}</td>`).join('')}<td>${chip(a[S.n],a[S.p])}</td></tr>`).join('')}
  <tr class="tot"><td class="lab">Total</td>${[...tot].map(v=>`<td>${cell(v)}</td>`).join('')}<td>${chip(tot[S.n],tot[S.p])}</td></tr></tbody></table></div>`;
}

/* ---------- grands clients : comparable par groupe ---------- */
const GC={dim:'cg',sort:{k:'cP',dir:-1},exp:new Set(),all:false};
function gcData(){
  const map=GC.dim==='cg'?D.custCgI:D.custGrpI,G=new Map();
  const get=k=>{let g=G.get(k);if(!g){g={key:k,cN:0,cP:0,comp:new Set(),lost:new Set(),lostP:0,nw:new Set(),nwN:0,tN:0,tP:0,st:new Map()};G.set(k,g)}return g};
  const sto=(g,c)=>{let s=g.st.get(c);if(!s){s={c,n:0,p:0,qn:0,qp:0};g.st.set(c,s)}return s};
  for(const i of R.iN){const c=C.cust[i],g=get(map[c]),a=C.amt[i],t=R.st[c];g.tN+=a;const s=sto(g,c);s.n+=a;s.qn+=C.qty[i];if(t===1){g.cN+=a;g.comp.add(c)}else if(t===3){g.nwN+=a;g.nw.add(c)}}
  for(const i of R.iP){const c=C.cust[i],g=get(map[c]),a=C.amt[i],t=R.st[c];g.tP+=a;const s=sto(g,c);s.p+=a;s.qp+=C.qty[i];if(t===1){g.cP+=a;g.comp.add(c)}else if(t===2){g.lostP+=a;g.lost.add(c)}}
  return[...G.values()];
}
const GCG={label:g=>cText(GC.dim,g.key).toLowerCase(),comp:g=>g.comp.size,cN:g=>g.cN,cP:g=>g.cP,diff:g=>g.cN-g.cP,evo:g=>evoV(g.cN,g.cP),bN:g=>g.comp.size?g.cN/g.comp.size:0,bP:g=>g.comp.size?g.cP/g.comp.size:0,lost:g=>g.lost.size,nw:g=>g.nw.size,tN:g=>g.tN,tP:g=>g.tP,tevo:g=>evoV(g.tN,g.tP)};
function renderGC(){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const groups=gcData();
  const tot={cN:0,cP:0,comp:0,lost:0,lostP:0,nw:0,nwN:0,tN:0,tP:0};
  groups.forEach(g=>{tot.cN+=g.cN;tot.cP+=g.cP;tot.lostP+=g.lostP;tot.nwN+=g.nwN;tot.tN+=g.tN;tot.tP+=g.tP});
  tot.comp=R.k.comp;tot.lost=R.k.lost;tot.nw=R.k.nw;
  /* chart */
  const top=groups.filter(g=>g.cP>0&&g.comp.size>=2).sort((a,b)=>b.cP-a.cP).slice(0,12);
  const W=680,rh=30,H=10+top.length*rh+6,lw=200,x0=lw+70,x1=W-80,z=(x0+x1)/2,half=(x1-x0)/2;
  const ev=top.map(g=>(g.cN-g.cP)/g.cP),mx=Math.max(0.05,...ev.map(Math.abs));
  let s=`<line x1="${z}" x2="${z}" y1="4" y2="${H-4}" class="grid"/>`;
  top.forEach((g,i)=>{const r=ev[i],y=10+i*rh,w=Math.max(2,Math.abs(r)/mx*half),pos=r>=0,x=pos?z:z-w;const l=cText(GC.dim,g.key);const lab=l.length>26?l.slice(0,25)+'…':l;
    const tt=`<b>${esc(l)}</b><br>${g.comp.size} portes comparables<br>${n} : ${fE(g.cN)}<br>${p} : ${fE(g.cP)}<br>Commande moyenne : ${fE(g.cN/g.comp.size)} vs ${fE(g.cP/g.comp.size)}`;
    s+=`<g data-tip="${esc(tt)}"><rect x="0" y="${y}" width="${W}" height="${rh}" fill="transparent"/><text x="${lw-8}" y="${y+rh/2+4}" text-anchor="end" class="lb">${esc(lab)}</text>
    <rect x="${x}" y="${y+7}" width="${w}" height="16" rx="3" fill="${pos?'var(--good)':'var(--bad)'}"/>
    <text x="${pos?x+w+6:x-6}" y="${y+rh/2+4}" text-anchor="${pos?'start':'end'}" class="vl">${(r>=0?'+':'−')+Math.abs(r*100).toLocaleString('fr-FR',{maximumFractionDigits:1})} %</text>
    <text x="${W-4}" y="${y+rh/2+4}" text-anchor="end" class="ax">${g.comp.size} p.</text></g>`});
  const chart=top.length?`<svg viewBox="0 0 ${W} ${H}" width="100%" style="max-width:760px;display:block" role="img" aria-label="Évolution comparable par grand client">${s}</svg>`:'<p class="mutedv">Pas assez de portes comparables pour cette sélection.</p>';
  /* table */
  const g=GCG[GC.sort.k]||GCG.cP,dir=GC.sort.dir;
  let rows=groups.filter(x=>x.comp.size||x.lost.size||x.nw.size).sort((a,b)=>{const x=g(a),y=g(b);return(x<y?-1:x>y?1:0)*dir});
  const hidden=!GC.all&&rows.length>20?rows.length-20:0;if(hidden)rows=rows.slice(0,20);
  const cols=[['comp','Portes comp.'],['cN',`CA comp. ${n}`,'cn'],['cP',`CA comp. ${p}`,'cp'],['diff','Écart €'],['evo','Évo comp.'],['bN',`Cde moy. ${n}`,'cn'],['bP',`Cde moy. ${p}`,'cp'],['lost','Portes perdues'],['nw','Nouvelles'],['tN',`CA total ${n}`,'cn'],['tP',`CA total ${p}`,'cp'],['tevo','Évo totale']];
  const ar=k=>GC.sort.k===k?`<span class="ar">${dir>0?'▲':'▼'}</span>`:'';
  const head=`<tr><th class="lab" data-k="label">${DIM[GC.dim]}${ar('label')}</th>${cols.map(([k,h,c])=>`<th data-k="${k}" class="${c||''}">${h}${ar(k)}<span class="bar"></span></th>`).join('')}</tr>`;
  const line=(x,lab,cls,attrs)=>`<tr class="${cls}" ${attrs||''}><td class="lab">${lab}</td><td>${fN(x.comp)}</td><td>${fE(x.cN)}</td><td>${fE(x.cP)}</td><td>${sgn(x.cN-x.cP)}</td><td>${chip(x.cN,x.cP)}</td>
    <td>${x.comp?fE(x.cN/x.comp):'<span class="dash">—</span>'}</td><td>${x.comp?fE(x.cP/x.comp):'<span class="dash">—</span>'}</td>
    <td>${x.lost?`${fN(x.lost)} <span class="meta">${fM(x.lostP)}</span>`:'<span class="dash">0</span>'}</td><td>${x.nw?`${fN(x.nw)} <span class="meta">${fM(x.nwN)}</span>`:'<span class="dash">0</span>'}</td>
    <td>${fE(x.tN)}</td><td>${fE(x.tP)}</td><td>${chip(x.tN,x.tP)}</td></tr>`;
  const body=[];
  for(const gr of rows){const open=GC.exp.has(gr.key);
    const lab=`<button type="button" class="tg" data-g="${gr.key}" aria-expanded="${open}"><i>${open?'−':'+'}</i><span>${cLabel(GC.dim,gr.key)}</span></button>`;
    body.push(line({comp:gr.comp.size,cN:gr.cN,cP:gr.cP,lost:gr.lost.size,lostP:gr.lostP,nw:gr.nw.size,nwN:gr.nwN,tN:gr.tN,tP:gr.tP},lab,'l0'));
    if(open){const ord={1:0,2:1,3:2};const st=[...gr.st.values()].filter(x=>R.st[x.c]).sort((a,b)=>ord[R.st[a.c]]-ord[R.st[b.c]]||(b.p+b.n)-(a.p+a.n));
      for(const x of st){const t=R.st[x.c];const tag=t===1?'<span class="chip up">comparable</span>':t===2?'<span class="chip down">perdue</span>':'<span class="chip new">nouvelle</span>';
        const lab=`<span style="padding-left:24px;display:inline-block">${cLabel('cust',x.c)} ${tag}</span>`;
        body.push(line({comp:t===1?1:0,cN:t===1?x.n:0,cP:t===1?x.p:0,lost:t===2?1:0,lostP:t===2?x.p:0,nw:t===3?1:0,nwN:t===3?x.n:0,tN:x.n,tP:x.p},lab,'l1'))}}}
  if(hidden)body.push(`<tr class="more"><td colspan="${cols.length+1}"><button type="button" class="linkbtn" id="gcAll">Afficher les ${fN(hidden)} autres ${GC.dim==='cg'?'groupes':'groupements'}</button></td></tr>`);
  body.push(line(tot,'Total','tot'));
  return{chart,table:`<div class="scroll"><table class="pv">${'<thead>'+head+'</thead>'}<tbody>${body.join('')}</tbody></table></div>`};
}
function gcPanel(P){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const {chart,table}=renderGC();
  P.innerHTML=`<div class="toolbar">${famSeg()}<div class="seg" id="gcd" role="group" aria-label="Regroupement"><button type="button" data-d="cg" aria-pressed="${GC.dim==='cg'}">Par groupe client</button><button type="button" data-d="grp" aria-pressed="${GC.dim==='grp'}">Par groupement</button></div></div>
  <div class="card"><div class="card-h"><h2>Vue centrale · évolution comparable</h2><span class="note">12 plus gros ${GC.dim==='cg'?'groupes':'groupements'} en CA comparable ${p} · portes avec ${typeLab()} &gt; 0 sur ${n} et ${p}</span></div><div class="card-b">${chart}</div></div>
  <div class="card"><div class="card-h"><h2>Comparable par ${GC.dim==='cg'?'groupe client':'groupement'}</h2><span class="note">Chaque magasin est rattaché à son groupe principal · cliquez sur + pour voir les magasins</span></div><div id="gct">${table}</div></div>`;
  bindFam();
  $('#gcd').onclick=e=>{const b=e.target.closest('button');if(!b)return;GC.dim=b.dataset.d;GC.exp.clear();GC.all=false;renderPanel()};
  $('#gct').onclick=e=>{const t=e.target.closest('.tg');if(t){const k=+t.dataset.g;GC.exp.has(k)?GC.exp.delete(k):GC.exp.add(k);gcPanel(P);return}
    if(e.target.id==='gcAll'){GC.all=true;gcPanel(P);return}
    const th=e.target.closest('th[data-k]');if(th){const k=th.dataset.k;GC.sort=GC.sort.k===k?{k,dir:-GC.sort.dir}:{k,dir:k==='label'?1:-1};gcPanel(P)}};
}

/* ---------- shared horizontal evolution bars ---------- */
function hbarEvo(items,opt){
  opt=opt||{};
  if(!items.length)return'<p class="mutedv">Pas assez de données pour cette sélection.</p>';
  const W=680,rh=30,H=10+items.length*rh+6,lw=200,x0=lw+70,x1=W-96,z=(x0+x1)/2,half=(x1-x0)/2;
  const ev=items.map(g=>(g.n-g.p)/g.p),mx=Math.max(0.05,...ev.map(Math.abs));
  let s=`<line x1="${z}" x2="${z}" y1="4" y2="${H-4}" class="grid"/>`;
  items.forEach((g,i)=>{const r=ev[i],y=10+i*rh,w=Math.max(2,Math.abs(r)/mx*half),pos=r>=0,x=pos?z:z-w;const lab=g.l.length>26?g.l.slice(0,25)+'…':g.l;
    const tt=`<b>${esc(g.l)}</b><br>N : ${fE(g.n)}<br>N-1 : ${fE(g.p)}<br>Écart : ${fS(g.n-g.p)} €${g.tip?'<br>'+g.tip:''}`;
    s+=`<g data-tip="${esc(tt)}"><rect x="0" y="${y}" width="${W}" height="${rh}" fill="transparent"/><text x="${lw-8}" y="${y+rh/2+4}" text-anchor="end" class="lb">${esc(lab)}</text>
    <rect x="${x}" y="${y+7}" width="${w}" height="16" rx="3" fill="${pos?'var(--good)':'var(--bad)'}"/>
    <text x="${pos?x+w+6:x-6}" y="${y+rh/2+4}" text-anchor="${pos?'start':'end'}" class="vl">${(r>=0?'+':'−')+Math.abs(r*100).toLocaleString('fr-FR',{maximumFractionDigits:1})} %</text>
    <text x="${W-4}" y="${y+rh/2+4}" text-anchor="end" class="ax">${g.right||''}</text></g>`});
  return`<svg viewBox="0 0 ${W} ${H}" width="100%" style="max-width:760px;display:block" role="img" aria-label="${esc(opt.aria||'Évolution')}">${s}</svg>`;
}

/* ---------- cascade (bridge N-1 -> N) ---------- */
function cascade(host){
  const k=R.k,n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const compD=k.compN-k.compP,other=k.tN-(k.tP-k.lostP+compD+k.nwN);
  const steps=[{l:`CA ${p}`,v:k.tP,t:'tot',c:'var(--p)'},{l:'Portes perdues',v:-k.lostP,sub:`${fN(k.lost)} portes`},{l:'Écart comparable',v:compD,sub:`${fN(k.comp)} portes`},{l:'Nouvelles portes',v:k.nwN,sub:`${fN(k.nw)} portes`}];
  if(Math.abs(other)>=1)steps.push({l:'Autres',v:other,sub:'avoirs, retours'});
  steps.push({l:`CA ${n}`,v:k.tN,t:'tot',c:'var(--n)'});
  const W=680,H=290,ml=10,mr=10,mt=30,mb=50,pw=W-ml-mr,ph=H-mt-mb;
  let run=0,lo=0,hi=0;const bars=steps.map(s=>{let a,b;if(s.t){a=0;b=s.v;run=s.v}else{a=run;b=run+s.v;run=b}lo=Math.min(lo,a,b);hi=Math.max(hi,a,b);return{...s,a,b}});
  const mx=niceMax(hi),y=v=>mt+ph-(v-Math.min(0,lo))/(mx-Math.min(0,lo))*ph;
  const bw=pw/bars.length;let s=`<line x1="${ml}" x2="${W-mr}" y1="${y(0)}" y2="${y(0)}" class="grid"/>`;
  bars.forEach((b,i)=>{const x=ml+i*bw+bw*.16,w=bw*.68,top=Math.min(y(b.a),y(b.b)),h=Math.max(2,Math.abs(y(b.a)-y(b.b)));
    const fill=b.c||(b.v>=0?'var(--good)':'var(--bad)');
    if(i<bars.length-1){const nx=ml+(i+1)*bw+bw*.16;s+=`<line x1="${x+w}" x2="${nx}" y1="${y(b.b)}" y2="${y(b.b)}" stroke="var(--line-2)" stroke-dasharray="3 3"/>`}
    const val=b.t?fM(b.v):(b.v>=0?'+':'−')+fM(Math.abs(b.v));
    s+=`<g data-tip="<b>${esc(b.l)}</b><br>${b.t?fE(b.v):fS(b.v)+' €'}${b.sub?'<br>'+b.sub:''}"><rect x="${ml+i*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>
      <rect x="${x}" y="${top}" width="${w}" height="${h}" rx="3" fill="${fill}"/>
      <text x="${x+w/2}" y="${top-7}" text-anchor="middle" class="vl">${val}</text>
      <text x="${x+w/2}" y="${H-30}" text-anchor="middle" class="lb">${esc(b.l)}</text>
      ${b.sub?`<text x="${x+w/2}" y="${H-14}" text-anchor="middle" class="ax">${esc(b.sub)}</text>`:''}</g>`});
  host.innerHTML=`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:560px;max-width:820px;display:block" role="img" aria-label="Décomposition de l'écart de CA">${s}</svg></div>`;
}

/* ---------- commercial comparable chart ---------- */
function salesChart(host){
  const ns=D.dicts.sales.length,aN=new Float64Array(ns),aP=new Float64Array(ns),doors=Array.from({length:ns},()=>new Set());
  for(const i of R.iN){const c=C.cust[i];if(R.st[c]!==1)continue;const s=C.sales[i];aN[s]+=C.amt[i];doors[s].add(c)}
  for(const i of R.iP){const c=C.cust[i];if(R.st[c]!==1)continue;const s=C.sales[i];aP[s]+=C.amt[i];doors[s].add(c)}
  const items=[];for(let s=0;s<ns;s++)if(aP[s]>0)items.push({l:D.dicts.sales[s],n:aN[s],p:aP[s],right:fN(doors[s].size)+' p.',tip:fN(doors[s].size)+' portes comparables'});
  items.sort((a,b)=>b.p-a.p);
  host.innerHTML=hbarEvo(items.slice(0,16),{aria:'Évolution comparable par commercial'});
}

/* ---------- heatmap lignes produit x saisons ---------- */
const HM={metric:'amt',scale:'row',sort:'n',all:false};
function fK(v){const a=Math.abs(v);if(a>=1e6)return(v/1e6).toLocaleString('fr-FR',{maximumFractionDigits:1})+'M';if(a>=1e3)return nf0.format(v/1e3)+'k';return nf0.format(v)}
function heatPanel(P){
  const ss=D.dicts.season,ns=ss.length,arr=HM.metric==='amt'?C.amt:C.qty,M=new Map();
  for(const i of R.all){const l=C.line[i];let a=M.get(l);if(!a){a=new Float64Array(ns);M.set(l,a)}a[C.season[i]]+=arr[i]}
  let rows=[...M.entries()].filter(([,a])=>a.some(v=>v>0));
  const ev=a=>a[S.p]>0?(a[S.n]-a[S.p])/a[S.p]:(a[S.n]>0?Infinity:-Infinity);
  if(HM.sort==='n')rows.sort((x,y)=>y[1][S.n]-x[1][S.n]||y[1][S.p]-x[1][S.p]);
  else if(HM.sort==='tot')rows.sort((x,y)=>y[1].reduce((s,v)=>s+v,0)-x[1].reduce((s,v)=>s+v,0));
  else if(HM.sort==='up')rows.sort((x,y)=>ev(y[1])-ev(x[1]));
  else rows.sort((x,y)=>ev(x[1])-ev(y[1]));
  const total=rows.length,hidden=!HM.all&&total>60?total-60:0;if(hidden)rows=rows.slice(0,60);
  const gmax=Math.max(1,...rows.flatMap(([,a])=>[...a]));
  const cell=(v,mx,i)=>{if(v<=0)return`<td class="hm0"${i===S.n?' data-n':''}>·</td>`;const r=Math.sqrt(v/mx),pct=Math.round(8+r*82);
    return`<td style="background:color-mix(in oklab,var(--n) ${pct}%,var(--surface));color:${pct>52?'#fff':'var(--ink)'}" data-tip="${esc(fE(v))}">${HM.metric==='amt'?fK(v):fN(v)}</td>`};
  const n=sLab(ss[S.n]),p=sLab(ss[S.p]);
  const body=rows.map(([l,a])=>{const mx=HM.scale==='row'?Math.max(...a):gmax;return`<tr><td class="lab">${esc(D.dicts.line[l])}</td>${[...a].map((v,i)=>cell(v,mx,i)).join('')}<td>${chip(a[S.n],a[S.p])}</td></tr>`}).join('');
  const seg=(id,cur,opts)=>`<div class="seg" id="${id}" role="group">${opts.map(([v,l])=>`<button type="button" data-v="${v}" aria-pressed="${cur===v}">${l}</button>`).join('')}</div>`;
  P.innerHTML=`<div class="toolbar">${famSeg()}${seg('hmM',HM.metric,[['amt','Valeur (€)'],['qty','Quantités']])}${seg('hmS',HM.scale,[['row','Échelle par ligne'],['all','Échelle commune']])}
    <label class="levels"><span class="tl">Tri</span><select id="hmSort"><option value="n" ${HM.sort==='n'?'selected':''}>CA ${n} décroissant</option><option value="tot" ${HM.sort==='tot'?'selected':''}>Total 8 saisons</option><option value="up" ${HM.sort==='up'?'selected':''}>Plus fortes hausses ${n}/${p}</option><option value="down" ${HM.sort==='down'?'selected':''}>Plus fortes baisses ${n}/${p}</option></select></label></div>
  <div class="card"><div class="card-h"><h2>Lignes produit par saison</h2><span class="note">${HM.scale==='row'?'Couleur relative à chaque ligne : on lit sa montée ou son extinction':'Même échelle pour toutes les lignes : on compare leur poids'} · ${fN(total)} lignes</span></div>
  <div class="card-b hm-legend"><span>faible</span><span class="hm-grad"></span><span>fort</span><span class="meta">· point = aucune commande</span></div>
  <div class="scroll"><table class="pv hm"><thead><tr><th class="lab">Ligne produit</th>${ss.map((x,i)=>`<th class="${i===S.n?'cn':i===S.p?'cp':''}">${sLab(x)}<span class="bar"></span></th>`).join('')}<th>Évo ${n}/${p}</th></tr></thead><tbody>${body}
  ${hidden?`<tr class="more"><td colspan="${ns+2}"><button type="button" class="linkbtn" id="hmAll">Afficher les ${fN(hidden)} autres lignes</button></td></tr>`:''}</tbody></table></div></div>`;
  bindFam();
  $('#hmM').onclick=e=>{const b=e.target.closest('button');if(b){HM.metric=b.dataset.v;heatPanel(P)}};
  $('#hmS').onclick=e=>{const b=e.target.closest('button');if(b){HM.scale=b.dataset.v;heatPanel(P)}};
  $('#hmSort').onchange=e=>{HM.sort=e.target.value;heatPanel(P)};
  const al=$('#hmAll');if(al)al.onclick=()=>{HM.all=true;heatPanel(P)};
}

/* ---------- magasins : recherche + top / flop ---------- */
const MG={q:''};
function storePanel(P){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const list=[];for(let c=0;c<R.cN.length;c++){if(R.cN[c]||R.cP[c])list.push(c)}
  const d=c=>R.cN[c]-R.cP[c];
  const up=list.filter(c=>d(c)>0).sort((a,b)=>d(b)-d(a)).slice(0,15),down=list.filter(c=>d(c)<0).sort((a,b)=>d(a)-d(b)).slice(0,15);
  const stc=c=>{const t=R.st[c];return t===1?'':t===2?' <span class="chip down">perdue</span>':t===3?' <span class="chip new">nouvelle</span>':''};
  const tbl=arr=>`<div class="scroll"><table class="pv"><thead><tr><th class="lab">Magasin</th><th class="lab">Commercial</th><th class="cn">CA ${n}<span class="bar"></span></th><th class="cp">CA ${p}<span class="bar"></span></th><th>Écart €</th><th>Évo</th></tr></thead><tbody>
    ${arr.length?arr.map(c=>`<tr><td class="lab">${cLabel('cust',c)}${stc(c)}</td><td class="lab mutedv" style="min-width:140px">${esc(D.dicts.sales[D.custSalesI[c]])}</td><td>${fE(R.cN[c])}</td><td>${fE(R.cP[c])}</td><td>${sgn(d(c))}</td><td>${chip(R.cN[c],R.cP[c])}</td></tr>`).join(''):'<tr class="more"><td colspan="6">Aucun magasin.</td></tr>'}</tbody></table></div>`;
  P.innerHTML=`<div class="toolbar">${famSeg()}<label class="search"><span class="tl">Fiche magasin</span><input type="search" id="mgq" placeholder="N° client, ville ou groupement…" value="${esc(MG.q)}" autocomplete="off"></label></div>
  <div class="card" id="mgres" hidden></div>
  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card"><div class="card-h"><h2>Plus fortes hausses</h2><span class="note">15 magasins · écart ${n} vs ${p}</span></div>${tbl(up)}</div>
    <div class="card"><div class="card-h"><h2>Plus fortes baisses</h2><span class="note">15 magasins · écart ${n} vs ${p}</span></div>${tbl(down)}</div>
  </div>`;
  bindFam();
  const inp=$('#mgq'),res=$('#mgres');
  const search=()=>{MG.q=inp.value;const q=inp.value.trim().toLowerCase();if(q.length<2){res.hidden=true;return}
    const hits=[];for(let c=0;c<D.dicts.cust.length&&hits.length<30;c++){const t=(D.dicts.cust[c]+' '+(D.custCity[c]||'')+' '+(D.custGrp[c]||'')).toLowerCase();if(t.includes(q))hits.push(c)}
    const gh=D.dicts.grp.map((v,i)=>[v,i]).filter(([v])=>v!=='(non renseigné)'&&v.toLowerCase().includes(q)).slice(0,8);
    res.hidden=false;res.innerHTML=`${gh.length?`<div class="card-b"><span class="tl">Groupements</span> ${gh.map(([v,i])=>`<button type="button" class="fiche" data-g="${i}" style="font-size:12.5px;padding:3px 10px">${esc(v)}</button>`).join(' ')}</div>`:''}<div class="card-h"><h2>Résultats</h2><span class="note">${hits.length>=30?'30 premiers résultats':fN(hits.length)+' magasin(s)'} · cliquez sur Fiche</span></div><div class="scroll"><table class="pv"><tbody>${hits.map(c=>`<tr><td class="lab">${cLabel('cust',c)}${stc(c)}</td><td class="lab mutedv">${esc(D.dicts.sales[D.custSalesI[c]])}</td><td>${fE(R.cN[c])}</td><td>${fE(R.cP[c])}</td><td>${chip(R.cN[c],R.cP[c])}</td></tr>`).join('')||'<tr class="more"><td>Aucun magasin trouvé.</td></tr>'}</tbody></table></div>`};
  inp.oninput=search;if(MG.q)search();
}

/* ---------- fiche magasin ---------- */
/* ---------- références manquantes (best-sellers du réseau) ---------- */
function missingRefsNet(cm,ownN,ownP){
  const m=new Map();for(let k=0;k<cm.length;k++)if(cm[k]){const cg=D.dicts.cg[D.custCgI[k]];m.set(cg,(m.get(cg)||0)+1)}
  const cg=([...m.entries()].sort((a,b)=>b[1]-a[1])[0]||[])[0];const per=cg&&D.net[cg]&&D.net[cg][S.n];
  if(!per)return{lab:'—',doors:0,app:[],ftw:[],list:[]};
  const own=new Set(),ownPc=new Set();ownN.forEach((v,k)=>{if(v>0)own.add(D.dicts.item[k])});ownP.forEach((v,k)=>{if(v>0)ownPc.add(D.dicts.item[k])});
  const rows=per.items.filter(r=>!own.has(r[0])).map(r=>({k:D.dicts.item.indexOf(r[0]),code:r[0],desc:r[1],lineName:r[2],famName:r[3],d:r[4],a:r[5],q:r[6],prev:ownPc.has(r[0])}));
  const app=rows.filter(r=>r.famName==='Textile').slice(0,5),ftw=rows.filter(r=>r.famName==='Footwear').slice(0,5);
  return{lab:'réseau '+cg.charAt(0)+cg.slice(1).toLowerCase(),doors:per.doors,app,ftw,list:app.concat(ftw),net:true};
}
function missingRefs(cm,ownN,ownP){
  if(D.net)return missingRefsNet(cm,ownN,ownP);
  // réseau = groupe client principal des magasins sélectionnés ; repli sur le business model
  const tally=(arr)=>{const m=new Map();for(let k=0;k<cm.length;k++)if(cm[k])m.set(arr[k],(m.get(arr[k])||0)+1);return[...m.entries()].sort((a,b)=>b[1]-a[1])[0]};
  let key='cg',arr=D.custCgI,ix=(tally(arr)||[])[0];let lab=D.dicts.cg[ix];
  if(ix==null||lab==='(non renseigné)'||lab==='NO GROUP'){key='bm1';arr=D.custBm1I;ix=(tally(arr)||[])[0];lab=D.dicts.bm1[ix]}
  if(ix==null||lab==='(non renseigné)')return{lab:'—',doors:0,list:[]};
  const net=new Uint8Array(cm.length);for(let k=0;k<cm.length;k++)if(arr[k]===ix&&!cm[k])net[k]=1;
  const mk=d=>{if(!F[d].size)return null;const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return m};
  const ms=mk('sot'),mp=mk('perim');
  const items=new Map(),doorsAct=new Set();
  for(let i=0;i<N;i++){if(C.season[i]!==S.n)continue;const c=C.cust[i];if(!net[c])continue;if(ms&&!ms[C.sot[i]])continue;if(mp&&!mp[C.perim[i]])continue;
    const a=C.amt[i];if(a<=0)continue;doorsAct.add(c);let o=items.get(C.item[i]);if(!o){o={a:0,q:0,d:new Set(),line:C.line[i],fam:C.fam[i]};items.set(C.item[i],o)}o.a+=a;o.q+=C.qty[i];o.d.add(c)}
  const nd=doorsAct.size;
  const list=[...items.entries()].filter(([k])=>!(ownN.get(k)>0)).map(([k,o])=>({k,code:D.dicts.item[k],desc:D.itemDesc[k],lineName:D.dicts.line[o.line],famName:D.dicts.fam[o.fam],a:o.a,q:o.q,d:o.d.size,line:o.line,fam:o.fam,prev:ownP.get(k)>0})).sort((x,y)=>y.d-x.d||y.a-x.a);
  const fT=D.dicts.fam.indexOf('Textile'),fF=D.dicts.fam.indexOf('Footwear');
  const app=list.filter(r=>r.fam===fT).slice(0,5),ftw=list.filter(r=>r.fam===fF).slice(0,5);
  const netLab=(key==='cg'?'réseau ':'')+(key==='cg'?lab.charAt(0)+lab.slice(1).toLowerCase():lab);
  return{lab:netLab,doors:nd,app,ftw,list:app.concat(ftw)};
}

/* ---------- références clés du réseau (80 % du CA) ---------- */
const med=a=>{if(!a.length)return 0;const s=a.slice().sort((x,y)=>x-y);const m=s.length>>1;return s.length%2?s[m]:(s[m-1]+s[m])/2};
function keyRefs(cm,items){
  const tally=(arr)=>{const m=new Map();for(let k=0;k<cm.length;k++)if(cm[k])m.set(arr[k],(m.get(arr[k])||0)+1);return[...m.entries()].sort((a,b)=>b[1]-a[1])[0]};
  let key='cg',arr=D.custCgI,ix=(tally(arr)||[])[0];let lab=D.dicts.cg[ix];
  if(ix==null||lab==='(non renseigné)'||lab==='NO GROUP'){key='bm1';arr=D.custBm1I;ix=(tally(arr)||[])[0];lab=D.dicts.bm1[ix]}
  if(ix==null||lab==='(non renseigné)')return null;
  const net=new Uint8Array(cm.length);for(let k=0;k<cm.length;k++)if(arr[k]===ix&&!cm[k])net[k]=1;
  const mk=d=>{if(!F[d].size)return null;const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return m};const ms=mk('sot'),mp=mk('perim');
  const it=new Map(),doorQ=new Map();let netCA=0;
  for(let i=0;i<N;i++){if(C.season[i]!==S.n)continue;const c=C.cust[i];if(!net[c])continue;if(ms&&!ms[C.sot[i]])continue;if(mp&&!mp[C.perim[i]])continue;
    const a=C.amt[i],q=C.qty[i];netCA+=a;doorQ.set(c,(doorQ.get(c)||0)+Math.max(0,q));
    let o=it.get(C.item[i]);if(!o){o={a:0,dq:new Map(),fam:C.fam[i],line:C.line[i]};it.set(C.item[i],o)}o.a+=a;if(a>0)o.dq.set(c,(o.dq.get(c)||0)+q)}
  const doors=[...doorQ.values()].filter(q=>q>0);const nd=doors.length;if(!nd||netCA<=0)return null;
  const sorted=[...it.entries()].filter(([,o])=>o.a>0).sort((a,b)=>b[1].a-a[1].a);
  let cum=0;const core=[];for(const[k,o]of sorted){if(cum>=0.8*netCA)break;cum+=o.a;core.push([k,o])}
  // taille du client vs magasin médian du réseau
  let clientQ=0;items.forEach(v=>clientQ+=Math.max(0,v.qn||0));const medDoor=med(doors);
  const size=medDoor>0?Math.min(3,Math.max(0.3,clientQ/medDoor)):1;
  const missing=[],low=[];let have=0;
  core.forEach(([k,o],rank)=>{const share=o.a/netCA,dif=o.dq.size/nd,mq=med([...o.dq.values()]);const own=items.get(k);const q=own?own.qn:0;
    const exp=mq*size;const row={k,share,dif,d:o.dq.size,mq,q,exp,fam:o.fam,line:o.line,rank:rank+1};
    if(!own||!(own.n>0))missing.push(row);else{have++;if(q<0.5*exp)low.push(row)}});
  return{lab:(key==='cg'?'réseau ':'')+(key==='cg'?lab.charAt(0)+lab.slice(1).toLowerCase():lab),nd,nRefs:core.length,cover:cum/netCA,have,missing,low,clientQ,medDoor,size,netCA};
}
const KR={allM:false,allL:false};
function keyRefsHtml(KRd,G,n,p){
  if(!KRd)return`<section><h3 class="fs">Références clés du réseau</h3><p class="mutedv" style="margin:0">Pas de réseau de comparaison pour ce client (groupe client et business model non renseignés).</p></section>`;
  const who=G?'ce groupement':'ce client';const pct=v=>Math.round(v*1000)/10+' %';
  const sizeTxt=KRd.size>=0.95&&KRd.size<=1.05?'comparable au magasin médian du réseau':KRd.size<1?`plus petit que le magasin médian du réseau (${pct(KRd.size)} de ses volumes)`:`plus gros que le magasin médian du réseau (${pct(KRd.size)} de ses volumes)`;
  const mRows=(KR.allM?KRd.missing:KRd.missing.slice(0,10)).map(r=>`<tr><td class="lab">${cLabel('item',r.k)}</td><td class="lab mutedv">${esc(D.dicts.fam[r.fam])}</td><td>#${r.rank}</td><td>${pct(r.share)}</td><td><span class="dif"><i style="width:${Math.round(r.dif*100)}%"></i></span> ${Math.round(r.dif*100)} % <span class="meta">${fN(r.d)}/${fN(KRd.nd)}</span></td><td>${fN(r.mq)}</td><td><b>${fN(Math.max(1,Math.round(r.exp)))}</b></td></tr>`).join('');
  const lRows=(KR.allL?KRd.low:KRd.low.slice(0,10)).map(r=>`<tr><td class="lab">${cLabel('item',r.k)}</td><td class="lab mutedv">${esc(D.dicts.fam[r.fam])}</td><td>#${r.rank}</td><td><b>${fN(r.q)}</b></td><td>${fN(r.mq)}</td><td>${fN(Math.round(r.exp))}</td><td class="lab memo">Commandé ${fN(r.q)} pièce${r.q>1?'s':''}, soit ${pct(r.q/Math.max(1,r.exp))} du volume attendu (${fN(Math.round(r.exp))}). Proposer un complément de ${fN(Math.max(1,Math.round(r.exp-r.q)))} pièce${Math.round(r.exp-r.q)>1?'s':''}.</td></tr>`).join('');
  return`<section><h3 class="fs">Références clés du ${esc(KRd.lab)} · ${n}</h3>
  <p class="meta fexp">Sur ${n}, le ${esc(KRd.lab)} (${fN(KRd.nd)} magasins actifs, hors ${who}) réalise <b>${pct(KRd.cover)} de son CA avec ${fN(KRd.nRefs)} références</b> : ce sont les références clés. ${G?'Ce groupement':'Ce client'} en a commandé <b>${fN(KRd.have)}</b> ; <b>${fN(KRd.missing.length)}</b> manquent ; <b>${fN(KRd.low.length)}</b> sont commandées en volume faible. Les références clés commandées en volume suffisant ne sont pas affichées.</p>
  <p class="meta fexp"><b>Volume attendu</b> = quantité médiane commandée par les magasins du réseau qui ont pris la référence, ajustée à la taille de ${who} : avec ${fN(KRd.clientQ)} pièces commandées sur ${n} contre ${fN(KRd.medDoor)} pour le magasin médian, ${who} est ${sizeTxt}. « Volume faible » = moins de la moitié du volume attendu.${!seasonDone(D.dicts.season[S.n])?` La saison ${n} est encore en cours : ces chiffres vont évoluer.`:''}</p>
  <h4 class="fs4">À proposer : références clés non commandées <span class="meta">${fN(KRd.missing.length)}</span></h4>
  <div class="scroll"><table class="pv mrt"><thead><tr><th class="lab">Référence</th><th class="lab">Famille</th><th>Rang réseau</th><th>Part du CA réseau</th><th>Diffusion réseau</th><th>Qté médiane / porte</th><th>Volume attendu</th></tr></thead><tbody>${mRows||'<tr class="more"><td colspan="7">Toutes les références clés sont commandées.</td></tr>'}
  ${!KR.allM&&KRd.missing.length>10?`<tr class="more"><td colspan="7"><button type="button" class="linkbtn" data-kr="m">Afficher les ${fN(KRd.missing.length-10)} autres</button></td></tr>`:''}</tbody></table></div>
  <h4 class="fs4">À compléter : références clés commandées en volume faible <span class="meta">${fN(KRd.low.length)}</span></h4>
  <div class="scroll"><table class="pv mrt"><thead><tr><th class="lab">Référence</th><th class="lab">Famille</th><th>Rang réseau</th><th>Qté ${who.replace('ce ','')}</th><th>Qté médiane / porte</th><th>Volume attendu</th><th class="lab">Mémo</th></tr></thead><tbody>${lRows||'<tr class="more"><td colspan="7">Aucune référence clé en volume faible.</td></tr>'}
  ${!KR.allL&&KRd.low.length>10?`<tr class="more"><td colspan="7"><button type="button" class="linkbtn" data-kr="l">Afficher les ${fN(KRd.low.length-10)} autres</button></td></tr>`:''}</tbody></table></div></section>`;
}

/* saison en cours selon le calendrier commercial : FW N = mai → fin novembre N ; SS N+1 = décembre N → avril N+1 */
function curSeasonName(d){d=d||new Date();const y=d.getFullYear(),m=d.getMonth()+1;return m>=5&&m<=11?y+'-02-FW':m===12?(y+1)+'-01-SS':y+'-01-SS'}
/* ---------- vignettes produits (catalogues FW26 / SS27) ---------- */
let REM={r:{}};try{REM=JSON.parse(document.getElementById('remises').textContent)}catch(e){}
const remFmt=v=>v==null||v===''?'—':typeof v==='number'?v.toLocaleString('fr-FR',{maximumFractionDigits:2})+' %':String(v);
function remChip(c){const r=REM.r[c];if(!r)return'<span class="dash">—</span>';return`<span class="remc" data-tip="Remise FW26 : ${remFmt(r[0])}<br>Remise SS27 : ${remFmt(r[1])}${r[2]?'<br>Paiement : '+esc(r[2]):''}">${remFmt(r[1])}</span>`}
/* Photos produit : plus de planche statique (celle du template, figée sur 2 112 articles). Même principe que les autres
   pages de l'intranet (Arrivées Logtex…) : le navigateur charge la photo sur lecoqsportif.com en essayant les suffixes
   de nom connus ; si aucun ne répond, le serveur interroge la recherche du site (route lecoqsportif_image_lookup, avec
   cache) ; sinon la case reste grise. */
const PHOTO_BASE='https://www.lecoqsportif.com/cdn/shop/files/';
const PHOTO_CASCADE=['_2.webp','_new_1.webp','_1.webp','_2.jpg','_new_1.jpg','_1.jpg'];
function pImgNone(S,title){return`<span class="pimg none" style="width:${S}px;height:${S}px"${title?` title="${title}"`:''}></span>`}
function pImg(code,S){S=S||44;code=String(code==null?'':code).trim();if(!code||code==='(non renseigné)')return pImgNone(S);
  return`<span class="pimg ph" style="width:${S}px;height:${S}px"><img src="${PHOTO_BASE}${encodeURIComponent(code)}${PHOTO_CASCADE[0]}" data-article="${esc(code)}" data-step="0" alt="" loading="lazy" decoding="async" onerror="window.__ssgPhotoErr(this)"></span>`}
window.__ssgPhotoErr=function(img){
  const step=+img.dataset.step||0;
  if(step<PHOTO_CASCADE.length-1){img.dataset.step=String(step+1);img.src=img.src.replace(PHOTO_CASCADE[step],PHOTO_CASCADE[step+1]);return}
  img.onerror=null;
  const box=img.parentElement,none=()=>{if(box){box.className='pimg none';box.title="Pas d'image";box.innerHTML=''}};
  const url=(SSG.imageLookup||'').replace('ARTICLE_PLACEHOLDER',encodeURIComponent(img.dataset.article||''));
  if(!url){none();return}
  fetch(url,{credentials:'same-origin'}).then(r=>r.json()).then(d=>{if(d&&d.success&&d.url){img.onerror=none;img.src=d.url}else none()}).catch(none);
};
/* ---------- fiche client : détail d'une saison (pré-saison / réassort + sell-out) ---------- */
const FCH={se:null,tab:'ps',all:false,view:'vis'};const FGS={all:false};
function ficheSeasonHtml(cm){
  if(FCH.view!=='tab'){VV.cm=cm;const ss0=D.dicts.season;if(FCH.se==null){const cs=ss0.indexOf(curSeasonName());FCH.se=cs>=0?cs:S.n}return`<div class="vv-inline">${vvHtml(true)}</div>`}
  const ss=D.dicts.season;if(FCH.se==null){const cs=ss.indexOf(curSeasonName());FCH.se=cs>=0?cs:S.n}
  const se=FCH.se,sn=ss[se],n=sLab(sn),cls=rsClass();
  const mp=F.perim&&F.perim.size?(()=>{const m=new Uint8Array(D.dicts.perim.length);F.perim.forEach(i=>m[i]=1);return m})():null;
  const it=new Map(),tot={ps:{q:0,a:0},re:{q:0,a:0}};
  for(let i=0;i<N;i++){if(C.season[i]!==se||!cm[C.cust[i]])continue;if(mp&&!mp[C.perim[i]])continue;const t=cls[C.sot[i]];if(t!==0&&t!==1)continue;const k=t===0?'ps':'re';
    const o=it.get(C.item[i])||{f:C.fam[i],ps:{q:0,a:0},re:{q:0,a:0}};o[k].q+=C.qty[i];o[k].a+=C.amt[i];tot[k].q+=C.qty[i];tot[k].a+=C.amt[i];it.set(C.item[i],o)}
  /* sell-out par référence (reporting Push Réassort) */
  let so=null,soWk=null;
  if(SOD){const si=SOD.seasons.indexOf(sn);if(si>=0){so=new Map();soWk=SOD.iref[sn];const inv=new Map();SOD.ci.forEach((d,c)=>{if(d>=0)inv.set(c,d)});const I=SOD.I;
    for(let i=0;i<I.q.length;i++){if(I.season[i]!==si)continue;const d=inv.get(I.cust[i]);if(d==null||!cm[d])continue;const code=SOD.item[I.item[i]];const o=so.get(code)||{q:0,st:0,e:0};o.q+=I.q[i];o.st+=I.st[i];o.e+=I.e[i];so.set(code,o)}}}
  const tab=FCH.tab,k=tab;
  const list0=[...it.entries()].filter(([,o])=>o[k].q>0||o[k].a>0);
  const list=so?list0.sort((a,b)=>soCmp(so.get(D.dicts.item[a[0]]),so.get(D.dicts.item[b[0]]),a[1][k].q,b[1][k].q)):list0.sort((a,b)=>b[1][k].q-a[1][k].q);
  const FCOL=['#2a78d6','#a23f7a','#1f9d6b','#888'];const fcode=f=>{const nm=D.dicts.fam[f];return nm==='Textile'?['APP',FCOL[0]]:nm==='Footwear'?['FTW',FCOL[1]]:nm==='Accessoires'?['ACC',FCOL[2]]:['—',FCOL[3]]};
  const byF=new Map();list.forEach(([,o])=>{const a=byF.get(o.f)||{q:0,a:0,n:0};a.q+=o[k].q;a.a+=o[k].a;a.n++;byF.set(o.f,a)});
  const lim=FCH.all?list.length:40;
  const sell=(q,st)=>q+st>0?Math.round(q/(q+st)*100):null;
  const sCls=v=>v==null?'':v>=60?'pos':v<30?'neg':'';
  const rows=list.slice(0,lim).map(([itm,o])=>{const code=D.dicts.item[itm],s=so&&so.get(code),[fc,col]=fcode(o.f),r=s?sell(s.q,s.st):null;
    return`<tr><td class="fsd-im">${pImg(code,48)}</td><td class="lab"><span class="refpair"><b class="refc">${esc(code)}</b> <span class="refd">${esc(D.itemDesc[itm]||'')}</span></span></td><td><span class="memo-c" style="--mc:${col};font-size:10px">${fc}</span></td><td><b>${fN(o[k].q)}</b></td><td>${fE(o[k].a)}</td>
      ${so?`<td>${s?fN(s.q):'<span class="dash">—</span>'}</td><td>${s?fN(s.st):'<span class="dash">—</span>'}</td><td>${r==null?'<span class="dash">—</span>':`<span class="fsd-bar"><i style="width:${r}%"></i></span> <b class="${sCls(r)}">${r} %</b>`}</td><td>${s?stkTag(s):''}</td>`:''}</tr>`}).join('');
  const segs=ss.map((x,i)=>i).filter(i=>{let h=0;for(const [,o] of it)h+=0;return true});
  return`<div class="fsd-h"><h3 class="fs">Saison ${n} · détail des références</h3><button type="button" class="vv-open" data-fview="vis"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>Revenir à la vue visuelle</button><span class="meta">Cliquez sur une saison du graphique ci-dessus pour changer</span></div>
   <div class="fsd-tabs"><button type="button" data-fsd="ps" aria-pressed="${tab==='ps'}">Pré-saison <span>${fN(tot.ps.q)} pcs · ${fM(tot.ps.a)}</span></button><button type="button" data-fsd="re" aria-pressed="${tab==='re'}">Réassort <span>${fN(tot.re.q)} pcs · ${fM(tot.re.a)}</span></button></div>
   ${list.length?`<div class="fsd-fam">${[...byF.entries()].sort((a,b)=>b[1].q-a[1].q).map(([f,a])=>{const [fc,col]=fcode(f);return`<span><span class="memo-c" style="--mc:${col};font-size:10px">${fc}</span> <b>${fN(a.q)} pcs</b> · ${fN(a.n)} réf. · ${fM(a.a)}</span>`}).join('')}</div>
   <div class="scroll"><table class="pv fsd"><thead><tr><th></th><th class="lab">Référence</th><th>Fam.</th><th>Pièces ${tab==='ps'?'PS':'RE'}</th><th>CA</th>${so?`<th>Vendu</th><th>Stock</th><th>Sell-out ↓</th><th>État stock</th>`:''}</tr></thead><tbody>${rows}
   ${list.length>lim?`<tr class="more"><td colspan="9"><button type="button" class="linkbtn" data-fsdall="1">Afficher les ${fN(list.length-lim)} autres références</button></td></tr>`:''}</tbody></table></div>
   <p class="meta fexp" style="margin-top:6px">${so?`Sell-out : reporting Push Réassort Intersport / Sport 2000, cumul à fin ${soWkY(soWk)} ; taux = vendu ÷ (vendu + stock magasin). « — » = référence absente du reporting pour ce magasin.`:`Pas de données sell-out par référence pour ${n}${SOD?' (disponible pour '+SOD.seasons.map(sLab).join(', ')+')':''}.`} Vignettes : catalogues FW26 et SS27.</p>`
   :`<p class="mutedv" style="margin:6px 0">Aucune commande ${tab==='ps'?'de pré-saison':'de réassort'} sur ${n}.</p>`}`;
}
function bindFicheSeason(cm){
  const box=document.getElementById('fsDet');if(!box)return;
  const draw=()=>{box.innerHTML=ficheSeasonHtml(cm);document.querySelectorAll('#fiche [data-hse]').forEach(g=>g.classList.toggle('sel',+g.dataset.hse===FCH.se))};
  VV.redraw=draw;box.onclick=e=>{const fv=e.target.closest('[data-fview]');if(fv){FCH.view=fv.dataset.fview;draw();return}
    const m=e.target.closest('[data-vvm]');if(m){VV.mode=m.dataset.vvm;draw();return}const t=e.target.closest('[data-vvs]');if(t){VV.sort=t.dataset.vvs;draw();return}
    const sv=e.target.closest('[data-vvse]');if(sv){FCH.se=+sv.dataset.vvse;FCH.all=false;draw();return}
    if(e.target.matches&&e.target.matches('[data-vvex]')){VV.extra=e.target.checked;draw();return}
    if(e.target.closest('[data-vvopen]')){const h=document.getElementById('ficheT');openVV(cm,h?h.textContent.replace(/\s+/g,' ').trim():'');return}const b=e.target.closest('[data-fsd]');if(b){FCH.tab=b.dataset.fsd;FCH.all=false;draw();return}if(e.target.closest('[data-fsdall]')){FCH.all=true;draw()}};
  document.querySelectorAll('#fiche [data-hse]').forEach(g=>g.onclick=()=>{FCH.se=+g.dataset.hse;FCH.all=false;draw();box.scrollIntoView({behavior:'smooth',block:'start'})});
  draw();
}
/* ---------- fiche client : vue visuelle (fenêtre) Textile / Chaussures × Homme / Femme / Enfant ---------- */
/* ---------- classement par gamme (désignation) : Essentials d'abord ---------- */
let ITL=null;
function itemLine(it){if(!ITL){ITL=new Map();for(let i=0;i<N;i++)if(!ITL.has(C.item[i]))ITL.set(C.item[i],D.dicts.line[C.line[i]])}return it==null?'':ITL.get(it)||''}
function gmLabel(l){if(!l||l==='(non renseigné)')return'Autres';let g=l.replace(/^(MEN|WOMEN|KIDS|JUNIOR|HOMME|FEMME|ENFANTS?|JUNIORS?)\s+/i,'').replace(/\s+Kids$/i,'').trim();if(/ESSENTI/i.test(g))g='ESSENTIALS';return g.toUpperCase()}
function gmSort(arr,itOf,qOf,descOf){
  const tot=new Map();arr.forEach(x=>{const g=gmLabel(itemLine(itOf(x)));tot.set(g,(tot.get(g)||0)+qOf(x))});
  const rank=g=>/ESSENTI/i.test(g)?0:g==='Autres'?2:1;
  return arr.map(x=>({x,g:gmLabel(itemLine(itOf(x))),d:(descOf(x)||'').toUpperCase()})).sort((a,b)=>rank(a.g)-rank(b.g)||tot.get(b.g)-tot.get(a.g)||(a.g<b.g?-1:a.g>b.g?1:0)||(a.d<b.d?-1:a.d>b.d?1:0)).map(o=>(o.x.__gm=o.g,o.x))}
const VV={mode:'all',extra:true,cm:null,title:'',sort:'gm'};
/* état de stock d'une référence en magasin (reporting sell-out) */
function stkState(s){if(!s||s.q+s.st<=0)return null;const r=s.q/(s.q+s.st);
  if(s.st<=0&&s.q>0)return['rup','Rupture',0];if(r>=0.7||(s.st<=2&&s.q>0))return['low','Stock faible',1];if(r<0.2&&s.st>=10)return['over','Surstock',3];return['ok','Stock OK',2]}
function stkTag(s){const k=stkState(s);return k?`<span class="stk stk-${k[0]}">${k[1]}</span>`:''}
function soCmp(sa,sb,qa,qb){const ra=sa&&sa.q+sa.st>0?sa.q/(sa.q+sa.st):-1,rb=sb&&sb.q+sb.st>0?sb.q/(sb.q+sb.st):-1;return rb-ra||(sb?sb.q:0)-(sa?sa.q:0)||qb-qa}
let VV_SEG=null;
function vvItemSeg(){if(VV_SEG)return VV_SEG;VV_SEG=new Map();for(let i=0;i<N;i++){const it=C.item[i];if(!VV_SEG.has(it))VV_SEG.set(it,[segOfRow(i),C.fam[i]])}return VV_SEG}
function vvHtml(inline,ctx){const SOC=ctx==='so';
  const cm=VV.cm,ss=D.dicts.season,se=FCH.se,sn=ss[se],n=sLab(sn),cls=rsClass(),mode=VV.mode;
  const mp=F.perim&&F.perim.size?(()=>{const m=new Uint8Array(D.dicts.perim.length);F.perim.forEach(i=>m[i]=1);return m})():null;
  const it=new Map();
  for(let i=0;i<N;i++){if(C.season[i]!==se||!cm[C.cust[i]])continue;if(mp&&!mp[C.perim[i]])continue;const t=cls[C.sot[i]];if(t!==0&&t!==1)continue;
    const o=it.get(C.item[i])||{f:C.fam[i],g:segOfRow(i),ps:0,re:0,a:0};if(t===0)o.ps+=C.qty[i];else o.re+=C.qty[i];o.a+=C.amt[i];it.set(C.item[i],o)}
  /* sell-out magasin(s) de la saison */
  let so=null,soWk=null;
  if(SOD){const si=SOD.seasons.indexOf(sn);if(si>=0){so=new Map();soWk=SOD.iref[sn];const inv=new Map();SOD.ci.forEach((d,c)=>{if(d>=0)inv.set(c,d)});const I=SOD.I;
    for(let i=0;i<I.q.length;i++){if(I.season[i]!==si)continue;const d=inv.get(I.cust[i]);if(d==null||!cm[d])continue;const code=SOD.item[I.item[i]];const o=so.get(code)||{q:0,st:0,e:0,fi:I.item[i]};o.q+=I.q[i];o.st+=I.st[i];o.e+=I.e[i];so.set(code,o)}}}
  const famOf=f=>{const nm=D.dicts.fam[f];return nm==='Textile'?0:nm==='Footwear'?1:2};
  const sel=[...it.entries()].filter(([,o])=>mode==='all'?(o.ps+o.re>0||o.a>0):mode==='ps'?o.ps>0:o.re>0);
  const seen=new Set(sel.map(([k])=>D.dicts.item[k]));
  /* produits vendus en magasin mais non commandés sur cette saison (reporting) */
  const extra=[];
  if(so&&VV.extra&&!SOC){const segm=vvItemSeg(),idx=new Map();D.dicts.item.forEach((c,i)=>idx.set(c,i));
    so.forEach((o,code)=>{if(seen.has(code)||o.q<=0)return;const di=idx.get(code);const sg=di!=null?segm.get(di):null;
      const f=sg?famOf(sg[1]):SOD.ifam[o.fi]===0?0:SOD.ifam[o.fi]===1?1:2;extra.push({code,di,f,g:sg?sg[0]:4,o})})}
  const FAMS=[['Textile','APP','#2a78d6'],['Chaussures','FTW','#a23f7a'],['Accessoires','ACC','#1f9d6b']];
  const GL=['Homme','Femme','Unisexe','Enfant','Autre'],GO=[0,1,3,2,4];
  const sellR=s=>s&&s.q+s.st>0?Math.round(s.q/(s.q+s.st)*100):null;
  const sC=v=>v==null?'':v>=60?'pos':v<30?'neg':'';
  const card=(code,desc,o,s,ex)=>{const r=sellR(s);
    return`<div class="vv-c${ex?' ex':''}"><div class="vv-im">${pImg(code,112)}</div><div class="vv-b"><b class="refc">${esc(code)}</b><span class="vv-d">${esc(desc||'')}</span>
      ${ex?`<span class="vv-q"><i>Non commandé sur ${n}</i></span>`:`<span class="vv-q"><b>${fN(o.ps+o.re)} pcs</b>${mode==='all'&&o.ps&&o.re?` <i>PS ${fN(o.ps)} · RE ${fN(o.re)}</i>`:mode==='all'?` <i>${o.ps?'pré-saison':'réassort'}</i>`:''}</span><span class="vv-a">${fE(o.a)}</span>`}
      ${so&&s?stkTag(s):''}${so?(s?`<span class="vv-s"><span>Vendu <b>${fN(s.q)}</b></span><span>Stock <b>${fN(s.st)}</b></span></span><span class="vv-r"><span class="fsd-bar"><i style="width:${r||0}%"></i></span><b class="${sC(r)}">${r==null?'—':r+' %'}</b></span>`:'<span class="vv-s mutedv">pas de vente remontée</span>'):''}
      </div></div>`};
  let body='',tq=0,ta=0,tv=0;
  FAMS.forEach(([lab,fc,col],fi)=>{
    const rows=sel.filter(([,o])=>famOf(o.f)===fi),exr=extra.filter(x=>x.f===fi);if(!rows.length&&!exr.length)return;
    const fq=rows.reduce((s,[,o])=>s+o.ps+o.re,0),fa=rows.reduce((s,[,o])=>s+o.a,0);
    const fv=[...rows.map(([k])=>so&&so.get(D.dicts.item[k])),...exr.map(x=>x.o)].reduce((s,x)=>s+(x?x.q:0),0);tq+=fq;ta+=fa;tv+=fv;
    body+=`<section class="vv-f"><h3 class="vv-fh" style="--mc:${col}"><span class="memo-c" style="--mc:${col}">${fc}</span>${lab}<span>${fN(fq)} pcs commandées · ${fM(fa)}${so?` · ${fN(fv)} vendues en magasin`:''}</span></h3>`;
    GO.forEach(g=>{const sg=k=>so&&so.get(D.dicts.item[k]),qq=o=>o.ps+o.re;
      let rg=rows.filter(([,o])=>o.g===g).sort((a,b)=>VV.sort==='so'?soCmp(sg(a[0]),sg(b[0]),qq(a[1]),qq(b[1])):qq(b[1])-qq(a[1])),eg=exr.filter(x=>x.g===g).sort((a,b)=>soCmp(a.o,b.o,0,0));
      if(VV.sort==='gm'){rg=gmSort(rg,e=>e[0],e=>qq(e[1]),e=>D.itemDesc[e[0]]);eg=gmSort(eg,x=>x.di,x=>x.o.q,x=>x.di!=null?D.itemDesc[x.di]:SOD.idesc[x.o.fi])}if(!rg.length&&!eg.length)return;
      const q=rg.reduce((s,[,o])=>s+o.ps+o.re,0);
      const stc=[0,0,0,0];let stq=0;rg.forEach(([k])=>{const x=sg(k);if(x){stq+=x.st;const t=stkState(x);if(t)stc[t[2]]++}});
      body+=`<div class="vv-g"><h4>${GL[g]}<span>${fN(rg.length)} réf. · ${fN(q)} pcs${so?` · stock magasin ${fN(stq)} pcs`:''}</span>${so?`<span class="vv-gs">${stc[0]?`<span class="stk stk-rup">${stc[0]} rupture${stc[0]>1?'s':''}</span>`:''}${stc[1]?`<span class="stk stk-low">${stc[1]} stock faible</span>`:''}${stc[3]?`<span class="stk stk-over">${stc[3]} surstock</span>`:''}</span>`:''}</h4><div class="vv-grid">${(()=>{let last=null;return rg.map(e=>{const [k,o]=e,code=D.dicts.item[k];let h='';if(VV.sort==='gm'&&e.__gm!==last){last=e.__gm;const sub=rg.filter(z=>z.__gm===last);h=`<div class="vv-gm">${esc(last)}<span>${fN(sub.length)} réf. · ${fN(sub.reduce((t,z)=>t+qq(z[1]),0))} pcs</span></div>`}return h+card(code,D.itemDesc[k],o,so&&so.get(code),false)}).join('')})()}
        ${eg.length?eg.map(x=>card(x.code,x.di!=null?D.itemDesc[x.di]:(SOD.idesc[x.o.fi]||''),null,x.o,true)).join(''):''}</div></div>`});
    body+=`</section>`});
  const nb=(m,l)=>`<button type="button" data-vvm="${m}" aria-pressed="${mode===m}">${l}</button>`;
  return`${SOC?`<div class="fsd-h"><h3 class="fs">Assortiment ${n} · toutes les références du périmètre</h3><span class="meta">${fN(tq)} pcs commandées · ${fM(ta)}${so?` · ${fN(tv)} pièces vendues en magasin (cumul à fin ${soWkY(soWk)})`:''}</span></div>`:inline?`<div class="fsd-h"><h3 class="fs">Saison ${n} · assortiment</h3><span class="meta">${fN(tq)} pcs · ${fM(ta)}${so?` · ${fN(tv)} pièces vendues en magasin (cumul à fin ${soWkY(soWk)})`:''}</span>
       <span class="fsd-act"><button type="button" class="vv-mini" data-fview="tab">Vue tableau</button><button type="button" class="vv-open" data-vvopen="1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>Plein écran</button></span></div>`
     :`<div class="vv-h"><div><div class="vv-t">${VV.title}</div><div class="vv-st">Saison <b>${n}</b> · ${fN(tq)} pcs · ${fM(ta)}${so?` · ${fN(tv)} pièces vendues en magasin (cumul à fin ${soWkY(soWk)})`:''}</div></div>
     <button type="button" class="vv-x" data-vvclose aria-label="Fermer">×</button></div>`}
   <div class="vv-bar"><div class="vv-seg">${nb('all','Tout confondu')}${nb('ps','Pré-saison')}${nb('re','Réassort')}</div>
     ${SOC?'':`<div class="vv-seg">${ss.map((x,i)=>i).filter(i=>i>=ss.length-4).map(i=>`<button type="button" data-vvse="${i}" aria-pressed="${i===se}">${sLab(ss[i])}${ss[i]===curSeasonName()?' •':''}</button>`).join('')}</div>`}
     <div class="vv-seg"><button type="button" data-vvs="gm" aria-pressed="${VV.sort==='gm'}">Tri : par gamme</button><button type="button" data-vvs="so" aria-pressed="${VV.sort==='so'}">Tri : meilleur sell-out</button><button type="button" data-vvs="q" aria-pressed="${VV.sort==='q'}">Tri : quantités commandées</button></div>
     ${so&&!SOC?`<label class="vv-chk"><input type="checkbox" data-vvex ${VV.extra?'checked':''}> Afficher aussi les produits vendus non commandés</label>`:''}</div>
   ${so?(()=>{const c=[0,0,0,0],L=[[],[],[],[]];let stq=0,vq=0,nd=0;sel.forEach(([k])=>{const x=so.get(D.dicts.item[k]);if(!x){nd++;return}stq+=x.st;vq+=x.q;const t=stkState(x);if(t){c[t[2]]++;L[t[2]].push(D.dicts.item[k])}});const r=vq+stq>0?Math.round(vq/(vq+stq)*100):null;
     return`<div class="vv-stk"><div><span>Sell-out global</span><b>${r==null?'—':r+' %'}</b><em>${fN(vq)} vendues / ${fN(vq+stq)}</em></div><div><span>Stock magasin</span><b>${fN(stq)} pcs</b><em>sur les réf. commandées</em></div><div class="r"><span>Ruptures</span><b>${c[0]}</b><em>à réassortir en priorité</em></div><div class="l"><span>Stock faible</span><b>${c[1]}</b><em>sell-out ≥ 70 % ou ≤ 2 pcs</em></div><div class="o"><span>Surstock</span><b>${c[3]}</b><em>sell-out &lt; 20 % et ≥ 10 pcs</em></div><div><span>Stock OK</span><b>${c[2]}</b><em>${nd?nd+' réf. sans remontée':'&nbsp;'}</em></div></div>`})():''}
   <div class="vv-body${inline?' inl':''}">${body||`<p class="mutedv" style="padding:30px">Aucune commande sur ${n} pour ce client.</p>`}
   <p class="meta" style="margin:14px 0 4px">${so?`Ventes : reporting sell-out Intersport / Sport 2000 (cumul saison ${n}) ; taux = vendu ÷ (vendu + stock magasin). Cartes grisées = vendues en magasin sans commande ${n} (anciennes saisons / permanents).`:`Pas de sell-out par référence pour ${n}.`} Vignettes : catalogues FW26 et SS27.</p></div>`;
}
function openVV(cm,title){
  VV.cm=cm;VV.title=title||'';let o=document.getElementById('vv');if(!o){o=document.createElement('div');o.id='vv';ROOT.appendChild(o)}
  const draw=()=>{const y=o.querySelector('.vv-body')?o.querySelector('.vv-body').scrollTop:0;o.innerHTML=`<div class="vv-p" role="dialog" aria-modal="true">${vvHtml()}</div>`;return y};
  draw();o.hidden=false;
  o.onclick=e=>{if(e.target===o||e.target.closest('[data-vvclose]')){o.hidden=true;if(VV.redraw)VV.redraw();return}
    const m=e.target.closest('[data-vvm]');if(m){VV.mode=m.dataset.vvm;draw();return}const t=e.target.closest('[data-vvs]');if(t){VV.sort=t.dataset.vvs;draw();return}
    const s=e.target.closest('[data-vvse]');if(s){FCH.se=+s.dataset.vvse;FCH.all=false;draw();const b=document.getElementById('fsDet');if(b&&VV.redraw)VV.redraw();return}
    if(e.target.matches('[data-vvex]')){VV.extra=e.target.checked;draw()}};
  document.addEventListener('keydown',function k(e){if(e.key==='Escape'){o.hidden=true;document.removeEventListener('keydown',k)}});
}

/* choix de la saison de commande dans la fiche client */
const FPS={se:null};
function bindFps(cm){
  const box=document.getElementById('fpsBox');if(!box||!FA)return;
  if(!FA.ci)FA.ci=FA.dicts.cust.map(c=>D.dicts.cust.indexOf(c));
  const has=new Map();for(const i of faRows()){const dc=FA.ci[FA.C.cust[i]];if(dc<0||!cm[dc])continue;const v=FA.C.b[i]+FA.C.r[i];if(v>0)has.set(FA.C.season[i],(has.get(FA.C.season[i])||0)+v)}
  const cur=curSeasonName();let list=FA.dicts.season.map((x,i)=>i).filter(i=>has.get(i)>0.5||FA.dicts.season[i]===cur).sort((a,b)=>FA.dicts.season[a]<FA.dicts.season[b]?-1:1);
  list=list.slice(-6);
  if(FPS.se==null||!FA.dicts.season[FPS.se])FPS.se=FA.dicts.season.indexOf(cur)>=0?FA.dicts.season.indexOf(cur):list[list.length-1];
  const draw=()=>{const sn=FA.dicts.season[FPS.se];
    box.innerHTML=`<div class="fps-pick"><span>Saison de commande</span><div class="vv-seg">${list.map(i=>`<button type="button" data-fpse="${i}" aria-pressed="${i===FPS.se}">${sLab(FA.dicts.season[i])}${FA.dicts.season[i]===cur?' <em>en cours</em>':''}</button>`).join('')}</div></div>${fichePsHtml(cm,sn)}`};
  box.onclick=e=>{const b=e.target.closest('[data-fpse]');if(b){FPS.se=+b.dataset.fpse;draw()}};
  draw();
}
function fichePsHtml(cm,sName){
  if(!FA)return'';sName=sName||D.dicts.season[S.n];
  const se=FA.dicts.season.indexOf(sName);if(se<0)return'';
  if(!FA.ci)FA.ci=FA.dicts.cust.map(c=>D.dicts.cust.indexOf(c));
  const FAMS=[['Textile','APP','#2a78d6'],['Footwear','FTW','#a23f7a'],['Accessoires','ACC','#1f9d6b']];
  const m=new Map(FAMS.map(f=>[f[0],{b:0,i:0,r:0,l:0,next:null}]));const T={b:0,i:0,r:0,l:0,next:null};
  for(const i of faRows()){if(FA.C.season[i]!==se)continue;const dc=FA.ci[FA.C.cust[i]];if(dc<0||!cm[dc])continue;const o=m.get(FA.dicts.fam[FA.C.fam[i]]);if(!o)continue;
    o.b+=FA.C.b[i];o.i+=FA.C.i[i];o.r+=FA.C.r[i];T.b+=FA.C.b[i];T.i+=FA.C.i[i];T.r+=FA.C.r[i]}
  const IT=new Map();
  if(ROB){if(!ROB.ci)ROB.ci=ROB.dicts.cust.map(c=>D.dicts.cust.indexOf(c));const si=ROB.dicts.season.indexOf(sName),today=ROB.asof;
    for(const i of robRows()){if(ROB.C.season[i]!==si)continue;const dc=ROB.ci[ROB.C.cust[i]];if(dc<0||!cm[dc])continue;const o=m.get(ROB.dicts.fam[ROB.C.fam[i]]);if(!o)continue;
      const d=ROB.dicts.rdd[ROB.C.rdd[i]],r=ROB.C.r[i];if(r<=0)continue;
      const it=ROB.C.item[i];const a=IT.get(it)||{f:ROB.dicts.fam[ROB.C.fam[i]],q:0,r:0,ql:0,rl:0,dl:null,dn:null,cs:new Set()};a.q+=ROB.C.q[i];a.r+=r;a.cs.add(ROB.C.cust[i]);
      if(d&&d<today){a.ql+=ROB.C.q[i];a.rl+=r;if(!a.dl||d<a.dl)a.dl=d}else if(d){if(!a.dn||d<a.dn)a.dn=d}IT.set(it,a);
      if(!d)continue;if(d<today){o.l+=r;T.l+=r}else{if(!o.next||d<o.next)o.next=d;if(!T.next||d<T.next)T.next=d}}}
  const n=sLab(sName),cur=sName===curSeasonName(),ttl=`${cur?'<span class="cur-tag">Saison en cours</span> ':''}${typeLab()} ${n} · commandé, livré, reste à livrer`;
  if(T.b<1&&T.r<1)return`<section><h3 class="fs">${ttl}</h3><p class="mutedv" style="margin:0">Aucune commande ${typeLab()} ${n} pour ce client.</p></section>`;
  const pc=(a,b)=>b>0?Math.round(a/b*100):0;const li=pc(T.i,T.b),lr=Math.min(100-li,pc(T.r,T.b));
  const rows=FAMS.filter(f=>m.get(f[0]).b>0.5||m.get(f[0]).r>0.5).map(([lab,code,col])=>{const o=m.get(lab);return`<tr><td class="lab"><span class="memo-c" style="--mc:${col};margin-right:8px">${code}</span><b>${lab}</b></td><td>${fE(o.b)}</td><td>${fE(o.i)} <span class="mutedv">${pc(o.i,o.b)} %</span></td><td><b>${fE(o.r)}</b></td><td>${o.l>0.5?`<button type="button" class="fps-lk neg" onclick="fpsOpen(this,'late','${lab}')">${fE(o.l)} ›</button>`:'<span class="dash">—</span>'}</td><td>${o.next?fD(o.next):'<span class="dash">—</span>'}</td></tr>`}).join('');
  return`<section><h3 class="fs">${ttl}</h3>
    <div class="fps-k"><div><span>Commandé</span><b>${fM(T.b)}</b></div><div><span>Déjà livré &amp; facturé</span><b class="pos">${fM(T.i)}</b><em>${li} % du commandé</em></div><div><span>Reste à livrer</span><b>${fM(T.r)}</b><em>${T.l>0.5?`<b class="neg">dont ${fM(T.l)} en retard</b>`:'aucun retard'}${T.next?` · prochaine livraison demandée ${fD(T.next)}`:''}</em></div></div>
    <div class="fps-bar" role="img" aria-label="${li} % livré"><i class="ok" style="width:${li}%"></i><i class="rest" style="width:${lr}%"></i></div>
    <div class="fps-leg"><span><i class="ok"></i>Livré &amp; facturé ${li} %</span><span><i class="rest"></i>Reste à livrer ${lr} %</span></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Famille</th><th>Commandé</th><th>Livré &amp; facturé</th><th>Reste à livrer</th><th>dont en retard</th><th>Prochaine livraison</th></tr></thead><tbody>${rows}<tr class="tot"><td class="lab">Total</td><td>${fE(T.b)}</td><td>${fE(T.i)}</td><td>${fE(T.r)}</td><td>${T.l>0.5?`<button type="button" class="fps-lk neg" onclick="fpsOpen(this,'late','')">${fE(T.l)} ›</button>`:'—'}</td><td>${T.next?fD(T.next):'—'}</td></tr></tbody></table></div>
    ${fpsItems(IT,ROB?ROB.asof:FA.asof)}
    <p class="meta fexp" style="margin-top:6px">Commandes ${typeLab()} de la collection ${n} (mêmes données que l'onglet Facturation). « En retard » = date de livraison demandée dépassée au ${fD(FA.asof)}.</p></section>`;
}
function fpsOpen(btn,kind,fam){const sec=btn.closest('section');const d=sec.querySelector(kind==='late'?'.fps-late':'.fps-all');if(!d)return;d.open=true;
  let nv=0;d.querySelectorAll('tr[data-f]').forEach(r=>{r.hidden=!!fam&&r.dataset.f!==fam;if(!r.hidden)nv++});const h=d.querySelector('.fps-fam');if(h)h.innerHTML=fam?`· filtre <b>${fam}</b> (${nv} réf.) <a href="#" class="fps-all-f" onclick="event.preventDefault();event.stopPropagation();fpsOpen(this,'${kind}','')">toutes familles</a>`:'';d.scrollIntoView({behavior:'smooth',block:'nearest'})}
function fpsItems(IT,today){
  if(!ROB||!IT.size)return'';
  const FC={Textile:['APP','#2a78d6'],Footwear:['FTW','#a23f7a'],Accessoires:['ACC','#1f9d6b']};
  const days=d=>Math.round((new Date(today)-new Date(d))/864e5);
  const ref=it=>`${pImg(ROB.dicts.item[it],36)} <span class="refpair"><b class="refc">${esc(ROB.dicts.item[it])}</b> <span class="refd">${esc(ROB.itemDesc[it]||'')}</span></span>`;
  const fam=f=>{const x=FC[f]||['—','#888'];return`<span class="memo-c" style="--mc:${x[1]};font-size:10px">${x[0]}</span>`};
  const arr=(a,it)=>{let mx='',none=false;a.cs.forEach(c=>{const v=ROB.arr&&ROB.arr.get(ROB.dicts.cust[c]+'|'+ROB.dicts.item[it]);if(v){if(v.max>mx)mx=v.max;if(v.none)none=true}});
    if(mx&&mx>today)return`<span class="tag t-wait">attendu le ${fD(mx)}</span>`;if(mx)return`<span class="tag t-chk">en stock depuis le ${fD(mx)}</span>`;return'<span class="mutedv">pas d’arrivée connue</span>'};
  const late=[...IT.entries()].filter(([,a])=>a.rl>0.5).sort((x,y)=>y[1].rl-x[1].rl);
  const all=[...IT.entries()].sort((x,y)=>y[1].r-x[1].r);
  const multi=[...IT.values()].some(a=>a.cs.size>1);
  const lateT=late.length?`<details class="fps-d fps-late"${late.length<=8?' open':''}><summary><b class="neg">Produits en retard</b> <span class="fps-fam"></span> · ${fN(late.length)} réf. · ${fE(late.reduce((s,[,a])=>s+a.rl,0))} <span class="mutedv">— cliquez pour ${late.length<=8?'replier':'afficher'}</span></summary>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Référence</th><th>Qté en retard</th><th>Montant</th><th>Livraison demandée</th><th>Retard</th><th class="lab">Marchandise (ADV)</th></tr></thead><tbody>
    ${late.map(([it,a])=>`<tr data-f="${esc(a.f)}"><td class="lab">${fam(a.f)} ${ref(it)}${multi&&a.cs.size>1?` <span class="mutedv">· ${a.cs.size} magasins</span>`:''}</td><td>${fN(a.ql)}</td><td><b>${fE(a.rl)}</b></td><td>${fD(a.dl)}</td><td class="neg">${fN(days(a.dl))} j</td><td class="lab">${arr(a,it)}</td></tr>`).join('')}</tbody></table></div></details>`:'';
  const allT=`<details class="fps-d fps-all"><summary><b>Tout le reste à livrer</b> <span class="fps-fam"></span> · ${fN(all.length)} réf. · ${fE(all.reduce((s,[,a])=>s+a.r,0))} <span class="mutedv">— cliquez pour afficher</span></summary>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Référence</th><th>Qté</th><th>Montant</th><th>dont en retard</th><th>Prochaine livraison</th><th class="lab">Marchandise (ADV)</th></tr></thead><tbody>
    ${all.slice(0,150).map(([it,a])=>`<tr data-f="${esc(a.f)}"><td class="lab">${fam(a.f)} ${ref(it)}</td><td>${fN(a.q)}</td><td><b>${fE(a.r)}</b></td><td>${a.rl>0.5?`<span class="neg">${fE(a.rl)}</span>`:'<span class="dash">—</span>'}</td><td>${a.dn?fD(a.dn):a.dl?`<span class="neg">${fD(a.dl)}</span>`:'—'}</td><td class="lab">${arr(a,it)}</td></tr>`).join('')}${all.length>150?`<tr class="more"><td colspan="6">… ${fN(all.length-150)} autres références</td></tr>`:''}</tbody></table></div></details>`;
  return lateT+allT;
}
function openFiche(c,g){if(g!==FGS.g){FGS.all=false;FGS.g=g}
  const G=g!=null&&g>=0;const cm=new Uint8Array(D.dicts.cust.length);if(G){for(let k=0;k<cm.length;k++)if(D.custGrpI[k]===g)cm[k]=1}else cm[c]=1;
  const sN=new Float64Array(cm.length),sP=new Float64Array(cm.length);
  const ss=D.dicts.season,ns=ss.length,n=sLab(ss[S.n]),p=sLab(ss[S.p]);
  const mk=d=>{if(!F[d].size)return null;const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return m};
  const ms=mk('sot'),mp=mk('perim');
  const hist=new Float64Array(ns),qh=new Float64Array(ns),nf=D.dicts.fam.length;
  const fam={n:new Float64Array(nf),p:new Float64Array(nf)},lines=new Map(),items=new Map();
  for(let i=0;i<N;i++){const cu=C.cust[i];if(!cm[cu])continue;if(ms&&!ms[C.sot[i]])continue;if(mp&&!mp[C.perim[i]])continue;
    const s=C.season[i],a=C.amt[i];hist[s]+=a;qh[s]+=C.qty[i];
    if(s===S.n||s===S.p){const isN=s===S.n;if(isN)sN[cu]+=a;else sP[cu]+=a;fam[isN?'n':'p'][C.fam[i]]+=a;
      let l=lines.get(C.line[i]);if(!l){l={n:0,p:0};lines.set(C.line[i],l)}l[isN?'n':'p']+=a;
      let it=items.get(C.item[i]);if(!it){it={n:0,p:0,qn:0,qp:0};items.set(C.item[i],it)}if(isN){it.n+=a;it.qn+=C.qty[i]}else{it.p+=a;it.qp+=C.qty[i]}}}
  const tN=hist[S.n],tP=hist[S.p];
  /* répartition par type de commande (toutes saisons, hors filtre type) */
  const tyA=new Float64Array(D.dicts.sot.length),tyN=new Float64Array(D.dicts.sot.length);
  for(let i=0;i<N;i++){if(!cm[C.cust[i]])continue;if(mp&&!mp[C.perim[i]])continue;tyA[C.sot[i]]+=C.amt[i];if(C.season[i]===S.n)tyN[C.sot[i]]+=C.amt[i]}
  const TYL={PS:'Pré-saison',RE:'Réassort',OP:'Opérations'},TYO=['PS','RE','CO','OP'];
  const tyIds=D.dicts.sot.map((v,i)=>i).filter(i=>Math.abs(tyA[i])>=1||F.sot.has(i)).sort((x,y)=>{const a=TYO.indexOf(D.dicts.sot[x]),b=TYO.indexOf(D.dicts.sot[y]);return(a<0?9:a)-(b<0?9:b)||tyA[y]-tyA[x]});
  const tyBar=`<div class="fty" role="group" aria-label="Type de commande"><span class="tl">Type de commande</span><button type="button" data-ty="all" aria-pressed="${!F.sot.size}">Tous</button>${tyIds.map(i=>{const k=D.dicts.sot[i];return`<button type="button" data-ty="${i}" aria-pressed="${F.sot.has(i)}" data-tip="<b>${esc(k)}${TYL[k]?' · '+TYL[k]:''}</b><br>${n} : ${fE(tyN[i])}<br>8 saisons : ${fE(tyA[i])}">${esc(k)}${TYL[k]?' <span class=\"ftl\">'+TYL[k]+'</span>':''}<span class="ftv">${fK(tyA[i])}</span></button>`}).join('')}<span class="meta">plusieurs choix possibles · s'applique aussi au reste du site et à la trame PPT</span></div>`;
  const t=G?0:R.st[c],stc=t===1?'<span class="chip up">comparable</span>':t===2?'<span class="chip down">porte perdue</span>':t===3?'<span class="chip new">nouvelle porte</span>':'';
  const stores=[];let dN=0,dP=0,comp=0,lost=0,nw=0;for(let k=0;k<cm.length;k++){if(!cm[k]||(!sN[k]&&!sP[k]))continue;stores.push(k);const a=sN[k]>0,b=sP[k]>0;if(a)dN++;if(b)dP++;if(a&&b)comp++;else if(b)lost++;else if(a)nw++}
  stores.sort((a,b)=>sN[b]-sN[a]||sP[b]-sP[a]);
  const most=(arr,dict)=>{const m=new Map();stores.forEach(k=>m.set(arr[k],(m.get(arr[k])||0)+sN[k]+sP[k]));const top=[...m.entries()].sort((a,b)=>b[1]-a[1]).slice(0,3).map(x=>dict[x[0]]).filter(x=>x!=='(non renseigné)');return top.join(', ')||'—'};
  const remG=(()=>{if(!G)return'';const m=new Map();let n0=0;for(let k=0;k<cm.length;k++){if(!cm[k])continue;const r=REM.r[k];if(!r||r[1]==null){n0++;continue}const v=remFmt(r[1]);m.set(v,(m.get(v)||0)+1)}const s=[...m.entries()].sort((a,b)=>b[1]-a[1]).map(([v,c])=>`${v} (${c})`).join(', ');return s?s+(n0?` · ${n0} non renseignés`:''):'non renseignée'})();
  const info=G?[['Remise SS27',remG],['Magasins',fN(stores.length)+' avec commandes en '+n+' ou '+p],['Groupe client',most(D.custCgI,D.dicts.cg)],['Commerciaux',most(D.custSalesI,D.dicts.sales)],['Manager N+1',most(D.custN1I,D.dicts.n1)]]:[['Groupement',D.custGrp[c]||''],['Groupe client',D.dicts.cg[D.custCgI[c]]],['Business model',D.dicts.bm1[D.custBm1I[c]]],['Commercial',D.dicts.sales[D.custSalesI[c]]],['Manager N+1',D.dicts.n1[D.custN1I[c]]],...(REM.r[c]?[['Remise SS27',remFmt(REM.r[c][1])],['Remise FW26',remFmt(REM.r[c][0])],...(REM.r[c][2]?[['Paiement',REM.r[c][2]]]:[])]:[['Remise','non renseignée']])];
  /* history chart */
  const W=600,H=200,ml=50,mr=8,mt=22,mb=26,pw=W-ml-mr,ph=H-mt-mb,mx=niceMax(Math.max(...hist,1)),bw=pw/ns;
  let s='';for(let k=0;k<=2;k++){const v=mx*k/2,y=mt+ph-ph*k/2;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-6}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}€</text>`}
  for(let i=0;i<ns;i++){const v=Math.max(0,hist[i]),h=ph*v/mx,x=ml+i*bw+bw*.2,w=bw*.6,y=mt+ph-h;const fill=i===S.n?'var(--n)':i===S.p?'var(--p)':'var(--bar)';
    s+=`<g data-hse="${i}" class="hbar" data-tip="<b>${sLab(ss[i])}</b><br>${fE(hist[i])} · ${fN(qh[i])} pièces"><rect x="${ml+i*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${v?`<rect x="${x}" y="${y}" width="${w}" height="${Math.max(h,1)}" rx="3" fill="${fill}"/><text x="${x+w/2}" y="${y-5}" text-anchor="middle" class="vl">${fK(v)}</text>`:''}<text x="${x+w/2}" y="${H-8}" text-anchor="middle" class="ax">${sLab(ss[i])}</text></g>`}
  /* family mix */
  const famIdx=[...Array(nf).keys()].filter(i=>fam.n[i]||fam.p[i]);const FC=['var(--c1)','var(--c2)','var(--c3)','var(--c4)'];
  const mxT=Math.max(tN,tP,1);const stack=(arr,tot)=>tot>0?`<div class="stackw"><div class="stack" style="flex:0 0 calc((100% - 90px) * ${Math.max(0.02,tot/mxT).toFixed(4)})">${famIdx.map((i,j)=>arr[i]>0?`<span style="width:${arr[i]/tot*100}%;background:${FC[j%4]}" data-tip="${esc(D.dicts.fam[i])} : ${fE(arr[i])} (${Math.round(arr[i]/tot*100)} %)"></span>`:'').join('')}</div><span class="stackv">${fE(tot)}</span></div>`:'<div class="stack empty">aucune commande</div>';
  const famRows=famIdx.map((i,j)=>`<tr><td class="lab"><span class="sw" style="background:${FC[j%4]}"></span>${esc(D.dicts.fam[i])}</td><td>${fE(fam.n[i])}</td><td>${tN>0?Math.round(fam.n[i]/tN*100)+' %':'—'}</td><td>${fE(fam.p[i])}</td><td>${tP>0?Math.round(fam.p[i]/tP*100)+' %':'—'}</td><td>${chip(fam.n[i],fam.p[i])}</td></tr>`).join('');
  const lineRows=[...lines.entries()].sort((a,b)=>(b[1].n+b[1].p)-(a[1].n+a[1].p)).slice(0,12).map(([l,v])=>`<tr><td class="lab">${esc(D.dicts.line[l])}</td><td>${fE(v.n)}</td><td>${fE(v.p)}</td><td>${chip(v.n,v.p)}</td></tr>`).join('');
  const topN=[...items.entries()].filter(([,v])=>v.n>0).sort((a,b)=>b[1].n-a[1].n).slice(0,15);
  const gone=[...items.entries()].filter(([,v])=>v.p>0&&v.n<=0).sort((a,b)=>b[1].p-a[1].p).slice(0,10);
  const itRows=(arr,isN)=>arr.length?arr.map(([k,v])=>`<tr><td class="lab">${cLabel('item',k)}</td><td>${fN(isN?v.qn:v.qp)}</td><td>${fE(isN?v.n:v.p)}</td></tr>`).join(''):'<tr class="more"><td colspan="3">Aucune référence.</td></tr>';
  const ownN=new Map(),ownP=new Map();items.forEach((v,k)=>{ownN.set(k,v.n);ownP.set(k,v.p)});const MR=missingRefs(cm,ownN,ownP);KR.allM=false;KR.allL=false;const KRd=D.net?null:keyRefs(cm,items);
  const el=$('#fiche');
  el.innerHTML=`<div class="fiche-panel" role="dialog" aria-modal="true" aria-labelledby="ficheT">
    <div class="fiche-h"><div><div class="tl">Fiche ${G?'groupement':'magasin'} · ${typeLab()}${F.perim.size?' · périmètre BOH':''}</div><h2 id="ficheT">${G?esc(D.dicts.grp[g]):c===CENT?'Centrale Intersport':'Client n° '+esc(D.dicts.cust[c])} <span class="meta">${G?'':esc(D.custCity[c]||'')}</span></h2>
      <div class="fiche-info">${info.map(([k,v])=>`<span><span class="tl">${k}</span> ${esc(v)}</span>`).join('')}${!G&&D.custGrp[c]&&c!==CENT?`<button type="button" class="fiche" data-g="${D.custGrpI[c]}">Fiche du groupement</button>`:''}${c!==CENT?`<button type="button" class="fiche-trame" data-tm="${G?'grp':'cust'}" data-ts="${G?g:c}">Trame RDV (PowerPoint)</button>`:''}</div></div>
      <button type="button" class="close" id="ficheX" aria-label="Fermer la fiche">×</button></div>
    <div class="fiche-b">
      ${tyBar}
      <div class="kpis mini">
        <div class="kpi"><h3>CA ${n}</h3><div class="big">${fM(tN)}</div><div class="cmp">${p} : ${fM(tP)} ${chip(tN,tP)}</div></div>
        <div class="kpi"><h3>Quantités ${n}</h3><div class="big">${fN(qh[S.n])}</div><div class="cmp">${p} : ${fN(qh[S.p])} ${chip(qh[S.n],qh[S.p])}</div></div>
        ${G?`<div class="kpi"><h3>Portes actives ${n}</h3><div class="big">${fN(dN)}</div><div class="cmp">${p} : ${fN(dP)} · ${fN(comp)} comparables · ${fN(lost)} perdues · ${fN(nw)} nouvelles</div></div>`:''}
        <div class="kpi"><h3>Références ${n}</h3><div class="big">${fN(topN.length?[...items.values()].filter(v=>v.n>0).length:0)}</div><div class="cmp">${p} : ${fN([...items.values()].filter(v=>v.p>0).length)} ${stc}</div></div>
      </div>
      ${G?(()=>{const all=[];for(let k=0;k<cm.length;k++)if(cm[k])all.push(k);
        const st=k=>sN[k]>0&&sP[k]>0?['comp','actif','chip up']:sN[k]>0?['new','nouveau','chip new']:sP[k]>0?['lost','perdu','chip down']:['none','sans commande','chip'];
        const rk={comp:0,new:1,lost:2,none:3};all.sort((a,b)=>rk[st(a)[0]]-rk[st(b)[0]]||sN[b]-sN[a]||sP[b]-sP[a]);
        const cnt={comp:0,new:0,lost:0,none:0};all.forEach(k=>cnt[st(k)[0]]++);
        const lim=FGS.all?all.length:12;
        return`<section id="fgStores"><h3 class="fs">Magasins rattachés au groupement <span class="meta">${fN(all.length)} magasins · ${fN(cnt.comp)} actifs sur ${n} et ${p} · ${fN(cnt.new)} nouveaux · ${fN(cnt.lost)} perdus${cnt.none?` · ${fN(cnt.none)} sans commande sur ces 2 saisons`:''}</span></h3>
        <div class="scroll"><table class="pv"><thead><tr><th class="lab">Magasin</th><th class="lab">Statut</th><th class="lab">Commercial</th><th>Remise SS27</th><th class="cn">CA ${n}<span class="bar"></span></th><th class="cp">CA ${p}<span class="bar"></span></th><th>Écart €</th><th>Évo</th></tr></thead><tbody>
        ${all.slice(0,lim).map(k=>{const t=st(k);return`<tr class="${t[0]==='none'?'fg-none':''}"><td class="lab"><span class="cpair"><b class="cno">${esc(D.dicts.cust[k])}</b><span class="ccity">${esc(D.custCity[k]||'ville non renseignée')}</span><span class="cact"><button type="button" class="fiche" data-c="${k}" aria-label="Fiche du client ${esc(D.dicts.cust[k])}">Fiche</button></span></span></td><td class="lab"><span class="${t[2]}">${t[1]}</span></td><td class="lab mutedv" style="min-width:130px">${esc(D.dicts.sales[D.custSalesI[k]])}</td><td>${remChip(k)}</td><td>${sN[k]?fE(sN[k]):'<span class="dash">—</span>'}</td><td>${sP[k]?fE(sP[k]):'<span class="dash">—</span>'}</td><td>${sN[k]||sP[k]?sgn(sN[k]-sP[k]):''}</td><td>${sN[k]||sP[k]?chip(sN[k],sP[k]):''}</td></tr>`}).join('')}
        ${all.length>lim?`<tr class="more"><td colspan="8"><button type="button" class="linkbtn" data-fgall="1">Afficher les ${fN(all.length-lim)} autres magasins</button></td></tr>`:''}
        <tr class="tot"><td class="lab">Total</td><td></td><td></td><td></td><td>${fE(tN)}</td><td>${fE(tP)}</td><td>${sgn(tN-tP)}</td><td>${chip(tN,tP)}</td></tr></tbody></table></div></section>`})():''}
      ${G?'':`<section><h3 class="fs">Notes de suivi <span class="meta">partagées avec l'équipe</span></h3><div id="notesBox" data-c="${c}"></div></section>`}
      <section><h3 class="fs">Historique sur 8 saisons <span class="meta">${typeLab()}${F.perim.size?' · périmètre BOH':''} · cliquez sur une saison pour le détail des références</span></h3><div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:460px;max-width:680px;display:block" role="img" aria-label="Historique du magasin">${s}</svg></div></section>
      ${accFiche(cm)}
      ${annFiche(cm)}
      <section id="fsDet"></section>
      <section><h3 class="fs">Mix familles</h3>
        <div class="stackrow"><span class="tl">${n}</span>${stack(fam.n,tN)}</div><div class="stackrow"><span class="tl">${p}</span>${stack(fam.p,tP)}</div><p class="meta fexp" style="margin-top:4px">La longueur de chaque barre est proportionnelle au montant ${typeLab()} commandé sur la saison ; les couleurs montrent la répartition par famille.</p>
        <div class="scroll"><table class="pv"><thead><tr><th class="lab">Famille</th><th class="cn">CA ${n}<span class="bar"></span></th><th>Part</th><th class="cp">CA ${p}<span class="bar"></span></th><th>Part</th><th>Évo</th></tr></thead><tbody>${famRows||'<tr class="more"><td colspan="6">Aucune commande sur ces saisons.</td></tr>'}</tbody></table></div></section>
      <div id="fpsBox"></div>
      ${rsFicheHtml(cm)}
    </div></div>`;
  el.hidden=false;document.body.style.overflow='hidden';if(!G)renderNotes(c);FCH.all=false;bindFicheSeason(cm);FPS.se=null;bindFps(cm);
  el.querySelectorAll('.fty [data-ty]').forEach(b=>b.onclick=()=>{const v=b.dataset.ty;if(v==='all')F.sot.clear();else{const i=+v;F.sot.has(i)?F.sot.delete(i):F.sot.add(i)}
    updBtn('sot');const sc=el.querySelector('.fiche-b');const y=sc?sc.scrollTop:0;refresh();openFiche(c,g);const sc2=document.querySelector('#fiche .fiche-b');if(sc2)sc2.scrollTop=y});
  const fgs=document.getElementById('fgStores');if(fgs)fgs.addEventListener('click',e=>{if(e.target.closest('[data-fgall]')){FGS.all=true;openFiche(c,g);const x=document.getElementById('fgStores');if(x)x.scrollIntoView({block:'start'})}});
  const kb=$('#krBox');if(kb)kb.onclick=e=>{const b=e.target.closest('[data-kr]');if(!b)return;if(b.dataset.kr==='m')KR.allM=true;else KR.allL=true;kb.innerHTML=keyRefsHtml(KRd,G,n,p)};
  const close=()=>{el.hidden=true;el.innerHTML='';document.body.style.overflow=''};
  $('#ficheX').onclick=close;$('#ficheX').focus();
  const tb=el.querySelector('.fiche-trame');if(tb)tb.onclick=e=>{e.stopPropagation();EX.deck='trame';EX.mode=tb.dataset.tm;EX.sel=+tb.dataset.ts;EX.q='';openExport()};
  el.onclick=e=>{if(e.target===el)close()};
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!$('#fiche').hidden){$('#fiche').hidden=true;$('#fiche').innerHTML='';document.body.style.overflow=''}});
document.addEventListener('click',e=>{const gc=e.target.closest&&e.target.closest('.gchip[data-g],.gchip-l[data-g]');if(gc){const g=+gc.dataset.g;if(g>=0&&D.dicts.grp[g]&&D.dicts.grp[g]!=='(non renseigné)'){e.stopPropagation();e.preventDefault();openFiche(-1,g)}return}const f=e.target.closest&&e.target.closest('.fiche');if(f){e.stopPropagation();e.preventDefault();f.dataset.g!=null?openFiche(-1,+f.dataset.g):openFiche(+f.dataset.c)}},true);

/* ================= Trame RDV commercial (modèle PowerPoint LCS complété automatiquement) ================= */
const TR={bilan:null,vente:null};
function trSeasonShift(sn,d){/* d=-1 : même saison N-1 ; 'next' : saison suivante */
  const y=+sn.slice(0,4),fw=sn.endsWith('FW');
  if(d===-1)return(y-1)+sn.slice(4);
  return fw?(y+1)+'-01-SS':y+'-02-FW';
}
function trCm(mode,sel){const cm=new Uint8Array(D.dicts.cust.length);if(mode==='cust')cm[sel]=1;else for(let c=0;c<cm.length;c++)if(D.custGrpI[c]===sel)cm[c]=1;return cm}
function trPct(v,d){return v==null||!isFinite(v)?'—':(v*100).toLocaleString('fr-FR',{minimumFractionDigits:d||0,maximumFractionDigits:d||0})+' %'}
function trEvo(a,b){if(!b)return a>0?'nouveau':'—';const r=(a-b)/Math.abs(b);return(r>=0?'+':'−')+Math.abs(r*100).toLocaleString('fr-FR',{minimumFractionDigits:1,maximumFractionDigits:1})+' %'}
/* sell-out par référence pour une saison (Map code -> {q,st,e}) */
function trSO(cm,sn){const out=new Map();if(!SOD)return out;const si=SOD.seasons.indexOf(sn);if(si<0)return out;
  const inv=new Map();SOD.ci.forEach((d,c)=>{if(d>=0)inv.set(c,d)});const I=SOD.I;
  for(let i=0;i<I.q.length;i++){if(I.season[i]!==si)continue;const d=inv.get(I.cust[i]);if(d==null||!cm[d])continue;const code=SOD.item[I.item[i]];
    const o=out.get(code)||{q:0,st:0,e:0,fi:I.item[i]};o.q+=I.q[i];o.st+=I.st[i];o.e+=I.e[i];out.set(code,o)}
  return out}
const trRate=s=>s&&s.q+s.st>0?s.q/(s.q+s.st):null;

function trCompute(mode,sel){
  const ss=D.dicts.season,cm=trCm(mode,sel),cls=rsClass();
  const bn=TR.bilan,bp=trSeasonShift(bn,-1),vn=TR.vente;
  const sB=ss.indexOf(bn),sP=ss.indexOf(bp),sV=ss.indexOf(vn),sVP=ss.indexOf(trSeasonShift(vn,-1));
  const mSot=F.sot&&F.sot.size?(()=>{const m=new Uint8Array(D.dicts.sot.length);F.sot.forEach(i=>m[i]=1);return m})():null;
  const mPer=F.perim&&F.perim.size?(()=>{const m=new Uint8Array(D.dicts.perim.length);F.perim.forEach(i=>m[i]=1);return m})():null;
  const famIx=f=>{const nm=D.dicts.fam[f];return nm==='Textile'?0:nm==='Footwear'?1:nm==='Accessoires'?2:3};
  const X={caB:0,caP:0,caVP:0,famB:[0,0,0,0],famP:[0,0,0,0],dB:new Set(),dP:new Set(),ps:0,re:0,reItems:new Map(),vis:new Map(),itB:new Map(),itV:new Map(),segB:new Map()};
  const net=new Map(),netSeg=new Map();let netTot=0,cliTot=0;/* comparaison réseau pour la saison à vendre */
  for(let i=0;i<N;i++){const s=C.season[i];if(s!==sB&&s!==sP&&s!==sV&&s!==sVP)continue;if(mPer&&!mPer[C.perim[i]])continue;
    const c=C.cust[i],a=C.amt[i],q=C.qty[i],okSot=!mSot||mSot[C.sot[i]];
    if(s===sV&&okSot){const g=famIx(C.fam[i])*8+segOfRow(i);netSeg.set(g,(netSeg.get(g)||0)+a);netTot+=a;
      const o=net.get(C.item[i])||{a:0,d:new Set()};o.a+=a;o.d.add(c);net.set(C.item[i],o)}
    if(!cm[c])continue;
    if(s===sB){const t=cls[C.sot[i]];if(t===0||t===1){const o=X.vis.get(C.item[i])||{f:famIx(C.fam[i]),g:segOfRow(i),ps:0,re:0,a:0};if(t===0)o.ps+=q;else o.re+=q;o.a+=a;X.vis.set(C.item[i],o)}if(t===0)X.ps+=a;else if(t===1){X.re+=a;const o=X.reItems.get(C.item[i])||{q:0,a:0,psq:0};o.q+=q;o.a+=a;X.reItems.set(C.item[i],o)}
      if(t===0){const o=X.reItems.get(C.item[i]);if(o)o.psq+=q;else X.reItems.set(C.item[i],{q:0,a:0,psq:q})}}
    if(!okSot)continue;
    if(s===sB){X.caB+=a;X.famB[famIx(C.fam[i])]+=a;if(a>0)X.dB.add(c);const o=X.itB.get(C.item[i])||{q:0,a:0,f:famIx(C.fam[i])};o.q+=q;o.a+=a;X.itB.set(C.item[i],o)}
    else if(s===sP){X.caP+=a;X.famP[famIx(C.fam[i])]+=a;if(a>0)X.dP.add(c)}
    else if(s===sV){const o=X.itV.get(C.item[i])||{q:0,a:0,f:famIx(C.fam[i]),g:segOfRow(i)};o.q+=q;o.a+=a;X.itV.set(C.item[i],o);cliTot+=a;
      const g=famIx(C.fam[i])*8+segOfRow(i);X.segB.set(g,(X.segB.get(g)||0)+a)}
    else if(s===sVP)X.caVP+=a}
  /* commandé / facturé / reste à livrer (fichier facturation) */
  X.fa={b:0,i:0,r:0};
  if(FA){if(!FA.ci)FA.ci=FA.dicts.cust.map(c=>D.dicts.cust.indexOf(c));const se=FA.dicts.season.indexOf(bn);
    const al=F.sot&&F.sot.size?new Set([...F.sot].map(i=>D.dicts.sot[i])):null;
    for(let i=0;i<FA.n;i++){if(FA.C.season[i]!==se)continue;const dc=FA.ci[FA.C.cust[i]];if(dc<0||!cm[dc])continue;if(al&&!al.has(FA.dicts.sot[FA.C.sot[i]]))continue;X.fa.b+=FA.C.b[i];X.fa.i+=FA.C.i[i];X.fa.r+=FA.C.r[i]}}
  /* reste à livrer détaillé : retards, prochaines dates (bilan) + calendrier (saison à vendre) */
  X.late=new Map();X.lateTot=0;X.noArr=0;X.next=null;X.cal=[];
  if(ROB){if(!ROB.ci)ROB.ci=ROB.dicts.cust.map(c=>D.dicts.cust.indexOf(c));const today=ROB.asof,sb=ROB.dicts.season.indexOf(bn),sv=ROB.dicts.season.indexOf(vn);const cal=new Map();
    for(let i=0;i<ROB.n;i++){const se=ROB.C.season[i];if(se!==sb&&se!==sv)continue;const dc=ROB.ci[ROB.C.cust[i]];if(dc<0||!cm[dc])continue;const r=ROB.C.r[i];if(r<=0)continue;const d=ROB.dicts.rdd[ROB.C.rdd[i]];
      if(se===sv){if(d&&d!=='(non renseigné)'){const m=d.slice(0,7);cal.set(m,(cal.get(m)||0)+r)}continue}
      if(d&&d<today){X.lateTot+=r;const it=ROB.C.item[i];const o=X.late.get(it)||{r:0,d:d,cs:new Set()};o.r+=r;if(d<o.d)o.d=d;o.cs.add(ROB.C.cust[i]);X.late.set(it,o)}
      else if(d&&(!X.next||d<X.next))X.next=d}
    X.late.forEach((o,it)=>{let has=false;o.cs.forEach(c=>{const v=ROB.arr&&ROB.arr.get(ROB.dicts.cust[c]+'|'+ROB.dicts.item[it]);if(v&&v.max)has=true});if(!has)X.noArr++});
    X.cal=[...cal.entries()].sort((a,b)=>a[0]<b[0]?-1:1)}
  /* sell-out & stock magasin (saison bilan) */
  X.so=trSO(cm,bn);X.soV=trSO(cm,vn);
  X.stock=0;X.sold=0;X.so.forEach(o=>{X.stock+=o.st;X.sold+=o.q});
  X.weeks=SOD&&SOD.weeks&&SOD.weeks[bn]?SOD.weeks[bn].length:0;
  X.net=net;X.netSeg=netSeg;X.netTot=netTot;X.cliTot=cliTot;X.cm=cm;X.bn=bn;X.bp=bp;X.vn=vn;X.vp=trSeasonShift(vn,-1);
  return X;
}

/* vignette produit -> PNG pour la trame PowerPoint : image récupérée côté serveur (route lecoqsportif_image_base64,
   le navigateur ne peut pas lire une image d'un autre site pour la mettre dans un canvas), centrée sur fond blanc. */
const TR_IMG={};
function trThumb(code){code=String(code||'');if(!code||!SSG.imageBase64)return Promise.resolve(null);
  return TR_IMG[code]||(TR_IMG[code]=(async()=>{
    try{const r=await fetch(SSG.imageBase64.replace('ARTICLE_PLACEHOLDER',encodeURIComponent(code)),{credentials:'same-origin'});const d=await r.json();if(!d||!d.success)return null;
      const im=await new Promise(res=>{const i=new Image();i.onload=()=>res(i);i.onerror=()=>res(null);i.src='data:image/'+(d.imageType==='jpg'?'jpeg':d.imageType||'jpeg')+';base64,'+d.base64});if(!im)return null;
      const T=192,cv=document.createElement('canvas');cv.width=T;cv.height=T;const g=cv.getContext('2d');g.fillStyle='#fff';g.fillRect(0,0,T,T);g.imageSmoothingQuality='high';
      const k=Math.min(T/im.width,T/im.height),w=im.width*k,h=im.height*k;g.drawImage(im,(T-w)/2,(T-h)/2,w,h);return cv.toDataURL('image/png').split(',')[1]}
    catch(e){return null}})())}
/* graphique CA par famille N vs N-1 -> PNG */
function trChart(X){const W=1500,H=454,cv=document.createElement('canvas');cv.width=W;cv.height=H;const g=cv.getContext('2d');g.fillStyle='#fff';g.fillRect(0,0,W,H);
  const L=['Textile','Footwear','Accessoires'],ix=[0,1,2].filter(i=>X.famB[i]>0.5||X.famP[i]>0.5);const mx=Math.max(1,...ix.map(i=>Math.max(X.famB[i],X.famP[i])));
  const n=sLab(X.bn),p=sLab(X.bp),lx=230,rw=W-lx-330,rowH=(H-60)/Math.max(1,ix.length);
  g.font='600 30px Arial';g.fillStyle='#2a78d6';g.fillRect(lx,6,26,26);g.fillStyle='#101a2c';g.fillText(n,lx+36,30);g.fillStyle='#a23f7a';g.fillRect(lx+160,6,26,26);g.fillStyle='#101a2c';g.fillText(p,lx+196,30);
  ix.forEach((f,k)=>{const y=60+k*rowH;g.fillStyle='#101a2c';g.font='700 36px Arial';g.fillText(L[f],0,y+rowH/2+10);
    [[X.famB[f],'#2a78d6'],[X.famP[f],'#a23f7a']].forEach(([v,col],j)=>{const bh=Math.min(56,rowH*0.36),yy=y+rowH/2-bh-3+j*(bh+6),w=Math.max(3,v/mx*rw);g.fillStyle=col;g.fillRect(lx,yy,w,bh);g.fillStyle='#101a2c';g.font=(j?'400 ':'700 ')+'30px Arial';g.fillText(fM(v),lx+w+14,yy+bh/2+11)});
    g.font='700 32px Arial';const ev=trEvo(X.famB[f],X.famP[f]);g.fillStyle=ev.startsWith('+')||ev==='nouveau'?'#0b7f3a':'#c22f2f';g.fillText(ev,W-g.measureText(ev).width,y+rowH/2+10)});
  return cv.toDataURL('image/png').split(',')[1]}

/* valeurs des balises */
function trValues(X,mode,sel){
  const V={},code=it=>D.dicts.item[it],tc=(x,n)=>{x=String(x||'');return x.length>n?x.slice(0,n-1).trim()+'…':x},desc=it=>tc(D.itemDesc[it]||'',26);
  const n=sLab(X.bn),p=sLab(X.bp),v=sLab(X.vn);
  const sales=(()=>{const cnt=new Map();for(let c=0;c<X.cm.length;c++)if(X.cm[c]){const s=D.dicts.sales[D.custSalesI[c]];if(s&&s!=='(non renseigné)')cnt.set(s,(cnt.get(s)||0)+1)}return[...cnt.entries()].sort((a,b)=>b[1]-a[1]).map(x=>x[0])[0]||''})();
  const nSt=[...X.cm].reduce((s,x)=>s+x,0);
  V.CLIENT=mode==='cust'?(sel===CENT?'Centrale Intersport':'Client n° '+D.dicts.cust[sel]):'Groupement '+D.dicts.grp[sel];
  V.VILLE=mode==='cust'?(D.custCity[sel]||''):fN(nSt)+' magasins';
  V.SAISON_BILAN=n;V.SAISON_VENTE=v;V.COMMERCIAL=sales;
  V.DATE_RDV=new Date().toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'});
  /* 01 bilan */
  V.CA_COMMANDE=fE(X.fa.b||X.caB);V.CA_FACTURE=fE(X.fa.i);V.EVOL_CA=trEvo(X.caB,X.caP);
  V.PORTES=mode==='cust'?'1 magasin':fN(X.dB.size)+' actives ('+p+' : '+fN(X.dP.size)+')';
  const FL=['Textile','Footwear','Accessoires'];const fe=[0,1,2].filter(i=>X.famP[i]>0.5||X.famB[i]>0.5).map(i=>({f:FL[i],d:X.famB[i]-X.famP[i],r:X.famP[i]?(X.famB[i]-X.famP[i])/X.famP[i]:null,a:X.famB[i],b:X.famP[i]}));
  const best=[...fe].sort((a,b)=>b.d-a.d)[0],worst=[...fe].sort((a,b)=>a.d-b.d)[0];
  V.POINT_FORT_SAISON=best&&best.d>0?`Point fort : ${best.f} ${trEvo(best.a,best.b)} (${fM(best.a)} vs ${fM(best.b)})`:`Point fort : ${fM(X.caB)} commandés sur ${n}`;
  V.POINT_VIGILANCE_SAISON=worst&&worst.d<0?`Vigilance : ${worst.f} ${trEvo(worst.a,worst.b)} (${fM(worst.a)} vs ${fM(worst.b)})`:(X.lateTot>0.5?`Vigilance : ${fM(X.lateTot)} de livraisons en retard`:'Vigilance : aucun recul par famille');
  /* 02 commandes */
  V.CA_LIVRE=fE(Math.max(0,X.fa.b-X.fa.r));V.RAL=fE(X.fa.r);
  const pc=(a,b)=>b>0?Math.round(a/b*100)+' %':'—';
  V.TABLEAU_COMMANDES_OU_LIVRAISONS=[`Commandé ${fM(X.fa.b)} · livré ${pc(X.fa.b-X.fa.r,X.fa.b)} · facturé ${pc(X.fa.i,X.fa.b)}`,
    X.fa.r>0.5?`Reste à livrer ${fM(X.fa.r)}${X.lateTot>0.5?` dont ${fM(X.lateTot)} en retard`:''}${X.next?` · prochaine livraison demandée le ${fD(X.next)}`:''}`:'Tout est livré'];
  const late=[...X.late.entries()].sort((a,b)=>b[1].r-a[1].r);
  V.RELIQUATS_PRIORITAIRES=late.length?late.slice(0,2).map(([it,o])=>`${code(it)} ${desc(it)} : ${fM(o.r)} (dû le ${fD(o.d)})`):['Aucun reliquat en retard'];
  V.ANOMALIES_LIVRAISON=late.length?`${fN(late.length)} réf. en retard${X.noArr?`, dont ${fN(X.noArr)} sans date d'arrivée connue`:''}`:'Aucune anomalie de livraison';
  /* 03 rappel commandes saison à vendre, par famille */
  ['APP','FTW'].forEach((F2,fi)=>{const L=[...X.itV.entries()].filter(([,o])=>o.f===fi&&o.q>0).sort((a,b)=>b[1].q-a[1].q);
    const tq=L.reduce((s,[,o])=>s+o.q,0),ta=L.reduce((s,[,o])=>s+o.a,0);
    const so=[];L.forEach(([it])=>{const s=X.soV.get(code(it))||X.so.get(code(it));const r=trRate(s);if(r!=null)so.push(r)});
    V[`SS27_${F2}_TOTAL_QTE`]=fN(tq)+' pcs';V[`SS27_${F2}_TOTAL_CA`]=fE(ta);V[`SS27_${F2}_NB_REF`]=fN(L.length);
    V[`SS27_${F2}_SELLOUT_MOYEN`]=so.length?trPct(so.reduce((a,b)=>a+b,0)/so.length)+` (${so.length} réf. suivies)`:'nouvelle collection';
    for(let k=1;k<=8;k++){const e=L[k-1];if(!e){['PRODUIT','REF','QTE','SELLOUT','VISUEL'].forEach(t=>V[`SS27_${F2}_${t}_${k}`]='');V[`_EMPTY_${F2}_${k}`]=1;continue}
      const [it,o]=e,s=X.soV.get(code(it))||X.so.get(code(it)),r=trRate(s);
      V[`SS27_${F2}_PRODUIT_${k}`]=tc(D.itemDesc[it]||code(it),30);V[`SS27_${F2}_REF_${k}`]=code(it)+(D.itemDesc[it]?'':'');V[`SS27_${F2}_QTE_${k}`]=fN(o.q)+' pcs';
      V[`SS27_${F2}_SELLOUT_${k}`]=r==null?'nouveauté':trPct(r);V[`SS27_${F2}_VISUEL_${k}`]='';V[`_IMG_${F2}_${k}`]=code(it)}});
  /* 03 bis : rappel commandes saison bilan + taux de sortie */
  V.BIL_TITRE=`${n} · taux de sortie`;
  ['APP','FTW'].forEach((F2,fi)=>{const L=[...X.itB.entries()].filter(([,o])=>o.f===fi&&o.q>0).sort((a,b)=>b[1].q-a[1].q);
    const tq=L.reduce((s,[,o])=>s+o.q,0),ta=L.reduce((s,[,o])=>s+o.a,0);let sq=0,sst=0;L.forEach(([it])=>{const x=X.so.get(code(it));if(x){sq+=x.q;sst+=x.st}});
    V[`BIL_${F2}_TOTAL_QTE`]=fN(tq)+' pcs';V[`BIL_${F2}_TOTAL_CA`]=fE(ta);V[`BIL_${F2}_NB_REF`]=fN(L.length);
    V[`BIL_${F2}_SELLOUT_MOYEN`]=sq+sst>0?`${trPct(sq/(sq+sst))} · ${fN(sq)} vendues / ${fN(sst)} en stock`:'pas de remontée sell-out';
    const top=L.slice(0,8).sort((a,b)=>soCmp(X.so.get(code(a[0])),X.so.get(code(b[0])),a[1].q,b[1].q));
    for(let k=1;k<=8;k++){const e=top[k-1];if(!e){['PRODUIT','REF','QTE','SELLOUT','VISUEL'].forEach(t=>V[`BIL_${F2}_${t}_${k}`]='');V[`_EMPTY_B${F2}_${k}`]=1;continue}
      const [it,o]=e,x=X.so.get(code(it)),r=trRate(x),stt=stkState(x);
      V[`BIL_${F2}_PRODUIT_${k}`]=tc(D.itemDesc[it]||code(it),30);
      V[`BIL_${F2}_REF_${k}`]=code(it)+(x?` · vendu ${fN(x.q)} · stock ${fN(x.st)}${stt&&stt[0]!=='ok'?' · '+stt[1].toLowerCase():''}`:' · pas de remontée');
      V[`BIL_${F2}_QTE_${k}`]=fN(o.q)+' pcs';V[`BIL_${F2}_SELLOUT_${k}`]=r==null?'—':trPct(r);V[`BIL_${F2}_VISUEL_${k}`]='';V[`_IMG_B${F2}_${k}`]=code(it)}});
  /* 04 réassort */
  const reL=[...X.reItems.entries()].filter(([,o])=>o.q>0).sort((a,b)=>b[1].q-a[1].q);
  V.CA_PRE_SAISON=fE(X.ps);V.CA_REASSORT=fE(X.re);V.PART_REASSORT=X.ps+X.re>0?trPct(X.re/(X.ps+X.re),1):'—';V.NB_REF_REASSORT=fN(reL.length);
  V.TOP_REASSORT_REFERENCES=reL.length?reL.slice(0,3).map(([it,o])=>`${code(it)} ${desc(it)} : ${fN(o.q)} pcs réassorties (${fM(o.a)})`):['Aucun réassort sur la saison'];
  const reco=reL.filter(([it])=>{const r=trRate(X.so.get(code(it)));return r==null||r>=0.4}).slice(0,3);
  V.PRODUITS_A_RECONDUIRE=reco.length?'À reconduire : '+reco.map(([it])=>code(it)+' '+desc(it)).join(' · '):'À reconduire : —';
  const deep=reL.filter(([,o])=>o.psq>0&&o.q>=0.5*o.psq).slice(0,3);
  V.AJUSTEMENT_PROFONDEUR=deep.length?'Plus de profondeur en pré-saison : '+deep.map(([it,o])=>`${code(it)} (+${Math.round(o.q/o.psq*100)} % en réassort)`).join(' · '):'Profondeur pré-saison adaptée (réassort < 50 % de la PS)';
  /* 05 stocks */
  const st=[...X.so.entries()].map(([c,o])=>({c,o,k:stkState(o)}));
  const rup=st.filter(x=>x.k&&x.k[0]==='rup').sort((a,b)=>b.o.q-a.o.q),low=st.filter(x=>x.k&&x.k[0]==='low').sort((a,b)=>b.o.q-a.o.q),over=st.filter(x=>x.k&&x.k[0]==='over').sort((a,b)=>b.o.st-a.o.st);
  const dsc=c=>{const i=SOD.item.indexOf(c);return i>=0?tc(SOD.idesc[i]||'',24):''};
  const rate=X.weeks?X.sold/X.weeks:0;
  V.STOCK_MAGASIN=X.so.size?fN(X.stock)+' pcs':'non remonté';V.COUVERTURE=rate>0?Math.round(X.stock/rate)+' semaines':'—';
  V.NB_RUPTURES=X.so.size?fN(rup.length)+(low.length?` (+${low.length} stock faible)`:''):'—';V.STOCK_CENTRAL='à compléter';
  V.TABLEAU_STOCK_RUPTURES=X.so.size?[...[...rup,...low].slice(0,2).map(x=>x.k[0]==='rup'?`Rupture : ${x.c} ${dsc(x.c)} (${fN(x.o.q)} vendue${x.o.q>1?"s":""})`:`Stock faible : ${x.c} ${dsc(x.c)} (${fN(x.o.st)} pcs, écoulement ${trPct(trRate(x.o))})`),...over.slice(0,2).map(x=>`Surstock : ${x.c} ${dsc(x.c)} (${fN(x.o.st)} pcs, écoulement ${trPct(trRate(x.o))})`)]:['Pas de reporting sell-out pour ce client'];
  if(!V.TABLEAU_STOCK_RUPTURES.length)V.TABLEAU_STOCK_RUPTURES=['Aucune rupture ni surstock'];
  V.ACTIONS_SURSTOCK=over.length?`${over.length} réf. en surstock (${fN(over.reduce((s,x)=>s+x.o.st,0))} pcs) : mise en avant / opération`:'Aucun surstock';
  V.ACTIONS_RUPTURE=rup.length+low.length?`Réassortir : ${[...rup,...low].slice(0,4).map(x=>x.c).join(', ')}`:'Aucune rupture à traiter';
  /* 06 best-sellers */
  const bs=st.filter(x=>x.o.q>0).sort((a,b)=>b.o.q-a.o.q||trRate(b.o)-trRate(a.o));
  for(let k=1;k<=3;k++){const x=bs[k-1];V['BEST_SELLER_'+k]=x?x.c:'—';
    const ord=x?(()=>{const i=D.dicts.item.indexOf(x.c);const o=i>=0?X.itB.get(i):null;return o?o.q:0})():0;
    V['_BSL_'+k]=x?`${dsc(x.c)}\nCommandé ${fN(ord)} · vendu ${fN(x.o.q)} · écoulement ${trPct(trRate(x.o))}`:'';V['_BSIMG_'+k]=x?x.c:null}
  const weak=st.filter(x=>x.o.q+x.o.st>=10&&trRate(x.o)<0.2).sort((a,b)=>trRate(a.o)-trRate(b.o));
  V.REFERENCES_A_RECONDUIRE_OU_A_REVOIR=[`À reconduire : ${bs.slice(0,3).map(x=>x.c).join(', ')||'—'}`,`À revoir : ${weak.slice(0,3).map(x=>`${x.c} (${trPct(trRate(x.o))})`).join(', ')||'—'}`];
  /* 07 opportunités */
  V.BEST_SELLERS_A_RECONDUIRE='Best-sellers : '+(bs.slice(0,3).map(x=>x.c).join(', ')||'—');
  V.TAILLES_FORTES='Tailles fortes : à compléter (tailles non disponibles dans les données)';
  const GL=['Homme','Femme','Unisexe','Enfant','Autre'],FN=['Textile','Chaussure','Accessoires',''];
  const under=[...X.netSeg.entries()].filter(([g,a])=>a/X.netTot>=0.05).map(([g,a])=>({g,net:a/X.netTot,cli:X.cliTot?(X.segB.get(g)||0)/X.cliTot:0})).filter(x=>x.cli<x.net*0.6).sort((a,b)=>(b.net-b.cli)-(a.net-a.cli)).slice(0,2);
  V.CATEGORIES_SOUS_REPRESENTEES=under.length?'Sous-représenté : '+under.map(x=>`${FN[x.g>>3]} ${GL[x.g&7]} (${trPct(x.cli)} vs ${trPct(x.net)} réseau)`).join(' · '):'Mix '+v+' en ligne avec le réseau';
  const nouv=[...X.net.entries()].filter(([it])=>!X.itV.has(it)).sort((a,b)=>b[1].d.size-a[1].d.size).slice(0,2);
  V.NOUVEAUTES_PERTINENTES=nouv.length?'Top réseau non commandés : '+nouv.map(([it,o])=>`${code(it)} ${desc(it)} (${fN(o.d.size)} portes)`).join(' · '):'—';
  V.SURSTOCKS_A_EVITER='Surstocks : '+(over.slice(0,3).map(x=>x.c).join(', ')||'aucun');
  V.RUPTURES_A_PREVENIR='Ruptures : '+([...rup,...low].slice(0,3).map(x=>x.c).join(', ')||'aucune');
  V._UNDER=under.length?FN[under[0].g>>3]+' '+GL[under[0].g&7]:null;
  /* 08 proposition */
  const vq=[...X.itV.values()].reduce((s,o)=>s+o.q,0),va=[...X.itV.values()].reduce((s,o)=>s+o.a,0);
  V.BUDGET_CIBLE=X.caVP?`${fM(X.caVP)} (= ${sLab(X.vp)})`:'à définir';
  V.TOTAL_PANIER=va>0?fE(va):'0 €';V.TOTAL_UNITES=fN(vq)+' pcs';
  const MN=['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];const ml=m=>MN[+m.slice(5,7)-1]+' '+m.slice(2,4);
  V.CALENDRIER=X.cal.length?(X.cal.length===1?ml(X.cal[0][0]):ml(X.cal[0][0])+' → '+ml(X.cal[X.cal.length-1][0])):'à définir';
  V.TABLEAU_REFERENCES_TAILLES_QUANTITES=[...X.itV.entries()].sort((a,b)=>b[1].q-a[1].q).slice(0,5).map(([it,o])=>`${code(it)} ${desc(it)} : ${fN(o.q)} pcs · ${fM(o.a)}`);
  if(!V.TABLEAU_REFERENCES_TAILLES_QUANTITES.length)V.TABLEAU_REFERENCES_TAILLES_QUANTITES=['Panier à construire en rendez-vous'];
  /* 09 synthèse */
  V.CONSTAT_1=`${n} : ${fM(X.caB)} commandés, ${trEvo(X.caB,X.caP)} vs ${p}`;
  V.CONSTAT_2=X.so.size?`Sell-out ${trPct(X.sold+X.stock?X.sold/(X.sold+X.stock):null)} · ${fN(X.stock)} pcs en stock · ${rup.length} rupture(s), ${over.length} surstock(s)`:`Reste à livrer ${fM(X.fa.r)}${X.lateTot>0.5?' dont '+fM(X.lateTot)+' en retard':''}`;
  V.OPPORTUNITE_PRIORITAIRE=V._UNDER?`Développer ${V._UNDER} sur ${v}`:bs.length?`Reconduire les best-sellers (${bs.slice(0,2).map(x=>x.c).join(', ')})`:`Construire la commande ${v}`;
  V.DECISION_COMMANDE='Décision commande : à compléter en rendez-vous';
  V.ACTION_CLIENT_ET_DATE='Action client : … (date)';V.ACTION_COMMERCIAL_ET_DATE='Action commercial : … (date)';
  return V;
}

/* ---- remplissage du modèle ---- */
const TR_DROP=['Mettre en avant les écarts et dates attendues','Quantités, fréquence et disponibilité','Identifier surstocks, ruptures et tailles manquantes','Running / Training / Lifestyle / autres'];
const trEsc=s=>String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
const NS_A='http://schemas.openxmlformats.org/drawingml/2006/main',NS_P='http://schemas.openxmlformats.org/presentationml/2006/main',NS_R='http://schemas.openxmlformats.org/officeDocument/2006/relationships';
function trPicXml(id,rid,x,y,w,h,name){x=Math.round(x);y=Math.round(y);w=Math.round(w);h=Math.round(h);return`<p:pic xmlns:p="${NS_P}" xmlns:a="${NS_A}" xmlns:r="${NS_R}"><p:nvPicPr><p:cNvPr id="${id}" name="${name}"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr><p:blipFill><a:blip r:embed="${rid}"/><a:stretch><a:fillRect/></a:stretch></p:blipFill><p:spPr><a:xfrm><a:off x="${x}" y="${y}"/><a:ext cx="${w}" cy="${h}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr></p:pic>`}
/* ---- diapos « vue visuelle » : commandes saison bilan par famille × Homme/Femme/Enfant, triées par taux de sortie ---- */
function trVisGroups(X,vis,so){
  const FAMS=['Textile','Chaussures','Accessoires'],GL=['Homme','Femme','Unisexe','Enfant','Autre'],GO=[0,1,3,2,4],out=[];
  for(let f=0;f<3;f++)for(const g of GO){
    const qOf=o=>o.q!=null?o.q:o.ps+o.re;const SORTQ=arguments[3];const L=[...vis.entries()].filter(([,o])=>o.f===f&&o.g===g&&qOf(o)>0).map(([it,o])=>({it,code:D.dicts.item[it],desc:D.itemDesc[it]||'',q:qOf(o),a:o.a,s:so.get(D.dicts.item[it])||null}));
    if(!L.length)continue;L.sort(SORTQ?(a,b)=>b.q-a.q:(a,b)=>soCmp(a.s,b.s,a.q,b.q));const L2=gmSort(L,x=>x.it,x=>x.q,x=>x.desc);L.length=0;L.push(...L2);
    const st=[0,0,0,0];let stq=0;L.forEach(x=>{if(x.s){stq+=x.s.st;const k=stkState(x.s);if(k)st[k[2]]++}});
    out.push({f,fam:FAMS[f],seg:GL[g],items:L,q:L.reduce((s,x)=>s+x.q,0),a:L.reduce((s,x)=>s+x.a,0),stq,st,hasSo:L.some(x=>x.s)})}
  return out}
const TRV={per:12,maxPages:4};
function trRun(t,o){o=o||{};const f=o.font||'Jost';return`<a:r><a:rPr lang="fr-FR" sz="${o.sz||1100}"${o.b?' b="1"':''}${o.spc?` spc="${o.spc}"`:''} dirty="0"><a:solidFill><a:srgbClr val="${o.col||'000000'}"/></a:solidFill><a:latin typeface="${f}" pitchFamily="34" charset="0"/><a:cs typeface="${f}" pitchFamily="34" charset="-120"/></a:rPr><a:t>${trEsc(t)}</a:t></a:r>`}
function trTx(id,x,y,w,h,runs,o){o=o||{};return`<p:sp><p:nvSpPr><p:cNvPr id="${id}" name="${o.name||'Texte'}"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="${Math.round(x)}" y="${Math.round(y)}"/><a:ext cx="${Math.round(w)}" cy="${Math.round(h)}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:noFill/></p:spPr><p:txBody><a:bodyPr wrap="${o.wrap||'none'}" lIns="0" tIns="0" rIns="0" bIns="0" anchor="${o.anchor||'ctr'}"><a:noAutofit/></a:bodyPr><a:lstStyle/><a:p><a:pPr algn="${o.algn||'l'}"><a:buNone/></a:pPr>${runs}</a:p></p:txBody></p:sp>`}
function trRect(id,x,y,w,h,fill,o){o=o||{};return`<p:sp><p:nvSpPr><p:cNvPr id="${id}" name="${o.name||'Forme'}"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="${Math.round(x)}" y="${Math.round(y)}"/><a:ext cx="${Math.max(1,Math.round(w))}" cy="${Math.max(1,Math.round(h))}"/></a:xfrm><a:prstGeom prst="${o.geom||'rect'}"><a:avLst>${o.adj!=null?`<a:gd name="adj" fmla="val ${o.adj}"/>`:''}</a:avLst></a:prstGeom>${fill?`<a:solidFill><a:srgbClr val="${fill}"/></a:solidFill>`:'<a:noFill/>'}${o.line?`<a:ln w="12700"><a:solidFill><a:srgbClr val="${o.line}"/></a:solidFill></a:ln>`:'<a:ln><a:noFill/></a:ln>'}</p:spPr>${o.text||''}</p:sp>`}
async function trVisualSlides(zip,X,startNo,cfg){
  const groups=trVisGroups(X,cfg.vis,cfg.so,cfg.sortQ);if(!groups.length)return[];
  const base=await zip.file('ppt/slides/slide5.xml').async('string'),baseRel=await zip.file('ppt/slides/_rels/slide5.xml.rels').async('string');
  const KEEP=new Set(['Shape 0','Shape 1','Image 0','Text 2','Text 3','Numéro','Titre','Pied de page']);
  const STK={rup:['FDE2E4','B4232F'],low:['FFF1D6','9A5B00'],over:['E9E2FB','5A3EA8'],ok:['E1F5EA','1B7A4C']};
  const FCOL=['2A78D6','A23F7A','1F9D6B'];const n=sLab(X.bn);
  const created=[];let no=startNo,img=0;
  for(const G of groups){
    const pages=Math.min(cfg.cap,Math.ceil(G.items.length/TRV.per));
    for(let pg=0;pg<pages;pg++){
      const items=G.items.slice(pg*TRV.per,(pg+1)*TRV.per);
      const doc=new DOMParser().parseFromString(base,'application/xml');const rel=new DOMParser().parseFromString(baseRel,'application/xml');
      const tree=doc.getElementsByTagNameNS(NS_P,'spTree')[0];
      [...tree.children].forEach(ch=>{if(ch.localName!=='sp'&&ch.localName!=='pic')return;const c=ch.getElementsByTagNameNS(NS_P,'cNvPr')[0];if(!c||!KEEP.has(c.getAttribute('name')))tree.removeChild(ch)});
      [...tree.getElementsByTagNameNS(NS_P,'cNvPr')].forEach(c=>{const nm=c.getAttribute('name');const sp=c.parentNode.parentNode;const ts=sp.getElementsByTagNameNS(NS_A,'t');
        if(nm==='Numéro'&&ts.length>1){ts[1].textContent='   '+G.fam.toUpperCase()+' · '+G.seg.toUpperCase()}
        if(nm==='Titre'&&ts.length){ts[0].textContent=cfg.title+(pages>1?` (${pg+1}/${pages})`:'')}});
      let id=3000,xml='';const RID=[];
      /* en-tête du groupe */
      const chips=[[G.st[0],'rupture'+(G.st[0]>1?'s':''),'rup'],[G.st[1],'stock faible','low'],[G.st[3],'surstock','over']].filter(x=>x[0]>0);
      xml+=trRect(id++,1047750,2370000,16192500,25400,FCOL[G.f]);
      xml+=trTx(id++,1047750,2430000,16192500,400000,trRun(G.seg.toUpperCase(),{sz:1800,b:1,spc:100})+trRun(`    ${fN(G.items.length)} réf. · ${fN(G.q)} pcs · ${fM(G.a)}${G.hasSo?` · stock magasin ${fN(G.stq)} pcs`:''}`,{sz:1300,col:'6B7280'})+chips.map(c=>trRun(`    ${c[0]} ${c[1]}`,{sz:1300,b:1,col:STK[c[2]][1]})).join(''),{});
      if(G.items.length>TRV.per*pages&&pg===pages-1)xml+=trTx(id++,1047750,2430000,16192500,400000,trRun(`+ ${fN(G.items.length-TRV.per*pages)} autres réf. (taux de sortie les plus faibles)`,{sz:1100,col:'6B7280'}),{algn:'r'});
      /* cartes */
      const cols=6,gap=180000,W=(16192500-gap*(cols-1))/cols,H=3150000,y0=2950000;
      for(let k=0;k<items.length;k++){const it=items[k],cx=1047750+(k%cols)*(W+gap),cy=y0+Math.floor(k/cols)*(H+150000),pad=100000,iw=W-2*pad;
        xml+=trRect(id++,cx,cy,W,H,'FFFFFF',{geom:'roundRect',adj:6000,line:'DDE3EC',name:'Carte'});
        xml+=trRect(id++,cx+pad,cy+pad,iw,1200000,'F4F6FA',{geom:'roundRect',adj:5000});if(it.__gm)xml+=trTx(id++,cx+pad+50000,cy+pad+40000,iw-100000,170000,trRun(it.__gm.toUpperCase(),{sz:750,b:1,col:'8A93A3',spc:60}),{anchor:'t'});
        const png=await trThumb(it.code);if(png){img++;const path=`ppt/media/trv_${no}_${img}.png`;zip.file(path,png,{base64:true});const rid='rIdV'+img;RID.push([rid,`../media/trv_${no}_${img}.png`]);
          xml+=`<p:pic><p:nvPicPr><p:cNvPr id="${id++}" name="Visuel ${trEsc(it.code)}"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr><p:blipFill><a:blip r:embed="${rid}"/><a:stretch><a:fillRect/></a:stretch></p:blipFill><p:spPr><a:xfrm><a:off x="${Math.round(cx+(W-1000000)/2)}" y="${cy+pad+175000}"/><a:ext cx="1000000" cy="1000000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></p:spPr></p:pic>`}
        const tx=cx+pad;let y=cy+pad+1250000;
        xml+=trTx(id++,tx,y,iw,280000,trRun(it.code,{sz:1400,b:1}));y+=280000;
        xml+=trTx(id++,tx,y,iw,240000,trRun(it.desc.length>30?it.desc.slice(0,29).trim()+'…':it.desc,{sz:1000,col:'6B7280'}));y+=270000;
        xml+=trTx(id++,tx,y,iw,320000,trRun(fN(it.q)+' pcs',{sz:1600,b:1})+trRun('   '+fE(it.a),{sz:1050,col:'6B7280'}));y+=360000;
        const k2=stkState(it.s);
        if(k2){const c=STK[k2[0]],tw=k2[1].length*62000+160000;xml+=trRect(id++,tx,y,tw,230000,c[0],{geom:'roundRect',adj:20000,text:`<p:txBody><a:bodyPr lIns="0" tIns="0" rIns="0" bIns="0" anchor="ctr"/><a:lstStyle/><a:p><a:pPr algn="ctr"/>${trRun(k2[1],{sz:950,b:1,col:c[1]})}</a:p></p:txBody>`})}
        y+=290000;xml+=trRect(id++,tx,y,iw,9525,'E5E7EB');y+=40000;
        if(it.s){const r=trRate(it.s),rc=r==null?'000000':r>=0.6?'0B7F3A':r<0.3?'C22F2F':'000000';
          xml+=trTx(id++,tx,y,iw,240000,trRun('Vendu ',{sz:1000})+trRun(fN(it.s.q),{sz:1000,b:1})+trRun('    Stock ',{sz:1000})+trRun(fN(it.s.st),{sz:1000,b:1}));y+=270000;
          const bw=iw-620000;xml+=trRect(id++,tx,y+60000,bw,70000,'E5E7EB',{geom:'roundRect',adj:50000});if(r)xml+=trRect(id++,tx,y+60000,bw*r,70000,'0B7F3A',{geom:'roundRect',adj:50000});
          xml+=trTx(id++,tx+iw-580000,y-30000,580000,250000,trRun(r==null?'—':Math.round(r*100)+' %',{sz:1300,b:1,col:rc}),{algn:'r'})}
        else xml+=trTx(id++,tx,y,iw,240000,trRun(cfg.noSo||'pas de vente remontée',{sz:1000,col:cfg.noSo?'5D1E26':'6B7280',b:!!cfg.noSo}))}
      const frag=new DOMParser().parseFromString(`<p:spTree xmlns:p="${NS_P}" xmlns:a="${NS_A}" xmlns:r="${NS_R}">${xml}</p:spTree>`,'application/xml').documentElement;
      [...frag.children].forEach(ch=>tree.appendChild(doc.importNode(ch,true)));
      RID.forEach(([rid,t])=>{const r=rel.createElementNS('http://schemas.openxmlformats.org/package/2006/relationships','Relationship');r.setAttribute('Id',rid);r.setAttribute('Type','http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');r.setAttribute('Target',t);rel.documentElement.appendChild(r)});
      zip.file(`ppt/slides/slide${no}.xml`,new XMLSerializer().serializeToString(doc));zip.file(`ppt/slides/_rels/slide${no}.xml.rels`,new XMLSerializer().serializeToString(rel));
      created.push(no);no++}}
  return created}

async function trFill(V,X,onMsg){
  const b64=document.getElementById('trame64').textContent.trim();
  const zip=await JSZip.loadAsync(b64,{base64:true});
  onMsg&&onMsg('Vignettes produits '+sLab(X.bn)+'…');const VIS=await trVisualSlides(zip,X,13,{vis:X.vis,so:X.so,cap:Infinity,title:`Commandes ${sLab(X.bn)} · taux de sortie`});
  onMsg&&onMsg('Vignettes produits '+sLab(X.vn)+'…');const VEN=await trVisualSlides(zip,X,13+VIS.length,{vis:X.itV,so:X.soV.size?X.soV:X.so,cap:Infinity,sortQ:true,title:`Rappel commandes ${sLab(X.vn)}`,noSo:'nouveauté'});
  const DROP=VEN.length?[5,6]:[];
  const ORDER=[1,2,3,4,...VIS,...(VEN.length?VEN:[5,6]),7,8,9,10,11,12];
  {let ct=await zip.file('[Content_Types].xml').async('string'),pr=await zip.file('ppt/_rels/presentation.xml.rels').async('string'),pp=await zip.file('ppt/presentation.xml').async('string');
   [...VIS,...VEN].forEach(d=>{ct=ct.replace('</Types>',`<Override PartName="/ppt/slides/slide${d}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/></Types>`);
     pr=pr.replace('</Relationships>',`<Relationship Id="rIdS${d}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide${d}.xml"/></Relationships>`)});
   DROP.forEach(d=>{ct=ct.replace(`<Override PartName="/ppt/slides/slide${d}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>`,'');
     pr=pr.replace(`<Relationship Id="rIdS${d}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide${d}.xml"/>`,'');zip.remove(`ppt/slides/slide${d}.xml`);zip.remove(`ppt/slides/_rels/slide${d}.xml.rels`)});
   pp=pp.replace(/<p:sldIdLst>[\s\S]*?<\/p:sldIdLst>/,'<p:sldIdLst>'+ORDER.map(d=>`<p:sldId id="${1000+d}" r:id="rIdS${d}"/>`).join('')+'</p:sldIdLst>');
   zip.file('[Content_Types].xml',ct);zip.file('ppt/_rels/presentation.xml.rels',pr);zip.file('ppt/presentation.xml',pp)}
  const slides=Object.keys(zip.files).filter(f=>/^ppt\/slides\/slide\d+\.xml$/.test(f)).sort((a,b)=>+a.match(/\d+/)[0]-+b.match(/\d+/)[0]);
  let media=0,nid=5000;const vente=sLab(X.vn);
  for(const f of slides){
    const sn=+f.match(/slide(\d+)\.xml/)[1];onMsg&&onMsg('Diapositive '+(ORDER.indexOf(sn)+1)+' / '+slides.length+'…');
    const doc=new DOMParser().parseFromString(await zip.file(f).async('string'),'application/xml');
    const relF=f.replace('slides/','slides/_rels/')+'.rels';const rel=new DOMParser().parseFromString(await zip.file(relF).async('string'),'application/xml');
    let rn=100;const addImg=async(b64png)=>{media++;const path='ppt/media/trame_'+media+'.png';zip.file(path,b64png,{base64:true});const id='rIdT'+(rn++);
      const r=rel.createElementNS('http://schemas.openxmlformats.org/package/2006/relationships','Relationship');r.setAttribute('Id',id);r.setAttribute('Type','http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');r.setAttribute('Target','../media/trame_'+media+'.png');rel.documentElement.appendChild(r);return id};
    const spName=sp=>{const c=sp.getElementsByTagNameNS(NS_P,'cNvPr')[0];return c?c.getAttribute('name'):''};
    const xfrm=sp=>{const o=sp.getElementsByTagNameNS(NS_A,'off')[0],e=sp.getElementsByTagNameNS(NS_A,'ext')[0];return o&&e?[+o.getAttribute('x'),+o.getAttribute('y'),+e.getAttribute('cx'),+e.getAttribute('cy')]:null};
    const insertPic=(after,b64id,x,y,w,h,nm)=>{const frag=new DOMParser().parseFromString(trPicXml(nid++,b64id,x,y,w,h,nm),'application/xml').documentElement;after.parentNode.insertBefore(doc.importNode(frag,true),after.nextSibling)};
    const sps=[...doc.getElementsByTagNameNS(NS_P,'sp')];
    /* images : vignettes produits (diapos rappel commandes) */
    for(const sp of sps){const nm=spName(sp);const m=nm.match(/^Emplacement visuel (\d+)$/);if(!m)continue;
      const tts=[...doc.getElementsByTagNameNS(NS_A,'t')];const pre=tts.some(t=>t.textContent.includes('{{BIL_'))?'B':'';const fam=tts.some(t=>t.textContent.includes('_FTW_'))?'FTW':'APP';const k=+m[1];const code=V[`_IMG_${pre}${fam}_${k}`];
      if(code){const png=await trThumb(code);if(png){const [x,y,w,h]=xfrm(sp);const s=h-120000;insertPic(sp,await addImg(png),x+(w-s)/2,y+60000,s,s,'Visuel '+code)}}
      if(V[`_EMPTY_${pre}${fam}_${k}`]){const prev=sp.previousElementSibling;if(prev&&/^Visuel produit/.test(spName(prev))){const sf=prev.getElementsByTagNameNS(NS_A,'solidFill')[0];if(sf)prev.parentNode.removeChild(prev)}}}
    /* graphique CA par famille (diapo bilan) */
    for(const sp of sps){if(spName(sp)!=='Gauche contenu')continue;if(!sp.textContent.includes('CA par catégorie'))continue;
      const [x,y,w,h]=xfrm(sp);insertPic(sp,await addImg(trChart(X)),x,y,w,Math.round(w*454/1500));sp.parentNode.removeChild(sp)}
    /* best-sellers : vignettes sous le texte */
    if(sn&&[...doc.getElementsByTagNameNS(NS_A,'t')].some(t=>t.textContent.includes('{{BEST_SELLER_1}}'))){
      const vals=[...doc.getElementsByTagNameNS(NS_P,'sp')].filter(sp=>/\{\{BEST_SELLER_\d\}\}/.test(sp.textContent));
      for(const sp of vals){const k=+sp.textContent.match(/BEST_SELLER_(\d)/)[1];const code=V['_BSIMG_'+k];if(!code)continue;const png=await trThumb(code);if(!png)continue;const [x,y,w,h]=xfrm(sp);
        const sz=1300000;insertPic(sp,await addImg(png),x+w-sz,2780000,sz,sz,'Visuel '+code);const e=sp.getElementsByTagNameNS(NS_A,'ext')[0];e.setAttribute('cx',String(w-sz-150000))}}
    /* textes */
    {const pos=ORDER.indexOf(sn)+1;for(const sp of [...doc.getElementsByTagNameNS(NS_P,'sp')]){if(spName(sp)!=='Text 3')continue;const t=sp.getElementsByTagNameNS(NS_A,'t')[0];if(t&&/^\d\d$/.test(t.textContent.trim()))t.textContent=String(pos).padStart(2,'0')}}
    let bsl=0;
    for(const t of [...doc.getElementsByTagNameNS(NS_A,'t')]){const tx=t.textContent;
      if(TR_DROP.includes(tx.trim())){const p=t.closest?t.parentNode.parentNode:null;if(p&&p.localName==='p')p.parentNode.removeChild(p);continue}
      if(tx==='Graphique à alimenter automatiquement'){t.textContent=`CA par famille · ${sLab(X.bn)} vs ${sLab(X.bp)}`;continue}
      if(tx.includes('Rappel commandes SS27')){t.textContent=tx.replace('SS27',vente);continue}
      if(!tx.includes('{{'))continue;
      if(tx.includes('{{QTÉ / SELL-OUT / TAUX ÉCOULEMENT}}')){bsl++;const parts=(V['_BSL_'+bsl]||'').split('\n');t.textContent=parts[0];if(parts[1]){const r=t.parentNode,br=doc.createElementNS(NS_A,'a:br'),r2=r.cloneNode(true);r2.getElementsByTagNameNS(NS_A,'t')[0].textContent=parts[1];const rp=r.getElementsByTagNameNS(NS_A,'rPr')[0];if(rp)br.appendChild(rp.cloneNode(true));r.parentNode.insertBefore(br,r.nextSibling);r.parentNode.insertBefore(r2,br.nextSibling)}continue}
      const one=tx.match(/^\s*\{\{([^}]+)\}\}\s*$/);
      if(one&&Array.isArray(V[one[1]])){const arr=V[one[1]],r=t.parentNode,p=r.parentNode;
        let last=p;arr.forEach((s,i)=>{if(i===0){t.textContent=s;return}const c=p.cloneNode(true);const ts=c.getElementsByTagNameNS(NS_A,'t');for(let j=0;j<ts.length;j++)ts[j].textContent=j===0?s:'';p.parentNode.insertBefore(c,last.nextSibling);last=c});
        /* garder l'ordre */
        continue}
      t.textContent=tx.replace(/\{\{([^}]+)\}\}/g,(m,k)=>{const v=V[k];return v==null?'':Array.isArray(v)?v.join(' · '):String(v)})}
    /* remettre l'ordre des paragraphes clonés (insérés en ordre inverse) — fait ci-dessus via nextSibling, on corrige ici */
    zip.file(f,new XMLSerializer().serializeToString(doc));zip.file(relF,new XMLSerializer().serializeToString(rel));
  }
  return zip.generateAsync({type:'blob',mimeType:'application/vnd.openxmlformats-officedocument.presentationml.presentation',compression:'DEFLATE'});
}
async function runTrame(){
  const msg=$('#exMsg'),btn=$('#exGo');btn.disabled=true;msg.textContent='Préparation de la trame…';
  try{
    try{localStorage.setItem('lcs-export',JSON.stringify({mode:EX.mode,sel:EX.sel,deck:EX.deck}))}catch(e){}
    const X=trCompute(EX.mode,EX.sel);
    if(!X.caB&&!X.caP&&!X.itV.size){msg.textContent=`Aucune commande sur ${sLab(TR.bilan)}, ${sLab(trSeasonShift(TR.bilan,-1))} ni ${sLab(TR.vente)} pour cette sélection.`;btn.disabled=false;return}
    const V=trValues(X,EX.mode,EX.sel);
    const blob=await trFill(V,X,t=>msg.textContent=t);
    const nm=(EX.mode==='cust'?'Client_'+D.dicts.cust[EX.sel]:D.dicts.grp[EX.sel]);
    const fname=('RDV commercial '+nm+' '+sLab(TR.bilan)+' - '+sLab(TR.vente)).replace(/[\\/:*?"<>|]+/g,' ').replace(/\s+/g,'_')+'.pptx';
    window.__TRBLOB=blob;await fileSave(fname,blob,msg);
  }catch(err){const code=err&&err.code;msg.textContent=code==='declined'?'Téléchargement annulé.':'Erreur pendant la génération de la trame ('+(err&&err.message||code||'inconnue')+').';console.error('Trame',err)}
  btn.disabled=false;
}

/* ---------- export PowerPoint ---------- */
const EX={mode:'cust',sel:null,q:'',deck:'trame'};
try{const s=JSON.parse(localStorage.getItem('lcs-export')||'null');if(s&&s.mode){EX.mode=s.mode;EX.sel=s.sel;if(s.deck)EX.deck=s.deck}}catch(e){}
const EXMODES=[['cust','Client'],['grp','Groupement'],['cg','Groupe client'],['sales','Commercial'],['cur','Sélection actuelle']];
function exOptions(mode,q){
  q=(q||'').trim().toLowerCase();const out=[];
  if(mode==='cust'){for(let c=0;c<D.dicts.cust.length&&out.length<40;c++){const t=(D.dicts.cust[c]+' '+(D.custCity[c]||'')+' '+(D.custGrp[c]||'')+' '+D.dicts.sales[D.custSalesI[c]]).toLowerCase();if(!q||t.includes(q))out.push([c,cLabel('cust',c).replace(/<button[^>]*>.*?<\/button>/g,'')])}}
  else if(mode!=='cur'){const dd=D.dicts[mode];dd.forEach((v,i)=>{if(v==='(non renseigné)')return;if(!q||v.toLowerCase().includes(q))out.push([i,esc(v)])})}
  return out.slice(0,60);
}
function exName(mode,sel){
  if(mode==='cur')return'Sélection actuelle';if(sel==null)return'';
  if(mode==='cust')return sel===CENT?'Centrale Intersport':'Client n° '+D.dicts.cust[sel]+(D.custCity[sel]?' – '+D.custCity[sel]:'');
  return D.dicts[mode][sel];
}
function openExport(){
  const el=$('#exp');const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const draw=()=>{
    const T=EX.deck==='trame';if(T&&EX.mode!=='cust'&&EX.mode!=='grp'){EX.mode='cust';EX.sel=null}
    if(!TR.bilan){const ss=D.dicts.season,cs=curSeasonName();TR.bilan=ss.includes(cs)?cs:ss[S.n];TR.vente=trSeasonShift(TR.bilan,'next');if(!ss.includes(TR.vente))TR.vente=TR.bilan}
    const ssOpt=sel=>D.dicts.season.slice(-8).map(x=>`<option value="${x}" ${x===sel?'selected':''}>${sLab(x)}${x===curSeasonName()?' (en cours)':''}</option>`).join('');
    const opts=exOptions(EX.mode,EX.q);
    el.innerHTML=`<div class="exp-panel" role="dialog" aria-modal="true" aria-labelledby="expT">
      <div class="fiche-h"><div><div class="tl">Export PowerPoint</div><h2 id="expT">Préparer une présentation</h2><div class="fiche-info"><span><span class="tl">Saisons</span> ${n} vs ${p}</span><span><span class="tl">Types</span> ${typeLab()}</span>${F.perim.size?'<span><span class="tl">Périmètre</span> BOH</span>':''}</div></div><button type="button" class="close" id="expX" aria-label="Fermer">×</button></div>
      <div class="fiche-b">
        <div><div class="tl" style="margin-bottom:6px">Modèle</div><div class="seg" id="exD"><button type="button" data-d="trame" aria-pressed="${T}">Trame RDV commercial</button><button type="button" data-d="bilan" aria-pressed="${!T}">Bilan détaillé</button></div></div>
        ${T?`<div class="ex-ss"><label>Saison analysée (bilan)<select id="exSB">${ssOpt(TR.bilan)}</select></label><label>Saison à vendre<select id="exSV">${ssOpt(TR.vente)}</select></label><span class="mutedv">N-1 du bilan : ${sLab(trSeasonShift(TR.bilan,-1))}</span></div>`:''}
        <div><div class="tl" style="margin-bottom:6px">Présentation pour</div><div class="seg" id="exM">${EXMODES.filter(([m])=>!T||m==='cust'||m==='grp').map(([m,l])=>`<button type="button" data-m="${m}" aria-pressed="${EX.mode===m}">${l}</button>`).join('')}</div></div>
        ${EX.mode==='cur'?`<p class="mutedv" style="margin:0">La présentation reprend exactement les filtres actifs en haut de la page.</p>`:
        `<div><input type="search" id="exQ" class="exq" placeholder="${EX.mode==='cust'?'N° client, ville, groupement ou commercial…':'Rechercher…'}" value="${esc(EX.q)}" autocomplete="off">
         <div class="exlist" role="listbox">${opts.map(([v,l])=>`<button type="button" role="option" data-v="${v}" aria-selected="${EX.sel===v}">${l}</button>`).join('')||'<p class="mutedv">Aucun résultat.</p>'}</div></div>`}
        <div class="exfoot"><div id="exSel">${EX.mode==='cur'||EX.sel!=null?`<span class="tl">Sélection</span> <b>${esc(exName(EX.mode,EX.sel))}</b>`:'<span class="mutedv">Choisissez un élément dans la liste.</span>'}</div>
        <button type="button" class="btn" id="exGo" ${EX.mode!=='cur'&&EX.sel==null?'disabled':''}>${T?'Générer la trame RDV':'Générer le PowerPoint'}</button></div>
        <p class="mutedv" id="exMsg" style="margin:0">${T?`Trame Le Coq Sportif, complétée automatiquement : bilan ${sLab(TR.bilan)} vs ${sLab(trSeasonShift(TR.bilan,-1))}, commandes et livraisons, commandes ${sLab(TR.bilan)} en vue visuelle (Textile / Chaussures × Homme / Femme / Enfant, classées par gamme : Essentials d'abord), toutes les références commandées en ${sLab(TR.vente)} avec visuels, réassort, stocks et ruptures, best-sellers, opportunités et proposition de commande. Les champs sans donnée sont marqués « à compléter ».`:`Contenu : vue globale et historique, d'où vient l'écart, vue par catégorie, détail Homme / Femme / Enfant par famille, lignes produit, références, magasins.`}</p>
      </div></div>`;
    $('#expX').onclick=close;
    $('#exM').onclick=e=>{const b=e.target.closest('button');if(!b)return;if(EX.mode!==b.dataset.m){EX.mode=b.dataset.m;EX.sel=null;EX.q=''}draw()};
    const q=$('#exQ');if(q){q.oninput=()=>{EX.q=q.value;const pos=q.selectionStart;draw();const nq=$('#exQ');nq.focus();nq.setSelectionRange(pos,pos)}}
    const l=el.querySelector('.exlist');if(l)l.onclick=e=>{const b=e.target.closest('[data-v]');if(!b)return;EX.sel=+b.dataset.v;draw()};
    $('#exGo').onclick=()=>EX.deck==='trame'?runTrame():runExport();
    $('#exD').onclick=e=>{const b=e.target.closest('button');if(!b)return;EX.deck=b.dataset.d;draw()};
    const sb=$('#exSB'),sv=$('#exSV');if(sb){sb.onchange=()=>{TR.bilan=sb.value;const nx=trSeasonShift(TR.bilan,'next');if(D.dicts.season.includes(nx))TR.vente=nx;draw()};sv.onchange=()=>{TR.vente=sv.value;draw()}}
  };
  const close=()=>{el.hidden=true;el.innerHTML=''};
  el.onclick=e=>{if(e.target===el)close()};
  el.hidden=false;draw();
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){const x=$('#exp');if(x&&!x.hidden){x.hidden=true;x.innerHTML=''}}});

function exDataset(){
  const ss=D.dicts.season,ns=ss.length;
  let masks;
  if(EX.mode==='cur')masks=FDIMS.filter(d=>F[d].size);else masks=['sot','perim'].filter(d=>F[d].size);
  masks=masks.map(d=>{const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return[C[d],m]});
  let cm=null;
  if(EX.mode!=='cur'){cm=new Uint8Array(D.dicts.cust.length);const s=EX.sel;
    if(EX.mode==='cust')cm[s]=1;else{const map=EX.mode==='grp'?D.custGrpI:EX.mode==='cg'?D.custCgI:D.custSalesI;for(let c=0;c<cm.length;c++)if(map[c]===s)cm[c]=1}}
  const ex=S.exclC?CENT:-2,nc=D.dicts.cust.length;
  const X={hist:new Float64Array(ns),qh:new Float64Array(ns),cN:new Float64Array(nc),cP:new Float64Array(nc),fam:new Map(),seg:new Map(),line:new Map(),item:new Map()};
  const SEG=['Homme','Femme','Unisexe','Enfant','Autre'];
  const segOf=i=>{const a=D.dicts.age[C.age[i]],g=D.dicts.genus[C.genus[i]];if(['INFANT','PRESCHOOL','GRADESCHOO','JUNIOR'].includes(a))return 3;if(g==='MALE')return 0;if(g==='FEMALE')return 1;if(g==='UNISEX')return 2;return 4};
  const acc=(map,k,isN,a,q,extra)=>{let o=map.get(k);if(!o){o={n:0,p:0,qn:0,qp:0,...extra};map.set(k,o)}if(isN){o.n+=a;o.qn+=q}else{o.p+=a;o.qp+=q}};
  outer:for(let i=0;i<N;i++){const c=C.cust[i];if(cm&&!cm[c])continue;if(c===ex)continue;for(const[col,m]of masks)if(!m[col[i]])continue outer;
    const s=C.season[i],a=C.amt[i],q=C.qty[i];X.hist[s]+=a;X.qh[s]+=q;
    if(s!==S.n&&s!==S.p)continue;const isN=s===S.n;
    if(isN)X.cN[c]+=a;else X.cP[c]+=a;
    const f=C.fam[i];acc(X.fam,f,isN,a,q);acc(X.seg,f*8+segOf(i),isN,a,q);acc(X.line,C.line[i],isN,a,q,{f});acc(X.item,C.item[i],isN,a,q)}
  const K={tN:X.hist[S.n],tP:X.hist[S.p]||0,qN:X.qh[S.n],qP:X.qh[S.p]||0,dN:0,dP:0,comp:0,compN:0,compP:0,lost:0,lostP:0,nw:0,nwN:0,stores:[]};
  for(let c=0;c<nc;c++){const a=X.cN[c]>0,b=X.cP[c]>0;if(!a&&!b)continue;K.stores.push(c);if(a)K.dN++;if(b)K.dP++;if(a&&b){K.comp++;K.compN+=X.cN[c];K.compP+=X.cP[c]}else if(b){K.lost++;K.lostP+=X.cP[c]}else{K.nw++;K.nwN+=X.cN[c]}}
  X.K=K;X.SEG=SEG;X.cm=cm;return X;
}

let PPTX_P=null;
function loadPptx(){
  if(typeof PptxGenJS!=='undefined')return Promise.resolve(true);
  if(PPTX_P)return PPTX_P;
  const srcs=['https://cdn.jsdelivr.net/npm/pptxgenjs@3.12.0/dist/pptxgen.bundle.js','https://unpkg.com/pptxgenjs@3.12.0/dist/pptxgen.bundle.js','https://cdnjs.cloudflare.com/ajax/libs/pptxgenjs/3.12.0/pptxgen.bundle.js'];
  PPTX_P=(async()=>{for(const src of srcs){const ok=await new Promise(res=>{const s=document.createElement('script');s.src=src;s.onload=()=>res(typeof PptxGenJS!=='undefined');s.onerror=()=>res(false);document.head.appendChild(s);setTimeout(()=>res(typeof PptxGenJS!=='undefined'),15000)});if(ok)return true}PPTX_P=null;return false})();
  return PPTX_P;
}
async function runExport(){
  const msg=$('#exMsg'),btn=$('#exGo');
  btn.disabled=true;msg.textContent='Chargement du module PowerPoint…';
  if(!(await loadPptx())){msg.textContent="Le module PowerPoint n'a pas pu être chargé (réseau ou pare-feu). Vérifiez la connexion puis réessayez.";btn.disabled=false;return}
  btn.disabled=true;msg.textContent='Préparation de la présentation…';
  try{
    try{localStorage.setItem('lcs-export',JSON.stringify({mode:EX.mode,sel:EX.sel}))}catch(e){}
    const X=exDataset();
    if(!X.K.tN&&!X.K.tP){msg.textContent='Aucune commande sur ces deux saisons pour cette sélection.';btn.disabled=false;return}
    const name=exName(EX.mode,EX.sel);
    const pptx=buildDeck(X,name);
    const blob=await pptx.write({outputType:'blob'});
    const fname=('Bilan sellin '+name+' '+sLab(D.dicts.season[S.n])+(S.p<0?'':' vs '+sLab(D.dicts.season[S.p]))).replace(/[\\/:*?"<>|]+/g,' ').replace(/\s+/g,'_')+'.pptx';
    await fileSave(fname,blob,msg);
  }catch(err){
    const code=err&&err.code;
    msg.textContent=code==='declined'?'Téléchargement annulé.':code==='rate_limited'?'Une confirmation est déjà ouverte, réessayez dans un instant.':code==='extension_not_enabled'||code==='rejected_extension'?"Le téléchargement de fichiers PowerPoint n'est pas autorisé dans cette vue.":code==='unavailable'||code==='not_granted'||code==='capability_disabled'?"Le téléchargement n'est pas disponible dans cette vue. Ouvrez le tableau de bord dans claude.ai (navigateur) puis réessayez.":"L'export a échoué : "+(code?code+' – ':'')+(err&&err.message||err);
    console.error('Export PowerPoint',err);
  }
  btn.disabled=false;
}

function buildDeck(X,name){
  const K=X.K,ss=D.dicts.season,n=sLab(ss[S.n]),p=sLab(ss[S.p]),solo=S.p<0;
  const NAVY='0F2347',BLUE='2A78D6',PLUM='A23F7A',GREEN='0B7F3A',RED='C22F2F',INK='101A2C',GREY='5B6578',LIGHT='F3F5F8',LINE='DDE3EC',FONT='Arial';
  const multi=K.stores.length>1;
  const pptx=new PptxGenJS();pptx.layout='LAYOUT_WIDE';pptx.author='Suivi Sellin global';pptx.title='Bilan '+name;
  const logo='image/png;base64,'+LOGO64;
  const pct=(a,b)=>b>0?(a-b)/b:null;
  const evoTxt=(a,b)=>{const r=pct(a,b);if(r==null)return a>0?'nouveau':'—';return(r>=0?'+':'−')+Math.abs(r*100).toLocaleString('fr-FR',{minimumFractionDigits:1,maximumFractionDigits:1})+' %'};
  const evoCol=(a,b)=>{const r=pct(a,b);return r==null?GREY:r>=0?GREEN:RED};
  const E=v=>nf0.format(Math.round(v))+' €',Q=v=>nf0.format(Math.round(v)),M=v=>fM(v).replace(/ /g,' ');
  const sub=solo?`${name} · ${typeLab()} ${n}`:`${name} · ${typeLab()} ${n} vs ${p}`;
  let pageNo=1;
  const base=(title,subtitle)=>{const s=pptx.addSlide();s.background={color:'FFFFFF'};
    s.addShape(pptx.ShapeType.rect,{x:0,y:0,w:13.333,h:0.95,fill:{color:NAVY},line:{color:NAVY}});
    s.addShape(pptx.ShapeType.rect,{x:0,y:0.95,w:4.444,h:0.05,fill:{color:'1D4FD6'},line:{color:'1D4FD6'}});
    s.addShape(pptx.ShapeType.rect,{x:8.889,y:0.95,w:4.444,h:0.05,fill:{color:'D6242E'},line:{color:'D6242E'}});
    s.addText(title.toUpperCase(),{x:0.45,y:0.12,w:10.8,h:0.5,fontFace:FONT,fontSize:22,bold:true,color:'FFFFFF',margin:0});
    s.addText(subtitle||sub,{x:0.45,y:0.58,w:10.8,h:0.3,fontFace:FONT,fontSize:11,color:'B9C6DE',margin:0});
    s.addImage({data:logo,x:12.15,y:0.12,w:0.82,h:0.71});
    pageNo++;s.addText(`Source : extraction carnet de commandes BOH · ${typeLab()}${F.perim.size?' · périmètre BOH':''} · page ${pageNo}`,{x:0.45,y:7.08,w:12.4,h:0.3,fontFace:FONT,fontSize:8,color:GREY,margin:0});
    if(solo){const oT=s.addTable.bind(s),oC=s.addChart.bind(s);
      s.addTable=(rows,o)=>{const h=rows[0].map(c=>typeof c==='string'?c:(c&&c.text)||'');const bad=new Set(h.map((x,i)=>(x===p||x.endsWith(' '+p)||/^(Écart|Évo|Déjà en)/.test(x))?i:-1).filter(i=>i>=0));
        if(!bad.size)return oT(rows,o);const L=h.length;const r2=rows.map(r=>r.length===L?r.filter((_,i)=>!bad.has(i)):r);let o2=o;
        if(o&&o.colW){const tw=o.colW.reduce((a,b)=>a+b,0),kw=o.colW.filter((_,i)=>!bad.has(i)),kt=kw.reduce((a,b)=>a+b,0);o2={...o,colW:kw.map(w=>w*tw/kt)}}
        r2.forEach(r=>r.forEach(c=>{if(c&&c.options&&c.options.colspan>kw_len(r2))c.options.colspan=r2[0].length}));return oT(r2,o2)};
      s.addChart=(type,data,o)=>{const d=Array.isArray(data)?data.filter(x=>x.name!==p):data;return oC(type,d,d.length===1?{...o,chartColors:[BLUE],showLegend:false}:o)}}
    return s};
  const kw_len=r=>r[0].length;
  const hcell=(t,al,col)=>({text:t,options:{bold:true,color:col||'FFFFFF',fill:{color:NAVY},align:al||'right',fontSize:10}});
  const tableOpts=(w)=>({x:0.45,w:w||12.4,fontFace:FONT,fontSize:10,color:INK,border:{type:'solid',pt:0.5,color:LINE},rowH:0.3,valign:'middle',margin:[0.03,0.07,0.03,0.07]});
  const kpi=(s,x,y,w,label,big,line,col)=>{s.addShape(pptx.ShapeType.rect,{x,y,w,h:1.35,fill:{color:LIGHT},line:{color:LIGHT}});
    s.addText(label.toUpperCase(),{x:x+0.15,y:y+0.1,w:w-0.3,h:0.3,fontFace:FONT,fontSize:9,bold:true,color:GREY,margin:0});
    s.addText(big,{x:x+0.15,y:y+0.38,w:w-0.3,h:0.55,fontFace:FONT,fontSize:26,bold:true,color:INK,margin:0});
    s.addText(line,{x:x+0.15,y:y+0.93,w:w-0.3,h:0.32,fontFace:FONT,fontSize:10,color:col||GREY,margin:0})};

  /* 1. cover */
  {const s=pptx.addSlide();s.background={color:NAVY};
    s.addShape(pptx.ShapeType.rect,{x:0,y:7.2,w:4.444,h:0.3,fill:{color:'1D4FD6'},line:{color:'1D4FD6'}});
    s.addShape(pptx.ShapeType.rect,{x:4.444,y:7.2,w:4.444,h:0.3,fill:{color:'FFFFFF'},line:{color:'FFFFFF'}});
    s.addShape(pptx.ShapeType.rect,{x:8.889,y:7.2,w:4.444,h:0.3,fill:{color:'D6242E'},line:{color:'D6242E'}});
    s.addImage({data:logo,x:0.8,y:0.7,w:1.85,h:1.6});s.addImage({data:'image/png;base64,'+WORD64,x:0.8,y:2.45,w:2.4,h:0.38});
    s.addText(solo?`BILAN SELL-IN ${n}`:`BILAN SELL-IN ${n} VS ${p}`,{x:0.8,y:3.2,w:11.5,h:0.8,fontFace:FONT,fontSize:36,bold:true,color:'FFFFFF',margin:0});
    s.addText(name,{x:0.8,y:4.0,w:11.5,h:0.6,fontFace:FONT,fontSize:24,color:'FFFFFF',margin:0});
    const info=[];
    if(EX.mode==='cust'){info.push('Groupement : '+(D.custGrp[EX.sel]||'sans groupement'),'Groupe client : '+D.dicts.cg[D.custCgI[EX.sel]],'Commercial : '+D.dicts.sales[D.custSalesI[EX.sel]])}
    else if(multi)info.push(`${Q(K.stores.length)} magasins concernés`);
    info.push('Types de commande : '+typeLab(),'Données : extraction du 06/10/2026');
    s.addText(info.join('\n'),{x:0.8,y:4.8,w:11.5,h:1.6,fontFace:FONT,fontSize:13,color:'B9C6DE',margin:0,valign:'top',paraSpaceAfter:4})}

  /* 2. vue globale */
  if(solo){const s=base('Vue globale');const w=2.95,g=0.2;const rN=[...X.item.values()].filter(v=>v.n>0).length;
    kpi(s,0.45,1.3,w,'CA '+n,M(K.tN),typeLab()+' · saison seule');kpi(s,0.45+(w+g),1.3,w,'Quantités '+n,Q(K.qN),'pièces commandées');
    kpi(s,0.45+2*(w+g),1.3,w,multi?'Portes actives':'Références '+n,multi?Q(K.dN):Q(rN),multi?'magasins avec une commande':'références commandées');
    kpi(s,0.45+3*(w+g),1.3,w,multi?'Commande moyenne':'Prix moyen / pièce',multi?(K.dN?M(K.tN/K.dN):'—'):(K.qN?E(K.tN/K.qN):'—'),multi?'CA divisé par les portes':'CA divisé par les pièces');
    const labels=ss.map(sLab),vals=[...X.hist].map(v=>Math.max(0,Math.round(v)));
    s.addText('Historique du CA sur 8 saisons',{x:0.45,y:2.9,w:7,h:0.35,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
    s.addChart(pptx.ChartType.bar,[{name:'CA',labels,values:vals}],{x:0.35,y:3.2,w:7.4,h:3.75,barDir:'col',chartColors:[BLUE],showValue:true,dataLabelFormatCode:'#,##0,"k€"',dataLabelFontSize:9,dataLabelColor:INK,valAxisLabelFormatCode:'#,##0,"k€"',valAxisLabelFontSize:9,catAxisLabelFontSize:10,valGridLine:{color:LINE,size:0.5},valAxisMinVal:0});
    const fams=[...X.fam.entries()].filter(([f,v])=>v.n>0&&D.dicts.fam[f]!=='(non renseigné)').sort((a,b)=>b[1].n-a[1].n);
    const lines=[...X.line.entries()].filter(([,v])=>v.n>0).sort((a,b)=>b[1].n-a[1].n).slice(0,3);
    const pts=[`CA ${n} : ${M(K.tN)} pour ${Q(K.qN)} pièces.`];
    if(fams.length)pts.push('Répartition : '+fams.map(([f,v])=>`${D.dicts.fam[f]} ${Math.round(v.n/K.tN*100)} %`).join(', ')+'.');
    if(lines.length)pts.push('Premières lignes produit : '+lines.map(([l,v])=>`${D.dicts.line[l]} (${M(v.n)})`).join(', ')+'.');
    if(multi)pts.push(`${Q(K.dN)} magasins actifs, commande moyenne ${K.dN?M(K.tN/K.dN):'—'}.`);
    s.addShape(pptx.ShapeType.rect,{x:8.05,y:2.9,w:4.8,h:4.05,fill:{color:LIGHT},line:{color:LIGHT}});
    s.addText('Points clés',{x:8.25,y:3.0,w:4.4,h:0.4,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
    s.addText(pts.map(t=>({text:t,options:{bullet:true,paraSpaceAfter:6}})),{x:8.25,y:3.45,w:4.45,h:3.4,fontFace:FONT,fontSize:11,color:INK,valign:'top',margin:0})}
  else{const s=base('Vue globale');
    const w=2.95,g=0.2;
    kpi(s,0.45,1.3,w,'CA '+n,M(K.tN),`${p} : ${M(K.tP)}  ·  ${evoTxt(K.tN,K.tP)}`,evoCol(K.tN,K.tP));
    kpi(s,0.45+(w+g),1.3,w,'Quantités '+n,Q(K.qN),`${p} : ${Q(K.qP)}  ·  ${evoTxt(K.qN,K.qP)}`,evoCol(K.qN,K.qP));
    if(multi){kpi(s,0.45+2*(w+g),1.3,w,'Portes actives',Q(K.dN),`${p} : ${Q(K.dP)}  ·  écart ${K.dN-K.dP>=0?'+':'−'}${Q(Math.abs(K.dN-K.dP))}`);
      kpi(s,0.45+3*(w+g),1.3,w,'CA comparable',M(K.compN),`${Q(K.comp)} portes  ·  ${evoTxt(K.compN,K.compP)}`,evoCol(K.compN,K.compP))}
    else{const rN=[...X.item.values()].filter(v=>v.n>0).length,rP=[...X.item.values()].filter(v=>v.p>0).length;
      kpi(s,0.45+2*(w+g),1.3,w,'Références '+n,Q(rN),`${p} : ${Q(rP)}`);
      kpi(s,0.45+3*(w+g),1.3,w,'Prix moyen / pièce',K.qN?E(K.tN/K.qN):'—',`${p} : ${K.qP?E(K.tP/K.qP):'—'}`)}
    const labels=ss.map(sLab),vals=[...X.hist].map(v=>Math.max(0,Math.round(v)));
    s.addText('Historique du CA sur 8 saisons',{x:0.45,y:2.9,w:7,h:0.35,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
    s.addChart(pptx.ChartType.bar,[{name:'CA',labels,values:vals}],{x:0.35,y:3.2,w:7.4,h:3.75,barDir:'col',chartColors:[BLUE],showValue:true,dataLabelFormatCode:'#,##0,"k€"',dataLabelFontSize:9,dataLabelColor:INK,valAxisLabelFormatCode:'#,##0,"k€"',valAxisLabelFontSize:9,catAxisLabelFontSize:10,valGridLine:{color:LINE,size:0.5},valAxisMinVal:0,showLegend:false,barGapWidthPct:60});
    /* key points */
    const fams=[...X.fam.entries()].filter(([f,v])=>v.p>0&&D.dicts.fam[f]!=='(non renseigné)').map(([f,v])=>({l:D.dicts.fam[f],r:(v.n-v.p)/v.p,n:v.n,p:v.p}));
    const best=fams.slice().sort((a,b)=>b.r-a.r)[0],worst=fams.slice().sort((a,b)=>a.r-b.r)[0];
    const lines=[...X.line.entries()].map(([l,v])=>({l:D.dicts.line[l],d:v.n-v.p}));
    const lu=lines.slice().sort((a,b)=>b.d-a.d)[0],ld=lines.slice().sort((a,b)=>a.d-b.d)[0];
    const pts=[`CA ${n} : ${M(K.tN)}, soit ${evoTxt(K.tN,K.tP)} par rapport à ${p} (${K.tN-K.tP>=0?'+':'−'}${M(Math.abs(K.tN-K.tP))}).`];
    if(best&&fams.length>1)pts.push(`Catégorie la plus dynamique : ${best.l} (${evoTxt(best.n,best.p)}).`);
    if(worst&&fams.length>1&&worst!==best)pts.push(`Catégorie en plus fort recul : ${worst.l} (${evoTxt(worst.n,worst.p)}).`);
    if(lu&&lu.d>0)pts.push(`Ligne qui progresse le plus : ${lu.l} (+${M(lu.d)}).`);
    if(ld&&ld.d<0)pts.push(`Ligne qui recule le plus : ${ld.l} (−${M(-ld.d)}).`);
    if(multi)pts.push(`${Q(K.comp)} portes comparables (${evoTxt(K.compN,K.compP)}), ${Q(K.lost)} portes perdues (${M(K.lostP)} en ${p}) et ${Q(K.nw)} nouvelles.`);
    s.addShape(pptx.ShapeType.rect,{x:8.05,y:2.9,w:4.8,h:4.05,fill:{color:LIGHT},line:{color:LIGHT}});
    s.addText('Points clés',{x:8.25,y:3.0,w:4.4,h:0.4,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
    s.addText(pts.map(t=>({text:t,options:{bullet:true,paraSpaceAfter:6}})),{x:8.25,y:3.45,w:4.45,h:3.4,fontFace:FONT,fontSize:11,color:INK,valign:'top',margin:0})}

  /* 3. d'où vient l'écart */
  if(multi&&!solo){const s=base("D'où vient l'écart");
    const compD=K.compN-K.compP,other=K.tN-(K.tP-K.lostP+compD+K.nwN);
    const rows=[[hcell('Étape','left'),hcell('Montant'),hcell('Portes'),hcell('Commentaire','left')],
      [{text:'CA '+p,options:{bold:true}},{text:E(K.tP),options:{align:'right',bold:true}},{text:Q(K.dP),options:{align:'right'}},''],
      ['Portes perdues',{text:(K.lostP?'−':'')+E(K.lostP),options:{align:'right',color:RED}},{text:Q(K.lost),options:{align:'right'}},`Commandaient en ${p}, aucune commande en ${n}`],
      ['Écart sur le comparable',{text:(compD>=0?'+':'−')+E(Math.abs(compD)),options:{align:'right',color:compD>=0?GREEN:RED}},{text:Q(K.comp),options:{align:'right'}},`${evoTxt(K.compN,K.compP)} sur les portes présentes les deux saisons`],
      ['Nouvelles portes',{text:'+'+E(K.nwN),options:{align:'right',color:GREEN}},{text:Q(K.nw),options:{align:'right'}},`Aucune commande en ${p}`]];
    if(Math.abs(other)>=1)rows.push(['Autres',{text:(other>=0?'+':'−')+E(Math.abs(other)),options:{align:'right'}},'','Avoirs, retours, lignes négatives']);
    rows.push([{text:'CA '+n,options:{bold:true}},{text:E(K.tN),options:{align:'right',bold:true}},{text:Q(K.dN),options:{align:'right'}},{text:evoTxt(K.tN,K.tP)+' vs '+p,options:{color:evoCol(K.tN,K.tP),bold:true}}]);
    s.addTable(rows,{...tableOpts(12.4),y:1.3,colW:[3.0,2.0,1.2,6.2],rowH:0.4,fontSize:12});
    const yb=1.3+rows.length*0.4+0.4;
    kpi(s,0.45,yb,3.95,'Commande moyenne comparable '+n,K.comp?M(K.compN/K.comp):'—',`${p} : ${K.comp?M(K.compP/K.comp):'—'}`);
    kpi(s,4.65,yb,3.95,'Commande moyenne toutes portes '+n,K.dN?M(K.tN/K.dN):'—',`${p} : ${K.dP?M(K.tP/K.dP):'—'}`);
    kpi(s,8.85,yb,4.0,'Taux de rétention des portes',K.dP?Math.round(K.comp/K.dP*100)+' %':'—',`${Q(K.comp)} portes ${p} toujours actives en ${n}`)}

  /* 4. vue par catégorie */
  const famList=[...X.fam.entries()].filter(([f,v])=>(v.n||v.p)&&D.dicts.fam[f]!=='(non renseigné)').sort((a,b)=>(b[1].n+b[1].p)-(a[1].n+a[1].p));
  {const s=base('Vue par catégorie');
    const rows=[[hcell('Catégorie','left'),hcell('CA '+n),hcell('CA '+p),hcell('Écart €'),hcell('Évo'),hcell('Qté '+n),hcell('Qté '+p),hcell('Évo qté'),hcell('Part '+n)]];
    famList.forEach(([f,v])=>rows.push([D.dicts.fam[f],{text:E(v.n),options:{align:'right'}},{text:E(v.p),options:{align:'right'}},{text:(v.n-v.p>=0?'+':'−')+E(Math.abs(v.n-v.p)),options:{align:'right',color:evoCol(v.n,v.p)}},{text:evoTxt(v.n,v.p),options:{align:'right',color:evoCol(v.n,v.p),bold:true}},{text:Q(v.qn),options:{align:'right'}},{text:Q(v.qp),options:{align:'right'}},{text:evoTxt(v.qn,v.qp),options:{align:'right',color:evoCol(v.qn,v.qp)}},{text:K.tN>0?Math.round(v.n/K.tN*100)+' %':'—',options:{align:'right'}}]));
    rows.push([{text:'Total',options:{bold:true}},{text:E(K.tN),options:{align:'right',bold:true}},{text:E(K.tP),options:{align:'right',bold:true}},{text:(K.tN-K.tP>=0?'+':'−')+E(Math.abs(K.tN-K.tP)),options:{align:'right',bold:true,color:evoCol(K.tN,K.tP)}},{text:evoTxt(K.tN,K.tP),options:{align:'right',bold:true,color:evoCol(K.tN,K.tP)}},{text:Q(K.qN),options:{align:'right',bold:true}},{text:Q(K.qP),options:{align:'right',bold:true}},{text:evoTxt(K.qN,K.qP),options:{align:'right',bold:true,color:evoCol(K.qN,K.qP)}},{text:'100 %',options:{align:'right',bold:true}}]);
    s.addTable(rows,{...tableOpts(12.4),y:1.3,colW:[2.4,1.45,1.45,1.4,1.0,1.2,1.2,1.1,1.2],rowH:0.36,fontSize:11});
    const labels=famList.map(([f])=>D.dicts.fam[f]);
    s.addChart(pptx.ChartType.bar,[{name:n,labels,values:famList.map(([,v])=>Math.round(v.n))},{name:p,labels,values:famList.map(([,v])=>Math.round(v.p))}],
      {x:0.35,y:1.3+rows.length*0.36+0.3,w:12.6,h:Math.max(2.4,5.6-rows.length*0.36),barDir:'bar',chartColors:[BLUE,PLUM],showValue:true,dataLabelFormatCode:'#,##0,"k€"',dataLabelFontSize:9,valAxisHidden:true,catAxisLabelFontSize:11,valGridLine:{style:'none'},showLegend:true,legendPos:'t',legendFontSize:10,barGapWidthPct:40})}

  /* 5. détail par famille : homme / femme / enfant */
  famList.forEach(([f,fv])=>{
    const fl=D.dicts.fam[f];const s=base(`${fl} · détail Homme / Femme / Enfant`,`${sub} · ${fl} : ${M(fv.n)}${solo?'':` (${evoTxt(fv.n,fv.p)})`}`);
    const segs=X.SEG.map((l,k)=>[l,X.seg.get(f*8+k)]).filter(([,v])=>v&&(v.n||v.p));
    const rows=[[hcell('Segment','left'),hcell('CA '+n),hcell('CA '+p),hcell('Écart €'),hcell('Évo'),hcell('Qté '+n),hcell('Qté '+p),hcell('Part '+n)]];
    segs.forEach(([l,v])=>rows.push([l,{text:E(v.n),options:{align:'right'}},{text:E(v.p),options:{align:'right'}},{text:(v.n-v.p>=0?'+':'−')+E(Math.abs(v.n-v.p)),options:{align:'right',color:evoCol(v.n,v.p)}},{text:evoTxt(v.n,v.p),options:{align:'right',bold:true,color:evoCol(v.n,v.p)}},{text:Q(v.qn),options:{align:'right'}},{text:Q(v.qp),options:{align:'right'}},{text:fv.n>0?Math.round(v.n/fv.n*100)+' %':'—',options:{align:'right'}}]));
    s.addTable(rows,{...tableOpts(7.4),y:1.3,colW:[1.35,1.05,1.05,1.0,0.8,0.75,0.75,0.65],rowH:0.34,fontSize:10});
    const labels=segs.map(([l])=>l);
    s.addChart(pptx.ChartType.bar,[{name:n,labels,values:segs.map(([,v])=>Math.round(v.n))},{name:p,labels,values:segs.map(([,v])=>Math.round(v.p))}],
      {x:8.05,y:1.25,w:4.9,h:2.75,barDir:'col',chartColors:[BLUE,PLUM],showValue:false,valAxisLabelFormatCode:'#,##0,"k"',valAxisLabelFontSize:8,catAxisLabelFontSize:9,valGridLine:{color:LINE,size:0.5},valAxisMinVal:0,showLegend:true,legendPos:'t',legendFontSize:9});
    const ls=[...X.line.entries()].filter(([,v])=>v.f===f).map(([l,v])=>({l:D.dicts.line[l],...v,d:v.n-v.p}));
    const up=ls.filter(x=>x.d>0).sort((a,b)=>b.d-a.d).slice(0,6),dn=ls.filter(x=>x.d<0).sort((a,b)=>a.d-b.d).slice(0,6);
    const lt=(arr,title,x)=>{s.addText(title,{x,y:4.2,w:6,h:0.35,fontFace:FONT,fontSize:12,bold:true,color:INK,margin:0});
      const r=[[hcell('Ligne produit','left'),hcell(n),hcell(p),hcell('Évo')]];
      (arr.length?arr:[{l:'—',n:0,p:0}]).forEach(v=>r.push([v.l,{text:E(v.n),options:{align:'right'}},{text:E(v.p),options:{align:'right'}},{text:evoTxt(v.n,v.p),options:{align:'right',bold:true,color:evoCol(v.n,v.p)}}]));
      s.addTable(r,{...tableOpts(6.0),x,y:4.6,colW:[2.6,1.2,1.2,1.0],rowH:0.3,fontSize:9})};
    if(solo)lt(ls.filter(x=>x.n>0).sort((a,b)=>b.n-a.n).slice(0,6),'Principales lignes produit',0.45);else{lt(up,'Lignes en progression',0.45);lt(dn,'Lignes en recul',6.85)}
  });

  /* 6. lignes produit */
  {const s=base('Lignes produit');
    const ls=[...X.line.entries()].map(([l,v])=>({...v,l:D.dicts.line[l],f:D.dicts.fam[v.f]})).sort((a,b)=>b.n-a.n||b.p-a.p).slice(0,16);
    const r=[[hcell('Ligne produit','left'),hcell('Catégorie','left'),hcell('CA '+n),hcell('CA '+p),hcell('Écart €'),hcell('Évo'),hcell('Qté '+n),hcell('Qté '+p)]];
    ls.forEach(v=>r.push([v.l,v.f,{text:E(v.n),options:{align:'right'}},{text:E(v.p),options:{align:'right'}},{text:(v.n-v.p>=0?'+':'−')+E(Math.abs(v.n-v.p)),options:{align:'right',color:evoCol(v.n,v.p)}},{text:evoTxt(v.n,v.p),options:{align:'right',bold:true,color:evoCol(v.n,v.p)}},{text:Q(v.qn),options:{align:'right'}},{text:Q(v.qp),options:{align:'right'}}]));
    s.addText(`Les 16 premières lignes en CA ${n}`,{x:0.45,y:1.15,w:12,h:0.3,fontFace:FONT,fontSize:11,color:GREY,margin:0});
    s.addTable(r,{...tableOpts(12.4),y:1.5,colW:[3.2,1.6,1.5,1.5,1.4,1.0,1.1,1.1],rowH:0.31,fontSize:10})}

  /* 6b. références manquantes */
  if(X.cm&&(EX.mode==='cust'||EX.mode==='grp')){const ownN=new Map(),ownP=new Map();X.item.forEach((v,k)=>{ownN.set(k,v.n);ownP.set(k,v.p)});const MR=missingRefs(X.cm,ownN,ownP);
    if(MR.list.length){const s=base('Références manquantes');
      s.addText(`Top 5 Apparel et top 5 Footwear des best-sellers ${n} du ${MR.lab} non commandés ${EX.mode==='cust'?'par ce magasin':'par le groupement'}. Diffusion = part des ${Q(MR.doors)} portes actives du ${MR.lab} ayant commandé la référence.`,{x:0.45,y:1.15,w:12.4,h:0.35,fontFace:FONT,fontSize:11,color:GREY,margin:0});
      const r=[[hcell('Référence','left'),hcell('Désignation','left'),hcell('Ligne produit','left'),hcell('Diffusion '+MR.lab),hcell('CA '+MR.lab+' '+n),hcell('Qté moy. / porte'),hcell('Déjà en '+p,'center')]];
      [['Apparel',MR.app],['Footwear',MR.ftw]].forEach(([fl,arr])=>{r.push([{text:fl.toUpperCase(),options:{bold:true,colspan:7,fill:{color:LIGHT}}}]);arr.forEach(v=>r.push([v.code,v.desc||'—',v.lineName,{text:Math.round(v.d/Math.max(1,MR.doors)*100)+' % ('+Q(v.d)+' p.)',options:{align:'right',bold:true}},{text:E(v.a),options:{align:'right'}},{text:Q(v.q/Math.max(1,v.d)),options:{align:'right'}},{text:v.prev?'oui':'',options:{align:'center'}}]))});
      s.addTable(r,{...tableOpts(12.4),y:1.6,colW:[3.5,1.0,2.1,2.2,1.6,1.1,0.9],rowH:0.32,fontSize:9.5})}}

  /* 7. références */
  {const s=base('Références');
    const it=[...X.item.entries()];
    const top=it.filter(([,v])=>v.n>0).sort((a,b)=>b[1].n-a[1].n).slice(0,14),gone=it.filter(([,v])=>v.p>0&&v.n<=0).sort((a,b)=>b[1].p-a[1].p).slice(0,14);
    const rt=(arr,isN,title,x)=>{s.addText(title,{x,y:1.2,w:6,h:0.35,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
      const r=[[hcell('Référence','left'),hcell('Désignation','left'),hcell('Qté'),hcell('CA')]];
      (arr.length?arr:[[null,{n:0,p:0,qn:0,qp:0}]]).forEach(([k,v])=>r.push([k==null?'—':D.dicts.item[k],k==null?'':(D.itemDesc[k]||'—'),{text:Q(isN?v.qn:v.qp),options:{align:'right'}},{text:E(isN?v.n:v.p),options:{align:'right'}}]));
      s.addTable(r,{...tableOpts(solo?12.4:6.1),x,y:1.6,colW:solo?[2.0,7.4,1.2,1.8]:[3.0,0.95,0.8,1.35],rowH:0.3,fontSize:9})};
    rt(top,true,`Top références ${n}`,0.45);if(!solo)rt(gone,false,`À reproposer : commandées en ${p}, pas en ${n}`,6.8)}

  /* 8. magasins */
  if(multi){const s=base('Magasins');
    const st=K.stores.slice().sort((a,b)=>X.cN[b]-X.cN[a]||X.cP[b]-X.cP[a]);
    const top=st.filter(c=>X.cN[c]>0).slice(0,14),lost=st.filter(c=>X.cN[c]<=0&&X.cP[c]>0).sort((a,b)=>X.cP[b]-X.cP[a]).slice(0,14);
    const cl=c=>c===CENT?'Centrale Intersport':`${D.dicts.cust[c]} · ${D.custCity[c]||''}`;
    const r1=[[hcell('Magasin','left'),hcell('Groupement','left'),hcell(n),hcell(p),hcell('Évo')]];
    top.forEach(c=>r1.push([cl(c),D.custGrp[c]||'—',{text:E(X.cN[c]),options:{align:'right'}},{text:E(X.cP[c]),options:{align:'right'}},{text:evoTxt(X.cN[c],X.cP[c]),options:{align:'right',bold:true,color:evoCol(X.cN[c],X.cP[c])}}]));
    s.addText(`Top magasins ${n}`,{x:0.45,y:1.2,w:7,h:0.35,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
    s.addTable(r1,{...tableOpts(7.6),y:1.6,colW:[2.6,1.5,1.25,1.25,1.0],rowH:0.3,fontSize:9});
    const r2=[[hcell('Porte perdue','left'),hcell('CA '+p)]];
    (lost.length?lost:[null]).forEach(c=>r2.push(c==null?['Aucune','']:[cl(c),{text:E(X.cP[c]),options:{align:'right'}}]));
    if(!solo){s.addText(`Portes à reconquérir (${Q(K.lost)})`,{x:8.3,y:1.2,w:4.6,h:0.35,fontFace:FONT,fontSize:13,bold:true,color:INK,margin:0});
    s.addTable(r2,{...tableOpts(4.6),x:8.3,y:1.6,colW:[3.2,1.4],rowH:0.3,fontSize:9})}}
  return pptx;
}

/* ================= usage quotidien ================= */
/* --- Mon portefeuille --- */
function initMine(){
  const el=$('#mineBox');if(!el)return;
  if(D.conf){el.innerHTML=`<span class="conf-badge">Confidentiel</span><span class="mine-l">Portefeuille de <b>${esc(D.conf.sales)}</b> · ${fN(D.dicts.cust.length)} clients</span>`;return}
  const names=D.dicts.sales.map((v,i)=>[v,i]).filter(([v])=>v!=='(non renseigné)').sort((a,b)=>a[0].localeCompare(b[0]));
  el.innerHTML=`<label class="mine"><span class="tl">Mon portefeuille</span><select id="mine"><option value="">Toute l'équipe</option>${names.map(([v,i])=>`<option value="${esc(v)}">${esc(v)}</option>`).join('')}</select></label>`;
  let saved='';try{saved=localStorage.getItem('lcs-mine')||''}catch(e){}
  const sel=$('#mine');
  const apply=v=>{const i=D.dicts.sales.indexOf(v);F.sales.clear();if(i>=0)F.sales.add(i);updBtn('sales');if(i>=0&&EX.sel==null){EX.mode='sales';EX.sel=i}};
  if(saved&&D.dicts.sales.includes(saved)){sel.value=saved;apply(saved)}
  sel.onchange=()=>{try{localStorage.setItem('lcs-mine',sel.value)}catch(e){}apply(sel.value);refresh()};
}

/* --- Notes partagées (capability db) --- */
let NDB=null,NUSER=null,MYID=null,NOTES=new Map(),NOTES_ON=false;
async function initNotes(){
  try{const c=window.claude;if(!c||!c.use)return;NDB=await c.use('db');if(!NDB)return;NUSER=await c.use('user');
    if(NUSER){try{MYID=await NUSER.id()}catch(e){}homeUserName()}
    NOTES_ON=true;
    NDB.collection('notes').onSnapshot(snap=>{const m=new Map();snap.docs.forEach(d=>{const x=d.data();if(!x||!x.c)return;const a=m.get(x.c)||[];a.push({id:d.id,...x});m.set(x.c,a)});
      m.forEach(a=>a.sort((p,q)=>String(q.at||'').localeCompare(String(p.at||''))));NOTES=m;const b=$('#notesBox');if(b)renderNotes(+b.dataset.c)},()=>{});
  }catch(e){NDB=null}
}
async function renderNotes(c){
  const box=$('#notesBox');if(!box)return;const code=D.dicts.cust[c];
  if(!NDB){box.innerHTML=`<p class="mutedv" style="margin:0">Les notes partagées sont disponibles quand le tableau de bord est ouvert dans claude.ai, avec un compte connecté.</p>`;return}
  const list=NOTES.get(code)||[];let names={};
  if(NUSER&&list.length){try{const ps=await NUSER.profiles([...new Set(list.map(n=>n.author).filter(Boolean))]);Object.keys(ps).forEach(k=>names[k]=ps[k].name)}catch(e){}}
  const draft=$('#noteTxt')?$('#noteTxt').value:'';
  box.innerHTML=`<div class="note-new"><textarea id="noteTxt" rows="2" placeholder="Ex. : RDV le 12/11, relance sur les références manquantes Footwear…">${esc(draft)}</textarea><button type="button" class="btn" id="noteAdd">Ajouter la note</button></div><div id="noteMsg" class="mutedv"></div>
    <div class="notes">${list.length?list.map(n=>`<div class="nt-item"><div class="nt-h"><b>${esc(names[n.author]||'Un collègue')}</b><span class="meta">${n.at?new Date(n.at).toLocaleString('fr-FR',{dateStyle:'medium',timeStyle:'short'}):''}</span>${n.author&&n.author===MYID?`<button type="button" class="linkbtn" data-del="${esc(n.id)}">Supprimer</button>`:''}</div><div class="nt-t"></div></div>`).join(''):'<p class="mutedv" style="margin:0">Aucune note pour ce client.</p>'}</div>`;
  box.querySelectorAll('.nt-t').forEach((el,i)=>el.textContent=list[i].text||'');
  $('#noteAdd').onclick=async()=>{const t=$('#noteTxt').value.trim();if(!t)return;$('#noteAdd').disabled=true;
    try{await NDB.collection('notes').add({c:code,text:t.slice(0,2000),author:MYID||'',at:new Date().toISOString()});$('#noteTxt').value='';$('#noteMsg').textContent='Note ajoutée.'}
    catch(e){$('#noteMsg').textContent=(e&&e.code==='not_granted')?"Vous n'avez pas les droits d'écriture sur cette page (accès en lecture seule).":"La note n'a pas pu être enregistrée."}
    $('#noteAdd').disabled=false};
  box.querySelectorAll('[data-del]').forEach(b=>b.onclick=async()=>{try{await NDB.collection('notes').doc(b.dataset.del).delete()}catch(e){$('#noteMsg').textContent="Suppression impossible."}});
}

/* --- Export Excel --- */
function pivotRows(levels,pred){
  const L=levels.includes('cust')||levels.includes('item')?levels.slice():levels.concat('cust');
  const root=pivot(L,pred);const rows=[];
  const head=[...L.map(d=>DIM[d]),'Niveau',`CA ${sLab(D.dicts.season[S.n])}`,`CA ${sLab(D.dicts.season[S.p])}`,'Écart €','Évo %',`Qté ${sLab(D.dicts.season[S.n])}`,`Qté ${sLab(D.dicts.season[S.p])}`,`Portes ${sLab(D.dicts.season[S.n])}`,`Portes ${sLab(D.dicts.season[S.p])}`];
  if(S.p<0){const h=[...L.map(d=>DIM[d]),'Niveau',`CA ${sLab(D.dicts.season[S.n])}`,`Qté ${sLab(D.dicts.season[S.n])}`,`Portes ${sLab(D.dicts.season[S.n])}`];rows.push(h);
    (function walk(nd,path){[...nd.ch.values()].filter(k=>k.aN||k.qN).sort((a,b)=>b.aN-a.aN).forEach(k=>{const p=path.concat(cText(k.dim,k.key));rows.push([...L.map((_,i)=>p[i]||''),p.length,Math.round(k.aN),k.qN,k.dN.size]);walk(k,p)})})(root,[]);
    rows.push(['Total',...L.slice(1).map(()=>''),0,Math.round(root.aN),root.qN,root.dN.size]);return rows}
  rows.push(head);
  (function walk(nd,path){[...nd.ch.values()].sort((a,b)=>b.aN-a.aN||b.aP-a.aP).forEach(k=>{const p=path.concat(cText(k.dim,k.key));const r=L.map((_,i)=>p[i]||'');
    rows.push([...r,p.length,Math.round(k.aN),Math.round(k.aP),Math.round(k.aN-k.aP),k.aP?Math.round((k.aN-k.aP)/Math.abs(k.aP)*1000)/10:null,k.qN,k.qP,k.dN.size,k.dP.size]);walk(k,p)})})(root,[]);
  rows.push(['Total',...L.slice(1).map(()=>''),0,Math.round(root.aN),Math.round(root.aP),Math.round(root.aN-root.aP),root.aP?Math.round((root.aN-root.aP)/Math.abs(root.aP)*1000)/10:null,root.qN,root.qP,root.dN.size,root.dP.size]);
  return rows;
}
async function exportExcel(){
  const btn=$('#xlsBtn');const old=btn.textContent;btn.disabled=true;btn.textContent='Préparation…';
  try{
    const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]),k=R.k;const wb=XLSX.utils.book_new();
    const filt=FDIMS.filter(d=>F[d].size).map(d=>[DIM[d],[...F[d]].map(i=>D.dicts[d][i]).join(', ')]);
    const syn=[['Suivi Sellin global'+(D.conf?' · '+D.conf.sales:'')],['Saisons',`${n} vs ${p}`],...filt,[],['Indicateur',n,p,'Évo %'],
      ['CA €',Math.round(k.tN),Math.round(k.tP),k.tP?Math.round((k.tN-k.tP)/k.tP*1000)/10:null],['Quantités',k.qN,k.qP,k.qP?Math.round((k.qN-k.qP)/k.qP*1000)/10:null],
      ['Portes actives',k.dN,k.dP,null],['Commande moyenne €',k.dN?Math.round(k.tN/k.dN):0,k.dP?Math.round(k.tP/k.dP):0,null],['CA comparable €',Math.round(k.compN),Math.round(k.compP),k.compP?Math.round((k.compN-k.compP)/k.compP*1000)/10:null],
      ['Portes comparables',k.comp],['Portes perdues',k.lost,Math.round(k.lostP)],['Nouvelles portes',k.nw,Math.round(k.nwN)]];
    if(S.p<0){syn.length=0;syn.push(['Suivi Sellin global'+(D.conf?' · '+D.conf.sales:'')],['Saison',`${n} (saison seule)`],...filt,[],['Indicateur',n],['CA €',Math.round(k.tN)],['Quantités',k.qN],['Portes actives',k.dN],['Commande moyenne €',k.dN?Math.round(k.tN/k.dN):0])}
    XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(syn),'Synthèse');
    XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(pivotRows(S.levels,null)),'Tableau');
    if(S.p>=0)XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(pivotRows(S.levels,'comp')),'Comparable');
    const st=['','comparable','perdue','nouvelle'];
    const mag=[['N° client','Ville','Groupement','Groupe client','Commercial','Statut',`CA ${n}`,`CA ${p}`,'Écart €','Évo %','Notes']];
    for(let c=0;c<R.cN.length;c++){if(!R.cN[c]&&!R.cP[c])continue;const code=D.dicts.cust[c];mag.push([code,D.custCity[c]||'',D.custGrp[c]||'',D.dicts.cg[D.custCgI[c]],D.dicts.sales[D.custSalesI[c]],st[R.st[c]],Math.round(R.cN[c]),Math.round(R.cP[c]),Math.round(R.cN[c]-R.cP[c]),R.cP[c]>0?Math.round((R.cN[c]-R.cP[c])/R.cP[c]*1000)/10:null,(NOTES.get(code)||[]).map(x=>x.text).join(' | ')])}
    if(S.p<0){mag.length=0;mag.push(['N° client','Ville','Groupement','Groupe client','Commercial',`CA ${n}`,'Notes']);for(let c=0;c<R.cN.length;c++){if(!R.cN[c])continue;const code=D.dicts.cust[c];mag.push([code,D.custCity[c]||'',D.custGrp[c]||'',D.dicts.cg[D.custCgI[c]],D.dicts.sales[D.custSalesI[c]],Math.round(R.cN[c]),(NOTES.get(code)||[]).map(x=>x.text).join(' | ')])}}
    XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(mag),'Magasins');
    if(BL){const rows=blRows();const tr=blTree(rows,['cust','col']);const aoa=[['N° client','Ville','Groupement','Commercial','Collection','Reste à facturer €','Pièces','dont en retard €','dont attendu fournisseur €','1re livraison','Dernière livraison']];
      [...tr.ch.values()].sort((a,b)=>b.a-a.a).forEach(cn=>[...cn.ch.values()].forEach(x=>aoa.push([D.dicts.cust[cn.key],D.custCity[cn.key]||'',D.custGrp[cn.key]||'',D.dicts.sales[D.custSalesI[cn.key]],BL.dicts.col[x.key],Math.round(x.a),x.q,Math.round(x.late),Math.round(x.wait),x.dmin==='9'?'':x.dmin,x.dmax])));
      XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(aoa),'Reste à facturer');
      const ta=blTree(rows,['art']);const ar=[['Référence','Désignation','Clients','Pièces','Reste à facturer €','dont en retard €','Livraison demandée (1re)','Livraison demandée (dernière)','Entrée en stock prévue (1re)','Entrée en stock prévue (dernière)']];
      [...ta.ch.values()].sort((a,b)=>b.a-a.a).forEach(x=>ar.push([BL.dicts.art[x.key],BL.dicts.artDesc[x.key]||'',x.cust.size,x.q,Math.round(x.a),Math.round(x.late),x.dmin==='9'?'':x.dmin,x.dmax,x.smin==='9'?'':x.smin,x.smax]));
      XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(ar),'Références à facturer')}
    if(!['synthese'].includes(S.tab)){$('#panel').querySelectorAll('table').forEach((tb,i)=>{const aoa=[...tb.rows].map(r=>[...r.cells].map(c=>{const t=c.innerText.replace(/\s*Fiche\s*/g,' ').replace(/[▲▼]/g,'').trim();const num=t.replace(/[\s  €%]/g,'').replace('−','-').replace(',','.');return /^[-+]?\d+(\.\d+)?$/.test(num)?+num:t}));XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(aoa),('Onglet '+(i+1)).slice(0,31))})}
    const buf=XLSX.write(wb,{type:'array',bookType:'xlsx'});
    await fileSave(`Suivi_sellin${D.conf?'_'+D.conf.sales.replace(/\s+/g,'_'):''}_${n}${S.p<0?'':'_vs_'+p}.xlsx`,new Blob([buf],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}),null);
  }catch(e){if(!(e&&e.code==='declined'))alertMsg("L'export Excel a échoué"+(e&&e.code?' ('+e.code+')':'')+'.')}
  finally{btn.disabled=false;btn.textContent=old}
}
/* ---------- enregistrement de fichier : capacité downloads, sinon lien direct ---------- */
const FS_MSG={declined:'Téléchargement annulé.',rate_limited:'Une confirmation de téléchargement est déjà ouverte : validez-la ou réessayez dans un instant.',too_large:'Fichier trop volumineux pour cette vue.'};
async function fileSave(fname,data,msg){
  const say=h=>{if(msg)msg.innerHTML=h;else alertMsg(h.replace(/<[^>]+>/g,''))};
  let dl=null;try{dl=window.claude&&window.claude.use?await window.claude.use('downloads'):null}catch(e){dl=null}
  let code=null;
  if(dl){try{say('Confirmez le téléchargement dans la fenêtre qui s’ouvre…');await dl.save({filename:fname,data});say('Fichier enregistré : <b>'+esc(fname)+'</b>');return true}
    catch(e){code=e&&e.code||'inconnue';console.warn('downloads.save',code,e&&e.message);if(FS_MSG[code]){say(FS_MSG[code]);return false}}}
  /* repli : lien de téléchargement classique */
  const blob=data instanceof Blob?data:new Blob([data],{type:fname.endsWith('.pptx')?'application/vnd.openxmlformats-officedocument.presentationml.presentation':fname.endsWith('.xlsx')?'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'application/octet-stream'});
  const url=URL.createObjectURL(blob);window.__LASTFILE={fname,blob};
  try{const a=document.createElement('a');a.href=url;a.download=fname;a.style.display='none';document.body.appendChild(a);a.click();setTimeout(()=>a.remove(),1000)}catch(e){}
  let perm='';try{const pm=window.claude&&window.claude.use?await window.claude.use('permissions'):null;if(pm){const st=await pm.state('downloads');if(st==='denied')perm=' Le téléchargement a été refusé pour cette page : <button type="button" class="linkbtn" data-fsperm="1">rétablir l’autorisation</button>.'}}catch(e){}
  say(`Fichier prêt : <a href="${url}" download="${esc(fname)}"><b>télécharger ${esc(fname)}</b></a>${code?` <span class="meta">(enregistrement direct indisponible : ${esc(code)})</span>`:''}.${perm} Si rien ne se passe, ouvrez le tableau de bord dans claude.ai depuis un navigateur.`);
  const pb=(msg||document).querySelector&&(msg||document).querySelector('[data-fsperm]');if(pb)pb.onclick=async()=>{try{const pm=await window.claude.use('permissions');await pm.manage()}catch(e){}};
  return false;
}
function alertMsg(t){const el=$('#toast');el.textContent=t;el.hidden=false;clearTimeout(alertMsg.t);alertMsg.t=setTimeout(()=>el.hidden=true,5000)}

/* ================= nouvelles analyses ================= */
const SEGS=['Homme','Femme','Unisexe','Enfant','Autre'];
const fP=v=>v.toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})+'\u00a0€';
function segOfRow(i){const a=D.dicts.age[C.age[i]],g=D.dicts.genus[C.genus[i]];if(['INFANT','PRESCHOOL','GRADESCHOO','JUNIOR'].includes(a))return 3;if(g==='MALE')return 0;if(g==='FEMALE')return 1;if(g==='UNISEX')return 2;return 4}
function waterfall(host,steps,aria){
  const W=680,H=280,ml=10,mr=10,mt=30,mb=46,pw=W-ml-mr,ph=H-mt-mb;
  let run=0,lo=0,hi=0;const bars=steps.map(s=>{let a,b;if(s.t){a=0;b=s.v;run=s.v}else{a=run;b=run+s.v;run=b}lo=Math.min(lo,a,b);hi=Math.max(hi,a,b);return{...s,a,b}});
  const mx=niceMax(hi),mn=Math.min(0,lo),y=v=>mt+ph-(v-mn)/(mx-mn)*ph,bw=pw/bars.length;let s=`<line x1="${ml}" x2="${W-mr}" y1="${y(0)}" y2="${y(0)}" class="grid"/>`;
  bars.forEach((b,i)=>{const x=ml+i*bw+bw*.18,w=bw*.64,top=Math.min(y(b.a),y(b.b)),h=Math.max(2,Math.abs(y(b.a)-y(b.b)));const fill=b.c||(b.v>=0?'var(--good)':'var(--bad)');
    if(i<bars.length-1)s+=`<line x1="${x+w}" x2="${ml+(i+1)*bw+bw*.18}" y1="${y(b.b)}" y2="${y(b.b)}" stroke="var(--line-2)" stroke-dasharray="3 3"/>`;
    s+=`<g data-tip="<b>${esc(b.l)}</b><br>${b.t?fE(b.v):fS(b.v)+' €'}${b.sub?'<br>'+esc(b.sub):''}"><rect x="${ml+i*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/><rect x="${x}" y="${top}" width="${w}" height="${h}" rx="3" fill="${fill}"/>
    <text x="${x+w/2}" y="${top-7}" text-anchor="middle" class="vl">${b.t?fM(b.v):(b.v>=0?'+':'−')+fM(Math.abs(b.v))}</text><text x="${x+w/2}" y="${H-26}" text-anchor="middle" class="lb">${esc(b.l)}</text>${b.sub?`<text x="${x+w/2}" y="${H-10}" text-anchor="middle" class="ax">${esc(b.sub)}</text>`:''}</g>`});
  host.innerHTML=`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:540px;max-width:820px;display:block" role="img" aria-label="${esc(aria)}">${s}</svg></div>`;
}
function pvmPanel(P){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);const nf=D.dicts.fam.length,K=nf*5;
  const aN=new Float64Array(K),aP=new Float64Array(K),qN=new Float64Array(K),qP=new Float64Array(K);
  for(const i of R.iN){const k=C.fam[i]*5+segOfRow(i);aN[k]+=C.amt[i];qN[k]+=C.qty[i]}
  for(const i of R.iP){const k=C.fam[i]*5+segOfRow(i);aP[k]+=C.amt[i];qP[k]+=C.qty[i]}
  const cats=[...Array(K).keys()].filter(k=>(aN[k]||aP[k])&&(qN[k]>0||qP[k]>0));
  const QN=cats.reduce((s,k)=>s+Math.max(0,qN[k]),0),QP=cats.reduce((s,k)=>s+Math.max(0,qP[k]),0);
  const TN=cats.reduce((s,k)=>s+aN[k],0),TP=cats.reduce((s,k)=>s+aP[k],0);const PP=QP?TP/QP:0;
  const cMix={},cPrix={};let mix=0,prix=0;cats.forEach(k=>{const pp=qP[k]>0?aP[k]/qP[k]:(qN[k]>0?aN[k]/qN[k]:0),pn=qN[k]>0?aN[k]/qN[k]:0;const sn=QN?Math.max(0,qN[k])/QN:0,sp=QP?Math.max(0,qP[k])/QP:0;const mk=QN*(sn-sp)*pp,pk=Math.max(0,qN[k])*(pn-pp);cMix[k]=mk;cPrix[k]=pk;mix+=mk;prix+=pk});
  const vol=(QN-QP)*PP,rest=(TN-TP)-vol-mix-prix;
  const steps=[{l:`CA ${p}`,v:TP,t:1,c:'var(--p)'},{l:'Effet volume',v:vol,sub:`${fS(QN-QP)} pièces`},{l:'Effet mix',v:mix,sub:'répartition des achats'},{l:'Effet prix',v:prix,sub:`à catégorie égale`}];
  if(Math.abs(rest)>=1)steps.push({l:'Autres',v:rest,sub:'lignes sans quantité'});steps.push({l:`CA ${n}`,v:TN,t:1,c:'var(--n)'});
  const rows=cats.sort((a,b)=>aN[b]-aN[a]).map(k=>{const f=D.dicts.fam[Math.floor(k/5)],sg=SEGS[k%5],pn=qN[k]?aN[k]/qN[k]:0,pp=qP[k]?aP[k]/qP[k]:0;
    return`<tr><td class="lab">${esc(f)} · ${sg}</td><td>${fN(qN[k])}</td><td>${fN(qP[k])}</td><td>${chip(qN[k],qP[k])}</td><td>${pn?fP(pn):'—'}</td><td>${pp?fP(pp):'—'}</td><td>${pn&&pp?chip(pn,pp):'<span class="dash">—</span>'}</td><td>${QN?Math.round(Math.max(0,qN[k])/QN*1000)/10:0} %</td><td>${QP?Math.round(Math.max(0,qP[k])/QP*1000)/10:0} %</td><td>${fE(aN[k])}</td><td>${chip(aN[k],aP[k])}</td></tr>`}).join('');
  const cl=k=>`${D.dicts.fam[Math.floor(k/5)]} ${SEGS[k%5]}`;const pct1=v=>(Math.round(v*1000)/10).toLocaleString('fr-FR')+' %';
  const sh=k=>[QN?Math.max(0,qN[k])/QN:0,QP?Math.max(0,qP[k])/QP:0];
  const up=cats.filter(k=>sh(k)[0]-sh(k)[1]>0).sort((a,b)=>(sh(b)[0]-sh(b)[1])-(sh(a)[0]-sh(a)[1]))[0],dn=cats.filter(k=>sh(k)[0]-sh(k)[1]<0).sort((a,b)=>(sh(a)[0]-sh(a)[1])-(sh(b)[0]-sh(b)[1]))[0];
  const pr=k=>qP[k]>0?aP[k]/qP[k]:0,pn_=k=>qN[k]>0?aN[k]/qN[k]:0;
  const prK=cats.filter(k=>qN[k]>0&&qP[k]>0).sort((a,b)=>Math.abs(cPrix[b])-Math.abs(cPrix[a])).slice(0,3);
  const PN=QN?TN/QN:0;
  const sgnE=v=>(v>=0?'+':'−')+fM(Math.abs(v));
  const pvmText=`<div class="pvm-t">
    <p><b>En résumé.</b> Le CA passe de ${fM(TP)} en ${p} à ${fM(TN)} en ${n}, soit ${sgnE(TN-TP)} (${chip(TN,TP)}). Cet écart se décompose en trois effets qui s'additionnent exactement.</p>
    <p><b>1. Effet volume : ${sgnE(vol)}.</b> Les clients ont commandé ${fN(QN)} pièces en ${n} contre ${fN(QP)} en ${p}, soit ${fS(QN-QP)} pièces (${chip(QN,QP)}). On valorise cet écart au prix moyen de ${p}, qui était de ${fP(PP)} par pièce : ${fS(QN-QP)} × ${fP(PP)} = ${sgnE(vol)}.</p>
    <p><b>2. Effet mix : ${sgnE(mix)}.</b> C'est l'effet du changement de répartition des pièces entre catégories (famille × Homme / Femme / Unisexe / Enfant), qui n'ont pas le même prix.${up!=null?` La catégorie qui gagne le plus de poids est <b>${cl(up)}</b> : ${pct1(sh(up)[1])} des pièces en ${p}, ${pct1(sh(up)[0])} en ${n} (prix moyen ${p} : ${fP(pr(up)||pn_(up))}).`:''}${dn!=null?` Celle qui en perd le plus est <b>${cl(dn)}</b> : ${pct1(sh(dn)[1])} en ${p}, ${pct1(sh(dn)[0])} en ${n} (prix moyen ${p} : ${fP(pr(dn))}).`:''} ${mix<0?'Les achats se sont donc déplacés vers des catégories moins chères en moyenne.':'Les achats se sont donc déplacés vers des catégories plus chères en moyenne.'}</p>
    <p><b>3. Effet prix : ${sgnE(prix)}.</b> À catégorie égale, le prix moyen payé par pièce a ${prix<0?'baissé':'augmenté'}. Les plus fortes contributions :${prK.map(k=>` <b>${cl(k)}</b> ${fP(pr(k))} → ${fP(pn_(k))} par pièce (${sgnE(cPrix[k])})`).join(' ;')}. Le prix moyen par pièce dépend des références choisies dans la catégorie et des remises accordées : l'extraction ne permet pas de séparer ces deux causes.</p>
    <p class="meta">Pour mémoire, le prix moyen toutes catégories passe de ${fP(PP)} à ${fP(PN)} par pièce. Calcul sur ${typeLab()}${F.perim.size?', périmètre BOH':''}, avec les filtres actifs.</p></div>`;
  P.innerHTML=`<div class="toolbar">${famSeg()}</div>
  <div class="card"><div class="card-h"><h2>Effet volume, mix et prix</h2><span class="note">De ${p} à ${n} · catégories = famille × Homme / Femme / Unisexe / Enfant</span></div><div class="card-b" id="pvmC"></div>
    <div class="card-b expl">${pvmText}</div></div></div>
  <div class="card"><div class="card-h"><h2>Détail par catégorie</h2><span class="note">Prix moyen = CA ÷ pièces</span></div><div class="scroll"><table class="pv"><thead><tr><th class="lab">Catégorie</th><th class="cn">Qté ${n}<span class="bar"></span></th><th class="cp">Qté ${p}<span class="bar"></span></th><th>Évo qté</th><th class="cn">Prix moy. ${n}<span class="bar"></span></th><th class="cp">Prix moy. ${p}<span class="bar"></span></th><th>Évo prix</th><th>Part qté ${n}</th><th>Part qté ${p}</th><th class="cn">CA ${n}<span class="bar"></span></th><th>Évo CA</th></tr></thead><tbody>${rows||'<tr class="more"><td colspan="11">Aucune donnée.</td></tr>'}</tbody></table></div></div>`;
  bindFam();waterfall($('#pvmC'),steps,'Décomposition volume, mix, prix');
}
/* ================= accompagnement commercial (avoirs SEI) ================= */
let ACC=null;function accData(){if(ACC)return ACC;try{ACC=JSON.parse(document.getElementById('accdata').textContent)}catch(e){ACC=null}return ACC}
const ACC_MEMO=`<details class="acc-memo"><summary>Mémo : que recouvre chaque catégorie ?</summary><div class="acc-memo-b">
<p><span class="sw" style="background:#c2410c"></span><b>Décote (markdown)</b> — avoir accordé pour aider le magasin à baisser ses prix de vente (fin de saison, soldes, déstockage). Repéré par le flag « Is Decote » ou le motif MARK-DOWN.</p>
<p><span class="sw" style="background:#2563eb"></span><b>Retours</b> — avoir émis après un retour physique de marchandise (motifs RETURN…). Les pièces correspondent aux articles repris.</p>
<p><span class="sw" style="background:#7c3aed"></span><b>Défauts / SAV</b> — produit défectueux, service après-vente, réclamation ou litige (motifs DEFECTIVE, AS, CLAIM).</p>
<p><span class="sw" style="background:#0f766e"></span><b>Autres</b> — avoirs sans motif commercial précis : participation à l’<b>affiliation</b> (environ 7 M€ depuis 2024), <b>marketing</b> / coopération commerciale (1,2 M€), avoirs <b>financiers</b> ou royalties, <b>transport</b>, et avoirs sur articles sans motif renseigné (surtout retail et e-commerce : tickets magasins, retours web). La colonne « Objet » des derniers avoirs précise la nature.</p>
<p><span class="sw" style="background:#94a3b8"></span><b>Régul.</b> — régularisation de facturation (motif IN-REGUL) : on annule une facture erronée (prix, quantité, client, TVA…) et on refacture correctement. L’avoir est compensé par une nouvelle facture, ce n’est donc <b>pas un accompagnement</b> : il est affiché pour information et exclu des totaux.</p>
<p class="meta">Montants HT rendus au client. Saison : celle de la référence avoirée ; à défaut, déduite de la date de l’avoir (février–juillet = SS, août–janvier = FW).</p></div></details>`;
const ACCS={per:'y',s:'all',y:'all',dim:'cust',all:false,regul:false,interco:false,dot:true,cat:-1,q:''};
const ACC_COL=['#c2410c','#2563eb','#7c3aed','#94a3b8','#0f766e','#cbd5e1'];
const ACC_SHORT=['Décote','Retours','Défauts/SAV','Régul.','Autres','Interco'];
function accCatOn(k){return k===3?ACCS.regul:k===5?ACCS.interco:true}
function accCustMask(){const cm=new Uint8Array(D.dicts.cust.length);const M=[['bm1',D.custBm1I],['cg',D.custCgI],['grp',D.custGrpI],['sales',D.custSalesI],['n1',D.custN1I]].filter(([d])=>F[d]&&F[d].size);
  for(let c=0;c<cm.length;c++){if(S.exclC&&c===CENT)continue;let ok=true;for(const[d,a]of M)if(!F[d].has(a[c])){ok=false;break}cm[c]=ok?1:0}return cm}
function accRows(cm,pred){const A=accData();if(!A)return[];const X=A.C,out=[];for(let i=0;i<A.n;i++){if(cm&&!cm[X.cust[i]])continue;if(!accCatOn(X.cat[i]))continue;if(!ACCS.dot&&X.dot[i])continue;if(pred&&!pred(i))continue;out.push(i)}return out}
const ACC_S0='2023-02-FW';
function accDateSe(d){const y=+d.slice(0,4),m=+d.slice(5,7);return m>=2&&m<=7?y+'-01-SS':m>=8?y+'-02-FW':(y-1)+'-02-FW'}
function accSeKey(i){let s=ACC.dicts.se[ACC.C.se[i]];if(!s)s=accDateSe(ACC.dicts.ym[ACC.C.ym[i]]+'-01');return s<ACC_S0?'old':s}
function accSeLab(k){return k===''?'Sans saison':k==='old'?'Avant FW23':sLab(k)}
function accYearOk(i){const A=ACC;if(ACCS.per==='s'){return ACCS.s==='all'||accSeKey(i)===ACCS.s}if(ACCS.y==='all')return true;return A.dicts.ym[A.C.ym[i]].slice(0,4)===ACCS.y}
function accPanel(P){
  const A=accData();if(!A){P.innerHTML='<div class="card"><div class="card-b">Données d’accompagnement indisponibles.</div></div>';return}
  const X=A.C,cm=accCustMask(),years=[...new Set(A.dicts.ym.map(v=>v.slice(0,4)))].sort();
  const rows=accRows(cm,accYearOk);const nc=A.cats.length;
  const T=new Float64Array(nc),TQ=new Float64Array(nc);for(const i of rows){T[X.cat[i]]+=X.a[i];TQ[X.cat[i]]+=X.q[i]}
  const tot=T.reduce((s,v)=>s+v,0);
  /* répartition par saison de collection (mêmes filtres, toutes périodes) */
  const SE=new Map();for(const i of accRows(cm,null)){const k=accSeKey(i);let v=SE.get(k);if(!v){v=new Float64Array(nc);SE.set(k,v)}v[X.cat[i]]+=X.a[i]}
  const ANB=annBySe(cm);
  const SEK=[...SE.keys()].filter(k=>k&&k!=='old').sort().reverse().concat(SE.has('old')?['old']:[],SE.has('')?['']:[]);
  /* agrégat par dimension */
  const keyOf=ACCS.dim==='cust'?c=>c:ACCS.dim==='grp'?c=>D.custGrpI[c]:c=>D.custSalesI[c];
  const G=new Map();for(const i of rows){if(ACCS.cat>=0&&X.cat[i]!==ACCS.cat)continue;const k=keyOf(X.cust[i]);let o=G.get(k);if(!o){o={k,c:new Float64Array(nc),t:0,q:0,cs:new Set()};G.set(k,o)}o.c[X.cat[i]]+=X.a[i];o.t+=X.a[i];o.q+=X.q[i];o.cs.add(X.cust[i])}
  const qs=ACCS.q.trim().toLowerCase();
  /* annulations PS (intranet) sur la même maille */
  const AN=annData();let annLab='';
  if(AN&&ACCS.cat<0){const ses=ACCS.per==='s'&&ACCS.s!=='all'?new Set([AN.dicts.se.indexOf(ACCS.s)]):new Set(AN.dicts.se.map((s,i)=>i).filter(i=>!AN.partial.includes(AN.dicts.se[i])));
    annLab=ACCS.per==='s'&&ACCS.s!=='all'?(annPartial(ACCS.s)?accSeLab(ACCS.s)+'*':accSeLab(ACCS.s)):[...ses].map(i=>sLab(AN.dicts.se[i])).join('+');
    const XA=AN.C;for(let i=0;i<AN.n;i++){if(!ses.has(XA.se[i])||AN.dicts.cls[XA.cls[i]]!=='PS')continue;const c=XA.cust[i];if(!cm[c])continue;const k=keyOf(c);let o=G.get(k);if(!o){o={k,c:new Float64Array(nc),t:0,q:0,cs:new Set()};G.set(k,o)}o.aqc=(o.aqc||0)+XA.qc[i];o.aqa=(o.aqa||0)+XA.qa[i];o.aaa=(o.aaa||0)+XA.aa[i];if(XA.qa[i]>0)o.cs.add(c)}}
  const annOn=!!annLab;
  let list=[...G.values()].filter(o=>Math.abs(o.t)>=1||(o.aqa||0)>0).filter(o=>{if(!qs)return true;const t=ACCS.dim==='cust'?(D.dicts.cust[o.k]+' '+(D.custCity[o.k]||'')+' '+(D.custGrp[o.k]||'')):ACCS.dim==='grp'?D.dicts.grp[o.k]:D.dicts.sales[o.k];return String(t).toLowerCase().includes(qs)}).sort((a,b)=>b.t-a.t);
  const nAll=list.length,lim=ACCS.all?list.length:40;list=list.slice(0,lim);
  /* mensuel */
  const MS=new Map();for(const i of rows){const m=A.dicts.ym[X.ym[i]];let v=MS.get(m);if(!v){v=new Float64Array(nc);MS.set(m,v)}v[X.cat[i]]+=X.a[i]}
  const months=[...MS.keys()].sort().slice(-24);
  const W=760,H=220,ml=52,mr=8,mt=14,mb=34,pw=W-ml-mr,ph=H-mt-mb;let mx=1;months.forEach(m=>{const v=MS.get(m);let s=0;for(let k=0;k<nc;k++)if(v[k]>0)s+=v[k];mx=Math.max(mx,s)});mx=niceMax(mx);
  const bw=pw/Math.max(1,months.length);let svg='';for(let k=0;k<=2;k++){const v=mx*k/2,y=mt+ph-ph*k/2;svg+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-6}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}€</text>`}
  months.forEach((m,j)=>{const v=MS.get(m);let y=mt+ph;const x=ml+j*bw+bw*.15,w=bw*.7;let tip=`<b>${m}</b>`;for(let k=0;k<nc;k++){if(v[k]<=0)continue;const h=ph*v[k]/mx;y-=h;svg+=`<rect x="${x}" y="${y}" width="${w}" height="${Math.max(h,.5)}" fill="${ACC_COL[k]}"/>`;tip+=`<br>${ACC_SHORT[k]} : ${fE(v[k])}`}
    svg+=`<rect x="${ml+j*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent" data-tip="${esc(tip)}"/>`;if(j%2===0||months.length<13)svg+=`<text x="${x+w/2}" y="${H-14}" text-anchor="middle" class="ax">${m.slice(5)}/${m.slice(2,4)}</text>`});
  const kpi=k=>`<div class="kpi acc-k${ACCS.cat===k?' on':''}" data-cat="${k}" style="cursor:pointer;border-top:4px solid ${ACC_COL[k]}"><h3>${esc(A.cats[k])}</h3><div class="big">${fM(T[k])}</div><div class="cmp">${tot?Math.round(T[k]/tot*100):0} % du total${TQ[k]?` · ${fN(TQ[k])} pcs`:''}</div></div>`;
  const shown=[...Array(nc).keys()].filter(accCatOn);
  const head=`<th class="lab">${ACCS.dim==='cust'?'Client':ACCS.dim==='grp'?'Groupement':'Commercial'}</th>${ACCS.dim==='cust'?'<th class="lab">Commercial</th>':'<th>Clients</th>'}<th>Total avoirs</th>${shown.map(k=>`<th><span class="sw" style="background:${ACC_COL[k]}"></span>${ACC_SHORT[k]}</th>`).join('')}<th>Pièces</th>${annOn?`<th class="ann-c">PS annulée ${esc(annLab)}</th><th class="ann-c">Taux annul.</th>`:''}`;
  const lab=o=>ACCS.dim==='cust'?cLabel('cust',o.k):ACCS.dim==='grp'?(D.dicts.grp[o.k]==='(non renseigné)'?'<span class="meta">Sans groupement</span>':`<button type="button" class="gchip" data-g="${o.k}">${esc(D.dicts.grp[o.k])}</button>`):esc(D.dicts.sales[o.k]);
  const body=list.map(o=>`<tr><td class="lab">${lab(o)}</td>${ACCS.dim==='cust'?`<td class="lab mutedv">${esc(D.dicts.sales[D.custSalesI[o.k]])}</td>`:`<td>${fN(o.cs.size)}</td>`}<td><b>${fE(o.t)}</b></td>${shown.map(k=>`<td>${Math.abs(o.c[k])>=1?fE(o.c[k]):'<span class="dash">—</span>'}</td>`).join('')}<td>${o.q?fN(o.q):'<span class="dash">—</span>'}</td>${annOn?`<td class="ann-c">${o.aqa>0?`<b>${fN(o.aqa)}</b> pcs · ${fE(o.aaa)}`:'<span class="dash">—</span>'}</td><td class="ann-c">${o.aqc>0?`<span class="${o.aqa/o.aqc>=.15?'neg':''}">${annPct(o.aqa,o.aqc)}</span>`:'<span class="dash">—</span>'}</td>`:''}</tr>`).join('');
  P.innerHTML=`<div class="toolbar">
    <div class="seg" id="accP"><button type="button" data-p="y" aria-pressed="${ACCS.per==='y'}">Par année d’avoir</button><button type="button" data-p="s" aria-pressed="${ACCS.per==='s'}">Par saison de collection</button></div>
    ${ACCS.per==='y'?`<div class="seg" id="accY">${['all',...years].map(y=>`<button type="button" data-y="${y}" aria-pressed="${ACCS.y===y}">${y==='all'?'Depuis 2024':y}</button>`).join('')}</div>`
      :`<div class="seg" id="accS">${['all',...SEK].map(s=>`<button type="button" data-s="${s}" aria-pressed="${ACCS.s===s}">${s==='all'?'Toutes':accSeLab(s)}</button>`).join('')}</div>`}
    <div class="seg" id="accD">${[['cust','Par client'],['grp','Par groupement'],['sales','Par commercial']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${ACCS.dim===k}">${l}</button>`).join('')}</div>
    <label class="tog"><input type="checkbox" id="accDot" ${ACCS.dot?'checked':''}> Inclure les dotations</label>
    <label class="tog"><input type="checkbox" id="accRegul" ${ACCS.regul?'checked':''}> Inclure les régularisations de facturation</label>
    <label class="tog"><input type="checkbox" id="accInter" ${ACCS.interco?'checked':''}> Inclure l’interco</label></div>
  ${ACC_MEMO}
  <div class="kpis">${shown.map(kpi).join('')}</div>
  <div class="card"><div class="card-h"><h2>Avoirs par mois</h2><span class="note">${ACCS.per==='s'?(ACCS.s==='all'?'toutes saisons':accSeLab(ACCS.s))+' · 24 derniers mois':ACCS.y==='all'?'24 derniers mois':ACCS.y} · montants HT · ${fM(tot)} au total</span></div><div class="card-b"><div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:520px;display:block" role="img" aria-label="Avoirs par mois">${svg}</svg></div>
    <div class="legend" style="margin-top:6px">${shown.map(k=>`<span><span class="sw" style="background:${ACC_COL[k]}"></span>${esc(A.cats[k])}</span>`).join('')}</div></div></div>
  ${annCard(cm)}
  <div class="card"><div class="card-h"><h2>Par saison de collection</h2><span class="note">saison de la référence avoirée ; sans référence (affiliation, décote globale…), saison déduite de la date de l’avoir · cliquez sur une ligne pour filtrer</span></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Saison</th>${shown.map(k=>`<th><span class="sw" style="background:${ACC_COL[k]}"></span>${ACC_SHORT[k]}</th>`).join('')}<th>Total avoirs</th><th class="ann-c">PS annulée</th><th class="ann-c">Taux annul. PS</th></tr></thead><tbody>
    ${SEK.map(s=>{const v=SE.get(s);const tt=shown.reduce((a,k)=>a+v[k],0);if(Math.abs(tt)<1)return'';const an=ANB.get(s);return`<tr class="acc-se${ACCS.per==='s'&&ACCS.s===s?' sel':''}" data-s="${s}" style="cursor:pointer"><td class="lab"><b>${accSeLab(s)}</b></td>${shown.map(k=>`<td>${Math.abs(v[k])>=1?fE(v[k]):'<span class="dash">—</span>'}</td>`).join('')}<td><b>${fE(tt)}</b></td><td class="ann-c">${an&&an.qa>0?`<b>${fN(an.qa)}</b> pcs · ${fE(an.aa)}`:'<span class="dash">—</span>'}</td><td class="ann-c">${an&&an.qc>0?`${annPct(an.qa,an.qc)}${annPartial(s)?' *':''}`:'<span class="dash">—</span>'}</td></tr>`}).join('')}</tbody></table></div></div>
  <div class="card"><div class="card-h"><h2>${ACCS.cat>=0?esc(A.cats[ACCS.cat])+' · ':''}${ACCS.per==='s'&&ACCS.s!=='all'?accSeLab(ACCS.s)+' · ':''}Accompagnement ${ACCS.dim==='cust'?'par client':ACCS.dim==='grp'?'par groupement':'par commercial'}</h2>
    <span class="note">${fN(nAll)} ligne${nAll>1?'s':''}${ACCS.cat>=0?' · <button type="button" class="linkbtn" id="accCatX">toutes catégories</button>':' · cliquez sur une catégorie pour filtrer'}</span></div>
    <div class="card-b" style="padding-bottom:0;display:flex;gap:10px;flex-wrap:wrap;align-items:center"><div class="seg" id="accD2">${[['cust','Par client'],['grp','Par groupement'],['sales','Par commercial']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${ACCS.dim===k}">${l}</button>`).join('')}</div><input type="search" id="accQ" placeholder="Rechercher un client, une ville, un groupement…" value="${esc(ACCS.q)}" style="flex:1 1 280px;max-width:420px;padding:8px 12px;border:1px solid var(--line-2);border-radius:8px;font:inherit"></div>
    <div class="scroll"><table class="pv"><thead><tr>${head}</tr></thead><tbody>${body||`<tr class="more"><td colspan="${4+shown.length+(annOn?2:0)}">Aucun avoir sur cette sélection.</td></tr>`}
    ${nAll>lim?`<tr class="more"><td colspan="${4+shown.length+(annOn?2:0)}"><button type="button" class="linkbtn" id="accAll">Afficher les ${fN(nAll-lim)} autres</button></td></tr>`:''}</tbody></table></div></div>
  <div class="card"><div class="card-b meta" style="margin:0">Source : ${esc(A.src)}, extraction du ${fD(A.asof)}. Montants HT en euros, en positif = montant rendu au client. Catégories : <b>Décote</b> = avoirs de markdown (flag « Is Decote » ou motif MARK-DOWN) ; <b>Retours</b> = motifs RETURN ; <b>Défauts / SAV / litiges</b> = DEFECTIVE, AS, CLAIM ; <b>Régularisations</b> = IN-REGUL (annulations/refacturations, souvent compensées par une nouvelle facture, masquées par défaut) ; <b>Autres avoirs</b> = affiliation, remises et motifs non renseignés. Les filtres clients du haut de page s’appliquent.</div></div>`;
  annBind(P,()=>{const y=window.scrollY;accPanel(P);window.scrollTo(0,y)});
  const on=(id,fn)=>{const el=$(id);if(el)el.onclick=e=>{const b=e.target.closest('button');if(b){fn(b);accPanel(P)}}};
  on('#accY',b=>{ACCS.y=b.dataset.y;ACCS.all=false});on('#accS',b=>{ACCS.s=b.dataset.s;ACCS.all=false});on('#accP',b=>{ACCS.per=b.dataset.p;ACCS.all=false});
  P.querySelectorAll('tr.acc-se').forEach(r=>r.onclick=()=>{const s=r.dataset.s;if(ACCS.per==='s'&&ACCS.s===s){ACCS.s='all'}else{ACCS.per='s';ACCS.s=s}ACCS.all=false;accPanel(P)});on('#accD',b=>{ACCS.dim=b.dataset.d;ACCS.all=false});{const el=$('#accD2');if(el)el.onclick=e=>{const b=e.target.closest('button');if(!b)return;ACCS.dim=b.dataset.d;ACCS.all=false;const y=window.scrollY;accPanel(P);window.scrollTo(0,y)}}
  $('#accDot').onchange=e=>{ACCS.dot=e.target.checked;accPanel(P)};$('#accRegul').onchange=e=>{ACCS.regul=e.target.checked;accPanel(P)};$('#accInter').onchange=e=>{ACCS.interco=e.target.checked;accPanel(P)};
  P.querySelectorAll('.acc-k').forEach(k=>k.onclick=()=>{const c=+k.dataset.cat;ACCS.cat=ACCS.cat===c?-1:c;ACCS.all=false;accPanel(P)});
  const cx=$('#accCatX');if(cx)cx.onclick=()=>{ACCS.cat=-1;accPanel(P)};const aa=$('#accAll');if(aa)aa.onclick=()=>{ACCS.all=true;accPanel(P)};
  const q=$('#accQ');if(q){let qt;q.oninput=e=>{clearTimeout(qt);qt=setTimeout(()=>{ACCS.q=e.target.value;ACCS.all=false;accPanel(P);const el=$('#accQ');el.focus();el.setSelectionRange(el.value.length,el.value.length)},250)}}
}
/* ================= annulations de commandes (intranet X3) ================= */
let ANN=null;function annData(){if(ANN)return ANN;try{ANN=JSON.parse(document.getElementById('anndata').textContent)}catch(e){ANN=null}return ANN}
const ANNS={se:null,cls:'PS',dim:'cust',all:false,q:''};
const ANN_CLS=[['PS','Pré-saison (PS)'],['RE','Réassort (RE)'],['all','Tous types']];
function annPartial(s){const A=annData();return A&&A.partial.includes(s)}
function annAgg(cm,se,cls){const A=annData(),X=A.C,T={qc:0,ac:0,qa:0,aa:0,ql:0,al:0};const by=new Map();
  for(let i=0;i<A.n;i++){if(X.se[i]!==se)continue;const c=X.cust[i];if(cm&&!cm[c])continue;const k=A.dicts.cls[X.cls[i]];if(cls!=='all'&&k!==cls)continue;
    for(const f of['qc','ac','qa','aa','ql','al'])T[f]+=X[f][i];let o=by.get(c);if(!o){o={qc:0,ac:0,qa:0,aa:0,ql:0,al:0};by.set(c,o)}for(const f of['qc','ac','qa','aa','ql','al'])o[f]+=X[f][i]}
  return{T,by}}
function annBySe(cm){const A=annData(),m=new Map();if(!A)return m;const X=A.C;for(let i=0;i<A.n;i++){if(!cm[X.cust[i]])continue;if(A.dicts.cls[X.cls[i]]!=='PS')continue;const s=A.dicts.se[X.se[i]];let o=m.get(s);if(!o){o={qc:0,qa:0,aa:0};m.set(s,o)}o.qc+=X.qc[i];o.qa+=X.qa[i];o.aa+=X.aa[i]}return m}
const annPct=(a,b)=>b>0?(a/b*100).toLocaleString('fr-FR',{maximumFractionDigits:1})+' %':'—';
function annCard(cm){
  const A=annData();if(!A)return'';const ss=A.dicts.se;if(ANNS.se==null){const cur=curSeasonName();ANNS.se=Math.max(0,ss.indexOf(cur)>=0?ss.indexOf(cur):ss.length-1)}
  const sN=ss[ANNS.se];const{T,by}=annAgg(cm,ANNS.se,ANNS.cls);
  const keyOf=ANNS.dim==='cust'?c=>c:ANNS.dim==='grp'?c=>D.custGrpI[c]:c=>D.custSalesI[c];
  const G=new Map();for(const[c,o]of by){const k=keyOf(c);let g=G.get(k);if(!g){g={k,qc:0,ac:0,qa:0,aa:0,ql:0,al:0,n:0};G.set(k,g)}for(const f of['qc','ac','qa','aa','ql','al'])g[f]+=o[f];if(o.qa>0)g.n++}
  const qs=ANNS.q.trim().toLowerCase();
  let list=[...G.values()].filter(g=>g.qa>0||g.aa>0).filter(o=>{if(!qs)return true;const t=ANNS.dim==='cust'?(D.dicts.cust[o.k]+' '+(D.custCity[o.k]||'')+' '+(D.custGrp[o.k]||'')):ANNS.dim==='grp'?D.dicts.grp[o.k]:D.dicts.sales[o.k];return String(t).toLowerCase().includes(qs)}).sort((a,b)=>b.aa-a.aa);
  const nAll=list.length,lim=ANNS.all?nAll:30;list=list.slice(0,lim);
  const lab=o=>ANNS.dim==='cust'?cLabel('cust',o.k):ANNS.dim==='grp'?(D.dicts.grp[o.k]==='(non renseigné)'?'<span class="meta">Sans groupement</span>':`<button type="button" class="gchip" data-g="${o.k}">${esc(D.dicts.grp[o.k])}</button>`):esc(D.dicts.sales[o.k]);
  const clsL=(ANN_CLS.find(x=>x[0]===ANNS.cls)||[])[1];
  return`<div class="card" id="annCard"><div class="card-h"><h2>Annulations de commandes · ${sLab(sN)}</h2><span class="note">${esc(clsL)} · source intranet X3 (état des commandes clients) au ${fD(A.asof)}</span></div>
  <div class="card-b"><div class="toolbar" style="margin:0 0 10px">
    <div class="seg" id="annSe">${ss.map((s,i)=>`<button type="button" data-i="${i}" aria-pressed="${i===ANNS.se}">${sLab(s)}${annPartial(s)?'*':''}</button>`).join('')}</div>
    <div class="seg" id="annCls">${ANN_CLS.map(([k,l])=>`<button type="button" data-k="${k}" aria-pressed="${ANNS.cls===k}">${l}</button>`).join('')}</div>
    <div class="seg" id="annDim">${[['cust','Par client'],['grp','Par groupement'],['sales','Par commercial']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${ANNS.dim===k}">${l}</button>`).join('')}</div></div>
  ${annPartial(sN)?`<p class="warnline">* ${sLab(sN)} : l’intranet ne conserve qu’une partie des commandes de cette saison (commandes anciennes archivées). Montants et taux à lire avec prudence.</p>`:''}
  <div class="kpis mini">
    <div class="kpi"><h3>Commandé ${ANNS.cls==='all'?'':ANNS.cls}</h3><div class="big">${fN(T.qc)} pcs</div><div class="cmp">${fM(T.ac)}</div></div>
    <div class="kpi" style="border-top:4px solid #b91c1c"><h3>Annulé</h3><div class="big">${fN(T.qa)} pcs</div><div class="cmp">${fM(T.aa)}</div></div>
    <div class="kpi"><h3>Taux d’annulation</h3><div class="big">${annPct(T.qa,T.qc)}</div><div class="cmp">en pièces · ${annPct(T.aa,T.ac)} en valeur</div></div></div>
  <input type="search" id="annQ" placeholder="Rechercher un client, une ville, un groupement…" value="${esc(ANNS.q)}" style="width:100%;max-width:420px;padding:8px 12px;border:1px solid var(--line-2);border-radius:8px;font:inherit;margin:10px 0 0"></div>
  <div class="scroll"><table class="pv"><thead><tr><th class="lab">${ANNS.dim==='cust'?'Client':ANNS.dim==='grp'?'Groupement':'Commercial'}</th>${ANNS.dim==='cust'?'<th class="lab">Commercial</th>':'<th>Clients concernés</th>'}<th>Commandé (pcs)</th><th>Commandé (€)</th><th>Annulé (pcs)</th><th>Annulé (€)</th><th>Taux pcs</th><th>Taux €</th></tr></thead><tbody>
  ${list.map(o=>`<tr><td class="lab">${lab(o)}</td>${ANNS.dim==='cust'?`<td class="lab mutedv">${esc(D.dicts.sales[D.custSalesI[o.k]])}</td>`:`<td>${fN(o.n)}</td>`}<td>${fN(o.qc)}</td><td>${fE(o.ac)}</td><td><b>${fN(o.qa)}</b></td><td><b>${fE(o.aa)}</b></td><td><span class="${o.qc&&o.qa/o.qc>=.15?'neg':''}">${annPct(o.qa,o.qc)}</span></td><td>${annPct(o.aa,o.ac)}</td></tr>`).join('')||`<tr class="more"><td colspan="8">Aucune annulation sur cette sélection.</td></tr>`}
  ${nAll>lim?`<tr class="more"><td colspan="8"><button type="button" class="linkbtn" id="annAll">Afficher les ${fN(nAll-lim)} autres</button></td></tr>`:''}</tbody></table></div>
  <div class="card-b" style="padding-top:8px"><button type="button" class="btn" id="annXls">Télécharger le détail des annulations (Excel)</button> <span class="meta" id="annXlsMsg">toutes saisons · par client, saison et type de commande + références annulées · filtres clients appliqués</span></div>
  <div class="card-b meta" style="margin:0">Quantités et montants annulés = lignes de commande annulées (soldées sans livraison) dans X3, comparés au commandé du même type de commande. Le commandé est celui du carnet de commandes SEI (référence du site) ; l’intranet fournit les quantités et montants annulés et le livré. Taux = annulé ÷ commandé. Une annulation récente peut encore figurer en « reste à livrer » dans le carnet SEI tant que celui-ci n’est pas mis à jour. Client = client commandeur (magasin), y compris pour les commandes facturées à une centrale.</div></div>`}
function annBind(P,rerender){
  const on=(id,fn)=>{const el=P.querySelector(id);if(el)el.onclick=e=>{const b=e.target.closest('button');if(b){fn(b);ANNS.all=false;rerender()}}};
  on('#annSe',b=>ANNS.se=+b.dataset.i);on('#annCls',b=>ANNS.cls=b.dataset.k);on('#annDim',b=>ANNS.dim=b.dataset.d);
  const xb=P.querySelector('#annXls');if(xb)xb.onclick=async()=>{try{const A=annData(),X=A.C,cm=accCustMask();
    const rows=[['Client','Ville','Groupement','Commercial','Saison','Type commande','Qté commandée','Montant commandé €','Qté annulée','Montant annulé €','Taux annulation pcs','Saison partielle (intranet)']];
    for(let i=0;i<A.n;i++){const c=X.cust[i];if(!cm[c])continue;const s=A.dicts.se[X.se[i]];rows.push([D.dicts.cust[c],D.custCity[c]||'',D.custGrp[c]||'',D.dicts.sales[D.custSalesI[c]],sLab(s),A.dicts.cls[X.cls[i]],X.qc[i],Math.round(X.ac[i]*100)/100,X.qa[i],Math.round(X.aa[i]*100)/100,X.qc[i]?Math.round(X.qa[i]/X.qc[i]*1000)/1000:'',annPartial(s)?'oui':''])}
    const ii=new Map(D.dicts.item.map((c,i)=>[c,i]));const it=[['Client','Ville','Groupement','Saison','Référence','Désignation','Qté commandée','Qté annulée','Montant annulé €']];
    for(const r of A.items){if(!cm[r[0]])continue;const k=ii.get(r[2]);it.push([D.dicts.cust[r[0]],D.custCity[r[0]]||'',D.custGrp[r[0]]||'',sLab(A.dicts.se[r[1]]),r[2],k!=null?D.itemDesc[k]||'':'',r[5],r[3],r[4]])}
    const wb=XLSX.utils.book_new();const s1=XLSX.utils.aoa_to_sheet(rows);s1['!cols']=[{wch:9},{wch:22},{wch:16},{wch:22},{wch:8},{wch:8},{wch:12},{wch:14},{wch:11},{wch:14},{wch:11},{wch:10},{wch:14},{wch:10}];XLSX.utils.book_append_sheet(wb,s1,'Annulations par client');
    const s2=XLSX.utils.aoa_to_sheet(it);s2['!cols']=[{wch:9},{wch:22},{wch:16},{wch:8},{wch:10},{wch:34},{wch:12},{wch:11},{wch:14}];XLSX.utils.book_append_sheet(wb,s2,'Références annulées');
    const buf=XLSX.write(wb,{type:'array',bookType:'xlsx',compression:true});await fileSave('Annulations_commandes_'+A.asof+'.xlsx',new Blob([buf],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}),P.querySelector('#annXlsMsg'))}catch(e){const m=P.querySelector('#annXlsMsg');if(m)m.textContent='Export impossible : '+(e&&e.message||e)}};
  const a=P.querySelector('#annAll');if(a)a.onclick=()=>{ANNS.all=true;rerender()};
  const q=P.querySelector('#annQ');if(q){let qt;q.oninput=e=>{clearTimeout(qt);qt=setTimeout(()=>{ANNS.q=e.target.value;ANNS.all=false;rerender();const el=P.querySelector('#annQ');if(el){el.focus();el.setSelectionRange(el.value.length,el.value.length)}},250)}}
}
function annFiche(cm){
  const A=annData();if(!A)return'';const X=A.C,ss=A.dicts.se,ns=ss.length;const R=[...Array(ns)].map(()=>({PS:[0,0,0,0],RE:[0,0,0,0],T:[0,0,0,0]}));let any=false;
  for(let i=0;i<A.n;i++){if(!cm[X.cust[i]])continue;const k=A.dicts.cls[X.cls[i]];const r=R[X.se[i]];const v=[X.qc[i],X.ac[i],X.qa[i],X.aa[i]];for(const key of[k==='PS'?'PS':k==='RE'?'RE':null,'T'])if(key)for(let j=0;j<4;j++)r[key][j]+=v[j];if(X.qc[i])any=true}
  if(!any)return'';
  const rows=[...Array(ns).keys()].reverse().filter(s=>R[s].PS[0]>0||R[s].T[2]>0).map(s=>{const r=R[s];return`<tr><td class="lab"><b>${sLab(ss[s])}</b>${annPartial(ss[s])?' <span class="meta" title="Saison partiellement archivée dans l’intranet">*</span>':''}</td><td>${fN(r.PS[0])}</td><td><b>${fN(r.PS[2])}</b></td><td>${fE(r.PS[3])}</td><td><span class="${r.PS[0]&&r.PS[2]/r.PS[0]>=.15?'neg':''}">${annPct(r.PS[2],r.PS[0])}</span></td><td>${fN(r.RE[2])}</td><td>${fN(r.T[2])}</td><td>${fE(r.T[3])}</td></tr>`}).join('');
  const it=new Map();for(const r of A.items){if(!cm[r[0]])continue;const k=r[2];let o=it.get(k);if(!o){o={qa:0,aa:0,qc:0,se:new Set()};it.set(k,o)}o.qa+=r[3];o.aa+=r[4];o.qc+=r[5];o.se.add(r[1])}
  const top=[...it.entries()].sort((a,b)=>b[1].aa-a[1].aa).slice(0,8);const ii=new Map(D.dicts.item.map((c,i)=>[c,i]));
  return`<section><h3 class="fs">Annulations de commandes <span class="meta">intranet X3 · pièces annulées (intranet) vs PS commandée (carnet SEI)</span></h3>
  <div class="scroll"><table class="pv"><thead><tr><th class="lab">Saison</th><th>PS commandée (pcs)</th><th>PS annulée (pcs)</th><th>PS annulée (€)</th><th>Taux PS</th><th>RE annulé (pcs)</th><th>Total annulé (pcs)</th><th>Total annulé (€)</th></tr></thead><tbody>${rows}</tbody></table></div>
  ${top.length?`<details style="margin-top:6px"><summary class="linkbtn">Références les plus annulées</summary><div class="scroll"><table class="pv"><thead><tr><th class="lab">Référence</th><th class="lab">Saison(s)</th><th>Commandé</th><th>Annulé</th><th>Montant annulé</th></tr></thead><tbody>${top.map(([k,o])=>{const i=ii.get(k);return`<tr><td class="lab"><span class="dif-ref">${pImg(k,40)}<span>${i!=null?cLabel('item',i):'<b>'+esc(k)+'</b>'}</span></span></td><td class="lab mutedv">${[...o.se].map(s=>sLab(ss[s])).join(', ')}</td><td>${fN(o.qc)}</td><td><b>${fN(o.qa)}</b></td><td>${fE(o.aa)}</td></tr>`}).join('')}</tbody></table></div></details>`:''}
  ${rows.includes('*')?'<p class="meta" style="margin:4px 0 0">* saison partiellement archivée dans l’intranet.</p>':''}</section>`}

/* section fiche client / groupement */
function accFiche(cm){
  const A=accData();if(!A)return'';const X=A.C,nc=A.cats.length,years=[...new Set(A.dicts.ym.map(v=>v.slice(0,4)))].sort();
  const Y=new Map();let any=false;for(let i=0;i<A.n;i++){if(!cm[X.cust[i]])continue;const k=X.cat[i];if(k===5)continue;const y=A.dicts.ym[X.ym[i]].slice(0,4);let v=Y.get(y);if(!v){v=new Float64Array(nc);Y.set(y,v)}v[k]+=X.a[i];any=true}
  if(!any)return`<section><h3 class="fs">Accompagnement commercial <span class="meta">avoirs SEI depuis 2024</span></h3><p class="meta">Aucun avoir enregistré pour ${'ce périmètre'} depuis 2024.</p></section>`;
  const cats=[0,1,2,4,3];
  const rows=years.filter(y=>Y.has(y)).map(y=>{const v=Y.get(y);const t=cats.filter(k=>k!==3).reduce((s,k)=>s+v[k],0);return`<tr><td class="lab"><b>${y}</b></td>${cats.map(k=>`<td${k===3?' class="mutedv"':''}>${Math.abs(v[k])>=1?fE(v[k]):'<span class="dash">—</span>'}</td>`).join('')}<td><b>${fE(t)}</b></td></tr>`}).join('');
  const SEf=new Map();for(let i=0;i<A.n;i++){if(!cm[X.cust[i]])continue;const k=X.cat[i];if(k===5||k===3)continue;const s=accSeKey(i);let v=SEf.get(s);if(!v){v=new Float64Array(nc);SEf.set(s,v)}v[k]+=X.a[i]}
  const sek=[...SEf.keys()].filter(k=>k&&k!=='old').sort().reverse().concat(SEf.has('old')?['old']:[],SEf.has('')?['']:[]);
  const cats2=[0,1,2,4];
  const ANB=annBySe(cm);const sek2=[...new Set([...sek,...[...ANB.keys()].filter(s=>ANB.get(s).qa>0&&s>=ACC_S0)])].sort((a,b)=>a===''?1:b===''?-1:a==='old'?1:b==='old'?-1:b.localeCompare(a));
  const seRows=sek2.map(s=>{const v=SEf.get(s)||new Float64Array(nc);const tt=cats2.reduce((a,k)=>a+v[k],0);const an=ANB.get(s);if(Math.abs(tt)<1&&!(an&&an.qa>0))return'';return`<tr><td class="lab"><b>${accSeLab(s)}</b></td>${cats2.map(k=>`<td>${Math.abs(v[k])>=1?fE(v[k]):'<span class="dash">—</span>'}</td>`).join('')}<td><b>${fE(tt)}</b></td><td class="ann-c">${an&&an.qa>0?`<b>${fN(an.qa)}</b> pcs · ${fE(an.aa)}`:'<span class="dash">—</span>'}</td><td class="ann-c">${an&&an.qc>0?`<span class="${an.qa/an.qc>=.15?'neg':''}">${annPct(an.qa,an.qc)}</span>${annPartial(s)?' *':''}`:'<span class="dash">—</span>'}</td></tr>`}).join('');
  const docs=A.docs.filter(d=>cm[d[0]]&&d[2]!==5&&d[2]!==3).sort((a,b)=>b[3].localeCompare(a[3])).slice(0,12);
  const itm=new Map();for(const r of A.items){if(!cm[r[0]])continue;const k=r[2];let o=itm.get(k);if(!o){o={a:0,q:0,c:new Float64Array(3)};itm.set(k,o)}o.a+=r[3];o.q+=r[4];o.c[r[1]]+=r[3]}
  const top=[...itm.entries()].sort((a,b)=>b[1].a-a[1].a).slice(0,8);
  const ii=new Map(D.dicts.item.map((c,i)=>[c,i]));
  return`<section><h3 class="fs">Accompagnement commercial <span class="meta">avoirs SEI depuis 2024 · montants HT rendus au client</span></h3>${ACC_MEMO}
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Année</th>${cats.map(k=>`<th><span class="sw" style="background:${ACC_COL[k]}"></span>${ACC_SHORT[k]}</th>`).join('')}<th>Total (hors régul.)</th></tr></thead><tbody>${rows}</tbody></table></div><p class="meta" style="margin:6px 0 0">Par saison ci-dessous : saison de la référence avoirée ; pour les avoirs sans référence (affiliation, décote globale…), saison déduite de la date de l’avoir (février–juillet = SS, août–janvier = FW).</p>
    ${seRows?`<div class="scroll" style="margin-top:10px"><table class="pv"><thead><tr><th class="lab" title="Saison de la référence avoirée ; sinon saison déduite de la date de l’avoir">Saison</th>${cats2.map(k=>`<th><span class="sw" style="background:${ACC_COL[k]}"></span>${ACC_SHORT[k]}</th>`).join('')}<th>Total avoirs</th><th class="ann-c">PS annulée</th><th class="ann-c">Taux annul. PS</th></tr></thead><tbody>${seRows}</tbody></table></div>`:''}
    ${docs.length?`<details style="margin-top:8px"><summary class="linkbtn">Derniers avoirs (${docs.length})</summary><div class="scroll"><table class="pv"><thead><tr><th class="lab">Date</th><th class="lab">Saison</th><th class="lab">Avoir</th><th class="lab">Nature</th><th class="lab">Objet</th><th class="lab">Motif</th><th>Pièces</th><th>Montant</th></tr></thead><tbody>${docs.map(d=>`<tr><td class="lab">${fD(d[3])}</td><td class="lab">${d[8]?sLab(d[8]):`${sLab(accDateSe(d[3]))} <span class="meta">(date)</span>`}</td><td class="lab">${esc(d[1])}${D.dicts.cust[d[0]]&&cm.reduce?'':''}</td><td class="lab"><span class="sw" style="background:${ACC_COL[d[2]]}"></span>${ACC_SHORT[d[2]]}${d[7]?' · dotation':''}</td><td class="lab">${esc(d[9]||'')}</td><td class="lab mutedv">${esc(d[6]||'—')}</td><td>${d[5]?fN(d[5]):'—'}</td><td><b>${fE(d[4])}</b></td></tr>`).join('')}</tbody></table></div></details>`:''}
    ${top.length?`<details style="margin-top:6px"><summary class="linkbtn">Références les plus concernées (décote, retours, défauts)</summary><div class="scroll"><table class="pv"><thead><tr><th class="lab">Référence</th><th>Pièces</th><th>Montant</th><th class="lab">Nature</th></tr></thead><tbody>${top.map(([k,o])=>{const i=ii.get(k);const nat=[0,1,2].filter(j=>Math.abs(o.c[j])>=1).map(j=>ACC_SHORT[j]).join(', ');return`<tr><td class="lab"><span class="dif-ref">${pImg(k,40)}<span>${i!=null?cLabel('item',i):'<b>'+esc(k)+'</b>'}</span></span></td><td>${fN(o.q)}</td><td><b>${fE(o.a)}</b></td><td class="lab mutedv">${nat}</td></tr>`}).join('')}</tbody></table></div></details>`:''}
  </section>`;
}

const DIF={mode:'item',all:false,vis:false,q:''};
function difPanel(P){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);const key=DIF.mode==='item'?'item':'line';
  const doorsN=new Set(),doorsP=new Set(),M=new Map();
  const add=(i,isN)=>{const a=C.amt[i];if(a<=0)return;const c=C.cust[i];(isN?doorsN:doorsP).add(c);const k=C[key][i];let o=M.get(k);if(!o){o={dn:new Set(),dp:new Set(),an:0,ap:0,qn:0,qp:0,fam:C.fam[i],line:C.line[i]};M.set(k,o)}if(isN){o.dn.add(c);o.an+=a;o.qn+=C.qty[i]}else{o.dp.add(c);o.ap+=a;o.qp+=C.qty[i]}};
  R.iN.forEach(i=>add(i,true));R.iP.forEach(i=>add(i,false));
  const DN=doorsN.size,DP=doorsP.size;
  let rows=[...M.entries()].filter(([,o])=>o.dn.size>0).sort((a,b)=>b[1].dn.size-a[1].dn.size||b[1].an-a[1].an);
  const RK=new Map(rows.map(([k],i)=>[k,i+1]));
  const total=rows.length,med=total?rows[Math.floor(total/2)][1].dn.size/Math.max(1,DN):0;
  const qs=DIF.q.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').split(/\s+/).filter(Boolean);
  if(qs.length){const nz=s=>(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');rows=rows.filter(([k,o])=>{const h=nz(DIF.mode==='item'?D.dicts.item[k]+' '+(D.itemDesc[k]||'')+' '+D.dicts.line[o.line]+' '+D.dicts.fam[o.fam]:D.dicts.line[k]+' '+D.dicts.fam[o.fam]);return qs.every(w=>h.includes(w))})}
  const found=rows.length;
  const hidden=!DIF.all&&found>50?found-50:0;if(hidden)rows=rows.slice(0,50);
  const pc=(a,b)=>b?Math.round(a/b*100):0;
  P.innerHTML=`<div class="toolbar"><input type="search" id="difQ" placeholder="Rechercher une réf., un nom, une ligne… (ex. 2710740, lucie, essentials)" value="${esc(DIF.q)}" autocomplete="off" style="flex:1 1 320px;min-width:240px;padding:8px 12px;border:1px solid var(--line-2);border-radius:8px;font:inherit">${famSeg()}<div class="seg" id="difM" role="group"><button type="button" data-m="item" aria-pressed="${DIF.mode==='item'}">Par référence</button><button type="button" data-m="line" aria-pressed="${DIF.mode==='line'}">Par ligne produit</button></div>${DIF.mode==='item'?`<div class="seg" id="difV" role="group"><button type="button" data-v="0" aria-pressed="${!DIF.vis}">Tableau</button><button type="button" data-v="1" aria-pressed="${DIF.vis}">Vue visuelle</button></div>`:''}</div>
  <div class="card"><div class="card-h"><h2>Diffusion de l'offre ${n}</h2><span class="note">${fN(total)} ${DIF.mode==='item'?'références':'lignes'} commandées par ${fN(DN)} portes actives · diffusion médiane ${Math.round(med*100)} %</span>${qs.length?`</div><div class="card-b meta" style="padding-top:0;margin:0">${found?`${fN(found)} résultat${found>1?'s':''} pour « ${esc(DIF.q.trim())} » · rang et diffusion calculés sur toute l'offre`:`Aucun résultat pour « ${esc(DIF.q.trim())} » dans l'offre ${n} filtrée.`}`:''}</div>
  ${DIF.mode==='item'&&DIF.vis?`<div class="card-b"><div class="dif-grid">${rows.map(([k,o],r)=>`<div class="dif-card"><span class="dif-rk">${RK.get(k)}</span>${pImg(D.dicts.item[k],132)}<div class="dif-cl">${cLabel('item',k)}</div><div class="meta">${esc(D.dicts.line[o.line])}</div>
    <div class="dif-m"><span class="dif"><i style="width:${pc(o.dn.size,DN)}%"></i></span><b>${pc(o.dn.size,DN)} %</b> <span class="meta">${fN(o.dn.size)} portes${o.dp.size?` · ${p} ${pc(o.dp.size,DP)} %`:''}</span></div>
    <div class="meta">${fN(o.qn)} pcs · ${fN(o.qn/Math.max(1,o.dn.size))} pcs/porte · ${fE(o.an)}</div></div>`).join('')}</div>
    ${hidden?`<div style="margin-top:12px"><button type="button" class="linkbtn" id="difAll">Afficher les ${fN(hidden)} autres</button></div>`:''}</div></div>`:`
  <div class="scroll"><table class="pv"><thead><tr><th class="lab">${DIF.mode==='item'?'Référence':'Ligne produit'}</th>${DIF.mode==='item'?'<th class="lab">Ligne</th>':''}<th class="cn">Diffusion ${n}<span class="bar"></span></th><th class="cp">Diffusion ${p}<span class="bar"></span></th><th>Profondeur ${n}</th><th>CA ${n}</th><th>Qté ${n}</th></tr></thead><tbody>
  ${rows.map(([k,o])=>`<tr><td class="lab">${DIF.mode==='item'?`<span class="dif-ref">${pImg(D.dicts.item[k],48)}<span>${cLabel(key,k)}</span></span>`:cLabel(key,k)}</td>${DIF.mode==='item'?`<td class="lab mutedv">${esc(D.dicts.line[o.line])}</td>`:''}<td><span class="dif"><i style="width:${pc(o.dn.size,DN)}%"></i></span> ${pc(o.dn.size,DN)} % <span class="meta">${fN(o.dn.size)} p.</span></td><td>${o.dp.size?pc(o.dp.size,DP)+' %':'<span class="dash">—</span>'}</td><td>${fN(o.qn/Math.max(1,o.dn.size))} <span class="meta">pcs/porte</span></td><td>${fE(o.an)}</td><td>${fN(o.qn)}</td></tr>`).join('')}
  ${hidden?`<tr class="more"><td colspan="7"><button type="button" class="linkbtn" id="difAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}</tbody></table></div></div>`}`;
  bindFam();
  const dq=$('#difQ');if(dq){let qt;dq.oninput=e=>{clearTimeout(qt);qt=setTimeout(()=>{DIF.q=e.target.value;DIF.all=false;difPanel(P);const el=$('#difQ');el.focus();el.setSelectionRange(el.value.length,el.value.length)},250)}}
  const dv=$('#difV');if(dv)dv.onclick=e=>{const b=e.target.closest('button');if(b){DIF.vis=b.dataset.v==='1';difPanel(P)}};
  $('#difM').onclick=e=>{const b=e.target.closest('button');if(b){DIF.mode=b.dataset.m;DIF.all=false;difPanel(P)}};
  const a=$('#difAll');if(a)a.onclick=()=>{DIF.all=true;difPanel(P)};
}
const AL={drop:30,min:5000,lostMin:10000,lineMin:20000,crit:20000,warn:5000,lvl:'crit',sales:-1,showSet:false};
function alertData(){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const items=[];
  for(let c=0;c<R.cN.length;c++){const a=R.cN[c],b=R.cP[c];
    if(R.st[c]===1&&b>=AL.min&&(a-b)/b<=-AL.drop/100)items.push({c,type:'baisse',stake:b-a});
    else if(R.st[c]===2&&b>=AL.lostMin)items.push({c,type:'perdue',stake:b})}
  // détail par catégorie pour expliquer la baisse
  const need=new Map(items.map(x=>[x.c,x]));
  const cat=new Map();const add=(i,isN)=>{const c=C.cust[i];if(!need.has(c))return;const k=C.fam[i]*5+segOfRow(i);let m=cat.get(c);if(!m){m=new Map();cat.set(c,m)}const v=m.get(k)||[0,0];v[isN?0:1]+=C.amt[i];m.set(k,v)};
  R.iN.forEach(i=>add(i,true));R.iP.forEach(i=>add(i,false));
  // reste à facturer (tous types) par client
  const bl=new Map(),blN=new Map();const sN=D.dicts.season[S.n];if(BL){for(let i=0;i<BL.n;i++){const c=BL.cols.cust[i];if(!need.has(c))continue;const a=BL.cols.a[i];bl.set(c,(bl.get(c)||0)+a);if(BL.dicts.col[BL.cols.col[i]]===sN)blN.set(c,(blN.get(c)||0)+a)}}
  // pièces et nb de références par client
  const qn=new Map(),qp=new Map(),rn=new Map(),rp=new Map();
  for(const i of R.iN){const c=C.cust[i];if(!need.has(c))continue;qn.set(c,(qn.get(c)||0)+C.qty[i]);if(C.amt[i]>0){let s=rn.get(c);if(!s){s=new Set();rn.set(c,s)}s.add(C.item[i])}}
  for(const i of R.iP){const c=C.cust[i];if(!need.has(c))continue;qp.set(c,(qp.get(c)||0)+C.qty[i]);if(C.amt[i]>0){let s=rp.get(c);if(!s){s=new Set();rp.set(c,s)}s.add(C.item[i])}}
  // évolution du réseau (groupe client) sur les portes comparables
  const netN=new Map(),netP=new Map();for(let c=0;c<R.cN.length;c++){if(R.st[c]!==1)continue;const g=D.custCgI[c];netN.set(g,(netN.get(g)||0)+R.cN[c]);netP.set(g,(netP.get(g)||0)+R.cP[c])}
  // dernière saison commandée avant N (portes sans commande)
  const last=new Map();const lostSet=new Set(items.filter(x=>x.type==='perdue').map(x=>x.c));if(lostSet.size){for(const i of R.all){const c=C.cust[i];if(!lostSet.has(c)||C.amt[i]<=0)continue;const s=C.season[i];if(s!==S.n&&(!last.has(c)||s>last.get(c)))last.set(c,s)}}
  items.forEach(x=>{x.lvl=x.stake>=AL.crit?'crit':x.stake>=AL.warn?'warn':'info';x.sales=D.custSalesI[x.c];
    const m=cat.get(x.c);let worst=null;if(m)m.forEach((v,k)=>{const d=v[0]-v[1];if(!worst||d<worst.d)worst={k,d,n:v[0],p:v[1]}});x.worst=worst;x.bl=bl.get(x.c)||0;x.blN=blN.get(x.c)||0;
    const cats=m?[...m.entries()].map(([k,v])=>({k,n:v[0],p:v[1],d:v[0]-v[1]})):[];x.cats=cats;x.qn=qn.get(x.c)||0;x.qp=qp.get(x.c)||0;x.rn=(rn.get(x.c)||new Set()).size;x.rp=(rp.get(x.c)||new Set()).size;
    const g=D.custCgI[x.c];x.netN=netN.get(g)||0;x.netP=netP.get(g)||0;x.last=last.get(x.c);x.grp=D.custGrpI[x.c]});
  items.sort((a,b)=>b.stake-a.stake);
  // lignes produit : en fort recul (encore actives) / disparues
  const L=new Map();for(const i of R.iN){const k=C.line[i];const o=L.get(k)||{n:0,p:0,f:C.fam[i]};o.n+=C.amt[i];L.set(k,o)}for(const i of R.iP){const k=C.line[i];const o=L.get(k)||{n:0,p:0,f:C.fam[i]};o.p+=C.amt[i];L.set(k,o)}
  const down=[...L.entries()].filter(([,o])=>o.p>=AL.lineMin&&o.n>0&&o.n<=o.p*0.5).sort((a,b)=>(a[1].n-a[1].p)-(b[1].n-b[1].p));
  const gone=[...L.entries()].filter(([,o])=>o.p>=AL.lineMin&&o.n<=0).sort((a,b)=>b[1].p-a[1].p);
  return{items,down,gone};
}
function updAlertBadge(){const b=$('#alBadge');if(!b)return;const a=alertData();const t=a.items.filter(x=>x.lvl==='crit').length;b.textContent=t;b.hidden=!t}
function alertPanel(P){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);const A=alertData();
  const cnt=l=>A.items.filter(x=>x.lvl===l);const crit=cnt('crit'),warn=cnt('warn'),info=cnt('info');
  const sum=a=>a.reduce((s,x)=>s+x.stake,0);
  const baisse=A.items.filter(x=>x.type==='baisse'&&x.lvl!=='info'),perd=A.items.filter(x=>x.type==='perdue'&&x.lvl!=='info');
  const inProg=!seasonDone(D.dicts.season[S.n]);
  /* résumé */
  const summary=`<div class="pvm-t">
    <p><b>${fN(crit.length)} alertes critiques</b> (plus de ${fE(AL.crit)} en jeu chacune) représentent <b>${fM(sum(crit))}</b>, et ${fN(warn.length)} alertes à surveiller (entre ${fE(AL.warn)} et ${fE(AL.crit)}) représentent ${fM(sum(warn))}.</p>
    <p><b>${fN(baisse.length)} clients en forte baisse</b> : ils ont commandé sur ${p} et ${n}, mais au moins ${AL.drop} % de moins sur ${n}, pour ${fM(sum(baisse))} d'écart au total. <b>${fN(perd.length)} grosses portes n'ont rien commandé sur ${n}</b>, alors qu'elles avaient fait ${fM(sum(perd))} sur ${p}.</p>
    ${inProg?`<p class="warnline"><b>Attention :</b> la saison ${n} n'est pas terminée au ${fD(ASOF)}. Une partie de ces écarts peut venir de commandes qui ne sont pas encore passées. Vérifiez avec le client avant de conclure.</p>`:''}
    <p class="meta">« En jeu » = écart de CA ${typeLab()} entre ${p} et ${n} (ou tout le CA ${p} pour une porte sans commande). Seuils modifiables dans « Réglages ».</p></div>`;
  /* par commercial */
  const bySales=new Map();A.items.forEach(x=>{if(x.lvl==='info')return;const o=bySales.get(x.sales)||{crit:0,warn:0,stake:0};o[x.lvl]++;o.stake+=x.stake;bySales.set(x.sales,o)});
  const salesRows=[...bySales.entries()].sort((a,b)=>b[1].stake-a[1].stake).map(([s,o])=>`<tr class="${AL.sales===s?'selrow':''}" data-s="${s}" style="cursor:pointer"><td class="lab">${esc(D.dicts.sales[s])}</td><td>${o.crit?`<span class="sev crit">${o.crit}</span>`:'<span class="dash">0</span>'}</td><td>${o.warn?`<span class="sev warn">${o.warn}</span>`:'<span class="dash">0</span>'}</td><td><b>${fE(o.stake)}</b></td></tr>`).join('');
  /* liste priorisée */
  let list=A.items.filter(x=>(AL.lvl==='all'||x.lvl===AL.lvl)&&(AL.sales<0||x.sales===AL.sales));
  const catName=k=>`${D.dicts.fam[Math.floor(k/5)]} ${SEGS[k%5]}`;const pc=v=>(v>=0?'+':'−')+Math.abs(Math.round(v*100))+' %';
  const sentence=x=>{const a=R.cN[x.c],b=R.cP[x.c];const L=[];
    if(x.type==='perdue'){
      L.push(`<b>Aucune commande ${typeLab()} sur ${n}.</b> Sur ${p}, ce magasin avait commandé ${fE(b)} (${fN(x.qp)} pièces, ${fN(x.rp)} références).`);
      const top=x.cats.filter(c=>c.p>0).sort((u,v)=>v.p-u.p).slice(0,3);if(top.length)L.push(`Ses achats ${p} : ${top.map(c=>`${catName(c.k)} ${fE(c.p)}`).join(', ')}.`);
      if(x.last!=null&&x.last!==S.p)L.push(`Dernière saison avec commande : ${sLab(D.dicts.season[x.last])}.`);
    }else{
      L.push(`<b>${fE(a)} sur ${n} contre ${fE(b)} sur ${p}</b>, soit ${fS(a-b)} € (${pc((a-b)/b)}). Pièces : ${fN(x.qn)} contre ${fN(x.qp)} ; références : ${fN(x.rn)} contre ${fN(x.rp)}.`);
      const dn=x.cats.filter(c=>c.d<0).sort((u,v)=>u.d-v.d).slice(0,2);if(dn.length)L.push(`Baisses principales : ${dn.map(c=>`${catName(c.k)} ${fE(c.p)} → ${fE(c.n)} (${fS(c.d)} €)`).join(' ; ')}.`);
      const up=x.cats.filter(c=>c.d>0).sort((u,v)=>v.d-u.d)[0];if(up)L.push(`En hausse : ${catName(up.k)} ${fS(up.d)} €.`);
      const cg=D.dicts.cg[D.custCgI[x.c]];if(x.netP>0&&Math.abs(x.netP-b)>1&&cg!=='(non renseigné)'&&cg!=='NO GROUP'){const ne=(x.netN-x.netP)/x.netP,ce=(a-b)/b;L.push(`Le réseau ${esc(cg)} fait ${pc(ne)} sur ses portes comparables : ce magasin fait ${Math.abs(Math.round((ce-ne)*100))} points ${ce<ne?'de moins':'de plus'}.`)}
    }
    if(x.bl>0)L.push(`Reste à facturer : ${fE(x.bl)} tous types de commande${x.blN>0?`, dont ${fE(x.blN)} sur la collection ${n}`:''}.`);
    return L.map(l=>`<span class="al-l">${l}</span>`).join('')};
  const lvlLab={crit:'Critique',warn:'À surveiller',info:'Info'};
  const NRg=D.dicts.grp.indexOf('(non renseigné)');
  const allFilt=A.items.filter(x=>AL.sales<0||x.sales===AL.sales);
  const byG=new Map();allFilt.forEach(x=>{if(x.grp===NRg)return;const a=byG.get(x.grp)||[];a.push(x);byG.set(x.grp,a)});
  const units=[];const used=new Set();
  byG.forEach((arr,g)=>{if(arr.length<2)return;const stake=arr.reduce((s,x)=>s+x.stake,0);const lvl=stake>=AL.crit?'crit':stake>=AL.warn?'warn':'info';arr.forEach(x=>used.add(x));units.push({g,arr,stake,lvl})});
  allFilt.forEach(x=>{if(!used.has(x))units.push({x,stake:x.stake,lvl:x.lvl})});
  let ulist=units.filter(u=>AL.lvl==='all'||u.lvl===AL.lvl).sort((a,b)=>b.stake-a.stake);
  const shown=ulist.slice(0,AL.showAll?ulist.length:30);list=ulist;
  const storeRow=(x,sub)=>`<div class="al-row${sub?' al-sub':''}"><span class="sev ${x.lvl}">${lvlLab[x.lvl]}</span><div class="al-main"><div class="al-t">${cLabel('cust',x.c)} <span class="chip ${x.type==='perdue'?'down':'new'}">${x.type==='perdue'?'porte sans commande':'forte baisse'}</span> <span class="meta">${esc(D.dicts.sales[x.sales])}</span></div><div class="al-s">${sentence(x)}</div></div><div class="al-v"><b>${fE(x.stake)}</b><span class="meta">en jeu</span></div></div>`;
  const grpRow=u=>{const arr=u.arr,open=AL.openG&&AL.openG.has(u.g);const nb=arr.filter(x=>x.type==='baisse'),nl=arr.filter(x=>x.type==='perdue');
    const a=arr.reduce((s,x)=>s+R.cN[x.c],0),b=arr.reduce((s,x)=>s+R.cP[x.c],0);
    const cm=new Map();arr.forEach(x=>x.cats.forEach(c=>{const v=cm.get(c.k)||[0,0];v[0]+=c.n;v[1]+=c.p;cm.set(c.k,v)}));
    const dn=[...cm.entries()].map(([k,v])=>({k,d:v[0]-v[1],n:v[0],p:v[1]})).filter(c=>c.d<0).sort((u2,v2)=>u2.d-v2.d).slice(0,2);
    const blT=arr.reduce((s,x)=>s+x.bl,0);const sales=[...new Set(arr.map(x=>D.dicts.sales[x.sales]))];
    const txt=`<b>${fN(arr.length)} magasins du groupement en alerte</b>${nl.length?` : ${fN(nl.length)} sans commande sur ${n}`:''}${nb.length?`${nl.length?',':' :'} ${fN(nb.length)} en forte baisse`:''}. Ensemble : <b>${fE(a)} sur ${n} contre ${fE(b)} sur ${p}</b> (${fS(a-b)} €${b?`, ${pc((a-b)/b)}`:''}).${dn.length?` Baisses principales : ${dn.map(c=>`${catName(c.k)} ${fE(c.p)} → ${fE(c.n)}`).join(' ; ')}.`:''}${blT>0?` Reste à facturer : ${fE(blT)}.`:''}`;
    return`<div class="al-row al-g" data-g="${u.g}"><span class="sev ${u.lvl}">${lvlLab[u.lvl]}</span><div class="al-main"><div class="al-t"><button type="button" class="tg" aria-expanded="${open}"><i>${open?'−':'+'}</i></button> <b>${esc(D.dicts.grp[u.g])}</b> <span class="chip new">groupement · ${fN(arr.length)} magasins</span> <button type="button" class="fiche" data-g="${u.g}">Fiche du groupement</button> <span class="meta">${esc(sales.join(', '))}</span></div><div class="al-s">${txt} <span class="linkbtn al-open">${open?'Masquer les magasins':'Voir le détail magasins'}</span></div></div><div class="al-v"><b>${fE(u.stake)}</b><span class="meta">en jeu</span></div></div>${open?arr.map(x=>storeRow(x,true)).join(''):''}`};
  const rows=shown.map(u=>u.arr?grpRow(u):storeRow(u.x,false)).join('');
  const lvlSeg=`<div class="seg" id="alLvl" role="group">${[['crit',`Critiques (${crit.length})`],['warn',`À surveiller (${warn.length})`],['info',`Info (${info.length})`],['all','Toutes']].map(([k,l])=>`<button type="button" data-l="${k}" aria-pressed="${AL.lvl===k}">${l}</button>`).join('')}</div>`;
  if(AL.mode!=='full'){
    const typ=AL.typ||'all',lv=AL.slvl||'prio';
    const base=A.items.filter(x=>(AL.sales<0||x.sales===AL.sales));
    const pass=x=>(lv==='prio'?x.lvl!=='info':lv==='all'?true:x.lvl===lv)&&(typ==='all'||x.type===typ);
    const L=base.filter(pass);const lim=AL.showAll?L.length:25;
    const cntT=t=>base.filter(x=>(lv==='prio'?x.lvl!=='info':lv==='all'?true:x.lvl===lv)&&(t==='all'||x.type===t)).length;
    const team=!D.conf&&!(F.sales&&F.sales.size===1);
    const sList=[...new Set(A.items.filter(x=>x.lvl!=='info').map(x=>x.sales))].map(s=>[s,A.items.filter(x=>x.sales===s&&x.lvl!=='info').length]).sort((a,b)=>b[1]-a[1]);
    const tile=(k,lab,arr,cls)=>`<button type="button" class="als-t ${cls} ${lv===k?'on':''}" data-lv="${k}"><span>${lab}</span><b>${fN(arr.length)}</b><em>${fM(sum(arr))} en jeu</em></button>`;
    const prio=A.items.filter(x=>x.lvl!=='info'&&(AL.sales<0||x.sales===AL.sales));
    const cr=prio.filter(x=>x.lvl==='crit'),wa=prio.filter(x=>x.lvl==='warn');
    const row=x=>{const a=R.cN[x.c],b=R.cP[x.c],open=AL.openC&&AL.openC.has(x.c);
      const who=x.c===CENT?'<b>Centrale Intersport</b> <span class="als-city">4437</span>':`<b>${esc(D.dicts.cust[x.c])}</b> <span class="als-city">${esc(D.custCity[x.c]||'')}</span>`;
      const grp=x.c!==CENT&&D.custGrp[x.c]?`<button type="button" class="gchip" data-g="${D.custGrpI[x.c]}" title="Ouvrir la fiche du groupement">${esc(D.custGrp[x.c])}</button>`:'<span class="dash">—</span>';
      const motif=x.type==='perdue'?'<span class="als-m lost">Sans commande</span>':`<span class="als-m drop">${Math.round((a-b)/b*100)} %</span>`;
      return`<tr class="als-r${open?' open':''}" data-c="${x.c}"><td><span class="als-dot ${x.lvl}" title="${lvlLab[x.lvl]}"></span></td><td class="lab"><button type="button" class="als-who" data-c="${x.c}" title="Ouvrir la fiche magasin">${who}</button></td><td class="lab als-gc">${grp}</td>${team?`<td class="lab mutedv">${D.dicts.sales[x.sales]&&D.dicts.sales[x.sales]!=='(non renseigné)'?esc(D.dicts.sales[x.sales]):'—'}</td>`:''}<td>${motif}</td><td class="als-nv"><span class="cn">${fM(a)}</span> <span class="mutedv">vs</span> <span class="cp">${fM(b)}</span></td><td><b class="neg">${fM(x.stake)}</b></td><td><button type="button" class="fiche" data-c="${x.c}">Fiche magasin</button></td><td><button type="button" class="als-more" aria-expanded="${open}" title="Pourquoi cette alerte ?">${open?'−':'?'}</button></td></tr>${open?`<tr class="als-x"><td></td><td colspan="${team?8:7}"><div class="al-s">${sentence(x)}</div></td></tr>`:''}`};
    P.innerHTML=`<div class="toolbar"><div class="seg" id="alMode"><button type="button" data-m="simple" aria-pressed="true">Vue simple</button><button type="button" data-m="full" aria-pressed="false">Vue détaillée</button></div>${famSeg()}</div>
    <div class="als-tiles">${tile('crit','Critiques',cr,'crit')}${tile('warn','À surveiller',wa,'warn')}${tile('prio','Total prioritaire',prio,'tot')}</div>
    <div class="card"><div class="als-bar"><div class="seg" id="alTyp">${[['all','Toutes'],['baisse','Clients en baisse'],['perdue','Sans commande']].map(([k,l])=>`<button type="button" data-t="${k}" aria-pressed="${typ===k}">${l} <span class="mutedv">${fN(cntT(k))}</span></button>`).join('')}</div>
      ${team&&sList.length?`<label class="levels"><span class="tl">Commercial</span><select id="alSales"><option value="-1">Tous</option>${sList.map(([s,c])=>`<option value="${s}" ${AL.sales===s?'selected':''}>${esc(D.dicts.sales[s])} (${c})</option>`).join('')}</select></label>`:''}</div>
      <div class="scroll"><table class="pv als"><thead><tr><th></th><th class="lab">Magasin</th><th class="lab">Groupement</th>${team?'<th class="lab">Commercial</th>':''}<th>Motif</th><th>${n} vs ${p}</th><th>En jeu</th><th></th><th></th></tr></thead><tbody>${L.slice(0,lim).map(row).join('')||`<tr class="more"><td colspan="9">Aucune alerte avec ces filtres.</td></tr>`}</tbody></table></div>
      ${L.length>lim?`<div class="card-b"><button type="button" class="linkbtn" id="alMore">Afficher les ${fN(L.length-lim)} autres</button></div>`:''}</div>
    <p class="meta">Cliquez sur une ligne pour le détail. <span class="als-dot crit"></span> critique (≥ ${fE(AL.crit)} en jeu) · <span class="als-dot warn"></span> à surveiller (≥ ${fE(AL.warn)}). « En jeu » = écart de CA ${typeLab()} ${p} → ${n}, ou tout le CA ${p} pour un client sans commande.${!seasonDone(D.dicts.season[S.n])?` La saison ${n} n'est pas terminée : vérifiez avec le client avant de conclure.`:''} Seuils et lignes produit en recul : « Vue détaillée ».</p>`;
    bindFam();
    $('#alMode').onclick=e=>{const b=e.target.closest('button');if(b){AL.mode=b.dataset.m;alertPanel(P)}};
    P.querySelectorAll('.als-t').forEach(b=>b.onclick=()=>{AL.slvl=b.dataset.lv;AL.showAll=false;alertPanel(P)});
    $('#alTyp').onclick=e=>{const b=e.target.closest('button');if(b){AL.typ=b.dataset.t;AL.showAll=false;alertPanel(P)}};
    const se=$('#alSales');if(se)se.onchange=()=>{AL.sales=+se.value;AL.showAll=false;alertPanel(P)};
    const mo=$('#alMore');if(mo)mo.onclick=()=>{AL.showAll=true;alertPanel(P)};
    P.querySelectorAll('tr.als-r').forEach(r=>r.onclick=e=>{if(e.target.closest('.fiche,.gchip'))return;if(!e.target.closest('.als-more')){openFiche(+r.dataset.c);return}AL.openC=AL.openC||new Set();const c=+r.dataset.c;AL.openC.has(c)?AL.openC.delete(c):AL.openC.add(c);const y=window.scrollY;alertPanel(P);window.scrollTo(0,y)});
    return;
  }
  P.innerHTML=`<div class="toolbar"><div class="seg" id="alMode"><button type="button" data-m="simple" aria-pressed="false">Vue simple</button><button type="button" data-m="full" aria-pressed="true">Vue détaillée</button></div>${famSeg()}<button type="button" class="linkbtn" id="alSetBtn">${AL.showSet?'Masquer les réglages':'Réglages des seuils'}</button></div>
  ${AL.showSet?`<div class="card"><div class="card-b alset"><label>Baisse ≥ <input type="number" id="alDrop" value="${AL.drop}" min="5" max="95" step="5"> %</label><label>CA ${p} minimum (baisse) <input type="number" id="alMin" value="${AL.min}" step="1000"> €</label><label>Porte sans commande si CA ${p} ≥ <input type="number" id="alLost" value="${AL.lostMin}" step="1000"> €</label><label>Critique dès <input type="number" id="alCrit" value="${AL.crit}" step="1000"> €</label><label>À surveiller dès <input type="number" id="alWarn" value="${AL.warn}" step="1000"> €</label></div></div>`:''}
  <div class="card"><div class="card-b expl">${summary}</div></div>
  <div class="al-grid">
    <div class="card"><div class="card-h"><h2>Par commercial</h2><span class="note">Cliquez pour filtrer la liste</span></div><div class="scroll"><table class="pv"><thead><tr><th class="lab">Commercial</th><th>Critiques</th><th>À surveiller</th><th>En jeu</th></tr></thead><tbody>${salesRows||'<tr class="more"><td colspan="4">Aucune alerte.</td></tr>'}</tbody></table></div>${AL.sales>=0?`<div class="card-b"><button type="button" class="linkbtn" id="alAllS">Voir tous les commerciaux</button></div>`:''}</div>
    <div class="card"><div class="card-h"><h2>Alertes à traiter${AL.sales>=0?' · '+esc(D.dicts.sales[AL.sales]):''}</h2>${lvlSeg}</div>
      <div class="al-list">${rows||'<p class="mutedv" style="padding:16px;margin:0">Aucune alerte à ce niveau.</p>'}</div>
      ${list.length>shown.length?`<div class="card-b"><button type="button" class="linkbtn" id="alMore">Afficher les ${fN(list.length-shown.length)} autres</button></div>`:''}</div>
  </div>
  <div class="card"><div class="card-h"><h2>Lignes produit en fort recul</h2><span class="note">Lignes encore commandées sur ${n}, mais à moins de la moitié de leur CA ${p} (au moins ${fE(AL.lineMin)} en ${p})</span></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Ligne produit</th><th class="lab">Famille</th><th class="cn">CA ${n}<span class="bar"></span></th><th class="cp">CA ${p}<span class="bar"></span></th><th>Écart €</th><th>Évo</th></tr></thead><tbody>${A.down.slice(0,20).map(([k,o])=>`<tr><td class="lab">${esc(D.dicts.line[k])}</td><td class="lab mutedv">${esc(D.dicts.fam[o.f])}</td><td>${fE(o.n)}</td><td>${fE(o.p)}</td><td>${sgn(o.n-o.p)}</td><td>${chip(o.n,o.p)}</td></tr>`).join('')||'<tr class="more"><td colspan="6">Aucune ligne dans ce cas.</td></tr>'}</tbody></table></div>
    ${A.gone.length?`<details class="card-b"><summary class="linkbtn">${fN(A.gone.length)} lignes sans aucune commande sur ${n} (souvent des lignes renommées, ex. HOMME … devenu MEN …)</summary><div class="scroll"><table class="pv"><tbody>${A.gone.map(([k,o])=>`<tr><td class="lab">${esc(D.dicts.line[k])}</td><td class="lab mutedv">${esc(D.dicts.fam[o.f])}</td><td>${fE(o.p)} en ${p}</td></tr>`).join('')}</tbody></table></div></details>`:''}</div>`;
  bindFam();
  $('#alMode').onclick=e=>{const b=e.target.closest('button');if(b){AL.mode=b.dataset.m;alertPanel(P)}};
  $('#alSetBtn').onclick=()=>{AL.showSet=!AL.showSet;alertPanel(P)};
  if(AL.showSet){const upd=()=>{AL.drop=+$('#alDrop').value||30;AL.min=+$('#alMin').value||0;AL.lostMin=+$('#alLost').value||0;AL.crit=+$('#alCrit').value||20000;AL.warn=+$('#alWarn').value||5000;updAlertBadge();alertPanel(P)};['alDrop','alMin','alLost','alCrit','alWarn'].forEach(id=>$('#'+id).onchange=upd)}
  $('#alLvl').onclick=e=>{const b=e.target.closest('button');if(b){AL.lvl=b.dataset.l;AL.showAll=false;alertPanel(P)}};
  P.querySelectorAll('tr[data-s]').forEach(r=>r.onclick=()=>{const s=+r.dataset.s;AL.sales=AL.sales===s?-1:s;AL.showAll=false;alertPanel(P)});
  const as=$('#alAllS');if(as)as.onclick=()=>{AL.sales=-1;alertPanel(P)};
  const mo=$('#alMore');if(mo)mo.onclick=()=>{AL.showAll=true;alertPanel(P)};
  P.querySelectorAll('.al-g').forEach(r=>r.onclick=e=>{if(e.target.closest('.fiche'))return;AL.openG=AL.openG||new Set();const g=+r.dataset.g;AL.openG.has(g)?AL.openG.delete(g):AL.openG.add(g);const y=window.scrollY;alertPanel(P);window.scrollTo(0,y)});
}

/* ================= backlog client (reste à facturer) ================= */
let BL=null;
const BLDIM={r2:'Commercial',r1:'Manager',grp:'Groupe client',gpm:'Groupement',cust:'Client',fam:'Famille',col:'Collection',art:'Article',typ:'Type de commande',bm:'Business model'};
const BLS={mon:null,col:-1,levels:['r2','grp','gpm','cust'],exp:new Set(),sort:{k:'a',dir:-1}};
function initBacklog(){
  const el=document.getElementById('backlog');if(!el||!el.textContent.trim().startsWith('{'))return;
  try{BL=JSON.parse(el.textContent)}catch(e){BL=null;return}
  const n=BL.n=BL.cols.a.length,asof=BL.asof;BL.late=new Uint8Array(n);BL.wait=new Uint8Array(n);BL.mon=new Array(n);
  const dl=BL.dicts.dl,da=BL.dicts.da;
  const late=dl.map(x=>x&&x!=='(non renseigné)'&&x<asof?1:0),wait=da.map(x=>x&&x>asof?1:0),mon=dl.map(x=>x&&x!=='(non renseigné)'?x.slice(0,7):'');
  for(let i=0;i<n;i++){BL.late[i]=late[BL.cols.dl[i]];BL.wait[i]=wait[BL.cols.da[i]];BL.mon[i]=mon[BL.cols.dl[i]]}
}
function blRows(pred){
  const map={sales:'r2',n1:'r1',sot:'typ',bm1:'bm',fam:'fam',cg:'grp',grp:'gpm'};const masks=[];
  for(const[g,bk]of Object.entries(map)){if(!F[g]||!F[g].size)continue;const allowed=new Set([...F[g]].map(i=>D.dicts[g][i]));masks.push([BL.cols[bk],BL.dicts[bk].map(v=>allowed.has(v)?1:0)])}
  const ex=S.exclC?CENT:-2,out=[];
  outer:for(let i=0;i<BL.n;i++){if(BL.cols.cust[i]===ex)continue;for(const[c,m]of masks)if(!m[c[i]])continue outer;if(BLS.col>=0&&BL.cols.col[i]!==BLS.col)continue;if(pred&&!pred(i))continue;out.push(i)}
  return out;
}
function blLabel(dim,k){
  if(dim==='cust')return cLabel('cust',k);
  if(dim==='art')return '<span class="refpair"><b class="refc">'+esc(BL.dicts.art[k])+'</b> <span class="refd">'+esc(BL.dicts.artDesc[k]||'—')+'</span></span>';
  return esc(BL.dicts[dim][k]);
}
function blText(dim,k){if(dim==='cust')return cText('cust',k);if(dim==='art')return(BL.dicts.artDesc[k]||'')+' '+BL.dicts.art[k];return BL.dicts[dim][k]}
const fD=s=>s&&s!=='(non renseigné)'?s.slice(8,10)+'/'+s.slice(5,7)+'/'+s.slice(2,4):'—';
function blTree(rows,levels){
  const mk=(dim,key)=>({dim,key,a:0,q:0,late:0,wait:0,lw:0,ln:0,la:0,dmin:'9',dmax:'',smin:'9',smax:'',snone:0,cust:new Set(),ch:new Map()});const root=mk(null,'tot');
  for(const i of rows){const a=BL.cols.a[i],q=BL.cols.q[i],d=BL.dicts.dl[BL.cols.dl[i]];let nd=root;
    for(let l=-1;l<levels.length;l++){if(l>=0){const k=BL.cols[levels[l]][i];let c=nd.ch.get(k);if(!c){c=mk(levels[l],k);nd.ch.set(k,c)}nd=c}
      nd.a+=a;nd.q+=q;if(BL.late[i]){nd.late+=a;const s0=BL.dicts.da[BL.cols.da[i]];if(BL.wait[i])nd.lw+=a;else if(!s0)nd.ln+=a;else nd.la+=a}if(BL.wait[i])nd.wait+=a;if(d&&d!=='(non renseigné)'){if(d<nd.dmin)nd.dmin=d;if(d>nd.dmax)nd.dmax=d}const sd=BL.dicts.da[BL.cols.da[i]];if(sd){if(sd<nd.smin)nd.smin=sd;if(sd>nd.smax)nd.smax=sd}else nd.snone+=a;nd.cust.add(BL.cols.cust[i])}}
  return root;
}
function blPanel(P){
  if(!BL){P.innerHTML='<div class="card"><div class="card-b">Aucun backlog chargé.</div></div>';return}
  const rows=blRows();const root=blTree(rows,[]);const tot=root.a||0;
  const nOrders=[...root.cust].reduce((s,c)=>s+(BL.ordersByCust[c]||0),0);
  const pc=v=>tot?Math.round(v/tot*100):0;
  const tile=(h,big,foot,cls)=>`<div class="kt ${cls||''}"><div class="kt-h"><h3>${h}</h3></div><div class="big">${big}</div><div class="foot">${foot}</div></div>`;
  const cols=BL.dicts.col;const colsUsed=[...new Set(rows.map(i=>BL.cols.col[i]))];
  const seg=`<div class="seg" id="blCol" role="group" aria-label="Collection"><button type="button" data-c="-1" aria-pressed="${BLS.col<0}">Toutes collections</button>${cols.map((c,i)=>[c,i]).filter(([c,i])=>BL.cols.col.some((x,j)=>x===i&&BL.cols.a[j]>20000)).map(([c,i])=>`<button type="button" data-c="${i}" aria-pressed="${BLS.col===i}">${esc(c.includes('-')?sLab(c):c)}</button>`).join('')}</div>`;
  const lvOpts=['r2','r1','grp','gpm','cust','fam','col','typ','bm','art'];
  const lp=`<div class="levels" id="blLv"><span class="tl">Lignes</span>${[0,1,2,3].map(i=>`<select data-i="${i}" aria-label="Niveau ${i+1}"><option value="">—</option>${lvOpts.map(o=>`<option value="${o}" ${BLS.levels[i]===o?'selected':''}>${BLDIM[o]}</option>`).join('')}</select>`).join('<span class="sep">›</span>')}<span class="meta">puis ${BLS.levels.includes('art')?'':'› Article '}en cliquant sur +</span></div>`;
  P.innerHTML=`<div class="toolbar">${seg}${famSeg()}</div>
  <div class="kgrid">
    ${tile('Reste à facturer',fM(tot),`${fN(root.q)} pièces`)}
    ${tile('Clients',fN(root.cust.size),`${fN(nOrders)} commandes ouvertes`)}
    ${tile('En retard',fM(root.late),`${pc(root.late)} % · livraison demandée avant le ${fD(BL.asof)}`,root.late?'down':'')}
    ${tile('Attendu fournisseur',fM(root.wait),`${pc(root.wait)} % · arrivée prévue après le ${fD(BL.asof)}`)}
    ${tile('Livraison la + ancienne',`<span class="bigd">${fD(root.dmin==='9'?'':root.dmin)}</span>`,'date demandée la plus ancienne encore à livrer')}
    ${tile('Livraison la + lointaine',`<span class="bigd">${fD(root.dmax)}</span>`,'date demandée la plus éloignée')}
  </div>
  <div class="card"><div class="card-b expl"><div class="pvm-t">
    <p><b>En résumé.</b> Au ${fD(BL.asof)}, il reste <b>${fE(tot)}</b> à facturer, soit ${fN(root.q)} pièces commandées et pas encore livrées, pour ${fN(root.cust.size)} clients.</p>
    <p><b>En retard : ${fE(root.late)} (${pc(root.late)} %).</b> Ce sont des lignes dont la date de livraison demandée est déjà passée et qui ne sont pas encore facturées. À vérifier en priorité avec le client et l'ADV.</p>
    <p><b>Attendu fournisseur : ${fE(root.wait)} (${pc(root.wait)} %).</b> Ce sont des lignes dont la marchandise n'est pas encore arrivée chez nous : la date d'arrivée prévue fournisseur est postérieure au ${fD(BL.asof)}. Elles ne peuvent pas être livrées avant cette date. La date d'entrée en stock prévue de chaque référence est indiquée dans le tableau « Références concernées » ci-dessous.</p>
    <p class="meta">Filtres appliqués : ${(()=>{const m={sales:'Commercial',n1:'Manager',sot:'Type de commande',bm1:'Business model',fam:'Famille',cg:'Groupe client',grp:'Groupement'};const l=Object.keys(m).filter(k=>F[k].size).map(k=>m[k]+' = '+[...F[k]].map(i=>D.dicts[k][i]).join(', '));return l.length?l.join(' · '):'aucun'})()}${BLS.col>=0?' · Collection = '+esc(BL.dicts.col[BLS.col]):''}. Pour voir tout le backlog, toutes commandes confondues, videz le filtre Sales order type. Source : fichier Backlog client du ${fD(BL.asof)} (montants en euros, hors PLV). Le reste à facturer n'est pas comparé aux saisons N / N-1 : c'est une photo à date.</p></div></div></div>
  <div class="card"><div class="card-h"><h2>Reste à facturer par mois de livraison</h2><span class="note">Backlog au ${fD(BL.asof)} · « Retard » = date de livraison dépassée</span></div><div class="card-b" id="blChart"></div></div>
  <div class="card"><div class="card-h"><h2>Backlog détaillé</h2><span class="note">Filtres appliqués : commercial, manager, type de commande, business model, famille, groupe client, groupement</span></div><div class="card-b blsel" style="padding-bottom:0"></div><div class="card-b" style="padding-bottom:0">${lp}</div><div id="blT"></div></div>
  <div class="card"><div class="card-h"><h2>Références concernées</h2><div class="blsel"></div><span class="note">Une ligne par référence article · « Entrée en stock prévue » = date d'arrivée prévue fournisseur (plusieurs dates si les tailles n'arrivent pas ensemble) · Statut : « retard » = date de livraison demandée dépassée sur une partie des lignes ; le motif indique pourquoi (marchandise attendue chez le fournisseur, ou déjà en stock et à voir avec l'ADV)</span></div><div id="blR"></div></div>`;
  bindFam();
  $('#blCol').onclick=e=>{const b=e.target.closest('button');if(!b)return;BLS.col=+b.dataset.c;BLS.exp.clear();blPanel(P)};
  $('#blLv').onchange=e=>{const i=+e.target.dataset.i;const nx=BLS.levels.slice(0,4);while(nx.length<4)nx.push('');nx[i]=e.target.value;BLS.levels=nx.filter(Boolean).filter((x,j,a)=>a.indexOf(x)===j);if(!BLS.levels.length)BLS.levels=['cust'];BLS.exp.clear();blPanel(P)};
  const mkey=i=>BL.late[i]?'Retard':(BL.mon[i]||'Sans date');const rowsM=BLS.mon?rows.filter(i=>mkey(i)===BLS.mon):rows;
  blChart($('#blChart'),rows);blTable($('#blT'),rowsM);blRefs($('#blR'),rowsM);
  const ml=k=>{const MN=['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];return k==='Retard'?'en retard (date de livraison dépassée)':k==='Sans date'?'sans date de livraison':'livraison demandée en '+MN[+k.slice(5,7)-1]+' '+k.slice(0,4)};
  const sumM=rowsM.reduce((s,i)=>s+BL.cols.a[i],0);
  document.querySelectorAll('.blsel').forEach(el=>{el.innerHTML=BLS.mon?`<span class="selchip">Lignes ${ml(BLS.mon)} · ${fE(sumM)} · ${fN(new Set(rowsM.map(i=>BL.cols.cust[i])).size)} clients <button type="button" class="linkbtn blclr">Tout afficher</button></span>`:'<span class="meta">Cliquez sur une barre du graphique pour ne voir que ce mois.</span>'});
  document.querySelectorAll('.blclr').forEach(b=>b.onclick=()=>{BLS.mon=null;BLS.exp.clear();blPanel(P)});
  $('#blChart').onclick=e=>{const g=e.target.closest('[data-mk]');if(!g)return;BLS.mon=BLS.mon===g.dataset.mk?null:g.dataset.mk;BLS.exp.clear();BLR.all=false;blPanel(P);const tb=$('#blT');if(tb)tb.closest('.card').scrollIntoView({behavior:'smooth',block:'start'})};
}
function blChart(host,rows){
  const months=new Map(),colTot=new Map();
  for(const i of rows){const key=BL.late[i]?'Retard':(BL.mon[i]||'Sans date');const c=BL.cols.col[i],a=BL.cols.a[i];let m=months.get(key);if(!m){m=new Map();months.set(key,m)}m.set(c,(m.get(c)||0)+a);colTot.set(c,(colTot.get(c)||0)+a)}
  const keys=[...months.keys()].sort((a,b)=>a==='Retard'?-1:b==='Retard'?1:a==='Sans date'?1:b==='Sans date'?-1:a.localeCompare(b));
  if(!keys.length){host.innerHTML='<p class="mutedv">Aucun backlog pour cette sélection.</p>';return}
  const series=[...colTot.entries()].sort((a,b)=>b[1]-a[1]).map(x=>x[0]);const main=series.slice(0,4);const PAL=['var(--c1)','var(--c2)','var(--c3)','var(--c4)'];
  const colOf=c=>{const j=main.indexOf(c);return j>=0?PAL[j]:'var(--bar)'};
  const totals=keys.map(k=>[...months.get(k).values()].reduce((s,v)=>s+v,0));
  const W=Math.max(680,keys.length*58+70),H=280,ml=56,mr=10,mt=26,mb=40,pw=W-ml-mr,ph=H-mt-mb,mx=niceMax(Math.max(...totals)),bw=pw/keys.length;
  const MN=['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
  const lab=k=>k==='Retard'||k==='Sans date'?k:MN[+k.slice(5,7)-1]+' '+k.slice(2,4);
  let s='';for(let t=0;t<=4;t++){const v=mx*t/4,y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}€</text>`}
  keys.forEach((k,i)=>{const x=ml+i*bw+bw*.16,w=bw*.68;let base=mt+ph;let tip=`<b>${lab(k)}</b><br>Total : ${fE(totals[i])}`;let marks='';
    const parts=[...months.get(k).entries()].sort((a,b)=>main.indexOf(a[0])-main.indexOf(b[0]));
    parts.forEach(([c,v],j)=>{if(v<=0)return;const h=ph*v/mx;base-=h;marks+=`<rect x="${x}" y="${base+(j?1:0)}" width="${w}" height="${Math.max(.5,h-(j?1:0))}" fill="${k==='Retard'?'var(--bad)':colOf(c)}" ${k==='Retard'?`opacity="${1-j*0.18}"`:''}/>`;tip+=`<br>${esc(BL.dicts.col[c])} : ${fE(v)}`});
    const sel=BLS.mon===k;if(sel)marks+=`<rect x="${x-3}" y="${base-3}" width="${w+6}" height="${mt+ph-base+3}" rx="4" fill="none" stroke="var(--ink)" stroke-width="2"/>`;
    s+=`<g data-mk="${k}" style="cursor:pointer" data-tip="${esc(tip+'<br><i>Cliquer pour filtrer les tableaux</i>')}"><rect x="${ml+i*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${marks}<text x="${x+w/2}" y="${base-6}" text-anchor="middle" class="vl">${fK(totals[i])}</text><text x="${x+w/2}" y="${H-20}" text-anchor="middle" class="ax" ${k==='Retard'?'style="fill:var(--bad);font-weight:700"':''}>${lab(k)}</text></g>`});
  host.innerHTML=`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:${Math.min(W,900)}px;max-width:${W*1.3}px;display:block" role="img" aria-label="Backlog par mois de livraison">${s}</svg></div>
  <div class="legend" style="margin-top:8px">${main.map(c=>`<span><span class="sw" style="background:${colOf(c)}"></span>${esc(BL.dicts.col[c])}</span>`).join('')}${series.length>4?'<span><span class="sw" style="background:var(--bar)"></span>Autres collections</span>':''}<span><span class="sw" style="background:var(--bad)"></span>Retard</span></div>`;
}
function stockCell(n){
  if(n.smax==='')return n.a?'<span class="mutedv">pas de date fournisseur</span>':'<span class="dash">—</span>';
  const fut=n.smax>BL.asof;const txt=n.smin!==n.smax&&n.smin!=='9'?`${fD(n.smin)} → ${fD(n.smax)}`:fD(n.smax);
  return fut?`<span class="chip new">${txt}</span>`:`<span class="mutedv">arrivé ${txt}</span>`;
}
const BLR={q:'',all:false,sort:'a'};
function blRefs(host,rows){
  const tr=blTree(rows,['art']);const q=BLR.q.trim().toLowerCase();
  let list=[...tr.ch.values()];
  // collection / famille par article
  const colOf=new Map(),famOf=new Map();for(const i of rows){const a=BL.cols.art[i];if(!colOf.has(a))colOf.set(a,new Set());colOf.get(a).add(BL.dicts.col[BL.cols.col[i]]);famOf.set(a,BL.dicts.fam[BL.cols.fam[i]])}
  if(q)list=list.filter(n=>(BL.dicts.art[n.key]+' '+(BL.dicts.artDesc[n.key]||'')).toLowerCase().includes(q));
  const srt={a:(x,y)=>y.a-x.a,q:(x,y)=>y.q-x.q,c:(x,y)=>y.cust.size-x.cust.size,s:(x,y)=>(x.smax||'0').localeCompare(y.smax||'0')*-1,d:(x,y)=>x.dmin.localeCompare(y.dmin)}[BLR.sort];list.sort(srt);
  const total=list.length,hidden=!BLR.all&&total>60?total-60:0;const shown=hidden?list.slice(0,60):list;
  const st=n=>{if(n.late>0){const m=[[n.lw,'attente fournisseur','t-wait'],[n.la,'arrivé, non expédié','t-chk'],[n.ln,'a priori en stock · voir ADV','t-chk']].filter(x=>x[0]>0.5).sort((a,b)=>b[0]-a[0])[0]||[0,'',''];
      return`<span class="chip down" data-tip="${esc(`<b>${fE(n.late)} en retard</b> (date de livraison dépassée)<br>dont attente fournisseur : ${fE(n.lw)}<br>dont arrivé, non expédié : ${fE(n.la)}<br>dont sans arrivée prévue : ${fE(n.ln)}`)}">retard ${fK(n.late)}€</span>${m[1]?` <span class="tag ${m[2]}">${m[1]}</span>`:''}`}
    return n.wait>0?'<span class="chip new">en attente fournisseur</span>':n.smax?'<span class="chip up">arrivé en stock</span>':`<span class="mutedv">à livrer${n.dmin&&n.dmin!=='9'?' à partir du '+fD(n.dmin):''}</span>`};
  const th=(k,l)=>`<th data-s="${k}" style="cursor:pointer">${l}${BLR.sort===k?' <span class="ar">▼</span>':''}</th>`;
  host.innerHTML=`<div class="card-b" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;padding-bottom:0"><input type="search" id="blRq" class="exq" style="max-width:360px" placeholder="Rechercher une référence ou une désignation…" value="${esc(BLR.q)}" autocomplete="off"><span class="meta">${fN(total)} références · triées par ${({a:'montant',q:'pièces',c:'nombre de clients',s:"date d'entrée en stock",d:'date de livraison'})[BLR.sort]}</span></div>
  <div class="scroll"><table class="pv mrt"><thead><tr><th class="lab">Référence</th><th class="lab">Désignation article</th><th class="lab">Collection</th><th class="lab">Famille</th>${th('c','Clients')}${th('q','Pièces')}${th('a','Reste à facturer')}${th('d','Livraison demandée')}${th('s','Entrée en stock prévue')}<th>Statut</th></tr></thead><tbody>
  ${shown.map(n=>`<tr><td class="lab"><b class="cno">${esc(BL.dicts.art[n.key])}</b></td><td class="lab">${esc(BL.dicts.artDesc[n.key]||'—')}</td><td class="lab mutedv">${[...(colOf.get(n.key)||[])].map(c=>c.includes('-')?sLab(c):c).join(', ')}</td><td class="lab mutedv">${esc(famOf.get(n.key)||'')}</td><td>${fN(n.cust.size)}</td><td>${fN(n.q)}</td><td>${fE(n.a)}</td><td>${n.dmin!=='9'&&n.dmin!==n.dmax?`${fD(n.dmin)} → ${fD(n.dmax)}`:fD(n.dmin==='9'?'':n.dmin)}</td><td>${n.smax?(n.smin!==n.smax&&n.smin!=='9'?`${fD(n.smin)} → ${fD(n.smax)}`:fD(n.smax)):'<span class="mutedv">pas de date</span>'}</td><td>${st(n)}</td></tr>`).join('')||'<tr class="more"><td colspan="10">Aucune référence.</td></tr>'}
  ${hidden?`<tr class="more"><td colspan="10"><button type="button" class="linkbtn" id="blRall">Afficher les ${fN(hidden)} autres références</button></td></tr>`:''}</tbody></table></div>`;
  const inp=$('#blRq');inp.oninput=()=>{BLR.q=inp.value;const pos=inp.selectionStart;blRefs(host,rows);const n=$('#blRq');n.focus();n.setSelectionRange(pos,pos)};
  host.querySelectorAll('th[data-s]').forEach(h=>h.onclick=()=>{BLR.sort=h.dataset.s;blRefs(host,rows)});
  const al=$('#blRall');if(al)al.onclick=()=>{BLR.all=true;blRefs(host,rows)};
}
function blTable(host,rows){
  const L=BLS.levels.includes('art')?BLS.levels:BLS.levels.concat('art');
  const root=blTree(rows,L);const tot=root.a||1;const sk=BLS.sort;
  const G={smax:n=>n.smax,label:n=>blText(n.dim,n.key).toLowerCase(),a:n=>n.a,q:n=>n.q,late:n=>n.late,wait:n=>n.wait,dmin:n=>n.dmin,dmax:n=>n.dmax};
  const cols=[['a','Reste à facturer'],['q','Pièces'],['late','dont en retard'],['wait','dont attendu fourn.'],['dmin','Livr. la + ancienne'],['dmax','Livr. la + lointaine'],['smax','Entrée en stock prévue'],['pct','Part']];
  const ar=k=>sk.k===k?`<span class="ar">${sk.dir>0?'▲':'▼'}</span>`:'';
  const head=`<tr><th class="lab" data-k="label">${BLS.levels.map(d=>BLDIM[d]).join(' › ')}${L.length>BLS.levels.length?' <span class="meta">› Article</span>':''}${ar('label')}</th>${cols.map(([k,h])=>`<th data-k="${k}">${h}${ar(k)}</th>`).join('')}</tr>`;
  const cell=n=>`<td>${fE(n.a)}</td><td>${fN(n.q)}</td><td>${n.late?`<span class="neg">${fE(n.late)}</span>`:'<span class="dash">—</span>'}</td><td>${n.wait?fE(n.wait):'<span class="dash">—</span>'}</td><td>${fD(n.dmin==='9'?'':n.dmin)}</td><td>${fD(n.dmax)}</td><td>${stockCell(n)}</td><td><span class="dif"><i style="width:${Math.round(n.a/tot*100)}%"></i></span> ${Math.round(n.a/tot*1000)/10} %</td>`;
  const out=[];const g=G[sk.k]||G.a;
  (function walk(nd,depth,path){const kids=[...nd.ch.values()].sort((a,b)=>{const x=g(a),y=g(b);return(x<y?-1:x>y?1:0)*sk.dir});
    kids.slice(0,400).forEach(k=>{const p=path+'/'+k.dim+k.key;const has=k.ch.size>0,open=BLS.exp.has(p);
      out.push(`<tr class="l${depth}"><td class="lab"><span style="padding-left:${depth*18}px;display:inline-flex;align-items:baseline">${has?`<button type="button" class="tg" data-p="${p}" aria-expanded="${open}"><i>${open?'−':'+'}</i></button><span class="tgl" data-p="${p}" style="margin-left:6px">${blLabel(k.dim,k.key)}</span>`:`<span class="leaf"></span><span>${blLabel(k.dim,k.key)}</span>`}</span></td>${cell(k)}</tr>`);
      if(has&&open)walk(k,depth+1,p)});
    if(kids.length>400)out.push(`<tr class="more"><td colspan="9">… ${fN(kids.length-400)} autres lignes</td></tr>`)})(root,0,'');
  out.push(`<tr class="tot"><td class="lab">Total</td>${cell(root)}</tr>`);
  host.innerHTML=`<div class="scroll"><table class="pv"><thead>${head}</thead><tbody>${out.join('')}</tbody></table></div>`;
  host.onclick=e=>{const t=e.target.closest('[data-p]');if(t){const p=t.dataset.p;BLS.exp.has(p)?BLS.exp.delete(p):BLS.exp.add(p);blTable(host,rows);return}
    const th=e.target.closest('th[data-k]');if(th&&th.dataset.k!=='pct'){const k=th.dataset.k;BLS.sort=BLS.sort.k===k?{k,dir:-BLS.sort.dir}:{k,dir:k==='label'||k==='dmin'?1:-1};blTable(host,rows)}};
}
function blFicheHtml(cm){
  if(!BL)return'';const rows=[];for(let i=0;i<BL.n;i++)if(cm[BL.cols.cust[i]])rows.push(i);
  if(!rows.length)return`<section><h3 class="fs">Reste à facturer</h3><p class="mutedv" style="margin:0">Aucun backlog au ${fD(BL.asof)}.</p></section>`;
  const r=blTree(rows,['col']);const ra=blTree(rows,['art']);
  const colRows=[...r.ch.values()].sort((a,b)=>b.a-a.a).map(n=>`<tr><td class="lab">${esc(BL.dicts.col[n.key])}</td><td>${fE(n.a)}</td><td>${fN(n.q)}</td><td>${n.late?`<span class="neg">${fE(n.late)}</span>`:'<span class="dash">—</span>'}</td><td>${n.wait?fE(n.wait):'<span class="dash">—</span>'}</td><td>${fD(n.dmin==='9'?'':n.dmin)}</td></tr>`).join('');
  const artRows=[...ra.ch.values()].sort((a,b)=>b.a-a.a).slice(0,12).map(n=>`<tr><td class="lab mrl">${blLabel('art',n.key)}</td><td>${fN(n.q)}</td><td>${fE(n.a)}</td><td>${fD(n.dmin==='9'?'':n.dmin)}</td><td>${n.smax?fD(n.smax):'<span class="mutedv">pas de date</span>'}</td><td>${n.late?`<span class="chip down">retard</span>${n.lw>=n.la&&n.lw>=n.ln?' <span class="tag t-wait">attente fourn.</span>':' <span class="tag t-chk">voir ADV</span>'}`:n.wait?'<span class="chip new">en attente fournisseur</span>':''}</td></tr>`).join('');
  return`<section><h3 class="fs">Reste à facturer <span class="meta">backlog au ${fD(BL.asof)}, tous types de commande · ${fE(r.a)} · ${fN(r.q)} pièces${r.late?` · dont ${fE(r.late)} en retard`:''}</span></h3>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Collection</th><th>Montant</th><th>Pièces</th><th>En retard</th><th>Attendu fourn.</th><th>Livraison la + ancienne</th></tr></thead><tbody>${colRows}</tbody></table></div>
    <div class="scroll" style="margin-top:10px"><table class="pv mrt"><thead><tr><th class="lab">Référence</th><th>Pièces</th><th>Montant</th><th>Livraison</th><th>Entrée en stock prévue</th><th></th></tr></thead><tbody>${artRows}</tbody></table></div></section>`;
}

/* ================= pré-saison vs réassort ================= */
const RS={season:null,dim:'cg',all:false,view:'rea'};
const RSK=['ps','re','op','ot'],RSL={ps:'Pré-saison (PS)',re:'Réassort (RE)',op:'Opérations (OP)',ot:'Autres types'},RSC={ps:'var(--c1)',re:'var(--c2)',op:'var(--c3)',ot:'var(--bar)'};
let ASOF='2026-10-13'; /* remplacé par la date des données dans init() (intégration intranet) */
function seasonDone(s){const y=+s.slice(0,4);const end=s.endsWith('SS')?`${y}-08-31`:`${y+1}-02-28`;return end<ASOF}
function rsClass(){const m=D.dicts.sot.map(v=>v==='PS'?0:v==='RE'?1:v==='OP'?2:v==='(non renseigné)'?-1:3);return m}
function rsRows(){
  const masks=FDIMS.filter(d=>d!=='sot'&&F[d].size).map(d=>{const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return[C[d],m]});
  const ex=S.exclC?CENT:-2,out=[];outer:for(let i=0;i<N;i++){if(C.cust[i]===ex)continue;for(const[c,m]of masks)if(!m[c[i]])continue outer;out.push(i)}return out;
}
function rsAgg(rows,keyFn){ // -> Map key -> {s: seasonIdx -> [ps,re,op,ot]}
  const cls=rsClass(),ns=D.dicts.season.length,M=new Map();let excl=0;
  for(const i of rows){const t=cls[C.sot[i]];if(t<0){excl+=C.amt[i];continue}const k=keyFn(i);let o=M.get(k);if(!o){o=Array.from({length:ns},()=>[0,0,0,0]);M.set(k,o)}o[C.season[i]][t]+=C.amt[i]}
  M.excl=excl;return M;
}
const rsPct=(v)=>{const t=v[0]+v[1]+v[2]+v[3];return t>0?v[1]/t:0};
function rsPanel(P){
  if(RS.view!=='mix'){reaPanel(P);return}
  const ss=D.dicts.season,ns=ss.length;if(RS.season==null){RS.season=0;ss.forEach((s,i)=>{if(seasonDone(s))RS.season=i})}
  const rows=rsRows();const tot=rsAgg(rows,()=>0).get(0)||Array.from({length:ns},()=>[0,0,0,0]);const excl=rsAgg(rows,()=>0).excl;
  const famA=rsAgg(rows,i=>C.fam[i]);
  const se=RS.season,v=tot[se],T=v[0]+v[1]+v[2]+v[3];const pc=x=>T?Math.round(x/T*1000)/10:0;
  const ratio=v[0]>0?v[1]/v[0]*100:0;
  /* narrative */
  const famLines=[...famA.entries()].filter(([f])=>D.dicts.fam[f]!=='(non renseigné)').map(([f,a])=>({f:D.dicts.fam[f],v:a[se]})).filter(x=>x.v.reduce((s,y)=>s+y,0)>0).sort((a,b)=>rsPct(b.v)-rsPct(a.v));
  const txt=`<div class="pvm-t">
    <p><b>Définitions.</b> <b>Pré-saison</b> = commandes de type PS, passées avant la saison sur la collection. <b>Réassort</b> = commandes de type RE, passées pendant la saison pour réapprovisionner. <b>Opérations</b> = type OP. <b>Autres</b> = CO, PR, RV, RX, SA, EO. Les lignes sans type (${fM(excl)} sur la période, essentiellement des montants négatifs : avoirs ou retours) sont exclues de cette vue.</p>
    <p><b>Saison ${sLab(ss[se])}.</b> Total commandé : ${fE(T)}. Pré-saison : ${fE(v[0])} (${pc(v[0])} %). Réassort : ${fE(v[1])} (${pc(v[1])} %). Opérations : ${fE(v[2])} (${pc(v[2])} %). Autres : ${fE(v[3])} (${pc(v[3])} %).</p>
    <p><b>Lecture simple :</b> pour 100 € commandés en pré-saison, les clients ont recommandé ${(Math.round(ratio*10)/10).toLocaleString('fr-FR')} € en réassort sur la même saison.${famLines.length?` La catégorie où le réassort pèse le plus est <b>${famLines[0].f}</b> (${Math.round(rsPct(famLines[0].v)*1000)/10} % de son total), celle où il pèse le moins est <b>${famLines[famLines.length-1].f}</b> (${Math.round(rsPct(famLines[famLines.length-1].v)*1000)/10} %).`:''}</p>
    <p class="meta">Attention : pour les saisons en cours ou à venir au ${fD(ASOF)} (${ss.filter(s=>!seasonDone(s)).map(sLab).join(', ')}), le réassort n'est pas terminé, son poids est donc mécaniquement faible. Comparez de préférence des saisons terminées. Filtres appliqués : tous sauf Sales order type${F.perim.size?' (périmètre BOH actif)':''}.</p></div>`;
  /* chart per season */
  const W=680,H=280,ml=56,mr=10,mt=28,mb=36,pw=W-ml-mr,ph=H-mt-mb,mx=niceMax(Math.max(...tot.map(x=>x.reduce((s,y)=>s+Math.max(0,y),0)))),bw=pw/ns;
  let s='';for(let k=0;k<=4;k++){const val=mx*k/4,y=mt+ph-ph*k/4;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(val)}€</text>`}
  tot.forEach((x,i)=>{const bx=ml+i*bw+bw*.18,w=bw*.64;let base=mt+ph;let marks='';let tip=`<b>${sLab(ss[i])}</b>`;
    RSK.forEach((k,j)=>{const val=Math.max(0,x[j]);if(!val)return;const h=ph*val/mx;base-=h;marks+=`<rect x="${bx}" y="${base+(j?1:0)}" width="${w}" height="${Math.max(.5,h-(j?1:0))}" fill="${RSC[k]}"/>`;tip+=`<br>${RSL[k]} : ${fE(x[j])}`});
    const p=rsPct(x);tip+=`<br><b>Part réassort : ${Math.round(p*1000)/10} %</b>`;
    s+=`<g data-tip="${esc(tip)}"><rect x="${ml+i*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${marks}${base<mt+ph?`<text x="${bx+w/2}" y="${base-14}" text-anchor="middle" class="vl">${Math.round(p*100)} %</text><text x="${bx+w/2}" y="${base-3}" text-anchor="middle" class="ax">réassort</text>`:''}<text x="${bx+w/2}" y="${H-12}" text-anchor="middle" class="ax" ${i===se?'style="font-weight:700;fill:var(--ink)"':''}>${sLab(ss[i])}</text></g>`});
  const chart=`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:540px;max-width:820px;display:block" role="img" aria-label="Pré-saison et réassort par saison">${s}</svg></div><div class="legend" style="margin-top:8px">${RSK.map(k=>`<span><span class="sw" style="background:${RSC[k]}"></span>${RSL[k]}</span>`).join('')}</div>`;
  /* category x season table */
  const fams=[...famA.keys()].filter(f=>D.dicts.fam[f]!=='(non renseigné)');
  const heat=(p)=>`background:color-mix(in oklab,var(--c2) ${Math.round(6+Math.min(1,p/0.4)*70)}%,var(--surface));color:${p>0.25?'#fff':'var(--ink)'}`;
  const catRow=(lab,arr)=>`<tr><td class="lab"><b>${lab}</b></td>${arr.map((x,i)=>{const t=x[0]+x[1]+x[2]+x[3];if(t<=0)return'<td class="dash">—</td>';const p=rsPct(x);return`<td style="${heat(p)};text-align:center" data-tip="${esc(`<b>${lab} ${sLab(ss[i])}</b><br>Pré-saison : ${fE(x[0])}<br>Réassort : ${fE(x[1])}<br>Opérations : ${fE(x[2])}<br>Autres : ${fE(x[3])}`)}">${Math.round(p*1000)/10} %</td>`}).join('')}</tr>`;
  const catTable=`<div class="scroll"><table class="pv hm"><thead><tr><th class="lab">Part du réassort dans le total commandé</th>${ss.map((x,i)=>`<th style="text-align:center">${sLab(x)}${!seasonDone(x)?' <span class="meta">en cours</span>':''}</th>`).join('')}</tr></thead><tbody>${fams.map(f=>catRow(D.dicts.fam[f],famA.get(f))).join('')}${catRow('Total',tot)}</tbody></table></div>`;
  /* client / centrale table */
  const dimKey=RS.dim;const keyFn=dimKey==='cg'?(i=>D.custCgI[C.cust[i]]):dimKey==='grp'?(i=>D.custGrpI[C.cust[i]]):dimKey==='sales'?(i=>D.custSalesI[C.cust[i]]):(i=>C.cust[i]);
  const famKey=(i)=>keyFn(i)*16+C.fam[i];
  const byK=rsAgg(rows,keyFn),byKF=rsAgg(rows,famKey);
  const fT=D.dicts.fam.indexOf('Textile'),fF=D.dicts.fam.indexOf('Footwear'),fA=D.dicts.fam.indexOf('Accessoires');
  let list=[...byK.entries()].map(([k,a])=>({k,v:a[se],t:a[se].reduce((s,y)=>s+y,0)})).filter(x=>x.t>0).sort((a,b)=>b.t-a.t);
  const total=list.length,hidden=!RS.all&&total>50?total-50:0;if(hidden)list=list.slice(0,50);
  const lab=k=>dimKey==='cust'?cLabel('cust',k):dimKey==='grp'?cLabel('grp',k):esc(D.dicts[dimKey][k]);
  const famPct=(k,f)=>{const a=byKF.get(k*16+f);if(!a)return'<span class="dash">—</span>';const x=a[se];const t=x.reduce((s,y)=>s+y,0);return t>0?`${Math.round(rsPct(x)*100)} %`:'<span class="dash">—</span>'};
  const rowsH=list.map(x=>{const p=rsPct(x.v),r=x.v[0]>0?x.v[1]/x.v[0]*100:null;return`<tr><td class="lab">${lab(x.k)}</td><td>${fE(x.t)}</td><td>${fE(x.v[0])}</td><td>${fE(x.v[1])}</td><td>${fE(x.v[2]+x.v[3])}</td><td><span class="dif"><i style="width:${Math.round(p*100)}%;background:var(--c2)"></i></span> <b>${Math.round(p*1000)/10} %</b></td><td>${r==null?'<span class="dash">—</span>':Math.round(r)+' €'}</td><td>${famPct(x.k,fT)}</td><td>${famPct(x.k,fF)}</td><td>${famPct(x.k,fA)}</td></tr>`}).join('');
  P.innerHTML=`<div class="toolbar">${reaSegView()}${famSeg()}<label class="levels"><span class="tl">Saison analysée</span><select id="rsSeason">${ss.map((x,i)=>`<option value="${i}" ${i===se?'selected':''}>${sLab(x)} · ${x}${!seasonDone(x)?' (en cours)':''}</option>`).join('')}</select></label></div>
  <div class="card"><div class="card-b expl">${txt}</div></div>
  <div class="card"><div class="card-h"><h2>Pré-saison et réassort par saison</h2><span class="note">Montants commandés · % = part du réassort dans le total de la saison</span></div><div class="card-b">${chart}</div></div>
  <div class="card"><div class="card-h"><h2>Poids du réassort par catégorie</h2><span class="note">Plus la case est foncée, plus le réassort pèse · survolez une case pour le détail en €</span></div>${catTable}</div>
  <div class="card"><div class="card-h"><h2>Réassort par ${({cg:'centrale (groupe client)',grp:'groupement',cust:'client',sales:'commercial'})[dimKey]} · ${sLab(ss[se])}</h2>
    <div class="seg" id="rsDim" role="group">${[['cg','Centrale'],['grp','Groupement'],['cust','Client'],['sales','Commercial']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dimKey===k}">${l}</button>`).join('')}</div></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">${({cg:'Centrale',grp:'Groupement',cust:'Client',sales:'Commercial'})[dimKey]}</th><th>Total commandé</th><th>Pré-saison</th><th>Réassort</th><th>OP + autres</th><th>Part réassort</th><th>Réassort pour 100 € de PS</th><th>% réassort Textile</th><th>% réassort Footwear</th><th>% réassort Access.</th></tr></thead><tbody>${rowsH||'<tr class="more"><td colspan="10">Aucune commande sur cette saison.</td></tr>'}
    ${hidden?`<tr class="more"><td colspan="10"><button type="button" class="linkbtn" id="rsAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}</tbody></table></div></div>`;
  bindFam();bindRsView(P);
  $('#rsSeason').onchange=e=>{RS.season=+e.target.value;rsPanel(P)};
  $('#rsDim').onclick=e=>{const b=e.target.closest('button');if(b){RS.dim=b.dataset.d;RS.all=false;rsPanel(P)}};
  const a=$('#rsAll');if(a)a.onclick=()=>{RS.all=true;rsPanel(P)};
}
function rsFicheHtml(cm){
  const cls=rsClass(),ss=D.dicts.season,ns=ss.length;const mp=(()=>{if(!F.perim.size)return null;const m=new Uint8Array(D.dicts.perim.length);F.perim.forEach(i=>m[i]=1);return m})();
  const agg=Array.from({length:ns},()=>[0,0,0,0]);const fam=new Map();
  for(let i=0;i<N;i++){if(!cm[C.cust[i]])continue;if(mp&&!mp[C.perim[i]])continue;const t=cls[C.sot[i]];if(t<0)continue;agg[C.season[i]][t]+=C.amt[i];
    let f=fam.get(C.fam[i]);if(!f){f=Array.from({length:ns},()=>[0,0,0,0]);fam.set(C.fam[i],f)}f[C.season[i]][t]+=C.amt[i]}
  const used=ss.map((s,i)=>i).filter(i=>agg[i].some(v=>v));if(!used.length)return'';
  const famRow=f=>{const a=fam.get(f);if(!a)return'';return`<tr><td class="lab mutedv">% réassort ${esc(D.dicts.fam[f])}</td>${used.map(i=>{const t=a[i].reduce((s,y)=>s+y,0);return`<td>${t>0?Math.round(rsPct(a[i])*100)+' %':'<span class="dash">—</span>'}</td>`}).join('')}</tr>`};
  return`<section><h3 class="fs">Pré-saison et réassort <span class="meta">part du réassort (RE) dans le total commandé, par saison</span></h3><div class="scroll"><table class="pv"><thead><tr><th class="lab"></th>${used.map(i=>`<th>${sLab(ss[i])}</th>`).join('')}</tr></thead><tbody>
    <tr><td class="lab">Pré-saison (PS)</td>${used.map(i=>`<td>${fE(agg[i][0])}</td>`).join('')}</tr>
    <tr><td class="lab">Réassort (RE)</td>${used.map(i=>`<td>${fE(agg[i][1])}</td>`).join('')}</tr>
    <tr><td class="lab">OP + autres</td>${used.map(i=>`<td>${fE(agg[i][2]+agg[i][3])}</td>`).join('')}</tr>
    <tr class="l0"><td class="lab">Part réassort</td>${used.map(i=>`<td><b>${Math.round(rsPct(agg[i])*1000)/10} %</b></td>`).join('')}</tr>
    ${[...fam.keys()].filter(f=>D.dicts.fam[f]!=='(non renseigné)').map(famRow).join('')}</tbody></table></div></section>`;
}


/* ---------- une colonne par information (références, clients) ---------- */
function hr0(tb,hi){const r=tb.tHead.rows[0];if(r.cells[hi])r.cells[hi].classList.add('keyc');[...tb.tBodies[0].rows].forEach(row=>{const c=row.cells[hi];if(c&&row.cells.length>1)c.classList.add('keyc')})}
function splitCols(tb,hi,specs){
  tb.classList.add('split');hr0(tb,hi);const hr=tb.tHead.rows[0];let prev=hr.cells[hi];specs.forEach(([lab])=>{const th=document.createElement('th');th.className='lab';th.textContent=lab;prev.after(th);prev=th});
  [...tb.tBodies[0].rows].forEach(r=>{const cs=[...r.cells];if(cs.length===1&&cs[0].colSpan>1){cs[0].colSpan+=specs.length;return}
    let ci=-1,acc=0;for(let k=0;k<cs.length;k++){if(acc===hi){ci=k;break}acc+=cs[k].colSpan||1}
    if(ci<0){specs.forEach(()=>r.appendChild(document.createElement('td')));return}
    let p=cs[ci];specs.forEach(([,sel,cls])=>{const td=document.createElement('td');td.className='lab '+cls;const el=p.parentNode&&cs[ci].querySelector(sel);if(el)td.appendChild(el);p.after(td);p=td})});
}
function splitAll(root){
  root.querySelectorAll('table.pv').forEach(tb=>{if(tb.dataset.rs)return;const hr=tb.tHead&&tb.tHead.rows[0];if(!hr)return;tb.dataset.rs=1;
    const heads=[...hr.cells].map(th=>th.textContent.trim());
    const ri=heads.findIndex(h=>/^Référence/.test(h));
    if(ri>=0&&heads[ri+1]!=='Désignation article'&&!/^Désignation/.test(heads[ri+1]||'')&&tb.querySelector('tbody .refpair'))splitCols(tb,ri,[['Désignation','.refd','refdc']]);
    const h2=[...hr.cells].map(th=>th.textContent.trim());
    const ci=h2.findIndex(h=>/^(Magasin|Client|Porte perdue)/.test(h));
    if(ci>=0&&tb.querySelector('tbody .cpair'))splitCols(tb,ci,[['Ville','.ccity','ccol'],['Groupement','.cgrp','ccol'],['','.cact','ccol']]);
  });
}
let RSO=null;function watchRefs(){const go=()=>{clearTimeout(RSO);RSO=setTimeout(()=>{['#panel','#fiche'].forEach(s=>{const el=$(s);if(el)splitAll(el)})},0)};
  new MutationObserver(go).observe(document.body,{childList:true,subtree:true});go()}

/* ================= onglet Moi : tableau de bord commercial ================= */
const MOI={who:null,mode:'sales',quad:null};
function goTab(t){const b=document.querySelector(`.tab[data-t="${t}"]`);if(b){b.click();window.scrollTo(0,0)}}
const more=(t,l)=>`<button type="button" class="linkbtn gomore" data-go="${t}">${l} →</button>`;
function moiPanel(P){
  const ss=D.dicts.season,n=sLab(ss[S.n]),p=sLab(ss[S.p]),ns=ss.length,nc=D.dicts.cust.length;
  const isMgr=MOI.mode==='n1';const dict=isMgr?D.dicts.n1:D.dicts.sales,map=isMgr?D.custN1I:D.custSalesI;
  if(MOI.who==null){let saved='';try{saved=localStorage.getItem('lcs-mine')||''}catch(e){}const i=D.dicts.sales.indexOf(saved);MOI.who=i>=0&&!isMgr?i:dict.findIndex(v=>v!=='(non renseigné)')}
  const who=MOI.who,name=dict[who]||'—';
  const mine=new Uint8Array(nc);for(let c=0;c<nc;c++)if(map[c]===who)mine[c]=1;
  /* --- CA par client et saison, hors filtre commercial --- */
  const masks=FDIMS.filter(d=>d!=='sales'&&d!=='n1'&&F[d].size).map(d=>{const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return[C[d],m]});
  const ex=S.exclC?CENT:-2;const H=Array.from({length:ns},()=>new Float64Array(nc));const Q=[new Float64Array(nc),new Float64Array(nc)];
  const catM=new Map();const famT=new Map();
  outer:for(let i=0;i<N;i++){const c=C.cust[i];if(c===ex)continue;for(const[col,m]of masks)if(!m[col[i]])continue outer;const s=C.season[i],a=C.amt[i];H[s][c]+=a;
    if(s===S.n)Q[0][c]+=C.qty[i];else if(s===S.p)Q[1][c]+=C.qty[i];
    if(mine[c]&&(s===S.n||s===S.p)){const k=C.fam[i]*5+segOfRow(i);const v=catM.get(k)||[0,0];v[s===S.n?0:1]+=a;catM.set(k,v)}}
  const cN=H[S.n],cP=H[S.p];
  const agg=(sel)=>{let tN=0,tP=0,qN=0,qP=0,dN=0,dP=0,coN=0,coP=0,comp=0,lost=0,lostP=0,nw=0,nwN=0;for(let c=0;c<nc;c++){if(!sel(c))continue;const a=cN[c],b=cP[c];tN+=a;tP+=b;qN+=Q[0][c];qP+=Q[1][c];if(a>0)dN++;if(b>0)dP++;if(a>0&&b>0){comp++;coN+=a;coP+=b}else if(b>0){lost++;lostP+=b}else if(a>0){nw++;nwN+=a}}return{tN,tP,qN,qP,dN,dP,coN,coP,comp,lost,lostP,nw,nwN}};
  const M=agg(c=>mine[c]),T=agg(()=>true);
  const ev=(a,b)=>b?(a-b)/b:0,pc=v=>(v>=0?'+':'−')+Math.abs(Math.round(v*1000)/10).toLocaleString('fr-FR')+' %';
  /* classement (évolution comparable) */
  const rk=dict.map((v,i)=>{if(v==='(non renseigné)'||v==='Représentant divers')return null;const a=agg(c=>map[c]===i);return a.coP>0?{i,e:ev(a.coN,a.coP)}:null}).filter(Boolean).sort((a,b)=>b.e-a.e);
  const rank=rk.findIndex(x=>x.i===who)+1;
  /* reste à livrer */
  const bl={tot:0,late:0,wait:0,ok:0,q:0,byCust:new Map(),byMon:new Map(),byCg:new Map()};
  if(BL)for(let i=0;i<BL.n;i++){const c=BL.cols.cust[i];if(!mine[c])continue;const a=BL.cols.a[i];bl.tot+=a;bl.q+=BL.cols.q[i];
    const st=BL.late[i]?'late':BL.wait[i]?'wait':'ok';bl[st]+=a;const o=bl.byCust.get(c)||{late:0,wait:0,ok:0,tot:0,dmin:'9'};o[st]+=a;o.tot+=a;const d=BL.dicts.dl[BL.cols.dl[i]];if(st!=='late'&&d&&d!=='(non renseigné)'&&d<o.dmin)o.dmin=d;bl.byCust.set(c,o);
    const mk=BL.late[i]?'0-Retard':(BL.mon[i]||'9-Sans date');bl.byMon.set(mk,(bl.byMon.get(mk)||0)+a);const g=D.custCgI[c];const og=bl.byCg.get(g)||{tot:0,late:0,wait:0};og.tot+=a;og[st==='ok'?'tot':st]+=st==='ok'?0:a;bl.byCg.set(g,og)}
  /* par enseigne */
  const ens=new Map();for(let c=0;c<nc;c++){if(!mine[c]||(!cN[c]&&!cP[c]&&!bl.byCust.has(c)))continue;const g=D.custCgI[c];const o=ens.get(g)||{n:0,p:0,dN:0,dP:0,lost:0,cl:new Set()};o.n+=cN[c];o.p+=cP[c];if(cN[c]>0)o.dN++;if(cP[c]>0)o.dP++;if(cP[c]>0&&!(cN[c]>0))o.lost++;o.cl.add(c);ens.set(g,o)}
  /* réseau par enseigne (toutes portes comparables) */
  const netE=new Map();for(let c=0;c<nc;c++){if(!(cN[c]>0&&cP[c]>0))continue;const g=D.custCgI[c];const o=netE.get(g)||[0,0];o[0]+=cN[c];o[1]+=cP[c];netE.set(g,o)}
  /* alertes */
  const al=[];for(let c=0;c<nc;c++){if(!mine[c])continue;const a=cN[c],b=cP[c];if(a>0&&b>=AL.min&&(a-b)/b<=-AL.drop/100)al.push({c,t:'baisse',v:b-a});else if(!(a>0)&&b>=AL.lostMin)al.push({c,t:'perdue',v:b})}
  al.sort((x,y)=>y.v-x.v);
  /* urgences */
  const urg=[];
  [...bl.byCust.entries()].filter(([,o])=>o.late>0).forEach(([c,o])=>urg.push({c,kind:'retard',v:o.late,txt:`${fE(o.late)} à facturer avec une date de livraison dépassée. À relancer avec l'ADV.`}));
  al.filter(x=>x.v>=AL.crit).forEach(x=>urg.push({c:x.c,kind:x.t,v:x.v,txt:x.t==='perdue'?`Aucune commande sur ${n}, contre ${fE(cP[x.c])} sur ${p}.`:`${fE(cN[x.c])} sur ${n} contre ${fE(cP[x.c])} sur ${p} (${pc(ev(cN[x.c],cP[x.c]))}).`}));
  urg.sort((a,b)=>b.v-a.v);
  const plan=[...bl.byCust.entries()].filter(([,o])=>o.ok+o.wait>0).map(([c,o])=>({c,v:o.ok+o.wait,wait:o.wait,d:o.dmin})).sort((a,b)=>a.d.localeCompare(b.d)).slice(0,10);
  /* bons / mauvais points */
  const deltas=[];for(let c=0;c<nc;c++)if(mine[c]&&(cN[c]||cP[c]))deltas.push({c,d:cN[c]-cP[c]});
  const ups=deltas.filter(x=>x.d>0).sort((a,b)=>b.d-a.d).slice(0,3),dns=deltas.filter(x=>x.d<0).sort((a,b)=>a.d-b.d).slice(0,3);
  const cats=[...catM.entries()].map(([k,v])=>({k,d:v[0]-v[1],n:v[0],p:v[1]}));const cu=cats.filter(x=>x.d>0).sort((a,b)=>b.d-a.d)[0],cd=cats.filter(x=>x.d<0).sort((a,b)=>a.d-b.d)[0];
  const cn=k=>`${D.dicts.fam[Math.floor(k/5)]} ${SEGS[k%5]}`;
  const good=[],bad=[];
  if(M.coP>0&&ev(M.coN,M.coP)>ev(T.coN,T.coP))good.push(`Comparable à <b>${pc(ev(M.coN,M.coP))}</b>, mieux que l'équipe (${pc(ev(T.coN,T.coP))}).`);
  else if(M.coP>0)bad.push(`Comparable à <b>${pc(ev(M.coN,M.coP))}</b>, moins bien que l'équipe (${pc(ev(T.coN,T.coP))}).`);
  if(M.nw)good.push(`<b>${fN(M.nw)} nouvelle${M.nw>1?'s':''} porte${M.nw>1?'s':''}</b> ouverte${M.nw>1?'s':''} sur ${n}, pour ${fE(M.nwN)}.`);
  ups.forEach(x=>good.push(`${cLabelTxt(x.c)} : <b>${fS(x.d)} €</b> (${fE(cP[x.c])} → ${fE(cN[x.c])}).`));
  if(cu)good.push(`Catégorie en progression : <b>${cn(cu.k)}</b> ${fS(cu.d)} € (${fE(cu.p)} → ${fE(cu.n)}).`);
  if(M.lost)bad.push(`<b>${fN(M.lost)} porte${M.lost>1?'s':''} sans commande</b> sur ${n}, qui avai${M.lost>1?'ent':'t'} fait ${fE(M.lostP)} sur ${p}.`);
  dns.forEach(x=>bad.push(`${cLabelTxt(x.c)} : <b>${fS(x.d)} €</b> (${fE(cP[x.c])} → ${fE(cN[x.c])}).`));
  if(cd)bad.push(`Catégorie en recul : <b>${cn(cd.k)}</b> ${fS(cd.d)} € (${fE(cd.p)} → ${fE(cd.n)}).`);
  if(bl.late>0)bad.push(`<b>${fE(bl.late)}</b> de reste à facturer en retard (date de livraison dépassée).`);
  /* --- rendu --- */
  const inProg=!seasonDone(ss[S.n]);
  const names=dict.map((v,i)=>[v,i]).filter(([v])=>v!=='(non renseigné)').sort((a,b)=>a[0].localeCompare(b[0]));
  const tile=(h,a,b,ta,tb,fmt)=>{const e=ev(a,b),et=ev(ta,tb);return`<div class="kt ${b?(a>=b?'up':'down'):''}"><div class="kt-h"><h3>${h}</h3>${chip(a,b).replace('class="chip','class="chip bigchip')}</div><div class="big">${fmt(a)}</div><div class="foot">${p} : ${fmt(b)}<br>Équipe : ${tb?pc(et):'—'} ${b&&tb?`<b class="${e>=et?'pos':'neg'}">(${e>=et?'mieux':'moins bien'})</b>`:''}</div></div>`};
  const cmM=M.dN?M.tN/M.dN:0,cmMP=M.dP?M.tP/M.dP:0,cmT=T.dN?T.tN/T.dN:0,cmTP=T.dP?T.tP/T.dP:0;
  const ensRows=[...ens.entries()].sort((a,b)=>b[1].n-a[1].n).map(([g,o])=>{const b2=bl.byCg.get(g)||{tot:0,late:0,wait:0};const ne=netE.get(g);const al2=al.filter(x=>D.custCgI[x.c]===g).length;
    return`<tr><td class="lab"><b>${esc(D.dicts.cg[g])}</b> <span class="meta">${fN(o.cl.size)} clients</span></td><td>${fE(o.n)}</td><td>${fE(o.p)}</td><td>${chip(o.n,o.p)}</td><td>${ne&&ne[1]>0?pc(ev(ne[0],ne[1])):'<span class="dash">—</span>'}</td><td>${fN(o.dN)} <span class="meta">/ ${fN(o.dP)}</span></td><td>${b2.tot?fE(b2.tot):'<span class="dash">—</span>'}</td><td>${b2.late?`<span class="neg">${fE(b2.late)}</span>`:'<span class="dash">—</span>'}</td><td>${b2.wait?fE(b2.wait):'<span class="dash">—</span>'}</td><td>${al2?`<span class="sev crit">${al2}</span>`:'<span class="dash">0</span>'}</td></tr>`}).join('');
  const kindLab={retard:['Retard livr.','warn'],perdue:['Sans commande','crit'],baisse:['Forte baisse','crit']};
  const urgRows=urg.slice(0,12).map(u=>`<div class="al-row"><span class="sev ${kindLab[u.kind][1]}">${kindLab[u.kind][0]}</span><div class="al-main"><div class="al-t">${cLabel('cust',u.c)}</div><div class="al-s">${u.txt}</div></div><div class="al-v"><b>${fE(u.v)}</b><span class="meta">${u.kind==='retard'?'en retard':'en jeu'}</span></div></div>`).join('');
  const planRows=plan.map(x=>`<tr><td class="lab">${cLabel('cust',x.c)}</td><td>${fE(x.v)}</td><td>${x.wait?fE(x.wait):'<span class="dash">—</span>'}</td><td>${x.d==='9'?'—':fD(x.d)}</td></tr>`).join('');
  /* reste à livrer par mois */
  const mons=[...bl.byMon.entries()].sort((a,b)=>a[0].localeCompare(b[0]));const MN=['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
  const mx=Math.max(1,...mons.map(x=>x[1]));const monBars=mons.map(([k,v])=>{const lab=k.startsWith('0')?'Retard':k.startsWith('9')?'Sans date':MN[+k.slice(5,7)-1]+' '+k.slice(2,4);return`<div class="mb"><span class="mb-l ${k.startsWith('0')?'neg':''}">${lab}</span><span class="mb-t"><i style="width:${Math.max(1,v/mx*100)}%;background:${k.startsWith('0')?'var(--bad)':'var(--n)'}"></i></span><span class="mb-v">${fE(v)}</span></div>`}).join('');
  /* historique par type de saison */
  const histHtml=(()=>{const myH=H.map(arr=>{let s=0;for(let c=0;c<nc;c++)if(mine[c])s+=arr[c];return s}),tmH=H.map(arr=>arr.reduce((s,v)=>s+v,0));
    const rows=k=>ss.map((s,i)=>[s,i]).filter(([s])=>s.endsWith(k)&&(myH[ss.indexOf(s)]>0||tmH[ss.indexOf(s)]>0));
    const mx=Math.max(1,...myH);
    const blk=(k,lab)=>{const r=rows(k);if(!r.length)return'';return`<div class="hs"><div class="hs-t">${lab}</div>${r.map(([s,i])=>{const prev=ss.indexOf((+s.slice(0,4)-1)+s.slice(4));const pm=prev>=0?myH[prev]:0,pt=prev>=0?tmH[prev]:0;
      const ev1=pm>0?(myH[i]-pm)/pm:null,evt=pt>0?(tmH[i]-pt)/pt:null;const cur=i===S.n,pp=i===S.p,run=!seasonDone(s);
      return`<div class="hr${cur?' cur':''}"><span class="hr-l">${sLab(s)}${run?' <span class="meta">en cours</span>':''}</span><span class="mb-t"><i style="width:${Math.max(1,myH[i]/mx*100)}%;background:${cur?'var(--n)':pp?'var(--p)':'var(--bar)'}"></i></span><span class="hr-v">${fE(myH[i])}</span><span class="hr-e">${ev1==null?'<span class="dash">—</span>':`<b class="${ev1>=0?'pos':'neg'}">${pc(ev1)}</b>`} <span class="meta">${evt==null?'':`équipe ${pc(evt)}`}</span></span></div>`}).join('')}</div>`};
    return blk('SS','Printemps-été (SS)')+blk('FW','Automne-hiver (FW)')+`<p class="meta" style="margin:8px 0 0">Évolution = par rapport à la même saison l'année précédente (SS26 vs SS25, FW26 vs FW25). « Équipe » = même calcul pour tous les commerciaux.${!seasonDone(ss[S.n])?' Les saisons en cours ne sont pas closes.':''}</p>`})();
  /* historique indexé */
  const myH=H.map(arr=>{let s=0;for(let c=0;c<nc;c++)if(mine[c])s+=arr[c];return s}),tmH=H.map(arr=>arr.reduce((s,v)=>s+v,0));
  const W=640,Hh=200,ml=46,mr=10,mt=16,mb=28,pw=W-ml-mr,ph=Hh-mt-mb;const base=myH.findIndex(v=>v>0),bt=tmH[base]||1,bm=myH[base]||1;
  const iM=myH.map(v=>v/bm*100),iT=tmH.map(v=>v/bt*100);const top=niceMax(Math.max(...iM,...iT,100));const X=i=>ml+i*(pw/(ns-1)),Y=v=>mt+ph-v/top*ph;
  let svg='';for(let k=0;k<=4;k++){const v=top*k/4;svg+=`<line x1="${ml}" x2="${W-mr}" y1="${Y(v)}" y2="${Y(v)}" class="grid"/><text x="${ml-6}" y="${Y(v)+4}" text-anchor="end" class="ax">${Math.round(v)}</text>`}
  const line=(arr,col)=>{const pts=arr.map((v,i)=>[X(i),Y(v)]).filter((_,i)=>i>=base&&(myH[i]>0||tmH[i]>0));return`<polyline fill="none" stroke="${col}" stroke-width="2.5" points="${pts.map(p=>p.join(',')).join(' ')}"/>`+pts.map(p=>`<circle cx="${p[0]}" cy="${p[1]}" r="3.5" fill="${col}"/>`).join('')};
  svg+=line(iT,'var(--bar)')+line(iM,'var(--n)');ss.forEach((s,i)=>{svg+=`<g data-tip="<b>${sLab(s)}</b><br>Moi : ${fE(myH[i])}<br>Équipe : ${fE(tmH[i])}"><rect x="${X(i)-20}" y="${mt}" width="40" height="${ph}" fill="transparent"/><text x="${X(i)}" y="${Hh-8}" text-anchor="middle" class="ax">${sLab(s)}</text></g>`});
  /* quadrant */
  const pts=[];for(let c=0;c<nc;c++)if(mine[c]&&cP[c]>0&&cN[c]>0)pts.push({c,size:cP[c],e:ev(cN[c],cP[c])});
  const medS=med(pts.map(x=>x.size));const q={fid:[],sauv:[],dev:[],surv:[]};pts.forEach(x=>{const big=x.size>=medS,upq=x.e>=0;q[big?(upq?'fid':'sauv'):(upq?'dev':'surv')].push(x)});
  const qbox=(k,t,cls,d)=>`<button type="button" class="qd ${cls} ${MOI.quad===k?'on':''}" data-q="${k}"><b>${q[k].length}</b><span>${t}</span><span class="meta">${d}</span></button>`;
  const qList=MOI.quad?q[MOI.quad].sort((a,b)=>b.size-a.size).map(x=>`<tr><td class="lab">${cLabel('cust',x.c)}</td><td>${fE(cN[x.c])}</td><td>${fE(cP[x.c])}</td><td>${chip(cN[x.c],cP[x.c])}</td></tr>`).join(''):'';
  P.innerHTML=`<div class="moi-head card"><div class="card-b">
    <div class="moi-sel"><div class="seg" id="moiMode"><button type="button" data-m="sales" aria-pressed="${!isMgr}">Commercial</button><button type="button" data-m="n1" aria-pressed="${isMgr}">Manager N+1</button></div>
    <select id="moiWho">${names.map(([v,i])=>`<option value="${i}" ${i===who?'selected':''}>${esc(v)}</option>`).join('')}</select></div>
    <h2 class="moi-t">${esc(name)}</h2>
    <p class="moi-s">${isMgr?'Ton équipe':'Ton portefeuille'} fait <b>${fE(M.tN)}</b> sur ${n}, contre ${fE(M.tP)} sur ${p} (<b>${pc(ev(M.tN,M.tP))}</b>), avec ${fN(M.dN)} portes actives.${rank&&!isMgr?` En évolution comparable, tu es <b>${rank}${rank===1?'er':'e'} sur ${rk.length}</b> commerciaux.`:''} Reste à livrer : <b>${fE(bl.tot)}</b>${bl.late?`, dont <b class="neg">${fE(bl.late)} en retard</b>`:''}.</p>
    ${inProg?`<p class="warnline" style="margin:0">La saison ${n} n'est pas terminée au ${fD(ASOF)} : les écarts avec ${p} vont encore bouger.</p>`:''}
    <p class="meta" style="margin:6px 0 0">Clients rattachés à ${isMgr?'ce manager':'ce commercial'} (commercial principal sur leurs commandes). Filtres appliqués : tous sauf Commercial et Manager.</p></div></div>
  <div class="kgrid">${tile('CA '+typeLab(),M.tN,M.tP,T.tN,T.tP,fM)}${tile('Quantités',M.qN,M.qP,T.qN,T.qP,fN)}${tile('Portes actives',M.dN,M.dP,T.dN,T.dP,fN)}${tile('Commande moyenne',cmM,cmMP,cmT,cmTP,fM)}${tile('Comparable',M.coN,M.coP,T.coN,T.coP,fM)}
    <div class="kt ${bl.late?'down':''}"><div class="kt-h"><h3>Reste à livrer</h3></div><div class="big">${fM(bl.tot)}</div><div class="foot">En retard : <b class="neg">${fE(bl.late)}</b><br>Attendu fournisseur : ${fE(bl.wait)}<br>Prêt / à date : ${fE(bl.ok)}</div></div></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><h2>Ce qui va bien</h2></div><ul class="pts good">${good.map(x=>`<li>${x}</li>`).join('')||'<li class="mutedv">Rien de notable.</li>'}</ul></div>
    <div class="card"><div class="card-h"><h2>Ce qui ne va pas</h2></div><ul class="pts bad">${bad.map(x=>`<li>${x}</li>`).join('')||'<li class="mutedv">Rien de notable.</li>'}</ul></div>
  </div>
  <div class="card"><div class="card-h"><h2>Par enseigne</h2>${more('clients','Vue centrale')}<span class="note">Réseau = évolution de toutes les portes comparables de l'enseigne, tous commerciaux</span></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Enseigne</th><th class="cn">CA ${n}<span class="bar"></span></th><th class="cp">CA ${p}<span class="bar"></span></th><th>Évo</th><th>Évo réseau</th><th>Portes ${n} / ${p}</th><th>Reste à livrer</th><th>dont retard</th><th>dont attendu fourn.</th><th>Alertes</th></tr></thead><tbody>${ensRows||'<tr class="more"><td colspan="10">Aucune enseigne.</td></tr>'}</tbody></table></div></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><h2>Urgent <span class="alc on">${urg.length}</span></h2>${more('alertes','Toutes les alertes')}<span class="note">Retards de livraison et alertes critiques, par montant</span></div><div class="al-list">${urgRows||'<p class="mutedv" style="padding:16px;margin:0">Aucune urgence.</p>'}</div>${urg.length>12?`<div class="card-b meta">+ ${urg.length-12} autres : voir l'onglet Alertes et Reste à facturer.</div>`:''}</div>
    <div class="card"><div class="card-h"><h2>À planifier</h2>${more('backlog','Reste à facturer')}<span class="note">Prochaines livraisons, non urgentes</span></div>
      <div class="card-b"><div class="mbs">${monBars||'<p class="mutedv">Pas de reste à livrer.</p>'}</div></div>
      <div class="scroll"><table class="pv"><thead><tr><th class="lab">Client</th><th>À livrer</th><th>dont attendu fourn.</th><th>Prochaine livraison</th></tr></thead><tbody>${planRows||'<tr class="more"><td colspan="4">—</td></tr>'}</tbody></table></div></div>
  </div>
  <div class="grid2">
    <div class="card"><div class="card-h"><h2>Historique</h2>${more('saisons','Évolution saisons')}<span class="note">CA ${typeLab()} de ${isMgr?"l'équipe du manager":'ton portefeuille'} par saison, comparé à la même saison l'année précédente</span></div><div class="card-b">${histHtml}</div></div>
    <div class="card"><div class="card-h"><h2>Mon portefeuille</h2>${more('magasins','Magasins')}<span class="note">Clients comparables · gros = CA ${p} ≥ ${fE(medS)}</span></div><div class="card-b"><div class="quad">${qbox('fid','Gros clients en hausse','q1','à fidéliser')}${qbox('sauv','Gros clients en baisse','q2','à sauver')}${qbox('dev','Petits clients en hausse','q3','à développer')}${qbox('surv','Petits clients en baisse','q4','à surveiller')}</div></div>
      ${MOI.quad?`<div class="scroll"><table class="pv"><thead><tr><th class="lab">Client</th><th class="cn">CA ${n}<span class="bar"></span></th><th class="cp">CA ${p}<span class="bar"></span></th><th>Évo</th></tr></thead><tbody>${qList}</tbody></table></div>`:''}</div>
  </div>`;
  $('#moiWho').onchange=e=>{MOI.who=+e.target.value;MOI.quad=null;moiPanel(P)};
  $('#moiMode').onclick=e=>{const b=e.target.closest('button');if(!b)return;MOI.mode=b.dataset.m;MOI.who=null;MOI.quad=null;moiPanel(P)};
  P.querySelectorAll('[data-go]').forEach(b=>b.onclick=()=>{if(!isMgr){F.sales.clear();F.sales.add(who)}else{F.n1.clear();F.n1.add(who)}updBtn('sales');updBtn('n1');compute();renderKPIs();goTab(b.dataset.go)});
  P.querySelectorAll('.qd').forEach(b=>b.onclick=()=>{MOI.quad=MOI.quad===b.dataset.q?null:b.dataset.q;moiPanel(P)});
}
function cLabelTxt(c){return `<b>${esc(D.dicts.cust[c])}</b> ${esc(D.custCity[c]||'')}${D.custGrp[c]?' (<button type="button" class="gchip-l" data-g="'+D.custGrpI[c]+'">'+esc(D.custGrp[c])+'</button>)':''} <button type="button" class="fiche" data-c="${c}">Fiche</button>`}


/* ---------- répartition Homme / Femme / Enfant ---------- */
function hfeView(host){
  const n=sLab(D.dicts.season[S.n]),p=sLab(D.dicts.season[S.p]);
  const SG=['Homme','Femme','Enfant','Unisexe'],COL=['var(--c1)','var(--c5)','var(--c3)','var(--bar)'];
  const segIx=i=>{const s=segOfRow(i);return s===0?0:s===1?1:s===3?2:s===2?3:-1};
  const fams=[['Textile','Apparel'],['Footwear','Footwear']].map(([f,l])=>[D.dicts.fam.indexOf(f),l]).filter(([f])=>f>=0);
  const A=new Map();fams.forEach(([f])=>A.set(f,{n:[0,0,0,0],p:[0,0,0,0],qn:[0,0,0,0],qp:[0,0,0,0]}));
  const add=(i,isN)=>{const o=A.get(C.fam[i]);if(!o)return;const s=segIx(i);if(s<0)return;o[isN?'n':'p'][s]+=C.amt[i];o[isN?'qn':'qp'][s]+=C.qty[i]};
  R.iN.forEach(i=>add(i,true));R.iP.forEach(i=>add(i,false));
  const pctS=(v,t)=>t>0?Math.round(v/t*1000)/10:0;
  host.innerHTML=`<div class="hfe">${fams.map(([f,l])=>{const o=A.get(f);const tN=o.n.reduce((a,b)=>a+b,0),tP=o.p.reduce((a,b)=>a+b,0),mx=Math.max(tN,tP,1);
    const bar=(arr,tot)=>tot>0?`<div class="stackw"><div class="stack" style="flex:0 0 calc((100% - 90px) * ${Math.max(.02,tot/mx).toFixed(4)})">${arr.map((v,k)=>v>0?`<span style="width:${v/tot*100}%;background:${COL[k]}" data-tip="${SG[k]} : ${fE(v)} (${pctS(v,tot)} %)"></span>`:'').join('')}</div><span class="stackv">${fE(tot)}</span></div>`:'<div class="stack empty">aucune commande</div>';
    const used=[0,1,2,3].filter(k=>o.n[k]||o.p[k]);
    return`<div class="hfe-b"><h3 class="fs">${l}</h3>
      <div class="stackrow"><span class="tl">${n}</span>${bar(o.n,tN)}</div><div class="stackrow"><span class="tl">${p}</span>${bar(o.p,tP)}</div>
      <div class="scroll"><table class="pv"><thead><tr><th class="lab">Segment</th><th class="cn">CA ${n}<span class="bar"></span></th><th>Part</th><th class="cp">CA ${p}<span class="bar"></span></th><th>Part</th><th>Évo CA</th><th>Qté ${n}</th><th>Évo qté</th></tr></thead><tbody>
      ${used.map(k=>`<tr><td class="lab"><span class="sw" style="background:${COL[k]}"></span>${SG[k]}</td><td>${fE(o.n[k])}</td><td><b>${pctS(o.n[k],tN)} %</b></td><td>${fE(o.p[k])}</td><td>${pctS(o.p[k],tP)} %</td><td>${chip(o.n[k],o.p[k])}</td><td>${fN(o.qn[k])}</td><td>${chip(o.qn[k],o.qp[k])}</td></tr>`).join('')}
      <tr class="tot"><td class="lab">Total ${l}</td><td>${fE(tN)}</td><td>100 %</td><td>${fE(tP)}</td><td>100 %</td><td>${chip(tN,tP)}</td><td>${fN(o.qn.reduce((a,b)=>a+b,0))}</td><td>${chip(o.qn.reduce((a,b)=>a+b,0),o.qp.reduce((a,b)=>a+b,0))}</td></tr></tbody></table></div></div>`}).join('')}</div>
    <p class="meta fexp" style="margin-top:10px">Enfant = tailles enfant (Infant, Preschool, Gradeschool${D.dicts.age.includes('JUNIOR')?', Junior':''}) cumulées. Homme / Femme = tailles adulte selon le genre article. Unisexe = articles adultes mixtes, affichés à part. La longueur des barres est proportionnelle au CA de chaque saison.</p>`;
}

/* ================= focus réassort (facturation) ================= */
let REA=null;const RF={season:null,metric:'q',dim:'grp',all:false};
async function initRea(){
  const el=document.getElementById('readata');if(!el||!el.textContent.trim().startsWith('{'))return;
  const o=JSON.parse(el.textContent);const bin=Uint8Array.from(atob(o.bin),c=>c.charCodeAt(0));
  const ab=await new Response(new Blob([bin]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer();
  const n=o.n;o.C={b:new Float32Array(ab,0,n),i:new Float32Array(ab,4*n,n),q:new Float32Array(ab,8*n,n),qi:new Float32Array(ab,12*n,n)};let off=16*n;
  o.u16.forEach(k=>{o.C[k]=new Uint16Array(ab,off,n);off+=2*n});o.u8.forEach(k=>{o.C[k]=new Uint8Array(ab,off,n);off+=n});o.bin=null;
  o.ci=o.dicts.cust.map(c=>D.dicts.cust.indexOf(c));REA=o;
}
function reaRows(){
  const masks=[];for(const d of ['perim','fam','bm1','cg','n1','sales','grp']){if(!F[d]||!F[d].size)continue;const allowed=new Set([...F[d]].map(i=>D.dicts[d][i]));masks.push([REA.C[d],REA.dicts[d].map(v=>allowed.has(v)?1:0)])}
  const ex=S.exclC?REA.dicts.cust.indexOf('4437'):-2;
  const out=[];outer:for(let i=0;i<REA.n;i++){if(REA.C.cust[i]===ex)continue;for(const[c,m]of masks)if(!m[c[i]])continue outer;out.push(i)}return out;
}
function reaSegView(){return`<div class="seg" id="rsView"><button type="button" data-v="rea" aria-pressed="${RS.view!=='mix'}">Focus réassort (facturé)</button><button type="button" data-v="mix" aria-pressed="${RS.view==='mix'}">Pré-saison vs réassort (commandé)</button></div>`}
function bindRsView(P){$('#rsView').onclick=e=>{const b=e.target.closest('button');if(!b)return;RS.view=b.dataset.v;rsPanel(P)}}
function reaPanel(P){
  if(!REA){P.innerHTML=`<div class="toolbar">${reaSegView()}</div><div class="card"><div class="card-b">Données de facturation non chargées.</div></div>`;bindRsView(P);return}
  const ss=REA.dicts.season,C2=REA.C,asofYM=REA.asof.slice(0,7),asofP=ymAdd(asofYM,-12),invD=REA.dicts.inv,wkD=REA.dicts.wk;
  const rows=reaRows();
  if(RF.season==null){const tot=ss.map(()=>0);for(const i of rows)tot[C2.season[i]]+=C2.b[i];RF.season=ss.indexOf(D.dicts.season[S.n]);if(RF.season<0||tot[RF.season]<1)RF.season=tot.findLastIndex(v=>v>50000)}
  const se=RF.season,sName=ss[se],sp=ss.indexOf((+sName.slice(0,4)-1)+sName.slice(4)),n=sLab(sName),p=sp>=0?sLab(ss[sp]):'N-1';
  const done=seasonDone(sName);const lim=done?'9999':asofYM,limP=done?'9999':asofP;
  /* agrégats */
  const z=()=>({b:0,i:0,q:0,qi:0,cust:new Set(),custI:new Set()});const N0=z(),P0=z(),PF=z();
  const mN=new Map(),mP=new Map(),wN=new Map();const dimK=RF.dim,agg=new Map();
  const keyOf=i=>dimK==='cust'?C2.cust[i]:C2[dimK][i];
  for(const i of rows){const s=C2.season[i];if(s!==se&&s!==sp)continue;const iv=invD[C2.inv[i]],c=C2.cust[i],k=keyOf(i);
    let a=agg.get(k);if(!a){a={n:z(),p:z(),months:new Set(),last:''};agg.set(k,a)}
    if(s===se){N0.b+=C2.b[i];N0.q+=C2.q[i];if(C2.b[i]>0)N0.cust.add(c);a.n.b+=C2.b[i];a.n.q+=C2.q[i];if(C2.b[i]>0)a.n.cust.add(c);
      if(iv&&iv<=lim&&C2.i[i]){N0.i+=C2.i[i];N0.qi+=C2.qi[i];if(C2.qi[i]>0)N0.custI.add(c);a.n.i+=C2.i[i];a.n.qi+=C2.qi[i];if(C2.qi[i]>0){a.n.custI.add(c);a.months.add(iv);if(iv>a.last)a.last=iv}
        const o=mN.get(iv)||{q:0,a:0,c:new Set()};o.q+=C2.qi[i];o.a+=C2.i[i];if(C2.qi[i]>0)o.c.add(c);mN.set(iv,o)}
      const w=wkD[C2.wk[i]];if(w){const o=wN.get(w)||{qi:0,qr:0,c:new Set()};o.qi+=C2.qi[i];o.qr+=C2.q[i]-C2.qi[i];if(C2.q[i]>0)o.c.add(c);wN.set(w,o)}}
    else{if(iv&&C2.i[i]){PF.i+=C2.i[i];PF.qi+=C2.qi[i];if(C2.qi[i]>0)PF.custI.add(c);const k2=ymAdd(iv,12);const o=mP.get(k2)||{q:0,a:0,c:new Set()};o.q+=C2.qi[i];o.a+=C2.i[i];if(C2.qi[i]>0)o.c.add(c);mP.set(k2,o)
      if(iv<=limP){P0.i+=C2.i[i];P0.qi+=C2.qi[i];if(C2.qi[i]>0)P0.custI.add(c);a.p.i+=C2.i[i];a.p.qi+=C2.qi[i];if(C2.qi[i]>0)a.p.custI.add(c)}}}}
  const nm=N0.custI.size,pm=P0.custI.size;const perN=nm?N0.qi/nm:0,perP=pm?P0.qi/pm:0;
  const monthsN=[...mN.keys()].sort();const firstM=monthsN.find(k=>mN.get(k).q>=0.02*N0.qi)||monthsN[0];
  const lastM=done?([...monthsN].reverse().find(k=>mN.get(k).q>=0.02*N0.qi)||monthsN[monthsN.length-1]):ymAdd(asofYM,-1);const rM=[];if(firstM)for(let k=done?firstM:ymAdd(lastM,-2);k<=lastM;k=ymAdd(k,1))rM.push(k);
  const wks=rM.reduce((a,k)=>a+new Date(+k.slice(0,4),+k.slice(5,7),0).getDate()/7,0)||1;
  const wkRate=rM.reduce((a,k)=>a+(mN.get(k)?.q||0),0)/wks,wkRateP=rM.reduce((a,k)=>a+(mP.get(k)?.q||0),0)/wks;
  const rLab=rM.length?(rM.length===1?ymLab(rM[0]):ymLab(rM[0])+' – '+ymLab(rM[rM.length-1])):'—';
  const dif=(a,b)=>chip(a,b);const sameD=done?'':' à la même date';
  const tile=(h,big,foot,cls)=>`<div class="kt ${cls||''}"><div class="kt-h"><h3>${h}</h3></div><div class="big">${big}</div><div class="foot">${foot}</div></div>`;
  /* graphique mensuel */
  const mxQ=Math.max(1,...[...mN.values(),...mP.values()].map(o=>o.q));const allM=[...new Set([...mN.keys(),...mP.keys()])].filter(k=>k&&Math.max(mN.get(k)?.q||0,mP.get(k)?.q||0)>=0.02*mxQ).sort();const months=[];if(allM.length)for(let k=allM[0];k<=allM[allM.length-1]&&months.length<24;k=ymAdd(k,1))months.push(k);
  const met=RF.metric,val=(o)=>!o?0:met==='q'?o.q:met==='a'?o.a:o.c.size,unit=met==='q'?' pcs':met==='a'?' €':' mag.',fV=v=>met==='a'?fE(v):fN(Math.round(v))+unit;
  const W=Math.max(680,months.length*60+70),H=250,ml=60,mr=10,mt=22,mb=30,pw=W-ml-mr,ph=H-mt-mb,bw=pw/Math.max(1,months.length);
  const mx=niceMax(Math.max(1,...months.map(k=>Math.max(val(mN.get(k)),val(mP.get(k))))));
  let s='';for(let t=0;t<=4;t++){const v=mx*t/4,y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${met==='a'?fK(v)+'€':fK(v)}</text>`}
  months.forEach((k,j)=>{const vn=val(mN.get(k)),vp=val(mP.get(k));const x=ml+j*bw,w=bw*.34;const hn=ph*vn/mx,hp=ph*vp/mx;const fut=!done&&k>asofYM;
    s+=`<g data-tip="${esc(`<b>${ymLab(k)}</b><br>${n} : ${fut?'à venir':fV(vn)}<br>${p} (${ymLab(ymAdd(k,-12))}) : ${fV(vp)}`)}"><rect x="${x}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>
    <rect x="${x+bw*.14}" y="${mt+ph-hp}" width="${w}" height="${hp}" fill="var(--bar)"/>${fut?'':`<rect x="${x+bw*.52}" y="${mt+ph-hn}" width="${w}" height="${hn}" fill="var(--n)"/>`}
    ${vn&&!fut?`<text x="${x+bw*.52+w/2}" y="${mt+ph-hn-4}" text-anchor="middle" class="ax">${met==='a'?fK(vn):fK(vn)}</text>`:''}
    <text x="${x+bw/2}" y="${H-10}" text-anchor="middle" class="ax">${ymLab(k)}</text></g>`});
  const chartM=months.length?`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:${Math.min(W,820)}px;display:block" role="img" aria-label="Réassort facturé par mois">${s}</svg></div>
    <div class="legend" style="margin-top:6px"><span><span class="sw" style="background:var(--n)"></span>${n}</span><span><span class="sw" style="background:var(--bar)"></span>${p}, même mois de l'année précédente</span></div>`:'<p class="meta">Aucun réassort facturé.</p>';
  /* graphique hebdo (N, semaine de livraison demandée) */
  const weeks=[...wN.keys()].sort();let chartW='';
  if(weeks.length>=3){const W2=Math.max(1060,weeks.length*34+70),H2=240,ph2=H2-mt-mb-14,bw2=(W2-ml-mr)/weeks.length;const mx2=niceMax(Math.max(1,...weeks.map(w=>wN.get(w).qi+wN.get(w).qr)));
    let s2='';for(let t=0;t<=4;t++){const v=mx2*t/4,y=mt+14+ph2-ph2*t/4;s2+=`<line x1="${ml}" x2="${W2-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}</text>`}
    const curW=(()=>{const d=new Date(REA.asof);d.setDate(d.getDate()-((d.getDay()+6)%7));return d.toISOString().slice(0,10)})();
    weeks.forEach((w,j)=>{const o=wN.get(w);const x=ml+j*bw2+bw2*.15,bw_=bw2*.7,hi=ph2*o.qi/mx2,hr=ph2*o.qr/mx2,base=mt+14+ph2;const late=w<curW&&o.qr>0;
      s2+=`<g data-tip="${esc(`<b>Semaine du ${fD(w)}</b><br>Magasins : ${o.c.size}<br>Pièces facturées : ${fN(Math.round(o.qi))}<br>Pièces restant à livrer : ${fN(Math.round(o.qr))}${late?' (en retard)':''}`)}"><rect x="${ml+j*bw2}" y="${mt}" width="${bw2}" height="${ph2+14}" fill="transparent"/>
      ${hi?`<rect x="${x}" y="${base-hi}" width="${bw_}" height="${hi}" fill="var(--n)"/>`:''}${hr?`<rect x="${x}" y="${base-hi-hr}" width="${bw_}" height="${hr}" fill="${late?'var(--bad)':'var(--c4)'}"/>`:''}
      <text x="${x+bw_/2}" y="${base-hi-hr-4}" text-anchor="middle" class="ax">${o.c.size}</text>
      ${j%2===0?`<text x="${x+bw_/2}" y="${H2-10}" text-anchor="middle" class="ax">${w.slice(8,10)}/${w.slice(5,7)}</text>`:''}</g>`});
    chartW=`<div class="card"><div class="card-h"><h2>Rythme hebdomadaire ${n}</h2><span class="note">Pièces par semaine de livraison demandée · le chiffre au-dessus de chaque barre = nombre de magasins</span></div><div class="card-b">
      <div class="scroll"><svg viewBox="0 0 ${W2} ${H2}" width="100%" style="min-width:${Math.min(W2,820)}px;display:block" role="img" aria-label="Réassort par semaine">${s2}</svg></div>
      <div class="legend" style="margin-top:6px"><span><span class="sw" style="background:var(--n)"></span>Facturé</span><span><span class="sw" style="background:var(--c4)"></span>À livrer</span><span><span class="sw" style="background:var(--bad)"></span>À livrer, semaine dépassée</span></div>
      <p class="meta" style="margin:8px 0 0">La facture n'est datée qu'au mois dans l'extraction : la semaine utilisée ici est celle de la livraison demandée sur la commande. Pas d'équivalent ${p} (les lignes déjà facturées de l'an dernier n'ont plus de date de livraison).</p></div></div>`}
  /* tableau */
  const lab=k=>{if(dimK==='cust'){const ci=REA.ci[k];return ci>=0?cLabel('cust',ci):esc(REA.dicts.cust[k])}return esc(REA.dicts[dimK][k])};
  let list=[...agg.entries()].filter(([,a])=>a.n.qi>0||a.p.qi>0||a.n.q>0).sort((x,y)=>y[1].n.qi-x[1].n.qi||y[1].n.q-x[1].n.q);const hidden=list.length>40&&!RF.all?list.length-40:0;if(hidden)list=list.slice(0,40);
  const isC=dimK==='cust';
  const trow=([k,a])=>{const mag=a.n.custI.size,pmag=a.p.custI.size;return`<tr><td class="lab">${lab(k)}</td>${isC?'':`<td>${fN(mag)}</td><td>${fN(pmag)}</td>`}<td><b>${fN(Math.round(a.n.qi))}</b></td><td>${fN(Math.round(a.p.qi))}</td><td>${dif(a.n.qi,a.p.qi)}</td><td>${fE(a.n.i)}</td>${isC?'':`<td>${mag?fN(Math.round(a.n.qi/mag)):'<span class="dash">—</span>'}</td>`}<td>${fN(a.months.size)}</td><td>${a.last?ymLab(a.last):'<span class="dash">—</span>'}</td><td>${a.n.q-a.n.qi>0.5?fN(Math.round(a.n.q-a.n.qi)):'<span class="dash">—</span>'}</td></tr>`};
  const head=`<tr><th class="lab">${({grp:'Groupement',cg:'Centrale (groupe client)',sales:'Commercial',cust:'Client'})[dimK]}</th>${isC?'':`<th>Magasins ${n}</th><th>Magasins ${p}</th>`}<th>Pièces ${n}</th><th>Pièces ${p}</th><th>Évol.</th><th>Facturé ${n} €</th>${isC?'':'<th>Pièces / magasin</th>'}<th>Mois actifs</th><th>Dernière facture</th><th>Pièces à livrer</th></tr>`;
  /* textes */
  const evM=pm?Math.round((nm-pm)/pm*100):null;
  const txt=`<div class="pvm-t">
    <p><b>${fN(nm)} magasins</b> ont été facturés en réassort ${n}${done?'':` à fin ${ymLab(asofYM)}`}${sp>=0?`, contre ${fN(pm)} en ${p}${sameD}${evM!=null?` (${evM>=0?'+':''}${evM} %)`:''}. Sur toute la saison ${p}, ${fN(PF.custI.size)} magasins avaient fait du réassort`:''}.</p>
    <p>Ils ont reçu <b>${fN(Math.round(N0.qi))} pièces</b> (${fE(N0.i)}), soit <b>${fN(Math.round(perN))} pièces par magasin</b>${sp>=0?` contre ${fN(Math.round(perP))} en ${p}`:''}. Sur ${rLab}, le rythme moyen est de <b>${fN(Math.round(wkRate))} pièces facturées par semaine</b>${sp>=0?`, contre ${fN(Math.round(wkRateP))} sur les mêmes mois en ${p}`:''}.${N0.q-N0.qi>0.5?` En plus, ${fN(Math.round(N0.q-N0.qi))} pièces de réassort sont commandées et pas encore facturées (${fN(N0.cust.size)} magasins ont une commande de réassort ${n} au total).`:''}</p>
    ${!done?`<p class="meta">La saison ${n} est en cours : comparez avec ${p} à la même date (barres grises), pas avec le total de la saison.</p>`:''}</div>`;
  P.innerHTML=`<div class="toolbar">${reaSegView()}<label class="levels"><span class="tl">Saison</span><select id="rfSeason">${ss.map((x,i)=>`<option value="${i}" ${i===se?'selected':''}>${sLab(x)} · ${x}${!seasonDone(x)?' (en cours)':''}</option>`).join('')}</select></label>${famSeg()}</div>
  <div class="kgrid k4">
    ${tile('Magasins en réassort',fN(nm),`facturés en réassort ${n}${sp>=0?`<br>${p}${sameD} : ${fN(pm)} ${dif(nm,pm)}`:''}`,sp>=0?(nm>=pm?'up':'down'):'')}
    ${tile('Pièces facturées',fN(Math.round(N0.qi)),`${fE(N0.i)}${sp>=0?`<br>${p}${sameD} : ${fN(Math.round(P0.qi))} ${dif(N0.qi,P0.qi)}`:''}`,sp>=0?(N0.qi>=P0.qi?'up':'down'):'')}
    ${tile('Pièces par magasin',fN(Math.round(perN)),`moyenne par magasin réassorti${sp>=0?`<br>${p}${sameD} : ${fN(Math.round(perP))} ${dif(perN,perP)}`:''}`)}
    ${tile('Rythme hebdo',fN(Math.round(wkRate))+' pcs',`pièces facturées par semaine, ${rLab}${sp>=0?`<br>${p} mêmes mois : ${fN(Math.round(wkRateP))} ${dif(wkRate,wkRateP)}`:''}`,sp>=0?(wkRate>=wkRateP?'up':'down'):'')}
  </div>
  <div class="card"><div class="card-b expl">${txt}</div></div>
  <div class="card"><div class="card-h"><h2>Réassort facturé par mois</h2><div class="seg" id="rfMet">${[['q','Pièces'],['a','€'],['c','Magasins']].map(([k,l])=>`<button type="button" data-m="${k}" aria-pressed="${met===k}">${l}</button>`).join('')}</div></div><div class="card-b">${chartM}</div></div>
  ${chartW}
  <div class="card"><div class="card-h"><h2>Réassort par ${({grp:'groupement',cg:'centrale',sales:'commercial',cust:'client'})[dimK]}</h2><div class="seg" id="rfDim">${[['grp','Groupement'],['cg','Centrale'],['sales','Commercial'],['cust','Client']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dimK===k}">${l}</button>`).join('')}</div></div>
    <div class="scroll"><table class="pv"><thead>${head}</thead><tbody>${list.map(trow).join('')}${hidden?`<tr class="more"><td colspan="12"><button type="button" class="linkbtn" id="rfAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}</tbody></table></div>
    <div class="card-b"><p class="meta" style="margin:0">${p} = facturé jusqu'à ${done?'la fin de la saison':ymLab(asofP)} (même date). Magasin = numéro client facturé. Réassort = commandes de type RE, hors frais (transport, services) et PLV. Pièces facturées estimées à partir du commandé et du reste à livrer de chaque ligne.</p></div></div>`;
  bindFam();bindRsView(P);
  $('#rfSeason').onchange=e=>{RF.season=+e.target.value;rsPanel(P)};
  $('#rfMet').onclick=e=>{const b=e.target.closest('button');if(b){RF.metric=b.dataset.m;rsPanel(P)}};
  $('#rfDim').onclick=e=>{const b=e.target.closest('button');if(b){RF.dim=b.dataset.d;RF.all=false;rsPanel(P)}};
  const a=$('#rfAll');if(a)a.onclick=()=>{RF.all=true;rsPanel(P)};
}

/* ================= sell-out ================= */
let SOD=null;const SO={ps:true,only:true,base:'cum',view:'syn',season:null,ref:null,src:-1,metric:'e',dim:'fam',cmp:'all',exp:new Set(),q:'',all:false,sort:'n',sdim:'item',ssort:'cq',svs:'all'};
const SO_FC=['#2a78d6','#a23f7a','#1f9d6b'],SO_FCODE=['APP','FTW','ACC'];
async function initSellout(){
  const el=document.getElementById('sodata');if(!el||!el.textContent.trim().startsWith('{'))return;
  try{const o=JSON.parse(el.textContent);const bin=Uint8Array.from(atob(o.gz),c=>c.charCodeAt(0));
    const txt=await new Response(new Blob([bin]).stream().pipeThrough(new DecompressionStream('gzip'))).text();
    const d=JSON.parse(txt);d.ci=d.cust.map(c=>D.dicts.cust.indexOf(c));SOD=d}catch(e){console.error('sellout',e)}
}
/* calendrier ISO : semaine N-1 = semaine dont le lundi tombe 52 semaines (364 j) plus tôt */
const SO_D=864e5,soMon=yw=>{const y=Math.floor(yw/100),w=yw%100,j4=Date.UTC(y,0,4),dow=(new Date(j4).getUTCDay()+6)%7;return j4-dow*SO_D+(w-1)*7*SO_D};
const soYW=ms=>{const d=new Date(ms),th=ms+(3-(d.getUTCDay()+6)%7)*SO_D,y=new Date(th).getUTCFullYear();return y*100+1+Math.floor((th-Date.UTC(y,0,1))/SO_D/7)};
const SO_PC=new Map(),SO_NC=new Map();
const soPrev=yw=>{let v=SO_PC.get(yw);if(v==null){v=soYW(soMon(yw)-364*SO_D);SO_PC.set(yw,v)}return v};
const soNext=yw=>{let v=SO_NC.get(yw);if(v==null){v=soYW(soMon(yw)+364*SO_D);SO_NC.set(yw,v)}return v};
const soDt=ms=>{const d=new Date(ms);return String(d.getUTCDate()).padStart(2,'0')+'/'+String(d.getUTCMonth()+1).padStart(2,'0')};
const soRange=yw=>{const m=soMon(yw);return soDt(m)+'–'+soDt(m+6*SO_D)+'/'+String(new Date(m+6*SO_D).getUTCFullYear()).slice(2)};

const soWk=yw=>'S'+(yw%100);
const soWkY=yw=>'S'+(yw%100)+' '+String(Math.floor(yw/100)).slice(2);
function soCustOk(){
  const n=SOD.cust.length,ok=new Uint8Array(n);
  const maps=[['sales',D.custSalesI],['cg',D.custCgI],['grp',D.custGrpI],['n1',D.custN1I],['bm1',D.custBm1I]].filter(([d,m])=>F[d]&&F[d].size&&m);
  for(let c=0;c<n;c++){const di=SOD.ci[c];if(di===CENT&&S.exclC)continue;if(maps.length){if(di<0)continue;let k=true;for(const[d,m]of maps)if(!F[d].has(m[di])){k=false;break}if(!k)continue}ok[c]=1}
  return ok;
}
function soFamOk(){const allowed=F.fam&&F.fam.size?new Set([...F.fam].map(i=>D.dicts.fam[i])):null;return SOD.fams.map(f=>!allowed||allowed.has(f)?1:0)}
function soLab(c){const di=SOD.ci[c];if(di>=0)return cLabelTxt(di);const i=SOD.cinfo[c];return`<b>${esc(SOD.cust[c])}</b> ${esc(i[3]||'')}${i[2]?' ('+esc(i[2])+')':''}`}
function soSales(c){const di=SOD.ci[c];return di>=0?D.dicts.sales[D.custSalesI[di]]:(SOD.cinfo[c][1]||'(non renseigné)')}
function soGrp(c){const di=SOD.ci[c];return di>=0?(D.custGrp[di]||'(sans groupement)'):(SOD.cinfo[c][2]||'(sans groupement)')}
/* dernier relevé de chaque magasin×famille à la semaine w (incluse) */
function soSnap(se,w){
  const S2=SOD.S,best=new Map();if(se<0)return best;
  for(let i=0;i<S2.cq.length;i++){if(S2.season[i]!==se||S2.yw[i]>w)continue;const k=S2.cust[i]*4+S2.fam[i];const j=best.get(k);if(j==null||S2.yw[j]<S2.yw[i])best.set(k,i)}
  return best;
}
/* périmètre pré-saison : magasin×famille ayant une commande PS > 0 sur la saison */
const SO_PS=new Map();
function soPSset(se){if(SO_PS.has(se))return SO_PS.get(se);const st=new Set(),sIdx=D.dicts.season.indexOf(SOD.seasons[se]),psI=D.dicts.sot.indexOf('PS'),fb=new Map(SOD.fams.map((f,i)=>[D.dicts.fam.indexOf(f),i]));
  if(sIdx>=0&&psI>=0)for(let i=0;i<N;i++){if(C.season[i]!==sIdx||C.sot[i]!==psI||C.amt[i]<=0)continue;const f=fb.get(C.fam[i]);if(f!=null)st.add(C.cust[i]*4+f)}
  SO_PS.set(se,st);return st}
function soPSok(se,c,f){if(!SO.ps)return true;const di=SOD.ci[c];return di>=0&&soPSset(se).has(di*4+f)}
/* périmètre comparable : magasin×famille ayant vendu sur N et N-1 à date */
let SO_CSK='',SO_CS=null;
function soCS(se){if(!SO.only||SOD.pair[se]<0)return null;const ref=SO.ref;const k=[se,ref,SO.ps,SO.base,SO.src,[...(F.fam||[])].join(','),JSON.stringify(['sales','cg','grp','n1','bm1'].map(d=>F[d]?[...F[d]]:[])),S.exclC].join('|');
  if(k!==SO_CSK){SO_CSK=k;SO_CS=new Set(soPairs(se,ref,true).filter(r=>r.comp).map(r=>r.c*4+r.f))}return SO_CS}
/* lignes magasin×famille N et N-1 à date */
function soPairs(se,ref,raw){
  const pse=SOD.pair[se],cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src;
  const N=soSnap(se,ref),P=soSnap(pse,soPrev(ref)),S2=SOD.S,rows=new Map();
  const add=(m,side)=>{for(const[k,i]of m){if(!cok[S2.cust[i]]||!fok[S2.fam[i]]||!srcOk(S2.src[i])||!soPSok(side?se:pse,S2.cust[i],S2.fam[i]))continue;
    const r=rows.get(k)||{c:S2.cust[i],f:S2.fam[i],src:S2.src[i],nq:0,ne:0,ns:0,pq:0,pe:0,ps:0};
    if(side){r.nq=S2.cq[i];r.ne=S2.ce[i];r.ns=S2.st[i]}else{r.pq=S2.cq[i];r.pe=S2.ce[i];r.ps=S2.st[i]}rows.set(k,r)}};
  add(N,1);add(P,0);
  if(SO.base==='wk'&&pse>=0){
    const W=SOD.W,wn=new Set();for(let i=0;i<W.q.length;i++)if(W.season[i]===se&&W.yw[i]<=ref)wn.add(W.yw[i]);
    for(const r of rows.values()){r.nq=r.ne=r.pq=r.pe=0}
    for(let i=0;i<W.q.length;i++){const s=W.season[i];if(s!==se&&s!==pse)continue;const w=s===se?W.yw[i]:soNext(W.yw[i]);if(!wn.has(w))continue;
      if(!cok[W.cust[i]]||!fok[W.fam[i]]||!srcOk(W.src[i])||!soPSok(s,W.cust[i],W.fam[i]))continue;const k=W.cust[i]*4+W.fam[i];
      const r=rows.get(k)||{c:W.cust[i],f:W.fam[i],src:W.src[i],nq:0,ne:0,ns:0,pq:0,pe:0,ps:0};
      if(s===se){r.nq+=W.q[i];r.ne+=W.e[i]}else{r.pq+=W.q[i];r.pe+=W.e[i]}rows.set(k,r)}
    SO._wn=[...wn].sort((a,b)=>a-b);
  }
  for(const r of rows.values())r.comp=r.nq>0&&r.pq>0;
  const out=[...rows.values()];return SO.only&&!raw&&pse>=0?out.filter(r=>r.comp):out;
}
function soAgg(list,keyFn){
  const m=new Map();
  for(const r of list){const k=keyFn(r);const a=m.get(k)||{nq:0,ne:0,ns:0,pq:0,pe:0,ps:0,dn:new Set(),dp:new Set()};
    a.nq+=r.nq;a.ne+=r.ne;a.ns+=r.ns;a.pq+=r.pq;a.pe+=r.pe;a.ps+=r.ps;if(r.nq>0)a.dn.add(r.c);if(r.pq>0)a.dp.add(r.c);m.set(k,a)}
  return m;
}
const soPct=(n,p)=>{if(!p)return n?'<span class="chip new">nouveau</span>':'<span class="dash">—</span>';const r=(n-p)/Math.abs(p)*100;return`<span class="evo ${r>=0?'pos':'neg'}">${r>=0?'+':''}${r.toLocaleString('fr-FR',{minimumFractionDigits:1,maximumFractionDigits:1})} %</span>`};
const soSell=(q,st)=>q+st>0?Math.round(q/(q+st)*100)+' %':'<span class="dash">—</span>';
const soDiff=v=>`<span class="${v>=0?'pos':'neg'}">${v>=0?'+':'−'}${fN(Math.abs(v))}</span>`;
function soCmpTable(title,cls,m,order,lab,nL,pL,opt){
  opt=opt||{};const T={nq:0,ne:0,ns:0,pq:0,pe:0,ps:0,dn:new Set(),dp:new Set()};
  for(const a of m.values()){for(const k of['nq','ne','ns','pq','pe','ps'])T[k]+=a[k];a.dn.forEach(x=>T.dn.add(x));a.dp.forEach(x=>T.dp.add(x))}
  const row=(l,a,tc)=>`<tr class="${tc||''}"><td class="lab">${l}</td><td class="cn">${fE(a.ne)}</td><td class="cp">${fE(a.pe)}</td><td>${soDiff(a.ne-a.pe)} €</td><td>${soPct(a.ne,a.pe)}</td><td class="cn">${fN(a.nq)}</td><td class="cp">${fN(a.pq)}</td><td>${soPct(a.nq,a.pq)}</td><td class="cn">${fN(a.dn.size)}</td><td class="cp">${fN(a.dp.size)}</td><td>${soDiff(a.dn.size-a.dp.size)}</td>${opt.stock?`<td class="cn so-st"><b>${fN(a.ns)}</b></td><td class="cp">${fN(a.ps)}</td><td>${soPct(a.ns,a.ps)}</td><td class="cn">${soSell(a.nq,a.ns)}</td><td class="cp">${soSell(a.pq,a.ps)}</td>`:''}</tr>`;
  const keys=order||[...m.keys()].sort((x,y)=>m.get(y).ne-m.get(x).ne);
  return`<div class="card so-t ${cls}"><div class="so-h">${title}</div><div class="scroll"><table class="pv"><thead><tr><th class="lab">${opt.head||'Famille'}</th><th class="cn">${nL} (€)</th><th class="cp">${pL} (€) à date</th><th>Écart (€)</th><th>Évo (€)</th><th class="cn">${nL} qté</th><th class="cp">${pL} qté à date</th><th>Évo qté</th><th class="cn">Portes ${nL}</th><th class="cp">Portes ${pL}</th><th>Écart portes</th>${opt.stock?`<th class="cn so-st">Stock ${nL} (pcs)</th><th class="cp">Stock ${pL} (pcs)</th><th>Évo stock</th><th class="cn">Écoul. ${nL}</th><th class="cp">Écoul. ${pL}</th>`:''}</tr></thead><tbody>
    ${keys.filter(k=>m.has(k)).map(k=>row(lab(k),m.get(k))).join('')||'<tr class="more"><td colspan="13">Aucune donnée.</td></tr>'}${row('Total',T,'tot')}</tbody></table></div></div>`;
}
function soFamLab(f){return`<span class="memo-c" style="background:${SO_FC[f]};margin-right:8px">${SO_FCODE[f]}</span><b>${SOD.fams[f]}</b>`}
/* ---------- panneau principal ---------- */
function selloutPanel(P){
  if(!SOD){P.innerHTML='<div class="card"><div class="card-b">Données sell-out non chargées.</div></div>';return}
  if(SO.season==null)SO.season=SOD.seasons.length-1;
  const se=SO.season,sName=SOD.seasons[se],pse=SOD.pair[se],last=SOD.last[sName];
  const wks=SOD.weeks[sName];if(SO.ref==null||!wks.includes(SO.ref))SO.ref=last;
  if(SO.baseFor!==se){SO.baseFor=se;SO.base='cum';if(pse>=0){const a=SOD.weeks[sName][0],b=SOD.weeks[SOD.seasons[pse]][0];const wn=new Set(SOD.W.yw.filter((w,i)=>SOD.W.season[i]===se));if(a%100!==b%100&&SOD.W.yw.some((w,i)=>SOD.W.season[i]===pse&&wn.has(soNext(w))))SO.base='wk'}}
  const ref=SO.ref,n=sLab(sName),p=pse>=0?sLab(SOD.seasons[pse]):'—';
  const views=[['syn','Synthèse N / N-1'],['det','Détail à date'],['wk','Semaine par semaine'],['evo','Évolution saisons'],['bs','Best-sellers & ruptures'],['stk','Stock & réassort'],['vis','Vue visuelle réf.']];
  const tb=`<div class="toolbar"><div class="seg" id="soPS" title="Ne garder que les magasins ayant commandé la famille en pré-saison">${[[1,'Avec pré-saison (PS > 0)'],[0,'Tous les vendeurs']].map(([k,l])=>`<button type="button" data-p="${k}" aria-pressed="${+SO.ps===k}">${l}</button>`).join('')}</div><div class="seg" id="soOnly" title="Périmètre magasins">${[[1,'Magasins comparables'],[0,'Tous les magasins']].map(([k,l])=>`<button type="button" data-o="${k}" aria-pressed="${+SO.only===k}">${l}</button>`).join('')}</div><div class="seg" id="soView">${views.map(([k,l])=>`<button type="button" data-v="${k}" aria-pressed="${SO.view===k}">${l}</button>`).join('')}</div></div>
  <div class="toolbar"><label class="levels"><span class="tl">Saison</span><select id="soSeason">${SOD.seasons.map((x,i)=>`<option value="${i}" ${i===se?'selected':''}>${sLab(x)}${SOD.pair[i]>=0?' vs '+sLab(SOD.seasons[SOD.pair[i]]):''}</option>`).join('')}</select></label>
    ${!['evo','bs'].includes(SO.view)?`<label class="levels"><span class="tl">À date de</span><select id="soRef">${wks.slice().reverse().map(w=>`<option value="${w}" ${w===ref?'selected':''}>${soWkY(w)} · ${soRange(w)}${w===last?' (dernière)':''}</option>`).join('')}</select></label>`:''}
    ${SO.view==='syn'||SO.view==='det'?`<div class="seg" id="soBase" title="Base de comparaison">${[['cum','Cumul saison à date'],['wk','Semaines communes']].map(([k,l])=>`<button type="button" data-b="${k}" aria-pressed="${SO.base===k}">${l}</button>`).join('')}</div>`:''}
    <div class="seg" id="soSrc">${[[-1,'Toutes enseignes'],[0,'Intersport'],[1,'Sport 2000']].map(([k,l])=>`<button type="button" data-s="${k}" aria-pressed="${SO.src===k}">${l}</button>`).join('')}</div>${famSeg()}</div>`;
  let body='';
  if(SO.view==='syn')body=soSyn(se,pse,ref,n,p);
  else if(SO.view==='det')body=soDet(se,pse,ref,n,p);
  else if(SO.view==='wk')body=soWeeks(se,pse,ref,n,p);
  else if(SO.view==='evo')body=soEvo();
  else if(SO.view==='bs')body=soBest(se,pse,n,p);
  else if(SO.view==='vis')body=soVis(sName);
  else body=soStock(se,n);
  P.innerHTML=tb+body+`<p class="meta" style="margin-top:10px">Source : reporting « Push Réassort (SellOut) » Intersport et Sport 2000 sur SharePoint, semaines 2026 et archives 2025 (lecture seule). Montants au prix de vente magasin. « À date » : ${n} jusqu'à la ${soWkY(ref)} comparé à ${p} jusqu'à la ${soWkY(soPrev(ref))} (${soRange(ref)} comparé à ${soRange(soPrev(ref))} : même semaine du calendrier ISO, 52 semaines plus tôt). ${SO.ps?'<b>Périmètre pré-saison</b> : seuls les magasins ayant une commande PS &gt; 0 sur la famille pour la saison sont comptés (PS '+n+' pour '+n+', PS '+p+' pour '+p+'), comme dans les vues sell-in. ':''}Magasin comparable = a vendu la famille sur les deux saisons à date.${SO.only&&pse>=0?' <b>Périmètre actuel : magasins comparables uniquement</b> (s\'applique aux synthèses, au détail, aux semaines et au stock ; la vue « Évolution saisons » reste sur tous les magasins).':''}</p>`;
  bindFam();
  $('#soView').onclick=e=>{const b=e.target.closest('button');if(b){SO.view=b.dataset.v;SO.all=false;SO.exp.clear();selloutPanel(P)}};
  $('#soSeason').onchange=e=>{SO.season=+e.target.value;SO.ref=null;SO.exp.clear();SO.all=false;selloutPanel(P)};
  const r=$('#soRef');if(r)r.onchange=e=>{SO.ref=+e.target.value;selloutPanel(P)};
  $('#soSrc').onclick=e=>{const b=e.target.closest('button');if(b){SO.src=+b.dataset.s;selloutPanel(P)}};
  soBind(P);
  if(SO.view==='vis'){const box=P.querySelector('.vv-inline');if(box)box.onclick=e=>{const m=e.target.closest('[data-vvm]');if(m){VV.mode=m.dataset.vvm;selloutPanel(P);return}const t=e.target.closest('[data-vvs]');if(t){VV.sort=t.dataset.vvs;selloutPanel(P)}}}
}
function soBind(P){
  const on=(id,fn)=>{const el=$(id);if(el)el.onclick=e=>{const b=e.target.closest('button');if(b){fn(b);selloutPanel(P)}}};
  on('#soDim',b=>{SO.dim=b.dataset.d;SO.all=false;SO.exp.clear()});
  on('#soCmp',b=>{SO.cmp=b.dataset.c;SO.all=false});
  on('#soBase',b=>{SO.base=b.dataset.b});
  on('#soFol',b=>{SO.fol=b.dataset.f;SO.folAll=false});
  const fa=$('#soFolAll');if(fa)fa.onclick=()=>{SO.folAll=true;selloutPanel(P)};
  on('#soPS',b=>{SO.ps=b.dataset.p==='1';SO.all=false});
  on('#soBsk',b=>{SO.bsk=b.dataset.k});
  on('#soBsn',b=>{SO.bsn=+b.dataset.n});
  on('#soOnly',b=>{SO.only=b.dataset.o==='1';SO.cmp='all';SO.all=false});
  on('#soSort',b=>{SO.sort=b.dataset.o});
  on('#soSvs',b=>{SO.svs=b.dataset.v});
  on('#soMet',b=>{SO.metric=b.dataset.m});
  on('#soSDim',b=>{SO.sdim=b.dataset.d;SO.all=false;SO.exp.clear()});
  on('#soSSort',b=>{SO.ssort=b.dataset.o});
  const q=$('#soQ');if(q){let qt;q.oninput=e=>{clearTimeout(qt);qt=setTimeout(()=>{SO.q=e.target.value;SO.all=false;selloutPanel(P);const el=$('#soQ');el.focus();el.setSelectionRange(el.value.length,el.value.length)},250)}}
  P.querySelectorAll('.tg[data-x]').forEach(b=>b.onclick=()=>{const k=+b.dataset.x;SO.exp.has(k)?SO.exp.delete(k):SO.exp.add(k);selloutPanel(P)});
  const a=$('#soAll');if(a)a.onclick=()=>{SO.all=true;selloutPanel(P)};
}

/* ---------- vue visuelle globale des références (périmètre filtré) ---------- */
function soVis(sName){
  const se=D.dicts.season.indexOf(sName);if(se<0)return`<div class="card"><div class="card-b">Saison ${sLab(sName)} absente du carnet de commandes.</div></div>`;
  const masks=FDIMS.filter(d=>F[d]&&F[d].size&&['sot','perim','fam','line','age','genus'].indexOf(d)<0).map(d=>{const m=new Uint8Array(D.dicts[d].length);F[d].forEach(i=>m[i]=1);return[C[d],m]});
  const cm=new Uint8Array(D.dicts.cust.length),ex=S.exclC?CENT:-2;
  outer:for(let i=0;i<N;i++){if(C.season[i]!==se)continue;const c=C.cust[i];if(c===ex)continue;for(const[col,m]of masks)if(!m[col[i]])continue outer;cm[c]=1}
  if(SO.src>=0&&SOD){const keep=new Uint8Array(cm.length);for(let i=0;i<SOD.I.q.length;i++){if(SOD.I.src[i]!==SO.src)continue;const d=SOD.ci[SOD.I.cust[i]];if(d>=0)keep[d]=1}for(let c=0;c<cm.length;c++)if(!keep[c])cm[c]=0}
  const saveSe=FCH.se,saveCm=VV.cm;FCH.se=se;VV.cm=cm;let h;try{h=vvHtml(true,'so')}finally{FCH.se=saveSe;VV.cm=saveCm}
  return`<div class="card"><div class="card-b vv-inline">${h}</div></div>`;
}
/* ---------- 1. synthèse ---------- */
function soSyn(se,pse,ref,n,p){
  const L=soPairs(se,ref),ord=[1,0,2];
  const all=soAgg(L,r=>r.f),comp=soAgg(L.filter(r=>r.comp),r=>r.f),nc=soAgg(L.filter(r=>!r.comp),r=>r.f);
  const tot=a=>{const T={ne:0,pe:0,nq:0,pq:0,ns:0,ps:0,dn:new Set(),dp:new Set()};for(const x of a.values()){T.ne+=x.ne;T.pe+=x.pe;T.nq+=x.nq;T.pq+=x.pq;T.ns+=x.ns;T.ps+=x.ps;x.dn.forEach(v=>T.dn.add(v));x.dp.forEach(v=>T.dp.add(v))}return T};
  const A=tot(all),C=tot(comp);
  const ev=(a,b)=>b?Math.round((a-b)/Math.abs(b)*1000)/10:null,cl=v=>v==null?'':v>=0?'up':'down',fv=v=>v==null?'—':(v>=0?'+':'')+v.toLocaleString('fr-FR')+' %';
  const noP=pse<0;
  return`<div class="kgrid k5">
    ${faTile(`Sell-out ${n} à date`,fM(A.ne),noP?`${fN(A.nq)} pièces`:`${p} : ${fM(A.pe)} · <b>${fv(ev(A.ne,A.pe))}</b>`,noP?'':cl(ev(A.ne,A.pe)))}
    ${faTile('Pièces vendues',fN(A.nq),noP?'':`${p} : ${fN(A.pq)} · <b>${fv(ev(A.nq,A.pq))}</b>`,noP?'':cl(ev(A.nq,A.pq)))}
    ${faTile('Portes vendeuses',fN(A.dn.size),noP?'':`${p} : ${fN(A.dp.size)} · écart ${soDiff(A.dn.size-A.dp.size)}`,noP?'':(A.dn.size>=A.dp.size?'up':'down'))}
    ${faTile('Stock magasins (pcs)',fN(A.ns),noP?`écoulement ${soSell(A.nq,A.ns)} · fin ${soWkY(ref)}`:`${p} : ${fN(A.ps)} pcs · <b>${fv(ev(A.ns,A.ps))}</b><br>écoulement ${soSell(A.nq,A.ns)} (${p} : ${soSell(A.pq,A.ps)})`)}
    ${faTile('À périmètre comparable',noP?'—':fv(ev(C.ne,C.pe)),noP?'pas de saison N-1 dans les archives':`${fM(C.ne)} vs ${fM(C.pe)} · ${fN(C.dn.size)} portes présentes les 2 saisons`,noP?'':cl(ev(C.ne,C.pe)))}
  </div>
  ${soBaseNote(se,pse,ref,n,p)}
  ${noP?'<div class="card"><div class="card-b">Pas de saison comparable dans les archives 2025 pour cette saison : seules les valeurs N sont affichées.</div></div>':''}
  ${soWeekChart(se,pse,ref,n,p,true)}
  ${soStockVsSales(all,ord,A,n,p,noP,ref,se,pse,L)}
  ${SO.only&&pse>=0?'':soCmpTable(`Sell-out ${n} vs ${p} · total à date ${soWk(ref)}`,'t-all',all,ord,soFamLab,n,p,{stock:true})}
  ${soCmpTable('Valo comparable · magasins présents les 2 saisons','t-comp',comp,ord,soFamLab,n,p,{stock:true})}
  ${SO.only?'':soCmpTable('Valo non comparable · magasins nouveaux ou perdus','t-nc',nc,ord,soFamLab,n,p,{stock:true})}
  ${soFollow(se,pse,ref,n,p)}`;
}
function soBaseNote(se,pse,ref,n,p){
  if(pse<0)return'';const f=s=>{const w=SOD.weeks[SOD.seasons[s]];return w[0]};
  if(SO.base==='wk'){const wn=SO._wn||[];return`<div class="card"><div class="card-b meta" style="margin:0">Base « semaines communes » : ventes des semaines où ${n} a un relevé (${wn.length?wn.map(soWk).join(', '):'aucune'}) comparées aux mêmes semaines un an plus tôt.${wn.length&&!wn.some(w=>SOD.W.yw.includes(soPrev(w)))?' Aucune semaine équivalente dans les archives '+p+' : comparaison impossible sur cette base.':''} Stocks : fin ${soWkY(ref)}.</div></div>`}
  const a=f(se),b=f(pse);if(a%100===b%100)return'';
  return`<div class="card"><div class="card-b meta" style="margin:0">⚠️ Les relevés ${p} commencent en ${soWkY(b)} et ceux de ${n} en ${soWkY(a)} : le cumul à date n'a pas le même nombre de semaines. Pour comparer à rythme égal, choisissez « Semaines communes ».</div></div>`;
}
function soStockVsSales(all,ord,A,n,p,noP,ref,se,pse,L){
  /* détail par référence (relevé de référence de chaque saison) : stock dormant, réf. vendues, nouveautés */
  const I=SOD.I,keep=new Set(L.map(r=>r.c*4+r.f)),sN=SOD.seasons[se],sP=pse>=0?SOD.seasons[pse]:null;
  const pref=s=>s?s.slice(2,4)+(s.endsWith('SS')?'1':'2'):'#';const pN=pref(sN),pP=pref(sP);
  /* parts calculées sur le détail par référence, appliquées aux totaux de la carte (même base de semaines) */
  const mk=()=>({q:[0,0],st:[0,0],d0:[0,0],nq:[0,0],nst:[0,0],nd0:[0,0]});const IA=new Map(),T=mk();
  for(let i=0;i<I.q.length;i++){const s=I.season[i];if(s!==se&&s!==pse)continue;const it=I.item[i],f=SOD.ifam[it],k=I.cust[i]*4+f;if(!keep.has(k))continue;
    const j=s===se?0:1,q=I.q[i],st=I.st[i],nw=(SOD.item[it]||'').startsWith(j?pP:pN);
    let a=IA.get(f);if(!a){a=mk();IA.set(f,a)}
    for(const o of[a,T]){o.q[j]+=q;o.st[j]+=st;if(q<=0)o.d0[j]+=st;if(nw){o.nq[j]+=q;o.nst[j]+=st;if(q<=0)o.nd0[j]+=st}}}
  const sh=(x,y)=>y>0?x/y:0;
  const conv=(base,ia)=>{if(!ia)ia=mk();const o={nq:base.nq,ns:base.ns,pq:base.pq,ps:base.ps,dz:[0,0]};
    [['nq','ns'],['pq','ps']].forEach(([kq,ks],j)=>{const Q=base[kq],St=base[ks];
      if(SO.svs==='sold'){o[ks]=St*(1-sh(ia.d0[j],ia.st[j]))}
      else if(SO.svs==='new'){o[kq]=Q*sh(ia.nq[j],ia.q[j]);o[ks]=St*sh(ia.nst[j],ia.st[j]);o.dz[j]=St*sh(ia.nd0[j],ia.st[j])}
      else o.dz[j]=St*sh(ia.d0[j],ia.st[j]);
      o[kq]=Math.round(o[kq]);o[ks]=Math.round(o[ks]);o.dz[j]=Math.round(o.dz[j])});return o};
  const useI=SO.svs!=='all';
  const rows=[...ord.filter(f=>all.has(f)).map(f=>[soFamLab(f),conv(all.get(f),IA.get(f)),SO_FC[f]]),['<b>Total</b>',conv(A,T),'#1b2333']];
  const iN=SOD.iref[sN],iP=sP?SOD.iref[sP]:null;
  const segSvs=`<div class="seg" id="soSvs" style="margin:0 0 10px">${[['all','Toutes les réf. suivies'],['sold','Réf. vendues seulement'],['new',`Nouveautés ${n} seulement`]].map(([k,l])=>`<button type="button" data-v="${k}" aria-pressed="${SO.svs===k}">${l}</button>`).join('')}</div>`;
  const dorm=(x,isN)=>{const d=x.dz[isN?0:1];return d>0?`<span class="svs-dz" data-tip="${esc('Pièces en stock sur des couples magasin × référence sans aucune vente sur la saison')}">dont <b>${fN(d)}</b> sur réf. sans vente</span>`:''};
  let mx=1;
  const ratio=(q,st)=>q>0?(st/q).toLocaleString('fr-FR',{maximumFractionDigits:1}):'—';
  const bar=(q,st,col,pale,lab,dz)=>`<div class="svs-l"><span class="svs-k">${lab}</span><div class="svs-b" style="opacity:${pale?.55:1}"><i style="width:${q/mx*100}%;background:${col}" data-tip="${esc(`${lab} · vendu : ${fN(q)} pcs`)}"></i><i class="svs-st" style="width:${st/mx*100}%" data-tip="${esc(`${lab} · stock résiduel : ${fN(st)} pcs`)}"></i></div><span class="svs-t"><b>${fN(q)}</b> vendus · <b>${fN(st)}</b> en stock · écoul. <b>${soSell(q,st)}</b> · stock = <b>${ratio(q,st)}×</b> les ventes${dz||''}</span></div>`;
  return`<div class="card"><div class="card-h"><h2>Ventes vs stock résiduel · fin ${soWkY(ref)}</h2><span class="legend"><span><span class="sw" style="background:#2a78d6"></span>Vendu (couleur famille)</span><span><span class="sw svs-st" style="display:inline-block"></span>Stock restant en magasin</span></span></div><div class="card-b svs">${segSvs}
    ${rows.map(([lab,a,col])=>{mx=Math.max(1,a.nq+a.ns,noP?0:a.pq+a.ps);return`<div class="svs-r"><div class="svs-n">${lab}</div><div>${bar(a.nq,a.ns,col,false,n,SO.svs==='sold'?'':dorm(a,true))}${noP?'':bar(a.pq,a.ps,col,true,p,SO.svs==='sold'?'':dorm(a,false))}</div></div>`}).join('')}
    <p class="meta" style="margin:6px 0 0">En pièces. Chaque famille a sa propre échelle (N et N-1 comparables entre eux). Écoulement = vendu ÷ (vendu + stock restant). « Stock = 2× les ventes » : il reste en magasin deux fois ce qui a déjà été vendu.${SO.base==='wk'?' Ventes sur les semaines communes.':' Ventes en cumul saison à date.'} Magasins : ${SO.only?'comparables (présents et vendeurs les 2 saisons sur la famille), les mêmes pour '+n+' et '+p:'tous les magasins vendeurs'}${SO.ps?', avec pré-saison':''}.
    Le stock porte sur toutes les références suivies dans les rapports sell-out ${n} (nouveautés et permanents/continuité, codes plus anciens inclus), vendues ou non ; pas sur le stock total du magasin.
    ${SO.svs==='sold'?'« Réf. vendues seulement » : seuls les couples magasin × référence avec au moins une vente sur la saison.':SO.svs==='new'?`« Nouveautés » : uniquement les références de la collection ${n} (codes ${pN}…${noP?'':`, et ${pP}… pour ${p}`}), sans les permanents.`:''}
    ${useI?` Répartition calculée sur le détail par référence (relevé ${soWkY(iN)}${iP&&!noP?`, ${p} : ${soWkY(iP)}`:''}) et appliquée aux totaux ci-dessus.`:''} « Sur réf. sans vente » : stock sur des couples magasin × référence sans aucune vente sur la saison.</p></div></div>`;
}
/* magasins à suivre : pré-saison (PS) N > 0 sur la famille, mais sell-out absent ou en fort recul */
function soFollow(se,pse,ref,n,p){
  const sName=SOD.seasons[se],sIdx=D.dicts.season.indexOf(sName),psI=D.dicts.sot.indexOf('PS');if(sIdx<0||psI<0)return'';
  const fok=soFamOk(),fmap=SOD.fams.map(f=>D.dicts.fam.indexOf(f)),fback=new Map(fmap.map((v,i)=>[v,i]));
  const maps=[['sales',D.custSalesI],['cg',D.custCgI],['grp',D.custGrpI],['n1',D.custN1I],['bm1',D.custBm1I]].filter(([d,m])=>F[d]&&F[d].size&&m);
  const dOk=di=>!(di===CENT&&S.exclC)&&maps.every(([d,m])=>F[d].has(m[di]));
  const ps=new Map();
  for(let i=0;i<N;i++){if(C.season[i]!==sIdx||C.sot[i]!==psI)continue;const f=fback.get(C.fam[i]);if(f==null||!fok[f])continue;const di=C.cust[i];if(!dOk(di))continue;const k=di*4+f;const o=ps.get(k)||{e:0,q:0};o.e+=C.amt[i];o.q+=C.qty[i];ps.set(k,o)}
  const rows=new Map();for(const r of soPairs(se,ref,true)){const di=SOD.ci[r.c];if(di>=0)rows.set(di*4+r.f,r)}
  const soKnown=new Set(SOD.ci.filter(x=>x>=0));
  const L=[];let nZero=0,nLost=0,nDrop=0,nAbs=0,eAt=0;
  for(const[k,o]of ps){if(o.e<=0)continue;const di=Math.floor(k/4),f=k%4,r=rows.get(k);
    if(!r){if(SO.src>=0||!soKnown.has(di))continue;L.push({di,f,ps:o,r:null,st:'abs'});nAbs++;eAt+=o.e;continue}
    let st=null;if(r.nq<=0){st=r.pq>0?'lost':'zero';r.pq>0?nLost++:nZero++}else if(pse>=0&&r.pq>0&&r.ne<r.pe*0.5){st='drop';nDrop++}
    if(st){L.push({di,f,ps:o,r,st});eAt+=o.e}}
  if(!L.length)return`<div class="card"><div class="card-h"><h2>Magasins à suivre</h2></div><div class="card-b">Aucun magasin avec une pré-saison ${n} sans sell-out ou en fort recul sur ce périmètre.</div></div>`;
  const ord={abs:0,lost:1,zero:2,drop:3};L.sort((a,b)=>b.ps.e-a.ps.e);
  const flt=SO.fol||'all',LL=flt==='all'?L:L.filter(x=>x.st===flt);
  const lim=SO.folAll?LL.length:Math.min(40,LL.length);
  const tag={abs:'<span class="chip down">absent du reporting</span>',lost:'<span class="chip down">plus de vente</span>',zero:'<span class="chip down">aucune vente</span>',drop:'<span class="chip warn" style="background:#fff3dd;color:#b26a00">recul &gt; 50 %</span>'};
  const tr=x=>{const r=x.r;return`<tr><td class="lab">${cLabelTxt(x.di)}</td><td class="lab mutedv" style="min-width:120px">${esc(D.dicts.sales[D.custSalesI[x.di]])}</td><td class="lab">${soFamLab(x.f)}</td><td><b>${fE(x.ps.e)}</b><br><span class="mutedv">${fN(x.ps.q)} pcs</span></td><td class="cn">${r?fN(r.nq)+' pcs':'—'}</td><td class="cp">${r&&pse>=0?fN(r.pq)+' pcs':'—'}</td><td class="cn">${r?fN(r.ns):'—'}</td><td>${r?soSell(r.nq,r.ns):'—'}</td><td>${tag[x.st]}</td></tr>`};
  const fb=[['all','Tous',L.length],['abs','Absents du reporting',nAbs],['lost','Plus de vente',nLost],['zero','Aucune vente',nZero],['drop','Recul > 50 %',nDrop]].filter(([k,,c])=>k==='all'||c);
  return`<div class="card so-t t-nc" id="soFollowCard"><div class="so-h">Magasins à suivre · pré-saison ${n} commandée sur la famille, sell-out absent ou en fort recul</div>
  <div class="card-b" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><div class="seg" id="soFol">${fb.map(([k,l,c])=>`<button type="button" data-f="${k}" aria-pressed="${flt===k}">${l} (${fN(c)})</button>`).join('')}</div><span class="meta">${fN(L.length)} couples magasin × famille · ${fM(eAt)} de pré-saison concernés</span></div>
  <div class="scroll"><table class="pv"><thead><tr><th class="lab">Magasin</th><th class="lab">Commercial</th><th class="lab">Famille</th><th>PS ${n} commandée</th><th class="cn">Sell-out ${n}</th><th class="cp">Sell-out ${p}</th><th class="cn">Stock (pcs)</th><th>Écoul.</th><th>Statut</th></tr></thead><tbody>${LL.slice(0,lim).map(tr).join('')}${LL.length>lim?`<tr class="more"><td colspan="9"><button type="button" class="linkbtn" id="soFolAll">Afficher les ${fN(LL.length-lim)} autres</button></td></tr>`:''}</tbody></table></div>
  <div class="card-b"><p class="meta" style="margin:0">Seuls les magasins ayant une commande de pré-saison (type PS) ${n} &gt; 0 sur la famille sont retenus. Seuls les magasins suivis par le reporting Intersport / Sport 2000 (présents en 2025 ou 2026) sont pris en compte : export, distributeurs et web sont exclus. « Absent du reporting » : PS commandée mais le magasin n'apparaît pas dans le reporting sell-out ${n}. « Plus de vente » : vendait en ${p}, rien en ${n} à date. « Recul &gt; 50 % » : sell-out ${n} inférieur à la moitié de ${p}. Trié par montant de pré-saison.</p></div></div>`;
}
/* ---------- 2. détail à date ---------- */
function soDet(se,pse,ref,n,p){
  const dk=SO.dim,q=SO.q.trim().toLowerCase();
  const dims=[['fam','Famille'],['src','Enseigne'],['sales','Commercial'],['grp','Groupement'],['cust','Magasin'],['item','Référence'],['line','Ligne produit']];
  const cmpSeg=`<div class="seg" id="soCmp">${[['all','Tous les magasins'],['comp','Comparables'],['nc','Non comparables']].map(([k,l])=>`<button type="button" data-c="${k}" aria-pressed="${SO.cmp===k}">${l}</button>`).join('')}</div>`;
  let html='';
  if(dk==='item'||dk==='line'){html=soItems(se,pse,n,p,dk,q)}
  else{
    let L=soPairs(se,ref);if(SO.cmp==='comp')L=L.filter(r=>r.comp);else if(SO.cmp==='nc')L=L.filter(r=>!r.comp);
    const kf={fam:r=>r.f,src:r=>r.src,sales:r=>soSales(r.c),grp:r=>soGrp(r.c),cust:r=>r.c}[dk];
    const m=soAgg(L,kf);
    let keys=[...m.keys()];
    if(q)keys=keys.filter(k=>(dk==='cust'?SOD.cust[k]+' '+SOD.cinfo[k].join(' ')+' '+(SOD.ci[k]>=0?(D.custCity[SOD.ci[k]]||'')+' '+(D.custGrp[SOD.ci[k]]||''):''):dk==='fam'?SOD.fams[k]:dk==='src'?SOD.srcs[k]:String(k)).toLowerCase().includes(q));
    const sk={n:k=>m.get(k).ne,d:k=>m.get(k).ne-m.get(k).pe,dn:k=>m.get(k).ne-m.get(k).pe,st:k=>m.get(k).ns}[SO.sort]||(k=>m.get(k).ne);
    keys.sort((a,b)=>SO.sort==='dn'?sk(a)-sk(b):sk(b)-sk(a));
    let hidden=0;if(keys.length>60&&!SO.all){hidden=keys.length-60;keys=keys.slice(0,60)}
    const lab={fam:soFamLab,src:k=>`<b>${esc(SOD.srcs[k]==='INTERSPORT'?'Intersport':'Sport 2000')}</b>`,sales:k=>`<b>${esc(k)}</b>`,grp:k=>`<b>${esc(k)}</b>`,cust:k=>soLab(k)}[dk];
    const mm=new Map(keys.map(k=>[k,m.get(k)]));
    html=soCmpTable(`${n} vs ${p} à date ${soWk(ref)} · par ${dims.find(d=>d[0]===dk)[1].toLowerCase()}`,SO.cmp==='comp'?'t-comp':SO.cmp==='nc'?'t-nc':'t-all',mm,keys,lab,n,p,{stock:true,head:dims.find(d=>d[0]===dk)[1]});
    if(hidden)html+=`<p style="margin:-6px 0 12px"><button type="button" class="linkbtn" id="soAll">Afficher les ${fN(hidden)} autres lignes</button></p>`;
  }
  return`<div class="card"><div class="card-b" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><div class="seg" id="soDim">${dims.map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dk===k}">${l}</button>`).join('')}</div>
    ${dk==='item'||dk==='line'||SO.only?'':cmpSeg}
    <input type="search" id="soQ" placeholder="Rechercher…" value="${esc(SO.q)}" style="min-width:220px;padding:7px 10px;border:1px solid var(--line-2);border-radius:8px">
    <div class="seg" id="soSort">${[['n','Trier par ventes'],['d','plus forte hausse'],['dn','plus forte baisse'],['st','par stock']].map(([k,l])=>`<button type="button" data-o="${k}" aria-pressed="${SO.sort===k}">${l}</button>`).join('')}</div></div></div>${html}`;
}
function soItems(se,pse,n,p,dk,q){
  const I=SOD.I,cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src,sN=SOD.seasons[se],sP=pse>=0?SOD.seasons[pse]:null;
  const m=new Map(),byIC=new Map();
  const cs=soCS(se);for(let i=0;i<I.q.length;i++){const s=I.season[i];if((s!==se&&s!==pse)||!srcOk(I.src[i])||!cok[I.cust[i]])continue;const it=I.item[i];if(cs&&!cs.has(I.cust[i]*4+SOD.ifam[it]))continue;if(!soPSok(s,I.cust[i],SOD.ifam[it]))continue;if(!fok[SOD.ifam[it]])continue;
    const k=dk==='line'?SOD.ifam[it]+'|'+(SOD.iline[it]||'(sans ligne)'):it;const a=m.get(k)||{nq:0,ne:0,ns:0,pq:0,pe:0,ps:0,dn:new Set(),dp:new Set(),f:SOD.ifam[it],ral:0,rq:0};
    if(s===se){a.nq+=I.q[i];a.ne+=I.e[i];a.ns+=I.st[i];a.ral+=I.ral[i];a.rq+=I.rq[i];if(I.q[i]>0)a.dn.add(I.cust[i]);if(dk==='item'&&SO.exp.has(it)){const l=byIC.get(it)||[];l.push(i);byIC.set(it,l)}}
    else{a.pq+=I.q[i];a.pe+=I.e[i];a.ps+=I.st[i];if(I.q[i]>0)a.dp.add(I.cust[i])}
    m.set(k,a)}
  let keys=[...m.keys()].filter(k=>{const a=m.get(k);return a.nq||a.ns||a.pq});
  if(q)keys=keys.filter(k=>(dk==='line'?String(k):SOD.item[k]+' '+SOD.idesc[k]+' '+SOD.iline[k]).toLowerCase().includes(q));
  const sk={n:k=>m.get(k).ne,d:k=>m.get(k).ne-m.get(k).pe,dn:k=>m.get(k).ne-m.get(k).pe,st:k=>m.get(k).ns}[SO.sort]||(k=>m.get(k).ne);
  keys.sort((a,b)=>SO.sort==='dn'?sk(a)-sk(b):sk(b)-sk(a));
  const tot=keys.length;let hidden=0;if(keys.length>60&&!SO.all){hidden=keys.length-60;keys=keys.slice(0,60)}
  const nW=SOD.irq[sN]||4;
  const covI=a=>a.rq>0?a.ns/(a.rq/nW):null,fCov=v=>v==null?'<span class="dash">—</span>':v>52?'> 52 sem.':fN(Math.round(v))+' sem.',covCls=v=>v==null?'':v<3?'neg':v>16?'warnc':'';
  const rows=keys.map(k=>{const a=m.get(k);const open=dk==='item'&&SO.exp.has(k);const c=covI(a);
    const lab=dk==='item'?`<button type="button" class="tg" data-x="${k}" aria-expanded="${open}"><i>${open?'−':'+'}</i></button> ${pImg(SOD.item[k],36)} <span class="memo-c" style="background:${SO_FC[a.f]};font-size:10px">${SO_FCODE[a.f]}</span> <b>${esc(SOD.item[k])}</b> <span class="mutedv">${esc(SOD.idesc[k])}</span>`:`<span class="memo-c" style="background:${SO_FC[a.f]};font-size:10px">${SO_FCODE[a.f]}</span> <b>${esc(String(k).split('|')[1])}</b>`;
    let h=`<tr><td class="lab">${lab}</td><td class="cn">${fE(a.ne)}</td>${sP?`<td class="cp">${fE(a.pe)}</td><td>${soPct(a.ne,a.pe)}</td>`:''}<td class="cn">${fN(a.nq)}</td>${sP?`<td class="cp">${fN(a.pq)}</td>`:''}<td class="cn">${fN(a.dn.size)}</td>${sP?`<td class="cp">${fN(a.dp.size)}</td>`:''}<td class="cn"><b>${fN(a.ns)}</b></td><td>${a.nq+a.ns>0?Math.round(a.nq/(a.nq+a.ns)*100)+' %':'—'}</td><td class="${covCls(c)}">${fCov(c)}</td><td>${fN(a.ral)}</td></tr>`;
    if(open){const l=(byIC.get(k)||[]).sort((x,y)=>I.st[y]-I.st[x]||I.q[y]-I.q[x]);h+=l.slice(0,60).map(i=>{const cc=I.rq[i]>0?I.st[i]/(I.rq[i]/nW):null;return`<tr class="sub"><td class="lab" style="padding-left:44px">${soLab(I.cust[i])}</td><td>${fE(I.e[i])}</td>${sP?'<td></td><td></td>':''}<td>${fN(I.q[i])}</td>${sP?'<td></td>':''}<td></td>${sP?'<td></td>':''}<td><b>${fN(I.st[i])}</b></td><td>${I.q[i]+I.st[i]>0?Math.round(I.q[i]/(I.q[i]+I.st[i])*100)+' %':'—'}</td><td class="${covCls(cc)}">${fCov(cc)}</td><td>${fN(I.ral[i])}</td></tr>`}).join('')+(l.length>60?`<tr class="more"><td colspan="12" style="padding-left:44px">… ${fN(l.length-60)} autres magasins</td></tr>`:'')}
    return h});
  if(hidden)rows.push(`<tr class="more"><td colspan="12"><button type="button" class="linkbtn" id="soAll">Afficher les ${fN(hidden)} autres</button></td></tr>`);
  const iN=SOD.iref[sN],iP=sP?SOD.iref[sP]:null;
  return`<div class="card so-t t-all"><div class="so-h">Sell-out ${dk==='item'?'par référence':'par ligne produit'} (ventes en magasin) · ${n} cumul ${soWkY(iN)}${sP?` vs ${p} cumul ${soWkY(iP)}`:''} · ${fN(tot)} lignes</div>
  <div class="scroll"><table class="pv"><thead><tr><th class="lab">${dk==='item'?'Référence':'Ligne produit'}</th><th class="cn" title="Sell-out cumulé de la saison, valorisé au prix de vente magasin">Sell-out ${n}<br><span class="th-u">€ prix de vente</span></th>${sP?`<th class="cp" title="Sell-out cumulé de la saison N-1 au prix de vente magasin">Sell-out ${p}<br><span class="th-u">€ prix de vente</span></th><th>Évo (€)</th>`:''}<th class="cn" title="Pièces vendues en magasin, cumul saison">Pièces vendues<br><span class="th-u">${n}</span></th>${sP?`<th class="cp">Pièces vendues<br><span class="th-u">${p}</span></th>`:''}<th class="cn" title="Nombre de magasins ayant vendu au moins une pièce">Magasins vendeurs<br><span class="th-u">${n}</span></th>${sP?`<th class="cp">Magasins vendeurs<br><span class="th-u">${p}</span></th>`:''}<th class="cn" title="Stock en magasin (pièces) à la date du relevé">Stock magasins<br><span class="th-u">pièces</span></th><th title="Pièces vendues ÷ (pièces vendues + stock magasin)">Écoulement<br><span class="th-u">vendu ÷ (vendu + stock)</span></th><th title="Stock magasin ÷ ventes moyennes hebdomadaires des ${nW} dernières semaines">Couverture<br><span class="th-u">semaines de stock</span></th><th title="Reste à livrer aux magasins (commandes en cours non livrées), en pièces">RAL magasins<br><span class="th-u">pièces à livrer</span></th></tr></thead><tbody>${rows.join('')||'<tr class="more"><td colspan="12">Aucune donnée.</td></tr>'}</tbody></table></div>
  <div class="card-b"><p class="meta" style="margin:0 0 6px"><b>Lecture :</b> chiffres de <b>sell-out</b> (ventes des magasins aux consommateurs) issus du reporting Push Réassort Intersport / Sport 2000, et non des commandes Le Coq Sportif. Montants au prix de vente magasin TTC du reporting · stock et RAL en pièces, à la date du relevé · couverture = stock ÷ ventes moyennes des ${nW} dernières semaines (rouge &lt; 3 sem., orange &gt; 16 sem.).</p><p class="meta" style="margin:0">Le détail par référence est disponible à la dernière semaine de chaque saison (${soWkY(iN)}${sP?' et '+soWkY(iP)+' pour '+p:''}) ; il ne suit pas le choix « À date de ». Les références changent d'une saison à l'autre : seules les références reconduites ont une valeur ${p}. Lignes produit : nomenclature 2026 (Line Product) et 2025 (catégorie) différentes. Cliquez sur + pour le stock magasin par magasin.</p></div></div>`;
}
/* ---------- 3. semaines ---------- */
function soWeekData(se,pse){
  const cs=soCS(se),W=SOD.W,cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src,isE=SO.metric==='e';
  const N=new Map(),NF=new Map(),Pm=new Map();
  for(let i=0;i<W.q.length;i++){const s=W.season[i];if((s!==se&&s!==pse)||!srcOk(W.src[i])||!cok[W.cust[i]]||!fok[W.fam[i]])continue;if(cs&&!cs.has(W.cust[i]*4+W.fam[i]))continue;if(!soPSok(s,W.cust[i],W.fam[i]))continue;const v=isE?W.e[i]:W.q[i];
    if(s===se){N.set(W.yw[i],(N.get(W.yw[i])||0)+v);const k=W.yw[i]*4+W.fam[i];NF.set(k,(NF.get(k)||0)+v)}else{const w=soNext(W.yw[i]);Pm.set(w,(Pm.get(w)||0)+v)}}
  return{N,NF,Pm};
}
function soWeekChart(se,pse,ref,n,p,compact){
  const{N,NF,Pm}=soWeekData(se,pse),isE=SO.metric==='e',fok=soFamOk();
  const weeks=[...new Set([...N.keys(),...Pm.keys()])].filter(w=>w<=ref||!N.has(w)).sort((a,b)=>a-b);
  if(!weeks.length)return'';
  const Wd=Math.max(680,weeks.length*44+70),H=compact?240:280,ml=56,mr=10,mt=24,mb=30,pw=Wd-ml-mr,ph=H-mt-mb,bw=pw/weeks.length;
  const mx=niceMax(Math.max(1,...weeks.map(w=>Math.max(N.get(w)||0,Pm.get(w)||0))));
  const fv=v=>isE?fE(v):fN(v)+' pcs';
  let s='';for(let t=0;t<=4;t++){const v=mx*t/4,y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${Wd-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}${isE?'€':''}</text>`}
  weeks.forEach((w,j)=>{const x0=ml+j*bw,bwid=bw*.36;let base=mt+ph,mk='';
    const pv=Pm.get(w)||0;if(pv>0){const h=ph*pv/mx;mk+=`<rect x="${x0+bw*.12}" y="${mt+ph-h}" width="${bwid}" height="${h}" fill="#b9b2c9" fill-opacity=".55"/>`}
    [0,1,2].forEach(f=>{const v=NF.get(w*4+f)||0;if(v<=0)return;const h=ph*v/mx;base-=h;mk+=`<rect x="${x0+bw*.5}" y="${base}" width="${bwid}" height="${h}" fill="${SO_FC[f]}"/>`});
    const nv=N.get(w)||0,tip=`<b>Semaine ${w%100}</b><br>${n} (${soWkY(w)} · ${soRange(w)}) : ${N.has(w)?fv(nv):'pas de relevé'}${[0,1,2].filter(f=>NF.get(w*4+f)).map(f=>`<br>&nbsp;· ${SOD.fams[f]} : ${fv(NF.get(w*4+f))}`).join('')}<br>${p} (${soWkY(soPrev(w))} · ${soRange(soPrev(w))}) : ${Pm.has(w)?fv(pv):'pas de relevé'}${N.has(w)&&pv?'<br>Évolution : '+((nv-pv)/pv*100).toFixed(1).replace('.',',')+' %':''}`;
    s+=`<g data-tip="${esc(tip)}"><rect x="${x0}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${mk}<text x="${x0+bw/2}" y="${H-10}" text-anchor="middle" class="ax">${soWk(w)}</text></g>`});
  const leg=[0,1,2].filter(f=>fok[f]).map(f=>`<span><span class="sw" style="background:${SO_FC[f]}"></span>${n} ${SOD.fams[f]}</span>`).join('')+(pse>=0?`<span><span class="sw" style="background:#b9b2c9"></span>${p}, même semaine</span>`:'');
  return`<div class="card"><div class="card-h"><h2>Sell-out par semaine · ${n} vs ${p}</h2><div class="seg" id="soMet"><button type="button" data-m="e" aria-pressed="${isE}">€ prix de vente</button><button type="button" data-m="q" aria-pressed="${!isE}">Pièces</button></div></div><div class="card-b"><div class="scroll"><svg viewBox="0 0 ${Wd} ${H}" width="100%" style="min-width:${Math.min(Wd,760)}px;display:block" role="img" aria-label="Sell-out par semaine">${s}</svg></div><div class="legend" style="margin-top:6px">${leg}</div></div></div>`;
}
function soWeeks(se,pse,ref,n,p){
  const{N,Pm}=soWeekData(se,pse),isE=SO.metric==='e',fv=v=>isE?fE(v):fN(v);
  /* stock fin de semaine */
  const S2=SOD.S,cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src,stN=new Map(),stP=new Map();
  const cs=soCS(se);for(let i=0;i<S2.cq.length;i++){const s=S2.season[i];if((s!==se&&s!==pse)||!srcOk(S2.src[i])||!cok[S2.cust[i]]||!fok[S2.fam[i]])continue;if(cs&&!cs.has(S2.cust[i]*4+S2.fam[i]))continue;if(!soPSok(s,S2.cust[i],S2.fam[i]))continue;if(s===se)stN.set(S2.yw[i],(stN.get(S2.yw[i])||0)+S2.st[i]);else{const w=soNext(S2.yw[i]);stP.set(w,(stP.get(w)||0)+S2.st[i])}}
  const weeks=[...new Set([...N.keys(),...Pm.keys()])].filter(w=>w<=ref||!N.has(w)).sort((a,b)=>a-b);
  let cn=0,cp=0;const rows=weeks.map(w=>{const a=N.get(w)||0,b=Pm.get(w)||0;if(w<=ref){cn+=a;cp+=b}
    return`<tr><td class="lab"><b>${soWkY(w)}</b> <span class="mutedv">${soRange(w)}</span><br><span class="mutedv" style="font-size:11.5px">${p} : ${soWkY(soPrev(w))} · ${soRange(soPrev(w))}</span></td><td class="cn">${N.has(w)?fv(a):'<span class="dash">—</span>'}</td><td class="cp">${Pm.has(w)?fv(b):'<span class="dash">—</span>'}</td><td>${N.has(w)&&b?soPct(a,b):''}</td><td class="cn">${w<=ref&&cn?fv(cn):''}</td><td class="cp">${w<=ref&&cn?fv(cp):''}</td><td>${w<=ref&&cn&&cp?soPct(cn,cp):''}</td><td class="cn">${stN.has(w)?fN(stN.get(w)):'<span class="dash">—</span>'}</td><td class="cp">${stP.has(w)?fN(stP.get(w)):'<span class="dash">—</span>'}</td></tr>`});
  return soWeekChart(se,pse,ref,n,p)+`<div class="card so-t t-all"><div class="so-h">Semaine par semaine · ${isE?'€ prix de vente':'pièces'}</div><div class="scroll"><table class="pv"><thead><tr><th class="lab">Semaine</th><th class="cn">${n}</th><th class="cp">${p}</th><th>Évo</th><th class="cn">Cumul ${n}</th><th class="cp">Cumul ${p}</th><th>Évo cumul</th><th class="cn">Stock ${n} (pcs)</th><th class="cp">Stock ${p} (pcs)</th></tr></thead><tbody>${rows.reverse().join('')}</tbody></table></div>
  <div class="card-b"><p class="meta" style="margin:0">Ventes de la semaine reconstituées à partir des colonnes S-1 / S-2 des relevés hebdomadaires ; une semaine sans relevé apparaît « — ». Le cumul additionne les semaines affichées (il peut différer du cumul saison du fichier quand une semaine manque).</p></div></div>`;
}
/* ---------- 4. évolution saisons ---------- */
function soEvo(){
  const cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src,S2=SOD.S,isE=SO.metric==='e';
  const res=SOD.seasons.map((sn,se)=>{const m=soSnap(se,SOD.last[sn]);const o={f:[0,0,0].map(()=>({e:0,q:0,d:new Set(),st:0})),d:new Set()};
    for(const[k,i]of m){if(!cok[S2.cust[i]]||!fok[S2.fam[i]]||!srcOk(S2.src[i])||!soPSok(se,S2.cust[i],S2.fam[i]))continue;const f=o.f[S2.fam[i]];f.e+=S2.ce[i];f.q+=S2.cq[i];f.st+=S2.st[i];if(S2.cq[i]>0){f.d.add(S2.cust[i]);o.d.add(S2.cust[i])}}
    return o});
  const order=SOD.seasons.map((s,i)=>i).reverse();
  const v=(f)=>isE?f.e:f.q,fv=x=>isE?fE(x):fN(x);
  const tot=o=>o.f.reduce((a,f)=>a+v(f),0);
  const mx=niceMax(Math.max(1,...res.map(tot)));
  const W=560,H=240,ml=56,mt=24,ph=H-mt-34,bw=(W-ml-10)/res.length;
  let s='';for(let t=0;t<=4;t++){const y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${W-10}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(mx*t/4)}${isE?'€':''}</text>`}
  res.forEach((o,j)=>{const x=ml+j*bw+bw*.2,w=bw*.6;let base=mt+ph;[0,1,2].forEach(f=>{const h=ph*Math.max(0,v(o.f[f]))/mx;if(!h)return;base-=h;s+=`<rect x="${x}" y="${base}" width="${w}" height="${h}" fill="${SO_FC[f]}"/>`});
    s+=`<text x="${x+w/2}" y="${base-5}" text-anchor="middle" class="ax">${fK(tot(o))}</text><text x="${x+w/2}" y="${H-12}" text-anchor="middle" class="ax">${sLab(SOD.seasons[j])} · ${soWkY(SOD.last[SOD.seasons[j]])}</text>`});
  const cell=(o,f)=>`<td>${fv(v(o.f[f]))}</td>`;
  const tr=(lab,fn)=>`<tr><td class="lab">${lab}</td>${order.map(j=>fn(res[j],j)).join('')}</tr>`;
  return`<div><div class="card so-t t-all"><div class="so-h">Sell-out par saison · cumul au dernier relevé · ${isE?'€':'pièces'}</div><div class="scroll"><table class="pv"><thead><tr><th class="lab">Famille</th>${order.map(j=>`<th>${sLab(SOD.seasons[j])}<br><span class="mutedv">${soWkY(SOD.last[SOD.seasons[j]])}</span></th>`).join('')}</tr></thead><tbody>
    ${[1,0,2].filter(f=>fok[f]).map(f=>tr(soFamLab(f),o=>cell(o,f))).join('')}
    ${tr('<b>Total</b>',o=>`<td><b>${fv(tot(o))}</b></td>`)}
    ${tr('Portes vendeuses',o=>`<td>${fN(o.d.size)}</td>`)}
    ${tr('Stock magasins (pcs)',o=>`<td>${fN(o.f.reduce((a,f)=>a+f.st,0))}</td>`)}
    ${tr('Évo vs même saison N-1',(o,j)=>{const pj=SOD.pair[j];return`<td>${pj>=0?soPct(tot(o),tot(res[pj])):'<span class="dash">—</span>'}</td>`})}
  </tbody></table></div></div>
  <div class="card"><div class="card-h"><h2>Évolution des saisons</h2><div class="seg" id="soMet"><button type="button" data-m="e" aria-pressed="${isE}">€</button><button type="button" data-m="q" aria-pressed="${!isE}">Pièces</button></div></div><div class="card-b"><svg viewBox="0 0 ${W} ${H}" width="100%" style="max-width:640px;display:block">${s}</svg></div></div></div>
  <div class="card"><div class="card-b"><p class="meta" style="margin:0">Archives disponibles à partir de 2025 : SS25 (S01 → S22 2025, cumul incluant la pré-saison 2024), FW25 (S21 → S44 2025), SS26 (S46 2025 → S28 2026 ; pas de relevé S01–S24 2026 dans les archives, le cumul reste juste), FW26 (en cours). Les saisons en cours ne sont pas terminées : comparez-les plutôt dans la vue « Synthèse N / N-1 » à date.</p></div></div>`;
}
/* ---------- 6. best-sellers, ruptures & excess ---------- */
function soBest(se,pse,n,p){
  const I=SOD.I,cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src,sN=SOD.seasons[se],nW=SOD.irq[sN]||4,iref=SOD.iref[sN];
  const m=new Map(),prevE=new Map();
  for(let i=0;i<I.q.length;i++){const s=I.season[i];if((s!==se&&s!==pse)||!srcOk(I.src[i])||!cok[I.cust[i]])continue;const it=I.item[i],f=SOD.ifam[it];if(!fok[f]||!soPSok(s,I.cust[i],f))continue;
    if(s===pse){prevE.set(it,(prevE.get(it)||0)+I.e[i]);continue}
    const a=m.get(it)||{q:0,e:0,rq:0,st:0,sv:0,ral:0,reco:0,dn:0,rup:[],dorm:0,dormSt:0,f,ix:[]};
    a.q+=I.q[i];a.e+=I.e[i];a.rq+=I.rq[i];a.st+=I.st[i];a.sv+=I.sv[i];a.ral+=I.ral[i];a.reco+=I.reco[i];
    if(I.q[i]>0){a.dn++;if(I.st[i]<=0)a.rup.push(i)}
    if(I.st[i]>0&&I.rq[i]<=0){a.dorm++;a.dormSt+=I.st[i]}
    m.set(it,a)}
  const cov=a=>a.rq>0?a.st/(a.rq/nW):null;
  const stat=a=>{const c=cov(a),rp=a.dn?a.rup.length/a.dn:0;if((c!=null&&c<3)||rp>=.25)return'rup';if(c!=null&&c<6)return'ten';if(c==null&&a.st>0||c>16)return a.rup.length?'reb':'exc';return'ok'};
  const all=[...m.entries()];
  const key=SO.bsk||'e',val=a=>key==='rq'?a.rq:a.e;
  const best=all.filter(([,a])=>a.q>0).sort((x,y)=>val(y[1])-val(x[1]));
  const topN=SO.bsn||30,top=best.slice(0,topN);
  const topRup=top.filter(([,a])=>stat(a)==='rup'),nRupStores=top.reduce((s,[,a])=>s+a.rup.length,0);
  const exc=all.filter(([,a])=>a.st>0&&(stat(a)==='exc'||stat(a)==='reb')).sort((x,y)=>y[1].sv-x[1].sv);
  const excSt=exc.reduce((s,[,a])=>s+a.st,0),excSv=exc.reduce((s,[,a])=>s+a.sv,0);
  const fCov=v=>v==null?'<span class="dash">pas de vente</span>':v>52?'> 52 sem.':fN(Math.round(v))+' sem.',covCls=v=>v==null?'warnc':v<3?'neg':v>16?'warnc':'';
  const chipS={rup:'<span class="chip down">Rupture</span>',ten:'<span class="chip" style="background:#fff3dd;color:#b26a00">Tension</span>',ok:'<span class="chip up">OK</span>',exc:'<span class="chip" style="background:#ebe6f3;color:#5a4a86">Excess</span>',reb:'<span class="chip" style="background:#e4effa;color:#1d5fae">À rééquilibrer</span>'};
  const refLab=(it,a,open,x)=>`<button type="button" class="tg" data-x="${x}" aria-expanded="${open}"><i>${open?'−':'+'}</i></button> ${pImg(SOD.item[it],38)} <span class="memo-c" style="background:${SO_FC[a.f]};font-size:10px">${SO_FCODE[a.f]}</span> <b>${esc(SOD.item[it])}</b> <span class="mutedv">${esc(SOD.idesc[it])}</span>`;
  const sub=(idx,kind)=>{const l=idx.slice().sort((x,y)=>kind==='rup'?(I.rq[y]-I.rq[x]||I.q[y]-I.q[x]):(I.st[y]-I.st[x]));return l.slice(0,40).map(i=>`<tr class="sub"><td class="lab" style="padding-left:44px">${soLab(I.cust[i])}</td><td>${fN(I.q[i])} pcs</td><td>${fN(I.rq[i])}</td><td><b>${fN(I.st[i])}</b></td><td colspan="3">${kind==='rup'?(I.ral[i]>0?`<span class="chip up">${fN(I.ral[i])} pcs en RAL</span>`:'<span class="chip down">rien en commande</span>')+(I.reco[i]>0?` · réassort conseillé ${fN(I.reco[i])}`:''):(I.rq[i]<=0?'<span class="chip" style="background:#ebe6f3;color:#5a4a86">aucune vente '+nW+' sem.</span>':'')}</td><td></td></tr>`).join('')+(l.length>40?`<tr class="more"><td colspan="9" style="padding-left:44px">… ${fN(l.length-40)} autres magasins</td></tr>`:'')};
  const bestRows=top.map(([it,a],r)=>{const open=SO.exp.has(it),c=cov(a),pe=prevE.get(it);
    let h=`<tr><td class="lab"><span class="mutedv" style="display:inline-block;width:22px">${r+1}</span>${refLab(it,a,open,it)}</td><td><b>${fE(a.e)}</b> <span class="mutedv">· ${fN(a.q)} pcs</span>${pse>=0?`<br><span class="mutedv">${p} : ${pe?fE(pe):'—'}</span>`:''}</td><td>${fN(a.rq)}</td><td><b>${fN(a.st)}</b></td><td class="${covCls(c)}">${fCov(c)}</td><td>${a.rup.length?`<b class="neg">${fN(a.rup.length)}</b> / ${fN(a.dn)} <span class="mutedv">(${Math.round(a.rup.length/a.dn*100)} %)</span>`:`0 / ${fN(a.dn)}`}</td><td>${fN(a.ral)}</td><td>${fN(a.reco)}</td><td>${chipS[stat(a)]}</td></tr>`;
    if(open)h+=a.rup.length?`<tr class="sub"><td class="lab" colspan="10" style="padding-left:44px"><b>Magasins en rupture</b> (ont vendu la référence, stock à 0) :</td></tr>`+sub(a.rup,'rup'):`<tr class="sub"><td colspan="10" style="padding-left:44px" class="mutedv">Aucun magasin en rupture sur cette référence.</td></tr>`;
    return h}).join('');
  const lim=SO.all?exc.length:Math.min(30,exc.length);
  const excRows=exc.slice(0,lim).map(([it,a])=>{const x=-1-it,open=SO.exp.has(x),c=cov(a);
    let h=`<tr><td class="lab">${refLab(it,a,open,x)}</td><td><b>${fN(a.st)}</b></td><td><b>${fE(a.sv)}</b></td><td>${fN(a.rq)}</td><td class="${covCls(c)}">${fCov(c)}</td><td>${fN(a.q)}</td><td>${a.q+a.st>0?Math.round(a.q/(a.q+a.st)*100)+' %':'—'}</td><td>${fN(a.dorm)} <span class="mutedv">(${fN(a.dormSt)} pcs)</span></td><td>${fN(a.ral)}</td></tr>`;
    if(open){const idx=[];for(let i=0;i<I.q.length;i++)if(I.season[i]===se&&I.item[i]===it&&I.st[i]>0&&srcOk(I.src[i])&&cok[I.cust[i]]&&soPSok(se,I.cust[i],a.f))idx.push(i);h+=sub(idx,'exc')}
    return h}).join('');
  return`<div class="kgrid k4">
    ${faTile(`Top ${topN} best-sellers`,fE(top.reduce((s,[,a])=>s+a.e,0)),`${fN(top.reduce((s,[,a])=>s+a.q,0))} pcs · ${Math.round(top.reduce((s,[,a])=>s+a.e,0)/Math.max(1,best.reduce((s,[,a])=>s+a.e,0))*100)} % du sell-out ${n}`)}
    ${faTile('Best-sellers en rupture',fN(topRup.length)+' réf.',`sur ${topN} · couverture &lt; 3 sem. ou ≥ 25 % des portes à 0`,topRup.length?'down':'up')}
    ${faTile('Magasins en rupture',fN(nRupStores),`couples magasin × best-seller vendus puis à stock 0`,nRupStores?'down':'up')}
    ${faTile('Excess magasins',fN(excSt)+' pcs',`${fE(excSv)} de stock · ${fN(exc.length)} réf. &gt; 16 sem. de couverture ou sans vente`,excSt?'down':'')}
  </div>
  <div class="card so-t t-all"><div class="so-h">Best-sellers ${n} · ruptures · cumul ${soWkY(iref)}</div>
    <div class="card-b" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><div class="seg" id="soBsk">${[['e','Classer par sell-out saison'],['rq','par ventes des '+nW+' dernières sem.']].map(([k,l])=>`<button type="button" data-k="${k}" aria-pressed="${key===k}">${l}</button>`).join('')}</div><div class="seg" id="soBsn">${[20,30,50,100].map(k=>`<button type="button" data-n="${k}" aria-pressed="${topN===k}">Top ${k}</button>`).join('')}</div><span class="meta">Cliquez sur + pour la liste des magasins en rupture (avec RAL et réassort conseillé)</span></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Référence</th><th>Sell-out ${n}</th><th>Ventes ${nW} sem.</th><th>Stock magasins</th><th>Couverture</th><th>Magasins en rupture</th><th>RAL</th><th>Réassort conseillé</th><th>Statut</th></tr></thead><tbody>${bestRows||'<tr class="more"><td colspan="10">Aucune vente.</td></tr>'}</tbody></table></div></div>
  <div class="card so-t t-comp"><div class="so-h">Excess · stock magasins sans écoulement suffisant</div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">Référence</th><th>Stock (pcs)</th><th>Valeur stock</th><th>Ventes ${nW} sem.</th><th>Couverture</th><th>Vendu saison</th><th>Écoulement</th><th>Magasins sans vente ${nW} sem.</th><th>RAL</th></tr></thead><tbody>${excRows||'<tr class="more"><td colspan="9">Aucun excess détecté.</td></tr>'}${exc.length>lim?`<tr class="more"><td colspan="9"><button type="button" class="linkbtn" id="soAll">Afficher les ${fN(exc.length-lim)} autres</button></td></tr>`:''}</tbody></table></div>
    <div class="card-b"><p class="meta" style="margin:0">Données référence × magasin à fin ${soWkY(iref)}. <b>Rupture</b> : couverture &lt; 3 semaines au rythme des ${nW} dernières semaines, ou au moins 25 % des magasins qui ont vendu la référence sont à stock 0. <b>Tension</b> : 3 à 6 semaines. <b>À rééquilibrer</b> : stock global élevé (&gt; 16 sem.) alors que des magasins vendeurs sont à 0 — transfert ou réassort ciblé possible. <b>Excess</b> : plus de 16 semaines de couverture, ou du stock sans aucune vente sur ${nW} semaines ; trié par valeur de stock (prix de vente). Le RAL indique ce qui est déjà en commande pour le magasin. Filtres commercial, enseigne, famille et pré-saison appliqués.</p></div></div>`;
}
/* ---------- 5. stock & réassort ---------- */
function soStock(se,n){
  const sName=SOD.seasons[se],last=SOD.last[sName],cok=soCustOk(),fok=soFamOk(),srcOk=s=>SO.src<0||s===SO.src;
  const W=SOD.W,wkSet=[...new Set(SOD.W.yw.filter((w,i)=>SOD.W.season[i]===se))].sort((a,b)=>a-b),r4w=wkSet.filter(w=>w<=last).slice(-4);
  const famT=[0,1,2].map(()=>({cq:0,ce:0,st:0,ral:0,reco:0,w:0,r4:0})),byC=new Map();
  const Sn=soSnap(se,last),S2=SOD.S,L=SOD.L,cs=soCS(se),csOk=(c,f)=>(!cs||cs.has(c*4+f))&&soPSok(se,c,f);
  for(const[k,i]of Sn){if(!cok[S2.cust[i]]||!fok[S2.fam[i]]||!srcOk(S2.src[i])||!csOk(S2.cust[i],S2.fam[i]))continue;const f=famT[S2.fam[i]];f.cq+=S2.cq[i];f.ce+=S2.ce[i];f.st+=S2.st[i];const c=S2.cust[i];const o=byC.get(c)||{cq:0,ce:0,st:0,ral:0,reco:0,w:0,r4:0};o.cq+=S2.cq[i];o.ce+=S2.ce[i];o.st+=S2.st[i];byC.set(c,o)}
  for(let i=0;i<L.ral.length;i++){if(L.season[i]!==se||!cok[L.cust[i]]||!fok[L.fam[i]]||!srcOk(L.src[i])||!csOk(L.cust[i],L.fam[i]))continue;const f=famT[L.fam[i]];f.ral+=L.ral[i];f.reco+=L.reco[i];const o=byC.get(L.cust[i]);if(o){o.ral+=L.ral[i];o.reco+=L.reco[i]}}
  for(let i=0;i<W.q.length;i++){if(W.season[i]!==se||!srcOk(W.src[i])||!cok[W.cust[i]]||!fok[W.fam[i]]||!csOk(W.cust[i],W.fam[i]))continue;const w=W.yw[i];if(!r4w.includes(w))continue;const f=famT[W.fam[i]];f.r4+=W.q[i];if(w===last)f.w+=W.q[i];const o=byC.get(W.cust[i]);if(o){o.r4+=W.q[i];if(w===last)o.w+=W.q[i]}}
  const T=famT.reduce((a,f)=>{for(const k in f)a[k]=(a[k]||0)+f[k];return a},{});
  const sell=(q,st)=>q+st>0?q/(q+st):0,cov=(st,r4)=>r4>0?st/(r4/Math.max(1,r4w.length)):null;
  const fCov=v=>v==null?'<span class="dash">—</span>':v>52?'> 52 sem.':fN(Math.round(v))+' sem.',covCls=v=>v==null?'':v<3?'neg':v>16?'warnc':'';
  const famRow=(lab,f,cls)=>{const c=cov(f.st,f.r4);return`<tr class="${cls||''}"><td class="lab">${lab}</td><td>${fN(f.w)}</td><td>${fN(f.r4)}</td><td>${fN(f.cq)}</td><td>${fE(f.ce)}</td><td><b>${fN(f.st)}</b></td><td>${Math.round(sell(f.cq,f.st)*100)} %</td><td class="${covCls(c)}">${fCov(c)}</td><td>${fN(f.ral)}</td><td>${fN(f.reco)}</td></tr>`};
  const famTable=`<div class="scroll"><table class="pv"><thead><tr><th class="lab">Famille</th><th>Ventes ${soWk(last)}</th><th>Ventes 4 sem.</th><th>Cumul (pcs)</th><th>Cumul (€)</th><th>Stock magasins</th><th>Écoulement</th><th>Couverture</th><th>RAL</th><th>Réassort conseillé</th></tr></thead><tbody>${[1,0,2].filter(f=>fok[f]).map(f=>famRow(soFamLab(f),famT[f])).join('')}${famRow('Total',T,'tot')}</tbody></table></div>`;
  const dk=SO.sdim,q=SO.q.trim().toLowerCase();let body='';
  if(dk==='item'){const sv=SO.dim;SO.dim='item';body=soItems(se,-1,n,'',  'item',q);SO.dim=sv}
  else{
    const agg=new Map();for(const[c,o]of byC){const k=dk==='cust'?c:dk==='grp'?soGrp(c):soSales(c);const a=agg.get(k)||{cq:0,ce:0,st:0,ral:0,reco:0,w:0,r4:0,n:0};for(const kk of['cq','ce','st','ral','reco','w','r4'])a[kk]+=o[kk];a.n++;agg.set(k,a)}
    let rows=[...agg.entries()].filter(([k])=>!q||(dk==='cust'?(SOD.cust[k]+' '+SOD.cinfo[k].join(' ')+' '+(SOD.ci[k]>=0?(D.custCity[SOD.ci[k]]||'')+' '+(D.custGrp[SOD.ci[k]]||''):'')):String(k)).toLowerCase().includes(q));
    rows.sort((a,b)=>SO.ssort==='st'?b[1].st-a[1].st:SO.ssort==='cov'?((cov(a[1].st,a[1].r4)??1e9)-(cov(b[1].st,b[1].r4)??1e9)):b[1].cq-a[1].cq);
    let hidden=0;if(rows.length>60&&!SO.all){hidden=rows.length-60;rows=rows.slice(0,60)}
    body=`<div class="card"><div class="scroll"><table class="pv"><thead><tr><th class="lab">${({cust:'Magasin',grp:'Groupement',sales:'Commercial'})[dk]}</th>${dk!=='cust'?'<th>Magasins</th>':''}<th>Ventes ${soWk(last)}</th><th>Ventes 4 sem.</th><th>Cumul</th><th>Stock magasins</th><th>Écoulement</th><th>Couverture</th><th>RAL</th><th>Réassort conseillé</th></tr></thead><tbody>${rows.map(([k,a])=>{const c=cov(a.st,a.r4);return`<tr><td class="lab">${dk==='cust'?soLab(k):esc(k)}</td>${dk!=='cust'?`<td>${fN(a.n)}</td>`:''}<td>${fN(a.w)}</td><td>${fN(a.r4)}</td><td>${fN(a.cq)}</td><td><b>${fN(a.st)}</b></td><td>${Math.round(sell(a.cq,a.st)*100)} %</td><td class="${covCls(c)}">${fCov(c)}</td><td>${fN(a.ral)}</td><td>${fN(a.reco)}</td></tr>`}).join('')}${hidden?`<tr class="more"><td colspan="10"><button type="button" class="linkbtn" id="soAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}</tbody></table></div></div>`}
  return`<div class="kgrid k4">
    ${faTile('Ventes '+soWkY(last),fN(T.w)+' pcs',`4 dernières semaines : ${fN(T.r4)} pcs`)}
    ${faTile('Cumul '+n,fN(T.cq)+' pcs',`${fE(T.ce)} au prix de vente`)}
    ${faTile('Stock en magasin',fN(T.st)+' pcs',`fin ${soWkY(last)} · ${fN(byC.size)} magasins`)}
    ${faTile('Écoulement / couverture',Math.round(sell(T.cq,T.st)*100)+' %',`vendu / (vendu + stock)<br>couverture : ${fCov(cov(T.st,T.r4))} au rythme des 4 dernières semaines`)}
  </div>
  <div class="card"><div class="card-h"><h2>Stock par famille · fin ${soWkY(last)}</h2><span class="note">Couverture = stock ÷ ventes moyennes des 4 dernières semaines · rouge &lt; 3 sem., orange &gt; 16 sem.</span></div>${famTable}</div>
  <div class="card"><div class="card-b" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><div class="seg" id="soSDim">${[['item','Référence'],['cust','Magasin'],['grp','Groupement'],['sales','Commercial']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dk===k}">${l}</button>`).join('')}</div>
    <input type="search" id="soQ" placeholder="Rechercher…" value="${esc(SO.q)}" style="min-width:220px;padding:7px 10px;border:1px solid var(--line-2);border-radius:8px">
    ${dk==='item'?`<div class="seg" id="soSort">${[['n','Trier par ventes'],['st','par stock']].map(([k,l])=>`<button type="button" data-o="${k}" aria-pressed="${SO.sort===k}">${l}</button>`).join('')}</div>`:`<div class="seg" id="soSSort">${[['cq','Trier par ventes'],['st','par stock'],['cov','par couverture (rupture d\'abord)']].map(([k,l])=>`<button type="button" data-o="${k}" aria-pressed="${SO.ssort===k}">${l}</button>`).join('')}</div>`}</div></div>${body}`;
}
/* ================= facturation ================= */
let FA=null;const FAS={season:null,dim:'sales',exp:new Set(),view:'simple',rdim:'cust',rsort:'r',rq:'',month:null};
async function initFact(){
  const el=document.getElementById('factdata');if(!el||!el.textContent.trim().startsWith('{'))return;
  const o=JSON.parse(el.textContent);const bin=Uint8Array.from(atob(o.bin),c=>c.charCodeAt(0));
  const ab=await new Response(new Blob([bin]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer();
  const n=o.n;o.C={b:new Float32Array(ab,0,n),i:new Float32Array(ab,4*n,n),r:new Float32Array(ab,8*n,n)};let off=12*n;
  o.u16.forEach(k=>{o.C[k]=new Uint16Array(ab,off,n);off+=2*n});o.u8.forEach(k=>{o.C[k]=new Uint8Array(ab,off,n);off+=n});o.bin=null;FA=o;
}
function faRows(){
  const masks=[];for(const d of ['sot','perim','fam','bm1','cg','n1','sales','grp']){if(!F[d]||!F[d].size)continue;const allowed=new Set([...F[d]].map(i=>D.dicts[d][i]));masks.push([FA.C[d],FA.dicts[d].map(v=>allowed.has(v)?1:0)])}
  const out=[];outer:for(let i=0;i<FA.n;i++){for(const[c,m]of masks)if(!m[c[i]])continue outer;out.push(i)}return out;
}
const ymLab=k=>{if(!k)return'—';const MN=['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];return MN[+k.slice(5,7)-1]+' '+k.slice(2,4)};
const ymAdd=(k,m)=>{let y=+k.slice(0,4),mo=+k.slice(5,7)+m;while(mo>12){mo-=12;y++}while(mo<1){mo+=12;y--}return y+'-'+String(mo).padStart(2,'0')};
function blLateAgg(sName,keyOf){
  const ci=BL?BL.dicts.col.indexOf(sName):-1;const out=new Map();if(ci<0)return out;
  const sv=BLS.col;BLS.col=ci;const rows=blRows();BLS.col=sv;const asof=BL.asof;
  for(const i of rows){const k=keyOf(i);let o=out.get(k);if(!o){o={late:0,wait:0,arr:0,none:0,old:'9',waitMax:'',arrMax:'',next:'9',fut:0};out.set(k,o)}
    const a=BL.cols.a[i],dl=BL.dicts.dl[BL.cols.dl[i]],da=BL.dicts.da[BL.cols.da[i]];
    if(BL.late[i]){o.late+=a;if(dl<o.old)o.old=dl;if(!da)o.none+=a;else if(da>asof){o.wait+=a;if(da>o.waitMax)o.waitMax=da}else{o.arr+=a;if(da>o.arrMax)o.arrMax=da}}
    else{o.fut+=a;if(dl&&dl!=='(non renseigné)'&&dl<o.next)o.next=dl}}
  return out;
}
/* ===== Facturation v3 : 2 vues, base RDD ===== */
let ROB=null;
async function initRob(){
  const el=document.getElementById('robdata');if(!el||!el.textContent.trim().startsWith('{'))return;
  const o=JSON.parse(el.textContent);const bin=Uint8Array.from(atob(o.bin),c=>c.charCodeAt(0));
  const ab=await new Response(new Blob([bin]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer();
  const n=o.n;o.C={r:new Float32Array(ab,0,n),q:new Float32Array(ab,4*n,n)};let off=8*n;
  o.u16.forEach(k=>{o.C[k]=new Uint16Array(ab,off,n);off+=2*n});o.u8.forEach(k=>{o.C[k]=new Uint8Array(ab,off,n);off+=n});o.bin=null;
  o.ci=o.dicts.cust.map(c=>D.dicts.cust.indexOf(c));
  /* date d'entrée en stock prévue (backlog ADV) par client + article */
  o.arr=new Map();if(BL){for(let i=0;i<BL.n;i++){const da=BL.dicts.da[BL.cols.da[i]];const k=D.dicts.cust[BL.cols.cust[i]]+'|'+BL.dicts.art[BL.cols.art[i]];const v=o.arr.get(k);if(!v)o.arr.set(k,{max:da||'',none:!da});else{if(da&&da>v.max)v.max=da;if(!da)v.none=true}}}
  ROB=o;
}
function robRows(){
  const masks=[];for(const d of ['sot','perim','fam','bm1','cg','n1','sales','grp']){if(!F[d]||!F[d].size)continue;const allowed=new Set([...F[d]].map(i=>D.dicts[d][i]));masks.push([ROB.C[d],ROB.dicts[d].map(v=>allowed.has(v)?1:0)])}
  const ex=S.exclC?ROB.dicts.cust.indexOf('4437'):-2;
  const out=[];outer:for(let i=0;i<ROB.n;i++){if(ROB.C.cust[i]===ex)continue;for(const[c,m]of masks)if(!m[c[i]])continue outer;out.push(i)}return out;
}
function faViewSeg(){return`<div class="seg" id="faView"><button type="button" data-v="simple" aria-pressed="${FAS.view==='simple'}">Vue simple</button><button type="button" data-v="fact" aria-pressed="${FAS.view==='fact'}">Facturé (saison)</button><button type="button" data-v="ral" aria-pressed="${FAS.view==='ral'}">Reste à livrer</button><button type="button" data-v="year" aria-pressed="${FAS.view==='year'}">Facturé par année</button></div>`}
function bindFaView(P){$('#faView').onclick=e=>{const b=e.target.closest('button');if(!b)return;FAS.view=b.dataset.v;FAS.month=null;factPanel(P)}}
const faTile=(h,big,foot,cls)=>`<div class="kt ${cls||''}"><div class="kt-h"><h3>${h}</h3></div><div class="big">${big}</div><div class="foot">${foot}</div></div>`;
const faPct=(a,b)=>b?(Math.round(a/b*1000)/10).toLocaleString('fr-FR')+' %':'—';
const faPts=v=>`<span class="chip ${v>=0?'up':'down'}">${v>=0?'▲ +':'▼ −'}${Math.abs(v)} pts</span>`;
function faSeasonSel(ss,se){return`<label class="levels"><span class="tl">Saison</span><select id="faSeason">${ss.map((x,i)=>`<option value="${i}" ${i===se?'selected':''}>${sLab(x)} · ${x}</option>`).join('')}</select></label>`}
function faBars(months,get,{asofYM,sel,tipF,legend}){
  /* get(k) -> [[valeur,couleur],...] empilés */
  const W=Math.max(680,months.length*52+70),H=250,ml=56,mr=10,mt=22,mb=30,pw=W-ml-mr,ph=H-mt-mb,bw=pw/Math.max(1,months.length);
  const mx=niceMax(Math.max(1,...months.map(k=>get(k).reduce((a,x)=>a+Math.max(0,x[0]),0))));
  let s='';for(let t=0;t<=4;t++){const v=mx*t/4,y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}€</text>`}
  const xi=months.indexOf(asofYM);if(xi>=0)s+=`<line x1="${ml+xi*bw+bw/2}" x2="${ml+xi*bw+bw/2}" y1="${mt-6}" y2="${mt+ph}" stroke="var(--ink-2)" stroke-dasharray="3 3"/><text x="${ml+xi*bw+bw/2+4}" y="${mt-8}" class="ax">aujourd'hui</text>`;
  months.forEach((k,j)=>{const x=ml+j*bw+bw*.18,w=bw*.64;let base=mt+ph,m='';get(k).forEach(([v,c])=>{if(v<=0)return;const h=ph*v/mx;base-=h;m+=`<rect x="${x}" y="${base}" width="${w}" height="${h}" fill="${c}"/>`});
    const op=sel&&sel!==k?' opacity=".35"':'';
    s+=`<g data-m="${k}" style="cursor:pointer"${op} data-tip="${esc(tipF(k))}"><rect x="${ml+j*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${m}<text x="${x+w/2}" y="${H-10}" text-anchor="middle" class="ax" ${sel===k?'style="font-weight:700"':''}>${ymLab(k)}</text></g>`});
  return`<div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:${Math.min(W,820)}px;display:block" role="img">${s}</svg></div><div class="legend" style="margin-top:6px">${legend}</div>`;
}
function faMemo(se){
  const rows=faRows(),C2=FA.C,asofYM=FA.asof.slice(0,7),rddD=FA.dicts.rdd;
  const FAMS=[['Textile','APP','#2a78d6'],['Footwear','FTW','#a23f7a'],['Accessoires','ACC','#1f9d6b']];
  const m=new Map(FAMS.map(f=>[f[0],{r:0,l:0,b:0}]));
  if(ROB){const si=ROB.dicts.season.indexOf(FA.dicts.season[se]),today=ROB.asof;for(const i of robRows()){if(ROB.C.season[i]!==si)continue;const o=m.get(ROB.dicts.fam[ROB.C.fam[i]]);if(!o)continue;const r=ROB.C.r[i];o.r+=r;const d=ROB.dicts.rdd[ROB.C.rdd[i]];if(d&&d<today)o.l+=r}}
  else for(const i of rows){if(C2.season[i]!==se)continue;const o=m.get(FA.dicts.fam[C2.fam[i]]);if(!o)continue;const r=C2.r[i];if(!r)continue;o.r+=r;const d=rddD[C2.rdd[i]];if(d&&d<asofYM)o.l+=r}
  const tot=[...m.values()].reduce((a,o)=>a+o.r,0);if(tot<1)return'';
  const n=sLab(FA.dicts.season[se]);
  const box=([lab,code,col])=>{const o=m.get(lab);const p=tot?o.r/tot*100:0;return`<div class="memo-b" style="--mc:${col}"><div class="memo-h"><span class="memo-c">${code}</span>${lab}</div><div class="memo-v">${fM(o.r)}</div><div class="memo-bar"><i style="width:${p}%"></i></div><div class="memo-f">${Math.round(p)} % du reste à facturer${o.l>0.5?`<br><b class="neg">${fM(o.l)} en retard</b> · ${fM(o.r-o.l)} à venir`:`<br>${fM(o.r)} à venir`}</div></div>`};
  return`<div class="card memo"><div class="card-h"><h2>Mémo · reste à facturer ${n}</h2><span class="note">Total ${fM(tot)} · en retard = date de livraison demandée avant le ${fD(FA.asof)}</span></div><div class="memo-g">${FAMS.map(box).join('')}</div></div>`;
}
function yearPanel(P){
  const rows=faRows(),C2=FA.C,invD=FA.dicts.inv,ss=FA.dicts.season,asofYM=FA.asof.slice(0,7);
  const years=[...new Set(invD.filter(Boolean).map(k=>k.slice(0,4)))].sort();
  if(!FAS.year)FAS.year=asofYM.slice(0,4);const Y=FAS.year,YP=String(+Y-1);
  const cur=Y===asofYM.slice(0,4),lastM=cur?Math.max(1,+asofYM.slice(5,7)-1):12;const cmpN={v:0};
  const SCOL=['#8fb8ea','#2a78d6','#f2b84b','#c98a04','#7fd3ad','#1f9d6b','#e7a0c6','#a23f7a'];
  const bySP=new Map(),seaP=new Map(),byM=new Map(),bySea=new Map(),totN={v:0},totP={v:0},dk=FAS.ydim||'season',agg=new Map(),byMP=new Map();
  for(const i of rows){const v=C2.i[i];if(!v)continue;const iv=invD[C2.inv[i]];if(!iv)continue;const y=iv.slice(0,4),m=+iv.slice(5,7);
    if(y===Y){const s=C2.season[i];const o=byM.get(m)||new Map();o.set(s,(o.get(s)||0)+v);byM.set(m,o);bySea.set(s,(bySea.get(s)||0)+v);totN.v+=v;if(m<=lastM)cmpN.v+=v;
      const k=C2[dk][i];const a=agg.get(k)||{n:0,p:0,nc:0};a.n+=v;if(m<=lastM)a.nc+=v;agg.set(k,a)}
    else if(y===YP){byMP.set(m,(byMP.get(m)||0)+v);{const s=C2.season[i];const o=bySP.get(m)||new Map();o.set(s,(o.get(s)||0)+v);bySP.set(m,o);seaP.set(s,(seaP.get(s)||0)+v)}if(m<=lastM){totP.v+=v;const k=dk==='season'?C2[dk][i]+2:C2[dk][i];const a=agg.get(k)||{n:0,p:0,nc:0};a.p+=v;agg.set(k,a)}}}
  const seas=[...bySea.entries()].filter(([,v])=>Math.abs(v)>1).sort((a,b)=>a[0]-b[0]);
  const col=s=>SCOL[s%SCOL.length];
  /* graphique mensuel empilé par saison + repère N-1 */
  const W=760,H=270,ml=56,mr=10,mt=22,mb=30,pw=W-ml-mr,ph=H-mt-mb,bw=pw/12;
  const tot=m=>[...(byM.get(m)||new Map()).values()].reduce((a,b)=>a+b,0);
  const mx=niceMax(Math.max(1,...Array.from({length:12},(_,j)=>Math.max(tot(j+1),byMP.get(j+1)||0))));
  const MN=['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
  let s='';for(let t=0;t<=4;t++){const v=mx*t/4,y=mt+ph-ph*t/4;s+=`<line x1="${ml}" x2="${W-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}€</text>`}
  const allS=[...new Set([...bySea.keys(),...seaP.keys()])].filter(si=>Math.abs(bySea.get(si)||0)>1||Math.abs(seaP.get(si)||0)>1).sort((a,b)=>a-b);
  const stack=(o,x,w,op)=>{let base=mt+ph,mk='';allS.forEach(si=>{const v=Math.max(0,(o&&o.get(si))||0);if(!v)return;const h=ph*v/mx;base-=h;mk+=`<rect x="${x}" y="${base}" width="${w}" height="${h}" fill="${col(si)}" ${op?`fill-opacity="${op}" stroke="${col(si)}" stroke-width="1"`:''}/>`});return[mk,base]};
  for(let j=0;j<12;j++){const m=j+1,w=bw*.36,xp=ml+j*bw+bw*.12,xn=xp+w+bw*.04;const o=byM.get(m),op=bySP.get(m);
    const vp=byMP.get(m)||0,vn=tot(m);const hp=ph*Math.max(0,vp)/mx,hn=ph*Math.max(0,vn)/mx,bp=mt+ph-hp,bn=mt+ph-hn;
    const mp=vp?`<rect x="${xp}" y="${bp}" width="${w}" height="${hp}" rx="3" fill="var(--bar)"/>`:'',mn=vn?`<rect x="${xn}" y="${bn}" width="${w}" height="${hn}" rx="3" fill="var(--n)"/>`:'';
    const det=(mm)=>allS.filter(si=>mm&&mm.get(si)).map(si=>`${sLab(ss[si])} : ${fE(mm.get(si))}`).join('<br>')||'rien';
    const tip=`<b>${MN[j]} ${Y}</b> : ${fE(vn)}<br>${det(o)}<br><br><b>${MN[j]} ${YP}</b> : ${fE(vp)}<br>${det(op)}`;
    s+=`<g data-tip="${esc(tip)}"><rect x="${ml+j*bw}" y="${mt}" width="${bw}" height="${ph}" fill="transparent"/>${mp}${mn}
      ${vn?`<text x="${xn+w/2}" y="${bn-5}" text-anchor="middle" class="ax" style="font-size:10.5px;font-weight:700;fill:var(--ink)">${fK(vn)}</text>`:''}
      <text x="${ml+j*bw+bw/2}" y="${H-10}" text-anchor="middle" class="ax">${MN[j]}</text></g>`}
  const legend=`<span><span class="sw" style="background:var(--n)"></span>Facturé ${Y}</span><span><span class="sw" style="background:var(--bar)"></span>Facturé ${YP}, même mois</span><span class="meta">· survolez un mois pour le détail par saison</span>`;
  /* mix des saisons */
  const mix=seas.map(([si,v])=>`<div class="mix-s" style="flex:${Math.max(v,0)};background:${col(si)}" data-tip="${esc(`${sLab(ss[si])} : ${fE(v)} (${faPct(v,totN.v)})`)}"><span>${v/totN.v>=0.06?sLab(ss[si]):''}</span></div>`).join('');
  const mixL=seas.slice().sort((a,b)=>b[1]-a[1]).map(([si,v])=>`<li><span class="sw" style="background:${col(si)}"></span><b>${sLab(ss[si])}</b> <span class="mutedv">(${ss[si]})</span><span class="mix-v">${fE(v)} · ${faPct(v,totN.v)}</span></li>`).join('');
  /* tableau */
  const labD={season:'Saison',fam:'Famille',sot:'Type',sales:'Commercial',cg:'Groupe client',grp:'Groupement',cust:'Client'};
  const lab=k=>dk==='season'?`<span class="sw" style="background:${col(k)}"></span>${sLab(ss[k])} <span class="mutedv">${ss[k]}</span>`:dk==='cust'?`<b>${esc(FA.dicts.cust[k])}</b> ${esc(FA.custCity[k]||'')}`:esc(FA.dicts[dk][k]);
  let list=[...agg.entries()].filter(([,a])=>Math.abs(a.n)>1||Math.abs(a.p)>1).sort((a,b)=>b[1].n-a[1].n);const hidden=list.length>40&&!FAS.all?list.length-40:0;if(hidden)list=list.slice(0,40);
  const ev=chip(cmpN.v,totP.v);const per=cur?`janv. → ${MN[lastM-1]}`:'année complète';
  P.innerHTML=`<div class="toolbar">${faViewSeg()}<label class="levels"><span class="tl">Année de facturation</span><select id="faYear">${years.map(y=>`<option ${y===Y?'selected':''}>${y}</option>`).join('')}</select></label>${famSeg()}</div>
  <div class="kgrid k3">
    ${faTile('Facturé en '+Y,fM(totN.v),cur?`du 1er janvier au ${fD(FA.asof)}`:'année complète')}
    ${faTile(`Évolution vs ${YP}`,(totP.v?`<span class="${cmpN.v>=totP.v?'pos':'neg'}">${cmpN.v>=totP.v?'+':'−'}${Math.abs(Math.round((cmpN.v-totP.v)/totP.v*1000)/10).toLocaleString('fr-FR')} %</span>`:'—'),`${per} : ${fM(cmpN.v)} en ${Y}<br>contre ${fM(totP.v)} en ${YP}`,cmpN.v>=totP.v?'up':'down')}
    ${faTile('Saisons facturées',fN(seas.length),seas.length?`principale : <b>${sLab(ss[seas.slice().sort((a,b)=>b[1]-a[1])[0][0]])}</b> (${faPct(seas.slice().sort((a,b)=>b[1]-a[1])[0][1],totN.v)})`:'—')}
  </div>
  <div class="card"><div class="card-h"><h2>Mélange des saisons facturées en ${Y}</h2><span class="note">Base = mois de la facture, toutes saisons confondues</span></div><div class="card-b"><div class="mix">${mix}</div><ul class="mix-l">${mixL}</ul></div></div>
  <div class="card"><div class="card-h"><h2>Facturé par mois · ${Y}</h2><span class="note">Bleu = ${Y} · gris = ${YP}</span></div><div class="card-b"><div class="scroll"><svg viewBox="0 0 ${W} ${H}" width="100%" style="min-width:620px;max-width:980px;display:block" role="img" aria-label="Facturé par mois et par saison">${s}</svg></div><div class="legend" style="margin-top:6px">${legend}</div></div></div>
  <div class="card"><div class="card-h"><h2>Détail ${Y}</h2><div class="seg" id="fyDim">${Object.entries(labD).map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dk===k}">${l}</button>`).join('')}</div></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">${labD[dk]}</th><th>Facturé ${Y}</th><th>Part</th><th>${Y} ${per}</th><th>${YP} ${per}${dk==='season'?'<br><span class="meta">saison équivalente un an plus tôt</span>':''}</th><th>Évolution</th></tr></thead><tbody>
    ${list.map(([k,a])=>`<tr><td class="lab">${lab(k)}</td><td><b>${fE(a.n)}</b></td><td>${faPct(a.n,totN.v)}</td><td>${fE(a.nc)}</td><td>${fE(a.p)}${dk==='season'&&ss[k-2]?` <span class="mutedv">(${sLab(ss[k-2])})</span>`:''}</td><td>${chip(a.nc,a.p)}</td></tr>`).join('')}
    ${hidden?`<tr class="more"><td colspan="6"><button type="button" class="linkbtn" id="faAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}
    <tr class="tot"><td class="lab">Total</td><td>${fE(totN.v)}</td><td>100 %</td><td>${fE(cmpN.v)}</td><td>${fE(totP.v)}</td><td>${ev}</td></tr></tbody></table></div>
    <div class="card-b"><p class="meta" style="margin:0">La comparaison porte sur les mois complets (janvier à ${MN[lastM-1]}) des deux années${cur?` ; le mois en cours n'est compté que dans le total ${Y}`:''}. Les factures des saisons antérieures à SS24 ne sont pas dans l'extraction${years[0]?` ; ${years[0]} est donc incomplet`:''}.</p></div></div>`;
  bindFam();bindFaView(P);
  $('#faYear').onchange=e=>{FAS.year=e.target.value;FAS.all=false;factPanel(P)};
  $('#fyDim').onclick=e=>{const b=e.target.closest('button');if(b){FAS.ydim=b.dataset.d;FAS.all=false;factPanel(P)}};
  const a=$('#faAll');if(a)a.onclick=()=>{FAS.all=true;factPanel(P)};
}
function faSimplePanel(P){
  const ss=FA.dicts.season;if(!FAS.simpleInit){FAS.simpleInit=true;const ci=ss.indexOf(curSeasonName());if(ci>=0)FAS.season=ci}
  const se=FAS.season,sName=ss[se],n=sLab(sName),cur=sName===curSeasonName();
  const cm=new Uint8Array(D.dicts.cust.length).fill(1);
  const main=fichePsHtml(cm,sName);
  /* à venir et retards par client (données ROB, mêmes filtres) */
  let upc='',lateC='';
  if(ROB){if(!ROB.ci)ROB.ci=ROB.dicts.cust.map(c=>D.dicts.cust.indexOf(c));const si=ROB.dicts.season.indexOf(sName),today=ROB.asof;
    const FC={Textile:'#2a78d6',Footwear:'#a23f7a',Accessoires:'#1f9d6b'};
    const mon=new Map(),byC=new Map();
    for(const i of robRows()){if(ROB.C.season[i]!==si)continue;const r=ROB.C.r[i];if(r<=0)continue;const d=ROB.dicts.rdd[ROB.C.rdd[i]];if(!d)continue;const f=ROB.dicts.fam[ROB.C.fam[i]];
      if(d>=today){const k=d.slice(0,7);const o=mon.get(k)||{r:0,q:0,f:{},c:new Set()};o.r+=r;o.q+=ROB.C.q[i];o.f[f]=(o.f[f]||0)+r;o.c.add(ROB.C.cust[i]);mon.set(k,o)}
      else{const c=ROB.C.cust[i];const o=byC.get(c)||{r:0,q:0,old:d,refs:new Set()};o.r+=r;o.q+=ROB.C.q[i];if(d<o.old)o.old=d;o.refs.add(ROB.C.item[i]);byC.set(c,o)}}
    const ms=[...mon.keys()].sort();const mx=Math.max(1,...ms.map(k=>mon.get(k).r));
    upc=ms.length?`<div class="card"><div class="card-h"><h2>Ce qui va arriver</h2><span class="note">Reste à livrer ${n} par mois de livraison demandée, à partir d'aujourd'hui</span></div><div class="card-b fsu">
      ${ms.map(k=>{const o=mon.get(k);return`<div class="fsu-r"><span class="fsu-m">${ymLab(k)}</span><div class="fsu-b">${Object.entries(o.f).map(([f,v])=>`<i style="width:${v/mx*100}%;background:${FC[f]||'#999'}" data-tip="${esc(f+' : '+fE(v))}"></i>`).join('')}</div><span class="fsu-v"><b>${fM(o.r)}</b> <span class="mutedv">${fN(o.q)} pcs · ${fN(o.c.size)} clients</span></span></div>`}).join('')}
      <div class="legend" style="margin-top:8px">${Object.entries(FC).map(([f,c])=>`<span><span class="sw" style="background:${c}"></span>${f}</span>`).join('')}</div></div></div>`:'';
    const lc=[...byC.entries()].sort((a,b)=>b[1].r-a[1].r);const lim=FAS.lateAll?lc.length:15;
    const lab=c=>{const di=ROB.ci[c];return di>=0?(di===CENT?'<b>Centrale Intersport</b> 4437':`<b>${esc(D.dicts.cust[di])}</b> ${esc(D.custCity[di]||'')}${D.custGrp[di]?` <span class="mutedv">· ${esc(D.custGrp[di])}</span>`:''}`):esc(ROB.dicts.cust[c])};
    const days=d=>Math.round((new Date(today)-new Date(d))/864e5);
    lateC=lc.length?`<div class="card"><div class="card-h"><h2>Clients en retard</h2><span class="note">${fN(lc.length)} clients · livraison demandée dépassée au ${fD(today)}</span></div>
      <div class="scroll"><table class="pv"><thead><tr><th class="lab">Client</th><th class="lab">Commercial</th><th>En retard</th><th>Pièces</th><th>Réf.</th><th>Plus ancien retard</th><th></th></tr></thead><tbody>
      ${lc.slice(0,lim).map(([c,o])=>{const di=ROB.ci[c];const sl=di>=0?D.dicts.sales[D.custSalesI[di]]:'';return`<tr><td class="lab">${lab(c)}</td><td class="lab mutedv">${sl&&sl!=='(non renseigné)'?esc(sl):'—'}</td><td><b class="neg">${fE(o.r)}</b></td><td>${fN(o.q)}</td><td>${fN(o.refs.size)}</td><td>${fD(o.old)} <span class="neg">· ${fN(days(o.old))} j</span></td><td>${di>=0?`<button type="button" class="fiche" data-c="${di}">Fiche</button>`:''}</td></tr>`}).join('')}
      ${lc.length>lim?`<tr class="more"><td colspan="7"><button type="button" class="linkbtn" id="faLateAll">Afficher les ${fN(lc.length-lim)} autres</button></td></tr>`:''}</tbody></table></div>
      <div class="card-b"><p class="meta" style="margin:0">Cliquez sur « Fiche » pour voir, client par client, les produits en retard et leur arrivée côté ADV.</p></div></div>`:'';
  }
  P.innerHTML=`<div class="toolbar">${faViewSeg()}${faSeasonSel(ss,se)}${famSeg()}</div>
    ${cur?'':`<div class="card"><div class="card-b meta" style="margin:0">Saison affichée : ${n}. La saison en cours est ${sLab(curSeasonName())}.</div></div>`}
    <div class="card fsim">${main||`<div class="card-b">Aucune commande ${typeLab()} ${n} avec ces filtres.</div>`}</div>
    ${upc}${lateC}`;
  bindFam();bindFaView(P);
  $('#faSeason').onchange=e=>{FAS.season=+e.target.value;factPanel(P)};
  const la=$('#faLateAll');if(la)la.onclick=()=>{FAS.lateAll=true;factPanel(P)};
}
function factPanel(P){
  if(!FA){P.innerHTML='<div class="card"><div class="card-b">Données de facturation non chargées.</div></div>';return}
  const ss=FA.dicts.season;if(FAS.season==null){FAS.season=ss.indexOf(D.dicts.season[S.n]);if(FAS.season<0)FAS.season=ss.length-1}
  if(FAS.view==='simple')return faSimplePanel(P);
  if(FAS.view==='ral')return ralPanel(P);
  if(FAS.view==='year')return yearPanel(P);
  const se=FAS.season,sName=ss[se],sp=ss.indexOf((+sName.slice(0,4)-1)+sName.slice(4)),n=sLab(sName),p=sp>=0?sLab(ss[sp]):'N-1';
  const rows=faRows(),C2=FA.C,today=FA.asof,asofYM=today.slice(0,7),asofP=ymAdd(asofYM,-12),rddD=FA.dicts.rdd;
  /* par mois de livraison demandée (RDD) */
  const z=()=>({b:0,i:0,r:0});const byM=new Map(),byMP=new Map();const T=z(),TP=z(),Tdue=z(),TPdue=z();
  const dk=FAS.dim,agg=new Map(),INV=new Map(),X=new Map();
  for(const i of rows){const s=C2.season[i];if(s!==se&&s!==sp)continue;const isN=s===se;const m=rddD[C2.rdd[i]]||'';const b=C2.b[i],v=C2.i[i],r=C2.r[i];
    if(isN){const iv=FA.dicts.inv[C2.inv[i]]||'';if(v&&iv){INV.set(iv,(INV.get(iv)||0)+v);const row=X.get(m)||new Map();row.set(iv,(row.get(iv)||0)+v);X.set(m,row)}if(r){const row=X.get(m)||new Map();const kk=m&&m<asofYM?'~late':'~fut';row.set(kk,(row.get(kk)||0)+r);X.set(m,row)}}
    const mk=isN?m:(m?ymAdd(m,12):'');const map=isN?byM:byMP;const o=map.get(mk)||z();o.b+=b;o.i+=v;o.r+=r;map.set(mk,o);
    const tt=isN?T:TP;tt.b+=b;tt.i+=v;tt.r+=r;const due=m&&m<=(isN?asofYM:asofP);if(due){const d=isN?Tdue:TPdue;d.b+=b;d.i+=v;d.r+=r}
    const k=C2[dk][i];let a=agg.get(k);if(!a){a={n:z(),p:z(),nd:z(),pd:z()};agg.set(k,a)}const t=isN?a.n:a.p;t.b+=b;t.i+=v;t.r+=r;if(due){const d=isN?a.nd:a.pd;d.b+=b;d.i+=v;d.r+=r}}
  const rate=o=>o.b?o.i/o.b:0;const pts=sp>=0?Math.round((rate(Tdue)-rate(TPdue))*100):null;
  const ms=[...new Set([...byM.keys(),...byMP.keys()])].filter(Boolean).sort();const months=[];if(ms.length)for(let k=ms[0];k<=ms[ms.length-1]&&months.length<24;k=ymAdd(k,1))months.push(k);
  const chart=faBars(months,k=>{const o=byM.get(k)||z();return[[o.i,'var(--n)'],[o.r,k<asofYM?'var(--bad)':'var(--c4)']]},{asofYM,
    tipF:k=>{const o=byM.get(k)||z(),q=byMP.get(k)||z();return`<b>Livraison demandée en ${ymLab(k)}</b><br>Commandé : ${fE(o.b)}<br>Facturé : ${fE(o.i)} (${faPct(o.i,o.b)})<br>Reste à livrer : ${fE(o.r)}`},
    legend:`<span><span class="sw" style="background:var(--n)"></span>Facturé</span><span><span class="sw" style="background:var(--bad)"></span>Pas encore facturé, date dépassée</span><span><span class="sw" style="background:var(--c4)"></span>Pas encore facturé, à venir</span>${byM.get('')?`<span class="meta">· ${fE(byM.get('').b)} sans date de livraison demandée</span>`:''}`});
  /* prévu (RDD) vs réel (mois de facture) */
  const pm=[...new Set([...[...byM.keys()].filter(Boolean),...INV.keys()])].sort();const mon=[];if(pm.length)for(let k=pm[0];k<=pm[pm.length-1]&&mon.length<24;k=ymAdd(k,1))mon.push(k);
  const months2=mon.filter(k=>(byM.get(k)?.b||0)>1||(INV.get(k)||0)>1);const mm=months2.length?months2:mon;
  const prevu=k=>byM.get(k)?.b||0,reel=k=>k<=asofYM?(INV.get(k)||0):0;
  let cP=0,cR=0;const cum=mm.map(k=>{cP+=prevu(k);cR+=reel(k);return[cP,cR]});
  const W3=Math.max(680,mm.length*64+70),H3=270,ml=56,mr=10,mt=22,mb=30,pw3=W3-ml-mr,ph3=H3-mt-mb,bw3=pw3/Math.max(1,mm.length);
  const mx3=niceMax(Math.max(1,...mm.map(k=>Math.max(prevu(k),reel(k)))));
  let s3='';for(let t=0;t<=4;t++){const v=mx3*t/4,y=mt+ph3-ph3*t/4;s3+=`<line x1="${ml}" x2="${W3-mr}" y1="${y}" y2="${y}" class="grid"/><text x="${ml-8}" y="${y+4}" text-anchor="end" class="ax">${fK(v)}€</text>`}
  mm.forEach((k,j)=>{const x=ml+j*bw3,w=bw3*.34,a=prevu(k),b=reel(k),ha=ph3*a/mx3,hb=ph3*b/mx3,d=b-a,fut=k>asofYM;
    s3+=`<g data-tip="${esc(`<b>${ymLab(k)}</b><br>Prévu (livraison demandée) : ${fE(a)}<br>${fut?'Facturé : à venir':`Facturé réellement : ${fE(b)}<br>Écart : ${d>=0?'+':'−'}${fE(Math.abs(d))}`}<br><span style="opacity:.75">Cumul prévu ${fE(cum[j][0])} · cumul facturé ${fE(cum[j][1])}</span>`)}"><rect x="${x}" y="${mt}" width="${bw3}" height="${ph3}" fill="transparent"/>
    <rect x="${x+bw3*.13}" y="${mt+ph3-ha}" width="${w}" height="${ha}" fill="var(--bar)"/>${fut?'':`<rect x="${x+bw3*.5}" y="${mt+ph3-hb}" width="${w}" height="${hb}" fill="var(--n)"/>`}
    ${!fut&&Math.abs(d)>=mx3*0.03?`<text x="${x+bw3/2}" y="${mt+ph3-Math.max(ha,hb)-5}" text-anchor="middle" class="ax" style="fill:${d>=0?'var(--good)':'var(--bad)'};font-weight:600">${d>=0?'+':'−'}${fK(Math.abs(d))}</text>`:''}
    <text x="${x+bw3/2}" y="${H3-10}" text-anchor="middle" class="ax">${ymLab(k)}</text></g>`});
  const chartPR=`<div class="scroll"><svg viewBox="0 0 ${W3} ${H3}" width="100%" style="min-width:${Math.min(W3,820)}px;display:block" role="img" aria-label="Prévu vs facturé par mois">${s3}</svg></div>
    <div class="legend" style="margin-top:6px"><span><span class="sw" style="background:var(--bar)"></span>Prévu : commandé avec livraison demandée ce mois-là (RDD)</span><span><span class="sw" style="background:var(--n)"></span>Facturé réellement ce mois-là</span><span class="meta">· chiffre = écart réel − prévu</span></div>`;
  /* matrice report */
  const rM=mm.filter(k=>X.has(k)),cM=[...new Set(rM.map(k=>X.get(k)).flatMap(r=>[...r.keys()]))].filter(k=>!k.startsWith('~')&&rM.reduce((a,r)=>a+Math.abs(X.get(r).get(k)||0),0)>500).sort();
  const cell=(v,cls)=>v>0.5?`<td class="${cls||''}">${fK(v)}</td>`:'<td class="dash">·</td>';
  const mxCell=Math.max(1,...[...X.values()].flatMap(r=>[...r.values()]));
  const heatC=(v,kind)=>v>0.5?`style="background:color-mix(in oklab,${kind} ${Math.round(8+Math.min(1,v/mxCell)*55)}%,var(--surface))"`:'';
  let early=0,onTime=0,lateI=0;
  const matRows=rM.map(r=>{const row=X.get(r);const tot=[...row.values()].reduce((a,b)=>a+b,0);
    const cs=cM.map(c=>{const v=row.get(c)||0;if(c<r)early+=v;else if(c===r)onTime+=v;else lateI+=v;const kind=c===r?'var(--n)':c<r?'var(--c2)':'var(--c4)';return v>0.5?`<td ${heatC(v,kind)} data-tip="${esc(`Livraison demandée ${ymLab(r)} → facturé ${ymLab(c)} : ${fE(v)}`)}">${fK(v)}</td>`:'<td class="dash">·</td>'}).join('');
    const nl=row.get('~late')||0,nf=row.get('~fut')||0;
    return`<tr><td class="lab"><b>${ymLab(r)}</b></td><td>${fK(tot)}</td>${cs}<td ${nl>0.5?'class="neg"':''}>${nl>0.5?fK(nl):'·'}</td><td>${nf>0.5?fK(nf):'·'}</td></tr>`}).join('');
  const matrix=`<div class="scroll"><table class="pv mtx"><thead><tr><th class="lab">Livraison demandée ↓ · facturé →</th><th>Prévu</th>${cM.map(c=>`<th>${ymLab(c)}</th>`).join('')}<th>Pas facturé, dépassé</th><th>Pas facturé, à venir</th></tr></thead><tbody>${matRows}</tbody></table></div>`;
  const totI=early+onTime+lateI;
  let typeCard='';if(F.sot.size>1||F.sot.size===0){const ty=new Map();for(const i of rows){if(C2.season[i]!==se)continue;const k=C2.sot[i];const o=ty.get(k)||{b:0,i:0,r:0};o.b+=C2.b[i];o.i+=C2.i[i];o.r+=C2.r[i];ty.set(k,o)}
    const tl=[...ty.entries()].filter(([,o])=>Math.abs(o.b)>1).sort((a,b)=>b[1].b-a[1].b);
    if(tl.length>1)typeCard=`<div class="card"><div class="card-h"><h2>Par type de commande</h2><span class="note">${n}</span></div><div class="scroll"><table class="pv"><thead><tr><th class="lab">Type</th><th>Commandé</th><th>Facturé</th><th>% facturé</th><th>Reste à livrer</th></tr></thead><tbody>${tl.map(([k,o])=>`<tr><td class="lab"><b>${esc(FA.dicts.sot[k])}</b></td><td>${fE(o.b)}</td><td>${fE(o.i)}</td><td>${faPct(o.i,o.b)}</td><td>${fE(o.r)}</td></tr>`).join('')}</tbody></table></div></div>`}
  const lab=k=>dk==='cust'?`<span class="cpair"><b class="cno">${esc(FA.dicts.cust[k])}</b><span class="ccity">${esc(FA.custCity[k]||'')}</span><span class="cgrp">${FA.custGrp[k]?'<button type="button" class="gchip" data-g="'+D.dicts.grp.indexOf(FA.custGrp[k])+'" title="Ouvrir la fiche du groupement">'+esc(FA.custGrp[k])+'</button>':''}</span></span>`:esc(FA.dicts[dk][k]);
  let list=[...agg.entries()].filter(([,a])=>a.n.b).sort((x,y)=>y[1].n.b-x[1].n.b);const hidden=list.length>40&&!FAS.all?list.length-40:0;if(hidden)list=list.slice(0,40);
  const trow=(l,a,cls)=>{const rn=rate(a.nd),rp=rate(a.pd),d=a.pd.b&&a.nd.b?Math.round((rn-rp)*100):null;return`<tr class="${cls||''}"><td class="lab">${l}</td><td>${fE(a.n.b)}</td><td>${fE(a.n.i)}</td><td><span class="dif"><i style="width:${Math.min(100,rate(a.n)*100)}%"></i></span> ${faPct(a.n.i,a.n.b)}</td><td>${fE(a.nd.b)}</td><td><b>${a.nd.b?faPct(a.nd.i,a.nd.b):'—'}</b></td><td>${a.nd.b-a.nd.i>0.5?`<span class="neg">${fE(a.nd.b-a.nd.i)}</span>`:'<span class="dash">—</span>'}</td></tr>`};
  const tot={n:T,p:TP,nd:Tdue,pd:TPdue};
  P.innerHTML=`<div class="toolbar">${faViewSeg()}${faSeasonSel(ss,se)}${famSeg()}</div>
  <div class="kgrid k4">
    ${faTile('Commandé '+n,fM(T.b),`commandes ${typeLab()}`)}
    ${faTile('Facturé',fM(T.i),`<b>${faPct(T.i,T.b)}</b> du commandé total`)}
    ${faTile('Échu au '+fD(today),fM(Tdue.b),`commandé avec une livraison demandée<br>jusqu'à fin ${ymLab(asofYM)}`)}
    ${faTile('Échu facturé',faPct(Tdue.i,Tdue.b),`${fE(Tdue.i)} facturés<br><b class="neg">${fE(Tdue.b-Tdue.i)}</b> échus pas encore facturés`,Tdue.b-Tdue.i>0.5?'down':'')}
  </div>
  ${faMemo(se)}
  <div class="card"><div class="card-b expl"><div class="pvm-t">
    <p>Sur les <b>${fE(Tdue.b)}</b> commandés en ${n} avec une livraison demandée jusqu'à fin ${ymLab(asofYM)}, <b>${fE(Tdue.i)} sont facturés (${faPct(Tdue.i,Tdue.b)})</b>. Il reste <b class="neg">${fE(Tdue.b-Tdue.i)}</b> échus à facturer, à suivre dans la vue « Reste à livrer ».</p>
    <p class="meta">Base = date de livraison demandée (RDD) sur la commande. Pas de comparaison ${p} sur cette base : dans l'extraction, les lignes déjà facturées des saisons passées n'ont plus de date de livraison demandée.</p></div></div></div>
  ${typeCard}
  <div class="card"><div class="card-h"><h2>Prévu vs facturé, mois par mois</h2><span class="note">Prévu = mois de livraison demandée (RDD) · Facturé = mois de la facture</span></div><div class="card-b">${chartPR}</div></div>
  <div class="card"><div class="card-h"><h2>Où se reportent les écarts</h2><span class="note">Ligne = mois prévu · colonne = mois où c'est réellement facturé</span></div>
    <div class="card-b expl"><div class="pvm-t"><p>Sur ${fE(totI)} facturés : <b>${faPct(onTime,totI)} le mois prévu</b>, ${faPct(lateI,totI)} un ou plusieurs mois <b>après</b> (report), ${faPct(early,totI)} <b>avant</b> le mois prévu (anticipé). Les deux dernières colonnes montrent ce qui n'est pas encore facturé.</p>
    <p class="meta"><span class="sw" style="background:var(--n)"></span> diagonale = facturé le mois prévu · <span class="sw" style="background:var(--c4)"></span> à droite = facturé en retard · <span class="sw" style="background:var(--c2)"></span> à gauche = facturé en avance. Montants en k€.</p></div></div>${matrix}</div>
  <div class="card"><div class="card-h"><h2>Détail ${n}</h2><div class="seg" id="faDim">${[['sot','Type'],['sales','Commercial'],['cg','Groupe client'],['grp','Groupement'],['cust','Client'],['fam','Famille']].map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dk===k}">${l}</button>`).join('')}</div></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">${({sot:'Type de commande',sales:'Commercial',cg:'Groupe client',grp:'Groupement',cust:'Client',fam:'Famille'})[dk]}</th><th>Commandé</th><th>Facturé</th><th>% facturé</th><th>Échu</th><th>% de l'échu facturé</th><th>Échu non facturé</th></tr></thead>
    <tbody>${list.map(([k,a])=>trow(lab(k),a)).join('')}${hidden?`<tr class="more"><td colspan="7"><button type="button" class="linkbtn" id="faAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}${trow('Total',tot,'tot')}</tbody></table></div>
    <div class="card-b"><p class="meta" style="margin:0">Échu = commandé dont la livraison demandée tombe jusqu'à fin ${ymLab(asofYM)}.</p></div></div>`;
  bindFam();bindFaView(P);
  $('#faSeason').onchange=e=>{FAS.season=+e.target.value;factPanel(P)};
  $('#faDim').onclick=e=>{const b=e.target.closest('button');if(b){FAS.dim=b.dataset.d;FAS.all=false;factPanel(P)}};
  const a=$('#faAll');if(a)a.onclick=()=>{FAS.all=true;factPanel(P)};
}
function ralPanel(P){
  const ss=FA.dicts.season,se=FAS.season,sName=ss[se],n=sLab(sName);
  if(!ROB){P.innerHTML=`<div class="toolbar">${faViewSeg()}</div><div class="card"><div class="card-b">Données non chargées.</div></div>`;bindFaView(P);return}
  const today=ROB.asof,asofYM=today.slice(0,7),C3=ROB.C,rd=ROB.dicts.rdd;const si=ROB.dicts.season.indexOf(sName);
  const rows=robRows().filter(i=>C3.season[i]===si);
  const eom=(()=>{const d=new Date(+asofYM.slice(0,4),+asofYM.slice(5,7),0);return asofYM+'-'+String(d.getDate()).padStart(2,'0')})();
  let tot=0,late=0,cur=0,fut=0,none=0;const byM=new Map();
  for(const i of rows){const r=C3.r[i],d=rd[C3.rdd[i]];tot+=r;if(!d){none+=r;continue}if(d<today)late+=r;else if(d<=eom)cur+=r;else fut+=r;const m=d.slice(0,7);const o=byM.get(m)||{l:0,f:0};if(d<today)o.l+=r;else o.f+=r;byM.set(m,o)}
  const ms=[...byM.keys()].sort();const months=[];if(ms.length)for(let k=ms[0];k<=ms[ms.length-1]&&months.length<24;k=ymAdd(k,1))months.push(k);
  const sel=FAS.month;
  const chart=faBars(months,k=>{const o=byM.get(k)||{l:0,f:0};return[[o.l,'var(--bad)'],[o.f,'var(--c4)']]},{asofYM,sel,
    tipF:k=>{const o=byM.get(k)||{l:0,f:0};return`<b>Livraison demandée en ${ymLab(k)}</b><br>Reste à livrer : ${fE(o.l+o.f)}${o.l?`<br>dont date dépassée : ${fE(o.l)}`:''}<br><i>Cliquez pour filtrer le tableau</i>`},
    legend:`<span><span class="sw" style="background:var(--bad)"></span>Date de livraison demandée dépassée</span><span><span class="sw" style="background:var(--c4)"></span>À venir</span>${sel?` <button type="button" class="linkbtn" id="ralClr">Retirer le filtre ${ymLab(sel)} ✕</button>`:''}`});
  /* tableau */
  const dk=FAS.rdim||'cust';const keyOf=i=>C3[dk][i];
  const agg=new Map();for(const i of rows){const d=rd[C3.rdd[i]]||'';if(sel&&d.slice(0,7)!==sel)continue;const k=keyOf(i);let a=agg.get(k);if(!a){a={r:0,q:0,l:0,old:'9',items:new Map()};agg.set(k,a)}
    const r=C3.r[i];a.r+=r;a.q+=C3.q[i];if(d&&d<today)a.l+=r;if(d&&d<a.old)a.old=d;
    if(dk==='cust'){const it=C3.item[i];let o=a.items.get(it);if(!o){o={r:0,q:0,dmin:'9',dmax:''};a.items.set(it,o)}o.r+=r;o.q+=C3.q[i];if(d){if(d<o.dmin)o.dmin=d;if(d>o.dmax)o.dmax=d}}}
  const q=(FAS.rq||'').toLowerCase();
  const labT=k=>dk==='cust'?`${ROB.dicts.cust[k]} ${FA.custCity[FA.dicts.cust.indexOf(ROB.dicts.cust[k])]||''}`:dk==='item'?`${ROB.dicts.item[k]} ${ROB.itemDesc[k]}`:ROB.dicts[dk][k];
  const lab=k=>{if(dk==='cust'){const ci=ROB.ci[k];return ci>=0?cLabelTxt(ci):esc(ROB.dicts.cust[k])}if(dk==='item')return`<span class="refpair"><b class="refc">${esc(ROB.dicts.item[k])}</b> <span class="refd">${esc(ROB.itemDesc[k]||'—')}</span></span>`;return esc(ROB.dicts[dk][k])};
  let list=[...agg.entries()].filter(([k,a])=>Math.abs(a.r)>0.5&&(!q||labT(k).toLowerCase().includes(q))).sort((x,y)=>FAS.rsort==='late'?y[1].l-x[1].l||y[1].r-x[1].r:y[1].r-x[1].r);
  const total=list.length,hidden=total>50&&!FAS.all?total-50:0;if(hidden)list=list.slice(0,50);
  const exp=FAS.exp;
  const arrTxt=(c,it)=>{const v=ROB.arr.get(ROB.dicts.cust[c]+'|'+ROB.dicts.item[it]);if(!v)return'<span class="mutedv">—</span>';if(v.max&&v.max>today)return`<span class="tag t-wait">attendu le ${fD(v.max)}</span>`;if(v.max)return`<span class="tag t-chk">arrivé le ${fD(v.max)}</span>`;return'<span class="mutedv">pas d\'arrivée prévue</span>'};
  const body=list.map(([k,a])=>{const open=dk==='cust'&&exp.has(k);let h=`<tr><td class="lab">${dk==='cust'?`<button type="button" class="tg" data-x="${k}" aria-expanded="${open}"><i>${open?'−':'+'}</i></button> `:''}${lab(k)}</td><td><b>${fE(a.r)}</b></td><td>${fN(Math.round(a.q))}</td><td>${a.l>0.5?`<span class="neg">${fE(a.l)}</span>`:'<span class="dash">—</span>'}</td><td>${a.old!=='9'?fD(a.old):'—'}</td>${dk==='cust'?'<td></td>':''}</tr>`;
    if(open){const its=[...a.items.entries()].sort((x,y)=>y[1].r-x[1].r);if(its.length>20)h+='';its.slice(0,20).forEach(([it,o])=>{const lt=o.dmin!=='9'&&o.dmin<today;h+=`<tr class="sub"><td class="lab" style="padding-left:40px"><b>${esc(ROB.dicts.item[it])}</b> <span class="mutedv">${esc(ROB.itemDesc[it]||'—')}</span></td><td>${fE(o.r)}</td><td>${fN(Math.round(o.q))}</td><td>${lt?'<span class="chip down">dépassée</span>':''}</td><td>${o.dmin!=='9'?fD(o.dmin)+(o.dmax!==o.dmin?' → '+fD(o.dmax):''):'—'}</td><td>${arrTxt(k,it)}</td></tr>`});if(its.length>20)h+=`<tr class="more"><td colspan="6" style="padding-left:40px">… ${fN(its.length-20)} autres références, voir la vue Référence ou l'export Excel</td></tr>`}
    return h}).join('');
  const dimL={cust:'Client',item:'Référence',sot:'Type',sales:'Commercial',cg:'Groupe client',grp:'Groupement'};
  P.innerHTML=`<div class="toolbar">${faViewSeg()}${faSeasonSel(ss,se)}${famSeg()}</div>
  <div class="kgrid k4">
    ${faTile('Reste à livrer '+n,fM(tot),'commandé, pas encore facturé')}
    ${faTile('Date dépassée',fM(late),`livraison demandée avant le ${fD(today)}`,late>0.5?'down':'')}
    ${faTile('À livrer ce mois',fM(cur),`livraison demandée du ${fD(today)} au ${fD(eom)}`)}
    ${faTile('Mois suivants',fM(fut),`livraison demandée après ${ymLab(asofYM)}${none>0.5?`<br>+ ${fM(none)} sans date`:''}`)}
  </div>
  ${faMemo(se)}
  <div class="card"><div class="card-b expl"><div class="pvm-t"><p><b>${fE(tot)}</b> commandés en ${n} ne sont pas encore facturés. <b class="neg">${fE(late)}</b> ont une date de livraison demandée déjà passée : ce sont les commandes à suivre en priorité.</p>
    <p class="meta">Base = date de livraison demandée (RDD) sur la commande. Pour chaque référence d'un client (bouton +), la dernière colonne reprend la date d'entrée en stock prévue du backlog ADV : « attendu le … » = marchandise pas encore reçue, « arrivé le … » ou « pas d'arrivée prévue » = a priori en stock, à voir avec l'ADV.</p></div></div></div>
  <div class="card"><div class="card-h"><h2>Reste à livrer par mois de livraison demandée</h2><span class="note">Cliquez sur une barre pour filtrer le tableau</span></div><div class="card-b" id="ralC">${chart}</div></div>
  <div class="card"><div class="card-h"><h2>Détail${sel?' · livraison demandée en '+ymLab(sel):''}</h2><div class="seg" id="ralDim">${Object.entries(dimL).map(([k,l])=>`<button type="button" data-d="${k}" aria-pressed="${dk===k}">${l}</button>`).join('')}</div></div>
    <div class="card-b" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><input type="search" id="ralQ" placeholder="Rechercher…" value="${esc(FAS.rq||'')}" style="min-width:260px"><div class="seg" id="ralSort"><button type="button" data-s="r" aria-pressed="${FAS.rsort!=='late'}">Trier par montant</button><button type="button" data-s="late" aria-pressed="${FAS.rsort==='late'}">Trier par date dépassée</button></div><span class="meta">${fN(total)} lignes</span></div>
    <div class="scroll"><table class="pv"><thead><tr><th class="lab">${dimL[dk]}</th><th>Reste à livrer</th><th>Pièces</th><th>dont date dépassée</th><th>Livraison demandée${dk==='cust'?'':' la plus ancienne'}</th>${dk==='cust'?'<th class="lab">Entrée en stock (ADV)</th>':''}</tr></thead>
    <tbody>${body||'<tr class="more"><td colspan="6">Rien à livrer pour cette sélection.</td></tr>'}${hidden?`<tr class="more"><td colspan="6"><button type="button" class="linkbtn" id="faAll">Afficher les ${fN(hidden)} autres</button></td></tr>`:''}</tbody></table></div></div>`;
  bindFam();bindFaView(P);
  $('#faSeason').onchange=e=>{FAS.season=+e.target.value;FAS.month=null;factPanel(P)};
  $('#ralC').onclick=e=>{const g=e.target.closest('[data-m]');if(g){FAS.month=FAS.month===g.dataset.m?null:g.dataset.m;FAS.all=false;factPanel(P)}};
  const c=$('#ralClr');if(c)c.onclick=e=>{e.stopPropagation();FAS.month=null;factPanel(P)};
  $('#ralDim').onclick=e=>{const b=e.target.closest('button');if(b){FAS.rdim=b.dataset.d;FAS.all=false;factPanel(P)}};
  $('#ralSort').onclick=e=>{const b=e.target.closest('button');if(b){FAS.rsort=b.dataset.s;factPanel(P)}};
  let qt;$('#ralQ').oninput=e=>{clearTimeout(qt);qt=setTimeout(()=>{FAS.rq=e.target.value;FAS.all=false;factPanel(P);const el=$('#ralQ');el.focus();el.setSelectionRange(el.value.length,el.value.length)},250)};
  P.querySelectorAll('.tg').forEach(b=>b.onclick=()=>{const k=+b.dataset.x;exp.has(k)?exp.delete(k):exp.add(k);factPanel(P)});
  const a=$('#faAll');if(a)a.onclick=()=>{FAS.all=true;factPanel(P)};
}

function aboutHtml(){
  return`<div class="card"><div class="card-b about"><div class="cols">
  <div><h2>Source et périmètre</h2><ul>
    <li><b>Carnet, facturé, reste à facturer</b> : vue <b>LCS_X3SALESX</b> du cube SEI, toutes sociétés (LCSI, Espagne, Italie), saisons 2024-01-SS à 2027-02-FW. Le filtre <b>Sales order type</b> est sur <b>PS</b> par défaut.</li>
    <li>Le compte <b>4437</b> (Intersport France) est classé en <b>Centrale Intersport</b> (groupe client et groupement) et peut être exclu d'un clic avec la case « Exclure la centrale Intersport ».</li>
    <li>Le filtre <b>Périmètre BOH</b> est actif par défaut. Les commandes X3 n'ont pas de drapeau de périmètre : un client est dans le périmètre si son business model l'est (hors E-COMMERCE, FRANCE OTHER, DOTATIONS, GROUP COMPANIES, DESTOCKEUR, DISCOUNTERS, RETAIL BUSINESS et clients sans business model). Règle déduite des données, à confirmer par le Sales Manager. Retirez le filtre pour tout inclure.</li>
    <li>La PLV (famille POP) est exclue de tous les chiffres. Les valeurs vides apparaissent sous « (non renseigné) ».</li>
    <li><b>Hiérarchie client</b> (business model, groupe, groupement, manager, commercial) : table LCS_CUSTOMER, valeurs actuelles du client.</li>
  </ul></div>
  <div><h2>Définitions</h2><ul>
    <li><b>Porte</b> : un client (n° client) avec un CA &gt; 0 sur la saison, pour les types de commande sélectionnés.</li>
    <li><b>Comparable</b> : porte avec une PS &gt; 0 sur les deux saisons comparées (avec le filtre par défaut sur PS). Le calcul suit les filtres actifs.</li>
    <li><b>Porte perdue</b> : CA &gt; 0 en N-1 et aucune commande en N. <b>Nouvelle porte</b> : l'inverse. <b>Évo</b> : (N − N-1) / N-1, « nouveau » quand N-1 vaut zéro.</li>
    <li><b>Pré-saison</b> = commandes de type PS ; <b>réassort</b> = type RE, passées pendant la saison.</li>
    <li><b>RAL</b> : reste à livrer. <b>Écoulement</b> : vendu ÷ (vendu + stock magasin). <b>Couverture</b> : stock ÷ ventes moyennes des dernières semaines.</li>
    <li><b>Réassort conseillé</b> : formule du fichier Push Réassort, (vendu + stock) × écoulement ÷ (0,03 × semaines depuis la première vente ou le premier stock) × 0,33.</li>
  </ul></div>
  <div><h2>Autres sources</h2><ul>
    <li><b>Remises et conditions de paiement</b> : vue PRODXXX_CLIENT_INTRANE (celle de Comptes Clients).</li>
    <li><b>Backlog et dates d'arrivée</b> : Backlog Clients de l'intranet (X3) ; la date prévue vient du transit intersites, à défaut du plan transport SS27.</li>
    <li><b>Avoirs</b> : CONSO_INVOICES (type CREDITMEMO). La liste « Derniers avoirs » ne montre que les avoirs de 100 € et plus.</li>
    <li><b>Annulations</b> : règles de la stat État des commandes clients (quantités annulées, converties en euros au cours X3 du jour).</li>
    <li><b>Sell-out Intersport et Sport 2000</b> : ventes et stocks hebdomadaires du cube (LCS_SELLOUT_SALES), limités aux articles du reporting Push Réassort de la saison ; RAL, préparation et réassort conseillé viennent des fichiers Push Réassort de la dernière semaine déposés sur SharePoint (saisons en cours seulement).</li>
    <li><b>Photos</b> : site lecoqsportif.com ; les articles de la collection à venir n'y sont pas encore.</li>
  </ul></div>
  <div><h2>Mise à jour</h2><ul>
    <li>Les données sont reconstruites chaque matin ; elles sont à jour au <b>${fD(ASOF)}</b>.</li>
    <li>Le sell-out suit les fichiers Push Réassort déposés chaque lundi sur SharePoint.</li>
    <li>Export PowerPoint : bouton en haut à droite. La présentation d'un client, groupement, groupe client ou commercial suit les filtres Sales order type et Périmètre BOH et couvre toutes les familles ; « Sélection actuelle » reprend tous les filtres.</li>
    <li>Le bouton « Requêtes utilisées » en bas de chaque onglet donne la requête exacte de chaque chiffre.</li>
  </ul></div>
  </div></div></div>`;
}

function refresh(){{const sub=document.querySelector('.mast .sub');if(sub){let el=document.getElementById('curSeason');if(!el){el=document.createElement('span');el.id='curSeason';el.className='cur-sub';sub.appendChild(el)}el.innerHTML=`<span class="cur-tag">Saison en cours ${sLab(curSeasonName())}</span>`}}compute();renderKPIs();renderPanel();updAlertBadge()}

/* ================= intégration intranet (hors template d'origine) =================
   Le template embarquait ses données dans la page. Ici elles viennent du serveur : on les télécharge une à une
   (jamais en parallèle : trois requêtes lourdes simultanées ont déjà fait tomber la préprod en 524), on les range
   dans des balises <script type="application/json" id="…"> et le code d'origine les relit comme avant. */
const SSG=(()=>{try{return JSON.parse(document.getElementById('ssgEndpoints').textContent)}catch(e){return{enabled:[],data:{}}}})();
const SSG_Q=(()=>{try{return JSON.parse(document.getElementById('ssgQueries').textContent)}catch(e){return{}}})();
async function loadServerData(){
  const names=Object.keys(SSG.data||{}),el=$('#panel');
  for(let i=0;i<names.length;i++){
    el.innerHTML=`<div class="loading">Chargement des données… (${i+1} / ${names.length})</div>`;
    const r=await fetch(SSG.data[names[i]],{credentials:'same-origin'});
    if(!r.ok)throw new Error('Source « '+names[i]+' » : erreur '+r.status);
    const tag=document.createElement('script');tag.type='application/json';tag.id=names[i];tag.textContent=await r.text();document.body.appendChild(tag);
    if(names[i]==='remises'){try{REM=JSON.parse(tag.textContent)}catch(e){}} /* REM est lu au chargement du script dans le template d'origine : on le renseigne ici une fois le jeu reçu */
  }
}
/* Onglets livrés un par un : les autres sont masqués tant que leurs données ne sont pas branchées. */
function applyTabGate(){
  const on=SSG.enabled||[];if(!on.length)return;
  document.querySelectorAll('#tabs .tab').forEach(b=>{b.hidden=!on.includes(b.dataset.t);b.setAttribute('aria-selected',b.dataset.t===on[0])});
  S.tab=on[0];
}
/* Bouton « Requêtes » tout en bas de chaque page : titre, source et SQL exact de chaque requête de l'onglet. */
function queriesBox(){
  const qs=SSG_Q[S.tab]||[];if(!qs.length)return'';
  return`<div class="qbox" id="qBox"><button type="button" class="qbtn" id="qBtn" aria-expanded="false">Requêtes utilisées (${qs.length})</button>
    <div class="qbody" id="qBody" hidden>${qs.map(q=>`<div class="qitem"><h3>${esc(q.title)} <span class="qsrc">${esc(q.source)}</span></h3><p class="qnote">${esc(q.note)}</p><div class="qfile">${esc(q.file)}</div>
      <div class="qpre"><button type="button" class="qcopy">Copier</button><pre>${esc(q.sql)}</pre></div></div>`).join('')}</div></div>`;
}
(()=>{
  const P=document.getElementById('panel');let busy=false;
  const place=()=>{
    if(busy)return;busy=true;
    const old=document.getElementById('qBox');const open=old&&!document.getElementById('qBody').hidden;if(old)old.remove();
    const html=queriesBox();if(html){P.insertAdjacentHTML('beforeend',html);if(open){$('#qBody').hidden=false;$('#qBtn').setAttribute('aria-expanded','true')}}
    busy=false;
  };
  new MutationObserver(()=>{if(!busy&&!(P.lastElementChild&&P.lastElementChild.id==='qBox'&&P.dataset.qtab===S.tab))place();P.dataset.qtab=S.tab}).observe(P,{childList:true});
  P.addEventListener('click',e=>{
    const b=e.target.closest('#qBtn');if(b){const body=$('#qBody');body.hidden=!body.hidden;b.setAttribute('aria-expanded',String(!body.hidden));return}
    const c=e.target.closest('.qcopy');if(c&&navigator.clipboard){navigator.clipboard.writeText(c.parentElement.querySelector('pre').textContent).then(()=>{c.textContent='Copié';setTimeout(()=>c.textContent='Copier',1500)})}
  });
})();
/* Le template lisait l'identité de l'utilisateur et les notes partagées dans l'outil Claude où il a été créé
   (window.claude.use('user' | 'db')). Petit adaptateur qui offre la même interface, branché sur l'intranet :
   identité = compte connecté, notes = table MySQL suivi_sellin_note (voir SuiviSellinNotesController). */
(()=>{
  const E=SSG;if(window.claude||!E.notes)return;
  const j=async(u,o)=>{const r=await fetch(u,Object.assign({credentials:'same-origin'},o));if(!r.ok){const e=new Error('http '+r.status);e.code=r.status===403?'not_granted':'error';throw e}return r.json()};
  let me=null;const getMe=async()=>me||(me=await j(E.me));
  const names=new Map();let cb=null,timer=null;
  async function pull(){const list=await j(E.notes);list.forEach(n=>names.set(n.author,n.authorName));
    if(cb)cb({docs:list.map(n=>({id:n.id,data:()=>({c:n.c,text:n.text,author:n.author,at:n.at})}))})}
  const write=(method,url,body)=>j(url,{method,headers:{'Content-Type':'application/json','X-CSRF-Token':E.csrf},body:body?JSON.stringify(body):undefined});
  const db={collection:()=>({
    onSnapshot:f=>{cb=f;pull().catch(()=>{});clearInterval(timer);timer=setInterval(()=>pull().catch(()=>{}),60000)},
    add:async o=>{await write('POST',E.notes,{c:o.c,text:o.text});await pull()},
    doc:id=>({delete:async()=>{await write('DELETE',E.notes+'/'+id);await pull()}})})};
  const user={id:async()=>(await getMe()).id,me:async()=>getMe(),
    profiles:async ids=>{const m=await getMe();const o={};ids.forEach(i=>o[i]={name:names.get(i)||(i===m.id?m.name:'')});return o}};
  window.claude={use:async n=>n==='db'?db:n==='user'?user:null};
})();
loadServerData().then(()=>{applyTabGate();return init()}).catch(err=>{$('#panel').innerHTML=`<div class="loading">Les données n'ont pas pu être chargées (${esc(err.message)}). Réessayez dans un instant ; si le problème persiste, prévenez l'équipe IT.</div>`});
/* appelée par les attributs onclick du HTML généré */
window.fpsOpen=fpsOpen;
})();

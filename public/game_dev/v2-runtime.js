(()=>{
'use strict';
const STORE='pitmetric.openwheel26.v2';
const $=s=>document.querySelector(s);
const $$=s=>Array.from(document.querySelectorAll(s));
const clamp=(v,a,b)=>Math.max(a,Math.min(b,v));
let corrected=false,lastLivePos=null;

document.addEventListener('keydown',e=>{
  if(e.repeat||document.querySelector('#raceGame.hidden')) return;
  const keys={Digit1:'balanced',Digit2:'push',Digit3:'save',KeyB:'boost',KeyD:'defend',KeyP:'pit'};
  if(keys[e.code]){
    const btn=document.querySelector(`[data-cmd="${keys[e.code]}"]`);
    if(btn){e.preventDefault();btn.click();}
  }
  if(e.code==='KeyC'){
    const btn=$('#cameraBtn');
    if(btn){e.preventDefault();btn.click();}
  }
});

function rememberLivePosition(){
  if($('#raceGame')?.classList.contains('hidden')||!$('#resultOverlay')?.classList.contains('hidden')) return;
  const rows=$$('#leaderboardRows .lbrow');
  const index=rows.findIndex(r=>r.classList.contains('player'));
  if(index>=0) lastLivePos=index+1;
}
setInterval(rememberLivePosition,80);

function points(pos,type,series){
  if(type==='race') return [25,18,15,12,10,8,6,4,2,1][pos-1]||0;
  if(type==='sprint') return series==='F1'?([8,7,6,5,4,3,2,1][pos-1]||0):([10,8,6,5,4,3,2,1][pos-1]||0);
  return 0;
}
function gains(pos,teamPerf,type){
  const target=clamp(Math.round((101-teamPerf)*.7)+4,3,20);
  const delta=target-pos;
  return {
    rep:type==='practice'?0:clamp(Math.round(delta/2)+(pos<=target?1:0),-2,5),
    trust:clamp(Math.round(delta/3),-2,4),
    partner:pos<=target?1:0
  };
}
function sessionType(){
  const title=($('#resultTitle')?.textContent||'').toLowerCase();
  if(title.includes('sprint')) return 'sprint';
  if(title.includes('grand prix')||title.includes('feature race')) return 'race';
  return null;
}
function correctResult(){
  const type=sessionType();
  if(!type||!lastLivePos) return;
  const actual=lastLivePos;
  const shown=Number(($('#resultPos')?.textContent||'').replace(/\D/g,''));
  if(!shown||shown===actual) return;
  let s;
  try{s=JSON.parse(localStorage.getItem(STORE));}catch{return;}
  if(!s||!s.team) return;
  const oldPts=points(shown,type,s.series),newPts=points(actual,type,s.series);
  const oldG=gains(shown,s.team.perf,type),newG=gains(actual,s.team.perf,type);
  s.points=Math.max(0,(s.points||0)-oldPts+newPts);
  s.reputation=clamp((s.reputation||50)-oldG.rep+newG.rep,1,99);
  s.teamTrust=clamp((s.teamTrust||50)-oldG.trust+newG.trust,1,99);
  s.partnerTrust=clamp((s.partnerTrust||50)-oldG.partner+newG.partner,1,99);
  if(s.lastResult){s.lastResult.pos=actual;s.lastResult.points=newPts;}
  localStorage.setItem(STORE,JSON.stringify(s));
  $('#resultPos').textContent=`P${actual}`;
  $('#resultPoints').textContent=`+${newPts}`;
  $('#resultRep').textContent=`${newG.rep>=0?'+':''}${newG.rep}`;
  $('#resultTrust').textContent=`${newG.trust>=0?'+':''}${newG.trust}`;
  corrected=true;
}

const result=$('#resultOverlay');
if(result){
  new MutationObserver(()=>{
    if(!result.classList.contains('hidden')) setTimeout(correctResult,0);
  }).observe(result,{attributes:true,attributeFilter:['class']});
}
$('#resultContinueBtn')?.addEventListener('click',()=>{
  if(corrected) setTimeout(()=>location.reload(),0);
  lastLivePos=null;
});
$('#startSessionBtn')?.addEventListener('click',()=>{corrected=false;lastLivePos=null;});
})();

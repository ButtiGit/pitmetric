(()=>{
'use strict';
const script=document.createElement('script');
script.src='/game_dev/v3.js?v=3';
script.defer=true;
script.onerror=()=>console.error('Open Wheel Career V3 failed to load');
document.body.appendChild(script);
})();
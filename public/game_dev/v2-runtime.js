(()=>{
'use strict';
const load=(src,onload)=>{const s=document.createElement('script');s.src=src;s.defer=true;s.onload=onload||null;s.onerror=()=>console.error('Open Wheel Career extension failed to load:',src);document.body.appendChild(s);};
load('/game_dev/v3.js?v=4',()=>load('/game_dev/v4.js?v=1'));
})();
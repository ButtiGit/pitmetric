(() => {
    'use strict';

    const D = window.GameDevData;
    const STORAGE_KEY = 'pitmetric.openwheel26.v2';
    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => Array.from(document.querySelectorAll(selector));
    const clamp = (value,min,max) => Math.max(min,Math.min(max,value));
    const rand = (min,max) => Math.random()*(max-min)+min;
    const pick = (items) => items[Math.floor(Math.random()*items.length)];
    const escapeHtml = (value) => String(value == null ? '' : value).replace(/[&<>"']/g,(char)=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));

    let state = emptyState();
    let timer = null;
    let toastTimer = null;
    let radioQuestion = null;

    function emptyState(){
        return {
            created:false,
            role:'driver',
            name:'',
            nation:'Italia',
            history:'rookie',
            series:null,
            team:null,
            partner:null,
            engineer:null,
            skills:{},
            skillDeltas:{},
            overall:0,
            reputation:18,
            roundIndex:0,
            sessionIndex:0,
            weekends:0,
            seasonPoints:0,
            wins:0,
            podiums:0,
            relationships:{driver:58,engineer:58,team:55,morale:68},
            weekend:{active:false,completed:false,result:null,qualifying:null,sessions:[]},
            race:{active:false,finished:false,speed:1,elapsed:0,lap:0,totalLaps:0,cars:[],pace:'balanced',attack:false,overtake:false,pitRequested:false,safetyCar:false,weather:'dry',temperature:26,feed:[],sessionName:'',usedCompounds:[]}
        };
    }

    function save(){
        if(!state.created) return;
        localStorage.setItem(STORAGE_KEY,JSON.stringify(state));
        $('#saveState').textContent='salvato';
        window.setTimeout(()=>{$('#saveState').textContent='salvataggio locale';},900);
    }

    function load(){
        try{
            const stored=JSON.parse(localStorage.getItem(STORAGE_KEY));
            if(!stored||!stored.created) return false;
            state=Object.assign(emptyState(),stored);
            state.relationships=Object.assign(emptyState().relationships,stored.relationships||{});
            state.weekend=Object.assign(emptyState().weekend,stored.weekend||{});
            state.race=Object.assign(emptyState().race,stored.race||{}, {active:false,finished:false,cars:[],feed:stored.race?.feed||[]});
            $('#careerWizard').hidden=true;
            return true;
        }catch(error){
            return false;
        }
    }

    function skillDefs(role=state.role){return D.skills[role];}
    function calendar(){return D.calendars[state.series]||D.calendars.F2;}
    function teams(){return D.teams[state.series]||D.teams.F2;}
    function round(){return calendar()[clamp(state.roundIndex,0,calendar().length-1)];}
    function team(){return teams().find((item)=>item.name===state.team)||teams()[0];}
    function initials(name){return String(name||'--').split(/\s+/).slice(0,2).map((part)=>part.charAt(0)).join('').toUpperCase();}
    function overall(skills=state.skills){const values=Object.values(skills).map(Number);return values.length?Math.round(values.reduce((sum,value)=>sum+value,0)/values.length):0;}
    function relationTarget(){return state.role==='driver'?'engineer':'driver';}

    function showToast(message){
        const toast=$('#toast');
        toast.textContent=message;
        toast.classList.add('show');
        window.clearTimeout(toastTimer);
        toastTimer=window.setTimeout(()=>toast.classList.remove('show'),2400);
    }

    function growSkill(key,amount){
        if(!(key in state.skills)) return;
        state.skills[key]=clamp(Number(state.skills[key])+amount,1,99);
        state.skillDeltas[key]=(state.skillDeltas[key]||0)+amount;
    }

    function changeRelationship(key,amount){state.relationships[key]=clamp((state.relationships[key]||50)+amount,0,100);}

    function renderAll(){
        if(!state.created) return;
        state.overall=overall();
        renderIdentity();
        renderSkills();
        renderRelationships();
        renderHero();
        renderCalendar();
        renderWeekend();
        renderStrategy();
        renderFeed();
        renderRadioHeader();
    }

    function renderIdentity(){
        $('#avatar').textContent=initials(state.name);
        $('#playerName').textContent=state.name;
        $('#playerRole').textContent=(state.role==='driver'?'Pilota':'Ingegnere di gara')+' - '+state.team;
        $('#overallValue').textContent=state.overall;
        $('#overallRing').style.setProperty('--score',String(state.overall));
        $('#reputationValue').textContent=String(state.reputation);
        $('#seriesValue').textContent=state.series;
        $('#weekendsValue').textContent=String(state.weekends);
        $('#skillOverall').textContent='OVR '+state.overall;
    }

    function renderSkills(){
        $('#skillsList').innerHTML=skillDefs().map(([key,label])=>{
            const value=Math.round(state.skills[key]||0);
            const delta=state.skillDeltas[key]||0;
            return '<div class="skill-item"><div><div class="skill-copy"><span>'+escapeHtml(label)+'</span><span>'+value+'</span></div><div class="meter"><span style="width:'+value+'%"></span></div></div><div class="skill-delta">'+(delta>0?'+'+delta.toFixed(1):'')+'</div></div>';
        }).join('');
    }

    function renderRelationships(){
        const counterpart=state.role==='driver'?'Ingegnere - '+state.engineer:'Pilota - '+state.partner;
        const items=[
            [counterpart,state.relationships[relationTarget()]],
            ['Fiducia del team',state.relationships.team],
            ['Morale',state.relationships.morale]
        ];
        $('#relationshipPanel').innerHTML=items.map(([label,value])=>'<div class="relationship-item"><div><label>'+escapeHtml(label)+'</label><div class="meter"><span style="width:'+Math.round(value)+'%"></span></div></div><div class="relationship-score">'+Math.round(value)+'</div></div>').join('');
        $('#trustTag').textContent='Fiducia '+Math.round(state.relationships[relationTarget()]);
    }

    function renderHero(){
        const current=round();
        const currentTeam=team();
        $('#heroKicker').textContent=state.series+' - Round '+current.round+'/'+calendar().length;
        $('#heroTitle').textContent=current.name+'.';
        $('#heroDescription').textContent=state.role==='driver'
            ? 'Hai il sedile '+currentTeam.short+'. Lavora con '+state.engineer+': ritmo, gomme, feedback e fiducia contano quanto la posizione finale.'
            : 'Sei al pit wall '+currentTeam.short+' con '+state.partner+'. Leggi dati e gara, fai le chiamate e costruisci una relazione che regga sotto pressione.';
        const format=current.special?'Formato speciale Baku':current.sprint?(state.series==='F1'?'Sprint weekend':'Sprint e Feature'):'Weekend standard';
        $('#heroTags').innerHTML=['Team '+currentTeam.name,current.date,format,'Rep '+state.reputation,'Punti '+state.seasonPoints].map((label)=>'<span class="tag">'+escapeHtml(label)+'</span>').join('');
    }

    function sessionsFor(current){
        if(state.series==='F1') return current.sprint?['FP1','Sprint Qualifying','Sprint','Qualifying','Race']:['FP1','FP2','FP3','Qualifying','Race'];
        if(current.special) return ['Practice','Qualifying 1','Qualifying 2','Sprint','Feature Race 1','Feature Race 2'];
        return ['Practice','Qualifying','Sprint','Feature Race'];
    }

    function isRaceSession(session){return /Sprint|Race/i.test(session);}
    function isQualifying(session){return /Qualifying/i.test(session);}

    function renderWeekend(){
        const current=round();
        const sessions=state.weekend.active&&state.weekend.sessions.length?state.weekend.sessions:sessionsFor(current);
        $('#sessionStrip').innerHTML=sessions.map((session,index)=>{
            const className=state.weekend.active&&index<state.sessionIndex?'done':state.weekend.active&&index===state.sessionIndex?'current':'';
            const status=index<state.sessionIndex?'completata':index===state.sessionIndex&&state.weekend.active?'corrente':'sessione';
            return '<div class="session-chip '+className+'"><small>'+status+'</small><strong>'+escapeHtml(session)+'</strong></div>';
        }).join('');
        $('#circuitName').textContent=current.name+' - '+current.venue;
        $('#circuitMeta').textContent=current.date+' - '+current.laps+' giri GP';
        [$('#trackShadow'),$('#trackRoad'),$('#trackEdge'),$('#trackGuide')].forEach((path)=>path.setAttribute('d',current.path));
        $('#totalLapsValue').textContent=String(state.race.active||state.race.finished?state.race.totalLaps:current.laps);

        if(!state.weekend.active){
            $('#weekendAction').innerHTML='<div><strong>Il paddock e pronto</strong><span>Prove, qualifica e gara influenzeranno abilita, reputazione e rapporti.</span></div><button class="button button-primary" id="startWeekend" type="button">Inizia weekend</button>';
            $('#startWeekend').addEventListener('click',startWeekend);
            resetTrack();
        }else if(!state.race.active){
            const session=state.weekend.sessions[state.sessionIndex];
            const race=isRaceSession(session);
            $('#weekendAction').innerHTML='<div><strong>'+escapeHtml(session)+'</strong><span>'+(race?'Vai in griglia e gestisci la sessione in tempo reale.':isQualifying(session)?'Il risultato combina abilita, auto e pressione.':'Impara la pista, migliora il setup e costruisci fiducia.')+'</span></div><button class="button button-primary" id="runSession" type="button">'+(race?'Vai in griglia':'Completa sessione')+'</button>';
            $('#runSession').addEventListener('click',runSession);
            if(!state.race.finished) resetTrack();
        }else{
            $('#weekendAction').innerHTML='<div><strong>Sessione live</strong><span>Segui timing, gomme, energia, meteo e radio. Le scelte hanno conseguenze.</span></div><button class="button button-secondary" id="pauseRace" type="button">'+(timer?'Pausa':'Riprendi')+'</button>';
            $('#pauseRace').addEventListener('click',()=>timer?pauseRace():resumeRace());
        }
        renderTiming();
        renderTelemetry();
    }

    function resetTrack(){
        $('#carsLayer').innerHTML='';
        $('#liveLabel').textContent='Stand-by';
        $('#raceState').textContent='STAND-BY';
        $('#lapValue').textContent='0';
        $('#positionValue').textContent='--';
        $('#gapValue').textContent='--';
    }

    function startWeekend(){
        const current=round();
        state.weekend={active:true,completed:false,result:null,qualifying:null,sessions:sessionsFor(current)};
        state.sessionIndex=0;
        state.skillDeltas=Object.fromEntries(Object.keys(state.skills).map((key)=>[key,0]));
        addRadio('team','Briefing completato. Prima priorita: costruire una baseline pulita e capire il degrado.');
        addFeed('system','Round '+current.round+': '+current.name+' iniziato.');
        save();
        renderAll();
    }

    function runSession(){
        const session=state.weekend.sessions[state.sessionIndex];
        if(isRaceSession(session)){startRace(session);return;}
        const currentTeam=team();
        const quality=state.role==='driver'
            ?((state.skills.pace||70)+(state.skills.qualifying||70))/2
            :((state.skills.setup||70)+(state.skills.data||70))/2;
        if(isQualifying(session)){
            const fieldSize=22;
            const strength=quality*.57+currentTeam.perf*.43;
            const predicted=Math.round(fieldSize-((strength-74)/27)*(fieldSize-2)+rand(-3.1,3.1));
            state.weekend.qualifying=clamp(predicted,1,fieldSize);
            addFeed('qualifying',session+': '+(state.role==='driver'?state.name:state.partner)+' chiude P'+state.weekend.qualifying+'.');
            growSkill(state.role==='driver'?'qualifying':'setup',rand(.05,.16));
        }else{
            growSkill(state.role==='driver'?'feedback':'data',rand(.04,.13));
            changeRelationship('team',.45);
            addRadio(state.role==='driver'?'team':'driver',state.role==='driver'?'Buon run. I dati confermano il tuo feedback sul bilanciamento.':'La modifica mi aiuta. La macchina e piu prevedibile nel lento.');
            addFeed('practice',session+': programma completato, correlazione dati '+Math.round(rand(87,97))+'%.');
        }
        state.sessionIndex++;
        if(state.sessionIndex>=state.weekend.sessions.length) finishWeekend(state.weekend.qualifying||12);
        save();
        renderAll();
    }

    function buildField(){
        const result=[];
        teams().forEach((currentTeam)=>currentTeam.drivers.forEach((driver,slot)=>{
            const playerDriver=state.role==='driver'&&currentTeam.name===state.team&&slot===1;
            result.push({
                id:playerDriver?'player':currentTeam.short+'-'+slot,
                name:playerDriver?state.name:driver,
                team:currentTeam.name,
                teamShort:currentTeam.short,
                color:currentTeam.color,
                rating:playerDriver?state.overall:(D.ratings[driver]||84),
                perf:currentTeam.perf,
                player:playerDriver,
                seed:result.length
            });
        }));
        if(state.role==='engineer'){
            const target=result.find((car)=>car.name===state.partner)||result.find((car)=>car.team===state.team);
            if(target){target.player=true;target.id='player';target.rating=clamp(target.rating+(state.overall-70)*.12,72,99);}
        }
        return result;
    }

    function startRace(sessionName){
        const current=round();
        const sprint=/Sprint/i.test(sessionName);
        const totalLaps=sprint?Math.max(18,Math.round(current.laps*.55)):current.laps;
        const sourceField=buildField();
        let grid=state.weekend.qualifying||clamp(Math.round(12+rand(-5,5)),1,sourceField.length);
        if(state.series==='F2'&&sprint&&state.weekend.qualifying) grid=state.weekend.qualifying<=10?11-state.weekend.qualifying:state.weekend.qualifying;
        const playerCar=sourceField.find((car)=>car.player);
        const ordered=sourceField.filter((car)=>!car.player).sort((a,b)=>a.seed-b.seed);
        if(playerCar) ordered.splice(clamp(grid-1,0,ordered.length),0,playerCar);
        ordered.forEach((car,index)=>{
            car.progress=-index*.0025;
            car.position=index+1;
            car.tyre=sprint?pick(['soft','medium']):pick(['medium','hard','soft']);
            car.wear=rand(0,3);
            car.energy=rand(86,100);
            car.pitCount=0;
            car.retired=false;
            car.damage=0;
            car.usedCompounds=[car.tyre];
        });
        const player=ordered.find((car)=>car.player);
        state.race={
            active:true,finished:false,speed:1,elapsed:0,lap:0,totalLaps,cars:ordered,pace:'balanced',attack:false,overtake:false,pitRequested:false,safetyCar:false,
            weather:Math.random()<.16?'changeable':'dry',temperature:Math.round(rand(19,31)),feed:[],sessionName,usedCompounds:player?[player.tyre]:[],sprint,
            dryCompoundRule:state.series==='F1'&&!sprint
        };
        syncControlButtons();
        addFeed('start',sessionName+': partenza dalla P'+grid+'.');
        addRadio('team',state.role==='driver'?'Procedura partenza completata. Proteggi le gomme nelle prime curve.':'Partenza pulita. Leggiamo il primo stint e il traffico prima di forzare la strategia.');
        buildCarMarkers();
        save();
        renderAll();
        resumeRace();
    }

    function resumeRace(){
        if(!state.race.active||state.race.finished||timer) return;
        timer=window.setInterval(simTick,120);
        renderWeekend();
    }

    function pauseRace(){
        window.clearInterval(timer);
        timer=null;
        renderWeekend();
    }

    function simTick(){
        if(!state.race.active) return;
        const multiplier=state.race.speed||1;
        for(let step=0;step<multiplier;step++) advanceSimulation();
        renderCars();
        updateRaceHud();
        if(state.race.finished){pauseRace();concludeRace();}
    }

    function advanceSimulation(){
        const race=state.race;
        race.elapsed++;
        const player=race.cars.find((car)=>car.player);
        const playerAbility=state.role==='driver'?state.overall:clamp((player?.rating||84)+(state.overall-70)*.08,75,98);
        race.cars.forEach((car)=>{
            if(car.retired||car.progress>=race.totalLaps) return;
            const pace=car.player?race.pace:'balanced';
            const ability=car.player?playerAbility:car.rating;
            const base=.0042+(ability-80)*.000013+(car.perf-80)*.0000095;
            const paceFactor=pace==='push'?1.045:pace==='conserve'?.966:1;
            const safetyFactor=race.safetyCar?.64:1;
            const overtakeFactor=car.player&&race.overtake&&car.energy>10&&overtakeEligible(car)?1.028:1;
            car.progress+=base*paceFactor*tyreFactor(car)*safetyFactor*(1+rand(-.017,.017))*overtakeFactor;
            car.wear=clamp(car.wear+(pace==='push'?.0075:pace==='conserve'?.004:.0055)*(car.tyre==='soft'?1.18:car.tyre==='hard'?.8:1),0,100);
            car.energy=clamp(car.energy+(car.player&&race.overtake&&overtakeEligible(car)?-.045:.012),0,100);
            if(car.damage>0) car.progress-=base*car.damage*.0015;
        });
        if(race.pitRequested&&player&&!player.retired&&fraction(player.progress)>.82) pitStop(player,true);
        aiPitStops();
        resolveBattles();
        maybeIncident();
        maybeWeather();
        enforceDryCompoundWarning();
        updateOrder();
        race.lap=Math.max(0,Math.floor(player?.progress||0)+1);
        if(player&&(player.progress>=race.totalLaps||player.retired)) race.finished=true;
        if(race.safetyCar&&Math.random()<.0012){
            race.safetyCar=false;
            addFeed('safety','Safety Car in questo giro. Ripartenza imminente.');
            addRadio('team','Safety Car in. Prepara la ripartenza e porta le gomme in temperatura.');
        }
        if(race.elapsed%520===0) maybeRadioPrompt();
        if(race.elapsed%70===0) save();
    }

    function fraction(progress){return progress-Math.floor(progress);}

    function tyreFactor(car){
        const race=state.race;
        let base=car.tyre==='soft'?1.012:car.tyre==='hard'?.991:1;
        if(car.tyre==='inter') base=race.weather==='wet'?1.012:race.weather==='changeable'?.995:.95;
        if(car.tyre==='wet') base=race.weather==='wet'?1.004:.92;
        const cliff=car.wear>78?1-(car.wear-78)*.0027:1;
        const wetPenalty=race.weather==='wet'&&!['inter','wet'].includes(car.tyre)?.89:1;
        return base*cliff*wetPenalty;
    }

    function updateOrder(){
        const active=state.race.cars.filter((car)=>!car.retired).sort((a,b)=>b.progress-a.progress);
        const retired=state.race.cars.filter((car)=>car.retired).sort((a,b)=>b.progress-a.progress);
        active.concat(retired).forEach((car,index)=>{car.position=index+1;});
    }

    function orderedCars(){return [...state.race.cars].sort((a,b)=>a.position-b.position);}

    function overtakeEligible(car){
        const ordered=orderedCars().filter((item)=>!item.retired);
        const index=ordered.indexOf(car);
        if(index<=0) return false;
        const ahead=ordered[index-1];
        return ahead.progress-car.progress<=.012;
    }

    function resolveBattles(){
        const race=state.race;
        const ordered=race.cars.filter((car)=>!car.retired).sort((a,b)=>b.progress-a.progress);
        for(let index=1;index<ordered.length;index++){
            const behind=ordered[index];
            const ahead=ordered[index-1];
            const gap=ahead.progress-behind.progress;
            if(gap<0||gap>.0035) continue;
            const playerBias=behind.player?(race.attack?.055:.012)+(race.overtake&&overtakeEligible(behind)?.05:0):.012;
            const skill=behind.player?(state.skills.racecraft||state.skills.strategy||70):behind.rating;
            const risk=$('#riskMode').value==='attack'?.012:$('#riskMode').value==='safe'?-.006:0;
            const chance=.018+playerBias+risk+(skill-80)*.001;
            if(Math.random()<chance){
                behind.progress=ahead.progress+.0005;
                ahead.progress-=.00015;
                addFeed('overtake',behind.name+' supera '+ahead.name+' per la P'+index+'.');
                if(behind.player){growSkill(state.role==='driver'?'racecraft':'strategy',.022);changeRelationship(relationTarget(),.08);}
            }
        }
    }

    function maybeIncident(){
        if(Math.random()>.00018) return;
        const candidates=state.race.cars.filter((car)=>!car.retired&&car.progress>.12&&car.progress<state.race.totalLaps-.2);
        if(!candidates.length) return;
        let car=pick(candidates);
        const focus=state.role==='driver'?(state.skills.focus||70):(state.skills.pressure||70);
        const nonPlayers=candidates.filter((candidate)=>!candidate.player);
        if(car.player&&nonPlayers.length&&Math.random()<focus/115) car=pick(nonPlayers);
        const severity=Math.random();
        if(severity>.8){
            car.retired=true;
            addFeed('incident',car.name+' e fermo dopo un incidente. Ritiro.');
            if(car.player) addRadio('alert','Fermati in sicurezza. La gara e finita.');
            if(Math.random()<.7) deploySafetyCar();
        }else{
            car.damage=clamp(car.damage+rand(4,16),0,40);
            car.progress-=rand(.002,.008);
            addFeed('incident',car.name+' ha un contatto e perde tempo'+(car.damage>12?' con danno anteriore.':'.'));
            if(car.player) addRadio('team','Contatto registrato. Controlliamo pressione e carico anteriore.');
            if(Math.random()<.22) deploySafetyCar();
        }
    }

    function deploySafetyCar(){
        if(state.race.safetyCar) return;
        state.race.safetyCar=true;
        addFeed('safety','SAFETY CAR. Gara neutralizzata.');
        addRadio('team','Safety Car. Delta positivo. La finestra box ora costa meno tempo.');
    }

    function aiPitStops(){
        const race=state.race;
        race.cars.forEach((car)=>{
            if(car.player||car.retired||car.progress<1) return;
            const threshold=car.tyre==='soft'?54:car.tyre==='medium'?67:78;
            const cheapStop=race.safetyCar&&Math.random()<.055;
            if((car.wear>threshold||cheapStop)&&fraction(car.progress)>.82) pitStop(car,false);
        });
    }

    function pitStop(car,isPlayer){
        const oldTyre=car.tyre;
        let next=isPlayer?$('#nextTyre').value:pick(['soft','medium','hard']);
        if(!isPlayer&&state.race.dryCompoundRule&&car.pitCount===0&&next===oldTyre){
            next=oldTyre==='medium'?'hard':'medium';
        }
        car.pitCount++;
        car.progress-=state.series==='F1'?.24:.20;
        car.tyre=next;
        car.wear=0;
        car.damage=Math.max(0,car.damage-10);
        car.usedCompounds=Array.from(new Set((car.usedCompounds||[]).concat(next)));
        if(isPlayer){
            state.race.pitRequested=false;
            state.race.usedCompounds=Array.from(new Set((state.race.usedCompounds||[]).concat(next)));
            syncControlButtons();
        }
        addFeed('pit',car.name+': pit stop, '+oldTyre.toUpperCase()+' - '+next.toUpperCase()+'.');
        if(isPlayer) addRadio('team','Stop completato. '+next.toUpperCase()+' montate. Controlla il traffico in uscita.');
    }

    function maybeWeather(){
        const race=state.race;
        if(race.weather!=='changeable'||race.elapsed<150||Math.random()>.00035) return;
        race.weather='wet';
        addFeed('weather','Pioggia in aumento. Il crossover verso le intermedie e vicino.');
        addRadio('team','Pioggia in aumento. Valuta il grip e preparati per le intermedie.');
        renderStrategy();
    }

    function enforceDryCompoundWarning(){
        const race=state.race;
        if(!race.dryCompoundRule||race.weather==='wet'||race.sprint) return;
        const player=race.cars.find((car)=>car.player);
        if(!player||race.usedCompounds.length>=2) return;
        const remaining=race.totalLaps-player.progress;
        if(remaining<4&&remaining>3.94){
            addRadio('alert','Regola gomme: serve una seconda specifica slick prima del traguardo.');
            addFeed('safety','Richiamo regolamentare: seconda specifica slick obbligatoria in gara asciutta.');
        }
    }

    function buildCarMarkers(){
        const layer=$('#carsLayer');
        layer.innerHTML='';
        state.race.cars.forEach((car,index)=>{
            const ns='http://www.w3.org/2000/svg';
            const group=document.createElementNS(ns,'g');
            group.setAttribute('class','car-marker'+(car.player?' player':''));
            group.setAttribute('data-car-index',String(index));
            const rear=document.createElementNS(ns,'rect');
            rear.setAttribute('x','-9');rear.setAttribute('y','-6');rear.setAttribute('width','4');rear.setAttribute('height','12');rear.setAttribute('rx','1');rear.setAttribute('fill',car.player?'#c8ff35':car.color);
            const body=document.createElementNS(ns,'rect');
            body.setAttribute('x','-6');body.setAttribute('y','-4');body.setAttribute('width','14');body.setAttribute('height','8');body.setAttribute('rx','3');body.setAttribute('fill',car.player?'#c8ff35':car.color);body.setAttribute('class','body');
            const nose=document.createElementNS(ns,'rect');
            nose.setAttribute('x','6');nose.setAttribute('y','-2');nose.setAttribute('width','7');nose.setAttribute('height','4');nose.setAttribute('rx','1');nose.setAttribute('fill',car.player?'#c8ff35':car.color);
            const text=document.createElementNS(ns,'text');
            text.setAttribute('x','1');text.setAttribute('y','.5');text.textContent=car.player?'ME':String(index+1);
            group.append(rear,body,nose,text);
            layer.append(group);
        });
        renderCars();
    }

    function renderCars(){
        const path=$('#trackGuide');
        let length=0;
        try{length=path.getTotalLength();}catch(error){return;}
        state.race.cars.forEach((car,index)=>{
            const group=document.querySelector('[data-car-index="'+index+'"]');
            if(!group) return;
            if(car.retired){group.setAttribute('opacity','.2');return;}
            const progress=((car.progress%1)+1)%1;
            const point=path.getPointAtLength(progress*length);
            const ahead=path.getPointAtLength(((progress+.002)%1)*length);
            const angle=Math.atan2(ahead.y-point.y,ahead.x-point.x)*180/Math.PI;
            const lane=((index%3)-1)*3.1;
            group.setAttribute('transform','translate('+point.x+' '+(point.y+lane)+') rotate('+angle+')');
        });
    }

    function updateRaceHud(){
        const player=state.race.cars.find((car)=>car.player);
        if(!player) return;
        $('#lapValue').textContent=String(Math.min(state.race.totalLaps,Math.max(1,Math.floor(player.progress)+1)));
        $('#totalLapsValue').textContent=String(state.race.totalLaps);
        $('#positionValue').textContent='P'+player.position;
        const leader=orderedCars().find((car)=>!car.retired);
        $('#gapValue').textContent=leader===player?'LEADER':'+'+Math.max(0,(leader.progress-player.progress)*78).toFixed(1)+'s';
        $('#liveLabel').textContent=state.race.safetyCar?'Safety Car':state.race.weather==='wet'?'Wet race':'Race live';
        $('#raceState').textContent=state.race.safetyCar?'SAFETY CAR':state.race.weather==='wet'?'WET RACE':'RACE LIVE';
        renderTiming();
        renderTelemetry();
        renderFeed();
    }

    function renderTelemetry(){
        const player=state.race.cars?.find?.((car)=>car.player);
        if(!player){
            $('#tyreTelemetry').textContent='--';$('#wearTelemetry').textContent='--';$('#energyTelemetry').textContent='--';$('#carTelemetry').textContent='--';return;
        }
        $('#tyreTelemetry').textContent=String(player.tyre||'--').toUpperCase();
        $('#wearTelemetry').textContent=Math.round(player.wear||0)+'%';
        $('#energyTelemetry').textContent=Math.round(player.energy||0)+'%';
        $('#carTelemetry').textContent=player.retired?'RITIRO':player.damage>15?'DANNO':player.damage>0?'OK / ALA':'OK';
    }

    function renderTiming(){
        if(!state.race.cars?.length){
            $('#timingBody').innerHTML='<tr><td class="empty-row" colspan="5">Il live timing apparira quando iniziera una sessione di gara.</td></tr>';
            $('#fieldCount').textContent='-- auto';
            return;
        }
        const ordered=orderedCars();
        const leader=ordered.find((car)=>!car.retired);
        $('#fieldCount').textContent=ordered.length+' auto';
        $('#timingBody').innerHTML=ordered.map((car)=>{
            const gap=car.retired?'OUT':car===leader?'LEADER':'+'+Math.max(0,(leader.progress-car.progress)*78).toFixed(1);
            const tyre=car.tyre||'medium';
            const tyreClass='tyre-'+tyre;
            return '<tr class="'+(car.player?'you':'')+'"><td class="position-cell">'+car.position+'</td><td><strong>'+escapeHtml(car.name)+'</strong></td><td><span class="team-swatch" style="background:'+car.color+'"></span>'+escapeHtml(car.teamShort)+'</td><td><span class="tyre-badge '+tyreClass+'">'+tyre.charAt(0).toUpperCase()+'</span></td><td>'+gap+'</td></tr>';
        }).join('');
    }

    function pointsFor(pos,sprint){
        const table=state.series==='F1'?(sprint?[8,7,6,5,4,3,2,1]:[25,18,15,12,10,8,6,4,2,1]):(sprint?[10,8,6,5,4,3,2,1]:[25,18,15,12,10,8,6,4,2,1]);
        return table[pos-1]||0;
    }

    function concludeRace(){
        const player=state.race.cars.find((car)=>car.player);
        let finalPos=player?.position||22;
        if(state.race.dryCompoundRule&&state.race.weather!=='wet'&&state.race.usedCompounds.length<2&&!player?.retired){
            finalPos=22;
            addFeed('incident','Classificazione compromessa: obbligo di due specifiche slick non rispettato.');
            addRadio('alert','Strategia gomme non conforme alla regola della gara asciutta.');
        }
        state.weekend.result=finalPos;
        const sprint=/Sprint/i.test(state.race.sessionName);
        const points=pointsFor(finalPos,sprint);
        state.seasonPoints+=points;
        if(finalPos===1&&!sprint) state.wins++;
        if(finalPos<=3&&!sprint) state.podiums++;
        growSkill(state.role==='driver'?(finalPos<=5?'racecraft':'focus'):(finalPos<=5?'strategy':'pressure'),finalPos<=5?rand(.08,.22):rand(.03,.11));
        if(finalPos<=10){state.reputation=clamp(state.reputation+(finalPos<=3?3:1),0,100);changeRelationship('team',finalPos<=5?1.3:.6);}
        if(player?.retired){state.reputation=clamp(state.reputation-1,0,100);changeRelationship('morale',-2);}
        addFeed('finish',state.race.sessionName+': bandiera a scacchi, P'+finalPos+(points?' - '+points+' punti':'')+'.');
        state.race.active=false;
        state.race.finished=true;
        state.sessionIndex++;
        if(state.sessionIndex>=state.weekend.sessions.length) finishWeekend(finalPos);
        else{save();renderAll();}
    }

    function finishWeekend(finalPos){
        state.weekend.active=false;
        state.weekend.completed=true;
        state.weekends++;
        growSkill(state.role==='driver'?'adaptability':'leadership',rand(.06,.17));
        growSkill(state.role==='driver'?'feedback':'communication',rand(.04,.14));
        state.relationships.morale=clamp(state.relationships.morale+(finalPos<=10?1.4:-.5),0,100);
        state.overall=overall();
        $('#resultTitle').textContent=round().name+' - debrief';
        $('#resultPosition').textContent='P'+finalPos;
        $('#resultReputation').textContent=String(state.reputation);
        $('#resultTrust').textContent=String(Math.round(state.relationships.team));
        const rows=skillDefs().filter(([key])=>(state.skillDeltas[key]||0)>0).map(([key,label])=>'<div class="growth-row"><span>'+escapeHtml(label)+'</span><b>+'+state.skillDeltas[key].toFixed(2)+'</b></div>');
        $('#growthList').innerHTML=rows.join('')||'<div class="growth-row"><span>Esperienza weekend</span><b>+0.05</b></div>';
        $('#resultModal').hidden=false;
        save();
        renderAll();
    }

    function renderCalendar(){
        $('#calendarTitle').textContent=state.series+' - Calendario 2026';
        $('#calendarList').innerHTML=calendar().map((item,index)=>{
            const classes=(index===state.roundIndex?' current':'')+(index<state.roundIndex?' done':'');
            const format=item.special?'SPECIAL - 2 FEATURE':item.sprint?(state.series==='F1'?'SPRINT WEEKEND':'SPRINT + FEATURE'):'';
            return '<div class="round-card'+classes+'"><small>Round '+item.round+'</small><strong>'+escapeHtml(item.name)+'</strong><span>'+escapeHtml(item.date)+'</span>'+(format?'<em>'+format+'</em>':'')+'</div>';
        }).join('');
        window.setTimeout(()=>document.querySelector('.round-card.current')?.scrollIntoView({inline:'center',block:'nearest',behavior:'smooth'}),40);
    }

    function renderStrategy(){
        const condition=state.race.weather||'dry';
        const labels=condition==='wet'?['Pioggia','Pioggia','Variabile','Variabile','Asciutto']:condition==='changeable'?['Asciutto','Variabile','Pioggia','Pioggia','Variabile']:['Asciutto','Asciutto','Asciutto','Variabile','Asciutto'];
        $('#weatherLabel').textContent=(condition==='wet'?'Pioggia':condition==='changeable'?'Variabile':'Asciutto')+' '+state.race.temperature+' C';
        $('#forecast').innerHTML=labels.map((label,index)=>'<div class="forecast-cell"><strong>'+label+'</strong><span>+'+(index*10)+' min</span></div>').join('');
    }

    function addFeed(type,text){
        state.race.feed=state.race.feed||[];
        state.race.feed.unshift({type,text,lap:state.race.active?Math.max(1,state.race.lap||1):null,time:Date.now()});
        state.race.feed=state.race.feed.slice(0,60);
        renderFeed();
    }

    function renderFeed(){
        const feed=state.race.feed||[];
        $('#eventFeed').innerHTML=feed.length?feed.map((item)=>'<div class="feed-item '+escapeHtml(item.type)+'"><div class="feed-lap">'+(item.lap?'L'+item.lap:'SYS')+'</div><div>'+escapeHtml(item.text)+'</div></div>').join(''):'<div class="empty-row">Sorpassi, pit stop, incidenti e Race Control compariranno qui.</div>';
    }

    function addRadio(who,text){
        const log=$('#radioLog');
        const label=who==='you'?state.name:who==='driver'?state.partner:who==='alert'?'Race Control':state.role==='driver'?state.engineer:state.name;
        const element=document.createElement('div');
        element.className='radio-message '+who;
        element.innerHTML='<strong>'+escapeHtml(label||'Team')+'</strong>'+escapeHtml(text);
        log.append(element);
        log.scrollTop=log.scrollHeight;
    }

    function renderRadioHeader(){
        $('#radioTitle').textContent=state.role==='driver'?state.name.split(' ')[0]+' / '+state.engineer:state.partner+' / '+state.name.split(' ')[0];
        if(!$('#radioLog').children.length) addRadio('team','Canale radio aperto. La fiducia cresce con decisioni coerenti e comunicazione utile.');
    }

    function maybeRadioPrompt(){
        if(radioQuestion||!state.race.active) return;
        const player=state.race.cars.find((car)=>car.player);
        if(!player) return;
        const driverPrompts=[
            {q:player.wear>62?'Le gomme stanno calando. Box questa tornata?':'Come senti il bilanciamento?',choices:[['Box, confermo','pit'],['Posso estendere','extend'],['Manca rotazione nel lento','feedback']]},
            {q:state.race.safetyCar?'Safety Car. La finestra box e conveniente. Che facciamo?':'Auto davanti vicina. Vuoi usare energia?',choices:[['Box ora','pit'],['Attacco adesso','attack'],['Conservo energia','save']]}
        ];
        const engineerPrompts=[
            {q:player.wear>62?'Le posteriori stanno andando. Non tengo questo passo.':'La macchina scivola nel settore centrale. Cosa faccio?',choices:[['Box questa tornata','pit'],['Estendi due giri','extend'],['Correzione setup, resta fuori','setup']]},
            {q:state.race.safetyCar?'Safety Car. Entro?':'Sono vicino alla vettura davanti. Posso spingere?',choices:[['Box, box','pit'],['Push con energia','attack'],['Gestisci e aspetta','save']]}
        ];
        radioQuestion=pick(state.role==='driver'?driverPrompts:engineerPrompts);
        addRadio(state.role==='driver'?'team':'driver',radioQuestion.q);
        $('#radioChoices').innerHTML=radioQuestion.choices.map(([label,action])=>'<button class="radio-choice" data-radio-action="'+action+'" type="button">'+escapeHtml(label)+'</button>').join('');
    }

    function handleRadio(action){
        const player=state.race.cars.find((car)=>car.player);
        if(!player) return;
        if(action==='pit'){
            state.race.pitRequested=true;
            addRadio('you',state.role==='driver'?'Box questa tornata. Ricevuto.':'Box questa tornata. Confermo la chiamata.');
            changeRelationship(relationTarget(),player.wear>55||state.race.safetyCar?1.1:-.6);
            growSkill(state.role==='engineer'?'strategy':'feedback',.035);
        }else if(action==='extend'){
            state.race.pitRequested=false;
            addRadio('you',state.role==='driver'?'Posso tenerle vive ancora due giri.':'Resta fuori due giri. Proteggi il posteriore.');
            changeRelationship(relationTarget(),player.wear<70?.6:-.9);
            growSkill('tyres',.03);
        }else if(action==='attack'){
            state.race.attack=true;
            if(overtakeEligible(player)) state.race.overtake=true;
            addRadio('you',state.role==='driver'?'Spingo ora.':'Push now. Usa energia se entri nel gap di attivazione.');
            changeRelationship(relationTarget(),.5);
        }else if(action==='save'){
            state.race.attack=false;state.race.overtake=false;
            addRadio('you',state.role==='driver'?'Ricevuto, ricarico.':'Gestisci, ricarica e prepara il prossimo attacco.');
            growSkill(state.role==='engineer'?'communication':'focus',.02);
        }else{
            addRadio('you',state.role==='driver'?'Manca rotazione nel lento, ingresso buono ma centro curva debole.':'Correggi il differenziale in ingresso di un click e dimmi se libera il centro curva.');
            growSkill(state.role==='driver'?'feedback':'setup',.045);
            changeRelationship(relationTarget(),.8);
        }
        radioQuestion=null;
        $('#radioChoices').innerHTML='';
        syncControlButtons();
        save();
        renderRelationships();
    }

    function syncControlButtons(){
        const controls=$$('#raceControls [data-control]');
        controls.forEach((button)=>{
            const control=button.dataset.control;
            const active=control==='pace'?state.race.pace!=='balanced':Boolean(state.race[control==='pit'?'pitRequested':control]);
            button.classList.toggle('active',active||control==='pace'&&state.race.pace==='balanced');
        });
        $('#paceControlLabel').textContent=state.race.pace==='push'?'Push':state.race.pace==='conserve'?'Conserva':'Bilanciato';
    }

    function setupWizard(){
        let step=1;
        let role='driver';
        let draft={};
        let budget=24;
        const base=66;

        function ensureDraft(){
            const defs=skillDefs(role);
            if(!Object.keys(draft).length||!defs.every(([key])=>key in draft)){
                draft=Object.fromEntries(defs.map(([key])=>[key,base]));
                budget=24;
            }
        }

        function renderBuilder(){
            ensureDraft();
            $('#skillBuilder').innerHTML=skillDefs(role).map(([key,label,help])=>'<div class="skill-build"><div><strong>'+escapeHtml(label)+'</strong><small>'+escapeHtml(help)+'</small></div><div class="skill-stepper"><button data-minus="'+key+'" type="button">-</button><b>'+draft[key]+'</b><button data-plus="'+key+'" type="button">+</button></div></div>').join('');
            $('#skillBudget').textContent=String(budget);
        }

        function renderStep(){
            [1,2,3].forEach((number)=>{$('#wizardStep'+number).hidden=number!==step;});
            $$('.steps span').forEach((dot,index)=>dot.classList.toggle('active',index<step));
            $('#wizardBack').hidden=step===1;
            $('#wizardNext').hidden=step===3;
            $('#wizardNext').textContent=step===1?'Costruisci abilita':'Mostra offerte';
            if(step===2) renderBuilder();
            if(step===3) renderOffers();
        }

        $$('[data-role]').forEach((button)=>button.addEventListener('click',()=>{
            role=button.dataset.role;
            $$('[data-role]').forEach((item)=>item.classList.toggle('selected',item===button));
            draft={};budget=24;
        }));

        $('#skillBuilder').addEventListener('click',(event)=>{
            const plus=event.target.closest('[data-plus]');
            const minus=event.target.closest('[data-minus]');
            if(plus){const key=plus.dataset.plus;if(budget>0&&draft[key]<82){draft[key]++;budget--;renderBuilder();}}
            if(minus){const key=minus.dataset.minus;if(draft[key]>base){draft[key]--;budget++;renderBuilder();}}
        });

        $('#wizardNext').addEventListener('click',()=>{
            if(step===1){if(!$('#careerName').value.trim()){showToast('Inserisci un nome.');return;}step=2;}
            else if(step===2) step=3;
            renderStep();
        });
        $('#wizardBack').addEventListener('click',()=>{step=Math.max(1,step-1);renderStep();});

        function marketOffers(marketRating,rep){
            const list=[];
            D.teams.F2.forEach((candidate)=>{
                const threshold=63+(candidate.perf-80)*.5;
                if(marketRating>=threshold-5) list.push({series:'F2',team:candidate,fit:clamp(Math.round(74+marketRating-threshold+rand(-5,5)),58,98)});
            });
            if(marketRating>=76||rep>=65){
                D.teams.F1.filter((candidate)=>candidate.perf<=(marketRating>=84?96:86)).forEach((candidate)=>{
                    const threshold=76+(candidate.perf-79)*.55;
                    if(marketRating>=threshold-3) list.push({series:'F1',team:candidate,fit:clamp(Math.round(66+marketRating-threshold+rand(-4,4)),55,95)});
                });
            }
            list.sort((a,b)=>b.fit-a.fit);
            const unique=[];
            for(const item of list){if(!unique.some((offer)=>offer.team.name===item.team.name)) unique.push(item);if(unique.length===3) break;}
            if(!unique.length) D.teams.F2.slice(-3).forEach((candidate,index)=>unique.push({series:'F2',team:candidate,fit:68-index*2}));
            return unique.map((offer,index)=>Object.assign(offer,{target:offer.series==='F1'?(offer.team.perf>90?'Podi e vittorie':'Punti regolari'):(offer.team.perf>88?'Titolo F2':'Top 8'),length:index===0?'1 stagione + opzione':'1 stagione',pressure:offer.team.perf>90?'Molto alta':offer.team.perf>85?'Alta':'Media'}));
        }

        function renderOffers(){
            ensureDraft();
            const history=D.history[$('#careerHistory').value]||D.history.rookie;
            const offers=marketOffers(overall(draft)+history.bonus,history.rep);
            $('#offersList').innerHTML=offers.map((offer,index)=>'<div class="offer-card"><div class="offer-head"><div><strong>'+escapeHtml(offer.team.name)+'</strong><div class="offer-meta">'+offer.series+' - '+(role==='driver'?'sedile pilota':'race engineer')+'</div></div><b>'+offer.fit+'%</b></div><div class="offer-lines"><span>Obiettivo <b>'+offer.target+'</b></span><span>Durata <b>'+offer.length+'</b></span><span>Pressione <b>'+offer.pressure+'</b></span></div><button class="button button-primary" data-offer="'+index+'" type="button">Firma contratto</button></div>').join('');
            $$('[data-offer]').forEach((button)=>button.addEventListener('click',()=>startCareer(role,draft,offers[Number(button.dataset.offer)])));
        }

        renderStep();
    }

    function startCareer(role,skills,offer){
        const historyKey=$('#careerHistory').value;
        state=emptyState();
        state.created=true;
        state.role=role;
        state.name=$('#careerName').value.trim();
        state.nation=$('#careerNation').value;
        state.history=historyKey;
        state.skills=Object.assign({},skills);
        state.skillDeltas=Object.fromEntries(Object.keys(skills).map((key)=>[key,0]));
        state.overall=overall(skills);
        state.reputation=D.history[historyKey].rep;
        state.series=offer.series;
        state.team=offer.team.name;
        state.partner=offer.team.drivers[0];
        state.engineer=D.engineers[(offer.team.short.charCodeAt(0)+offer.team.short.length)%D.engineers.length];
        state.relationships={driver:58,engineer:58,team:clamp(Math.round(52+offer.fit/8),55,68),morale:70};
        $('#careerWizard').hidden=true;
        save();
        renderAll();
        addRadio('team','Benvenuto in '+state.team+'. Qui contano cronometro, chiarezza e fiducia reciproca.');
        addFeed('system','Contratto firmato: '+state.name+' - '+state.team+' ('+state.series+').');
    }

    function endSeason(){
        state.roundIndex=0;
        const promotion=state.series==='F2'&&(state.seasonPoints>=120||state.reputation>=72||state.overall>=80);
        if(promotion){
            const candidates=D.teams.F1.filter((item)=>item.perf<=88).sort((a,b)=>Math.abs(a.perf-state.overall)-Math.abs(b.perf-state.overall));
            const promoted=candidates[0]||D.teams.F1[D.teams.F1.length-1];
            state.series='F1';state.team=promoted.name;state.partner=promoted.drivers[0];state.engineer=D.engineers[(promoted.short.charCodeAt(0)+promoted.short.length)%D.engineers.length];state.reputation=clamp(state.reputation+8,0,100);
            showToast('Promozione in F1: contratto '+state.team+'.');
        }else{
            state.reputation=clamp(state.reputation+(state.seasonPoints>80?5:1),0,100);
            showToast('Stagione conclusa. Contratto rinnovato.');
        }
        state.seasonPoints=0;state.weekend=emptyState().weekend;state.race=emptyState().race;state.sessionIndex=0;
        save();renderAll();
    }

    function bindControls(){
        $('#raceControls').addEventListener('click',(event)=>{
            const button=event.target.closest('[data-control]');
            if(!button||!state.race.active) return;
            const control=button.dataset.control;
            const player=state.race.cars.find((car)=>car.player);
            if(control==='pace'){
                const modes=['balanced','push','conserve'];
                state.race.pace=modes[(modes.indexOf(state.race.pace)+1)%modes.length];
            }else if(control==='attack') state.race.attack=!state.race.attack;
            else if(control==='overtake'){
                if(!state.race.overtake&&player&&!overtakeEligible(player)){showToast('Overtake Mode non disponibile: serve una vettura nel gap di attivazione.');return;}
                state.race.overtake=!state.race.overtake;
            }else if(control==='pit'){
                state.race.pitRequested=!state.race.pitRequested;
                addRadio('you',state.race.pitRequested?'Box questa tornata.':'Annulla pit, resto fuori.');
            }
            syncControlButtons();save();
        });

        $('#speedButtons').addEventListener('click',(event)=>{
            const button=event.target.closest('[data-speed]');if(!button) return;
            state.race.speed=Number(button.dataset.speed);
            $$('.speed-button').forEach((item)=>item.classList.toggle('active',item===button));
        });
        $('#radioChoices').addEventListener('click',(event)=>{const button=event.target.closest('[data-radio-action]');if(button) handleRadio(button.dataset.radioAction);});
        $('#rulesButton').addEventListener('click',()=>{$('#rulesModal').hidden=false;});
        $('#closeRules').addEventListener('click',()=>{$('#rulesModal').hidden=true;});
        $('#closeResult').addEventListener('click',()=>{
            $('#resultModal').hidden=true;
            state.roundIndex++;
            if(state.roundIndex>=calendar().length) endSeason();
            else{state.weekend=emptyState().weekend;state.race=emptyState().race;state.sessionIndex=0;save();renderAll();}
        });
        $('#resetButton').addEventListener('click',()=>{
            if(!window.confirm('Cancellare la carriera locale e ricominciare?')) return;
            pauseRace();localStorage.removeItem(STORAGE_KEY);window.location.reload();
        });
    }

    const restored=load();
    setupWizard();
    bindControls();
    if(restored) renderAll();
})();

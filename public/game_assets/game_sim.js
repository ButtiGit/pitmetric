'use strict';

const game_data = window.game_data_2026;
const game_storage_key = 'game_pitmetric_race_engineer_v2';
const game_tick_ms = 250;
const game_compounds = {
    game_SOFT:{game_code:'S',game_wear:1.42,game_grip:1.06,game_heat:1.18},
    game_MEDIUM:{game_code:'M',game_wear:1.00,game_grip:1.00,game_heat:1.00},
    game_HARD:{game_code:'H',game_wear:.72,game_grip:.965,game_heat:.86},
    game_INTER:{game_code:'I',game_wear:1.04,game_grip:.94,game_heat:.92},
    game_WET:{game_code:'W',game_wear:.82,game_grip:.90,game_heat:.84}
};

let game_timer = null;
let game_channel = 'driver';
let game_selected_team = null;
let game_selected_driver = null;
let game_selected_round = null;
let game_state = null;

function game_clamp(game_value, game_min, game_max){return Math.min(game_max,Math.max(game_min,game_value));}
function game_rand(game_min, game_max){return game_min+Math.random()*(game_max-game_min);}
function game_round(game_value, game_digits=0){const game_factor=10**game_digits;return Math.round(game_value*game_factor)/game_factor;}
function game_pick(game_values){return game_values[Math.floor(Math.random()*game_values.length)];}
function game_escape(game_value){const game_node=document.createElement('div');game_node.textContent=String(game_value);return game_node.innerHTML;}
function game_formatLap(game_seconds){if(!Number.isFinite(game_seconds))return '--:--.---';const game_minutes=Math.floor(game_seconds/60);const game_rest=game_seconds-game_minutes*60;return `${game_minutes}:${game_rest.toFixed(3).padStart(6,'0')}`;}
function game_nowLabel(){return new Date().toLocaleTimeString('it-IT',{hour:'2-digit',minute:'2-digit'});}
function game_team(){return game_data.game_teams[game_state?.game_team_key||game_selected_team];}
function game_driver(){return game_data.game_drivers[game_state?.game_driver_key||game_selected_driver];}
function game_roundData(){return game_data.game_calendar.find(game_round_item=>game_round_item.game_key===(game_state?.game_round_key||game_selected_round));}
function game_session(){return game_state?.game_sessions?.[game_state.game_session_index]||null;}
function game_compound(){return game_compounds[`game_${game_state.game_tyre}`]||game_compounds.game_MEDIUM;}
function game_packageRating(){const game_team_data=game_team();const game_driver_data=game_driver();return game_team_data&&game_driver_data?game_team_data.game_pace*.58+game_driver_data.game_pace*.42:75;}
function game_expectedPosition(){return game_clamp(Math.round(22-((game_packageRating()-58)/38)*20),1,22);}

function game_makeSessions(game_round_data){
    if(game_round_data.game_sprint){
        return [
            {game_key:'fp1',game_name:'FP1',game_type:'practice',game_laps:12,game_objective:'Build the baseline. Ask for balance feedback and understand tyre behaviour.'},
            {game_key:'sprint_qualifying',game_name:'Sprint Qualifying',game_type:'qualifying',game_laps:6,game_objective:'Prepare the tyre, find a gap and call the push laps.'},
            {game_key:'sprint',game_name:'Sprint',game_type:'race',game_laps:Math.max(16,Math.round(game_round_data.game_laps*.32)),game_objective:'Short race. No time to recover from a bad call.'},
            {game_key:'qualifying',game_name:'Qualifying',game_type:'qualifying',game_laps:8,game_objective:'Maximise the package and set the Grand Prix grid.'},
            {game_key:'race',game_name:'Grand Prix',game_type:'race',game_laps:game_round_data.game_laps,game_objective:'Manage the full race: pace, tyres, fuel, battery, weather and pit crew.'}
        ];
    }
    return [
        {game_key:'fp1',game_name:'FP1',game_type:'practice',game_laps:12,game_objective:'Build the baseline and learn what the driver feels.'},
        {game_key:'fp2',game_name:'FP2',game_type:'practice',game_laps:14,game_objective:'Long-run tyre and fuel work. Decide what needs changing.'},
        {game_key:'fp3',game_name:'FP3',game_type:'practice',game_laps:10,game_objective:'Final preparation before qualifying.'},
        {game_key:'qualifying',game_name:'Qualifying',game_type:'qualifying',game_laps:8,game_objective:'Find clean air and call the push laps.'},
        {game_key:'race',game_name:'Grand Prix',game_type:'race',game_laps:game_round_data.game_laps,game_objective:'This is the one that matters. Engineer the complete race.'}
    ];
}

function game_createState(game_team_key,game_driver_key,game_round_key){
    const game_round_data=game_data.game_calendar.find(game_round_item=>game_round_item.game_key===game_round_key);
    const game_driver_data=game_data.game_drivers[game_driver_key];
    const game_expected=game_expectedPositionFor(game_team_key,game_driver_key);
    return {
        game_version:2,
        game_team_key,
        game_driver_key,
        game_round_key,
        game_sessions:game_makeSessions(game_round_data),
        game_session_index:0,
        game_phase:'briefing',
        game_lap:0,
        game_progress:0,
        game_elapsed:0,
        game_position:game_clamp(game_expected+Math.round(game_rand(-2,2)),1,22),
        game_sprint_grid:game_expected,
        game_race_grid:game_expected,
        game_gap_ahead:game_rand(.7,2.1),
        game_gap_back:game_rand(.7,2.1),
        game_last_lap:null,
        game_best_lap:null,
        game_speed:0,
        game_throttle:0,
        game_brake:0,
        game_gear:0,
        game_rpm:0,
        game_fuel:20,
        game_fuel_target:0,
        game_battery:82,
        game_tyre:'MEDIUM',
        game_tyre_wear:0,
        game_tyre_age:0,
        game_tyre_temp:86,
        game_brake_temp:480,
        game_engine_temp:96,
        game_mode:'balanced',
        game_intent:'neutral',
        game_weather:'DRY',
        game_rain_probability:game_round_data.game_rain,
        game_track_wetness:0,
        game_score:50,
        game_confidence:game_clamp(68+(game_driver_data.game_consistency-70)*.45,55,92),
        game_damage:{game_front:0,game_floor:0,game_rear:0,game_suspension:0,game_engine:0},
        game_pit:{game_requested:false,game_tyre:'MEDIUM',game_wing:0,game_repair:false,game_stop_mode:'safe',game_ready:false,game_last_stop:null},
        game_chat:[],
        game_session_results:[],
        game_speed_multiplier:24,
        game_question_cooldown:18,
        game_event_cooldown:18,
        game_pending_question:null,
        game_completed:false,
        game_lap_sample:0
    };
}

function game_expectedPositionFor(game_team_key,game_driver_key){
    const game_team_data=game_data.game_teams[game_team_key];
    const game_driver_data=game_data.game_drivers[game_driver_key];
    const game_rating=game_team_data.game_pace*.58+game_driver_data.game_pace*.42;
    return game_clamp(Math.round(22-((game_rating-58)/38)*20),1,22);
}

function game_renderSetup(){
    const game_team_grid=document.getElementById('game_team_grid');
    game_team_grid.innerHTML=Object.entries(game_data.game_teams).map(([game_key,game_value])=>`<button class="game_team_option${game_selected_team===game_key?' game_selected':''}" data-game-team="${game_key}" style="--game_option_color:${game_value.game_color}" type="button"><strong>${game_escape(game_value.game_name)}</strong><small>Pace ${game_value.game_pace} · Crew ${game_value.game_pitcrew}</small></button>`).join('');
    game_team_grid.querySelectorAll('[data-game-team]').forEach(game_button=>game_button.addEventListener('click',()=>game_selectTeam(game_button.dataset.gameTeam)));
    game_renderDriverChoices();
    const game_calendar_grid=document.getElementById('game_calendar_grid');
    game_calendar_grid.innerHTML=game_data.game_calendar.map(game_round_item=>`<button class="game_calendar_option${game_selected_round===game_round_item.game_key?' game_selected':''}" data-game-round="${game_round_item.game_key}" type="button"><span class="game_round_num">R${game_round_item.game_round}</span><strong>${game_escape(game_round_item.game_venue)}</strong><small>${game_escape(game_round_item.game_dates)} · ${game_escape(game_round_item.game_country)}</small>${game_round_item.game_sprint?'<span class="game_sprint_tag">SPRINT</span>':''}</button>`).join('');
    game_calendar_grid.querySelectorAll('[data-game-round]').forEach(game_button=>game_button.addEventListener('click',()=>{game_selected_round=game_button.dataset.gameRound;game_renderSetup();game_updateSetupSummary();}));
    game_updateSetupSummary();
}

function game_selectTeam(game_team_key){
    game_selected_team=game_team_key;
    game_selected_driver=null;
    document.documentElement.style.setProperty('--game_team',game_data.game_teams[game_team_key].game_color);
    document.documentElement.style.setProperty('--game_team_soft',`${game_data.game_teams[game_team_key].game_color}22`);
    game_renderSetup();
}

function game_renderDriverChoices(){
    const game_driver_grid=document.getElementById('game_driver_grid');
    if(!game_selected_team){game_driver_grid.innerHTML='<div class="game_command_hint">Scegli prima una scuderia.</div>';return;}
    const game_team_data=game_data.game_teams[game_selected_team];
    game_driver_grid.innerHTML=game_team_data.game_drivers.map(game_driver_key=>{const game_driver_data=game_data.game_drivers[game_driver_key];return `<button class="game_driver_option${game_selected_driver===game_driver_key?' game_selected':''}" data-game-driver="${game_driver_key}" type="button"><span class="game_driver_option_number">${game_driver_data.game_number}</span><span><strong>${game_escape(game_driver_data.game_name)}</strong><small>${game_driver_data.game_country} · feedback ${game_driver_data.game_feedback} · tyres ${game_driver_data.game_tyres}</small></span><b>PACE ${game_driver_data.game_pace}</b></button>`;}).join('');
    game_driver_grid.querySelectorAll('[data-game-driver]').forEach(game_button=>game_button.addEventListener('click',()=>{game_selected_driver=game_button.dataset.gameDriver;game_renderDriverChoices();game_updateSetupSummary();}));
}

function game_updateSetupSummary(){
    const game_summary=document.getElementById('game_setup_summary');
    const game_start=document.getElementById('game_start_weekend');
    if(game_selected_team&&game_selected_driver&&game_selected_round){
        const game_team_data=game_data.game_teams[game_selected_team];
        const game_driver_data=game_data.game_drivers[game_selected_driver];
        const game_round_data=game_data.game_calendar.find(game_round_item=>game_round_item.game_key===game_selected_round);
        game_summary.textContent=`${game_team_data.game_name} · ${game_driver_data.game_name} · ${game_round_data.game_venue}${game_round_data.game_sprint?' · Sprint weekend':''}`;
        game_start.disabled=false;
    }else{game_summary.textContent='Seleziona scuderia, pilota e gara.';game_start.disabled=true;}
}

function game_startWeekend(){
    if(!game_selected_team||!game_selected_driver||!game_selected_round)return;
    game_state=game_createState(game_selected_team,game_selected_driver,game_selected_round);
    game_applyTeamTheme();
    document.getElementById('game_setup_overlay').hidden=true;
    game_prepareSession(true);
    game_save();
}

function game_applyTeamTheme(){
    const game_team_data=game_team();
    if(!game_team_data)return;
    document.documentElement.style.setProperty('--game_team',game_team_data.game_color);
    document.documentElement.style.setProperty('--game_team_soft',`${game_team_data.game_color}22`);
}

function game_prepareSession(game_first=false){
    const game_session_data=game_session();
    const game_round_data=game_roundData();
    if(!game_session_data)return;
    game_state.game_phase='briefing';
    game_state.game_lap=0;
    game_state.game_progress=0;
    game_state.game_elapsed=0;
    game_state.game_last_lap=null;
    game_state.game_best_lap=null;
    game_state.game_speed=0;
    game_state.game_tyre=game_session_data.game_type==='qualifying'?'SOFT':'MEDIUM';
    game_state.game_tyre_wear=0;
    game_state.game_tyre_age=0;
    game_state.game_tyre_temp=84;
    game_state.game_brake_temp=430;
    game_state.game_engine_temp=94;
    game_state.game_battery=game_session_data.game_type==='qualifying'?100:82;
    game_state.game_mode=game_session_data.game_type==='qualifying'?'push':'balanced';
    game_state.game_intent='neutral';
    game_state.game_fuel=game_sessionFuel(game_session_data);
    game_state.game_fuel_target=game_state.game_fuel;
    game_state.game_pit={game_requested:false,game_tyre:game_session_data.game_type==='qualifying'?'SOFT':'MEDIUM',game_wing:0,game_repair:false,game_stop_mode:'safe',game_ready:false,game_last_stop:null};
    game_state.game_question_cooldown=game_rand(14,24);
    game_state.game_event_cooldown=game_rand(14,24);
    game_state.game_pending_question=null;
    game_state.game_track_wetness=0;
    game_state.game_weather=Math.random()<game_round_data.game_rain/100*.32?'CLOUDY':'DRY';
    game_state.game_rain_probability=game_round_data.game_rain;
    if(game_session_data.game_key==='sprint')game_state.game_position=game_state.game_sprint_grid;
    else if(game_session_data.game_key==='race')game_state.game_position=game_state.game_race_grid;
    else game_state.game_position=game_clamp(game_expectedPosition()+Math.round(game_rand(-2,2)),1,22);
    game_state.game_gap_ahead=game_rand(.7,2.2);
    game_state.game_gap_back=game_rand(.7,2.2);
    if(game_first)game_state.game_chat=[];
    game_addMessage('system',`${game_round_data.game_venue.toUpperCase()} · ${game_session_data.game_name.toUpperCase()} BRIEFING`);
    game_addMessage('driver',game_openingRadio());
    game_addMessage('crew',`Garage to pit wall. Crew standing by. Current default set: ${game_state.game_pit.game_tyre}.`);
    game_renderAll();
}

function game_sessionFuel(game_session_data){
    const game_per_lap=1.52;
    const game_margin=game_session_data.game_type==='race'?2.4:4.0;
    return game_round(game_session_data.game_laps*game_per_lap+game_margin,1);
}

function game_openingRadio(){
    const game_session_data=game_session();
    const game_driver_data=game_driver();
    if(game_session_data.game_key==='fp1')return `Radio check. ${game_driver_data.game_name} in the car. Tell me what you want me to work on and I'll report the balance.`;
    if(game_session_data.game_type==='practice')return `Okay, ready for ${game_session_data.game_name}. What is the run plan?`;
    if(game_session_data.game_type==='qualifying')return `Tyres are cold. Keep me clear of traffic and call the push lap.`;
    if(game_session_data.game_key==='sprint')return `Starting P${game_state.game_position}. Short race — keep the calls simple.`;
    return `Starting P${game_state.game_position}. Keep me updated on gaps, tyres, fuel and weather. Let's do this.`;
}

function game_startPauseSession(){
    if(!game_state||game_state.game_completed)return;
    if(game_state.game_phase==='briefing'){
        game_state.game_phase='running';
        game_state.game_lap=1;
        game_addMessage('system',`${game_session().game_name.toUpperCase()} STARTED · TELEMETRY LIVE`);
        game_addMessage('driver','We are live. Radio clear.');
    }else if(game_state.game_phase==='running'){
        game_state.game_phase='paused';
        game_addMessage('system','SIMULATION PAUSED');
    }else if(game_state.game_phase==='paused'){
        game_state.game_phase='running';
        game_addMessage('system','SIMULATION RESUMED');
    }
    game_renderAll();
}

function game_tick(){
    if(!game_state||game_state.game_phase!=='running')return;
    const game_session_data=game_session();
    const game_sim_seconds=(game_tick_ms/1000)*game_state.game_speed_multiplier;
    const game_target_lap=Math.max(58,game_roundData().game_lap_base+game_paceModifier());
    const game_lap_fraction=game_sim_seconds/game_target_lap;
    game_state.game_elapsed+=game_sim_seconds;
    game_state.game_progress+=game_lap_fraction;
    game_state.game_question_cooldown-=game_sim_seconds;
    game_state.game_event_cooldown-=game_sim_seconds;
    game_updateTelemetry();
    game_updateResources(game_lap_fraction,game_sim_seconds);
    game_updateWeather(game_sim_seconds);
    game_updateRadioEvents(game_sim_seconds);
    if(game_state.game_progress>=1){game_state.game_progress-=1;game_completeLap(game_target_lap);}
    if(game_state.game_phase==='running')game_renderLive();
    if(game_session_data.game_type==='qualifying'&&game_state.game_lap>0&&game_state.game_lap%2===0)game_updateQualifyingPosition();
}

function game_paceModifier(){
    const game_team_data=game_team();
    const game_driver_data=game_driver();
    const game_round_data=game_roundData();
    const game_rating=game_packageRating();
    const game_mode_delta={game_push:-.82,game_balanced:0,game_manage:.55,game_lift:1.05,game_recharge:1.15,game_deploy:-.48}[`game_${game_state.game_mode}`]??0;
    const game_intent_delta=game_state.game_intent==='attack'?-.28:game_state.game_intent==='defend'?.22:0;
    const game_wear_delta=Math.max(0,game_state.game_tyre_wear-28)*.035;
    const game_damage_delta=game_state.game_damage.game_front*.020+game_state.game_damage.game_floor*.025+game_state.game_damage.game_rear*.012+game_state.game_damage.game_suspension*.04+game_state.game_damage.game_engine*.03;
    const game_package_delta=(90-game_rating)*.09;
    const game_consistency_noise=game_rand(-1,1)*((103-game_driver_data.game_consistency)/100)*.75;
    let game_wet_delta=0;
    if(game_state.game_track_wetness>8){
        game_wet_delta+=(95-game_driver_data.game_wet)*.035;
        if(!['INTER','WET'].includes(game_state.game_tyre))game_wet_delta+=game_state.game_track_wetness*.075;
        if(game_state.game_tyre==='WET'&&game_state.game_track_wetness<35)game_wet_delta+=2.6;
        if(game_state.game_tyre==='INTER'&&game_state.game_track_wetness>65)game_wet_delta+=1.8;
    }else if(['INTER','WET'].includes(game_state.game_tyre)){game_wet_delta+=game_state.game_tyre==='WET'?4.5:2.2;}
    const game_traffic_delta=game_session().game_type==='qualifying'&&game_state.game_gap_ahead<.8?game_rand(.7,1.6):0;
    const game_battery_delta=Math.max(0,28-game_state.game_battery)*.018;
    const game_reliability_delta=(90-game_team_data.game_reliability)*.008;
    return game_package_delta+game_mode_delta+game_intent_delta+game_wear_delta+game_damage_delta+game_consistency_noise+game_wet_delta+game_traffic_delta+game_battery_delta+game_reliability_delta+(1-game_round_data.game_overtake)*.05;
}

function game_updateTelemetry(){
    const game_curve=Math.abs(Math.sin(game_state.game_progress*Math.PI*8.4));
    const game_braking=Math.pow(game_curve,7);
    const game_attack=['push','deploy'].includes(game_state.game_mode)?1.035:game_state.game_mode==='manage'?.97:1;
    let game_speed=320*game_attack-game_braking*215+Math.sin(game_state.game_progress*26)*17;
    game_speed-=game_state.game_damage.game_front*.18+game_state.game_damage.game_floor*.15+game_state.game_damage.game_engine*.25;
    game_speed*=1-game_state.game_track_wetness*.0025;
    game_state.game_speed=game_clamp(game_speed,55,340);
    game_state.game_brake=game_clamp(game_braking*100,0,100);
    game_state.game_throttle=game_clamp(100-game_state.game_brake-Math.max(0,Math.sin(game_state.game_progress*20))*8,0,100);
    game_state.game_gear=game_clamp(Math.round((game_state.game_speed-35)/42),1,8);
    game_state.game_rpm=game_clamp(7600+game_state.game_speed*18,7600,12000);
}

function game_updateResources(game_lap_fraction,game_sim_seconds){
    const game_driver_data=game_driver();
    const game_compound_data=game_compound();
    const game_mode_wear={game_push:1.28,game_balanced:1,game_manage:.72,game_lift:.78,game_recharge:.92,game_deploy:1.14}[`game_${game_state.game_mode}`]??1;
    const game_skill_wear=1-(game_driver_data.game_tyres-75)*.004;
    game_state.game_tyre_wear=game_clamp(game_state.game_tyre_wear+3.15*game_compound_data.game_wear*game_mode_wear*game_skill_wear*game_lap_fraction,0,100);
    const game_fuel_factor=game_state.game_mode==='push'?1.08:game_state.game_mode==='lift'?.76:game_state.game_mode==='manage'?.91:1;
    game_state.game_fuel=Math.max(0,game_state.game_fuel-1.52*game_fuel_factor*game_lap_fraction);
    const game_battery_change={game_push:-7,game_balanced:-1.4,game_manage:1.8,game_lift:4.5,game_recharge:10,game_deploy:-15}[`game_${game_state.game_mode}`]??0;
    game_state.game_battery=game_clamp(game_state.game_battery+game_battery_change*game_lap_fraction,0,100);
    const game_target_tyre=game_state.game_track_wetness>20?76:94+(game_state.game_mode==='push'?7:0);
    game_state.game_tyre_temp+=((game_target_tyre-game_state.game_tyre_temp)*Math.min(.16,game_sim_seconds*.018));
    game_state.game_brake_temp+=(520+game_state.game_brake*2-game_state.game_brake_temp)*Math.min(.12,game_sim_seconds*.014);
    game_state.game_engine_temp+=(97+(game_state.game_mode==='push'?8:0)-game_state.game_engine_temp)*Math.min(.1,game_sim_seconds*.012);
}

function game_updateWeather(game_sim_seconds){
    if(game_state.game_event_cooldown>0)return;
    game_state.game_event_cooldown=game_rand(18,34);
    const game_round_data=game_roundData();
    if(game_state.game_weather!=='RAIN'&&Math.random()<game_round_data.game_rain/100*.13){
        game_state.game_weather='RAIN';
        game_state.game_rain_probability=game_clamp(game_state.game_rain_probability+25,0,100);
        game_addMessage('system','WEATHER CHANGE · RAIN REPORTED AROUND THE CIRCUIT',true);
        game_addMessage('driver','Rain on the visor now. Grip is dropping. What do you want to do?',true);
        game_setPendingQuestion('weather');
    }else if(game_state.game_weather==='RAIN'&&Math.random()<.18){
        game_state.game_weather='CLOUDY';
        game_addMessage('driver','Rain is easing. There is still water offline.');
    }
    if(game_state.game_weather==='RAIN')game_state.game_track_wetness=game_clamp(game_state.game_track_wetness+game_sim_seconds*.12,0,100);
    else game_state.game_track_wetness=game_clamp(game_state.game_track_wetness-game_sim_seconds*.07,0,100);
}

function game_updateRadioEvents(game_sim_seconds){
    if(game_state.game_pending_question&&game_state.game_elapsed>game_state.game_pending_question.game_expires){
        game_state.game_score=game_clamp(game_state.game_score-3,0,100);
        game_state.game_confidence=game_clamp(game_state.game_confidence-2,0,100);
        game_addMessage('driver','I needed that call earlier. I had to make the decision myself.');
        game_state.game_pending_question=null;
    }
    if(game_state.game_question_cooldown>0||game_state.game_pending_question)return;
    game_state.game_question_cooldown=game_rand(24,45);
    if(game_state.game_tyre_wear>58){game_addMessage('driver',`Tyres are going away now. Rear is sliding on exit. Manage or keep pushing?`,true);game_setPendingQuestion('tyres');return;}
    if(game_state.game_battery<22){game_addMessage('driver','Battery is low. Do you want recharge or keep deploying?',true);game_setPendingQuestion('battery');return;}
    if(game_fuelDelta()<-1.0){game_addMessage('driver','Fuel number looks tight. Give me a target.',true);game_setPendingQuestion('fuel');return;}
    if(game_maxDamage()>28){game_addMessage('driver','Something does not feel right with the car. Can you check the damage?',true);game_setPendingQuestion('damage');return;}
    const game_random_question=Math.random();
    if(game_random_question<.35){game_addMessage('driver','What is the gap ahead? Can I attack?');game_setPendingQuestion('gap');}
    else if(game_random_question<.68){game_addMessage('driver','How do the tyres look from your side?');game_setPendingQuestion('tyres');}
    else{game_addMessage('driver','Car balance is changing. Want detailed feedback?');game_setPendingQuestion('feedback');}
}

function game_setPendingQuestion(game_type){game_state.game_pending_question={game_type,game_expires:game_state.game_elapsed+35};}
function game_resolveQuestion(game_bonus=2){if(game_state.game_pending_question){game_state.game_score=game_clamp(game_state.game_score+game_bonus,0,100);game_state.game_confidence=game_clamp(game_state.game_confidence+1,0,100);game_state.game_pending_question=null;}}
function game_fuelDelta(){const game_session_data=game_session();const game_laps_left=Math.max(0,game_session_data.game_laps-game_state.game_lap+1);const game_needed=game_laps_left*1.52;return game_state.game_fuel-game_needed;}
function game_maxDamage(){return Math.max(...Object.values(game_state.game_damage));}

function game_completeLap(game_target_lap){
    const game_session_data=game_session();
    const game_lap_time=game_target_lap+game_rand(-.18,.18);
    game_state.game_last_lap=game_lap_time;
    game_state.game_best_lap=game_state.game_best_lap===null?game_lap_time:Math.min(game_state.game_best_lap,game_lap_time);
    game_state.game_tyre_age+=1;
    if(game_state.game_pit.game_requested)game_executePitStop();
    game_resolveRaceTraffic();
    game_maybeIncident();
    if(game_session_data.game_type==='qualifying')game_updateQualifyingPosition();
    if(game_state.game_lap>=game_session_data.game_laps){game_finishSession();return;}
    game_state.game_lap+=1;
    game_state.game_gap_ahead=game_clamp(game_state.game_gap_ahead+game_rand(-.42,.38),.25,4.8);
    game_state.game_gap_back=game_clamp(game_state.game_gap_back+game_rand(-.38,.42),.25,4.8);
    if(game_state.game_lap%3===0)game_save();
}

function game_updateQualifyingPosition(){
    if(game_session().game_type!=='qualifying')return;
    const game_score_bonus=(game_state.game_score-50)/18;
    const game_tyre_penalty=Math.max(0,game_state.game_tyre_wear-35)/20;
    const game_position=game_expectedPosition()+game_rand(-2.2,2.2)-game_score_bonus+game_tyre_penalty;
    game_state.game_position=game_clamp(Math.round(game_position),1,22);
}

function game_resolveRaceTraffic(){
    if(game_session().game_type!=='race')return;
    const game_driver_data=game_driver();
    const game_round_data=game_roundData();
    const game_attack_bonus=game_state.game_intent==='attack'?.18:0;
    const game_push_bonus=['push','deploy'].includes(game_state.game_mode)?.13:0;
    const game_overtake_chance=.08+game_round_data.game_overtake*.18+(game_driver_data.game_aggression-75)*.004+game_attack_bonus+game_push_bonus;
    if(game_state.game_position>1&&game_state.game_gap_ahead<1.35&&Math.random()<game_overtake_chance){
        game_state.game_position-=1;
        game_state.game_gap_ahead=game_rand(.7,1.9);
        game_state.game_gap_back=game_rand(.4,1.1);
        game_addMessage('driver',`Got him. We are P${game_state.game_position}.`);
        game_state.game_score=game_clamp(game_state.game_score+1,0,100);
    }
    const game_vulnerable=['manage','lift','recharge'].includes(game_state.game_mode)||game_state.game_tyre_wear>65;
    const game_defend_bonus=game_state.game_intent==='defend'?.14:0;
    const game_lost_chance=(game_vulnerable?.19:.08)-game_defend_bonus;
    if(game_state.game_position<22&&game_state.game_gap_back<1.05&&Math.random()<game_lost_chance){
        game_state.game_position+=1;
        game_state.game_gap_back=game_rand(.8,1.8);
        game_addMessage('driver',`Lost the position. P${game_state.game_position} now.`);
    }
}

function game_maybeIncident(){
    const game_team_data=game_team();
    const game_driver_data=game_driver();
    let game_chance=.006+(100-game_team_data.game_reliability)*.00024+(game_driver_data.game_aggression-75)*.00022;
    if(game_state.game_mode==='push'||game_state.game_intent==='attack')game_chance+=.006;
    if(game_state.game_track_wetness>30)game_chance+=.008;
    if(Math.random()>game_chance)return;
    const game_part=game_pick(['game_front','game_floor','game_suspension','game_engine']);
    const game_amount=game_rand(8,28);
    game_state.game_damage[game_part]=game_clamp(game_state.game_damage[game_part]+game_amount,0,100);
    const game_part_name={game_front:'front wing',game_floor:'floor',game_suspension:'suspension',game_engine:'power unit'}[game_part];
    game_addMessage('driver',`I felt a hit — ${game_part_name} might be damaged. Check it.`,true);
    game_addMessage('crew',`Telemetry flag: possible ${game_part_name} damage.`,true);
    game_setPendingQuestion('damage');
}

function game_executePitStop(){
    const game_team_data=game_team();
    const game_pit=game_state.game_pit;
    let game_stop=2.35+(100-game_team_data.game_pitcrew)*.018+game_rand(-.16,.28);
    if(!game_pit.game_ready){game_stop+=1.4;game_state.game_score=game_clamp(game_state.game_score-3,0,100);game_addMessage('crew','Crew was not fully set when the car arrived. Slow stop.',true);}
    if(game_pit.game_stop_mode==='fast'){
        game_stop-=.28;
        if(Math.random()>(game_team_data.game_pitcrew/108)){game_stop+=2.8;game_addMessage('crew','Problem on the stop! One wheel was slow.',true);game_state.game_score=game_clamp(game_state.game_score-2,0,100);}
    }else{game_stop+=.12;}
    game_stop=Math.max(1.8,game_stop);
    game_state.game_tyre=game_pit.game_tyre;
    game_state.game_tyre_wear=0;
    game_state.game_tyre_age=0;
    game_state.game_tyre_temp=72;
    if(game_pit.game_repair){
        game_state.game_damage.game_front=Math.max(0,game_state.game_damage.game_front-80);
        game_state.game_damage.game_rear=Math.max(0,game_state.game_damage.game_rear-55);
        game_stop+=3.5;
    }
    if(game_pit.game_wing!==0){game_stop+=.6+Math.abs(game_pit.game_wing)*.25;game_state.game_damage.game_front=Math.max(0,game_state.game_damage.game_front-15);}
    const game_places_lost=game_clamp(Math.round(2+(game_roundData().game_overtake<.4?2:0)+game_stop/2.8),2,7);
    game_state.game_position=game_clamp(game_state.game_position+game_places_lost,1,22);
    game_state.game_pit.game_last_stop=game_stop;
    game_state.game_pit.game_requested=false;
    game_state.game_pit.game_ready=false;
    game_addMessage('crew',`Stop complete: ${game_stop.toFixed(2)}s. ${game_state.game_tyre} fitted. Car released P${game_state.game_position}.`);
    game_addMessage('driver',`Back out. Tyres cold. P${game_state.game_position}.`);
}

function game_finishSession(){
    game_state.game_phase='finished';
    const game_session_data=game_session();
    if(game_session_data.game_key==='sprint_qualifying')game_state.game_sprint_grid=game_state.game_position;
    if(game_session_data.game_key==='qualifying')game_state.game_race_grid=game_state.game_position;
    game_state.game_session_results.push({game_key:game_session_data.game_key,game_name:game_session_data.game_name,game_position:game_state.game_position,game_best_lap:game_state.game_best_lap,game_score:game_state.game_score});
    game_addMessage('system',`${game_session_data.game_name.toUpperCase()} COMPLETE · P${game_state.game_position}`);
    game_addMessage('driver',game_state.game_position<=3?'Nice work. That was a strong session.':game_state.game_score>=60?'Good job. We got most of it right.':'We left something on the table there. Let’s review it.');
    game_showResult();
    game_save();
    game_renderAll();
}

function game_showResult(){
    const game_overlay=document.getElementById('game_result_overlay');
    const game_session_data=game_session();
    document.getElementById('game_result_title').textContent=`${game_session_data.game_name}: P${game_state.game_position}`;
    document.getElementById('game_result_text').textContent=game_state.game_session_index===game_state.game_sessions.length-1?'Weekend complete. Your final race result and engineer score are locked in.':'Session complete. The radio, setup knowledge and your decisions carry into the next session.';
    document.getElementById('game_result_stats').innerHTML=`<div class="game_result_stat"><small>POSITION</small><strong>P${game_state.game_position}</strong></div><div class="game_result_stat"><small>BEST LAP</small><strong>${game_formatLap(game_state.game_best_lap)}</strong></div><div class="game_result_stat"><small>ENGINEER</small><strong>${Math.round(game_state.game_score)}</strong></div>`;
    const game_button=document.getElementById('game_result_continue');
    game_button.textContent=game_state.game_session_index===game_state.game_sessions.length-1?'CLOSE WEEKEND':'CONTINUE WEEKEND';
    game_overlay.hidden=false;
}

function game_continueWeekend(){
    document.getElementById('game_result_overlay').hidden=true;
    if(game_state.game_session_index>=game_state.game_sessions.length-1){
        game_state.game_completed=true;
        game_state.game_phase='weekend_complete';
        game_renderAll();
        game_save();
        return;
    }
    game_state.game_session_index+=1;
    game_prepareSession(false);
    game_save();
}

function game_sendRadio(game_text){
    if(!game_state)return;
    const game_clean=String(game_text||'').trim();
    if(!game_clean)return;
    game_addMessage('engineer',game_clean);
    if(game_channel==='crew')game_handleCrewCommand(game_clean);else game_handleDriverCommand(game_clean);
    document.getElementById('game_radio_input').value='';
    game_save();
    game_renderAll();
}

function game_handleDriverCommand(game_text){
    const game_message=game_text.toLowerCase();
    const game_driver_data=game_driver();
    if(game_hasAny(game_message,['push','spingi','full send','go for it'])){
        game_state.game_mode='push';game_state.game_intent='attack';game_driverReply(game_pick(['Copy. Pushing now.','Understood, maximum pace.','Copy, I’ll use what I have.']));game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['manage','gestisci','save tyres','save tires','tyre save','tire save'])){
        game_state.game_mode='manage';game_driverReply('Copy, managing the tyres. I’ll protect the exits.');game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['lift','coast','save fuel','risparmia','fuel save'])){
        game_state.game_mode='lift';game_driverReply('Copy, lift and coast. Give me the target if it changes.');game_resolveQuestion(3);return;
    }
    if(game_hasAny(game_message,['recharge','harvest','ricarica'])){
        game_state.game_mode='recharge';game_driverReply('Recharge mode, copy. I’ll harvest this lap.');game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['deploy','overtake','use battery','batteria','attack battery'])){
        game_state.game_mode='deploy';game_state.game_intent='attack';game_driverReply('Deploy, copy. Using battery now.');game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['box','pit this lap','rientra','pit now'])){
        game_state.game_pit.game_requested=true;game_driverReply(game_state.game_pit.game_ready?'Box this lap, copy.':'Box this lap, copy. Is the crew ready?');game_resolveQuestion(3);return;
    }
    if(game_hasAny(game_message,['stay out','resta fuori','do not box','no box'])){
        game_state.game_pit.game_requested=false;game_driverReply('Staying out.');game_resolveQuestion(1);return;
    }
    if(game_hasAny(game_message,['attack car','attack ahead','attacca','race him'])){
        game_state.game_intent='attack';game_driverReply(`Copy. I’ll attack. Gap is ${game_state.game_gap_ahead.toFixed(1)}.`);game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['defend','difendi','hold position'])){
        game_state.game_intent='defend';game_driverReply('Copy, defending the position.');game_resolveQuestion(1);return;
    }
    if(game_hasAny(game_message,['feedback','balance','bilanciamento','how is the car','come va','front?','rear?'])){
        game_driverReply(game_feedbackReport(game_driver_data));game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['tyre','tire','gomme','gomma','temperatures'])){
        game_driverReply(game_tyreReport());game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['gap','distacco','car ahead','car behind'])){
        game_driverReply(`Ahead ${game_state.game_gap_ahead.toFixed(1)}s, behind ${game_state.game_gap_back.toFixed(1)}s. I’m P${game_state.game_position}.`);game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['weather','rain','meteo','pioggia'])){
        game_driverReply(game_state.game_weather==='RAIN'?`Rain is definitely here. Wetness feels around ${Math.round(game_state.game_track_wetness)}%.`:`Track feels ${game_state.game_weather.toLowerCase()}. No standing water from the cockpit.`);game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['fuel','benzina'])){
        const game_delta=game_fuelDelta();game_driverReply(game_delta<0?`Copy. Fuel is tight by about ${Math.abs(game_delta).toFixed(1)} kg. Tell me to lift if needed.`:`Fuel looks okay, about +${game_delta.toFixed(1)} kg to target.`);game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['damage','danno','car status'])){
        game_driverReply(game_damageDriverReport());game_resolveQuestion(2);return;
    }
    if(game_hasAny(game_message,['copy','understood','ok','okay'])){game_driverReply('Copy.');return;}
    game_driverReply(game_driver_data.game_feedback>=90?'I did not understand that call. Give me a clear instruction or ask for specific feedback.':'Say again? Keep the radio call short.');
}

function game_handleCrewCommand(game_text){
    const game_message=game_text.toLowerCase();
    if(game_message.includes('prepare')||game_message.includes('prepara')){
        const game_tyre=game_parseTyre(game_message);
        if(game_tyre){game_state.game_pit.game_tyre=game_tyre;game_state.game_pit.game_ready=true;game_crewReply(`${game_tyre} set coming out. Crew ready for the car.`);return;}
    }
    if(game_hasAny(game_message,['wing +1','ala +1'])){game_state.game_pit.game_wing=1;game_crewReply('Front wing +1 click confirmed for the stop.');return;}
    if(game_hasAny(game_message,['wing -1','ala -1'])){game_state.game_pit.game_wing=-1;game_crewReply('Front wing -1 click confirmed for the stop.');return;}
    if(game_hasAny(game_message,['repair wing','ripara ala','repair'])){game_state.game_pit.game_repair=true;game_crewReply('Wing repair selected. Expect extra stationary time.');return;}
    if(game_hasAny(game_message,['no repair','nessuna riparazione'])){game_state.game_pit.game_repair=false;game_crewReply('Copy. No repair work at the stop.');return;}
    if(game_hasAny(game_message,['fast stop','push stop'])){game_state.game_pit.game_stop_mode='fast';game_crewReply('Fast-stop protocol selected. Higher execution risk.');return;}
    if(game_hasAny(game_message,['safe stop','clean stop'])){game_state.game_pit.game_stop_mode='safe';game_crewReply('Safe-stop protocol selected.');return;}
    if(game_hasAny(game_message,['check damage','inspect','controlla danni'])){game_crewReply(game_damageCrewReport());return;}
    if(game_hasAny(game_message,['pit crew ready','crew ready','stand by','standby'])){game_state.game_pit.game_ready=true;game_crewReply(`Crew ready. ${game_state.game_pit.game_tyre} tyres, wing ${game_state.game_pit.game_wing>=0?'+':''}${game_state.game_pit.game_wing}, ${game_state.game_pit.game_stop_mode} stop.`);return;}
    game_crewReply('Pit wall, repeat the order. We need tyre, wing, repair, stop mode or ready command.');
}

function game_parseTyre(game_message){if(game_message.includes('soft'))return 'SOFT';if(game_message.includes('medium'))return 'MEDIUM';if(game_message.includes('hard'))return 'HARD';if(game_message.includes('inter'))return 'INTER';if(game_message.includes('wet'))return 'WET';return null;}
function game_hasAny(game_message,game_needles){return game_needles.some(game_needle=>game_message.includes(game_needle));}
function game_driverReply(game_text,game_urgent=false){game_addMessage('driver',game_text,game_urgent);}
function game_crewReply(game_text,game_urgent=false){game_addMessage('crew',game_text,game_urgent);}

function game_feedbackReport(game_driver_data){
    const game_precision=game_driver_data.game_feedback;
    const game_front_issue=game_state.game_damage.game_front>18||game_state.game_tyre_wear>52;
    const game_rear_issue=game_state.game_tyre_temp>103||game_state.game_tyre_wear>66;
    if(game_precision>=90){
        if(game_front_issue)return `Entry is weak. Front washes out after initial turn-in, especially medium speed. It got worse as the tyre went away.`;
        if(game_rear_issue)return `Rear is moving on traction and temperature is building. I can manage it, but we are losing exit.`;
        return `Balance is good. Slight understeer mid-corner, traction is stable. I can push more if you need it.`;
    }
    if(game_front_issue)return 'Front is not really there. I’m fighting understeer.';
    if(game_rear_issue)return 'Rear is moving around a lot on exit.';
    return 'Car feels okay. Maybe a little lazy in the middle of the corner.';
}

function game_tyreReport(){
    if(game_state.game_tyre_wear>75)return `Tyres are finished. Very little rear grip now. Wear feels severe.`;
    if(game_state.game_tyre_wear>50)return `Tyres are fading. I can keep them alive if we manage for a few laps.`;
    if(game_state.game_tyre_temp>104)return `Tyres are overheating. Surface is going away in the long corners.`;
    if(game_state.game_tyre_temp<80)return `Tyres are still cold. I need another lap before full push.`;
    return `Tyres feel in the window. ${game_state.game_tyre}, ${game_state.game_tyre_age} laps old.`;
}

function game_damageDriverReport(){
    const game_max=game_maxDamage();
    if(game_max<8)return 'Car feels structurally okay from here.';
    if(game_state.game_damage.game_front===game_max)return 'Front load is down. I think the front wing took damage.';
    if(game_state.game_damage.game_floor===game_max)return 'Rear platform feels unstable in high speed. Could be floor damage.';
    if(game_state.game_damage.game_suspension===game_max)return 'Steering is not straight. Suspension may be damaged.';
    if(game_state.game_damage.game_engine===game_max)return 'Power delivery feels strange. Check the power unit.';
    return 'Something is damaged but I cannot isolate it from the cockpit.';
}

function game_damageCrewReport(){
    const game_damage=game_state.game_damage;
    return `Damage scan: front ${Math.round(game_damage.game_front)}%, floor ${Math.round(game_damage.game_floor)}%, rear ${Math.round(game_damage.game_rear)}%, suspension ${Math.round(game_damage.game_suspension)}%, PU ${Math.round(game_damage.game_engine)}%.`;
}

function game_addMessage(game_speaker,game_text,game_urgent=false){
    if(!game_state)return;
    game_state.game_chat.push({game_speaker,game_text,game_urgent,game_time:game_nowLabel()});
    if(game_state.game_chat.length>90)game_state.game_chat.splice(0,game_state.game_chat.length-90);
    game_renderChat();
}

function game_renderChat(){
    if(!game_state)return;
    const game_log=document.getElementById('game_radio_log');
    game_log.innerHTML=game_state.game_chat.map(game_message=>`<div class="game_message game_${game_message.game_speaker}${game_message.game_urgent?' game_urgent':''}"><div class="game_message_meta">${game_message.game_speaker==='driver'?game_escape(game_driver().game_name.toUpperCase()):game_message.game_speaker==='crew'?'PIT CREW':game_message.game_speaker==='engineer'?'YOU · RACE ENGINEER':'RACE CONTROL'} · ${game_message.game_time}</div><div class="game_message_bubble">${game_escape(game_message.game_text)}</div></div>`).join('');
    game_log.scrollTop=game_log.scrollHeight;
}

function game_renderAll(){
    if(!game_state)return;
    game_applyTeamTheme();
    game_renderIdentity();
    game_renderSessionStrip();
    game_renderPackage();
    game_renderChat();
    game_renderLive();
    game_renderControls();
}

function game_renderIdentity(){
    const game_round_data=game_roundData();
    const game_team_data=game_team();
    const game_driver_data=game_driver();
    document.getElementById('game_round_label').textContent=`ROUND ${game_round_data.game_round}`;
    document.getElementById('game_gp_label').textContent=`${game_round_data.game_venue} · ${game_round_data.game_dates}`;
    document.getElementById('game_sprint_badge').hidden=!game_round_data.game_sprint;
    document.getElementById('game_driver_name').textContent=game_driver_data.game_name;
    document.getElementById('game_team_name').textContent=game_team_data.game_name;
    document.getElementById('game_driver_number').textContent=game_driver_data.game_number;
    document.getElementById('game_car_number').textContent=game_driver_data.game_number;
}

function game_renderSessionStrip(){
    document.getElementById('game_session_strip').innerHTML=game_state.game_sessions.map((game_session_item,game_index)=>`<div class="game_session_step${game_index===game_state.game_session_index?' game_current':''}${game_index<game_state.game_session_index?' game_done':''}"><span><strong>${game_escape(game_session_item.game_name)}</strong><small>${game_session_item.game_type.toUpperCase()}</small></span></div>`).join('');
}

function game_renderPackage(){
    const game_team_data=game_team();
    const game_driver_data=game_driver();
    document.getElementById('game_package_title').textContent=`${game_team_data.game_short} · #${game_driver_data.game_number}`;
    const game_rows=[['Car pace',game_team_data.game_pace],['Reliability',game_team_data.game_reliability],['Pit crew',game_team_data.game_pitcrew],['Driver pace',game_driver_data.game_pace],['Feedback',game_driver_data.game_feedback],['Tyre mgmt',game_driver_data.game_tyres],['Wet skill',game_driver_data.game_wet]];
    document.getElementById('game_skill_rows').innerHTML=game_rows.map(([game_label,game_value])=>`<div class="game_skill_row"><span>${game_label}</span><div class="game_skill_bar"><div class="game_skill_fill" style="width:${game_value}%"></div></div><b>${game_value}</b></div>`).join('');
}

function game_renderLive(){
    if(!game_state)return;
    const game_session_data=game_session();
    const game_phase_label={game_briefing:'BRIEFING',game_running:'LIVE',game_paused:'PAUSED',game_finished:'FINISHED',game_weekend_complete:'WEEKEND COMPLETE'}[`game_${game_state.game_phase}`]||game_state.game_phase.toUpperCase();
    const game_live_pill=document.getElementById('game_live_pill');
    game_live_pill.textContent=game_phase_label;
    game_live_pill.classList.toggle('game_is_live',game_state.game_phase==='running');
    document.getElementById('game_session_name').textContent=game_session_data.game_name;
    document.getElementById('game_session_subtitle').textContent=game_session_data.game_objective;
    document.getElementById('game_position').textContent=`P${game_state.game_position}`;
    document.getElementById('game_lap').textContent=`${game_state.game_lap} / ${game_session_data.game_laps}`;
    document.getElementById('game_sector').textContent=`S${game_state.game_progress<.34?1:game_state.game_progress<.68?2:3}`;
    document.getElementById('game_gap_ahead').textContent=game_state.game_position===1?'LEADER':`${game_state.game_gap_ahead.toFixed(1)}s`;
    document.getElementById('game_gap_back').textContent=game_state.game_position===22?'LAST':`${game_state.game_gap_back.toFixed(1)}s`;
    document.getElementById('game_speed').textContent=Math.round(game_state.game_speed);
    document.getElementById('game_tyre').textContent=game_compound().game_code;
    document.getElementById('game_tyre_wear').textContent=`${Math.round(game_state.game_tyre_wear)}% wear · ${game_state.game_tyre_age}L`;
    document.getElementById('game_fuel').textContent=`${game_state.game_fuel.toFixed(1)}kg`;
    const game_fuel_delta=game_fuelDelta();
    document.getElementById('game_fuel_delta').textContent=`${game_fuel_delta>=0?'+':''}${game_fuel_delta.toFixed(1)} kg target`;
    document.getElementById('game_battery').textContent=`${Math.round(game_state.game_battery)}%`;
    document.getElementById('game_mode').textContent=game_state.game_mode.toUpperCase();
    document.getElementById('game_weather').textContent=game_state.game_weather;
    document.getElementById('game_rain').textContent=`wet ${Math.round(game_state.game_track_wetness)}% · rain ${Math.round(game_state.game_rain_probability)}%`;
    document.getElementById('game_last_lap').textContent=game_formatLap(game_state.game_last_lap);
    document.getElementById('game_best_lap').textContent=`best ${game_formatLap(game_state.game_best_lap)}`;
    document.getElementById('game_score').textContent=Math.round(game_state.game_score);
    document.getElementById('game_confidence').textContent=`Driver confidence ${Math.round(game_state.game_confidence)}%`;
    game_renderDamage();
    game_renderTrackCar();
}

function game_renderDamage(){
    const game_damage=game_state.game_damage;
    const game_parts=[['game_damage_front',game_damage.game_front],['game_damage_floor',game_damage.game_floor],['game_damage_rear',game_damage.game_rear],['game_damage_suspension',game_damage.game_suspension],['game_damage_engine',game_damage.game_engine]];
    game_parts.forEach(([game_id,game_value])=>{const game_el=document.getElementById(game_id);game_el.classList.toggle('game_damage_minor',game_value>=10&&game_value<35);game_el.classList.toggle('game_damage_major',game_value>=35);});
    const game_max=game_maxDamage();
    const game_status=document.getElementById('game_car_status');
    game_status.textContent=game_max<10?'OK':game_max<35?'MINOR':'DAMAGED';
    game_status.style.color=game_max<10?'var(--game_green)':game_max<35?'var(--game_yellow)':'var(--game_red)';
}

function game_renderTrackCar(){
    const game_path=document.getElementById('game_track_line');
    const game_car=document.getElementById('game_track_car');
    if(!game_path||!game_car||typeof game_path.getTotalLength!=='function')return;
    const game_length=game_path.getTotalLength();
    const game_point=game_path.getPointAtLength(game_length*game_state.game_progress);
    game_car.setAttribute('cx',game_point.x);game_car.setAttribute('cy',game_point.y);
}

function game_renderControls(){
    const game_button=document.getElementById('game_session_button');
    if(game_state.game_completed){game_button.textContent='WEEKEND COMPLETE';game_button.disabled=true;game_button.classList.remove('game_pause');}
    else if(game_state.game_phase==='briefing'){game_button.textContent=`START ${game_session().game_name.toUpperCase()}`;game_button.disabled=false;game_button.classList.remove('game_pause');}
    else if(game_state.game_phase==='running'){game_button.textContent='PAUSE';game_button.disabled=false;game_button.classList.add('game_pause');}
    else if(game_state.game_phase==='paused'){game_button.textContent='RESUME';game_button.disabled=false;game_button.classList.remove('game_pause');}
    else{game_button.textContent='SESSION COMPLETE';game_button.disabled=true;game_button.classList.remove('game_pause');}
    document.querySelectorAll('[data-game-speed]').forEach(game_speed_button=>game_speed_button.classList.toggle('game_speed_active',Number(game_speed_button.dataset.gameSpeed)===game_state.game_speed_multiplier));
    const game_crew_state=document.getElementById('game_crew_state');
    game_crew_state.textContent=game_state.game_pit.game_ready?`${game_state.game_pit.game_tyre} READY`:'STANDBY';
    game_crew_state.classList.toggle('game_ready',game_state.game_pit.game_ready);
}

function game_renderCalendarModal(){
    document.getElementById('game_calendar_full').innerHTML=game_data.game_calendar.map(game_round_item=>`<div class="game_calendar_row"><strong>R${game_round_item.game_round} · ${game_escape(game_round_item.game_venue)}</strong><small>${game_escape(game_round_item.game_dates)} · ${game_escape(game_round_item.game_country)}</small>${game_round_item.game_sprint?'<span>SPRINT</span>':''}</div>`).join('');
}

function game_setChannel(game_next_channel){
    game_channel=game_next_channel;
    document.querySelectorAll('[data-game-channel]').forEach(game_button=>game_button.classList.toggle('game_channel_active',game_button.dataset.gameChannel===game_channel));
    document.getElementById('game_channel_prefix').textContent=game_channel==='driver'?'DRIVER':'CREW';
    document.getElementById('game_radio_input').placeholder=game_channel==='driver'?"Es. 'push now', 'how are the tyres?', 'box this lap'...":"Es. 'prepare medium', 'wing +1', 'pit crew ready'...";
}

function game_save(){if(game_state)localStorage.setItem(game_storage_key,JSON.stringify(game_state));}
function game_restore(){
    try{
        const game_raw=localStorage.getItem(game_storage_key);if(!game_raw)return false;
        const game_saved=JSON.parse(game_raw);if(game_saved.game_version!==2)return false;
        if(!game_data.game_teams[game_saved.game_team_key]||!game_data.game_drivers[game_saved.game_driver_key]||!game_data.game_calendar.some(game_round_item=>game_round_item.game_key===game_saved.game_round_key))return false;
        game_state=game_saved;game_selected_team=game_state.game_team_key;game_selected_driver=game_state.game_driver_key;game_selected_round=game_state.game_round_key;game_applyTeamTheme();document.getElementById('game_setup_overlay').hidden=true;game_renderAll();return true;
    }catch(game_error){console.warn('game_restore failed',game_error);return false;}
}

function game_newWeekend(){
    if(game_state){game_selected_team=game_state.game_team_key;game_selected_driver=game_state.game_driver_key;game_selected_round=game_state.game_round_key;}
    game_renderSetup();document.getElementById('game_setup_overlay').hidden=false;
}

function game_bindEvents(){
    document.getElementById('game_start_weekend').addEventListener('click',game_startWeekend);
    document.getElementById('game_new_weekend_button').addEventListener('click',game_newWeekend);
    document.getElementById('game_calendar_button').addEventListener('click',()=>{game_renderCalendarModal();document.getElementById('game_calendar_overlay').hidden=false;});
    document.getElementById('game_calendar_close').addEventListener('click',()=>{document.getElementById('game_calendar_overlay').hidden=true;});
    document.getElementById('game_calendar_overlay').addEventListener('click',game_event=>{if(game_event.target.id==='game_calendar_overlay')game_event.currentTarget.hidden=true;});
    document.getElementById('game_result_continue').addEventListener('click',game_continueWeekend);
    document.getElementById('game_session_button').addEventListener('click',game_startPauseSession);
    document.getElementById('game_radio_form').addEventListener('submit',game_event=>{game_event.preventDefault();game_sendRadio(document.getElementById('game_radio_input').value);});
    document.querySelectorAll('[data-game-channel]').forEach(game_button=>game_button.addEventListener('click',()=>game_setChannel(game_button.dataset.gameChannel)));
    document.querySelectorAll('[data-game-radio]').forEach(game_button=>game_button.addEventListener('click',()=>{game_setChannel('driver');game_sendRadio(game_button.dataset.gameRadio);}));
    document.querySelectorAll('[data-game-crew]').forEach(game_button=>game_button.addEventListener('click',()=>{game_setChannel('crew');game_sendRadio(game_button.dataset.gameCrew);}));
    document.querySelectorAll('[data-game-command-example]').forEach(game_button=>game_button.addEventListener('click',()=>{game_setChannel('driver');const game_input=document.getElementById('game_radio_input');game_input.value=game_button.dataset.gameCommandExample;game_input.focus();}));
    document.querySelectorAll('[data-game-crew-example]').forEach(game_button=>game_button.addEventListener('click',()=>{game_setChannel('crew');const game_input=document.getElementById('game_radio_input');game_input.value=game_button.dataset.gameCrewExample;game_input.focus();}));
    document.querySelectorAll('[data-game-speed]').forEach(game_button=>game_button.addEventListener('click',()=>{if(!game_state)return;game_state.game_speed_multiplier=Number(game_button.dataset.gameSpeed);game_renderControls();game_save();}));
}

function game_boot(){
    if(!game_data){document.getElementById('game_app').innerHTML='<div style="padding:30px;color:white">game_data_2026 failed to load.</div>';return;}
    game_bindEvents();
    game_renderSetup();
    game_renderCalendarModal();
    game_setChannel('driver');
    game_restore();
    game_timer=setInterval(game_tick,game_tick_ms);
}

document.addEventListener('DOMContentLoaded',game_boot);

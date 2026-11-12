export const VERSION = 1;
export const SAVE_KEY = 'pitmetric.formula-lab.v1';
export const clamp = (n, min = 0, max = 100) => Math.max(min, Math.min(max, n));
const copy = (value) => structuredClone(value);
export const AXES = { wing: 'Carico alare', balance: 'Bilanciamento', suspension: 'Rigidità', gearing: 'Rapporti' };
export const STATS = { aero: 'Aerodinamica', power: 'Potenza', grip: 'Grip meccanico', care: 'Gestione gomme', reliability: 'Affidabilità' };
export const COMPOUNDS = { soft: { name: 'Soft', short: 'S', pace: -.65, wear: 5.6, color: '#ff6c77' }, medium: { name: 'Medium', short: 'M', pace: 0, wear: 3.6, color: '#ffd66e' }, hard: { name: 'Hard', short: 'H', pace: .48, wear: 2.5, color: '#e4eaf1' }, inter: { name: 'Intermedie', short: 'I', pace: 1, wear: 2.6, color: '#79d7ac' } };
export const CIRCUITS = [
    { name: 'Riviera', place: 'Tra mare e muretti', laps: 28, base: 78, weights: [ .45, .1, .45 ], wing: 78, gearing: 28, stiffness: -12, weather: 'dry', wear: .85, path: 'M100 140 Q90 70 160 65 L305 65 Q350 65 340 120 L290 180 Q275 205 310 220 L390 245 Q425 270 380 295 L245 310 Q205 315 220 265 L240 220 Q250 195 210 195 L140 210 Q95 215 100 140Z' },
    { name: 'Valleverde', place: 'Curve veloci, boschi e pioggia', laps: 32, base: 87, weights: [.5, .25, .25], wing: 62, gearing: 56, stiffness: 9, weather: 'rain', wear: 1, path: 'M90 260 L160 90 Q170 65 200 90 L250 140 L335 70 Q360 55 375 85 L420 190 Q430 220 400 235 L285 280 Q260 290 245 250 L220 210 L160 295 Q110 330 90 260Z' },
    { name: 'Autodromo del Sole', place: 'Rettifili e frenate profonde', laps: 30, base: 81, weights: [.15, .65, .2], wing: 25, gearing: 84, stiffness: 2, weather: 'dry', wear: .9, path: 'M100 270 L100 100 Q100 70 140 70 L180 70 L205 110 L235 70 L365 70 Q410 70 410 120 L410 235 Q410 280 365 280 L290 280 L260 245 L225 280 L140 310 Q100 320 100 270Z' },
    { name: 'Porto Notte', place: 'Asfalto caldo, trazione decisiva', laps: 34, base: 91, weights: [.3, .15, .55], wing: 68, gearing: 38, stiffness: -8, weather: 'dry', wear: 1.2, path: 'M100 100 L230 70 L255 130 L370 90 L410 150 L350 180 L400 250 L285 310 L250 250 L175 295 L125 230 L175 180 L90 155Z' },
    { name: 'Highland Park', place: 'Vento, dislivelli e scrosci', laps: 32, base: 89, weights: [.55, .3, .15], wing: 60, gearing: 62, stiffness: 4, weather: 'rain', wear: 1.05, path: 'M90 240 Q60 175 130 120 L200 65 Q235 40 250 90 L270 140 L350 85 Q390 60 400 110 L420 220 Q425 250 390 255 L300 240 L245 300 Q215 330 190 280 L160 230 Q110 280 90 240Z' },
    { name: 'Gran Finale · Aurora', place: 'Un ultimo giro fatto bene', laps: 36, base: 84, weights: [.35, .35, .3], wing: 52, gearing: 65, stiffness: 0, weather: 'dry', wear: 1, path: 'M100 260 L95 110 Q95 70 140 70 L290 70 L265 120 L340 100 Q410 85 415 155 L405 240 Q400 280 360 270 L285 250 L250 310 L200 260 L140 300 Q100 320 100 260Z' },
];
export const TEAMS = [
    { id: 'aurora', name: 'Aurora Corse', color: '#ff7859', origin: 'Italia', story: 'Una piccola scuderia, una bella idea. Il progetto cresce con te.', base: 54, cash: 2400000, specialty: 'Equilibrata', driverIds: ['luca', 'mara'] },
    { id: 'mistral', name: 'Mistral Racing', color: '#70c9ef', origin: 'Francia', story: 'Leggera e gentile con le gomme. Trova la potenza che manca.', base: 57, cash: 2100000, specialty: 'Gestione gomme', driverIds: ['eli', 'noah'] },
    { id: 'koyo', name: 'Koyo Works', color: '#d7b5ff', origin: 'Giappone', story: 'Metodo e affidabilità. Trasforma la precisione in velocità.', base: 60, cash: 1800000, specialty: 'Affidabilità', driverIds: ['ren', 'yuki'] },
    { id: 'nord', name: 'Nord Motorsport', color: '#82d6ab', origin: 'Svezia', story: 'Il telaio è già buono. Fai lavorare insieme i suoi pezzi.', base: 64, cash: 1500000, specialty: 'Grip meccanico', driverIds: ['liv', 'oskar'] },
    { id: 'cinder', name: 'Cinder GP', color: '#ffd475', origin: 'Regno Unito', story: 'Un motore generoso. Serve una macchina che lo accompagni.', base: 67, cash: 1300000, specialty: 'Potenza', driverIds: ['ada', 'leo'] },
    { id: 'solstice', name: 'Solstice Grand Prix', color: '#f7a9d0', origin: 'Spagna', story: 'Davanti nel progetto, ancora tutto da scoprire nell’assetto.', base: 70, cash: 1100000, specialty: 'Aerodinamica', driverIds: ['ines', 'nico'] },
];
const driver = (id, name, pace, smooth, feedback, balance, suspension, trait) => ({ id, name, pace, smooth, feedback, balance, suspension, trait });
export const DRIVERS = [
    driver('luca', 'Luca Moretti', 77, 82, 90, 65, 63, 'Ama un anteriore preciso'), driver('mara', 'Mara Costa', 79, 88, 82, 35, 39, 'Vuole fidarsi del posteriore'),
    driver('eli', 'Élie Laurent', 81, 90, 85, 42, 45, 'Costante negli stint lunghi'), driver('noah', 'Noah Martin', 78, 85, 90, 61, 58, 'Legge bene la macchina'),
    driver('ren', 'Ren Aoki', 82, 86, 92, 56, 56, 'Preciso e metodico'), driver('yuki', 'Yuki Mori', 80, 92, 85, 39, 41, 'Delicato sulle gomme'),
    driver('liv', 'Liv Berg', 84, 88, 87, 63, 61, 'Ama le curve in appoggio'), driver('oskar', 'Oskar Lind', 82, 85, 90, 44, 48, 'Sente ogni variazione'),
    driver('ada', 'Ada Brooks', 87, 82, 86, 68, 66, 'Veloce con un avantreno vivo'), driver('leo', 'Leo Clarke', 84, 87, 88, 42, 43, 'Preferisce una base stabile'),
    driver('ines', 'Inés Vega', 90, 91, 90, 60, 60, 'Unisce sensibilità e ritmo'), driver('nico', 'Nico Soler', 87, 85, 89, 45, 47, 'Aggressivo ma pulito'),
    driver('sofia', 'Sofia Riva', 85, 93, 94, 54, 48, 'Collaudatrice eccellente'), driver('alex', 'Alex Reed', 90, 80, 83, 72, 68, 'Talento da domare'),
    driver('hana', 'Hana Sato', 83, 96, 89, 30, 35, 'Fa durare le gomme'), driver('toni', 'Toni Serra', 88, 87, 90, 48, 52, 'Si adatta a ogni progetto'),
];
const design = (id, name, description, stats, character = {}) => ({ id, name, description, stats, character });
export const PARTS = [
    { id: 'wing', name: 'Ali', code: '01', cost: 200000, options: [design('efficient', 'Flusso pulito', 'Meno resistenza, meno carico. Brilla sui rettifili.', {power: 7, aero: -2}), design('loaded', 'Alto carico', 'Più appoggio in curva, qualcosa in meno sul dritto.', {aero: 9, power: -4}, {balance: 4}), design('balanced', 'Doppio profilo', 'Un compromesso versatile e prevedibile.', {aero: 4, power: 2, care: 1})] },
    { id: 'floor', name: 'Fondo', code: '02', cost: 260000, options: [design('rigid', 'Venturi spinto', 'Potente con sospensioni rigide. Più sensibile alle sconnessioni.', {aero: 10, grip: -3, care: -2}, {stiffness: 7}), design('compliant', 'Finestra ampia', 'Appoggio costante, soprattutto con una sospensione morbida.', {aero: 4, grip: 5, care: 3}), design('balanced', 'Flusso stabile', 'Una base sana, facile da portare al limite.', {aero: 5, grip: 2, reliability: 2})] },
    { id: 'suspension', name: 'Sospensioni', code: '03', cost: 180000, options: [design('rigid', 'Piattaforma rigida', 'Sostiene il fondo Venturi, ma chiede di più alle gomme.', {aero: 4, grip: 4, care: -4}, {stiffness: 8}), design('compliant', 'Assorbimento', 'Trazione, gomme felici e intesa con il fondo Finestra ampia.', {grip: 7, care: 5, aero: -2}, {stiffness: -9}), design('balanced', 'Progressiva', 'Un telaio comunicativo, senza un carattere estremo.', {grip: 4, care: 2}, {balance: -2})] },
    { id: 'engine', name: 'Power unit', code: '04', cost: 300000, options: [design('attack', 'Mappa prestazione', 'Potenza in alto. Più calore e usura del gruppo motore.', {power: 12, reliability: -7, care: -2}), design('efficient', 'Recupero efficiente', 'Un motore gentile che premia la costanza.', {power: 4, reliability: 7, care: 3}), design('balanced', 'Erogazione fluida', 'Buona spinta e nessuno strappo.', {power: 7, reliability: 1})] },
    { id: 'cooling', name: 'Raffreddamento', code: '05', cost: 140000, options: [design('open', 'Pance aperte', 'Tiene al sicuro il motore spinto. Un po’ più di resistenza.', {reliability: 12, power: -3, aero: -1}), design('slim', 'Pance strette', 'Aerodinamica pulita. Attenzione alla mappa prestazione.', {aero: 6, power: 3, reliability: -5}), design('balanced', 'Canali modulari', 'Raffreddamento equilibrato, facile da gestire.', {reliability: 6, aero: 1})] },
    { id: 'gearbox', name: 'Trasmissione', code: '06', cost: 160000, options: [design('short', 'Uscita rapida', 'Trazione e risposta nelle curve lente.', {grip: 7, power: -2}, {balance: 3}), design('long', 'Allungo', 'Velocità di punta, con meno spunto.', {power: 7, grip: -2}), design('balanced', 'Sequenziale dolce', 'Cambiata pulita e una vita più lunga ai pezzi.', {power: 2, grip: 2, reliability: 4})] },
];
export const STAFF = { research: {name: 'Progettazione', description: '+1 progetto a ogni GP e sviluppo più efficace.', cost: 420000}, mechanics: {name: 'Meccanici', description: 'Soste più veloci e minore usura dei pezzi.', cost: 320000}, simulator: {name: 'Simulatore', description: '+1 prova a ogni GP e finestra assetto più tollerante.', cost: 280000} };
export function createCareer(teamId, seed = Date.now()) {
    const team = TEAMS.find(t => t.id === teamId);
    if (!team) throw new Error('Scegli una scuderia valida.');
    const parts = PARTS.map(p => ({ id: `${p.id}-base`, slot: p.id, concept: 'balanced', level: 0, condition: 100 }));
    return {version: VERSION, teamId, season: 1, round: 0, cash: team.cash, seed: seed >>> 0 || 1, projects: 4, testsLeft: 4, inventory: parts, fitted: Object.fromEntries(parts.map(p => [p.slot, p.id])), drivers: [...team.driverIds], setups: [defaultSetup(), defaultSetup()], strategies: [defaultStrategy(), defaultStrategy()], staff: {research: 0, mechanics: 0, simulator: 0}, points: {}, teamPoints: {}, tests: [], history: [], race: null, lastResult: null, totalRaces: 0 };
}
export const defaultSetup = () => ({wing: 50, balance: 50, suspension: 50, gearing: 50});
export const defaultStrategy = () => ({first: 'medium', second: 'hard', pitLap: 14, pace: 'balanced'});
export const circuitFor = s => CIRCUITS[s.round];
export const driverFor = id => DRIVERS.find(d => d.id === id);
export const fittedPart = (s, slot) => s.inventory.find(p => p.id === s.fitted[slot]);
export function carStats(s) {
    const team = TEAMS.find(t => t.id === s.teamId);
    const stats = Object.fromEntries(Object.keys(STATS).map(k => [k, team.base]));
    stats.reliability = 78;
    const specialty = {'Gestione gomme':'care','Affidabilità':'reliability','Grip meccanico':'grip','Potenza':'power','Aerodinamica':'aero'}[team.specialty];
    if (specialty) stats[specialty] += 6;
    const character = {balance: 0, stiffness: 0};
    for (const family of PARTS) {
        const part = fittedPart(s, family.id);
        const concept = family.options.find(o => o.id === part.concept);
        const scale = .35 + part.level * .38 + (part.refinement || 0);
        for (const [key, value] of Object.entries(concept.stats)) stats[key] += value * scale;
        for (const [key, value] of Object.entries(concept.character)) character[key] += value * scale;
        const wearPenalty = Math.max(0, 65 - part.condition) * .09;
        stats.reliability -= wearPenalty;
        if (part.slot !== 'cooling') stats[part.slot === 'engine' || part.slot === 'gearbox' ? 'power' : 'grip'] -= wearPenalty * .4;
    }
    const synergies = [];
    const floor = fittedPart(s, 'floor').concept;
    const suspension = fittedPart(s, 'suspension').concept;
    if (floor === 'rigid' && suspension === 'rigid') { stats.aero += 7; synergies.push('Piattaforma Venturi: +7 aerodinamica'); }
    if (floor === 'compliant' && suspension === 'compliant') { stats.grip += 5; stats.care += 4; synergies.push('Finestra elastica: +5 grip, +4 gestione gomme'); }
    if (fittedPart(s, 'engine').concept === 'attack') {
        if (fittedPart(s, 'cooling').concept === 'open') { stats.reliability += 6; synergies.push('Motore al fresco: +6 affidabilità'); }
        if (fittedPart(s, 'cooling').concept === 'slim') { stats.reliability -= 9; synergies.push('Calore da gestire: −9 affidabilità'); }
    }
    for (const key of Object.keys(stats)) stats[key] = clamp(stats[key], 15, 99);
    return {stats, character, synergies};
}
export function setupTargets(s, seat) {
    const driver = driverFor(s.drivers[seat]);
    const track = circuitFor(s);
    const {character} = carStats(s);
    return {wing: track.wing, gearing: track.gearing, balance: clamp(driver.balance - character.balance), suspension: clamp(driver.suspension + track.stiffness - character.stiffness)};
}
export function setupFit(s, seat, setup = s.setups[seat]) {
    const targets = setupTargets(s, seat);
    const tolerance = 4 + s.staff.simulator * 2 + (driverFor(s.drivers[seat]).feedback - 80) * .1;
    const errors = Object.fromEntries(Object.keys(AXES).map(k => [k, Math.max(0, Math.abs(setup[k] - targets[k]) - tolerance)]));
    const penalty = errors.wing * .023 + errors.gearing * .018 + errors.balance * .036 + errors.suspension * .027;
    return {fit: clamp(100 - penalty * 20), penalty, errors, targets};
}
export function predictedLap(s, seat, setup = s.setups[seat]) {
    const {stats} = carStats(s);
    const [a, p, g] = circuitFor(s).weights;
    const capability = stats.aero * a + stats.power * p + stats.grip * g;
    return circuitFor(s).base + (78 - capability) * .105 + (88 - driverFor(s.drivers[seat]).pace) * .055 + setupFit(s, seat, setup).penalty;
}
const assertGarage = s => { if (s.race) throw new Error('La vettura è in pista. Termina il GP prima di lavorarci.'); };
export function setSetup(s, seat, key, value) {
    assertGarage(s);
    if (![0, 1].includes(seat) || !(key in AXES) || !Number.isFinite(Number(value))) throw new Error('Assetto non valido.');
    s.setups[seat][key] = clamp(Number(value));
}
export function setStrategy(s, seat, key, value) {
    if (![0, 1].includes(seat) || !['first', 'second', 'pitLap', 'pace'].includes(key)) throw new Error('Strategia non valida.');
    if (['first', 'second'].includes(key) && !COMPOUNDS[value]) throw new Error('Mescola non valida.');
    if (key === 'pace' && !['calm','balanced','push'].includes(value)) throw new Error('Passo non valido.');
    if (key === 'pitLap') { if (!Number.isFinite(Number(value))) throw new Error('Giro non valido.'); value = clamp(Math.round(value), 2, circuitFor(s).laps - 2); }
    if (s.race && key === 'first') throw new Error('La gara è già iniziata.');
    s.strategies[seat][key] = value;
}
export function developmentCost(s, slot, concept) {
    const family = PARTS.find(p => p.id === slot);
    if (!family?.options.some(o => o.id === concept)) throw new Error('Progetto non valido.');
    const level = Math.max(0, ...s.inventory.filter(p => p.slot === slot && p.concept === concept).map(p => p.level)) + 1;
    return {level, cost: Math.round(family.cost * (1 + (level - 1) * .45))};
}
export function develop(s, slot, concept) {
    assertGarage(s);
    const {level, cost} = developmentCost(s, slot, concept);
    if (level > 4) throw new Error('Hai raggiunto la quarta evoluzione di questo progetto.');
    if (s.projects < 1) throw new Error('Progetti esauriti: il reparto riparte al prossimo GP.');
    if (s.cash < cost) throw new Error('Budget insufficiente per questo progetto.');
    s.cash -= cost; s.projects--;
    const part = {id: `${slot}-${concept}-${s.season}-${s.totalRaces}-${s.inventory.length}`, slot, concept, level, condition: 100, refinement: s.staff.research * .06};
    s.inventory.push(part); s.fitted[slot] = part.id;
    return part;
}
export function install(s, id) {
    assertGarage(s);
    const part = s.inventory.find(p => p.id === id);
    if (!part) throw new Error('Pezzo non disponibile in magazzino.');
    s.fitted[part.slot] = part.id;
}
export function service(s) {
    assertGarage(s);
    const cost = Math.ceil(PARTS.reduce((sum, p) => sum + 100 - fittedPart(s, p.id).condition, 0) * 700);
    if (s.cash < cost) throw new Error('Budget insufficiente. Le entrate del prossimo GP ti aiuteranno.');
    s.cash -= cost; PARTS.forEach(p => {fittedPart(s, p.id).condition = 100;});
    return cost;
}
export function upgradeStaff(s, key) {
    assertGarage(s);
    if (!STAFF[key]) throw new Error('Reparto non valido.');
    const cost = STAFF[key].cost * (s.staff[key] + 1);
    if (s.staff[key] >= 3 || s.cash < cost) throw new Error('Miglioramento non disponibile con il budget attuale.');
    s.cash -= cost; s.staff[key]++;
    if (key === 'research') s.projects++;
    if (key === 'simulator') s.testsLeft++;
}
export function hire(s, seat, id) {
    assertGarage(s);
    if (![0, 1].includes(seat) || !DRIVERS.slice(12).some(d => d.id === id) || s.drivers.includes(id)) throw new Error('Pilota non disponibile.');
    const cost = (driverFor(id).pace - 70) * 35000;
    if (s.cash < cost) throw new Error('Budget insufficiente per questo contratto.');
    s.cash -= cost; s.drivers[seat] = id;
}
export function runTest(s) {
    assertGarage(s);
    if (s.testsLeft < 1) throw new Error('Prove finite. Puoi ancora regolare l’assetto e partire; nuove prove al prossimo GP.');
    s.testsLeft--;
    const result = {season: s.season, round: s.round, car: copy(s.fitted), setups: copy(s.setups), drivers: [...s.drivers], laps: [predictedLap(s, 0), predictedLap(s, 1)], fits: [setupFit(s, 0), setupFit(s, 1)]};
    s.tests.unshift(result); s.tests = s.tests.slice(0, 18);
    return result;
}
function random(s) {
    s.seed = (Math.imul(1664525, s.seed) + 1013904223) >>> 0;
    return s.seed / 4294967296;
}
export function startRace(s) {
    assertGarage(s);
    const track = circuitFor(s);
    const grid = [];
    for (const team of TEAMS) {
        const ids = team.id === s.teamId ? s.drivers : team.driverIds;
        ids.forEach((id, seat) => {
            const mine = team.id === s.teamId;
            const d = driverFor(id);
            const base = mine ? predictedLap(s, seat) : track.base + (78 - Math.min(93, team.base + 4 + (s.season - 1) * 1.5 + s.round * .9)) * .105 + (88 - d.pace) * .055 + .38;
            grid.push({id, name: d.name, team: team.id, color: team.color, mine, seat, base, qualifying: base - .8 + random(s) * .5, time: 0, lastLap: 0, tire: mine ? s.strategies[seat].first : seat ? 'hard' : 'medium', tireHealth: 100, stops: 0, pitNext: false, pitted: false, finishPosition: null});
        });
    }
    grid.sort((a,b) => a.qualifying - b.qualifying).forEach((car, i) => { car.time = i * .26; car.grid = i + 1; });
    s.race = {lap: 0, laps: track.laps, cars: grid, weather: 'dry', safety: 0, event: null, resolved: [], feed: ['La tua macchina è nelle loro mani. Buon GP.'], finished: false, qualifying: grid.map(c => ({id:c.id, time:c.qualifying, position:c.grid})), blueprint: copy(carStats(s).stats)};
    return s.race;
}
const log = (race, message) => { race.feed.unshift(message); race.feed = race.feed.slice(0, 30); };
function pit(s, car) {
    const race = s.race;
    car.time += race.safety ? 10 : 20 - (car.mine ? s.staff.mechanics * 1.6 : 1.8);
    car.tire = car.mine ? s.strategies[car.seat].second : race.weather === 'wet' ? 'inter' : 'hard';
    car.tireHealth = 100; car.stops++; car.pitNext = false; car.pitted = true;
    if (car.mine) log(race, `${car.name}: sosta completata, gomme ${COMPOUNDS[car.tire].name}.`);
}
export function requestPit(s, seat) {
    const car = s.race?.cars.find(c => c.mine && c.seat === seat);
    if (!car || s.race.finished) throw new Error('Nessuna gara in corso.');
    car.pitNext = !car.pitNext;
}
export function resolveEvent(s, choice) {
    const race = s.race;
    if (!race?.event) return;
    if (!['adapt','stay'].includes(choice)) throw new Error('Scelta non valida.');
    const kind = race.event.kind;
    if (choice === 'adapt') {
        if (kind === 'rain' || kind === 'dry') {
            s.strategies.forEach(st => {st.second = kind === 'rain' ? 'inter' : 'medium';});
            race.cars.filter(c => c.mine).forEach(c => {c.pitNext = true;});
        }
        if (kind === 'safety') race.cars.filter(c => c.mine && c.tireHealth < 80).forEach(c => {c.pitNext = true;});
        if (kind === 'heat') s.strategies.forEach(st => {st.pace = 'calm';});
    }
    log(race, choice === 'adapt' ? 'Il muretto applica la scelta consigliata.' : 'Confermi il tuo piano. Puoi modificarlo quando vuoi.');
    race.resolved.push(kind); race.event = null;
}
export function advanceLap(s) {
    const race = s.race;
    if (!race || race.finished || race.event) return race;
    const next = race.lap + 1;
    const track = circuitFor(s);
    let event = null;
    if (next === 10 && track.weather === 'rain' && !race.resolved.includes('rain')) {
        race.weather = 'wet'; event = {kind:'rain', title:'Arriva uno scroscio', body:'La pista si bagna. Le intermedie recuperano circa 7 secondi al giro sulle slick.', advice:'Monta le intermedie su entrambe le vetture.'};
    } else if (next === 23 && track.weather === 'rain' && !race.resolved.includes('dry')) {
        race.weather = 'dry'; event = {kind:'dry', title:'Torna una linea asciutta', body:'Lo scroscio è passato. Le slick tornano più veloci; puoi scegliere quando fermarti.', advice:'Passa alle Medium.'};
    } else if (next === 16 && !race.resolved.includes('safety')) {
        race.safety = 3; event = {kind:'safety', title:'Safety car in pista', body:'Un rivale ha perso un pezzo. Nessuno si è fatto male. Il gruppo rallenta per tre giri e una sosta costa meno.', advice:'Approfitta della sosta se hai gomme già usate.'};
    } else if (next === 7 && race.blueprint.reliability < 65 && !race.resolved.includes('heat')) {
        event = {kind:'heat', title:'Temperature in crescita', body:'Il pacchetto motore e raffreddamento lavora al limite. Un passo morbido contiene il calore.', advice:'Riduci il ritmo e proteggi i pezzi.'};
    }
    if (event) {race.event = event; log(race, event.title); return race;}
    race.lap = next;
    const {stats} = carStats(s);
    race.cars.forEach(car => {
        const d = driverFor(car.id);
        const strategy = car.mine ? s.strategies[car.seat] : {pitLap: Math.floor(track.laps * .5) + car.seat, pace:'balanced'};
        car.pitted = false;
        const weatherStop = !car.mine && ((race.weather === 'wet' && car.tire !== 'inter') || (race.weather === 'dry' && car.tire === 'inter'));
        if (car.pitNext || (car.stops === 0 && next >= strategy.pitLap) || weatherStop) pit(s, car);
        const compound = COMPOUNDS[car.tire];
        const pace = strategy.pace === 'push' ? -.38 : strategy.pace === 'calm' ? .32 : 0;
        const mismatch = race.weather === 'wet' ? car.tire !== 'inter' ? 7 : 0 : car.tire === 'inter' ? 4.5 : 0;
        const worn = Math.max(0, 65 - car.tireHealth) * .045 + Math.max(0, 22 - car.tireHealth) * .14;
        const reliability = car.mine ? Math.max(0, 72 - stats.reliability) * (strategy.pace === 'calm' ? .012 : .035) : 0;
        const lapTime = car.base + compound.pace + pace + mismatch + worn + reliability + (random(s) - .5) * .35;
        car.lastLap = race.safety ? Math.max(lapTime, track.base + 19) : lapTime;
        car.time += car.lastLap;
        const care = car.mine ? stats.care : 65;
        const wear = compound.wear * track.wear * (1.32 - d.smooth / 250 - care / 400) * (strategy.pace === 'push' ? 1.22 : strategy.pace === 'calm' ? .8 : 1) * (race.safety ? .45 : 1);
        car.tireHealth = clamp(car.tireHealth - wear);
    });
    race.cars.sort((a,b) => a.time - b.time);
    if (race.safety) {
        const leader = race.cars[0].time;
        race.cars.forEach((c,i) => {c.time = leader + i * .8;}); race.safety--;
    }
    if (next >= race.laps) finishRace(s);
    return race;
}
function finishRace(s) {
    const race = s.race;
    if (race.finished) return;
    race.finished = true;
    const points = [25,18,15,12,10,8,6,4,2,1,0,0];
    let earned = 0;
    race.cars.forEach((car,i) => {
        car.finishPosition = i + 1;
        s.points[car.id] = (s.points[car.id] || 0) + points[i];
        s.teamPoints[car.team] = (s.teamPoints[car.team] || 0) + points[i];
        if (car.mine) earned += points[i];
    });
    const income = 520000 + earned * 14000;
    s.cash += income;
    PARTS.forEach(p => {
        const part = fittedPart(s, p.id);
        part.condition = clamp(part.condition - 11 - (p.id === 'engine' && part.concept === 'attack' ? 5 : 0) + s.staff.mechanics * 1.5);
    });
    s.totalRaces++;
    s.lastResult = {season:s.season, round:s.round, track:circuitFor(s).name, results:copy(race.cars), income, earned, stats:copy(race.blueprint), fits:s.setups.map((_,i) => setupFit(s,i).fit), qualifying:copy(race.qualifying)};
    s.history.unshift(copy(s.lastResult)); s.history = s.history.slice(0, 24);
    log(race, `Bandiera a scacchi. ${earned} punti e ${Math.round(income/1000)} mila crediti per continuare a progettare.`);
}
export function nextRound(s) {
    if (!s.race?.finished) throw new Error('Concludi prima la gara.');
    s.round++;
    if (s.round >= CIRCUITS.length) {s.round = 0; s.season++; s.points = {}; s.teamPoints = {}; s.cash += 900000;}
    s.race = null; s.projects = 3 + s.staff.research; s.testsLeft = 4 + s.staff.simulator;
    s.strategies.forEach(st => {st.pitLap = Math.floor(circuitFor(s).laps/2);});
}
const validNumber = (n, min = 0, max = 1e12) => Number.isFinite(n) && n >= min && n <= max;
const validSetup = setup => setup && Object.keys(AXES).every(k => validNumber(setup[k], 0, 100));
const validCar = c => c && driverFor(c.id) && TEAMS.some(t => t.id === c.team) && typeof c.name === 'string' && c.name.length < 100 && /^#[0-9a-f]{6}$/i.test(c.color) && COMPOUNDS[c.tire] && validNumber(c.base, 1, 1000) && validNumber(c.qualifying, 1, 1000) && validNumber(c.time) && validNumber(c.lastLap, 0, 1000) && validNumber(c.tireHealth, 0, 100) && [0, 1].includes(c.seat) && Number.isInteger(c.stops) && c.stops >= 0 && typeof c.mine === 'boolean' && Number.isInteger(c.grid) && c.grid >= 1 && c.grid <= 12;
const validResult = r => r && Number.isInteger(r.season) && Number.isInteger(r.round) && r.round >= 0 && r.round < CIRCUITS.length && typeof r.track === 'string' && r.track.length < 100 && validNumber(r.income) && validNumber(r.earned, 0, 43) && Array.isArray(r.fits) && r.fits.length === 2 && r.fits.every(n => validNumber(n, 0, 100)) && Array.isArray(r.results) && r.results.length === 12 && r.results.every(c => validCar(c) && Number.isInteger(c.finishPosition) && c.finishPosition >= 1 && c.finishPosition <= 12) && r.results.filter(c => c.mine).length === 2 && new Set(r.results.filter(c => c.mine).map(c => c.seat)).size === 2;
export function parseSave(text) {
    const s = JSON.parse(text);
    if (!s || s.version !== VERSION || !TEAMS.some(t => t.id === s.teamId) || !Number.isInteger(s.round) || s.round < 0 || s.round >= CIRCUITS.length) throw new Error('Salvataggio non compatibile.');
    if (!Number.isInteger(s.season) || s.season < 1 || !Number.isFinite(s.cash) || s.cash < 0 || !Number.isInteger(s.seed) || s.seed < 0 || s.seed > 4294967295 || !Number.isInteger(s.totalRaces) || s.totalRaces < 0) throw new Error('Salvataggio incompleto.');
    if (!Array.isArray(s.inventory) || s.inventory.length > 5000 || !s.inventory.length || new Set(s.inventory.map(p => p.id)).size !== s.inventory.length) throw new Error('Magazzino non valido.');
    for (const p of s.inventory) {
        const family = PARTS.find(f => f.id === p.slot);
        if (typeof p.id !== 'string' || !family?.options.some(o => o.id === p.concept) || !Number.isInteger(p.level) || p.level < 0 || p.level > 4 || !Number.isFinite(p.condition) || p.condition < 0 || p.condition > 100 || (p.refinement !== undefined && !validNumber(p.refinement, 0, .18))) throw new Error('Pezzo non valido.');
    }
    for (const family of PARTS) if (!s.inventory.some(p => p.slot === family.id && p.id === s.fitted?.[family.id])) throw new Error('Montaggio non valido.');
    if (!Array.isArray(s.drivers) || s.drivers.length !== 2 || s.drivers[0] === s.drivers[1] || s.drivers.some(id => ![...TEAMS.find(t => t.id === s.teamId).driverIds, ...DRIVERS.slice(12).map(d => d.id)].includes(id))) throw new Error('Piloti non validi.');
    if (!Array.isArray(s.setups) || s.setups.length !== 2 || s.setups.some(setup => Object.keys(AXES).some(k => !Number.isFinite(setup[k]) || setup[k] < 0 || setup[k] > 100))) throw new Error('Assetti non validi.');
    if (!Array.isArray(s.strategies) || s.strategies.length !== 2 || s.strategies.some(st => !COMPOUNDS[st.first] || !COMPOUNDS[st.second] || !['calm','balanced','push'].includes(st.pace) || !Number.isInteger(st.pitLap) || st.pitLap < 2 || st.pitLap > circuitFor(s).laps - 2)) throw new Error('Strategie non valide.');
    if (Object.keys(STAFF).some(k => !Number.isInteger(s.staff?.[k]) || s.staff[k] < 0 || s.staff[k] > 3) || !Number.isInteger(s.projects) || s.projects < 0 || !Number.isInteger(s.testsLeft) || s.testsLeft < 0) throw new Error('Reparti non validi.');
    if (!Array.isArray(s.tests) || s.tests.length > 18 || !Array.isArray(s.history) || s.history.length > 24 || !s.points || !s.teamPoints || Object.values(s.points).some(n => !validNumber(n)) || Object.values(s.teamPoints).some(n => !validNumber(n))) throw new Error('Storico non valido.');
    for (const t of s.tests) {
        if (!t || !Number.isInteger(t.season) || t.season < 1 || !Number.isInteger(t.round) || !Array.isArray(t.drivers) || t.drivers.length !== 2 || t.drivers.some(id => !driverFor(id)) || !Array.isArray(t.setups) || t.setups.length !== 2 || !t.setups.every(validSetup) || !Array.isArray(t.laps) || t.laps.length !== 2 || t.laps.some(n => !validNumber(n, 1, 1000)) || !Array.isArray(t.fits) || t.fits.length !== 2 || t.fits.some(f => !f || !validSetup(f.targets) || !validNumber(f.fit, 0, 100))) throw new Error('Prova non valida.');
    }
    if (s.history.some(r => !validResult(r)) || (s.lastResult !== null && !validResult(s.lastResult))) throw new Error('Risultato non valido.');
    if (s.race) {
        const r = s.race;
        if (!Number.isInteger(r.lap) || r.lap < 0 || r.lap > circuitFor(s).laps || r.laps !== circuitFor(s).laps || !Array.isArray(r.cars) || r.cars.length !== 12 || new Set(r.cars.map(c => c.id)).size !== 12 || !Array.isArray(r.resolved) || !Array.isArray(r.feed) || !Array.isArray(r.qualifying) || !r.blueprint || !['wet','dry'].includes(r.weather) || !Number.isInteger(r.safety) || typeof r.finished !== 'boolean' || (r.finished !== (r.lap === r.laps))) throw new Error('Gara non valida.');
        if (r.feed.some(v => typeof v !== 'string' || v.length > 1000) || Object.values(r.blueprint).some(v => !validNumber(v, 0, 100))) throw new Error('Diario non valido.');
        if (r.finished && (!s.lastResult || s.lastResult.round !== s.round || s.lastResult.season !== s.season)) throw new Error('Risultato finale mancante.');
        if (r.event && (!['rain','dry','safety','heat'].includes(r.event.kind) || typeof r.event.title !== 'string' || typeof r.event.body !== 'string' || typeof r.event.advice !== 'string')) throw new Error('Imprevisto non valido.');
        if (!r.cars.every(validCar)) throw new Error('Vettura non valida.');
        const mine = r.cars.filter(c => c.mine);
        if (mine.length !== 2 || mine.some(c => c.team !== s.teamId || c.id !== s.drivers[c.seat]) || new Set(mine.map(c => c.seat)).size !== 2) throw new Error('Scuderia non valida.');
    }
    return s;
}

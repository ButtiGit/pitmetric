<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DemoManagerController extends Controller
{
    private const SECTIONS = [
        'dashboard', 'events', 'sessions', 'circuits', 'garage', 'components',
        'configurations', 'setups', 'maintenance', 'timing', 'telemetry',
        'insights', 'expenses', 'team',
    ];

    public function index(): View
    {
        return $this->render('dashboard');
    }

    public function show(string $section): View
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        return $this->render($section);
    }

    private function render(string $section): View
    {
        $it = app()->getLocale() === 'it';

        return view('demo.manager_demo', [
            'section' => $section,
            'title' => $this->sectionTitle($section, $it),
            'demo' => $this->demoData($it),
        ]);
    }

    private function sectionTitle(string $section, bool $it): string
    {
        return match ($section) {
            'dashboard' => 'Dashboard',
            'events' => $it ? 'Race weekend' : 'Race weekends',
            'sessions' => $it ? 'Sessioni' : 'Sessions',
            'circuits' => $it ? 'Circuiti' : 'Circuits',
            'garage' => 'Garage',
            'components' => $it ? 'Componenti' : 'Components',
            'configurations' => $it ? 'Configurazioni' : 'Configurations',
            'setups' => $it ? 'Setup tecnici' : 'Technical setups',
            'maintenance' => $it ? 'Manutenzione' : 'Maintenance',
            'timing' => $it ? 'Tempi' : 'Timing',
            'telemetry' => $it ? 'Telemetria' : 'Telemetry',
            'insights' => 'Intelligence',
            'expenses' => $it ? 'Costi' : 'Expenses',
            'team' => 'Team',
            default => ucfirst($section),
        };
    }

    private function demoData(bool $it): array
    {
        return [
            'workspace' => ['name' => 'Race Team Demo', 'plan' => 'Manager', 'role' => 'Owner'],
            'vehicles' => [
                ['id' => 12, 'name' => 'KR2 Race Kart', 'category' => 'kart', 'status' => 'active', 'manufacturer' => 'KR', 'model' => 'KR2', 'year' => 2026, 'identifier' => 'KART-12', 'notes' => $it ? 'Telaio gara principale, assetto asciutto.' : 'Primary race chassis, dry setup.', 'parts' => ['Rotax MAX EVO #02', 'Chain DID #04', 'Bridgestone YLR #08', 'Brake Pads #03']],
                ['id' => 18, 'name' => 'BMW M2 Track', 'category' => 'car', 'status' => 'active', 'manufacturer' => 'BMW', 'model' => 'M2 G87', 'year' => 2025, 'identifier' => 'CAR-18', 'notes' => $it ? 'Auto track-day per test telemetria e costi.' : 'Track-day car for telemetry and cost testing.', 'parts' => ['Pagid RSL29 #01', 'Michelin Cup 2 #05']],
                ['id' => 27, 'name' => 'Yamaha R7 Cup', 'category' => 'motorcycle', 'status' => 'inactive', 'manufacturer' => 'Yamaha', 'model' => 'R7', 'year' => 2024, 'identifier' => 'BIKE-27', 'notes' => $it ? 'Mezzo secondario, storico mantenuto.' : 'Secondary vehicle with retained history.', 'parts' => ['RK Chain #02', 'Pirelli SC #06']],
            ],
            'components' => [
                ['name' => 'Rotax MAX EVO #02', 'type' => $it ? 'Motore' : 'Engine', 'vehicle' => 'KR2 Race Kart', 'position' => $it ? 'Motore' : 'Engine', 'metric' => $it ? 'Ore motore' : 'Runtime', 'since' => '11,8 h', 'lifetime' => '36,4 h', 'purchase' => '€ 2.850,00', 'serial' => 'RTX-EVO-02'],
                ['name' => 'Chain DID #04', 'type' => $it ? 'Trasmissione' : 'Drivetrain', 'vehicle' => 'KR2 Race Kart', 'position' => $it ? 'Catena' : 'Chain', 'metric' => $it ? 'Distanza' : 'Distance', 'since' => '286,4 km', 'lifetime' => '621,9 km', 'purchase' => '€ 89,90', 'serial' => 'DID-04'],
                ['name' => 'Bridgestone YLR #08', 'type' => $it ? 'Pneumatici' : 'Tyres', 'vehicle' => 'KR2 Race Kart', 'position' => $it ? 'Set gara' : 'Race set', 'metric' => $it ? 'Giri' : 'Laps', 'since' => '93 giri', 'lifetime' => '221 giri', 'purchase' => '€ 238,00', 'serial' => 'YLR-08'],
                ['name' => 'Brake Pads #03', 'type' => $it ? 'Freni' : 'Brakes', 'vehicle' => 'KR2 Race Kart', 'position' => $it ? 'Posteriore' : 'Rear', 'metric' => $it ? 'Distanza' : 'Distance', 'since' => '417,2 km', 'lifetime' => '417,2 km', 'purchase' => '€ 74,00', 'serial' => 'BP-03'],
                ['name' => 'Pagid RSL29 #01', 'type' => $it ? 'Freni' : 'Brakes', 'vehicle' => 'BMW M2 Track', 'position' => $it ? 'Anteriore' : 'Front', 'metric' => $it ? 'Distanza' : 'Distance', 'since' => '684,1 km', 'lifetime' => '684,1 km', 'purchase' => '€ 419,00', 'serial' => 'RSL29-01'],
                ['name' => 'Michelin Cup 2 #05', 'type' => $it ? 'Pneumatici' : 'Tyres', 'vehicle' => 'BMW M2 Track', 'position' => $it ? 'Set pista' : 'Track set', 'metric' => $it ? 'Sessioni' : 'Sessions', 'since' => '7', 'lifetime' => '7', 'purchase' => '€ 1.160,00', 'serial' => 'MC2-05'],
                ['name' => 'RK Chain #02', 'type' => $it ? 'Trasmissione' : 'Drivetrain', 'vehicle' => 'Yamaha R7 Cup', 'position' => $it ? 'Catena' : 'Chain', 'metric' => $it ? 'Distanza' : 'Distance', 'since' => '943,0 km', 'lifetime' => '943,0 km', 'purchase' => '€ 129,00', 'serial' => 'RK-02'],
                ['name' => 'Pirelli SC #06', 'type' => $it ? 'Pneumatici' : 'Tyres', 'vehicle' => 'Yamaha R7 Cup', 'position' => $it ? 'Set gara' : 'Race set', 'metric' => $it ? 'Sessioni' : 'Sessions', 'since' => '5', 'lifetime' => '5', 'purchase' => '€ 389,00', 'serial' => 'PSC-06'],
            ],
            'configurations' => [
                ['name' => 'Race Build V3', 'vehicle' => 'KR2 Race Kart', 'version' => 3, 'parts' => 4, 'updated' => '24/09/2026'],
                ['name' => 'Track Build V2', 'vehicle' => 'BMW M2 Track', 'version' => 2, 'parts' => 2, 'updated' => '20/09/2026'],
                ['name' => 'Cup Build V4', 'vehicle' => 'Yamaha R7 Cup', 'version' => 4, 'parts' => 2, 'updated' => '04/09/2026'],
            ],
            'setups' => [
                ['name' => 'Busca Dry Qualifying', 'vehicle' => 'KR2 Race Kart', 'track' => 'Circuito di Busca', 'tyres' => '0.78 / 0.80 bar', 'gear' => '12 / 80', 'updated' => '24/09/2026'],
                ['name' => 'Kart Planet Race', 'vehicle' => 'KR2 Race Kart', 'track' => 'Kart Planet', 'tyres' => '0.76 / 0.79 bar', 'gear' => '12 / 78', 'updated' => '18/09/2026'],
                ['name' => 'M2 Fast Road', 'vehicle' => 'BMW M2 Track', 'track' => 'Tazio Nuvolari', 'tyres' => '2.1 / 2.0 bar', 'gear' => 'Auto', 'updated' => '20/09/2026'],
            ],
            'circuits' => [
                ['name' => 'Circuito di Busca', 'layout' => 'Full', 'length' => '1.200 m', 'sessions' => 7, 'best' => '52.184'],
                ['name' => 'Kart Planet', 'layout' => 'Race', 'length' => '1.125 m', 'sessions' => 6, 'best' => '48.932'],
                ['name' => 'Tazio Nuvolari', 'layout' => 'Full', 'length' => '5.260 m', 'sessions' => 3, 'best' => '1:42.610'],
                ['name' => 'Franciacorta Karting', 'layout' => 'International', 'length' => '1.300 m', 'sessions' => 2, 'best' => '54.281'],
            ],
            'sessions' => [
                ['date' => '24/09/2026', 'event' => 'Busca Race Weekend', 'vehicle' => 'KR2 Race Kart', 'circuit' => 'Circuito di Busca', 'type' => 'Race', 'laps' => 42, 'distance' => '50,4 km', 'best' => '52.184'],
                ['date' => '24/09/2026', 'event' => 'Busca Race Weekend', 'vehicle' => 'KR2 Race Kart', 'circuit' => 'Circuito di Busca', 'type' => 'Qualifying', 'laps' => 18, 'distance' => '21,6 km', 'best' => '51.992'],
                ['date' => '18/09/2026', 'event' => 'Kart Planet Test', 'vehicle' => 'KR2 Race Kart', 'circuit' => 'Kart Planet', 'type' => 'Test', 'laps' => 37, 'distance' => '41,6 km', 'best' => '48.932'],
                ['date' => '20/09/2026', 'event' => 'M2 Track Day', 'vehicle' => 'BMW M2 Track', 'circuit' => 'Tazio Nuvolari', 'type' => 'Track day', 'laps' => 21, 'distance' => '110,5 km', 'best' => '1:42.610'],
                ['date' => '07/09/2026', 'event' => 'Busca Practice', 'vehicle' => 'KR2 Race Kart', 'circuit' => 'Circuito di Busca', 'type' => 'Practice', 'laps' => 31, 'distance' => '37,2 km', 'best' => '52.611'],
                ['date' => '30/08/2026', 'event' => 'Kart Planet Race', 'vehicle' => 'KR2 Race Kart', 'circuit' => 'Kart Planet', 'type' => 'Race', 'laps' => 44, 'distance' => '49,5 km', 'best' => '49.105'],
            ],
            'events' => [
                ['name' => 'Busca Race Weekend', 'date' => '24–25 Sep 2026', 'circuit' => 'Circuito di Busca · Full', 'status' => 'active', 'entries' => 2, 'sessions' => 4, 'tasks' => 6],
                ['name' => 'Kart Planet Test', 'date' => '18 Sep 2026', 'circuit' => 'Kart Planet · Race', 'status' => 'completed', 'entries' => 1, 'sessions' => 3, 'tasks' => 4],
                ['name' => 'Franciacorta Trophy', 'date' => '10–11 Oct 2026', 'circuit' => 'Franciacorta · International', 'status' => 'planned', 'entries' => 2, 'sessions' => 0, 'tasks' => 8],
            ],
            'maintenance' => [
                ['component' => 'Chain DID #04', 'title' => $it ? 'Controllo tensione e lubrificazione' : 'Tension and lubrication check', 'status' => 'due_soon', 'due' => $it ? 'tra 64 km' : 'in 64 km', 'last' => '18/09/2026'],
                ['component' => 'Rotax MAX EVO #02', 'title' => $it ? 'Revisione top-end' : 'Top-end service', 'status' => 'due_soon', 'due' => $it ? 'tra 3,2 h' : 'in 3.2 h', 'last' => '07/09/2026'],
                ['component' => 'Brake Pads #03', 'title' => $it ? 'Controllo spessore' : 'Thickness inspection', 'status' => 'ok', 'due' => $it ? 'tra 183 km' : 'in 183 km', 'last' => '30/08/2026'],
                ['component' => 'Pagid RSL29 #01', 'title' => $it ? 'Controllo pastiglie anteriori' : 'Front pad inspection', 'status' => 'ok', 'due' => $it ? 'tra 315 km' : 'in 315 km', 'last' => '20/09/2026'],
                ['component' => 'RK Chain #02', 'title' => $it ? 'Sostituzione catena' : 'Chain replacement', 'status' => 'overdue', 'due' => $it ? 'scaduta di 43 km' : '43 km overdue', 'last' => '04/09/2026'],
            ],
            'expenses' => [
                ['date' => '24/09/2026', 'category' => $it ? 'Pista' : 'Track', 'vehicle' => 'KR2 Race Kart', 'note' => $it ? 'Iscrizione Busca Race Weekend' : 'Busca Race Weekend entry', 'amount' => '€ 180,00'],
                ['date' => '23/09/2026', 'category' => $it ? 'Carburante' : 'Fuel', 'vehicle' => 'KR2 Race Kart', 'note' => '25 L + oil', 'amount' => '€ 71,40'],
                ['date' => '20/09/2026', 'category' => $it ? 'Pista' : 'Track', 'vehicle' => 'BMW M2 Track', 'note' => 'Track day', 'amount' => '€ 290,00'],
                ['date' => '18/09/2026', 'category' => $it ? 'Ricambi' : 'Parts', 'vehicle' => 'KR2 Race Kart', 'note' => 'Chain DID #04', 'amount' => '€ 89,90'],
                ['date' => '12/09/2026', 'category' => $it ? 'Pneumatici' : 'Tyres', 'vehicle' => 'KR2 Race Kart', 'note' => 'Bridgestone YLR #08', 'amount' => '€ 238,00'],
                ['date' => '05/09/2026', 'category' => $it ? 'Manutenzione' : 'Maintenance', 'vehicle' => 'KR2 Race Kart', 'note' => $it ? 'Service motore' : 'Engine service', 'amount' => '€ 310,00'],
            ],
            'timing' => [
                ['driver' => 'S. Demo', 'session' => 'Qualifying', 'lap' => 14, 'time' => '51.992', 's1' => '17.021', 's2' => '17.408', 's3' => '17.563', 'delta' => 'BEST'],
                ['driver' => 'S. Demo', 'session' => 'Race', 'lap' => 31, 'time' => '52.184', 's1' => '17.110', 's2' => '17.441', 's3' => '17.633', 'delta' => '+0.192'],
                ['driver' => 'S. Demo', 'session' => 'Race', 'lap' => 28, 'time' => '52.337', 's1' => '17.204', 's2' => '17.489', 's3' => '17.644', 'delta' => '+0.345'],
                ['driver' => 'M. Demo', 'session' => 'Race', 'lap' => 19, 'time' => '52.611', 's1' => '17.300', 's2' => '17.581', 's3' => '17.730', 'delta' => '+0.619'],
            ],
            'telemetry' => [
                ['label' => $it ? 'Velocità max' : 'Top speed', 'value' => '112,8 km/h', 'note' => 'Lap 14'],
                ['label' => $it ? 'RPM max' : 'Max RPM', 'value' => '13.740', 'note' => 'Lap 14'],
                ['label' => $it ? 'Gas medio' : 'Avg throttle', 'value' => '71,4%', 'note' => 'Qualifying'],
                ['label' => $it ? 'Frenata' : 'Braking', 'value' => '9,8%', 'note' => $it ? 'tempo giro' : 'lap time'],
            ],
            'members' => [
                ['name' => 'Simone Demo', 'email' => 'owner@demo.pitmetric', 'role' => 'Owner', 'status' => 'Active'],
                ['name' => 'Marco Demo', 'email' => 'mechanic@demo.pitmetric', 'role' => 'Manager', 'status' => 'Active'],
                ['name' => 'Luca Demo', 'email' => 'driver@demo.pitmetric', 'role' => 'Driver', 'status' => 'Active'],
            ],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;

final class PrivacyReadiness
{
    /** @return list<array{name: string, status: string, detail: string}> */
    public function checks(): array
    {
        return [
            $this->check('Debug disabilitato', config('app.debug') === false, 'Configurazione effettiva di questo ambiente; non è un test di esposizione esterno.'),
            $this->check('URL HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Verificare anche TLS, redirect HTTP e proxy nel server di produzione.'),
            $this->check('Cookie sessione protetto', config('session.secure') === true && config('session.http_only') === true && in_array(config('session.same_site'), ['lax', 'strict'], true), 'Secure, HttpOnly e SameSite; verificare i cookie effettivi dopo il deploy.'),
            $this->check('Sessioni cifrate', config('session.encrypt') === true, 'Cifratura applicativa del payload; chiavi e backup vanno protetti separatamente.'),
            $this->check('Autenticazione a due fattori', Features::enabled(Features::twoFactorAuthentication()) && Schema::hasColumn('users', 'two_factor_confirmed_at'), 'Verificare attivazione e utilizzo effettivo per tutti gli account privilegiati.'),
            $this->check('MFA degli editor', $this->editorsHaveMfa(), 'Controlla gli account editor configurati: la disponibilità della funzione non dimostra che sia stata attivata. MFA non obbligatoria per l’accesso: verificarne anche l’enforcement prima dell’adeguatezza.'),
            $this->check('Registro richieste privacy', Schema::hasTable('privacy_requests'), 'Richieste cifrate, storico dei riscontri, scadenza iniziale di un mese. Nessuna proroga automatica.'),
        ];
    }

    /** @return list<array{name: string, detail: string}> */
    public function outstanding(): array
    {
        return [
            ['name' => 'Titolare, ruoli e informative (artt. 13–14 GDPR)', 'detail' => 'Confermare identità e contatti del titolare, ruolo dei team, finalità, basi giuridiche, destinatari, trasferimenti, conservazione e modalità di esercizio dei diritti. Pubblicare informative complete prima della raccolta.'],
            ['name' => 'Registro trattamenti, fornitori e conservazione', 'detail' => 'Inventariare dati e flussi, inclusi piloti, documenti, newsletter, email, GPS e copie offline. Verificare accordi art. 28, subfornitori, localizzazione e trasferimenti. Approvare tempi e procedure di cancellazione anche per backup, log e richieste privacy.'],
            ['name' => 'Backup e ripristino (art. 32 GDPR)', 'detail' => 'Definire RPO/RTO, copie cifrate separate, accessi e retention. Eseguire e documentare una prova reale di ripristino; i test applicativi non la sostituiscono.'],
            ['name' => 'Incidenti e data breach (artt. 33–34 GDPR)', 'detail' => 'Nominare referente e reperibilità, registrare gli incidenti e valutarne il rischio. Predisporre notifica ove richiesta entro 72 ore dalla conoscenza, comunicazione agli interessati ove dovuta e procedure con i team titolari.'],
            ['name' => 'Valutazioni privacy e tracciamenti', 'detail' => 'Valutare necessità di DPIA e DPO, basi per marketing e dati di minori, gestione consensi e revoca. Verificare cookie, identificatori di installazione, geolocalizzazione e dati salvati sul dispositivo. Non assumere il consenso dal login.'],
            ['name' => 'Sistema di gestione ISO/IEC 27001:2022', 'detail' => 'Definire campo di applicazione, responsabili, inventario, analisi e trattamento dei rischi, dichiarazione di applicabilità, politiche, formazione, gestione fornitori, evidenze operative, audit interno e riesame della direzione. La certificazione richiede un organismo esterno.'],
            ['name' => 'Verifica indipendente della produzione', 'detail' => 'Verificare isolamento tenant su tutte le superfici, MFA editor, revoca sessioni/token, patch, upload, segreti, log e infrastruttura. Eseguire test di sicurezza autorizzati e chiudere i rilievi prima di dichiarare adeguatezza.'],
        ];
    }

    private function editorsHaveMfa(): bool
    {
        $editors = config('pitmetric.update_editor_emails', []);
        if (! is_array($editors) || $editors === [] || ! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
            return false;
        }

        return User::query()->whereIn('email', $editors)->whereNotNull('two_factor_secret')->whereNotNull('two_factor_confirmed_at')->count() === count(array_unique($editors));
    }

    /** @return array{name: string, status: string, detail: string} */
    private function check(string $name, bool $passed, string $detail): array
    {
        return ['name' => $name, 'status' => $passed ? 'Configurato' : 'Da configurare', 'detail' => $detail];
    }
}

            <section aria-labelledby="app-downloads-title" class="space-y-3">
                <div class="flex items-end justify-between gap-3 px-1">
                    <h2 id="app-downloads-title" class="text-base font-black tracking-tight text-pm-text">Download e aperture</h2>
                    <span class="font-mono text-[10px] uppercase tracking-[.12em] text-pm-muted">Periodo mobile · 30 giorni</span>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['label' => 'Download APK (GitHub)', 'value' => $githubApkDownloads, 'note' => 'Totale release corrente · GitHub', 'accent' => 'text-[#F25A56]'],
                        ['label' => 'Clic download APK', 'value' => $siteReady ? $totals['apk_download'] : null, 'note' => 'Clic sul sito · 30 giorni', 'accent' => 'text-[#F2B774]'],
                        ['label' => 'Aperture PWA', 'value' => $siteReady ? $totals['pwa_open'] : null, 'note' => 'Clic su apri PWA', 'accent' => 'text-[#8BC7F6]'],
                    ] as $stat)
                        <article class="pm-panel flex min-w-0 flex-col justify-between p-4 sm:p-5">
                            <p class="text-[11px] font-bold uppercase tracking-[.1em] text-pm-text-secondary">{{ $stat['label'] }}</p>
                            <p class="mt-5 truncate font-mono text-3xl font-black tracking-[-.05em] sm:text-4xl {{ $stat['accent'] }}" title="{{ $stat['value'] === null ? 'Dato non disponibile' : number_format($stat['value'], 0, ',', '.') }}">{{ $stat['value'] === null ? '—' : number_format($stat['value'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-[11px] leading-4 text-pm-muted">{{ $stat['note'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="pm-panel relative overflow-hidden p-5 sm:p-6" aria-labelledby="site-activations-title">
                <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-20 size-64 rounded-full bg-[#8BC7F6]/[.07] blur-[65px]"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl">
                        <span class="inline-flex items-center gap-2 rounded-full border border-[#8BC7F6]/25 bg-[#8BC7F6]/10 px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-wider text-[#8BC7F6]">Nuovo · Attivazioni</span>
                        <h2 id="site-activations-title" class="mt-3 text-xl font-black tracking-tight text-pm-text">App e PWA attive dopo l'aggiornamento</h2>
                        <p class="mt-2 text-xs leading-6 text-pm-text-secondary">Il contatore cresce di <strong class="text-pm-text">+1 alla prima apertura</strong> della versione aggiornata su ciascuna installazione o browser. Le riaperture non vengono contate due volte e gli avvii offline vengono inviati alla riconnessione.</p>
                        <p class="mt-1 text-[11px] leading-5 text-pm-muted">Totali cumulativi dal rilascio del 09/11/2026, non limitati agli ultimi 30 giorni. Sono aperture confermate, non utenti connessi in questo istante.</p>
                    </div>
                    <div class="grid shrink-0 grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[440px]">
                        @foreach ([
                            ['label' => 'Totale', 'value' => $appActivations['total'] ?? null, 'accent' => 'text-[#8BC7F6]'],
                            ['label' => 'PWA', 'value' => $appActivations['pwa'] ?? null, 'accent' => 'text-pm-text'],
                            ['label' => 'Android', 'value' => $appActivations['android'] ?? null, 'accent' => 'text-pm-text'],
                            ['label' => 'iOS', 'value' => $appActivations['ios'] ?? null, 'accent' => 'text-pm-text'],
                        ] as $activation)
                            <div class="rounded-xl border border-pm-border bg-pm-subtle px-3 py-4 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-pm-text-secondary">{{ $activation['label'] }}</p>
                                <p class="mt-2 font-mono text-3xl font-black tabular-nums {{ $activation['accent'] }}">{{ $activation['value'] === null ? '—' : number_format($activation['value'], 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @unless ($activationReady)
                    <p class="relative mt-4 rounded-lg border border-pm-warning/25 bg-pm-warning-subtle px-3 py-2 text-xs text-pm-warning">Contatore in attesa: esegui le nuove migrazioni Laravel sul server.</p>
                @endunless
            </section>


<button type="button" class="btn btn-info" data-toggle="modal" data-target="#brainGuideModal">
    <i class="fas fa-book"></i> Guida
</button>

<!-- Brain Guide Modal -->
<div class="modal fade" id="brainGuideModal" tabindex="-1" role="dialog" aria-labelledby="brainGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brainGuideModalLabel">Guida Neuroni</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="brainGuideCarousel" class="custom-carousel">
                    <div class="carousel-inner">
                        <!-- Pagina 1: Introduzione -->
                        <div class="carousel-item active">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-brain fa-2x text-primary mb-3"></i></h4>
                                <h5>Sistema Neuronale del Cervello</h5>
                                <p class="lead">Guida completa ai diversi tipi di neuroni disponibili</p>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle"></i> Come usare questa guida</h6>
                                        <p class="mb-0">Ogni tipo di neurone ha la sua pagina dedicata. Usa i pulsanti "Avanti" e "Indietro" per esplorare tutte le funzionalità del sistema neurale.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 2: RILEVAMENTO -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-eye fa-2x text-primary mb-3"></i></h4>
                                <h5>RILEVAMENTO (Detection)</h5>
                                <p class="text-muted">Il sensore principale del sistema neurale</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-bullseye"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone di rilevamento è il "sensore" del cervello. La sua funzione è scandagliare l'ambiente circostante alla ricerca di obiettivi specifici entro un determinato raggio di azione.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-cog"></i> Parametri di Configurazione</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Raggio di ricerca:</strong> Numero di celle attorno all'elemento entro cui cercare</li>
                                                <li><strong>Tipo di target:</strong> Elemento specifico, qualsiasi entità, o sostanza chimica</li>
                                                <li><strong>Precisione:</strong> Ricerca esatta o approssimativa</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-sign-out-alt"></i> Uscite e Segnali</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Successo:</strong> Quando trova il target cercato</li>
                                                <li><strong>Fallimento:</strong> Quando non trova nulla nel raggio</li>
                                                <li><strong>Coordinate:</strong> Posizione esatta del target trovato</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-lightbulb"></i> Utilizzo Pratico</h6>
                                        <p>È sempre il primo neurone di una catena comportamentale. Ad esempio: "Rileva cibo vicino" attiva poi "Vai verso il cibo" che attiva "Mangia".</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 3: PERCORSO -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-route fa-2x text-success mb-3"></i></h4>
                                <h5>PERCORSO (Path)</h5>
                                <p class="text-muted">Il navigatore intelligente</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-map-marked-alt"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone percorso calcola automaticamente il cammino più efficiente verso un obiettivo precedentemente rilevato. Utilizza algoritmi avanzati di pathfinding per trovare la strada migliore evitando tutti gli ostacoli presenti nella mappa.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-microchip"></i> Tecnologia di Pathfinding</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Algoritmo A*:</strong> Trova il percorso ottimale considerando distanza e costi</li>
                                                <li><strong>Evitamento ostacoli:</strong> Muri, acqua, altri elementi</li>
                                                <li><strong>Adattabilità:</strong> Ricalcola dinamicamente se il percorso si blocca</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-sliders-h"></i> Strategia di Avvicinamento</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Stop prima del target SI:</strong> si avvicina al target fermandosi una cella prima</li>
                                                <li><strong>Stop prima del target NO:</strong> va direttamente sulla cella del target</li>
                                                <li><strong>Attivazione:</strong> Riceve coordinate dal neurone rilevamento</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle"></i> Funzionamento</h6>
                                        <p>Quando è attivo "Stop prima del target = SI", l'elemento segue il percorso calcolato ma si ferma una cella prima di raggiungere il target. Con "NO", completa il percorso arrivando esattamente sulla cella del target.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 4: ATTACCO -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-sword fa-2x text-danger mb-3"></i></h4>
                                <h5>ATTACCO (Attack)</h5>
                                <p class="text-muted">Il guerriero del sistema</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0"><i class="fas fa-bolt"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone attacco esegue un'azione di combattimento fisico contro un'entità bersaglio. È il componente finale di una catena di caccia che inizia con il rilevamento e continua con il movimento verso il target.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-dna"></i> Requisiti Genetici</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Gene Vita:</strong> Deve essere configurato (livello minimo 1)</li>
                                                <li><strong>Gene Attacco:</strong> Deve essere configurato (livello minimo 1)</li>
                                                <li><strong>Entrambi richiesti:</strong> Il neurone non funziona senza entrambi i geni</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-chart-line"></i> Calcolo della Potenza</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Formula:</strong> Potenza = (Gene Vita + Gene Attacco) / 2</li>
                                                <li><strong>Moltiplicatore:</strong> I livelli genetici influenzano direttamente il danno</li>
                                                <li><strong>Difesa:</strong> Il gene vita influenza anche la resistenza ai contrattacchi</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Condizioni di Attivazione</h6>
                                        <p>L'attacco avviene solo se l'elemento è posizionato correttamente rispetto al target. Se la posizione non è ideale, il neurone non si attiva per evitare inefficacia.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 5: MOVIMENTO -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-walking fa-2x text-info mb-3"></i></h4>
                                <h5>MOVIMENTO (Movement)</h5>
                                <p class="text-muted">Il motore dell'elemento</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-arrows-alt"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone movimento permette all'elemento di spostarsi liberamente nella griglia di gioco. Gestisce automaticamente l'evitamento degli ostacoli e calcola percorsi validi in tempo reale.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-expand-arrows-alt"></i> Raggio di Movimento</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Celle raggiungibili:</strong> Numero massimo di celle in un singolo turno</li>
                                                <li><strong>Area circolare:</strong> Il movimento è limitato a un raggio circolare</li>
                                                <li><strong>Ostacoli:</strong> Muri, acqua e altri elementi bloccano il passaggio</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-route"></i> Intelligenza di Movimento</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Pathfinding automatico:</strong> Trova strade alternative se bloccato</li>
                                                <li><strong>Evitamento collisioni:</strong> Non passa attraverso ostacoli solidi</li>
                                                <li><strong>Adattabilità:</strong> Ricalcola percorso se necessario</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="alert alert-primary">
                                        <h6><i class="fas fa-gamepad"></i> Attivazione Manuale</h6>
                                        <p>Il giocatore può attivare direttamente il movimento per esplorazione libera o posizionamento strategico.</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-secondary">
                                        <h6><i class="fas fa-robot"></i> Attivazione Automatica</h6>
                                        <p>Altri neuroni possono attivare il movimento come parte di comportamenti programmati (es. fuga, inseguimento).</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 6: CHIMICO -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-flask fa-2x text-warning mb-3"></i></h4>
                                <h5>CHIMICO (Chemical)</h5>
                                <p class="text-muted">Il sensore ambientale chimico</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0"><i class="fas fa-microscope"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone chimico è uno specializzato sensore ambientale che rileva e misura le concentrazioni di elementi chimici presenti nell'aria, nel terreno o nell'acqua circostante.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Elementi Rilevabili</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Idrogeno (H):</strong> Gas leggero, componente base</li>
                                                <li><strong>Elio (He):</strong> Gas nobile, inerte</li>
                                                <li><strong>Cloro (Cl):</strong> Gas tossico, reattivo</li>
                                                <li><strong>Altri elementi:</strong> Secondo configurazione ambientale</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-balance-scale"></i> Sistema di Regole</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Soglie configurabili:</strong> Valori min/max per ogni elemento</li>
                                                <li><strong>Classificazione:</strong> Basso/Medio/Alto basata sulle soglie</li>
                                                <li><strong>Precisione:</strong> Rilevamento esatto delle concentrazioni</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-brain"></i> Uscite Multiple</h6>
                                        <p>Genera segnali diversi per ogni livello di concentrazione: "livello basso", "livello medio", "livello alto". Perfetto per comportamenti ambientali complessi.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 7: CONSUMO -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-utensils fa-2x text-success mb-3"></i></h4>
                                <h5>CONSUMO (Consume)</h5>
                                <p class="text-muted">Il neurone della nutrizione e crescita</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-cookie-bite"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone consumo permette all'elemento di assimilare un bersaglio posizionato sulla sua stessa cella. Questo processo elimina il bersaglio e innesca aggiornamenti genetici basati sulle caratteristiche dell'elemento consumato.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-dna"></i> Effetti sui Geni</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Aggiornamento Genetico:</strong> Modifica i valori dei geni dell'elemento consumatore.</li>
                                                <li><strong>Parametri Dinamici:</strong> Gli effetti dipendono dalla composizione del bersaglio.</li>
                                                <li><strong>Atomicità:</strong> L'aggiornamento avviene istantaneamente dopo il consumo.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-exclamation-circle"></i> Requisiti di Esecuzione</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Posizionamento:</strong> Il consumatore deve trovarsi esattamente sulla cella del bersaglio.</li>
                                                <li><strong>Eliminazione:</strong> Il bersaglio viene rimosso definitivamente dalla mappa.</li>
                                                <li><strong>Sequenza:</strong> Solitamente attivato dopo un neurone Percorso (senza stop).</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-sync"></i> Sincronizzazione</h6>
                                        <p>Questo neurone sincronizza automaticamente i cambiamenti genetici tra il server e l'interfaccia di gioco, assicurando che le statistiche dell'elemento siano sempre aggiornate.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 8: LETTURA GENE -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-dna fa-2x text-info mb-3"></i></h4>
                                <h5>LETTURA GENE (Read Gene)</h5>
                                <p class="text-muted">Il monitor dello stato interno</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-search"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Il neurone di lettura gene permette di monitorare il valore attuale di uno specifico gene all'interno dell'elemento. È essenziale per creare comportamenti che dipendono dalle condizioni fisiche o dalle capacità dell'elemento.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-filter"></i> Applicazioni Lociche</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Controllo Salute:</strong> Verifica se il gene vita è sceso sotto una soglia per attivare la fuga.</li>
                                                <li><strong>Potenza Attacco:</strong> Modula il comportamento in base alla forza attuale.</li>
                                                <li><strong>Adattabilità:</strong> Cambia strategia in base all'evoluzione genetica.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-layer-group"></i> Uscite Condizionali</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Range di Valori:</strong> Genera segnali diversi in base all'intensità del gene.</li>
                                                <li><strong>Trigger Precisi:</strong> Può attivare rami diversi del cervello in base ai dati letti.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 9: LIMITE GENE -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-vial fa-2x text-danger mb-3"></i></h4>
                                <h5>LIMITE GENE (Max Value Gene)</h5>
                                <p class="text-muted">Il sensore di saturazione genetica</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0"><i class="fas fa-arrow-up"></i> Funzione Principale</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>Questo neurone specializzato rileva se un determinato gene ha raggiunto il suo valore massimo consentito dal genoma. È fondamentale per ottimizzare i processi di consumo ed evitare sprechi di risorse.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-check-double"></i> Controllo Saturazione</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>SI (Saturato):</strong> Attiva l'uscita se il gene è al massimo.</li>
                                                <li><strong>NO (Non Saturato):</strong> Attiva l'uscita se c'è ancora spazio per crescere.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-stopwatch"></i> Utilizzo Strategico</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Efficienza:</strong> Interrompe la ricerca di cibo se i geni sono già al massimo.</li>
                                                <li><strong>Gestione Risorse:</strong> Indirizza l'elemento verso altri obiettivi quando un parametro è completo.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 7: START e END -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-play-circle fa-2x text-success mb-3"></i></h4>
                                <h5>START e END</h5>
                                <p class="text-muted">I marcatori di inizio e fine circuito</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="card border-success h-100">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-play"></i> Neurone START</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Ruolo:</strong> Punto di partenza di ogni circuito neurale. È il primo neurone che viene eseguito quando un circuito si attiva.</p>
                                            <p><strong>Attivazione:</strong> Automatica quando il circuito viene abilitato dal giocatore o dal sistema.</p>
                                            <p><strong>Connessioni:</strong> Ha solo uscite (output), nessun ingresso. È il "trigger" iniziale.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-danger h-100">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0"><i class="fas fa-stop"></i> Neurone END</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Ruolo:</strong> Punto di arrivo di un circuito neurale. Segnala la conclusione dell'esecuzione.</p>
                                            <p><strong>Funzione:</strong> Conferma che il comportamento programmato è stato completato con successo.</p>
                                            <p><strong>Connessioni:</strong> Ha solo ingressi (input), nessuna uscita. È il "terminatore".</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-primary">
                                        <h6><i class="fas fa-project-diagram"></i> Struttura dei Circuiti</h6>
                                        <p>Ogni circuito neurale deve avere esattamente un neurone START e almeno un neurone END. La struttura è: START → [Neuroni di processamento] → END.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagina 8: COLLEGAMENTI -->
                        <div class="carousel-item">
                            <div class="text-center mb-4">
                                <h4><i class="fas fa-project-diagram fa-2x text-info mb-3"></i></h4>
                                <h5>COLLEGAMENTI e CIRCUITI</h5>
                                <p class="text-muted">L'architettura del cervello</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-sitemap"></i> Come Funzionano i Collegamenti</h6>
                                        </div>
                                        <div class="card-body">
                                            <p>I collegamenti definiscono il flusso di esecuzione nel cervello neurale. Sono rappresentati da frecce colorate che connettono l'uscita di un neurone all'ingresso di un altro neurone.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-arrow-right"></i> Tipi di Segnale</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Successo/Fallimento:</strong> Da neuroni di rilevamento</li>
                                                <li><strong>Trigger semplice:</strong> Attivazione diretta</li>
                                                <li><strong>Valori chimici:</strong> Livelli basso/medio/alto</li>
                                                <li><strong>Condizionale:</strong> Basato su condizioni specifiche</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-circle-notch"></i> Circuiti Neurali</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li><strong>Gruppi funzionali:</strong> Neuroni collegati per uno scopo</li>
                                                <li><strong>Attivazione indipendente:</strong> Ogni circuito può essere ON/OFF</li>
                                                <li><strong>Modularità:</strong> Circuiti riutilizzabili</li>
                                                <li><strong>Complessità:</strong> Da semplice (2 neuroni) a complesso</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-lightbulb"></i> Esempio Pratico</h6>
                                        <p><strong>Circuito "Caccia":</strong> START → RILEVAMENTO (cibo) → PERCORSO (verso cibo) → ATTACCO (mangia) → END</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 px-2">
                        <button class="btn btn-outline-secondary" type="button" id="guidePrevBtn">
                            <i class="fas fa-chevron-left"></i> Indietro
                        </button>

                        <div class="text-center">
                            <small class="text-muted" id="currentSlideNumber">1 / 11</small>
                        </div>

                        <button class="btn btn-outline-secondary" type="button" id="guideNextBtn">
                            Avanti <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script>
    $(function () {
        let currentSlide = 0;
        const totalSlides = 11;
        const carousel = document.getElementById('brainGuideCarousel');
        const prevBtn = document.getElementById('guidePrevBtn');
        const nextBtn = document.getElementById('guideNextBtn');
        const slideNumber = document.getElementById('currentSlideNumber');

        function updateSlideDisplay() {
            if (!carousel) return;

            const items = carousel.querySelectorAll('.carousel-item');
            items.forEach((item) => {
                item.style.display = 'none';
                item.classList.remove('active');
            });

            if (items[currentSlide]) {
                items[currentSlide].style.display = 'block';
                items[currentSlide].classList.add('active');
            }

            if (slideNumber) {
                slideNumber.textContent = `${currentSlide + 1} / ${totalSlides}`;
            }

            if (prevBtn) {
                prevBtn.disabled = currentSlide === 0;
                prevBtn.style.opacity = currentSlide === 0 ? '0.5' : '1';
            }
            if (nextBtn) {
                nextBtn.disabled = currentSlide === totalSlides - 1;
                nextBtn.style.opacity = currentSlide === totalSlides - 1 ? '0.5' : '1';
            }
        }

        if (prevBtn && nextBtn && carousel) {
            prevBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSlideDisplay();
                }
            });

            nextBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentSlide < totalSlides - 1) {
                    currentSlide++;
                    updateSlideDisplay();
                }
            });

            updateSlideDisplay();
        }

        $('#brainGuideModal').on('show.bs.modal', function() {
            currentSlide = 0;
            updateSlideDisplay();
        });
    });
</script>
@stop

@section('css')
<style>
    #brainGuideCarousel .carousel-item {
        display: none;
    }

    #brainGuideCarousel .carousel-item.active {
        display: block !important;
    }

    #guidePrevBtn:disabled, #guideNextBtn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    #currentSlideNumber {
        font-weight: bold;
    }

    .custom-carousel .carousel-item {
        position: static !important;
    }

    .modal-body .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }

    .modal-body .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .modal-body .card-header {
        font-size: 14px;
        font-weight: bold;
        padding: 8px 12px;
    }

    .modal-body .card-body {
        padding: 10px 12px;
    }

    .modal-body .list-unstyled li {
        margin-bottom: 4px;
    }

    .modal-body .fas {
        width: 16px;
        margin-right: 8px;
    }
</style>
@stop
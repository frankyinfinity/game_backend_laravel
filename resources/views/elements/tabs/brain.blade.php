@include('elements.tabs.guide')

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

                         <!-- Pagina 7: LETTURA GENE -->
                         <div class="carousel-item">
                             <div class="text-center mb-4">
                                 <h4><i class="fas fa-dna fa-2x text-info mb-3"></i></h4>
                                 <h5>LETTURA GENE (Gene Reading)</h5>
                                 <p class="text-muted">Il lettore di informazioni genetiche</p>
                             </div>

                             <div class="row mb-3">
                                 <div class="col-12">
                                     <div class="card border-info">
                                         <div class="card-header bg-info text-white">
                                             <h6 class="mb-0"><i class="fas fa-dna"></i> Funzione Principale</h6>
                                         </div>
                                         <div class="card-body">
                                             <p>Il neurone lettura gene permette di accedere alle informazioni genetiche configurate nell'elemento. È in grado di leggere i valori genetici definiti nella sezione "Informazioni" e utilizzarli per prendere decisioni comportamentali.</p>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="card h-100">
                                         <div class="card-header">
                                             <h6 class="mb-0"><i class="fas fa-list"></i> Configurazione</h6>
                                         </div>
                                         <div class="card-body">
                                             <ul>
                                                 <li><strong>Selezione Gene:</strong> Scegli un gene dalla sezione Informazioni</li>
                                                 <li><strong>Accesso diretto:</strong> Legge i valori genetici attuali dell'elemento</li>
                                                 <li><strong>Trigger semplice:</strong> Genera un segnale di attivazione quando eseguito</li>
                                             </ul>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="card h-100">
                                         <div class="card-header">
                                             <h6 class="mb-0"><i class="fas fa-bolt"></i> Uscita</h6>
                                         </div>
                                         <div class="card-body">
                                             <ul>
                                                 <li><strong>Trigger:</strong> Segnale verde che indica la lettura completata</li>
                                                 <li><strong>Attivazione:</strong> Può essere collegato ad altri neuroni per comportamenti condizionati</li>
                                                 <li><strong>Sequenziale:</strong> Perfetto per catene di decisioni basate sui geni</li>
                                             </ul>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="row mt-3">
                                 <div class="col-12">
                                     <div class="alert alert-info">
                                         <h6><i class="fas fa-info-circle"></i> Utilizzo Pratico</h6>
                                         <p>Ideale per comportamenti che dipendono dalle caratteristiche genetiche dell'elemento, come adattamenti ambientali o specializzazioni comportamentali basate sui valori genetici configurati.</p>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <!-- Pagina 8: START e END -->
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

                          <!-- Pagina 9: COLLEGAMENTI -->
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
                            <small class="text-muted" id="currentSlideNumber">1 / 9</small>
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
 @php
     use App\Helpers\NeuronTooltipHelper;
     $existingNeuronItems = $element->brain && $element->brain->neurons
         ? $element->brain->neurons->map(function ($n) {
             return [
                 'id' => (int) $n->id,
                 'type' => $n->type,
                 'grid_i' => (int) $n->grid_i,
                 'grid_j' => (int) $n->grid_j,
                 'radius' => $n->radius !== null ? (int) $n->radius : null,
                 'stop_before_target' => (bool) $n->stop_before_target,
                 'target_type' => $n->target_type,
                 'target_element_id' => $n->target_element_id !== null ? (int) $n->target_element_id : null,
                 'chemical_element_id' => $n->chemical_element_id !== null ? (int) $n->chemical_element_id : null,
                 'complex_chemical_element_id' => $n->complex_chemical_element_id !== null ? (int) $n->complex_chemical_element_id : null,
                 'gene_life_id' => $n->gene_life_id !== null ? (int) $n->gene_life_id : null,
                 'gene_attack_id' => $n->gene_attack_id !== null ? (int) $n->gene_attack_id : null,
                 'element_infomation_id' => $n->element_infomation_id !== null ? (int) $n->element_infomation_id : null,
                 'element_has_rule_chimical_element_id' => $n->element_has_rule_chimical_element_id !== null ? (int) $n->element_has_rule_chimical_element_id : null,
                 'tooltip' => NeuronTooltipHelper::generateTextFromNeuron($n),
                 'condition_orders' => $n->conditionOrders->map(function ($co) {
                     return [
                         'id' => $co->id,
                         'condition' => $co->condition,
                         'sort_order' => (int) $co->sort_order,
                         'color' => $co->color,
                         'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
                     ];
                 })->values()->all(),
             ];
         })->values()->all()
         : [];
    $existingNeuronLinks = $element->brain && $element->brain->neurons
        ? $element->brain->neurons->flatMap(function ($n) {
            return $n->outgoingLinks->map(function ($l) {
                return [
                    'id' => (int) $l->id,
                    'from_neuron_id' => (int) $l->from_neuron_id,
                    'to_neuron_id' => (int) $l->to_neuron_id,
                    'neuron_condition_order_id' => (int) $l->neuron_condition_order_id,
                    'condition' => $l->condition,
                    'color' => $l->color,
                ];
            });
        })->values()->all()
        : [];
    $existingCircuits = $element->brain && $element->brain->circuits
        ? $element->brain->circuits->map(function ($c) {
            return [
                'id' => $c->id,
                'uid' => $c->uid,
                'state' => $c->state,
                'active' => (bool) $c->active,
                'color' => $c->color,
                'start_neuron_id' => $c->start_neuron_id,
                'neuron_ids' => $c->details->pluck('neuron_id')->toArray(),
            ];
        })->values()->all()
        : [];
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="brain_grid_width">Larghezza Griglia</label>
            <input type="number"
                   class="form-control @error('brain_grid_width') is-invalid @enderror"
                   id="brain_grid_width"
                   name="brain_grid_width"
                   min="1"
                   step="1"
                   value="{{ old('brain_grid_width', optional($element->brain)->grid_width ?? 5) }}"
                   placeholder="Es. 5">
            @error('brain_grid_width')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="brain_grid_height">Altezza Griglia</label>
            <input type="number"
                   class="form-control @error('brain_grid_height') is-invalid @enderror"
                   id="brain_grid_height"
                   name="brain_grid_height"
                   min="1"
                   step="1"
                   value="{{ old('brain_grid_height', optional($element->brain)->grid_height ?? 5) }}"
                   placeholder="Es. 5">
            @error('brain_grid_height')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
</div>

<input type="hidden" id="neuron_items" name="neuron_items" value="{{ old('neuron_items', json_encode($existingNeuronItems)) }}">
<input type="hidden" id="neuron_links" name="neuron_links" value="{{ old('neuron_links', json_encode($existingNeuronLinks)) }}">
<input type="hidden" id="neuron_circuits" name="neuron_circuits" value="{{ json_encode($existingCircuits) }}">

<div class="row mb-3">
    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salva
        </button>
        <a href="{{ route('elements.index') }}" class="btn btn-secondary">
            <i class="fas fa-times"></i> Annulla
        </a>
        <small class="text-muted ml-2">Ricordati di salvare per mantenere le modifiche.</small>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Anteprima Griglia (PIXI.js)</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 text-center">
                        <div id="brain-grid-pixi" style="display:inline-block; border:1px solid #b0b0b0; border-radius:4px;"></div>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="fas fa-network-wired"></i> Circuiti</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover border" id="circuits-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>UID</th>
                                        <th class="text-center">Stato</th>
                                        <th class="text-center">Colore</th>
                                        <th class="text-right">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="circuits-table-body">
                                    <!-- Popolato via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="brainNeuronModal" tabindex="-1" role="dialog" aria-labelledby="brainNeuronModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brainNeuronModalLabel">Configura Neurone</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-cog"></i> Configurazione Neurone</h6>
                        <div class="mb-2 text-muted">
                            Cella selezionata: <strong id="selected_cell_label">-</strong>
                        </div>
                        <div class="form-group">
                            <label for="neuron_type">Tipologia</label>
                            <select class="form-control" id="neuron_type">
                                @foreach(\App\Models\Neuron::TYPE_LABELS as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_radius_group">
                            <label for="neuron_radius">Raggio (in celle)</label>
                            <input type="number" class="form-control" id="neuron_radius" min="1" step="1" value="1">
                        </div>
                        <div class="form-group" id="neuron_stop_before_target_group" style="display:none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="neuron_stop_before_target" value="1">
                                <label class="form-check-label" for="neuron_stop_before_target">
                                    Stop movimento prima del target
                                </label>
                            </div>
                        </div>
                        <div class="form-group" id="neuron_target_type_group">
                            <label for="neuron_target_type">Target da individuare</label>
                            <select class="form-control" id="neuron_target_type">
                                <option value="">-- Seleziona Target --</option>
                                <option value="element">Element</option>
                                <option value="entity">Entity</option>
                                <option value="chemical_element">Elemento Chimico</option>
                                <option value="complex_chemical_element">Elemento Chimico Complesso</option>
                            </select>
                        </div>
                        <div class="form-group" id="neuron_target_element_group">
                            <label for="neuron_target_element_id">Seleziona Element</label>
                            <select class="form-control" id="neuron_target_element_id">
                                <option value="">-- Seleziona --</option>
                                @foreach(($brainTargetElements ?? collect()) as $targetElement)
                                    <option value="{{ $targetElement->id }}">{{ $targetElement->name }} (#{{ $targetElement->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_gene_life_group" style="display:none;">
                            <label for="neuron_gene_life_id">Gene Vita</label>
                            <select class="form-control" id="neuron_gene_life_id">
                                <option value="">-- Seleziona Gene Vita --</option>
                                @foreach(($brainGenes ?? collect()) as $brainGene)
                                    <option value="{{ $brainGene->id }}">{{ $brainGene->name }} (#{{ $brainGene->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_gene_attack_group" style="display:none;">
                            <label for="neuron_gene_attack_id">Gene Attacco</label>
                            <select class="form-control" id="neuron_gene_attack_id">
                                <option value="">-- Seleziona Gene Attacco --</option>
                                @foreach(($brainGenes ?? collect()) as $brainGene)
                                    <option value="{{ $brainGene->id }}">{{ $brainGene->name }} (#{{ $brainGene->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_element_infomation_group" style="display:none;">
                            <label for="neuron_element_infomation_id">Gene</label>
                            <select class="form-control" id="neuron_element_infomation_id" title="Seleziona un gene">
                                <option value="">-- Seleziona Gene --</option>
                                @foreach(($informationGenes ?? collect()) as $infoGene)
                                    <option value="{{ $infoGene->id }}" title="{{ $infoGene->name }} (#{{ $infoGene->id }})">{{ $infoGene->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_rule_chimical_element_group" style="display:none;">
                            <label for="neuron_element_has_rule_chimical_element_id">Regola Elemento Chimico</label>
                            <select class="form-control" id="neuron_element_has_rule_chimical_element_id">
                                <option value="">-- Seleziona Regola --</option>
                                @foreach(($allRuleChimicalElements ?? collect()) as $rule)
                                    <option value="{{ $rule->id }}">{{ $rule->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_chemical_element_group" style="display:none;">
                            <label for="neuron_chemical_element_id">Elemento Chimico</label>
                            <select class="form-control" id="neuron_chemical_element_id">
                                <option value="">-- Seleziona Elemento Chimico --</option>
                                @foreach(($brainChimicalElements ?? collect()) as $elem)
                                    <option value="{{ $elem->id }}">{{ $elem->name }} ({{ $elem->symbol }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_complex_chemical_element_group" style="display:none;">
                            <label for="neuron_complex_chemical_element_id">Elemento Chimico Complesso</label>
                            <select class="form-control" id="neuron_complex_chemical_element_id">
                                <option value="">-- Seleziona Elemento Chimico Complesso --</option>
                                @foreach(($brainComplexChimicalElements ?? collect()) as $elem)
                                    <option value="{{ $elem->id }}">{{ $elem->name }} ({{ $elem->symbol }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-link"></i> Collegamenti in uscita</h6>
                        <div id="neuron-links-container" style="max-height: 400px; overflow-y: auto;">
                            <!-- Links will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" id="btn_delete_neuron">Rimuovi</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btn_save_neuron">Salva neurone</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('js')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
    @endpush
@endonce

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const widthInput = document.getElementById('brain_grid_width');
    const heightInput = document.getElementById('brain_grid_height');
    const container = document.getElementById('brain-grid-pixi');
    const neuronItemsInput = document.getElementById('neuron_items');
    const neuronLinksInput = document.getElementById('neuron_links');
    const neuronCircuitsInput = document.getElementById('neuron_circuits');
    const neuronTypeInput = document.getElementById('neuron_type');
    const neuronRadiusInput = document.getElementById('neuron_radius');
    const neuronRadiusGroup = document.getElementById('neuron_radius_group');
    const neuronStopBeforeTargetGroup = document.getElementById('neuron_stop_before_target_group');
    const neuronStopBeforeTargetInput = document.getElementById('neuron_stop_before_target');
    const neuronTargetTypeInput = document.getElementById('neuron_target_type');
    const neuronTargetTypeGroup = document.getElementById('neuron_target_type_group');
    const neuronTargetElementGroup = document.getElementById('neuron_target_element_group');
    const neuronTargetElementIdInput = document.getElementById('neuron_target_element_id');
    const neuronGeneLifeGroup = document.getElementById('neuron_gene_life_group');
    const neuronGeneLifeIdInput = document.getElementById('neuron_gene_life_id');
    const neuronGeneAttackGroup = document.getElementById('neuron_gene_attack_group');
    const neuronGeneAttackIdInput = document.getElementById('neuron_gene_attack_id');
    const neuronElementInfomationGroup = document.getElementById('neuron_element_infomation_group');
    const neuronElementInfomationIdInput = document.getElementById('neuron_element_infomation_id');
    const neuronRuleChimicalElementGroup = document.getElementById('neuron_rule_chimical_element_group');
    const neuronRuleChimicalElementIdInput = document.getElementById('neuron_element_has_rule_chimical_element_id');
    const neuronChemicalElementGroup = document.getElementById('neuron_chemical_element_group');
    const neuronChemicalElementIdInput = document.getElementById('neuron_chemical_element_id');
    const neuronComplexChemicalElementGroup = document.getElementById('neuron_complex_chemical_element_group');
    const neuronComplexChemicalElementIdInput = document.getElementById('neuron_complex_chemical_element_id');
    const selectedCellLabel = document.getElementById('selected_cell_label');
    const saveNeuronBtn = document.getElementById('btn_save_neuron');
    const deleteNeuronBtn = document.getElementById('btn_delete_neuron');
    const neuronModalEl = document.getElementById('brainNeuronModal');
    const circuitsTableBody = document.getElementById('circuits-table-body');
    if (!widthInput || !heightInput || !container || !neuronItemsInput || !neuronLinksInput || !neuronTypeInput || !neuronRadiusInput || !neuronRadiusGroup || !neuronStopBeforeTargetGroup || !neuronStopBeforeTargetInput || !neuronTargetTypeInput || !neuronTargetTypeGroup || !neuronTargetElementGroup || !neuronTargetElementIdInput || !neuronGeneLifeGroup || !neuronGeneLifeIdInput || !neuronGeneAttackGroup || !neuronGeneAttackIdInput || !neuronElementInfomationGroup || !neuronElementInfomationIdInput || !neuronRuleChimicalElementGroup || !neuronRuleChimicalElementIdInput || !neuronChemicalElementGroup || !neuronChemicalElementIdInput || !neuronComplexChemicalElementGroup || !neuronComplexChemicalElementIdInput || !selectedCellLabel || !saveNeuronBtn || !deleteNeuronBtn || !neuronModalEl || !circuitsTableBody) {
        console.warn('One or more required elements for the brain tab are missing.');
        return;
    }

    if (typeof PIXI === 'undefined') {
        console.error('PIXI.js is not loaded.');
        return;
    }

     const fixedCellSize = 40;
    const typeDetection = @json(\App\Models\Neuron::TYPE_DETECTION);
    const typePath = @json(\App\Models\Neuron::TYPE_PATH);
    const typeAttack = @json(\App\Models\Neuron::TYPE_ATTACK);
    const typeMovement = @json(\App\Models\Neuron::TYPE_MOVEMENT);
    const typeStart = @json(\App\Models\Neuron::TYPE_START);
    const typeEnd = @json(\App\Models\Neuron::TYPE_END);
     const typeReadChimicalElement = @json(\App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT);
     const typeReadGene = @json(\App\Models\Neuron::TYPE_READ_GENE);
     const typeMaxValueGene = @json(\App\Models\Neuron::TYPE_MAX_VALUE_GENE);
     const MAX_VALUE_GENE_YES = @json(\App\Models\Neuron::MAX_VALUE_GENE_YES);
     const MAX_VALUE_GENE_NO = @json(\App\Models\Neuron::MAX_VALUE_GENE_NO);
     const targetTypeElement = @json(\App\Models\Neuron::TARGET_TYPE_ELEMENT);
    const targetTypeEntity = @json(\App\Models\Neuron::TARGET_TYPE_ENTITY);
    const targetTypeChemicalElement = @json(\App\Models\Neuron::TARGET_TYPE_CHEMICAL_ELEMENT);
    const targetTypeComplexChemicalElement = @json(\App\Models\Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT);
    const typeSymbols = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    const typeLabels = @json(\App\Models\Neuron::TYPE_LABELS);
    const targetTypeLabels = @json(\App\Models\Neuron::TARGET_TYPE_LABELS);
    const portDetectionSuccess = @json(\App\Models\NeuronLink::PORT_DETECTION_SUCCESS);
    const portDetectionFailure = @json(\App\Models\NeuronLink::PORT_DETECTION_FAILURE);
     const portTrigger = @json(\App\Models\NeuronLink::PORT_TRIGGER);
     const DEFAULT_CHIMICAL_ELEMENT = @json(\App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT);
     const portColors = @json(\App\Models\NeuronLink::PORT_COLORS);
    const saveNeuronUrl = @json(route('elements.brain.neurons.save', $element));
    const moveNeuronUrl = @json(route('elements.brain.neurons.move', [$element, ':neuron']));
    const deleteNeuronUrl = @json(route('elements.brain.neurons.delete', $element));
    const saveNeuronLinkUrl = @json(route('elements.brain.neuron-links.save', $element));
    const brainChimicalElements = @json($brainChimicalElements ?? []);
    const brainComplexChimicalElements = @json($brainComplexChimicalElements ?? []);
    const brainTargetElements = @json($brainTargetElements ?? []);
    const informationGenes = @json($informationGenes ?? []);
    const deleteNeuronLinkUrl = @json(route('elements.brain.neuron-links.delete', $element));
    const allRuleChimicalElements = @json($allRuleChimicalElements);

     function getConditionColor(type, ruleId, condition) {
         if (type === typeReadChimicalElement) {
             const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(ruleId));
             if (rule && rule.details) {
                 const detail = rule.details.find(d => `[${d.min}/${d.max}]` === condition);
                 if (detail && detail.color) return detail.color;
             }
             if (condition === DEFAULT_CHIMICAL_ELEMENT) return '#6b7280';
         }
         if (type === typeMaxValueGene) {
             if (condition === MAX_VALUE_GENE_YES) return '#16A34A';
             if (condition === MAX_VALUE_GENE_NO) return '#DC2626';
             return '#16A34A';
         }
         const colorInt = portColors[condition] || portColors[portTrigger];
         // Ensure colorInt is handled as hex string
         return '#' + (colorInt ? colorInt.toString(16).padStart(6, '0') : '000000');
     }
    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    let app = null;
    let selectedCell = null;
    let neuronItems = [];
    let neuronLinks = [];
    let currentNeuronId = null;
    let tooltipText = null;
    let tooltipBg = null;
    let neuronCircuits = [];
    let draggedNeuron = null;
    let draggedNeuronLayer = null;
    let draggedNeuronOriginalI = null;
    let draggedNeuronOriginalJ = null;
    let draggedOffsetX = 0;
    let draggedOffsetY = 0;
    let dragStarted = false;
    let highlightedCircuitId = null;
    let circuitsDataTable = null;

    try {
        const parsed = JSON.parse(neuronItemsInput.value || '[]');
        neuronItems = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        neuronItems = [];
    }
    neuronLinks = @json($existingNeuronLinks);
    if (!Array.isArray(neuronLinks)) {
        neuronLinks = [];
    }
    
    try {
        const parsedCircuits = JSON.parse(neuronCircuitsInput ? neuronCircuitsInput.value : '[]');
        neuronCircuits = Array.isArray(parsedCircuits) ? parsedCircuits : [];
    } catch (e) {
        neuronCircuits = [];
    }


    function normalize(value) {
        const parsed = parseInt(value, 10);
        if (Number.isNaN(parsed) || parsed < 1) return 1;
        return parsed;
    }

    function renderCircuitsTable() {
        if (!circuitsTableBody) return;
        
        if ($.fn.DataTable.isDataTable('#circuits-table')) {
            $('#circuits-table').DataTable().destroy();
        }

        circuitsTableBody.innerHTML = '';

        neuronCircuits.forEach((circuit, index) => {
            const cColorHex = circuit.color || '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
            
            const tr = document.createElement('tr');
            tr.dataset.circuitId = circuit.id;
            
            const tdUid = document.createElement('td');
            tdUid.innerHTML = `<small class="text-monospace">${circuit.uid.substring(0, 8)}...</small>`;
            tdUid.title = circuit.uid;
            tr.appendChild(tdUid);

            const tdStatus = document.createElement('td');
            tdStatus.className = 'text-center';
            const badgeClass = circuit.state === 'closed' ? 'badge-success' : 'badge-warning';
            tdStatus.innerHTML = `<span class="badge ${badgeClass}">${circuit.state}</span>`;
            tr.appendChild(tdStatus);

            const tdColor = document.createElement('td');
            tdColor.className = 'text-center';
            tdColor.innerHTML = `<div style="width: 20px; height: 20px; background-color: ${cColorHex}; border-radius: 4px; margin: 0 auto; border: 1px solid #ccc;"></div>`;
            tr.appendChild(tdColor);

            const tdActions = document.createElement('td');
            tdActions.className = 'text-right';
            const btnToggle = document.createElement('button');
            btnToggle.type = 'button';
            btnToggle.className = `btn btn-xs ${circuit.active ? 'btn-success' : 'btn-secondary'} btn-toggle-circuit`;
            btnToggle.dataset.id = circuit.id;
            btnToggle.innerHTML = circuit.active ? '<i class="fas fa-check-circle"></i> Attivo' : '<i class="fas fa-times-circle"></i> Disattivo';

            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'btn btn-xs btn-danger ml-1 btn-delete-circuit';
            btnDelete.dataset.id = circuit.id;
            btnDelete.innerHTML = '<i class="fas fa-trash"></i>';
            btnDelete.title = 'Elimina Circuito';
            
            tdActions.appendChild(btnToggle);
            tdActions.appendChild(btnDelete);
            tr.appendChild(tdActions);

            circuitsTableBody.appendChild(tr);
        });

        $('#circuits-table').DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: true,
            autoWidth: false,
            destroy: true,
            language: {
                emptyTable: "Nessun circuito rilevato"
            }
        });
    }

    // Event delegation for DataTable rows
    $(document).on('mouseenter', '#circuits-table-body tr', function() {
        const circuitId = $(this).data('circuit-id');
        if (circuitId) {
            highlightedCircuitId = circuitId;
            renderGrid();
        }
    });

    $(document).on('mouseleave', '#circuits-table-body tr', function() {
        highlightedCircuitId = null;
        renderGrid();
    });

    $(document).on('click', '.btn-toggle-circuit', function(e) {
        e.stopPropagation();
        const circuitId = $(this).data('id');
        toggleCircuitActive(circuitId);
    });

    $(document).on('click', '.btn-delete-circuit', function(e) {
        e.stopPropagation();
        const circuitId = $(this).data('id');
        if (confirm('Sei sicuro di voler eliminare questo circuito?')) {
            deleteCircuit(circuitId);
        }
    });

    // Clear highlight when leaving the table area
    $(document).on('mouseleave', '#circuits-table', function() {
        highlightedCircuitId = null;
        renderGrid();
    });

    async function toggleCircuitActive(circuitId) {
        const url = `/elements/${@json($element->id)}/brain/circuits/` + circuitId + `/toggle-active`;
        try {
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success) {
                neuronCircuits = data.circuits;
                renderGrid();
                renderCircuitsTable();
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function deleteCircuit(circuitId) {
        const url = `/elements/${@json($element->id)}/brain/circuits/` + circuitId;
        try {
            const resp = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (data.success) {
                neuronItems = data.neurons || neuronItems;
                neuronLinks = data.links || neuronLinks;
                neuronCircuits = data.circuits;
                updateNeuronHiddenInput();
                updateNeuronLinksHiddenInput();
                renderGrid();
                renderCircuitsTable();
            }
        } catch (e) {
            console.error(e);
        }
    }

    function neuronKey(i, j) {
        return `${i}_${j}`;
    }

    function updateNeuronHiddenInput() {
        neuronItemsInput.value = JSON.stringify(neuronItems);
    }

    function updateNeuronLinksHiddenInput() {
        neuronLinksInput.value = JSON.stringify(neuronLinks);
    }

    function filterOutOfBoundsNeurons(rows, cols) {
        neuronItems = neuronItems.filter((item) => {
            const i = Number(item.grid_i);
            const j = Number(item.grid_j);
            return i >= 0 && j >= 0 && i < rows && j < cols;
        });
    }

    function findNeuronAtCell(i, j) {
        const key = neuronKey(i, j);
        return neuronItems.find((item) => neuronKey(Number(item.grid_i), Number(item.grid_j)) === key) || null;
    }

    function findNeuronById(id) {
        return neuronItems.find((item) => Number(item.id) === Number(id)) || null;
    }

    function drawDashedLine(graphics, x1, y1, x2, y2, dash = 6, gap = 4) {
        const dx = x2 - x1;
        const dy = y2 - y1;
        const distance = Math.sqrt((dx * dx) + (dy * dy));
        if (distance === 0) return;

        const ux = dx / distance;
        const uy = dy / distance;
        let drawn = 0;
        while (drawn < distance) {
            const sx = x1 + (ux * drawn);
            const sy = y1 + (uy * drawn);
            const len = Math.min(dash, distance - drawn);
            const ex = sx + (ux * len);
            const ey = sy + (uy * len);
            graphics.moveTo(sx, sy);
            graphics.lineTo(ex, ey);
            drawn += dash + gap;
        }
    }

    function populateLinksTab(neuronId) {
        const neuron = findNeuronById(neuronId);
        if (!neuron) return;

        const container = document.getElementById('neuron-links-container');
        container.innerHTML = '';

        // Use neuron.condition_orders as the primary source
        let conditionsData = neuron.condition_orders || [];
        
        if (conditionsData.length === 0) {
            // Fallback for new neurons if observer hasn't run yet or for some reason it's empty
            const neuronConditions = getOutputConditionsDetailed(neuron);
            conditionsData = neuronConditions.map((c, i) => ({
                condition: c.condition,
                sort_order: i,
                color: getConditionColor(neuron.type, neuron.element_has_rule_chimical_element_id, c.condition),
                rule_chimical_element_detail_id: c.rule_detail_id
            }));
            neuron.condition_orders = conditionsData;
        }

        // Sort by sort_order
        const sortedData = [...conditionsData].sort((a, b) => a.sort_order - b.sort_order);

        for (let i = 0; i < sortedData.length; i++) {
            const condObj = sortedData[i];
            const condition = condObj.condition;
            const color = condObj.color || '#16A34A';
            const link = neuronLinks.find(l => Number(l.from_neuron_id) === Number(neuronId) && l.condition === condition);

            const div = document.createElement('div');
            div.className = 'form-group mb-3 border-bottom pb-2';

            const labelContainer = document.createElement('div');
            labelContainer.className = 'd-flex align-items-center mb-1';
            
            const dot = document.createElement('span');
            dot.style.display = 'inline-block';
            dot.style.width = '12px';
            dot.style.height = '12px';
            dot.style.borderRadius = '50%';
            dot.style.backgroundColor = color;
            dot.style.marginRight = '10px';
            labelContainer.appendChild(dot);

            const label = document.createElement('label');
            label.className = 'mb-0 mr-auto';
            label.style.fontWeight = 'bold';
            label.textContent = condition === portTrigger ? 'Trigger' : (condition === portDetectionSuccess ? 'Success' : (condition === portDetectionFailure ? 'Failure' : condition));
            labelContainer.appendChild(label);

            const btnGroup = document.createElement('div');
            btnGroup.className = 'btn-group ml-2';

            const btnUp = document.createElement('button');
            btnUp.className = 'btn btn-xs btn-outline-secondary btn-move-up';
            btnUp.innerHTML = '<i class="fas fa-arrow-up"></i>';
            btnUp.title = 'Sposta Su';
            btnUp.onclick = (e) => { e.preventDefault(); moveCondition(neuronId, i, -1); };
            if (i === 0) btnUp.disabled = true;

            const btnDown = document.createElement('button');
            btnDown.className = 'btn btn-xs btn-outline-secondary btn-move-down';
            btnDown.innerHTML = '<i class="fas fa-arrow-down"></i>';
            btnDown.title = 'Sposta Giù';
            btnDown.onclick = (e) => { e.preventDefault(); moveCondition(neuronId, i, 1); };
            if (i === conditionsData.length - 1) btnDown.disabled = true;

            btnGroup.appendChild(btnUp);
            btnGroup.appendChild(btnDown);
            labelContainer.appendChild(btnGroup);
            
            div.appendChild(labelContainer);

            const select = document.createElement('select');
            select.className = 'form-control link-target';
            select.setAttribute('data-condition', condition);
            select.setAttribute('data-rule-detail-id', condObj.rule_chimical_element_detail_id || '');
            select.onchange = () => {
                // Sorting enabled for all ports now
            };

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Nessun collegamento --';
            select.appendChild(defaultOption);

            for (let j = 0; j < neuronItems.length; j++) {
                const n = neuronItems[j];
                if (n.id === neuronId) continue;

                const option = document.createElement('option');
                option.value = n.id;
                option.textContent = `#${n.id} (${n.grid_i},${n.grid_j}) - ${typeLabels[n.type] || n.type}`;
                if (link && link.to_neuron_id == n.id) {
                    option.selected = true;
                }
                select.appendChild(option);
            }

            div.appendChild(select);
            container.appendChild(div);
        }

        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.id = 'btn_save_links';
        saveBtn.className = 'btn btn-primary mt-3';
        saveBtn.textContent = 'Salva Collegamenti';
        saveBtn.onclick = function() { saveLinks(neuronId); };
        container.appendChild(saveBtn);
    }

    function moveCondition(neuronId, index, direction) {
        const neuron = findNeuronById(neuronId);
        if (!neuron) return;

        const container = document.getElementById('neuron-links-container');
        const selects = Array.from(container.querySelectorAll('.link-target'));
        const currentData = selects.map((s, i) => {
            const cond = s.dataset.condition;
            const target = s.value;
            const detailId = s.dataset.ruleDetailId;
            const oldOrder = neuron.condition_orders ? neuron.condition_orders.find(o => o.condition === cond) : null;
            return {
                name: cond,
                targetId: target,
                sort_order: i,
                color: oldOrder ? oldOrder.color : null,
                rule_chimical_element_detail_id: detailId
            };
        });

        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= currentData.length) return;

        const temp = currentData[index];
        currentData[index] = currentData[targetIndex];
        currentData[targetIndex] = temp;

        // Re-assign sort_order
        currentData.forEach((d, i) => {
            d.sort_order = i;
        });

        // Update global neuron.condition_orders
        neuron.condition_orders = currentData.map(d => ({
            condition: d.name,
            sort_order: d.sort_order,
            color: d.color,
            rule_chimical_element_detail_id: d.rule_chimical_element_detail_id
        }));

        populateLinksTabWithData(neuronId, currentData);
        renderGrid();
    }

    function populateLinksTabWithData(neuronId, currentData) {
        const neuron = findNeuronById(neuronId);
        const container = document.getElementById('neuron-links-container');
        container.innerHTML = '';

        for (let i = 0; i < currentData.length; i++) {
            const condObj = currentData[i];
            const condition = condObj.name;
            const link = neuronLinks.find(l => Number(l.from_neuron_id) === Number(neuronId) && l.condition === condition);

            const div = document.createElement('div');
            div.className = 'form-group mb-3 border-bottom pb-2';

            const labelContainer = document.createElement('div');
            labelContainer.className = 'd-flex align-items-center mb-1';
            
            const color = condObj.color || getConditionColor(neuron.type, neuron.element_has_rule_chimical_element_id, condition);
            const dot = document.createElement('span');
            dot.style.display = 'inline-block';
            dot.style.width = '12px';
            dot.style.height = '12px';
            dot.style.borderRadius = '50%';
            dot.style.backgroundColor = color;
            dot.style.marginRight = '10px';
            labelContainer.appendChild(dot);

            const label = document.createElement('label');
            label.className = 'mb-0 mr-auto';
            label.style.fontWeight = 'bold';
            label.textContent = condition === portTrigger ? 'Trigger' : (condition === portDetectionSuccess ? 'Success' : (condition === portDetectionFailure ? 'Failure' : condition));
            labelContainer.appendChild(label);

            const btnGroup = document.createElement('div');
            btnGroup.className = 'btn-group ml-2';

            const btnUp = document.createElement('button');
            btnUp.className = 'btn btn-xs btn-outline-secondary btn-move-up';
            btnUp.innerHTML = '<i class="fas fa-arrow-up"></i>';
            btnUp.onclick = (e) => { e.preventDefault(); moveCondition(neuronId, i, -1); };
            if (i === 0) btnUp.disabled = true;

            const btnDown = document.createElement('button');
            btnDown.className = 'btn btn-xs btn-outline-secondary btn-move-down';
            btnDown.innerHTML = '<i class="fas fa-arrow-down"></i>';
            btnDown.onclick = (e) => { e.preventDefault(); moveCondition(neuronId, i, 1); };
            if (i === currentData.length - 1) btnDown.disabled = true;

            btnGroup.appendChild(btnUp);
            btnGroup.appendChild(btnDown);
            labelContainer.appendChild(btnGroup);
            
            div.appendChild(labelContainer);

            const select = document.createElement('select');
            select.className = 'form-control link-target';
            select.setAttribute('data-condition', condition);
            select.setAttribute('data-rule-detail-id', condObj.rule_chimical_element_detail_id || '');
            select.onchange = () => {
                // Sorting enabled for all ports
            };

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Nessun collegamento --';
            select.appendChild(defaultOption);

            for (let j = 0; j < neuronItems.length; j++) {
                const n = neuronItems[j];
                if (n.id === neuronId) continue;
                const option = document.createElement('option');
                option.value = n.id;
                option.textContent = `#${n.id} (${n.grid_i},${n.grid_j}) - ${typeLabels[n.type] || n.type}`;
                if (condObj.targetId == n.id) option.selected = true;
                select.appendChild(option);
            }

            div.appendChild(select);
            container.appendChild(div);
        }

        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.id = 'btn_save_links';
        saveBtn.className = 'btn btn-primary mt-3';
        saveBtn.textContent = 'Salva Collegamenti';
        saveBtn.onclick = function() { saveLinks(neuronId); };
        container.appendChild(saveBtn);
    }

     function getOutputConditionsDetailed(neuron) {
         if (neuron.type === typeDetection) {
             return [
                 { condition: portDetectionSuccess, rule_detail_id: null },
                 { condition: portDetectionFailure, rule_detail_id: null }
             ];
         } else if (neuron.type === typeReadChimicalElement) {
             const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(neuron.element_has_rule_chimical_element_id));
             if (rule && rule.details) {
                 const conditions = rule.details.map(d => ({ condition: `[${d.min}/${d.max}]`, rule_detail_id: d.id }));
                 conditions.push({ condition: DEFAULT_CHIMICAL_ELEMENT, rule_detail_id: null });
                 return conditions;
             }
             return [{ condition: DEFAULT_CHIMICAL_ELEMENT, rule_detail_id: null }];
         } else if (neuron.type === typeMaxValueGene) {
             return [
                 { condition: MAX_VALUE_GENE_YES, rule_detail_id: null },
                 { condition: MAX_VALUE_GENE_NO, rule_detail_id: null }
             ];
         } else {
             return [{ condition: portTrigger, rule_detail_id: null }];
         }
     }

    async function saveLinks(neuronId) {
        const container = document.getElementById('neuron-links-container');
        const selects = Array.from(container.querySelectorAll('.link-target'));
        const saveBtn = document.getElementById('btn_save_links');
        
        const sourceNeuron = neuronItems.find(n => Number(n.id) === Number(neuronId));
        if (!sourceNeuron) return;

        if (saveBtn) saveBtn.disabled = true;

        try {
            const conditionOrders = [];

            for (let i = 0; i < selects.length; i++) {
                const select = selects[i];
                const condition = select.dataset.condition;
                const ruleDetailId = select.dataset.ruleDetailId ? Number(select.dataset.ruleDetailId) : null;
                const targetId = select.value ? Number(select.value) : null;
                const existingLink = neuronLinks.find(l => Number(l.from_neuron_id) === Number(neuronId) && l.condition === condition);
                
                conditionOrders.push({
                    condition: condition,
                    sort_order: i,
                    rule_chimical_element_detail_id: ruleDetailId
                });

                if (targetId) {
                    if (existingLink) {
                        if (Number(existingLink.to_neuron_id) !== targetId) {
                            // Delete old link
                            await requestDeleteNeuronLink({
                                from_neuron_id: Number(neuronId),
                                to_neuron_id: Number(existingLink.to_neuron_id),
                                condition: condition
                            });
                            neuronLinks = neuronLinks.filter(l => l !== existingLink);

                            // Create new link
                            const savedLink = await requestSaveNeuronLink({
                                from_neuron_id: Number(neuronId),
                                to_neuron_id: targetId,
                                condition: condition,
                                color: existingLink.color
                            });
                            if (savedLink) neuronLinks.push(savedLink);
                        }
                    } else {
                         // Create new link
                         let linkColor = null;
                         if (sourceNeuron.type === typeReadChimicalElement) {
                             const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(sourceNeuron.element_has_rule_chimical_element_id));
                             if (rule && rule.details) {
                                 const detail = rule.details.find(d => `[${d.min}/${d.max}]` === condition);
                                 if (detail && detail.color) linkColor = detail.color;
                             }
                             if (condition === DEFAULT_CHIMICAL_ELEMENT) linkColor = '#6b7280';
                         } else if (sourceNeuron.type === typeMaxValueGene) {
                             if (condition === MAX_VALUE_GENE_YES) linkColor = '#16A34A';
                             else if (condition === MAX_VALUE_GENE_NO) linkColor = '#DC2626';
                             else linkColor = '#16A34A';
                         } else if (condition === portDetectionFailure) {
                             linkColor = '#' + portColors[portDetectionFailure].toString(16).padStart(6, '0');
                         } else {
                             linkColor = '#' + portColors[portTrigger].toString(16).padStart(6, '0');
                         }

                        const savedLink = await requestSaveNeuronLink({
                            from_neuron_id: Number(neuronId),
                            to_neuron_id: targetId,
                            condition: condition,
                            color: linkColor
                        });
                        if (savedLink) neuronLinks.push(savedLink);
                    }
                } else {
                    if (existingLink) {
                        await requestDeleteNeuronLink({
                            from_neuron_id: Number(neuronId),
                            to_neuron_id: Number(existingLink.to_neuron_id)
                        });
                        neuronLinks = neuronLinks.filter(l => l !== existingLink);
                    }
                }
            }

            // Save the full order
            await requestSaveNeuronConditionOrders(neuronId, conditionOrders);

            updateNeuronLinksHiddenInput();
            renderGrid();
            renderCircuitsTable();
            $(neuronModalEl).modal('hide');

        } catch (error) {
            alert(error.message || 'Errore durante il salvataggio dei collegamenti');
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }
    }

    async function requestSaveNeuronConditionOrders(neuronId, orders) {
        const response = await fetch(`/elements/${@json($element->id)}/brain/neurons/${neuronId}/condition-orders`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ orders: orders })
        });
        if (!response.ok) throw new Error('Errore salvataggio ordine porte');
        const data = await response.json();
        // Update local neuron state
        const neuron = findNeuronById(neuronId);
        if (neuron && data.orders) {
            neuron.condition_orders = data.orders.map(o => ({
                id: o.id,
                condition: o.condition,
                sort_order: o.sort_order,
                color: o.color,
                rule_chimical_element_detail_id: o.rule_chimical_element_detail_id
            }));
        }
        return data;
    }


    function getRightAnchorPoint(neuron, cellSize, condition) {
        if (!neuron) return { x: 0, y: 0 };
        const topLeftX = (Number(neuron.grid_j) * cellSize);
        const topLeftY = (Number(neuron.grid_i) * cellSize);
        const baseX = topLeftX + cellSize;

        let orders = neuron.condition_orders || [];
        if (orders.length === 0) {
            const conditions = getOutputConditionsDetailed(neuron);
            orders = conditions.map((c, i) => ({ condition: c.condition, sort_order: i }));
        }

        const sortedOrders = [...orders].sort((a, b) => a.sort_order - b.sort_order);
        const index = sortedOrders.findIndex(o => o.condition === condition);
        const count = sortedOrders.length;
        
        if (count > 0 && index !== -1) {
            const step = cellSize / (count + 1);
            return { x: baseX, y: topLeftY + (step * (index + 1)) };
        }

        return { x: baseX, y: topLeftY + (cellSize / 2) };
    }

    function getLeftAnchorPoint(neuron, cellSize) {
        if (!neuron) return { x: 0, y: 0 };
        const topLeftX = (Number(neuron.grid_j) * cellSize);
        const topLeftY = (Number(neuron.grid_i) * cellSize);
        return { x: topLeftX, y: topLeftY + (cellSize / 2) };
    }

     function toggleDetectionFieldsByType() {
         const isDetection = neuronTypeInput.value === typeDetection;
         const isMovement = neuronTypeInput.value === typeMovement;
         const isAttack = neuronTypeInput.value === typeAttack;
         const isReadChimicalElement = neuronTypeInput.value === typeReadChimicalElement;
         const isReadGene = neuronTypeInput.value === typeReadGene;
         const isMaxValueGene = neuronTypeInput.value === typeMaxValueGene;
         const isPath = neuronTypeInput.value === typePath;
         neuronRadiusGroup.style.display = (isDetection || isMovement) ? '' : 'none';
         neuronStopBeforeTargetGroup.style.display = isPath ? '' : 'none';
         neuronTargetTypeGroup.style.display = isDetection ? '' : 'none';
         neuronGeneLifeGroup.style.display = isAttack ? '' : 'none';
         neuronGeneAttackGroup.style.display = isAttack ? '' : 'none';
         neuronElementInfomationGroup.style.display = (isReadGene || isMaxValueGene) ? '' : 'none';
         neuronRuleChimicalElementGroup.style.display = isReadChimicalElement ? '' : 'none';
         neuronChemicalElementGroup.style.display = 'none';
         neuronComplexChemicalElementGroup.style.display = 'none';
         if (!isDetection) {
             neuronTargetElementGroup.style.display = 'none';
             return;
         }
         const targetType = neuronTargetTypeInput.value;
         neuronTargetElementGroup.style.display = targetType === targetTypeElement ? '' : 'none';
         neuronChemicalElementGroup.style.display = targetType === targetTypeChemicalElement ? '' : 'none';
         neuronComplexChemicalElementGroup.style.display = targetType === targetTypeComplexChemicalElement ? '' : 'none';
     }

    function updateGeneInformationTooltip() {
        const selectedOption = neuronElementInfomationIdInput.options[neuronElementInfomationIdInput.selectedIndex];
        const geneName = selectedOption ? selectedOption.text : '';
        neuronElementInfomationIdInput.title = geneName ? `Gene selezionato: ${geneName}` : 'Seleziona un gene';
    }

    function openNeuronModal(i, j) {
        selectedCell = { i, j };
        selectedCellLabel.textContent = `(${i}, ${j})`;

        const existing = findNeuronAtCell(i, j);
        if (existing) {
            currentNeuronId = existing.id;
            const inferredTargetType = existing.target_type
                || (existing.target_element_id != null ? targetTypeElement : null)
                || (existing.chemical_element_id != null ? targetTypeChemicalElement : null)
                || (existing.complex_chemical_element_id != null ? targetTypeComplexChemicalElement : null)
                || targetTypeElement;

            neuronTypeInput.value = existing.type;
            neuronRadiusInput.value = existing.radius != null ? Number(existing.radius) : 1;
            neuronStopBeforeTargetInput.checked = existing.stop_before_target || false;
            neuronGeneLifeIdInput.value = existing.gene_life_id != null ? String(existing.gene_life_id) : '';
            neuronGeneAttackIdInput.value = existing.gene_attack_id != null ? String(existing.gene_attack_id) : '';
            neuronElementInfomationIdInput.value = existing.element_infomation_id != null ? String(existing.element_infomation_id) : '';
            updateGeneInformationTooltip();
            neuronRuleChimicalElementIdInput.value = existing.element_has_rule_chimical_element_id != null ? String(existing.element_has_rule_chimical_element_id) : '';
            neuronChemicalElementIdInput.value = existing.chemical_element_id != null ? String(existing.chemical_element_id) : '';
            neuronComplexChemicalElementIdInput.value = existing.complex_chemical_element_id != null ? String(existing.complex_chemical_element_id) : '';
            deleteNeuronBtn.style.display = '';

            toggleDetectionFieldsByType(); // Update visibility based on loaded values
            updateGeneInformationTooltip();

            // Populate links tab
            populateLinksTab(existing.id);
        } else {
            currentNeuronId = null;
            neuronTypeInput.value = typeDetection;
            neuronRadiusInput.value = 1;
            neuronStopBeforeTargetInput.checked = false;
            neuronTargetTypeInput.value = targetTypeElement;
            neuronTargetElementIdInput.value = '';
            neuronChemicalElementIdInput.value = '';
            neuronComplexChemicalElementIdInput.value = '';
            neuronGeneLifeIdInput.value = '';
            neuronGeneAttackIdInput.value = '';
            neuronElementInfomationIdInput.value = '';
            neuronRuleChimicalElementIdInput.value = '';
            deleteNeuronBtn.style.display = 'none';

            // Clear links tab
            document.getElementById('neuron-links-container').innerHTML = '';
        }

        toggleDetectionFieldsByType();
        updateGeneInformationTooltip();
        $(neuronModalEl).modal('show');

        // Set the target type and specific inputs after the modal is fully shown
        $(neuronModalEl).one('shown.bs.modal', function() {
            if (currentNeuronId) {
                const existing = findNeuronById(currentNeuronId);
                if (existing) {
                    const inferredTargetType = existing.target_type
                        || (existing.target_element_id != null ? targetTypeElement : null)
                        || (existing.chemical_element_id != null ? targetTypeChemicalElement : null)
                        || (existing.complex_chemical_element_id != null ? targetTypeComplexChemicalElement : null)
                        || targetTypeElement;
                    
                    neuronTargetTypeInput.value = inferredTargetType;
                    toggleDetectionFieldsByType(); 

                    if (inferredTargetType === targetTypeElement) {
                        neuronTargetElementIdInput.value = existing.target_element_id != null ? String(existing.target_element_id) : '';
                    } else if (inferredTargetType === targetTypeChemicalElement) {
                        neuronChemicalElementIdInput.value = existing.chemical_element_id != null ? String(existing.chemical_element_id) : '';
                    } else if (inferredTargetType === targetTypeComplexChemicalElement) {
                        neuronComplexChemicalElementIdInput.value = existing.complex_chemical_element_id != null ? String(existing.complex_chemical_element_id) : '';
                    }
                }
            }
        });
    }

    function drawNeuronLinks(layer, cellSize) {
        for (const link of neuronLinks) {
            const fromN = findNeuronById(link.from_neuron_id);
            const toN = findNeuronById(link.to_neuron_id);
            if (!fromN || !toN) continue;

            const linkCondition = link.condition;
            const fromPoint = getRightAnchorPoint(fromN, cellSize, linkCondition);
            const toPoint = getLeftAnchorPoint(toN, cellSize);

            // Get color from fromN.condition_orders
            const orderObj = fromN.condition_orders ? fromN.condition_orders.find(o => o.condition === linkCondition) : null;
            const linkColorStr = (orderObj && orderObj.color) ? orderObj.color : getConditionColor(fromN.type, fromN.element_has_rule_chimical_element_id, linkCondition);
            let lineColor = linkColorStr.startsWith('#') ? parseInt(linkColorStr.replace('#', '0x'), 16) : Number(linkColorStr);
            
            // If condition is 'default_chimical_element', use gray
            if (linkCondition === 'default_chimical_element') {
                lineColor = 0x6b7280; // Gray
            }

            const line = new PIXI.Graphics();
            line.lineStyle(3, lineColor, 1);
            line.moveTo(fromPoint.x, fromPoint.y);
            line.lineTo(toPoint.x, toPoint.y);
            layer.addChild(line);

            const mx = (fromPoint.x + toPoint.x) / 2;
            const my = (fromPoint.y + toPoint.y) / 2;

            const deleteBtn = new PIXI.Graphics();
            deleteBtn.beginFill(0xdc3545);
            deleteBtn.lineStyle(2, 0xffffff, 1);
            deleteBtn.drawCircle(0, 0, 9);
            deleteBtn.endFill();

            // Draw white X
            deleteBtn.moveTo(-4, -4);
            deleteBtn.lineTo(4, 4);
            deleteBtn.moveTo(4, -4);
            deleteBtn.lineTo(-4, 4);

            deleteBtn.x = mx;
            deleteBtn.y = my;
            deleteBtn.eventMode = 'static';
            deleteBtn.cursor = 'pointer';
            deleteBtn.isInteractiveElement = true;

            deleteBtn.on('pointerdown', async (e) => {
                e.stopPropagation();
                if (!confirm('Vuoi eliminare questo collegamento?')) return;
                try {
                    await requestDeleteNeuronLink({
                        from_neuron_id: Number(link.from_neuron_id),
                        to_neuron_id: Number(link.to_neuron_id),
                        condition: linkCondition
                    });
                    neuronLinks = neuronLinks.filter((l) => !(Number(l.from_neuron_id) === Number(link.from_neuron_id) && Number(l.to_neuron_id) === Number(link.to_neuron_id) && l.condition === linkCondition));
                    updateNeuronLinksHiddenInput();
                    renderGrid();
                    renderCircuitsTable();
                } catch (error) {
                    alert(error.message || 'Errore durante la rimozione del collegamento');
                }
            });
            layer.addChild(deleteBtn);
        }
    }

    function drawNeuronSymbols(layer, cellSize) {
        for (const neuron of neuronItems) {
            const topLeftX = (Number(neuron.grid_j) * cellSize);
            const topLeftY = (Number(neuron.grid_i) * cellSize);

            const i = Number(neuron.grid_i);
            const j = Number(neuron.grid_j);

            const symbol = typeSymbols[neuron.type] || '?';

            // Circuit borders
            const belongsToCircuits = neuronCircuits.filter(c => c.neuron_ids && c.neuron_ids.includes(Number(neuron.id)));
            belongsToCircuits.forEach((circuit, index) => {
                const cColor = circuit.color ? parseInt(circuit.color.replace('#', '0x'), 16) : 0xcccccc;
                const offset = 3 + (index * 4);
                const cBorder = new PIXI.Graphics();
                cBorder.lineStyle(2, cColor, 0.8);
                cBorder.drawRect((j * cellSize) + 1 - offset, (i * cellSize) + 1 - offset, cellSize - 2 + (offset * 2), cellSize - 2 + (offset * 2));
                layer.addChild(cBorder);
            });

            // Neuron cell border (clickable and draggable)
            const isInactive = belongsToCircuits.length > 0 && belongsToCircuits.every(c => !c.active);
            const bgColor = isInactive ? 0xd1d5db : 0xFFFFFF;

            const isHighlighted = highlightedCircuitId && belongsToCircuits.some(c => c.id === highlightedCircuitId);

            const neuronBorder = new PIXI.Graphics();
            if (isHighlighted) {
                neuronBorder.lineStyle(6, 0x3b82f6, 1); // Thicker blue border for better visibility
            } else {
                neuronBorder.lineStyle(2, 0x111827, 1);
            }
            neuronBorder.beginFill(bgColor, 1);
            neuronBorder.drawRect((j * cellSize) + 1, (i * cellSize) + 1, cellSize - 2, cellSize - 2);
            neuronBorder.endFill();
            neuronBorder.eventMode = 'static';
            neuronBorder.cursor = 'grab';
            neuronBorder.isInteractiveElement = true;

            neuronBorder.on('pointerdown', (e) => {
                e.stopPropagation();
                if (e.button === 0) {
                    draggedNeuron = neuronBorder;
                    draggedNeuronLayer = layer;
                    draggedNeuronOriginalI = i;
                    draggedNeuronOriginalJ = j;
                    const rect = app.view.getBoundingClientRect();
                    draggedOffsetX = e.global.x - ((j * cellSize) + 1);
                    draggedOffsetY = e.global.y - ((i * cellSize) + 1);
                    dragStarted = false;
                    neuronBorder.cursor = 'grabbing';
                    neuronBorder.alpha = 0.7;
                }
            });
            // Tooltip
            neuronBorder.on('pointerover', () => {
                if (!tooltipText || !tooltipBg) return;
                tooltipText.text = neuron.tooltip || 'Neurone';
                tooltipText.visible = true;
                tooltipBg.visible = true;
                const paddingX = 6, paddingY = 4;
                tooltipBg.clear();
                tooltipBg.lineStyle(1, 0x000000, 1);
                tooltipBg.beginFill(0xFFFFFF, 1);
                tooltipBg.drawRect(0, 0, tooltipText.width + paddingX*2, tooltipText.height + paddingY*2);
                tooltipBg.endFill();
                tooltipBg.x = tooltipText.x - paddingX;
                tooltipBg.y = tooltipText.y - paddingY;
            });
            neuronBorder.on('pointerout', () => {
                if (tooltipText) tooltipText.visible = false;
                if (tooltipBg) tooltipBg.visible = false;
            });
            neuronBorder.on('pointermove', (e) => {
                if (!tooltipText || !tooltipText.visible || !tooltipBg) return;
                const paddingX = 6, paddingY = 4;
                const offsetX = 12, offsetY = 12;
                const rect = app && app.view ? app.view.getBoundingClientRect() : null;
                const maxX = rect ? rect.width : (app ? app.renderer.width : Infinity);
                const maxY = rect ? rect.height : (app ? app.renderer.height : Infinity);
                const tooltipW = tooltipText.width + paddingX*2;
                const tooltipH = tooltipText.height + paddingY*2;
                let x = e.global.x + offsetX;
                let y = e.global.y + offsetY;
                if (x + tooltipW > maxX) x = Math.max(0, maxX - tooltipW);
                if (y + tooltipH > maxY) y = Math.max(0, maxY - tooltipH);
                tooltipText.x = x;
                tooltipText.y = y;
                tooltipBg.x = tooltipText.x - paddingX;
                tooltipBg.y = tooltipText.y - paddingY;
            });

            layer.addChild(neuronBorder);

            // Neuron symbol text
            const text = new PIXI.Text(symbol, {
                fill: 0x1f2937,
                fontSize: Math.max(16, Math.floor(cellSize * 0.55)),
                fontWeight: 'bold',
                fontFamily: 'Consolas',
                align: 'center',
            });
            text.eventMode = 'none';
            text.x = (j * cellSize) + (cellSize / 2) - (text.width / 2);
            text.y = (i * cellSize) + (cellSize / 2) - (text.height / 2);
            layer.addChild(text);

            // Start circuit badge
            if (neuron.type === typeStart) {
                const startCircuit = neuronCircuits.find(c => Number(c.start_neuron_id) === Number(neuron.id));
                if (startCircuit) {
                    const badge = new PIXI.Graphics();
                    const bColor = startCircuit.state === 'closed' ? 0x10b981 : 0xf59e0b;
                    badge.beginFill(bColor);
                    badge.lineStyle(1, 0xffffff, 1);
                    badge.drawCircle((j * cellSize) + 8, (i * cellSize) + 8, 5);
                    badge.endFill();
                    layer.addChild(badge);
                }
            }

            // Anchors (static visualization)
            // Draw left input anchor (single point) for neurons that receive connections
            const needsLeftAnchor = neuron.type === typeDetection || neuron.type === typePath || neuron.type === typeAttack || neuron.type === typeMovement || neuron.type === typeEnd || neuron.type === typeReadChimicalElement || neuron.type === typeReadGene || neuron.type === typeMaxValueGene;
            if (needsLeftAnchor) {
                const leftAnchorPoint = getLeftAnchorPoint(neuron, cellSize);
                const leftAnchor = new PIXI.Graphics();
                leftAnchor.beginFill(0x16a34a); // Green color for all left anchors
                leftAnchor.lineStyle(2, 0xffffff, 1);
                leftAnchor.drawCircle(leftAnchorPoint.x, leftAnchorPoint.y, 8);
                leftAnchor.endFill();
                leftAnchor.eventMode = 'none';
                layer.addChild(leftAnchor);
            }

            // Draw right output anchors from condition_orders
            const hasRightAnchor = neuron.type === typeDetection || neuron.type === typePath || neuron.type === typeStart || neuron.type === typeAttack || neuron.type === typeMovement || neuron.type === typeReadChimicalElement || neuron.type === typeReadGene || neuron.type === typeMaxValueGene;




            if (hasRightAnchor) {
                let orders = neuron.condition_orders || [];
                if (orders.length === 0) {
                    const conditions = getOutputConditionsDetailed(neuron);
                    orders = conditions.map((c, idx) => ({
                        condition: c.condition,
                        sort_order: idx,
                        color: getConditionColor(neuron.type, neuron.element_has_rule_chimical_element_id, c.condition),
                        rule_chimical_element_detail_id: c.rule_detail_id
                    }));
                }

                const sortedOrders = [...orders].sort((a, b) => a.sort_order - b.sort_order);

                for (const orderObj of sortedOrders) {
                    const cond = orderObj.condition;
                    const anchor = getRightAnchorPoint(neuron, cellSize, cond);

                    const colorStr = (orderObj && orderObj.color) ? orderObj.color : getConditionColor(neuron.type, neuron.element_has_rule_chimical_element_id, cond);
                    let colorInt;
                    if (typeof colorStr === 'string' && colorStr.startsWith('#')) {
                        colorInt = parseInt(colorStr.replace('#', '0x'), 16);
                    } else if (typeof colorStr === 'number') {
                        colorInt = colorStr;
                    } else {
                        colorInt = 0x000000;
                    }

                    const dynamicRadius = neuron.type === typeReadChimicalElement ? 10 : 8;
                    const xOffset = neuron.type === typeReadChimicalElement ? 2 : 0;

                    const a = new PIXI.Graphics();
                    a.beginFill(colorInt);
                    a.lineStyle(neuron.type === typeReadChimicalElement ? 1 : 2, 0xffffff, 1);
                    a.drawCircle(anchor.x + xOffset, anchor.y, dynamicRadius);
                    a.endFill();
                    a.eventMode = 'none';
                    layer.addChild(a);
                }
            }
        }
    }

    function renderGrid() {
        const cols = normalize(widthInput.value || 5);
        const rows = normalize(heightInput.value || 5);
        const cellSize = fixedCellSize;
        const canvasWidth = cols * cellSize;
        const canvasHeight = rows * cellSize;
        filterOutOfBoundsNeurons(rows, cols);
        updateNeuronHiddenInput();

        if (!app) {
            app = new PIXI.Application({
                width: canvasWidth,
                height: canvasHeight,
                antialias: true,
                backgroundAlpha: 1,
                backgroundColor: 0xffffff
            });
            container.innerHTML = '';
            container.appendChild(app.view);
            
            app.stage.eventMode = 'static';
            app.stage.hitArea = new PIXI.Rectangle(0, 0, canvasWidth, canvasHeight);

            app.stage.on('pointerdown', (event) => {
                if (draggedNeuron) return;
                const i = Math.floor(event.global.y / fixedCellSize);
                const j = Math.floor(event.global.x / fixedCellSize);
                const maxRows = normalize(heightInput.value || 5);
                const maxCols = normalize(widthInput.value || 5);
                if (i < 0 || j < 0 || i >= maxRows || j >= maxCols) return;
                openNeuronModal(i, j);
            });

            // Native browser events for reliable drag
            app.view.addEventListener('pointermove', (e) => {
                if (!draggedNeuron) return;
                const rect = app.view.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const dx = Math.abs(x - draggedOffsetX - ((draggedNeuronOriginalJ * fixedCellSize) + 1));
                const dy = Math.abs(y - draggedOffsetY - ((draggedNeuronOriginalI * fixedCellSize) + 1));
                if (dx > 3 || dy > 3) dragStarted = true;
                if (!dragStarted) return;
                draggedNeuron.x = x - draggedOffsetX - ((draggedNeuronOriginalJ * fixedCellSize) + 1);
                draggedNeuron.y = y - draggedOffsetY - ((draggedNeuronOriginalI * fixedCellSize) + 1);
                if (draggedNeuronLayer) draggedNeuronLayer.sortChildren();
            });

            app.view.addEventListener('pointerup', async (e) => {
                if (!draggedNeuron) return;
                draggedNeuron.cursor = 'grab';
                draggedNeuron.alpha = 1;
                if (!dragStarted) {
                    const tempNeuron = draggedNeuron;
                    const tempI = draggedNeuronOriginalI;
                    const tempJ = draggedNeuronOriginalJ;
                    draggedNeuron = null;
                    draggedNeuronLayer = null;
                    openNeuronModal(tempI, tempJ);
                    return;
                }
                const neuron = findNeuronAtCell(draggedNeuronOriginalI, draggedNeuronOriginalJ);
                if (neuron) {
                    const newJ = Math.floor((draggedNeuron.x + (draggedNeuronOriginalJ * fixedCellSize) + 1 + (fixedCellSize / 2)) / fixedCellSize);
                    const newI = Math.floor((draggedNeuron.y + (draggedNeuronOriginalI * fixedCellSize) + 1 + (fixedCellSize / 2)) / fixedCellSize);
                    const maxRows = normalize(heightInput.value || 5);
                    const maxCols = normalize(widthInput.value || 5);
                    const clampedI = Math.max(0, Math.min(newI, maxRows - 1));
                    const clampedJ = Math.max(0, Math.min(newJ, maxCols - 1));
                    if (clampedI !== draggedNeuronOriginalI || clampedJ !== draggedNeuronOriginalJ) {
                        const targetOccupied = findNeuronAtCell(clampedI, clampedJ);
                        if (!targetOccupied || Number(targetOccupied.id) === Number(neuron.id)) {
                            try {
                                const updatedNeuron = await requestMoveNeuron(neuron.id, clampedI, clampedJ);
                                neuronItems = neuronItems.filter(item => Number(item.id) !== Number(neuron.id));
                                neuronItems.push(updatedNeuron);
                                updateNeuronHiddenInput();
                            } catch (error) {
                                alert(error.message || 'Errore durante lo spostamento del neurone');
                            }
                        } else {
                            alert('La cella di destinazione è già occupata');
                        }
                    }
                }
                draggedNeuron = null;
                draggedNeuronLayer = null;
                dragStarted = false;
                renderGrid();
                renderCircuitsTable();
            });

        } else {
            app.renderer.resize(canvasWidth, canvasHeight);
            app.stage.removeChildren();
        }

        // Draw background rectangle to ensure it's not transparent
        const bg = new PIXI.Graphics();
        bg.beginFill(0xFFFFFF);
        bg.drawRect(0, 0, canvasWidth, canvasHeight);
        bg.endFill();
        app.stage.addChild(bg);

        const lines = new PIXI.Graphics();
        lines.lineStyle(1, 0xdddddd, 1);
        for (let c = 0; c <= cols; c++) {
            const x = c * cellSize;
            drawDashedLine(lines, x, 0, x, canvasHeight);
        }
        for (let r = 0; r <= rows; r++) {
            const y = r * cellSize;
            drawDashedLine(lines, 0, y, canvasWidth, y);
        }
        app.stage.addChild(lines);

        const linksLayer = new PIXI.Container();
        app.stage.addChild(linksLayer);
        drawNeuronLinks(linksLayer, cellSize);

        const symbolsLayer = new PIXI.Container();
        app.stage.addChild(symbolsLayer);
        drawNeuronSymbols(symbolsLayer, cellSize);

        tooltipBg = new PIXI.Graphics();
        tooltipBg.visible = false;
        tooltipBg.zIndex = 19999;
        app.stage.addChild(tooltipBg);

        tooltipText = new PIXI.Text('', {
            fill: 0x000000,
            fontSize: 12,
            fontWeight: 'bold',
            fontFamily: 'Consolas',
            align: 'left',
        });
        tooltipText.visible = false;
        tooltipText.zIndex = 20000;
        app.stage.addChild(tooltipText);
        app.stage.sortChildren();
    }

    async function requestSaveNeuron(payload) {
        const response = await fetch(saveNeuronUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante il salvataggio neurone');
        if (data.circuits) neuronCircuits = data.circuits;
        return data.neuron;
    }

    async function requestDeleteNeuron(payload) {
        const response = await fetch(deleteNeuronUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante la rimozione neurone');
        if (data.circuits) neuronCircuits = data.circuits;
    }

    async function requestMoveNeuron(neuronId, gridI, gridJ) {
        const url = moveNeuronUrl.replace(':neuron', neuronId);
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                grid_i: gridI,
                grid_j: gridJ,
            }),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante lo spostamento neurone');
        if (data.circuits) neuronCircuits = data.circuits;
        return data.neuron;
    }

    async function requestSaveNeuronLink(payload) {
        const response = await fetch(saveNeuronLinkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante il salvataggio collegamento');
        if (data.circuits) neuronCircuits = data.circuits;
        return data.link;
    }

    async function requestDeleteNeuronLink(payload) {
        const response = await fetch(deleteNeuronLinkUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante la rimozione collegamento');
        if (data.circuits) neuronCircuits = data.circuits;
    }

    widthInput.addEventListener('input', renderGrid);
    heightInput.addEventListener('input', renderGrid);
    neuronTypeInput.addEventListener('change', toggleDetectionFieldsByType);
    neuronTargetTypeInput.addEventListener('change', toggleDetectionFieldsByType);
    neuronElementInfomationIdInput.addEventListener('change', updateGeneInformationTooltip);

    saveNeuronBtn.addEventListener('click', async function () {
        if (!selectedCell) return;

        const type = neuronTypeInput.value;
        const i = Number(selectedCell.i);
        const j = Number(selectedCell.j);
        let radius = null;
        let targetType = null;
        let targetElementId = null;
        let chemicalElementId = null;
        let complexChemicalElementId = null;
        let geneLifeId = null;
        let geneAttackId = null;
        let elementInfomationId = null;
        let elementHasRuleChimicalElementId = null;
        if (type === typeDetection) {
            radius = Math.max(1, normalize(neuronRadiusInput.value || 1));
            targetType = neuronTargetTypeInput.value;
            if (targetType === targetTypeElement) {
                const parsed = parseInt(neuronTargetElementIdInput.value, 10);
                targetElementId = Number.isNaN(parsed) ? null : parsed;
            } else if (targetType === targetTypeChemicalElement) {
                const parsed = parseInt(neuronChemicalElementIdInput.value, 10);
                chemicalElementId = Number.isNaN(parsed) ? null : parsed;
            } else if (targetType === targetTypeComplexChemicalElement) {
                const parsed = parseInt(neuronComplexChemicalElementIdInput.value, 10);
                complexChemicalElementId = Number.isNaN(parsed) ? null : parsed;
            }
        } else if (type === typeMovement) {
            radius = Math.max(1, normalize(neuronRadiusInput.value || 1));
        } else if (type === typeAttack) {
            const parsedLife = parseInt(neuronGeneLifeIdInput.value, 10);
            const parsedAttack = parseInt(neuronGeneAttackIdInput.value, 10);
            geneLifeId = Number.isNaN(parsedLife) ? null : parsedLife;
            geneAttackId = Number.isNaN(parsedAttack) ? null : parsedAttack;
            if (geneLifeId == null || geneAttackId == null) {
                alert('Per il neurone Attacco devi selezionare Gene Vita e Gene Attacco');
                return;
            }
         } else if (type === typeReadGene) {
             const parsedInfo = parseInt(neuronElementInfomationIdInput.value, 10);
             elementInfomationId = Number.isNaN(parsedInfo) ? null : parsedInfo;
             if (elementInfomationId == null) {
                 alert('Per il neurone Lettura Gene devi selezionare un Gene');
                 return;
             }
         } else if (type === typeMaxValueGene) {
             const parsedInfo = parseInt(neuronElementInfomationIdInput.value, 10);
             elementInfomationId = Number.isNaN(parsedInfo) ? null : parsedInfo;
             if (elementInfomationId == null) {
                 alert('Per il neurone Valore Massimo Gene devi selezionare un Gene');
                 return;
             }
         } else if (type === typeReadChimicalElement) {
            const parsedRule = parseInt(neuronRuleChimicalElementIdInput.value, 10);
            elementHasRuleChimicalElementId = Number.isNaN(parsedRule) ? null : parsedRule;
            if (elementHasRuleChimicalElementId == null) {
                alert('Per il neurone Lettura Elemento Chimico devi selezionare una Regola');
                return;
            }
        }

        saveNeuronBtn.disabled = true;
        try {
            const savedNeuron = await requestSaveNeuron({
                id: currentNeuronId,
                brain_grid_width: normalize(widthInput.value || 5),
                brain_grid_height: normalize(heightInput.value || 5),
                type: type,
                grid_i: i,
                grid_j: j,
                radius: radius,
                stop_before_target: neuronStopBeforeTargetInput.checked,
                target_type: targetType,
                target_element_id: targetElementId,
                chemical_element_id: chemicalElementId,
                complex_chemical_element_id: complexChemicalElementId,
                gene_life_id: geneLifeId,
                gene_attack_id: geneAttackId,
                element_infomation_id: elementInfomationId,
                element_has_rule_chimical_element_id: elementHasRuleChimicalElementId,
            });
            neuronItems = neuronItems.filter((item) => !(Number(item.grid_i) === i && Number(item.grid_j) === j));
            neuronItems.push(savedNeuron);
            updateNeuronHiddenInput();
            renderGrid();
            $(neuronModalEl).modal('hide');
        } catch (error) {
            alert(error.message || 'Errore durante il salvataggio neurone');
        } finally {
            saveNeuronBtn.disabled = false;
        }
    });

    deleteNeuronBtn.addEventListener('click', async function () {
        if (!selectedCell) return;
        const i = Number(selectedCell.i);
        const j = Number(selectedCell.j);
        const neuron = findNeuronAtCell(i, j);

        deleteNeuronBtn.disabled = true;
        try {
            await requestDeleteNeuron({ grid_i: i, grid_j: j });
            neuronItems = neuronItems.filter((item) => !(Number(item.grid_i) === i && Number(item.grid_j) === j));
            if (neuron) {
                neuronLinks = neuronLinks.filter((l) => Number(l.from_neuron_id) !== Number(neuron.id) && Number(l.to_neuron_id) !== Number(neuron.id));
            }
            updateNeuronHiddenInput();
            updateNeuronLinksHiddenInput();
            renderGrid();
            $(neuronModalEl).modal('hide');
        } catch (error) {
            alert(error.message || 'Errore durante la rimozione neurone');
        } finally {
            deleteNeuronBtn.disabled = false;
        }
    });

    const mainForm = container.closest('form');
    if (mainForm) {
        mainForm.addEventListener('submit', function () {
            updateNeuronHiddenInput();
            updateNeuronLinksHiddenInput();
        });
    }

    toggleDetectionFieldsByType();
    updateNeuronLinksHiddenInput();
    renderGrid();
    renderCircuitsTable();

    // Update guide carousel counter
    const guideCarousel = document.getElementById('brainGuideCarousel');
    if (guideCarousel) {
        guideCarousel.addEventListener('slid.bs.carousel', function (event) {
            const totalSlides = 10;
            const currentIndex = Array.from(guideCarousel.querySelectorAll('.carousel-item')).findIndex(item => item.classList.contains('active'));
            document.getElementById('currentSlideNumber').textContent = `${currentIndex + 1} / ${totalSlides}`;
        });
    }
});
</script>
@endpush

@push('css')
<style>
    #circuits-table-body tr {
        cursor: pointer;
    }
    #circuits-table-body tr:hover td {
        background-color: #f2f2f2 !important;
    }
</style>
@endpush
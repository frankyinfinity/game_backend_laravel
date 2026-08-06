@extends('adminlte::page')

@section('title', 'Console - Test')

@section('content_header')
<h1>Console - Test</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-terminal"></i> Console Collegata a GCloud
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="btn-clear"><i class="fas fa-eraser"></i> Pulisci</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div id="terminal" class="terminal">
            <div id="terminal-output"></div>
            <div class="terminal-input-line">
                <span class="terminal-prompt">$</span>
                <input type="text" id="terminal-input" class="terminal-input" autocomplete="off" spellcheck="false" placeholder="Inserisci un comando...">
            </div>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i>
            Comandi eseguiti su: <code>gcloud compute ssh --zone "europe-west12-c" "instance-game" --project "game-500515"</code>
        </small>
    </div>
</div>
@stop

@section('css')
<style>
    .terminal {
        background: #1e1e1e;
        color: #d4d4d4;
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px;
        height: 70vh;
        overflow-y: auto;
        padding: 15px;
        border-radius: 0;
    }

    #terminal-output {
        white-space: pre-wrap;
        word-wrap: break-word;
        margin-bottom: 10px;
    }

    .terminal-line {
        margin-bottom: 2px;
    }

    .terminal-line.command {
        color: #569cd6;
    }

    .terminal-line.error {
        color: #f48771;
    }

    .terminal-line.success {
        color: #6a9955;
    }

    .terminal-input-line {
        display: flex;
        align-items: center;
    }

    .terminal-prompt {
        color: #6a9955;
        margin-right: 8px;
        font-weight: bold;
    }

    .terminal-input {
        background: transparent;
        border: none;
        color: #d4d4d4;
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px;
        flex: 1;
        outline: none;
        padding: 0;
    }

    .terminal-input::placeholder {
        color: #6a9955;
        opacity: 0.5;
    }

    .terminal-loading {
        color: #dcdcaa;
        font-style: italic;
    }
</style>
@stop

@section('js')
<script>
    $(document).ready(function () {
        const terminalOutput = $('#terminal-output');
        const terminalInput = $('#terminal-input');
        const terminal = $('#terminal');

        function appendLine(text, className) {
            const line = $('<div>').addClass('terminal-line').addClass(className || '').text(text);
            terminalOutput.append(line);
            terminal.scrollTop(terminal[0].scrollHeight);
        }

        function appendCommand(command) {
            appendLine('$ ' + command, 'command');
        }

        function executeCommand(command) {
            if (!command.trim()) return;

            appendCommand(command);
            terminalInput.val('');
            terminalInput.prop('disabled', true);

            const loadingLine = $('<div>').addClass('terminal-line terminal-loading').text('Esecuzione in corso...');
            terminalOutput.append(loadingLine);
            terminal.scrollTop(terminal[0].scrollHeight);

            $.ajax({
                url: '{{ route('test.console.exec') }}',
                type: 'POST',
                data: {
                    command: command,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    loadingLine.remove();
                    if (response.output) {
                        appendLine(response.output, response.success ? 'success' : 'error');
                    }
                    if (!response.success) {
                        appendLine('Exit code: ' + (response.exit_code !== undefined ? response.exit_code : 'N/A'), 'error');
                    }
                },
                error: function (xhr) {
                    loadingLine.remove();
                    appendLine('Errore di comunicazione con il server.', 'error');
                },
                complete: function () {
                    terminalInput.prop('disabled', false);
                    terminalInput.focus();
                }
            });
        }

        terminalInput.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                executeCommand($(this).val());
            }
        });

        $('#btn-clear').on('click', function () {
            terminalOutput.empty();
            terminalInput.focus();
        });

        // Focus input on terminal click
        terminal.on('click', function () {
            terminalInput.focus();
        });

        // Initial message
        appendLine('Console collegata a GCloud Compute Engine', 'success');
        appendLine('Zona: europe-west12-c | Istanza: instance-game | Progetto: game-500515', '');
        appendLine('Digita un comando e premi Invio per eseguirlo sulla macchina remota.', '');
        appendLine('', '');
        terminalInput.focus();
    });
</script>
@stop
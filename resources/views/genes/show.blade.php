@extends('adminlte::page')

@section('title', 'Dettaglio Gene')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Dettaglio Gene</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%;">ID</th>
                        <td>{{ $gene->id }}</td>
                    </tr>
                    <tr>
                        <th>Nome</th>
                        <td>{{ $gene->name }}</td>
                    </tr>
                    <tr>
                        <th>Key</th>
                        <td><code>{{ $gene->key }}</code></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <a href="{{ route('genes.index') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Torna alla lista
                </a>
            </div>
        </div>
    </div>
</div>
@stop
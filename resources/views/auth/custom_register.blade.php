@extends('adminlte::master')

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('classes_body', 'layout-top-nav')

@section('body')
    <div class="wrapper">
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-12 text-center">
                            <h1 class="m-0">Registrazione</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Modulo Custom</h3>
                            <div class="card-tools">
                                <a href="javascript:history.back()" class="btn btn-tool">
                                    <i class="fas fa-arrow-left"></i> Indietro
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-5">
                                <p class="text-muted">Card a tutta larghezza, senza menu laterale e con pulsante indietro interno.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop

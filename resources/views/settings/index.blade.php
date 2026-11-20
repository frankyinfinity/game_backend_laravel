@extends('adminlte::page')

@section('title', 'Impostazioni')

@section('content_header')
<h1>Impostazioni</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog"></i> Impostazioni Applicazione</h3>
    </div>
    <form action="{{ route('settings.update') }}" method="POST" class="mb-0">
        @csrf
        @method('PUT')
        <div class="card-body">
            @forelse($settings as $setting)
            <div class="form-group">
                <label for="setting_{{ $setting->id }}">{{ $setting->label }}</label>
                @if(in_array($setting->key, \App\Models\Setting::SCORE_SELECT_KEYS))
                <select id="setting_{{ $setting->id }}" name="settings[{{ $setting->id }}]" class="form-control">
                    <option value="">-- Seleziona Score --</option>
                    @foreach($scores as $score)
                    <option value="{{ $score->id }}" {{ (string) old('settings.' . $setting->id, $setting->value) === (string) $score->id ? 'selected' : '' }}>
                        @if($score->groupScore)[{{ $score->groupScore->name }}] @endif{{ $score->name }}
                    </option>
                    @endforeach
                </select>
                @elseif(in_array($setting->key, \App\Models\Setting::NUMERIC_KEYS))
                <input type="number" id="setting_{{ $setting->id }}" name="settings[{{ $setting->id }}]"
                       class="form-control" step="1" value="{{ old('settings.' . $setting->id, $setting->value) }}">
                @else
                <input type="text" id="setting_{{ $setting->id }}" name="settings[{{ $setting->id }}]"
                       class="form-control" value="{{ old('settings.' . $setting->id, $setting->value) }}">
                @endif
                <small class="form-text text-muted">
                    <code>{{ $setting->key }}</code>
                    @if($setting->description)
                        &mdash; {{ $setting->description }}
                    @endif
                </small>
            </div>
            @empty
            <p class="text-muted text-center mb-0">Nessuna impostazione trovata.</p>
            @endforelse
        </div>
        @if($settings->isNotEmpty())
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Salva Tutto</button>
        </div>
        @endif
    </form>
</div>
@stop

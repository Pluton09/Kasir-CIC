@extends('layouts.app')

@section('title', 'Masuk Kasir')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="card">
        @csrf

        <h1>Masuk Kasir</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <div class="field">
            <label for="email">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
            >
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </div>

        <button type="submit" class="pri">Masuk</button>
    </form>
@endsection

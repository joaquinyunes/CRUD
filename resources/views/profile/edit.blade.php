@extends('layouts.app')

@section('page_title', 'Mi perfil')

@section('content')
<div class="r-head">
    <h2 class="r-display-l">Mi perfil</h2>
</div>

<div class="r-stack-lg" style="max-width: 680px;">
    <div class="r-card-flat">
        @include('profile.partials.update-profile-information-form')
    </div>

    <div class="r-card-flat">
        @include('profile.partials.update-password-form')
    </div>

    <div class="r-card-flat">
        @include('profile.partials.delete-user-form')
    </div>
</div>
@endsection

@extends('mailmerge::layouts.app', ['model' => 'email', 'action' => 'create'])
@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{route('email-templates.index')}}">email</a>
</li>
<li>
    Create
</li>
@endsection
@section('content')
<div class="row">
    <div class='col-md-12'>
        <div class='card bg-white'>
            <div class="card-body">
                @include('forms.email', ['sessions' => $sessions])
            </div>
        </div>
    </div>
</div>
@endSection

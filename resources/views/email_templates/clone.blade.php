@extends(config('mailmerge.blade_layout'), ['model' => 'email', 'action' => 'clone'])
@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{route('email-templates.index')}}">email</a>
</li>
<li>
    Clone
</li>
@endsection
@section('content')
<div class="row">
    <div class='col-md-12'>
        <div class='card bg-white'>
            <div class="card-body">
                @include('mailmerge::forms.email', ['route'=> route('email-templates.cloneUpdate'),
                'method' => 'POST',
                'email' => $email])
            </div>
        </div>
    </div>
</div>
@endSection

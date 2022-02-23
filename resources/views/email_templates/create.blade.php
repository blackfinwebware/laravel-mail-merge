@extends(config('mailmerge.blade_layout'), ['model' => 'email', 'action' => 'create'])
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
                @include('mailmerge::forms.email', ['email' => $email])
            </div>
        </div>
    </div>
</div>
@endSection

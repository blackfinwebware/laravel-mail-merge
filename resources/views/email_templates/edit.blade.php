@extends(config('mailmerge.blade_layout'), ['model' => 'email', 'action' => 'edit'])
@section('breadcrumb')
<li class="breadcrumb-item">
    <a href="{{route('email-templates.index')}}">email</a>
</li>
<li class="breadcrumb-item">
    Edit
</li>
@endsection

@section('tools')
<a href="{{route('email-templates.create')}}">
    <span class="fa fa-plus"></span> email
</a>
@endsection

@section('content')
<div class="row">
    <div class='col-md-12'>
        <div class='card'>
            <div class="card-body">
                @include('mailmerge::forms.email',[
                'route'=> route('email-templates.update', $email->id),
                'method'=>'PUT',
                'groups' => $groups
                ])
            </div>
        </div>
    </div>
</div>
@endSection

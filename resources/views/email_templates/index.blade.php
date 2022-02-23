@extends( config('mailmerge.blade_layout') )
@section('content')
    <div class="table-responsive">
        <table class="records_list table table-striped @if(count($emails) > 0) datatable @endif">
            <thead>
            <tr>
                <th>Name</th>
                <th>To</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @if(count($emails) > 0)
                @foreach($emails as $email)
                    <tr>
                       <td>{{ $email->name }}</td>
                       <td>{{ $email->to }}</td>
                       <td>{{ $email->subject }}</td>
                       <td>{{ $email->message }}</td>
                        <td>
                         <ul style="list-style-type: none;margin: 0;padding:0">
                             <li style="padding:4px;"><a href="{{ route('email-templates.edit',[$email->id]) }}" class="btn" data-toggle="tooltip" title="Edit">Edit</a></li>
                             <li style="padding:4px;"><a href="{{ route('email-templates.clone',[$email->id]) }}" class="btn" data-toggle="tooltip" title="Clone">Clone</a></li>
                         </ul>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7">No email templates found.</td>
                </tr>
            @endif
            </tbody>
        </table>

        @if(!isset($nonav))
          {{ $emails->links() }}
        @endif

    </div>

      <div class="form-group col-md-6 float-right">
          <a href="{{ route('email-templates.create') }}" class="btn btn-primary btn-sm float-right">Create</a>
      </div>
@endsection

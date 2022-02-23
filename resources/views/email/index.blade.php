@extends('mailmerge::layouts.app')

@section('content')
    <div class="table-responsive">
        <table class="records_list table table-striped @if(count($emails) > 0) datatable @endif">
            <thead>
            <tr>
              {{--  <th style="text-align:center;">Select<br><input type="checkbox" id="email_selection_control" name="email_selection[control]" ></th> --}}
                <th>@sortablelink('id', 'ID')</th>
              {{--  <th>@sortablelink('assigned_id', 'Assigned ID')</th>
                <th>@sortablelink('title', 'Title')</th>
                <th>@sortablelink('presentationType', 'Presentation Type')</th>
                <th>@sortablelink('emailStatus', 'Status')</th>
                <th>@sortablelink('submitter', 'Submitter')</th>
                <th style="min-width:130px">Chairs</th>
                <th>@sortablelink('advisoryGroup', 'Interest Group')</th>
                <th>@sortablelink('justification', 'Description')</th>
                <th>@sortablelink('emailTrack', 'Session Track')</th>
                <th>@sortablelink('active_presentations_count', 'Num Presentations')</th>
                <th>@sortablelink('meetingRoom', 'Meeting Room')</th>
                <th>@sortablelink('begin_time', 'Begin Time')</th>
                <th>@sortablelink('end_time', 'End Time')</th>
                <th>@sortablelink('include_special_symposium', 'Include Special Symposium')</th> --}}
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @if(count($emails) > 0)
                @foreach($emails as $email)
                    <tr>
                      {{--  <td style="text-align:center;"><input type="checkbox" value="{!! $email->id !!}" id="email_selected_{!! $email->id !!}" name="email_selected[{!! $email->id !!}]" class="email_selection"></td> --}}
                        <td style="text-align:center;">{{ $email->id }}</td>
                      {{--  <td style="text-align:center;">{{ $email->assigned_id }}</td>
                        <td>{{ $email->title }}</td>
                        <td>{{ $email->presentation_type }}</td>
                        <td>{{ $email->email_status }}</td>
                        <td>@if($email->submitter && $email->submitter->person && $email->submitter->person->work_email)
                                <a title='Email this chair'  href="mailto:{!! $email->submitter->person->work_email !!}">{{ $email->submitter }}</a>
                                @else
                            {{ $email->submitter }}
                                @endif
                        </td>
                        <td style="min-width:160px">{!! $email->chairsAsHtml() !!}</td>
                        <td>{{ $email->advisory_group }}</td>
                        <td>
                            <a title="view all" data-href="{{ route('email-templates.showJustification', $email->id) }}" data-toggle="modal" href="#modalDialog">{{ \Illuminate\Support\Str::limit($email->justification, 200) }}</a>
                        </td>
                        <td>{{ $email->email_track }}</td>
                        <td style="text-align:center;">
                            @if($email->active_presentations_count)
                                <a title='View list of presentations' href="{{ route('presentation.filterIndex', ['presentation_index_filter_email_id' => $email->id]) }}">{{ $email->active_presentations_count }}</a>
                            @else

                            @endif
                        </td>
                        <td>{{ $email->meeting_room }}</td>
                        <td>{{ \App\Utils\BlackfinUtils::localeDatetime($email->begin_time) }}</td>
                        <td>{{ \App\Utils\BlackfinUtils::localeDatetime($email->end_time) }}</td>
                        <td style="text-align: center;">@if($email->include_special_symposium) <span class="fa fa-check"></span> @endif</td> --}}
                        <td>
                          <a href="{{ route('email-templates.edit',[$email->id]) }}" data-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>
                         {{--   <a href="{{ route('email-templates.clone', [$email->id]) }}"  onclick="return confirm('Are you sure that you want to clone this item?')">
                                <i class="fa fa-clone" data-name="clone" data-size="18" data-loop="true" data-c="#428BCA" data-hc="#428BCA" title="Clone this email"></i>
                            </a>  --}}
                            {!! Form::open(array(
                                    'style' => 'display: inline-block;',
                                    'method' => 'DELETE',
                                    'id' => "form_email_delete_" . $email->id,
                                    'onsubmit' => "return confirm('".trans("Are you sure?")."');",
                                    'route' => ['email_template.destroy', $email->id])) !!}

                            <a href="#" onclick="$('#form_email_delete_{{ $email->id }}').submit()"><i class="fa fa-trash" data-toggle="tooltip" title="Delete"></i></a>
                            {!! Form::close() !!}
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="7">No emails found.</td>
                </tr>
            @endif
            </tbody>
        </table>

        @if(!isset($nonav))
          {{ $emails->links() }}
            Total records found: {{ $emails->count() }}
        @endif

    </div>

      <div class="form-group col-md-6 float-right">
          <a href="{{ route('email-templates.create') }}" class="btn btn-success float-right">Add new</a>
      </div>

    <div class="modal fade" id="modalDialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content" id="modal_content">

            </div>
        </div>
    </div>
@endsection

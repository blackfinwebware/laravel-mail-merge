@extends( config('mailmerge.blade_layout') )
<style>
    pre p > a:link, pre a:link { color: blue !important; }
    pre {
        white-space: pre-wrap;       /* css-3 */
        white-space: -moz-pre-wrap !important;  /* Mozilla, since 1999 */
        white-space: -pre-wrap;      /* Opera 4-6 */
        white-space: -o-pre-wrap;    /* Opera 7 */
        word-wrap: break-word;       /* Internet Explorer 5.5+ */
        margin: 0;
    }
</style>
@section('content')
    <h4 style="margin: .3em 0 .9em;display: inline-block">MailMerge Results</h4>

    <div class="form-group text-right " style="display:inline; float:right;">
        <a href="{{ route('email-templates.index') }}" class="btn btn-sm btn-primary" data-toggle="tooltip" title="List">List</a>
    </div>
{!! $emails !!}
@endsection

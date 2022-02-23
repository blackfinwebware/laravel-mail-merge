<form action="{{$route ?? route('email-templates.store')}}" method="POST">
    {{csrf_field()}}
    <input type="hidden" name="_method" value="{{$method ?? 'POST'}}"/>

    <div class="form-group">
        <label for="name" class='col-form-label'>Name</label>
        <input type="text" class="form-control {{ $errors->has('name') ? ' has-danger' : '' }}" name="name" id="name" value="{{old('name',$email->name)}}" placeholder="" maxlength="255" >
        <p class="form-text text-muted">
            This must be unique.
        </p>
        @if($errors->has('name'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('name') }}</strong>
            </div>
        @endif
    </div>

    <div class="form-group">
        <label for="group" class='col-form-label input-required'>Group</label>
        <select name="group" class='form-control'>
            <option value=""></option>
            @foreach($groups as $group)
                <option value="{{ $group }}">{{ $group }}</option>
            @endforeach
        </select>
        <p class="form-text text-muted">
            Specify either a Group here or enter email addresses in the To field, but not both. If a group is specified, that will be used and the To field will be ignored.
        </p>
        @if($errors->has('group'))
            <p class="form-text text-muted">
                {{ $errors->first('group') }}
            </p>
        @endif
    </div>

    <div class="form-group">
        <label for="to" class='col-form-label input-required'>To</label>
        <textarea name="to" class='form-control' rows="8">{{$email->to}}</textarea>
        <p class="form-text text-muted">
            Enter email addresses either comma-separated or one per line<br>
        </p>
        @if($errors->has('to'))
            <p class="form-text text-muted">
                {{ $errors->first('to') }}
            </p>
        @endif
    </div>

    <div class="form-group">
        <label for="cc" class='col-form-label'>Cc</label>
        <input type="text" name="cc" value="{{$email->cc}}" class='form-control'>
        @if($errors->has('cc'))
            <p class="form-text text-muted">
                {{ $errors->first('cc') }}
            </p>
        @endif
    </div>

    <div class="form-group">
        <label for="bcc" class='col-form-label'>Bcc</label>
        <input type="text" name="bcc" value="{{$email->bcc}}" class='form-control'>
        @if($errors->has('bcc'))
            <p class="form-text text-muted">
                {{ $errors->first('bcc') }}
            </p>
        @endif
    </div>

    <div class="form-group">
        <label for="from" class='col-form-label input-required'>From</label>
        <input required type="text" class="form-control {{ $errors->has('from') ? ' has-danger' : '' }}" name="from" id="from" value="{{old('from',$email->from)}}" placeholder="" maxlength="255" >
        @if($errors->has('from'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('from') }}</strong>
            </div>
        @endif
    </div>

    <div class="form-group">
        <label for="replyto" class='col-form-label'>Reply To</label>
        <input type="text" class="form-control {{ $errors->has('replyto') ? ' has-danger' : '' }}" name="replyto" id="replyto" value="{{old('replyto', $email->replyto)}}" placeholder="" maxlength="255" >
        @if($errors->has('replyto'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('replyto') }}</strong>
            </div>
        @endif
    </div>

    <div class="form-group">
        <label for="subject" class='col-form-label input-required'>Subject</label>
        <input required type="text" class="form-control {{ $errors->has('subject') ? ' has-danger' : '' }}" name="subject" id="subject" value="{{old('subject', $email->subject)}}" placeholder="" maxlength="255" >

        @if($errors->has('subject'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('subject') }}</strong>
            </div>
        @endif
    </div>

    <p class="form-text text-muted" style="padding-left: 0">The following are macros available for expansion, and upon merge will be substituted in both the Subject and the Message by the appropriate string -- assuming that it can be discovered.</p>
    <div id="outer-container-avail-macros" >
      <div style="display: inline-flex; flex-flow: column nowrap; min-width: 100%; height: 100%;">
        <div style="display: flex;flex-flow: row nowrap;width: 100%;flex: 0 0 auto; padding-bottom: 10px; align-items: start">
          @if(!empty($macros))
           @foreach($macros as $key => $macroNames)
             <table class="text-muted no-padding avail-macros" style="margin-left: @if($loop->index == 0) 0; @else 10px; @endif "><tbody>
              <tr><td style="color:darkslategray">{{ $key }}</td></tr>
            @foreach($macroNames as $macroName)
              <tr><td>&lt;&lt;{{ $macroName }}&gt;&gt;</td></tr>
            @endforeach
            </tbody></table>
           @endforeach
          @else
              You will need to create your own Macro Expansion Guide class to define your application specific macros and how they should be expanded.
         @endif
        </div>
      </div>
    </div>

    <div class="form-group">
        <label for="message" class='col-form-label input-required'>Message</label>
        <textarea name="message" class='form-control' required rows="12">{{$email->message}}</textarea>
        @if($errors->has('message'))
            <p class="form-text text-muted">
                {{ $errors->first('message') }}
            </p>
        @endif
    </div>

    <div class="form-group text-right " style="margin-top:20px" >
        <a href="{{ route('email-templates.index') }}" class="btn btn-sm btn-secondary" data-toggle="tooltip" title="List">List</a>
        <input type="submit" class="btn btn-sm btn-primary" value="Save" name="save"/>
        <input type="submit" class="btn btn-sm btn-warning" value="Send" name="send"/>
        <input type="submit" class="btn btn-sm btn-success" value="Testsend" name="testsend"/>
    </div>
</form>

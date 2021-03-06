@extends('template')
@section('title')
Members
@stop
@section('content')

<div class="create_edit">
<div class="panel panel-primary">

    <div class="panel-heading">
        <h3 class="panel-title"><strong>New Member (D)</strong></h3>
    </div>

    <div class="panel-body">
        {!! Form::model($member = new \App\Person, ['action'=>'MarketingController@storeMember']) !!}

            {!! Form::hidden('level', $level, ['class'=>'form-control']) !!}

            @include('market.member.form')

            <div class="col-md-12">
                <div class="form-group pull-right">
                    {!! Form::submit('Add', ['class'=> 'btn btn-success']) !!}
                    <a href="/market/member" class="btn btn-default">Cancel</a>
                </div>
            </div>
        {!! Form::close() !!}
    </div>
</div>
</div>

@stop
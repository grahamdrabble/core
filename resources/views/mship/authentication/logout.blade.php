@extends('layout')

@section('title')
    Account Logout
@stop

@section('content')
    <p>
        You have now been logged out of the <?= Input::get('ssoKey', 'X') ?> system.  Would you like for your SSO session to be terminated, preventing future access to other services?
    </p>

    <div class="row">
        <div class="col-md-7 col-md-offset-4">
            <form class="form-horizontal" method="POST" action="{{ URL::route("mship.auth.logout.post") }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="form-group col-md-offset-3">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success btn-lg" name="processlogout" value="1">YES, please!</button>
                        <button type="submit" class="btn btn-danger btn-lg" name="processlogout" value="0">NO, thanks</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    Login
                </div>
                <div class="card-block">
                    <form role="form" method="POST" action="{{ url('/login') }}">
                        {{ csrf_field() }}

                        <div class="form-group row{{ $errors->has('id') ? ' has-danger' : '' }}">
                            <label for="inputId" class="col-sm-2 col-form-label">User ID</label>
                            <div class="col-sm-10">
                                <input id="inputId" type="number" class="form-control{{ $errors->has('id') ? ' form-control-danger' : '' }}" name="id" value="{{ old('id') }}" placeholder="12345678" required autofocus>
                                @if ($errors->has('id'))
                                <div class="form-control-feedback">
                                    {{ $errors->first('id') }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row{{ $errors->has('password') ? ' has-danger' : '' }}">
                            <label for="inputPassword" class="col-sm-2 col-form-label">Password</label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control{{ $errors->has('password') ? ' form-control-danger' : '' }}" id="inputPassword" name="password" placeholder="Password">
                                @if ($errors->has('password'))
                                <div class="form-control-feedback">
                                    {{ $errors->first('password') }}
                                </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-10 offset-sm-2">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input class="form-check-input" type="checkbox" name="remember"> Remember me
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-10 offset-sm-2">
                                <button type="submit" class="btn btn-primary">
                                    Login
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

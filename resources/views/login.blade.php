<html>
@include('header')
<link href="{{ asset('css/login.css') }}" rel="stylesheet">
<body>
    @if ($error != ""){
        {{$error}}
    }
    @else
        <div id="facebook-login-btb">
            <a href={{$loginUrl}}>Log in with Facebook!</a>
        </div>
    @endif
</body>
</html>
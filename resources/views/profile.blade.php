<html>
@include('header')
<link href="{{ asset('css/profile.css') }}" rel="stylesheet">
<link href="{{ asset('css/login.css') }}" rel="stylesheet">
<body>
<div class="card">
    <img src="{{$userProfile['picture']}}" alt="{{$userProfile['name']}}" style="width:100%">
    <div class="container">
        <h1 style="margin-left: -880px;">{{$userProfile['name']}}</h1>
        <div id="facebook-login-btb">
            <a href='../public/logout' style="margin-top: -107px;margin-left: 20px;">Logout</a>
        </div>
    </div>
</div>
</body>
</html>
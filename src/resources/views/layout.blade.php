<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')Free Js Tools @yield('post-title')</title>
    <link href="{{ asset('style.css') }}" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, shrink-to-fit=no" >
</head>
<body>
    <section id="logo-container">
        <div class="logo">freejstools</div>
    </section>
    <section id="main-container">
        @yield('content')
    </section>
</body>
</html>
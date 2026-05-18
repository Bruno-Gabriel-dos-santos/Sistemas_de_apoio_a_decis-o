<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Adminer - Acessando DB</title>
</head>
<body>
    <form id="adminerForm" method="post" action="/sistemas/{{ $sistema->id }}/db/adminer/proxy">
        @csrf
        <input type="hidden" name="auth[driver]" value="{{ $config['driver'] }}">
        <input type="hidden" name="auth[server]" value="{{ $config['server'] }}">
        <input type="hidden" name="auth[username]" value="{{ $config['username'] }}">
        <input type="hidden" name="auth[password]" value="{{ $config['password'] }}">
        <input type="hidden" name="auth[db]" value="{{ $config['db'] }}">
    </form>
    <script>
        // Auto-post to Adminer (this page is intended to be loaded inside an iframe)
        try {
            document.getElementById('adminerForm').submit();
        } catch (e) {
            document.body.innerText = 'Erro ao abrir Adminer.';
        }
    </script>
</body>
</html>

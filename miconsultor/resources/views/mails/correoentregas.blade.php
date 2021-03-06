<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Correo de Notificación</title>
</head>
<body>

    <p>Estimado usuario, tiene una nueva notificación.</p>
    {{-- <p>Estos son los datos del usuario que ha realizado la denuncia:</p> --}}
    <ul>
        <li>{{ $datosNotificacion[0]["usuarioent"] }}</li>
        <li>{{ $datosNotificacion[0]["empresa"] }}</li>
        <li>{{ $datosNotificacion[0]["modulo"] }}</li>
        <li>{{ $datosNotificacion[0]["menu"] }}</li>
        <li>{{ $datosNotificacion[0]["submenu"] }}</li>
        <li>{{ $datosNotificacion[0]["mensaje"] }}</li>
        {{-- <li>Teléfono: {{ $datosUser->cel }}</li>
        <li>DNI: {{ $datosUser->usernombre }}</li> --}}
    </ul>
    {{-- <p>Y esta es la posición reportada:</p>
    <ul>
        <li>Latitud: {{ $datosUser->nombre }}</li>
        <li>Longitud: {{ $datosUser->nombre }}</li>
        <li>
            <a href="https://www.google.com/maps/dir/{{ $datosUser->lat }},{{ $datosUser->lng }}">
                Ver en Google Maps
            </a>
        </li>
    </ul> --}}
</body>
</html>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Correo de Validación</title>
</head>
<body>

    <p>Estimado usuario, su codigo de confirmación ha sido generado correctamente.</p>
    {{-- <p>Estos son los datos del usuario que ha realizado la denuncia:</p> --}}
    <ul>
        <li>Codgio de confirmacion: {{ $datosUser["identificador"] }}</li>
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
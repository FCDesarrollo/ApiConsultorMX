<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <title>Correo de Validación</title>
</head>
<body>
    <strong>¡Correo Enviado Exitosamente!</strong>
    <p>Usted ha sido registrado por un administrador, favor de ingresar a la siguiente dirección para completar su registro.</p>
    <p>Código de confirmación: {{ $datosUser["identificador"] }}</p>
    <a href={{ $datosUser["link"] }}>Haga click aquí para completar su registro.</a>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<body>
<div style="border: 1px solid black;">
<table style="width:100%">
  <tr>
    <td><strong>Código Mensaje:</strong></td>
    <td>{{ $datosCorreo["codigoMensaje"] }}</td>
  </tr>
  <tr>
    <td><strong>Cuenta Origen:</strong></td>
    <td>{{ $datosCorreo["cuentaOrigen"] }}</td>
  </tr>
  <tr>
    <td><strong>Cuenta Destino:</strong></td>
    <td>{{ $datosCorreo["cuentaDestino"] }}</td>
  </tr>
  <tr>
    <td><strong>Proveedor:</strong></td>
    <td>{{ $datosCorreo["proveedor"] }}</td>
  </tr>
  <tr>
    <td><strong>Importe Pagado:</strong></td>
    <td>{{ $datosCorreo["importePagado"] }}</td>
  </tr>
</table>
<table style="width:100%; margin-top:15px;">
  <tr>
    <th>Fecha</th>
    <th>Serie-Folio</th> 
    <th>Total</th>
    <th>Pagado</th>
    <th>Pendiente</th>
  </tr>
  @foreach ($datosCorreo["detallesPago"] as $detallesPago)
  <tr style="text-align:center;">
    <td>{{ $detallesPago->Fecha }}</td>
    <td>{{ $detallesPago->SerieFolio }}</td>
    <td>{{ $detallesPago->Total }}</td>
    <td>{{ $detallesPago->Pagado }}</td>
    <td>{{ $detallesPago->Pendiente }}</td>
  </tr>
  @endforeach
</table>
</div>
</body>
</html>
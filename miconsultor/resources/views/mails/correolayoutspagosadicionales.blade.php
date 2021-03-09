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
    <th>Tipo Pago</th>
    <th>Fecha</th> 
    <th>Importe</th>
  </tr>
  @foreach ($datosCorreo["detallesPago"] as $detallesPago)
  <tr style="text-align:center;">
    <td>{{ $detallesPago->TipoPago }}</td>
    <td>{{ $detallesPago->Fecha }}</td>
    <td>{{ $detallesPago->Importe }}</td>
  </tr>
  @endforeach
</table>
</div>
</body>
</html>
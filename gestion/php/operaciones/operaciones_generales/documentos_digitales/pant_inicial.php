<?php
class pant_inicial extends toba_ei_pantalla
{
	function generar_layout()
	{

                $estado_firma = $this->controlador()->get_estado_firma();
		echo "<h1>{$estado_firma}</h1>";

                switch ($estado_firma) {
                  // Si el documento fue autorizado
                  case arai_documentos::DOCUMENTO_FIRMADO_DESC:
                    $firmantes = $this->controlador()->get_firmantes();
                    echo "<h2>Firmantes:</h2>";
                    echo "<p>{$firmantes}</p>";
                    break;
                  // Si el documento esta pendiente de firma
                  case arai_documentos::DOCUMENTO_PENDIENTE_FIRMA_DESC:
                    $firmantes = $this->controlador()->get_firmantes();
                    echo "<h2>Firmantes:</h2>";
                    echo "<p>{$firmantes}</p>";
                    $pendientes_firma = $this->controlador()->get_pendientes_firma();
                    echo "<h2>Pendientes de firma:</h2>";
                    echo "<p>{$pendientes_firma}</p>";
                    break;
                  // Si el documento fue rechazado
                  case arai_documentos::DOCUMENTO_RECHAZADO_DESC:
                    $rechazado_por = $this->controlador()->get_rechazado_por();
                    echo "<h2>Rechazado por:</h2>";
                    echo "<p>{$rechazado_por}</p>";
                    break;
                }
                
	}
}
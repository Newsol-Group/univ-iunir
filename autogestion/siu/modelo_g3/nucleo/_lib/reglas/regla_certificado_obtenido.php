<?php
class regla_certificado_obtenido extends regla
{
	function validar()
	{
		$alumno = $this->get_parametro('alumno');
		$entidad = $this->get_parametro('entidad');
		$fecha = $this->get_parametro('fecha');
		$datos_certificado = toba::consulta_php('co_certificados')->get_certificado_por_entidad($entidad);
		if (!empty($datos_certificado)) {
			$filtro = "	sga_certificados_otorg.certificado = {$datos_certificado['certificado']} AND
						sga_certificados_otorg.fecha_egreso <= '$fecha' AND
						sga_certificados_otorg.anulado = 0";
			$certificado_obtenido = toba::consulta_php('co_alumnos')->tiene_certificados_otorgados($alumno, $filtro);
			$resultado = $this->resultado_final($certificado_obtenido);
			if (!$resultado) {
				$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
				$this->set_parametros_mensaje_validacion(array($nombre_alumno, $datos_certificado['nombre']), true);
			}
		} else {
			// Si el código de entidad recibido no corresponde a un certificado, el resultado es VERDADERO.
			$resultado = true;
		}
		return $resultado;
	}

}
?>
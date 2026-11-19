<?php
class regla_tiene_paga_rematriculacion extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$concepto = 20;
		$anio = 0;

		$filtro_alumno = '';
		$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumnos_filtro($alumno, $filtro_alumno);
		
		$resultado = true;
		$sql = "SELECT	reinscripcion
				FROM	sga_reinscripciones 
				WHERE 	alumno = $alumno
				AND anio_academico = (select max(anio_academico) from sga_periodos where now() between fecha_inicio and fecha_fin)		
		;";
		
		$reinscripcion = guarani::db()->consultar($sql);
		$fila = toba::consulta_php('co_facturacion')->get_estado_pago_alumno_rematriculacion($alumno, $datos_alumno['ubicacion'], $datos_alumno['propuesta'], $concepto, $anio);
	
		if ($fila['ESTADO'] == 'ERROR') {
				$resultado = false;
		}

		if ($fila['DESCRP'] == 'FACTURA IMPAGA') 
			$resultado = false;

		if (!$resultado) 
			$this->set_parametros_mensaje_validacion(array($datos_alumno['apellido_y_nombre'],$datos_alumno['propuesta_nombre']), true);

		return $resultado;
	}
}
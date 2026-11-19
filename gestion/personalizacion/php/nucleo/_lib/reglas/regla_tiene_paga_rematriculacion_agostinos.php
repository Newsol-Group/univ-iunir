<?php
class regla_tiene_paga_rematriculacion_agostinos extends regla
{
	function validar()
	{
		// Parámetros de contexto.
		$alumno = $this->get_parametro('alumno');
		$concepto = 1;

		$filtro_alumno = '';
		$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumnos_filtro($alumno, $filtro_alumno);

		$resultado = true;
		$sql = "SELECT	solicitud as nrosol
				FROM	sga_constancias_solicitud 
				JOIN 	negocio_nsg.sga_constancias_ext ON negocio_nsg.sga_constancias_ext.id_constancia = sga_constancias_solicitud.constancia
				WHERE 	alumno = $alumno
				and 	negocio_nsg.sga_constancias_ext.concepto = $concepto
		;";
		
		$nrosol = guarani::db()->consultar($sql);
		$estado_pago = toba::consulta_php('co_facturacion')->get_estado_pago_alumno_rematriculacion($alumno, $datos_alumno['ubicacion'], $datos_alumno['propuesta'], $concepto, $datos_alumno['anio'], $nrosol);

		if ($estado_pago == 'N') {
			//Si no pagó, se debe verificar que fuera un alumno que no tuviera que rematricularse, sino, debe informarse que aún no pagó la rematriculación  
			//y no se puede inscribir a cursadas

			$filtro_alumno = " alumno in (select distinct a.alumno from sga_insc_cursada a, sga_comisiones b, sga_periodos_lectivos c, sga_periodos d
					where a.comision = b.comision and b.periodo_lectivo = c.periodo_lectivo and d.periodo = c.periodo and d.nombre like '%(2da Cohorte)'
					and d.anio_academico = date_part('year', now())-1)";
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumnos_filtro($alumno, $filtro_alumno);

			if(!empty($datos_alumno))
				$resultado = false;
		}

		if (!$resultado) 
			$this->set_parametros_mensaje_validacion(array($datos_alumno['apellido_y_nombre'], $nombre_comision), true);

		return $resultado;
	}
}
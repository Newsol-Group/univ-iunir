<?php
/**
 * 
 * @package Cursadas
 * @subpackage Reportes
 * 
 */
class ci_rep_oferta_comisiones extends ci_rep_sin_listado_inicial
{
    const SIN_DEFINIR = 'Sin definir';

	function get_tabla_principal()
	{
		return 'sga_comisiones';
	}

	function get_datos($filtro)
	{
		$filtro_activas = "sga_comisiones.estado = '" . comunes::estado_activo . "'";
		$filtro = empty($filtro) ? $filtro_activas : $filtro . ' AND ' . $filtro_activas;
		return toba::consulta_php('co_comisiones')->get_listado_comisiones_subcomisiones($filtro);
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('docente')->set_condicion_fija('es_igual_a');
		$filtro->columna('bandas_horarias')->set_condicion_fija('es_igual_a');
		if (empty($this->s__datos['bandas_horarias'])) {
			$filtro->columna('bandas_horarias')->set_estado(array('valor' => comunes::no));
		}
		parent::conf__filtro($filtro);
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_banda_horaria = '';
			if (isset($datos['bandas_horarias'])) {
				if ($datos['bandas_horarias']['valor'] == comunes::no) {
					// Si existen asignaciones, excluir las que corresponden a clases agregadas por fuera de las bandas horarias de la comisión.
					$filtro_banda_horaria = 'AND (sga_asignaciones.asignacion IS NULL OR sga_asignaciones.fecha_desde <> sga_asignaciones.fecha_hasta)';
				}
				$this->dep('filtro')->columna('bandas_horarias')->resetear_estado();
			}
			$filtro_docente = '';
			if (isset($datos['docente'])) {
				$filtro_docente .= "AND (
									NOT EXISTS (SELECT 1 FROM sga_subcomisiones WHERE sga_subcomisiones.comision = sga_comisiones.comision) AND
									EXISTS (SELECT 1 FROM sga_docentes_comision WHERE sga_docentes_comision.comision = sga_comisiones.comision AND sga_docentes_comision.docente = '{$datos['docente']['valor']}')
									OR
									EXISTS (SELECT 1 FROM sga_subcomisiones WHERE sga_subcomisiones.comision = sga_comisiones.comision) AND
									EXISTS (SELECT 1 FROM sga_subcomisiones, sga_docentes_subcomision WHERE sga_subcomisiones.comision = sga_comisiones.comision AND sga_docentes_subcomision.subcomision = sga_subcomisiones.subcomision AND sga_docentes_subcomision.docente = '{$datos['docente']['valor']}'))";
				$this->dep('filtro')->columna('docente')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_banda_horaria $filtro_docente";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);

			// Se arman las descripciones de los cortes de control por Actividad, Comisión y Subcomisión si existe.
			foreach ($datos as $clave=>$dato) {
				// Descripción de Actividad.
				$codigo = (isset($dato['actividad_codigo'])) ? ' (' . $dato['actividad_codigo'] . ')' : '';
				$datos[$clave]['actividad_descr'] = 'Actividad: ' . $dato['actividad_nombre'] . $codigo;
				// Descripción de Comisión.
				$cupo_com = (isset($dato['comision_cupo']) && ($dato['comision_cupo'] != 0)) ? $dato['comision_cupo'] : ' Sin definir';
				$turno = (isset($dato['turno_nombre'])) ? ' | Turno: ' . $dato['turno_nombre'] : '';
				$datos[$clave]['comision_datos'] = 'Comisión: ' . $dato['comision_nombre'] . $turno . ' | Cupo / Insc: ' . $cupo_com .  '  / ' . $dato['comision_inscriptos'] . ' | Ubicación: ' . $dato['ubicacion_nombre'];
				$docentes = isset($dato['docentes_resp']) ? ' | Docentes: ' . $dato['docentes_resp'] : ''; 
				// Descripción de Subcomisión si existe.
				if (isset($dato['subcomision'])) {
					$cupo_subcom = (isset($dato['subcomision_cupo']) && $dato['subcomision_cupo'] != 0) ? $dato['subcomision_cupo'] : ' Sin definir';
					$datos[$clave]['subcomision_datos'] = 'Subcomisión: ' . $dato['subcomision_nombre'] . ' | Cupo / Insc: ' . $cupo_subcom . ' / ' . $dato['subcomision_inscriptos'] . $docentes;
				} else {
					$datos[$clave]['subcomision_datos'] = '';
					$datos[$clave]['subcomision'] = '';
					$datos[$clave]['comision_datos'] .= $docentes;
				}

                $datos[$clave]['tipo_clase_nombre'] = (trim($datos[$clave]['tipo_clase_nombre']))? $datos[$clave]['tipo_clase_nombre'] : self::SIN_DEFINIR;
                $datos[$clave]['horario'] = (trim($datos[$clave]['horario']))? $datos[$clave]['horario'] : self::SIN_DEFINIR;
                $datos[$clave]['dia_semana_nombre'] = (trim($datos[$clave]['dia_semana_nombre']))? $datos[$clave]['dia_semana_nombre'] : self::SIN_DEFINIR;
                $datos[$clave]['espacio_nombre'] = (trim($datos[$clave]['espacio_nombre']))? $datos[$clave]['espacio_nombre'] : self::SIN_DEFINIR;

			}
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
}
?>
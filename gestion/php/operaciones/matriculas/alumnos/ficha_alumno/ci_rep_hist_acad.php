<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_rep_hist_acad extends ci_reporte_ficha_alumno
{
	protected $s__datos;
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__datos)) {
			$this->s__datos = $this->get_datos();
		}
		$cuadro->set_datos($this->s__datos);		
		
		if (empty($this->s__datos)) {
			$this->pantalla('pant_inicial')->eliminar_evento('pdf');
		}
		/*
		 * Está columna, solo es utilizada para el ordenamiento de la columna que tiene
		 * el codigo de la actividad y el nombre.
		 */
		$cuadro->eliminar_columnas(array('actividad_nombre'));
	}

	function get_datos($etiq = null)
	{
		$filtro = 'ha.persona = ' . $this->controlador()->controlador()->get_persona_seleccionada();
		return toba::consulta_php('co_alumnos')->get_historia_academica($filtro);
	}
	
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
				//ei_arbol($datos[$fila]);
		$datos_fila = $datos[$fila];
		$mostrar_evento = false;

		if ($datos_fila['origen'] == acta::origen_equivalencias) { 
			// Origen equivalencia
			$mostrar_evento = true;
			$evento->vinculo()->agregar_parametro(utf8_encode('es_equiv'), $datos_fila['origen'] == acta::origen_equivalencias);
			$evento->vinculo()->agregar_parametro(utf8_encode('equiv'), utf8_encode($datos_fila['equivalencia']));
			$evento->vinculo()->agregar_parametro(utf8_encode('equiv_tramite'), utf8_encode($datos_fila['equivalencia_tramite']));
			$evento->vinculo()->agregar_parametro(utf8_encode('actividad'), utf8_encode($datos_fila['actividad_codigo_y_nombre']));
		}

		if ($datos_fila['origen'] == acta::origen_aprobacion_resolucion && $datos_fila['es_modulo']) { 
			// Módulo aprobado por resolución
			$mostrar_evento = true;
			$evento->vinculo()->agregar_parametro(utf8_encode('es_modulo_res'), $datos_fila['origen'] == acta::origen_aprobacion_resolucion);
			$evento->vinculo()->agregar_parametro(utf8_encode('elemento'), utf8_encode($datos_fila['actividad']));
			$evento->vinculo()->agregar_parametro(utf8_encode('plan_version'), utf8_encode($datos_fila['plan_version']));
			$evento->vinculo()->agregar_parametro(utf8_encode('modulo'), utf8_encode($datos_fila['actividad_codigo_y_nombre']));
		}

		if ($datos_fila['origen_otra_propuesta']) { 
			// Aprobado en otra propuesta
			$mostrar_evento = true;
			$evento->vinculo()->agregar_parametro(utf8_encode('es_op'), $datos_fila['op']);
			$evento->vinculo()->agregar_parametro(utf8_encode('elemento_revision'), utf8_encode($datos_fila['elemento_revision']));
			$evento->vinculo()->agregar_parametro(utf8_encode('plan_version_origen'), utf8_encode($datos_fila['plan_version_origen']));
		}

		if ($mostrar_evento) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}
	
	function unset_vars()
	{
		unset($this->s__datos);
	}
	
}
?>
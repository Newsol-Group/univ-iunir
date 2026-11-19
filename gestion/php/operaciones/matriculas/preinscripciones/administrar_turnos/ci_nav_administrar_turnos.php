<?php
class ci_nav_administrar_turnos extends ci_navegacion
{
	protected $s__selecion;

	function es_edicion()
	{
		return !empty($this->s__seleccion);
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_turnos_preinscripcion')->get_listado_turnos_admistrador($filtro);
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
	}

	/**
	 * Se define la configuración del evento de selección para el cuadro.
	 */
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si el turno no está Disponible no se permite seleccionarla.		
		$datos = $this->dep('cuadro')->get_datos();
		$estado = $datos[$fila]['disponible'];
		if ($estado != '0') {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
//		$filtro->columna('fecha')->borrar_condicion('desde');
//		$filtro->columna('fecha')->borrar_condicion('hasta');
		$filtro->columna('fecha')->borrar_condicion('es_distinto_de');

		$filtro->columna('hora')->borrar_condicion('es_mayor_que');
		$filtro->columna('hora')->borrar_condicion('es_menor_que');
		$filtro->columna('hora')->borrar_condicion('es_menor_igual_que');
		$filtro->columna('hora')->borrar_condicion('es_igual_a');
		$filtro->columna('hora')->borrar_condicion('es_distinto_de');
	}

	function evt__filtro__filtrar($datos)
	{

		$filtro_configuracion = '';
		if (isset($datos['configuracion'])) {
			$config = toba::db()->quote($datos['configuracion']['valor']);
			$filtro_configuracion .= " AND pre_turnos_config.turno_config = $config";
			$this->dep('filtro')->columna('configuracion')->resetear_estado();
		}
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = " {$this->dep('filtro')->get_sql_where()} $filtro_configuracion";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
		 * 	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 * 	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 * 	que debe filtrar para obtener datos.
		 */
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).			
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

}
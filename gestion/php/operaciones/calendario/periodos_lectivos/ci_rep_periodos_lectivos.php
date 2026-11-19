<?php
/**
 *
 *
 * @package		Calendario
 * @subpackage	Períodos Lectivos
 * 
 */
class ci_rep_periodos_lectivos extends ci_rep_sin_listado_inicial
{
	protected $s__periodo_lectivo_seleccionado;
	protected $s__datos_periodo_lectivo;
	
	protected $ev_info;

	function get_tabla_principal()
	{
		return 'sga_periodos_lectivos';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_periodos_lectivos')->get_listado($filtro);
	}

	function ini()
	{
		parent::ini();
		toba::memoria()->desactivar_reciclado();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA PERÍODOS LECTIVOS 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__periodo_lectivo_seleccionado = $seleccion['periodo'];
		$this->set_pantalla('periodos_inscripcion');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA PERÍODOS DE INSCRIPCIÓN 
	//-----------------------------------------------------------------------------------

	function conf__periodos_inscripcion()
	{
		$filtro = "sga_periodos_lectivos.periodo = $this->s__periodo_lectivo_seleccionado";
		$this->s__datos_periodo_lectivo = current(toba::consulta_php('co_periodos_lectivos')->get_listado($filtro));

		$this->ev_info()->set_datos($this->s__datos_periodo_lectivo);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_detalle ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_detalle(toba_ei_cuadro $cuadro)
	{
		$datos = toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_inscripcion_por_periodo($this->s__periodo_lectivo_seleccionado);
		$cuadro->set_datos($datos);

		$etiqueta = guarani_etiquetas::get_tabla('sga_periodos_inscripcion');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
	}

	function conf_evt__cuadro_detalle__informacion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_detalle')->get_datos();
		$datos_fila = $datos[$fila];

		$evento->vinculo()->agregar_parametro(utf8_encode('periodo_insc'), utf8_encode($datos_fila['periodo_insc']));
		$evento->vinculo()->agregar_parametro(utf8_encode('periodo_inscripcion'), utf8_encode($datos_fila['periodo_inscripcion']));
		$evento->vinculo()->agregar_parametro(utf8_encode('periodo'), utf8_encode($this->s__periodo_lectivo_seleccionado));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__periodo_lectivo_seleccionado);
		unset($this->s__datos_periodo_lectivo);
		$this->set_pantalla('periodos_lectivos');
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('periodo_anio_academico', 'periodo_nombre', 'cant_comisiones', 'periodo_fecha_inicio_visual', 'periodo_fecha_fin_visual', 'fecha_inicio_dictado_visual', 'fecha_fin_dictado_visual'));

			$this->ev_info->set_columnas_fila(array('periodo_anio_academico', 'periodo_nombre', 'cant_comisiones'), 1);
			$this->ev_info->set_columnas_fila(array('periodo_fecha_inicio_visual', 'periodo_fecha_fin_visual'), 2);
			$this->ev_info->set_columnas_fila(array('fecha_inicio_dictado_visual', 'fecha_fin_dictado_visual'), 3);

			$this->ev_info->set_etiqueta('periodo_anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'Período Lectivo');
			$this->ev_info->set_etiqueta('cant_comisiones', 'Comisiones');

			$this->ev_info->set_etiqueta('periodo_fecha_inicio_visual', 'Inicio');
			$this->ev_info->set_etiqueta('periodo_fecha_fin_visual', 'Fin');

			$this->ev_info->set_etiqueta('fecha_inicio_dictado_visual', 'Inicio Dictado');
			$this->ev_info->set_etiqueta('fecha_fin_dictado_visual', 'Fin Dictado');
		}
		return $this->ev_info;
	}

}
?>
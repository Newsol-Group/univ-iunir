<?php
abstract class ci_nav_procesos_bk extends ci_navegacion
{
	protected $s__nombre_proceso;
	
	protected $s__ruta_archivo_resultados;
	protected $s__datos_resultados;
	protected $s__gl;
	protected $s__modo_visualizacion;

	function conf()
	{
		parent::conf();
		$this->set_nombre_proceso();
	}

	abstract function set_nombre_proceso();
	
	function get_resultado_reporte($reporte)
	{
		return $this->s__gl->get($reporte, $this->s__ruta_archivo_resultados);
	}

	function modo_visualizacion()
	{
		if (!isset($this->s__modo_visualizacion)) {
			return false;
		}
		return $this->s__modo_visualizacion;
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		if ($this->existe_dependencia('ci_procesamiento')) {
			return $this->dep('ci_procesamiento')->ajax__reporte($params, $respuesta);
		}
	}

	//--------------------------------------------------------------------------
	//---- cuadro_resultados_ant -----------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_resultados_ant(guarani_pers_ei_cuadro $cuadro)
	{
		$nombre_proceso = toba::db()->quote($this->s__nombre_proceso);
		$where = "proc_logs.nombre_proceso = $nombre_proceso";
		$datos = toba::consulta_php('co_procesos_bk')->get_listado($where);
		$cuadro->set_datos($datos);

		$msj_eof = guarani_mensajes::get('800_eof_cuadro', array('resultados anteriores'));
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro_resultados_ant__seleccion($seleccion)
	{
		$datos_log = toba::consulta_php('co_procesos_bk')->get_log_proceso($seleccion['log_proceso']);
		$carpeta_logs = guarani_logs::get_carpeta_logs() . $datos_log['carpeta_logs'];
		$this->s__ruta_archivo_resultados = $carpeta_logs . '/' . guarani_procesos_bk::archivo_resultados . '.xml';

		$this->s__gl = new guarani_logs_resultados('dummy');
		$proceso = $this->s__nombre_proceso;
		$datos = $this->s__gl->get($proceso::reporte_resultados, $this->s__ruta_archivo_resultados);

		$this->s__datos_resultados = current($datos['mensajes']);
		$this->s__modo_visualizacion = true;
		$this->set_pantalla('resultados_visualizacion');
	}

	function conf_evt__cuadro_resultados_ant__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_resultados_ant')->get_datos();
		if ($datos[$fila]['estado'] != 100) {
			$evento->anular();
		}
	}

	//--------------------------------------------------------------------------
	//---- resultados ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__resultados(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
		if (isset($this->s__datos_resultados)) {
			$form->set_datos($this->s__datos_resultados);
			if ($this->modo_visualizacion()) {
				foreach (array_keys($this->s__datos_resultados) as $ef) {
					if ($form->existe_ef($ef) && $form->existe_evento('rep_' . $ef) && $form->ef($ef)->get_estado() == 0)
						$form->evento('rep_' . $ef)->desactivar();
				}
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- form_totales --------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
		if (isset($this->s__datos_resultados)) {
			$form->set_datos($this->s__datos_resultados);
			if ($this->modo_visualizacion()) {
                            if (is_array($this->s__datos_resultados)) {
				foreach (array_keys($this->s__datos_resultados) as $ef) {
					if ($form->existe_ef($ef) && $form->existe_evento('rep_' . $ef) && $form->ef($ef)->get_estado() == 0) {
						$form->evento('rep_' . $ef)->desactivar();
                                        }
				}
                            }
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- Configuraciones -----------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__resultados_visualizacion__salida()
	{
		unset($this->s__ruta_archivo_resultados);
		unset($this->s__datos_resultados);
		unset($this->s__gl);
		unset($this->s__modo_visualizacion);
	}

	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		$confirm = 'Esta acción abortará la ejecución del procesamiento restante. Lo procesado hasta el momento no será revertido. ¿Desea continuar?';
		$pantalla->evento('abortar')->set_msg_confirmacion($confirm);
	}

	//--------------------------------------------------------------------------
	//---- Eventos -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__result_anteriores()
	{
		$this->set_pantalla('resultados_seleccion');
	}

	function evt__volver_inicio()
	{
		$this->set_pantalla('seleccion');
	}

	function evt__volver_resultados_seleccion()
	{
		$this->set_pantalla('resultados_seleccion');
	}

	function evt__abortar()
	{
		$this->cn()->abortar_proceso();
	}

}
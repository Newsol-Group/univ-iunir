<?php
/**
 *
 *
 * @package 	Comunicación
 * @subpackage 	-
 * 
 */
class ci_edi_mensaje extends guarani_pers_ci
{
	const fecha = 1;
	const dias_7 = 2;
	const dias_15 = 3;
	const dias_30 = 4;

	protected $s__adjunto;

	function conf()
	{
		if (!$this->cn()->entidad()->es_nueva()) {			
		}
	}

	function conf__pant_mensaje(guarani_ei_pantalla $pantalla)
	{
		if (!$this->cn()->entidad()->es_nueva()) {			
			$pantalla->eliminar_evento('enviar');
			$pantalla->eliminar_evento('adjuntar');
			$pantalla->eliminar_dep('form_adjuntos');
			$this->dep('cuadro_adjuntos')->evento('eliminar')->anular();
			$this->dep('form_mensaje')->set_solo_lectura();
		}
	}

	function marcar_control_cambios()
	{
		$this->controlador()->controlador()->set_hubo_cambios();
		echo toba_js::abrir();
		echo 'var adjuntos_modificados = true;';
		echo toba_js::cerrar();
	}

	function es_adjunto_temporal($path_archivo)
	{
		return strpos($path_archivo, '/temp') > 0;
	}

	//-----------------------------------------------------------------------------------
	//---- form_mensaje -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_mensaje(form_mensaje $form)
	{
		$form->ef('responder_a')->set_expreg(comunes::er_email);
		$datos = $this->cn()->entidad()->get_datos();
		$alcance = $this->cn()->entidad()->get_alcance();
		if ($alcance != mensajes::todos_alumnos_y_docentes) {
			$form->desactivar_efs(array('enviar_por_twitter', 'enviar_por_facebook', 'mensaje_twitter'));
		}
		if (!$this->cn()->entidad()->es_nueva()) {
			$form->desactivar_efs(array('cuerpo'));
			$datos['cuerpo_fijo'] = $datos['cuerpo'];
		} else {
			$form->desactivar_efs(array('cuerpo_fijo'));
		}
		$form->set_datos($datos);
	}

	function evt__form_mensaje__modificacion($datos)
	{
		switch ($datos['hasta']) {
			case self::dias_7 :
				$datos['fecha_hasta'] = guarani_fecha::get_fecha_desplazada($datos['fecha_desde'], 7);
				break;

			case self::dias_15 :
				$datos['fecha_hasta'] = guarani_fecha::get_fecha_desplazada($datos['fecha_desde'], 15);
				break;

			case self::dias_30 :
				$datos['fecha_hasta'] = guarani_fecha::get_fecha_desplazada($datos['fecha_desde'], 30);
				break;
		}

		if ($this->cn()->entidad()->es_nueva()) {
			$usuario = toba::usuario()->get_id();
			$datos_persona = guarani::co('co_personas')->get_persona_x_usuario($usuario);
			if (!empty($datos_persona['persona'])) {
				$datos['persona_alta'] = $datos_persona['persona'];
			}
		}
		
		$this->cn()->entidad()->set_datos($datos);
	}

	function get_opciones_hasta()
	{
		return array(array('valor' => self::fecha, 'descr' => 'La fecha'),
			array('valor' => self::dias_7, 'descr' => '7 días posteriores'),
			array('valor' => self::dias_15, 'descr' => '15 días posteriores'),
			array('valor' => self::dias_30, 'descr' => '30 días posteriores')
		);
	}

	//-----------------------------------------------------------------------------------
	//---- form_adjuntos ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_adjuntos__modificacion($datos)
	{
		$this->s__adjunto = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_adjuntos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_adjuntos(toba_ei_cuadro $cuadro)
	{
		$archivos = $this->cn()->entidad()->get_adjuntos();
		if (!empty($archivos)) {
			
			foreach ($archivos as $clave => $valor) {
				$archivos[$clave]['path'] = $valor['path'];
				$archivos[$clave]['nombre'] = $valor['nombre'].'.'.$valor['extension'];
			}
		}		
		$cuadro->set_datos($archivos);
	}

	function conf_evt__cuadro_adjuntos__descargar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_adjuntos')->get_datos();
		$datos_fila = $datos[$fila];

		$evento->vinculo()->agregar_parametro(utf8_encode('path'), utf8_encode($datos_fila['path']));
		$evento->vinculo()->agregar_parametro(utf8_encode('nombre'), utf8_encode($datos_fila['nombre']));
	}

	function evt__cuadro_adjuntos__eliminar($seleccion)
	{
		if ($this->es_adjunto_temporal($seleccion['path'])) {
			$this->cn()->entidad()->eliminar_de_adjuntos_agregar($seleccion['path']);
			$this->cn()->entidad()->eliminar_adjunto($seleccion['path']);
		} else {
			$this->cn()->entidad()->marcar_para_eliminar($seleccion['path']);
		}
		$this->marcar_control_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__enviar()
	{
		$this->cn()->entidad()->sincronizar();

		$this->cn()->entidad()->enviar_mensaje();

		$status = $this->cn()->entidad()->get_errores();

		$this->controlador()->controlador()->evt__cancelar();

		if (guarani_arreglos::count($status) == 1) {
			toba::notificacion()->agregar($status[0]['mensaje'], $status[0]['tipo']);
		} else {
			throw guarani::error($status);
		}
	}

	function evt__guardar()
	{
		$this->cn()->entidad()->sincronizar();

		$status = $this->cn()->entidad()->get_errores();

		$this->controlador()->controlador()->evt__cancelar();

		if (guarani_arreglos::count($status) == 1) {
			toba::notificacion()->agregar($status[0]['mensaje'], $status[0]['tipo']);
		} else {
			throw guarani::error($status);
		}
	}

	function evt__adjuntar()
	{
		if (!empty($this->s__adjunto['archivo'])) {
			if ($this->cn()->entidad()->es_nueva()) {
				$this->cn()->entidad()->agregar_documento($this->s__adjunto);
			} else {
				$this->cn()->entidad()->marcar_para_agregar_documento($this->s__adjunto);
			}
			unset($this->s__adjunto);
			$this->marcar_control_cambios();
		}
	}

}
?>
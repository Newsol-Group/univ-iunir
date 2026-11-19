<?php
class ci_edi_cerrar_acta extends ci_impresion
{
	protected $s__alumnos;
	protected $s__id_acta;
	protected $s__datos_acta;
	
	// Selección manual de libro / tomo.
	protected $s__libro_tomo;
	
	function ini()
	{
		$this->s__id_acta = $this->controlador()->get_acta_seleccionada();
		$this->s__datos_acta = $this->controlador()->get_datos_acta();
	}

	function get_libros_tomos_actas()
	{
		$datos_acta = $this->controlador()->get_datos_acta();
		return toba::consulta_php('co_actas')->get_libros_tomos_actas($datos_acta);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL 
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$operacion = guarani::get_id_operacion_toba();
		$celda_actual = toba::memoria()->get_celda_memoria_actual_id();
		$llamado_mesa = $this->controlador()->get_llamado_mesa();

		// Configuración del vínculo para la impresión del acta.
		$this->evento('imprimir')->vinculo()->set_parametros(array('operacion_a_volver' => $operacion, 'celda_a_restaurar' => $celda_actual, 'id_acta' => $this->s__id_acta, 'llamado_mesa_entidad' => $llamado_mesa));
		$this->evento('imprimir')->vinculo()->set_celda_memoria('celda_imp');

		// Configuración del vínculo para la edición del acta.
		$this->evento('editar')->vinculo()->set_parametros(array('operacion_a_volver' => $operacion, 'celda_a_restaurar' => $celda_actual, 'id_acta' => $this->s__id_acta, 'llamado_mesa_entidad' => $llamado_mesa));
		$this->evento('editar')->vinculo()->set_celda_memoria('celda_edit');
	}

	//-----------------------------------------------------------------------------------
	//---- form_libro -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_libro(toba_ei_formulario $form)
	{
		$datos = array();
		$datos['observaciones'] = $this->s__datos_acta['observaciones'];
		if (!empty($this->s__libro_tomo)) {
			$datos['libro_tomo'] = $this->s__libro_tomo;
		}
		$form->set_datos($datos);

		$form->set_solo_lectura('observaciones');
		if (!acta::solicitar_libro_cierre($this->s__id_acta, true)) {
			$form->desactivar_efs(array('libro_tomo'));
		}
	}

	function evt__form_libro__modificacion($datos)
	{
		if (!empty($datos['libro_tomo'])) {
			$this->s__libro_tomo = $datos['libro_tomo'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función invocada desde el ci de navegación para limpiar la memoria de las variables utilizadas en la edición.
	 */
	function resetear_variables()
	{
		unset($this->s__alumnos);
		unset($this->s__id_acta);
		unset($this->s__datos_acta);
		unset($this->s__libro_tomo);
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__alumnos(toba_ei_cuadro $cuadro)
	{
		//Seteo el tamaño de página del cuadro
		$lineas_folio = $this->s__datos_acta['renglones_folio'];
		$estado = $this->s__datos_acta['estado'];
		$cuadro->set_tamanio_pagina($lineas_folio);

		//Seteo los datos del cuadro
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		$this->s__alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_examen($this->s__id_acta, $estado, $params_sistema);
		$cuadro->set_datos($this->s__alumnos);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cerrar()
	{
		//Se realizan todas las acciones para cerrar el acta seleccionada
		$this->cn()->cerrar_acta($this->s__id_acta, $this->controlador()->get_mesa_acta_seleccionada(), $this->controlador()->get_llamado(), $this->s__alumnos, $this->s__libro_tomo);

		//Limpio la memoria
		$this->disparar_limpieza_memoria();

		//Se resetea el cn
		$this->cn()->resetear();

		//Se resetea la zona
		toba::zona()->resetear();

		//Seteo la pantalla e informo el éxito del cierre del acta
		$this->controlador()->set_pantalla('seleccion_acta');
		toba::notificacion()->agregar('Se ha cerrado el acta de exámen con éxito', 'info');
	}

}
?>

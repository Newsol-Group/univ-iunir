<?php
class ci_edi_allegados extends guarani_pers_ci
{
	const chars_maximo_combo = 100;

	protected $datos_allegado;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ALLEGADOS 
	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	//---- cuadro_allegados -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_allegados__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_allegado($seleccion);
		$this->set_pantalla('edicion_allegado');
	}

	function conf__cuadro_allegados(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_allegados();
		foreach ($datos as $clave => $valor) {
			$email='';	
			if (!empty($valor['email'])){
				$email=' Email: ' . $valor['email'] ;
			}
			$telefono='';	
			if (!empty($valor['telefono'])){
				$telefono=' telefono: ' . $valor['telefono'] ;
			}
			$tipo_documento='';	
			if (!empty($valor['tipo_documento'])){
				$tipo_documento_desc = current(toba::consulta_php('co_personas')->get_tipos_documentos($valor['tipo_documento']));
			}


			$datos[$clave]['descr_allegado'] = $valor['apellido'] . ' ' . $valor['nombres'] . ' (' . $valor['tipo_allegado_nombre'] . ') '.$email.' '.$telefono.' '.$tipo_documento;
		}

		$etiqueta = guarani_etiquetas::get_tabla('mdp_personas_allegados');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_allegados__agregar()
	{
		$this->set_pantalla('edicion_allegado');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION ALLEGADOS 
	//-----------------------------------------------------------------------------------

	function conf__edicion_allegado(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_allegado_seleccionado()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('mdp_personas_allegados');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_allegados ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_allegados(toba_ei_formulario $form)
	{
		$form->ef('numero')->set_expreg(comunes::er_numero_entero);
		$form->ef('piso')->set_expreg(comunes::er_numero_entero);
		$form->ef('email')->set_expreg(comunes::er_email);
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
		if (isset($this->datos_allegado)) {
			$form->set_datos($this->datos_allegado);
		} else {
			$datos = $this->cn()->entidad()->get_datos_allegado_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_allegados__modificacion($datos)
	{
		$this->datos_allegado = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_allegado($this->datos_allegado);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_allegado($this->datos_allegado);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_allegado_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_allegado);
		$this->cn()->entidad()->deseleccionar_allegado();
		$this->set_pantalla('seleccion_allegado');
	}

}
?>
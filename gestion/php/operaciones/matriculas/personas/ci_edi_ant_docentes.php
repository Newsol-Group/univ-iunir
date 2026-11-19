<?php
class ci_edi_ant_docentes extends guarani_pers_ci
{
	protected $datos_ant_docente;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ANTECEDENTES DOCENTES 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_ant_docentes ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_ant_docentes__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_ant_docente($seleccion);
		$this->set_pantalla('edicion_antecedente');
	}

	function conf__cuadro_ant_docentes(toba_ei_cuadro $cuadro)
	{
		$datos_ant_docentes = $this->cn()->entidad()->get_datos_ant_docentes();

		foreach ($datos_ant_docentes as $clave => $valor) {
			/**
			 * Para el campo nombre de Institución se chequea de dónde debe sacarse el valor, si del campo nombre de la 
			 * institución obtenido por carga externa, o del campo institucion_otra de la tabla, según cuál de ellos tenga valor.
			 * En este caso que sea este último se verifica que el valor que tiene no sea el valor por defecto (solo para 
			 * visualización) si no uno ingresado por el usuario. De lo contrario el campo se muestra sin valor alguno.
			 */
			if (!isset($valor['institucion'])) {
				$datos_ant_docentes[$clave]['institucion_nombre'] = $valor['institucion_otra'];
			}
		}

		$etiqueta = guarani_etiquetas::get_tabla('mdp_antecedentes_docentes');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos_ant_docentes);
	}

	function evt__cuadro_ant_docentes__agregar()
	{
		$this->set_pantalla('edicion_antecedente');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION ANTECEDENTES DOCENTES 
	//-----------------------------------------------------------------------------------

	function conf__edicion_antecedente(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_ant_docente_seleccionado()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('mdp_antecedentes_docentes');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ant_docentes ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ant_docentes(toba_ei_formulario $form)
	{
		if (isset($this->datos_ant_docente)) {
			$form->set_datos($this->datos_ant_docente);
		} else {
			$datos = $this->cn()->entidad()->get_datos_ant_docente_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_ant_docentes__modificacion($datos)
	{
		$this->datos_ant_docente = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_ant_docente($this->datos_ant_docente);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_ant_docente($this->datos_ant_docente);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_ant_docente_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_ant_docente);
		$this->cn()->entidad()->deseleccionar_ant_docente();
		$this->set_pantalla('seleccion_antecedente');
	}

}
?>
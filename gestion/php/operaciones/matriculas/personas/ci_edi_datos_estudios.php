<?php
/**
 *
 *
 * @package		Matricula
 * @subpackage	Personas
 * 
 */
class ci_edi_datos_estudios extends guarani_pers_ci
{
	protected $datos_estudio;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ESTUDIO 
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_estudios --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_estudios__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_estudio($seleccion);
		$this->set_pantalla('edicion_estudio');
	}

	function conf__cuadro_estudios(toba_ei_cuadro $cuadro)
	{
		$datos_estudios = $this->cn()->entidad()->get_datos_estudios();

		foreach ($datos_estudios as $clave => $valor) {
			/**
			 * Para el campo nombre de Institución se chequea de dónde debe sacarse el valor, si del campo nombre de la 
			 * institución obtenido por carga externa, o del campo institucion_otra de la tabla, según cuál de ellos tenga valor.
			 * En este caso que sea este último se verifica que el valor que tiene no sea el valor por defecto (solo para 
			 * visualización) si no uno ingresado por el usuario. De lo contrario el campo se muestra sin valor alguno.
			 * Se realiza algo análogo para los campos título y titulo_otro.
			 */
			if ($valor['nivel_estudio'] == persona::nivel_secundario) {
				if (!empty($valor['colegio'])) {
					$datos_estudios[$clave]['institucion_nombre'] = toba::consulta_php('co_instituciones')->get_nombre_colegio_por_id($valor['colegio']);
				} else {
					$datos_estudios[$clave]['institucion_nombre'] = $valor['colegio_otro'];
				}
			} else {
				if (!isset($valor['institucion'])) {
					$datos_estudios[$clave]['institucion_nombre'] = $valor['institucion_otra'];
				}
			}

			if (!isset($valor['titulo'])) {
				$datos_estudios[$clave]['titulo_nombre'] = $valor['titulo_otro'];
			}
		}

		$etiqueta = guarani_etiquetas::get_tabla('mdp_datos_estudios');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos_estudios);
	}

	function evt__cuadro_estudios__agregar()
	{
		$this->set_pantalla('edicion_estudio');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION ESTUDIO 
	//-----------------------------------------------------------------------------------

	function conf__edicion_estudio(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->controlador()->evento('cancelar')->desactivar();
		if ($this->controlador()->controlador()->pantalla('edicion')->existe_evento('eliminar')) {
			$this->controlador()->controlador()->evento('eliminar')->desactivar();
		}
		$this->controlador()->controlador()->evento('guardar')->desactivar();

		if ($this->cn()->entidad()->hay_estudio_seleccionado()) {
			$this->evento('alta')->ocultar();
			$etiqueta = guarani_etiquetas::get_tabla('mdp_datos_estudios');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_estudios ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_estudios(toba_ei_formulario $form)
	{
		if (isset($this->datos_estudio)) {
			$form->set_datos($this->datos_estudio);
		} else {
			$datos = $this->cn()->entidad()->get_datos_estudio_seleccionado();
			$form->set_datos($datos);
		}
	}

	function evt__form_estudios__modificacion($datos)
	{
		if (!is_numeric($datos['titulo']) || $datos['nivel_estudio'] == persona::nivel_sin_estudio) {
			$datos['titulo'] = null;
		}

		if (!is_numeric($datos['institucion']) || $datos['nivel_estudio'] == persona::nivel_sin_estudio) {
			$datos['institucion'] = null;
		}

		$this->datos_estudio = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nuevo_estudio($this->datos_estudio);
		$this->evt__cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_estudio($this->datos_estudio);
		$this->evt__cancelar();
	}

	function evt__baja()
	{
		$this->cn()->entidad()->eliminar_estudio_seleccionado();
		$this->evt__cancelar();
	}

	function evt__cancelar()
	{
		unset($this->datos_estudio);
		$this->cn()->entidad()->deseleccionar_estudio();
		$this->set_pantalla('seleccion_estudio');
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	// Se recupera la nacionalidad de la institución seleccionada, según el país asociado a la localidad de la misma.
	function ajax__get_origen_institucion($params, toba_ajax_respuesta $respuesta) 
	{
		$institucion = $params['institucion'];
		$nacionalidad = toba::consulta_php('co_instituciones')->get_origen_institucion($institucion);
		if (!empty($nacionalidad)) {
			$salida = array('origen'=>$nacionalidad);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}
	
	// Se recupera la nacionalidad del colegio seleccionado, según el país asociado a la localidad del mismo.
	function ajax__get_origen_colegio($params, toba_ajax_respuesta $respuesta) 
	{
		$colegio = $params['colegio'];
		$nacionalidad = toba::consulta_php('co_instituciones')->get_origen_colegio($colegio);
		if (!empty($nacionalidad)) {
			$salida = array('origen'=>$nacionalidad);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}
}
?>
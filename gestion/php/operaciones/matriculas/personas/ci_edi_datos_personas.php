<?php
class ci_edi_datos_personas extends guarani_pers_ci
{
	protected $s__foto_path;
	protected $datos_personas_temp;
	protected $datos_fotos_temp;
	protected $datos_docs_temp;
	protected $datos_contacto_temp;
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';

	//-----------------------------------------------------------------------------------
	//---- PANTALLA PERSONA 	
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_personas ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_personas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_personas($datos);			
		} catch (guarani_error $e) {
			$this->datos_personas_temp = $datos;
			throw $e;
		}
	}

	function conf__form_personas(toba_ei_formulario $form)
	{	
		if (isset($this->datos_personas_temp)) {
			$this->controlador()->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_personas_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_personas());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_fotos -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_fotos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_fotos($datos);
		} catch (guarani_error $e) {
			$this->datos_fotos_temp = $datos;
			throw $e;
		}
	}

	function evt__form_fotos__eliminar_foto()
	{
		$this->cn()->entidad()->eliminar_datos_fotos();
		$this->eliminar_foto_tmp();

		if (isset($this->datos_fotos_temp)) {
			unset($this->datos_fotos_temp);
		}
	}

	function conf__form_fotos(toba_ei_formulario $form)
	{
		if (isset($this->datos_fotos_temp)) {
			$this->controlador()->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_fotos_temp);
		} else {
			$persona = $this->cn()->entidad()->get_datos_personas();
			$datos = $this->cn()->entidad()->get_datos_fotos();

			//-- Se necesita el path físico y la url de una archivo temporal que va a contener la imagen.
			if (isset($datos['blob'])) {
				$fp_imagen = $datos['blob'];
				$temp_nombre = md5(uniqid(time()));
				$temp_archivo = toba::proyecto()->get_www_temp($temp_nombre);

				//-- Se pasa el contenido al archivo temporal
				$temp_fp = fopen($temp_archivo['path'], 'w');
				stream_copy_to_stream($fp_imagen, $temp_fp);
				fclose($temp_fp);
				$this->s__foto_path = $temp_archivo['path'];

				$tamaño = round(filesize($temp_archivo['path']) / 1024);
				$datos['imagen'] = '<br/><br/> Tamaño: ' . $tamaño . ' KB';
			} else {
				$form->evento('eliminar_foto')->ocultar();
				$temp_nombre = (isset($persona['sexo']) && $persona['sexo'] == persona::sexo_femenino) ? 'usuario_femenino.png' : 'usuario_masculino.png';
				$temp_archivo = toba::proyecto()->get_www("img/$temp_nombre");
			}

			$title = "";
			if (isset($persona['apellido']) && isset($persona['nombres'])) {
				$title = "{$persona['apellido']}, {$persona['nombres']}";
			}
			//-- Se muestra la imagen temporal
			$datos['imagen_vista_previa'] = "<img src='{$temp_archivo['url']}' title='$title' style='border:2px solid black'>";

			$form->set_datos($datos);
		}
	}

	function eliminar_foto_tmp()
	{
		if (isset($this->s__foto_path)) {
			unlink($this->s__foto_path);
			unset($this->s__foto_path);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_documentos ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_documentos__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_documento($seleccion);
		$this->set_pantalla('pantalla_edicion_documento');
	}

	function conf__cuadro_documentos(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_documentos();

		$formateo = new toba_formateo('html');
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['tipo_pais_doc'] = $valor['tipo_nombre'] . ' (' . $valor['pais_nombre'] . ')';
			if ($valor['tipo_documento'] == persona::doc_CUIL) {
				$datos[$clave]['nro_documento'] = $formateo->formato_cuit($valor['nro_documento']);
			}
		}

		$etiqueta = guarani_etiquetas::get_tabla('mdp_personas_documentos');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_documentos__agregar($datos)
	{
	$this->set_pantalla('pantalla_edicion_documento');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_contactos -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_contactos__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_contacto($seleccion);
		$this->set_pantalla('pantalla_edicion_contacto');
	}

	function conf__cuadro_contactos(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_contactos();

		foreach ($datos as $clave => $valor) {
			$tipo_contacto = $valor['contacto_tipo'];
			$descripcion = '';
			if (persona::es_contacto_telefonico($tipo_contacto)) {
				$descripcion = '(' . $valor['telefono_codigo_area'] . ') ' . $valor['telefono_numero'];
				if (persona::es_contacto_telefono_cel($tipo_contacto)) {
					$datos[$clave]['contacto_tipo_nombre'] .= isset($valor['celular_compania_nombre']) && !is_null($valor['celular_compania_nombre']) ? ' (' . $valor['celular_compania_nombre'] . ')' : '';
				}
			} elseif (persona::es_contacto_mail($tipo_contacto)) {
				$descripcion = $valor['email'];
			} elseif ($tipo_contacto == persona::otro_contacto || $tipo_contacto == persona::twitter || $tipo_contacto == persona::facebook || $tipo_contacto == persona::instagram) {
				$descripcion = $valor['otros_contactos'];
			}
			$datos[$clave]['contacto_descripcion'] = $descripcion;
		}

		$etiqueta = guarani_etiquetas::get_tabla('mdp_personas_contactos');
		$eof_mensaje = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural')));

		$cuadro->set_eof_mensaje($eof_mensaje);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_contactos__agregar($datos)
	{
		$this->set_pantalla('pantalla_edicion_contacto');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCUMENTOS 
	//-----------------------------------------------------------------------------------

	function conf__pantalla_edicion_documento(toba_ei_pantalla $pantalla)
	{
		$datos_persona = $this->cn()->entidad()->get_datos_personas();
		$this->controlador->controlador()->evento('cancelar')->desactivar();
		$this->controlador->controlador()->evento('guardar')->desactivar();
		if (!$this->cn()->entidad()->es_nueva()) {
			$this->controlador->controlador()->evento('eliminar')->desactivar();
		}
		if ($this->cn()->entidad()->hay_documento_seleccionado()) {
			$etiqueta = guarani_etiquetas::get_tabla('mdp_personas_documentos');
			$this->dep('form_documentos')->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_documentos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_documentos(toba_ei_formulario $form)
	{
		$datos_persona = $this->cn()->entidad()->get_datos_personas();
		if ($datos_persona['pais_origen']) {
			$form->set_datos_defecto(array('pais_documento' => $datos_persona['pais_origen']));
		}
		if (isset($this->datos_docs_temp)) {
			$form->set_datos($this->datos_docs_temp);
		} else {
			$datos_documento = $this->cn()->entidad()->get_datos_documento_seleccionado();
			$form->set_datos($datos_documento);
		}
	}

	function evt__form_documentos__alta($datos)
	{
		try {
			$this->cn()->entidad()->nuevo_documento($datos);
			$this->evt__form_documentos__cancelar();
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	function evt__form_documentos__baja()
	{
		$this->cn()->entidad()->eliminar_documento_seleccionado();
		$this->evt__form_documentos__cancelar();
	}

	function evt__form_documentos__modificacion($datos)
	{
		$seleccionado = $this->cn()->entidad()->get_datos_documento_seleccionado() ?? NULL;
		try {
			if(isset($seleccionado)){				
				if($datos['pais_documento'] != $seleccionado['pais_documento'] || $datos['tipo_documento'] != $seleccionado['tipo_documento'] || $datos['nro_documento'] != $seleccionado['nro_documento'] ){
					if($this->usa_renaper() == comunes::si){
						//Todos los documentos le coloco N, pierde la validación con renaper
						$documentos = $this->cn()->entidad()->dep('datos')->tabla('documentos')->get_filas();
						foreach ($documentos as $key => $documento) {
							$this->cn()->entidad()->dep('datos')->tabla('documentos')->set_fila_columna_valor($key,'validado_con_renaper',comunes::no);
							$datos['validado_con_renaper'] = comunes::no;
						}
					}					
					$this->cn()->entidad()->set_datos_documento($datos);				
				}
				$this->evt__form_documentos__cancelar();
			}else{
				$this->cn()->entidad()->validar_datos_documentos($datos);
				$this->datos_docs_temp = $datos;
			}
		} catch (guarani_error $e) {
			$this->datos_docs_temp = $datos;
			throw $e;
		}		
	}

	function evt__form_documentos__cancelar()
	{
		$this->cn()->entidad()->deseleccionar_documento();
		unset($this->datos_docs_temp);
		$this->set_pantalla('personas');
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__get_pais_localidad($params, toba_ajax_respuesta $respuesta)
	{
		$localidad = $params[0];
		$pais = toba::consulta_php('co_mug')->get_localidad_pais($localidad);
		if ($pais == '') {
			$respuesta->set(array('pais' => null));
		} else {
			$respuesta->set(array('pais' => $pais['valor']));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA CONTACTOS 
	//-----------------------------------------------------------------------------------

	function conf__pantalla_edicion_contacto(toba_ei_pantalla $pantalla)
	{
		$this->controlador->controlador()->evento('cancelar')->desactivar();
		$this->controlador->controlador()->evento('guardar')->desactivar();
		if (!$this->cn()->entidad()->es_nueva()) {
			$this->controlador->controlador()->evento('eliminar')->desactivar();
		}
		if ($this->cn()->entidad()->hay_contacto_seleccionado()) {
			$etiqueta = guarani_etiquetas::get_tabla('mdp_personas_contactos');
			$this->dep('form_contactos')->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('el'))));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_contactos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_contactos(toba_ei_formulario $form)
	{
		$form->ef('telefono_codigo_area')->set_expreg(comunes::er_telefono_codigo_area);
		$form->ef('email')->set_expreg(comunes::er_email);

		if (isset($this->datos_contacto_temp)) {
			$form->set_datos($this->datos_contacto_temp);
		} else {
			$datos_contacto = $this->cn()->entidad()->get_datos_contacto_seleccionado();
			$form->set_datos($datos_contacto);
		}

		if ($this->cn()->entidad()->hay_contacto_seleccionado()) {
			$form->set_solo_lectura(array('contacto_tipo'));
		}
	}

	function evt__form_contactos__alta($datos)
	{
		try {
			$this->cn()->entidad()->nuevo_contacto($datos);
			$this->evt__form_contactos__cancelar();
		} catch (guarani_error $e) {
			$this->datos_contacto_temp = $datos;
			throw $e;
		}
	}

	function evt__form_contactos__baja()
	{
		$this->cn()->entidad()->eliminar_contacto_seleccionado();
		$this->evt__form_contactos__cancelar();
	}

	function evt__form_contactos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_contacto($datos);
			$this->evt__form_contactos__cancelar();
		} catch (guarani_error $e) {
			$this->datos_contacto_temp = $datos;
			throw $e;
		}
	}

	function evt__form_contactos__cancelar()
	{
		$this->cn()->entidad()->deseleccionar_contacto();
		unset($this->datos_contacto_temp);
		$this->set_pantalla('personas');
	}

	//--------------------------------------------------------------------------
	
	/**
	 * Retorna los tipos de contacto disponibles para una persona.
	 * Se tiene en cuenta que SÓLO se puede ingresar:
	 *  - UN Mail Institucional y 
	 *  - UN Mail Primario
	 * 
	 * @return array
	 */
	function get_tipos_contactos()
	{
		$datos_contactos = $this->cn()->entidad()->get_datos_contactos();

		$excluir_mp = false;
		$excluir_mi = false;

		foreach ($datos_contactos as $contacto) {
			if ($contacto['contacto_tipo'] == persona::mail_institucional) {
				$datos_contacto = $this->cn()->entidad()->get_datos_contacto_seleccionado();
				if (!isset($datos_contacto['contacto_tipo']) || ($datos_contacto['contacto_tipo'] != persona::mail_institucional)) {
					$excluir_mi = true;
				}
			}
			if ($contacto['contacto_tipo'] == persona::mail_primario) {
				$datos_contacto = $this->cn()->entidad()->get_datos_contacto_seleccionado();
				if (!isset($datos_contacto['contacto_tipo']) || ($datos_contacto['contacto_tipo'] != persona::mail_primario)) {
					$excluir_mp = true;
				}
			}
		}

		$datos = toba::consulta_php('co_personas')->get_tipos_contactos();

		$salida = array();
		foreach ($datos as $dato) {
			if (!(($excluir_mp && $dato['valor'] == persona::mail_primario) ||
					($excluir_mi && $dato['valor'] == persona::mail_institucional))) {
				$salida[] = $dato;
			}
		}

		return $salida;
	}
	function get_tipos_documentos_x_pais($pais){
		$datos = toba::consulta_php('co_personas')->get_datos_tipos_documentos_x_pais($pais);
		return $datos;
	}

	/**
	 * Configuración parametrizada que determina si utiliza Renaper
	 * 
	 * @return string 'S' / 'N'
	 */
	function usa_renaper()
	{
		return guarani::parametros()->get_valor('mat_validacion_renaper');
	}

	function get_datos_documentos()
	{	
		$documento = $this->cn()->entidad()->get_datos_documentos();
		foreach ($documento as $clave => $valor) {
			if ($valor['tipo_documento'] == persona::doc_CUIL || $valor['tipo_documento'] == persona::doc_dni) {
				return $valor['validado_con_renaper'] ?? comunes::no;
			}
		}
		return comunes::no;	
	}

	/**
	 * Resetea los valores de mdp_personas_documentos.validado_con_renaper
	 */
	function ajax__cambios_campos_renaper($parametros, toba_ajax_respuesta $respuesta)
	{
		if($parametros['cambio_renaper']){
			$documentos = $this->cn()->entidad()->dep('datos')->tabla('documentos')->get_filas();
			foreach ($documentos as $key => $documento) {
				$this->cn()->entidad()->dep('datos')->tabla('documentos')->set_fila_columna_valor($key,'validado_con_renaper',comunes::no);
			}
		}
	}

}
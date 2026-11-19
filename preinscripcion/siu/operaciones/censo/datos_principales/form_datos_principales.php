<?php

namespace siu\operaciones\censo\datos_principales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\preinscripcion_form;
use siu\modelo\opciones;
use siu\modelo\g3\g3;
use siu\modelo\util;
use siu\modelo\comunes;

class form_datos_principales extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_principales';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('datos_principales'));
	}

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar()
	{
		$form_valido = parent::validar();
		$form_valido = $form_valido & $this->validar_cuil();
		$form_valido = $form_valido & $this->validar_fecha_nacimiento();
		$form_valido = $form_valido & $this->validar_fechas_visa();
		$form_valido = $form_valido & $this->validar_fechas_residencia();
		$form_valido = $form_valido & $this->validar_documento();
		$form_valido = $form_valido & $this->validar_nro_documento();
		$form_valido = $form_valido & $this->validar_pasaporte();
		return $form_valido;
	}

	//---- Validaci�n de CUIL --------------------------------------------------

	/**
	 * Se valida que el cuil sea v�lido.
	 */
	protected function validar_cuil()
	{
		$valido = true;
		$datos = $this->get_datos();
		if (!empty($datos['numero_cuil'])) {
			$cuil = $datos['numero_cuil'];
			if (!comunes::validarCUIT($cuil)) {
				$this->agregar_error('numero_cuil', kernel::traductor()->trans('form_validacion_error.error_cuil'));
				$valido = false;
			}
		}
		return $valido;
	}
	
	//---- Fin validaci�n CUIL -------------------------------------------------

	/**
	 * Se valida fecha de nacimiento.
	 */
	protected function validar_fecha_nacimiento()
	{
		$valido = true;
		$datos = $this->get_datos();
		if (!empty($datos['fecha_nacimiento'])) {
			$fecha_nacimiento = $datos['fecha_nacimiento'];

			// Se valida que el a�o de nacimiento sea posterior a 1900
			$fecha_partes = explode('/', $fecha_nacimiento);

			if (isset($fecha_partes[2]) && $fecha_partes[2] <= 1900) {
				$this->agregar_error('fecha_nacimiento', kernel::traductor()->trans('error_fecha_antigua'));
				$valido = false;
			}

			// Se valida la fecha de nacimiento no sea posterior a la actual
			if ($valido) {
				if (fecha_es_mayor_a_fecha_actual(convertir_fecha_formato_base($fecha_nacimiento))) {
					$this->agregar_error('fecha_nacimiento', kernel::traductor()->trans('error_fecha_posterior'));
					$valido = false;
				} else {
					$edad = comunes::calcular_edad($fecha_nacimiento);
					// Si es menor de 25 le pongo el campo 'es_mayor_25_sin_secundario' en 'N'
					if ($edad < 25) {
						$this->set_dato('es_mayor_25_sin_secundario', comunes::no);
					}
				}
			}
		}
		return $valido;
	}

	protected function validar_fechas_visa()
	{
		$valido = true;
		$datos = $this->get_datos();
		$fecha_otorgamiento = (!empty($datos['otorgamiento_visa'])) ? convertir_fecha_formato_base($datos['otorgamiento_visa']) : '';
		$fecha_vencimiento = (!empty($datos['vencimiento_visa'])) ? convertir_fecha_formato_base($datos['vencimiento_visa']) : '';

		// La fecha de vencimiento no puede ser menor a la fecha actual
		if (!empty($fecha_vencimiento)) {
			if (fecha_es_menor_a_fecha_actual($fecha_vencimiento)) {
				$valido = false;
				$this->agregar_error('vencimiento_visa', kernel::traductor()->trans('error_fecha_vencimiento_hoy'));
			}
		}

		if ($valido) {
			// La fecha de vencimiento no puede ser menor a la fecha de otorgamiento
			if (!empty($fecha_otorgamiento) && !empty($fecha_vencimiento)) {
				if (!fecha_es_menor($fecha_otorgamiento, $fecha_vencimiento)) {
					$valido = false;
					$this->agregar_error('vencimiento_visa', kernel::traductor()->trans('error_fecha_vencimiento_otorg'));
				}
			}
		}

		return $valido;
	}

	protected function validar_fechas_residencia()
	{
		$valido = true;
		$datos = $this->get_datos();

		$tipo_doc = kernel::persona()->datos()->get_tipo_doc();
		$tipo_residencia = $datos['residencia'];

		if ($tipo_residencia != opciones::NO_SELECCION) {
			$fecha_otorgamiento = (!empty($datos['fecha_respre_otorg'])) ? convertir_fecha_formato_base($datos['fecha_respre_otorg']) : '';
			$fecha_vencimiento = (!empty($datos['fecha_respre_venc'])) ? convertir_fecha_formato_base($datos['fecha_respre_venc']) : '';

			if (!empty($fecha_otorgamiento)) {
				// Se valida que el a�o de la fecha de otorgamiento sea posterior a 1900
				$fecha_partes = explode('-', $fecha_otorgamiento);

				if (isset($fecha_partes[0]) && $fecha_partes[0] <= 1900) {
					$this->agregar_error('fecha_respre_otorg', kernel::traductor()->trans('error_fecha_antigua'));
					$valido = false;
				}

				// Se valida la fecha de otorgamiento no sea posterior a la actual
				if ($valido) {
					if (fecha_es_mayor_a_fecha_actual($fecha_otorgamiento)) {
						$this->agregar_error('fecha_respre_otorg', kernel::traductor()->trans('error_fecha_posterior'));
						$valido = false;
					}
				}
			}

			// La fecha de vencimiento no puede ser menor a la fecha actual
			if (!empty($fecha_vencimiento)) {
				if (fecha_es_menor_a_fecha_actual($fecha_vencimiento)) {
					$valido = false;
					$this->agregar_error('fecha_respre_venc', kernel::traductor()->trans('error_fecha_vencimiento_hoy'));
				}
			}

			if ($valido) {
				// La fecha de vencimiento no puede ser menor a la fecha de otorgamiento
				if (!empty($fecha_otorgamiento) && !empty($fecha_vencimiento)) {
					if (!fecha_es_menor($fecha_otorgamiento, $fecha_vencimiento)) {
						$valido = false;
						$this->agregar_error('fecha_respre_venc', kernel::traductor()->trans('error_fecha_vencimiento_otorg'));
					}
				}
			}

			if ($valido) {
				if (($tipo_residencia == 2) || ($tipo_residencia == 3)) {
					// Para residencia precaria y temporal, se requiere fecha de vencimiento.
					if (empty($fecha_vencimiento)) {
						$valido = false;
						$this->agregar_error('fecha_respre_venc', kernel::traductor()->trans('error_fecha_vencimiento_req'));
					}
				}
			}
		}

		return $valido;
	}
        protected function validar_documento()
	{
		$documento_valido = true;

		if ((!$this->tiene_error('nacionalidad')) && (!$this->tiene_error('pais_documento')) && (!$this->tiene_error('tipo_documento'))) {
			$nacion = $this->datos['nacionalidad'];
			$paisdoc = $this->datos['pais_documento'];
			$tipodoc = $this->datos['tipo_documento'];

			// Validaciones de Pa�s del Documento
			if ($nacion != 2 && $paisdoc != 54) {
				$mensaje = kernel::traductor()->trans('error_pais_doc_1');
				$this->agregar_error('pais_documento', $mensaje);
				$documento_valido = false;
			}

			// Validaciones de Tipo de Documento_pais
			$entidad_tipo_doc = g3::entidad('tipos_documentos_pais')->get_entidad($this->datos['pais_documento']."_".$this->datos['tipo_documento']);
			if (empty($entidad_tipo_doc)){
				$mensaje = kernel::traductor()->trans('error_pais_doc_0');
				$this->agregar_error('pais_documento', $mensaje);
				$this->agregar_error('tipo_documento', $mensaje);
				$documento_valido = false;
			}
			
		}

		return $documento_valido;
	}

	function validar_nro_documento()
	{
		$entidad_tipo_doc = g3::entidad('tipos_documentos_pais')->get_entidad($this->datos['pais_documento']."_".$this->datos['tipo_documento']);
		
		// Validaci�n en base a la expresi�n regular
		$valido = true;
		$exp_reg= '/' . $entidad_tipo_doc['exp_regular_validacion'] . '/';
		if (!preg_match($exp_reg, $this->datos['nro_documento'])) {
			// Documento inv�lido seg�n la expresi�n regular
			$valido = false;
			$this->agregar_error('nro_documento', $entidad_tipo_doc['exp_regular_mensaje']);
		}

		switch ($this->datos['tipo_documento']) {
			case comunes::TIPO_DOCUMENTO_DNI:
				// Valida que el DNI sea mayor a 1 mill�n y menor a 100 millones.
				if (($this->datos['nro_documento'] < comunes::NRO_DOCUMENTO_DNI_DNT_MIN) || ($this->datos['nro_documento'] > comunes::NRO_DOCUMENTO_DNI_DNT_MAX)) {
					$mensaje = kernel::traductor()->trans('nro_documento.dni.error');
					$this->agregar_error('nro_documento', $mensaje);
					$valido = false;
				}
				break;
			case comunes::TIPO_DOCUMENTO_CUIL_CUIT:
				// Valida que el CUIL/CUIT sea valido.
				if (!comunes::validarCUIT($this->datos['nro_documento'])) {
					$mensaje = kernel::traductor()->trans('nro_documento.cuil.error');
					$this->agregar_error('nro_documento', $mensaje);
					$valido = false;
				}
				break;
		}
                
		return $valido;
	}

	//---- Validaci�n de CUIL --------------------------------------------------

	/**
	 * Se valida que el cuil sea v�lido.
	 */
	protected function validar_pasaporte()
	{
		$valido = true;
		$datos = $this->get_datos();
		//validacion de pasaporte
		$entidad_tipo_pasaporte = g3::entidad('tipos_documentos_pais')->get_entidad($this->datos['pais_documento']."_90");
		klog2('$entidad_tipo_pasaporte', $entidad_tipo_pasaporte);
		$exp_reg= '/' . $entidad_tipo_pasaporte['exp_regular_validacion'] . '/';
		if (!preg_match($exp_reg, $datos['nro_pasap'])) {
			// Documento inv�lido seg�n la expresi�n regular
			$valido = false;
			$this->agregar_error('nro_pasap', $entidad_tipo_pasaporte['exp_regular_mensaje']);
		}
		if ($datos['nro_pasap']== '0') {
				$this->agregar_error('nro_pasap', kernel::traductor()->trans('form_validacion_error.vacio'));
				$valido = false;
			
		}
		return $valido;
	}

	//--------------------------------------------------------------------------
	//---- Definici�n de formulario --------------------------------------------
	//--------------------------------------------------------------------------

	protected function generar_definicion()
	{
		//---- Datos Personales ------------------------------------------------
		$this->set_campo('apellido', array(
			form::label => 'apellido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombres', array(
			form::label => 'nombres',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));
        /*$this->set_campo('apellido_elegido', array(
			form::label => 'apellido_elegido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));*/

		$this->set_campo('nombres_elegido', array(
			form::label => 'nombres_elegido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::ayuda => kernel::traductor()->trans('nombres_elegido_ayuda'),
			form::largo => 60
		));

        $this->set_campo('pais_documento', array(
			form::label => 'pais_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
            form::valor_default => 'pais_documento',
			form::multi_options => util::opciones()->get_paises_nacionalidad(),
		));
                
		$this->set_campo('tipo_documento', array(
			form::label => 'tipo_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::valor_default => 'tipo_documento',
			form::multi_options => util::opciones()->get_tipos_documentos_pais(),
			
		));
                
		$this->set_campo('nro_documento', array(
			form::label => 'nro_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => false,
		));

		$this->set_campo('nacionalidad_desc', array(
			form::label => 'nacionalidad_desc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
		));
		
		$this->set_campo('nacionalidad', array(
			form::label => '',
			form::filtro => validador::TIPO_INT,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'hidden'),
			form::readonly => true,
		));

		$this->set_campo('e_mail', array(
			form::label => 'e_mail',
			form::filtro => validador::TIPO_MAIL,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::largo => 100
		));

		$this->set_campo('numero_cuil', array(
			form::label => 'numero_cuil',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 15
		));

		$this->set_campo('sexo', array(
			form::label => 'sexo',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_generos(),
		));
        
        $this->set_campo('identidad_genero', array(
			form::label => 'identidad_genero',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_identidad_genero(),
		));
                
        $this->set_campo('identidad_genero_otro', array(
			form::label => 'identidad_genero_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));
			   
		$this->set_campo('celular_caracteristica', array(
			form::label => 'celular_caracteristica',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 4,
			form::clase_css => 'input-mini',
		));

		$this->set_campo('celular_valor', array(
			form::label => 'celular_valor',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 8,
			form::clase_css => 'input-small',
		));

		//---- Datos de nacimiento ---------------------------------------------
		$this->set_campo('fecha_nacimiento', array(
			form::label => 'fecha_nacimiento',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));
		
		$this->set_campo('es_mayor_25_sin_secundario', array(
			form::label => '',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::readonly => true,
		));

		$this->set_campo('loc_nacimiento', array(
			form::label => 'loc_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('loc_nac_descr', array(
			form::label => 'loc_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::clase_css => 'input-localidad',
		));

		$this->set_campo('pais_nacimiento', array(
			form::label => 'pais_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(false),
			form::clase_css => 'select-pais'
		));

		//---- C�dula de identidad ---------------------------------------------
		$this->set_campo('ent_emisora_ci', array(
			form::label => 'ent_emisora_ci',
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_entidades_emisoras_ci(),
			form::valor_default => '',
		));

		$this->set_campo('nro_ci', array(
			form::label => 'nro_ci',
			form::filtro => validador::TIPO_ALPHANUM,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 15
		));

		//---- Pasaporte -------------------------------------------------------
		$this->set_campo('pais_emisor_pasap', array(
			form::label => 'pais_emisor_pasap',
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(),
			form::valor_default => '',
		));

		$this->set_campo('nro_pasap', array(
			form::label => 'nro_pasap',
			form::filtro => validador::TIPO_ALPHANUM,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 15
		));

		//---- Visa ------------------------------------------------------------
		$this->set_campo('tipo_visa', array(
			form::label => 'tipo_visa',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_tipos_visa(),
			form::valor_default => '',
			form::largo => 20
		));

		$this->set_campo('otorgamiento_visa', array(
			form::label => 'otorgamiento_visa',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('vencimiento_visa', array(
			form::label => 'vencimiento_visa',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		//---- Residencia ------------------------------------------------------
		$this->set_campo('residencia', array(
			form::label => 'residencia',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_residencia(),
			form::valor_default => '',
		));

		$this->set_campo('fecha_respre_otorg', array(
			form::label => 'fecha_respre_otorg',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date-residencia-otorg input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('fecha_respre_venc', array(
			form::label => 'fecha_respre_venc',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date-residencia-venc input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		//---- Pueblos originarios ---------------------------------------------
		$this->set_campo('pertenece_pueblo_originario', array(
			form::label => 'pertenece_pueblo_originario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_si_no_no_desea_contestar(),
			form::valor_default => '',
		));

		$this->set_campo('pueblo_originario', array(
			form::label => 'pueblo_originario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_pueblos_originarios(),
			form::valor_default => '',
		));

		$this->set_campo('pueblo_originario_otro', array(
			form::label => 'pueblo_originario_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));
        
	}

	function control_group_localidad($campo_localidad, $campo_descr, $campo_pais)
	{
		if ($this->def_visible($campo_localidad)) {
			$label = $this->label($campo_localidad);

			if (($this->get_obligatorio($campo_localidad)) || ($this->def_obligatorio($campo_localidad))) {
				$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
				$label = str_replace('</label>', ' (*)</label>', $label);
			}

			$elemento_localidad = $this->elemento($campo_localidad);
			$elemento_pais = $this->elemento($campo_pais);
			$elemento_descr = $this->elemento($campo_descr);

			$tiene_error = $this->tiene_error($campo_localidad) || $this->tiene_error($campo_descr);
			$clase_error = ($tiene_error) ? 'error' : '';

			$html = "<div class='control-group $clase_error'>";
			$html .= $label;
			$html .= $elemento_localidad;
			$html .= $elemento_pais;
			$html .= $elemento_descr;
			$html .= $this->ayuda($campo_localidad);
			if ($tiene_error) {
				$html .= "<br/>";
				$html .= "<span class='help-inline'>{$this->error($campo_descr)}</span>";
			}
			$html .= "</div>";

			return $html;
		}
	}

}
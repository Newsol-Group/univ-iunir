<?php
namespace siu\operaciones\alta_usuario_cursos\crear_usuario;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\transacciones\usuario_cursos;
use siu\modelo\entidades\parametro;

class form_crear_usuario extends form
{
	/**
	 * @var \siu\modelo\transacciones\usuario_cursos
	 */
	const validado_con_renaper = '__alta.uc_validado_con_renaper__';
	const usuario_var = '__alta.uc_usuario__';
	const renaper_persona = '__alta.uc_renaper_persona__';
	protected $modelo;

	function __construct()
	{
		$this->modelo = guarani::usuario_cursos();
	}

	function get_id_html()
	{
		return 'form_crear_usuario';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('alta_usuario_cursos', 'carga_datos_personales');
	}

	protected function generar_definicion()
	{
		//---- Datos personales ------------------------------------------------

		$this->set_campo('apellido', array(
			form::label => 'alta_uc.apellido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombres', array(
			form::label => 'alta_uc.nombres',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));


		$this->set_campo('nombres_elegido', array(
			form::label => 'alta_uc.nombres_elegido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60,
			form::ayuda =>  kernel::traductor()->trans('ayuda_nombre_elegido'),
		));

		//Género
		$this->set_campo('sexo', array(
			form::label => 'alta_uc.sexo',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__generos(), //opciones::get_generos()
		));

		$this->set_campo('identidad_genero', array(
			form::label => 'alta_uc.identidad_genero',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => guarani::datos_censales()->opciones__identidad_genero(),
			form::valor_default => usuario_cursos::NO_SELECCION,
		));//Identidad de Género y Diversidad
		
		$this->set_campo('identidad_genero_otro', array(
			form::label => 'alta_uc.identidad_genero_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));

		$this->set_campo('nacionalidad', array(
			form::label => 'alta_uc.nacionalidad',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__nacionalidades(),
			form::valor_default => usuario_cursos::NO_SELECCION,
		));

		$this->set_campo('pais_documento', array(
			form::label => 'alta_uc.pais_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__paises(),
			form::valor_default => usuario_cursos::NO_SELECCION,
		));

		$this->set_campo('tipo_documento', array(
			form::label => 'alta_uc.tipo_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__tipos_documentos(),
			form::valor_default => usuario_cursos::NO_SELECCION,
		));

		$this->set_campo('nro_documento', array(
			form::label => 'alta_uc.nro_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20
		));

		//---- Datos de nacimiento ---------------------------------------------

		$this->set_campo('fecha_nacimiento', array(
			form::label => 'alta_uc.fecha_nacimiento',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y', 'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('localidad', array(
			form::label => 'alta_uc.loc_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('localidad_descr', array(
			form::label => 'alta_uc.loc_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::clase_css => 'input-localidad',
		));

		$this->set_campo('pais', array(
			form::label => 'alta_uc.pais_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__paises(),
			form::valor_default => usuario_cursos::NO_SELECCION,
			form::clase_css => 'select-pais'
		));

		//---- Datos del usuario -----------------------------------------------

		$this->set_campo('email', array(
			form::label => 'alta_uc.email',
			form::obligatorio => true,
			form::filtro => validador::TIPO_MAIL,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));

		$this->set_campo('email_rep', array(
			form::label => 'alta_uc.email_rep',
			form::obligatorio => true,
			form::filtro => validador::TIPO_MAIL,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));

		$this->set_campo('usuario', array(
			form::label => 'alta_uc.usuario',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100,
			form::readonly => false
		));

		$this->set_campo('password', array(
			form::label => 'alta_uc.clave',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'password'),
			form::largo => 40
		));

		$this->set_campo('rpassword', array(
			form::label => 'alta_uc.repetir_clave',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'password'),
			form::largo => 40
		));

		$this->set_campo('propuesta', array(
			form::label => 'alta_uc.doc.propuesta',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::clase_css	=> 'input-xxlarge',
			form::multi_options => $this->modelo->opciones__propuestas_cursos(),
		));
        
         if (kernel::proyecto()->valida_con_renaper() == \comunes::si){
            //sobre la fecha de nacimiento, el domicilio y CUIL de la persona
            $this->set_campo('nro_tramite_dni', array(
                form::label => 'alta_uc.nro_tramite_dni',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'text'),
                form::largo => 11
            ));

            //---- Domicilio de procedencia ----------------------------------------
            $this->set_campo('periodo_lectivo_calle', array(
                form::label => '',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'text'),
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 50
            ));
            $this->set_campo('periodo_lectivo_numero', array(
                form::label => '',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 10,
                form::clase_css => 'input-medium',
            ));
            $this->set_campo('periodo_lectivo_piso', array(
                form::label => '',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 3,
                form::clase_css => 'input-medium',
            ));
            $this->set_campo('periodo_lectivo_departamento', array(
                form::label => '',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 5,
                form::clase_css => 'input-medium',
            ));
            $this->set_campo('periodo_lectivo_codigo_postal', array(
                form::label => '',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::readonly => false,
                form::clase_css => 'input-small',
                form::largo => 15
            ));
            $this->set_campo('periodo_lectivo_barrio', array(
                form::label => '',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 60
            ));
            $this->set_campo('numero_cuil', array(
                form::label => 'alta_uc.cuil',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 20,
                form::clase_css => 'input-medium',
            ));
            $this->set_campo('validado_con_renaper', array(
                form::label => 'alta_uc.validado_con_renaper',
                form::filtro => validador::TIPO_TEXTO,
                form::obligatorio => false,
                form::elemento => array('tipo' => 'hidden'),
                form::largo => 1
            ));
         }
        
	}

	//--------------------------------------------------------------------------
	//---- Generación de html --------------------------------------------------
	//--------------------------------------------------------------------------

	function control_group($nombre, $clase_control_group = '')
	{
		if (!$this->check_activo($nombre)) {
			return '';
		}
		if ($this->get_elemento_html_tipo($nombre) == 'hidden') {
			return $this->elemento($nombre);
		}

		$label = $this->label($nombre);
		if ($this->get_obligatorio($nombre)) {
			$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
			$label = str_replace('</label>', ' (*)</label>', $label);
		}

		$elemento = $this->elemento($nombre);
		$tiene_error = $this->tiene_error($nombre);
		$clase_error = ($tiene_error) ? 'error' : '';

		$html = "<div class='control-group $clase_control_group $clase_error'>";
		$html .= $label;
		$html .= $elemento;
		$html .= $this->ayuda($nombre);
		if ($tiene_error) {
			$html .= "<span class='help-inline'>{$this->error($nombre)}</span>";
		}
		$html .= "</div>";

		return $html;
	}

	function control_group_localidad($campo_localidad, $campo_descr, $campo_pais)
	{
		$label = $this->label($campo_localidad);

		if ($this->get_obligatorio($campo_localidad)) {
			$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
			$label = str_replace('</label>', ' (*)</label>', $label);
		}

		$elemento_localidad = $this->elemento($campo_localidad);
		$elemento_pais = $this->elemento($campo_pais);
		$elemento_descr = $this->elemento($campo_descr);

		$tiene_error = $this->tiene_error($campo_localidad) || $this->tiene_error($campo_descr);
		$clase_error = ($tiene_error) ? 'class="control-group error"' : '';

		$html = "<div $clase_error>";
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

	//--------------------------------------------------------------------------
	//---- Validaciones --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar()
	{
        $esta_seteada = kernel::sesion()->esta_seteada(self::validado_con_renaper);
        
        if($esta_seteada && kernel::sesion()->get(self::validado_con_renaper) == \comunes::si){
            $apellido = kernel::sesion()->get(self::renaper_persona)['lastNames'];
            $nombres = kernel::sesion()->get(self::renaper_persona)['names'];
            $this->set_dato('apellido', $apellido);
            $this->set_dato('nombres', $nombres);
        }
		// Si la carga de datos es correcta, se procede a realizar las validaciones particulares
		$usuario_valido = $this->validar_usuario();
		$fecha_nacimiento_valido = $this->validar_fecha_nacimiento();
		$mail_repetido_valido = $this->validar_emails();
		$clave_valida = $this->validar_clave();
		$documento_valido = $this->validar_documento();
		$nro_documento_valido = $this->validar_nro_documento();
                
		return parent::validar() && $usuario_valido && $fecha_nacimiento_valido && $mail_repetido_valido && $clave_valida && $documento_valido && $nro_documento_valido;
	}

	/**
	 * Se valida que el nombre de usuario no esté siendo usado en el sistema.
	 * 
	 * @return boolean
	 */
	protected function validar_usuario()
	{
		$usuario_ok = true;		

		$usuario = kernel::sesion()->esta_seteada(self::usuario_var) ? kernel::sesion()->get(self::usuario_var) : '';
		if($usuario){
			if (!\toba::consulta_php('co_usuarios')->validar_nombre_usuario_unico($usuario, null)) {
				$usuario_ok = false;
				$mensaje = kernel::traductor()->trans('alta_uc.error_usuario_repetido');
				$this->agregar_error('usuario', $mensaje);
			}else{
				//Este seteo se hace para evitar que cambien los valores por consola desde el navegador
				$this->set_dato('usuario', $usuario);
			}
		}else{
			$usuario_ok = false;
			$mensaje = \moodle_nucleo::msj_error_username;
			$this->agregar_error('usuario', $mensaje);
		}

		return $usuario_ok;
	}

	/**
	 * Se valida
	 *  - Que el año de nacimiento sea posterior a 1900
	 *  - Que la fecha de nacimiento no sea posterior a la fecha actual
	 *  - Que la persona tenga al menos 15 años
	 * @return boolean
	 */
	protected function validar_fecha_nacimiento()
	{
		$valido = true;
		$datos = $this->get_datos();
		if (!empty($datos['fecha_nacimiento'])) {
			$fecha_nacimiento = $datos['fecha_nacimiento'];

			// Se valida que el año de nacimiento sea posterior a 1900
			$fecha_partes = explode('/', $fecha_nacimiento);

			if (isset($fecha_partes[2]) && $fecha_partes[2] <= 1900) {
				$this->agregar_error('fecha_nacimiento', kernel::traductor()->trans('alta_uc.error_fecha_antigua'));
				$valido = false;
			}

			// Se valida la fecha de nacimiento no sea posterior a la actual
			if ($valido) {
				if (fecha_es_mayor_a_fecha_actual(convertir_fecha_formato_base($fecha_nacimiento))) {
					$this->agregar_error('fecha_nacimiento', kernel::traductor()->trans('alta_uc.error_fecha_posterior'));
					$valido = false;
				}
			}
		}
		return $valido;
	}

	/**
	 * Se valida que el mail ingresado y su repetición sean idénticos
	 * 
	 * @return boolean
	 */
	protected function validar_emails()
	{
		$email_ok = true;

		if ((!empty($this->datos['email'])) && (!empty($this->datos['email_rep']))) {
			$email = trim($this->datos['email']);
			$email_rep = trim($this->datos['email_rep']);
			if ($email != $email_rep) {
				// Se valida que las claves sean iguales
				$mensaje = kernel::traductor()->trans('alta_uc.error_emails_no_coinciden');
				$this->agregar_error('email', $mensaje);
				$this->agregar_error('email_rep', $mensaje);
				$email_ok = false;
			}
		}

		return $email_ok;
	}

	/**
	 * Se valida que la clave y su repetición sean idénticas, y se invoca a la
	 * ventana de extensión clave_permitida en la clase usuario_cursos
	 * 
	 * @return boolean
	 */
	protected function validar_clave()
	{
		$clave_valida = true;

		if ((!empty($this->datos['password'])) && (!empty($this->datos['rpassword']))) {
			$clave = trim($this->datos['password']);
			$rclave = trim($this->datos['rpassword']);

			if ($clave != $rclave) {
				// Se valida que las claves sean iguales
				$mensaje = kernel::traductor()->trans('alta_uc.error_clave_no_coinciden');
				$this->agregar_error('password', $mensaje);
				$this->agregar_error('rpassword', $mensaje);
				$clave_valida = false;
			} else if (!\usuario::validar_clave_permitida($clave)) {
				$clave_valida = false;
				$longitud = parametro::get_valor('login_clave_longitud_minima');
				$mensaje = kernel::traductor()->trans('alta_uc.error_clave_longitud', array('%1%' => $longitud));
				$this->agregar_error('password', $mensaje);
			}
		}

		return $clave_valida;
	}

	/**
	 * Se realizan las validaciones de documento especificadas para el alta de persona
	 * 
	 * @return boolean
	 */
	protected function validar_documento()
	{
		$documento_valido = true;

		if ((!$this->tiene_error('nacionalidad')) && (!$this->tiene_error('pais_documento')) && (!$this->tiene_error('tipo_documento'))) {
			$nacion = $this->datos['nacionalidad'];
			$paisdoc = $this->datos['pais_documento'];
			$tipodoc = $this->datos['tipo_documento'];
			//valido que el documento este dentro de la

			// Validaciones de País del Documento
			if ($nacion != 2 && $paisdoc != 54) {
				$mensaje = kernel::traductor()->trans('alta_uc.error_pais_doc_1');
				$this->agregar_error('pais_documento', $mensaje);
				$documento_valido = false;
			}
			$documentos_pais = \persona::get_tipos_documentos_x_pais();
		
			// Validaciones de Tipo de Documento
			$validacion = $documentos_pais[$tipodoc.'_'.$paisdoc] ?? null;
			if (empty($validacion)){
				$mensaje = kernel::traductor()->trans('alta_uc.error_pais_doc_0');
				$this->agregar_error('pais_documento', $mensaje);
				$this->agregar_error('tipo_documento', $mensaje);
				$documento_valido = false;
			}
		}

		return $documento_valido;
	}

	function validar_nro_documento()
	{
		$paisdoc = $this->datos['pais_documento'];
		$tipo_doc = $this->datos['tipo_documento'];
		$nro_doc = $this->datos['nro_documento'];
		$tipos_documento = \persona::get_tipos_documentos_x_pais();
		$expresion = $tipos_documento[$tipo_doc."_".$paisdoc]['exp_regular_validacion'] ?? '';
		$mensaje = $tipos_documento[$tipo_doc."_".$paisdoc]['exp_regular_mensaje'] ?? '';

		$es_valido = true;

		$patt = '/' . $expresion . '/';
		if (!preg_match($patt, $nro_doc)) {
			//Si el documento es invalido
			$es_valido = false;
			$this->agregar_error('nro_documento', $mensaje);
		}
		
		switch ($this->datos['tipo_documento']) {
			case \persona::doc_dni:
				// Valida que el DNI sea mayor a 1 millón y menor a 100 millones.
				if (($this->datos['nro_documento'] < \persona::NRO_DOCUMENTO_DNI_DNT_MIN) || ($this->datos['nro_documento'] > \persona::NRO_DOCUMENTO_DNI_DNT_MAX)) {
					$mensaje = kernel::traductor()->trans('alta_uc.dni.error');
					$this->agregar_error('nro_documento', $mensaje);
					$es_valido = false;
				}
				break;
			case \persona::doc_CUIL:
				// Valida que el CUIL/CUIT sea valido.
				if (!\persona::validarCUIT($this->datos['nro_documento'])) {
					$mensaje = kernel::traductor()->trans('alta_uc.cuil.error');
					$this->agregar_error('nro_documento', $mensaje);
					$es_valido = false;
				}
				break;
		}

		return $es_valido;
	}

	protected function validar_campo($nombre, $valor)
	{
		// http://framework.zend.com/manual/en/zend.form.elements.html#zend.form.elements.validators
		$filtro = $this->get_filtro($nombre);
		$filtro_params = $this->get_filtro_params($nombre);
		$obligatorio = $this->check_activo($nombre) && $this->get_obligatorio($nombre);
		$largo = $this->get_largo($nombre);

		$error_filtro = !validador::es_valido($valor, $filtro, $filtro_params);
		$error_largo = ($largo > 0) && mb_strlen($valor) > $largo;

		$error_vacio = $obligatorio && ((empty($valor) && $valor !== '0' && $valor !== 0) || (is_null($valor) || ($valor == usuario_cursos::NO_SELECCION)));
		$error_select = false;
		if ($this->get_elemento_html_tipo($nombre) == 'select' && $this->get_validar_select($nombre)) {
			// se hace el chequeo del validador inarray
			$error_select = !$this->zend_form->getElement($nombre)->isValid($valor);
		}

		$es_valido = !$error_filtro && !$error_largo && !$error_vacio && !$error_select;
		$msg = '';

		if (!$es_valido) {
			$msg = 'form_validacion_error.';
			if ($error_filtro) {
				$msg .= "filtro_$filtro";
			} elseif ($error_largo) {
				$msg .= 'largo';
			} elseif ($error_vacio) {
				$msg .= 'vacio';
			} elseif ($error_select) {
				$msg .= 'select';
			}
		}
		if (!$es_valido) {
			\klog("Error validación => $nombre:$msg");
		}
		return array($es_valido, $msg);
	}

}
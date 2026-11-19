<?php

namespace siu\operaciones\datos_censales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\operaciones\datos_censales\_comun\pagelet_con_form_dc;
use siu\operaciones\datos_censales\_comun\pagelet_con_edicion;
use SIU\Chulupi\nucleo\FinalizadorRequest;
use siu\modelo\transacciones\becas;

class controlador extends controlador_g3w2
{
	protected $seleccion;

	/**
	 * @return \siu\modelo\transacciones\datos_censales
	 */
	function modelo()
	{
		return guarani::datos_censales();
	}

	function ini()
	{
		$this->controlar_csrf_accion('guardar');
		$this->controlar_csrf_accion('guardar_edicion');
		$this->controlar_csrf_accion('guardar_datos_salud');
		$this->controlar_csrf_accion('guardar_preferencias');
	}

	public function __construct()
	{
		parent::__construct();
		$this->modelo()->controlar_tablas();
	}

	function accion__index()
	{
		$formulario = $this->load_form_activo();
		kernel::renderer()->add($formulario);
	}

	//--------------------------------------------------------------------------

	function datos_vigentes()
	{
		return $this->modelo()->datos_vigentes();
	}

	//--------------------------------------------------------------------------
	//---- Confirmación / control de faltantes ---------------------------------
	//--------------------------------------------------------------------------

	function accion__confirmar()
	{
		if (!$this->hay_datos_faltantes()) {
			// Se actualiza la fecha de última modificación 
			$this->modelo()->evt__confirmar();
			$this->vista()->set_datos_confirmados();
		} else {
			$this->vista()->set_error_confirmar();
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'datos_personales'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	function get_campos_faltantes()
	{
		if (!isset($this->campos_faltantes)) {
			$this->campos_faltantes = $this->modelo()->get_datos_faltantes();
		}
		return $this->campos_faltantes;
	}

	function hay_datos_faltantes()
	{
		$faltantes = $this->get_campos_faltantes();
		return (\guarani_arreglos::count($faltantes) > 0);
	}

	function hay_datos_faltantes_convocatoria()
	{
		$faltantes = $this->get_campos_faltantes();
		// https://stackoverflow.com/questions/1319903/how-to-flatten-a-multidimensional-array
		$faltantes_aplanado = [];
		array_walk_recursive($faltantes, function($faltante) use (&$faltantes_aplanado) { $faltantes_aplanado[] = $faltante; });
		kernel::log()->add_debug("=== faltantes_aplanado ===", $faltantes_aplanado);

		$campos_convocatoria = kernel::sesion()->esta_seteada(becas::CLAVE_CAMPOS_DATOS_CENSALES_SESION) ? kernel::sesion()->get(becas::CLAVE_CAMPOS_DATOS_CENSALES_SESION) : [];
		kernel::log()->add_debug("=== campos_convocatoria ===", $campos_convocatoria);

		$campos_convocatoria_faltantes = array_intersect($faltantes_aplanado, $campos_convocatoria);
		kernel::log()->add_debug("=== campos_convocatoria_faltantes ===", $campos_convocatoria_faltantes);

		return (\guarani_arreglos::count($campos_convocatoria_faltantes) > 0);
	}
	
	//--------------------------------------------------------------------------

	function accion__ocultar_msg_datos_confirmados()
	{
		$this->vista()->ocultar_msg_datos_confirmados();

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'carrera'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	protected function load_form_activo()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array(
			'default' => 'datos_personales'
		));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		return $pagelet;
	}

	function accion__guardar()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'datos_contacto'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			// Si es la solapa "Datos personales"
			if ($formulario == "datos_personales") {
				// Si en "Identidad de Género y Diversidad" se elige distinto a "Ninguna de las anteriores. Especifique"
				if ($datos['identidad_genero'] != \persona::identidad_genero_ninguna) {
					$datos['identidad_genero_otro'] = '';
				}
			}
			
			$this->modelo()->evt__guardar($formulario, $datos);
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_GUARDADO);
		} else {
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__guardar_edicion()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'actuacion_docente'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$datos = $form->get_datos();
			$this->modelo()->evt__guardar_edicion($formulario, $datos);
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_GUARDADO);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
		} else {
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_ERROR_VALIDACION);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__guardar_datos_salud()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'datos_salud'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			$this->modelo()->evt__guardar_datos_salud($datos);
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_GUARDADO);
		} else {
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__guardar_preferencias()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'preferencias'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			$this->modelo()->evt__guardar_preferencias($datos);
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_GUARDADO);
		} else {
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__edicion()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);

		kernel::renderer()->add($pagelet);
	}

	function accion__volver_edicion()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);

		kernel::renderer()->add($pagelet);
	}

	function accion__eliminar()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);

		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$this->modelo()->evt__eliminar($formulario, $this->seleccion);

		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		kernel::renderer()->add($pagelet);
	}

	//---- Acciones de Actuación Docente ---------------------------------------
	function accion__editar_actuacion_docente()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		kernel::renderer()->add($pagelet);
	}

	function get_actuacion_docente()
	{
		$datos = $this->modelo()->info__antecedentes_docentes();
		$datos_antecedente = $datos[$this->seleccion];
		if (!empty($datos_antecedente['fecha_desde'])) {
			$datos_antecedente['fecha_desde'] = convertir_fecha_formato_frontend($datos_antecedente['fecha_desde']);
		}
		if (!empty($datos_antecedente['fecha_hasta'])) {
			$datos_antecedente['fecha_hasta'] = convertir_fecha_formato_frontend($datos_antecedente['fecha_hasta']);
		}
		return $datos_antecedente;
	}

	//---- Acciones de Actuación Profesional ---------------------------------------
	function accion__editar_actuacion_profesional()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		kernel::renderer()->add($pagelet);
	}

	function get_actuacion_profesional()
	{
		$datos = $this->modelo()->info__antecedentes_profesionales();
		$datos_antecedente = $datos[$this->seleccion];
		if (!empty($datos_antecedente['fecha_desde'])) {
			$datos_antecedente['fecha_desde'] = convertir_fecha_formato_frontend($datos_antecedente['fecha_desde']);
		}
		if (!empty($datos_antecedente['fecha_hasta'])) {
			$datos_antecedente['fecha_hasta'] = convertir_fecha_formato_frontend($datos_antecedente['fecha_hasta']);
		}
		return $datos_antecedente;
	}

	//---- Acciones de Datos de contacto ---------------------------------------
	function accion__editar_datos_contacto()
        {
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		kernel::renderer()->add($pagelet);
	}

	function get_mail_principal()
	{
		$datos = $this->modelo()->info__datos_contacto();
		foreach ($datos as $clave => $valor) {
			if ($valor['contacto_tipo'] == \persona::mail_primario) {
				return $valor['email'];
			}
		}
		return '';
	}

	function get_dato_contacto()
	{
		if (!empty($this->seleccion)) {
			$datos = $this->modelo()->info__datos_contacto();
			$datos_contacto = $datos[$this->seleccion];
			return $datos_contacto;
		}
		return array();
	}

	function get_opciones_contacto_tipo()
	{
		$datos = \toba::consulta_php('co_personas')->get_tipos_contactos();
		$salida = array();
		foreach ($datos as $dato) {
			// Se excluyen:
			//  - MP: el mail primario se cambia desde operación "Configuración"
			//  - MI: el alumno no debe cargar su mail institucional.
			if (!(($dato['valor'] == \persona::mail_institucional) || ($dato['valor'] == \persona::mail_primario))) {
				$salida[] = $dato;
			}
		}
		return $this->modelo()->armar_combo_opciones($salida, 'valor', 'descr', true, false);
	}

	//---- Acciones de Allegados -----------------------------------------------
	function accion__editar_allegados()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		kernel::renderer()->add($pagelet);
	}

	function get_allegados()
	{
		$datos = $this->modelo()->info__allegados();
		$datos_antecedente = $datos[$this->seleccion];
		return $datos_antecedente;
	}

	//---- Acciones de Datos de estudio  ---------------------------------------
	function accion__editar_datos_estudios()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		kernel::renderer()->add($pagelet);
	}

	function get_datos_estudios()
	{
		$datos = $this->modelo()->info__datos_estudios();
		$datos_estudios = $datos[$this->seleccion];
		return $datos_estudios;
	}

	//--------------------------------------------------------------------------
	//---- Funciones de autocompletado de estudios -----------------------------
	//--------------------------------------------------------------------------

	function accion__lista_instituciones()
	{
		$criterio = $this->getAutocompleteTerm();
		if (empty($criterio)) {
			$datos = array();
		} else {
			$datos = $this->modelo()->opciones__instituciones_autocomplete($criterio);
		}
		$this->render_autocomplete($datos, 'valor', 'descr');
	}

	function accion__lista_colegios()
	{
		$criterio = $this->getAutocompleteTerm();
		if (empty($criterio)) {
			$datos = array();
		} else {
			$datos = $this->modelo()->opciones__colegios_autocomplete($criterio);
		}
		$this->render_autocomplete($datos, 'valor', 'descr');
	}

	function accion__lista_titulos()
	{
		$criterio = $this->getAutocompleteTerm();
		$tipo = utf8_decode(($this->get_param('tipo', 'get')));
		if ((empty($criterio)) || (empty($tipo))) {
			$datos = array();
		} else {
			$datos = $this->modelo()->opciones__titulos_autocomplete($criterio, $tipo);
		}
		$this->render_autocomplete($datos, 'valor', 'descr');
	}

	//--------------------------------------------------------------------------
	//---- CASCADAS: Selector de localidades -----------------------------------
	//--------------------------------------------------------------------------

	function accion__get_descr_localidad()
	{
		$localidad = $this->get_param('localidad', 'post');
		$datos = $this->modelo()->info__descr_localidad($localidad);
		$this->render_raw_json($datos);
	}

	function accion__lista_localidades()
	{
		$criterio = $this->getAutocompleteTerm();
		$pais = utf8_decode(($this->get_param('pais', 'get')));
		if ((empty($criterio)) || (empty($pais))) {
			$datos = array();
		} else {
			$datos = $this->modelo()->opciones__localidades_autocomplete($criterio, $pais);
		}
		$this->render_autocomplete($datos, 'localidad', 'localidad_descr');
	}

	function render_autocomplete($datos, $campo_valor, $campo_descr)
	{
		$paquete = [];
		foreach ($datos as $key => $dato) {
			$valor = $dato[$campo_valor];
			$descripcion = capitalizar($dato[$campo_descr]);
			$paquete[] = ["label" => $descripcion, "value" => [$campo_valor => $valor, $campo_descr => $descripcion]];
		}
		echo kernel::renderer()->json_encode($paquete);
		throw new FinalizadorRequest();
	}

	protected function to_autocomplete($values)
	{
		$lista_autocomplete = array();
		foreach ($values as $value) {
			$lista_autocomplete[] = array(
				'localidad' => $value['localidad'],
				'localidad_descr' => $value['localidad_descr'],
				'label' => '[' . $value['localidad_descr'] . '] ',
				'value' => '[' . $value['localidad_descr'] . '] '
			);
		}
		return $lista_autocomplete;
	}

	function accion__cascada_provincias()
	{
		$pais = $this->get_param('pais', 'post');
		$datos = $this->modelo()->opciones__provincias($pais);
		$this->render_raw_json($datos);
	}

	function accion__cascada_departamentos()
	{
		$provincia = $this->get_param('provincia', 'post');
		$datos = $this->modelo()->opciones__departamentos($provincia);
		$this->render_raw_json($datos);
	}

	function accion__cascada_localidades()
	{
		$departamento = $this->get_param('departamento', 'post');
		$datos = $this->modelo()->opciones__localidades($departamento);
		$this->render_raw_json($datos);
	}

	function accion__cascada_cod_postales()
	{
		$localidad = $this->get_param('localidad', 'post');
		$datos = $this->modelo()->opciones__cod_postales($localidad);
		$this->render_raw_json($datos);
	}

	function accion__cp_unico()
	{
		$localidad = $this->get_param('localidad', 'post');
		$datos = $this->modelo()->opciones__cod_postales($localidad);
		if (\guarani_arreglos::count($datos) == 1) {
			$this->render_raw_json(array('cp' => current($datos)));
		} else {
			return false;
		}
	}

}
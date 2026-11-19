<?php
namespace siu\extension_kernel\formularios;

use SIU\Chulupi\interfaz\componentes\forms\elementos\FormElementoAutocomplete;
use SIU\Chulupi\interfaz\componentes\forms\elementos\FormElementoFijo;
use SIU\Chulupi\interfaz\componentes\forms\FormElemento;
use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\elementos\guarani_accion;
use siu\extension_kernel\formularios\elementos\guarani_elemento_botonera;
use siu\extension_kernel\formularios\elementos\guarani_elemento_ml;
use siu\extension_kernel\formularios\elementos\guarani_elemento_timepicker;

class fabrica_formularios
{


	/**
	 * @return guarani_form
	 */
	function formulario()
	{
		$form = $this->instanciar('guarani_form', array());
		return $form;
	}

	/**
	 * @return guarani_vista_form
	 */
	function formulario_vista()
	{
		$vista = $this->instanciar('guarani_vista_form', array());
		return $vista;
	}

	/**
	 * @return salida_bootstrap_grilla
	 */
	function formulario_salida_html()
	{
		$vista = $this->instanciar('salida_bootstrap_grilla', array());
		return $vista;
	}

	/**
	 * @param $id
	 * @param $innerHtml
	 * @return guarani_accion
	 */
	function accion_boton_submit($id, $innerHtml)
	{
		$accion = $this->instanciar('elementos\\guarani_accion', array($id, $innerHtml, 'submit')); //$this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_accion_boton", array());
		$accion->set_vista_helper($vista);
		return $accion;
	}

	/**
	 * Le setea una vista compuesta al elemento para mostrar un boton con texto a la derecha del mismo.
	 * @param FormElemento $elemento
	 * @param $id_btn  id_html del boton
	 * @param $texto   el texto del boton
	 * @return el elemento pasado como parametro
	 */
	function elemento_decorar_boton_texto(FormElemento $elemento, $id_btn, $texto)
	{
		$vista_actual = $elemento->get_vista_helper();
		$instancia = $this->instanciar("elementos\\decorador_boton_derecha", array($vista_actual));

		/** @var $instancia decorador_boton_derecha */
		$instancia->set_boton("<span class='texto'>$texto</span>", $id_btn);
		$elemento->set_vista_helper($instancia);

		return $elemento;
	}

	function elemento_decorar_boton_limpiar(FormElemento $elemento, $id_btn)
	{
		$vista_actual = $elemento->get_vista_helper();
		$instancia = $this->instanciar("elementos\\decorador_boton_derecha", array($vista_actual));

		/** @var $instancia decorador_boton_derecha */
		$instancia->set_boton('<i class="icon-remove"></i>', $id_btn);
		$elemento->set_vista_helper($instancia);
		$instancia->set_class('decorado-limpiar');
		return $elemento;
	}


	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_form_elemento
	 */
	function elemento($id, $config)
	{
		$elemento = $this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_form_elemento
	 */
	function elemento_texto($id, $config)
	{
		$conf_base = array(
			FormElementoConfig::filtro => validador::TIPO_TEXTO,
			FormElementoConfig::clase_css => 'span12',
			FormElementoConfig::elemento => array('tipo' => 'text'));

		$config = array_merge($conf_base, $config);
		$elemento = $this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_form_elemento
	 */
	function elemento_textarea($id, $config)
	{
		$conf_base = array(
			FormElementoConfig::filtro => validador::TIPO_TEXTO,
			FormElementoConfig::elemento => array('tipo' => 'textarea'),
			FormElementoConfig::rows => 3,
			FormElementoConfig::clase_css => 'span12',
		);

		$config = array_merge($conf_base, $config);
		$elemento = $this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}


	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return FormElementoFijo
	 */
	function elemento_fijo($id, $config)
	{
		$elemento = new FormElementoFijo($id, $config);
		$vista = $this->instanciar("elementos\\guarani_vista_elemento_fijo", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_elemento_botonera
	 */
	function elemento_botonera($id, $config)
	{
		$elemento = $this->instanciar("elementos\\guarani_elemento_botonera", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_botonera", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @param callable $fn_autcompletar funcion para el autocompletar
	 * @param string $url la url donde postea el autocompletar
	 * @return FormElementoAutocomplete
	 */
	function elemento_autocompletar($id, $config, $fn_autcompletar, $url)
	{
		$elemento = $this->instanciar("elementos\\guarani_elemento_autocomplete", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_autocompletar", array());
		$elemento->set_vista_helper($vista);
		$elemento->set_fn_autocomplete($fn_autcompletar);
		$vista->set_url($url);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_elemento_botonera
	 */
	function elemento_botonera_dias($id, $config)
	{
		$config[FormElementoConfig::multi_options] = guarani_elemento_botonera::dias_semana();
		$elemento = $this->instanciar("elementos\\guarani_elemento_botonera", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_botonera", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_elemento_botonera
	 */
	function elemento_botonera_turnos($id, $config)
	{
		$config[FormElementoConfig::multi_options] = guarani_elemento_botonera::turnos();
		$elemento = $this->instanciar("elementos\\guarani_elemento_botonera", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_botonera", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_elemento_timepicker
	 */
	function elemento_timepicker($id, $config)
	{
		$elemento = $this->instanciar("elementos\\guarani_elemento_timepicker", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @return guarani_elemento_ml
	 */
	function elemento_ml($id)
	{
		$elemento = $this->instanciar("elementos\\guarani_elemento_ml", array($id, array()));
		$vista = $this->instanciar("elementos\\guarani_vista_ml", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_form_elemento
	 */
	function elemento_fecha($id, $config)
	{
		$conf_base = array(
			FormElementoConfig::filtro => validador::TIPO_DATE,
			FormElementoConfig::filtro_params => array('format' => 'd/m/Y', 'allowempty' => true),
			FormElementoConfig::clase_css => 'date',
			FormElementoConfig::placeholder => kernel::traductor()->trans('admin_actas_examen_fc.ddmmaaaa'), //todo cambiar a otro lugar
			FormElementoConfig::elemento => array('tipo' => 'text'));

		$config = array_merge($conf_base, $config);
		$elemento = $this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $opciones usar formato de guarani_form_elemento::armar_combo_opciones
	 * @param array $config configuracion del elemento
	 * @return guarani_form_elemento
	 */
	function elemento_select($id, $opciones, $config)
	{
		$conf_base = array(
			FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
			FormElementoConfig::elemento => array('tipo' => 'select'),
			FormElementoConfig::multi_options => $opciones,
			FormElementoConfig::validar_select => true,
			FormElementoConfig::clase_css => 'span12');

		$config = array_merge($conf_base, $config);

		$elemento = $this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}

	/**
	 * @param string $id identificador del elemento
	 * @param array $config configuracion del elemento
	 * @return guarani_form_elemento
	 */
	function elemento_checkbox($id, $config)
	{
		
		$conf_base = array(
			FormElementoConfig::filtro => validador::TIPO_TEXTO,
			FormElementoConfig::elemento => array('tipo' => 'checkbox'),
			FormElementoConfig::clase_css => 'span12');

		$config = array_merge($conf_base, $config);

		$elemento = $this->instanciar("guarani_form_elemento", array($id, $config));
		$vista = $this->instanciar("elementos\\guarani_vista_default", array());
		$elemento->set_vista_helper($vista);
		return $elemento;
	}
	
	protected function instanciar($clase, $params)
	{
		return kernel::localizador()->instanciar_params_array("extension_kernel\\formularios\\" . $clase, $params);
	}


}
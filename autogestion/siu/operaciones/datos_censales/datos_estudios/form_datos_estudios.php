<?php
namespace siu\operaciones\datos_censales\datos_estudios;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use siu\operaciones\datos_censales\_comun\form_edicion;
use siu\operaciones\datos_censales\_comun\form_censo;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\datos\catalogo;

class form_datos_estudios extends form_edicion
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_datos_estudios';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar_edicion', array('datos_estudios'));
	}
	
	protected function generar_definicion() 
	{
		$this->set_campo(catalogo::id, array(
			form::label			=> 'id',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::valor_default => ''
		));
		
		$this->set_campo('nivel_estudio', array(
			form::label			=> 'datos_censales.est.nivel_estudio',
			form::obligatorio	=> true,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__niveles_estudio()
		));
		
		$this->set_campo('institucion', array(
			form::label			=> 'datos_censales.est.institucion',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'hidden'),
		));
		
		$this->set_campo('institucion_descr', array(
			form::label			=> 'datos_censales.est.institucion',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input js-campo_descr',
		));
		
		$this->set_campo('colegio', array(
			form::label			=> 'datos_censales.est.colegio',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'hidden'),
		));
		
		$this->set_campo('colegio_descr', array(
			form::label			=> 'datos_censales.est.colegio',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input js-campo_descr',
		));
		
		$this->set_campo('anio_egreso', array(
			form::label			=> 'datos_censales.est.anio_egreso',
			form::obligatorio	=> false,
			form::filtro_params => array('allowempty' => true),
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__anios_egreso(),
			form::clase_css		=> 'combo_small',
		));
		
		$this->set_campo('titulo', array(
			form::label			=> 'datos_censales.est.titulo',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'hidden'),
		));
		
		$this->set_campo('titulo_descr', array(
			form::label			=> 'datos_censales.est.titulo',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input js-campo_descr',
		));
		
		$this->set_campo('titulo_tesis', array(
			form::label			=> 'datos_censales.est.titulo_tesis',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 255
		));
		
		$this->set_campo('director_tesis', array(
			form::label			=> 'datos_censales.est.director_tesis',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 255
		));
		
		$this->set_campo('estado', array(
			form::label			=> 'datos_censales.est.estado',
			form::obligatorio	=> false,
			form::filtro_params => array('allowempty' => true),
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__estados_estudio(),
		));
		
		$this->set_campo('fecha_ingreso', array(
			form::label			=> 'datos_censales.est.fecha_ingreso',
			form::filtro		=> validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y', 
										 'allowempty' => true),
			form::clase_css		=> 'date input-small',
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text')
		));
		
		$this->set_campo('fecha_egreso', array(
			form::label			=> 'datos_censales.est.fecha_egreso',
			form::filtro		=> validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y', 
										 'allowempty' => true),
			form::clase_css		=> 'date input-small',
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text')
		));
		
		$this->set_campo('adeuda_materias_secundario', array(
			form::label => 'datos_censales.est.adeuda_materias_secundario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));
		
		//---- Razones de abandono de estudio ----------------------------------
		$this->set_campo('razon_abandono', array(
			form::label => 'datos_censales.est.razon_abandono',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__razones_abandono_estudio(),
			form::valor_default => ''
		));
		
		$this->set_campo('razon_abandono_otros', array(
			form::label => 'datos_censales.est.razon_abandono_otros',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 200,
			form::clase_css => 'input-xxlarge'
		));
	}
	
	function validar()
	{
		$form_valido = parent::validar();
		
		$nivel_estudio = $this->get_dato('nivel_estudio');
		if ($nivel_estudio == form_censo::NO_SELECCION) {
			$form_valido = false;
			$msg = kernel::traductor()->trans('form_validacion_error.vacio');
			$this->agregar_error('nivel_estudio', $msg);
		}
		
		$fecha_ingreso = $this->get_dato('fecha_ingreso');
		$fecha_egreso = $this->get_dato('fecha_egreso');
		if ((!empty($fecha_ingreso)) && (!empty($fecha_egreso))) {
			$fecha_ingreso = convertir_fecha_formato_base($fecha_ingreso);
			$fecha_egreso = convertir_fecha_formato_base($fecha_egreso);
			
			if (fecha_es_menor($fecha_egreso, $fecha_ingreso)) {
				$form_valido = false;
				$msg = kernel::traductor()->trans('datos_censales.error_fechas');
				$this->agregar_error('fecha_egreso', $msg);
			}
		}

		return $form_valido;
	}
}
?>
<?php
namespace siu\operaciones\datos_censales\domicilio;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;

use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_domicilio extends form_censo
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_domicilio';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar', array('domicilio'));
	}
	
	protected function generar_definicion() 
	{
		//---- Domicilio durante el período de clases --------------------------
		$this->set_campo('periodo_lectivo_calle', array(
			form::label			=> 'datos_censales.periodo_lectivo_calle',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 50,
			form::clase_css		=> 'input-calle'
		));
		
		$this->set_campo('periodo_lectivo_numero', array(
			form::label			=> 'datos_censales.periodo_lectivo_numero',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 5,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('periodo_lectivo_piso', array(
			form::label			=> 'datos_censales.periodo_lectivo_piso',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 3,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('periodo_lectivo_departamento', array(
			form::label			=> 'datos_censales.periodo_lectivo_departamento',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 5,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('periodo_lectivo_unidad', array(
			form::label			=> 'datos_censales.periodo_lectivo_unidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 3,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('periodo_lectivo_pais', array(
			form::label			=> 'datos_censales.periodo_lectivo_pais',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__paises(),
			form::clase_css		=> 'pais-localidad',
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('periodo_lectivo_localidad', array(
			form::label			=> 'datos_censales.periodo_lectivo_localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::valor_default => ''
		));
		
		$this->set_campo('periodo_lectivo_localidad_descr', array(
			form::label			=> 'datos_censales.periodo_lectivo_localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'input-localidad',
		));
		
		$this->set_campo('periodo_lectivo_codigo_postal', array(
			form::label			=> 'datos_censales.periodo_lectivo_codigo_postal',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::readonly		=> true,
			form::clase_css		=> 'input-small',
			form::largo			=> 15
		));
		
		$this->set_campo('periodo_lectivo_barrio', array(
			form::label			=> 'datos_censales.periodo_lectivo_barrio',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 60,
			form::clase_css		=> 'input-medio'
		));
		
		$this->set_campo('tipo_vivienda', array(
			form::label			=> 'datos_censales.tipo_vivienda',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__tipos_vivienda(),
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('vive_con', array(
			form::label			=> 'datos_censales.vive_con',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__vive_con(),
			form::valor_default => self::NO_SELECCION,
		));
		
		//---- Domicilio de procedencia ----------------------------------------
		$this->set_campo('procedencia_calle', array(
			form::label			=> 'datos_censales.procedencia_calle',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 50,
			form::clase_css		=> 'input-calle'
		));
		
		$this->set_campo('procedencia_numero', array(
			form::label			=> 'datos_censales.procedencia_numero',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 5,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('procedencia_piso', array(
			form::label			=> 'datos_censales.procedencia_piso',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 3,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('procedencia_departamento', array(
			form::label			=> 'datos_censales.procedencia_departamento',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 5,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('procedencia_unidad', array(
			form::label			=> 'datos_censales.procedencia_unidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 3,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('procedencia_pais', array(
			form::label			=> 'datos_censales.procedencia_pais',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__paises(),
			form::clase_css		=> 'pais-localidad',
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('procedencia_localidad', array(
			form::label			=> 'datos_censales.procedencia_localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::valor_default => ''
		));
		
		$this->set_campo('procedencia_localidad_descr', array(
			form::label			=> 'datos_censales.procedencia_localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'input-localidad',
		));
		
		$this->set_campo('procedencia_codigo_postal', array(
			form::label			=> 'datos_censales.procedencia_codigo_postal',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::readonly		=> true,
			form::clase_css		=> 'input-small',
			form::largo			=> 15
		));
		
		$this->set_campo('procedencia_barrio', array(
			form::label			=> 'datos_censales.procedencia_barrio',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 60,
			form::clase_css		=> 'input-medio'
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
			$clase_error = ($tiene_error) ? 'class="error"' : '';
			
			
			$html  = "<div $clase_error>";
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
?>
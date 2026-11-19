<?php
namespace siu\operaciones\datos_censales\allegados;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use siu\operaciones\datos_censales\_comun\form_edicion;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_allegados extends form_edicion
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_allegados';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar_edicion', array('allegados'));
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
		
		$this->set_campo('tipo_allegado', array(
			form::label			=> 'datos_censales.all.tipo_allegado',
			form::obligatorio	=> true,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__tipos_allegado(),
			form::valor_default => form_censo::NO_SELECCION,
		));
		
		$this->set_campo('apellido', array(
			form::label			=> 'datos_censales.all.apellido',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::clase_css		=> 'input-medio',
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 60
		));
		
		$this->set_campo('nombres', array(
			form::label			=> 'datos_censales.all.nombres',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::clase_css		=> 'input-medio',
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 60
		));
		
		//---- Domicilio -------------------------------------------------------
		$this->set_campo('calle', array(
			form::label			=> 'datos_censales.all.calle',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 50,
			form::clase_css		=> 'input-calle'
		));
		
		$this->set_campo('numero', array(
			form::label			=> 'datos_censales.all.numero',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 5,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('piso', array(
			form::label			=> 'datos_censales.all.piso',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 3,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('departamento', array(
			form::label			=> 'datos_censales.all.departamento',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 5,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('unidad', array(
			form::label			=> 'datos_censales.all.unidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 3,
			form::clase_css		=> 'input-small',
		));
		
		$this->set_campo('pais', array(
			form::label			=> 'datos_censales.all.pais',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__paises(),
			form::clase_css		=> 'pais-localidad',
			form::valor_default => self::NO_SELECCION,
		));
		
		$this->set_campo('localidad', array(
			form::label			=> 'datos_censales.all.localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::valor_default => ''
		));
		
		$this->set_campo('localidad_descr', array(
			form::label			=> 'datos_censales.all.localidad',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'input-localidad',
		));
		
		$this->set_campo('codigo_postal', array(
			form::label			=> 'datos_censales.all.codigo_postal',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::readonly		=> true,
			form::clase_css		=> 'input-small',
			form::largo			=> 15
		));
		
		$this->set_campo('barrio', array(
			form::label			=> 'datos_censales.all.barrio',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 60,
			form::clase_css		=> 'input-medio'
		));
		
		$this->set_campo('telefono', array(
			form::label			=> 'datos_censales.all.telefono',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 30
		));
		
		//---- Estudios --------------------------------------------------------
		$this->set_campo('estudio_alcanzado', array(
			form::label			=> 'datos_censales.all.estudio_alcanzado',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__estudio_alcanzado(),
			form::valor_default => ''
		));
		
		//---- Trabajo ---------------------------------------------------------
		$this->set_campo('trabajo_existe', array(
			form::label			=> 'datos_censales.all.trabajo_existe',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_existe(),
			form::clase_css		=> 'select-largo',
			form::valor_default => ''
		));
		
		$this->set_campo('trabajo_hace', array(
			form::label			=> 'datos_censales.all.trabajo_hace',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_hace(),
			form::valor_default => ''
		));
		
		$this->set_campo('trabajo_ocupacion', array(
			form::label			=> 'datos_censales.all.trabajo_ocupacion',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_ocupacion(),
			form::clase_css		=> 'select-largo',
			form::valor_default => ''
		));
				
		$this->set_campo('trabajo_tarea', array(
			form::label			=> 'datos_censales.all.trabajo_tarea',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'input-largo',
			form::largo			=> 255
		));
	}
	
	//--------------------------------------------------------------------------
	
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
	
	function validar()
	{
		$form_valido = parent::validar();
		if ($this->get_dato('tipo_allegado') == form_censo::NO_SELECCION) {
			$form_valido = false;
			$this->agregar_error('tipo_allegado', 'form_validacion_error.vacio');
		}
		return $form_valido;
	}
}
?>
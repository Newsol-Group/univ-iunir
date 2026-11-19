<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Regularidad
 */
class ci_edi_readmisiones extends guarani_pers_ci
{
	protected $datos_temp;
	protected $s__contabiliza;
	
	function conf__formulario(toba_ei_formulario $form)
	{
		$datos_perdida_regularidad = $this->get_datos_perdida_regularidad();
		$anio_academico = $datos_perdida_regularidad['anio_academico_perdida_reg'] + 1;
		$fecha_vencimiento = ($anio_academico + 1).'-03-31';
		$solo_lectura = array('fecha_format', 'anio_academico_perdida_reg', 'documento_numero', 'causa_nombre');
		$datos_defecto = array('contabiliza' => comunes::si, 'fecha' => guarani_fecha::get_hoy(), 'fecha_vencimiento' => $fecha_vencimiento, 'tipo' => 'R', 'anio_academico' => $anio_academico);
		$form->set_solo_lectura($solo_lectura);
		$form->set_datos_defecto($datos_defecto);
		
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$datos_readmision = $this->cn()->entidad()->get_datos() ? $this->cn()->entidad()->get_datos() : array();
			$form->set_datos(array_merge($datos_readmision, $datos_perdida_regularidad));
		}
	}

	function get_datos_perdida_regularidad()
	{
		$datos_perdida_regularidad = $this->controlador()->get_seleccion_datos();
		$datos_perdida_regularidad['anio_academico_perdida_reg'] = $datos_perdida_regularidad['anio_academico'];
		unset($datos_perdida_regularidad['anio_academico']);
		unset($datos_perdida_regularidad['fecha']);
		return $datos_perdida_regularidad;
	}
	
	function evt__formulario__modificacion($datos)
	{
		try {
			$this->s__contabiliza = $datos['contabiliza'];
			$this->cn()->entidad()->set_datos($datos);
			
			if ($this->entidad_es_nueva()) {
				$vencimiento = $this->armar_datos_vencimiento($datos['fecha_vencimiento'], $datos['observaciones']);
				$this->cn()->entidad()->agregar_vencimiento($vencimiento);
			} else {
				$cambios = $this->cn()->entidad()->get_cambios_fila($datos);
				
				if (isset($cambios['fecha_vencimiento'])) {
					$vencimiento = $this->armar_datos_vencimiento($cambios['fecha_vencimiento']['actual'], $datos['observaciones'], $cambios['fecha_vencimiento']['anterior']);
					$this->cn()->entidad()->agregar_vencimiento($vencimiento);
				}
			}
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}
	
	function armar_datos_vencimiento($fecha_nueva, $observaciones, $fecha_original=null)
	{
		$persona = toba::consulta_php('co_personas')->get_persona_x_usuario(toba::usuario()->get_id());
		$vencimiento = array();
		$vencimiento['tipo_modificacion'] = 1;
		$vencimiento['fecha_vencimiento_original'] = $fecha_original;
		$vencimiento['fecha_vencimiento_nueva'] = $fecha_nueva;
		$vencimiento['fecha_modificacion'] = guarani_fecha::get_ahora();
		$vencimiento['operador'] = $persona['persona'];
		$vencimiento['observaciones'] = $observaciones;
		return $vencimiento;
	}
	
	function entidad_es_nueva()
	{
		return $this->cn()->entidad()->es_nueva();
	}
	
	function get_contabiliza()
	{
		return $this->s__contabiliza;
	}
	
}
?>

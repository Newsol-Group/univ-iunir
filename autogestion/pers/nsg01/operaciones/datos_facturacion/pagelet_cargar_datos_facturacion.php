<?php

namespace nsg01\operaciones\datos_facturacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;


class pagelet_cargar_datos_facturacion extends pagelet
{
	function get_nombre()
    {
        return 'cargar_datos_facturacion';
    }

	/**
	 * Shortcut al modelo
	 * 
	 * @return \siu\modelo\transacciones\datos_facturacion
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}
	
    function prepare()
    {
		$this->data['url_cancel'] = kernel::vinculador()->crear('datos_facturacion', 'index');
		$parametros['persona'] = kernel::persona()->get_id();
		$es_perfil_cursos = kernel::persona()->es_perfil_cursos();

		if (!$es_perfil_cursos) {
			$alumno = kernel::persona()->get_id_alumno_activo();
			$parametros['alumno'] = array('alumno' => $alumno);
		}
		else {

			$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', array('persona' => $parametros['persona']));
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];
		
			foreach($propuestas as &$propuesta) {
				$propuesta['cursos'] = catalogo::consultar('cursos', 'get_cursos_academica', $pers_prop_param);
			}

			$parametros['propuesta'] = $propuesta['propuesta'];
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];

			$alumno = catalogo::consultar('alumno', 'get_alumno', $pers_prop_param);
			$parametros['alumno'] = $alumno;
		}

		$datos_facturacion = catalogo::consultar('datos_facturacion', 'get_datos_facturacion_alumno', $parametros['alumno']);

		$this->set_datos_persona($alumno);

		if (empty($datos_facturacion)) 
		{
			$this->set_empty_datos_facturacion($parametros);
		}
		else 
		{
			$this->set_datos_facturacion($parametros, $datos_facturacion);
		}

		$this->data['medios_pago'] = catalogo::consultar('datos_facturacion', 'get_medios_pago');
  	}
	
	function set_datos_persona($alumno) {
		$this->data['alumno'] = $alumno;
		$this->data['persona']['apellido'] = kernel::persona()->get_solo_apellido();
		$this->data['persona']['nombres'] = kernel::persona()->get_solo_nombres();
		$this->data['persona']['tipo_documento_desc_abreviada'] = kernel::persona()->get_tipo_documento_desc_abreviada();
		$this->data['persona']['nro_documento'] = kernel::persona()->get_nro_documento();
	}

	function set_empty_datos_facturacion($parametros) {
		$this->set_forma_pago($parametros, []);
		$this->data['porcentaje_facturacion_alumno'] = '';	
		$this->data['nro_tarjeta_alumno_mat'] = '';
		$this->data['cbu_alumno_mat'] = '';	
		$this->data['nro_tarjeta_alumno_cuota'] = '';	
		$this->data['cbu_alumno_cuota'] = '';	
		$this->data['observaciones'] = '';	
		$this->data['medio_pago_alumno_mat'] = 3;
		$this->data['medio_pago_alumno_cuota'] = 3;
	}

	function set_datos_facturacion($parametros, $datos_facturacion) {
		$this->set_forma_pago($parametros, $datos_facturacion);
		$this->data['porcentaje_facturacion_alumno'] =  $datos_facturacion[0]["porcentaje_facturacion_alumno"];
		$this->data['nro_tarjeta_alumno_mat'] = $datos_facturacion[0]["nro_tarjeta_alumno_mat"];
		$this->data['cbu_alumno_mat'] = $datos_facturacion[0]["cbu_alumno_mat"];
		$this->data['nro_tarjeta_alumno_cuota'] = $datos_facturacion[0]["nro_tarjeta_alumno_cuota"];
		$this->data['cbu_alumno_cuota'] = $datos_facturacion[0]["cbu_alumno_cuota"];
		$this->data['observaciones'] = $datos_facturacion[0]["observaciones"];
		$this->data['medio_pago_alumno_mat'] = $datos_facturacion[0]['medio_pago_alumno_mat'];
		$this->data['medio_pago_alumno_cuota'] = $datos_facturacion[0]['medio_pago_alumno_cuota'];
	}

	function set_forma_pago($parametros, $datos_facturacion) {
		
		$forma_pago = catalogo::consultar('datos_facturacion', 'get_formas_pago_alumno', $parametros['alumno']);
		
		if (!isset($forma_pago))
			$forma_pago = $datos_facturacion[0]['fp_propuesta'];
		
		$this->data['forma_pago'] = $forma_pago; 	
	}

	function armar_combo($datos, $valor, $descripcion, $arma_medio_pago, $valor_seleccionado = null, $opcional = false)
	{
		$options = "";
		
		foreach ($datos as $dato) {
			
			$selected = "";

			if ($dato[$valor] == $valor_seleccionado) {
				$selected = "selected='selected'";
			}
			$options .= "<option value='{$dato[$valor]}' $selected>{$dato[$descripcion]}</option>";
		}
		
		return $options;
	}
}

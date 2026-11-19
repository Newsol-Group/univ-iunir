<?php

namespace nsg01\operaciones\datos_facturacion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_listado extends pagelet
{
	function get_nombre()
    {
        return 'listado';
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
		$this->data['fact_empresa_enabled'] = false;
		

		$parametros['persona'] = kernel::persona()->get_id();
		$es_perfil_cursos = kernel::persona()->es_perfil_cursos();

		if (!$es_perfil_cursos) {
			$alumno = kernel::persona()->get_id_alumno_activo();
			$parametros['alumno'] = array('alumno' => $alumno);
		}
		else {

			$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', array('persona' => $parametros['persona']));
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];
			//var_dump($propuestas);
			foreach($propuestas as &$propuesta) {
				$propuesta['cursos'] = catalogo::consultar('cursos', 'get_cursos_academica', $pers_prop_param);
			}

			$parametros['propuesta'] = $propuesta['propuesta'];
			///var_dump($propuesta['propuesta']);

			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];

			$alumno = catalogo::consultar('alumno', 'get_alumno', $pers_prop_param);
			$parametros['alumno'] = $alumno;
		}

		$datos_facturacion = catalogo::consultar('datos_facturacion', 'get_datos_facturacion_alumno', $parametros['alumno']);

	// Si no hay datos de facturaci�n
		if(!empty($datos_facturacion)){
			
			$operacion = kernel::ruteador()->get_id_operacion();
			$this->data['url_cargar_datos_facturacion'] = kernel::vinculador()->crear($operacion, 'cargar_datos_facturacion');
	
			$this->data['habilitar_cargar_datos_facturacion'] = true;

			$this->data['dato_facturacion'] = $datos_facturacion[0]["dato_facturacion"];

			$this->data['porcentaje_facturacion_alumno'] =  $datos_facturacion[0]["porcentaje_facturacion_alumno"];

			if (isset($datos_facturacion[0]["medio_pago_alumno_mat"])) {
				$parametros['medio_pago'] = $datos_facturacion[0]["medio_pago_alumno_mat"];
				$this->data['id_medio_pago_alumno_mat'] = $datos_facturacion[0]["medio_pago_alumno_mat"];
				$this->data['medio_pago_alumno_mat'] =  catalogo::consultar('datos_facturacion', 'get_descr_medio_pago', array('medio_pago' => $parametros['medio_pago']));
			}
			//else $this->data['medio_pago_alumno_mat'] =  3;
			
			if (isset($datos_facturacion[0]["forma_pago"])) {
				$parametros['forma_pago'] = $datos_facturacion[0]["forma_pago"];
				$this->data['forma_pago'] = catalogo::consultar('datos_facturacion', 'get_descr_forma_pago', array('forma_pago' => $parametros['forma_pago']));
			}
		
			$this->data['nro_tarjeta_alumno_mat'] =  $datos_facturacion[0]["nro_tarjeta_alumno_mat"];
			$this->data['cbu_alumno_mat'] =  $datos_facturacion[0]["cbu_alumno_mat"];

			if (isset($datos_facturacion[0]["medio_pago_alumno_cuota"])) {
				$parametros['medio_pago'] = $datos_facturacion[0]["medio_pago_alumno_cuota"];
				$this->data['id_medio_pago_alumno_cuota'] = $datos_facturacion[0]["medio_pago_alumno_cuota"];
				$this->data['medio_pago_alumno_cuota'] =  catalogo::consultar('datos_facturacion', 'get_descr_medio_pago', array('medio_pago' => $parametros['medio_pago']));

			}
			//else $this->data['medio_pago_alumno_mat'] =  3;

			$this->data['nro_tarjeta_alumno_cuota'] =  $datos_facturacion[0]["nro_tarjeta_alumno_cuota"];
			$this->data['cbu_alumno_cuota'] =  $datos_facturacion[0]["cbu_alumno_cuota"];

			$this->data['pago_a_cargo_empresa'] =  $datos_facturacion[0]["pago_a_cargo_empresa"];
			$this->data['porcentaje_facturacion_empresa'] =  $datos_facturacion[0]["porcentaje_facturacion_empresa"];

			if (isset($datos_facturacion[0]["medio_pago_empresa_mat"])) {
				$parametros['medio_pago'] = $datos_facturacion[0]["medio_pago_empresa_mat"];
				$this->data['medio_pago_empresa_mat'] =  catalogo::consultar('datos_facturacion', 'get_descr_medio_pago', array('medio_pago' => $parametros['medio_pago']));
			}

			$this->data['nro_tarjeta_empresa_mat'] =  $datos_facturacion[0]["nro_tarjeta_empresa_mat"];
			$this->data['cbu_empresa_mat'] =  $datos_facturacion[0]["cbu_empresa_mat"];

			if (isset($datos_facturacion[0]["medio_pago_empresa_cuota"])) {
				$parametros['medio_pago'] = $datos_facturacion[0]["medio_pago_empresa_cuota"];
				$this->data['medio_pago_empresa_cuota'] = catalogo::consultar('datos_facturacion', 'get_descr_medio_pago', array('medio_pago' => $parametros['medio_pago']));
			}
			$this->data['nro_tarjeta_empresa_cuota'] =  $datos_facturacion[0]["nro_tarjeta_empresa_cuota"];
			$this->data['cbu_empresa_cuota'] =  $datos_facturacion[0]["cbu_empresa_cuota"];

			if (isset($datos_facturacion[0]["empresa"])) {
				$parametros['empresa'] = $datos_facturacion[0]["empresa"];
				$this->data['empresa'] =  catalogo::consultar('datos_facturacion', 'get_descr_empresa', array('empresa' => $parametros['empresa']));
			}
			$this->data['email_empresa'] =  $datos_facturacion[0]["email_empresa"];
			$this->data['observaciones'] =  $datos_facturacion[0]["observaciones"];

		} //sino
		else
		{
			$operacion = kernel::ruteador()->get_id_operacion();
			$this->data['url_cargar_datos_facturacion'] = kernel::vinculador()->crear($operacion, 'cargar_datos_facturacion');
			$this->data['habilitar_cargar_datos_facturacion'] = true;
		}
				
		$this->add_var_js('guardado_ok', false);

		if(kernel::sesion()->esta_seteada('guardado_ok')){
			kernel::sesion()->borrar('guardado_ok');
			$this->add_var_js('guardado_ok', true);
			$this->add_mensaje_js('guardado_ok_msj', kernel::traductor()->trans('datos_facturacion.datos_guardados_exito'));
		}
    }
	
}

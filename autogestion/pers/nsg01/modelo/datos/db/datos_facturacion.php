<?php

namespace nsg01\modelo\datos\db;

use SIU\Chulupi\kernel;

class datos_facturacion
{
	
     /**
     * parametros:	alumno
     * no_quote:	alumno
     * cache: memoria
     * filas: n
     */
    function get_datos_facturacion_alumno($parametros)
    {
	return \toba::consulta_php('co_facturacion')->get_datos_facturacion_alumno($parametros['alumno']);
    }
	
     /**
     * parametros:
     * cache: memoria
     * filas: n
     */
    function get_medios_pago($parametros)
    {
        return \toba::consulta_php('co_facturacion')->get_medios_pago_autogestion();
    }
	
     /**
     * parametros: medio_pago
     * no_quote: medio_pago
     * cache: memoria
     * filas: n
     */
    function get_descr_medio_pago($parametros)
    {
        return \toba::consulta_php('co_facturacion')->get_descripcion_medio_pago($parametros['medio_pago']);
    }

    /**
     * parametros: forma_pago
     * no_quote: forma_pago
     * cache: memoria
     * filas: n
     */
    function get_descr_forma_pago($parametros)
    {
        return \toba::consulta_php('co_facturacion')->get_descripcion_forma_pago($parametros['forma_pago']);
    }

	
     /**
     * parametros:
     * cache: memoria
     * filas: n
     */
    function get_empresas($parametros)
    {
        return \toba::consulta_php('co_instituciones')->get_listado_nombres('');
    }

     /**
     * parametros: empresa
     * no_quote: empresa
     * cache: memoria
     * filas: n
     */
   		
    function get_descr_empresa($parametros)
    {
        return \toba::consulta_php('co_instituciones')->get_descripcion_institucion($parametros['empresa']);
    }

	/**
     	* parametros: alumno
	* no_quote: alumno
     	* cache: no
     	*/

      	function get_formas_pago_alumno($parametros)
    	{
        	return \toba::consulta_php('co_facturacion')->get_formas_pago_alumno($parametros['alumno']);
    	}

	/**
     	* parametros: datos_facturacion
     	* cache: no
     	*/
    	function registrar_datos_facturacion($datos_facturacion)
    	{
		
		$parametros['alumno'] = $datos_facturacion['datos_facturacion']['alumno'];

		$parametros['forma_pago'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['forma_pago'])) 
			$parametros['forma_pago'] = $datos_facturacion['datos_facturacion']['forma_pago'];

		$parametros['porcentaje_facturacion_alumno'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['porcentaje_facturacion_alumno'])) 
			$parametros['porcentaje_facturacion_alumno'] = $datos_facturacion['datos_facturacion']['porcentaje_facturacion_alumno'];

		$parametros['medio_pago_alumno_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_alumno_mat'])) 
			$parametros['medio_pago_alumno_mat'] = $datos_facturacion['datos_facturacion']['medio_pago_alumno_mat'];

		$parametros['nro_tarjeta_alumno_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_mat'])) 
			$parametros['nro_tarjeta_alumno_mat'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_mat'];

		$parametros['cbu_alumno_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_alumno_mat'])) 
			$parametros['cbu_alumno_mat'] = $datos_facturacion['datos_facturacion']['cbu_alumno_mat'];

		$parametros['medio_pago_alumno_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_alumno_cuota'])) 
			$parametros['medio_pago_alumno_cuota'] = $datos_facturacion['datos_facturacion']['medio_pago_alumno_cuota'];

		$parametros['nro_tarjeta_alumno_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_cuota'])) 
			$parametros['nro_tarjeta_alumno_cuota'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_cuota'];

		$parametros['cbu_alumno_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_alumno_cuota'])) 
			$parametros['cbu_alumno_cuota'] = $datos_facturacion['datos_facturacion']['cbu_alumno_cuota'];

		$parametros['porcentaje_facturacion_empresa'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['porcentaje_facturacion_empresa'])) 
			$parametros['porcentaje_facturacion_empresa'] = $datos_facturacion['datos_facturacion']['porcentaje_facturacion_empresa'];

		$parametros['medio_pago_empresa_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_empresa_mat'])) 
			$parametros['medio_pago_empresa_mat'] = $datos_facturacion['datos_facturacion']['medio_pago_empresa_mat'];

		$parametros['nro_tarjeta_empresa_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_mat'])) 
			$parametros['nro_tarjeta_empresa_mat'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_mat'];

		$parametros['cbu_empresa_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_empresa_mat'])) 
			$parametros['cbu_empresa_mat'] = $datos_facturacion['datos_facturacion']['cbu_empresa_mat'];

		$parametros['medio_pago_empresa_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_empresa_cuota'])) 
			$parametros['medio_pago_empresa_cuota'] = $datos_facturacion['datos_facturacion']['medio_pago_empresa_cuota'];

		$parametros['nro_tarjeta_empresa_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_cuota'])) 
			$parametros['nro_tarjeta_empresa_cuota'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_cuota'];

		$parametros['cbu_empresa_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_empresa_cuota'])) 
			$parametros['cbu_empresa_cuota'] = $datos_facturacion['datos_facturacion']['cbu_empresa_cuota'];

		$parametros['empresa'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['empresa'])) 
			$parametros['empresa'] = $datos_facturacion['datos_facturacion']['empresa'];

		$parametros['email_empresa'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['email_empresa'])) 
			$parametros['email_empresa'] = $datos_facturacion['datos_facturacion']['email_empresa'];

		$parametros['observaciones'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['observaciones'])) 
			$parametros['observaciones'] = $datos_facturacion['datos_facturacion']['observaciones'];

		if($parametros['porcentaje_facturacion_empresa'] > 0) {
			$parametros['pago_a_cargo_empresa'] = 'S';
		} else {
			$parametros['pago_a_cargo_empresa'] = 'N';
		}
		$parametros['pago_a_cargo_empresa'] = kernel::db()->quote($parametros['pago_a_cargo_empresa']);

       		$sql = "
                INSERT INTO negocio_nsg.nsg_datos_facturacion (
							alumno,
							forma_pago,
							porcentaje_facturacion_alumno,
							medio_pago_alumno_mat,
							nro_tarjeta_alumno_mat,
							cbu_alumno_mat,
							medio_pago_alumno_cuota,
							nro_tarjeta_alumno_cuota,
							cbu_alumno_cuota,
							pago_a_cargo_empresa,
							porcentaje_facturacion_empresa,
							medio_pago_empresa_mat,
							nro_tarjeta_empresa_mat,
							cbu_empresa_mat,
							medio_pago_empresa_cuota,
							nro_tarjeta_empresa_cuota,
							cbu_empresa_cuota,
							empresa,
							email_empresa,
							observaciones
							)
						VALUES(
							{$parametros['alumno']},
							{$parametros['forma_pago']},
							{$parametros['porcentaje_facturacion_alumno']},
							{$parametros['medio_pago_alumno_mat']},
							{$parametros['nro_tarjeta_alumno_mat']},
							{$parametros['cbu_alumno_mat']},
							{$parametros['medio_pago_alumno_cuota']},
							{$parametros['nro_tarjeta_alumno_cuota']},
							{$parametros['cbu_alumno_cuota']},
							{$parametros['pago_a_cargo_empresa']},
							{$parametros['porcentaje_facturacion_empresa']},
							{$parametros['medio_pago_empresa_mat']},
							{$parametros['nro_tarjeta_empresa_mat']},
							{$parametros['cbu_empresa_mat']},
							{$parametros['medio_pago_empresa_cuota']},
							{$parametros['nro_tarjeta_empresa_cuota']},
							{$parametros['cbu_empresa_cuota']},
							{$parametros['empresa']},
							{$parametros['email_empresa']},
							{$parametros['observaciones']}
							)
        	";
		kernel::db()->ejecutar($sql);
        	return kernel::db()->ultimo_insert_id('negocio_nsg.nsg_datos_facturacion_seq');					
   	}
	
	 /**
     * parametros: datos_facturacion
     * cache: no
     */
    function actualizar_datos_facturacion($datos_facturacion)
    {
		$parametros['alumno'] = $datos_facturacion['datos_facturacion']['alumno'];

		$parametros['forma_pago'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['forma_pago'])) 
			$parametros['forma_pago'] = $datos_facturacion['datos_facturacion']['forma_pago'];

		$parametros['porcentaje_facturacion_alumno'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['porcentaje_facturacion_alumno'])) 
			$parametros['porcentaje_facturacion_alumno'] = $datos_facturacion['datos_facturacion']['porcentaje_facturacion_alumno'];

		$parametros['medio_pago_alumno_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_alumno_mat'])) 
			$parametros['medio_pago_alumno_mat'] = $datos_facturacion['datos_facturacion']['medio_pago_alumno_mat'];

		$parametros['nro_tarjeta_alumno_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_mat'])) 
			$parametros['nro_tarjeta_alumno_mat'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_mat'];

		$parametros['cbu_alumno_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_alumno_mat'])) 
			$parametros['cbu_alumno_mat'] = $datos_facturacion['datos_facturacion']['cbu_alumno_mat'];

		$parametros['medio_pago_alumno_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_alumno_cuota'])) 
			$parametros['medio_pago_alumno_cuota'] = $datos_facturacion['datos_facturacion']['medio_pago_alumno_cuota'];

		$parametros['nro_tarjeta_alumno_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_cuota'])) 
			$parametros['nro_tarjeta_alumno_cuota'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_alumno_cuota'];

		$parametros['cbu_alumno_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_alumno_cuota'])) 
			$parametros['cbu_alumno_cuota'] = $datos_facturacion['datos_facturacion']['cbu_alumno_cuota'];

		$parametros['porcentaje_facturacion_empresa'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['porcentaje_facturacion_empresa'])) 
			$parametros['porcentaje_facturacion_empresa'] = $datos_facturacion['datos_facturacion']['porcentaje_facturacion_empresa'];

		$parametros['medio_pago_empresa_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_empresa_mat'])) 
			$parametros['medio_pago_empresa_mat'] = $datos_facturacion['datos_facturacion']['medio_pago_empresa_mat'];

		$parametros['nro_tarjeta_empresa_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_mat'])) 
			$parametros['nro_tarjeta_empresa_mat'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_mat'];

		$parametros['cbu_empresa_mat'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_empresa_mat'])) 
			$parametros['cbu_empresa_mat'] = $datos_facturacion['datos_facturacion']['cbu_empresa_mat'];

		$parametros['medio_pago_empresa_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['medio_pago_empresa_cuota'])) 
			$parametros['medio_pago_empresa_cuota'] = $datos_facturacion['datos_facturacion']['medio_pago_empresa_cuota'];

		$parametros['nro_tarjeta_empresa_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_cuota'])) 
			$parametros['nro_tarjeta_empresa_cuota'] = $datos_facturacion['datos_facturacion']['nro_tarjeta_empresa_cuota'];

		$parametros['cbu_empresa_cuota'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['cbu_empresa_cuota'])) 
			$parametros['cbu_empresa_cuota'] = $datos_facturacion['datos_facturacion']['cbu_empresa_cuota'];

		$parametros['empresa'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['empresa'])) 
			$parametros['empresa'] = $datos_facturacion['datos_facturacion']['empresa'];

		$parametros['email_empresa'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['email_empresa'])) 
			$parametros['email_empresa'] = $datos_facturacion['datos_facturacion']['email_empresa'];

		$parametros['observaciones'] = 'null';
		if(!is_null($datos_facturacion['datos_facturacion']['observaciones'])) 
			$parametros['observaciones'] = $datos_facturacion['datos_facturacion']['observaciones'];

		if($parametros['porcentaje_facturacion_empresa'] > 0) {
			$parametros['pago_a_cargo_empresa'] = 'S';
		} else {
			$parametros['pago_a_cargo_empresa'] = 'N';
		}
		$parametros['pago_a_cargo_empresa'] = kernel::db()->quote($parametros['pago_a_cargo_empresa']);

	$sql = "
                UPDATE negocio_nsg.nsg_datos_facturacion SET forma_pago = {$parametros['forma_pago']},
								porcentaje_facturacion_alumno = {$parametros['porcentaje_facturacion_alumno']},
								medio_pago_alumno_mat = {$parametros['medio_pago_alumno_mat']},
								nro_tarjeta_alumno_mat = {$parametros['nro_tarjeta_alumno_mat']},
								cbu_alumno_mat = {$parametros['cbu_alumno_mat']},
								medio_pago_alumno_cuota = {$parametros['medio_pago_alumno_cuota']},
								nro_tarjeta_alumno_cuota = {$parametros['nro_tarjeta_alumno_cuota']},
								cbu_alumno_cuota = {$parametros['cbu_alumno_cuota']},
								pago_a_cargo_empresa = {$parametros['pago_a_cargo_empresa']},
								porcentaje_facturacion_empresa = {$parametros['porcentaje_facturacion_empresa']},
								medio_pago_empresa_mat = {$parametros['medio_pago_empresa_mat']},
								nro_tarjeta_empresa_mat = {$parametros['nro_tarjeta_empresa_mat']},
								cbu_empresa_mat = {$parametros['cbu_empresa_mat']},
								medio_pago_empresa_cuota = {$parametros['medio_pago_empresa_cuota']},
								nro_tarjeta_empresa_cuota = {$parametros['nro_tarjeta_empresa_cuota']},
								cbu_empresa_cuota = {$parametros['cbu_empresa_cuota']},
								empresa = {$parametros['empresa']},
								email_empresa = {$parametros['email_empresa']},
								observaciones = {$parametros['observaciones']} 
		WHERE alumno = {$parametros['alumno']} ";

		kernel::db()->ejecutar($sql);						
   }

}

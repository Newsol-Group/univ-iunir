<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class act_constancias_nucleo
{
	/**
	 * Registra el Rechazo de una Solicitud de Constancia.
	 */
	function rechazar_solicitud($id_solicitud, $id_persona, $observaciones='')
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);
		$usuario			= toba::db()->quote($id_persona);
		$estado_rechazado	= toba::db()->quote(constancias::estado_solicitud_rechazado);
		$observaciones		= toba::db()->quote($observaciones);

		$sql = "UPDATE 	sga_constancias_solicitud
				SET 	procesado_por = $usuario,
						fecha_proceso = now(), 
						estado = $estado_rechazado,
						observaciones = $observaciones 
				WHERE 	solicitud = $id_solicitud
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se rechazó la solicitud con éxito. ************');
	}
	
	/**
	 * Registra la Eliminación de una Solicitud de Constancia (en estado Impresa o Pendiente).
	 */
	function eliminar_solicitud($id_solicitud)
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);
	
		$sql = "DELETE
				FROM	sga_constancias_solicitud
				WHERE	solicitud = $id_solicitud
		";


		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó la solicitud con éxito. ************');
	}

	/**
	 * Elimina los parámetros (valores) asociados a una Solicitud en estado pendiente o impresa, para
	 * luego poder dar de baja la solicitud.
	 */
	function eliminar_parametros_solicitud($id_solicitud)
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);
	
		$sql = "DELETE
				FROM	sga_constancias_solicitud_param
				WHERE	solicitud = $id_solicitud
		";


		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los parámetros de la solicitud con éxito. ************');
	}
	
	/**
	 * Registra la Entrega de una Solicitud de Constancia.
	 */
	function registrar_entrega_solicitud($id_solicitud, $id_persona, $fecha_entrega)
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);
		$usuario			= toba::db()->quote($id_persona);
		$estado_entregado	= toba::db()->quote(constancias::estado_solicitud_entregado);
		$fecha_entrega		= toba::db()->quote($fecha_entrega);
		

		$sql = "UPDATE 	sga_constancias_solicitud
				SET 	entregado_por = $usuario,
						fecha_entrega = $fecha_entrega, 
						estado = $estado_entregado
				WHERE 	solicitud = $id_solicitud
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se registró la entrega de la solicitud con éxito. ************');
	}
	
	/**
	 * Registra la Impresión de una Solicitud de Constancia.
	 */
	function registrar_impresion_solicitud($id_solicitud, $id_persona, $observaciones)
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);
		$usuario			= toba::db()->quote($id_persona);
		$estado			= toba::db()->quote(constancias::estado_solicitud_impreso);
		$observaciones		= toba::db()->quote($observaciones);
                
		$sql = "UPDATE 	sga_constancias_solicitud
				SET 	procesado_por = $usuario,
						fecha_proceso = now(), 
						estado = $estado,
                        observaciones = $observaciones
				WHERE 	solicitud = $id_solicitud
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se registró la impresión de la solicitud con éxito. ************');
	}
	
	/**
	 * Registra la Impresión y la Entrega simultánea de una Solicitud de Constancia. 
	 */
	function registrar_impresion_entrega_solicitud($id_solicitud, $id_persona, $observaciones)
	{
		$id_solicitud		= toba::db()->quote($id_solicitud);
		$usuario			= toba::db()->quote($id_persona);
		$estado				= toba::db()->quote(constancias::estado_solicitud_entregado);
        $observaciones		= toba::db()->quote($observaciones);
		
		$sql = "UPDATE 	sga_constancias_solicitud
				SET 	procesado_por = $usuario,
						fecha_proceso = now(), 
						estado = $estado,
						fecha_entrega = now(),
						entregado_por = $usuario,
                        observaciones = $observaciones
				WHERE 	solicitud = $id_solicitud
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se registró la impresión y entrega de la solicitud con éxito. ************');
	}
	
	function insertar_solicitud($alumno, $constancia, $procesado_por, $estado, $comision)
    {
		$fecha_solicitud = quote(guarani_fecha::get_ahora());
		$interfaz = quote(comunes::interfaz_autogestion);
                $codigo_verificacion = quote(constancias::generar_codigo_verificacion());

		toba::db()->abrir_transaccion();
		$sql = "
				INSERT INTO sga_constancias_solicitud (
					alumno, constancia, fecha_solicitud, procesado_por, interfaz ,estado, codigo_verificacion)
				VALUES ($alumno, $constancia, $fecha_solicitud, $procesado_por , $interfaz, $estado, $codigo_verificacion);
				INSERT INTO sga_constancias_solicitud_param 
					SELECT currval('sga_constancias_solicitud_seq'),'comision', $comision;
				";
		guarani_db::ejecutar($sql);
		toba::db()->cerrar_transaccion();
                
                return toba::db()->recuperar_secuencia('sga_constancias_solicitud_seq');
	}	
}

?>
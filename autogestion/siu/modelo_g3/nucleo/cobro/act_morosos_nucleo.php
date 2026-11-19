<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 *
 *
 * @package Cobro
 * @subpackage Morosos
 */

class act_morosos_nucleo
{
    /**
     * Notifica morosos.
     */
    function notificar_morosos($fecha_morosidad, $nros_transacciones = [])
    {
		
		$sql_insert_morosos = "";
		$nros_transacciones_no_encontradas = $nros_transacciones;
		
		// Si hay transacciones
		if(!empty($nros_transacciones)){
			
			$nros_transacciones_quote = [];

			foreach($nros_transacciones as $nro_transaccion){
				$nros_transacciones_quote[] = toba::db()->quote($nro_transaccion);
			}

			$nros_transacciones_in = implode(', ', $nros_transacciones_quote);

			$sql = "SELECT	sga_insc_cursada.alumno,
							sga_insc_cursada.nro_transaccion
					FROM	sga_insc_cursada
					WHERE	sga_insc_cursada.nro_transaccion IN ($nros_transacciones_in)
					UNION
					SELECT	sga_alumnos.alumno,
							sga_propuestas_aspira.nro_transaccion
					FROM	sga_propuestas_aspira,
							sga_alumnos
					WHERE	sga_propuestas_aspira.persona = sga_alumnos.persona AND
							sga_propuestas_aspira.propuesta = sga_alumnos.propuesta AND
							sga_propuestas_aspira.nro_transaccion IN ($nros_transacciones_in);";

			$datos_morosos = guarani_db::consultar($sql);

			$fecha_morosidad = toba::db()->quote($fecha_morosidad);
			
			foreach($datos_morosos as $dato_moroso){
				$alumno = toba::db()->quote($dato_moroso['alumno']);
				$nro_transaccion = toba::db()->quote($dato_moroso['nro_transaccion']);
				$sql_insert_morosos .= "INSERT INTO int_sq_morosos (alumno, nro_transaccion, fecha) VALUES ($alumno, $nro_transaccion, $fecha_morosidad);";
				
				// Elimino las transacciones que existen en la DB de la variable $nros_transacciones_no_encontradas
				// https://techozu.com/remove-elements-from-an-array-by-value-in-php/
				if (($key = array_search($dato_moroso['nro_transaccion'], $nros_transacciones_no_encontradas)) !== false) {
					 unset($nros_transacciones_no_encontradas[$key]);
				}
			}
		
		}
		
        toba::db()->abrir_transaccion();
        try {
			// Limpio la tabla 'int_sq_morosos'
			$sql_delete = "DELETE FROM int_sq_morosos";
			guarani_db::ejecutar($sql_delete);
			
			// Si las transacciones existen en la DB las registro en la tabla 'int_sq_morosos'
			if ($sql_insert_morosos) {
				guarani_db::ejecutar($sql_insert_morosos);
			}

            toba::db()->cerrar_transaccion();
        } catch (toba_error_db $e) {
            toba::db()->abortar_transaccion();
            toba::logger()->debug($e->getMessage());
            toba::logger()->info('************ Hubo un inconveniente al actualizar los datos ************');
            return [false, []];
        }

        return [true, $nros_transacciones_no_encontradas];
    }

}
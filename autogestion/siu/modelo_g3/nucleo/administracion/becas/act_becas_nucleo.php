<?php
/**
 *
 * @package 	Administración
 * @subpackage 	Becas
 */
class act_becas_nucleo
{
	function get_id_proceso()
	{
		$sql = "SELECT nextval('nro_proceso_seq')";
		return guarani_db::ejecutar($sql);
	}

	function eliminar_alumnos_becas()
	{
		$sql = "DELETE
				FROM	int_alumnos_becas
		";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los alumnos para becas con éxito. ************'); 
	}

	function insertar_alumno_becas($datos, $id_proceso)
	{
		$codigo_institucion = toba::db()->quote(trim(substr($datos[0], 0, 10)));
		$nombre_institucion = toba::db()->quote(trim(substr($datos[1], 0, 100)));
		$codigo_facultad = toba::db()->quote(trim(substr($datos[2], 0, 10)));
		$nombre_facultad = toba::db()->quote(trim(substr($datos[3], 0, 100)));
		$codigo_titulo = toba::db()->quote(trim(substr($datos[4], 0, 10)));
		$nombre_titulo = toba::db()->quote(trim(substr($datos[5], 0, 100)));
		$tipo_documento = toba::db()->quote(trim(substr($datos[6], 0, 5)));
		$numero_documento = toba::db()->quote(trim(substr($datos[7], 0, 20)));
		$apellido = toba::db()->quote(trim(substr($datos[8], 0, 100)));
		$nombre = toba::db()->quote(trim(substr($datos[9], 0, 100)));
		$id_proceso = toba::db()->quote($id_proceso);
		$sql = "INSERT INTO int_alumnos_becas (	codigo_institucion,
												nombre_institucion,
												codigo_facultad,
												nombre_facultad,
												codigo_titulo_araucano,
												nombre_titulo_araucano,
												tipo_documento,
												numero_documento,
												apellido,
												nombre,
												id_proceso)
				VALUES ($codigo_institucion,
						$nombre_institucion,
						$codigo_facultad,
						$nombre_facultad,
						$codigo_titulo,
						$nombre_titulo,
						$tipo_documento,
						$numero_documento,
						$apellido,
						$nombre,
						$id_proceso)";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó el alumno para becas con éxito. ************');
	}

	function procesar_alumnos_becas($becas, $anio_academico, $periodo, $id_proceso, $responsable_academica=null, $numero_documento=null)
	{
		$becas = toba::db()->quote($becas);
		$anio_academico = toba::db()->quote($anio_academico);
		$periodo = toba::db()->quote($periodo);
		$id_proceso = toba::db()->quote($id_proceso);
		$responsable_academica = empty($responsable_academica) ? 'null' : toba::db()->quote($responsable_academica); 
		$numero_documento = empty($numero_documento) ? 'null' : toba::db()->quote($numero_documento); 
		$sql = "SELECT f_int_alumnos_becas($becas, $id_proceso, $anio_academico, $periodo, $responsable_academica, true, false, $numero_documento)";
		return guarani_db::ejecutar($sql, true);
	}

    /**
     * Inserta reapertura a beca.
     *
     * @param int $respuesta
     * @param string $observaciones
     * @param string $fecha_hasta
     * @param string $fecha_respuesta_original
     * @param array $datos_censales
     * @param array $requisitos
     */
    function insertar_reapertura_beca($respuesta, $observaciones, $fecha_hasta, $fecha_respuesta_original, $datos_censales, $requisitos)
    {
        $observaciones = addslashes($observaciones);
        // https://popsql.com/learn-sql/postgresql/how-to-insert-data-into-an-array-in-postgresql
        $datos_censales_array = '{'.implode(',', $datos_censales).'}';
        $requisitos_array = '{'.implode(',', $requisitos).'}';
        $sql = "INSERT INTO gde_becas_reapertura (respuesta, observaciones, fecha_hasta, fecha_respuesta_original, datos_censales, requisitos)
                VALUES 
                ({$respuesta}, '{$observaciones}', '{$fecha_hasta}', '{$fecha_respuesta_original}', '{$datos_censales_array}', '{$requisitos_array}')
        ";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se insertó reapertura a beca con éxito. ************');
    }

    /**
     * Marco requisitos del alumno como rechazados.
     *
     * @param int $habilitacion
     * @param int $persona
     * @param array $requisitos
     */
    function rechazar_requisitos_alumno($habilitacion, $persona, $requisitos)
    {
        $requisitos = implode(', ', $requisitos);
        $sql = "UPDATE gde_alumnos_requisitos
                SET rechazado = '".comunes::si."'
                WHERE habilitacion = {$habilitacion}
                AND persona = {$persona}
                AND requisito IN ({$requisitos})
        ";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se rechazó requisitos del alumno con éxito. ************');
    }
}
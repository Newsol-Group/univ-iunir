<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernelDb as error_kernel_db;
use siu\modelo\lib\catalogo;
use siu\errores\error_preinscripcion_cnx_bd;
use siu\modelo\g3\g3;
use siu\modelo\util;

class consultas
{
	//--------------------------------------------------------------------------
	//---- Consulta versi�n de la base -----------------------------------------
	//--------------------------------------------------------------------------

	function get_version_base()
	{
		$sql = "SELECT 		version_base
				FROM 		app_versiones_base 
				ORDER BY	id_conversion DESC
				LIMIT 		1
				";

		try {
			$datos = util::catalogo()->consultar_fila($sql);
			$salida = $datos['version_base'];
		} catch (error_kernel_db $e) {
			throw new error_preinscripcion_cnx_bd('error_cnx_bd');
		}

		return $salida;
	}

	//--------------------------------------------------------------------------
	//---- Configuraci�n del formulario de Preinscripci�n ----------------------
	//--------------------------------------------------------------------------

	/**
	 * Tipos de propuestas
	 * 
	 * @return array
	 */	
	function get_propuestas_tipos()
	{
		$sql = "SELECT 
					propuesta_tipo,
					descripcion
				FROM 
					sga_propuestas_tipos;
				";

		return util::catalogo()->consultar($sql);
	}
	
	/**
	 * Campos de la tabla sga_preinscripcion
	 * 
	 * @return array
	 */
	function get_campos_formulario_preinscripcion()
	{
		$sql = "SELECT	column_name
				FROM	information_schema.columns
				WHERE	table_name = 'sga_preinscripcion'
				";

		$datos = util::catalogo()->consultar($sql);
		return aplanar_matriz($datos, 'column_name');
	}
	
	function get_parametros_campos_form($propuesta_tipo = null)
	{
		if ($propuesta_tipo) {
			$where = 'WHERE propuesta_tipo = ' . kernel::db()->quote($propuesta_tipo);
		} else {
			$where = '';
		}

		$sql = "SELECT 
					propuesta_tipo,
					columna,
					visible,
					obligatorio,
					se_imprime,
					genera_reporte
				FROM 
					sga_campos_conf
				$where";

		return kernel::db()->consultar($sql);
	}
	
	//--------------------------------------------------------------------------
	//---- Datos de Preinscripci�n ---------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene los datos b�sicos de una cuenta a partir del nombre de e_mail
	 * 
	 * @param string $e_mail
	 * @return array
	 */
	function get_datos_basicos_cuenta($e_mail)
	{
		$e_mail = kernel::db()->quote($e_mail);

		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion, 
					sga_preinscripcion.e_mail, 

					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,	
					COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,	

					sga_preinscripcion.pais_documento, 
					sga_preinscripcion.tipo_documento, 
					sga_preinscripcion.nro_documento, 
					sga_preinscripcion.nacionalidad
				FROM 
					sga_preinscripcion
				WHERE
					sga_preinscripcion.e_mail = $e_mail
				";

		$datos = kernel::db()->consultar_fila($sql);
		
		// Campos descriptivos que se obtienen de G3
		$datos['pais_documento_desc'] = g3::entidad('paises')->get_nombre($datos['pais_documento']);
		$datos['tipo_documento_desc'] = g3::entidad('tipos_documentos_pais')->get_nombre($datos['pais_documento']."_".$datos['tipo_documento']);
		//g3::entidad('tipos_documentos_pais')->get_nombre($datos['tipo_documento']);
		$datos['tipo_documento_desc_abreviada'] = g3::entidad('tipos_documentos_pais')->get_nombre_abreviado($datos['pais_documento']."_".$datos['tipo_documento']);
		$datos['nacionalidad_desc'] = g3::entidad('nacionalidades')->get_nombre($datos['nacionalidad']);
		
		return $datos;
	}
	
	/**
	 * Obtiene todos los datos del formulario de preinscripci�n
	 * 
	 * @param int $id_preinscripcion
	 * @return array
	 */
	function get_datos_preinscripcion($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);

		$sql = "SELECT 
					* 
				FROM 
					sga_preinscripcion 
				WHERE 
					id_preinscripcion = $id_preinscripcion";

		$datos = kernel::db()->consultar_fila($sql);
				
		return $datos;
	}
	
	/**
	 * Obtiene los campos del formulario de preinscripci�n con los formateos que 
	 * correspondan para visualizaci�n y los campos descriptivos.
	 * 
	 * @param int $id_preinscripcion
	 * @return array
	 */
	function get_datos_preinscripcion_formateados($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);

		$sql = "SELECT 
					* 
				FROM 
					sga_preinscripcion 
				WHERE 
					id_preinscripcion = $id_preinscripcion";

		$datos = kernel::db()->consultar_fila($sql);
		
		// Se descompone el n�mero de celular para mostrarlo en el formulario
		if (!empty($datos['celular_numero'])) {
			$datos_cel = explode('-', $datos['celular_numero']);
			$datos['celular_caracteristica'] = trim($datos_cel[1]);
			$datos['celular_valor'] = trim($datos_cel[3]);
		}

		// Formateo de fechas
		if (!empty($datos['fecha_nacimiento'])) {
			$datos['fecha_nacimiento'] = convertir_fecha_formato_frontend($datos['fecha_nacimiento']);
		}
		if (!empty($datos['fecha_respre_otorg'])) {
			$datos['fecha_respre_otorg'] = convertir_fecha_formato_frontend($datos['fecha_respre_otorg']);
		}
		if (!empty($datos['fecha_respre_venc'])) {
			$datos['fecha_respre_venc'] = convertir_fecha_formato_frontend($datos['fecha_respre_venc']);
		}
		if (!empty($datos['otorgamiento_visa'])) {
			$datos['otorgamiento_visa'] = convertir_fecha_formato_frontend($datos['otorgamiento_visa']);
		}
		if (!empty($datos['vencimiento_visa'])) {
			$datos['vencimiento_visa'] = convertir_fecha_formato_frontend($datos['vencimiento_visa']);
		}
		if (!empty($datos['fecha_ult_modif'])) {
			$datos['fecha_ult_modif'] = convertir_fecha_formato_frontend($datos['fecha_ult_modif']);
		}
		
		// Campos descriptivos que se obtiene de G3
		$datos['pais_documento_desc'] = g3::entidad('paises')->get_nombre($datos['pais_documento']);
		$datos['tipo_documento_desc'] = g3::entidad('tipos_documentos_pais')->get_nombre($datos['pais_documento']."_".$datos['tipo_documento']);
		$datos['tipo_documento_desc_abreviada'] = g3::entidad('tipos_documentos_pais')->get_nombre_abreviado($datos['pais_documento']."_".$datos['tipo_documento']);
		$datos['nacionalidad_desc'] = g3::entidad('nacionalidades')->get_nombre($datos['nacionalidad']);
		
		$datos['loc_nac_descr'] = g3::entidad('localidades')->get_nombre($datos['loc_nacimiento']);
		$datos['loc_per_lect_descr'] = g3::entidad('localidades')->get_nombre($datos['loc_per_lect']);
		$datos['loc_proc_descr'] = g3::entidad('localidades')->get_nombre($datos['loc_proc']);
		$datos['loc_pers_alleg_descr'] = g3::entidad('localidades')->get_nombre($datos['loc_pers_alleg']);
		
		$datos['colegio_secundario_descr'] = g3::entidad('colegios')->get_nombre($datos['colegio_secundario']);
		$datos['titulo_secundario_descr'] = g3::titulos_secundario()->get_nombre($datos['titulo_secundario']);
		
		return $datos;
	}
	
	/**
	 * Obtiene las propuestas en las que est� inscripto el aspirante
	 * 
	 * @param string $id_preinscripcion
	 * @return array
	 */
	/**
	 * 10/09/2025
	 * Newsol - Joaquin	
	 * Se agrega la columna preinscripcion_propuesta a la consulta
	 */
	function get_propuestas_insc($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);
		
		$sql = "SELECT 
					id_preinscripcion,
					preinscripcion_propuesta,
					responsable_academica,
					propuesta,
					ubicacion,
					modalidad,
					periodo_insc,
					estado,
					fecha_preinscripcion,
					fecha_fin_vigencia,
					observaciones
				FROM 
					sga_preinscripcion_propuestas
				WHERE
					sga_preinscripcion_propuestas.id_preinscripcion = $id_preinscripcion
					AND fecha_fin_vigencia >= CURRENT_DATE
				ORDER BY
					responsable_academica
				";
		
		$datos = kernel::db()->consultar($sql);
		
		// Si corresponde, se obtienen las descripciones
		$salida = array();
		foreach ($datos as $dato) {
			$dato['ra_nombre'] = g3::responsables_academicas()->get_nombre($dato['responsable_academica']);
			$dato['propuesta_nombre'] = g3::propuestas()->get_nombre($dato['propuesta']);
			$dato['ubicacion_nombre'] = g3::entidad('ubicaciones')->get_nombre($dato['ubicacion']);
			$dato['modalidad_nombre'] = g3::entidad('modalidades')->get_nombre($dato['modalidad']);
			$dato['propuesta_tipo'] = g3::propuestas()->get_id_tipo_propuesta($dato['propuesta']);
			$dato['tipo_propuesta_descr'] = g3::propuestas()->get_tipo_propuesta($dato['propuesta']);
			// Si se obtuvieron descripciones es porque pas� el filtro de perfil de datos
			if ((!empty($dato['ra_nombre'])) && (!empty($dato['propuesta_nombre'])) && (!empty($dato['ubicacion_nombre'])) 
				&& (!empty($dato['modalidad_nombre'])) && (!empty($dato['propuesta_tipo'])) && (!empty($dato['tipo_propuesta_descr']))) {
				$salida[] = $dato;
			}
		}
		
		return $salida;
	}
	
	/**
	 * Obtiene los datos de actuaci�n docente cargados por el aspirante
	 * 
	 * @param int $id_preinscripcion
	 * @return array
	 */
	function get_datos_actuacion_docente($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);
		
		$sql = "SELECT 
					actuacion_doc,
					id_preinscripcion,
					institucion,
					institucion_prein,
					cargo,
					materia,
					hora_sem_trab,
					fecha_desde,
					fecha_hasta
				FROM 
					sga_actuacion_doc
				WHERE
					id_preinscripcion = $id_preinscripcion";
		
		$datos = kernel::db()->consultar($sql);
		
		// Ajuste de datos para visualizaci�n
		$nuevo = array();
		foreach ($datos as $dato) {
			if (!empty($dato['hora_sem_trab'])) {
				$horas_descr = util::opciones()->get_hora_sem_trab_alum();
				$dato['hora_sem_trab_descr'] = $horas_descr[$dato['hora_sem_trab']];
			} else {
				$dato['hora_sem_trab_descr'] = '';
			}

			if (!empty($dato['fecha_desde'])) {
				$dato['fecha_desde'] = convertir_fecha_formato_frontend($dato['fecha_desde']);
			}

			if (!empty($dato['fecha_hasta'])) {
				$dato['fecha_hasta'] = convertir_fecha_formato_frontend($dato['fecha_hasta']);
			}

			//---- ID encriptado -------------------------------------------
			$id = util::catalogo()->generar_id($dato['actuacion_doc']);
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		
		return $nuevo;
	}
	
	/**
	 * Obtiene los datos de actuaci�n profesional cargados por el aspirante
	 * 
	 * @param int $id_preinscripcion
	 * @return array
	 */
	function get_datos_actuacion_profesional($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);
		
		$sql = "SELECT 
					actuacion_prof,
					id_preinscripcion,
					empresa,
					cargo,
					area,
					hora_sem_trab,
					fecha_desde,
					fecha_hasta
				FROM 
					sga_actuacion_prof
				WHERE
					id_preinscripcion = $id_preinscripcion";
		
		$datos = kernel::db()->consultar($sql);
		
		// Ajuste de datos para visualizaci�n
		$nuevo = array();
		foreach ($datos as $dato) {
			
			$dato['antec_empresa'] = $dato['empresa'];
			
			if (!empty($dato['hora_sem_trab'])) {
				$horas_descr = util::opciones()->get_hora_sem_trab_alum();
				$dato['hora_sem_trab_descr'] = $horas_descr[$dato['hora_sem_trab']];
			} else {
				$dato['hora_sem_trab_descr'] = '';
			}

			if (!empty($dato['fecha_desde'])) {
				$dato['fecha_desde'] = convertir_fecha_formato_frontend($dato['fecha_desde']);
			}

			if (!empty($dato['fecha_hasta'])) {
				$dato['fecha_hasta'] = convertir_fecha_formato_frontend($dato['fecha_hasta']);
			}

			//---- ID encriptado -------------------------------------------
			$id = util::catalogo()->generar_id($dato['actuacion_prof']);
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		
		return $nuevo;
	}

	/**
	 * Obtiene los datos de formaci�n acad�mica cargados por el aspirante
	 * 
	 * @param int $id_preinscripcion
	 * @return array
	 */
	function get_datos_formacion_acad($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);
		
		$sql = "SELECT 
					formacion_acad,
					id_preinscripcion,
					titulo_superior,
					titulo_sup_prein,
					institucion,
					institucion_prein,
					fecha_ingreso,
					fecha_egreso,
					titulo_tesis,
					director_tesis,
					categoria_coneau,
					duracion_teorica,
					nivel_estudio,
					estado
				FROM 
					sga_formacion_acad
				WHERE
					id_preinscripcion = $id_preinscripcion";
		
		$datos = kernel::db()->consultar($sql);
		
		// Ajuste de datos para visualizaci�n
		$nuevo = array();
		foreach ($datos as $key => $dato) {
			// Ajustes para visualizaci�n
			if (!empty($dato['categoria_coneau'])) {
				$categorias_coneau = util::opciones()->get_categorias_coneau();
				$categoria = trim($dato['categoria_coneau']);
				$dato['categoria_coneau'] = $categoria;
				$dato['categoria_coneau_descr'] = $categorias_coneau[$categoria];
			} else {
				$dato['categoria_coneau_descr'] = '';
			}

			if (!empty($dato['nivel_estudio'])) {
				$niveles = util::opciones()->get_niveles_estudio_fa();
				$dato['nivel_estudio_descr'] = $niveles[$dato['nivel_estudio']];
			} else {
				$dato['nivel_estudio_descr'] = '';
			}

			if (!empty($dato['estado'])) {
				$estados = util::opciones()->get_estados_antecedente_fa();
				$dato['estado_descr'] = $estados[$dato['estado']];
			} else {
				$dato['estado_descr'] = '';
			}

			if (!empty($dato['fecha_ingreso'])) {
				$dato['fecha_ingreso'] = convertir_fecha_formato_frontend($dato['fecha_ingreso']);
			}

			if (!empty($dato['fecha_egreso'])) {
				$dato['fecha_egreso'] = convertir_fecha_formato_frontend($dato['fecha_egreso']);
			}

			//---- ID encriptado -------------------------------------------
			$id = util::catalogo()->generar_id($dato['formacion_acad']);
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		
		return $nuevo;
	}
	
	//--------------------------------------------------------------------------
	//---- PERFIL DE DATOS -----------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene la configuraci�n establecida de perfil de datos
	 * 
	 * @return array
	 */
	function get_config_perfil_datos()
	{
		$id_pto_acceso = kernel::db()->quote($_SERVER['ID_PTO_ACCESO']);
		
		$sql = "SELECT 
					id_pto_acceso,
					ra,
					propuesta_tipo,
					propuesta,
					ubicacion
				FROM 
					cfg_perfiles_datos
				WHERE
					id_pto_acceso = $id_pto_acceso
				ORDER BY
					ra,
					propuesta_tipo,
					propuesta
				";
		
		$datos = kernel::db()->consultar($sql);
		$salida = array(
			'ra' => array(),
			'propuesta_tipo' => array(),
			'propuesta' => array(),
			'ubicacion' => array(),
		);
		foreach ($datos as $dato) {
			if (!is_null($dato['ra'])) {
				$salida['ra'][] = $dato['ra'];
			}
			if (!is_null($dato['propuesta_tipo'])) {
				$salida['propuesta_tipo'][] = $dato['propuesta_tipo'];
			}
			if (!is_null($dato['propuesta'])) {
				$salida['propuesta'][] = $dato['propuesta'];
			}
			if (!is_null($dato['ubicacion'])) {
				$salida['ubicacion'][] = $dato['ubicacion'];
			}
		}
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- Turno de presentaci�n de documentaci�n ------------------------------
	//--------------------------------------------------------------------------

	function get_turnos_presentacion($id_preinscripcion)
	{
		// Recupero las preinscripciones a propuestas
                $preinscripcion_propuestas = kernel::persona()->datos()->get_datos_inscripciones();
                
		$salida = [];
                // Recorro las preinscripciones a propuestas
		foreach ($preinscripcion_propuestas as $preinscripcion_propuesta) {

                        $responsable_academica = $preinscripcion_propuesta['responsable_academica'];

                        // Hay turnos de presentaci�n de documentaci�n disponibles
                        $hay_turnos = g3::turnos()->hay_turnos_preinscripcion_propuesta(
                            $responsable_academica,
                            $preinscripcion_propuesta['ubicacion'],
                            $preinscripcion_propuesta['modalidad']);

                        // Turno asignado
                        $turno_asignado = g3::turnos()->get_turno_presentacion($id_preinscripcion, $responsable_academica);

                        // Hay turno asignado
                        $hay_turno_asignado = !empty($turno_asignado);

                        // Si no hay turnos de presentaci�n de documentaci�n disponibles
                        // ni tampoco turno asignado
                        // salteo a la siguiente preinscripci�n a propuesta
                        if (!$hay_turnos && !$hay_turno_asignado) {
                            continue;
                        }

                        // Si ya existe la RA en $salida salteo a la siguiente preinscripci�n a propuesta
                        if (isset($salida[$responsable_academica])) {
                            continue;
                        }

                        $nombre = g3::responsables_academicas()->get_nombre($responsable_academica);

                        // Si la RA tiene nombre
			if (!empty($nombre)) {

                                // Si hay turno asignado
				if ($hay_turno_asignado) {
					$pre_turno = $turno_asignado['pre_turno'];
					$turno_fecha = convertir_fecha_formato_frontend($turno_asignado['fecha']);
					$turno_hora = convertir_hora_formato_frontend($turno_asignado['hora']);
					$turno_operador = $turno_asignado['operador'];
					$asignado = true;
				} else {// Si no hay turno asignado
                                        $msg_no_asignado = kernel::traductor()->trans('turno.no_asignado');
					$pre_turno = null;
					$turno_fecha = $msg_no_asignado;
					$turno_hora = $msg_no_asignado;
					$turno_operador = $msg_no_asignado;
					$asignado = false;
				}

				$salida[$responsable_academica] = [
					'pre_turno' => $pre_turno,
					'responsable_academica_plana' => $responsable_academica,
					'responsable_academica' => util::catalogo()->generar_id($responsable_academica),
					'responsable_academica_nombre' => $nombre,
					'fecha' => $turno_fecha,
					'hora' => $turno_hora,
					'operador' => $turno_operador,
					'asignado' => $asignado,
				];
			}
		}
		
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- Requisitos digitales ------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene todos los datos del formulario de preinscripci�n
	 * 
	 * @param int $id_preinscripcion
	 * @return array
	 */
	function get_requisitos_digitales($id_preinscripcion)
	{
		$id_preinscripcion = kernel::db()->quote($id_preinscripcion);

		$sql = "SELECT 
					requisito_digital,
					id_preinscripcion,
					requisito,
					id_documento
				FROM 
					sga_requisitos_digitales
				WHERE 
					id_preinscripcion = $id_preinscripcion";

		$datos = kernel::db()->consultar($sql);
		return $datos;
	}
}
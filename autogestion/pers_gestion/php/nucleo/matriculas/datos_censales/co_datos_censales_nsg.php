<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Datos Censales
 * 
 */
class co_datos_censales_nsg extends co_datos_censales
{
	/**Newsol (NUEVO) - Retorna la localidad (pais, provincia, localidad) de los Alumnos declarados en el último dato censal
	 * @param <type> $filtro
	 * @return <type>
	 */
	function get_alumnos_x_localidad($filtro = '')
	{

		$where = '';
		if ($filtro != '') {
			$where .= " AND $filtro";
		}
		$sql = "	SELECT	vw_perfil_de_datos_propuestas.propuesta,
						vw_perfil_de_datos_propuestas.codigo,
						vw_perfil_de_datos_propuestas.nombre AS propuesta_nombre,
						'(' || vw_perfil_de_datos_propuestas.codigo || ') ' || vw_perfil_de_datos_propuestas.nombre AS propuesta_datos,
						sga_ubicaciones.ubicacion,
						sga_ubicaciones.nombre AS ubicacion_nombre,
						sga_alumnos.legajo,
						" . guarani_sql::get_case_si_no('sga_alumnos', 'regular', 'regular') . ",
						sga_alumnos_calidad.nombre AS calidad_nombre,
						vw_personas.apellido_nombres AS persona_nombre,
						vw_personas.tipo_nro_documento AS tipo_nro_documento,
						mdp_datos_censales.dato_censal,
						mdp_datos_personales.procedencia_localidad,
						vw_localidades.localidad,
						vw_localidades.localidad_nombre,
						vw_localidades.dpto_partido_nombre,
						vw_localidades.provincia_nombre,
						vw_localidades.pais,
						vw_localidades.pais_nombre,
						vw_localidades.continente_nombre

				FROM	sga_alumnos,
						sga_alumnos_calidad,
						sga_ubicaciones,
						vw_personas,
						vw_perfil_de_datos_propuestas,
						mdp_datos_censales,
						mdp_datos_personales,
						vw_localidades

				WHERE	sga_alumnos.ubicacion						= sga_ubicaciones.ubicacion
				AND		sga_alumnos.calidad					= sga_alumnos_calidad.calidad
				AND		vw_personas.persona					= sga_alumnos.persona
				AND		vw_perfil_de_datos_propuestas.propuesta = sga_alumnos.propuesta
				AND		mdp_datos_censales.dato_censal				= (SELECT get_ultimo_dato_censal(vw_personas.persona))
				AND		mdp_datos_personales.dato_censal			= mdp_datos_censales.dato_censal
				AND		mdp_datos_personales.periodo_lectivo_localidad 	= vw_localidades.localidad
				$where
				ORDER BY vw_perfil_de_datos_propuestas.propuesta, sga_ubicaciones.ubicacion, vw_personas.apellido, vw_personas.nombres";

		return guarani_db::consultar($sql);
	}
}
?>
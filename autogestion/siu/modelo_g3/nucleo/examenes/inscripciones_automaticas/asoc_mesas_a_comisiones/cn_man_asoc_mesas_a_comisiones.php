<?php
/**
 *
 * 
 * @package 	Exámenes
 * @subpackage 	Inscripciones
 * 
 */
class cn_man_asoc_mesas_a_comisiones extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'asoc_mesas_a_comisiones';
	}

	function get_listado($filtro)
	{
		/**
		 * Sólo se recuperarán mesas que:
		 * 	+ Al menos tengan definida la instancia "Regular" (ya que las inscripciones serán para alumnos regulares)
		 * 	+ Fecha del examen mayor a la fecha actual
		 *  + Estado de los llamados mesa Activo (no Suspendido).
		 */
		if ($filtro != '') {
			$filtro .= ' AND ';
		}
		$filtro .= ' EXISTS( SELECT	
    							sga_mesas_examen_instancias.mesa_examen
    						FROM
            					sga_mesas_examen_instancias
            				WHERE
            					sga_mesas_examen_instancias.mesa_examen = sga_mesas_examen.mesa_examen
                				AND sga_mesas_examen_instancias.instancia = ' . mesa_de_examen::inst_exam_regular . ') ';
		$filtro .= " AND sga_llamados_mesa.fecha > current_date ";
		$filtro .= " AND sga_llamados_mesa.estado = '" . mesa_de_examen::mesa_estado_activo . "'";
		$datos = toba::consulta_php('co_mesas_de_examen')->get_listado($filtro);
		return $datos;
	}

	function cargar_atributos_parametros($mesa_examen)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}
}
?>
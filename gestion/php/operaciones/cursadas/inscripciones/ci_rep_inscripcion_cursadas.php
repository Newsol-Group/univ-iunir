<?php
/**
	*
	* 
	* @package        Cursadas
	* @subpackage    Reportes
	* 
	*/
class ci_rep_inscripcion_cursadas extends ci_reporte
{
	protected $msg_eof_cuadro = '800_eof_cuadro_sinfiltro';
	protected $s__filtro_requisito_no_cumplido;
    //La variables siguientes son usadas para indicar que query ejecutar
    protected $s__query_aceptadas_pendientes;
    protected $s__query_rechazadas_bajas;

    //--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}

		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('estado')->set_condicion_fija('es_igual_a');
		$filtro->columna('resp_acad')->set_condicion_fija('es_igual_a');
		$filtro->columna('requisito_no_cumplido')->set_condicion_fija('es_igual_a');
	}

	/**
		* Redefinición del evento filtrar, para que arme una cadena WHERE acorde con la
		* nueva estructura de modalidades de cursada de comisión.
		*/
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			$filtro_ras = '';
			$this->s__filtro_requisito_no_cumplido = '';
            $this->s__query_aceptadas_pendientes = false;
            $this->s__query_rechazadas_bajas = false;

			// La Query consulta 2 vistas vw_insc_cursada y vw_insc_cursada_log, eta ultima tiene un alias vw_insc_cursada, para unificar el uso de los filtros.
			if (isset($datos['requisito_no_cumplido'])) {
				$this->s__filtro_requisito_no_cumplido = "AND vw_insc_cursada.operacion = 'R' 
															AND EXISTS (SELECT 1 FROM sga_insc_cursada_log_requisitos 
															WHERE inscripcion = vw_insc_cursada.inscripcion AND sga_insc_cursada_log_requisitos.requisito =" . $datos['requisito_no_cumplido']['valor']. ")";
			}

			$this->dep('filtro')->columna('requisito_no_cumplido')->resetear_estado();

			if (isset($datos['estado'])) {
				
				if (($datos['estado']['valor'] == 'A') || ($datos['estado']['valor'] == 'P')) {
					// Inscripciones Aceptadas o Pendientes
                    $this->s__query_aceptadas_pendientes = true;
                    $this->s__query_rechazadas_bajas = false;
                    
				} else {
					//  Inscripciones Rechazadas o dadas de Baja.
                    $this->s__query_aceptadas_pendientes = false;
                    $this->s__query_rechazadas_bajas = true;
                    
				}

            } else {
                $this->s__query_aceptadas_pendientes = true;
                $this->s__query_rechazadas_bajas = true;
            }

			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}

			if (isset($datos['resp_acad'])) {
				$filtro_ras .= " AND sga_propuestas.propuesta IN (SELECT sga_propuestas.propuesta FROM sga_propuestas, sga_propuestas_ra, sga_responsables_academicas WHERE sga_responsables_academicas.responsable_academica = '{$datos['resp_acad']['valor']}' AND sga_responsables_academicas.responsable_academica = sga_propuestas_ra.responsable_academica AND sga_propuestas.propuesta = sga_propuestas_ra.propuesta)";
				$this->dep('filtro')->columna('resp_acad')->resetear_estado();
			}
		
			$filtro = $filtro_modalidad . $filtro_ras;
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__datos)) {
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
			$cuadro->set_eof_mensaje($this->get_msg_eof());
		} elseif (toba::zona()->cargada()) {
			$datos = $this->get_datos('sga_comisiones.comision = ' . toba::zona()->get_editable());
			$cuadro->set_datos($datos);
			$cuadro->set_eof_mensaje($this->get_msg_eof());
		} else {
			$cuadro->set_eof_mensaje(guarani_mensajes::get($this->msg_eof_cuadro));
		}
		$cuadro->set_filtro_asociado($this->dep('filtro'));
	}

	function get_tabla_principal()
	{
		return 'sga_insc_cursada';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_inscripciones_cursadas')->get_listado_vista($filtro, false, $this->s__filtro_requisito_no_cumplido, $this->s__query_aceptadas_pendientes, $this->s__query_rechazadas_bajas);
	}
	
	function get_requisitos_cursadas()
	{
		$accion_cursadas='1';//accion por examen
		return toba::consulta_php('co_requisitos_x_accion')->get_requisitos_x_accion_distinct($accion_cursadas);
	}
}
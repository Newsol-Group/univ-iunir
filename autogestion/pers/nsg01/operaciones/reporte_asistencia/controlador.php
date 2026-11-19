<?php
namespace nsg01\operaciones\reporte_asistencia;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;
use \nsg01\guarani;

class controlador extends \siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro
{
	public $hash;
	
	protected $params_comision;
	protected $comision;
	protected $subcomision;

        /**
         * @return int Id de la comisión.
         */
        public function getComision()
        {
            return $this->comision;
        }

        /**
         * @return int Id de la Subcomisión.
         */
        public function getSubcomision()
        {
            return $this->subcomision;
        }

	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'info_subcomision':
			case 'info_comision':
			case 'generar_pdf_comision':
			case 'generar_excel_comision':
				return 'vista_comision';
		}
		return 'vista';
	}

	/**
	 * @return \siu\modelo\transacciones\reporte_asistencia
	 */
	function modelo()
	{
		return guarani::reporte_asistencia();
	}

	function get_info_zona()
	{
		if ($this->get_clase_vista() == 'vista_comision' && $this->is_zona_activa()) {
			return array('zona' => 'comision', 'seleccion' => $this->params_comision);
		}
	}

	function is_zona_activa()
	{
		return kernel::request()->getParam('z', 1) == 1;
	}

	function accion__generar_pdf_comision()
	{
		$data_encabezado = $this->decodificar_parametro();
		
		$pl = $this->vista()->pagelet('items');
		$pl->data['comision'] = $data_encabezado;
		$pl->set_comision($this->hash);

		if ($data_encabezado['es_subcomision']) {
			
			$pl->set_subcomision($this->hash);
			$pl->data['alumnos_comision'] = $this->get_info_subcomision();

		} else {

			$pl->data['alumnos_comision'] = $this->get_info_comision();

		}

		$pl->post_configurar();
		list($path_archivo, $nombre_archivo) = $pl->generar_pdf();
                $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__generar_excel_comision()
	{
		$data_encabezado = $this->decodificar_parametro();
		
		$pl = $this->vista()->pagelet('items');
		$pl->data['comision'] = $data_encabezado;
		$pl->set_comision($this->hash);

		if ($data_encabezado['es_subcomision']) {
			
			$pl->set_subcomision($this->hash);
			$pl->data['alumnos_comision'] = $this->get_info_subcomision();

		} else {

			$pl->data['alumnos_comision'] = $this->get_info_comision();

		}

		$pl->post_configurar();
		$pl->generar_excel();
	}

	function accion__info_comision()
	{
		$data_encabezado = $this->decodificar_parametro();

		$pl = $this->vista()->pagelet('items');
		$pl->data['comision'] = $data_encabezado;
		$pl->set_comision($this->hash);

		if ($data_encabezado['es_subcomision']) {

			$pl->set_subcomision($this->hash);
			$pl->data['alumnos_comision'] = $this->get_info_subcomision();

		} else {

			$pl->data['alumnos_comision'] = $this->get_info_comision();

		}

	}

	function accion__info_subcomision()
	{
		$data_comision = $this->decodificar_parametro();
		$pl = $this->vista()->pagelet('items');
		$pl->set_subcomision($this->hash);
		$pl->set_comision($this->hash);
		$pl->data['alumnos_comision'] = $this->get_info_subcomision();
		$pl->data['comision'] = $data_comision;
	}

	/**
	 * @return array
	 */
	function decodificar_parametro()
	{
		$this->hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$data_comision = $this->decodificar_hash($this->hash, 'info_comision');
		$this->params_comision = $data_comision;
		$this->comision = $data_comision['comision'];

		if (isset($data_comision['subcomision'])) {
			$this->subcomision = $data_comision['subcomision']; //POR LA ZONA NO VIENE!  FIX?? COMO??
		} else {
			// Se ingresó por la zona, deben buscarse más datos de la comisión que no vienen en la consulta
			$data_comision = $this->modelo()->info__comision_cabecera($data_comision['comision']);
			$this->subcomision = null;
		}
		return $data_comision;
	}

	function accion__buscar_actividad()
	{
		$json = array();
		$actividades = catalogo::consultar('unidad_academica', 'actividades', array(
					'term' => $this->getAutocompleteTerm(),
					'plan_version' => null
		));
		$json[] = array('id' => '', 'label' => 'Todas');
		foreach ($actividades as $actividad) {
			$json[] = array(
				'id' => $actividad['elemento'],
				'label' => $actividad['nombre'] . ' (' . $actividad['codigo'] . ')'
			);
		}
		$this->render_raw_json($json);
	}

	function accion__buscar_docente()
	{
		$json = array();
		$docentes = catalogo::consultar('docente', 'get_docentes_activos', array('apellido' => $this->getAutocompleteTerm()));
		foreach ($docentes as $docente) {
			$json[] = array(
				'id' => $docente['docente'],
				'label' => $docente['apellido'] . ', ' . $docente['nombres'] . ' (' . $docente['legajo'] . ')'
			);
		}
		$this->render_raw_json($json);
	}

	/**
	 * @return ProcesadorVista
	 */
	function get_lista_comisiones()
	{
		$datos = isset($this->datos_filtro) ? $this->datos_filtro : array();
		return $this->codificador_modelo()->info__lista_comisiones($datos);
	}

	function get_info_subcomision()
	{
		$datos = $this->modelo()->info__subcomision($this->comision, $this->subcomision);
                
                $i = 1;
                $msj_sin_definir = kernel::traductor()->trans('reporte_asistencia.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['apellido_nombres'] = $dato['apellido_nombres'] ?? $msj_sin_definir;
			$datos[$key]['nro_identificacion'] = $dato['nro_identificacion'] ?? $msj_sin_definir;
			$datos[$key]['total_inasistencias'] = $dato['total_inasistencias'];
			$datos[$key]['total_justificadas'] = $dato['total_justificadas'] ?? $msj_sin_definir;
			$datos[$key]['libre_descr'] = $dato['libre_descr'] ?? $msj_sin_definir;
			$datos[$key]['total_clases_hasta_hoy'] = $dato['total_clases_hasta_hoy'] ?? $msj_sin_definir;
			$datos[$key]['total_clases_previstas_anio'] = $dato['total_clases_previstas_anio'] ?? $msj_sin_definir;
			$datos[$key]['porc_asistencia_hasta_hoy'] = $dato['porc_asistencia_hasta_hoy'] ?? $msj_sin_definir;
			$datos[$key]['porc_asistencia_previstas_anio'] = $dato['porc_asistencia_previstas_anio'] ?? $msj_sin_definir;
			$datos[$key]['orden'] = $i++;
		}

                return $datos;
	}

	function get_info_comision()
	{
		$datos = $this->modelo()->info__comision($this->comision);

                $i = 1;
                $msj_sin_definir = kernel::traductor()->trans('reporte_asistencia.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['apellido_nombres'] = $dato['apellido_nombres'] ?? $msj_sin_definir;
			$datos[$key]['nro_identificacion'] = $dato['nro_identificacion'] ?? $msj_sin_definir;
			$datos[$key]['total_inasistencias'] = $dato['total_inasistencias'];
			$datos[$key]['total_justificadas'] = $dato['total_justificadas'] ?? $msj_sin_definir;
			$datos[$key]['libre_descr'] = $dato['libre_descr'] ?? $msj_sin_definir;
			$datos[$key]['total_clases_hasta_hoy'] = $dato['total_clases_hasta_hoy'] ?? $msj_sin_definir;
			$datos[$key]['total_clases_previstas_anio'] = $dato['total_clases_previstas_anio'] ?? $msj_sin_definir;
			$datos[$key]['porc_asistencia_hasta_hoy'] = $dato['porc_asistencia_hasta_hoy'] ?? $msj_sin_definir;
			$datos[$key]['porc_asistencia_previstas_anio'] = $dato['porc_asistencia_previstas_anio'] ?? $msj_sin_definir;
			$datos[$key]['orden'] = $i++;
		}

                return $datos;
	}

	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\reporte_asistencia\reporte\builder_filtro_reporte');
	}

}
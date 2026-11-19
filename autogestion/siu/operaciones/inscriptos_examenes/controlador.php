<?php
namespace siu\operaciones\inscriptos_examenes;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use siu\guarani;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;
use siu\modelo\entidades\alumno_foto;

class controlador extends controlador_con_filtro
{
	public $hash;
	protected $params_mesa;
	protected $mesa;

        /**
         * @return int Id del llamado mesa.
         */
        public function getLlamadoMesa()
        {
            return $this->mesa;
        }

	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'info_mesa':
			case 'generar_pdf_mesa':
			case 'generar_excel_mesa':
				return 'vista_mesa';
		}
		return 'vista_reporte';
	}

	function modelo()
	{
		return guarani::inscriptos_examenes();
	}


	function accion__info_mesa()
	{
		$this->decodificar_parametro();
		$pl = $this->vista()->pagelet('items');
		$pl->set_mesa($this->hash);
		$mesa = $this->get_info_mesa();
		$pl->data['mesa'] = $mesa;
		$alumnos = $this->get_info_mesa_alumnos();	
		foreach($alumnos as $key => $dato)
		{ $alumnos[$key]['URL'] = kernel::vinculador()->crear('inscriptos_examenes', 'ficha_alumno', array(
				'id_imagen'	=> $dato['id_imagen'],
				'alumno'	=> $dato['alumno'],
				'actividad'	=> $mesa['actividad']
			));
		};
		$pl->data['alumnos'] = $alumnos;
		$pl->data['url_back'] = kernel::vinculador()->crear('inscriptos_examenes', 'index');
	}
	
	function accion__ficha_alumno()
	{
		$id_imagen = $this->validate_param('id_imagen', 'get', validador::TIPO_TEXTO);
		$alumno = $this->validate_param('alumno', 'get', validador::TIPO_INT);
		$actividad = $this->validate_param('actividad', 'get', validador::TIPO_INT);

		$clase = 'operaciones\_comun\pagelets\pagelet_ficha_ultima_cursada';
		$pagelet = kernel::localizador()->instanciar($clase, 'ficha_alumno');
		$pagelet->set_data($this->modelo()->info__examenes($actividad, $alumno));
		$url_imagen = empty($id_imagen) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png') : alumno_foto::url_imagen($id_imagen, alumno_foto::TAMANIO_GRANDE);
		$pagelet->set_url_imagen($url_imagen);
		kernel::renderer()->add($pagelet);
	}
		
	
	function accion__generar_pdf_mesa()
	{
		$this->decodificar_parametro();
		$pl = $this->vista()->pagelet('items');
		$pl->data['mesa'] = $this->get_info_mesa();
		$pl->data['alumnos'] = $this->get_info_mesa_alumnos();
		$pl->post_configurar();
		list($path_archivo, $nombre_archivo) = $pl->generar_pdf();
                $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__generar_excel_mesa()
	{
		$this->decodificar_parametro();
		$pl = $this->vista()->pagelet('items');
		$pl->data['mesa'] = $this->get_info_mesa();
		$pl->data['alumnos'] = $this->get_info_mesa_alumnos();
		$pl->post_configurar();
		$pl->generar_excel();
		//parent::accion__generar_excel();
	}

	/**
	 * @return array
	 */
	public function decodificar_parametro()
	{
		$this->hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$data_mesa = $this->decodificar_hash($this->hash, 'info_mesa');
		
		$this->params_mesa = $data_mesa;
		$this->mesa = $data_mesa['llamado_mesa'];

		//$data_mesa = $this->modelo()->info__comision_cabecera($data_mesa['mesa']);
		return $data_mesa;
	}

	public function accion__buscar_actividad()
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

	public function accion__buscar_docente()
	{
		$json = array();
		$docentes = catalogo::consultar('docente', 'get_docentes_activos', array('apellido' => $this->getAutocompleteTerm()));
		//$json[] = array('id' => '', 'label' => 'Todos');
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
	function get_lista_mesas()
	{
		$datos = isset($this->datos_filtro) ? $this->datos_filtro : array();
		return $this->codificador_modelo()->info__lista_mesas($datos);
	}

	public function get_info_mesa()
	{
		return $this->modelo()->info__mesa_examen(array('llamado_mesa' => $this->mesa));
	}
	
	public function get_info_mesa_alumnos()
	{
                $parametros = [
                    'llamado_mesa' => $this->mesa,
                    'incluir_otros_alumnos' => null,
                    'evaluacion' => null
                ];
		$datos = catalogo::consultar('docente', 'alum_inscriptos_examen', $parametros);

                $msj_sin_definir = kernel::traductor()->trans('inscriptos_examenes.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['nombre_alumno'] = $dato['nombre_alumno'] ?? $msj_sin_definir;
			$datos[$key]['instancia_nombre'] = $dato['instancia_nombre'] ?? $msj_sin_definir;
			$datos[$key]['estado_inscripcion'] = $dato['estado_inscripcion'];
			$datos[$key]['email'] = $dato['email'] ?? $msj_sin_definir;
			$datos[$key]['telefono'] = $dato['telefono'] ?? $msj_sin_definir;
		}

                return $datos;
	}

	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\inscriptos_examenes\reporte\builder_filtro_reporte');
	}

}
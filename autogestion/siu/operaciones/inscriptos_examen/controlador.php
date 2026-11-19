<?php
namespace siu\operaciones\inscriptos_examen;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;
use siu\modelo\entidades\alumno_foto;

class controlador extends controlador_con_filtro
{
	public $hash;
	public $llamado_mesa_params;

        /**
         * @return int Id del llamado mesa.
         */
        public function getLlamadoMesa()
        {
            return $this->llamado_mesa_params['llamado_mesa'];
        }

	function get_info_zona()
	{
		if (isset($this->llamado_mesa_params['llamado_mesa']) && $this->is_zona_activa()) { //si no esta seleccionado nada, no activo la zona
			return array('zona' => 'examen', 'seleccion' => $this->llamado_mesa_params);
		}
	}

	function is_zona_activa()
	{
		return kernel::request()->getParam('z', 1) == 1;
	}

        /**
	 * @return \siu\modelo\transacciones\inscriptos_examenes
	 */
	function modelo()
	{
		return guarani::inscriptos_examenes();
	}

	protected function instanciar_form_builder()
	{
		
	}

	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'info':
			case 'generar_detalle_pdf':
			case 'generar_detalle_xls':
				return 'vista_detalle';
		}
		return 'vista';
	}

	function accion__info()
	{
		$this->decodificar_parametro();
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

	function accion__generar_detalle_pdf()
	{
		$this->decodificar_parametro();
                list($path_archivo, $nombre_archivo) = $this->vista()->pagelet('detalle')->generar_pdf();
                $this->descargar_archivo($path_archivo, $nombre_archivo, true);
	}

	function accion__generar_detalle_xls()
	{
		$this->decodificar_parametro();
		$this->vista()->pagelet('detalle')->generar_xls();
	}

	/**
	 * @return array
	 */
	function decodificar_parametro()
	{
		$this->hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$this->llamado_mesa_params = $this->decodificar_hash($this->hash, '*');
		return $this->llamado_mesa_params;
	}

}
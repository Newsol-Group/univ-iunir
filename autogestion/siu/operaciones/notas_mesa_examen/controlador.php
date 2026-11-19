<?php
namespace siu\operaciones\notas_mesa_examen;

use siu\errores\error_guarani_acta_cursada_cerrada;
use siu\errores\error_guarani_usuario;
use siu\guarani;
use siu\operaciones\_comun\controlador_carga_notas;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani_procesar_renglones;
use SIU\Chulupi\util\validador;
use siu\modelo\entidades\alumno_foto;

class controlador extends controlador_carga_notas
{
	public $llamado_mesa_hash, $llamado_mesa_params, $pagina;

	function get_clase_vista()
	{
		if ($this->accion == 'index') {
			// Se muestra la lista de mesas
			return 'vista_mesas';
		} else {
			return 'vista_folio';
		}
	}

	/**
	 * @return \siu\modelo\transacciones\carga_notas_mesa_examen
	 */
	public function modelo()
	{
		return guarani::carga_notas_mesa_examen();
	}

	function get_info_zona()
	{
		if (isset($this->llamado_mesa_params['llamado_mesa'])) { 
			// Si no hay selección se desactiva la zona
			return array('zona' => 'examen', 'seleccion' => $this->llamado_mesa_params);
		}
	}

	function accion__index()
	{
		
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

	function accion__edicion()
	{
		$this->load_params();
		$renglon = $this->validate_param('renglon', 'get', validador::TIPO_INT, array('default' => ''));
		$this->vista()->pagelet('renglones')->set_highlight($renglon);
	}

	function accion__info_escala()
	{
		$escala_id = $this->validate_param(0, 'get', validador::TIPO_INT);
		$escala_notas = $this->modelo()->info__escala($escala_id);
		$this->render_raw_json($escala_notas);
	}

	function accion__guardar()
	{
		try {
			$this->load_params();
			$this->guardar_pagina(kernel::request()->getPost('renglones'));
		} catch (error_guarani_acta_cursada_cerrada $e) {
			kernel::log()->add_error($e);
			$error = new error_guarani_usuario(kernel::traductor()->trans('acta_cerrada_comunicarse_oficina_alumnos', array('%1%' => kernel::vinculador()->crear('notas_cursada_comision'))));
			$error->set_codigo_response(-5);
			throw $error;
		} catch (error_guarani_procesar_renglones $e) {
			kernel::log()->add_debug('error al procesar renglones', $e);
			$this->vista()->pagelet('renglones')->set_error_guarani_procesar_renglones($e);
		}

		// Se indica que cuando llegue el pedido ajax a esta acción la respuesta
		// debe ser sólamente el contenido del pagelet renglones, no toda la operación
		kernel::renderer()->add($this->vista()->pagelet('renglones'));

		//redefino la accion actual para controlar bien los parametros
		$this->codificador_modelo()->set_operacion_y_accion('notas_mesa_examen', 'edicion');

		$this->accion__edicion();
	}

	/**
	 * Autocompletado de alumno
	 */
	function accion__auto_alumno()
	{

		$this->load_params_mesa();
		$term = $this->getAutocompleteTerm();

		if (empty($term)) {
			$this->render_raw_json(array());
			return;
		}

		$raw_data = $this->modelo()->info__busqueda_alumno($this->llamado_mesa_params, $term);
		$data = array();
		foreach ($raw_data as $alumno) {
			$data[] = array('id' => kernel::vinculador()->crear('notas_mesa_examen', 'edicion', array(0 => $this->llamado_mesa_hash,
					1 => $alumno['pagina'],
					'renglon' => $alumno['renglon'])),
				'label' => $alumno['apellido_nombres']);
		}
		$this->render_raw_json($data);
	}

	//--------------------------------------------------------------------------
	//---- UTIL guardado y validación de renglones -----------------------------
	//--------------------------------------------------------------------------
	/**
	 * Busca en el get la mesa y la página
	 */
	protected function load_params()
	{
		$this->load_params_mesa();
		$this->pagina = $this->validate_param(1, 'get', validador::TIPO_INT, array('default' => 1));
	}

	protected function load_params_mesa()
	{
		$this->llamado_mesa_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$this->llamado_mesa_params = $this->decodificar_hash($this->llamado_mesa_hash, 'edicion');
	}

	protected function get_clave_sesion_pagina()
	{
		return "pagina[$this->llamado_mesa_hash,$this->pagina]";
	}

	function get_encabezado()
	{
		return $this->modelo()->info__mesa_cabecera($this->llamado_mesa_params);
	}

	function get_pagina()
	{
		$renglones = $this->modelo()->info__alumnos_mesa($this->llamado_mesa_params, $this->pagina);
		// Se guardan en sesión las filas para después actualizar, ver método guardar_pagina.
		kernel::sesion()->set($this->get_clave_sesion_pagina(/* $this->llamado_mesa, $this->pagina */), $renglones);
		return $renglones;
	}

	protected function vacio_a_null($valor)
	{
		return (trim($valor) == '') ? null : $valor;
	}

	protected function guardar_pagina($renglones_post)
	{

		$renglones_sesion = kernel::sesion()->get($this->get_clave_sesion_pagina());
		$renglones_modificados = array();

		foreach ($renglones_sesion as $id => $fila_s) {
			$renglon = $fila_s['renglon'];
			if (isset($renglones_post[$renglon])) {
				$hay_cambios_detalle = $this->hay_cambios_detalle($renglones_post[$renglon], $fila_s, array('fecha', 'nota', 'resultado'));

				if ($hay_cambios_detalle) {
					$renglones_modificados[$id] = $fila_s;
					$fecha = $this->validate_value($renglones_post[$renglon]['fecha'], validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => null));
					$renglones_modificados[$id]['nota'] = $this->vacio_a_null($renglones_post[$renglon]['nota']);
					$renglones_modificados[$id]['fecha'] = $fecha;
					$renglones_modificados[$id]['resultado'] = $this->vacio_a_null($renglones_post[$renglon]['resultado']);
				}
			}
		}

		if (!empty($renglones_modificados)) {
			$this->modelo()->evt__procesar_pagina($this->llamado_mesa_params, $renglones_modificados);
		}
	}

	function hay_cambios_detalle($renglon_post, $renglon_sesion, $columnas)
	{
		foreach ($columnas as $columna) {
			if (!isset($renglon_post[$columna])) {
				continue;
			}
			if ($renglon_post[$columna] != $renglon_sesion[$columna]) {
				return true;
			}
		}
		return false;
	}

}
?>
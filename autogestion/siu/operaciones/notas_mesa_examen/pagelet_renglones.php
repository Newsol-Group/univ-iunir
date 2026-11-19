<?php
namespace siu\operaciones\notas_mesa_examen;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\modelo\transacciones\carga_notas_mesa_examen;
use siu\util\generador_escalas;

class pagelet_renglones extends \siu\operaciones\_comun\pagelets\pagelet_renglones
{
	function get_nombre()
	{
		return 'renglones';
	}

	/**
	 * @return carga_notas_mesa_examen
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	protected function get_renglones()
	{
		$encabezado = $this->get_encabezado();

		// Se pide al controlador para hacer validación con los datos enviados al guardar
		$renglones = $this->controlador->get_pagina();

		foreach ($renglones as $id => $renglon) {
			$renglones[$id]['URL_FICHA'] = kernel::vinculador()->crear('notas_mesa_examen', 'ficha_alumno', array(
				'id_imagen' => $renglones[$id]['id_imagen'],
				'alumno' => $renglones[$id]['alumno'],
				'actividad' => $encabezado['elemento']
			));
		}

		if ($this->errores !== false && $this->errores->hay_renglones()) {
			$renglones_error = $this->errores->get_renglones();
			foreach ($renglones_error as $id => $renglon) {
				if ($this->alumno_en_renglones($renglon['renglon']['alumno'], $renglones)) {
					// El alumno no fue quitado de la mesa
					$renglones[$id]['error'] = $renglon['msg'];
					if ($renglon['error_concurrencia']) { 
						// No se actualizan los renglones porque el cambio se invalidó
						$renglones[$id]['error_concurrencia'] = true;
					} else {
						$renglones[$id]['nota'] = $renglon['renglon']['nota'];
						$renglones[$id]['fecha'] = \convertir_fecha_formato_frontend($renglon['renglon']['fecha']);
						$renglones[$id]['resultado'] = $renglon['renglon']['resultado'];
					}
				}
			}
		}

		return $renglones;
	}

	function alumno_en_renglones($alumno, $renglones)
	{
		foreach ($renglones as $renglon) {
			if ($renglon['alumno'] == $alumno) {
				return true;
			}
		}
		return false;
	}

	protected function get_escalas()
	{
		return $this->modelo()->info__escala_notas($this->controlador->llamado_mesa_params);
	}

	protected function get_resultados()
	{
		return $this->modelo()->info__resultados($this->controlador->llamado_mesa_params);
	}

	function hay_renglones()
	{
		$encabezado = $this->get_encabezado();
		return $encabezado['paginas'] > 0;
	}

	function get_pagina_actual()
	{
		return $this->controlador->pagina;
	}

	function get_paginas()
	{
		$encabezado = $this->get_encabezado();
		$paginas = array();
		for ($pagina = 1; $pagina <= $encabezado['paginas']; $pagina++) {
			$paginas[$pagina] = kernel::vinculador()->crear('notas_mesa_examen', 'edicion', array($this->controlador->llamado_mesa_hash, $pagina));
		}
		return $paginas;
	}

	protected function get_url_guardar()
	{
		// @todo: Generando el link así, no se chequea el derecho. Aca habría que hacer un llamado explicito -
		// En este caso no hace falta ya que es un mismo derecho para editar y guardar.
		return kernel::vinculador()->crear('notas_mesa_examen', 'guardar', array($this->controlador->llamado_mesa_hash, $this->controlador->pagina));
	}

	function get_js_files()
	{
		$op = $this->get_operacion_fisica();
		$url = kernel::vinculador()->vinculo_js_pagelet($op, $this->get_nombre());
		if ($url == false) {
			$this->hay_archivo_js = false;
			$archivos = array();
		} else {
			$this->hay_archivo_js = true;
			$archivos = array($url);
		}

		$bloque_acceso = kernel::proyecto()->get_bloque_acceso();

		$escalas_usadas = $this->modelo()->info__escalas_notas_usadas($this->controlador->llamado_mesa_params);
		foreach ($escalas_usadas as $dato_escala) {
			$escala = $dato_escala['escala_nota'];
			$nombre_js = "escala_$escala.js";
			$path = guarani::path_escalas_js() . "/$nombre_js";
			if (!file_exists($path)) {
				kernel::log()->add_debug('path escalas', "el archivo '$path' no existe. Generando escalas...");
				generador_escalas::generar();
			}
			$archivos[] = kernel::vinculador()->vinculo_recurso("js/escalas/$bloque_acceso/$nombre_js");
		}

		$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_renglones.js");
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/escalas/$bloque_acceso/cond_res.js");

		return $archivos;
	}

	function prepare()
	{
		$encabezado = $this->get_encabezado();

		$this->data = array();
		$this->data['renglones'] = $this->get_renglones();
		$this->data['cabeceras_escalas'] = $this->modelo()->info__datos_escalas_notas($this->controlador->llamado_mesa_params);
		$this->data['escalas'] = $this->modelo()->info__escala_notas($this->controlador->llamado_mesa_params);
		$this->data['resultados'] = $this->modelo()->info__resultados($this->controlador->llamado_mesa_params);
		$this->data['url_guardar'] = $this->get_url_guardar();

		$this->add_var_js('highlight_renglon', $this->renglon);
		$this->add_var_js('fecha_inicio', $encabezado['fecha_formato_visual']);
		$this->add_var_js('fecha_fin', date('d/m/Y'));
		$this->add_var_js('url_autocomplete', kernel::vinculador()->crear('notas_mesa_examen', 'auto_alumno', $this->controlador->llamado_mesa_hash));

		$this->add_mensaje_js('no_se_encontraron_alumnos', kernel::traductor()->trans('no_se_encontraron_alumnos'));
		$this->add_mensaje_js('fecha_invalida', kernel::traductor()->trans('carga_notas_mesa_examen.fecha_invalida', array('%1%' => $encabezado['fecha_formato_visual'], '%2%' => date('d/m/Y'))));
		$this->add_mensaje_js('nota_invalida', kernel::traductor()->trans('nota_invalida'));
		$this->add_mensaje_js('mostrar_actas_promocion', kernel::traductor()->trans('mostrar_actas_promocion'));
		$this->add_mensaje_js('esconder_actas_promocion', kernel::traductor()->trans('esconder_actas_promocion'));

		$this->add_mensaje_js('guardado_exitoso', kernel::traductor()->trans('notas_cursada_guardado_exitoso'));
		$this->add_mensaje_js('guardado_error', kernel::traductor()->trans('notas_cursada_guardado_error'));
	}

}
?>

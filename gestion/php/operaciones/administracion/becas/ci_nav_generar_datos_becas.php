<?php
class ci_nav_generar_datos_becas extends guarani_pers_ci
{
	protected $becas;
	protected $anio_academico;
	protected $periodo;
	protected $responsable_academica;
	protected $numero_documento;
	protected $path_archivo;
	protected $s__errores;

	function conf__resultados()
	{
		$this->evento('descargar_archivos')->vinculo()->set_parametros(array('becas' => $this->becas, 'anio_academico' => $this->anio_academico, 'responsable_academica' => $this->responsable_academica));
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$form->set_datos_defecto(array('becas' => beca::becas_manuel_belgrano, 'periodo' => beca::periodo_anual));
		$upload_max_filesize = ini_get('upload_max_filesize');
		$form->ef('archivo_alumnos')->set_descripcion("El formato de archivo permitido es txt. Tamaño máximo de archivo: {$upload_max_filesize}B.");
	}

	function evt__formulario__modificacion($datos)
	{
		$this->becas = $datos['becas'];
		$this->anio_academico = $datos['anio_academico'];
		$this->periodo = $datos['periodo'];
		$this->responsable_academica = $datos['responsable_academica'];
		$this->numero_documento = $datos['numero_documento'];
		toba_manejador_archivos::crear_arbol_directorios(toba::proyecto()->get_path_temp().'/archivos');
		$this->path_archivo = toba::proyecto()->get_path_temp().'/archivos/'.uniqid();
		rename($datos['archivo_alumnos']['tmp_name'], $this->path_archivo);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__errores);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__generar()
	{
		if (filesize($this->path_archivo) == 0) {
			throw new toba_error(guarani_mensajes::get('800ADM_archivo_vacio'));
		}
		$id_proceso = guarani::act('act_becas')->get_id_proceso();
		$this->s__errores = $this->cn()->procesar_archivo_alumnos($this->path_archivo, $id_proceso);
		if (!empty($this->s__errores)) {
			$this->set_pantalla('pant_errores');
		} else {
			$this->cn()->generar($this->becas, $this->anio_academico, $this->periodo, $id_proceso, $this->responsable_academica, $this->numero_documento);
			$this->set_pantalla('resultados');
		}
	}

	function evt__volver()
	{
		$this->set_pantalla('pant_inicial');
	}
}
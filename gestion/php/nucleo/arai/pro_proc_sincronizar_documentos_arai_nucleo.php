<?php
/**
 *
 *
 * @package 	Araí
 * @subpackage 	Documentos
 */
class pro_proc_sincronizar_documentos_arai_nucleo extends guarani_procesos_bk
{

	const nombre = 'pro_proc_sincronizar_documentos_arai';

	const reporte_sincronizados = 1;
	const reporte_fallidos = 2;
	const reporte_resultados = 4;

	protected $actas;
	protected $tramites_equivalencias;
	protected $cant_sincronizados;
	protected $cant_fallidos;

	function set_datos($datos = [])
	{
		$this->actas = $datos['actas'];
		$this->tramites_equivalencias = $datos['tramites_equivalencias'];
	}

	function procesar()
	{
		$cant_documentos = (guarani_arreglos::count($this->actas) + guarani_arreglos::count($this->tramites_equivalencias));
		$paso = 100/$cant_documentos;

		$this->cant_sincronizados = 0;
		$this->cant_fallidos = 0;
		
		foreach ($this->actas as $acta) {
			
			try {

				$params = json_decode($acta['parametros_documento_digital'], true);
				acta::enviar_acta_arai_documentos($acta['id_acta'], $params['parametros'], $params['ras'], null, true, false, false);
				$this->log->set($acta, [self::reporte_sincronizados]);
				$this->cant_sincronizados++;

			} catch (\Exception $e) {
				$acta['observaciones'] = $e->getMessage();
				$this->log->set($acta, [self::reporte_fallidos]);
				$this->cant_fallidos++;
			}

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}
		
		foreach ($this->tramites_equivalencias as $tramite_equivalencia) {
			
			try {
				
				$params = json_decode($tramite_equivalencia['parametros_documento_digital'], true);
				equivalencia::enviar_tramite_equivalencia_arai_documentos($tramite_equivalencia['equivalencia_tramite'], $params['parametros'], $params['ras'], null, true, false, false);
				$this->log->set($tramite_equivalencia, [self::reporte_sincronizados]);
				$this->cant_sincronizados++;

			} catch (\Exception $e) {
				$tramite_equivalencia['observaciones'] = $e->getMessage();
				$this->log->set($tramite_equivalencia, [self::reporte_fallidos]);
				$this->cant_fallidos++;
			}

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['sincronizados'] = $this->cant_sincronizados;
		$mensaje_resultado['fallidos'] = $this->cant_fallidos;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

}
<?php
class pant_documento extends toba_ei_pantalla
{
	function generar_layout()
	{
		// Estado del documento
		$estado = $this->controlador()->get_descr_estado();
		echo "<h3><u>Estado de documento</u>: $estado</h3>";

		// Previsualización
		$path = $this->controlador()->get_url_preview_doc_digital();

		if (!empty($path)) {
			echo "<h3><u>Previsualización</u>:</h3>";
			echo "<img src='$path' alt='Documento' height='150px'>";
			$titulo_form = '<hr/>Reemplazar documento';
		} else {
			$titulo_form = 'Cargar documento';
		}

		if (!$this->controlador()->aceptado_desde_draft()) {
			// Formulario de carga
			echo "<h3><u>$titulo_form</u>:</h3>";
			$this->dep('formulario')->generar_html();
		} else {
			// Si fue aceptado desde Draft, no se reemplaza
			$this->eliminar_dep('formulario');
		}

		$extensiones = toba::consulta_php('co_configuracion')->get_rdi_extensiones_permitidas();
		$extensiones_permitidas = implode(", ", $extensiones);
		$tamanio_maximo = toba::consulta_php('co_configuracion')->get_tamanio_maximo_archivo();

		echo "<h3>Tipos de archivos permitidos: $extensiones_permitidas</h3>";
		echo "<h3>Máximo tamaño de archivo permitido: $tamanio_maximo MB</h3>";
	}

}
?>
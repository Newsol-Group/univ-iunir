<?php
class pant_doc_draft extends toba_ei_pantalla
{
	function generar_layout()
	{
		// Previsualización
		$path = $this->controlador()->get_url_preview_doc_digital_draft();
		if (!empty($path)) {
			echo "<h3><u>Previsualización</u>:</h3>";
			echo "<img src='$path' alt='Documento' height='150px'>";
			
		   $this->dep('formulario_doc_draft')->generar_html();
		}
	}

}
?>
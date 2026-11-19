<?php
class form_datos_facturacion_nsg extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		echo '<table border=0>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('persona');
    		echo '</td>';
    	echo '</tr>';
    	echo '<tr>';
    		echo '<td colspan=2>';
    		$this->generar_html_ef('propuesta');
    		echo '</td>';
    	echo '</tr>';
    	echo '<tr>';
	echo '</tr>';
	echo '</table>';

	echo "<table border=0 width='100%'>";
		echo '<tr>';
		  	echo '<td>';
			$this->generar_html_ef('forma_pago');
			echo '</td>';
		echo '</tr>';
	echo '</table>';
	
	echo "<fieldset title='Datos del Alumno' style='padding-left: 0px;padding-right: 0px;'><legend>Datos del Alumno</legend>";
		echo "<table border=0 width='100%'>";
		echo '<tr>';
		  	echo '<td>';
			$this->generar_html_ef('porcentaje_facturacion_alumno', '120px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('medio_pago_alumno_mat', '120px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('nro_tarjeta_alumno_mat', '120px');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('cbu_alumno_mat', '120px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('medio_pago_alumno_cuota', '120px');
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td>';
			$this->generar_html_ef('nro_tarjeta_alumno_cuota', '120px');
			echo '</td>';
			echo '<td>';
			$this->generar_html_ef('cbu_alumno_cuota', '120px');
			echo '</td>';
		echo '</tr>';

	echo '</table>';
	echo '</fieldset>';

	echo "<table border=0 width='100%'>";
		echo '<tr>';
		  	echo '<td>';
			$this->generar_html_ef('pago_a_cargo_empresa', '150px');
			echo '</td>';
		echo '</tr>';
	echo '</table>';


	echo "<fieldset title='Datos de la Empresa' id='datos_empresa' style='padding-left: 0px;padding-right: 0px;'><legend>Datos de la Empresa</legend>";
		echo "<table border=0 width='100%'>";
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('porcentaje_facturacion_empresa', '120px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('medio_pago_empresa_mat', '120px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('nro_tarjeta_empresa_mat', '120px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('cbu_empresa_mat', '120px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('medio_pago_empresa_cuota', '120px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('nro_tarjeta_empresa_cuota','120px');
				echo '</td>';
				echo '<td>';
				$this->generar_html_ef('cbu_empresa_cuota', '120px');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('empresa');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('email_empresa');
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				$this->generar_html_ef('observaciones', '310px');
				echo '</td>';

			echo '</tr>';
		echo '</table>';
	echo '</fieldset>';
	}

}
?>
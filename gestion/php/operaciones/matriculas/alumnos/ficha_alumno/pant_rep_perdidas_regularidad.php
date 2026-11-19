<?php
class pant_rep_perdidas_regularidad extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "	<style>
					.ei-cuadro-col-tit-grupo {
						border-left: 1px dotted black;
						border-right: 1px dotted black;
					}
				</style>
			";
		parent::generar_layout();
	}

}
?>
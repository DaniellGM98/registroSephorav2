<?php
$db = new mysqli('localhost','bwgleibp_dds','IHO060725FY6','bwgleibp_tecnodeveloper');


//Función almacenado
$result = $db->query("SELECT clearWiretech();");

// DELIMITER //
// Create Function asist()
//    RETURNS INT
//    DETERMINISTIC
//    BEGIN
//     UPDATE `dds_app_wire` SET `ingreso`=0, `num_entradas`=0, `acceso_entrada`=NULL, `fecha_ingreso`=NULL, `num_salidas`=0, `acceso_salida`=NULL, `fecha_salida`=NULL, `tiempo_evento`='00:00:00' WHERE 1;
//     RETURN 1;
//    END //
// DELIMITER ;








//Procedimiento
// $result = $db->query("CALL facilito_procedure();");

// DELIMITER //

// DROP PROCEDURE IF EXISTS facilito_procedure//
// CREATE PROCEDURE facilito_procedure()
// BEGIN

//   DECLARE var_id INTEGER;
//   DECLARE var_final INTEGER DEFAULT 0;

//   DECLARE cursor1 CURSOR FOR SELECT usuario.id FROM usuario where usuario.status=1;

//   DECLARE CONTINUE HANDLER FOR NOT FOUND SET var_final = 1;
// 	IF (SELECT COUNT(*) FROM asistencia WHERE fecha=CURDATE())>0 THEN
//   OPEN cursor1;

//   bucle: LOOP

//     FETCH cursor1 INTO var_id;

//     IF var_final = 1 THEN
//       LEAVE bucle;
//     END IF;
    
//     IF (SELECT COUNT(*) FROM asistencia WHERE fk_usuario=var_id AND fecha=CURDATE())>0 THEN
//     	UPDATE `usuario` SET `fecha_modificacion`=NOW() - INTERVAL 2 HOUR, `dias_laborados`=(`dias_laborados` + 1) WHERE id=var_id;
//     ELSE
//        	UPDATE `usuario` SET `fecha_modificacion`=NOW() - INTERVAL 2 HOUR, `faltas`=(`faltas` + 1) WHERE id=var_id;
//        	INSERT INTO `falta`(`fk_usuario`, `fecha`, `fecha_dia`) VALUES (var_id,NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR);
//     END IF;

//   END LOOP bucle;
//   CLOSE cursor1;
//   INSERT INTO `seg_log`(`seg_usuario_id`, `descripcion`, `tabla`, `registro`, `fecha_modificacion`) VALUES (1,'Cierre asistencias del día','usuario',CURDATE(),NOW() - INTERVAL 2 HOUR);
// 	END IF;
// END
// DELIMITER ;

while ($row = $result->fetch_assoc())
{
   var_dump($row);
}
?>
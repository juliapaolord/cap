<?php
    
if(count($_GET) > 0 && isset($_GET["request"])){
    $serverName = "juliapaola\sqlexpress";
    //$serverName = "vmwinsiete\sqlexpress,1533";
    $connectionInfo = array( "Database"=>"cpa", "UID"=>"sa", "PWD"=>"a01630895","CharacterSet" => "UTF-8");
    $conn = sqlsrv_connect( $serverName, $connectionInfo);

    if( $conn === false ) {
        die( print_r( sqlsrv_errors(), true));
    } else{
        switch($_GET["request"]){
            //Get all areas 
            /*case 0:
                $sql = "SELECT area_id, area FROM CPA_Area WHERE activo = 'SI'";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;*/

            //Login verification and get user info
            /*
            login
            */
            case 1:
                $email = $_GET["email"];
                $password = $_GET["password"];
                $sql = "SELECT * FROM CPA_Usuario WHERE email = '$email' AND clave = '$password'";
                $stmt = sqlsrv_query($conn, $sql);
                if($stmt === false){
                    die( print_r( sqlsrv_errors(), true));
                }
                $user = sqlsrv_fetch_object( $stmt);
                if($user != null){
                    echo json_encode($user,JSON_UNESCAPED_UNICODE);
                }else{
                    echo "FAILED";
                }
                sqlsrv_free_stmt( $stmt);
                break;

            //Get manager department
            /*
            sidenav
            */
            case 2:
                $user_id = $_GET["usuario_id"];
                $sql = "SELECT departamento_id, departamento FROM CPA_Departamento WHERE gerente_id = '$user_id'";
                $stmt = sqlsrv_query($conn,$sql);
                if($stmt === false){
                    die( print_r( sqlsrv_errors(), true));
                    echo 'Error';
                }else{
                    $dpt = sqlsrv_fetch_object($stmt);
                    echo json_encode($dpt,JSON_UNESCAPED_UNICODE);
                }
                
                sqlsrv_free_stmt($stmt);
                break;

            //Get department indicators
            /*
            catalogue
            */
            case 3:
                $rows = array();
                $department_id = $_GET["department_id"];
                $sql = "SELECT indicador_id, indicador, area, rol, CPA_Indicador.rol_id, unidad, fuente, frecuencia 
                        FROM CPA_Indicador, CPA_Area, CPA_Rol, CPA_Unidad, CPA_Fuente, CPA_Frecuencia 
                        WHERE CPA_Area.area_id = CPA_Indicador.area_id
                            AND CPA_Rol.rol_id = CPA_Indicador.rol_id
                            AND CPA_Unidad.unidad_id = CPA_Indicador.unidad_id
                            AND CPA_Fuente.fuente_id = CPA_Indicador.fuente_id
                            AND CPA_Frecuencia.frecuencia_id = CPA_Indicador.frecuencia_id
                            AND CPA_Rol.departamento_id = $department_id
                            AND CPA_Indicador.activo = 'SI'
                        ORDER BY indicador";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Get all frequencies
            /*
            catalogue
            */
            case 4:
                $sql = "SELECT frecuencia FROM CPA_Frecuencia";
                $stmt = sqlsrv_query($conn, $sql);
                while( $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) ){
                    $rows[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Insert new indicator
            /*
            catalogue
            */
            case 5:
                $indicator = $_GET["indicator"];
                $area_id = $_GET["area_id"];
                $role_id = $_GET["role_id"];
                $unit = $_GET["unit"];
                $frequency = $_GET["frequency"];
                $source = $_GET["source"];
                $department_id = $_GET["department_id"];

                $procedure_params = array( &$indicator,&$area_id,&$role_id,&$unit,&$source,&$frequency,&$department_id);

                $sql = "EXEC CPA_NuevoIndicador
                        @indicador = ?, @area_id = ?, @rol_id = ?, @unidad = ?, 
                        @fuente = ?, @frecuencia = ?, @dpt_id = ?";
                $stmt = sqlsrv_prepare($conn, $sql, $procedure_params);

                if( !$stmt ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                if( sqlsrv_execute( $stmt ) === false ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt($stmt);
                echo 'Enviado';
                break;

            //Get collaborators
            /*
            collaborators
            */
            case 6:
                $department_id = $_GET["department_id"];
                $sql = "SELECT e.nombre, e.empleado_id, e.rol, e.rol_id, mt.mes, mt.final FROM
                            (SELECT CONCAT(nombre, ' ', apellido) as nombre, empleado_id, CPA_Empleado.rol_id, rol 
                            FROM CPA_Empleado, CPA_Rol
                            WHERE CPA_Rol.rol_id = CPA_Empleado.rol_id 
                                AND CPA_Empleado.departamento_id = $department_id
                                AND CPA_Empleado.activo = 'SI') as e
                            LEFT JOIN
                            (SELECT m.mes, sq.empleado_id, f.final FROM CPA_Mes as m, ( 
                                SELECT MAX(CONVERT(int,mes_id)) AS mes, empleado_id
                                FROM CPA_CalificacionFinal
                                WHERE fechaFin IS NOT NULL
                                GROUP BY empleado_id) as sq, CPA_CalificacionFinal as f
                            WHERE m.mes_id = CONVERT(varchar(6), sq.mes)
                                AND f.mes_id = m.mes_id
                                AND sq.empleado_id = f.empleado_id) as mt
                            ON e.empleado_id = mt.empleado_id";
                            
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                } else {
                    echo "NO INFO";
                }
                break;

            //Get collaborator grades
            /*
            profile
            */
            case 7: 
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "SELECT mes_id, area_id, indicador, unidad, unidad_id, fuente, frecuencia, meta, minimo, real_obtenido, peso, porcentaje, calificacion
                        FROM CPA_CalificacionIndicador AS ci, (
                            SELECT indicador_id, indicador, unidad, i.unidad_id, fuente, frecuencia, i.area_id
                            FROM CPA_Indicador AS i, CPA_Unidad AS u, CPA_Fuente AS f, CPA_Frecuencia AS fr
                            WHERE i.unidad_id = u.unidad_id AND i.fuente_id = f.fuente_id AND fr.frecuencia_id = i.frecuencia_id) AS sq
                        WHERE ci.indicador_id = sq.indicador_id 
                        AND empleado_id = $collaborator_id 
                        AND mes_id = '$month_id'
                        AND EXISTS (
							SELECT * FROM CPA_CalificacionFinal f WHERE fechaFin IS NOT NULL 
							AND ci.empleado_id = f.empleado_id AND ci.mes_id = f.mes_id)";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Get modifiers' types
            /*1
            modifiers
            profile*/
            case 8:
                $area_id = $_GET["area_id"];
                $sql = "SELECT * 
                        FROM CPA_TipoModificador
                        WHERE area_id = $area_id";
                            
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Get modifiers
            case 9:
                $area_id = $_GET["area_id"];
                $department_id = $_GET["department_id"];
                $sql = "SELECT sq.evento_id, sq.evento, u.unidad, sq.tipo_id, sq.area_id, sq.departamento_id
                        FROM CPA_Unidad AS u RIGHT JOIN (
                            SELECT evento_id, evento, unidad_id, e.tipo_id, t.area_id, e.departamento_id
                            FROM CPA_Evento AS e, CPA_TipoModificador AS t
                            WHERE e.tipo_id = t.tipo_id AND area_id = $area_id 
                                AND (e.departamento_id = $department_id OR e.departamento_id IS NULL)
                                AND e.activo = 'SI') AS sq
                        ON u.unidad_id = sq.unidad_id";
                            
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Get available months por certain employee
            /*
            profile
            */
            case 10:
                $collaborator_id = $_GET["collaborator_id"];
                $sql = "SELECT * 
                        FROM CPA_Mes 
                        WHERE exists (
                            SELECT DISTINCT mes_id 
                            FROM CPA_CalificacionIndicador ci
                            WHERE mes_id = CPA_Mes.mes_id AND empleado_id = $collaborator_id
                            AND EXISTS (
                                SELECT * FROM CPA_CalificacionFinal f WHERE fechaFin IS NOT NULL 
                                AND ci.empleado_id = f.empleado_id AND ci.mes_id = f.mes_id))";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                } else {
                    echo "NO INFO";
                }
                break;

            //Get modifiers per month for an specific employee
            /*
            profile
            */
            case 11:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "SELECT area_id, evento, sq.evento_id, unidad, fuente, cantidad, sq.tipo_id, t.puntos
                        FROM CPA_TipoModificador AS t, (
                            SELECT evento, m.evento_id, unidad, fuente, tipo_id, cantidad
                            FROM CPA_Modificador AS m, CPA_Evento AS e, CPA_Fuente AS f, CPA_Unidad AS u
                            WHERE empleado_id = $collaborator_id AND mes_id = '$month_id'
                                AND m.evento_id = e.evento_id AND m.fuente_id = f.fuente_id 
                                AND e.unidad_id = u.unidad_id
                                AND EXISTS (
                                    SELECT * FROM CPA_CalificacionFinal f
                                    WHERE m.empleado_id = f.empleado_id AND m.mes_id = f.mes_id)) AS sq
                        WHERE t.tipo_id = sq.tipo_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Get final grade for certain month
            /*
            profile
            */
            case 12:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "SELECT parcial, final
                        FROM CPA_CalificacionFinal
                        WHERE empleado_id = $collaborator_id AND mes_id = '$month_id'
                        AND fechaFin IS NOT NULL ";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Get indicators for resume
            /*
            profile
            */
            case 13:
                $collaborator_id = $_GET["collaborator_id"];
                $sql = "SELECT DISTINCT i.indicador_id, indicador, i.area_id, area 
                        FROM CPA_CalificacionIndicador c, CPA_Indicador i, CPA_Area a
                        WHERE c.indicador_id = i.indicador_id 
                            AND empleado_id = $collaborator_id
                            AND i.area_id = a.area_id
                        ORDER BY i.area_id, indicador_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Get all grades for resume
            /*
            profile
            */
            case 14:
                $collaborator_id = $_GET["collaborator_id"];
                $sql = "SELECT ci.indicador_id, ci.mes_id, porcentaje, peso, calificacion, i.area_id 
                        FROM CPA_CalificacionIndicador AS ci, CPA_Indicador AS i
                        WHERE ci.empleado_id = $collaborator_id 
                            AND ci.indicador_id = i.indicador_id
                            AND EXISTS (
							SELECT * FROM CPA_CalificacionFinal f WHERE fechaFin IS NOT NULL 
							AND ci.empleado_id = f.empleado_id AND ci.mes_id = f.mes_id)
                        ORDER BY i.area_id, ci.indicador_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Insert event
            /*
            modifiers
            */
            case 15:
                $type_id = $_GET["type_id"];
                $event = $_GET["event"];
                $unit_id = $_GET["unit_id"];
                $department = $_GET["department_id"];
                $procedure_params = array(&$event,&$type_id,&$unit_id,&$department_id);

                $sql = "EXEC CPA_AgregarEvento
                        @evento = ?, @tipo_id = ?, @unidad_id = ?, @dpt_id = ?";
                $stmt = sqlsrv_prepare($conn, $sql, $procedure_params);

                if( !$stmt ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                if( sqlsrv_execute( $stmt ) === false ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt($stmt);
                echo 'Enviado';
                break;
            
            //Get all final grades per collaborator
            /*
            profile
            */
            case 16:
                $collaborator_id = $_GET["collaborator_id"];
                $sql = "SELECT mes_id, parcial, final 
                        FROM CPA_CalificacionFinal
                        WHERE empleado_id = $collaborator_id
                        ORDER BY mes_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                break;

            //Get events per month per collaborator
            /*
            profile
            */
            case 17:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "SELECT sum(m.cantidad) AS eventos, tipo, area_id
                        FROM CPA_Modificador m, CPA_Evento e, CPA_TipoModificador t, CPA_CalificacionFinal f
                        WHERE m.evento_id = e.evento_id
                        AND m.mes_id = '$month_id'
                        AND m.empleado_id = $collaborator_id
                        AND fechaFin IS NOT NULL
						AND f.mes_id = m.mes_id
						AND f.empleado_id = m.empleado_id
                        AND t.tipo_id = e.tipo_id
                        GROUP BY tipo, area_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Get previous report
            /*
            reportcard
            */
            case 18:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $procedure_params = array( &$collaborator_id,&$month_id);
                $sql = "EXEC CPA_IndicadoresBoleta
                        @empleado = ?, @mes = ?";
                $stmt = sqlsrv_prepare($conn, $sql, $procedure_params);
                if( !$stmt ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                if( sqlsrv_execute( $stmt ) === false ) {
                    die( print_r( sqlsrv_errors(), true));
                }

                $sql = "SELECT ci.indicador_id, indicador, meta, minimo, peso, area_id, real_obtenido, calificacion, porcentaje, unidad_id 
                        FROM CPA_Indicador i, CPA_CalificacionIndicador ci, (
						SELECT fechaInicio FROM CPA_CalificacionFinal WHERE mes_id = '$month_id' AND empleado_id = $collaborator_id) sq
                        WHERE ci.indicador_id = i.indicador_id
                        AND mes_id = '$month_id'
                        AND empleado_id = $collaborator_id
                        AND fechaInicio IS NOT NULL";
                $stmt = sqlsrv_query( $conn, $sql);
                
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt($stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Send indicator grades
            /*
            reportcard
            */
            case 19:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $indicator_id = $_GET["indicador_id"];
                $meta = $_GET["meta"];
                $minimo = $_GET["minimo"];
                $real_obtenido = $_GET["real_obtenido"];
                $peso = $_GET["peso"];
                $porcentaje = $_GET["porcentaje"];
                $calificacion = $_GET["calificacion"];

                $procedure_params = array( &$collaborator_id,&$month_id,&$indicator_id,&$meta,&$minimo,
                    &$real_obtenido,&$porcentaje,&$peso,&$calificacion);

                $sql = "EXEC CPA_InsertarIndicadores
                        @empleado = ?, @mes = ?, @indicador_id = ?, @meta = ?, 
                        @minimo = ?, @real_obtenido = ?, @porcentaje = ?, @peso = ?, @calificacion = ?";
                $stmt = sqlsrv_prepare($conn, $sql, $procedure_params);

                if( !$stmt ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                if( sqlsrv_execute( $stmt ) === false ) {
                    die( print_r( sqlsrv_errors(), true));
                }
                sqlsrv_free_stmt($stmt);
                echo 'Enviado';
                break;

            //Get remaining months per collaborator
            /*
            profile
            */
            case 20:
                $collaborator_id = $_GET["collaborator_id"];
                $sql = "SELECT m.mes_id, mes 
                        FROM CPA_Mes m
                        WHERE activo = 'SI' AND m.mes_id NOT IN (
                            SELECT mes_id FROM CPA_CalificacionFinal 
                            WHERE empleado_id = $collaborator_id AND fechaFin IS NOT NULL)";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Get remaining indicators
            /*
            reportcard
            */
            case 21:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $role_id = $_GET["role_id"];
                $sql = "SELECT indicador_id, indicador, area_id, unidad_id 
                        FROM CPA_Indicador i 
                        WHERE indicador_id not IN (
                            SELECT indicador_id FROM CPA_CalificacionIndicador ci
	                        WHERE empleado_id = $collaborator_id 
                            AND mes_id = '$month_id' AND activo = 'SI')";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Insert Collaborator
            case 22:
                $name = $_GET["name"];
                $lastName = $_GET["lastName"];
                $role = $_GET["role"];
                $department_id = $_GET["department_id"];
                $sql = "EXEC CPA_InsertarEmpleado @nombre = ?, @apellido = ?, @rol = ?, @dpt = ?";
                $params = array("$name","$lastName","$role",$department_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Get roles for collaborator insertion
            case 23:
                $department_id = $_GET["department_id"];
                $sql = "SELECT rol_id, rol FROM CPA_Rol WHERE departamento_id = $department_id AND activo = 'SI'";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Get final per report
            case 24:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "SELECT parcial, final 
                        FROM CPA_CalificacionFinal 
                        WHERE empleado_id = $collaborator_id AND mes_id = '$month_id'";
                $stmt = sqlsrv_query( $conn, $sql);
                $row = sqlsrv_fetch_object( $stmt);
                sqlsrv_free_stmt( $stmt);
                if($row != null){
                    echo json_encode($row,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Remove indicator
            case 25:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $indicator_id = $_GET["indicator_id"];
                $sql = "DELETE FROM CPA_CalificacionIndicador
                        WHERE empleado_id = ? AND mes_id = ? and indicador_id = ?";
                $params = array($collaborator_id,"$month_id",$indicator_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Clear report card
            case 26:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "DELETE FROM CPA_CalificacionIndicador
                        WHERE empleado_id = ? AND mes_id = ?";
                $params = array($collaborator_id,"$month_id"); 
                $stmt = sqlsrv_query( $conn, $sql, $params);

                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }
                
                $sql = "DELETE FROM CPA_Modificador
                        WHERE empleado_id = ? AND mes_id = ?";
                $params = array($collaborator_id,"$month_id"); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Available events for reportcard
            case 27:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $department_id = $_GET["department_id"];
                $type_id = $_GET["type_id"];
                $sql = "SELECT evento_id, evento FROM CPA_Evento WHERE evento_id NOT IN (
                            SELECT evento_id FROM CPA_Modificador 
                            WHERE empleado_id = $collaborator_id AND mes_id = '$month_id') 
                        AND (departamento_id = $department_id OR departamento_id is null) and tipo_id = $type_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;
            
            //Get frequencies from modificators
            case 28:
                $department_id = $_GET["department_id"];
                $sql = "SELECT fuente FROM CPA_Fuente f, CPA_Modificador e 
                        WHERE f.departamento_id = $department_id AND f.fuente_id = e.fuente_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                break;

            //Insert modificator
            case 29:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $event_id = $_GET["event_id"];
                $department_id = $_GET["department_id"];
                $source = $_GET["source"];
                $sql = "EXEC CPA_InsertarModificador @evento_id = ?, @mes_id = ?, @empleado_id = ?, @fuente = ?,
                        @dpto_id = ?";
                $params = array($event_id,"$month_id",$collaborator_id, "$source",$department_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Update modifier quantity
            case 30:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $event_id = $_GET["event_id"];
                $quantity = $_GET["quantity"];
                $sql = "UPDATE CPA_Modificador SET cantidad = ?
                        WHERE empleado_id = ? AND mes_id = ? AND evento_id = ?";
                $params = array($quantity,$collaborator_id,"$month_id",$event_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Remove modifier
            case 31:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $event_id = $_GET["event_id"];
                $sql = "DELETE FROM CPA_Modificador
                        WHERE empleado_id = ? AND mes_id = ? and evento_id = ?";
                $params = array($collaborator_id,"$month_id",$event_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Send reportcard
            case 32:
                $collaborator_id = $_GET["collaborator_id"];
                $month_id = $_GET["month_id"];
                $sql = "UPDATE CPA_CalificacionFinal SET fechaFin = CONVERT(date,GETDATE())
                        WHERE empleado_id = ? AND mes_id = ?";
                $params = array($collaborator_id,"$month_id"); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Get available months
            case 33:
                $sql = "SELECT mes_id, mes 
                        FROM CPA_Mes 
                        WHERE activo = 'SI'";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                } else {
                    echo "NO INFO";
                }
                break;

            //Get department resume
            case 34:
                $department_id = $_GET["department_id"];
                $sql = "SELECT CONCAT(nombre, ' ', apellido) AS empleado, e.empleado_id, puntos_extras, penalizaciones, parcial, final, mes_id 
                        FROM CPA_CalificacionFinal f, CPA_Empleado e 
                        WHERE e.departamento_id = $department_id AND e.empleado_id = f.empleado_id AND fechaFin IS NOT NULL
                        ORDER BY final DESC";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                sqlsrv_free_stmt( $stmt);
                $sql = "SELECT concat(e.nombre, ' ', e.apellido) AS empleado, parcial, puntos_extras, penalizaciones, final, '2017' AS mes_id FROM CPA_Empleado e, (
                            SELECT f.empleado_id, avg(f.parcial) AS parcial, sum(f.puntos_extras) AS puntos_extras, sum(f.penalizaciones) AS penalizaciones, avg(f.final) as final
                            FROM CPA_CalificacionFinal f
                            WHERE fechaFin IS NOT NULL
                            GROUP BY f.empleado_id) sq
                        WHERE e.empleado_id = sq.empleado_id
                        AND e.activo = 'SI' AND e.departamento_id = $department_id
                        ORDER BY final DESC";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                sqlsrv_free_stmt($stmt);
                break;
            
            //Get department info for manager
            case 35:
                $department = $_GET["department"];
                $sql = "SELECT departamento_id, departamento 
                        FROM CPA_Departamento 
                        WHERE departamento COLLATE Latin1_General_CI_AI Like '%$department%' COLLATE Latin1_General_CI_AI";
                $stmt = sqlsrv_query($conn,$sql);
                if($stmt === false){
                    die( print_r( sqlsrv_errors(), true));
                    echo 'Error';
                }else{
                    $dpt = sqlsrv_fetch_object($stmt);
                    echo json_encode($dpt,JSON_UNESCAPED_UNICODE);
                }
                
                sqlsrv_free_stmt($stmt);
                break;

            //Remove collaborator
            case 36:
                $collaborator_id = $_GET["collaborator_id"];
                $active = "NO";
                $sql = "UPDATE CPA_Empleado SET activo = ?
                        WHERE empleado_id = ?";
                $params = array($active,$collaborator_id,); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Remove indicator from catalogue
            case 37:
                $indicator_id = $_GET["indicator_id"];
                $sql = "UPDATE CPA_Indicador SET activo = ? WHERE indicador_id = ?";
                $params = array("NO", $indicator_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Remove modifier from catalogue
            case 38:
                $event_id = $_GET["event_id"];
                $sql = "UPDATE CPA_Evento SET activo = ? WHERE evento_id = ?";
                $params = array("NO", $event_id); 
                $stmt = sqlsrv_query( $conn, $sql, $params);
                if( !$stmt ) {
                    echo 'No';
                    die( print_r( sqlsrv_errors(), true));
                }else{
                    echo 'Enviado';
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Get remaining collaborators from department for month
            case 39:
                $department_id = $_GET["department_id"];
                $month_id = $_GET["month_id"];
                $sql = "SELECT CONCAT(nombre, ' ', apellido) AS nombre, empleado_id, rol, e.rol_id FROM CPA_Empleado e, CPA_Rol r
                WHERE empleado_id NOT IN(
	                SELECT empleado_id FROM CPA_CalificacionFinal WHERE mes_id = '$month_id' AND fechaFin IS NOT NULL)
                AND e.departamento_id = $department_id AND e.activo = 'SI' AND e.rol_id = r.rol_id
                ORDER BY nombre";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                sqlsrv_free_stmt($stmt);
                break;

            //Get average per month
            case 40:
                $department_id = $_GET["department_id"];
                $sql = "SELECT m.mes, promedio FROM CPA_Mes m, (
                            SELECT avg(final) AS promedio, mes_id FROM CPA_CalificacionFinal f, CPA_Empleado e
                            WHERE departamento_id = $department_id AND f.empleado_id = e.empleado_id AND fechaFIn IS NOT NULL
                            GROUP BY mes_id) sq 
                        WHERE activo = 'SI'
                        AND m.mes_id = sq.mes_id";
                $stmt = sqlsrv_query( $conn, $sql);
                while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
                    $rows[] = $row;
                }
                if(!empty($rows)){
                    echo json_encode($rows,JSON_UNESCAPED_UNICODE);
                }
                sqlsrv_free_stmt($stmt);
                break;
            
        }
        sqlsrv_close( $conn );
    }
}
?>
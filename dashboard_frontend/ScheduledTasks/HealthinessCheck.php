<?php

/* Dashboard Builder.
   Copyright (C) 2017 DISIT Lab https://www.disit.org - University of Florence
   This program is free software; you can redistribute it and/or
   modify it under the terms of the GNU General Public License
   as published by the Free Software Foundation; either version 2
   of the License, or (at your option) any later version.
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA. */

include '../config.php';

error_reporting(E_ERROR);

$high_level_type = "";
$nature = "";
$sub_nature = "";
$low_level_type = "";
$unique_name_id = "";
$instance_uri = "";
$unit = "";
$metric = "";
$saved_direct = "";
$kb_based = "";
$parameters = "";

$startTime = new DateTime(null, new DateTimeZone('Europe/Rome'));
$start_scritp_time = $startTime->format('c');
$start_scritp_time_string = explode("+", $start_scritp_time);
$start_time_ok = str_replace("T", " ", $start_scritp_time_string[0]);
echo("Starting HealthinessCheck SCRIPT at: ".$start_time_ok."\n");

$link = mysqli_connect($host, $username, $password);
//error_reporting(E_ALL);
mysqli_select_db($link, $dbname);

//$query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'From Dashboad to IOT Device' OR DashboardWizard.high_level_type = 'From IOT Device to Dashboard' OR DashboardWizard.high_level_type = 'Sensor' AND healthiness != 'false' AND healthiness != 'true'";
//$query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'From Dashboad to IOT Device' OR DashboardWizard.high_level_type = 'From IOT Device to Dashboard' AND healthiness != 'false' AND healthiness != 'true'";
// $query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'Sensor' AND healthiness != 'false' AND healthiness != 'true'";
if (defined('STDIN')) {
    if ($argv[1]) {
        $id_arg = $argv[1];
        $query = "SELECT * FROM Dashboard.DashboardWizard WHERE (DashboardWizard.high_level_type = 'Sensor' OR DashboardWizard.high_level_type = 'Sensor-Actuator' OR (DashboardWizard.high_level_type = 'Special Widget' AND sub_nature = 'First Aid Data')) AND id > ".$id_arg ." GROUP BY unique_name_id ORDER BY id DESC;";
    //    $query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'From IOT Device to Dashboard' AND id > ".$id_arg ." GROUP BY unique_name_id ORDER BY id ASC;";
    } else {
        $query = "SELECT * FROM Dashboard.DashboardWizard WHERE (DashboardWizard.high_level_type = 'Sensor' OR DashboardWizard.high_level_type = 'Sensor-Actuator' OR (DashboardWizard.high_level_type = 'Special Widget' AND sub_nature = 'First Aid Data')) GROUP BY unique_name_id ORDER BY id DESC;";
    //    $query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'Sensor' GROUP BY unique_name_id ORDER BY id ASC;";
    //    $query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'From IOT Device to Dashboard' GROUP BY unique_name_id ORDER BY id ASC;";
    }
} else {
    $query = "SELECT * FROM Dashboard.DashboardWizard WHERE (DashboardWizard.high_level_type = 'Sensor' OR DashboardWizard.high_level_type = 'Sensor-Actuator' OR (DashboardWizard.high_level_type = 'Special Widget' AND sub_nature = 'First Aid Data')) GROUP BY unique_name_id ORDER BY id DESC;";
 //   $query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'Sensor' GROUP BY unique_name_id ORDER BY id ASC;";
  //  $query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'From IOT Device to Dashboard' GROUP BY unique_name_id ORDER BY id ASC;";
}
//$query = "SELECT * FROM Dashboard.DashboardWizard WHERE DashboardWizard.high_level_type = 'Sensor'";


$rs = mysqli_query($link, $query);
$result = [];


if ($rs) {
    $result = [];
    $count = 0;
    try {
        while ($row = mysqli_fetch_assoc($rs)) {
            $high_level_type = $row['high_level_type'];
            $nature = $row['nature'];
            $sub_nature = $row['sub_nature'];
            $low_level_type = $row['low_level_type'];
            $unique_name_id = $row['unique_name_id'];

            $instance_uri = $row['instance_uri'];
            //   $unit = $row[unit];
            $metric = $row['metric'];
            $saved_direct = $row['saved_direct'];
            $kb_based = $row['kb_based'];
            $parameters = $row['parameters'];

            array_push($result, $row);
            if ($row['high_level_type'] == 'From Dashboad to IOT Device' || $row['high_level_type'] == 'From IOT Device to Dashboard') {
                //   $url = "http://www.disit.org/ServiceMap/api/v1/?serviceUri=".$row[instance_uri]."&healthiness=true";
             //   $url = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['get_instances'] . "&healthiness=true&format=application%2Fsparql-results%2Bjson";
                $url = "http://www.disit.org/superservicemap/api/v1/?serviceUri=" . $row['get_instances'] . "&healthiness=true&format=application%2Fsparql-results%2Bjson";
               // $instance_uri = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['instance_uri'];
                $instance_uri = "single_marker";
            } else if ($row['nature'] != 'IoTDevice' && $sub_nature != "First Aid Data") {
            //    $url = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['get_instances'] . "&healthiness=true&format=application%2Fsparql-results%2Bjson";
                $url = "http://www.disit.org/superservicemap/api/v1/?serviceUri=" . $row['get_instances'] . "&healthiness=true&format=application%2Fsparql-results%2Bjson";
             //   $instance_uri = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['unique_name_id'];
                $instance_uri = "any + status";
            } else if ($sub_nature === "First Aid Data") {
                $url = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['parameters'] . "&healthiness=true&format=application%2Fsparql-results%2Bjson";
            } /*else {
                $url = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['get_instances'] . "&healthiness=true";
                //   $instance_uri = "http://servicemap.disit.org/WebAppGrafo/api/v1/?serviceUri=" . $row['unique_name_id'];
                $instance_uri = "any + status";
            }   */

            $response = file_get_contents($url);
            $responseArray = json_decode($response, true);

            $realtime_data = $responseArray['realtime']['results']['bindings'][0];
            $healthiness = $responseArray['healthiness'];

            $now = new DateTime(null, new DateTimeZone('Europe/Rome'));
            $date_now = $now->format('c');
            $date_now_ok = explode("+", $date_now);
            $check_time = str_replace("T", " ", $date_now_ok[0]);
            //     print_r($check_time);

            if ($sub_nature === 'Car_park') {
                $stop_flag = 1;
            }

            if (!empty($realtime_data)) {
                if (strpos($unique_name_id, 'METRO') !== false) {
                    $last_date = $realtime_data['instantTime']['value'];
                } else {
                    $last_date = $realtime_data['measuredTime']['value'];
                }

                $last_date_ok = explode("+", $last_date);
                $last_date_wonderful = str_replace("T", " ", $last_date_ok[0]);

                foreach ($realtime_data as $key => $item) {

                    if ($key != 'measuredTime' && $key != 'updating' && $key != 'instantTime') {
                        if ($key != 'capacity' || $sub_nature != 'Car_park') {
                            // QUI ALTRE CONDIZIONI PER FILTRARE IL PROCESSING !!!
                            $measure = $realtime_data[$key]['value'];
                            // if ($realtime_data[$key]['unit'] != '') {
                            if (!empty($realtime_data[$key]['unit'])) {
                                $unit = $realtime_data[$key]['unit'];
                            }

                            if (array_key_exists($key, $healthiness)) {

                                $healthiness_value = $healthiness[$key]['healthy'];

                            } else {

                                $healthiness_value = "false";
                            }

                        //    if ($row['high_level_type'] == 'From Dashboad to IOT Device' || $row['high_level_type'] == 'From IOT Device to Dashboard') {

                            /*
                                if ($healthiness_value = $healthiness[$key]['healthy'] === false) {
                                    $healthy = "false";
                                } else if ($healthiness_value = $healthiness[$key]['healthy'] === true) {
                                    $healthy = "true";
                                } else {
                                    $healthy = "false";
                                }
                                // "INSERT INTO questions  (title, description, username, date_made) VALUES ('$title','$description','$username','$a')"
                                $query_insert = "INSERT INTO IOT_Sensors_for_DashboardWizard (nature, high_level_type, sub_nature, low_level_type, unique_name_id, instance_uri, last_date, last_value, unit, metric, saved_direct, kb_based, parameters, healthiness, lastCheck, icon6) VALUES ('$nature','$high_level_type','$sub_nature','$key', '$unique_name_id', '$instance_uri', '$last_date_wonderful', '$measure', '$unit', '$metric', '$saved_direct', '$kb_based', '$parameters', '$healthiness_value', '$check_time', '')";
                                //  $rs = mysqli_query($link, $query_insert);
                                mysqli_query($link, "INSERT INTO IOT_Sensors_for_DashboardWizard (nature, high_level_type, sub_nature, low_level_type, unique_name_id, instance_uri, last_date, last_value, unit, metric, saved_direct, kb_based, parameters, healthiness, lastCheck, icon6) VALUES ('$nature','$high_level_type','$sub_nature','$key', '$unique_name_id', '$instance_uri', '$last_date_wonderful', '$measure', '$unit', '$metric', '$saved_direct', '$kb_based', '$parameters', '$healthiness_value', '$check_time', '')");

                            */

                        //    } else {

                                if ($healthiness_value = $healthiness[$key]['healthy'] === false) {
                                    $healthy = "false";
                                } else if ($healthiness_value = $healthiness[$key]['healthy'] === true) {
                                    $healthy = "true";
                                } else {
                                    $healthy = "false";
                                }

                                $query_update = "UPDATE DashboardWizard SET last_date= '" . $last_date_wonderful . "', last_value = '" . $measure . "', healthiness = '" . $healthy . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '" . $key . "';";
                                //    $rs = mysqli_query($link, $query_update);
                                mysqli_query($link, "UPDATE DashboardWizard SET last_date= '" . $last_date_wonderful . "', last_value = '" . $measure . "', healthiness = '" . $healthy . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '" . $key . "';");

                      //      }
                        }
                    } else {
                        $stop_flag = 1;
                    }

                }
                //**********************************************************************************
                $now = new DateTime(null, new DateTimeZone('Europe/Rome'));
                $date_now = $now->format('c');
                $date_now_ok = explode("+", $date_now);
                $check_time = str_replace("T", " ", $date_now_ok[0]);


                // Per i Sensori a livello generale (senza misure) si mette healthiness = 'true' se almeno una delle sue misure ha heathiness = 'true', altrimenti si mette healthiness = 'false';
                $checkHealthinessSensorGeneralQuery = "SELECT * FROM DashboardWizard WHERE unique_name_id = '" . $unique_name_id . "' AND healthiness = 'true'";
                $rs2 = mysqli_query($link, $checkHealthinessSensorGeneralQuery);

                $result2 = [];

                if ($rs2) {
                    $result2['table'] = [];
                    if ($row2 = mysqli_fetch_assoc($rs2)) {
                        $healthiness_sql = 'true';
                        $last_date_sql = $row2['last_date'];
                    } else {
                        $healthiness_sql = 'false';
                        $lastDateSensorGeneralQuery = "SELECT * FROM DashboardWizard WHERE unique_name_id = '" . $unique_name_id . "'";
                        $rs3 = mysqli_query($link, $lastDateSensorGeneralQuery);
                        if ($rs3) {
                            $result3['table'] = [];
                            if ($row3 = mysqli_fetch_assoc($rs3)) {
                                $last_date_sql = $row3['last_date'];
                            }
                        }
                    }
                }

                if ($last_date_sql === null) {
                    $query_updateGeneral = "UPDATE DashboardWizard SET last_date = last_date, healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';";
                    mysqli_query($link, "UPDATE DashboardWizard SET last_date = last_date, healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';");
                } else  if ($last_date_sql === null) {
                    $query_updateGeneral = "UPDATE DashboardWizard SET last_date = last_date, healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';";
                    mysqli_query($link, "UPDATE DashboardWizard SET last_date = last_date, healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';");
                } else {
                    $query_updateGeneral = "UPDATE DashboardWizard SET last_date= '" . $last_date_sql . "', healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';";
                    mysqli_query($link, "UPDATE DashboardWizard SET last_date= '" . $last_date_sql . "', healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';");
                }
                //**********************************************************************************

            } else {
                if ($unique_name_id != '') {

                    //**********************************************************************************
                    $now = new DateTime(null, new DateTimeZone('Europe/Rome'));
                    $date_now = $now->format('c');
                    $date_now_ok = explode("+", $date_now);
                    $check_time = str_replace("T", " ", $date_now_ok[0]);

                    $checkHealthinessSensorGeneralQuery = "SELECT * FROM DashboardWizard WHERE unique_name_id = '" . $unique_name_id . "' AND healthiness = 'true'";
                    $rs2 = mysqli_query($link, $checkHealthinessSensorGeneralQuery);

                    $result2 = [];

                    if ($rs2) {
                        $result2['table'] = [];
                        if ($row2 = mysqli_fetch_assoc($rs2)) {
                            $healthiness_sql = 'true';
                            $last_date_sql = $row2['last_date'];
                        } else {
                            $healthiness_sql = 'false';
                            $lastDateSensorGeneralQuery = "SELECT * FROM DashboardWizard WHERE unique_name_id = '" . $unique_name_id . "'";
                            $rs3 = mysqli_query($link, $lastDateSensorGeneralQuery);
                            if ($rs3) {
                                $result3['table'] = [];
                                if ($row3 = mysqli_fetch_assoc($rs3)) {
                                    $last_date_sql = $row3['last_date'];
                                }
                            }
                        }
                    }
                    if ($last_date_sql === null) {
                    //    $last_date_sql = '';
                    //    $query_updateGeneral = "UPDATE DashboardWizard SET healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';";
                        $query_updateGeneral = "UPDATE DashboardWizard SET healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "';";

                    //    mysqli_query($link, "UPDATE DashboardWizard SET healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';");
                        mysqli_query($link, "UPDATE DashboardWizard SET healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "';");
                    } else {
                    //    $query_updateGeneral = "UPDATE DashboardWizard SET last_date= '" . $last_date_sql . "', healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';";
                        $query_updateGeneral = "UPDATE DashboardWizard SET last_date= '" . $last_date_sql . "', healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "';";

                        // mysqli_query($link, "UPDATE DashboardWizard SET last_date= '" . $last_date_sql . "', healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "' AND low_level_type = '';");
                        mysqli_query($link, "UPDATE DashboardWizard SET last_date= '" . $last_date_sql . "', healthiness = '" . $healthiness_sql . "', lastCheck = '" . $check_time . "' WHERE unique_name_id= '" . $unique_name_id . "';");
                    }
                    //**********************************************************************************

                }
            }

            //    } else {
            //   if ($unique_name_id != '') {


            //      }

            //  }

            $stopFlag = 1;
            $count++;
            echo($count . " " . $unique_name_id . "\n");

        }
    } catch (Exception $e) {
        echo 'Exception: ',  $e->getMessage(), "\n";
    }


    //Eliminiamo i duplicati
    /*  $result = array_unique($result);
      mysqli_close($link);
      $result['detail'] = 'Ok';

      $response = [];

      $url = "http://www.disit.org/ServiceMap/api/v1/?serviceUri=http://www.disit.org/km4city/resource/SensoreViaBolognese&format=json";


      $response = file_get_contents($url);
      $res = json_encode($response);

      echo json_encode($res);*/

} else {
    mysqli_close($link);
    $result['detail'] = 'Ko';
}

mysqli_query($link, "UPDATE DashboardWizard SET healthiness = 'false' WHERE healthiness IS NULL OR healthiness = '';");

$endTime = new DateTime(null, new DateTimeZone('Europe/Rome'));
$end_scritp_time = $endTime->format('c');
$end_scritp_time_string = explode("+", $end_scritp_time);
$end_time_ok = str_replace("T", " ", $end_scritp_time_string[0]);
echo("End HealthinessCheck SCRIPT at: ".$end_time_ok);
//echo json_encode($result);
<?php
/* Dashboard Builder.
Copyright (C) 2018 DISIT Lab https://www.disit.org - University of Florence

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
error_reporting(E_ERROR | E_NOTICE);
date_default_timezone_set('Europe/Rome');

session_start();
$link = mysqli_connect($host, $username, $password);
mysqli_select_db($link, $dbname);

$metricName = mysqli_real_escape_string($link, $_REQUEST['metricName']);

$response = [];

$q = "SELECT * FROM Dashboard.HeatmapRanges WHERE metricName = '$metricName'";
$r = mysqli_query($link, $q);

if($r && mysqli_num_rows($r) > 0) {
    $res = mysqli_fetch_assoc($r);
    $response['heatmapLegendPath'] = $res['iconPath'];
    $response['detail'] = 'Ok';
} else {
    $response['detail'] = 'Ko';
}

echo json_encode($response);

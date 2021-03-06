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

include('../config.php');
header("Cache-Control: private, max-age=$cacheControlMaxAge");
?>

<script type='text/javascript'>
    $(document).ready(function <?= $_REQUEST['name_w'] ?>(firstLoad, metricNameFromDriver, widgetTitleFromDriver, widgetHeaderColorFromDriver, widgetHeaderFontColorFromDriver, fromGisExternalContent, fromGisExternalContentServiceUri, fromGisExternalContentField, fromGisExternalContentRange, /*randomSingleGeoJsonIndex,*/ fromGisMarker, fromGisMapRef) {
        <?php
        $titlePatterns = array();
        $titlePatterns[0] = '/_/';
        $titlePatterns[1] = '/\'/';
        $replacements = array();
        $replacements[0] = ' ';
        $replacements[1] = '&apos;';
        $title = $_REQUEST['title_w'];
        $link = mysqli_connect($host, $username, $password);
        if (checkWidgetNameInDashboard($link, $_REQUEST['name_w'], $_REQUEST['id_dashboard']) === false) {
            eventLog("Returned the following ERROR in widgetAlarmsNew.php for the widget ".escapeForHTML($_REQUEST['name_w'])." is not instantiated or allowed in this dashboard.");
            exit();
        }
        ?>
        var timeFontSize, scroller, widgetProperties, styleParameters, icon, serviceUri,
            eventName, eventType, newRow, newIcon, eventContentW, widgetTargetList, backgroundTitleClass,
            backgroundFieldsClass,
            background, originalHeaderColor, originalBorderColor, eventTitle, temp, day, month, hour, min, sec,
            eventStart,
            eventName, serviceUri, eventLat, eventLng, eventTooltip, eventStartDate, eventStartTime, widgetTitle,
            eventsNumber, widgetWidth, shownHeight, rowPercHeight, contentHeightPx, eventContentWPerc, dataContainer,
            middleContainer, subTypeContainer,
            dateContainer, timeContainer, severityContainer, mapPtrContainer, pinContainer, pinMsgContainer,
            fontSizePin, dateFontSize, mapPinImg, eventNameWithCase, eventSeverity,
            typeId, lastPopup, widgetParameters, countdownRef = null;

        var speed = 50;
        var hostFile = "<?= escapeForJS($_REQUEST['hostFile']) ?>";
        var widgetName = "<?= $_REQUEST['name_w'] ?>";
        var elToEmpty = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer");
        var embedWidget = <?= $_REQUEST['embedWidget']=='true'?'true':'false' ?>;
        var embedWidgetPolicy = '<?= escapeForJS($_REQUEST['embedWidgetPolicy']) ?>';
        var showHeader, showTitle, timeToReload, fontSize, widgetHeaderFontColor, widgetHeaderColor, widgetContentColor,
            fontSize, hasTimer = null;
        var headerHeight = 25;

        var eventsArray = [];
        var eventsOnMaps = {};
        var targetsArrayForNotify = [];

        var allFloodsOnMap = false;
        var allOthersOnMap = false;
        var severitySortState = 0;
        var timeSortState = 0;

        $(document).on("esbEventAdded", function (event) {
            if (event.generator !== "<?= $_REQUEST['name_w'] ?>") {
                $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").each(function (i) {
                    if ($("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(i).attr("data-onmap") === "true") {
                        var evtType = null;

                        for (widgetName in widgetTargetList) {
                            evtType = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(i).attr("data-eventtype");

                            for (var index in widgetTargetList[widgetName]) {
                                if (evtType === widgetTargetList[widgetName][index]) {
                                    $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(i).attr("data-onmap", "false");

                                    $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(i).parent().parent().find("div.trafficEventPinMsgContainer").html("");
                                    $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(i).removeClass("onMapTrafficEventPinAnimated");
                                    $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(i).parent().parent().find("div.trafficEventPinMsgContainer").removeClass("onMapTrafficEventPinAnimated");

                                    eventsOnMaps[widgetName].eventsNumber = 0;
                                    eventsOnMaps[widgetName].eventsPoints.splice(0);
                                    eventsOnMaps[widgetName].mapRef = null;

                                    severitySortState = 0;
                                    timeSortState = 0;
                                    $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(0).find("img").attr("src", "../img/trafficIcons/severity.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(0).find("div.trafficEventsButtonIndicator").html("");
                                    $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(1).find("img").attr("src", "../img/trafficIcons/time.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(1).find("div.trafficEventsButtonIndicator").html("");

                                    allFloodsOnMap = false;
                                    allOthersOnMap = false;
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("");
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "black");
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/flood.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("");
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "black");
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarm.png");
                                }
                            }
                        }
                    }
                });
            }
        });

        //Definizioni di funzione
        function loadDefaultMap(widgetName) {
            if ($('#' + widgetName + '_defaultMapDiv div.leaflet-map-pane').length > 0) {
                //Basta nasconderla, tanto viene distrutta e ricreata ad ogni utilizzo (per ora).
                $('#' + widgetName + '_mapDiv').hide();
                $('#' + widgetName + '_defaultMapDiv').show();
            }
            else {
                var mapdiv = widgetName + "_defaultMapDiv";
                var mapRef = L.map(mapdiv).setView([43.769789, 11.255694], 11);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
                    maxZoom: 18
                }).addTo(mapRef);
                mapRef.attributionControl.setPrefix('');
            }
        }

        function populateWidget(fromSort) {
            var i = 0;

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer').empty();

            for (var key in eventsArray) {
                temp = eventsArray[key].payload.open_time;
                eventStart = new Date(temp);

                day = eventStart.getDate();
                if (day < 10) {
                    day = "0" + day.toString();
                }

                month = eventStart.getMonth() + 1;
                if (month < 10) {
                    month = "0" + month.toString();
                }

                hour = eventStart.getHours();
                if (hour < 10) {
                    hour = "0" + hour.toString();
                }

                min = eventStart.getMinutes();
                if (min < 10) {
                    min = "0" + min.toString();
                }

                sec = eventStart.getSeconds();
                if (sec < 10) {
                    sec = "0" + sec.toString();
                }

                eventStartDate = day + "/" + month + "/" + eventStart.getFullYear().toString();
                eventStartTime = hour + ":" + min + ":" + sec;
                eventNameWithCase = eventsArray[key].payload.name;
                eventNameWithCase = eventsArray[key].payload.name.replace(/\./g, "");
                eventNameWithCase = eventsArray[key].payload.name.replace(/'/g, "&apos;");
                eventNameWithCase = eventsArray[key].payload.name.replace(/\u0027/g, "&apos;");

                eventName = eventsArray[key].payload.name.toLowerCase();
                eventName = eventsArray[key].payload.name.replace(/\./g, "").toLowerCase();
                eventName = eventsArray[key].payload.name.replace(/'/g, "&apos;").toLowerCase();
                eventName = eventsArray[key].payload.name.replace(/\u0027/g, "&apos;").toLowerCase();

                eventLat = eventsArray[key].payload.location.latitude;
                eventLng = eventsArray[key].payload.location.longitude;
                eventSeverity = eventsArray[key].payload.severity;
                typeId = eventsArray[key].payload.type_id;

                newRow = $('<div class="trafficEventRow"></div>');

                switch (eventSeverity) {
                    case "MINOR"://low
                        backgroundTitleClass = "lowSeverityTitle";
                        backgroundFieldsClass = "lowSeverity";//Giallo
                        background = "#ffcc00";
                        break;

                    case "MAJOR"://med
                        backgroundTitleClass = "medSeverityTitle";
                        backgroundFieldsClass = "medSeverity";//Arancio
                        background = "#ff9900";
                        break;

                    case "CRITICAL"://high
                        backgroundTitleClass = "highSeverityTitle";
                        backgroundFieldsClass = "highSeverity";//Rosso
                        background = "#ff6666";
                        break;
                }

                eventType = typeId.replace("urn:rixf:org.resolute-eu.common/alarm_types/", "");
                if (alarmTypes.hasOwnProperty(eventType)) {
                    icon = $('<img src="../img/alarmIcons/' + alarmTypes[eventType].icon + '" />');
                    eventTooltip = alarmTypes[eventType].desc;
                }
                else {
                    icon = $('<img src="../img/alarmIcons/' + alarmTypes["others"].icon + '" />');
                    eventTooltip = alarmTypes["others"].desc;
                }

                newRow.css("height", rowPercHeight + "%");
                eventTitle = $('<div class="eventTitle"><p class="eventTitlePar"><span>' + eventName + '</span></p></div>');
                eventTitle.addClass(backgroundTitleClass);
                eventTitle.css("height", "30%");
                $('#<?= $_REQUEST['name_w'] ?>_rollerContainer').append(newRow);

                newRow.append(eventTitle);

                dataContainer = $('<div class="trafficEventDataContainer"></div>');
                newRow.append(dataContainer);

                newIcon = $("<div class='trafficEventIcon centerWithFlex' data-toggle='tooltip' data-placement='top' title='" + eventTooltip + "'></div>");
                newIcon.append(icon);
                newIcon.addClass(backgroundFieldsClass);
                dataContainer.append(newIcon);

                middleContainer = $("<div class='trafficEventMiddleContainer'></div>");

                severityContainer = $("<div class='alarmSeverityContainer centerWithFlex'></div>");
                severityContainer.addClass(backgroundFieldsClass);
                severityContainer.html(eventSeverity);
                middleContainer.append(severityContainer);

                middleContainer.append(subTypeContainer);

                dateContainer = $("<div class='alarmDateContainer centerWithFlex'></div>");
                dateContainer.addClass(backgroundFieldsClass);
                dateContainer.html(eventStartDate);
                middleContainer.append(dateContainer);

                timeContainer = $("<div class='alarmTimeContainer centerWithFlex'></div>");
                timeContainer.addClass(backgroundFieldsClass);
                timeContainer.html(eventStartTime);
                middleContainer.append(timeContainer);

                dataContainer.append(middleContainer);

                mapPtrContainer = $("<div class='trafficEventMapPtr'></div>");
                mapPtrContainer.addClass(backgroundFieldsClass);

                pinContainer = $("<div class='trafficEventPinContainer'><a class='trafficEventLink' data-eventstartdate='" + eventStartDate + "' data-eventstarttime='" + eventStartTime + "' data-eventSeverity='" + eventSeverity + "' data-eventType='" + eventType + "' data-eventname='" + eventNameWithCase +
                    "' data-eventlat='" + eventLat + "' data-eventlng='" + eventLng + "' data-background='" + background + "' data-onMap='false'><i class='material-icons'>place</i></a></div>");
                mapPtrContainer.append(pinContainer);
                pinMsgContainer = $("<div class='trafficEventPinMsgContainer'></div>");
                mapPtrContainer.append(pinMsgContainer);

                dataContainer.append(mapPtrContainer);

                if (i < (eventsNumber - 1)) {
                    newRow.css("margin-bottom", "4px");
                }
                else {
                    newRow.css("margin-bottom", "0px");
                }

                $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop(0);

                //Interazione cross-widget
                pinContainer.find("a.trafficEventLink").hover(
                    function () {
                        var localEventType = $(this).attr("data-eventType");
                        var localBackground = $(this).attr("data-background");

                        originalHeaderColor = {};
                        originalBorderColor = {};

                        for (var widgetName in widgetTargetList) {
                            originalHeaderColor[widgetName] = $("#" + widgetName + "_header").css("background-color");
                            originalBorderColor[widgetName] = $("#" + widgetName).css("border-color");

                            for (var key in widgetTargetList[widgetName]) {
                                if (widgetTargetList[widgetName][key] === localEventType) {
                                    if ($(this).attr("data-onMap") === 'false') {
                                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("show");
                                    }
                                    else {
                                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("hide");
                                    }

                                    if ($(this).attr("data-eventType") === localEventType) {
                                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").removeClass("onMapTrafficEventPinAnimated");
                                        $(this).find("i.material-icons").removeClass("onMapTrafficEventPinAnimated");
                                    }

                                    $(this).parent().parent().find("div.trafficEventPinMsgContainer").css("color", "white");

                                    $("#" + widgetName + "_header").css("background", localBackground);
                                    $("#" + widgetName).css("border-color", localBackground);
                                }
                                else {
                                    //Caso in cui non ci sono target per l'evento disponibile: per ora ok così, poi raffineremo il comportamento
                                }
                            }
                        }
                    },
                    function () {
                        var localEventType = $(this).attr("data-eventType");
                        for (var widgetName in widgetTargetList) {
                            for (var key in widgetTargetList[widgetName]) {
                                if (widgetTargetList[widgetName][key] === localEventType) {
                                    if ($(this).attr("data-onMap") === 'false') {
                                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("");
                                    }
                                    else {
                                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("map");
                                    }

                                    if ($(this).attr("data-eventType") === localEventType) {
                                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").addClass("onMapTrafficEventPinAnimated");
                                        $(this).find("i.material-icons").removeClass("onMapTrafficEventPinAnimated");
                                    }

                                    $(this).parent().parent().find("div.trafficEventPinMsgContainer").css("color", "black");

                                    $("#" + widgetName + "_header").css("background", originalHeaderColor[widgetName]);
                                    $("#" + widgetName).css("border-color", originalBorderColor[widgetName]);
                                }
                            }
                        }
                    }
                );

                pinContainer.find("a.trafficEventLink").click(function () {
                    var localEventType = $(this).attr("data-eventType");
                    var goesOnMap = false;

                    if ($(this).attr("data-onMap") === 'false') {
                        $(this).attr("data-onMap", 'true');
                        goesOnMap = true;
                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("map");
                        $(this).addClass("onMapTrafficEventPinAnimated");
                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").addClass("onMapTrafficEventPinAnimated");
                    }
                    else {
                        $(this).attr("data-onMap", 'false');
                        goesOnMap = false;
                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("");
                        $(this).removeClass("onMapTrafficEventPinAnimated");
                        $(this).parent().parent().find("div.trafficEventPinMsgContainer").removeClass("onMapTrafficEventPinAnimated");
                    }

                    $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("");
                    $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "black");
                    $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/flood.png");
                    $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("");
                    $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "black");
                    $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarm.png");

                    switch (localEventType) {
                        case "flood":
                            if (allFloodsOnMap === true) {
                                allFloodsOnMap = false;
                            }
                            break;

                        case "others":
                            if (allOthersOnMap === true) {
                                allOthersOnMap = false;
                            }
                            break;
                    }

                    targetsArrayForNotify = [];

                    for (var widgetName in widgetTargetList) {
                        for (var key in widgetTargetList[widgetName]) {
                            if (widgetTargetList[widgetName][key] === localEventType) {
                                if (goesOnMap) {
                                    targetsArrayForNotify.push(widgetName);
                                    addEventToMap($(this), widgetName);
                                }
                                else {
                                    removeEventFromMap($(this), widgetName);
                                }
                            }
                        }
                    }
                });
                i++;
            }//Fine del for 

            var maxTitleFontSize = $('div.eventTitle').height() * 0.75;

            if (maxTitleFontSize > fontSize) {
                maxTitleFontSize = fontSize;
            }

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer p.eventTitlePar span').css("font-size", maxTitleFontSize + "px");
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmSeverityContainer').css("font-size", maxTitleFontSize + "px");
            var subdataFontSize = $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmDateContainer').eq(0).width() * 0.12;
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmDateContainer').css("font-size", subdataFontSize + "px");
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmTimeContainer').css("font-size", subdataFontSize + "px");
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventLink i').css("font-size", parseFloat($('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventPinContainer').eq(0).width() / 1.5) + "px");

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventPinMsgContainer').css("font-size", parseFloat($('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventPinContainer').eq(0).width() / 2.846) + "px");

            if (!fromSort) {
                var btnIndicatorFontSize = $("#<?= $_REQUEST['name_w'] ?>_div div.trafficEventsButtonIndicator").eq(0).width() / 48.4375;
                $("#<?= $_REQUEST['name_w'] ?>_div div.trafficEventsButtonIndicator").css("font-size", btnIndicatorFontSize + "em");
            }

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer [data-toggle="tooltip"]').tooltip({
                html: true
            });
        }

        function removeAllEventsFromMap(eventType) {
            for (var widgetName in widgetTargetList) {

                var passedData = [];

                for (var key in widgetTargetList[widgetName]) {
                    if (widgetTargetList[widgetName][key] === eventType) {
                        $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink[data-eventtype=" + eventType + "]").each(function () {
                            var lat = $(this).attr("data-eventlat");
                            var lng = $(this).attr("data-eventlng");
                            var eventType = $(this).attr("data-eventType");
                            var eventName = $(this).attr("data-eventname");

                            var coordsAndType = {};

                            coordsAndType.lng = lng;
                            coordsAndType.lat = lat;
                            coordsAndType.eventType = eventType;
                            coordsAndType.eventName = eventName;

                            passedData.push(coordsAndType);
                        });
                    }
                }
                $.event.trigger({
                    type: "removeAlarm",
                    target: widgetName,
                    passedData: passedData
                });

                $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink[data-eventtype=" + eventType + "]").each(function (i) {
                    $(this).attr("data-onMap", 'false');
                    $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("");
                    $(this).removeClass("onMapTrafficEventPinAnimated");
                    $(this).parent().parent().find("div.trafficEventPinMsgContainer").removeClass("onMapTrafficEventPinAnimated");
                });
            }
        }

        function addAllEventsToMap(eventType) {
            for (var widgetName in widgetTargetList) {

                var passedData = [];

                for (var key in widgetTargetList[widgetName]) {
                    if (widgetTargetList[widgetName][key] === eventType) {
                        $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink[data-eventtype=" + eventType + "]").each(function (i) {
                            var lat = $(this).attr("data-eventlat");
                            var lng = $(this).attr("data-eventlng");
                            var eventType = $(this).attr("data-eventType");
                            var eventName = $(this).attr("data-eventname");
                            var eventSeverity = $(this).attr("data-eventSeverity");
                            var eventStartDate = $(this).attr("data-eventstartdate");
                            var eventStartTime = $(this).attr("data-eventstarttime");

                            var coordsAndType = {};

                            coordsAndType.lng = lng;
                            coordsAndType.lat = lat;
                            coordsAndType.eventType = eventType;
                            coordsAndType.eventName = eventName;
                            coordsAndType.eventSeverity = eventSeverity;
                            coordsAndType.eventStartDate = eventStartDate;
                            coordsAndType.eventStartTime = eventStartTime;

                            passedData.push(coordsAndType);

                        });
                    }
                }
                $.event.trigger({
                    type: "addAlarm",
                    target: widgetName,
                    passedData: passedData
                });

                $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink[data-eventtype=" + eventType + "]").each(function (i) {
                    $(this).attr("data-onMap", 'true');
                    $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("map");
                    $(this).addClass("onMapTrafficEventPinAnimated");
                    $(this).parent().parent().find("div.trafficEventPinMsgContainer").addClass("onMapTrafficEventPinAnimated");
                });
            }
        }

        function addEventToMap(eventLink, widgetName) {

            var passedData = [];

            var lat = eventLink.attr("data-eventlat");
            var lng = eventLink.attr("data-eventlng");
            var eventType = eventLink.attr("data-eventType");
            var eventName = eventLink.attr("data-eventname");
            var eventSeverity = eventLink.attr("data-eventSeverity");
            var eventStartDate = eventLink.attr("data-eventstartdate");
            var eventStartTime = eventLink.attr("data-eventstarttime");

            var coordsAndType = {};

            coordsAndType.lng = lng;
            coordsAndType.lat = lat;
            coordsAndType.eventType = eventType;
            coordsAndType.eventName = eventName;
            coordsAndType.eventSeverity = eventSeverity;
            coordsAndType.eventStartDate = eventStartDate;
            coordsAndType.eventStartTime = eventStartTime;
            coordsAndType.type = "addAlarm";

            passedData.push(coordsAndType);

            $.event.trigger({
                type: "addAlarm",
                target: widgetName,
                passedData: passedData
            });
        }

        function removeEventFromMap(eventLink, widgetName) {

            let passedData = [];

            let lat = eventLink.attr("data-eventlat");
            let lng = eventLink.attr("data-eventlng");
            let eventType = eventLink.attr("data-eventType");
            let eventName = eventLink.attr("data-eventname");

            let coordsAndType = {};

            coordsAndType.lng = lng;
            coordsAndType.lat = lat;
            coordsAndType.eventType = eventType;
            coordsAndType.eventName = eventName;

            passedData.push(coordsAndType);

            $.event.trigger({
                type: "removeAlarm",
                target: widgetName,
                passedData: passedData
            });
        }

        function stepDownInterval() {
            var oldPos = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop();
            var newPos = oldPos + 1;

            var oldScrollTop = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop();
            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop(newPos);
            var newScrollTop = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop();

            if (oldScrollTop === newScrollTop) {
                $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop(0);
            }
        }

        function resizeWidget() {
            setWidgetLayout(hostFile, widgetName, widgetContentColor, widgetHeaderColor, widgetHeaderFontColor, showHeader, headerHeight, hasTimer);

            var btnIndicatorFontSize = $("#<?= $_REQUEST['name_w'] ?>_div div.trafficEventsButtonIndicator").eq(0).width() / 48.4375;
            $("#<?= $_REQUEST['name_w'] ?>_div div.trafficEventsButtonIndicator").css("font-size", btnIndicatorFontSize + "em");

            shownHeight = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").prop("offsetHeight");
            rowPercHeight = 75 * 100 / shownHeight;

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer div.trafficEventRow').css("height", rowPercHeight + "%");
            var maxTitleFontSize = $('div.eventTitle').height() * 0.75;

            if (maxTitleFontSize > fontSize) {
                maxTitleFontSize = fontSize;
            }

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer p.eventTitlePar span').css("font-size", maxTitleFontSize + "px");
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmSeverityContainer').css("font-size", maxTitleFontSize + "px");

            var subdataFontSize = $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmDateContainer').eq(0).width() * 0.12;
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmDateContainer').css("font-size", subdataFontSize + "px");
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .alarmTimeContainer').css("font-size", subdataFontSize + "px");
            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventLink i').css("font-size", parseFloat($('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventPinContainer').eq(0).width() / 1.5) + "px");

            $('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventPinMsgContainer').css("font-size", parseFloat($('#<?= $_REQUEST['name_w'] ?>_rollerContainer .trafficEventPinContainer').eq(0).width() / 2.846) + "px");

            clearInterval(scroller);
            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").scrollTop(0);
            scroller = setInterval(stepDownInterval, speed);
        }

        $(document).off('resizeHighchart_' + widgetName);
        $(document).on('resizeHighchart_' + widgetName, function (event) {
            showHeader = event.showHeader;
        });
        $(document).on('removeAlarmPin', function () {
            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink[data-eventtype=" + eventType + "]").each(function () {
                $(this).attr("data-onMap", 'false');
                $(this).parent().parent().find("div.trafficEventPinMsgContainer").html("");
                $(this).removeClass("onMapTrafficEventPinAnimated");
                $(this).parent().parent().find("div.trafficEventPinMsgContainer").removeClass("onMapTrafficEventPinAnimated");
            });
        });


        //Fine definizioni di funzione 


        $.ajax({
            url: "../controllers/getWidgetParams.php",
            type: "GET",
            data: {
                widgetName: "<?= $_REQUEST['name_w'] ?>"
            },
            async: true,
            dataType: 'json',
            success: function (widgetData) {
                showTitle = widgetData.params.showTitle;
                widgetContentColor = widgetData.params.color_w;
                fontSize = widgetData.params.fontSize;
                timeToReload = widgetData.params.frequency_w;
                hasTimer = widgetData.params.hasTimer;
                widgetTitle = widgetData.params.title_w;
                widgetHeaderColor = widgetData.params.frame_color_w;
                widgetHeaderFontColor = widgetData.params.headerFontColor;

                timeFontSize = parseInt(fontSize * 1.6);
                dateFontSize = parseInt(fontSize * 0.95);
                fontSizePin = parseInt(fontSize * 0.95);

                if (((embedWidget === true) && (embedWidgetPolicy === 'auto')) || ((embedWidget === true) && (embedWidgetPolicy === 'manual') && (showTitle === "no")) || ((embedWidget === false) && (showTitle === "no"))) {
                    showHeader = false;
                }
                else {
                    showHeader = true;
                }

                setWidgetLayout(hostFile, widgetName, widgetContentColor, widgetHeaderColor, widgetHeaderFontColor, showHeader, headerHeight, hasTimer);
                $('#<?= $_REQUEST['name_w'] ?>_div').parents('li.gs_w').off('resizeWidgets');
                $('#<?= $_REQUEST['name_w'] ?>_div').parents('li.gs_w').on('resizeWidgets', resizeWidget);

                $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer").css("background-color", $("#<?= $_REQUEST['name_w'] ?>_header").css("background-color"));

                if (firstLoad === false) {
                    showWidgetContent(widgetName);
                }
                else {
                    setupLoadingPanel(widgetName, widgetContentColor, firstLoad);
                }

                //Nuova versione
                if (('<?= sanitizeJsonRelaxed2($_REQUEST['styleParameters']) ?>' !== "") && ('<?= sanitizeJsonRelaxed2($_REQUEST['styleParameters']) ?>' !== "null")) {
                    styleParameters = JSON.parse('<?= sanitizeJsonRelaxed2($_REQUEST['styleParameters']) ?>');
                }

                if ('<?= sanitizeJsonRelaxed2($_REQUEST['parameters']) ?>'.length > 0) {
                    widgetParameters = JSON.parse('<?= sanitizeJsonRelaxed2($_REQUEST['parameters']) ?>');
                }

                widgetTargetList = widgetParameters;
                var targetName = null;

                for (var name in widgetTargetList) {
                    targetName = name + "_div";
                    eventsOnMaps[name] = {
                        noPointsUrl: null,
                        eventsNumber: 0,
                        eventsPoints: [],//Array indicizzato con le coordinate dei punti mostrati
                        mapRef: null
                    };
                }

                $.ajax({
                    url: "../widgets/esbDao.php",
                    type: "POST",
                    data: {
                        operation: "getAlarms"
                    },
                    async: true,
                    dataType: 'json',
                    success: function (data) {
                        if (firstLoad !== false) {
                            showWidgetContent(widgetName);
                        }
                        else {
                            elToEmpty.empty();
                        }

                        eventsNumber = Object.keys(data).length;

                        if (eventsNumber === 0) {
                            $('#<?= $_REQUEST['name_w'] ?>_buttonsContainer').hide();
                            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").hide();
                            $("#<?= $_REQUEST['name_w'] ?>_noDataAlert").show();
                        }
                        else {
                            $("#<?= $_REQUEST['name_w'] ?>_noDataAlert").hide();
                            $('#<?= $_REQUEST['name_w'] ?>_buttonsContainer').show();
                            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").show();

                            eventsNumber = Object.keys(data).length;
                            widgetWidth = $('#<?= $_REQUEST['name_w'] ?>_div').width();
                            shownHeight = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").prop("offsetHeight");
                            rowPercHeight = 75 * 100 / shownHeight;
                            contentHeightPx = eventsNumber * 100;
                            eventContentWPerc = null;

                            if (contentHeightPx > shownHeight) {
                                eventContentW = parseInt(widgetWidth - 45 - 22);
                            }
                            else {
                                eventContentW = parseInt(widgetWidth - 45 - 5);
                            }

                            eventContentWPerc = Math.floor(eventContentW / widgetWidth * 100);

                            //Inserimento una tantum degli eventi nell'apposito array (per ordinamenti)
                            for (var key in data) {
                                var localPayload = JSON.parse(data[key].payload);
                                data[key].payload = localPayload;
                                eventsArray.push(data[key]);
                            }

                            eventsArray.sort(function (a, b) {
                                //return b.payload.open_time - a.payload.open_time;
                                var itemA = new Date(a.payload.open_time);
                                var itemB = new Date(b.payload.open_time);
                                if (itemA < itemB) {
                                    return 1;
                                }
                                else {
                                    if (itemA > itemB) {
                                        return -1;
                                    }
                                    else {
                                        return 0;
                                    }
                                }
                            });

                            populateWidget(false);

                            $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(0).hover(
                                function () {
                                    $(this).css("cursor", "pointer");
                                    $(this).css("color", "white");
                                    $(this).find("img").attr("src", "../img/trafficIcons/severityWhite.png");

                                    switch (severitySortState) {
                                        case 0://Crescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-down"></i>');
                                            $(this).attr("title", "Sort by severity - Ascending");
                                            break;

                                        case 1://Decrescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-up"></i>');
                                            $(this).attr("title", "Sort by severity - Descending");
                                            break;

                                        case 2://No sort
                                            $(this).find("div.trafficEventsButtonIndicator").html('no sort');
                                            $(this).attr("title", "Sort by severity - None");
                                            break;
                                    }
                                },
                                function () {
                                    $(this).css("cursor", "auto");
                                    $(this).css("color", "black");
                                    $(this).find("img").attr("src", "../img/trafficIcons/severity.png");

                                    switch (severitySortState) {
                                        case 0://No sort
                                            $(this).find("div.trafficEventsButtonIndicator").html('');
                                            break;

                                        case 1://Crescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-down"></i>');
                                            break;

                                        case 2://Decrescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-up"></i>');
                                            break;
                                    }
                                }
                            );

                            $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(1).hover(
                                function () {
                                    $(this).css("cursor", "pointer");
                                    $(this).css("color", "white");
                                    $(this).find("img").attr("src", "../img/trafficIcons/timeWhite.png");
                                    switch (timeSortState) {
                                        case 0://Crescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-down"></i>');
                                            $(this).attr("title", "Sort by time - Ascending");
                                            break;

                                        case 1://Decrescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-up"></i>');
                                            $(this).attr("title", "Sort by time - Descending");
                                            break;

                                        case 2://No sort
                                            $(this).find("div.trafficEventsButtonIndicator").html('no sort');
                                            $(this).attr("title", "Sort by time - None");
                                            break;
                                    }
                                },
                                function () {
                                    $(this).css("cursor", "auto");
                                    $(this).css("color", "black");
                                    $(this).find("img").attr("src", "../img/trafficIcons/time.png");

                                    switch (timeSortState) {
                                        case 0://No sort
                                            $(this).find("div.trafficEventsButtonIndicator").html('');
                                            break;

                                        case 1://Crescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-down"></i>');
                                            break;

                                        case 2://Decrescente verso il basso
                                            $(this).find("div.trafficEventsButtonIndicator").html('<i class="fa fa-caret-up"></i>');
                                            break;
                                    }
                                }
                            );

                            $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(0).click(function () {
                                var localEventType = null;
                                var localEventName = null;
                                var localEventLat = null;
                                var localEventLng = null;
                                var localEventStartDate = null;
                                var localEventStartTime = null;
                                var localEventSeverity = null;

                                switch (severitySortState) {
                                    case 0:
                                        eventsArray.sort(function (a, b) {
                                            var itemA = a.payload.severity.toLowerCase();
                                            var itemB = b.payload.severity.toLowerCase();
                                            if (itemA < itemB) {
                                                return 1;
                                            }
                                            else {
                                                if (itemA > itemB) {
                                                    return -1;
                                                }
                                                else {
                                                    return 0;
                                                }
                                            }
                                        });

                                        populateWidget(true);
                                        removeAllEventsFromMap("others");
                                        removeAllEventsFromMap("flood");

                                        //Aggiunta su mappa dell'elemento in prima posizione post ordinamento
                                        var passedData = [];

                                        localEventType = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventType");
                                        localEventName = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventname");
                                        localEventLat = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlat");
                                        localEventLng = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlng");
                                        localEventStartDate = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstartdate");
                                        localEventStartTime = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstarttime");
                                        localEventSeverity = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventSeverity");

                                        var coordsAndType = {};

                                        coordsAndType.lng = localEventLng;
                                        coordsAndType.lat = localEventLat;
                                        coordsAndType.eventType = localEventType;
                                        coordsAndType.eventName = localEventName;
                                        coordsAndType.eventSeverity = localEventSeverity;
                                        coordsAndType.eventStartDate = localEventStartDate;
                                        coordsAndType.eventStartTime = localEventStartTime;

                                        passedData.push(coordsAndType);

                                        for(var widgetName in widgetTargetList) {
                                            for (var key in widgetTargetList[widgetName]) {
                                                if (widgetTargetList[widgetName][key] === localEventType) {
                                                    $.event.trigger({
                                                        type: "addAlarm",
                                                        target: widgetName,
                                                        passedData: passedData
                                                    });
                                                }
                                            }
                                        }
                                        severitySortState = 1;
                                        break;

                                    case 1:
                                        severitySortState = 2;
                                        eventsArray.sort(function (a, b) {
                                            var itemA = a.payload.severity.toLowerCase();
                                            var itemB = b.payload.severity.toLowerCase();
                                            if (itemA < itemB) {
                                                return -1;
                                            }
                                            else {
                                                if (itemA > itemB) {
                                                    return 1;
                                                }
                                                else {
                                                    return 0;
                                                }
                                            }
                                        });

                                        populateWidget(true);
                                        removeAllEventsFromMap("others");
                                        removeAllEventsFromMap("flood");

                                        //Aggiunta su mappa dell'elemento in prima posizione post ordinamento
                                        var passedData = [];

                                        localEventType = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventType");
                                        localEventName = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventname");
                                        localEventLat = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlat");
                                        localEventLng = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlng");
                                        localEventStartDate = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstartdate");
                                        localEventStartTime = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstarttime");
                                        localEventSeverity = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventSeverity");

                                        var coordsAndType = {};

                                        coordsAndType.lng = localEventLng;
                                        coordsAndType.lat = localEventLat;
                                        coordsAndType.eventType = localEventType;
                                        coordsAndType.eventName = localEventName;
                                        coordsAndType.eventSeverity = localEventSeverity;
                                        coordsAndType.eventStartDate = localEventStartDate;
                                        coordsAndType.eventStartTime = localEventStartTime;

                                        passedData.push(coordsAndType);

                                        for(var widgetName in widgetTargetList) {
                                            for (var key in widgetTargetList[widgetName]) {
                                                if (widgetTargetList[widgetName][key] === localEventType) {
                                                    $.event.trigger({
                                                        type: "addAlarm",
                                                        target: widgetName,
                                                        passedData: passedData
                                                    });
                                                }
                                            }
                                        }
                                        break;

                                    case 2:
                                        severitySortState = 0;
                                        eventsArray.sort(function (a, b) {
                                            return a.id - b.id;
                                        });

                                        populateWidget(true);
                                        removeAllEventsFromMap("others");
                                        removeAllEventsFromMap("flood");
                                        break;
                                }

                                timeSortState = 0;
                            });

                            $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(1).click(function () {
                                var localEventType = null;
                                var localEventName = null;
                                var localEventLat = null;
                                var localEventLng = null;
                                var localEventStartDate = null;
                                var localEventStartTime = null;
                                var localEventSeverity = null;

                                switch (timeSortState) {
                                    case 0:
                                        eventsArray.sort(function (a, b) {
                                            var itemA = new Date(a.payload.open_time);
                                            var itemB = new Date(b.payload.open_time);
                                            if (itemA > itemB) {
                                                return 1;
                                            }
                                            else {
                                                if (itemA < itemB) {
                                                    return -1;
                                                }
                                                else {
                                                    return 0;
                                                }
                                            }
                                        });

                                        populateWidget(true);
                                        removeAllEventsFromMap("others");
                                        removeAllEventsFromMap("flood");

                                        //Aggiunta su mappa dell'elemento in prima posizione post ordinamento
                                        var passedData = [];

                                        localEventType = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventType");
                                        localEventName = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventname");
                                        localEventLat = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlat");
                                        localEventLng = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlng");
                                        localEventStartDate = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstartdate");
                                        localEventStartTime = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstarttime");
                                        localEventSeverity = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventSeverity");

                                        timeSortState = 1;

                                        var coordsAndType = {};

                                        coordsAndType.lng = localEventLng;
                                        coordsAndType.lat = localEventLat;
                                        coordsAndType.eventType = localEventType;
                                        coordsAndType.eventName = localEventName;
                                        coordsAndType.eventSeverity = localEventSeverity;
                                        coordsAndType.eventStartDate = localEventStartDate;
                                        coordsAndType.eventStartTime = localEventStartTime;

                                        passedData.push(coordsAndType);

                                        for(var widgetName in widgetTargetList) {
                                            for (var key in widgetTargetList[widgetName]) {
                                                if (widgetTargetList[widgetName][key] === localEventType) {
                                                    $.event.trigger({
                                                        type: "addAlarm",
                                                        target: widgetName,
                                                        passedData: passedData
                                                    });
                                                }
                                            }
                                        }
                                        break;

                                    case 1:
                                        eventsArray.sort(function (a, b) {
                                            var itemA = new Date(a.payload.open_time);
                                            var itemB = new Date(b.payload.open_time);
                                            if (itemA < itemB) {
                                                return 1;
                                            }
                                            else {
                                                if (itemA > itemB) {
                                                    return -1;
                                                }
                                                else {
                                                    return 0;
                                                }
                                            }
                                        });

                                        populateWidget(true);
                                        removeAllEventsFromMap("others");
                                        removeAllEventsFromMap("flood");

                                        //Aggiunta su mappa dell'elemento in prima posizione post ordinamento
                                        var passedData = [];

                                        localEventType = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventType");
                                        localEventName = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventname");
                                        localEventLat = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlat");
                                        localEventLng = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventlng");
                                        localEventStartDate = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstartdate");
                                        localEventStartTime = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventstarttime");
                                        localEventSeverity = $("#<?= $_REQUEST['name_w'] ?>_rollerContainer a.trafficEventLink").eq(0).attr("data-eventSeverity");

                                        timeSortState = 2;

                                        var coordsAndType = {};

                                        coordsAndType.lng = localEventLng;
                                        coordsAndType.lat = localEventLat;
                                        coordsAndType.eventType = localEventType;
                                        coordsAndType.eventName = localEventName;
                                        coordsAndType.eventSeverity = localEventSeverity;
                                        coordsAndType.eventStartDate = localEventStartDate;
                                        coordsAndType.eventStartTime = localEventStartTime;

                                        passedData.push(coordsAndType);

                                        for(var widgetName in widgetTargetList) {
                                            for (var key in widgetTargetList[widgetName]) {
                                                if (widgetTargetList[widgetName][key] === localEventType) {
                                                    $.event.trigger({
                                                        type: "addAlarm",
                                                        target: widgetName,
                                                        passedData: passedData
                                                    });
                                                }
                                            }
                                        }
                                        break;

                                    case 2:
                                        eventsArray.sort(function (a, b) {
                                            var itemA = new Date(a.payload.open_time);
                                            var itemB = new Date(b.payload.open_time);
                                            if (itemA < itemB) {
                                                return 1;
                                            }
                                            else {
                                                if (itemA > itemB) {
                                                    return -1;
                                                }
                                                else {
                                                    return 0;
                                                }
                                            }
                                        });

                                        populateWidget(true);
                                        removeAllEventsFromMap("others");
                                        removeAllEventsFromMap("flood");

                                        timeSortState = 0;
                                        break;
                                }
                                severitySortState = 0;
                            });

                            //Bottoni per categoria
                            $("#<?= $_REQUEST['name_w'] ?>_floodButton").hover(
                                function () {
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/floodWhite.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton img").css("cursor", "pointer");

                                    originalHeaderColor = {};
                                    originalBorderColor = {};

                                    for (var widgetName in widgetTargetList) {
                                        originalHeaderColor[widgetName] = $("#" + widgetName + "_header").css("background-color");
                                        originalBorderColor[widgetName] = $("#" + widgetName).css("border-color");

                                        for (var key in widgetTargetList[widgetName]) {
                                            if (widgetTargetList[widgetName][key] === "flood") {
                                                $("#" + widgetName + "_header").css("background", "#ff6666");
                                                $("#" + widgetName).css("border-color", "#ff6666");
                                            }
                                        }
                                    }//Fine del for

                                    if (allFloodsOnMap === false) {
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "#FFFFFF");
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("show");
                                    }
                                    else {
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "#FFFFFF");
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("hide");
                                    }
                                },
                                function () {
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/flood.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_floodButton img").css("cursor", "auto");

                                    for (var widgetName in widgetTargetList) {
                                        for (var key in widgetTargetList[widgetName]) {
                                            if (widgetTargetList[widgetName][key] === "flood") {
                                                $("#" + widgetName + "_header").css("background", originalHeaderColor[widgetName]);
                                                $("#" + widgetName).css("border-color", originalBorderColor[widgetName]);
                                            }
                                        }
                                    }

                                    if (allFloodsOnMap === false) {
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/flood.png");
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "#000000");
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("");
                                    }
                                    else {
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/floodWhite.png");
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "#FFFFFF");
                                        $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("map");
                                    }
                                });

                            $("#<?= $_REQUEST['name_w'] ?>_floodButton").click(function () {
                                $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "black");
                                $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("");
                                $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarm.png");
                                allOthersOnMap = false;

                                if (allFloodsOnMap === false) {
                                    removeAllEventsFromMap("others");
                                    removeAllEventsFromMap("flood");

                                    addAllEventsToMap("flood");
                                    allFloodsOnMap = true;
                                }
                                else {
                                    removeAllEventsFromMap("flood");
                                    allFloodsOnMap = false;
                                }
                            });

                            $("#<?= $_REQUEST['name_w'] ?>_othersButton").hover(
                                function () {
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarmWhite.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton img").css("cursor", "pointer");

                                    originalHeaderColor = {};
                                    originalBorderColor = {};

                                    for (var widgetName in widgetTargetList) {
                                        originalHeaderColor[widgetName] = $("#" + widgetName + "_header").css("background-color");
                                        originalBorderColor[widgetName] = $("#" + widgetName).css("border-color");

                                        for (var key in widgetTargetList[widgetName]) {
                                            if (widgetTargetList[widgetName][key] === "others") {
                                                $("#" + widgetName + "_header").css("background", "#ff6666");
                                                $("#" + widgetName).css("border-color", "#ff6666");
                                            }
                                        }
                                    }//Fine del for

                                    if (allOthersOnMap === false) {
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "#FFFFFF");
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("show");
                                    }
                                    else {
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "#FFFFFF");
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("hide");
                                    }
                                },
                                function () {
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarm.png");
                                    $("#<?= $_REQUEST['name_w'] ?>_othersButton img").css("cursor", "auto");

                                    for (var widgetName in widgetTargetList) {
                                        for (var key in widgetTargetList[widgetName]) {
                                            if (widgetTargetList[widgetName][key] === "others") {
                                                $("#" + widgetName + "_header").css("background", originalHeaderColor[widgetName]);
                                                $("#" + widgetName).css("border-color", originalBorderColor[widgetName]);
                                            }
                                        }
                                    }

                                    if (allOthersOnMap === false) {
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarm.png");
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "#000000");
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("");
                                    }
                                    else {
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton img").attr("src", "../img/alarmIcons/alarmWhite.png");
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").css("color", "#FFFFFF");
                                        $("#<?= $_REQUEST['name_w'] ?>_othersButton div.trafficEventsButtonIndicator").html("map");
                                    }
                                });

                            $("#<?= $_REQUEST['name_w'] ?>_othersButton").click(function () {
                                $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").css("color", "black");
                                $("#<?= $_REQUEST['name_w'] ?>_floodButton div.trafficEventsButtonIndicator").html("");
                                $("#<?= $_REQUEST['name_w'] ?>_floodButton img").attr("src", "../img/alarmIcons/flood.png");
                                allFloodsOnMap = false;

                                if (allOthersOnMap === false) {
                                    removeAllEventsFromMap("others");
                                    removeAllEventsFromMap("flood");

                                    addAllEventsToMap("others");
                                    allOthersOnMap = true;
                                }
                                else {
                                    removeAllEventsFromMap("others");
                                    allOthersOnMap = false;
                                }
                            });


                            scroller = setInterval(stepDownInterval, speed);
                            var timeToClearScroll = (timeToReload - 0.5) * 1000;

                            setTimeout(function () {
                                clearInterval(scroller);
                                $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").off();

                                $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(0).find("div.trafficEventsButtonIndicator").html("");
                                $("#<?= $_REQUEST['name_w'] ?>_buttonsContainer div.trafficEventsButtonContainer").eq(1).find("div.trafficEventsButtonIndicator").html("");

                                //Ripristino delle homepage native per gli widget targets al reload, se pilotati per ultimi da questo widget
                                for (var widgetName in widgetTargetList) {
                                    if (($("#" + widgetName + "_driverWidgetType").val() === 'alarms') && (eventsOnMaps[widgetName].eventsNumber > 0)) {
                                        loadDefaultMap(widgetName);
                                    }
                                    else {
                                        //console.log("Attualmente non pilotato da alarms");
                                    }
                                }

                            }, timeToClearScroll);


                            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").mouseenter(function () {
                                clearInterval(scroller);
                            });

                            $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").mouseleave(function () {
                                scroller = setInterval(stepDownInterval, speed);
                            });
                        }
                    },
                    error: function (data) {
                        console.log("Ko");
                        console.log(JSON.stringify(data));

                        showWidgetContent(widgetName);
                        $('#<?= $_REQUEST['name_w'] ?>_buttonsContainer').hide();
                        $("#<?= $_REQUEST['name_w'] ?>_rollerContainer").hide();
                        $("#<?= $_REQUEST['name_w'] ?>_noDataAlert").show();
                    }
                });

                $("#<?= $_REQUEST['name_w'] ?>").on('customResizeEvent', function (event) {
                    resizeWidget();
                });

                $("#<?= $_REQUEST['name_w'] ?>").off('updateFrequency');
                $("#<?= $_REQUEST['name_w'] ?>").on('updateFrequency', function (event) {
                    clearInterval(countdownRef);
                    timeToReload = event.newTimeToReload;
                    countdownRef = startCountdown(widgetName, timeToReload, <?= $_REQUEST['name_w'] ?>, metricNameFromDriver, widgetTitleFromDriver, widgetHeaderColorFromDriver, widgetHeaderFontColorFromDriver, fromGisExternalContent, fromGisExternalContentServiceUri, fromGisExternalContentField, fromGisExternalContentRange, /*randomSingleGeoJsonIndex,*/ fromGisMarker, fromGisMapRef);
                });

                countdownRef = startCountdown(widgetName, timeToReload, <?= $_REQUEST['name_w'] ?>, metricNameFromDriver, widgetTitleFromDriver, widgetHeaderColorFromDriver, widgetHeaderFontColorFromDriver, fromGisExternalContent, fromGisExternalContentServiceUri, fromGisExternalContentField, fromGisExternalContentRange, /*randomSingleGeoJsonIndex,*/ fromGisMarker, fromGisMapRef);


            },
            error: function (errorData) {

            }
        });
    });//Fine document ready
</script>

<div class="widget" id="<?= $_REQUEST['name_w'] ?>_div">
    <div class='ui-widget-content'>
        <?php include '../widgets/widgetHeader.php'; ?>
        <?php include '../widgets/widgetCtxMenu.php'; ?>

        <div id="<?= $_REQUEST['name_w'] ?>_loading" class="loadingDiv">
            <div class="loadingTextDiv">
                <p>Loading data, please wait</p>
            </div>
            <div class="loadingIconDiv">
                <i class='fa fa-spinner fa-spin'></i>
            </div>
        </div>

        <div id="<?= $_REQUEST['name_w'] ?>_content" class="content">
            <?php include '../widgets/commonModules/widgetDimControls.php'; ?>
            <div id="<?= $_REQUEST['name_w'] ?>_mainContainer" class="chartContainer">
                <div id="<?= $_REQUEST['name_w'] ?>_noDataAlert" class="noDataAlert">
                    <div id="<?= $_REQUEST['name_w'] ?>_noDataAlertText" class="noDataAlertText">
                        No data available
                    </div>
                    <div id="<?= $_REQUEST['name_w'] ?>_noDataAlertIcon" class="noDataAlertIcon">
                        <i class="fa fa-times"></i>
                    </div>
                </div>
                <div id="<?= $_REQUEST['name_w'] ?>_buttonsContainer"
                     class="trafficEventsButtonsContainer centerWithFlex">
                    <div class="trafficEventsButtonContainer">
                        <div class="trafficEventsButtonIcon centerWithFlex">
                            <img src="../img/trafficIcons/severity.png"/>
                        </div>
                        <div class="trafficEventsButtonIndicator centerWithFlex"></div>
                    </div>
                    <div class="trafficEventsButtonContainer">
                        <div class="trafficEventsButtonIcon centerWithFlex">
                            <img src="../img/trafficIcons/time.png"/>
                        </div>
                        <div class="trafficEventsButtonIndicator centerWithFlex"></div>
                    </div>
                    <div id="<?= $_REQUEST['name_w'] ?>_floodButton" class="trafficEventsButtonContainer">
                        <div class="trafficEventsButtonIcon centerWithFlex">
                            <img src="../img/alarmIcons/flood.png"/>
                        </div>
                        <div class="trafficEventsButtonIndicator centerWithFlex"></div>
                    </div>
                    <div id="<?= $_REQUEST['name_w'] ?>_othersButton" class="trafficEventsButtonContainer">
                        <div class="trafficEventsButtonIcon centerWithFlex">
                            <img src="../img/alarmIcons/alarm.png"/>
                        </div>
                        <div class="trafficEventsButtonIndicator centerWithFlex"></div>
                    </div>
                </div>
                <div id="<?= $_REQUEST['name_w'] ?>_rollerContainer" class="trafficEventsRollerContainer"></div>
            </div>
        </div>
    </div>
</div>
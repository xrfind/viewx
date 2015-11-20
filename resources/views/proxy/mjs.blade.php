$(document).ready(function() {

    $('#proxy_error_{{ $from_secord }}_{{ $to_secord }}').remove(); // this is my <canvas> element
    $('#canvas_container').prepend('<canvas id="proxy_error_{{ $from_secord }}_{{ $to_secord }}" width="1140" height="400"></canvas>');
    $('#proxy_monitor_{{ $from_secord }}_{{ $to_secord }}').remove(); // this is my <canvas> element
    $('#canvas_container').prepend('<canvas id="proxy_monitor_{{ $from_secord }}_{{ $to_secord }}" width="1140" height="400"></canvas>');
    $('#proxy_title_{{ $from_secord }}_{{ $to_secord }}').remove(); // this is my <canvas> element
    $('#canvas_container').prepend('<div id="proxy_title_{{ $from_secord }}_{{ $to_secord }}"></div>');

    document.getElementById("proxy_title_{{ $from_secord }}_{{ $to_secord }}").innerHTML = "<h3>From {{ (int)($from_secord/(24*3600)) }}D{{ (int)($from_secord%(24*3600)/3600) }}H to {{ (int)($to_secord/(24*3600)) }}D{{ (int)($to_secord%(24*3600)/3600) }}H</h3>";

    var ctx = document.getElementById("proxy_monitor_{{ $from_secord }}_{{ $to_secord }}").getContext("2d");
    new Chart(ctx).Line({!! $pm !!}, {scaleLabel: function(object){return " " + object.value; }, pointHitDetectionRadius : 2});
    var ctx = document.getElementById("proxy_error_{{ $from_secord }}_{{ $to_secord }}").getContext("2d");
    new Chart(ctx).Bar({!! $pe !!}, {scaleLabel: function(object){return " " + object.value; }});

    document.getElementById("msg").innerHTML = "";
});

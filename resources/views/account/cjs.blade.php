$(document).ready(function() {

    $('#circle_{{ $from_secord }}_{{ $to_secord }}').remove(); // this is my <canvas> element
    $('#canvas_container').prepend('<canvas id="circle_{{ $from_secord }}_{{ $to_secord }}" width="1140" height="400"></canvas>');
    $('#title_{{ $from_secord }}_{{ $to_secord }}').remove(); // this is my <canvas> element
    $('#canvas_container').prepend('<div id="title_{{ $from_secord }}_{{ $to_secord }}"></div>');

    document.getElementById("title_{{ $from_secord }}_{{ $to_secord }}").innerHTML = "<h3>From {{ (int)($from_secord/(24*3600)) }}D{{ (int)($from_secord%(24*3600)/3600) }}H to {{ (int)($to_secord/(24*3600)) }}D{{ (int)($to_secord%(24*3600)/3600) }}H</h3>";

    var ctx = document.getElementById("circle_{{ $from_secord }}_{{ $to_secord }}").getContext("2d");
    new Chart(ctx).Line({!! $res !!}, {scaleLabel: function(object){return " " + object.value; }, pointHitDetectionRadius : 2});

    document.getElementById("msg").innerHTML = "";
});

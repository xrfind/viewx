@extends('index')


@section('title', 'Proxy Status Monitor')
@endsection


@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.js"></script>
<script type="text/javascript">
function loadchart() {
    var day = document.getElementById('day').value;
    var hour = document.getElementById('hour').value;
    if (day == '') day = 0;
    else day = parseInt(day);
    if (hour == '') hour = 0;
    else hour = parseInt(hour);
    if (hour >= 0 && day >= 0 && hour + day > 0) {
        document.getElementById("msg").innerHTML = "working...";
        var time = hour * 60 + day * 60 * 24;
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (xhttp.readyState == 4 && xhttp.status == 200) {
                eval(xhttp.responseText)
            }
        };
        var url = "{{ action('ProxyController@mstep', ['']) }}";
        xhttp.open("GET", url.concat('/').concat(time), true);
        xhttp.send();
    }
    else {
        document.getElementById("msg").innerHTML = "<strong>Error</strong>";
    }
}
$(document).ready(function() {
    document.getElementById("proxy_title_{{ $step_secord }}").innerHTML = "<h3>Unit: {{ (int)($step_secord/(24*3600)) }}D{{ (int)($step_secord%(24*3600)/3600) }}H{{ (int)($step_secord%3600)/60 }}M</h3>";
    var ctx = document.getElementById("proxy_monitor_{{ $step_secord }}").getContext("2d");
    new Chart(ctx).Line({!! $pm !!}, {scaleLabel: function(object){return " " + object.value; }, pointHitDetectionRadius : 2});
    var ctx = document.getElementById("proxy_error_{{ $step_secord }}").getContext("2d");
    new Chart(ctx).Bar({!! $pe !!}, {scaleLabel: function(object){return " " + object.value; }});
});
</script>
@endsection


@section('content')

<form class="form-inline">
  <div class="form-group">
    <label for="form">How long you want to view? : </label>
    <div class="input-group">
      <input type="text" class="form-control" id="day" placeholder="0">
      <div class="input-group-addon"> days </div>
    </div>
    <div class="input-group">
      <input type="text" class="form-control" id="hour" placeholder="0">
      <div class="input-group-addon"> hours </div>
    </div>
  </div>
  <button id='viewbutton' type="button" class="btn btn-primary" onclick="loadchart()">VIEW</button>
  <span class="" id="msg"> </span>
</form>

<div id="canvas_container">
    <div id="proxy_title_{{ $step_secord }}"></div>
    <canvas id="proxy_monitor_{{ $step_secord }}" width="1140" height="400"></canvas>
    <canvas id="proxy_error_{{ $step_secord }}" width="1140" height="400"></canvas>
</div>

@endsection

@extends('index')


@section('title', 'Login Health')
@endsection


@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.js"></script>
@include('loadchartfunction')
<script> @include('login.js') </script>
@endsection


@section('content')
@include('fromtoform')
<h2 id='period'> </h2>
<strong id='current'></strong>
<div id="canvas_container">
</div>
@endsection
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Booking</title>
</head>
<body>

  <p>{{$name}} would like to book a {{$resource->resourceName}} at {{$space->name}}.</p>

  <strong>TIME</strong><br/>
  {{$start}}<br/>
  TO<br/>
  {{$end}}
  <br/><br/>

  To Approve the Booking, <a href={{$approve}}>CLICK HERE</a>
  <br/>
  <br/>
  To Deny the Booking, <a href={{$deny}}>CLICK HERE</a>
  <br/>
  <br/>
  Contact: {{$email}}
</body>
</html>

<!DOCTYPE html>
<html>

<head>
    <title>Batch Notification</title>
</head>

<body>
    <h1>New Batch Ready for Processing</h1>
    <p>Provider: {{ $batch->provider_name }}</p>
    <p>Date: {{ $batch->date->format('Y-m-d') }}</p>
    <p>Total Cost: ${{ number_format($batch->total_cost, 2) }}</p>
    <p>Number of Claims: {{ $batch->claims->count() }}</p>
    <p>Insurer: {{ $batch->insurer->name }}</p>
</body>

</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shipment Status Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Shipment Status Update</h1>
        
        <p>Your order #{{ $orderId }} has a new status update.</p>
        
        <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Current Status:</strong> <span style="color: #4CAF50;">{{ ucfirst($status) }}</span></p>
            <p style="margin: 5px 0;"><strong>Tracking Number:</strong> {{ $trackingNumber }}</p>
            <p style="margin: 5px 0;"><strong>Shipping Provider:</strong> {{ $providerName }}</p>
        </div>
        
        @if($statusHistory->isNotEmpty())
        <h3>Status Timeline</h3>
        <div style="border-left: 2px solid #4CAF50; padding-left: 15px;">
            @foreach($statusHistory as $history)
            <div style="margin-bottom: 15px;">
                <p style="margin: 0; font-weight: bold;">{{ ucfirst($history->status) }}</p>
                <p style="margin: 5px 0; font-size: 14px; color: #666;">
                    {{ $history->description }}
                    @if($history->location)
                    - {{ $history->location }}
                    @endif
                </p>
                <p style="margin: 0; font-size: 12px; color: #999;">{{ $history->occurred_at->format('M d, Y h:i A') }}</p>
            </div>
            @endforeach
        </div>
        @endif
        
        @if($trackingUrl)
        <p>
            <a href="{{ $trackingUrl }}" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Track Your Shipment
            </a>
        </p>
        @endif
        
        <p>Thank you for your patience!</p>
    </div>
</body>
</html>

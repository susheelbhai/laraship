<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Order Has Been Shipped</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #4CAF50;">Your Order Has Been Shipped!</h1>
        
        <p>Great news! Your order #{{ $orderId }} has been shipped and is on its way to you.</p>
        
        <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Tracking Number:</strong> {{ $trackingNumber }}</p>
            <p style="margin: 5px 0;"><strong>Shipping Provider:</strong> {{ $providerName }}</p>
        </div>
        
        @if($trackingUrl)
        <p>
            <a href="{{ $trackingUrl }}" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                Track Your Shipment
            </a>
        </p>
        @endif
        
        <p>Thank you for your order!</p>
    </div>
</body>
</html>

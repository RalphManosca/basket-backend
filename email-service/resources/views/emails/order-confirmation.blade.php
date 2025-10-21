<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .order-details {
            margin: 20px 0;
        }
        .item {
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>
        <div class="content">
            <p>Thank you for your order!</p>

            <div class="order-details">
                <h2>Order #{{ $orderDetails['id'] }}</h2>

                <h3>Order Items:</h3>
                @foreach($orderDetails['items'] as $item)
                    <div class="item">
                        <strong>{{ $item['product_name'] }}</strong><br>
                        Quantity: {{ $item['quantity'] }}<br>
                        Price: ${{ number_format($item['unit_price'], 2) }}
                    </div>
                @endforeach

                <div class="total">
                    Total Amount: ${{ number_format($orderDetails['total_amount'], 2) }}
                </div>
            </div>

            <p>Your order has been confirmed and will be processed shortly.</p>
        </div>
    </div>
</body>
</html>

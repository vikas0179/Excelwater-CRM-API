<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order</title>
</head>

<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #F4F4F4;">
    <section style="padding: 16px; background-color: #FFFFFF; color: #000000;">
        <div style="border: 1px solid #1F2937; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="width:50%; text-align: right;">
                    <img src="https://crm.excelwater.ca/assets/logo.1b249126.webp" alt="Logo"
                        style="width: 144px; margin-bottom: 8px; margin-left: auto;">
                </div>
            </div>
            <div style="text-align:left; font-size: 14px; color: #6B7280;">
                <p style="margin: 2px">
                    <strong>Supplier Name: </strong>
                    <span>{{ isset($OrderList->supplier_name) ? $OrderList->supplier_name : '' }}</span>
                </p>
                <p style="margin: 2px">
                    <strong>Order ID </strong>
                    <span>{{ isset($OrderList->order_id) ? $OrderList->order_id : '' }}</span>
                </p>
                <p style="margin: 2px">
                    <strong>Order Number </strong>
                    <span>{{ isset($OrderList->order_number) ? $OrderList->order_number : '' }}</span>
                </p>
            </div>
            <div style="margin-top: 16px; overflow-x: auto; color: #6B7280;">
                <table style="width: 100%; font-size: 14px; border-collapse: collapse;" border="1">
                    <thead style="background-color: #F3F4F6;">
                        <tr style="background-color: #E0F2FE; color: #0EA5E9;">
                            <th style="padding: 8px; text-align: left;">Item</th>
                            <th style="padding: 8px; text-align: left;">Description</th>
                            <th style="padding: 8px; text-align: left;">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($OrderItemList))
                            @foreach ($OrderItemList as $order)
                                <tr>
                                    <td>{{ isset($order->item) ? $order->item : '' }}</td>
                                    <td>{{ isset($order->desc) ? $order->desc : '' }}</td>
                                    <td>{{ isset($order->qty) ? $order->qty : '' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</body>

</html>

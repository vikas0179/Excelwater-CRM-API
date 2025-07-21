<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
</head>

<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #F4F4F4;">
    <section style="padding: 16px; background-color: #FFFFFF; color: #000000;">
        <div style="border: 1px solid #1F2937; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="font-size: 14px; color: #6B7280; width:50%">
                    <span
                        style="font-weight: bold; font-size: 18px; margin-bottom: 4px; display: block; color: #6B7280;">Excel Water System</span>
                    <p style="margin: 2px 0;">31-145 Traders Blvd E</p>
                    <p style="margin: 2px 0;">Mississauga ON L4Z 3L3</p>
                    <p style="margin: 2px 0;">+1 888 622 3092</p>
                    <p style="margin: 2px 0;">info@excelwater.ca</p>
                    <p style="margin: 2px 0;">https://crm.excelwater.ca</p>
                    <span style="font-size: 24px; color: #0EA5E9; margin-top: 8px; display: block;">INVOICE</span>
                </div>
                <div style="width:50%; text-align: right;">
                    <img src="https://crm.excelwater.ca/assets/logo.1b249126.webp" alt="Logo"
                        style="width: 144px; margin-bottom: 8px; margin-left: auto;">
                </div>
            </div>
            <div style="display:flex; font-size: 14px; color: #6B7280;">
                <div style="width: 50%">
                    <p style="font-weight: 600; margin-bottom: 0px;">BILL TO</p>
                    <p style="margin-top: 0px;"><?= isset($InvoiceData->bill_to) ? $InvoiceData->bill_to : '' ?></p>
                </div>
                <div style="width: 50%">
                    <p style="font-weight: 600; margin-bottom: 0px;">SHIP TO</p>
                    <p style="margin-top: 0px;"><?= isset($InvoiceData->ship_to) ? $InvoiceData->ship_to : '' ?></p>
                </div>
            </div>
            <div style="text-align:right; font-size: 14px; color: #6B7280;">
                <p style="margin: 2px">
                    <strong>INVOICE # </strong>
                    <span><?= isset($InvoiceData->invoice_no) ? $InvoiceData->invoice_no : '' ?></span>
                </p>
                <p style="margin: 2px">
                    <strong>DATE </strong>
                    <span><?= isset($InvoiceData->invoice_date) ? date('d-m-Y', strtotime($InvoiceData->invoice_date)) : '' ?></span>
                </p>
                <p style="margin: 2px">
                    <strong>DUE DATE </strong>
                    <span>
                        <?= isset($InvoiceData->invoice_date) ? date('d-m-Y', strtotime($InvoiceData->invoice_date)) : '' ?></span>
                </p>
                <p style="margin: 2px"><strong>TERMS </strong><span>Due on receipt</span></p>
            </div>


            <div style="margin-top: 16px; overflow-x: auto; color: #6B7280;">
                <table style="width: 100%; font-size: 14px; border-collapse: collapse;" border="1">
                    <thead style="background-color: #F3F4F6;">
                        <tr style="background-color: #E0F2FE; color: #0EA5E9;">
                            <th style="padding: 8px; text-align: left;">Activity</th>
                            <th style="padding: 8px; text-align: right;">Qty</th>
                            <th style="padding: 8px; text-align: right;">Rate</th>
                            <th style="padding: 8px; text-align: right;">Tax</th>
                            <th style="padding: 8px; text-align: right;">Tax Amount</th>
                            <th style="padding: 8px; text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalAmount = 0; @endphp
                        @if (!empty($InvoiceItemsData) && count($InvoiceItemsData) > 0)
                            @foreach ($InvoiceItemsData as $val)
                                @php
                                    $product_code = isset($val->product_code) ? $val->product_code : null;
                                    $productCodeSet = '';
                                    if (!empty($product_code)) {
                                        $product_code = explode(',', $product_code);
                                        foreach ($product_code as $code) {
                                            $productCodeSet .= $code . '<br>';
                                        }
                                    }
                                    $qty = isset($val->qty) ? $val->qty : 0;
                                    $rate = isset($val->rate) ? $val->rate : 0;
                                    $Amount = $qty * $rate;
                                    $tax_amount = (13 / 100) * $Amount;
                                    $totalAmount += $qty * $rate;
                                @endphp
                                <tr>
                                    <td>
                                        <?= isset($val->item) ? $val->item : '' ?> <br>
                                        {!! $productCodeSet !!}
                                    </td>
                                    <td><?= $qty ?></td>
                                    <td><?= '$' . $rate ?></td>
                                    <td>HST ON</td>
                                    <td>{{ '$' . $tax_amount }}</td>
                                    <td><?= '$' . ($Amount + $tax_amount) ?></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div style="font-size: 14px; text-align: right; margin-top: 16px; color: #6B7280;">
                <p style="margin-bottom: 0px;"><strong>SUBTOTAL:</strong> <?= '$' . $totalAmount ?></p>
                <p style="margin-top: 0px; margin-bottom: 0px;"><strong>HST (ON) @ 13%:</strong>
                    @php $calculated = (13 / 100) * $totalAmount; @endphp
                    <?= '$' . $calculated ?>
                </p>
                <p style="font-weight: bold; font-size: 18px; margin-top: 0px;">
                    TOTAL: CAD <?= '$' . ($totalAmount + $calculated) ?>
                </p>
            </div>

        </div>
        <div style="border: 1px solid #1F2937; padding: 15px; display:flex; justify-content:space-between">
            <div style="display:flex; align-items: center;">
                <a href="<?= $InvoiceUrl ?>"
                    style="text-decoration: none; background-color: #2563EB; color: #FFFFFF; padding: 8px 16px; border-radius: 4px;">Invoice</a>
            </div>

        </div>

    </section>

</body>

</html>

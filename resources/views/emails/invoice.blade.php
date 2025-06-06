@include('emails.header-mail')

<style>
    table,
    th,
    td {
        border: 1px solid rgb(131, 114, 114);
        border-collapse: collapse;
        padding: 5px;
    }


    .invoice-table {
      margin: 0 auto;
      border-collapse: collapse;
      width: 60%;
    }
</style>

<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
    <p style="margin-bottom:9px; color:#181C32; font-size: 22px; font-weight:700">Hi there,</p>
    <p style="margin-bottom:2px; color:#7E8299"> <strong>Bill To:</strong>
        <?= isset($InvoiceData->bill_to) ? $InvoiceData->bill_to : '' ?></p>
    <p style="margin-bottom:2px; color:#7E8299"> <strong>Ship To:</strong>
        <?= isset($InvoiceData->ship_to) ? $InvoiceData->ship_to : '' ?></p>
    <p style="margin-bottom:2px; color:#7E8299"> <strong>Invoice:</strong>
        <?= isset($InvoiceData->invoice_no) ? $InvoiceData->invoice_no : '' ?></p>
    <p style="margin-bottom:2px; color:#7E8299"> <strong>Date:</strong>
        <?= isset($InvoiceData->invoice_date) ? date('d-m-Y', strtotime($InvoiceData->invoice_date)) : '' ?></p>
    <p style="margin-bottom:2px; color:#7E8299"> <strong>Due Date:</strong>
        <?= isset($InvoiceData->invoice_date) ? date('d-m-Y', strtotime($InvoiceData->invoice_date)) : '' ?></p>

    <p style="margin-bottom:9px; color:#5e6697; font-size: 22px; font-weight:700">Items</p>

    <div class="table-responsive">
        <table class="table .invoice-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Tax</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAmount = 0; @endphp
                @if (!empty($InvoiceItemsData) && count($InvoiceItemsData) > 0)
                    @foreach ($InvoiceItemsData as $val)
                        @php
                            $qty = isset($val->qty) ? $val->qty : 0;
                            $rate = isset($val->rate) ? $val->rate : 0;
                            $Amount = $qty * $rate;
                            $totalAmount += $qty * $rate;
                        @endphp
                        <tr>
                            <td><?= isset($val->item) ? $val->item : '' ?></td>
                            <td><?= isset($val->desc) ? $val->desc : '' ?></td>
                            <td><?= $qty ?></td>
                            <td><?= '$' . $rate ?></td>
                            <td>HST ON</td>
                            <td><?= '$' . $Amount ?></td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>


    <p style="margin-bottom:2px; color:#7E8299"> <strong>Sub Total:</strong>
        <?= '$' . $totalAmount ?>
    </p>

    <p style="margin-bottom:2px; color:#7E8299"> <strong>HST (ON) @ 13%:</strong>
        @php $calculated = (13 / 100) * $totalAmount; @endphp
        <?= '$' . $calculated ?>
    </p>

    <p style="margin-bottom:2px; color:#7E8299"> <strong>TOTAL: CAD:</strong>
        <?= '$' . ($totalAmount + $calculated) ?>
    </p>

</div>

<div style="font-size: 14px; font-weight: 500; margin-bottom: 20px; font-family:Arial,Helvetica,sans-serif;">
    <p style="margin-bottom:2px; color:#7E8299">Regards, <br><?= getenv('APP_NAME') ?> Team </p>
</div>

@include('emails.footer-mail')

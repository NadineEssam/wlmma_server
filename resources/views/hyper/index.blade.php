<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hyperpay Form</title>
    <script src="https://eu-prod.oppwa.com/v1/paymentWidgets.js?checkoutId={{ $CheckoutID }}" integrity="{integrity}"
        crossorigin="anonymous"></script>
</head>

<body>
    <h2>Hyperpay Form</h2>

    <form
        action="https://hyperpay.docs.oppwa.com/integrations/widget#getStatus?id={{ $CheckoutID }}&resourcePath=%2Fv1%2Fcheckouts%2FA097A23BF8384FC027DB09F7FB9B0A4D.uat01-vm-tx03%2Fpayment?id=300BF82C341CA88290F500E7CEC76B20.uat01-vm-tx02&resourcePath=%2Fv1%2Fcheckouts%2F300BF82C341CA88290F500E7CEC76B20.uat01-vm-tx02%2Fpayment"
        class="paymentWidgets" data-brands="AMEX MADA MASTER VISA"></form>
</body>

</html>

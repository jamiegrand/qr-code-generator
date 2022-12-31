jQuery(document).ready(function ($)
{
    // Submit the form and generate the QR code when the submit button is clicked
    $('#qr-code-form').submit(function (e)
    {
        e.preventDefault();

        // Get the website address from the form input
        var websiteAddress = $('#website-address').val();

        // Send the AJAX request to the server to generate the QR code
        $.ajax({
            url: qr_code_form_ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'qr_code_form_submit',
                website_address: websiteAddress,
                qr_code_form_nonce_field: $('#qr_code_form_nonce_field').val()
            },
            success: function (response)
            {
                //console.log('Response data:', response.data);
                // Display the QR code image URL and file location when the request is successful
                var qrCodeImageUrl = response.data.qr_code_image;
                $('#qr-code-result').html('<img src="' + qrCodeImageUrl + '" />');
            },
            error: function (error)
            {
                console.log('Error message:', error);
                // Display the error message when the request fails
                $('#qr-code-result').html(error);
            }
        });
    });
});
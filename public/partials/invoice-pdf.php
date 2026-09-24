<?php

if (! defined('ABSPATH')) {
    exit;
}


use PublishPress\Dompdf\Dompdf;

$footer = get_option('_ppcart_invoice_footer');
$font = 'Arial, Helvetica, sans-serif;';
$order_id = $order->id ?? $order->ID;
if ($order) {
    $file = ppcart_get_template_path('pdf-invoice/invoice');
    include_once $file;

    try {
        // Determine if it's an invoice or receipt
        $request_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $doc_type = (is_string($request_type) && 'receipt' === $request_type) ? 'receipt' : 'invoice';

        // Set the appropriate file prefix
        $file_prefix = $doc_type === 'receipt' ? esc_html__("receipt", 'publishpress-cart') : esc_html__("invoice", 'publishpress-cart');

        // Create the filename
        $fileName = $file_prefix . "-" . get_post_timestamp($order_id) . ".pdf";
        $pdfPath = $fileName;
        $dompdf = new Dompdf();
        $content = "<html>
                <body>
                    <style>
                    @page { margin-top: 0px;padding-top:0px; }
                    body { margin-top: 0px;padding-top:0px; }
                    footer {
                        position: fixed;
                        bottom: -35px;
                        left: 0px;
                        right: 0px;
                        height: 50px;
                        font-family: " . $font . ";
                        color: rgb(165,179,183);
                        font-size: 13px;
                        text-align: center;
                        line-height: 35px;
                    }
                    </style>";

        if ($footer) {
            $content .= '<footer>' . ppcart_kses_email_html($footer) . '</footer>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by ppcart_kses_email_html().
        }

        $content .= $html . "
                </body>
            </html>";
        $dompdf->loadHtml($content);
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('8.5x11');
        // Render the HTML as PDF
        $dompdf->render();

        if ($stream) {
            // Output the generated PDF to Browser
            $dompdf->stream($fileName, ["Attachment" => $download]);
        } else {
            $upload_dir   = wp_upload_dir();
            $invoicePath = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;
            if (!is_dir($invoicePath)) {
                wp_mkdir_p($invoicePath);
            }
            $invoicePath .= $fileName;
            $output = $dompdf->output();
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents -- Generated invoice is written to wp_upload_dir() for download/caching.
            file_put_contents($invoicePath, $output);
            return $invoicePath;
        }
    } catch (EXCEPTION $ex) {
        echo esc_html($ex->getMessage());
    }
} else {
    esc_html_e("Unauthorized", "publishpress-cart");
}

die();

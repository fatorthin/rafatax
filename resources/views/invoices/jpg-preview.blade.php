<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloading JPG...</title>
    <!-- Include html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: sans-serif;
        }

        #invoice-wrapper {
            background: white;
            padding: 0;
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            width: 210mm;
            /* A4 width */
        }

        /* Ensure the included view content doesn't break layout */
        #invoice-wrapper>* {
            margin: 0;
        }

        .controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .controls button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
        }

        .loading {
            color: white;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="controls">
        <button onclick="downloadJpg()">Download JPG Again</button>
    </div>

    <div id="loading" class="loading">Generating JPG...</div>

    <div id="invoice-wrapper">
        <!-- Render the PDF view content as HTML -->
        @include($originalView, [
            'headerImage' => $headerImage,
            'signatureImage' => $signatureImage,
            'invoice' => $invoice,
            'costLists' => $costLists,
        ])
    </div>

    <script>
        function downloadJpg() {
            var element = document.getElementById("invoice-wrapper");
            var loading = document.getElementById("loading");
            var btn = document.querySelector(".controls button");

            loading.style.display = 'block';
            btn.disabled = true;

            // Wait a moment for layout to settle (e.g. images)
            setTimeout(function() {
                html2canvas(element, {
                    scale: 2, // Higher scale for better resolution
                    useCORS: true,
                    logging: false
                }).then(function(canvas) {
                    var link = document.createElement("a");
                    document.body.appendChild(link);
                    // Use invoice number for filename
                    var filename =
                        "invoice-{{ preg_replace('/[^a-zA-Z0-9]/', '-', $invoice->invoice_number ?? $invoice->id) }}.jpg";
                    link.download = filename;
                    link.href = canvas.toDataURL("image/jpeg", 0.9);
                    link.target = '_blank';
                    link.click();
                    document.body.removeChild(link);

                    loading.style.display = 'none';
                    btn.disabled = false;

                    // Optional: Close window after auto-download if desired, but user might want to re-download.
                    // window.close();
                });
            }, 1000);
        }

        // Auto download on load
        window.onload = function() {
            downloadJpg();
        };
    </script>
</body>

</html>

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ asset('fonts/bookman-old-style/BOOKOS.TTF') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ asset('fonts/bookman-old-style/BOOKOSB.TTF') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ asset('fonts/bookman-old-style/BOOKOSI.TTF') }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ asset('fonts/bookman-old-style/BOOKOSBI.TTF') }}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }

        .kop-kkp-wrapper {
            max-width: 850px;
            margin: 0 auto 12px auto;
            padding: 12px 20px 10px 20px;
            border-bottom: 1.5px solid #9dc3e6;
            text-align: left;
            background: transparent;
        }

        .kop-kkp-wrapper table.kop-kkp-table,
        .page-table>thead .header-img-cell table.kop-kkp-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
        }

        .kop-kkp-wrapper table.kop-kkp-table tr,
        .page-table>thead .header-img-cell table.kop-kkp-table tr {
            background-color: transparent !important;
        }

        .kop-kkp-wrapper table.kop-kkp-table td,
        .page-table>thead .header-img-cell table.kop-kkp-table td {
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            vertical-align: middle !important;
            background: transparent !important;
        }

        .kop-kkp-wrapper td.kop-kkp-logo-cell,
        .page-table>thead .header-img-cell td.kop-kkp-logo-cell {
            width: 120px !important;
            padding-right: 18px !important;
            text-align: left !important;
        }

        .kop-kkp-wrapper img.kop-kkp-logo,
        .header-img-cell img.kop-kkp-logo,
        .page-table>thead .header-img-cell img.kop-kkp-logo {
            width: 112px !important;
            max-width: 112px !important;
            height: auto !important;
            display: block !important;
            margin: 0 !important;
        }

        .kop-kkp-wrapper td.kop-kkp-text-cell,
        .page-table>thead .header-img-cell td.kop-kkp-text-cell {
            text-align: left !important;
            font-family: 'Bookman Old Style', Georgia, serif !important;
            color: #111111 !important;
            line-height: 1.15 !important;
        }

        .kop-kkp-wrapper .kop-kkp-name {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 34px;
            font-weight: normal;
            color: #111111;
            line-height: 1.05;
            margin: 0 0 2px 0;
        }

        .kop-kkp-wrapper .kop-kkp-title-wrap {
            margin: 0 0 4px 0;
            line-height: 1.1;
        }

        .kop-kkp-wrapper .kop-kkp-title {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 22px;
            font-weight: bold;
            color: #111111;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.3px;
        }

        .kop-kkp-wrapper .kop-kkp-kip {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 17px;
            font-weight: bold;
            color: #111111;
            line-height: 1.15;
            margin: 0 0 5px 0;
        }

        .kop-kkp-wrapper .kop-kkp-contact {
            font-family: 'Bookman Old Style', Georgia, serif;
            font-size: 12px;
            font-weight: normal;
            color: #555555;
            line-height: 1.2;
            margin: 0;
            white-space: nowrap;
        }

        @media print {
            .kop-kkp-wrapper {
                max-width: 100%;
                margin: 0 0 16px 0;
                padding: 0 0 10px 0;
            }
        }

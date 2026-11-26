        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ str_replace('\\', '/', public_path('fonts/bookman-old-style/BOOKOS.TTF')) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ str_replace('\\', '/', public_path('fonts/bookman-old-style/BOOKOSB.TTF')) }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ str_replace('\\', '/', public_path('fonts/bookman-old-style/BOOKOSI.TTF')) }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        @font-face {
            font-family: 'Bookman Old Style';
            src: url('{{ str_replace('\\', '/', public_path('fonts/bookman-old-style/BOOKOSBI.TTF')) }}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }

        /* KKP Header Alignment */
        header.header-kkp {
            top: -178px;
            left: 40px;
            right: 40px;
            height: auto;
            text-align: left;
            padding-bottom: 10px;
            border-bottom: 1.5px solid #9dc3e6;
        }

        header.header-kkp table.kop-kkp-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
            border: none;
        }

        header.header-kkp table.kop-kkp-table tr {
            background-color: transparent !important;
        }

        header.header-kkp table.kop-kkp-table td {
            border: none;
            padding: 0;
            margin: 0;
            vertical-align: middle;
        }

        header.header-kkp td.kop-kkp-logo-cell {
            width: 118px;
            padding-right: 18px;
            text-align: left;
        }

        header.header-kkp img.kop-kkp-logo {
            width: 112px;
            height: auto;
            display: block;
        }

        header.header-kkp td.kop-kkp-text-cell {
            text-align: left;
            font-family: 'Bookman Old Style', serif;
            color: #111111;
            line-height: 1.15;
        }

        header.header-kkp .kop-kkp-name {
            font-family: 'Bookman Old Style', serif;
            font-size: 27pt;
            font-weight: normal;
            color: #111111;
            line-height: 1.05;
            margin: 0 0 2px 0;
        }

        header.header-kkp .kop-kkp-title-wrap {
            margin: 0 0 3px 0;
            line-height: 1.1;
        }

        header.header-kkp .kop-kkp-title {
            font-family: 'Bookman Old Style', serif;
            font-size: 18pt;
            font-weight: bold;
            color: #111111;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.3px;
        }

        header.header-kkp .kop-kkp-kip {
            font-family: 'Bookman Old Style', serif;
            font-size: 13.5pt;
            font-weight: bold;
            color: #111111;
            line-height: 1.15;
            margin: 0 0 4px 0;
        }

        header.header-kkp .kop-kkp-contact {
            font-family: 'Bookman Old Style', serif;
            font-size: 9pt;
            font-weight: normal;
            color: #555555;
            line-height: 1.2;
            margin: 0;
            white-space: nowrap;
        }

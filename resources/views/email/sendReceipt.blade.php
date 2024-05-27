<html>

<head>
    <style>
        body {
            //background-color: #f5f5f5;
            font-family: Times New Roman, Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0 auto;
            padding: 0;
        }


        .logo {
            padding: 5px;
            padding-left: 20px;
            width: 80px;
            height: 70px;
        }


        table tr {
            vertical-align: top;
        }

        table tr td {
            vertical-align: top;
        }

        .topright {
            font-size: 14px;
            position: absolute;
            color: #3B3B3A;
            top: 10px;
            right: 0;
        }

        .title {
            padding-top: 10px;
            padding-botom: 10px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .titleSmall {
            font-size: 12.5px;
            color: #3B3B3A;
            text-align: center;
            padding-top: 20px;
            padding-bottom: 10px;
        }

        .subsection {
            font-size: 13px;
            background-color: #CBEBC6;
            font-weight: bold;
            padding: 5px 10px 5px 10px;
        }

        .subsectionSmall {
            font-size: 10px;
            font-weight: normal;
            padding-left: 18px;
        }

        .content {
            padding: 10px;
        }

        .question {
            font-size: 13px;
            padding-top: 5px;
            padding-left: 5px;
            padding-right: 5px;
            margin-bottom: 5px;
            vertical-align: top;
        }

        .item {
            font-size: 20px;
            color: #000000;
            padding-top: 5px;
            padding-left: 20px;
            padding-right: 5px;
            padding-bottom: 5px;
            margin-bottom: 5px;
            vertical-align: top;
        }

        .note {
            padding-left: 15px;
            padding-right: 5px;
            vertical-align: top;
            font-size: 9px;
        }

        .signNote {
            padding-left: 5px;
            padding-right: 5px;
            vertical-align: top;
            text-align: center;
            font-size: 9px;
        }

        .textbox {
            font-size: 13px;
            color: #000000;
            margin: 0 20px 0 15px;
            padding: 5px;
            height: 15px;
            border: 1px solid black;
        }

        .checkbox {
            font-size: 13px;
            color: #000000;
            margin: 0 auto;
            padding: 2px;
            height: 12px;
            width: 12px;
            border: 1px solid black;
            vertical-align: middle;
        }

        .checkboxText {
            font-size: 11px;
            padding: 0px;
            //margin-bottom: 5px;
            vertical-align: middle;
        }

        .underline {
            font-size: 13px;
            color: #000000;
            margin: 0 5px 5px 5px;
            height: 20px;
            border-bottom: 1px dotted black;
        }

        .thumprint {
            font-size: 13px;
            color: #000000;
            margin: 0 auto;
            padding: 2px;
            height: 80px;
            width: 80px;
            border: 1px solid black;
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <table border=0 style="width:100%">
        <tr>
            <td>
                <table style="width:100%; padding:10px 20px 10px 20px">
                    <tr>
                        <td style="30%">
                            <center>
                                <img class="logo" src="{{ asset('assets/images/logo/kdn.png') }}"></img>
                            </center>
                        </td>
                        <td style="40%">
                            <center>
                                <div>GOVERNMENT OF MALAYSIA</div>
                                <div>IMMIGRATION DEPARTMENT OF MALAYSIA</div>
                                <div>Official Receipt</div>
                                <div>ORIGINAL</div>
                            </center>
                        </td>
                        <td style="30%">
                            <div>(IM. 199-Pin. 1/10)</div>
                            <div>Receipt No : {{ $data['RCNo'] }}</div>
                            <div>Date : {{ date('d/m/Y', strtotime($data['RCCD'])) }}</div>
                            <div>Time : {{ date('H:i', strtotime($data['RCCD'])) }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table style="width:100%; padding:10px 20px 10px 20px">
                    <tr>
                        <td>Received From : XXX</td>
                    </tr>
                    <tr>
                        <td>Identification No/Company Reg No.: XXX</td>
                    </tr>
                    <tr>
                        <td>Address :</td>
                    </tr>
                    <tr>
                        <td>Reference No :</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table style="width:100%; padding:10px 20px 10px 20px">
                    <thead style="background-color:#C0C0C0; ">
                        <tr>
                            <td style="wdith:10%; text-align:center">No</td>
                            <td style="wdith:20%; text-align:center">Application No</td>
                            <td style="wdith:40%; text-align:center">Name</td>
                            <td style="wdith:30%; text-align:center">Total Amount</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="wdith:10%; text-align:center">1</td>
                            <td style="wdith:20%; text-align:center">{{ $data['SANo'] }}</td>
                            <td style="wdith:40%; text-align:center">{{ $data['CSName'] }}</td>
                            <td style="wdith:30%; text-align:right; padding-right:20px">
                                {{ number_format($data['RCTotalFee'], 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td style="wdith:10%; text-align:center"></td>
                            <td style="wdith:20%; text-align:center"></td>
                            <td style="wdith:40%; text-align:right">Total</td>
                            <td style="wdith:30%; text-align:right; padding-right:20px">
                                {{ number_format($data['RCTotalFee'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table style="width:100%; padding:10px 20px 10px 20px">
                    <tr>
                        <td>SGD :</td>
                    </tr>
                    <tr>
                        <td>Remark :</td>
                    </tr>
                    <tr>
                        <td>Department / PTJ : 230100 - PEJ. IMM SINGAPURA</td>
                    </tr>
                    <tr>
                        <td>Computer generated. No signature is required.</td>
                    </tr>
                    <tr>
                        <td>Treasure Approval No.: BPKS(8.15)248-11(43)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>

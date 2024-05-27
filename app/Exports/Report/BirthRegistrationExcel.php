<?php
namespace App\Exports\Report;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class BirthRegistrationExcel implements FromView, WithColumnFormatting, WithEvents {

    private $result;
    private $request;
    private $reportId;
    private $userId;

    // Calculate Total
    protected $total;

    public function __construct($result, $request, $reportId = null, $userId = null){
        $this->result = $result;
        $this->request = $request;
        $this->reportId = $reportId;
        $this->userId = $userId;
    }

    public function columnFormats(): array
    {
        return [
            "A" => NumberFormat::FORMAT_TEXT,
            "B" => NumberFormat::FORMAT_TEXT,
            "C" => NumberFormat::FORMAT_TEXT,
            "D" => NumberFormat::FORMAT_TEXT,
            "E" => NumberFormat::FORMAT_TEXT,
            "F" => NumberFormat::FORMAT_TEXT,
            "G" => NumberFormat::FORMAT_TEXT,
            "H" => NumberFormat::FORMAT_TEXT,
            "I" => NumberFormat::FORMAT_TEXT,
            "J" => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                // Specific report settings
                // Report name
                $reportNameSetting = "BIRTH REGISTRATION";

                // Start at 7th row - Options - (7 = 1 row / 8 = 2  row / etc..)
                $headerMaxRowSpanSetting = 4;
                $scaleSetting = 55;

                // Options - (ORIENTATION_LANDSCAPE / ORIENTATION_PORTRAIT)
                $orientationSetting = PageSetup::ORIENTATION_PORTRAIT;

                // Options - (true / false)
                $footer = false;
                // End Settings

                $date = Carbon::now()->format('d/m/Y');
                $time = Carbon::now()->format('h:i:s');

                // Set Page Header
                if ($this->reportId) {
                    $event->sheet->getDelegate()->mergeCells('A1:B1');
                    $event->sheet->getDelegate()->setCellValue('A1', 'Report ID : '.$this->reportId);
                }

                if ($this->userId) {
                    $event->sheet->getDelegate()->mergeCells('A2:B2');
                    $event->sheet->getDelegate()->setCellValue('A2', 'User ID : '.$this->userId);
                }

                $event->sheet->getDelegate()->setCellValue($event->sheet->getDelegate()->getHighestColumn().'1', 'Date : '.$date);
                $event->sheet->getDelegate()->setCellValue($event->sheet->getDelegate()->getHighestColumn().'2', 'Time : '.$time);
                // End Page Header

                // Set Page Title
                $postfix = "";

                if($this->request['dateFrom'] != null && $this->request['dateTo'] == null) {
                    $postfix = "FROM ".date('d/m/Y', strtotime($this->request['dateFrom']));
                } elseif($this->request['dateFrom'] == null && $this->request['dateTo'] != null) {
                    $postfix = "TO ".date('d/m/Y', strtotime($this->request['date_to']));
                } elseif($this->request['dateFrom'] != null && $this->request['dateTo'] != null) {
                    $postfix = "FROM ".date('d/m/Y', strtotime($this->request['dateFrom']))." TO ".date('d/m/Y', strtotime($this->request['dateTo']));
                }

                $event->sheet->getDelegate()->mergeCells('A3:'.$event->sheet->getDelegate()->getHighestColumn().'3');
                $event->sheet->getDelegate()->setCellValue('A3', $reportNameSetting."\n".$postfix);
                $event->sheet->getDelegate()->getStyle('A3')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getRowDimension(3)->setRowHeight(40);
                // End Page Title


                $cellRangeTable = 'A4:'.$event->sheet->getDelegate()->getHighestColumn() . ($event->sheet->getDelegate()->getHighestRow());
                $event->sheet->getDelegate()->getStyle($cellRangeTable)->getFont()->setSize(8);

                // Apply array of styles to B2:G8 cell range
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '0000000'],
                        ]
                    ]
                ];

                $styleArrayAll = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '0000000'],
                        ]
                    ]
                ];

                $alignment = [
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        // 'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ];

                // Set table header
                $headerCoord = 'A4:'.$event->sheet->getDelegate()->getHighestColumn().$headerMaxRowSpanSetting;
                $event->sheet->getDelegate()->getStyle($headerCoord)->applyFromArray($styleArrayAll)->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle($headerCoord)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('EAEAEA');
                $event->sheet->getDelegate()->getStyle($headerCoord)->getAlignment()->setHorizontal('center');
                // End table header

                // Set body boarder
                $event->sheet->getDelegate()->getStyle($cellRangeTable)->applyFromArray($styleArray);
                $event->sheet->getDelegate()->getStyle($cellRangeTable)->applyFromArray($alignment);
            
                // Set table footer
                if ($footer == true) {
                    $footerCoord = 'A' . ($event->sheet->getDelegate()->getHighestRow()) . ':' . $event->sheet->getDelegate()->getHighestColumn() . ($event->sheet->getDelegate()
                            ->getHighestRow());
                    $event->sheet->getDelegate()->getStyle($footerCoord)->applyFromArray($styleArrayAll)->getFont()->setBold(true);
                    $event->sheet->getDelegate()->getStyle($footerCoord)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('EAEAEA');
                    $event->sheet->getDelegate()->getStyle('A' . ($event->sheet->getDelegate()->getHighestRow()))->getAlignment()->setHorizontal('right');
                }
                // table footer

                // Set first row to height 20
                $event->sheet->getDelegate()->getRowDimension(4)->setRowHeight(20);

                // Set range to wrap text in cells
                $event->sheet->getDelegate()->getStyle($cellRangeTable)->getAlignment()->setWrapText(true);

                // Set page orientation
                $event->sheet->getDelegate()->getPageSetup()->setOrientation($orientationSetting);

                //Set print scale
                $event->sheet->getDelegate()->getPageSetup()->setScale($scaleSetting);
            },
        ];
    }

    public function drawings() {
			/*
        $coord = 'E1';
        $maxWidth = 150;
        $width = 80;

        $drawing = new Drawing();
        $drawing->setPath(public_path('/assets/images/logo/logo.png'));
        $drawing->setCoordinates($coord);
        $drawing->setWidth($width);
        $offsetX = $maxWidth - $width;
        $drawing->setOffsetX($offsetX);

        return $drawing;
	
        $coord = 'C6';
        $maxWidth = 100;
        $width = 50;

        $coord2 = 'H6';
        $maxWidth2 = 70;
        $width2 = 50;

        $drawing = new Drawing();
        $drawing->setPath(public_path('/assets/images/logo/logo.png'));
        $drawing->setCoordinates($coord);
        $drawing->setWidth($width);
        $offsetX = $maxWidth - $width;
        $drawing->setOffsetX($offsetX);
        $drawing->setWidthAndHeight(48.7559,60.47);
        $drawing->setResizeProportional(true);

        $drawing2 = new Drawing();
        $drawing2->setPath(public_path('/assets/images/logo/logo.png'));
        $drawing2->setCoordinates($coord2);
        $drawing2->setWidth($width2);
        $offsetX2 = $maxWidth2 - $width2;
        $drawing2->setOffsetX($offsetX2);
        $drawing2->setWidthAndHeight(58.58,58.58);
        $drawing2->setResizeProportional(true);

        return [$drawing,$drawing2];
		*/
    }

    public function view(): View{
		
        return view('report.birthRegistration.reportExcel', [
            'data' => $this->result,
        ]);
    }
}
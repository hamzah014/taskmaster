<?php
    namespace App\Console\Commands;
    use Illuminate\Console\Command;
    use App\Models\AutoNumber;
    use App\Models\TenderProposal;
    use App\Models\TenderProposalComp;
    use App\Models\TenderProposalCompOfficer;
    use App\Models\TenderProposalCompShareHolder;
    use App\Models\Contractor;
    use App\Models\Staff;
    use App\Models\IntegrateSSMLog;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Config;

    class SSM extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
	protected $signature = 'OSC:SSM';
        /**
         * The console command description.
         *
         * @var string
         */
	protected $description = 'Task to pull SSM';
        /**
         * Create a new command instance.
         *
         * @return void
         */
        public function __construct()
        {
            parent::__construct();
        }
        /**
         * Execute the console command.
         *
         * @return mixed
         */
        public function handle()
        {
            $tenderProposalData = TenderProposal::where('TPCheckSSM','N')->get();
            if(isset($tenderProposalData) && count($tenderProposalData)>0) {
                foreach ($tenderProposalData as $x => $tenderProposal) {
                    $this->info('Process Record: '.($x+1).'/'.count($tenderProposalData).' - Proposal No:['.$tenderProposal->TPNo.']');
                    $contractor = Contractor::where('CONo',$tenderProposal->TP_CONo)->first();
                    if ($contractor != null){
                        $this->processSSM($contractor, $tenderProposal); //Process SSM Order Number
                        $this->getSSMData($contractor, $tenderProposal); //Get SSM Data By Order Number
                    }
                }
            }
            $this->info('SSM data has been processed successfully');
        }
        private function processSSM($contractor, $tenderProposal){
            $entityType = $this->getEntityType($contractor->COBusinessNo);
            if ( $entityType == ''){
                $this->error("Masalah Business No ".$contractor->COBusinessNo);
            }

            $integrateSSMLog = IntegrateSSMLog::where('ISSMLogRefNo',$tenderProposal->TPNo)->where('ISSMLogType','PROPOSAL')->where('ISSMLogInvoiceNo','!=','')->first();
            if ($integrateSSMLog != null){
                return;
            }
            try {

                $refNo = Carbon::now()->getPreciseTimestamp(3);
                $this->info('refNo '.$refNo);
                $params = [
                    'EntityNumber'	=> $contractor->COBusinessNo,
                    'CheckDigit'	=> "",
                    'EntityType'	=> $entityType,
                    'LocaleName'	=> "en",
                    'CustRefNo'		=> $refNo,
                ];
                 $request_data = json_encode($params);
                 $httpClient = new \GuzzleHttp\Client([
                     'base_uri' => config::get('app.ssm_url'), //"https://apistg.mydata-ssm.com.my", //Config::get('app.opp_url') ,
                     'headers' => [
                         'Content-Type' => 'application/json',
                         'Accept' => 'application/json',
                         'apiKey' => config::get('app.ssm_apikey'),//"E8i5wGtiwOjCTKSNOvbL92dRzd76psFF", //Config::get('app.opp_clientID'),
                     ],
                     'body'   => $request_data
                 ]);
                 $url = 'webapi/entity/profile';
                 $httpRequest = $httpClient->post($url);
                 $responseData = json_decode($httpRequest->getBody()->getContents());
                 if ($responseData != null){
                    $integrateSSMLog = new IntegrateSSMLog();
                    $integrateSSMLog->ISSMLogNo           = $refNo;
                    $integrateSSMLog->ISSMLogRefNo        = $tenderProposal->TPNo;
                    $integrateSSMLog->ISSMLogType         = 'PROPOSAL';
                    $integrateSSMLog->ISSMLogOrderNo      = $responseData->orderNumber;
                    $integrateSSMLog->ISSMLogInvoiceNo    = $responseData->invoiceNumber;
                    $integrateSSMLog->ISSMLogPayloadOrder = json_encode($responseData);
                    $integrateSSMLog->save();
                    if ($responseData->invoiceNumber == ''){
                        $tenderProposal->TPCheckSSM = 'E';
                        $tenderProposal->save();
                    }
                 }
             } catch(\GuzzleHttp\Exception\ConnectException $e){
                $this->error($e->getMessage());
                 /*
                 return response()->json([
                     'status' => 'failed',
                     'message' => 'Connection Error'
                 ]);*/
             }
        }
        private function getSSMData($contractor, $tenderProposal){
            $entityType = $this->getEntityType($contractor->COBusinessNo);

            $integrateSSMLog = IntegrateSSMLog::where('ISSMLogRefNo',$tenderProposal->TPNo)->where('ISSMLogType','PROPOSAL')->where('ISSMLogInvoiceNo','!=','')->first();
            if ($integrateSSMLog == null){
                return;
            }
            if ($integrateSSMLog->ISSMLogPayloadData == null){
                try {
                    $httpClient = new \GuzzleHttp\Client([
                        'base_uri' => config::get('app.ssm_url'), //"https://apistg.mydata-ssm.com.my", //Config::get('app.opp_url') ,
                        'headers' => [
                            //'Content-Type' => 'application/json',
                            //'Accept' => 'application/json',
                            //'apiKey' => "E8i5wGtiwOjCTKSNOvbL92dRzd76psFF", //Config::get('app.opp_clientID'),
                            //'OrderNumber' => $integrateSSMLog->ISSMLogOrderNo,
                        ],
                    ]);
                    $url = 'webapi/order/getJson?OrderNumber='.$integrateSSMLog->ISSMLogOrderNo;
                    $httpRequest = $httpClient->Get($url);
                    $responseData = json_decode($httpRequest->getBody()->getContents());

                    if ($responseData->successCode == '00'){
                        $integrateSSMLog->ISSMLogPayloadData = json_encode($responseData);
                        $integrateSSMLog->save();
                    }

                } catch(\GuzzleHttp\Exception\ConnectException $e){
                    if (isset($cmdClass)) $cmdClass->error($e->getMessage());
                    $this->error('ERROR: '.$e->getMessage());
                    /*
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Connection Error'
                    ]);*/
                }
            }else{
                $responseData = json_decode($integrateSSMLog->ISSMLogPayloadData);
            }

            if ($responseData != null){
            $comp = TenderProposalComp::where('TPC_TPNo', $tenderProposal->TPNo)->first();
            if ($comp == null){
                $comp = new TenderProposalComp();
                $comp->TPC_TPNo = $tenderProposal->TPNo;
            }
            //Business Info
            if(isset($responseData->robBusinessInfo)){
                $comp->TPC_entity_type          = $entityType;
                $comp->TPC_companyName          = $responseData->robBusinessInfo->registrationName;
                $comp->TPC_companyNo            = $responseData->robBusinessInfo->registrationNo.$responseData->robBusinessInfo->checkDigit;
                $comp->TPC_newFormatRegNo       = $responseData->robBusinessInfo->newFormatRegNo;
                $comp->TPC_ma_address1          = $responseData->robBusinessInfo->mainAddress1;
                $comp->TPC_ma_address2          = $responseData->robBusinessInfo->mainAddress2;
                $comp->TPC_ma_address3          = $responseData->robBusinessInfo->mainAddress3;
                $comp->TPC_ma_postcode          = $responseData->robBusinessInfo->mainPostcode;
                $comp->TPC_ma_town              = $responseData->robBusinessInfo->mainTown;
                $comp->TPC_ma_state             = $responseData->robBusinessInfo->mainState;
                $comp->TPC_pa_address1          = $responseData->robBusinessInfo->postAddress1;
                $comp->TPC_pa_address2          = $responseData->robBusinessInfo->postAddress2;
                $comp->TPC_pa_address3          = $responseData->robBusinessInfo->postAddress3;
                $comp->TPC_pa_postcode          = $responseData->robBusinessInfo->postPostcode;
                $comp->TPC_pa_town              = $responseData->robBusinessInfo->postTown;
                $comp->TPC_pa_state             = $responseData->robBusinessInfo->postState;
                $comp->TPC_businessDescription  = $responseData->robBusinessInfo->description;
                $comp->TPC_ownerCount           = $responseData->robBusinessInfo->ownerCount;

                $comp->TPC_bu_ammendmentDate     = ($responseData->robBusinessInfo->ammendmentDate != null) ? date('Y-m-d H:i:s', $responseData->robBusinessInfo->ammendmentDate / 1000) : null;
                $comp->TPC_bu_startBusinessDate  = ($responseData->robBusinessInfo->startBusinessDate != null) ? date('Y-m-d H:i:s', $responseData->robBusinessInfo->startBusinessDate / 1000) : null;
                $comp->TPC_bu_endBusinessDate    = ($responseData->robBusinessInfo->endBusinessDate != null) ? date('Y-m-d H:i:s', $responseData->robBusinessInfo->endBusinessDate / 1000) : null;
                $comp->TPC_bu_registrationDate   = ($responseData->robBusinessInfo->registrationDate != null) ? date('Y-m-d H:i:s', $responseData->robBusinessInfo->registrationDate / 1000) : null;
            }

            if(isset($responseData->robOwnershipListInfo->robOwnerShipInfos)){

                $robOwnerShipInfos = $responseData->robOwnershipListInfo->robOwnerShipInfos;

                foreach($robOwnerShipInfos as $robOwnerShipInfo) {
                        $comp->TPC_ol_ownerName         = $robOwnerShipInfo->ownerName;
                        $comp->TPC_ol_ownershipLink     = $robOwnerShipInfo->ownershipLink;
                        $comp->TPC_ol_idCardNumber      = $robOwnerShipInfo->idCardNumber;
                        $comp->TPC_ol_idCardType        = $robOwnerShipInfo->idCardType;
                        $comp->TPC_ol_newIcNo           = $robOwnerShipInfo->newIcNo;
                        $comp->TPC_ol_race              = $robOwnerShipInfo->race;
                        $comp->TPC_ol_othRace           = $robOwnerShipInfo->othRace;
                        $comp->TPC_ol_dob               = ($robOwnerShipInfo->dob != null) ? date('Y-m-d H:i:s', $robOwnerShipInfo->dob / 1000) : null;
                        $comp->TPC_ol_gender            = $robOwnerShipInfo->gender;
                        $comp->TPC_ol_nationality       = $robOwnerShipInfo->nationality;
                        $comp->TPC_ol_address1          = $robOwnerShipInfo->address1;
                        $comp->TPC_ol_address2          = $robOwnerShipInfo->address2;
                        $comp->TPC_ol_address3          = $robOwnerShipInfo->address3;
                        $comp->TPC_ol_postcode          = $robOwnerShipInfo->postcode;
                        $comp->TPC_ol_town              = $robOwnerShipInfo->town;
                        $comp->TPC_ol_state             = $robOwnerShipInfo->state;
                        $comp->TPC_ol_entryDate         = ($robOwnerShipInfo->entryDate != null) ? date('Y-m-d H:i:s', $robOwnerShipInfo->entryDate / 1000) : null;
                        $comp->TPC_ol_createDate        = ($robOwnerShipInfo->createDate != null) ? date('Y-m-d H:i:s', $robOwnerShipInfo->createDate / 1000) : null;
                        $comp->TPC_ol_updateDate        = ($robOwnerShipInfo->updateDate != null) ? date('Y-m-d H:i:s', $robOwnerShipInfo->updateDate / 1000) : null;
                    }
            }
            //Company Info
            if(isset($responseData->rocCompanyInfo)){
                $comp->TPC_entity_type          = $entityType;
                $comp->TPC_companyName          = $responseData->rocCompanyInfo->companyName;
                $comp->TPC_companyOldName       = $responseData->rocCompanyInfo->companyOldName;
                $comp->TPC_companyNo            = $responseData->rocCompanyInfo->companyNo.$responseData->rocCompanyInfo->checkDigit;
                $comp->TPC_newFormatRegNo       = $responseData->rocCompanyInfo->newFormatRegNo;
                $comp->TPC_companyStatus        = $responseData->rocCompanyInfo->companyStatus;
                $comp->TPC_companyType          = $responseData->rocCompanyInfo->companyType;
                $comp->TPC_currency             = $responseData->rocCompanyInfo->currency;
                $comp->TPC_dateOfChange         = ($responseData->rocCompanyInfo->dateOfChange != null) ? date('Y-m-d H:i:s', $responseData->rocCompanyInfo->dateOfChange / 1000) : null;
                $comp->TPC_incorpDate           = ($responseData->rocCompanyInfo->incorpDate != null) ? date('Y-m-d H:i:s', $responseData->rocCompanyInfo->incorpDate / 1000) : null;
                $comp->TPC_localforeignCompany  = $responseData->rocCompanyInfo->localforeignCompany;
                $comp->TPC_registrationDate     = ($responseData->rocCompanyInfo->registrationDate != null) ? date('Y-m-d H:i:s', $responseData->rocCompanyInfo->registrationDate / 1000) : null;
                $comp->TPC_statusOfCompany      = $responseData->rocCompanyInfo->statusOfCompany;
                $comp->TPC_wupType              = $responseData->rocCompanyInfo->wupType;
                $comp->TPC_businessDescription  = $responseData->rocCompanyInfo->businessDescription;
                $comp->TPC_companyCountry       = $responseData->rocCompanyInfo->companyCountry;
            }
            if(isset($responseData->rocRegAddressInfo)){
                $comp->TPC_ra_address1  = $responseData->rocRegAddressInfo->address1;
                $comp->TPC_ra_address2  = $responseData->rocRegAddressInfo->address2;
                $comp->TPC_ra_address3  = $responseData->rocRegAddressInfo->address3;
                $comp->TPC_ra_postcode  = $responseData->rocRegAddressInfo->postcode;
                $comp->TPC_ra_town      = $responseData->rocRegAddressInfo->town;
                $comp->TPC_ra_state     = $responseData->rocRegAddressInfo->state;
            }
            if(isset($responseData->rocBusinessAddressInfo)){
                $comp->TPC_ba_address1  = $responseData->rocBusinessAddressInfo->address1;
                $comp->TPC_ba_address2  = $responseData->rocBusinessAddressInfo->address2;
                $comp->TPC_ba_address3  = $responseData->rocBusinessAddressInfo->address3;
                $comp->TPC_ba_postcode  = $responseData->rocBusinessAddressInfo->postcode;
                $comp->TPC_ba_town      = $responseData->rocBusinessAddressInfo->town;
                $comp->TPC_ba_state     = $responseData->rocBusinessAddressInfo->state;
            }
            if(isset($responseData->rocCompanyOfficerListInfo->rocCompanyOfficerInfos)){
                $rocCompanyOfficerInfos = $responseData->rocCompanyOfficerListInfo->rocCompanyOfficerInfos;
                foreach($rocCompanyOfficerInfos as $officer) {
                    $compOfficer = TenderProposalCompOfficer::where('TPCO_TPNo', $tenderProposal->TPNo)->where('TPCO_idNo', $officer->idNo)->first();
                    if ($compOfficer == null){
                        $compOfficer = new TenderProposalCompOfficer();
                    }
                    $compOfficer->TPCO_TPNo             = $tenderProposal->TPNo;
                    $compOfficer->TPCO_companyNo        = $officer->companyNo;
                    $compOfficer->TPCO_idNo             = $officer->idNo;
                    $compOfficer->TPCO_idType           = $officer->idType;
                    $compOfficer->TPCO_name             = $officer->name;
                    $compOfficer->TPCO_address1         = $officer->address1;
                    $compOfficer->TPCO_address2         = $officer->address2;
                    $compOfficer->TPCO_address3         = $officer->address3;
                    $compOfficer->TPCO_appointmentDate  = ($officer->appointmentDate != null) ? date('Y-m-d H:i:s', $officer->appointmentDate / 1000) : null;
                    $compOfficer->TPCO_designationCode  = $officer->designationCode;
                    $compOfficer->TPCO_dob              = ($officer->dob != null) ? date('Y-m-d H:i:s', $officer->dob / 1000) : null;
                    $compOfficer->TPCO_officerInfo      = $officer->officerInfo;
                    $compOfficer->TPCO_postcode         = $officer->postcode;
                    $compOfficer->TPCO_state            = $officer->state;
                    $compOfficer->TPCO_town             = $officer->town;
                    $compOfficer->TPCO_startDate        = ($officer->startDate != null) ? date('Y-m-d H:i:s', $officer->startDate / 1000) : null;
                    $compOfficer->TPCO_resignDate       = ($officer->resignDate != null) ? date('Y-m-d H:i:s', $officer->resignDate / 1000) : null;
                    $compOfficer->save();
                }
            }
            if(isset($responseData->rocShareholderListInfo->rocShareholderInfos)){
                $rocShareholderInfos = $responseData->rocShareholderListInfo->rocShareholderInfos;
                foreach($rocShareholderInfos as $shareholder) {
                    $compShareholder = TenderProposalCompShareholder::where('TPCS_TPNo', $tenderProposal->TPNo)->where('TPCS_idNo', $shareholder->idNo)->first();
                    if ($compShareholder == null){
                        $compShareholder = new TenderProposalCompShareHolder();
                    }
                    $compShareholder->TPCS_TPNo             = $tenderProposal->TPNo;
                    $compShareholder->TPCS_companyNo        = $shareholder->companyNo;
                    $compShareholder->TPCS_idNo             = $shareholder->idNo;
                    $compShareholder->TPCS_idType           = $shareholder->idType;
                    $compShareholder->TPCS_name             = $shareholder->name;
                    $compShareholder->TPCS_share            = $shareholder->share;
                    $compShareholder->TPCS_shareVol         = $shareholder->shareVol;
                    $compShareholder->TPCS_dob              = ($shareholder->dob != null) ? date('Y-m-d H:i:s', $shareholder->dob / 1000) : null;
                    $compShareholder->TPCS_newFormatRegNo   = $shareholder->newFormatRegNo;
                    $compShareholder->save();
                }
            }
            if(isset($responseData->rocShareCapitalInfo)){
                $comp->TPC_sc_authorisedCapital     = $responseData->rocShareCapitalInfo->authorisedCapital;
                $comp->TPC_sc_currency              = $responseData->rocShareCapitalInfo->currency;
                $comp->TPC_sc_currenyNominal        = $responseData->rocShareCapitalInfo->currenyNominal;
                $comp->TPC_sc_ordAIssuedCash        = $responseData->rocShareCapitalInfo->ordAIssuedCash;
                $comp->TPC_sc_ordAIssuedNominal     = $responseData->rocShareCapitalInfo->ordAIssuedNominal;
                $comp->TPC_sc_ordAIssuedNonCash     = $responseData->rocShareCapitalInfo->ordAIssuedNonCash;
                $comp->TPC_sc_ordANominalValue      = $responseData->rocShareCapitalInfo->ordANominalValue;
                $comp->TPC_sc_ordANumberOfShares    = $responseData->rocShareCapitalInfo->ordANumberOfShares;
                $comp->TPC_sc_ordAmountAValue       = $responseData->rocShareCapitalInfo->ordAmountAValue;
                $comp->TPC_sc_ordAmountBValue       = $responseData->rocShareCapitalInfo->ordAmountBValue;
                $comp->TPC_sc_ordAmountValue        = $responseData->rocShareCapitalInfo->ordAmountValue;
                $comp->TPC_sc_ordBIssuedCash        = $responseData->rocShareCapitalInfo->ordBIssuedCash;
                $comp->TPC_sc_ordBIssuedNominal     = $responseData->rocShareCapitalInfo->ordBIssuedNominal;
                $comp->TPC_sc_ordBIssuedNonCash     = $responseData->rocShareCapitalInfo->ordBIssuedNonCash;
                $comp->TPC_sc_ordBNominalValue      = $responseData->rocShareCapitalInfo->ordBNominalValue;
                $comp->TPC_sc_ordBNumberOfShares    = $responseData->rocShareCapitalInfo->ordBNumberOfShares;
                $comp->TPC_sc_ordIssuedCash         = $responseData->rocShareCapitalInfo->ordIssuedCash;
                $comp->TPC_sc_ordIssuedNominal      = $responseData->rocShareCapitalInfo->ordIssuedNominal;
                $comp->TPC_sc_ordIssuedNonCash      = $responseData->rocShareCapitalInfo->ordIssuedNonCash;
                $comp->TPC_sc_ordNominalValue       = $responseData->rocShareCapitalInfo->ordNominalValue;
                $comp->TPC_sc_ordNumberOfShares     = $responseData->rocShareCapitalInfo->ordNumberOfShares;
                $comp->TPC_sc_othAIssuedCash        = $responseData->rocShareCapitalInfo->othAIssuedCash;
                $comp->TPC_sc_othAIssuedNonCash     = $responseData->rocShareCapitalInfo->othAIssuedNonCash;
                $comp->TPC_sc_othAmountValue        = $responseData->rocShareCapitalInfo->othAmountValue;
                $comp->TPC_sc_othBIssuedCash        = $responseData->rocShareCapitalInfo->othBIssuedCash;
                $comp->TPC_sc_othBIssuedNonCash     = $responseData->rocShareCapitalInfo->othBIssuedNonCash;
                $comp->TPC_sc_othIssuedCash         = $responseData->rocShareCapitalInfo->othIssuedCash;
                $comp->TPC_sc_othIssuedNominal      = $responseData->rocShareCapitalInfo->othIssuedNominal;
                $comp->TPC_sc_othIssuedNonCash      = $responseData->rocShareCapitalInfo->othIssuedNonCash;
                $comp->TPC_sc_othNominalValue       = $responseData->rocShareCapitalInfo->othNominalValue;
                $comp->TPC_sc_othNumberOfShares     = $responseData->rocShareCapitalInfo->othNumberOfShares;
                $comp->TPC_sc_prefAIssuedCash       = $responseData->rocShareCapitalInfo->prefAIssuedCash;
                $comp->TPC_sc_prefAIssuedNominal    = $responseData->rocShareCapitalInfo->prefAIssuedNominal;
                $comp->TPC_sc_prefAIssuedNonCash    = $responseData->rocShareCapitalInfo->prefAIssuedNonCash;
                $comp->TPC_sc_prefANominalValue     = $responseData->rocShareCapitalInfo->prefANominalValue;
                $comp->TPC_sc_prefANumberOfShares   = $responseData->rocShareCapitalInfo->prefANumberOfShares;
                $comp->TPC_sc_prefAmountAValue      = $responseData->rocShareCapitalInfo->prefAmountAValue;
                $comp->TPC_sc_prefAmountBValue      = $responseData->rocShareCapitalInfo->prefAmountBValue;
                $comp->TPC_sc_prefAmountValue       = $responseData->rocShareCapitalInfo->prefAmountValue;
                $comp->TPC_sc_prefBIssuedCash       = $responseData->rocShareCapitalInfo->prefBIssuedCash;
                $comp->TPC_sc_prefBIssuedNominal    = $responseData->rocShareCapitalInfo->prefBIssuedNominal;
                $comp->TPC_sc_prefBIssuedNonCash    = $responseData->rocShareCapitalInfo->prefBIssuedNonCash;
                $comp->TPC_sc_prefBNominalValue     = $responseData->rocShareCapitalInfo->prefBNominalValue;
                $comp->TPC_sc_prefBNumberOfShares   = $responseData->rocShareCapitalInfo->prefBNumberOfShares;
                $comp->TPC_sc_prefIssuedCash        = $responseData->rocShareCapitalInfo->prefIssuedCash;
                $comp->TPC_sc_prefIssuedNominal     = $responseData->rocShareCapitalInfo->prefIssuedNominal;
                $comp->TPC_sc_prefIssuedNonCash     = $responseData->rocShareCapitalInfo->prefIssuedNonCash;
                $comp->TPC_sc_prefNominalValue      = $responseData->rocShareCapitalInfo->prefNominalValue;
                $comp->TPC_sc_prefNumberOfShares    = $responseData->rocShareCapitalInfo->prefNumberOfShares;
                $comp->TPC_sc_totalIssued           = $responseData->rocShareCapitalInfo->totalIssued;
            }
            if(isset($responseData->rocBalanceSheetListInfo->rocBalanceSheetInfos)){
                $rocBalanceSheetInfos = $responseData->rocBalanceSheetListInfo->rocBalanceSheetInfos;
                foreach($rocBalanceSheetInfos as $balSheet) {
                    $comp->TPC_bs_accrualAccType        = $balSheet->accrualAccType;
                    $comp->TPC_bs_auditFirmAddress1     = $balSheet->auditFirmAddress1;
                    $comp->TPC_bs_auditFirmAddress2     = $balSheet->auditFirmAddress2;
                    $comp->TPC_bs_auditFirmAddress3     = $balSheet->auditFirmAddress3;
                    $comp->TPC_bs_auditFirmName         = $balSheet->auditFirmName;
                    $comp->TPC_bs_auditFirmNo           = $balSheet->auditFirmNo;
                    $comp->TPC_bs_auditFirmPostcode     = $balSheet->auditFirmPostcode;
                    $comp->TPC_bs_auditFirmState        = $balSheet->auditFirmState;
                    $comp->TPC_bs_auditFirmTown         = $balSheet->auditFirmTown;
                    $comp->TPC_bs_auditfirmFlag         = $balSheet->auditfirmFlag;
                    $comp->TPC_bs_branchkeycode         = $balSheet->branchkeycode;
                    $comp->TPC_bs_contigentLiability    = $balSheet->contigentLiability;
                    $comp->TPC_bs_currentAsset          = $balSheet->currentAsset;
                    $comp->TPC_bs_dateOfTabling         = ($balSheet->dateOfTabling != null) ? date('Y-m-d H:i:s', $balSheet->dateOfTabling / 1000) : null;
                    $comp->TPC_bs_financialReportType   = $balSheet->financialReportType;
                    $comp->TPC_bs_financialYearEndDate  = ($balSheet->financialYearEndDate != null) ? date('Y-m-d H:i:s', $balSheet->financialYearEndDate / 1000) : null;
                    $comp->TPC_bs_fixedAsset            = $balSheet->fixedAsset;
                    $comp->TPC_bs_fundAndReserve        = $balSheet->fundAndReserve;
                    $comp->TPC_bs_fundReserve           = $balSheet->fundReserve;
                    $comp->TPC_bs_headOfficeAccount     = $balSheet->headOfficeAccount;
                    $comp->TPC_bs_inappropriateProfit   = $balSheet->inappropriateProfit;
                    $comp->TPC_bs_liability             = $balSheet->liability;
                    $comp->TPC_bs_longTermLiability     = $balSheet->longTermLiability;
                    $comp->TPC_bs_minorityInterest      = $balSheet->minorityInterest;
                    $comp->TPC_bs_nonCurrAsset          = $balSheet->nonCurrAsset;
                    $comp->TPC_bs_nonCurrentLiability   = $balSheet->nonCurrentLiability;
                    $comp->TPC_bs_otherAsset            = $balSheet->otherAsset;
                    $comp->TPC_bs_paidUpCapital         = $balSheet->paidUpCapital;
                    $comp->TPC_bs_reserves              = $balSheet->reserves;
                    $comp->TPC_bs_shareAppAccount       = $balSheet->shareAppAccount;
                    $comp->TPC_bs_sharePremium          = $balSheet->sharePremium;
                    $comp->TPC_bs_totalInvestment       = $balSheet->totalInvestment;
                }
            }
            if(isset($responseData->rocProfitLossListInfo->rocProfitLossInfos)){
                $rocProfitLossInfos = $responseData->rocProfitLossListInfo->rocProfitLossInfos;
                foreach($rocProfitLossInfos as $profitLoss) {
                    $comp->TPC_pl_accrualAccount            = $profitLoss->accrualAccount;
                    $comp->TPC_pl_extraOrdinaryItem         = $profitLoss->extraOrdinaryItem;
                    $comp->TPC_pl_financialReportType       = $profitLoss->financialReportType;
                    $comp->TPC_pl_financialYearEndDate      = ($profitLoss->financialYearEndDate != null) ? date('Y-m-d H:i:s', $profitLoss->financialYearEndDate / 1000) : null;
                    $comp->TPC_pl_grossDividendRate         = $profitLoss->grossDividendRate;
                    $comp->TPC_pl_inappropriateProfitBf     = $profitLoss->inappropriateProfitBf;
                    $comp->TPC_pl_inappropriateProfitCf     = $profitLoss->inappropriateProfitCf;
                    $comp->TPC_pl_minorityInterest          = $profitLoss->minorityInterest;
                    $comp->TPC_pl_netDividend               = $profitLoss->netDividend;
                    $comp->TPC_pl_others                    = $profitLoss->others;
                    $comp->TPC_pl_priorAdjustment           = $profitLoss->priorAdjustment;
                    $comp->TPC_pl_profitAfterTax            = $profitLoss->profitAfterTax;
                    $comp->TPC_pl_profitBeforeTax           = $profitLoss->profitBeforeTax;
                    $comp->TPC_pl_profitShareholder         = $profitLoss->profitShareholder;
                    $comp->TPC_pl_revenue                   = $profitLoss->revenue;
                    $comp->TPC_pl_surplusAfterTax           = $profitLoss->surplusAfterTax;
                    $comp->TPC_pl_surplusBeforeTax          = $profitLoss->surplusBeforeTax;
                    $comp->TPC_pl_surplusDeficitAfterTax    = $profitLoss->surplusDeficitAfterTax;
                    $comp->TPC_pl_surplusDeficitBeforeTax   = $profitLoss->surplusDeficitBeforeTax;
                    $comp->TPC_pl_totalExpenditure          = $profitLoss->totalExpenditure;
                    $comp->TPC_pl_totalIncome               = $profitLoss->totalIncome;
                    $comp->TPC_pl_totalRevenue              = $profitLoss->totalRevenue;
                    $comp->TPC_pl_transferred               = $profitLoss->transferred;
                    $comp->TPC_pl_turnover                  = $profitLoss->turnover;
                }
            }
            $comp->save();
            $tenderProposal->TPCheckSSM = 'Y';
            $tenderProposal->save();
            }

        }

        private function getEntityType($businessNo){
            $digit = substr($businessNo, 4,2);
            if($digit == '01'){
                return 'Company';
            }else if($digit == '02'){
                return 'Company';
            }else if($digit == '03'){
                return 'Business';
            }else{
                return '';
            }
        }
    }

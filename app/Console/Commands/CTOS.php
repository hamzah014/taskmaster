<?php

    namespace App\Console\Commands;

    use Illuminate\Console\Command;
    use App\Http\Controllers\Api\CTOSController;
    use App\Models\TenderProposal;
    use App\Models\TenderProposalCTOS;
    use App\Models\TenderProposalCTOSComp;
    use App\Models\TenderProposalCTOSLoan;
    use App\Models\TenderProposalCTOSLoanDet;
    use App\Models\TenderProposalCTOSLegalCase;
    use App\Models\Contractor;
    use App\Models\IntegrationLog;
    use Illuminate\Support\Facades\Log;
    use Carbon\Carbon;
    class CTOS extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
	protected $signature = 'OSC:CTOS';

        /**
         * The console command description.
         *
         * @var string
         */
	protected $description = 'Task to pull Tender Proposal CTOS';

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
            $tenderProposalData = TenderProposal::where('TPCheckCTOS','N')->get();

            if(isset($tenderProposalData) && count($tenderProposalData)>0) {
                foreach ($tenderProposalData as $x => $tenderProposal) {
                    $this->info('Process Record: '.($x+1).'/'.count($tenderProposalData).' - Proposal No:['.$tenderProposal->TPNo.']');

                    $contractor = Contractor::where('CONo',$tenderProposal->TP_CONo)->first();
                    if ($contractor!= null){
                        $this->updateCTOS($contractor, $tenderProposal);
                    }
                }
            }
            $this->info('News data has been processed successfully');
        }

        private function updateCTOS($contractor, $tenderProposal){

            $ctos = new CTOSController();
            $proposalNo = $tenderProposal->TPNo;
            $basic_group_code = '24'; //11(Individual) 21(Business/Sole Proprietorship) 22(Partnership) 24(Company) 26 (LLP)
            $party_type = 'C'; //I(Individual), B(Business), C(Company)
            $ic_lc = '1405703D'; //'1909128M'; //$contractor->COCompNo
            $icNo = '';
            $name =htmlentities('MAH ENGINEERING & CONSTRUCTION SDN.'); //'INTernal Sdn Bhd'; //htmlentities($contractor->COName);

            $jsonArray = $ctos->processCTOS($proposalNo, $basic_group_code, $party_type, $ic_lc, $icNo, $name);

                if ($jsonArray != null){


                    $section_summary =  $jsonArray['enq_report']['enquiry']['section_summary'];
                    $section_ccris =  $jsonArray['enq_report']['enquiry']['section_ccris'];

                    $proposalCTOS = TenderProposalCTOS::where('TPCTOS_TPNo', $tenderProposal->TPNo)->first();
                    if ($proposalCTOS == null){
                        $proposalCTOS = new TenderProposalCTOS();
                    }
                    $proposalCTOS->TPCTOS_TPNo                                      = $tenderProposal->TPNo;
                    if ($section_summary != null){
                    $proposalCTOS->TPCTOS_ci_ctos_bankruptcy_source_code            = $section_summary['ctos']['bankruptcy']['@attributes']['source']['code'] ?? null;
                    $proposalCTOS->TPCTOS_ci_ctos_bankruptcy_source_name            = $section_summary['ctos']['bankruptcy']['@attributes']['source']['name'] ?? null;
                    $proposalCTOS->TPCTOS_ci_ctos_bankruptcy_status                 = $section_summary['ctos']['bankruptcy']['@attributes']['status'];
                    $proposalCTOS->TPCTOS_ci_ctos_legal_total                       = (float) $section_summary['ctos']['legal']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_legal_value                       = (float) $section_summary['ctos']['legal']['@attributes']['value'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_legal_personal_capacity_total     = (float) $section_summary['ctos']['legal_personal_capacity']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_legal_personal_capacity_value     = (float) $section_summary['ctos']['legal_personal_capacity']['@attributes']['value'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_non_legal_personal_capacity_total = (float) $section_summary['ctos']['legal_non_personal_capacity']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_non_legal_personal_capacity_value = (float) $section_summary['ctos']['legal_non_personal_capacity']['@attributes']['value'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_application_total                = (float) $section_summary['ccris']['application']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_application_approve              = (float) $section_summary['ccris']['application']['@attributes']['approved'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_application_pending              = (float) $section_summary['ccris']['application']['@attributes']['pending'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_facility_total                   = (float) $section_summary['ccris']['facility']['@attributes']['total']?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_facility_arrears                 = (float) $section_summary['ccris']['facility']['@attributes']['arrears']?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_facility_value                   = (float) $section_summary['ccris']['facility']['@attributes']['value']?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_special_attention_accounts       = (float) $section_summary['ccris']['special_attention']['@attributes']['accounts'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_bankruptcy_source_code         = $section_summary['ctos']['bankruptcy']['@attributes']['source']['code'] ?? null;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_bankruptcy_source_name         = $section_summary['ctos']['bankruptcy']['@attributes']['source']['name'] ?? null;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_bankruptcy_status              = $section_summary['ctos']['bankruptcy']['@attributes']['status'];
                    $proposalCTOS->TPCTOS_ci_ctos_rp_legal_total                    = (float) $section_summary['ctos']['related_parties']['legal']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_legal_value                    = (float) $section_summary['ctos']['related_parties']['legal']['@attributes']['value'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_legal_personal_capacity_total      = $section_summary['ctos']['related_parties']['legal_personal_capacity']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_legal_personal_capacity_value      = $section_summary['ctos']['related_parties']['legal_personal_capacity']['@attributes']['value'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_non_legal_personal_capacity_total  = $section_summary['ctos']['related_parties']['legal_non_personal_capacity']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ctos_rp_non_legal_personal_capacity_value  = $section_summary['ctos']['related_parties']['legal_non_personal_capacity']['@attributes']['value'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_application_total             = (float) $section_summary['ccris']['related_parties']['application']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_application_approve           = (float) $section_summary['ccris']['related_parties']['application']['@attributes']['approved'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_application_pending           = (float) $section_summary['ccris']['related_parties']['application']['@attributes']['pending'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_facility_total                = (float) $section_summary['ccris']['related_parties']['facility']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_facility_arrears              = (float) $section_summary['ccris']['related_parties']['facility']['@attributes']['arrears'] ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_facility_value                = (float) $section_summary['ccris']['related_parties']['facility']['@attributes']['value']  ?? 0;
                    $proposalCTOS->TPCTOS_ci_ccris_rp_special_attention_accounts    = (float) $section_summary['ccris']['related_parties']['special_attention']['@attributes']['accounts']  ?? 0;
                    }
                    if ($section_ccris != null){
                    $proposalCTOS->TPCTOS_cs_entity_key                             = $section_ccris['summary']['@attributes']['entity_key'];
                    $proposalCTOS->TPCTOS_cs_entity_warning                         = $section_ccris['summary']['@attributes']['entity_warning'];
                   // $proposalCTOS->TPCTOS_cs_application_approved_count             = $this->convertAmt($section_ccris['summary']['application']['approved']['@attributes']['count']);
                    $proposalCTOS->TPCTOS_cs_application_approved                   = (float) $section_ccris['summary']['application']['approved'] ?? 0;
                   // $proposalCTOS->TPCTOS_cs_application_pending_count              = (float) $section_ccris['summary']['application']['pending']['@attributes']['count'] ?? 0;
                    $proposalCTOS->TPCTOS_cs_application_pending                    = (float) $section_ccris['summary']['application']['pending'] ?? 0;
                 //   $proposalCTOS->TPCTOS_cs_liabilities_borrower_total_limit       = (float) $section_ccris['summary']['liabilities']['borrower']['@attributes']['total_limit'] ?? 0;
                  //  $proposalCTOS->TPCTOS_cs_liabilities_borrower_fec_limit         = (float) $section_ccris['summary']['liabilities']['borrower']['@attributes']['fec_limit'] ?? 0;
                    $proposalCTOS->TPCTOS_cs_liabilities_borrower                   = (float) $section_ccris['summary']['liabilities']['borrower'] ?? 0;
                    $proposalCTOS->TPCTOS_cs_liabilities_guarantor_total_limit      = (float) $section_ccris['summary']['liabilities']['guarantor']['@attributes']['total_limit'] ?? 0;
                    $proposalCTOS->TPCTOS_cs_liabilities_guarantor_fec_limit        = (float) $section_ccris['summary']['liabilities']['guarantor']['@attributes']['fec_limit'] ?? 0;
                  //  $proposalCTOS->TPCTOS_cs_liabilities_guarantor                  = (float) $section_ccris['summary']['liabilities']['guarantor'] ?? 0;
                    $proposalCTOS->TPCTOS_cs_legal_status                           = $section_ccris['summary']['legal']['@attributes']['status'];
                    $proposalCTOS->TPCTOS_cs_special_attention_status               = $section_ccris['summary']['special_attention']['@attributes']['status'];
                    $proposalCTOS->TPCTOS_cs_special_name_status                    = $section_ccris['summary']['special_name']['@attributes']['status'];
                    $derivatives_application_date  = ($section_ccris['derivatives']['application']['date'] != null) ? carbon::parse($this->convertDate($section_ccris['derivatives']['application']['date']))->format('Y-m-d h:i:s') : null;
                    $proposalCTOS->TPCTOS_cd_derivatives_application_date           = $derivatives_application_date;
                    $proposalCTOS->TPCTOS_cd_derivatives_application_facility       = (float) $section_ccris['derivatives']['application']['facility'] ?? 0;
                 //   $proposalCTOS->TPCTOS_cd_facilities_secure_outstanding_average  = (float) $section_ccris['derivatives']['facilities']['secure']['outstanding']['@attributes']['average'] ?? 0;
                 //   $proposalCTOS->TPCTOS_cd_facilities_secure_outstanding_limit    = (float) $section_ccris['derivatives']['facilities']['secure']['outstanding']['@attributes']['limit'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_facilities_secure_total                = (float) $section_ccris['derivatives']['facilities']['secure']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_facilities_secure_outstanding          = (float) $section_ccris['derivatives']['facilities']['secure']['outstanding'] ?? 0;
                 //   $proposalCTOS->TPCTOS_cd_facilities_unsecure_outstanding_average= (float) $section_ccris['derivatives']['facilities']['unsecure']['outstanding']['@attributes']['average'] ?? 0;
                 //   $proposalCTOS->TPCTOS_cd_facilities_unsecure_outstanding_limit  = (float) $section_ccris['derivatives']['facilities']['unsecure']['outstanding']['@attributes']['limit'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_facilities_unsecure_total              = (float) $section_ccris['derivatives']['facilities']['unsecure']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_facilities_unsecure_outstanding        = (float) $section_ccris['derivatives']['facilities']['unsecure']['outstanding'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_credit_card_avg_usage_6_mths           = (float) $section_ccris['derivatives']['credit_card']['@attributes']['avg_usage_6_mths'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_oth_revolving_credits_avg_usage_6_mths = (float) $section_ccris['derivatives']['oth_revolving_credits']['@attributes']['avg_usage_6_mths'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_charge_card_min_usage_12_months        = (float) $section_ccris['derivatives']['charge_card']['@attributes']['min_usage_12_months'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_charge_card_max_usage_12_months        = (float) $section_ccris['derivatives']['charge_card']['@attributes']['max_usage_12_months'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_ptptn_total                            = (float) $section_ccris['derivatives']['ptptn']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_local_lenders_total                    = (float) $section_ccris['derivatives']['local_lenders']['@attributes']['total'] ?? 0;
                    $proposalCTOS->TPCTOS_cd_foreign_lenders_total                  = (float) $section_ccris['derivatives']['foreign_lenders']['@attributes']['total'] ?? 0;
                    }
                    $proposalCTOS->save();


                    $accounts = $section_ccris['accounts'];

                    foreach($accounts as $account) {
                        if(isset($account)){
                            $proposalCTOSLoan = TenderProposalCTOSLoan::where('TPCL_TPNo', $tenderProposal->TPNo)->where('TPCL_mdta_id',$account['mdta_id'])->first();
                            if ($proposalCTOSLoan == null){
                                $proposalCTOSLoan = new TenderProposalCTOSLoan();
                            }
                            $proposalCTOSLoan->TPCL_TPNo                    = $tenderProposal->TPNo;
                            $proposalCTOSLoan->TPCL_mdta_id                 = (float) $account['mdta_id'];
                            $proposalCTOSLoan->TPCL_org1                    = $this->convertText($account['org1']);
                            $proposalCTOSLoan->TPCL_org2                    = $this->convertText($account['org2']);
                            $proposalCTOSLoan->TPCL_my_fgn                  = $account['my_fgn'];
                            $approval_date  = ($account['approval_date'] != null) ? carbon::parse($this->convertDate($account['approval_date']))->format('Y-m-d') : null;
                            $proposalCTOSLoan->TPCL_approval_date           = $approval_date;
                            $proposalCTOSLoan->TPCL_capacity_code           = $account['capacity'];
                            $proposalCTOSLoan->TPCL_capacity                = $account['capacity'];
                            $proposalCTOSLoan->TPCL_lender_type             = $account['lender_type'];
                            $proposalCTOSLoan->TPCL_limit                   = (float) $account['limit'];
                            if (isset($account['collaterals']['collateral']['@attributes']['code']))
                            $proposalCTOSLoan->TPCL_collateral_code         = $account['collaterals']['collateral']['@attributes']['code'];
                            if (isset($account['collaterals']['collateral']['name']))
                            $proposalCTOSLoan->TPCL_collateral_name         = $account['collaterals']['collateral']['name'];
                            if (isset($account['collaterals']['collateral']['value']))
                            $proposalCTOSLoan->TPCL_collateral_value        = $this->convertText($account['collaterals']['collateral']['value']);
                            if (isset($account['legal']['date'])){
                            $legal_date  = ($account['legal']['date'] != null) ? carbon::parse($this->convertDate($account['legal']['date']))->format('Y-m-d') : null;
                            $proposalCTOSLoan->TPCL_legal_date              = $legal_date;
                            }
                            if (isset($account['sub_accounts']['sub_account']['facility']))
                            $proposalCTOSLoan->TPCL_sub_account_facility    = $account['sub_accounts']['sub_account']['facility'];
                            if (isset($account['sub_accounts']['sub_account']['repay_term']))
                            $proposalCTOSLoan->TPCL_sub_account_repay_term  = $account['sub_accounts']['sub_account']['repay_term'];
                            if (isset($account['sub_accounts']['sub_account']['collaterals']))
                            $proposalCTOSLoan->TPCL_sub_account_collaterals = json_encode($account['sub_accounts']['sub_account']['collaterals']);
                            $proposalCTOSLoan->save();

                            $crPositions = $account['sub_accounts']['sub_account']['cr_positions']['cr_position'];
                            foreach($crPositions as $crPosition) {
                                if(isset($crPosition)){

                                    $position_date  = ($crPosition['position_date'] != null) ? carbon::parse($this->convertDate($crPosition['position_date']))->format('Y-m-d') : null;

                                    $proposalCTOSLoanDet = TenderProposalCTOSLoanDet::where('TPCLD_TPNo', $tenderProposal->TPNo)->where('TPCLD_mdta_id',$account['mdta_id'])->whereDate('TPCLD_position_date',$position_date)->first();
                                    if ($proposalCTOSLoanDet == null){
                                        $proposalCTOSLoanDet = new TenderProposalCTOSLoanDet();
                                    }
                                    $proposalCTOSLoanDet->TPCLD_TPNo                = $tenderProposal->TPNo;
                                    $proposalCTOSLoanDet->TPCLD_mdta_id             = (float) $account['mdta_id'];
                                    $proposalCTOSLoanDet->TPCLD_position_date       = $position_date;
                                    $proposalCTOSLoanDet->TPCLD_position_status     = $this->convertText($crPosition['status']);
                                    $proposalCTOSLoanDet->TPCLD_position_balance    = (float) $this->convertText($crPosition['balance']);
                                    $proposalCTOSLoanDet->TPCLD_inst_arrears        = (float) $crPosition['inst_arrears'];
                                    $proposalCTOSLoanDet->TPCLD_mon_arrears         = (float) $crPosition['mon_arrears'];
                                    $proposalCTOSLoanDet->TPCLD_inst_amount         = (float) $crPosition['inst_amount'];
                                    $rescheduled_date  = ($crPosition['rescheduled_date'] != null) ? carbon::parse($this->convertDate($this->convertText($crPosition['rescheduled_date'])))->format('Y-m-d') : null;
                                    $proposalCTOSLoanDet->TPCLD_rescheduled_date    = $rescheduled_date;
                                    $restructured_date  = ($crPosition['restructured_date'] != null) ? carbon::parse($this->convertDate($this->convertText($crPosition['restructured_date'])))->format('Y-m-d') : null;
                                    $proposalCTOSLoanDet->TPCLD_restructured_date   = $restructured_date;
                                    $proposalCTOSLoanDet->save();

                                }
                            }
                        }
                    }

                    $section_d =  $jsonArray['enq_report']['enquiry']['section_d'];
                    if ($section_d['@attributes']['data'] == 'true'){

                        $proposalCTOSLegalCase = TenderProposalCTOSLegalCase::where('TPCLS_TPNo', $tenderProposal->TPNo)->where('TPCLS_seq',$section_d['record']['@attributes']['seq'])->first();
                        if ($proposalCTOSLegalCase == null){
                            $proposalCTOSLegalCase = new TenderProposalCTOSLegalCase();
                        }
                        $proposalCTOSLegalCase->TPCLS_TPNo              = $tenderProposal->TPNo;
                        $proposalCTOSLegalCase->TPCLS_seq               = $section_d['record']['@attributes']['seq'];
                        $proposalCTOSLegalCase->TPCLS_rpttype           = $section_d['record']['@attributes']['rpttype'];
                        $proposalCTOSLegalCase->TPCLS_status            = $section_d['record']['@attributes']['status'];
                        if (isset($section_d['record']['title']))
                        $proposalCTOSLegalCase->TPCLS_title             = $section_d['record']['title'];
                        if (isset($section_d['record']['special_remark']))
                        $proposalCTOSLegalCase->TPCLS_special_remark    = $section_d['record']['special_remark'];
                        if (isset($section_d['record']['name']))
                        $proposalCTOSLegalCase->TPCLS_name              = $section_d['record']['name'];
                        if (isset($section_d['record']['name']['@attributes']['match']))
                        $proposalCTOSLegalCase->TPCLS_name_match        = $section_d['record']['name']['@attributes']['match'];
                        if (isset($section_d['record']['alias']))
                        $proposalCTOSLegalCase->TPCLS_alias             = $this->convertText($section_d['record']['alias']);
                        if (isset($section_d['record']['addr']))
                        $proposalCTOSLegalCase->TPCLS_addr              = $this->convertText($section_d['record']['addr']);
                        if (isset($section_d['record']['ic_lcno']))
                        $proposalCTOSLegalCase->TPCLS_ic_lcno           = $section_d['record']['ic_lcno'];
                        if (isset($section_d['record']['nic_brno']))
                        $proposalCTOSLegalCase->TPCLS_nic_brno          = $section_d['record']['nic_brno'];
                        if (isset($section_d['record']['cpo_date'])){
                        $cpo_date = ($section_d['record']['cpo_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['cpo_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_cpo_date          = $cpo_date;
                        }
                        if (isset($section_d['record']['position']))
                        $proposalCTOSLegalCase->TPCLS_position          = $this->convertText($section_d['record']['position']);
                        if (isset($section_d['record']['local']))
                        $proposalCTOSLegalCase->TPCLS_local             = $this->convertText($section_d['record']['local']);
                        if (isset($section_d['record']['case_no']))
                        $proposalCTOSLegalCase->TPCLS_case_no           = $this->convertText($section_d['record']['case_no']);
                        if (isset($section_d['record']['court_detail']))
                        $proposalCTOSLegalCase->TPCLS_court_detail      = $this->convertText($section_d['record']['court_detail']);
                        if (isset($section_d['record']['firm']))
                        $proposalCTOSLegalCase->TPCLS_firm              = $this->convertText($section_d['record']['firm']);
                        if (isset($section_d['record']['independent']))
                        $proposalCTOSLegalCase->TPCLS_independent       = $this->convertText($section_d['record']['independent']);
                        if (isset($section_d['record']['property_location']))
                        $proposalCTOSLegalCase->TPCLS_property_location = $this->convertText($section_d['record']['property_location']);
                        if (isset($section_d['record']['property_description']))
                        $proposalCTOSLegalCase->TPCLS_property_description  = $this->convertText($section_d['record']['property_description']);
                        if (isset($section_d['record']['respondent']))
                        $proposalCTOSLegalCase->TPCLS_respondent        = $this->convertText($section_d['record']['respondent']);
                        if (isset($section_d['record']['exparte']))
                        $proposalCTOSLegalCase->TPCLS_exparte           = $this->convertText($section_d['record']['exparte']);
                        if (isset($section_d['record']['plaintiff']))
                        $proposalCTOSLegalCase->TPCLS_plaintiff         = $this->convertText($section_d['record']['plaintiff']);
                        if (isset($section_d['record']['petitioner']))
                        $proposalCTOSLegalCase->TPCLS_petitioner        = $this->convertText($section_d['record']['petitioner']);
                        if (isset($section_d['record']['assignee']))
                        $proposalCTOSLegalCase->TPCLS_assignee          = $this->convertText($section_d['record']['assignee']);
                        if (isset($section_d['record']['chargee']))
                        $proposalCTOSLegalCase->TPCLS_chargee           = $this->convertText($section_d['record']['chargee']);
                        if (isset($section_d['record']['applicant']))
                        $proposalCTOSLegalCase->TPCLS_applicant         = $this->convertText($section_d['record']['applicant']);
                        if (isset($section_d['record']['notice']['date'])){
                        $notice_date = ($section_d['record']['notice']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['notice']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_notice_date           = $notice_date;
                        }
                        if (isset($section_d['record']['notice']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_notice_source_detail  = $section_d['record']['notice']['source_detail'];
                        if (isset($section_d['record']['petition']['date'])){
                        $petition_date = ($section_d['record']['petition']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['petition']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_petition_date         = $petition_date;
                        }
                        if (isset($section_d['record']['petition']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_petition_source_detail= $section_d['record']['petition']['source_detail'];
                        if (isset($section_d['record']['order']['date'])){
                        $order_date = ($section_d['record']['order']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['order']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_order_date            = $order_date;
                        }
                        if (isset($section_d['record']['order']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_order_source_detail   = $section_d['record']['order']['source_detail'];
                        if (isset($section_d['record']['release']['date'])){
                        $release_date = ($section_d['record']['release']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['release']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_release_date          = $release_date;
                        }
                        if (isset($section_d['record']['release']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_release_source_detail = $section_d['record']['release']['source_detail'];
                        if (isset($section_d['record']['action']['date'])){
                        $action_date = ($section_d['record']['action']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['action']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_action_date           = $action_date;
                        }
                        if (isset($section_d['record']['action']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_action_source_detail  = $section_d['record']['action']['source_detail'];
                        if (isset($section_d['record']['auction']['date'])){
                        $auction_date = ($section_d['record']['auction']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['auction']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_auction_date          = $auction_date;
                        }
                        if (isset($section_d['record']['auction']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_auction_source_detail = $section_d['record']['auction']['source_detail'];
                        if (isset($section_d['record']['originating_summons']['date'])){
                        $originating_summons_date = ($section_d['record']['originating_summons']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['originating_summons']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_originating_summons_date          = $originating_summons_date;
                        }
                        if (isset($section_d['record']['originating_summons']['source_detail']))
                        $proposalCTOSLegalCase->TPCLS_originating_summons_source_detail = $this->convertText($section_d['record']['originating_summons']['source_detail']);
                        if (isset($section_d['record']['gazette']))
                        $proposalCTOSLegalCase->TPCLS_gazette                           = $this->convertText($section_d['record']['gazette']);
                        if (isset($section_d['record']['gazette_order']))
                        $proposalCTOSLegalCase->TPCLS_gazette_order                     = $this->convertText($section_d['record']['gazette_order']);
                        if (isset($section_d['record']['gazette_strike_off']))
                        $proposalCTOSLegalCase->TPCLS_gazette_strike_off                = $this->convertText($section_d['record']['gazette_strike_off']);
                        if (isset($section_d['record']['gazette_notice']))
                        $proposalCTOSLegalCase->TPCLS_gazette_notice                    = $this->convertText($section_d['record']['gazette_notice']);
                        if (isset($section_d['record']['gazette_petition']))
                        $proposalCTOSLegalCase->TPCLS_gazette_petition                  = $this->convertText($section_d['record']['gazette_petition']);
                        if (isset($section_d['record']['gazette_discharge']))
                        $proposalCTOSLegalCase->TPCLS_gazette_discharge                 = $this->convertText($section_d['record']['gazette_discharge']);
                        if (isset($section_d['record']['register_owner']))
                        $proposalCTOSLegalCase->TPCLS_register_owner                    = $this->convertText($section_d['record']['register_owner']);
                        if (isset($section_d['record']['object']))
                        $proposalCTOSLegalCase->TPCLS_object                            = $this->convertText($section_d['record']['object']);
                        if (isset($section_d['record']['hear_date'])){
                        $hear_date          = ($section_d['record']['hear_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['hear_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_hear_date                 = $hear_date;
                        }
                        if (isset($section_d['record']['appoint_date'])){
                        $appoint_date          = ($section_d['record']['appoint_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['appoint_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_appoint_date              = $appoint_date;
                        }
                        if (isset($section_d['record']['gazette_date'])){
                        $gazette_date         = ($section_d['record']['gazette_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['gazette_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_gazette_date              = $gazette_date ;
                        }
                        if (isset($section_d['record']['trike_off_date'])){
                        $trike_off_date        = ($section_d['record']['trike_off_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['trike_off_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_trike_off_date            = $trike_off_date ;
                        }
                        if (isset($section_d['record']['letter_date_under_308_1'])){
                        $letter_date_under_308_1       = ($section_d['record']['letter_date_under_308_1'] != null) ? carbon::parse($this->convertDate($section_d['record']['letter_date_under_308_1']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_letter_date_under_308_1   = $letter_date_under_308_1;
                        }
                        if (isset($section_d['record']['letter_date_under_308_2'])){
                        $letter_date_under_308_2       = ($section_d['record']['letter_date_under_308_2'] != null) ? carbon::parse($this->convertDate($section_d['record']['letter_date_under_308_2']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_letter_date_under_308_2   = $letter_date_under_308_2;
                        }
                        if (isset($section_d['record']['resigned_date'])){
                        $resigned_date      = ($section_d['record']['resigned_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['resigned_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_resigned_date             = $resigned_date ;
                        }
                        if (isset($section_d['record']['incoporate_date'])){
                        $incoporate_date     = ($section_d['record']['incoporate_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['incoporate_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_incoporate_date           = $incoporate_date;
                        }
                        if (isset($section_d['record']['return_date'])){
                        $return_date     = ($section_d['record']['return_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['return_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_return_date                = $return_date;
                        }
                        if (isset($section_d['record']['assignment_date'])){
                        $assignment_date     = ($section_d['record']['assignment_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['assignment_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_assignment_date           = $assignment_date ;
                        }
                        if (isset($section_d['record']['order_for_sale_date'])){
                        $order_for_sale_date    = ($section_d['record']['order_for_sale_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['order_for_sale_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_order_for_sale_date       = $order_for_sale_date;
                        }
                        if (isset($section_d['record']['action_dated'])){
                        $action_dated    = ($section_d['record']['action_dated '] != null) ? carbon::parse($this->convertDate($section_d['record']['action_dated ']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_action_dated              = $action_dated;
                        }
                        if (isset($section_d['record']['outstanding_amount']))
                        $proposalCTOSLegalCase->TPCLS_outstanding_amount        = (float) $section_d['record']['outstanding_amount'];
                        if (isset($section_d['record']['shareholding']))
                        $proposalCTOSLegalCase->TPCLS_shareholding              = $section_d['record']['shareholding'];
                        if (isset($section_d['record']['amount']))
                        $proposalCTOSLegalCase->TPCLS_amount                    = (float) $section_d['record']['amount'];
                        if (isset($section_d['record']['reserve_price']))
                        $proposalCTOSLegalCase->TPCLS_reserve_price             = (float) $section_d['record']['reserve_price'];
                        if (isset($section_d['record']['capital']))
                        $proposalCTOSLegalCase->TPCLS_capital                   = (float) $section_d['record']['capital'];
                        if (isset($section_d['record']['paidup']))
                        $proposalCTOSLegalCase->TPCLS_paidup                    = (float) $section_d['record']['paidup'];
                        if (isset($section_d['record']['remark']))
                        $proposalCTOSLegalCase->TPCLS_remark                    = $this->convertText($section_d['record']['remark']);
                        if (isset($section_d['record']['remark1']))
                        $proposalCTOSLegalCase->TPCLS_remark1                   = $this->convertText($section_d['record']['remark1']);
                        if (isset($section_d['record']['remark2']))
                        $proposalCTOSLegalCase->TPCLS_remark2                   = $this->convertText($section_d['record']['remark2']);
                        if (isset($section_d['record']['remark3']))
                        $proposalCTOSLegalCase->TPCLS_remark3                   = $this->convertText($section_d['record']['remark3']);
                        if (isset($section_d['record']['remark4']))
                        $proposalCTOSLegalCase->TPCLS_remark4                   = $this->convertText($section_d['record']['remark4']);
                        if (isset($section_d['record']['administrator1']))
                        $proposalCTOSLegalCase->TPCLS_administrator1            = $this->convertText($section_d['record']['administrator1']);
                        if (isset($section_d['record']['administrator2']))
                        $proposalCTOSLegalCase->TPCLS_administrator2            = $this->convertText($section_d['record']['administrator2']);
                        if (isset($section_d['record']['administrator3']))
                        $proposalCTOSLegalCase->TPCLS_administrator3            = $this->convertText($section_d['record']['administrator3']);
                        if (isset($section_d['record']['administrator4']))
                        $proposalCTOSLegalCase->TPCLS_administrator4            = $this->convertText($section_d['record']['administrator4']);
                        if (isset($section_d['record']['lawyer']['name']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_name               = $this->convertText($section_d['record']['lawyer']['name']);
                        if (isset($section_d['record']['lawyer']['add1']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_add1               = $this->convertText($section_d['record']['lawyer']['add1']);
                        if (isset($section_d['record']['lawyer']['add2']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_add2               = $this->convertText($section_d['record']['lawyer']['add2']);
                        if (isset($section_d['record']['lawyer']['add3']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_add3               = $this->convertText($section_d['record']['lawyer']['add3']);
                        if (isset($section_d['record']['lawyer']['add4']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_add4               = $this->convertText($section_d['record']['lawyer']['add4']);
                        if (isset($section_d['record']['lawyer']['tel']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_tel                = $this->convertText($section_d['record']['lawyer']['tel']);
                        if (isset($section_d['record']['lawyer']['ref']))
                        $proposalCTOSLegalCase->TPCLS_lawyer_ref                = $this->convertText($section_d['record']['lawyer']['ref']);
                        if (isset($section_d['record']['cedcon']['name']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_name               = $this->convertText($section_d['record']['cedcon']['name']);
                        if (isset($section_d['record']['cedcon']['add1']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_add1               = $this->convertText($section_d['record']['cedcon']['add1']);
                        if (isset($section_d['record']['cedcon']['add2']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_add2               = $this->convertText($section_d['record']['cedcon']['add2']);
                        if (isset($section_d['record']['cedcon']['add3']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_add3               = $this->convertText($section_d['record']['cedcon']['add3']);
                        if (isset($section_d['record']['cedcon']['add4']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_add4               = $this->convertText($section_d['record']['cedcon']['add4']);
                        if (isset($section_d['record']['cedcon']['tel']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_tel                = $this->convertText($section_d['record']['cedcon']['tel']);
                        if (isset($section_d['record']['cedcon']['ref']))
                        $proposalCTOSLegalCase->TPCLS_cedcon_ref                = $this->convertText($section_d['record']['cedcon']['ref']);
                        if (isset($section_d['record']['liq']['name']))
                        $proposalCTOSLegalCase->TPCLS_liq_name                  = $this->convertText($section_d['record']['liq']['name']);
                        if (isset($section_d['record']['liq']['add1']))
                        $proposalCTOSLegalCase->TPCLS_liq_add1                  = $this->convertText($section_d['record']['liq']['add1']);
                        if (isset($section_d['record']['liq']['add2']))
                        $proposalCTOSLegalCase->TPCLS_liq_add2                  = $this->convertText($section_d['record']['liq']['add2']);
                        if (isset($section_d['record']['liq']['add3']))
                        $proposalCTOSLegalCase->TPCLS_liq_add3                  = $this->convertText($section_d['record']['liq']['add3']);
                        if (isset($section_d['record']['liq']['add4']))
                        $proposalCTOSLegalCase->TPCLS_liq_add4                  = $this->convertText($section_d['record']['liq']['add4']);
                        if (isset($section_d['record']['liq']['tel']))
                        $proposalCTOSLegalCase->TPCLS_liq_tel                   = $this->convertText($section_d['record']['liq']['tel']);
                        if (isset($section_d['record']['liq']['ref']))
                        $proposalCTOSLegalCase->TPCLS_liq_ref                   = $this->convertText($section_d['record']['liq']['ref']);
                        if (isset($section_d['record']['auctioner']['name']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_name            = $this->convertText($section_d['record']['auctioner']['name']);
                        if (isset($section_d['record']['auctioner']['add1']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_add1            = $this->convertText($section_d['record']['auctioner']['add1']);
                        if (isset($section_d['record']['auctioner']['add2']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_add2            = $this->convertText($section_d['record']['auctioner']['add2']);
                        if (isset($section_d['record']['auctioner']['add3']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_add3            = $this->convertText($section_d['record']['auctioner']['add3']);
                        if (isset($section_d['record']['auctioner']['add4']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_add4            = $this->convertText($section_d['record']['auctioner']['add4']);
                        if (isset($section_d['record']['auctioner']['tel']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_tel             = $this->convertText($section_d['record']['auctioner']['tel']);
                        if (isset($section_d['record']['auctioner']['ref']))
                        $proposalCTOSLegalCase->TPCLS_auctioner_ref             = $this->convertText($section_d['record']['auctioner']['ref']);
                        if (isset($section_d['record']['further_info_contact']['name']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_name = $this->convertText($section_d['record']['further_info_contact']['name']);
                        if (isset($section_d['record']['further_info_contact']['add1']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_add1 = $this->convertText($section_d['record']['further_info_contact']['add1']);
                        if (isset($section_d['record']['further_info_contact']['add2']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_add2 = $this->convertText($section_d['record']['further_info_contact']['add2']);
                        if (isset($section_d['record']['further_info_contact']['add3']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_add3 = $this->convertText($section_d['record']['further_info_contact']['add3']);
                        if (isset($section_d['record']['further_info_contact']['add4']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_add4 = $this->convertText($section_d['record']['further_info_contact']['add4']);
                        if (isset($section_d['record']['further_info_contact']['tel']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_tel  = $this->convertText($section_d['record']['further_info_contact']['tel']);
                        if (isset($section_d['record']['further_info_contact']['ref']))
                        $proposalCTOSLegalCase->TPCLS_further_info_contact_ref  = $this->convertText($section_d['record']['further_info_contact']['ref']);
                        if (isset($section_d['record']['settlement']['code']))
                        $proposalCTOSLegalCase->TPCLS_settlement_code           = $this->convertText($section_d['record']['settlement']['code']);
                        if (isset($section_d['record']['settlement']['date'])){
                        $settlement_date = ($section_d['record']['settlement']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['settlement']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_settlement_date           = $settlement_date ;
                        }
                        if (isset($section_d['record']['settlement']['source']))
                        $proposalCTOSLegalCase->TPCLS_settlement_source         = $this->convertText($section_d['record']['settlement']['source']) ;
                        if (isset($section_d['record']['settlement']['source_date'])){
                        $settlement_source_date   = ($section_d['record']['settlement']['source_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['settlement']['source_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_settlement_source_date    = $settlement_source_date;
                        }
                        if (isset($section_d['record']['latest_status']['code']))
                        $proposalCTOSLegalCase->TPCLS_latest_status_code        = $this->convertText($section_d['record']['latest_status']['code']);
                        if (isset($section_d['record']['latest_status']['date'])){
                        $latest_status_date = ($section_d['record']['latest_status']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['latest_status']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_latest_status_date        = $latest_status_date;
                        }
                        if (isset($section_d['record']['latest_status']['source']))
                        $proposalCTOSLegalCase->TPCLS_latest_status_source      = $this->convertText($section_d['record']['latest_status']['source']);
                        if (isset($section_d['record']['latest_status']['source_date'])){
                        $latest_status_source_date   = ($section_d['record']['latest_status']['source_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['latest_status']['source_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_latest_status_source_date = $latest_status_source_date;
                        }
                        if (isset($section_d['record']['latest_status']['exp_date'])){
                        $latest_status_exp_date   = ($section_d['record']['latest_status']['exp_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['latest_status']['exp_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_latest_status_exp_date = $latest_status_exp_date;
                        }
                        if (isset($section_d['record']['latest_status']['update_date'])){
                        $latest_status_update_date   = ($section_d['record']['latest_status']['update_date'] != null) ? carbon::parse($this->convertDate($section_d['record']['latest_status']['update_date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_latest_status_update_date = $latest_status_update_date;
                        }
                        if (isset($section_d['record']['other_defendants']))
                        $proposalCTOSLegalCase->TPCLS_other_defendants          = json_encode($section_d['record']['other_defendants']);
                        if (isset($section_d['record']['directors']))
                        $proposalCTOSLegalCase->TPCLS_directors                 = json_encode($section_d['record']['directors']);
                        if (isset($section_d['record']['subject_cmt']['amount']))
                        $proposalCTOSLegalCase->TPCLS_subject_cmt_comment       = $this->convertAmt($section_d['record']['subject_cmt']['amount']);
                        if (isset($section_d['record']['subject_cmt']['date'])){
                        $subject_cmt_date = ($section_d['record']['subject_cmt']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['subject_cmt']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_subject_cmt_date          = $subject_cmt_date ;
                        }
                        if (isset($section_d['record']['cra_cmt']['amount']))
                        $proposalCTOSLegalCase->TPCLS_cra_cmt_comment           = $this->convertAmt($section_d['record']['cra_cmt']['amount']);
                        if (isset($section_d['record']['cra_cmt']['date'])){
                        $cra_cmt_date  = ($section_d['record']['cra_cmt']['date'] != null) ? carbon::parse($this->convertDate($section_d['record']['cra_cmt']['date']))->format('Y-m-d') : null;
                        $proposalCTOSLegalCase->TPCLS_cra_cmt_date              = $cra_cmt_date;
                        }
                        if (isset($section_d['record']['withheld']['message']))
                        $proposalCTOSLegalCase->TPCLS_withheld_message          = $this->convertText($section_d['record']['withheld']['message']);

                        $proposalCTOSLegalCase->save();
                    }





















/*
                    $articles = $responseData->articles;

                    foreach($articles as $article) {
                        if(isset($responseData->articles)){
                        }


                    }
*/
                    $tenderProposal->TPCheckCTOS = 'Y';
                    $tenderProposal->save();

                }


        }

        private function convertDate($dateString){
            if (isset($dateString)){
                if (is_array($dateString)){
                    $newDate = '';
                }else{
                    $newDate = date("Y-m-d", strtotime($dateString));
                }
            }else{
                $newDate = '';
            }
            return $newDate;
        }

        private function convertAmt($value){
            if (isset($value)){
                if (is_array($value)){
                    $newValue = implode($value);
                }else{
                    $newValue = (float) $value;
                }
            }else{
                $newValue = null;
            }
            return $newValue;
        }

        private function convertText($value){
            if ($value == null){
                $newValue = null;
            }else{
                if (is_array($value)){
                    $newValue = implode($value);
                }else{
                    $newValue = $value;
                }
            }
            return $newValue;
        }
    }

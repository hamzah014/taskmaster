<?php

namespace App\Http\Controllers;

use App\Models\TenderProposalDetail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\FileAttach;
use App\Helper\Custom;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Auth;
use Image;
use Imagick;
use App\Models\AutoNumber;
use App\Models\BoardMeeting;
use App\Models\BoardMeetingEmail;
use App\Models\CertApp;
use App\Models\ClaimMeeting;
use App\Models\ClaimMeetingEmail;
use App\Models\Contractor;
use App\Models\IntegrateSSMLog;
use App\Models\EmailLog;
use App\Models\KickOffMeeting;
use App\Models\KickOffMeetingEmail;
use App\Models\Meeting;
use App\Models\MeetingDet;
use App\Models\MeetingEmail;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\WhatsappMessage;
use App\Services\DropdownService;
use Illuminate\Support\Facades\Auth as IlluminateAuth;
use Mail;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Session\Session;
use Yajra\DataTables\Facades\DataTables;

class MeetingEmailController extends Controller{

    
    public function __construct()
    {
        $this->subject = "Pemberitahuan Maklumat Mesyuarat";
    }

    public function blastEmail($id,$type){

        $user = Auth::user();
        $dropdownService = new DropdownService();
        $meetingLocation = $dropdownService->meetingLocation();

        if($type == 'M'){

            $meeting = Meeting::where('MNo',$id)->first();
            $meetingDet = MeetingDet::where('MD_MNo',$id)->first();

            $meetingType = $meetingDet->MD_MTCode;

            if($meetingType == 'EOT'){
                $emailType = "EOT Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Permohonan Lanjutan Masa";

            }
            
            else if($meetingType == 'VO'){
                $emailType = "VO Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Perubahan Kerja";
                
            }

            else if($meetingType == 'PTENDER'){
                $emailType = "PTD Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Projek Kerja";
                
            }

            else if($meetingType == 'MNP'){
                $emailType = "NP Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Rundingan Harga";
            }

            else if($meetingType == 'MPTA'){
                $emailType = "PTA Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Kelulusan Projek Kerja";
            }

            else if($meetingType == 'MPTE1'){
                $emailType = "PTE1 Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Pengesahan 1 Cadangan Projek";
            }

            else if($meetingType == 'MPTE2'){
                $emailType = "PTE2 Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Pengesahan 2 Cadangan Projek";
            }

            else if($meetingType == 'MLI'){
                $emailType = "LI Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat Surat Niat";
            }

            else if($meetingType == 'MEA'){
                $emailType = "EOTAJK Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat AJK Lanjutan Masa";
            }

            else if($meetingType == 'MVA'){
                $emailType = "VOAJK Meeting";
                $this->subject = "Pemberitahuan Maklumat Mesyuarat AJK Perubahan Kerja(VO)";
            }

            $meetingEmails = MeetingEmail::where('MAE_MNo',$id)->get();
            $recipients = array();

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverEmail = $meetingEmail->MAEEmailAddr;

                if (!in_array($receiverEmail, $recipients)) {

                    array_push($recipients, $receiverEmail);

                }

            }

            // Create an array of email addresses for the "To" header
            $toAddresses = array_values($recipients);
        
            // Convert the array of email addresses to a comma-separated string
            $to = implode(', ', $toAddresses);

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= $emailType;
            $emailLog->ELSentTo =  $to;

            // Send Email
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailData = array(
                'id' => $user->USID,
                'name'  => $user->USName ?? '',
                'email' => $toAddresses,
                'meetingNo' => $meeting->MNo,
                'meetingTitle' => $meeting->MTitle,
                'meetingLocation' => $meetingLocation[$meeting->M_LCCode],
                'meetingDate' => Carbon::parse($meeting->MDate)->format('d/m/Y'),
                'meetingTime' => Carbon::parse($meeting->MTime)->format('h:i A'),
            );

            try {
                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'])->subject($this->subject);
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();


        }
        else if($type == 'BM'){

            $boardMeeting = BoardMeeting::where('BMNo',$id)->first();
            $emailType = "Meeting Tender";
            $this->subject = "Pemberitahuan Maklumat Mesyuarat Tender";
            
            $meetingEmails = BoardMeetingEmail::where('BME_BMNo',$id)->get();
            $recipients = array();

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverEmail = $meetingEmail->BMEEmailAddr;

                if (!in_array($receiverEmail, $recipients)) {

                    array_push($recipients, $receiverEmail);
                    
                }

            }

            // Create an array of email addresses for the "To" header
            $toAddresses = array_values($recipients);
        
            // Convert the array of email addresses to a comma-separated string
            $to = implode(', ', $toAddresses);

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= $emailType;
            $emailLog->ELSentTo =  $to;

            // Send Email
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailData = array(
                'id' => $user->USID,
                'name'  => $user->USName ?? '',
                'email' => $toAddresses,
                'meetingNo' => $boardMeeting->BMNo,
                'meetingTitle' => $boardMeeting->BMTitle,
                'meetingLocation' => $meetingLocation[$boardMeeting->BM_LCCode],
                'meetingDate' => Carbon::parse($boardMeeting->BMDate)->format('d/m/Y'),
                'meetingTime' => Carbon::parse($boardMeeting->BMTime)->format('h:i A'),
            );

            try {
                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject($this->subject);
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();

        
        }
        else if($type == 'CM'){

            $claimMeeting = ClaimMeeting::where('CMNo',$id)->first();
            $emailType = "Meeting Tuntutan";
            $this->subject = "Pemberitahuan Maklumat Mesyuarat Tuntutan";
            
            $meetingEmails = ClaimMeetingEmail::where('CME_CMNo',$id)->get();
            $recipients = array();

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverEmail = $meetingEmail->CMEEmailAddr;

                if (!in_array($receiverEmail, $recipients)) {

                    array_push($recipients, $receiverEmail);
                    
                }

            }

            // Create an array of email addresses for the "To" header
            $toAddresses = array_values($recipients);
        
            // Convert the array of email addresses to a comma-separated string
            $to = implode(', ', $toAddresses);

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= $emailType;
            $emailLog->ELSentTo =  $to;

            // Send Email
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailData = array(
                'id' => $user->USID,
                'name'  => $user->USName ?? '',
                'email' => $toAddresses,
                'meetingNo' => $claimMeeting->CMNo,
                'meetingTitle' => $claimMeeting->CMTitle,
                'meetingLocation' => $meetingLocation[$claimMeeting->CM_LCCode],
                'meetingDate' => Carbon::parse($claimMeeting->CMDate)->format('d/m/Y'),
                'meetingTime' => Carbon::parse($claimMeeting->CMTime)->format('h:i A'),
            );

            try {
                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject($this->subject);
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();

        }
        else if($type == 'KOM'){

            $kickoffMeeting = KickOffMeeting::where('KOMNo',$id)->first();
            $emailType = "Meeting Permulaan";
            $this->subject = "Pemberitahuan Maklumat Mesyuarat Permulaan";
            
            $meetingEmails = KickOffMeetingEmail::where('KOME_KOMNo',$id)->get();
            $recipients = array();

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverEmail = $meetingEmail->KOMEEmailAddr;

                if (!in_array($receiverEmail, $recipients)) {

                    array_push($recipients, $receiverEmail);
                    
                }

            }

            // Create an array of email addresses for the "To" header
            $toAddresses = array_values($recipients);
        
            // Convert the array of email addresses to a comma-separated string
            $to = implode(', ', $toAddresses);

            $emailLog = new EmailLog();
            $emailLog->ELCB 	= $user->USCode;
            $emailLog->ELType 	= $emailType;
            $emailLog->ELSentTo =  $to;

            // Send Email
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;

            $emailData = array(
                'id' => $user->USID,
                'name'  => $user->USName ?? '',
                'email' => $toAddresses,
                'meetingNo' => $kickoffMeeting->KOMNo,
                'meetingTitle' => $kickoffMeeting->KOMTitle,
                'meetingLocation' => $meetingLocation[$kickoffMeeting->KOM_LCCode],
                'meetingDate' => Carbon::parse($kickoffMeeting->KOMMDate)->format('d/m/Y'),
                'meetingTime' => Carbon::parse($kickoffMeeting->KOMMTime)->format('h:i A'),
            );

            try {
                Mail::send(['html' => 'email.meetingNotification'], $emailData, function($message) use ($emailData) {
                    $message->to($emailData['email'] ,$emailData['name'])->subject($this->subject);
                });

                $emailLog->ELMessage = 'Success';
                $emailLog->ELSentStatus = 1;
            } catch (\Exception $e) {
                $emailLog->ELMessage = $e->getMessage();
                $emailLog->ELSentStatus = 2;
            }

            $emailLog->save();

            return $emailLog;

        }

    }

    public function blastWhatsapp($id,$type){

        $user = Auth::user();
        $dropdownService = new DropdownService();
        $meetingLocation = $dropdownService->meetingLocation();

        $custom = new Custom();

        if($type == 'M'){

            $meeting = Meeting::where('MNo',$id)->first();
            $meetingDet = MeetingDet::where('MD_MNo',$id)->first();

            $meetingType = $meetingDet->MD_MTCode;

            $message = $meeting->MTitle;
            $sendTime = Carbon::parse($meeting->MTime)->format('h:i A');
            $sendDate = Carbon::parse($meeting->MDate)->format('d/m/Y');
            $sendLocation = $meetingLocation[$meeting->M_LCCode] ?? "-";

            $meetingEmails = MeetingEmail::where('MAE_MNo',$id)->get();

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverPhone = $meetingEmail->MAEPhoneNo;
                $sendType = 'S';
                $whatsappMessage = new WhatsappMessage();

                if($receiverPhone){
    
                    try {
                        $result = $custom->sendWhatsappMeeting($receiverPhone,$message,$sendDate,$sendTime,$sendLocation);
        
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $result;
                        $whatsappMessage->WMType = $sendType;
                    } catch (\Exception $e) {
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $e->getMessage();
                        $whatsappMessage->WMType = $sendType;
                    }
                    $whatsappMessage->save();

                }

            }


        }
        else if($type == 'BM'){

            $boardMeeting = BoardMeeting::where('BMNo',$id)->first();

            $message = $boardMeeting->BMTitle;
            $sendTime = Carbon::parse($boardMeeting->BMTime)->format('h:i A');
            $sendDate = Carbon::parse($boardMeeting->BMDate)->format('d/m/Y');
            $sendLocation = $meetingLocation[$boardMeeting->BM_LCCode] ?? "-";

            $meetingEmails = BoardMeetingEmail::where('BME_BMNo',$id)->get();

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverPhone = $meetingEmail->BMEPhoneNo;
                $sendType = 'S';
                $whatsappMessage = new WhatsappMessage();

                if($receiverPhone){
    
                    try {
                        $result = $custom->sendWhatsappMeeting($receiverPhone,$message,$sendDate,$sendTime,$sendLocation);
        
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $result;
                        $whatsappMessage->WMType = $sendType;
                    } catch (\Exception $e) {
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $e->getMessage();
                        $whatsappMessage->WMType = $sendType;
                    }
                    $whatsappMessage->save();

                }

            }

        
        }
        else if($type == 'CM'){

            $claimMeeting = ClaimMeeting::where('CMNo',$id)->first();
            $meetingEmails = ClaimMeetingEmail::where('CME_CMNo',$id)->get();
            
            $message = $claimMeeting->CMTitle;
            $sendTime = Carbon::parse($claimMeeting->CMTime)->format('h:i A');
            $sendDate = Carbon::parse($claimMeeting->CMDate)->format('d/m/Y');
            $sendLocation = $meetingLocation[$claimMeeting->CM_LCCode] ?? "-";

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverPhone = $meetingEmail->CMEPhoneNo;
                $sendType = 'S';
                $whatsappMessage = new WhatsappMessage();

                if($receiverPhone){
    
                    try {
                        $result = $custom->sendWhatsappMeeting($receiverPhone,$message,$sendDate,$sendTime,$sendLocation);
        
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $result;
                        $whatsappMessage->WMType = $sendType;
                    } catch (\Exception $e) {
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $e->getMessage();
                        $whatsappMessage->WMType = $sendType;
                    }
                    $whatsappMessage->save();

                }

            }

        }
        else if($type == 'KOM'){

            $kickoffMeeting = KickOffMeeting::where('KOMNo',$id)->first();
            $meetingEmails = KickOffMeetingEmail::where('KOME_KOMNo',$id)->get();
            
            $message = $kickoffMeeting->KOMTitle;
            $sendTime = Carbon::parse($kickoffMeeting->KOMTime)->format('h:i A');
            $sendDate = Carbon::parse($kickoffMeeting->KOMDate)->format('d/m/Y');
            $sendLocation = $meetingLocation[$kickoffMeeting->KOM_LCCode] ?? "-";

            foreach($meetingEmails as $index => $meetingEmail){

                $receiverPhone = $meetingEmail->KOMEPhoneNo;
                $sendType = 'S';
                $whatsappMessage = new WhatsappMessage();

                if($receiverPhone){
    
                    try {
                        $result = $custom->sendWhatsappMeeting($receiverPhone,$message,$sendDate,$sendTime,$sendLocation);
        
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $result;
                        $whatsappMessage->WMType = $sendType;
                    } catch (\Exception $e) {
                        $whatsappMessage->WMMessage = $message;
                        $whatsappMessage->WMPhoneNoT = $receiverPhone;
                        $whatsappMessage->WMRespond = $e->getMessage();
                        $whatsappMessage->WMType = $sendType;
                    }
                    $whatsappMessage->save();

                }

            }

        }

    }




}

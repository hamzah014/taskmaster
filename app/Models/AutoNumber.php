<?php

namespace App\Models;

use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\User;

class AutoNumber extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSAutoNumber';
    protected $guarded = ['ANID'];
    protected $dates   = ['ANCD, ANMD'];

	protected $primaryKey = 'ANID';
    const CREATED_AT = 'ANCD';
    const UPDATED_AT = 'ANMD';

    public function generateRandomSHA256()
    {
        // Generate a random string
        $randomString = Str::random(32); // You can adjust the length of the random string as per your requirement.

        // Generate the SHA256 hash of the random string
        $sha256Hash = hash('sha256', $randomString);

        return $sha256Hash;
    }

    public function generateUserCode(){

        // initialize data
        $generate_number = null;
        $generateDocNo = true;
        $docType = 'US';

        $query = AutoNumber::query()->where('ANType',$docType)->lockforupdate()->first();

        if($query == null) {
            $runNo = 1;
            $runFormat = '00000000';
        }else{
            $runFormat = strlen($query->ANRunFormat);
            $runNo = $query->ANRun;
        }

        Do {
            $docNo = $query->ANPrefix.str_pad($runNo, $runFormat, '0', STR_PAD_LEFT).$query->ANSuffix;

            $recordCount = User::select('USID')->where('USCode',$docNo)->count();
            if ($recordCount == 0){
                if($query == null) {
                    $query = new AutoNumber();
                }
                $query->ANDesc = 'User';
                $query->ANType = $docType;
                $query->ANRun = $runNo + 1;
                $query->save();

                $generateDocNo = false;
            }else{
                $runNo = $runNo+1;
            }


        }while ($generateDocNo== true);

        $generate_number = $docNo;

        return $generate_number;
    }

    public function generateProjectCode(){

        // initialize data
        $generate_number = null;
        $generateDocNo = true;
        $docType = 'PJ';

        $query = AutoNumber::query()->where('ANType',$docType)->lockforupdate()->first();

        if($query == null) {
            $runNo = 1;
            $runFormat = '00000000';
        }else{
            $runFormat = strlen($query->ANRunFormat);
            $runNo = $query->ANRun;
        }

        Do {
            $docNo = $query->ANPrefix.str_pad($runNo, $runFormat, '0', STR_PAD_LEFT).$query->ANSuffix;

            $recordCount = Project::select('PJID')->where('PJCode',$docNo)->count();
            if ($recordCount == 0){
                if($query == null) {
                    $query = new AutoNumber();
                }
                $query->ANDesc = 'Project';
                $query->ANType = $docType;
                $query->ANRun = $runNo + 1;
                $query->save();

                $generateDocNo = false;
            }else{
                $runNo = $runNo+1;
            }


        }while ($generateDocNo== true);

        $generate_number = $docNo;

        return $generate_number;
    }

    public function generateProjectDocCode(){

        // initialize data
        $generate_number = null;
        $generateDocNo = true;
        $docType = 'PD';

        $query = AutoNumber::query()->where('ANType',$docType)->lockforupdate()->first();

        if($query == null) {
            $runNo = 1;
            $runFormat = '00000000';
        }else{
            $runFormat = strlen($query->ANRunFormat);
            $runNo = $query->ANRun;
        }

        Do {
            $docNo = $query->ANPrefix.str_pad($runNo, $runFormat, '0', STR_PAD_LEFT).$query->ANSuffix;

            $recordCount = ProjectDocument::select('PDID')->where('PDCode',$docNo)->count();
            if ($recordCount == 0){
                if($query == null) {
                    $query = new AutoNumber();
                }
                $query->ANDesc = 'Project Document';
                $query->ANType = $docType;
                $query->ANRun = $runNo + 1;
                $query->save();

                $generateDocNo = false;
            }else{
                $runNo = $runNo+1;
            }


        }while ($generateDocNo== true);

        $generate_number = $docNo;

        return $generate_number;
    }

    public function generateTaskCode(){

        // initialize data
        $generate_number = null;
        $generateDocNo = true;
        $docType = 'TP';

        $query = AutoNumber::query()->where('ANType',$docType)->lockforupdate()->first();

        if($query == null) {
            $runNo = 1;
            $runFormat = '00000000';
        }else{
            $runFormat = strlen($query->ANRunFormat);
            $runNo = $query->ANRun;
        }

        Do {
            $docNo = $query->ANPrefix.str_pad($runNo, $runFormat, '0', STR_PAD_LEFT).$query->ANSuffix;

            $recordCount = TaskProject::select('TPID')->where('TPCode',$docNo)->count();
            if ($recordCount == 0){
                if($query == null) {
                    $query = new AutoNumber();
                }
                $query->ANDesc = 'Task Project';
                $query->ANType = $docType;
                $query->ANRun = $runNo + 1;
                $query->save();

                $generateDocNo = false;
            }else{
                $runNo = $runNo+1;
            }


        }while ($generateDocNo== true);

        $generate_number = $docNo;

        return $generate_number;
    }

    public function generateUserToken(){

        $token = Str::random(60);

        return $token;


    }

}

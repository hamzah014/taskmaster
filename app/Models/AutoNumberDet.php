<?php

namespace App\Models;

use Carbon\Carbon;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\Models\ServiceApplication;
use App\Models\AutoNumber;
use App\User;

class AutoNumberDet extends Model implements Auditable
{
    use AuditableTrait;

    protected $table   = 'MSAutoNumberDet';
    protected $guarded = ['ANDID'];
    protected $dates   = ['ANDCD, ANDMD'];
	
	protected $primaryKey = 'ANDID';
    const CREATED_AT = 'ANDCD';
    const UPDATED_AT = 'ANDMD';
	
	public function generateServiceApplicationNo(){

		// initialize data
		$generate_number = null;
		$generateDocNo = true;
		$month = carbon::now()->format('m');
		$year = carbon::now()->format('y');
		$docType = 'SA';
		
		$autoNumber = AutoNumber::where('ANType', $docType)->first();
		if ($autoNumber == null){
			return $generate_number;
		}
		$runFormat = strlen($autoNumber->ANRunFormat);
		
		$query = $this->where('ANDType',$docType)->where('ANDYear',$year)->where('ANDMonth',$month)->first();

		if($query == null) {
			$runNo = 1;
		}else{
			$runNo = $query->ANDRun;
		}
			
		Do {
			$docNo = $autoNumber->ANPrefix.$year.$month.str_pad($runNo, $runFormat, '0', STR_PAD_LEFT).$autoNumber->ANSuffix;
		
			$recordCount = ServiceApplication::where('SANo',$docNo)->count();
			if ($recordCount == 0){
				if($query == null) {
					$this->create([
						'ANDType' => $docType,
						'ANDYear' => $year,
						'ANDMonth' => $month,
						'ANDRun' => $runNo + 1
					]);
				}else{
					$query->update([
						'ANDRun' => $runNo + 1
					]);
				}
				$generateDocNo = false;
			}else{
				$runNo = $runNo+1;
			}
			
		}while ($generateDocNo== true);
		
		$generate_number = $docNo;
		
		return $generate_number;
	}
	
	
	
	public function generateReceiptNo(){

		// initialize data
		$generate_number = null;
		$generateDocNo = true;
		$month = carbon::now()->format('m');
		$year = carbon::now()->format('y');
		$docType = 'RC';
		
		$autoNumber = AutoNumber::where('ANType',$docType)->first();
		if ($autoNumber == null){
			return $generate_number;
		}
		$runFormat = strlen($autoNumber->ANRunFormat);
		
		$query = $this->where('ANDType',$docType)->where('ANDYear',$year)->where('ANDMonth',$month)->first();

		if($query == null) {
			$runNo = 1;
		}else{
			$runNo = $query->ANDRun;
		}
			
		Do {
			$docNo = $autoNumber->ANPrefix.$year.$month.str_pad($runNo, $runFormat, '0', STR_PAD_LEFT).$autoNumber->ANSuffix;
		
			$recordCount = Receipt::where('RCNo',$docNo)->count();
			if ($recordCount == 0){
				if($query == null) {
					$this->create([
						'ANDType' => $docType,
						'ANDYear' => $year,
						'ANDMonth' => $month,
						'ANDRun' => $runNo + 1
					]);
				}else{
					$query->update([
						'ANDRun' => $runNo + 1
					]);
				}
				$generateDocNo = false;
			}else{
				$runNo = $runNo+1;
			}
			
		}while ($generateDocNo== true);
		
		$generate_number = $docNo;
		
		return $generate_number;
	}
}

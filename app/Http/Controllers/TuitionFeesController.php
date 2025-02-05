<?php

namespace App\Http\Controllers;

use App\Models\Tuition_Fees;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTuition_FeesRequest;
use App\Http\Requests\UpdateTuition_FeesRequest;
use Illuminate\Http\JsonResponse;

class TuitionFeesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tuitionfees = Tuition_Fees::all();
        return $tuitionfees;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Validate incoming request data
        $request->validate([
            'LRN' => 'required|integer',
            'tuition' => 'required|numeric',
            'general' => 'nullable|numeric',
            'esc' => 'nullable|numeric',
            'subsidy' => 'nullable|numeric',
            'req_downpayment' => 'nullable|numeric',
        ]);

        // Retrieve enrollment details by LRN
        $enrollment = Enrollment::where('LRN', $request->LRN)->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }

        $gradeLevel = $enrollment->grade_level;

        $tuitionFee = Tuition_Fees::create([
            'grade_level' => $gradeLevel,
            'tuition' => $request->tuition,
            'general' => $request->general,
            'esc' => $request->esc,
            'subsidy' => $request->subsidy,
            'req_downpayment' => $request->req_downpayment,
        ]);

        return response()->json($tuitionFee, 201); 
    }

    public function getTuitionDetails($LRN): JsonResponse
    {
        // Fetch enrollment record by LRN
        $enrollment = Enrollment::where('LRN', $LRN)->first();
    
        // Check if enrollment exists
        if (!$enrollment) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }
    
        // Initialize tuition details array
        $tuitionDetails = [
            'grade_level' => $enrollment->grade_level,
            'tuition' => null,
            'general' => null,
            'esc' => null,
            'subsidy' => null,
            'req_downpayment' => null,
            'old_account' => $enrollment->old_account, // Add old_account here
        ];
    
        // Fetch tuition data based on grade level
        $tuitionData = Tuition_Fees::where('grade_level', $enrollment->grade_level)->first();
    
        // Check if tuition data exists
        if ($tuitionData) {
            $tuitionDetails['tuition'] = $tuitionData->tuition; 
            $tuitionDetails['general'] = $tuitionData->general; 
            $tuitionDetails['subsidy'] = $tuitionData->subsidy; 
            $tuitionDetails['req_downpayment'] = $tuitionData->req_downpayment;
    
            // Determine ESC value based on enrollment type
            if ($enrollment->type === 'private') {
                $tuitionDetails['esc'] = 14000; // ESC for private students
            } else {
                $tuitionDetails['esc'] = 17500; // ESC for public students
            }
        } else {
            return response()->json(['message' => 'Tuition details not found'], 404);
        }
    
        return response()->json($tuitionDetails);
    }
    
    
    

    /**
     * Display the specified resource.
     */
    public function show(Tuition_Fees $tuition_Fees)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTuition_FeesRequest $request, Tuition_Fees $tuition_Fees)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tuition_Fees $tuition_Fees)
    {
        //
    }

    
}

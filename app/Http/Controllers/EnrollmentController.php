<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $enrollments = Enrollment::all();
        return $enrollments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEnrollmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
   public function show($LRN)
    {
        $enrollment = Enrollment::where('LRN', $LRN)->first();

        if (!$enrollment) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }

        return response()->json($enrollment);
    }

    public function getEnrollmentById($LRN)
    {
        $student = Student::where('LRN', $LRN)->first();

        if (!$student) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }

        return response()->json($student);
    }

    /**
     * Update the specified resource in storage.
     */
    

     public function create(Request $request)
     {
         // Validate incoming request data
         $formFields = $request->validate([
             'LRN' => 'required|integer|min:12', // Ensure LRN is valid
             'last_attended' => 'required|string|max:255',
             'public_private' => 'required|string|max:10',
             'guardian_name' => 'required|string|max:255',
             'grade_level' => 'required|string|max:50',
             'strand' => 'nullable|string|max:100',
             'school_year' => 'required|string|max:100',
             'date_register' => 'nullable|date_format:Y-m-d', // Make this nullable if you want to set it to now
         ]);
     
         // Add the current date to the form fields for date_register if not provided
         if (empty($formFields['date_register'])) {
             $formFields['date_register'] = now(); // Set current date
         }
     
         // Check if an enrollment record already exists for the given LRN
         $enrollment = Enrollment::where('LRN', $formFields['LRN'])->first();
     
         if ($enrollment) {
             // Update the existing enrollment record
             $enrollment->update($formFields);
             return response()->json(['message' => 'Enrollment updated successfully', 'data' => $enrollment], 200);
         } else {
             // Create a new enrollment record
             $enrollment = Enrollment::create($formFields);
             return response()->json(['message' => 'Enrollment created successfully', 'data' => $enrollment], 201);
         }
     }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Enrollment $enrollment)
    {
        //
    }

    public function getEnrollmentStatus($id)
    {
        // Fetch student data based on LRN or ID
        $student = Student::where('LRN', $id)->first();
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        // Fetch enrollment data
        $enrollment = Enrollment::where('LRN', $id)->first();
        $payment = Payment::where('LRN', $id)->first();

        return response()->json([
            'student' => $student,
            'enrollment' => $enrollment,
            'payment' => $payment,
        ]);
    }

}

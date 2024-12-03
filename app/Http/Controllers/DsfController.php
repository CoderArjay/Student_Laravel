<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Students;
use App\Models\Enrollments;
use App\Models\Payments;
use App\Models\Tuitions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB;
// use App\Http\Requests\StoredsfRequest;
// use App\Http\Requests\UpdatedsfRequest;

class DsfController extends Controller
{
    public function register(Request $request){
        $formField = $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed',
        ]);

        $formField['password'] = Hash::make($formField['password']);
        Admin::create($formField);
        return $request;
    }
    
    public function login(Request $request) {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required'
        ]);
    
        $user = Admin::where('email', $validated['email'])->first();
    
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect'
            ], 401);
        }

        $token = $user->createToken($user->fname);
    
        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken
        ], 200);
    }
    
    public function logout(Request $request) {
        $request->user()->tokens()->delete();
    
        return response()->json([
            'message' => 'You are logged out'
        ], 200);
    }

    public function display() {
        $data = DB::table('enrollments')
            ->join('students', 'enrollments.LRN', '=', 'students.LRN')
            ->join('payments', 'students.LRN', '=', 'payments.LRN')
            ->join('tuition_and_fees', 'enrollments.grade_level', '=', 'tuition_and_fees.grade_level')
            ->select(
                'students.LRN',
                'students.lname',
                'students.fname',
                'students.mname',
                'students.suffix',
                'students.gender',
                'students.address',
                'enrollments.grade_level',
                'students.contact_no',
                'enrollments.date_register',
                'enrollments.guardian_name',
                'enrollments.public_private',
                'enrollments.school_year',
                'enrollments.regapproval_date',
                'enrollments.payment_approval',
                'payments.OR_number',
                'payments.amount_paid',
                'payments.proof_payment',
                'payments.date_of_payment',
                'payments.description',
                'tuition_and_fees.tuition', 
                DB::raw('tuition_and_fees.tuition - payments.amount_paid AS remaining_balance') 
            )
            ->get();
        
        return response()->json($data, 200);
    }
    
    public function receiptdisplay(Request $request, $id) {
        $data = DB::table('enrollments')
            ->join('students', 'enrollments.LRN', '=', 'students.LRN')
            ->join('payments', 'students.LRN', '=', 'payments.LRN')
            ->join('tuition_and_fees', 'enrollments.grade_level', '=', 'tuition_and_fees.grade_level')
            ->select(
                'students.LRN',
                'students.lname',
                'students.fname',
                'students.mname',
                'students.suffix',
                'students.gender',
                'students.address',
                'enrollments.grade_level',
                'enrollments.contact_no',
                'enrollments.date_register',
                'enrollments.guardian_name',
                'enrollments.public_private',
                'enrollments.school_year',
                'enrollments.regapproval_date',
                'payments.OR_number',
                'payments.amount_paid',
                'payments.proof_payment',
                'payments.date_of_payment',
                'payments.description',
                'tuition_and_fees.tuition',
                DB::raw('tuition_and_fees.tuition - payments.amount_paid AS remaining_balance') // Calculate remaining balance
            )
            ->where('students.LRN', $id) // Filter by student ID
            ->first(); // Use first() to get a single record
        
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['message' => 'Student not found'], 404);
        }
    }

    public function approveEnrollment(Request $request, $id){
        $data = DB::table('enrollments')
            ->join('students', 'enrollments.LRN', '=', 'students.LRN')
            ->join('payments', 'students.LRN', '=', 'payments.LRN')
            ->join('tuition_and_fees', 'enrollments.grade_level', '=', 'tuition_and_fees.grade_level')
            ->select(
                'students.LRN',
                'students.lname',
                'students.fname',
                'students.mname',
                'students.suffix',
                'students.gender',
                'students.address',
                'enrollments.grade_level',
                'enrollments.contact_no',
                'enrollments.date_register',
                'enrollments.guardian_name',
                'enrollments.public_private',
                'enrollments.school_year',
                'enrollments.regapproval_date',
                'enrollments.payment_approval',
                'payments.OR_number',
                'payments.amount_paid',
                'payments.proof_payment',
                'payments.date_of_payment',
                'payments.description',
                'tuition_and_fees.tuition',
                DB::raw('tuition_and_fees.tuition - payments.amount_paid AS remaining_balance') // Calculate remaining balance
            )
            ->where('students.LRN', $id) // Filter by student ID
            ->update(['payment_approval' => 'Approve']);
            // ->first(); // Use first() to get a single record
        
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['message' => 'Student not found'], 404);
        }

    }

    public function displayStudent(){
        $data = DB::table('enrollments')
            ->join('students', 'enrollments.LRN', '=', 'students.LRN')
            ->join('payments', 'students.LRN', '=', 'payments.LRN')
            ->join('tuition_and_fees', 'enrollments.grade_level', '=', 'tuition_and_fees.grade_level')
            ->select(
                'students.LRN',
                'students.lname',
                'students.fname',
                'students.mname',
                'students.suffix',
                'students.gender',
                'students.address',
                'enrollments.grade_level',
                'enrollments.contact_no',
                'enrollments.date_register',
                'enrollments.guardian_name',
                'enrollments.public_private',
                'enrollments.school_year',
                'enrollments.regapproval_date',
                'enrollments.payment_approval',
                'payments.OR_number',
                'payments.amount_paid',
                // 'payments.proof_payment',
                'payments.date_of_payment',
                'payments.description',
                'tuition_and_fees.tuition',
                DB::raw('tuition_and_fees.tuition - payments.amount_paid AS remaining_balance') 
            )
            ->get();
        
        return response()->json($data, 200);
        return response()->json(students::all(), 200);
    }

    public function displaygrade(){
        return response()->json(tuitions_and_fees::orderBy('grade_level','asc')->get(),200);
    }

    public function displaySOA(Request $request, $LRN) {
        $data = DB::table('students')
            ->join('enrollments', 'enrollments.LRN', '=', 'students.LRN')
            ->leftJoin('payments', 'students.LRN', '=', 'payments.LRN')
            ->leftJoin('financial_statements', 'students.LRN', '=', 'financial_statements.LRN')
            ->leftJoin('tuition_and_fees', 'enrollments.grade_level', '=', 'tuition_and_fees.grade_level') // Join with tuitions
            ->select(
                'students.LRN',
                'students.lname',
                'students.fname',
                'students.mname',
                'students.suffix',
                'students.gender',
                'students.address',
                'enrollments.grade_level',
                'enrollments.contact_no',
                'enrollments.date_register',
                'enrollments.guardian_name',
                'enrollments.public_private',
                'enrollments.school_year',
                'enrollments.regapproval_date',
                'enrollments.payment_approval',
                'payments.OR_number',
                'payments.amount_paid',
                'payments.proof_payment',
                'payments.date_of_payment',
                'payments.description',
                'tuition_and_fees.tuition',
                'financial_statements.*', 
                DB::raw('IFNULL(tuition_and_fees.tuition, 0) - IFNULL(payments.amount_paid, 0) AS remaining_balance') // Calculate remaining balance
            )
            ->where('students.LRN', $LRN) // Filter by student ID
            ->get(); // Use get() to get all records
            
        if ($data->isNotEmpty()) {
            return response()->json($data, 200);
        } else {
            return response()->json(['message' => 'Student not found'], 404);
        }
    }
    
    public function updatepayment(Request $request, $id) {
        $request->validate([
            'OR_number' => "required|string",
            'description' => 'required|string',
            'amount_paid' => 'required|numeric',
            'LRN' => 'required|string',
        ]);
    
        // Find the payment record by LRN
        $payment = DB::table('payments')->where('LRN', $id)->first();
    
        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
    
        // Update the payment record
        DB::table('payments')->where('LRN', $id)->update([
            'OR_number' => $request->OR_number,
            'description' => $request->description,
            'amount_paid' => $request->amount_paid,
            'LRN' => $request->LRN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        // Retrieve the updated payments
        $payments = DB::table('payments')->orderBy('created_at', 'desc')->get();
    
        return response()->json([
            'message' => 'Success',
            'data' => $payments,
        ], 200);
    }      

    //for msg section....
    public function sendMessage(Request $request)
        {
            $message = new Message();
            $message->text = $request->input('text');
            $message->sender = $request->input('sender');
            $message->receiver = $request->input('receiver');
            $message->save();
            return response()->json(['message' => 'Message sent successfully']);
        }

        public function getMessages()
        {
            $messages = Message::all();
            return response()->json($messages);
        }
}

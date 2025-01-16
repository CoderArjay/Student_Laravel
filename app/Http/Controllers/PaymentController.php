<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon; // Import Carbon


class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::all();
        return $payments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Validate incoming request data
    $request->validate([
        'LRN' => 'required|string',
        'amount_paid' => 'nullable|numeric',
        'proof_payment' => 'nullable|image|mimes:jpeg,png,jpg,gif,jfif|max:2048',
        'description' => 'nullable|string',
        'date_of_payment' => 'nullable|date' // Ensure this is validated as a date
    ]);

    // Generate a unique OR_number
    $orNumber = $this->generateAndStoreOrNumber();

    // Get the LRN from the request
    $lrn = $request->LRN;

    // Fetch the student data to get the last name
    $student = Student::where('LRN', $lrn)->first();

    // Initialize variables for optional fields
    $filename = null;
    $path = null;

    // Store the uploaded image file with a custom name if it exists
    if ($request->hasFile('proof_payment')) {
        $file = $request->file('proof_payment');
        
        // Create a new filename using the student's last name and LRN
        $filename = "{$student->lname}_{$lrn}." . $file->getClientOriginalExtension(); // Get original extension

        // Store the file in the public disk with the new name
        $path = $file->storeAs('', $filename, 'public');
    }

    // Create a new payment record
    $payment = Payment::create([
        'LRN' => $lrn,
        'OR_number' => $orNumber,
        'amount_paid' => $request->amount_paid, // This will be null if not provided
        'proof_payment' => $path, // This will be null if no file was uploaded
        'description' => $request->description, // This will be null if not provided
        'date_of_payment' => $request->date_of_payment ? Carbon::parse($request->date_of_payment) : null // Convert to Carbon instance or null
    ]);

    return response()->json(['message' => 'Payment proof uploaded successfully!', 'data' => $payment], 201);
}

    public function getPaymentHistory($lrn)
    {
        // Fetch payment records for the given LRN
        $payments = Payment::where('LRN', $lrn)
            ->select('proof_payment', 'amount_paid', 'date_of_payment', 'created_at') // Include created_at for date formatting
            ->get();

        if ($payments->isEmpty()) {
            return response()->json(['message' => 'No payment history found for this student.'], 404);
        }

        // Map through payments to format the response
        $formattedPayments = $payments->map(function ($payment) {
            $proofPaymentUrl = asset('storage/' . $payment->proof_payment); // Construct proof of payment URL

            return [
                'date_of_payment' => $payment->created_at->format('Y-m-d'), // Format date of payment
                'amount_paid' => $payment->amount_paid,
                'proof_payment' => $proofPaymentUrl, // Include proof of payment URL
            ];
        });

        return response()->json($formattedPayments, 200);
    }


    public function getPaymentDetailsWithProof($lrn)
{
    // Fetch payment details for the given LRN
    $payment = Payment::where('LRN', $lrn)->first();

    if (!$payment) {
        return response()->json(['message' => 'Payment not found'], 404);
    }

    // Fetch enrollment details for the given LRN
    $enrollment = Enrollment::where('LRN', $lrn)->first(); // Assuming Enrollment is your model name

    if (!$enrollment) {
        return response()->json(['message' => 'Enrollment not found'], 404);
    }

    // Check if payment_approval is null
    $paymentApprovalStatus = $enrollment->payment_approval; // Get payment approval status

    // Construct the full URL for proof of payment
    $proofPaymentUrl = asset('storage/' . $payment->proof_payment); // Assuming proof_payment stores the path relative to storage

    return response()->json([
        'date_of_payment' => $payment->created_at->format('Y-m-d'), // Format date of payment
        'amount_paid' => $payment->amount_paid,
        'proof_payment' => $proofPaymentUrl, // Include proof of payment URL
        'payment_approval' => $paymentApprovalStatus // Include payment approval status
    ], 200);
}

    private function generateAndStoreOrNumber()
    {
        do {
            // Generate a random string of 10 characters for OR_number
            $orNumber = strtoupper(Str::random(10));
        } while (Payment::where('OR_number', $orNumber)->exists()); // Ensure uniqueness

        return $orNumber; // Return the unique OR_number
    }


    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }

    public function newPayment(Request $request)
{
    // Validate incoming request data
    $validator = Validator::make($request->all(), [
        'LRN' => 'required|string|max:255',
        'amount_paid' => 'required|numeric|min:0',
        'proof_payment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'description' => 'required|string|max:255',
        'date_of_payment' => 'required|date',
    ]);

    // Check for validation errors
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Get the LRN from the request
    $lrn = $request->LRN;

    // Fetch the student data to get the last name
    $student = Student::where('LRN', $lrn)->first();

    if (!$student) {
        return response()->json(['message' => 'Student not found.'], 404);
    }

    // Generate a unique OR_number
    $orNumber = $this->generateAndStoreOrNumber();

    // Store the uploaded image file with a custom name
    $file = $request->file('proof_payment');
    
    // Create a new filename using the student's last name and LRN
    $filename = "{$student->lname}_{$lrn}." . $file->getClientOriginalExtension(); // Get original extension

    // Store the file in the public disk with the new name
    $path = $file->storeAs('', $filename, 'public');

    // Create a new payment record
    $payment = Payment::create([
        'LRN' => $lrn,
        'OR_number' => $orNumber,
        'amount_paid' => $request->amount_paid,
        'proof_payment' => $path,
        'description' => $request->description,
        'date_of_payment' => $request->date_of_payment, // Use the date from the request
    ]);

    return response()->json(['message' => 'Payment uploaded successfully!', 'data' => $payment], 201);
}
}

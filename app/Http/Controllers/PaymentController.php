<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Support\Str;

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
            'proof_payment' => 'required|image|mimes:jpeg,png,jpg,gif,jfif|max:2048',
            'description' => 'nullable|string',
            'date_of_payment' => 'required|date'
        ]);

        // Generate a unique OR_number
        $orNumber = $this->generateAndStoreOrNumber();

        // Get the LRN from the request
        $lrn = $request->LRN;

        // Store the uploaded image file with a custom name
        $file = $request->file('proof_payment');
        
        // Create a new filename using the LRN
        $filename = '' . $lrn . '.' . $file->getClientOriginalExtension(); // Get original extension

        // Store the file in the public disk with the new name
        $path = $file->storeAs('payments', $filename, 'public');

        // Create a new payment record in the database
        $payment = Payment::create([
            'LRN' => $lrn,
            'OR_number' => $orNumber,
            'amount_paid' => $request->amount_paid,
            'proof_payment' => $path,
            'description' => $request->description,
            'date_of_payment' =>$request->now
        ]);

        return response()->json(['message' => 'Payment proof uploaded successfully!', 'data' => $payment], 201);
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
}

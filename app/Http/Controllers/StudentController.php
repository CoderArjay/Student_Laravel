<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::all()->map(function ($student) {
            $student->profile = Storage::url($student->profile); // Ensure this returns a full URL
            return $student;
        });
    
        return response()->json($students);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){ 
        //
    }

    public function enrollment(Request $request) // Sign Up
    {
        // Validate incoming request data
        $formField = $request->validate([
            'LRN' => 'required|integer|unique:students,LRN|min:11', 
            'fname' => 'required|string|max:255', 
            'lname' => 'required|string|max:255', 
            'mname' => 'required|string|max:255',
            'bdate' => 'required|date',
            'email' => 'required|email|max:255|unique:students,email',
            'password' => 'required|string',
            // 'confirm_password' => 'required|string'
            
        ]);

        DB::transaction(function () use ($formField, $request) {
            // Insert into the students table
            $student = Student::create($formField);
        });

        return response()->json(['message' => 'Student enrolled successfully'], 201);
    }
        
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:students,email', // Ensure you're checking against the correct column
            'password' => 'required|string|min:8'
        ]);

        // Fetch student along with enrollment data
        $student = Student::with('enrollment') // Eager load the enrollment relationship
            ->where('email', $request->email)
            ->first();

        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create token for the student
        $token = $student->createToken($student->fname)->plainTextToken;

        return response()->json([
            'student' => $student,
            'token' => $token,
            'id'=> $student->LRN
        ]);
    }

    public function Studentlogout(Request $request){
        $request->user()->tokens()->delete();
        return[
            'message' => 'You are logged out'
        ];
    }

    public function getStudentById($LRN)
    {
        // Use Query Builder to fetch data
        $student = DB::table('students')
            // ->join('students', 'students.LRN', '=', 'students.LRN')
            ->select(
                'students.*'
            )
            // ->where('students.LRN', $LRN)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student);
    }

    public function uploadProfile(Request $request)
{
    $request->validate([
        'student_pic' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'LRN' => 'required|exists:students,LRN'
    ]);

    try {
        // Find the student by LRN
        $student = Student::where('LRN', $request->input('LRN'))->firstOrFail();
        $image = $request->file('student_pic');

        // Create a unique filename using student's LRN and current timestamp
        $imageName = "{$student->lname}_" . time() . '.' . $image->getClientOriginalExtension();

        // Store the uploaded image in the 'public/profiles' directory
        $path = $image->storeAs('profiles', $imageName, 'public');

        // Delete the old image if it exists
        if ($student->student_pic && Storage::disk('public')->exists("profiles/{$student->student_pic}")) {
            Storage::disk('public')->delete("profiles/{$student->student_pic}");
        }

        // Update the student profile with the new image name
        $student->update(['student_pic' => $imageName]); // Update student_pic instead of LRN

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'image_url' => url("storage/profiles/{$imageName}") // Generate URL for the image
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Image upload failed: ' . $e->getMessage()], 500);
    }
}

    public function getProfileImage($lrn){
    $student = Student::where('LRN', $lrn)->first();

    if(!$student || !$student->student_pic){
        return response()->json(['message' => 'Profile image not found'], 404);
    }

    $baseURL = 'http://localhost:8000';
    $imagePath = $student->student_pic;

    $fullImageUrl = $baseURL . '/storage/' . $imagePath;
    return response()->json(['image_url' => $fullImageUrl], 200);

}

    /**
     * Display the specified resource.
     */
    public function show($LRN)
    {
        // Use Query Builder to fetch data
        $student = DB::table('students')
            // ->join('students', 'students.LRN', '=', 'students.LRN')
            ->select(
                'students.*', 
                // 'enrollments.grade_level'
                ) // Adjust fields as necessary
            ->where('students.LRN', $LRN)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $formFields = $request->validate([
            'LRN' => 'required|exists:students',
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'bdate' => 'nullable|date',
            'bplace' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_no' => 'nullable|max:11',
        ]);
    
        $student->update($formFields);
        return response()->json($student, 200);
    }

    public function updatePassword(Request $request){
        // Validate incoming request
        $request->validate([
            'LRN' => 'required|integer|exists:students,LRN',
            'oldPassword' => 'nullable|string', // Make oldPassword optional
            'newPassword' => 'nullable|string|min:8|confirmed', // Allow newPassword to be optional
            'fname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:students,email,' . $request->LRN . ',LRN', // Check uniqueness for email
            'address' => 'required|string|max:255',
        ]);

        // Retrieve user
        $user = Student::find($request->LRN);

        // If old password is provided, check it
        if ($request->oldPassword && !Hash::check($request->oldPassword, $user->password)) {
            return response()->json(['message' => 'Wrong password'], 401);
        }

        // Update user details
        if ($request->newPassword) {
            $user->password = Hash::make($request->newPassword); // Update password if provided
        }
        
        $user->fname = $request->fname;
        $user->mname = $request->mname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->address = $request->address;

        $user->save(); // Save all changes

        return response()->json(['message' => 'User details updated successfully']);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }


    public function checkStudentInfo($lrn)
    {
        // Check if a student with the given LRN exists in the database
        $exists = Student::where('LRN', $lrn)->exists();

        // Return a JSON response with the result
        return response()->json($exists);
    }

    public function getStudentData($lrn)
    {
        // Use Query Builder to fetch student and enrollment data
        $student = DB::table('students')
            ->leftJoin('enrollments', 'students.LRN', '=', 'enrollments.LRN') // Join with enrollments table
            ->leftJoin('payments', 'students.LRN', '=', 'payments.LRN')
            ->select(
                'students.*', 
                'enrollments.*',
                'payments.proof_payment', 
                )
            ->where('students.LRN', $lrn)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student, 200);
    }
}